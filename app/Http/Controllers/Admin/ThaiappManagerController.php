<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\View\View;

/**
 * Thaiapp-MANAGER · single admin hub for every server-controlled piece
 * the Flutter app consumes.
 *
 * Sub-surfaces (each has its own page + methods below):
 *   • hub          — /admin/thaiapp              nav cards + stats
 *   • nong-ying    — /admin/thaiapp/nong-ying    persona + TTS config
 *   • ai-pool      — /admin/thaiapp/ai-pool      API keys CRUD
 *   • ai-models    — /admin/thaiapp/ai-models    Gemma .task sync
 *   • banners      — /admin/thaiapp/banners      app_banners CRUD
 *   • sliders      — /admin/thaiapp/sliders      app_sliders CRUD
 *   • menus        — /admin/thaiapp/menus        app_menus CRUD
 *   • config       — /admin/thaiapp/config       key-value
 *   • releases     — /admin/thaiapp/releases     version history
 *
 * Access is gated by `role:admin,super_admin` at the route-group level.
 */
class ThaiappManagerController extends Controller
{
    // ─── Hub ──────────────────────────────────────────────────────────

    public function hub(): View
    {
        $env = app()->environment();

        $stats = [
            'bot_profile_id'   => (int) DB::table('app_configs')
                ->where('key', 'nong_ying_bot_profile_id')
                ->where('environment', $env)->value('value'),
            'api_keys_active'  => DB::table('ai_api_keys')
                ->where('is_active', 1)->whereNull('deleted_at')->count(),
            'api_keys_total'   => DB::table('ai_api_keys')->whereNull('deleted_at')->count(),
            'models_synced'    => $this->syncedModelsCount(),
            'banners_total'    => DB::table('app_banners')->count(),
            'sliders_total'    => DB::table('app_sliders')->count(),
            'menus_total'      => DB::table('app_menus')->count(),
            'configs_total'    => DB::table('app_configs')->where('environment', $env)->count(),
            'releases_total'   => DB::table('app_releases')->count(),
            'hf_token_set'     => (bool) (env('HF_TOKEN') ?: env('HUGGING_FACE_TOKEN') ?: env('HUGGINGFACE_TOKEN')),
        ];

        return view('admin.thaiapp.hub', compact('stats'));
    }

    // ─── น้องหญิง ─────────────────────────────────────────────────────

    public function nongYing(): View
    {
        $env = app()->environment();
        $botProfileId = (int) DB::table('app_configs')
            ->where('key', 'nong_ying_bot_profile_id')
            ->where('environment', $env)->value('value');
        $bot = $botProfileId
            ? DB::table('ai_bot_profiles')->where('id', $botProfileId)->first()
            : null;
        $tts = $bot && $bot->tts_config ? json_decode($bot->tts_config, true) : [];

        return view('admin.thaiapp.nong-ying', compact('bot', 'tts', 'botProfileId'));
    }

