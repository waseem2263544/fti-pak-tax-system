<?php

namespace App\Http\Controllers;

use App\Models\ClientFile;
use App\Models\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class FileController extends Controller
{
    public function index(Request $request)
    {
        $query = ClientFile::with('client', 'uploadedBy');

        if ($request->client_id) {
            $query->where('client_id', $request->client_id);
        }
        if ($request->category) {
            $query->where('category', $request->category);
        }

        $files = $query->orderBy('created_at', 'desc')->paginate(20);
        $clients = Client::orderBy('name')->get();
        $categories = ClientFile::distinct()->whereNotNull('category')->pluck('category');

        return view('files.index', compact('files', 'clients', 'categories'));
    }

    public function create()
    {
        $clients = Client::orderBy('name')->get();
        return view('files.create', compact('clients'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'client_id' => 'required|exists:clients,id',
            'file' => 'required|file|max:20480',
            'category' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
        ]);

        $uploaded = $request->file('file');
        $filename = time() . '_' . $uploaded->getClientOriginalName();
        $uploaded->storeAs('client-files', $filename, 'local');

        ClientFile::create([
            'client_id' => $request->client_id,
            'uploaded_by' => auth()->id(),
            'filename' => $filename,
            'original_name' => $uploaded->getClientOriginalName(),
            'mime_type' => $uploaded->getMimeType(),
            'size' => $uploaded->getSize(),
            'category' => $request->category,
            'notes' => $request->notes,
        ]);

        return redirect()->route('files.index')->with('success', 'File uploaded successfully');
    }

    public function download(ClientFile $file)
    {
        $path = storage_path('app/client-files/' . $file->filename);
        if (!file_exists($path)) {
            return back()->with('error', 'File not found');
        }
        return response()->download($path, $file->original_name);
    }

    public function destroy(ClientFile $file)
    {
        Storage::disk('local')->delete('client-files/' . $file->filename);
        $file->delete();
        return redirect()->route('files.index')->with('success', 'File deleted');
    }
}
