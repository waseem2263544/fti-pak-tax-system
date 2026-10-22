<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Social Studio: TikTok posts that Claude drafts and a person approves.
 *
 * On the live host these tables are created by public/migrate-social.php;
 * this migration keeps local and test databases in step with it.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('social_posts', function (Blueprint $table) {
            $table->id();
            $table->string('format', 20);                       // image | carousel | video
            $table->string('status', 30)->default('generating');
            $table->string('topic', 500);
            $table->string('audience', 200)->nullable();
            $table->text('brief')->nullable();                   // extra instructions given when it was requested
            $table->string('title', 300)->nullable();            // internal name for the board
            $table->text('caption')->nullable();
            $table->json('hashtags')->nullable();
            $table->json('slides')->nullable();                  // slides (image/carousel) or scenes (video)
            $table->json('fact_checks')->nullable();
            $table->text('feedback')->nullable();                // latest "request changes" note
            $table->unsignedInteger('revision')->default(1);
            $table->json('assets')->nullable();                  // rendered PNG paths on the local disk
            $table->string('video_path', 500)->nullable();
            $table->string('render_status', 20)->nullable();     // queued | rendering | done | failed
            $table->text('render_error')->nullable();
            $table->timestamp('render_claimed_at')->nullable();
            $table->text('error')->nullable();                   // generation failure
            $table->string('ai_model', 80)->nullable();
            $table->json('ai_usage')->nullable();
            $table->foreignId('idea_id')->nullable();
            $table->foreignId('created_by')->nullable();
            $table->foreignId('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->date('planned_for')->nullable();
            $table->timestamp('posted_at')->nullable();
            $table->timestamps();
            $table->index(['status']);
            $table->index(['render_status']);
        });

        Schema::create('social_ideas', function (Blueprint $table) {
            $table->id();
            $table->string('topic', 500);
            $table->text('angle')->nullable();
            $table->string('audience', 200)->nullable();
            $table->string('format', 20)->nullable();
            $table->string('status', 20)->default('new');        // new | used | dismissed
            $table->string('source', 20)->default('manual');     // manual | ai
            $table->foreignId('post_id')->nullable();
            $table->timestamp('used_at')->nullable();
            $table->timestamps();
            $table->index(['status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('social_ideas');
        Schema::dropIfExists('social_posts');
    }
};
