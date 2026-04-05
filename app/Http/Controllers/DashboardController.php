<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Task;
use App\Models\FbrNotice;
use App\Models\Notification;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    /**
     * Show dashboard.
     */
    public function index()
    {
        $user = auth()->user();

        // Get dashboard stats
        $totalClients = Client::count();
        $pendingProceedings = \App\Models\Proceeding::whereIn('status', ['pending', 'adjourned'])->count();
        $pendingTasks = Task::where('status', 'pending')->count();
        $overdueTasks = Task::where('status', 'overdue')->count();
        $newFbrNotices = FbrNotice::where('status', 'new')->count();
        $escalatedNotices = FbrNotice::where('is_escalated', true)->count();

        // Get user's assigned tasks
        $myTasks = $user->tasks()
            ->where('status', '!=', 'completed')
            ->orderBy('due_date')
            ->limit(5)
            ->get();

        // Get recent FBR notices
        $recentNotices = FbrNotice::orderBy('email_received_at', 'desc')
            ->limit(5)
            ->get();

        // Get user's unread notifications
        $unreadNotifications = $user->unreadNotifications()
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        // Get recent clients
        $recentClients = Client::orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        return view('dashboard', compact(
            'totalClients', 'pendingProceedings', 'pendingTasks', 'overdueTasks',
            'newFbrNotices', 'escalatedNotices', 'myTasks', 'recentNotices',
            'unreadNotifications', 'recentClients'
        ));
    }
}
