# Social Studio

TikTok posts for Fair Tax International, written in Urdu by Claude, approved
in the app, downloaded and uploaded to TikTok by hand. Sidebar: **Social Studio**.

## What it does

1. **Write.** *New post* (or the daily run) sends a topic to Claude. Claude
   searches the web for current FBR facts, then returns the post as structured
   data: slides, caption, hashtags, and a **fact-check list** naming every claim
   and its source. Formats: single image, carousel (4-8 slides), video
   (5-8 animated scenes, 20-35 s).
2. **Review.** The post lands in the review queue. The review page shows the
   slides (or plays the video) exactly as they will export, the caption with a
   Copy button, and the fact checks, with unverified claims highlighted.
   - **Approve**
   - **Send back with changes**: type what to change; Claude rewrites it and
     it comes back as version 2
   - **Edit text and layout yourself**: change any slide, layout or colour
   - **Start over** / **Reject**
3. **Files.** On approval, images and carousels are exported by the browser as
   1080x1920 PNGs (carousel = ZIP with `caption.txt`). Videos are rendered as
   MP4 by the render worker on the VPS.
4. **Post.** Download, upload to TikTok, paste the caption, then *Mark as
   posted*.

Nothing is ever posted automatically.

## Design

All formats share one template engine, `public/social-assets/renderer.js`,
in the house style from the motion-video work: navy / light / blue "rooms",
amber accents, Geist for Latin text and numbers, Noto Nastaliq Urdu (or Naskh,
in Settings) for Urdu, fonts bundled so nothing depends on Google. Layouts:
cover, point, list, steps, stat (big number that counts up in video), warning,
cta (end card with WhatsApp button). Text shrinks to fit its box. Content keeps
clear of TikTok's tabs, caption and the like/comment rail on the right, where
right-aligned Urdu would otherwise collide.

## Setup on the live app

1. Deploy as usual (`/deploy.php?secret=…`).
2. Open **/migrate-social.php** once (creates `social_posts`, `social_ideas`).
3. **Social Studio > Settings** (admins): paste a Claude API key from
   console.anthropic.com, check the brand facts and WhatsApp number, save.
4. cPanel > Cron Jobs: add, **hourly**:
   ```
   cd /home/USERNAME/public_html && php artisan social:run
   ```
   This drafts the daily posts (if switched on in Settings), finishes any post
   whose web request was cut off, and re-queues stalled renders.
5. Video rendering: set up the worker on the VPS - `render-worker/README.md`.
   Images and carousels work without it.

## Costs (Claude API, claude-sonnet-5-5)

Roughly 10-30k tokens and up to 4 web searches ($0.01 each) per post: about $0.05-0.15 a
post. Usage is shown at the bottom of each post.

## Files

| Path | What |
|---|---|
| `app/Services/Social/SocialContentGenerator.php` | Prompts, output schema, idea suggestions |
| `app/Services/Social/ClaudeClient.php` | Messages API client (web search, forced tool output) |
| `app/Services/Social/SocialBrand.php` | Brand kit and settings (`social.*` keys) |
| `app/Http/Controllers/Social/*` | Queue, review, ideas, settings |
| `app/Http/Controllers/Api/SocialRenderApiController.php` | Render worker API and render page |
| `app/Console/Commands/SocialRun.php` | `social:run` (hourly) |
| `public/social-assets/` | Renderer, fonts, export library |
| `render-worker/` | VPS worker (Node + headless Chrome + ffmpeg) |
| `public/migrate-social.php` | One-shot table creation for the live host |
