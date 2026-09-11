<?php

namespace App\Http\Controllers\Wht;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Wht\Concerns\ResolvesWhtCompany;
use App\Models\WhtChallan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * PSID / CPR tracking.
 *
 * PSIDs are derived from the transactions that reference them, so the list is
 * always in step with what has actually been recorded. Uploaded documents live
 * in storage/app/wht-challans and are served through download() — the old
 * portal kept them in the webroot where anyone could fetch them.
 */
class WhtChallanController extends Controller
{
    use ResolvesWhtCompany;

    private const DISK = 'local';
    private const DIR = 'wht-challans';

    public function index(Request $request)
    {
        $company = $this->currentCompany();

        // Every PSID referenced by either transaction table, with its tax total
        // and the tax period(s) it covers — a selection may span months.
        $purchases = DB::table('wht_purchases')
            ->selectRaw("psid_no, cpr_no, tax_withheld AS tax, period_month AS period, 'purchase' AS src")
            ->where('wht_company_id', $company->id)
            ->whereNotNull('psid_no')->where('psid_no', '!=', '');

        $rows = DB::table('wht_salaries')
            ->selectRaw("psid_no, cpr_no, tax_deducted AS tax, salary_month AS period, 'salary' AS src")
            ->where('wht_company_id', $company->id)
            ->whereNotNull('psid_no')->where('psid_no', '!=', '')
            ->unionAll($purchases);

        $challans = DB::query()
            ->fromSub($rows, 'd')
            ->selectRaw('d.psid_no, MAX(d.cpr_no) AS cpr_no, SUM(d.tax) AS total_tax, COUNT(*) AS entries,
                         MIN(d.period) AS period_from, MAX(d.period) AS period_to')
            ->groupBy('d.psid_no')
            ->orderByDesc('period_to')
            ->orderByDesc('total_tax')
            ->get();

        return view('wht.challans.index', compact('company', 'challans'));
    }

    /**
     * Every entry covered by one PSID or CPR, as a PDF.
     *
     * This is the schedule you attach to the challan for the file: what the
     * single deposited figure is actually made up of, across both vendor
     * payments and salaries.
     */
    public function pdf(Request $request)
    {
        @set_time_limit(180);

        $company = $this->currentCompany();

        $validated = $request->validate([
            'psid' => 'required_without:cpr|nullable|string|max:100',
            'cpr'  => 'required_without:psid|nullable|string|max:100',
        ]);

        // A CPR is the stronger reference once it exists, so prefer it.
        $field = filled($validated['cpr'] ?? null) ? 'cpr_no' : 'psid_no';
        $value = $field === 'cpr_no' ? $validated['cpr'] : $validated['psid'];

        $purchases = $company->purchases()->with('party')
            ->where($field, $value)
            ->orderBy('period_month')->orderBy('payment_date')->get();

        $salaries = $company->salaries()->with('employee')
            ->where($field, $value)
            ->orderBy('salary_month')->orderBy('payment_date')->get();

        if ($purchases->isEmpty() && $salaries->isEmpty()) {
            return back()->with('error', strtoupper(str_replace('_no', '', $field)) . " {$value} has no entries against it.");
        }

        // The stored documents, and whichever reference the entries carry.
        $challan = $company->challans()
            ->where($field === 'cpr_no' ? 'cpr_no' : 'psid_no', $value)
            ->first();

        $psid = $field === 'psid_no' ? $value : ($purchases->first()?->psid_no ?? $salaries->first()?->psid_no);
        $cpr = $field === 'cpr_no' ? $value : ($purchases->first()?->cpr_no ?? $salaries->first()?->cpr_no);

        $html = view('wht.challans.pdf', [
            'company'   => $company,
            'purchases' => $purchases,
            'salaries'  => $salaries,
            'psid'      => $psid,
            'cpr'       => $cpr,
            'paidOn'    => $challan?->paid_on ?? $purchases->first()?->cpr_date ?? $salaries->first()?->cpr_date,
            'reference' => $field === 'cpr_no' ? 'CPR' : 'PSID',
        ])->render();

        $tempDir = storage_path('app/mpdf-temp');

        if (!is_dir($tempDir)) {
            @mkdir($tempDir, 0775, true);
        }

        $name = sprintf('%s-%s-%s', $field === 'cpr_no' ? 'CPR' : 'PSID',
            preg_replace('/[^A-Za-z0-9_-]/', '', $value), str($company->name)->slug());

        $mpdf = new \Mpdf\Mpdf([
            'mode' => 'utf-8', 'format' => 'A4-L',
            'margin_left' => 10, 'margin_right' => 10,
            'margin_top' => 12, 'margin_bottom' => 14,
            'tempDir' => $tempDir,
        ]);
        $mpdf->SetTitle($name);
        $mpdf->WriteHTML($html);

        $dest = $request->boolean('download')
            ? \Mpdf\Output\Destination::DOWNLOAD
            : \Mpdf\Output\Destination::INLINE;

        $mpdf->Output($name . '.pdf', $dest);
    }

    public function upload(Request $request)
    {
        $company = $this->currentCompany();
        $this->authorizeAbility('edit', $company);

        $validated = $request->validate([
            'psid_no'   => 'required|string|max:100',
            'cpr_no'    => 'nullable|string|max:100',
            'paid_on'   => 'nullable|date',
            'psid_file' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
            'cpr_file'  => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
        ]);

        $challan = WhtChallan::firstOrNew([
            'wht_company_id' => $company->id,
            'psid_no'        => $validated['psid_no'],
        ]);

        $challan->cpr_no = $validated['cpr_no'] ?? $challan->cpr_no;
        $challan->paid_on = $validated['paid_on'] ?? $challan->paid_on;

        foreach (['psid_file', 'cpr_file'] as $field) {
            if (!$request->hasFile($field)) {
                continue;
            }

            // Replace rather than accumulate.
            if ($challan->$field) {
                Storage::disk(self::DISK)->delete($challan->$field);
            }

            $challan->$field = $request->file($field)->store(
                self::DIR . '/' . $company->id,
                self::DISK
            );
        }

        $challan->save();

        return back()->with('success', "Attachments saved for PSID {$validated['psid_no']}.");
    }

    public function download(WhtChallan $challan, string $type)
    {
        $company = $this->currentCompany();
        abort_unless($challan->wht_company_id === $company->id, 404);
        abort_unless(in_array($type, ['psid', 'cpr'], true), 404);

        $path = $challan->{"{$type}_file"};

        abort_unless($path && Storage::disk(self::DISK)->exists($path), 404, 'File not found.');

        $name = strtoupper($type) . '-' . $challan->psid_no . '.' . pathinfo($path, PATHINFO_EXTENSION);

        return Storage::disk(self::DISK)->download($path, $name);
    }

    public function deleteFile(Request $request, WhtChallan $challan, string $type)
    {
        $company = $this->currentCompany();
        abort_unless($challan->wht_company_id === $company->id, 404);
        abort_unless(in_array($type, ['psid', 'cpr'], true), 404);
        $this->authorizeAbility('delete', $company);

        $field = "{$type}_file";

        if ($challan->$field) {
            Storage::disk(self::DISK)->delete($challan->$field);
            $challan->update([$field => null]);
        }

        return back()->with('success', strtoupper($type) . ' document removed.');
    }
}
