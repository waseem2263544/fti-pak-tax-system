<?php

namespace App\Http\Controllers\Social;

use App\Http\Controllers\Controller;
use App\Models\SocialIdea;
use App\Models\SocialPost;
use App\Services\Social\SocialContentGenerator;
use Illuminate\Http\Request;

/**
 * The idea bank: topics waiting to become posts. The daily run uses the
 * oldest first; "Suggest ideas" asks Claude to research what is due soon.
 */
class SocialIdeaController extends Controller
{
    public function index(Request $request)
    {
        $status = in_array($request->get('status'), ['new', 'used', 'dismissed'], true) ? $request->get('status') : 'new';

        return view('social.ideas', [
            'ideas'  => SocialIdea::with('post')->where('status', $status)
                ->orderBy($status === 'new' ? 'id' : 'updated_at', $status === 'new' ? 'asc' : 'desc')
                ->paginate(40)->withQueryString(),
            'status' => $status,
            'counts' => SocialIdea::selectRaw('status, count(*) as n')->groupBy('status')->pluck('n', 'status'),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'topic'    => 'required|string|max:500',
            'angle'    => 'nullable|string|max:2000',
            'audience' => 'nullable|string|max:200',
            'format'   => 'nullable|in:image,carousel,video',
        ]);
        SocialIdea::create($data + ['status' => 'new', 'source' => 'manual']);

        return back()->with('success', 'Idea added.');
    }

    public function suggest(Request $request, SocialContentGenerator $generator)
    {
        $data = $request->validate([
            'count' => 'nullable|integer|min:3|max:20',
            'focus' => 'nullable|string|max:500',
        ]);
        @set_time_limit(300);

        try {
            $ideas = $generator->suggestIdeas((int) ($data['count'] ?? 10), (string) ($data['focus'] ?? ''));
        } catch (\Throwable $e) {
            report($e);

            return back()->with('error', 'Claude could not suggest ideas: ' . $e->getMessage());
        }

        return back()->with('success', count($ideas) . ' new ideas added.');
    }

    public function dismiss(SocialIdea $idea)
    {
        $idea->update(['status' => $idea->status === 'dismissed' ? 'new' : 'dismissed']);

        return back();
    }

    public function destroy(SocialIdea $idea)
    {
        $idea->delete();

        return back()->with('success', 'Idea deleted.');
    }
}
