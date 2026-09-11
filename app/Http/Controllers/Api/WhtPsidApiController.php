<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\WhtCompany;
use App\Services\Wht\WhtPsidBatcher;
use App\Services\Wht\WhtPsidWorkbook;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

/**
 * Read-only WHT endpoints for the wht-psid Claude skill.
 *
 * The app itself is where the work happens — Prepare PSID does the whole
 * download / assign / record cycle. These exist for the things a screen is bad
 * at: looking across every agent at once, and pulling files in bulk.
 *
 * Authenticated with the shared WHT_API_TOKEN rather than a session, because the
 * caller is a script. Nothing here writes; assigning a PSID or CPR is a
 * deliberate human action in the app.
 */
class WhtPsidApiController extends Controller
{
    public function __construct(private WhtPsidBatcher $batcher)
    {
    }

    private function authorized(Request $request): bool
    {
        $expected = (string) config('services.wht_api.token', '');

        // An unset token must not mean "open to everyone".
        if ($expected === '') {
            return false;
        }

        $given = (string) ($request->header('X-Wht-Token') ?? $request->get('token', ''));

        return $given !== '' && hash_equals($expected, $given);
    }

    /** GET /api/wht/agents */
    public function agents(Request $request)
    {
        if (!$this->authorized($request)) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        return response()->json(
            WhtCompany::orderBy('name')->get(['id', 'name', 'ntn_cnic', 'is_active'])
        );
    }

    /**
     * GET /api/wht/status?month=YYYY-MM
     *
     * Every agent's two batches for a period — what still needs a PSID, and
     * what has a PSID but no CPR. This is the "who hasn't deposited yet" view.
     */
    public function status(Request $request)
    {
        if (!$this->authorized($request)) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $month = $this->month($request);
        $out = [];

        foreach (WhtCompany::where('is_active', true)->orderBy('name')->get() as $agent) {
            $batches = [];

            foreach (WhtPsidBatcher::KINDS as $kind) {
                $b = $this->batcher->batch($agent, $month, $kind);

                $batches[$kind] = [
                    'entries'   => $b['count'],
                    'payees'    => $b['payees'],
                    'gross'     => round($b['gross'], 2),
                    'tax'       => round($b['tax'], 2),
                    'status'    => $b['status'],
                    'psid_no'   => $b['psid_no'],
                    'cpr_no'    => $b['cpr_no'],
                    'sections'  => $b['sections'],
                ];
            }

            $out[] = [
                'agent'   => ['id' => $agent->id, 'name' => $agent->name, 'ntn_cnic' => $agent->ntn_cnic],
                'batches' => $batches,
            ];
        }

        return response()->json(['period' => $month->format('Y-m'), 'agents' => $out]);
    }

    /** GET /api/wht/psid?agent=<id|name>&month=YYYY-MM — the rows, as JSON. */
    public function psid(Request $request)
    {
        if (!$this->authorized($request)) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $agent = $this->agent($request);

        if (!$agent) {
            return response()->json(['error' => 'Withholding agent not found'], 404);
        }

        $month = $this->month($request);
        $batches = [];

        foreach (WhtPsidBatcher::KINDS as $kind) {
            $b = $this->batcher->batch($agent, $month, $kind);
            $batches[$kind] = [
                'status' => $b['status'],
                'count'  => $b['count'],
                'gross'  => round($b['gross'], 2),
                'tax'    => round($b['tax'], 2),
                'rows'   => $b['rows'],
            ];
        }

        return response()->json([
            'agent'   => ['id' => $agent->id, 'name' => $agent->name, 'ntn_cnic' => $agent->ntn_cnic],
            'period'  => $month->format('Y-m'),
            'batches' => $batches,
        ]);
    }

    /**
     * GET /api/wht/psid/file?agent=<id|name>&month=YYYY-MM&kind=purchases|salaries
     *
     * The same workbook the Prepare PSID screen produces — one implementation,
     * so a file fetched here is byte-identical to one downloaded in the app.
     */
    public function file(Request $request)
    {
        @set_time_limit(180);

        if (!$this->authorized($request)) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $kind = $request->get('kind', 'purchases');

        if (!in_array($kind, WhtPsidBatcher::KINDS, true)) {
            return response()->json(['error' => 'kind must be purchases or salaries'], 422);
        }

        $agent = $this->agent($request);

        if (!$agent) {
            return response()->json(['error' => 'Withholding agent not found'], 404);
        }

        $month = $this->month($request);
        $batch = $this->batcher->batch($agent, $month, $kind);

        if ($batch['rows']->isEmpty()) {
            return response()->json(['error' => 'Nothing recorded for ' . $month->format('F Y')], 404);
        }

        $book = (new WhtPsidWorkbook())->build($agent, $month, $kind, $batch['rows']);

        $filename = sprintf(
            'PSID-%s-%s-%s.xlsx',
            str($agent->name)->slug(),
            $kind === 'salaries' ? 'salaries' : 'vendors',
            $month->format('Y-m')
        );

        return response()->streamDownload(function () use ($book) {
            $writer = new Xlsx($book);
            $writer->setPreCalculateFormulas(false);
            $writer->save('php://output');
            $book->disconnectWorksheets();
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    private function agent(Request $request): ?WhtCompany
    {
        $key = (string) $request->get('agent', '');

        if ($key === '') {
            return null;
        }

        return ctype_digit($key)
            ? WhtCompany::find($key)
            : WhtCompany::where('name', 'like', '%' . $key . '%')->first();
    }

    private function month(Request $request): Carbon
    {
        $request->validate(['month' => 'nullable|date_format:Y-m']);

        $month = $request->get('month', now()->subMonth()->format('Y-m'));

        return Carbon::createFromFormat('Y-m-d', $month . '-01')->startOfMonth();
    }
}
