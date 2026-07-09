<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\ItReturnTracker;
use Illuminate\Http\Request;

class IncomeTaxReturnController extends Controller
{
    /** Dashboard + tracking list of clients whose active service is Income Tax Return. */
    public function index(Request $request)
    {
        $statuses = ItReturnTracker::STATUSES;

        $clients = Client::query()
            ->whereHas('activeServices', fn($q) => $q->where('services.name', 'income_tax_return'))
            ->with('itReturnTracker')
            ->orderBy('name')
            ->get()
            ->map(function ($client) {
                $client->tracker_status = $client->itReturnTracker->status ?? ItReturnTracker::DEFAULT_STATUS;
                $client->tracker_remarks = $client->itReturnTracker->remarks ?? '';
                $client->tracker_updated = optional($client->itReturnTracker)->updated_at;
                return $client;
            });

        // Dashboard counts + percentages
        $total = $clients->count();
        $counts = [];
        foreach (array_keys($statuses) as $key) {
            $n = $clients->where('tracker_status', $key)->count();
            $counts[$key] = ['count' => $n, 'pct' => $total ? round($n / $total * 100) : 0];
        }
        $filedPct = $total ? round($counts['filed']['count'] / $total * 100) : 0;

        // Optional filter
        if ($request->filled('status') && isset($statuses[$request->status])) {
            $clients = $clients->where('tracker_status', $request->status)->values();
        }
        if ($request->filled('q')) {
            $needle = mb_strtolower($request->q);
            $clients = $clients->filter(fn($c) => str_contains(mb_strtolower($c->name), $needle))->values();
        }

        return view('income-tax-returns.index', compact('clients', 'statuses', 'counts', 'total', 'filedPct'));
    }

    /** Upsert the status / remarks for a client. Returns JSON for inline saving. */
    public function update(Request $request, Client $client)
    {
        $validated = $request->validate([
            'status'  => 'nullable|in:' . implode(',', array_keys(ItReturnTracker::STATUSES)),
            'remarks' => 'nullable|string|max:2000',
        ]);

        $tracker = ItReturnTracker::firstOrNew(['client_id' => $client->id]);
        if (!$tracker->exists) {
            $tracker->status = ItReturnTracker::DEFAULT_STATUS;
        }
        if (array_key_exists('status', $validated) && $validated['status']) {
            $tracker->status = $validated['status'];
        }
        if ($request->has('remarks')) {
            $tracker->remarks = $validated['remarks'] ?? null;
        }
        $tracker->updated_by = auth()->id();
        $tracker->save();

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'ok' => true,
                'status' => $tracker->status,
                'updated_at' => optional($tracker->updated_at)->format('d M Y H:i'),
            ]);
        }

        return back()->with('success', 'Updated.');
    }
}
