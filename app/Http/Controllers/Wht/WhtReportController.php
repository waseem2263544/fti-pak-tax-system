<?php

namespace App\Http\Controllers\Wht;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Wht\Concerns\ResolvesWhtCompany;
use App\Models\WhtPurchase;
use App\Models\WhtSalary;
use App\Models\WhtSection;
use App\Models\WhtSetting;
use App\Services\Wht\WhtCalculator;
use App\Services\Wht\WhtStatementFiler;
use App\Services\Wht\WhtStatementWorkbook;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Symfony\Component\HttpFoundation\StreamedResponse;

class WhtReportController extends Controller
{
    use ResolvesWhtCompany;

    public function dashboard()
    {
        $company = $this->currentCompany();

        $taxYear = WhtCalculator::taxYear(now());
        $yearStart = Carbon::create($taxYear - 1, 7, 1)->startOfMonth();
        $yearEnd = Carbon::create($taxYear, 6, 1)->startOfMonth();

        $purchases = $company->purchases()->forPeriod($yearStart, $yearEnd);
        $salaries = $company->salaries()->forPeriod($yearStart, $yearEnd);

        $stats = [
            'tax_year'        => $taxYear,
            'purchase_tax'    => (clone $purchases)->sum('tax_withheld'),
            'salary_tax'      => (clone $salaries)->sum('tax_deducted'),
            'purchase_count'  => (clone $purchases)->count(),
            'salary_count'    => (clone $salaries)->count(),
            'parties'         => $company->parties()->active()->count(),
            'undeposited_tax' => (clone $purchases)->where(fn($q) => $q->whereNull('cpr_no')->orWhere('cpr_no', ''))->sum('tax_withheld')
                               + (clone $salaries)->where(fn($q) => $q->whereNull('cpr_no')->orWhere('cpr_no', ''))->sum('tax_deducted'),
        ];

        // Month-by-month totals for the current tax year.
        $monthly = [];
        for ($m = $yearStart->copy(); $m->lessThanOrEqualTo($yearEnd); $m->addMonth()) {
            $key = $m->format('Y-m');
            $monthly[$key] = [
                'label'    => $m->format('M Y'),
                'purchase' => $company->purchases()->whereDate('period_month', $m)->sum('tax_withheld'),
                'salary'   => $company->salaries()->whereDate('salary_month', $m)->sum('tax_deducted'),
            ];
        }

        $recent = $company->purchases()->with('party')->latest('id')->limit(10)->get();

        return view('wht.dashboard', compact('company', 'stats', 'monthly', 'recent'));
    }

    /**
     * Transaction listing with a chosen column set — the old portal's Reports page.
     */
    public function index(Request $request)
    {
        $company = $this->currentCompany();

        $type = $request->get('type', 'purchases');
        $from = $request->get('from', now()->startOfYear()->format('Y-m'));
        $to = $request->get('to', now()->format('Y-m'));

        $rows = collect();

        if ($request->has('generate')) {
            $rows = $this->rowsFor($company, $type, $from, $to, $request->get('party_id'), $request->get('section'));
        }

        $parties = $company->parties()->orderBy('name')->get();
        $sections = WhtSection::active()->orderBy('code')->get();

        return view('wht.reports.index', compact('company', 'rows', 'parties', 'sections', 'type', 'from', 'to'));
    }

    /**
     * Monthly withholding statement (u/s 165), grouped by section.
     *
     * This is the shape FBR's statement wants — the old portal only had a flat
     * transaction dump, which then had to be pivoted by hand every month.
     */
    public function statement(Request $request)
    {
        $company = $this->currentCompany();

        $month = $request->get('month', now()->format('Y-m'));
        $monthStart = Carbon::createFromFormat('Y-m-d', $month . '-01')->startOfMonth();

        $grouped = $this->groupedStatement($company, $monthStart);

        return view('wht.reports.statement', compact('company', 'grouped', 'month', 'monthStart'));
    }

