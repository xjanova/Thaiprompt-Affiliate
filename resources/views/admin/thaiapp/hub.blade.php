@extends('layouts.arrow-x-v3')
@section('title', 'Thaiapp-MANAGER · ควบคุมแอพ')
@section('content')
<div class="container-fluid px-4 py-6">
    <div class="flex justify-between items-start mb-8">
        <div>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">📱 Thaiapp-MANAGER</h1>
            <p class="text-gray-600 dark:text-gray-400 mt-1">ควบคุมทุกอย่างที่แอพดึงจากเว็บ · แก้แล้วไม่ต้อง redeploy</p>
        </div>
        <div class="text-right">
            <div class="text-xs text-gray-500">HF Token</div>
            <div class="{{ $stats['hf_token_set'] ? 'text-emerald-500' : 'text-rose-500' }} font-bold">
                {{ $stats['hf_token_set'] ? '✓ SET' : '✗ MISSING' }}
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        @include('admin.thaiapp._card', ['href' => route('admin.thaiapp.nong-ying'), 'icon' => '🌸', 'title' => 'น้องหญิง', 'subtitle' => 'persona + TTS + greetings', 'stat' => 'bot profile #' . $stats['bot_profile_id']])

        @include('admin.thaiapp._card', ['href' => route('admin.thaiapp.ai-pool'), 'icon' => '🔑', 'title' => 'AI Pool', 'subtitle' => 'API keys (Gemini/Groq/...)', 'stat' => $stats['api_keys_active'] . ' / ' . $stats['api_keys_total'] . ' active'])

        @include('admin.thaiapp._card', ['href' => route('admin.thaiapp.ai-models'), 'icon' => '🧠', 'title' => 'AI Models', 'subtitle' => 'Gemma .task sync', 'stat' => $stats['models_synced'] . ' synced'])

        @include('admin.thaiapp._card', ['href' => route('admin.thaiapp.banners'), 'icon' => '🎨', 'title' => 'Banners', 'subtitle' => 'หน้าแรก notifications', 'stat' => $stats['banners_total'] . ' total'])

        @include('admin.thaiapp._card', ['href' => route('admin.thaiapp.sliders'), 'icon' => '🖼️', 'title' => 'Sliders', 'subtitle' => 'hero carousel', 'stat' => $stats['sliders_total'] . ' total'])

        @include('admin.thaiapp._card', ['href' => route('admin.thaiapp.menus'), 'icon' => '🧩', 'title' => 'Menus', 'subtitle' => 'home tiles', 'stat' => $stats['menus_total'] . ' total'])

        @include('admin.thaiapp._card', ['href' => route('admin.thaiapp.config'), 'icon' => '⚙️', 'title' => 'App Config', 'subtitle' => 'key-value (URLs, flags)', 'stat' => $stats['configs_total'] . ' entries'])

        @include('admin.thaiapp._card', ['href' => route('admin.thaiapp.releases'), 'icon' => '📦', 'title' => 'Releases', 'subtitle' => 'APK version history', 'stat' => $stats['releases_total'] . ' total'])
    </div>

    <div class="mt-8 p-4 bg-blue-50 dark:bg-blue-950/30 border border-blue-200 dark:border-blue-800 rounded-xl text-sm text-blue-900 dark:text-blue-200">
        💡 <strong>Tip:</strong> ทุกอย่างใน Manager นี้แก้แล้วเห็นผลทันทีในแอพ (persona/config มี ETag cache 5 นาที · banners/sliders รีเฟรชหน้าแรก)
    </div>
</div>
@endsection
