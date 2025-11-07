<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Services\ThemeService;
use Illuminate\Http\Request;

class ThemeController extends Controller
{
    protected $themeService;

    public function __construct(ThemeService $themeService)
    {
        $this->themeService = $themeService;
    }

    /**
     * Show theme selector
     */
    public function index()
    {
        $themes = $this->themeService->getAllThemes(true);
        $currentTheme = $this->themeService->getThemeForUser(auth()->id());

        return view('user.themes.index', compact('themes', 'currentTheme'));
    }

    /**
     * Set theme for user
     */
    public function setTheme(Request $request)
    {
        $validated = $request->validate([
            'theme_id' => 'required|exists:themes,id',
            'mode' => 'required|in:light,dark,auto',
        ]);

        $this->themeService->setThemeForUser(
            auth()->id(),
            $validated['theme_id'],
            $validated['mode']
        );

        return back()->with('success', 'เปลี่ยน Theme สำเร็จ');
    }

    /**
     * Get current theme CSS
     */
    public function getCss()
    {
        $mode = request('mode', 'light');
        $css = $this->themeService->getCssForUser(auth()->id(), $mode);

        return response($css)->header('Content-Type', 'text/css');
    }
}
