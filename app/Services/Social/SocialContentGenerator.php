<?php

namespace App\Services\Social;

use App\Models\SocialIdea;
use App\Models\SocialPost;
use Illuminate\Support\Arr;
use Throwable;

/**
 * Writes TikTok posts (and topic ideas) in Urdu with Claude.
 *
 * The post comes back through a `submit_post` tool, so the result is always
 * structured: slides in the layouts the renderer draws, a caption, hashtags,
 * and a fact-check list naming every factual claim and where it came from.
 * The reviewer sees that list before approving - tax figures go out under
 * the firm's name.
 */
class SocialContentGenerator
{
    /** Slide counts per format: [min, max]. */
    public const SLIDE_RANGE = [
        'image'    => [1, 1],
        'carousel' => [4, 8],
        'video'    => [5, 8],
    ];

    public function __construct(private ?ClaudeClient $client = null)
    {
    }

    private function client(): ClaudeClient
    {
        return $this->client ??= ClaudeClient::fromSettings();
    }

    /**
     * Fill a post that is in "generating" state. Never throws: a failure is
     * recorded on the post so the board shows it.
     */
    public function generate(SocialPost $post): SocialPost
    {
        try {
            $previous = $post->feedback && $post->slides ? [
                'slides'   => $post->slides,
                'caption'  => $post->caption,
                'hashtags' => $post->hashtags,
            ] : null;

            $result = $this->client()->runForTool(
                $this->systemPrompt(),
                $this->postPrompt($post, $previous),
                [$this->postTool($post->format)],
                'submit_post',
                (bool) SocialBrand::get('web_search', true),
            );

            $clean = $this->normalise($result, $post->format);

            $post->fill([
                'title'       => $clean['title'],
                'slides'      => $clean['slides'],
                'caption'     => $clean['caption'],
                'hashtags'    => $clean['hashtags'],
                'fact_checks' => $clean['fact_checks'],
                'status'      => 'review',
                'error'       => null,
                'ai_model'    => $this->client()->model(),
                'ai_usage'    => $this->client()->usage,
            ]);
            $post->clearRenders();
            $post->save();
        } catch (Throwable $e) {
            report($e);
            $post->update(['status' => 'failed', 'error' => mb_substr($e->getMessage(), 0, 2000)]);
        }

        return $post;
    }

    /**
     * Ask Claude for fresh topic ideas and store them in the idea bank.
     *
     * @return SocialIdea[]
     */
    public function suggestIdeas(int $count = 10, string $focus = ''): array
    {
        $existing = SocialIdea::where('status', 'new')->latest()->limit(40)->pluck('topic')->implode("\n- ");
        $recent = SocialPost::latest()->limit(30)->pluck('topic')->implode("\n- ");

        $prompt = "Today is " . now('Asia/Karachi')->format('l j F Y') . ".\n\n"
            . "Suggest {$count} TikTok post ideas for Fair Tax International's Urdu TikTok account.\n"
            . "Mix: what is due soon in the Pakistani tax calendar, common mistakes and myths, explainers of things people "
            . "fear (FBR notices, ATL, wealth statement), money-saving and compliance tips, and service promos.\n"
            . "Prefer topics that matter in the next 2-6 weeks; search the web for current FBR deadlines, extensions and "
            . "changes before you choose.\n"
            . ($focus !== '' ? "The user wants this focus: {$focus}\n" : '')
            . ($existing ? "\nAlready in the idea bank (do not repeat):\n- {$existing}\n" : '')
            . ($recent ? "\nRecently made posts (do not repeat):\n- {$recent}\n" : '')
            . "\nWrite topic and angle in English (they are internal notes); the posts themselves will be in Urdu.";

        $result = $this->client()->runForTool(
            $this->systemPrompt(),
            $prompt,
            [$this->ideasTool()],
            'submit_ideas',
            (bool) SocialBrand::get('web_search', true),
            4000,
        );

        $ideas = [];
        foreach (array_slice($result['ideas'] ?? [], 0, $count) as $row) {
            if (!trim((string) ($row['topic'] ?? ''))) {
                continue;
            }
            $ideas[] = SocialIdea::create([
                'topic'    => mb_substr(trim($row['topic']), 0, 500),
                'angle'    => trim((string) ($row['angle'] ?? '')) ?: null,
                'audience' => mb_substr(trim((string) ($row['audience'] ?? '')), 0, 200) ?: null,
                'format'   => in_array($row['format'] ?? null, array_keys(SocialPost::FORMATS), true) ? $row['format'] : null,
                'status'   => 'new',
                'source'   => 'ai',
            ]);
        }

        return $ideas;
    }

