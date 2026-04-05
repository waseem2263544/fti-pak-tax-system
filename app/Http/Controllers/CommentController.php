<?php

namespace App\Http\Controllers;

use App\Models\Comment;
use Illuminate\Http\Request;

class CommentController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'body' => 'required|string|max:2000',
            'commentable_type' => 'required|in:task,proceeding,process',
            'commentable_id' => 'required|integer',
        ]);

        $typeMap = [
            'task' => \App\Models\Task::class,
            'proceeding' => \App\Models\Proceeding::class,
            'process' => \App\Models\Process::class,
        ];

        $model = $typeMap[$request->commentable_type]::findOrFail($request->commentable_id);

        Comment::create([
            'user_id' => auth()->id(),
            'commentable_type' => get_class($model),
            'commentable_id' => $model->id,
            'body' => $request->body,
        ]);

        return back()->with('success', 'Comment added');
    }

    public function destroy(Comment $comment)
    {
        if ($comment->user_id !== auth()->id()) {
            return back()->with('error', 'You can only delete your own comments');
        }
        $comment->delete();
        return back()->with('success', 'Comment deleted');
    }
}
