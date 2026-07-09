<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\ItReturnTracker;
use App\Models\User;
use Illuminate\Http\Request;

class IncomeTaxReturnController extends Controller
{
    /** Dashboard + tracking list of clients whose active service is Income Tax Return. */
    public function index(Request $request)
    {
        $statuses = ItReturnTracker::STATUSES;

        $users = User::orderBy('name')->get(['id', 'name']);
        $showSkipped = $request->boolean('skipped');

        $all = Client::query()
            ->whereHas('activeServices', fn($q) => $q->where('services.name', 'income_tax_return'))
            ->with('itReturnTracker.assignee')
            ->orderBy('name')
            ->get()
            ->map(function ($client) {
                $client->tracker_status = $client->itReturnTracker->status ?? ItReturnTracker::DEFAULT_STATUS;
                $client->tracker_remarks = $client->itReturnTracker->remarks ?? '';
                $client->tracker_assigned = optional($client->itReturnTracker)->assigned_to;
                // Tracker contact/folder are INDEPENDENT of the client record (may differ per return).
                $client->tracker_contact = optional($client->itReturnTracker)->contact_number;
                $client->tracker_folder = optional($client->itReturnTracker)->folder_link;
                $client->tracker_skipped = (bool) optional($client->itReturnTracker)->skipped;
                $client->tracker_updated = optional($client->itReturnTracker)->updated_at;
                return $client;
            });

        $active = $all->where('tracker_skipped', false);
        $skippedCount = $all->where('tracker_skipped', true)->count();

        // Dashboard counts + percentages (over the active, non-skipped set)
        $total = $active->count();
        $counts = [];
        foreach (array_keys($statuses) as $key) {
            $n = $active->where('tracker_status', $key)->count();
            $counts[$key] = ['count' => $n, 'pct' => $total ? round($n / $total * 100) : 0];
        }
        $filedPct = $total ? round($counts['filed']['count'] / $total * 100) : 0;
        $mineCount = $active->where('tracker_assigned', auth()->id())->count();

        // Base list: skipped view shows hidden clients; otherwise the active set
        $clients = ($showSkipped ? $all->where('tracker_skipped', true) : $active)->values();

        // Optional filters
        if (!$showSkipped && $request->boolean('mine')) {
            $clients = $clients->where('tracker_assigned', auth()->id())->values();
        }
        if ($request->filled('status') && isset($statuses[$request->status])) {
            $clients = $clients->where('tracker_status', $request->status)->values();
        }
        if ($request->filled('q')) {
            $needle = mb_strtolower($request->q);
            $clients = $clients->filter(fn($c) => str_contains(mb_strtolower($c->name), $needle))->values();
        }

        return view('income-tax-returns.index', compact('clients', 'statuses', 'counts', 'total', 'filedPct', 'users', 'mineCount', 'showSkipped', 'skippedCount'));
    }

    /** Upsert the status / remarks for a client. Returns JSON for inline saving. */
    public function update(Request $request, Client $client)
    {
        $validated = $request->validate([
            'status'         => 'nullable|in:' . implode(',', array_keys(ItReturnTracker::STATUSES)),
            'assigned_to'    => 'nullable|exists:users,id',
            'contact_number' => 'nullable|string|max:50',
            'folder_link'    => 'nullable|string|max:500',
            'skipped'        => 'nullable|boolean',
            'remarks'        => 'nullable|string|max:2000',
        ]);

        $tracker = ItReturnTracker::firstOrNew(['client_id' => $client->id]);
        if (!$tracker->exists) {
            $tracker->status = ItReturnTracker::DEFAULT_STATUS;
        }
        if (array_key_exists('status', $validated) && $validated['status']) {
            $tracker->status = $validated['status'];
        }
        if ($request->has('assigned_to')) {
            $tracker->assigned_to = $validated['assigned_to'] ?: null;
        }
        if ($request->has('contact_number')) {
            $tracker->contact_number = $validated['contact_number'] ?: null;
        }
        if ($request->has('folder_link')) {
            $tracker->folder_link = $validated['folder_link'] ?: null;
        }
        if ($request->has('skipped')) {
            $tracker->skipped = $request->boolean('skipped');
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
