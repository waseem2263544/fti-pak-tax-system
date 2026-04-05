<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\NewsArticle;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class FetchTaxNews extends Command
{
    protected $signature = 'app:fetch-tax-news';
    protected $description = 'Fetch latest tax-related news from Pakistani sources';

    private $keywords = [
        'fbr', 'income tax', 'sales tax', 'withholding tax', 'tax return',
        'federal board of revenue', 'kpra', 'secp', 'tax tribunal',
        'tax amnesty', 'tax reform', 'sro', 'statutory regulatory order',
        'finance act', 'finance bill', 'budget', 'tax collection',
        'tax evasion', 'tax compliance', 'ntn', 'filer', 'non-filer',
        'capital gains tax', 'property tax', 'cvt', 'capital value tax',
        'customs duty', 'excise duty', 'tax audit', 'tax assessment',
        'commissioner appeals', 'appellate tribunal', 'tax exemption',
        'withholding agent', 'advance tax', 'minimum tax',
        'pakistan revenue', 'tax policy', 'double taxation',
    ];

    private $feeds = [
        [
            'name' => 'Business Recorder',
            'url' => 'https://www.brecorder.com/feeds/latest-news',
            'type' => 'rss',
        ],
        [
            'name' => 'Dawn News',
            'url' => 'https://www.dawn.com/feeds/business',
            'type' => 'rss',
        ],
        [
            'name' => 'The News International',
            'url' => 'https://www.thenews.com.pk/rss/2/16',
            'type' => 'rss',
        ],
        [
            'name' => 'Pakistan Today',
            'url' => 'https://www.pakistantoday.com.pk/category/business/feed/',
            'type' => 'rss',
        ],
        [
            'name' => 'Geo News Business',
            'url' => 'https://www.geo.tv/rss/1/53',
            'type' => 'rss',
        ],
    ];

    public function handle()
    {
        $this->info('Fetching tax news...');
        $totalImported = 0;

        foreach ($this->feeds as $feed) {
            try {
                $count = $this->processFeed($feed);
                $totalImported += $count;
                $this->info("  {$feed['name']}: fetched $count articles");
            } catch (\Exception $e) {
                $this->warn("  {$feed['name']}: failed - " . $e->getMessage());
                Log::warning("News fetch failed for {$feed['name']}: " . $e->getMessage());
            }
        }

        $this->info("Done. Total new articles: $totalImported");
    }

    private function processFeed($feed)
    {
        $response = Http::timeout(15)->get($feed['url']);

        if (!$response->successful()) {
            return 0;
        }

        $xml = @simplexml_load_string($response->body());
        if (!$xml) return 0;

        $items = $xml->channel->item ?? $xml->entry ?? [];
        $count = 0;

        foreach ($items as $item) {
            $title = trim((string) ($item->title ?? ''));
            $link = trim((string) ($item->link ?? $item->guid ?? ''));
            $description = trim((string) ($item->description ?? $item->summary ?? ''));
            $pubDate = (string) ($item->pubDate ?? $item->published ?? $item->updated ?? '');

            if (empty($title) || empty($link)) continue;

            // Strip HTML from description
            $description = strip_tags($description);
            $description = html_entity_decode($description);
            $description = trim(preg_replace('/\s+/', ' ', $description));
            if (strlen($description) > 300) {
                $description = substr($description, 0, 300) . '...';
            }

            // Check if relevant to tax
            $category = $this->categorize($title . ' ' . $description);
            if (!$category) continue;

            // Check URL duplicate
            if (NewsArticle::where('url', $link)->exists()) continue;

            // Check title similarity
            $similar = NewsArticle::findSimilar($title);
            if ($similar) {
                // Add this source to "also reported by"
                $alsoBy = $similar->also_reported_by ?? [];
                if (!in_array($feed['name'], $alsoBy) && $similar->source !== $feed['name']) {
                    $alsoBy[] = $feed['name'];
                    $similar->update(['also_reported_by' => $alsoBy]);
                }
                continue;
            }

            // Parse date
            $publishedAt = null;
            try {
                $publishedAt = Carbon::parse($pubDate);
            } catch (\Exception $e) {
                $publishedAt = now();
            }

            // Skip articles older than 7 days
            if ($publishedAt->lt(now()->subDays(7))) continue;

            NewsArticle::create([
                'title' => $title,
                'url' => $link,
                'source' => $feed['name'],
                'category' => $category,
                'summary' => $description ?: null,
                'published_at' => $publishedAt,
            ]);

            $count++;
        }

        return $count;
    }

    private function categorize($text)
    {
        $text = strtolower($text);
        $matched = false;

        foreach ($this->keywords as $keyword) {
            if (strpos($text, $keyword) !== false) {
                $matched = true;
                break;
            }
        }

        if (!$matched) return null;

        // Determine category
        $categories = [
            'Income Tax' => ['income tax', 'tax return', 'filer', 'non-filer', 'ntn', 'advance tax', 'minimum tax', 'tax assessment', 'tax audit'],
            'Sales Tax' => ['sales tax', 'gst', 'value added'],
            'Withholding Tax' => ['withholding tax', 'withholding agent'],
            'FBR Updates' => ['fbr', 'federal board of revenue', 'tax collection', 'sro', 'statutory regulatory'],
            'Judgments' => ['tribunal', 'commissioner appeals', 'court', 'judgment', 'appeal'],
            'Policy & Budget' => ['budget', 'finance act', 'finance bill', 'tax reform', 'tax amnesty', 'tax policy', 'tax exemption'],
            'KPRA / SECP' => ['kpra', 'secp'],
            'Customs & Excise' => ['customs', 'excise'],
        ];

        foreach ($categories as $cat => $words) {
            foreach ($words as $w) {
                if (strpos($text, $w) !== false) {
                    return $cat;
                }
            }
        }

        return 'General Tax';
    }
}
