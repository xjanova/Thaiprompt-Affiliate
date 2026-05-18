@extends('layouts.admin')

@section('title', '🩺 TTS Voice Diagnostic')

@section('content')
<div class="container mx-auto px-4 py-6" x-data="voiceDiagnostic()">
    {{-- Header --}}
    <div class="flex items-center justify-between mb-6 flex-wrap gap-2">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">🩺 TTS Voice Diagnostic</h1>
            <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">ตรวจระบบสังเคราะห์เสียง — ดูว่า provider ไหนใช้ได้/ไม่ได้</p>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('admin.fortune.settings.index') }}#tts"
               class="px-4 py-2 text-sm bg-gray-200 dark:bg-gray-700 hover:bg-gray-300 dark:hover:bg-gray-600 text-gray-900 dark:text-white rounded-lg transition">
                ← กลับไปตั้งค่า
            </a>
            <button type="button" @click="reloadPage()"
                    class="px-4 py-2 text-sm bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition">
                🔄 รีโหลด
            </button>
        </div>
    </div>

    {{-- Master toggle status --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-6 mb-6">
        <h2 class="text-lg font-bold text-gray-900 dark:text-white mb-4">⚙️ สถานะระบบ</h2>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
            <div class="p-4 rounded-lg {{ $settings->voice_summary_enabled ? 'bg-emerald-50 dark:bg-emerald-900/20 border border-emerald-300 dark:border-emerald-700' : 'bg-red-50 dark:bg-red-900/20 border border-red-300 dark:border-red-700' }}">
                <div class="text-xs text-gray-600 dark:text-gray-400 mb-1">Voice Summary Toggle</div>
                <div class="text-lg font-bold {{ $settings->voice_summary_enabled ? 'text-emerald-700 dark:text-emerald-300' : 'text-red-700 dark:text-red-300' }}">
                    {{ $settings->voice_summary_enabled ? '✅ เปิดอยู่' : '❌ ปิดอยู่' }}
                </div>
                @unless($settings->voice_summary_enabled)
                    <div class="text-xs mt-1 text-red-600 dark:text-red-400">
                        ต้องเปิด toggle ที่ <a href="{{ route('admin.fortune.settings.index') }}#tts" class="underline">/admin/fortune/settings</a> tab TTS
                    </div>
                @endunless
            </div>

            <div class="p-4 rounded-lg bg-blue-50 dark:bg-blue-900/20 border border-blue-300 dark:border-blue-700">
                <div class="text-xs text-gray-600 dark:text-gray-400 mb-1">Tier Scope</div>
                <div class="text-lg font-bold text-blue-700 dark:text-blue-300">
                    @switch($settings->voice_summary_tier_scope ?? 'celtic_99_only')
                        @case('celtic_99_only') 🌙 Celtic 99฿ เท่านั้น @break
                        @case('paid_all') 💎 ทุก paid reading @break
                        @case('all') 🌐 ทุก reading @break
                    @endswitch
                </div>
            </div>

            <div class="p-4 rounded-lg {{ $storageStatus['available'] ? 'bg-emerald-50 dark:bg-emerald-900/20 border border-emerald-300 dark:border-emerald-700' : 'bg-amber-50 dark:bg-amber-900/20 border border-amber-300 dark:border-amber-700' }}">
                <div class="text-xs text-gray-600 dark:text-gray-400 mb-1">Storage Driver</div>
                <div class="text-lg font-bold {{ $storageStatus['available'] ? 'text-emerald-700 dark:text-emerald-300' : 'text-amber-700 dark:text-amber-300' }}">
                    {{ $storage->driverName($storage->driver()) }}
                </div>
                <div class="text-xs mt-1 {{ $storageStatus['available'] ? 'text-emerald-600 dark:text-emerald-400' : 'text-amber-600 dark:text-amber-400' }}">
                    {{ $storageStatus['hint'] }}
                </div>
            </div>
        </div>
    </div>

    {{-- Stats --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-6 mb-6">
        <h2 class="text-lg font-bold text-gray-900 dark:text-white mb-4">📊 สถิติ</h2>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-3 text-sm">
            <div class="p-3 rounded bg-gray-50 dark:bg-gray-700">
                <div class="text-xs text-gray-600 dark:text-gray-400">Celtic Readings ทั้งหมด</div>
                <div class="text-xl font-bold text-gray-900 dark:text-white">{{ number_format($stats['total_celtic']) }}</div>
            </div>
            <div class="p-3 rounded bg-emerald-50 dark:bg-emerald-900/20">
                <div class="text-xs text-emerald-600 dark:text-emerald-400">มี Voice แล้ว</div>
                <div class="text-xl font-bold text-emerald-700 dark:text-emerald-300">{{ number_format($stats['voice_generated']) }}</div>
            </div>
            <div class="p-3 rounded bg-red-50 dark:bg-red-900/20">
                <div class="text-xs text-red-600 dark:text-red-400">Voice Fail</div>
                <div class="text-xl font-bold text-red-700 dark:text-red-300">{{ number_format($stats['voice_failed_state']) }}</div>
            </div>
            <div class="p-3 rounded bg-blue-50 dark:bg-blue-900/20">
                <div class="text-xs text-blue-600 dark:text-blue-400">Provider Primary</div>
                <div class="text-sm font-bold text-blue-700 dark:text-blue-300">{{ $settings->voice_summary_primary_provider ?? 'minimax' }}</div>
            </div>
        </div>
    </div>

    {{-- Provider tests --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-6 mb-6">
        <h2 class="text-lg font-bold text-gray-900 dark:text-white mb-4">🎙️ Provider Status & Test</h2>
        <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
            กด <strong>"ทดสอบ"</strong> แต่ละ provider → สังเคราะห์เสียง 30 chars → ถ้าฟังได้ = provider พร้อมใช้
        </p>

        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-gray-200 dark:border-gray-700">
                        <th class="text-left py-2 px-2">Provider</th>
                        <th class="text-center py-2 px-2">บทบาท</th>
                        <th class="text-center py-2 px-2">สถานะ</th>
                        <th class="text-left py-2 px-2">คำแนะนำ</th>
                        <th class="text-center py-2 px-2">ทดสอบ</th>
                        <th class="text-left py-2 px-2">ผล</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($providers as $p)
                        <tr class="border-b border-gray-100 dark:border-gray-700 align-top">
                            <td class="py-3 px-2 font-medium text-gray-900 dark:text-white">{{ $p['label'] }}</td>
                            <td class="py-3 px-2 text-center text-xs">
                                @if($p['is_primary'])
                                    <span class="px-2 py-0.5 bg-purple-100 dark:bg-purple-900/40 text-purple-800 dark:text-purple-200 rounded">⭐ Primary</span>
                                @elseif($p['in_fallback'])
                                    <span class="px-2 py-0.5 bg-blue-100 dark:bg-blue-900/40 text-blue-800 dark:text-blue-200 rounded">🔁 Fallback</span>
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
                            <td class="py-3 px-2 text-xs text-gray-600 dark:text-gray-400 max-w-md">{{ $p['hint'] }}</td>
                            <td class="py-3 px-2 text-center">
                                <button type="button" @click="testProvider('{{ $p['name'] }}')"
                                        :disabled="busy['{{ $p['name'] }}']"
                                        class="px-3 py-1 text-xs bg-amber-500 hover:bg-amber-600 disabled:opacity-50 text-white rounded">
                                    <span x-show="!busy['{{ $p['name'] }}']">🎙️ ทดสอบ</span>
                                    <span x-show="busy['{{ $p['name'] }}']" x-cloak>⏳ กำลังทดสอบ...</span>
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
    </div>

    {{-- Recent fails --}}
    @if($recentFails->count() > 0)
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-6 mb-6">
            <h2 class="text-lg font-bold text-gray-900 dark:text-white mb-4">⚠️ Reading ที่ voice ล้มเหลว 20 อันล่าสุด</h2>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-gray-200 dark:border-gray-700">
                            <th class="text-left py-2 px-2">Reading ID</th>
                            <th class="text-left py-2 px-2">Platform</th>
                            <th class="text-left py-2 px-2">Status</th>
                            <th class="text-left py-2 px-2 max-w-md">Error</th>
                            <th class="text-left py-2 px-2">เมื่อ</th>
                            <th class="text-center py-2 px-2">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($recentFails as $r)
                            <tr class="border-b border-gray-100 dark:border-gray-700">
                                <td class="py-2 px-2 font-mono text-xs">#{{ $r['id'] }}</td>
                                <td class="py-2 px-2 text-xs">{{ $r['platform'] }}</td>
                                <td class="py-2 px-2 text-xs">
                                    <span class="px-2 py-0.5 bg-red-100 dark:bg-red-900/40 text-red-800 dark:text-red-200 rounded">{{ $r['status'] }}</span>
                                </td>
                                <td class="py-2 px-2 text-xs text-red-600 dark:text-red-400 max-w-md truncate" title="{{ $r['error'] }}">{{ \Str::limit($r['error'], 80) }}</td>
                                <td class="py-2 px-2 text-xs text-gray-500">{{ $r['created_at'] }}</td>
                                <td class="py-2 px-2 text-center">
                                    <button type="button" @click="regenerate({{ $r['id'] }})"
                                            class="px-2 py-1 text-xs bg-blue-600 hover:bg-blue-700 text-white rounded">
                                        🔄 ลองใหม่
                                    </button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    {{-- Quick fixes --}}
    <div class="bg-amber-50 dark:bg-amber-900/20 border border-amber-300 dark:border-amber-700 rounded-xl p-6">
        <h2 class="text-lg font-bold text-amber-900 dark:text-amber-200 mb-3">💡 แก้ปัญหาที่พบบ่อย</h2>
        <ul class="text-sm text-amber-800 dark:text-amber-300 space-y-2 list-disc pl-5">
            <li><strong>MiniMax ใช้ไม่ได้</strong>: ตั้ง <code class="bg-amber-100 dark:bg-amber-900/40 px-1 rounded">MiniMax API key (JWT)</code> ที่ Voice section หรือเพิ่ม key ใน <a href="/admin/ai-api-keys" class="underline">API Pool</a> (provider=minimax, purpose=tts)</li>
            <li><strong>OpenAI ใช้ไม่ได้</strong>: เพิ่ม OpenAI key ใน <a href="/admin/ai-api-keys" class="underline">API Pool</a> (provider=openai, purpose=tts หรือ any)</li>
            <li><strong>Google TTS ใช้ไม่ได้</strong>: ตั้ง <code class="bg-amber-100 dark:bg-amber-900/40 px-1 rounded">GOOGLE_TTS_API_KEY</code> ใน <code>.env</code> หรือใช้ service account JSON เดียวกับ Translate/Vision</li>
            <li><strong>storage:link หาย (local driver)</strong>: รัน <code class="bg-amber-100 dark:bg-amber-900/40 px-1 rounded">php artisan storage:link</code> หรือกดปุ่ม "🔗 สร้าง symlink" ที่ <a href="{{ route('admin.fortune.settings.index') }}#tts" class="underline">Storage section</a></li>
            <li><strong>เซิร์ฟเวอร์เต็มเร็ว</strong>: ย้ายไป <strong>Cloudflare R2</strong> ที่ <a href="{{ route('admin.fortune.settings.index') }}#tts" class="underline">Storage section</a> — ฟรี 10GB + free egress</li>
        </ul>
    </div>
</div>

<script>
function voiceDiagnostic() {
    return {
        busy: {},
        results: {},

        async testProvider(name) {
            this.busy[name] = true;
            this.results[name] = null;

            try {
                const res = await fetch(`{{ url('/admin/fortune/voice-diagnostic/test') }}/${name}`, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                    },
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
            if (!confirm('Dispatch voice job สำหรับ reading #' + readingId + ' ใหม่?')) return;

            try {
                const res = await fetch(`{{ url('/admin/fortune/voice-diagnostic/regenerate') }}/${readingId}`, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json',
                    },
                });
                const data = await res.json();
                alert(data.message || data.error || 'OK');
            } catch (e) {
                alert('Error: ' + e.message);
            }
        },

        reloadPage() {
            window.location.reload();
        },
    };
}
</script>
@endsection
