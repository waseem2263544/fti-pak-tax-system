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

        $fileNumbers = FileNumber::with('client')->orderBy('file_no', 'desc')->paginate(20, ['*'], 'files_page');
        $letterNumbers = LetterNumber::with('client')->orderBy('date', 'desc')->paginate(20, ['*'], 'letters_page');
        $clients = Client::orderBy('name')->get();

        $nextFileNo = FileNumber::nextNumber();
        $nextLetterRef = LetterNumber::generateReference();

        return view('files.index', compact('fileNumbers', 'letterNumbers', 'clients', 'tab', 'nextFileNo', 'nextLetterRef'));
    }

    public function storeFile(Request $request)
    {
        $request->validate([
            'client_id' => 'required|exists:clients,id',
            'file_no' => 'nullable|integer',
            'description' => 'nullable|string|max:500',
        ]);

        FileNumber::create([
            'file_no' => $request->file_no ?: FileNumber::nextNumber(),
            'client_id' => $request->client_id,
            'description' => $request->description,
        ]);

        return redirect()->route('files.index', ['tab' => 'files'])->with('success', 'File number created');
    }

    public function storeLetter(Request $request)
    {
        $request->validate([
            'client_id' => 'required|exists:clients,id',
            'description' => 'required|string|max:500',
            'date' => 'required|date',
            'reference' => 'nullable|string|max:255',
        ]);

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
            'date' => $request->date,
            'reference' => $reference,
            'sequence_no' => $seq,
            'year' => $year,
            'client_id' => $request->client_id,
            'description' => $request->description,
        ]);

        return redirect()->route('files.index', ['tab' => 'letters'])->with('success', 'Letter number created: ' . $reference);
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
