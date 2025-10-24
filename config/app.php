<?php

use Illuminate\Support\Facades\Facade;
use Illuminate\Support\ServiceProvider;

return [

    'name' => env('APP_NAME', 'ThaiPrompt Marketplace'),
    'env' => env('APP_ENV', 'production'),
    'debug' => (bool) env('APP_DEBUG', false),
    'url' => env('APP_URL', 'http://localhost'),
    'asset_url' => env('ASSET_URL'),
    'timezone' => 'Asia/Bangkok',
    'locale' => 'th',
    'fallback_locale' => 'en',
    'faker_locale' => 'th_TH',
    'key' => env('APP_KEY'),
    'cipher' => 'AES-256-CBC',

    'maintenance' => [
        'driver' => 'file',
    ],

    'providers' => ServiceProvider::defaultProviders()->merge([
        /*
         * Package Service Providers...
         */
        Spatie\Permission\PermissionServiceProvider::class,
        Intervention\Image\ImageServiceProvider::class,
        Barryvdh\DomPDF\ServiceProvider::class,
        Maatwebsite\Excel\ExcelServiceProvider::class,

        /*
         * Application Service Providers...
         */
        App\Providers\AppServiceProvider::class,
        App\Providers\AuthServiceProvider::class,
        App\Providers\EventServiceProvider::class,
        App\Providers\RouteServiceProvider::class,
        App\Providers\SecurityServiceProvider::class,
    ])->toArray(),

    'aliases' => Facade::defaultAliases()->merge([
        'Image' => Intervention\Image\Facades\Image::class,
        'PDF' => Barryvdh\DomPDF\Facade\Pdf::class,
        'Excel' => Maatwebsite\Excel\Facades\Excel::class,
    ])->toArray(),

];