    /**
     * The same statement as an Excel workbook — a summary sheet whose totals are
     * live formulas over one detail sheet per section.
     */
    public function statementExcel(Request $request)
    {
        @set_time_limit(180);

        $company = $this->currentCompany();

        $month = $request->get('month', now()->format('Y-m'));
        $monthStart = Carbon::createFromFormat('Y-m-d', $month . '-01')->startOfMonth();

        // Writing .xlsx needs these; on shared hosting they are not a given, and
        // the failure is otherwise an opaque 500.
        $missing = array_values(array_filter(
            ['zip', 'xmlwriter', 'xmlreader', 'simplexml', 'dom', 'mbstring', 'gd', 'iconv', 'fileinfo'],
            fn($ext) => !extension_loaded($ext)
        ));

        if ($missing) {
            return back()->with('error',
                'Excel export needs these PHP extensions, which are not enabled on the server: '
                . implode(', ', $missing) . '. Ask your host to enable them, or use the CSV export.');
        }

        $grouped = $this->groupedStatement($company, $monthStart);

        if ($grouped->isEmpty()) {
            return back()->with('error', 'Nothing recorded for ' . $monthStart->format('F Y') . '.');
        }

        $book = (new WhtStatementWorkbook())->build($company, $monthStart, $grouped);

        $filename = sprintf(
            'WHT-Statement-%s-%s.xlsx',
            str($company->name)->slug(),
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

    /**
     * The s.165 statement in FBR's own Withholding Statement workbook.
     *
     * Their .xlsm is used as the template and written back as .xlsm so the
     * validation macro survives — bad rows get flagged locally before anything
     * reaches IRIS.
     */
    public function statementFiling(Request $request)
    {
        @set_time_limit(240);

        $company = $this->currentCompany();

        if (!WhtStatementFiler::templateExists()) {
            return back()->with('error', 'The FBR withholding statement template is missing from the server.');
        }

        $missing = array_values(array_filter(
            ['zip', 'xmlreader', 'xmlwriter', 'dom', 'simplexml', 'mbstring'],
            fn($e) => !extension_loaded($e)
        ));

        if ($missing) {
            return back()->with('error', 'This needs PHP extensions not enabled on the server: ' . implode(', ', $missing) . '.');
        }

        $month = $request->get('month', now()->format('Y-m'));
        $monthStart = Carbon::createFromFormat('Y-m-d', $month . '-01')->startOfMonth();

        $purchases = $company->purchases()->with('party')
            ->whereDate('period_month', $monthStart)
            ->orderBy('payment_date')->get();

        $salaries = $company->salaries()->with('employee')
            ->whereDate('salary_month', $monthStart)
            ->orderBy('payment_date')->get();

        if ($purchases->isEmpty() && $salaries->isEmpty()) {
            return back()->with('error', 'Nothing recorded for ' . $monthStart->format('F Y') . '.');
        }

        try {
            $book = (new WhtStatementFiler())->build($company, $monthStart, $purchases, $salaries);
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        $filename = sprintf(
            'Withholding-Statement-%s-%s.xlsm',
            str($company->name)->slug(),
            $monthStart->format('Y-m')
        );

        return response()->streamDownload(function () use ($book) {
            $writer = \PhpOffice\PhpSpreadsheet\IOFactory::createWriter($book, 'Xlsx');
            $writer->setPreCalculateFormulas(false);
            $writer->save('php://output');
            $book->disconnectWorksheets();
        }, $filename, [
            'Content-Type'  => 'application/vnd.ms-excel.sheet.macroEnabled.12',
            'Cache-Control' => 'max-age=0, no-store',
        ]);
    }

    /**
     * Statement rows for one tax period, grouped by section with the per-party
     * detail underneath. Shared by the HTML view and the Excel export so the two
     * can never drift apart.
     */
    private function groupedStatement($company, Carbon $monthStart)
    {
        $purchaseRows = $company->purchases()
            ->with('party')
            ->whereDate('period_month', $monthStart)
            ->get();

        $salaryRows = $company->salaries()
            ->with('employee')
            ->whereDate('salary_month', $monthStart)
            ->get();

        $sections = WhtSection::active()->get();

        $grouped = collect();

        foreach ($purchaseRows->groupBy('section') as $section => $items) {
            $grouped->push([
                'section'   => $section ?: 'Unclassified',
                'nature'    => $sections->first(fn($s) => $s->section === $section)?->payment_nature ?? '—',
                'payees'    => $items->pluck('party_id')->unique()->count(),
                'gross'     => $items->sum('gross_amount'),
                'tax'       => $items->sum('tax_withheld'),
                'items'     => $items,
                'kind'      => 'purchase',
            ]);
        }

        if ($salaryRows->isNotEmpty()) {
            $grouped->push([
                'section' => '149',
                'nature'  => 'Salary',
                'payees'  => $salaryRows->pluck('employee_id')->unique()->count(),
                'gross'   => $salaryRows->sum('total_salary'),
                'tax'     => $salaryRows->sum('tax_deducted'),
                'items'   => $salaryRows,
                'kind'    => 'salary',
            ]);
        }

        return $grouped->sortBy('section')->values();
    }

    /**
     * CSV export of whatever the report filters currently select.
     */
    public function export(Request $request): StreamedResponse
    {
        $company = $this->currentCompany();

        $type = $request->get('type', 'purchases');
        $from = $request->get('from', now()->startOfYear()->format('Y-m'));
        $to = $request->get('to', now()->format('Y-m'));

        $rows = $this->rowsFor($company, $type, $from, $to, $request->get('party_id'), $request->get('section'));

        $filename = sprintf('wht-%s-%s-%s-to-%s.csv', str($company->name)->slug(), $type, $from, $to);

        return response()->streamDownload(function () use ($rows, $type) {
            $out = fopen('php://output', 'w');

            fputcsv($out, $type === 'salaries'
                ? ['Salary Month', 'Employee', 'CNIC/NTN', 'Section', 'Total Salary', 'Taxable', 'Exempt', 'Tax Deducted', 'Net Paid', 'Payment Date', 'PSID', 'CPR']
                : ['Period', 'Vendor', 'CNIC/NTN', 'ATL', 'Section', 'Gross', 'Rate %', 'Tax Withheld', 'Net Paid', 'Payment Date', 'PSID', 'CPR']);

            foreach ($rows as $r) {
                fputcsv($out, $type === 'salaries'
                    ? [
                        $r->salary_month?->format('M Y'),
                        $r->employee?->name,
                        $r->employee?->cnic_ntn,
                        $r->section,
                        $r->total_salary,
                        $r->taxable_salary,
                        $r->exempt_amount,
                        $r->tax_deducted,
                        $r->final_net_payment,
                        $r->payment_date?->format('Y-m-d'),
                        $r->psid_no,
                        $r->cpr_no,
                    ]
                    : [
                        $r->period_month?->format('M Y'),
                        $r->party?->name,
                        $r->party?->cnic_ntn,
                        $r->party?->atl_status,
                        $r->section,
                        $r->gross_amount,
                        $r->tax_rate,
                        $r->tax_withheld,
                        $r->net_payment,
                        $r->payment_date?->format('Y-m-d'),
                        $r->psid_no,
                        $r->cpr_no,
                    ]);
            }

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    /**
     * WHT certificate for a single transaction.
     */
    public function certificate(Request $request, string $type, int $id)
    {
        @set_time_limit(120);

        $company = $this->currentCompany();

        abort_unless(in_array($type, ['purchase', 'salary'], true), 404);

        $record = $type === 'purchase'
            ? WhtPurchase::with('party')->where('wht_company_id', $company->id)->findOrFail($id)
            : WhtSalary::with('employee')->where('wht_company_id', $company->id)->findOrFail($id);

        $html = view('wht.reports.certificate', [
            'company'  => $company,
            'record'   => $record,
            'type'     => $type,
            'party'    => $type === 'purchase' ? $record->party : $record->employee,
            'prefix'   => WhtSetting::get('certificate_prefix', 'WHT'),
            'footer'   => WhtSetting::get('statement_footer', ''),
        ])->render();

        return $this->renderPdf($html, sprintf('WHT-Certificate-%s-%d', $type, $id), $request->boolean('download'));
    }

    /**
     * Annual certificate covering every payment to one party in a tax year —
     * what vendors and employees actually ask for at year end.
     */
    public function annualCertificate(Request $request, int $partyId)
    {
        @set_time_limit(120);

        $company = $this->currentCompany();
        $party = $company->parties()->findOrFail($partyId);

        $taxYear = (int) ($request->get('tax_year') ?: WhtCalculator::taxYear(now()));
        $start = Carbon::create($taxYear - 1, 7, 1)->startOfMonth();
        $end = Carbon::create($taxYear, 6, 1)->startOfMonth();

        $purchases = $party->purchases()->whereBetween('period_month', [$start, $end])->orderBy('period_month')->get();
        $salaries = $party->salaries()->whereBetween('salary_month', [$start, $end])->orderBy('salary_month')->get();

        abort_if($purchases->isEmpty() && $salaries->isEmpty(), 404, 'No transactions for this party in tax year ' . $taxYear . '.');

        $html = view('wht.reports.annual-certificate', [
            'company'   => $company,
            'party'     => $party,
            'purchases' => $purchases,
            'salaries'  => $salaries,
            'taxYear'   => $taxYear,
            'prefix'    => WhtSetting::get('certificate_prefix', 'WHT'),
            'footer'    => WhtSetting::get('statement_footer', ''),
        ])->render();

        return $this->renderPdf(
            $html,
            sprintf('WHT-Certificate-%s-TY%d', str($party->name)->slug(), $taxYear),
            $request->boolean('download')
        );
    }

    private function renderPdf(string $html, string $name, bool $download): void
    {
        $tempDir = storage_path('app/mpdf-temp');

        if (!is_dir($tempDir)) {
            @mkdir($tempDir, 0775, true);
        }

        $mpdf = new \Mpdf\Mpdf([
            'mode' => 'utf-8', 'format' => 'A4',
            'margin_left' => 14, 'margin_right' => 14,
            'margin_top' => 14, 'margin_bottom' => 16,
            'tempDir' => $tempDir,
        ]);
        $mpdf->SetTitle($name);
        $mpdf->WriteHTML($html);

        $dest = $download ? \Mpdf\Output\Destination::DOWNLOAD : \Mpdf\Output\Destination::INLINE;
        $mpdf->Output($name . '.pdf', $dest);
    }

    private function rowsFor($company, string $type, string $from, string $to, $partyId, $section)
    {
        $start = Carbon::createFromFormat('Y-m-d', $from . '-01')->startOfMonth();
        $end = Carbon::createFromFormat('Y-m-d', $to . '-01')->startOfMonth();

        if ($type === 'salaries') {
            $q = $company->salaries()->with('employee')->whereBetween('salary_month', [$start, $end]);

            if ($partyId) {
                $q->where('employee_id', $partyId);
            }

            return $q->orderBy('salary_month')->get();
        }

        $q = $company->purchases()->with('party')->whereBetween('period_month', [$start, $end]);

        if ($partyId) {
            $q->where('party_id', $partyId);
        }

        if ($section) {
            $q->where('section', $section);
        }

        return $q->orderBy('period_month')->get();
    }
}
