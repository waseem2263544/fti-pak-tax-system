<?php

namespace App\Http\Controllers;

use App\Models\FileNumber;
use App\Models\LetterNumber;
use App\Models\Client;
use Illuminate\Http\Request;

class FileController extends Controller
{
    public function index(Request $request)
    {
        $tab = $request->get('tab', 'files');

        $search = trim((string) $request->get('q', ''));

        $files = FileNumber::with('client');

        // The register runs to hundreds of rows, so it needs to be searchable
        // by number, by the name it recorded, by a linked client, or by what
        // the file is about.
        if ($search !== '') {
            $files->where(function ($q) use ($search) {
                $q->where('file_no', 'like', "%{$search}%")
                  ->orWhere('client_name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhereHas('client', fn($c) => $c->where('name', 'like', "%{$search}%"));
            });
        }

        $fileNumbers = $files->orderBy('file_no', 'desc')->paginate(20, ['*'], 'files_page')->withQueryString();
        $letters = LetterNumber::with('client');

        if ($search !== '') {
            $letters->where(function ($q) use ($search) {
                $q->where('reference', 'like', "%{$search}%")
                  ->orWhere('client_name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhereHas('client', fn($c) => $c->where('name', 'like', "%{$search}%"));
            });
        }

        // Undated rows would otherwise sort to the top of a date-desc list and
        // bury the recent ones, so the reference carries the ordering.
        $letterNumbers = $letters->orderByDesc('year')->orderByDesc('sequence_no')
            ->paginate(20, ['*'], 'letters_page')->withQueryString();
        $clients = Client::orderBy('name')->get();

        $nextFileNo = FileNumber::nextNumber();
        $nextLetterRef = LetterNumber::generateReference();

        return view('files.index', compact('fileNumbers', 'letterNumbers', 'clients', 'tab', 'nextFileNo', 'nextLetterRef', 'search'));
    }

    public function storeFile(Request $request)
    {
        $request->validate([
            'client_id'   => 'nullable|exists:clients,id',
            'client_name' => 'nullable|string|max:255',
            'file_no'     => 'nullable|integer',
            'description' => 'nullable|string|max:500',
        ]);

        // A file can be opened for someone who is not a client on the books,
        // which is most of the historic register, so one or the other will do.
        if (!$request->client_id && !trim((string) $request->client_name)) {
            return back()->withInput()->with('error', 'Choose a client, or type a name for the file.');
        }

        FileNumber::create([
            'file_no'     => $request->file_no ?: FileNumber::nextNumber(),
            'client_id'   => $request->client_id ?: null,
            'client_name' => $request->client_id ? null : trim($request->client_name),
            'description' => $request->description,
        ]);

        return redirect()->route('files.index', ['tab' => 'files'])->with('success', 'File number created');
    }

    public function storeLetter(Request $request)
    {
        $request->validate([
            'client_id'   => 'nullable|exists:clients,id',
            'client_name' => 'nullable|string|max:255',
            'description' => 'required|string|max:500',
            'date'        => 'required|date',
            'reference'   => 'nullable|string|max:255',
        ]);

        if (!$request->client_id && !trim((string) $request->client_name)) {
            return back()->withInput()->with('error', 'Choose a client, or type a name for the letter.');
        }

        $reference = $request->reference;
        $year = now()->year;
        $seq = LetterNumber::nextSequence();

        if (empty($reference)) {
            $reference = 'FTI/' . str_pad($seq, 3, '0', STR_PAD_LEFT) . '/' . $year;
        } else {
            // Try to extract seq and year from custom reference
            if (preg_match('/(\d+)\/(\d{2,4})$/', $reference, $m)) {
                $seq = intval($m[1]);
                $year = intval($m[2]);
                if ($year < 100) $year += 2000;
            }
        }

        LetterNumber::create([
            'date'        => $request->date,
            'reference'   => $reference,
            'sequence_no' => $seq,
            'year'        => $year,
            'client_id'   => $request->client_id ?: null,
            'client_name' => $request->client_id ? null : trim($request->client_name),
            'description' => $request->description,
        ]);

        return redirect()->route('files.index', ['tab' => 'letters'])->with('success', 'Letter number created: ' . $reference);
    }

    public function updateFile(Request $request, FileNumber $fileNumber)
    {
        $request->validate([
            'file_no'     => 'required|integer|min:1',
            'client_id'   => 'nullable|exists:clients,id',
            'client_name' => 'nullable|string|max:255',
            'description' => 'nullable|string|max:500',
        ]);

        if (!$request->client_id && !trim((string) $request->client_name)) {
            return back()->with('error', 'Choose a client, or type a name for the file.');
        }

        $fileNumber->update([
            'file_no'     => $request->file_no,
            'client_id'   => $request->client_id ?: null,
            'client_name' => $request->client_id ? null : trim($request->client_name),
            'description' => $request->description,
        ]);

        return redirect()->route('files.index', ['tab' => 'files'])->with('success', 'File number updated');
    }

    public function updateLetter(Request $request, LetterNumber $letterNumber)
    {
        $request->validate([
            'reference'   => 'required|string|max:255',
            'date'        => 'nullable|date',
            'client_id'   => 'nullable|exists:clients,id',
            'client_name' => 'nullable|string|max:255',
            'description' => 'nullable|string|max:500',
        ]);

        if (!$request->client_id && !trim((string) $request->client_name)) {
            return back()->with('error', 'Choose a client, or type a name for the letter.');
        }

        $data = [
            'reference'   => trim($request->reference),
            'date'        => $request->date ?: null,
            'client_id'   => $request->client_id ?: null,
            'client_name' => $request->client_id ? null : trim($request->client_name),
            'description' => $request->description,
        ];

        // Keep the sequence and year in step when the reference is corrected,
        // since the list is ordered by them.
        if (preg_match('#^(?i:fti|fit|tif)\s*/\s*(\d+)\s*/\s*(\d{2,4})$#', $data['reference'], $m)) {
            $data['sequence_no'] = (int) $m[1];
            $data['year'] = strlen($m[2]) === 2 ? 2000 + (int) $m[2] : (int) $m[2];
        }

        // A date typed in by hand supersedes whatever unreadable text the sheet had.
        if ($request->date) {
            $data['raw_date'] = null;
        }

        $letterNumber->update($data);

        return redirect()->route('files.index', ['tab' => 'letters'])->with('success', 'Letter number updated');
    }

    public function destroyFile(FileNumber $fileNumber)
    {
        $fileNumber->delete();
        return redirect()->route('files.index', ['tab' => 'files'])->with('success', 'File number deleted');
    }

    public function destroyLetter(LetterNumber $letterNumber)
    {
        $letterNumber->delete();
        return redirect()->route('files.index', ['tab' => 'letters'])->with('success', 'Letter number deleted');
    }
}
