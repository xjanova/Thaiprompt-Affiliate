<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ThemeController extends Controller
{
    /**
     * Display menu theme selection page (Millennium vs Classic X)
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        // This page uses menu_theme_preference (millennium vs classic_x)
        // No need to fetch themes from database
        return view('user.themes.index');
    }

    /**
     * Update user's menu theme preference (Millennium vs Classic X)
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
