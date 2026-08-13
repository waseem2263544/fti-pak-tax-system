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

        // Every PSID referenced by either transaction table, with its tax total.
        $purchases = DB::table('wht_purchases')
            ->selectRaw("psid_no, cpr_no, tax_withheld AS tax, 'purchase' AS src")
            ->where('wht_company_id', $company->id)
            ->whereNotNull('psid_no')->where('psid_no', '!=', '');

        $rows = DB::table('wht_salaries')
            ->selectRaw("psid_no, cpr_no, tax_deducted AS tax, 'salary' AS src")
            ->where('wht_company_id', $company->id)
            ->whereNotNull('psid_no')->where('psid_no', '!=', '')
            ->unionAll($purchases);

        $challans = DB::query()
            ->fromSub($rows, 'd')
            ->selectRaw('d.psid_no, MAX(d.cpr_no) AS cpr_no, SUM(d.tax) AS total_tax, COUNT(*) AS entries')
            ->groupBy('d.psid_no')
            ->orderByDesc('total_tax')
            ->get();

        // Attach the stored documents.
        $files = $company->challans()->get()->keyBy('psid_no');

        $challans = $challans->map(function ($row) use ($files) {
            $row->files = $files->get($row->psid_no);

            return $row;
        });

        return view('wht.challans.index', compact('company', 'challans'));
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
