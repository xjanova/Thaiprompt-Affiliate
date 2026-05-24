@extends('layouts.admin-v3')

@section('title', 'เทคโอเวอร์: ' . ($reading->facebook_user_name ?? 'ไม่ระบุชื่อ'))

@section('content')
<div class="container mx-auto px-4 py-6"
     x-data="takeoverPanel({
        readingId: {{ $reading->id }},
        isActive: {{ $reading->isAdminTakenOver() ? 'true' : 'false' }},
        remainingSeconds: {{ $reading->takeoverRemainingSeconds() }},
        defaultMinutes: {{ $settings->getTakeoverDefaultMinutes() }},
        statusUrl: @js(route('admin.fortune.takeover.status', $reading)),
        takeoverUrl: @js(route('admin.fortune.takeover.takeover', $reading)),
        resumeUrl: @js(route('admin.fortune.takeover.resume', $reading)),
        extendUrl: @js(route('admin.fortune.takeover.extend', $reading)),
        sendMessageUrl: @js(route('admin.fortune.takeover.send-message', $reading)),
        csrfToken: @js(csrf_token()),
     })"
     x-init="init()">

    {{-- Header --}}
    <div class="mb-4 flex items-center justify-between flex-wrap gap-3">
        <div>
            <a href="{{ route('admin.fortune.takeover.index') }}" class="text-sm text-gray-500 dark:text-gray-400 hover:underline">
                ← กลับไปรายการ
            </a>
            <h1 class="text-xl md:text-2xl font-bold text-gray-900 dark:text-white mt-1">
                {{ $reading->facebook_user_name ?? 'ไม่ระบุชื่อ' }}
            </h1>
            <div class="flex items-center gap-2 mt-1 text-xs text-gray-500 dark:text-gray-400">
                @if($reading->platform === 'line')
                    <span class="inline-flex items-center px-2 py-0.5 rounded bg-green-100 text-green-800 dark:bg-green-900/50 dark:text-green-300">💚 LINE</span>
                @else
                    <span class="inline-flex items-center px-2 py-0.5 rounded bg-blue-100 text-blue-800 dark:bg-blue-900/50 dark:text-blue-300">🔵 Facebook</span>
                @endif
                <span class="font-mono">{{ $reading->platform_user_id ?: $reading->facebook_user_id }}</span>
                @if($reading->bill_reference)
                    <span>• Bill: {{ $reading->bill_reference }}</span>
                @endif
            </div>
        </div>
    </div>

    {{-- Flash messages (session-based — สำหรับ ban redirect) --}}
    @if (session('success'))
        <div class="mb-4 p-4 bg-emerald-50 dark:bg-emerald-900/30 border border-emerald-200 dark:border-emerald-800 rounded-lg text-emerald-800 dark:text-emerald-200 text-sm">
            {{ session('success') }}
        </div>
    @endif
    @if (session('error'))
        <div class="mb-4 p-4 bg-red-50 dark:bg-red-900/30 border border-red-200 dark:border-red-800 rounded-lg text-red-800 dark:text-red-200 text-sm">
            {{ session('error') }}
        </div>
    @endif
    @if ($errors->any())
        <div class="mb-4 p-4 bg-red-50 dark:bg-red-900/30 border border-red-200 dark:border-red-800 rounded-lg text-red-800 dark:text-red-200">
            <ul class="list-disc list-inside text-sm">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
        {{-- LEFT: Takeover Control Panel --}}
        <div class="lg:col-span-1 space-y-4">
            {{-- Status Card --}}
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow overflow-hidden">
                <div class="p-5" :class="isActive ? 'bg-gradient-to-br from-purple-500 to-purple-700 text-white' : 'bg-gray-50 dark:bg-gray-900/50'">
                    <template x-if="isActive">
                        <div>
                            <div class="text-sm opacity-80">สถานะ</div>
                            <div class="text-2xl font-bold mt-1">🎯 กำลังเทคโอเวอร์</div>
                            <div class="mt-3 flex items-baseline gap-2">
                                <span class="text-4xl font-bold tabular-nums" x-text="formatRemaining()"></span>
                                <span class="text-sm opacity-80">เหลือ</span>
                            </div>
                        </div>
                    </template>
                    <template x-if="! isActive">
                        <div>
                            <div class="text-sm text-gray-600 dark:text-gray-400">สถานะ</div>
                            <div class="text-2xl font-bold mt-1 text-gray-900 dark:text-white">🤖 AI กำลังทำงาน</div>
                            <div class="text-sm text-gray-500 dark:text-gray-400 mt-3">
                                กดปุ่มด้านล่างเพื่อเทคโอเวอร์ AI จะหยุดตอบให้แอดมินคุยเอง
                            </div>
                        </div>
                    </template>
                </div>

                <div class="p-5 space-y-3">
                    {{-- Takeover (ถ้ายังไม่ active) --}}
                    <template x-if="! isActive">
                        <div class="space-y-2">
                            <label class="block text-xs font-medium text-gray-700 dark:text-gray-300">ระยะเวลา (นาที)</label>
                            <input type="number" x-model.number="minutesInput" min="1" max="1440"
                                   class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm">
                            <button @click="doTakeover()"
                                    :disabled="busy"
                                    class="w-full px-4 py-2.5 bg-purple-600 hover:bg-purple-700 disabled:opacity-50 disabled:cursor-not-allowed text-white rounded-lg text-sm font-semibold transition">
                                🎯 เทคโอเวอร์ — หยุด AI
                            </button>
                        </div>
                    </template>

                    {{-- Extend + Resume (ถ้า active) --}}
                    <template x-if="isActive">
                        <div class="space-y-2">
                            <div class="flex gap-2">
                                <input type="number" x-model.number="extendInput" min="1" max="1440" placeholder="นาที"
                                       class="flex-1 rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm">
                                <button @click="doExtend()"
                                        :disabled="busy"
                                        class="px-4 py-2 bg-blue-600 hover:bg-blue-700 disabled:opacity-50 text-white rounded-lg text-sm font-medium transition whitespace-nowrap">
                                    ⏱ ต่อเวลา
                                </button>
                            </div>
                            <button @click="doResume()"
                                    :disabled="busy"
                                    class="w-full px-4 py-2.5 bg-emerald-600 hover:bg-emerald-700 disabled:opacity-50 text-white rounded-lg text-sm font-semibold transition">
                                ✨ ให้ AI กลับมาทำงาน
                            </button>
                        </div>
                    </template>

                    {{-- Flash Message --}}
                    <div x-show="flashMessage"
                         x-transition
                         class="p-3 rounded-lg text-sm whitespace-pre-line"
                         :class="flashType === 'success'
                            ? 'bg-emerald-50 dark:bg-emerald-900/30 text-emerald-800 dark:text-emerald-200'
                            : 'bg-red-50 dark:bg-red-900/30 text-red-800 dark:text-red-200'">
                        <span x-text="flashMessage"></span>
                    </div>
                </div>
            </div>

            {{-- Customer Info --}}
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-5">
                <h3 class="font-semibold text-gray-900 dark:text-white mb-3">ข้อมูลลูกค้า</h3>
                <dl class="space-y-2 text-sm">
                    <div class="flex justify-between">
                        <dt class="text-gray-500 dark:text-gray-400">สถานะ Conversation</dt>
                        <dd class="font-medium text-gray-900 dark:text-white">{{ $reading->conversation_status }}</dd>
                    </div>
                    @if($reading->birth_date)
                    <div class="flex justify-between">
                        <dt class="text-gray-500 dark:text-gray-400">วันเกิด</dt>
                        <dd class="font-medium text-gray-900 dark:text-white">{{ $reading->birth_date->format('d/m/Y') }}</dd>
                    </div>
                    @endif
                    @if($reading->is_paid)
                    <div class="flex justify-between">
                        <dt class="text-gray-500 dark:text-gray-400">ชำระแล้ว</dt>
                        <dd class="font-medium text-emerald-600 dark:text-emerald-400">{{ number_format($reading->amount_paid, 2) }} ฿</dd>
                    </div>
                    @endif
                    <div class="flex justify-between">
                        <dt class="text-gray-500 dark:text-gray-400">สร้างเมื่อ</dt>
                        <dd class="font-medium text-gray-900 dark:text-white">{{ $reading->created_at->format('d/m/Y H:i') }}</dd>
                    </div>
                </dl>

                @if($reading->takeoverAdmin)
                <div class="mt-4 pt-3 border-t border-gray-200 dark:border-gray-700">
                    <p class="text-xs text-gray-500 dark:text-gray-400">เทคโอเวอร์ล่าสุดโดย</p>
                    <p class="text-sm font-medium text-gray-900 dark:text-white">{{ $reading->takeoverAdmin->name }}</p>
                    @if($reading->admin_takeover_reason)
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                        เหตุผล: {{ match($reading->admin_takeover_reason) {
                            'manual' => 'กดเทคเอง',
                            'auto_reply' => 'แอดมินพิมพ์ตอบ',
                            'customer_request' => 'ลูกค้าขอ',
                            default => $reading->admin_takeover_reason,
                        } }}
                    </p>
                    @endif
                </div>
                @endif
            </div>

            {{-- 🚫 (2026-05-23) Ban Danger Zone — แบน user คนนี้ (ไม่ต้องไปหน้า /admin/fortune/bans) --}}
            <div x-data="{ banDuration: 'permanent' }"
                 class="bg-white dark:bg-gray-800 rounded-xl shadow border-2 border-red-200 dark:border-red-900 overflow-hidden">
                <div class="bg-red-50 dark:bg-red-900/30 px-5 py-3 border-b border-red-200 dark:border-red-900">
                    <h3 class="font-semibold text-red-900 dark:text-red-200 flex items-center gap-2">
                        🚫 ระบบคุก — แบน user คนนี้
                    </h3>
                    <p class="text-xs text-red-700 dark:text-red-300 mt-0.5">
                        บอทจะไม่ตอบ user คนนี้อีก (admin ยังคุยผ่าน Page Inbox / LINE OA ได้)
                    </p>
                </div>
                <form method="POST" action="{{ route('admin.fortune.takeover.ban', $reading) }}"
                      onsubmit="return confirm('ยืนยันการแบน user คนนี้?');">
                    @csrf
                    <input type="hidden" name="from" value="show">

                    <div class="p-5 space-y-3">
                        <div>
                            <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-2">
                                ระยะเวลาแบน
                            </label>
                            <div class="grid grid-cols-1 gap-1.5">
                                <template x-for="opt in [
                                    {value: '10m', label: '⏱ 10 นาที — เตือนสั้นๆ', danger: false},
                                    {value: '1h', label: '⏰ 1 ชั่วโมง', danger: false},
                                    {value: '24h', label: '📅 24 ชั่วโมง', danger: false},
                                    {value: '7d', label: '🗓️ 7 วัน', danger: false},
                                    {value: 'permanent', label: '🔒 ถาวร', danger: true}
                                ]" :key="opt.value">
                                    <label class="flex items-center gap-2.5 px-3 py-2 rounded-lg border-2 cursor-pointer transition text-sm"
                                           :class="banDuration === opt.value
                                                ? 'border-red-500 bg-red-50 dark:bg-red-900/30 dark:border-red-500'
                                                : 'border-gray-200 dark:border-gray-700 hover:border-red-300 dark:hover:border-red-700'">
                                        <input type="radio" name="duration" :value="opt.value" x-model="banDuration"
                                               class="text-red-600 focus:ring-red-500">
                                        <span :class="opt.danger ? 'font-semibold text-red-700 dark:text-red-300' : 'text-gray-900 dark:text-gray-100'"
                                              x-text="opt.label"></span>
                                    </label>
                                </template>
                            </div>
                        </div>

                        <button type="submit"
                                class="w-full px-4 py-2.5 bg-red-600 hover:bg-red-700 text-white rounded-lg text-sm font-semibold transition">
                            🚫 ยืนยันแบน user คนนี้
                        </button>
                    </div>
                </form>
            </div>
        </div>

        {{-- RIGHT: Conversation + Reply Box --}}
        <div class="lg:col-span-2 space-y-4">
            {{-- Questions --}}
            @if(! empty($reading->questions))
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-5">
                <h3 class="font-semibold text-gray-900 dark:text-white mb-2">💭 คำถามของลูกค้า</h3>
                <ul class="space-y-1 text-sm text-gray-700 dark:text-gray-300 list-disc pl-5">
                    @foreach($reading->questions as $question)
                        <li>{{ $question }}</li>
                    @endforeach
                </ul>
            </div>
            @endif

            {{-- AI Responses --}}
            @if($reading->basic_response)
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-5">
                <h3 class="font-semibold text-gray-900 dark:text-white mb-2 flex items-center gap-2">
                    🔮 คำทำนายพื้นฐาน
                </h3>
                <div class="prose prose-sm dark:prose-invert max-w-none text-gray-700 dark:text-gray-300 whitespace-pre-wrap">{{ $reading->basic_response }}</div>
            </div>
            @endif

            @if($reading->deep_response)
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-5">
                <h3 class="font-semibold text-gray-900 dark:text-white mb-2 flex items-center gap-2">
                    ✨ คำทำนายเชิงลึก
                </h3>
                <div class="prose prose-sm dark:prose-invert max-w-none text-gray-700 dark:text-gray-300 whitespace-pre-wrap">{{ $reading->deep_response }}</div>
            </div>
            @endif

            {{-- Send Message Box --}}
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-5">
                <h3 class="font-semibold text-gray-900 dark:text-white mb-3 flex items-center gap-2">
                    💬 ส่งข้อความถึงลูกค้า
                </h3>
                <p class="text-xs text-gray-500 dark:text-gray-400 mb-3">
                    ข้อความจะส่งผ่าน {{ $reading->platform === 'line' ? 'LINE' : 'Facebook Messenger' }} —
                    ถ้ายังไม่ได้เทคโอเวอร์ ระบบจะเทคโอเวอร์ให้อัตโนมัติก่อนส่ง
                </p>
                <textarea x-model="messageText"
                          rows="4"
                          placeholder="พิมพ์ข้อความที่อยากส่ง..."
                          class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm"></textarea>
                <div class="flex justify-between items-center mt-3">
                    <p class="text-xs text-gray-400" x-text="`${messageText.length} / 2000`"></p>
                    <button @click="doSendMessage()"
                            :disabled="busy || messageText.trim().length === 0 || messageText.length > 2000"
                            class="px-5 py-2 bg-blue-600 hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed text-white rounded-lg text-sm font-semibold transition">
                        ส่งข้อความ →
                    </button>
                </div>
            </div>

            {{-- Takeover History --}}
            @if($reading->takeoverLogs->isNotEmpty())
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-5">
                <h3 class="font-semibold text-gray-900 dark:text-white mb-3">📜 ประวัติการเทคโอเวอร์</h3>
                <div class="space-y-2 max-h-96 overflow-y-auto">
                    @foreach($reading->takeoverLogs as $log)
                    <div class="p-3 rounded-lg bg-gray-50 dark:bg-gray-900/50 text-sm">
                        <div class="flex items-center justify-between">
                            <span class="font-medium text-gray-900 dark:text-white">{{ $log->getActionLabel() }}</span>
                            <span class="text-xs text-gray-500 dark:text-gray-400">{{ $log->created_at->diffForHumans() }}</span>
                        </div>
                        <div class="text-xs text-gray-600 dark:text-gray-400 mt-1 flex items-center gap-2 flex-wrap">
                            @if($log->user)
                                <span>👤 {{ $log->user->name }}</span>
                            @else
                                <span>🤖 ระบบ</span>
                            @endif
                            @if($log->reason)
                                <span>• {{ $log->getReasonLabel() }}</span>
                            @endif
                            @if($log->duration_minutes)
                                <span>• {{ $log->duration_minutes }} นาที</span>
                            @endif
                        </div>
                        @if($log->message)
                            <div class="mt-1 text-xs text-gray-700 dark:text-gray-300 italic bg-white dark:bg-gray-800 p-2 rounded">
                                "{{ Str::limit($log->message, 200) }}"
                            </div>
                        @endif
                    </div>
                    @endforeach
                </div>
            </div>
            @endif
        </div>
    </div>
