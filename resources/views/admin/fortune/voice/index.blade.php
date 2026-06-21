@extends('layouts.admin-v3')

@section('title', '🎧 จัดการเสียง')

@php
    // ⚠️ ใช้ชื่อ $providerOptions (ไม่ใช่ $providers) — กันชนกับ $providers (diagnostic list)
    //    ที่ controller ส่งมาให้ section ตรวจระบบด้านล่าง
    $providerOptions = [
        'minimax' => '🎙️ MiniMax (พรีเมียม)',
        'openai_tts' => '🎙️ OpenAI TTS',
        'google_tts' => '🎙️ Google Cloud TTS (ฟรี 1M/เดือน)',
        'gtts' => '🆓 gTTS (ฟรี ไม่ต้องตั้ง key)',
    ];
    $openaiVoices = ['alloy', 'echo', 'fable', 'onyx', 'nova', 'shimmer'];
    $tierScopes = [
        'celtic_99_only' => '🌙 เฉพาะ Celtic 99฿',
        'paid_all' => '💎 ทุก reading ที่จ่ายเงิน',
        'all' => '🌐 ทุก reading',
    ];
    $fallbacks = is_array($settings->voice_summary_fallback_providers) ? $settings->voice_summary_fallback_providers : [];
@endphp

@section('content')
<div class="container mx-auto px-4 py-6" x-data="voiceManager()">

    {{-- Header --}}
    <div class="flex items-center justify-between mb-6 flex-wrap gap-2">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">🎧 จัดการเสียง</h1>
            <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                ตั้งค่าโมเดลเสียง · คลังเสียงระบบ (สร้างไฟล์เก็บไว้ล่วงหน้า) · เสียงทำนาย —
                <span class="text-amber-600 dark:text-amber-400 font-medium">เสียงระบบส่งเฉพาะ Facebook</span>
            </p>
        </div>
        <div class="flex gap-2">
            <a href="#diagnostic"
               class="px-4 py-2 text-sm bg-gray-200 dark:bg-gray-700 hover:bg-gray-300 dark:hover:bg-gray-600 text-gray-900 dark:text-white rounded-lg transition">
                🩺 ตรวจระบบ ↓
            </a>
        </div>
    </div>

    {{-- Flash --}}
    @if(session('success'))
        <div class="mb-4 p-4 rounded-lg bg-emerald-50 dark:bg-emerald-900/20 border border-emerald-300 dark:border-emerald-700 text-emerald-800 dark:text-emerald-200 text-sm">
            {{ session('success') }}
        </div>
    @endif
    @if($errors->any())
        <div class="mb-4 p-4 rounded-lg bg-red-50 dark:bg-red-900/20 border border-red-300 dark:border-red-700 text-red-800 dark:text-red-200 text-sm">
            <ul class="list-disc list-inside">
                @foreach($errors->all() as $err)<li>{{ $err }}</li>@endforeach
            </ul>
        </div>
    @endif

    {{-- ════════════════ ตั้งค่าเครื่องเสียง + เสียงทำนาย ════════════════ --}}
    <form method="POST" action="{{ route('admin.fortune.voice.settings.update') }}"
          class="bg-white dark:bg-gray-800 rounded-xl shadow p-6 mb-6">
        @csrf
        @method('PUT')

        <h2 class="text-lg font-bold text-gray-900 dark:text-white mb-4">⚙️ ตั้งค่าเครื่องเสียง (โมเดล/เสียง)</h2>

        {{-- master toggles --}}
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-5">
            <label class="flex items-start gap-3 p-4 rounded-lg border border-gray-200 dark:border-gray-700 cursor-pointer">
                <input type="checkbox" name="system_voice_enabled" value="1" @checked($settings->system_voice_enabled)
                       class="mt-1 rounded text-sky-600">
                <span>
                    <span class="block font-medium text-gray-900 dark:text-white">🔔 เปิดเสียงระบบ (master)</span>
                    <span class="block text-xs text-gray-500 dark:text-gray-400">ส่งเสียงข้อความกลาง (กล่องกระตุ้น/กติกา/วันเกิด ฯลฯ) — FB เท่านั้น</span>
                </span>
            </label>
            <label class="flex items-start gap-3 p-4 rounded-lg border border-gray-200 dark:border-gray-700 cursor-pointer">
                <input type="checkbox" name="voice_summary_enabled" value="1" @checked($settings->voice_summary_enabled)
                       class="mt-1 rounded text-sky-600">
                <span>
                    <span class="block font-medium text-gray-900 dark:text-white">🔮 เปิดเสียงทำนาย/บทสรุป</span>
                    <span class="block text-xs text-gray-500 dark:text-gray-400">เสียงสรุปคำทำนาย (สร้างสดต่อคน)</span>
                </span>
            </label>
        </div>

        {{-- provider chain --}}
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-5">
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">โมเดลหลัก (Primary provider)</label>
                <select name="voice_summary_primary_provider"
                        class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm">
                    @foreach($providerOptions as $val => $label)
                        <option value="{{ $val }}" @selected(($settings->voice_summary_primary_provider ?? 'minimax') === $val)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">โมเดลสำรอง (Fallback — ลองตามลำดับ)</label>
                <div class="flex flex-wrap gap-3 p-2 rounded-lg border border-gray-200 dark:border-gray-700">
                    @foreach($providerOptions as $val => $label)
                        <label class="flex items-center gap-1.5 text-sm text-gray-700 dark:text-gray-300">
                            <input type="checkbox" name="voice_summary_fallback_providers[]" value="{{ $val }}"
                                   @checked(in_array($val, $fallbacks, true)) class="rounded text-sky-600">
                            {{ \Illuminate\Support\Str::of($label)->after(' ') }}
                        </label>
                    @endforeach
                </div>
            </div>
        </div>

        {{-- voice tuning per provider --}}
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-5">
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">MiniMax — model</label>
                <input type="text" name="minimax_model" value="{{ $settings->minimax_model }}" placeholder="speech-2.8-hd"
                       class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1 mt-2">MiniMax — voice id</label>
                <input type="text" name="minimax_voice_id" value="{{ $settings->minimax_voice_id }}" placeholder="Thai_female_1_sample1"
                       class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">OpenAI — model</label>
                <input type="text" name="openai_tts_model" value="{{ $settings->openai_tts_model }}" placeholder="tts-1 / tts-1-hd"
                       class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1 mt-2">OpenAI — voice</label>
                <select name="openai_tts_voice"
                        class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm">
                    <option value="">— default —</option>
                    @foreach($openaiVoices as $v)
                        <option value="{{ $v }}" @selected($settings->openai_tts_voice === $v)>{{ $v }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Google — voice</label>
                <input type="text" name="google_tts_voice" value="{{ $settings->google_tts_voice }}" placeholder="th-TH-Neural2-C"
                       class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1 mt-2">Google — speaking rate (0.25–4.0)</label>
                <input type="number" step="0.05" min="0.25" max="4.0" name="google_tts_speaking_rate" value="{{ $settings->google_tts_speaking_rate }}"
                       class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm">
            </div>
        </div>

        {{-- reading-voice scope + intro --}}
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-5">
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">เสียงทำนาย — ขอบเขต (tier scope)</label>
                <select name="voice_summary_tier_scope"
                        class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm">
                    @foreach($tierScopes as $val => $label)
                        <option value="{{ $val }}" @selected(($settings->voice_summary_tier_scope ?? 'celtic_99_only') === $val)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">ความยาวสูงสุดต่อบทสรุป (ตัวอักษร)</label>
                <input type="number" min="200" max="5000" name="voice_summary_max_chars" value="{{ $settings->voice_summary_max_chars ?? 2000 }}"
                       class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">ข้อความเกริ่นก่อนส่งเสียงทำนาย</label>
                <input type="text" name="voice_summary_intro_message" value="{{ $settings->voice_summary_intro_message }}"
                       class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm">
            </div>
        </div>

        <div class="flex items-center justify-between flex-wrap gap-2">
            <p class="text-xs text-gray-500 dark:text-gray-400">
                💡 API key ตั้งที่ <a href="{{ route('admin.fortune.settings.index') }}#tts" class="underline">ตั้งค่า</a>
                หรือ <a href="{{ url('/admin/ai-api-keys') }}" class="underline">AI API Keys</a>
            </p>
            <button type="submit" class="px-6 py-2.5 bg-sky-600 hover:bg-sky-700 text-white text-sm font-medium rounded-lg transition">
                💾 บันทึกการตั้งค่า
            </button>
        </div>
    </form>

    {{-- ════════════════ พรีวิวเสียง ════════════════ --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-6 mb-6">
        <h2 class="text-lg font-bold text-gray-900 dark:text-white mb-3">▶️ ทดลองฟังเสียง (ก่อนบันทึก)</h2>
        <textarea x-model="previewTextValue" rows="2" placeholder="พิมพ์ข้อความที่อยากฟัง..."
                  class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm mb-2"></textarea>
        <div class="flex items-center gap-2 flex-wrap">
            <select x-model="previewProvider" class="rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm">
                <option value="">โมเดลตามค่าตั้ง</option>
                @foreach($providerOptions as $val => $label)
                    <option value="{{ $val }}">{{ $label }}</option>
                @endforeach
            </select>
            <input type="text" x-model="previewVoice" placeholder="voice id (ถ้าต้องการ override)"
                   class="rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm">
            <button type="button" @click="doPreview()" :disabled="previewLoading"
                    class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 disabled:opacity-50 text-white text-sm rounded-lg transition">
                <span x-show="!previewLoading">▶️ ฟัง</span>
                <span x-show="previewLoading">⏳ กำลังสร้าง...</span>
            </button>
            <span x-show="previewError" x-text="previewError" class="text-sm text-red-600 dark:text-red-400"></span>
        </div>
        <audio x-show="previewUrl" :src="previewUrl" controls class="mt-3 w-full max-w-md"></audio>
    </div>

    {{-- ════════════════ คลังเสียงระบบ ════════════════ --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-6">
        <h2 class="text-lg font-bold text-gray-900 dark:text-white mb-1">🗂️ คลังเสียงระบบ (ข้อความกลาง)</h2>
        <p class="text-sm text-gray-600 dark:text-gray-400 mb-5">
            แก้ข้อความ → กด <b>🎙️ สร้างเสียง</b> เพื่อสร้างไฟล์เก็บไว้ (ใช้ซ้ำทุกคน ไม่สร้างใหม่) ·
            เปิด/ปิดรายอันได้ · เก็บไฟล์ที่เซิร์ฟเวอร์เรา (ไม่ส่ง cloud)
        </p>

        @forelse($clipsByGroup as $group => $clips)
            <div class="mb-6">
                <h3 class="text-md font-semibold text-gray-800 dark:text-gray-200 mb-3 border-b border-gray-200 dark:border-gray-700 pb-1">
                    {{ $groupLabels[$group] ?? $group }}
                </h3>

                <div class="space-y-4">
                    @foreach($clips as $clip)
                        <div class="rounded-lg border border-gray-200 dark:border-gray-700 p-4"
                             x-data="{ clipState: clips[{{ $clip->id }}] }">
                            <form method="POST" action="{{ route('admin.fortune.voice.clips.update', $clip) }}">
                                @csrf
                                @method('PUT')

                                <div class="flex items-center justify-between gap-2 mb-2 flex-wrap">
                                    <div class="flex items-center gap-2">
                                        <span class="text-xs font-mono px-2 py-0.5 rounded bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300">{{ $clip->clip_key }}</span>
                                        @if($clip->hasAudio())
                                            @if($clip->isUploaded())
                                                <span class="text-xs px-2 py-0.5 rounded bg-violet-100 dark:bg-violet-900/30 text-violet-700 dark:text-violet-300">
                                                    ⬆️ ไฟล์อัปโหลด{{ $clip->audio_original_name ? ': '.\Illuminate\Support\Str::limit($clip->audio_original_name, 24) : '' }}
                                                </span>
                                            @else
                                                <span class="text-xs px-2 py-0.5 rounded bg-emerald-100 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-300">
                                                    🎙️ เสียง AI ({{ $clip->audio_provider }})
                                                </span>
                                            @endif
                                        @else
                                            <span class="text-xs px-2 py-0.5 rounded bg-amber-100 dark:bg-amber-900/30 text-amber-700 dark:text-amber-300">⚠️ ยังไม่มีไฟล์เสียง</span>
                                        @endif
                                    </div>
                                    <label class="flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300">
                                        <input type="checkbox" name="enabled" value="1" @checked($clip->enabled) class="rounded text-emerald-600">
                                        เปิดใช้ (ส่งเสียงนี้)
                                    </label>
                                </div>

                                <input type="text" name="title" value="{{ $clip->title }}"
                                       class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm font-medium mb-2">
                                <input type="text" name="description" value="{{ $clip->description }}" placeholder="คำอธิบายว่าใช้ตอนไหน"
                                       class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 text-xs mb-2">
                                <textarea name="script_text" rows="3"
                                          class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm mb-2">{{ $clip->script_text }}</textarea>

                                {{-- per-clip voice override --}}
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-2 mb-3">
                                    <select name="voice_provider" class="rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-xs">
                                        <option value="">โมเดลตามค่ากลาง</option>
                                        @foreach($providerOptions as $val => $label)
                                            <option value="{{ $val }}" @selected(($clip->voice_config['provider'] ?? '') === $val)>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                    <input type="text" name="voice_id" value="{{ $clip->voice_config['voice'] ?? '' }}" placeholder="voice id (override เฉพาะคลิปนี้)"
                                           class="rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-xs">
                                </div>

                                <div class="flex items-center gap-2 flex-wrap">
                                    <button type="submit" class="px-4 py-1.5 bg-sky-600 hover:bg-sky-700 text-white text-xs rounded-lg transition">💾 บันทึกข้อความ</button>
                                    <button type="button" @click="generate({{ $clip->id }})" :disabled="clipState.loading"
                                            class="px-4 py-1.5 bg-emerald-600 hover:bg-emerald-700 disabled:opacity-50 text-white text-xs rounded-lg transition">
                                        <span x-show="!clipState.loading">🎙️ สร้างเสียง (เก็บไว้)</span>
                                        <span x-show="clipState.loading">⏳ กำลังสร้าง...</span>
                                    </button>
                                    @if($clip->hasAudio())
                                        <button type="button" @click="deleteAudio({{ $clip->id }})" class="px-3 py-1.5 bg-red-100 dark:bg-red-900/30 hover:bg-red-200 text-red-700 dark:text-red-300 text-xs rounded-lg transition">🗑️ ลบเสียง</button>
                                    @endif
                                    <span x-show="clipState.msg" x-text="clipState.msg" :class="clipState.ok ? 'text-emerald-600 dark:text-emerald-400' : 'text-red-600 dark:text-red-400'" class="text-xs"></span>
                                </div>
                            </form>

                            {{-- ⬆️ อัปโหลดไฟล์เสียงเอง (แทน TTS) — รองรับทุกฟอร์แมต (ฟอร์มแยก ห้าม nest) --}}
                            <form method="POST" action="{{ route('admin.fortune.voice.clips.upload', $clip) }}"
                                  enctype="multipart/form-data" class="mt-2 flex items-center gap-2 flex-wrap border-t border-dashed border-gray-200 dark:border-gray-700 pt-2">
                                @csrf
                                <span class="text-xs text-gray-500 dark:text-gray-400">⬆️ ใช้ไฟล์เสียงเอง:</span>
                                <input type="file" name="audio_file" accept="audio/*,.m4a,.mp3,.wav,.ogg,.aac,.flac,.opus"
                                       required class="text-xs text-gray-700 dark:text-gray-300 file:mr-2 file:py-1 file:px-3 file:rounded-lg file:border-0 file:text-xs file:bg-violet-100 file:text-violet-700 hover:file:bg-violet-200">
                                <button type="submit" class="px-3 py-1.5 bg-violet-600 hover:bg-violet-700 text-white text-xs rounded-lg transition">อัปโหลด</button>
                                <span class="text-xs text-gray-400">(รองรับทุกฟอร์แมต — ระบบแปลงเป็น mp3 ให้อัตโนมัติ)</span>
                            </form>

                            {{-- audio player (existing or freshly generated) --}}
                            <audio x-show="clipState.url || '{{ $clip->audio_url }}'"
                                   :src="clipState.url || '{{ $clip->audio_url }}'" controls class="mt-3 w-full max-w-md"></audio>
                        </div>
                    @endforeach
                </div>
            </div>
        @empty
            <div class="text-center text-gray-500 dark:text-gray-400 py-8">
                ยังไม่มีคลิปเสียงระบบ — รัน <code class="px-1 bg-gray-100 dark:bg-gray-700 rounded">php artisan db:seed --class=FortuneSystemVoiceClipSeeder</code>
            </div>
        @endforelse
    </div>

    {{-- ════════════════ 🩺 ตรวจระบบ (รวมจาก Voice Diagnostic) ════════════════ --}}
    <div id="diagnostic" class="mt-6 bg-white dark:bg-gray-800 rounded-xl shadow p-6">
        <h2 class="text-lg font-bold text-gray-900 dark:text-white mb-4">🩺 ตรวจระบบเสียง (Diagnostic)</h2>

        {{-- stats + storage --}}
        <div class="grid grid-cols-2 md:grid-cols-4 gap-3 text-sm mb-5">
            <div class="p-3 rounded bg-gray-50 dark:bg-gray-700">
                <div class="text-xs text-gray-600 dark:text-gray-400">Celtic ทั้งหมด</div>
                <div class="text-xl font-bold text-gray-900 dark:text-white">{{ number_format($stats['total_celtic']) }}</div>
            </div>
            <div class="p-3 rounded bg-emerald-50 dark:bg-emerald-900/20">
                <div class="text-xs text-emerald-600 dark:text-emerald-400">มีเสียงแล้ว</div>
                <div class="text-xl font-bold text-emerald-700 dark:text-emerald-300">{{ number_format($stats['voice_generated']) }}</div>
            </div>
            <div class="p-3 rounded bg-red-50 dark:bg-red-900/20">
                <div class="text-xs text-red-600 dark:text-red-400">เสียง Fail</div>
                <div class="text-xl font-bold text-red-700 dark:text-red-300">{{ number_format($stats['voice_failed_state']) }}</div>
            </div>
            <div class="p-3 rounded {{ ($storageStatus['available'] ?? false) ? 'bg-emerald-50 dark:bg-emerald-900/20' : 'bg-amber-50 dark:bg-amber-900/20' }}">
                <div class="text-xs text-gray-600 dark:text-gray-400">Storage (เสียงทำนาย)</div>
                <div class="text-sm font-bold {{ ($storageStatus['available'] ?? false) ? 'text-emerald-700 dark:text-emerald-300' : 'text-amber-700 dark:text-amber-300' }}">
                    {{ $storage->driverName($storage->driver()) }} {{ ($storageStatus['available'] ?? false) ? '✅' : '⚠️' }}
                </div>
            </div>
        </div>

        {{-- provider status + test --}}
        <h3 class="text-md font-semibold text-gray-800 dark:text-gray-200 mb-2">🎙️ สถานะ Provider + ทดสอบฟัง</h3>
        <div class="overflow-x-auto mb-5">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-gray-200 dark:border-gray-700 text-left">
                        <th class="py-2 px-2">Provider</th>
                        <th class="py-2 px-2 text-center">บทบาท</th>
                        <th class="py-2 px-2 text-center">สถานะ</th>
                        <th class="py-2 px-2 text-center">ทดสอบ</th>
                        <th class="py-2 px-2">ผล</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($providers as $p)
                        <tr class="border-b border-gray-100 dark:border-gray-700 align-top">
                            <td class="py-3 px-2 font-medium text-gray-900 dark:text-white">{{ $p['label'] }}</td>
                            <td class="py-3 px-2 text-center text-xs">
                                @if($p['is_primary'])
                                    <span class="px-2 py-0.5 bg-purple-100 dark:bg-purple-900/40 text-purple-800 dark:text-purple-200 rounded">⭐ หลัก</span>
                                @elseif($p['in_fallback'])
                                    <span class="px-2 py-0.5 bg-blue-100 dark:bg-blue-900/40 text-blue-800 dark:text-blue-200 rounded">🔁 สำรอง</span>
                                @else
                                    <span class="text-gray-400">—</span>
                                @endif
                            </td>
                            <td class="py-3 px-2 text-center">
                                @if($p['available'])
                                    <span class="px-2 py-1 text-xs bg-emerald-100 dark:bg-emerald-900/40 text-emerald-800 dark:text-emerald-200 rounded font-semibold">✅ พร้อม</span>
                                @else
                                    <span class="px-2 py-1 text-xs bg-red-100 dark:bg-red-900/40 text-red-800 dark:text-red-200 rounded font-semibold">❌ ไม่พร้อม</span>
                                @endif
                            </td>
                            <td class="py-3 px-2 text-center">
                                <button type="button" @click="testProvider('{{ $p['name'] }}')" :disabled="busy['{{ $p['name'] }}']"
                                        class="px-3 py-1 text-xs bg-amber-500 hover:bg-amber-600 disabled:opacity-50 text-white rounded">
                                    <span x-show="!busy['{{ $p['name'] }}']">🎙️ ทดสอบ</span>
                                    <span x-show="busy['{{ $p['name'] }}']">⏳ ...</span>
                                </button>
                            </td>
                            <td class="py-3 px-2">
                                <template x-if="results['{{ $p['name'] }}']">
                                    <div class="text-xs">
                                        <div :class="results['{{ $p['name'] }}'].success ? 'text-emerald-600 dark:text-emerald-400' : 'text-red-600 dark:text-red-400'"
                                             x-text="results['{{ $p['name'] }}'].message || results['{{ $p['name'] }}'].error"></div>
                                        <template x-if="results['{{ $p['name'] }}'].audio_url">
                                            <audio :src="results['{{ $p['name'] }}'].audio_url" controls class="mt-1 h-8"></audio>
                                        </template>
                                    </div>
                                </template>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        {{-- recent fails --}}
        @if($recentFails->count() > 0)
            <h3 class="text-md font-semibold text-gray-800 dark:text-gray-200 mb-2">⚠️ เสียงทำนายล้มเหลว 20 อันล่าสุด</h3>
            <div class="overflow-x-auto mb-3">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-gray-200 dark:border-gray-700 text-left">
                            <th class="py-2 px-2">Reading</th>
                            <th class="py-2 px-2">Platform</th>
                            <th class="py-2 px-2 max-w-md">Error</th>
                            <th class="py-2 px-2">เมื่อ</th>
                            <th class="py-2 px-2 text-center">ลองใหม่</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($recentFails as $r)
                            <tr class="border-b border-gray-100 dark:border-gray-700">
                                <td class="py-2 px-2 font-mono text-xs">#{{ $r['id'] }}</td>
                                <td class="py-2 px-2 text-xs">{{ $r['platform'] }}</td>
                                <td class="py-2 px-2 text-xs text-red-600 dark:text-red-400 max-w-md truncate" title="{{ $r['error'] }}">{{ \Illuminate\Support\Str::limit($r['error'], 80) }}</td>
                                <td class="py-2 px-2 text-xs text-gray-500">{{ $r['created_at'] }}</td>
                                <td class="py-2 px-2 text-center">
                                    <button type="button" @click="regenerate({{ $r['id'] }})" class="px-2 py-1 text-xs bg-blue-600 hover:bg-blue-700 text-white rounded">🔄</button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif

        <div class="text-xs text-gray-500 dark:text-gray-400">
            💡 Provider ไม่พร้อม: ตั้ง key ที่ <a href="{{ url('/admin/ai-api-keys') }}" class="underline">AI API Keys</a> (minimax/openai purpose=tts) ·
            Google = <code>GOOGLE_TTS_API_KEY</code> ใน .env · gtts ฟรีใช้ได้เลย ·
            storage:link หาย → <code>php artisan storage:link</code>
        </div>
    </div>
</div>

<script>
function voiceManager() {
    return {
        CSRF: '{{ csrf_token() }}',
        // per-clip state
        clips: {
            @foreach($clipsByGroup->flatten() as $clip)
                {{ $clip->id }}: { loading: false, msg: '', ok: false, url: '' },
            @endforeach
        },
        // preview
        previewTextValue: '',
        previewProvider: '',
        previewVoice: '',
        previewUrl: '',
        previewError: '',
        previewLoading: false,
        // diagnostic (provider test / regenerate)
        busy: {},
        results: {},

        async generate(id) {
            this.clips[id].loading = true;
            this.clips[id].msg = '';
            try {
                const res = await fetch(`{{ url('/admin/fortune/voice/clips') }}/${id}/generate`, {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': this.CSRF, 'Accept': 'application/json' },
                });
                const data = await res.json();
                if (data.success) {
                    this.clips[id].url = data.audio_url + '?t=' + Date.now();
                    this.clips[id].ok = true;
                    this.clips[id].msg = '✅ สร้างเสียงสำเร็จ (' + (data.provider_used || '') + ')';
                } else {
                    this.clips[id].ok = false;
                    this.clips[id].msg = '❌ ' + (data.error || 'สร้างไม่สำเร็จ');
                }
            } catch (e) {
                this.clips[id].ok = false;
                this.clips[id].msg = '❌ ' + e.message;
            } finally {
                this.clips[id].loading = false;
            }
        },

        async deleteAudio(id) {
            if (!confirm('ลบไฟล์เสียงของคลิปนี้?')) return;
            try {
                const res = await fetch(`{{ url('/admin/fortune/voice/clips') }}/${id}/audio`, {
                    method: 'DELETE',
                    headers: { 'X-CSRF-TOKEN': this.CSRF, 'Accept': 'application/json' },
                });
                const data = await res.json();
                if (data.success) {
                    this.clips[id].url = '';
                    this.clips[id].ok = true;
                    this.clips[id].msg = '🗑️ ลบแล้ว — รีโหลดหน้าเพื่อดูสถานะ';
                }
            } catch (e) {
                this.clips[id].msg = '❌ ' + e.message;
            }
        },

        async doPreview() {
            if (!this.previewTextValue || this.previewTextValue.trim().length < 5) {
                this.previewError = 'พิมพ์ข้อความอย่างน้อย 5 ตัวอักษร';
                return;
            }
            this.previewLoading = true;
            this.previewError = '';
            this.previewUrl = '';
            try {
                const payload = { text: this.previewTextValue };
                if (this.previewProvider) payload.voice_provider = this.previewProvider;
                if (this.previewVoice) payload.voice_id = this.previewVoice;
                const res = await fetch(`{{ route('admin.fortune.voice.preview') }}`, {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': this.CSRF, 'Accept': 'application/json', 'Content-Type': 'application/json' },
                    body: JSON.stringify(payload),
                });
                const data = await res.json();
                if (data.success) {
                    this.previewUrl = data.audio_url + '?t=' + Date.now();
                } else {
                    this.previewError = data.error || 'สร้างเสียงไม่สำเร็จ';
                }
            } catch (e) {
                this.previewError = e.message;
            } finally {
                this.previewLoading = false;
            }
        },

        // 🩺 diagnostic — ทดสอบ provider (ใช้ route voice-diagnostic เดิม)
        async testProvider(name) {
            this.busy[name] = true;
            this.results[name] = null;
            try {
                const res = await fetch(`{{ url('/admin/fortune/voice-diagnostic/test') }}/${name}`, {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': this.CSRF, 'Content-Type': 'application/json', 'Accept': 'application/json' },
                    body: JSON.stringify({ text: 'สวัสดีค่ะ ดิฉันแม่หมอจันทรา ทดสอบเสียงนะคะ' }),
                });
                this.results[name] = await res.json();
            } catch (e) {
                this.results[name] = { success: false, error: 'Fetch error: ' + e.message };
            } finally {
                this.busy[name] = false;
            }
        },

        async regenerate(readingId) {
            if (!confirm('สร้างเสียงใหม่สำหรับ reading #' + readingId + '?')) return;
            try {
                const res = await fetch(`{{ url('/admin/fortune/voice-diagnostic/regenerate') }}/${readingId}`, {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': this.CSRF, 'Accept': 'application/json' },
                });
                const data = await res.json();
                alert(data.message || data.error || 'OK');
            } catch (e) {
                alert('Error: ' + e.message);
            }
        },
    };
}
</script>
@endsection
