<?php

namespace App\Http\Controllers\Social;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Services\Social\SocialBrand;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

/**
 * Social Studio settings: Claude key, brand kit, daily automation and the
 * render worker. Administrators only - the API key spends money and the
 * brand facts are what Claude is allowed to state without a source.
 */
class SocialSettingsController extends Controller
{
    public function edit(Request $request)
    {
        $this->adminOnly($request);

        return view('social.settings', [
            'brand'       => SocialBrand::kit(),
            'hasKey'      => (bool) SocialBrand::apiKey(),
            'model'       => SocialBrand::model(),
            'webSearch'   => (bool) SocialBrand::get('web_search', true),
            'voice'       => SocialBrand::voice(),
            'facts'       => SocialBrand::facts(),
            'audience'    => SocialBrand::defaultAudience(),
            'daily'       => [
                'enabled' => SocialBrand::dailyEnabled(),
                'hour'    => (int) SocialBrand::get('daily_hour', 9),
                'count'   => SocialBrand::dailyCount(),
                'formats' => SocialBrand::dailyFormats(),
                'last'    => SocialBrand::get('daily_last'),
            ],
            'customLogos' => collect(SocialBrand::LOGO_KEYS)->mapWithKeys(fn ($v, $k) => [$k => (bool) SocialBrand::get($v[0])]),
            'music'       => SocialBrand::musicPath(),
            'renderToken' => SocialBrand::renderToken(),
            'lastWorker'  => SocialBrand::get('worker_seen_at'),
        ]);
    }

    public function update(Request $request)
    {
        $this->adminOnly($request);

        $data = $request->validate([
            'claude_api_key' => 'nullable|string|max:300',
            'claude_model'   => 'nullable|string|max:80',
            'web_search'     => 'nullable|boolean',
            'brand_name'     => 'required|string|max:120',
            'handle'         => 'nullable|string|max:80',
            'website'        => 'required|string|max:120',
            'whatsapp'       => 'required|string|max:40',
            'cta'            => 'nullable|string|max:120',
            'tagline'        => 'nullable|string|max:160',
            'urdu_font'      => 'required|in:nastaliq,naskh',
            'colors'         => 'array',
            'colors.*'       => ['nullable', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'voice'          => 'nullable|string|max:5000',
            'facts'          => 'nullable|string|max:8000',
            'audience'       => 'nullable|string|max:200',
            'daily_enabled'  => 'nullable|boolean',
            'daily_hour'     => 'nullable|integer|min:0|max:23',
            'daily_count'    => 'nullable|integer|min:1|max:10',
            'daily_formats'  => 'nullable|array',
            'daily_formats.*' => 'in:image,carousel,video',
            'logo_dark'      => 'nullable|image|mimes:png|max:2048',
            'logo_light'     => 'nullable|image|mimes:png|max:2048',
            'logo_blue'      => 'nullable|image|mimes:png|max:2048',
            'reset_logos'    => 'nullable|boolean',
            'music'          => 'nullable|file|mimes:mp3,m4a,aac,wav|max:15360',
            'remove_music'   => 'nullable|boolean',
        ]);

        if (!empty($data['claude_api_key'])) {
            Setting::putSecret('social.claude_api_key', trim($data['claude_api_key']));
        }
        if ($request->boolean('remove_api_key')) {
            Setting::forget('social.claude_api_key');
        }

        foreach (['claude_model', 'brand_name', 'handle', 'website', 'whatsapp', 'cta', 'tagline', 'urdu_font', 'audience'] as $key) {
            SocialBrand::put($key, trim((string) ($data[$key] ?? '')));
        }
        // Voice and facts fall back to the defaults when emptied.
        SocialBrand::put('voice', trim((string) ($data['voice'] ?? '')) === trim(SocialBrand::DEFAULT_VOICE) ? '' : trim((string) ($data['voice'] ?? '')));
        SocialBrand::put('facts', trim((string) ($data['facts'] ?? '')) === trim(SocialBrand::DEFAULT_FACTS) ? '' : trim((string) ($data['facts'] ?? '')));
        SocialBrand::put('web_search', $request->boolean('web_search') ? '1' : '0');

        $colors = array_intersect_key(array_filter($data['colors'] ?? []), SocialBrand::DEFAULT_COLORS);
        SocialBrand::put('colors', json_encode($colors));

        SocialBrand::put('daily_enabled', $request->boolean('daily_enabled') ? '1' : '0');
        SocialBrand::put('daily_hour', (string) ($data['daily_hour'] ?? 9));
        SocialBrand::put('daily_count', (string) ($data['daily_count'] ?? 2));
        SocialBrand::put('daily_formats', json_encode(array_values($data['daily_formats'] ?? ['carousel', 'image', 'video'])));

        if ($request->boolean('reset_logos')) {
            foreach (SocialBrand::LOGO_KEYS as [$key]) {
                if (SocialBrand::get($key)) {
                    Storage::disk('local')->delete(SocialBrand::get($key));
                }
                SocialBrand::put($key, '');
            }
        }
        foreach (['dark' => 'logo_dark', 'light' => 'logo_light', 'blue' => 'logo_blue'] as $bg => $field) {
            if ($request->hasFile($field)) {
                [$key] = SocialBrand::LOGO_KEYS[$bg];
                SocialBrand::put($key, $request->file($field)->store('social/brand', 'local'));
            }
        }

        if ($request->boolean('remove_music') && SocialBrand::musicPath()) {
            Storage::disk('local')->delete(SocialBrand::musicPath());
            SocialBrand::put('music_path', '');
        }
        if ($request->hasFile('music')) {
            SocialBrand::put('music_path', $request->file('music')->store('social/music', 'local'));
        }

        return redirect()->route('social.settings')->with('success', 'Social Studio settings saved.');
    }

    public function rotateToken(Request $request)
    {
        $this->adminOnly($request);
        SocialBrand::rotateRenderToken();

        return redirect()->route('social.settings')->with('success', 'New worker token created. Update it on the VPS.');
    }

    /**
     * Uploaded logos. Public on purpose: the render worker loads them without
     * a session, and a logo is not a secret.
     */
    public function brandFile(string $which)
    {
        $allowed = collect(SocialBrand::LOGO_KEYS)->pluck(0)->all();
        abort_unless(in_array($which, $allowed, true), 404);
        $path = SocialBrand::get($which);
        abort_unless($path && Storage::disk('local')->exists($path), 404);

        return response()->file(Storage::disk('local')->path($path), ['Cache-Control' => 'public, max-age=604800']);
    }

    private function adminOnly(Request $request): void
    {
        abort_unless($request->user()?->hasRole('admin'), 403, 'Only administrators can change Social Studio settings.');
    }
}
