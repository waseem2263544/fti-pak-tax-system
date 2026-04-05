<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Service;
use Illuminate\Http\Request;

class ClientController extends Controller
{
    /**
     * Display all clients.
     */
    public function index(Request $request)
    {
        $query = Client::query();

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('contact_no', 'like', "%{$search}%")
                  ->orWhere('fbr_username', 'like', "%{$search}%");
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('service')) {
            $query->whereHas('activeServices', function ($q) use ($request) {
                $q->where('services.id', $request->service);
            });
        }

        $clients = $query->orderBy('name')->paginate(20)->withQueryString();
        $services = Service::orderBy('display_name')->get();

        return view('clients.index', compact('clients', 'services'));
    }

    /**
     * Show client creation form.
     */
    public function create()
    {
        $services = Service::all();
        $potentialShareholders = Client::orderBy('name')->get();
        return view('clients.create', compact('services', 'potentialShareholders'));
    }

    /**
     * Store a newly created client.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:clients',
            'contact_no' => 'required|string|max:20',
            'status' => 'required|in:Individual,AOP,Company',
            'notes' => 'nullable|string',
            'fbr_username' => 'nullable|string',
            'fbr_password' => 'nullable|string',
            'it_pin_code' => 'nullable|string',
            'kpra_username' => 'nullable|string',
            'kpra_password' => 'nullable|string',
            'kpra_pin' => 'nullable|string',
            'secp_password' => 'nullable|string',
            'secp_pin' => 'nullable|string',
            'folder_link' => 'nullable|url',
            'shareholders' => 'nullable|array',
            'share_percentages' => 'nullable|array',
            'services' => 'nullable|array',
        ]);

        $client = Client::create($validated);

        // Attach shareholders
        if ($request->shareholders) {
            foreach ($request->shareholders as $i => $shareholderId) {
                if ($shareholderId) {
                    $sharePercentage = $request->share_percentages[$i] ?? null;
                    $client->shareholders()->attach($shareholderId, [
                        'share_percentage' => $sharePercentage
                    ]);
                }
            }
        }

        // Attach active services
        if ($request->services) {
            $client->activeServices()->attach($request->services, [
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        return redirect()->route('clients.show', $client)->with('success', 'Client created successfully');
    }

    /**
     * Show client details.
     */
    public function show(Client $client)
    {
        $client->load(['shareholders', 'activeServices', 'fbrNotices', 'tasks']);
        return view('clients.show', compact('client'));
    }

    /**
     * Show edit form.
     */
    public function edit(Client $client)
    {
        $services = Service::all();
        $potentialShareholders = Client::where('id', '!=', $client->id)->orderBy('name')->get();
        $client->load(['shareholders', 'activeServices']);
        return view('clients.edit', compact('client', 'services', 'potentialShareholders'));
    }

    /**
     * Update client.
     */
    public function update(Request $request, Client $client)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:clients,email,' . $client->id,
            'contact_no' => 'required|string|max:20',
            'status' => 'required|in:Individual,AOP,Company',
            'notes' => 'nullable|string',
            'fbr_username' => 'nullable|string',
            'fbr_password' => 'nullable|string',
            'it_pin_code' => 'nullable|string',
            'kpra_username' => 'nullable|string',
            'kpra_password' => 'nullable|string',
            'kpra_pin' => 'nullable|string',
            'secp_password' => 'nullable|string',
            'secp_pin' => 'nullable|string',
            'folder_link' => 'nullable|url',
            'shareholders' => 'nullable|array',
            'share_percentages' => 'nullable|array',
            'services' => 'nullable|array',
        ]);

        $client->update($validated);

        // Update shareholders
        $client->shareholders()->detach();
        if ($request->shareholders) {
            foreach ($request->shareholders as $i => $shareholderId) {
                if ($shareholderId) {
                    $sharePercentage = $request->share_percentages[$i] ?? null;
                    $client->shareholders()->attach($shareholderId, [
                        'share_percentage' => $sharePercentage
                    ]);
                }
            }
        }

        // Update services
        $client->activeServices()->detach();
        if ($request->services) {
            $client->activeServices()->attach($request->services, [
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        return redirect()->route('clients.show', $client)->with('success', 'Client updated successfully');
    }

    /**
     * Delete client.
     */
    public function destroy(Client $client)
    {
        $client->delete();
        return redirect()->route('clients.index')->with('success', 'Client deleted successfully');
    }
}
