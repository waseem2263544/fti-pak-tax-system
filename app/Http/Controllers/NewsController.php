<?php

namespace App\Http\Controllers;

use App\Models\NewsArticle;
use Illuminate\Http\Request;

class NewsController extends Controller
{
    public function index(Request $request)
    {
        $query = NewsArticle::query();

        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }

        if ($request->filled('source')) {
            $query->where('source', $request->source);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('summary', 'like', "%{$search}%");
            });
        }

        $news = $query->orderBy('published_at', 'desc')->paginate(20)->withQueryString();

        $categories = NewsArticle::distinct()->pluck('category')->filter()->sort();
        $sources = NewsArticle::distinct()->pluck('source')->filter()->sort();

        return view('news.index', compact('news', 'categories', 'sources'));
    }

    public function fetchNow()
    {
        \Illuminate\Support\Facades\Artisan::call('app:fetch-tax-news');
        $output = \Illuminate\Support\Facades\Artisan::output();

        preg_match('/Total new articles: (\d+)/', $output, $matches);
        $count = $matches[1] ?? 0;

        if ($count > 0) {
            return redirect()->route('news.index')->with('success', "Fetched $count new tax articles.");
        }
        return redirect()->route('news.index')->with('success', 'No new tax articles found. All up to date.');
    }

    public function togglePin(NewsArticle $newsArticle)
    {
        $newsArticle->update(['is_pinned' => !$newsArticle->is_pinned]);
        return back();
    }

    public function destroy(NewsArticle $newsArticle)
    {
        $newsArticle->delete();
        return back()->with('success', 'Article removed');
    }
}
