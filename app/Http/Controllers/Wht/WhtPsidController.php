<?php

namespace App\Http\Controllers\Wht;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Wht\Concerns\ResolvesWhtCompany;
use App\Models\WhtSetting;
use App\Services\Wht\WhtPsidBatcher;
use App\Services\Wht\WhtPsidWorkbook;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

/**
 * Prepare PSID — the whole deposit cycle for one tax period, in one screen.
 *
 * The firm raises two PSIDs a month per agent: one covering all vendor
 * withholding (across whatever sections apply) and one covering salaries. So a
 * "batch" here is a kind + a tax period, not a section.
 *
 * Download the upload file, take it to IRIS, paste the PSID back, and later the
 * CPR — each stamped across every transaction in the batch at once, which is
 * the part that previously meant editing dozens of rows by hand.
 */
class WhtPsidController extends Controller
{
    use ResolvesWhtCompany;

    private const KINDS = WhtPsidBatcher::KINDS;

    public function __construct(private WhtPsidBatcher $batcher)
    {
    }

    public function index(Request $request)
    {
        $company = $this->currentCompany();

        $month = $request->get('month', now()->subMonth()->format('Y-m'));
        $monthStart = Carbon::createFromFormat('Y-m-d', $month . '-01')->startOfMonth();

        $batches = [];

        foreach (self::KINDS as $kind) {
            $batches[$kind] = $this->batch($company, $monthStart, $kind);
        }

        $layoutJson = json_encode(WhtPsidWorkbook::layout(), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        $layoutIsCustom = (bool) WhtSetting::get(WhtPsidWorkbook::SETTING_KEY);

        return view('wht.psid.index', compact(
            'company', 'month', 'monthStart', 'batches', 'layoutJson', 'layoutIsCustom'
        ));
    }

    public function download(Request $request, string $kind)
    {
        @set_time_limit(180);

        abort_unless(in_array($kind, self::KINDS, true), 404);

        $company = $this->currentCompany();
        $monthStart = $this->monthFrom($request);

        $missing = array_values(array_filter(
            ['zip', 'xmlwriter', 'dom', 'simplexml', 'mbstring'],
            fn($e) => !extension_loaded($e)
        ));

        if ($missing) {
            return back()->with('error', 'Excel export needs these PHP extensions, not enabled on the server: ' . implode(', ', $missing) . '.');
        }

        $batch = $this->batch($company, $monthStart, $kind);

        if ($batch['rows']->isEmpty()) {
            return back()->with('error', 'Nothing to upload for ' . $monthStart->format('F Y') . '.');
        }

        $book = (new WhtPsidWorkbook())->build($company, $monthStart, $kind, $batch['rows']);

        $filename = sprintf(
            'PSID-%s-%s-%s.xlsx',
            str($company->name)->slug(),
            $kind === 'salaries' ? 'salaries' : 'vendors',
            $monthStart->format('Y-m')
        );

        return response()->streamDownload(function () use ($book) {
            $writer = new Xlsx($book);
            $writer->setPreCalculateFormulas(false);
            $writer->save('php://output');
            $book->disconnectWorksheets();
        }, $filename, [
            'Content-Type'  => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Cache-Control' => 'max-age=0, no-store',
        ]);
    }

    /** Stamp the PSID IRIS returned across every transaction in the batch. */
    public function assignPsid(Request $request, string $kind)
    {
        abort_unless(in_array($kind, self::KINDS, true), 404);

        $company = $this->currentCompany();
        $this->authorizeAbility('edit', $company);

        $validated = $request->validate(['psid_no' => 'required|string|max:50']);
        $monthStart = $this->monthFrom($request);

        $query = $this->query($company, $monthStart, $kind);

        // Flag entries already carrying a different PSID rather than silently
        // relabelling someone else's challan.
        $conflicting = (clone $query)
            ->whereNotNull('psid_no')->where('psid_no', '!=', '')
            ->where('psid_no', '!=', $validated['psid_no'])
            ->count();

        $affected = $query->update(['psid_no' => $validated['psid_no'], 'updated_at' => now()]);

        $note = $conflicting
            ? " {$conflicting} entr" . ($conflicting === 1 ? 'y' : 'ies') . ' previously had a different PSID and were reassigned.'
            : '';

        return back()->with('success', "PSID {$validated['psid_no']} assigned to {$affected} entries.{$note}");
    }

    /** Record the CPR once the challan has been paid. */
    public function assignCpr(Request $request, string $kind)
    {
        abort_unless(in_array($kind, self::KINDS, true), 404);

        $company = $this->currentCompany();
        $this->authorizeAbility('edit', $company);

        $validated = $request->validate([
            'cpr_no'   => 'required|string|max:50',
            'cpr_date' => 'nullable|date',
        ]);

        $monthStart = $this->monthFrom($request);

        $affected = $this->query($company, $monthStart, $kind)->update([
            'cpr_no'     => $validated['cpr_no'],
            'cpr_date'   => $validated['cpr_date'] ?? null,
            'updated_at' => now(),
        ]);

        return back()->with('success', "CPR {$validated['cpr_no']} recorded against {$affected} entries.");
    }

    /** Undo a mis-keyed PSID or CPR for the batch. */
    public function clear(Request $request, string $kind)
    {
        abort_unless(in_array($kind, self::KINDS, true), 404);

        $company = $this->currentCompany();
        $this->authorizeAbility('edit', $company);

        $what = $request->validate(['what' => 'required|in:psid,cpr'])['what'];
        $monthStart = $this->monthFrom($request);

        $fields = $what === 'psid'
            ? ['psid_no' => null, 'cpr_no' => null, 'cpr_date' => null]
            : ['cpr_no' => null, 'cpr_date' => null];

        $affected = $this->query($company, $monthStart, $kind)->update($fields + ['updated_at' => now()]);

        return back()->with('success', strtoupper($what) . " cleared on {$affected} entries.");
    }

    /** Admin-only: replace the column layout without a deploy. */
    public function saveLayout(Request $request)
    {
        abort_unless(auth()->user()?->hasRole('admin'), 403);

        $validated = $request->validate(['layout' => 'required|string']);

        $decoded = json_decode($validated['layout'], true);

        if (!is_array($decoded) || empty($decoded['columns'])) {
            return back()->with('error', 'That is not valid layout JSON — it needs at least a "columns" array.');
        }

        foreach ($decoded['columns'] as $col) {
            if (empty($col['header']) || empty($col['field'])) {
                return back()->with('error', 'Every column needs both a "header" and a "field".');
            }
        }

        WhtSetting::put(WhtPsidWorkbook::SETTING_KEY, json_encode($decoded));

        return back()->with('success', 'Column layout saved. It applies to the next file you generate.');
    }

    public function resetLayout()
    {
        abort_unless(auth()->user()?->hasRole('admin'), 403);

        WhtSetting::put(WhtPsidWorkbook::SETTING_KEY, '');

        return back()->with('success', 'Column layout reset to the built-in default.');
    }

    // ── internals ────────────────────────────────────────────────────────────

    private function monthFrom(Request $request): Carbon
    {
        $month = $request->get('month', now()->subMonth()->format('Y-m'));

        return Carbon::createFromFormat('Y-m-d', $month . '-01')->startOfMonth();
    }

    private function query($company, Carbon $monthStart, string $kind)
    {
        return $this->batcher->query($company, $monthStart, $kind);
    }

    private function batch($company, Carbon $monthStart, string $kind): array
    {
        return $this->batcher->batch($company, $monthStart, $kind);
    }
}
