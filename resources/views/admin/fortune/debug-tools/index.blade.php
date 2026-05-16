{{-- 🐛 Fortune Debug Tools — admin self-service debugging --}}
@extends('layouts.admin-v3')

@section('title', $pageTitle)

@section('content')
<div class="container mx-auto px-4 py-8" x-data="debugTools()">

    <div class="mb-6 flex items-center justify-between flex-wrap gap-3">
        <div>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">
                🐛 Fortune Debug Tools
            </h1>
            <p class="text-gray-500 dark:text-gray-400 text-sm mt-1">
                Tail laravel.log + ทดสอบ AI sync — ไม่ต้อง SSH server
            </p>
        </div>
        <div class="text-xs text-gray-500 dark:text-gray-400">
            <code>{{ $logPath }}</code>
        </div>
    </div>

    {{-- ╔═══════════════════════════════╗
         ║  SECTION 1 — Tail Laravel Log  ║
         ╚═══════════════════════════════╝ --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6 mb-6">
        <div class="flex items-center justify-between mb-4 flex-wrap gap-2">
            <h2 class="text-xl font-bold text-gray-900 dark:text-white">
                📜 Tail laravel.log
            </h2>
            <div class="flex items-center gap-2 flex-wrap">
                <label class="text-xs text-gray-600 dark:text-gray-400 flex items-center gap-1">
                    <input type="checkbox" x-model="autoRefresh" class="rounded">
                    auto-refresh 3s
                </label>
                <span class="text-xs text-gray-500 dark:text-gray-400" x-text="log.fetched_at ? '⏱ ' + log.fetched_at.substring(11, 19) : ''"></span>
                <button @click="fetchLog()" type="button"
                        :disabled="log.loading"
                        class="px-3 py-1.5 bg-indigo-600 hover:bg-indigo-700 disabled:opacity-50 text-white text-xs rounded-lg">
                    <span x-show="!log.loading">🔄 Refresh</span>
                    <span x-show="log.loading">⏳ Loading...</span>
                </button>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-4 gap-3 mb-3">
            <input type="text" x-model="log.filter" @keydown.enter="fetchLog()"
                   placeholder="filter (regex หรือ keyword) — เช่น celtic|fortune ask"
                   class="md:col-span-3 px-3 py-2 bg-gray-50 dark:bg-gray-900 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-gray-100 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none">
            <select x-model.number="log.lines" @change="fetchLog()"
                    class="px-3 py-2 bg-gray-50 dark:bg-gray-900 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-gray-100 text-sm">
                <option value="50">50 บรรทัด</option>
                <option value="100">100 บรรทัด</option>
                <option value="200">200 บรรทัด</option>
                <option value="500">500 บรรทัด</option>
            </select>
        </div>

        <div class="flex items-center gap-2 mb-2 flex-wrap text-xs">
            <span class="text-gray-500 dark:text-gray-400">Quick filters:</span>
            <template x-for="preset in ['fortune:celtic-admin-ask', 'celtic admin', 'askQuestionAsAdmin', 'FortuneAIService', 'AiApiKeyPool', 'ERROR']" :key="preset">
                <button @click="log.filter = preset; fetchLog()" type="button"
                        class="px-2 py-1 bg-gray-100 dark:bg-gray-700 hover:bg-indigo-100 dark:hover:bg-indigo-900/30 text-gray-700 dark:text-gray-300 rounded">
                    <span x-text="preset"></span>
                </button>
            </template>
            <button @click="log.filter = ''; fetchLog()" type="button"
                    class="px-2 py-1 bg-red-100 dark:bg-red-900/30 hover:bg-red-200 text-red-700 dark:text-red-300 rounded">
                ✕ clear
            </button>
        </div>

        <div x-show="log.count !== null" class="text-xs text-gray-500 dark:text-gray-400 mb-2">
            แสดง <strong x-text="log.count"></strong> บรรทัด • file <strong x-text="formatBytes(log.size_bytes)"></strong>
        </div>

        <pre class="bg-gray-900 text-green-300 rounded-lg p-4 overflow-x-auto text-xs leading-5 max-h-96 overflow-y-auto"
             x-show="!log.error"><template x-for="(line, idx) in log.lines" :key="idx"><span :class="{
                'text-red-400': line.includes('ERROR') || line.includes('exception'),
                'text-yellow-300': line.includes('WARNING') || line.includes('warn'),
                'text-cyan-300': line.includes('INFO'),
                'text-green-300': !line.includes('ERROR') && !line.includes('WARNING') && !line.includes('INFO')
             }" x-text="line + '\n'"></span></template></pre>

        <div x-show="log.error" class="bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-300 p-3 rounded-lg text-sm">
            ⚠️ <span x-text="log.error"></span>
        </div>

        <div x-show="!log.error && log.count === 0" class="bg-yellow-50 dark:bg-yellow-900/30 text-yellow-700 dark:text-yellow-300 p-3 rounded-lg text-sm">
            ไม่เจอ log ที่ตรง filter — ลองเปลี่ยน keyword หรือเพิ่มจำนวนบรรทัด
        </div>
    </div>

    {{-- ╔═════════════════════════╗
         ║  SECTION 2 — Test AI     ║
         ╚═════════════════════════╝ --}}
    <div class="bg-gradient-to-br from-purple-50 to-pink-50 dark:from-purple-900/20 dark:to-pink-900/20 rounded-xl shadow-lg p-6 mb-6 border border-purple-200 dark:border-purple-800">
        <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-1">
            🧪 ทดสอบ AI ทำนาย (sync)
        </h2>
        <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
            เรียก AI ตรงๆ ไม่ผ่าน background — เห็น response/error ในหน้านี้ทันที + log แสดงด้านบน
        </p>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
            <div>
                <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">
                    Reading (เลือก reading ที่จะทดสอบ)
                </label>
                <select x-model.number="test.reading_id"
                        class="w-full px-3 py-2 bg-white dark:bg-gray-900 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-gray-100 text-sm">
                    <option value="">— เลือก —</option>
                    @foreach($recentReadings as $r)
                        <option value="{{ $r->id }}">
                            #{{ $r->id }} • {{ $r->reading_type }} • {{ $r->facebook_user_name ?? '-' }} •
                            {{ $r->is_paid ? '✓paid' : 'unpaid' }} • {{ $r->conversation_status }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">
                    Options
                </label>
                <label class="flex items-center gap-2 px-3 py-2 bg-white dark:bg-gray-900 border border-gray-300 dark:border-gray-600 rounded-lg cursor-pointer">
                    <input type="checkbox" x-model="test.push_to_customer" class="rounded">
                    <span class="text-sm">
                        🚨 ส่งคำตอบให้ลูกค้าจริง (มี prefix <code>[DEBUG TEST]</code>)
                    </span>
                </label>
            </div>
        </div>

        <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">
            คำถามทดสอบ
        </label>
        <textarea x-model="test.question"
                  rows="2"
                  maxlength="500"
                  placeholder="ทดสอบคำถาม ✨"
                  class="w-full px-3 py-2 bg-white dark:bg-gray-900 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-gray-100 text-sm focus:ring-2 focus:ring-purple-500 focus:outline-none mb-3"></textarea>

        <button @click="runTestAi()" type="button"
                :disabled="!test.reading_id || test.question.trim().length < 3 || test.running"
                class="px-6 py-2.5 bg-gradient-to-r from-purple-600 to-pink-600 hover:from-purple-700 hover:to-pink-700 disabled:opacity-50 disabled:cursor-not-allowed text-white rounded-lg text-sm font-semibold shadow-md">
            <span x-show="!test.running">🧪 ทดสอบ AI</span>
            <span x-show="test.running" x-cloak>⏳ กำลังเรียก AI... (30-60s)</span>
        </button>

        {{-- Results --}}
        <div x-show="test.result" x-cloak class="mt-5">
            <h3 class="font-bold text-gray-900 dark:text-white mb-2">
                📊 ผลทดสอบ
                <span x-show="test.result?.success" class="text-green-600">✅</span>
                <span x-show="test.result?.success === false" class="text-red-600">❌</span>
            </h3>

            <div class="space-y-2">
                <template x-for="(step, idx) in test.result?.steps || []" :key="idx">
                    <div :class="step.success ? 'bg-green-50 dark:bg-green-900/20 border-green-300' : 'bg-red-50 dark:bg-red-900/20 border-red-300'"
                         class="border-l-4 p-3 rounded-r-lg">
                        <div class="flex items-center justify-between flex-wrap gap-2">
                            <strong class="text-sm" x-text="step.name"></strong>
                            <div class="flex items-center gap-2 text-xs">
                                <span x-show="step.elapsed_ms !== undefined" class="text-gray-500 dark:text-gray-400">
                                    <span x-text="step.elapsed_ms"></span>ms
                                </span>
                                <span x-show="step.skipped" class="px-2 py-0.5 bg-gray-200 dark:bg-gray-700 rounded text-gray-600 dark:text-gray-300">skipped</span>
                            </div>
                        </div>
                        <pre x-show="step" x-text="JSON.stringify(stepDetails(step), null, 2)"
                             class="mt-2 text-xs bg-gray-900 text-green-300 p-2 rounded overflow-x-auto"></pre>
                    </div>
                </template>
            </div>

            <div x-show="test.result?.ai_response_full" class="mt-4">
                <h4 class="font-bold text-gray-900 dark:text-white text-sm mb-2">💬 AI Response (full):</h4>
                <pre class="bg-gray-900 text-cyan-300 p-3 rounded-lg text-xs whitespace-pre-wrap" x-text="test.result?.ai_response_full"></pre>
            </div>

            <div x-show="test.result?.error" class="mt-4 bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-300 p-3 rounded-lg text-sm">
                <strong>❌ Exception:</strong> <span x-text="test.result?.error"></span>
                <pre x-show="test.result?.trace" class="mt-2 text-xs bg-red-50 dark:bg-red-950 p-2 rounded overflow-x-auto" x-text="test.result?.trace"></pre>
            </div>
        </div>
    </div>

</div>

<script>
function debugTools() {
    return {
        autoRefresh: false,
        refreshTimer: null,
        log: {
            filter: '',
            lines: 100,
            loading: false,
            lines: [],
            count: null,
            size_bytes: 0,
            fetched_at: null,
            error: null,
        },
        test: {
            reading_id: '',
            question: 'ทดสอบ — ความรักของฉันเดือนนี้จะเป็นยังไง',
            push_to_customer: false,
            running: false,
            result: null,
        },

        init() {
            this.fetchLog();
            this.$watch('autoRefresh', (v) => {
                if (v) this.startAutoRefresh();
                else this.stopAutoRefresh();
            });
        },

        startAutoRefresh() {
            this.refreshTimer = setInterval(() => this.fetchLog(), 3000);
        },
        stopAutoRefresh() {
            if (this.refreshTimer) {
                clearInterval(this.refreshTimer);
                this.refreshTimer = null;
            }
        },

        async fetchLog() {
            this.log.loading = true;
            this.log.error = null;
            try {
                const params = new URLSearchParams({
                    lines: this.log.lines,
                    filter: this.log.filter,
                });
                const res = await fetch('{{ route("admin.fortune.debug-tools.logs") }}?' + params, {
                    headers: { 'Accept': 'application/json' },
                });
                const data = await res.json();
                if (data.success) {
                    this.log.lines = data.lines;
                    this.log.count = data.count;
                    this.log.size_bytes = data.size_bytes;
                    this.log.fetched_at = data.fetched_at;
                } else {
                    this.log.error = data.message;
                }
            } catch (e) {
                this.log.error = e.message;
            } finally {
                this.log.loading = false;
            }
        },

        async runTestAi() {
            if (!this.test.reading_id || this.test.question.trim().length < 3) return;

            if (this.test.push_to_customer && !confirm('⚠️ ยืนยันส่งคำตอบ AI ให้ลูกค้าจริง?\nลูกค้าจะเห็น [DEBUG TEST] นำหน้า')) {
                return;
            }

            this.test.running = true;
            this.test.result = null;

            try {
                const res = await fetch('{{ route("admin.fortune.debug-tools.test-ai") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({
                        reading_id: this.test.reading_id,
                        question: this.test.question,
                        push_to_customer: this.test.push_to_customer,
                    }),
                });
                this.test.result = await res.json();
                // Refresh log to show new entries
                setTimeout(() => this.fetchLog(), 500);
            } catch (e) {
                this.test.result = { success: false, error: e.message };
            } finally {
                this.test.running = false;
            }
        },

        stepDetails(step) {
            const { name, success, elapsed_ms, skipped, ...rest } = step;
            return rest;
        },

        formatBytes(bytes) {
            if (!bytes) return '0 B';
            const units = ['B', 'KB', 'MB', 'GB'];
            const i = Math.floor(Math.log(bytes) / Math.log(1024));
            return (bytes / Math.pow(1024, i)).toFixed(1) + ' ' + units[i];
        },
    };
}
</script>
@endsection
