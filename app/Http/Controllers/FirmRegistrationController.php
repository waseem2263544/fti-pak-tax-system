<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\FirmRegistration;
use App\Services\FirmRegistration\FirmDocuments;
use Illuminate\Http\Request;

/**
 * Registration of a partnership firm: one form feeds Form A, the affidavit
 * and the partnership deed, generated as Word files laid out for stamp paper.
 */
class FirmRegistrationController extends Controller
{
    public function index(Request $request)
    {
        $firms = FirmRegistration::with('client')
            ->when($request->filled('q'), fn ($q) => $q->where('firm_name', 'like', '%' . $request->q . '%'))
            ->latest()
            ->paginate(25)
            ->withQueryString();

        return view('firm-registrations.index', compact('firms'));
    }

    public function create(Request $request)
    {
        $firm = new FirmRegistration([
            'duration' => 'AT WILL',
            'stamp_top_cm' => 11.25,
            'managing_partner' => 0,
            'managing_powers' => FirmRegistration::DEFAULT_MANAGING_POWERS,
            'deed_date' => now(),
            'commencement_date' => now(),
        ]);

        // "Copy from" an existing firm: same partners, new firm.
        if ($request->filled('from') && ($source = FirmRegistration::find($request->from))) {
            $firm->fill($source->only(['client_id', 'partners', 'witnesses', 'managing_partner', 'managing_powers', 'business_description', 'stamp_top_cm']));
        }

        $clients = Client::orderBy('name')->get(['id', 'name']);
        return view('firm-registrations.create', compact('firm', 'clients'));
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $data['created_by'] = auth()->id();
        $firm = FirmRegistration::create($data);

        return redirect()->route('firm-registrations.show', $firm)->with('success', 'Firm saved. Download the documents below.');
    }

    public function show(FirmRegistration $firmRegistration)
    {
        $firmRegistration->load('client', 'creator');
        return view('firm-registrations.show', ['firm' => $firmRegistration]);
    }

    public function edit(FirmRegistration $firmRegistration)
    {
        $clients = Client::orderBy('name')->get(['id', 'name']);
        return view('firm-registrations.edit', ['firm' => $firmRegistration, 'clients' => $clients]);
    }

    public function update(Request $request, FirmRegistration $firmRegistration)
    {
        $firmRegistration->update($this->validated($request));
        return redirect()->route('firm-registrations.show', $firmRegistration)->with('success', 'Firm updated.');
    }

    public function destroy(FirmRegistration $firmRegistration)
    {
        $firmRegistration->delete();
        return redirect()->route('firm-registrations.index')->with('success', 'Firm deleted.');
    }

    /** Download one document (form-a | affidavit | deed) or all three as a zip. */
    public function download(FirmRegistration $firmRegistration, string $document)
    {
        $docs = new FirmDocuments($firmRegistration);
        $tmp = tempnam(sys_get_temp_dir(), 'firm');

        if ($document === 'all') {
            $docs->saveZip($tmp);
            $name = preg_replace('/[\\\\\/:*?"<>|]+/', '', $firmRegistration->firm_name) . ' - Registration Documents.zip';
            return response()->download($tmp, $name, ['Content-Type' => 'application/zip'])->deleteFileAfterSend();
        }

        abort_unless(array_key_exists($document, FirmDocuments::DOCS), 404);
        $docs->save($document, $tmp);

        return response()->download($tmp, $docs->filename($document), [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        ])->deleteFileAfterSend();
    }

    private function validated(Request $request): array
    {
        $v = $request->validate([
            'client_id' => 'nullable|exists:clients,id',
            'firm_name' => 'required|string|max:255',
            'principal_place' => 'required|string|max:500',
            'other_places' => 'nullable|string|max:500',
            'business_description' => 'required|string|max:5000',
            'deed_date' => 'required|date',
            'commencement_date' => 'required|date',
            'duration' => 'required|string|max:100',
            'managing_partner' => 'nullable|integer|min:0',
            'managing_powers' => 'nullable|string|max:10000',
            'stamp_top_cm' => 'required|numeric|min:0|max:25',
            'notes' => 'nullable|string|max:5000',

            'partners' => 'required|array|min:2',
            'partners.*.name' => 'required|string|max:255',
            'partners.*.relation' => 'required|in:S/O,D/O,W/O',
            'partners.*.father_name' => 'required|string|max:255',
            'partners.*.cnic' => ['required', 'string', 'regex:/^\d{5}-\d{7}-\d$/'],
            'partners.*.address' => 'required|string|max:500',
            'partners.*.cell' => 'nullable|string|max:30',
            'partners.*.capital_share' => 'required|numeric|min:0|max:100',
            'partners.*.profit_share' => 'required|numeric|min:0|max:100',

            'witnesses' => 'required|array|size:2',
            'witnesses.*.name' => 'required|string|max:255',
            'witnesses.*.relation' => 'required|in:S/O,D/O,W/O',
            'witnesses.*.father_name' => 'required|string|max:255',
            'witnesses.*.cnic' => ['nullable', 'string', 'regex:/^\d{5}-\d{7}-\d$/'],
            'witnesses.*.address' => 'nullable|string|max:500',
            'witnesses.*.cell' => 'nullable|string|max:30',
        ], [
            'partners.*.cnic.regex' => 'Partner CNIC must be in the format 12345-1234567-1.',
            'witnesses.*.cnic.regex' => 'Witness CNIC must be in the format 12345-1234567-1.',
        ]);

        $v['partners'] = array_values($v['partners']);
        $v['witnesses'] = array_values($v['witnesses']);

        foreach (['capital_share' => 'Capital', 'profit_share' => 'Profit & loss'] as $key => $label) {
            $total = array_sum(array_map(fn ($p) => (float) $p[$key], $v['partners']));
            if (abs($total - 100) > 0.01) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    "partners" => "$label shares add up to {$total}%, they must total 100%.",
                ]);
            }
        }

        if (isset($v['managing_partner']) && !isset($v['partners'][$v['managing_partner']])) {
            $v['managing_partner'] = null;
        }
        $v['managing_partner'] = $request->input('managing_partner') === '' ? null : ($v['managing_partner'] ?? null);

        return $v;
    }
}
