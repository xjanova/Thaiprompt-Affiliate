<?php

namespace App\Services\PersonalPost;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use SimpleXMLElement;
use Throwable;

/**
 * ดึงหัวข้อที่กำลังมาแรงสำหรับเตรียมโพสต์ส่วนตัว
 *
 * แหล่งข้อมูล (ฟรี ไม่ต้องใช้คีย์):
 *  1. Google Trends ประเทศไทย — RSS `trending/rss?geo=TH` มีหัวข่าวแนบมากับแต่ละเทรนด์
 *  2. ฟีดข่าวเทค ไทย + ต่างประเทศ — ข่าวเปิดตัว/อัปเดตของวันนั้น
 *
 * ⚠️ หัวข่าวที่ได้คือ "ข้อเท็จจริง" ที่ส่งให้ AI เขียน — AI ห้ามแต่งเกินจากนี้
 */
class TrendSourceService
{
    public const GOOGLE_TRENDS_URL = 'https://trends.google.com/trending/rss?geo=TH';

    /**
     * ฟีดข่าวเทค — ยิงทดสอบแล้วตอบ 200 ทุกตัว (2026-11-11)
     */
    public const TECH_FEEDS = [
        ['name' => 'Blognone', 'url' => 'https://www.blognone.com/atom.xml', 'lang' => 'th'],
        ['name' => 'DroidSans', 'url' => 'https://www.droidsans.com/feed/', 'lang' => 'th'],
        ['name' => 'TechCrunch', 'url' => 'https://techcrunch.com/feed/', 'lang' => 'en'],
        ['name' => 'The Verge', 'url' => 'https://www.theverge.com/rss/index.xml', 'lang' => 'en'],
        ['name' => 'Ars Technica', 'url' => 'https://feeds.arstechnica.com/arstechnica/index', 'lang' => 'en'],
    ];

    /** ข่าวเทคเก่ากว่านี้ไม่เอา (ชั่วโมง) */
    protected const TECH_MAX_AGE_HOURS = 36;

    /** จำนวนข่าวสูงสุดต่อฟีด */
    protected const TECH_PER_FEED = 8;

    /**
     * ดึงผู้สมัครทั้งหมด (เทรนด์ + ข่าวเทค)
     *
     * @return array<int, array{source_type:string, title:string, url:?string, source_name:?string, traffic:?string, published_at:?string, headlines:array<int, array{title:string, source:?string, url:?string}>}>
     */
    public function candidates(): array
    {
        return array_merge($this->googleTrends(), $this->techNews());
    }

    /**
     * เทรนด์ Google ประเทศไทย
     *
     * @return array<int, array<string, mixed>>
     */
    public function googleTrends(): array
    {
        $xml = $this->fetchXml(self::GOOGLE_TRENDS_URL);
        if ($xml === null || ! isset($xml->channel->item)) {
            return [];
        }

        $out = [];
        foreach ($xml->channel->item as $item) {
            $ht = $item->children('ht', true);
            $headlines = [];
            foreach ($ht->news_item ?? [] as $news) {
                $title = $this->clean((string) $news->news_item_title);
                if ($title === '') {
                    continue;
                }
                $headlines[] = [
                    'title' => $title,
                    'source' => $this->clean((string) $news->news_item_source) ?: null,
                    'url' => trim((string) $news->news_item_url) ?: null,
                ];
            }

            $title = $this->clean((string) $item->title);
            if ($title === '') {
                continue;
            }

            $out[] = [
                'source_type' => 'google_trend',
                'title' => $title,
                'url' => $headlines[0]['url'] ?? null,
                'source_name' => $headlines[0]['source'] ?? 'Google Trends',
                'traffic' => trim((string) ($ht->approx_traffic ?? '')) ?: null,
                'published_at' => $this->parseDate((string) $item->pubDate),
                'headlines' => array_slice($headlines, 0, 4),
            ];
        }

        return $out;
    }

