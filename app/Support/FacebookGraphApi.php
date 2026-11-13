<?php

namespace App\Support;

final class FacebookGraphApi
{
    public const VERSION = 'v27.0';

    public const BASE_URL = 'https://graph.facebook.com/'.self::VERSION;

    public static function url(string $path): string
    {
        return self::BASE_URL.'/'.ltrim($path, '/');
    }
}
