<?php

namespace App\Http\Controllers\Admin\BotAutomation;

use App\Http\Controllers\Controller;
use App\Models\BotAutomation\BotPlatformConnection;
use Illuminate\Http\Request;

class BotPlatformController extends Controller
{
    /**
     * Display a listing of platform connections
     */
    public function index()
    {
        $connections = BotPlatformConnection::with(['user'])
            ->orderBy('created_at', 'desc')
            ->get();

        $availablePlatforms = [
            'facebook' => [
                'name' => 'Facebook',
                'icon' => 'fab fa-facebook',
                'color' => '#1877f2',
            ],
            'instagram' => [
                'name' => 'Instagram',
                'icon' => 'fab fa-instagram',
                'color' => '#E4405F',
            ],
            'twitter' => [
                'name' => 'Twitter (X)',
                'icon' => 'fab fa-twitter',
                'color' => '#1DA1F2',
            ],
            'line' => [
                'name' => 'LINE',
                'icon' => 'fab fa-line',
                'color' => '#00B900',
            ],
            'telegram' => [
                'name' => 'Telegram',
                'icon' => 'fab fa-telegram',
                'color' => '#0088cc',
            ],
            'whatsapp' => [
                'name' => 'WhatsApp',
                'icon' => 'fab fa-whatsapp',
                'color' => '#25D366',
            ],
        ];

        return view('admin.bot-automation.platforms.index', compact('connections', 'availablePlatforms'));
    }

    /**
     * Show the form for connecting a platform
     */
    public function connect(string $platform)
    {
        return view('admin.bot-automation.platforms.connect', compact('platform'));
    }

    /**
     * แสดงรายละเอียดและการตั้งค่าของ platform connection
     *
     * @param BotPlatformConnection $connection
     * @return \Illuminate\View\View
     */
    public function show(BotPlatformConnection $connection)
    {
        $connection->load(['user']);

        return view('admin.bot-automation.platforms.show', compact('connection'));
    }

    /**
     * แสดงฟอร์มแก้ไขการตั้งค่า platform connection
     *
     * @param BotPlatformConnection $connection
     * @return \Illuminate\View\View
     */
    public function edit(BotPlatformConnection $connection)
    {
        return view('admin.bot-automation.platforms.edit', compact('connection'));
    }

    /**
     * อัพเดทการตั้งค่า platform connection
     *
     * @param Request $request
     * @param BotPlatformConnection $connection
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Request $request, BotPlatformConnection $connection)
    {
        $validated = $request->validate([
            'account_name' => 'required|string|max:255',
            'access_token' => 'nullable|string',
            'refresh_token' => 'nullable|string',
            'page_id' => 'nullable|string',
            'webhook_url' => 'nullable|url',
            'is_active' => 'boolean',
        ]);

        $connection->update($validated);

        return redirect()
            ->route('admin.bot-automation.platforms.index')
            ->with('success', 'อัพเดทการตั้งค่าแพลตฟอร์มสำเร็จ');
    }

    /**
     * Store a newly created platform connection
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'platform' => 'required|in:facebook,instagram,twitter,line,telegram,whatsapp',
            'account_name' => 'required|string|max:255',
            'access_token' => 'required|string',
            'refresh_token' => 'nullable|string',
            'page_id' => 'nullable|string',
            'webhook_url' => 'nullable|url',
            'is_active' => 'boolean',
        ]);

        $validated['user_id'] = auth()->id();
        $validated['expires_at'] = now()->addDays(60); // Default 60 days

        BotPlatformConnection::create($validated);

        return redirect()
            ->route('admin.bot-automation.platforms.index')
            ->with('success', 'Platform connected successfully');
    }

    /**
     * Remove the specified platform connection
     */
    public function destroy(BotPlatformConnection $connection)
    {
        $connection->delete();

        return redirect()
            ->route('admin.bot-automation.platforms.index')
            ->with('success', 'Platform disconnected successfully');
    }

    /**
     * Refresh the platform token
     */
    public function refreshToken(BotPlatformConnection $connection)
    {
        // TODO: Implement actual token refresh logic for each platform
        $connection->update([
            'expires_at' => now()->addDays(60),
            'last_synced_at' => now(),
        ]);

        return back()->with('success', 'Token refreshed successfully');
    }

    /**
     * Toggle platform connection active status
     */
    public function toggle(BotPlatformConnection $connection)
    {
        $connection->update([
            'is_active' => !$connection->is_active,
        ]);

        $status = $connection->is_active ? 'activated' : 'deactivated';
        return back()->with('success', "Platform connection {$status} successfully");
    }
}
