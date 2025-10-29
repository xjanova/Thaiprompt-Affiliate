<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;

class LanguageController extends Controller
{
    /**
     * Switch application language
     */
    public function switch(Request $request, $locale)
    {
        // Validate locale
        $supportedLocales = config('app.supported_locales', ['en', 'th']);

        if (!in_array($locale, $supportedLocales)) {
            return redirect()->back()->with('error', 'Unsupported language');
        }

        // Store locale in session
        Session::put('locale', $locale);
        Session::save(); // Force save session

        // Set locale immediately
        app()->setLocale($locale);

        // Get the URL to redirect to (current page)
        $redirectUrl = url()->previous();

        // Redirect to current page to reload with new language
        return redirect($redirectUrl)->with('success', 'Language changed successfully');
    }

    /**
     * Get available languages
     */
    public function getLanguages()
    {
        return [
            'en' => [
                'name' => 'English',
                'native' => 'English',
                'flag' => '🇬🇧',
            ],
            'th' => [
                'name' => 'Thai',
                'native' => 'ไทย',
                'flag' => '🇹🇭',
            ],
        ];
    }
}
