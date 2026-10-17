<?php

namespace App\Services\Classroom;

/**
 * แยกรหัสวิดีโอ YouTube (11 ตัว) จากลิงก์ทุกแบบที่แอดมินวางมา
 *
 * รองรับ: youtu.be/ID · youtube.com/watch?v=ID · /embed/ID · /shorts/ID · /live/ID · /v/ID
 *        m./music./www. · youtube-nocookie.com · รหัส 11 ตัวเปล่าๆ
 *
 * @example YouTubeId::parse('https://youtu.be/dQw4w9WgXcQ?si=abc'); // 'dQw4w9WgXcQ'
 */
final class YouTubeId
{
    private const ID = '[A-Za-z0-9_-]{11}';

    public static function parse(?string $input): ?string
    {
        $input = trim((string) $input);
        if ($input === '') {
            return null;
        }

        // รหัสเปล่า
        if (preg_match('/^'.self::ID.'$/', $input)) {
            return $input;
        }

        // ไม่มี scheme → เติมให้ parse_url อ่านได้
        if (! preg_match('#^https?://#i', $input)) {
            $input = 'https://'.ltrim($input, '/');
        }

        $parts = parse_url($input);
        if ($parts === false || empty($parts['host'])) {
            return null;
        }

        $host = strtolower($parts['host']);
        $path = (string) ($parts['path'] ?? '');

        if ($host === 'youtu.be' || $host === 'www.youtu.be') {
            return preg_match('#^/('.self::ID.')(?:$|[/?])#', $path, $m) ? $m[1] : null;
        }

        $isYouTube = (bool) preg_match('/(^|\.)(youtube\.com|youtube-nocookie\.com)$/', $host);
        if (! $isYouTube) {
            return null;
        }

        if (! empty($parts['query'])) {
            parse_str($parts['query'], $query);
            $v = $query['v'] ?? null;
            if (is_string($v) && preg_match('/^'.self::ID.'$/', $v)) {
                return $v;
            }
        }

        if (preg_match('#^/(?:embed|shorts|live|v|e)/('.self::ID.')(?:$|[/?])#', $path, $m)) {
            return $m[1];
        }

        return null;
    }

    public static function watchUrl(string $id): string
    {
        return 'https://www.youtube.com/watch?v='.$id;
    }
}
