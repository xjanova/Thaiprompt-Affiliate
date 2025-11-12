<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Services\ThemeService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ThemeController extends Controller
{
    protected $themeService;

    public function __construct(ThemeService $themeService)
    {
        $this->themeService = $themeService;
    }

    /**
     * Display theme selection page
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        $themes = $this->themeService->getAllThemes(true);
        $userThemeData = $this->themeService->getThemeForUser(Auth::id());

        // Extract theme and mode from the service response
        $currentTheme = $userThemeData['theme'];
        $currentMode = $userThemeData['mode'] ?? 'auto';

        return view('user.themes.index', compact('themes', 'currentTheme', 'currentMode'));
    }

    /**
     * Set user's theme
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function setTheme(Request $request)
    {
        $request->validate([
            'theme_id' => 'required|exists:themes,id',
            'mode' => 'nullable|in:light,dark,auto',
        ]);

        $mode = $request->input('mode', 'auto');
        $this->themeService->setThemeForUser(Auth::id(), $request->theme_id, $mode);

        return response()->json([
            'success' => true,
            'message' => 'Theme set successfully',
        ]);
    }

    /**
     * Get CSS for user's current theme
     *
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function getCss(Request $request)
    {
        $mode = $request->input('mode');
        $css = $this->themeService->getCssForUser(Auth::id(), $mode);

        return response($css, 200)
            ->header('Content-Type', 'text/css');
    }

    /**
     * Update user's menu theme preference
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateTheme(Request $request)
    {
        $request->validate([
            'menu_theme_preference' => 'required|in:millennium,classic_x'
        ]);

        $user = Auth::user();
        $user->menu_theme_preference = $request->menu_theme_preference;
        $user->save();

        return response()->json([
            'success' => true,
            'message' => 'Theme updated successfully',
            'theme' => $user->menu_theme_preference
        ]);
    }

    /**
     * Get user's current theme
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getCurrentTheme()
    {
        $user = Auth::user();
        return response()->json([
            'theme' => $user->menu_theme_preference ?? 'millennium'
        ]);
    }
}
