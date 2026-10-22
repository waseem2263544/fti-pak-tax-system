<?php

namespace App\Services\Social;

use App\Models\Setting;
use Illuminate\Support\Str;

/**
 * Brand kit and configuration for Social Studio.
 *
 * Everything lives in the settings table (social.* keys) so an administrator
 * can change it from Social Studio > Settings without a deploy. The defaults
 * are the Fair Tax house style used in the motion-video work: the same
 * palette, Geist for Latin text and numbers, Nastaliq for Urdu.
 */
class SocialBrand
{
    public const DEFAULT_COLORS = [
        'blue'  => '#1982C3',
        'navy'  => '#0B2744',
        'ink'   => '#061B31',
        'amber' => '#FFCB21',
        'sky'   => '#A8DAFF',
        'paper' => '#F3F8FD',
        'green' => '#1AAF63',
        'red'   => '#F0484D',
        'slate' => '#4A6078',
    ];

    public const DEFAULT_VOICE = <<<'TXT'
Warm, clear, confident - a trusted tax adviser explaining things to a friend, never a lecture.
Write natural everyday Urdu as spoken in Pakistan. Keep technical and official terms in English
inside the Urdu sentence where people actually say them that way: FBR, IRIS, ATL, NTN, return,
wealth statement, sales tax, PSID, deadline. Use Western digits (15, 2026, Rs 1,000).
Use the respectful "آپ" form. Short sentences. No fear-mongering: state consequences calmly and
accurately, then give the fix. Never promise outcomes that FBR controls.
TXT;

    public const DEFAULT_FACTS = <<<'TXT'
Fair Tax International (fairtaxint.com) - ACCA-led tax, accountancy and corporate compliance firm.
750+ clients across Pakistan & the UAE (most are individuals, so say "clients", not "businesses").
Offices: Peshawar (Pakistan) and Sharjah (UAE).
Services in Pakistan: income tax returns and wealth statements, NTN registration, ATL, sales tax
registration and returns, FBR notice replies, SECP company registration, bookkeeping, withholding tax,
FBR digital invoicing setup.
Contact: WhatsApp / phone +92 314 9444 785, fairtaxint.com.
TXT;

    public static function get(string $key, $default = null)
    {
        return Setting::get('social.' . $key, $default);
    }

    public static function put(string $key, $value): void
    {
        Setting::put('social.' . $key, $value);
    }

    public static function colors(): array
    {
        $saved = json_decode((string) self::get('colors', ''), true);

        return array_merge(self::DEFAULT_COLORS, is_array($saved) ? $saved : []);
    }

    public static function apiKey(): ?string
    {
        return Setting::secret('social.claude_api_key') ?: (config('services.anthropic.key') ?: null);
    }

    public static function model(): string
    {
        return self::get('claude_model') ?: (config('services.anthropic.model') ?: 'claude-sonnet-5-5');
    }

    /** Shared secret the VPS render worker sends. Created on first use. */
    public static function renderToken(): string
    {
        $token = Setting::secret('social.render_token');
        if (!$token) {
            $token = Str::random(48);
            Setting::putSecret('social.render_token', $token);
        }

        return $token;
    }

    public static function rotateRenderToken(): string
    {
        $token = Str::random(48);
        Setting::putSecret('social.render_token', $token);

        return $token;
    }

    /**
     * Everything the renderer draws with. Logos and music are served through
     * the brand-file route so uploads in storage/ work the same as defaults.
     */
    public static function kit(): array
    {
        return [
            'name'      => self::get('brand_name', 'Fair Tax International'),
            'handle'    => self::get('handle', ''),
            'website'   => self::get('website', 'fairtaxint.com'),
            'whatsapp'  => self::get('whatsapp', '+92 314 9444 785'),
            'cta'       => self::get('cta', 'ابھی واٹس ایپ کریں'),
            'tagline'   => self::get('tagline', '750+ clients across Pakistan & the UAE'),
            'urduFont'  => self::get('urdu_font', 'nastaliq'),
            'colors'    => self::colors(),
            'logoOnDark'  => self::logoUrl('dark'),
            'logoOnLight' => self::logoUrl('light'),
            'logoOnBlue'  => self::logoUrl('blue'),
        ];
    }

    /**
     * $background is the background the logo sits on: "dark" wants the
     * version with white lettering, "light" the print version.
     */
    public const LOGO_KEYS = [
        'dark'  => ['logo_on_dark', 'images/logo.png'],
        'light' => ['logo_on_light', 'images/logo-print.png'],
        'blue'  => ['logo_on_blue', 'social-assets/logo-on-blue.png'],
    ];

    /**
     * $background is what the logo sits on: "dark" (navy) and "blue" want
     * white lettering, "light" the print version.
     */
    public static function logoUrl(string $background): string
    {
        [$key, $default] = self::LOGO_KEYS[$background] ?? self::LOGO_KEYS['dark'];
        if (self::get($key)) {
            return route('social.brand-file', ['which' => $key, 'v' => substr(md5((string) self::get($key)), 0, 8)]);
        }

        return asset($default);
    }

    public static function voice(): string
    {
        return self::get('voice') ?: self::DEFAULT_VOICE;
    }

    public static function facts(): string
    {
        return self::get('facts') ?: self::DEFAULT_FACTS;
    }

    public static function defaultAudience(): string
    {
        return self::get('audience', 'Salaried individuals and small business owners in Pakistan');
    }

    public static function musicPath(): ?string
    {
        return self::get('music_path') ?: null;
    }

    public static function dailyEnabled(): bool
    {
        return (bool) self::get('daily_enabled', false);
    }

    public static function dailyCount(): int
    {
        return max(1, min(10, (int) self::get('daily_count', 2)));
    }

    /** @return string[] */
    public static function dailyFormats(): array
    {
        $formats = json_decode((string) self::get('daily_formats', ''), true);

        return is_array($formats) && $formats ? $formats : ['carousel', 'image', 'video'];
    }
}