</div>

{{-- Alpine Component --}}
<script>
function takeoverPanel(config) {
    return {
        // State
        isActive: config.isActive,
        remainingSeconds: config.remainingSeconds,
        minutesInput: config.defaultMinutes,
        extendInput: 15,
        messageText: '',
        busy: false,
        flashMessage: '',
        flashType: 'success',
        pollTimer: null,
        countdownTimer: null,

        // URLs
        statusUrl: config.statusUrl,
        takeoverUrl: config.takeoverUrl,
        resumeUrl: config.resumeUrl,
        extendUrl: config.extendUrl,
        sendMessageUrl: config.sendMessageUrl,
        csrfToken: config.csrfToken,

        init() {
            // นับถอยหลังทุก 1 วิ
            this.countdownTimer = setInterval(() => {
                if (this.isActive && this.remainingSeconds > 0) {
                    this.remainingSeconds--;
                    if (this.remainingSeconds <= 0) {
                        this.refreshStatus();
                    }
                }
            }, 1000);

            // Poll server ทุก 15 วิ (กรณีแอดมินคนอื่นเทคเอง/AI reply)
            this.pollTimer = setInterval(() => this.refreshStatus(), 15000);

            // Alpine 3 cleanup — เรียกเมื่อ component teardown (SPA/Livewire nav, page unload)
            const self = this;
            this.$cleanup = () => {
                if (self.countdownTimer) clearInterval(self.countdownTimer);
                if (self.pollTimer) clearInterval(self.pollTimer);
            };
            // Fallback สำหรับกรณี full page reload
            window.addEventListener('beforeunload', this.$cleanup, { once: true });
        },

        formatRemaining() {
            const s = Math.max(0, this.remainingSeconds);
            const m = Math.floor(s / 60);
            const sec = s % 60;
            return `${m}:${String(sec).padStart(2, '0')}`;
        },

        async refreshStatus() {
            try {
                const res = await fetch(this.statusUrl);
                const data = await res.json();
                this.isActive = data.is_active;
                this.remainingSeconds = data.remaining_seconds;
            } catch (e) {
                console.error('status fetch failed', e);
            }
        },

        flash(message, type = 'success') {
            this.flashMessage = message;
            this.flashType = type;
            setTimeout(() => this.flashMessage = '', 4000);
        },

        async postJson(url, body = {}) {
            this.busy = true;
            try {
                const res = await fetch(url, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': this.csrfToken,
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify(body),
                });
                const data = await res.json();
                if (! res.ok) {
                    throw new Error(data.message || 'เกิดข้อผิดพลาด');
                }
                return data;
            } finally {
                this.busy = false;
            }
        },

        async doTakeover() {
            try {
                const data = await this.postJson(this.takeoverUrl, { minutes: this.minutesInput });
                this.isActive = true;
                this.remainingSeconds = data.data.remaining_seconds;
                this.flash(data.message);
            } catch (e) {
                this.flash(e.message, 'error');
            }
        },

        async doResume() {
            if (! confirm('ให้ AI กลับมาทำงานตอนนี้?')) return;
            try {
                const data = await this.postJson(this.resumeUrl);
                this.isActive = false;
                this.remainingSeconds = 0;
                this.flash(data.message);
            } catch (e) {
                this.flash(e.message, 'error');
            }
        },

        async doExtend() {
            try {
                const data = await this.postJson(this.extendUrl, { minutes: this.extendInput });
                this.remainingSeconds = data.data.remaining_seconds;
                this.flash(data.message);
            } catch (e) {
                this.flash(e.message, 'error');
            }
        },

        async doSendMessage() {
            const msg = this.messageText.trim();
            if (! msg) return;
            try {
                const data = await this.postJson(this.sendMessageUrl, { message: msg });
                this.messageText = '';
                // อัพเดต state (ส่งข้อความจะเทคโอเวอร์อัตโนมัติถ้ายังไม่ active)
                await this.refreshStatus();
                this.flash(data.message);
            } catch (e) {
                this.flash(e.message, 'error');
            }
        },

        destroy() {
            if (this.pollTimer) clearInterval(this.pollTimer);
            if (this.countdownTimer) clearInterval(this.countdownTimer);
        },
    };
}
</script>
@endsection
