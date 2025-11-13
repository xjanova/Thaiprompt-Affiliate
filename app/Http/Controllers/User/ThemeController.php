<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ThemeController extends Controller
{
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

        try {
            $user = Auth::user();

            // Log current state
            \Log::info('Updating theme for user', [
                'user_id' => $user->id,
                'old_theme' => $user->menu_theme_preference,
                'new_theme' => $request->menu_theme_preference
            ]);

            $user->menu_theme_preference = $request->menu_theme_preference;
            $saved = $user->save();

            // Verify save
            $user->refresh();

            \Log::info('Theme updated', [
                'user_id' => $user->id,
                'saved' => $saved,
                'current_theme' => $user->menu_theme_preference
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Theme updated successfully',
                'theme' => $user->menu_theme_preference,
                'saved' => $saved
            ]);
        } catch (\Exception $e) {
            \Log::error('Failed to update theme', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to update theme: ' . $e->getMessage()
            ], 500);
        }
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