    public function updateNongYing(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'system_prompt' => 'required|string|max:20000',
            'temperature'   => 'required|numeric|min:0|max:2',
            'top_p'         => 'required|numeric|min:0|max:1',
            'max_tokens'    => 'required|integer|min:64|max:8192',
            'tts_voice'     => 'required|string|max:32',
            'tts_temperature' => 'required|numeric|min:0|max:2',
            'tts_cloud_model' => 'required|string|max:128',
            'tts_fallback_voice_id' => 'nullable|string|max:64',
            'tts_fallback_auto_install' => 'nullable|boolean',
        ]);

        $env = app()->environment();
        $botId = (int) DB::table('app_configs')
            ->where('key', 'nong_ying_bot_profile_id')
            ->where('environment', $env)->value('value');

        $ttsConfig = [
            'voice'            => $data['tts_voice'],
            'voices_available' => ['th-premwadee', 'th-achara'],
            'temperature'      => (float) $data['tts_temperature'],
            'cloud_model'      => $data['tts_cloud_model'],
            'fallback' => [
                'engine'       => 'piper',
                'voice_id'     => $data['tts_fallback_voice_id'] ?: 'th_TH-vaja-medium',
                'auto_install' => (bool) ($data['tts_fallback_auto_install'] ?? false),
            ],
        ];

        DB::table('ai_bot_profiles')->where('id', $botId)->update([
            'system_prompt' => $data['system_prompt'],
            'temperature'   => $data['temperature'],
            'top_p'         => $data['top_p'],
            'max_tokens'    => $data['max_tokens'],
            'tts_config'    => json_encode($ttsConfig, JSON_UNESCAPED_UNICODE),
            'updated_at'    => now(),
        ]);

        // Bust the public persona ETag (admin edits should show up on
        // app's next /persona fetch).
        \Illuminate\Support\Facades\Cache::forget('persona:etag');

        return redirect()->route('admin.thaiapp.nong-ying')
            ->with('success', 'บันทึก persona ของน้องหญิงแล้วค่ะ · แอพจะเห็นการเปลี่ยนแปลงในครั้งถัดไปที่เรียก /v1/ai/nong-ying/persona');
    }

    // ─── AI Pool (API keys) ───────────────────────────────────────────

    public function aiPool(): View
    {
        $keys = DB::table('ai_api_keys')
            ->whereNull('deleted_at')
            ->orderBy('provider')
            ->orderByDesc('priority')
            ->get();
        $providers = ['gemini', 'groq', 'grok', 'qwen', 'openrouter', 'deepseek', 'typhoon', 'anthropic', 'openai'];
        return view('admin.thaiapp.ai-pool', compact('keys', 'providers'));
    }

    public function storeApiKey(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'provider' => 'required|string|max:32',
            'name'     => 'required|string|max:120',
            'api_key'  => 'required|string|max:512',
            'priority' => 'nullable|integer|min:0|max:100',
        ]);
        DB::table('ai_api_keys')->insert([
            'provider' => $data['provider'],
            'name'     => $data['name'],
            'api_key'  => $data['api_key'],
            'priority' => $data['priority'] ?? 50,
            'is_active' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        return back()->with('success', 'เพิ่ม API key แล้ว');
    }

    public function updateApiKey(Request $request, int $id): RedirectResponse
    {
        $data = $request->validate([
            'name'      => 'nullable|string|max:120',
            'priority'  => 'nullable|integer|min:0|max:100',
            'is_active' => 'nullable|boolean',
        ]);
        DB::table('ai_api_keys')->where('id', $id)->update([
            'name'      => $data['name'] ?? DB::raw('name'),
            'priority'  => $data['priority'] ?? DB::raw('priority'),
            'is_active' => isset($data['is_active']) ? (int) $data['is_active'] : DB::raw('is_active'),
            'updated_at' => now(),
        ]);
        return back()->with('success', 'อัพเดท key แล้ว');
    }

    public function destroyApiKey(int $id): RedirectResponse
    {
        DB::table('ai_api_keys')->where('id', $id)->update([
            'deleted_at' => now(),
            'is_active'  => 0,
        ]);
        return back()->with('success', 'ลบ key แล้ว');
    }

    // ─── AI Models (Gemma .task) ──────────────────────────────────────

    public function aiModels(): View
    {
        $tiers = [
            'gemma4_e2b' => ['label' => 'Gemma 4 E2B (default · 2 GB)', 'filename' => 'gemma-4-E2B-it-web.task'],
            'gemma4_e4b' => ['label' => 'Gemma 4 E4B (flagship · 3 GB)', 'filename' => 'gemma-4-E4B-it-web.task'],
        ];
        $dir = public_path('ai-models');
        $items = [];
        foreach ($tiers as $tier => $meta) {
            $path = "$dir/{$meta['filename']}";
            $exists = file_exists($path);
            $items[] = [
                'tier'     => $tier,
                'label'    => $meta['label'],
                'filename' => $meta['filename'],
                'exists'   => $exists,
                'size'     => $exists ? filesize($path) : null,
                'modified' => $exists ? date('Y-m-d H:i:s', filemtime($path)) : null,
                'sha256'   => $exists ? null : null, // computing sha256 for 2GB is slow · skip for list view
            ];
        }
        $diskFree = @disk_free_space(public_path()) ?: null;
        $hfTokenSet = (bool) (env('HF_TOKEN') ?: env('HUGGING_FACE_TOKEN') ?: env('HUGGINGFACE_TOKEN'));
        return view('admin.thaiapp.ai-models', compact('items', 'diskFree', 'hfTokenSet'));
    }

    public function syncModel(Request $request, string $tier): RedirectResponse
    {
        if (! in_array($tier, ['gemma4_e2b', 'gemma4_e4b'], true)) {
            return back()->with('error', 'tier ไม่รู้จัก');
        }
        try {
            $admin = new \App\Http\Controllers\Api\AiModelAdminController;
            $resp = $admin->sync($request, $tier);
            $body = json_decode($resp->getContent(), true);
            if ($resp->getStatusCode() === 200) {
                return back()->with('success', "sync {$tier} สำเร็จ · ขนาด " . number_format($body['size_bytes'] / (1024*1024), 0) . ' MB · ใช้เวลา ' . $body['elapsed_s'] . ' วินาที');
            }
            return back()->with('error', "sync ล้ม: " . ($body['message'] ?? 'unknown'));
        } catch (\Throwable $e) {
            return back()->with('error', 'sync ล้ม: ' . $e->getMessage());
        }
    }

    public function destroyModel(string $tier): RedirectResponse
    {
        $map = [
            'gemma4_e2b' => 'gemma-4-E2B-it-web.task',
            'gemma4_e4b' => 'gemma-4-E4B-it-web.task',
        ];
        if (! isset($map[$tier])) return back()->with('error', 'tier ไม่รู้จัก');
        $path = public_path('ai-models/' . $map[$tier]);
        if (file_exists($path)) unlink($path);
        return back()->with('success', "ลบไฟล์ {$tier} แล้ว");
    }

    // ─── Banners ──────────────────────────────────────────────────────

    public function banners(): View
    {
        $banners = DB::table('app_banners')->orderByDesc('id')->get();
        return view('admin.thaiapp.banners', compact('banners'));
    }

    public function storeBanner(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'title'            => 'required|string|max:200',
            'title_en'         => 'nullable|string|max:200',
            'description'      => 'nullable|string|max:500',
            'image_url'        => 'nullable|string|max:500',
            'background_color' => 'nullable|string|max:32',
        ]);
        DB::table('app_banners')->insert(array_merge($data, [
            'created_at' => now(),
            'updated_at' => now(),
        ]));
        return back()->with('success', 'เพิ่ม banner แล้ว');
    }

    public function updateBanner(Request $request, int $id): RedirectResponse
    {
        $data = $request->validate([
            'title'            => 'required|string|max:200',
            'title_en'         => 'nullable|string|max:200',
            'description'      => 'nullable|string|max:500',
            'image_url'        => 'nullable|string|max:500',
            'background_color' => 'nullable|string|max:32',
        ]);
        DB::table('app_banners')->where('id', $id)->update(array_merge($data, ['updated_at' => now()]));
        return back()->with('success', 'อัพเดท banner แล้ว');
    }

    public function destroyBanner(int $id): RedirectResponse
    {
        DB::table('app_banners')->where('id', $id)->delete();
        return back()->with('success', 'ลบ banner แล้ว');
    }

    // ─── Sliders ──────────────────────────────────────────────────────

    public function sliders(): View
    {
        $sliders = DB::table('app_sliders')->orderBy('order')->get();
        return view('admin.thaiapp.sliders', compact('sliders'));
    }

    public function storeSlider(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'order'        => 'nullable|integer|min:0|max:100',
            'title_th'     => 'required|string|max:200',
            'title_en'     => 'nullable|string|max:200',
            'subtitle_th'  => 'nullable|string|max:500',
            'cta_label_th' => 'nullable|string|max:80',
            'cta_deeplink' => 'nullable|string|max:500',
            'media_type'   => 'nullable|string|max:32',
            'media_url'    => 'nullable|string|max:500',
        ]);
        DB::table('app_sliders')->insert(array_merge($data, [
            'order' => $data['order'] ?? 0,
            'media_type' => $data['media_type'] ?? 'image',
            'created_at' => now(),
            'updated_at' => now(),
        ]));
        return back()->with('success', 'เพิ่ม slider แล้ว');
    }

    public function updateSlider(Request $request, int $id): RedirectResponse
    {
        $data = $request->validate([
            'order'        => 'nullable|integer|min:0|max:100',
            'title_th'     => 'required|string|max:200',
            'title_en'     => 'nullable|string|max:200',
            'subtitle_th'  => 'nullable|string|max:500',
            'cta_label_th' => 'nullable|string|max:80',
            'cta_deeplink' => 'nullable|string|max:500',
            'media_type'   => 'nullable|string|max:32',
            'media_url'    => 'nullable|string|max:500',
        ]);
        DB::table('app_sliders')->where('id', $id)->update(array_merge($data, ['updated_at' => now()]));
        return back()->with('success', 'อัพเดท slider แล้ว');
    }

    public function destroySlider(int $id): RedirectResponse
    {
        DB::table('app_sliders')->where('id', $id)->delete();
        return back()->with('success', 'ลบ slider แล้ว');
    }

    // ─── Menus (home tiles) ───────────────────────────────────────────

    public function menus(): View
    {
        $menus = DB::table('app_menus')->orderBy('slot')->orderBy('order')->get();
        return view('admin.thaiapp.menus', compact('menus'));
    }

    public function storeMenu(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'slot'     => 'required|string|max:32',
            'order'    => 'nullable|integer|min:0|max:100',
            'icon'     => 'nullable|string|max:64',
            'label_th' => 'required|string|max:80',
            'label_en' => 'nullable|string|max:80',
            'action'   => 'required|string|max:500',
            'enabled'  => 'nullable|boolean',
            'role'     => 'nullable|string|max:32',
        ]);
        DB::table('app_menus')->insert(array_merge($data, [
            'order'   => $data['order'] ?? 0,
            'enabled' => isset($data['enabled']) ? (int) $data['enabled'] : 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]));
        return back()->with('success', 'เพิ่ม menu แล้ว');
    }

    public function updateMenu(Request $request, int $id): RedirectResponse
    {
        $data = $request->validate([
            'slot'     => 'required|string|max:32',
            'order'    => 'nullable|integer|min:0|max:100',
            'icon'     => 'nullable|string|max:64',
            'label_th' => 'required|string|max:80',
            'label_en' => 'nullable|string|max:80',
            'action'   => 'required|string|max:500',
            'enabled'  => 'nullable|boolean',
            'role'     => 'nullable|string|max:32',
        ]);
        $data['enabled'] = isset($data['enabled']) ? (int) $data['enabled'] : 0;
        DB::table('app_menus')->where('id', $id)->update(array_merge($data, ['updated_at' => now()]));
        return back()->with('success', 'อัพเดท menu แล้ว');
    }

    public function destroyMenu(int $id): RedirectResponse
    {
        DB::table('app_menus')->where('id', $id)->delete();
        return back()->with('success', 'ลบ menu แล้ว');
    }

    // ─── App Config (key-value) ───────────────────────────────────────

    public function config(): View
    {
        $env = app()->environment();
        $configs = DB::table('app_configs')
            ->where('environment', $env)
            ->orderBy('key')
            ->get();
        return view('admin.thaiapp.config', compact('configs', 'env'));
    }

    public function updateConfig(Request $request, int $id): RedirectResponse
    {
        $data = $request->validate([
            'value' => 'nullable|string|max:5000',
        ]);
        DB::table('app_configs')->where('id', $id)->update([
            'value'      => $data['value'] ?? '',
            'updated_at' => now(),
        ]);
        \Illuminate\Support\Facades\Cache::forget('app_config');
        return back()->with('success', 'อัพเดท config แล้ว');
    }

    public function storeConfig(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'key'         => 'required|string|max:120',
            'value'       => 'nullable|string|max:5000',
            'value_type'  => 'required|in:string,int,bool,json',
            'description' => 'nullable|string|max:500',
            'is_public'   => 'nullable|boolean',
        ]);
        DB::table('app_configs')->insert([
            'key'         => $data['key'],
            'environment' => app()->environment(),
            'value'       => $data['value'] ?? '',
            'value_type'  => $data['value_type'],
            'description' => $data['description'] ?? null,
            'is_public'   => isset($data['is_public']) ? (int) $data['is_public'] : 1,
            'created_at'  => now(),
            'updated_at'  => now(),
        ]);
        return back()->with('success', 'เพิ่ม config แล้ว');
    }

    public function destroyConfig(int $id): RedirectResponse
    {
        DB::table('app_configs')->where('id', $id)->delete();
        return back()->with('success', 'ลบ config แล้ว');
    }

    // ─── Releases ─────────────────────────────────────────────────────

    public function releases(): View
    {
        $releases = DB::table('app_releases')->orderByDesc('id')->limit(30)->get();
        return view('admin.thaiapp.releases', compact('releases'));
    }

    // ─── Helpers ──────────────────────────────────────────────────────

    private function syncedModelsCount(): int
    {
        $dir = public_path('ai-models');
        if (! is_dir($dir)) return 0;
        return count(array_filter(
            scandir($dir) ?: [],
            fn ($f) => str_ends_with($f, '.task')
        ));
    }
}
