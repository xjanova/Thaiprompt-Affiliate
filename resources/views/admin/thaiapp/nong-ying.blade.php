@extends('layouts.arrow-x-v3')
@section('title', 'น้องหญิง · persona')
@section('content')
<div class="container-fluid px-4 py-6 max-w-5xl mx-auto">
    <a href="{{ route('admin.thaiapp.hub') }}" class="text-sm text-gray-500 hover:text-blue-500">← Thaiapp-MANAGER</a>
    <h1 class="text-3xl font-bold text-gray-900 dark:text-white mt-1 mb-6">🌸 น้องหญิง (bot profile #{{ $botProfileId }})</h1>
    @include('admin.thaiapp._flash')

    @if(!$bot)
        <div class="p-6 bg-rose-50 border border-rose-200 rounded-xl text-rose-800">
            ไม่พบ bot profile · ต้อง seed ก่อน (ดู v1.0.18 tinker script)
        </div>
    @else
    <form method="POST" action="{{ route('admin.thaiapp.nong-ying.update') }}" class="space-y-6">
        @csrf @method('PUT')

        <div class="p-6 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl">
            <h2 class="font-bold text-lg mb-4">Persona · system prompt</h2>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">System prompt (Markdown-ish)</label>
            <textarea name="system_prompt" rows="20" required
                class="w-full px-3 py-2 font-mono text-sm bg-gray-50 dark:bg-gray-900 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500">{{ old('system_prompt', $bot->system_prompt) }}</textarea>
            <div class="text-xs text-gray-500 mt-1">คำสั่งนี้แทรกใน request ทุกครั้ง (on-device + cloud) · มี app map สำหรับ deep-link [GO:/path]</div>
        </div>

        <div class="grid grid-cols-3 gap-4">
            <div class="p-4 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl">
                <label class="block text-sm font-medium mb-1">Temperature (0-2)</label>
                <input type="number" name="temperature" step="0.1" min="0" max="2" required
                    value="{{ old('temperature', $bot->temperature) }}"
                    class="w-full px-3 py-2 bg-gray-50 dark:bg-gray-900 border rounded-lg">
                <div class="text-xs text-gray-500 mt-1">0 = แม่นยำ · 1 = ธรรมชาติ · >1.5 = แฟนตาซี</div>
            </div>
            <div class="p-4 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl">
                <label class="block text-sm font-medium mb-1">Top-P</label>
                <input type="number" name="top_p" step="0.05" min="0" max="1" required
                    value="{{ old('top_p', $bot->top_p) }}"
                    class="w-full px-3 py-2 bg-gray-50 dark:bg-gray-900 border rounded-lg">
            </div>
            <div class="p-4 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl">
                <label class="block text-sm font-medium mb-1">Max tokens</label>
                <input type="number" name="max_tokens" min="64" max="8192" required
                    value="{{ old('max_tokens', $bot->max_tokens) }}"
                    class="w-full px-3 py-2 bg-gray-50 dark:bg-gray-900 border rounded-lg">
            </div>
        </div>

        <div class="p-6 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl">
            <h2 class="font-bold text-lg mb-4">🎤 TTS config (เสียงพูด)</h2>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium mb-1">Voice (cloud)</label>
                    <select name="tts_voice" class="w-full px-3 py-2 bg-gray-50 dark:bg-gray-900 border rounded-lg">
                        @foreach(['th-premwadee' => 'th-premwadee (Aoede · warm · default)', 'th-achara' => 'th-achara (Callirrhoe · gentle)'] as $v => $label)
                            <option value="{{ $v }}" @selected(($tts['voice'] ?? 'th-premwadee') === $v)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1">Voice temperature (0-2)</label>
                    <input type="number" name="tts_temperature" step="0.1" min="0" max="2"
                        value="{{ old('tts_temperature', $tts['temperature'] ?? 0.8) }}"
                        class="w-full px-3 py-2 bg-gray-50 dark:bg-gray-900 border rounded-lg">
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1">Cloud model</label>
                    <input type="text" name="tts_cloud_model"
                        value="{{ old('tts_cloud_model', $tts['cloud_model'] ?? 'gemini-2.5-flash-preview-tts') }}"
                        class="w-full px-3 py-2 bg-gray-50 dark:bg-gray-900 border rounded-lg">
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1">Piper fallback voice</label>
                    <input type="text" name="tts_fallback_voice_id"
                        value="{{ old('tts_fallback_voice_id', $tts['fallback']['voice_id'] ?? 'th_TH-vaja-medium') }}"
                        class="w-full px-3 py-2 bg-gray-50 dark:bg-gray-900 border rounded-lg">
                    <div class="text-xs text-gray-500 mt-1">ใช้เมื่อ Gemini ควอต้าหมด · 80 MB · user install ใน Settings</div>
                </div>
                <div class="col-span-2">
                    <label class="flex items-center gap-2">
                        <input type="hidden" name="tts_fallback_auto_install" value="0">
                        <input type="checkbox" name="tts_fallback_auto_install" value="1"
                            @checked($tts['fallback']['auto_install'] ?? false)
                            class="rounded">
                        <span class="text-sm">Auto-install Piper บน Wi-Fi (อนาคต · ยังไม่ต่อใน app v1.0.20)</span>
                    </label>
                </div>
            </div>
        </div>

        <div class="flex gap-3">
            <button type="submit" class="px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-lg">
                💾 บันทึก persona
            </button>
            <a href="{{ route('admin.thaiapp.hub') }}" class="px-6 py-3 bg-gray-200 dark:bg-gray-700 rounded-lg">ยกเลิก</a>
        </div>
    </form>
    @endif
</div>
@endsection
