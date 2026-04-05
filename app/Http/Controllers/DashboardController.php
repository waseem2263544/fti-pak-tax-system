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
        $newFbrNotices = FbrNotice::whereIn('status', ['new', 'reviewed'])->count();
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

        // Get upcoming proceedings
        $upcomingProceedings = \App\Models\Proceeding::with('client', 'assignedTo')
            ->whereIn('status', ['pending', 'adjourned'])
            ->where(function ($q) {
                $q->whereNull('hearing_date')
                  ->orWhere('hearing_date', '>=', now()->subDays(7));
            })
            ->orderByRaw('hearing_date IS NULL, hearing_date ASC')
            ->limit(10)
            ->get();

        $latestNews = \App\Models\NewsArticle::orderBy('published_at', 'desc')->limit(5)->get();

        return view('dashboard', compact(
            'totalClients', 'pendingProceedings', 'pendingTasks', 'overdueTasks',
            'newFbrNotices', 'escalatedNotices', 'myTasks', 'recentNotices',
            'unreadNotifications', 'upcomingProceedings', 'latestNews'
        ));
    }
}
