<?php

namespace App\Http\Controllers;

use App\Models\FbrNotice;
use App\Models\Client;
use Illuminate\Http\Request;

class FbrNoticeController extends Controller
{
    /**
     * Display all FBR notices.
     */
    public function index(Request $request)
    {
        $query = FbrNotice::with('client');

        if ($request->status) {
            $query->where('status', $request->status);
        }

        if ($request->tax_year) {
            $query->where('tax_year', $request->tax_year);
        }

        if ($request->section) {
            $query->where('notice_section', $request->section);
        }

        if ($request->is_escalated) {
            $query->where('is_escalated', true);
        }

        $notices = $query->orderBy('email_received_at', 'desc')->paginate(20);
        
        $taxYears = FbrNotice::distinct('tax_year')->pluck('tax_year');
        $sections = FbrNotice::distinct('notice_section')->pluck('notice_section');

        return view('fbr-notices.index', compact('notices', 'taxYears', 'sections'));
    }

    /**
     * Show notice details.
     */
    public function show(FbrNotice $notice)
    {
        $notice->load('client', 'escalatedTo');
        return view('fbr-notices.show', compact('notice'));
    }

    /**
     * Update notice status.
     */
    public function updateStatus(Request $request, FbrNotice $notice)
    {
        $validated = $request->validate([
            'status' => 'required|in:new,reviewed,resolved,escalated',
        ]);

        $notice->update(['status' => $validated['status']]);

        return redirect()->back()->with('success', 'Notice status updated');
    }

    /**
     * Escalate notice to manager.
     */
    public function escalate(Request $request, FbrNotice $notice)
    {
        $validated = $request->validate([
            'escalated_to' => 'required|exists:users,id',
        ]);

        $notice->update([
            'is_escalated' => true,
            'escalated_to' => $validated['escalated_to'],
            'status' => 'escalated',
        ]);

        return redirect()->back()->with('success', 'Notice escalated successfully');
    }

    /**
     * Assign notice to client.
     */
    public function assignClient(Request $request, FbrNotice $notice)
    {
        $validated = $request->validate([
            'client_id' => 'required|exists:clients,id',
        ]);

        $notice->update(['client_id' => $validated['client_id']]);

        return redirect()->back()->with('success', 'Notice assigned to client');
    }

    /**
     * Download raw notice content.
     */
    public function download(FbrNotice $notice)
    {
        return response()->json([
            'subject' => $notice->subject,
            'body' => $notice->body,
            'raw_content' => $notice->raw_content,
        ]);
    }
}