    // ------------------------------------------------------------------ prompts

    public function systemPrompt(): string
    {
        $voice = SocialBrand::voice();
        $facts = SocialBrand::facts();
        $kit = SocialBrand::kit();
        $today = now('Asia/Karachi')->format('j F Y');

        return <<<PROMPT
You are the social media writer for {$kit['name']}, a Pakistani tax and compliance firm. You write TikTok
posts in Urdu (Urdu script) for its account. Today is {$today} (Pakistan time).

BRAND VOICE
{$voice}

BRAND FACTS (confirmed by the firm - you may state these without a source)
{$facts}

ACCURACY RULES - the firm is a tax practice and every figure carries its name
- Every factual claim about tax law, deadlines, rates, penalties, thresholds or FBR procedure must be
  either one of the brand facts above, or something you confirmed with web search in this session
  (prefer FBR.gov.pk, the Income Tax Ordinance 2001, Sales Tax Act 1990, Finance Act text, official
  SROs/circulars). Deadlines and rates change every budget - always check the current tax year.
- If you cannot confirm a figure, leave it off the slides and say the consequence qualitatively
  ("جرمانہ ہو سکتا ہے" style), and record the claim as "unverified" in fact_checks.
- Never invent arithmetic the law does not produce. Hedge consequences ("ho sakta hai") where the law
  gives FBR discretion. Never promise outcomes FBR controls.
- List EVERY factual claim that appears on a slide or in the caption in fact_checks.

TIKTOK CRAFT
- Slide/scene 1 is the hook: a question, a surprising fact, or a direct "agar aap..." - it must stop the
  scroll within 2 seconds. No logos or greetings first.
- In headings, wrap the 1-2 most important words in **double asterisks**; they are drawn in the accent
  colour. Use it once per heading at most.
- One idea per slide. On-screen text is SHORT - people read it on a phone in 3 seconds:
  heading at most 7 Urdu words; body at most 18 words; at most 4 points of at most 7 words each.
- The last slide is always a "cta" slide: what to do next, pointing to WhatsApp {$kit['whatsapp']}.
- Caption: 1-3 short lines in Urdu (English terms allowed), ending with a call to action, max 300
  characters. Hashtags: 4-8, mixing English (#FBR #TaxPakistan) and Urdu (#ٹیکس) tags, no # symbol needed.

SLIDE LAYOUTS (choose per slide)
- cover:   hook slide. kicker (tiny label, 1-3 words), heading (the hook), optional ghost.
- point:   one idea. heading + body.
- list:    heading + 2-4 points (bullets).
- steps:   heading + 2-4 points shown as numbered steps (a process).
- stat:    one big number/date/amount. stat.value (e.g. "30 Sep", "Rs 10,000", "0.1%"), stat.label
           (what it is, in Urdu), optional body.
- warning: a consequence or mistake. heading + body or points; drawn in red.
- cta:     closing slide. heading (the action, e.g. "آج ہی return file کریں") + optional body.
Optional on any slide: "room" = navy (problem/attention), paper (light; explanation/fix), blue (proof,
good news). Leave it out to let the design choose. "ghost" = one short Latin word or number drawn huge
and faint in the background (e.g. "FBR", "ATL", "30"), only on cover/stat slides.
PROMPT;
    }

    private function postPrompt(SocialPost $post, ?array $previous): string
    {
        [$min, $max] = self::SLIDE_RANGE[$post->format];
        $audience = $post->audience ?: SocialBrand::defaultAudience();

        $format = match ($post->format) {
            'image'    => "a single-image post: exactly 1 slide that works on its own (usually 'cover', 'stat' or 'list' layout; it carries the CTA in its body or kicker - do not add a separate cta slide).",
            'carousel' => "a photo carousel of {$min}-{$max} slides (TikTok photo mode, swiped one by one). Slide 1 = hook, then the content, last = cta.",
            'video'    => "a short motion-graphics video of {$min}-{$max} scenes (about 20-35 seconds total; no voice-over, music is added on TikTok). "
                         . "Give each scene a duration in seconds: 2.5-4 for most, up to 5 for a list, 4-5 for the final cta. Scene 1 = hook, last = cta.",
        };

        $prompt = "Write {$format}\n\nTopic: {$post->topic}\nAudience: {$audience}\n";
        if ($post->brief) {
            $prompt .= "Extra instructions from the firm: {$post->brief}\n";
        }

        if ($previous) {
            $prompt .= "\nThis is a REVISION. The previous version was:\n"
                . json_encode($previous, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)
                . "\n\nThe reviewer asked for these changes:\n{$post->feedback}\n\n"
                . "Apply the changes. Keep everything the reviewer did not ask to change.\n";
        }

        return $prompt . "\nResearch what you need first, then call submit_post.";
    }

    // ------------------------------------------------------------------ tools

    private function postTool(string $format): array
    {
        [$min, $max] = self::SLIDE_RANGE[$format];

        $slide = [
            'type'       => 'object',
            'properties' => [
                'layout'  => ['type' => 'string', 'enum' => \App\Models\SocialPost::LAYOUTS],
                'room'    => ['type' => 'string', 'enum' => ['navy', 'paper', 'blue']],
                'kicker'  => ['type' => 'string', 'description' => 'Tiny label above the heading, 1-3 words.'],
                'heading' => ['type' => 'string'],
                'body'    => ['type' => 'string'],
                'points'  => ['type' => 'array', 'items' => ['type' => 'string'], 'maxItems' => 4],
                'stat'    => [
                    'type'       => 'object',
                    'properties' => ['value' => ['type' => 'string'], 'label' => ['type' => 'string']],
                ],
                'ghost'   => ['type' => 'string', 'description' => 'Optional short Latin word/number drawn faintly behind cover or stat slides.'],
            ],
            'required'   => ['layout', 'heading'],
        ];
        if ($format === 'video') {
            $slide['properties']['duration'] = ['type' => 'number', 'description' => 'Seconds on screen.'];
            $slide['required'][] = 'duration';
        }

        return [
            'name'         => 'submit_post',
            'description'  => 'Submit the finished TikTok post.',
            'input_schema' => [
                'type'       => 'object',
                'properties' => [
                    'title'       => ['type' => 'string', 'description' => 'Short internal name in English, e.g. "Return deadline reminder".'],
                    'slides'      => ['type' => 'array', 'items' => $slide, 'minItems' => $min, 'maxItems' => $max],
                    'caption'     => ['type' => 'string'],
                    'hashtags'    => ['type' => 'array', 'items' => ['type' => 'string'], 'maxItems' => 8],
                    'fact_checks' => [
                        'type'  => 'array',
                        'items' => [
                            'type'       => 'object',
                            'properties' => [
                                'claim'  => ['type' => 'string', 'description' => 'The claim, in English.'],
                                'status' => ['type' => 'string', 'enum' => ['verified', 'brand_fact', 'unverified']],
                                'source' => ['type' => 'string', 'description' => 'URL or "brand facts".'],
                            ],
                            'required'   => ['claim', 'status'],
                        ],
                    ],
                ],
                'required'   => ['title', 'slides', 'caption', 'hashtags', 'fact_checks'],
            ],
        ];
    }

    private function ideasTool(): array
    {
        return [
            'name'         => 'submit_ideas',
            'description'  => 'Submit the list of post ideas.',
            'input_schema' => [
                'type'       => 'object',
                'properties' => [
                    'ideas' => [
                        'type'  => 'array',
                        'items' => [
                            'type'       => 'object',
                            'properties' => [
                                'topic'    => ['type' => 'string'],
                                'angle'    => ['type' => 'string', 'description' => 'The hook or angle, one sentence.'],
                                'audience' => ['type' => 'string'],
                                'format'   => ['type' => 'string', 'enum' => array_keys(SocialPost::FORMATS)],
                            ],
                            'required'   => ['topic', 'angle', 'format'],
                        ],
                    ],
                ],
                'required'   => ['ideas'],
            ],
        ];
    }

    // ------------------------------------------------------------------ cleanup

    /**
     * Keep only what the renderer understands, within the slide limits.
     */
    public function normalise(array $result, string $format): array
    {
        [$min, $max] = self::SLIDE_RANGE[$format];

        $slides = [];
        foreach (array_slice($result['slides'] ?? [], 0, $max) as $raw) {
            $slides[] = self::cleanSlide((array) $raw, $format);
        }
        if (count($slides) < $min) {
            throw new \RuntimeException("Claude returned " . count($slides) . " slides; a {$format} needs at least {$min}.");
        }

        $hashtags = collect($result['hashtags'] ?? [])
            ->map(fn ($t) => trim(ltrim(trim((string) $t), '#')))
            ->map(fn ($t) => preg_replace('/\s+/u', '', $t))
            ->filter()->unique()->take(8)->values()->all();

        $checks = collect($result['fact_checks'] ?? [])->map(fn ($c) => [
            'claim'  => trim((string) ($c['claim'] ?? '')),
            'status' => in_array($c['status'] ?? '', ['verified', 'brand_fact', 'unverified'], true) ? $c['status'] : 'unverified',
            'source' => trim((string) ($c['source'] ?? '')),
        ])->filter(fn ($c) => $c['claim'] !== '')->values()->all();

        return [
            'title'       => mb_substr(trim((string) ($result['title'] ?? '')), 0, 300) ?: null,
            'slides'      => $slides,
            'caption'     => trim((string) ($result['caption'] ?? '')),
            'hashtags'    => $hashtags,
            'fact_checks' => $checks,
        ];
    }

    public static function cleanSlide(array $raw, string $format): array
    {
        $layout = in_array($raw['layout'] ?? '', SocialPost::LAYOUTS, true) ? $raw['layout'] : 'point';

        $slide = ['layout' => $layout];
        if (in_array($raw['room'] ?? '', ['navy', 'paper', 'blue'], true)) {
            $slide['room'] = $raw['room'];
        }
        foreach (['kicker', 'heading', 'body', 'ghost'] as $field) {
            $value = trim((string) ($raw[$field] ?? ''));
            if ($value !== '') {
                $slide[$field] = $value;
            }
        }
        $points = array_values(array_filter(array_map(fn ($p) => trim((string) $p), (array) ($raw['points'] ?? []))));
        if ($points) {
            $slide['points'] = array_slice($points, 0, 4);
        }
        $stat = (array) ($raw['stat'] ?? []);
        if (trim((string) Arr::get($stat, 'value', '')) !== '') {
            $slide['stat'] = [
                'value' => trim((string) $stat['value']),
                'label' => trim((string) Arr::get($stat, 'label', '')),
            ];
        }
        if ($format === 'video') {
            $slide['duration'] = round(max(1.5, min(8, (float) ($raw['duration'] ?? 3))), 1);
        }

        return $slide;
    }
}
