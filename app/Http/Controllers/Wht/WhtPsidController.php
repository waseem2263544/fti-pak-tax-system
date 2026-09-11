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
 * Prepare PSID — pick the entries a challan covers, generate the IRIS file,
 * then record the PSID and CPR against exactly those entries.
 *
 * Selection is manual and deliberate. An earlier version acted on a whole tax
 * period at once, which was wrong: when a client sends a second file for a
 * month already deposited, "everything in June" silently includes entries that
 * are already on a challan and you end up raising a second PSID over them.
 *
 * So entries that already carry a PSID are shown but NOT selected by default,
 * and assigning over a different PSID is called out rather than done quietly.
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

        $kind = $request->get('kind') === 'salaries' ? 'salaries' : 'purchases';
        $month = $request->get('month', now()->subMonth()->format('Y-m'));
        $show = $request->get('show', 'unassigned');

        $query = $this->baseQuery($company, $kind);

        if ($month !== '') {
            $query->whereDate($this->periodColumn($kind), $this->monthStart($month));
        }

        if ($show === 'unassigned') {
            $query->where(fn($q) => $q->whereNull('psid_no')->orWhere('psid_no', ''));
        }

        $items = $query->with($kind === 'salaries' ? 'employee' : 'party')
            ->orderBy($this->periodColumn($kind))
            ->orderBy('payment_date')
            ->get();

        $rows = $this->batcher->mapRows($items, $kind);

        // How much of this period still needs depositing. Entries with no tax
        // withheld never go on a challan, so they are not "outstanding".
        $outstanding = $this->baseQuery($company, $kind)
            ->when($month !== '', fn($q) => $q->whereDate($this->periodColumn($kind), $this->monthStart($month)))
            ->where(fn($q) => $q->whereNull('psid_no')->orWhere('psid_no', ''))
            ->where($this->taxColumn($kind), '>', 0)
            ->count();

        return view('wht.deposit.index', [
            'company'        => $company,
            'kind'           => $kind,
            'month'          => $month,
            'show'           => $show,
            'rows'           => $rows,
            'outstanding'    => $outstanding,
            'layoutJson'     => json_encode(WhtPsidWorkbook::layout(), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES),
            'layoutIsCustom' => (bool) WhtSetting::get(WhtPsidWorkbook::SETTING_KEY),
            'challans'       => $this->batcher->challanSummary($company),
        ]);
    }

    /** The IRIS upload file for the selected entries only. */
    public function download(Request $request)
    {
        @set_time_limit(180);

        $company = $this->currentCompany();
        [$kind, $ids] = $this->selection($request);

        $missing = array_values(array_filter(
            ['zip', 'xmlwriter', 'dom', 'simplexml', 'mbstring'],
            fn($e) => !extension_loaded($e)
        ));

        if ($missing) {
            return back()->with('error', 'Excel export needs these PHP extensions, not enabled on the server: ' . implode(', ', $missing) . '.');
        }

        $rows = $this->batcher->rowsForIds($company, $kind, $ids);

        if ($rows->isEmpty()) {
            return back()->with('error', 'Select at least one entry first.');
        }

        // IRIS rejects a challan line with no amount — "Valid Amount must be
        // provided" — and there is nothing to deposit for them anyway. They
        // still belong on the s.165 statement, which is a different file.
        $withTax = $rows->filter(fn($r) => (float) ($r['tax_withheld'] ?? 0) > 0)->values();

        if ($withTax->isEmpty()) {
            return back()->with('error', 'None of the selected entries has any tax withheld, so there is nothing to deposit.');
        }

        $rows = $withTax;

        // The period is only used to name the file; the selection may span months.
        $period = Carbon::parse(($rows->first()['period'] ?? now()->format('Y-m')) . '-01');

        $book = (new WhtPsidWorkbook())->build($company, $period, $kind, $rows);

        $filename = sprintf(
            'PSID-%s-%s-%s-%d-entries.xlsx',
            str($company->name)->slug(),
            $kind === 'salaries' ? 'salaries' : 'vendors',
            $period->format('Y-m'),
            $rows->count()
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

    /** Stamp the PSID IRIS returned onto exactly the selected entries. */
    public function assignPsid(Request $request)
    {
        $company = $this->currentCompany();
        $this->authorizeAbility('edit', $company);

        [$kind, $ids] = $this->selection($request);
        $psid = $request->validate(['psid_no' => 'required|string|max:50'])['psid_no'];

        $query = $this->baseQuery($company, $kind)->whereIn('id', $ids);

        // Never relabel someone else's challan without saying so.
        $conflicting = (clone $query)
            ->whereNotNull('psid_no')->where('psid_no', '!=', '')
            ->where('psid_no', '!=', $psid)
            ->count();

        $affected = $query->update(['psid_no' => $psid, 'updated_at' => now()]);

        $note = $conflicting
            ? " Warning: {$conflicting} of them already carried a different PSID and were reassigned."
            : '';

        return back()->with($conflicting ? 'error' : 'success',
            "PSID {$psid} assigned to {$affected} entries.{$note}");
    }

    public function assignCpr(Request $request)
    {
        $company = $this->currentCompany();
        $this->authorizeAbility('edit', $company);

        [$kind, $ids] = $this->selection($request);

        $validated = $request->validate([
            'cpr_no'   => 'required|string|max:50',
            'cpr_date' => 'nullable|date',
        ]);

        $affected = $this->baseQuery($company, $kind)->whereIn('id', $ids)->update([
            'cpr_no'     => $validated['cpr_no'],
            'cpr_date'   => $validated['cpr_date'] ?? null,
            'updated_at' => now(),
        ]);

        return back()->with('success', "CPR {$validated['cpr_no']} recorded against {$affected} entries.");
    }

    public function clear(Request $request)
    {
        $company = $this->currentCompany();
        $this->authorizeAbility('edit', $company);

        [$kind, $ids] = $this->selection($request);
        $what = $request->validate(['what' => 'required|in:psid,cpr'])['what'];

        $fields = $what === 'psid'
            ? ['psid_no' => null, 'cpr_no' => null, 'cpr_date' => null]
            : ['cpr_no' => null, 'cpr_date' => null];

        $affected = $this->baseQuery($company, $kind)->whereIn('id', $ids)
            ->update($fields + ['updated_at' => now()]);

        return back()->with('success', strtoupper($what) . " cleared on {$affected} entries.");
    }

    /** Admin-only: replace the column layout without a deploy. */
    public function saveLayout(Request $request)
    {
        abort_unless(auth()->user()?->hasRole('admin'), 403);

        $decoded = json_decode($request->validate(['layout' => 'required|string'])['layout'], true);

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

        return back()->with('success', 'Column layout reset to the FBR default.');
    }

    // ── internals ────────────────────────────────────────────────────────────

    /** @return array{0: string, 1: array<int>} */
    private function selection(Request $request): array
    {
        $validated = $request->validate([
            'kind'  => 'required|in:purchases,salaries',
            'ids'   => 'required|array|min:1',
            'ids.*' => 'integer',
        ]);

        return [$validated['kind'], $validated['ids']];
    }

    private function baseQuery($company, string $kind)
    {
        return $kind === 'salaries' ? $company->salaries() : $company->purchases();
    }

    private function periodColumn(string $kind): string
    {
        return $kind === 'salaries' ? 'salary_month' : 'period_month';
    }

    private function taxColumn(string $kind): string
    {
        return $kind === 'salaries' ? 'tax_deducted' : 'tax_withheld';
    }

    private function monthStart(string $month): Carbon
    {
        return Carbon::createFromFormat('Y-m-d', $month . '-01')->startOfMonth();
    }
}
