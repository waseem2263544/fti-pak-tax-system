<?php

namespace App\Http\Controllers;

use App\Models\FbrNotice;
use App\Models\Client;
use App\Models\User;
use Illuminate\Http\Request;

class FbrNoticeController extends Controller
{
    public function index(Request $request)
    {
        $query = FbrNotice::with('client');

        // Default: show only unread + read (needs attention)
        $filter = $request->get('filter', 'pending');

        if ($filter === 'pending') {
            $query->whereIn('status', ['new', 'reviewed']);
        } elseif ($filter === 'actioned') {
            $query->whereIn('status', ['resolved', 'escalated']);
        }
        // 'all' shows everything

        if ($request->filled('tax_year')) {
            $query->where('tax_year', $request->tax_year);
        }

        if ($request->filled('section')) {
            $query->where('notice_section', $request->section);
        }

        $notices = $query->orderBy('email_received_at', 'desc')->paginate(20);

        $taxYears = FbrNotice::distinct('tax_year')->whereNotNull('tax_year')->pluck('tax_year');
        $sections = FbrNotice::distinct('notice_section')->whereNotNull('notice_section')->pluck('notice_section');

        $pendingCount = FbrNotice::whereIn('status', ['new', 'reviewed'])->count();
        $actionedCount = FbrNotice::whereIn('status', ['resolved', 'escalated'])->count();

        return view('fbr-notices.index', compact('notices', 'taxYears', 'sections', 'filter', 'pendingCount', 'actionedCount'));
    }

    public function show(FbrNotice $fbrNotice)
    {
        $fbrNotice->load('client', 'escalatedTo');

        // Auto-mark as read when opened
        if ($fbrNotice->status === 'new') {
            $fbrNotice->update(['status' => 'reviewed']);
        }

        return view('fbr-notices.show', compact('fbrNotice'));
    }

    public function updateStatus(Request $request, FbrNotice $fbrNotice)
    {
        $validated = $request->validate([
            'status' => 'required|in:new,reviewed,resolved,escalated',
        ]);

        $fbrNotice->update(['status' => $validated['status']]);

        return redirect()->back()->with('success', 'Notice status updated');
    }

    public function escalate(Request $request, FbrNotice $fbrNotice)
    {
        $validated = $request->validate([
            'escalated_to' => 'required|exists:users,id',
        ]);

        $fbrNotice->update([
            'is_escalated' => true,
            'escalated_to' => $validated['escalated_to'],
            'status' => 'escalated',
        ]);

        return redirect()->back()->with('success', 'Notice escalated successfully');
    }

    public function assignClient(Request $request, FbrNotice $fbrNotice)
    {
        $validated = $request->validate([
            'client_id' => 'required|exists:clients,id',
        ]);

        $fbrNotice->update(['client_id' => $validated['client_id']]);

        return redirect()->back()->with('success', 'Notice assigned to client');
    }

    public function dismiss(FbrNotice $fbrNotice)
    {
        $fbrNotice->update(['status' => 'resolved']);
        return redirect()->back()->with('success', 'Notice dismissed');
    }

    public function markRead(FbrNotice $fbrNotice)
    {
        if ($fbrNotice->status === 'new') {
            $fbrNotice->update(['status' => 'reviewed']);
        }
        return response()->json(['success' => true]);
    }

    public function download(FbrNotice $fbrNotice)
    {
        return response()->json([
            'subject' => $fbrNotice->subject,
            'body' => $fbrNotice->body,
            'raw_content' => $fbrNotice->raw_content,
        ]);
    }
}
