<?php

namespace App\Http\Controllers;

use App\Models\Comment;
use App\Models\Notification;
use App\Models\User;
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

        $comment = Comment::create([
            'user_id' => auth()->id(),
            'commentable_type' => get_class($model),
            'commentable_id' => $model->id,
            'body' => $request->body,
        ]);

        // Parse @mentions and notify
        $this->processMentions($request->body, $model, $request->commentable_type);

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

    private function processMentions($body, $model, $type)
    {
        preg_match_all('/@\[user:(\d+)\]/', $body, $matches);

        if (empty($matches[1])) return;

        $mentionedUserIds = array_unique($matches[1]);
        $typeName = ucfirst($type);
        $title = $model->title ?? '';

        foreach ($mentionedUserIds as $userId) {
            if ((int) $userId === auth()->id()) continue;

            Notification::create([
                'user_id' => $userId,
                'client_id' => $model->client_id ?? null,
                'title' => auth()->user()->name . ' mentioned you',
                'message' => "In {$typeName}: {$title}",
                'type' => 'task',
                'priority' => 'medium',
                'related_task_id' => $model instanceof \App\Models\Task ? $model->id : null,
            ]);
        }
    }
}
