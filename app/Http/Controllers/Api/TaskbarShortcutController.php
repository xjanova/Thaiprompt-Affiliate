<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\UserTaskbarShortcut;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class TaskbarShortcutController extends Controller
{
    /**
     * Get all taskbar shortcuts for the authenticated user
     */
    public function index(Request $request)
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not authenticated',
            ], 401);
        }

        $shortcuts = UserTaskbarShortcut::where('user_id', $user->id)
            ->ordered()
            ->get();

        return response()->json([
            'success' => true,
            'shortcuts' => $shortcuts,
        ]);
    }

    /**
     * Store a new taskbar shortcut
     */
    public function store(Request $request)
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not authenticated',
            ], 401);
        }

        $validator = Validator::make($request->all(), [
            'icon' => 'required|string|max:10',
            'label' => 'required|string|max:100',
            'url' => 'required|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        // Get the max order value and add 1
        $maxOrder = UserTaskbarShortcut::where('user_id', $user->id)->max('order') ?? -1;

        $shortcut = UserTaskbarShortcut::create([
            'user_id' => $user->id,
            'icon' => $request->icon,
            'label' => $request->label,
            'url' => $request->url,
            'order' => $maxOrder + 1,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'เพิ่มไอค่อนทางลัดสำเร็จ',
            'shortcut' => $shortcut,
        ], 201);
    }

    /**
     * Delete a taskbar shortcut
     */
    public function destroy(Request $request, $id)
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not authenticated',
            ], 401);
        }

        $shortcut = UserTaskbarShortcut::where('id', $id)
            ->where('user_id', $user->id)
            ->first();

        if (!$shortcut) {
            return response()->json([
                'success' => false,
                'message' => 'ไม่พบไอค่อนทางลัดนี้',
            ], 404);
        }

        $shortcut->delete();

        return response()->json([
            'success' => true,
            'message' => 'ลบไอค่อนทางลัดสำเร็จ',
        ]);
    }

    /**
     * Reorder taskbar shortcuts
     */
    public function reorder(Request $request)
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not authenticated',
            ], 401);
        }

        $validator = Validator::make($request->all(), [
            'shortcuts' => 'required|array',
            'shortcuts.*.id' => 'required|integer|exists:user_taskbar_shortcuts,id',
            'shortcuts.*.order' => 'required|integer|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        // Update order for each shortcut
        foreach ($request->shortcuts as $shortcutData) {
            UserTaskbarShortcut::where('id', $shortcutData['id'])
                ->where('user_id', $user->id)
                ->update(['order' => $shortcutData['order']]);
        }

        return response()->json([
            'success' => true,
            'message' => 'จัดเรียงไอค่อนทางลัดสำเร็จ',
        ]);
    }
}
