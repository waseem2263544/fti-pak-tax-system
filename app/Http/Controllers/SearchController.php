<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Task;
use App\Models\Proceeding;
use App\Models\Process;
use App\Models\FbrNotice;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    public function index(Request $request)
    {
        $q = $request->get('q', '');
        $results = [];

        if (strlen($q) < 2) {
            return view('search.index', compact('q', 'results'));
        }

        $results['clients'] = Client::where('name', 'like', "%{$q}%")
            ->orWhere('email', 'like', "%{$q}%")
            ->orWhere('contact_no', 'like', "%{$q}%")
            ->orWhere('fbr_username', 'like', "%{$q}%")
            ->orderBy('name')->limit(10)->get();

        $results['tasks'] = Task::with('client', 'assignedUsers')
            ->where('title', 'like', "%{$q}%")
            ->orWhere('description', 'like', "%{$q}%")
            ->orderBy('created_at', 'desc')->limit(10)->get();

        $results['proceedings'] = Proceeding::with('client')
            ->where('title', 'like', "%{$q}%")
            ->orWhere('case_number', 'like', "%{$q}%")
            ->orWhere('section', 'like', "%{$q}%")
            ->orderBy('created_at', 'desc')->limit(10)->get();

        $results['processes'] = Process::with('client', 'service')
            ->where('title', 'like', "%{$q}%")
            ->orderBy('created_at', 'desc')->limit(10)->get();

        $results['notices'] = FbrNotice::with('client')
            ->where('subject', 'like', "%{$q}%")
            ->orWhere('notice_section', 'like', "%{$q}%")
            ->orderBy('email_received_at', 'desc')->limit(10)->get();

        $results['documents'] = Client::whereNotNull('folder_link')
            ->where('folder_link', '!=', '')
            ->where(function ($query) use ($q) {
                $query->where('name', 'like', "%{$q}%")
                      ->orWhere('folder_link', 'like', "%{$q}%");
            })
            ->orderBy('name')->limit(10)->get();

        $totalResults = collect($results)->sum(fn($r) => $r->count());

        return view('search.index', compact('q', 'results', 'totalResults'));
    }

    public function suggest(Request $request)
    {
        $q = $request->get('q', '');
        if (strlen($q) < 2) return response()->json([]);

        $suggestions = [];

        Client::where('name', 'like', "%{$q}%")->limit(5)->get()->each(function ($c) use (&$suggestions) {
            $suggestions[] = ['type' => 'Client', 'title' => $c->name, 'url' => route('clients.show', $c), 'icon' => 'bi-people-fill'];
        });

        Task::where('title', 'like', "%{$q}%")->limit(3)->get()->each(function ($t) use (&$suggestions) {
            $suggestions[] = ['type' => 'Task', 'title' => $t->title, 'url' => route('tasks.show', $t), 'icon' => 'bi-check2-square'];
        });

        Proceeding::where('title', 'like', "%{$q}%")->limit(3)->get()->each(function ($p) use (&$suggestions) {
            $suggestions[] = ['type' => 'Proceeding', 'title' => $p->title, 'url' => route('proceedings.show', $p), 'icon' => 'bi-bank2'];
        });

        Client::whereNotNull('folder_link')->where('folder_link', '!=', '')
            ->where('name', 'like', "%{$q}%")->limit(3)->get()->each(function ($c) use (&$suggestions) {
                $suggestions[] = ['type' => 'Document Folder', 'title' => $c->name, 'url' => $c->sharePointUrl, 'icon' => 'bi-folder2-open'];
            });

        return response()->json($suggestions);
    }
}
