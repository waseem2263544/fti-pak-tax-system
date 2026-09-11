<?php

namespace App\Http\Controllers\Wht;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Wht\Concerns\ResolvesWhtCompany;
use App\Models\WhtPurchase;
use App\Services\Wht\WhtTransactionImporter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Import vendor payments from a client's spreadsheet.
 *
 * Client sheets never look the same twice, so columns are matched by alias and
 * whatever is missing gets derived. What is NOT negotiable is the arithmetic:
 * tax is recomputed by WhtCalculator on import, and a tax figure present in the
 * sheet is only ever used as a cross-check.
 *
 * Upload shows a preview — what will import, what will import with a note, and
 * what is blocked — and nothing is written until that is confirmed.
 */
class WhtImportController extends Controller
{
    use ResolvesWhtCompany;

    private const CACHE_MINUTES = 60;

    public function __construct(private WhtTransactionImporter $importer)
    {
    }

    public function index()
    {
        $company = $this->currentCompany();

        return view('wht.imports.index', [
            'company'  => $company,
            'analysis' => null,
            'token'    => null,
        ]);
    }

    public function preview(Request $request)
    {
        @set_time_limit(180);

        $company = $this->currentCompany();
        $this->authorizeAbility('create', $company);

        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv,txt|max:8192',
        ]);

        $missing = array_values(array_filter(
            ['zip', 'xmlreader', 'simplexml', 'dom', 'mbstring'],
            fn($e) => !extension_loaded($e)
        ));

        if ($missing) {
            return back()->with('error', 'Reading spreadsheets needs these PHP extensions, not enabled on the server: ' . implode(', ', $missing) . '.');
        }

        try {
            $analysis = $this->importer->analyse($company, $request->file('file')->getRealPath());
        } catch (\Throwable $e) {
            return back()->with('error', 'Could not read that file: ' . $e->getMessage());
        }

        if ($analysis['rows']->isEmpty()) {
            return back()->with('error', 'No data rows found. Check the sheet has a header row and at least one payment.');
        }

        // Hold the analysed rows rather than the file, so committing cannot
        // re-read a sheet that changed underneath us.
        $token = Str::uuid()->toString();

        Cache::put($this->cacheKey($company->id, $token), [
            'rows'     => $analysis['rows']->toArray(),
            'filename' => $request->file('file')->getClientOriginalName(),
        ], now()->addMinutes(self::CACHE_MINUTES));

        return view('wht.imports.index', [
            'company'  => $company,
            'analysis' => $analysis,
            'token'    => $token,
            'filename' => $request->file('file')->getClientOriginalName(),
        ]);
    }

    public function commit(Request $request)
    {
        @set_time_limit(180);

        $company = $this->currentCompany();
        $this->authorizeAbility('create', $company);

        $validated = $request->validate([
            'token'            => 'required|uuid',
            'include_warnings' => 'nullable|boolean',
        ]);

        $cached = Cache::get($this->cacheKey($company->id, $validated['token']));

        if (!$cached) {
            return redirect()->route('wht.imports.index')
                ->with('error', 'That preview has expired. Upload the file again.');
        }

        $includeWarnings = $request->boolean('include_warnings', true);

        $importable = collect($cached['rows'])->filter(function ($r) use ($includeWarnings) {
            if ($r['status'] === WhtTransactionImporter::STATUS_BLOCKED) {
                return false;
            }

            return $includeWarnings || $r['status'] === WhtTransactionImporter::STATUS_OK;
        });

        if ($importable->isEmpty()) {
            return back()->with('error', 'Nothing left to import with those options.');
        }

        $created = 0;

        // All or nothing: a half-imported month is worse than none.
        DB::transaction(function () use ($importable, $company, &$created) {
            foreach ($importable as $r) {
                WhtPurchase::create([
                    'wht_company_id' => $company->id,
                    'party_id'       => $r['party_id'],
                    'period_month'   => $r['period_month'] . '-01',
                    'payment_date'   => $r['payment_date'],
                    'section'        => $r['section'],
                    'calc_mode'      => $r['calc_mode'],
                    'gross_amount'   => $r['gross_amount'],
                    'tax_rate'       => $r['tax_rate'],
                    'tax_rate_id'    => $r['tax_rate_id'],
                    'rate_source'    => $r['rate_source'] ?? 'matrix',
                    'tax_withheld'   => $r['tax_withheld'],
                    'net_payment'    => $r['net_payment'],
                    'remarks'        => $r['remarks'],
                    'created_by'     => auth()->id(),
                ]);
                $created++;
            }
        });

        Cache::forget($this->cacheKey($company->id, $validated['token']));

        return redirect()->route('wht.purchases.index')
            ->with('success', "Imported {$created} payments from {$cached['filename']}.");
    }

    /** The canonical sheet shape, for clients and for Claude to target. */
    public function template()
    {
        $rows = [
            ['payee_name', 'payee_cnic_ntn', 'payment_date', 'period_month', 'section', 'amount', 'amount_basis', 'tax_withheld', 'remarks'],
            ['Acme Traders', '1730112345678', '15/06/2026', '2026-06', '153(1)(a)/9', '1000000', 'gross', '', 'Invoice 221'],
            ['Beta Supplies', '3520298765432', '20/06/2026', '', '', '250000', 'net', '', 'Section from party default'],
        ];

        return response()->streamDownload(function () use ($rows) {
            $out = fopen('php://output', 'w');
            foreach ($rows as $r) {
                fputcsv($out, $r);
            }
            fclose($out);
        }, 'wht-import-template.csv', ['Content-Type' => 'text/csv']);
    }

    private function cacheKey(int $companyId, string $token): string
    {
        return "wht_import:{$companyId}:{$token}";
    }
}