    /**
     * ข่าวเทคล่าสุดจากทุกฟีด (เฉพาะข่าวไม่เกิน 36 ชม.)
     *
     * @return array<int, array<string, mixed>>
     */
    public function techNews(): array
    {
        $cutoff = now()->subHours(self::TECH_MAX_AGE_HOURS);
        $out = [];

        foreach (self::TECH_FEEDS as $feed) {
            $xml = $this->fetchXml($feed['url']);
            if ($xml === null) {
                continue;
            }

            $count = 0;
            foreach ($this->feedEntries($xml) as $entry) {
                if ($count >= self::TECH_PER_FEED) {
                    break;
                }
                if ($entry['title'] === '') {
                    continue;
                }
                // ข่าวที่ไม่มีวันที่ = เก็บไว้ (บางฟีดไม่ใส่) · มีวันที่แต่เก่า = ข้าม
                if ($entry['published_at'] !== null && Carbon::parse($entry['published_at'])->lt($cutoff)) {
                    continue;
                }

                $out[] = [
                    'source_type' => 'tech_news',
                    'title' => $entry['title'],
                    'url' => $entry['url'],
                    'source_name' => $feed['name'],
                    'traffic' => null,
                    'published_at' => $entry['published_at'],
                    'headlines' => [[
                        'title' => $entry['title'],
                        'source' => $feed['name'],
                        'url' => $entry['url'],
                        'summary' => $entry['summary'],
                    ]],
                ];
                $count++;
            }
        }

        return $out;
    }

    /**
     * อ่าน entry จากฟีด RSS 2.0 หรือ Atom
     *
     * @return array<int, array{title:string, url:?string, published_at:?string, summary:?string}>
     */
    protected function feedEntries(SimpleXMLElement $xml): array
    {
        $entries = [];

        // RSS 2.0
        if (isset($xml->channel->item)) {
            foreach ($xml->channel->item as $item) {
                $entries[] = [
                    'title' => $this->clean((string) $item->title),
                    'url' => trim((string) $item->link) ?: null,
                    'published_at' => $this->parseDate((string) $item->pubDate),
                    'summary' => $this->summary((string) $item->description),
                ];
            }

            return $entries;
        }

        // Atom (Blognone, The Verge)
        foreach ($xml->entry ?? [] as $entry) {
            $link = null;
            foreach ($entry->link ?? [] as $l) {
                $rel = (string) ($l['rel'] ?? 'alternate');
                if ($rel === 'alternate' || $link === null) {
                    $link = (string) $l['href'];
                }
            }
            $entries[] = [
                'title' => $this->clean((string) $entry->title),
                'url' => $link ? trim($link) : null,
                'published_at' => $this->parseDate((string) ($entry->published ?: $entry->updated)),
                'summary' => $this->summary((string) ($entry->summary ?: $entry->content)),
            ];
        }

        return $entries;
    }

    /**
     * ยิง HTTP แล้วแปลงเป็น XML — ล้มเหลว = null (ไม่ throw ให้ทั้งวันพัง)
     */
    protected function fetchXml(string $url): ?SimpleXMLElement
    {
        try {
            $response = Http::timeout(15)
                ->withHeaders([
                    'User-Agent' => 'Mozilla/5.0 (compatible; ThaipromptTrendReader/1.0)',
                    'Accept' => 'application/rss+xml, application/atom+xml, application/xml;q=0.9, */*;q=0.8',
                ])
                ->get($url);

            if (! $response->successful() || trim($response->body()) === '') {
                Log::info('PersonalPost: ดึงฟีดไม่สำเร็จ', ['url' => $url, 'status' => $response->status()]);

                return null;
            }

            $previous = libxml_use_internal_errors(true);
            $xml = simplexml_load_string($response->body(), SimpleXMLElement::class, LIBXML_NOCDATA | LIBXML_NONET);
            libxml_clear_errors();
            libxml_use_internal_errors($previous);

            return $xml === false ? null : $xml;
        } catch (Throwable $e) {
            Log::info('PersonalPost: ดึงฟีดล้ม', ['url' => $url, 'error' => $e->getMessage()]);

            return null;
        }
    }

    protected function clean(string $text): string
    {
        $text = html_entity_decode(strip_tags($text), ENT_QUOTES | ENT_HTML5, 'UTF-8');

        return trim((string) preg_replace('/\s+/u', ' ', $text));
    }

    protected function summary(string $html): ?string
    {
        $text = $this->clean($html);

        return $text === '' ? null : mb_substr($text, 0, 300);
    }

    protected function parseDate(string $value): ?string
    {
        $value = trim($value);
        if ($value === '') {
            return null;
        }

        try {
            return Carbon::parse($value)->timezone('Asia/Bangkok')->toIso8601String();
        } catch (Throwable) {
            return null;
        }
    }
}
