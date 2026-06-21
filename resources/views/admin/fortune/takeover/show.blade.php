@extends('layouts.admin-v4')

@section('title', 'เทคโอเวอร์: ' . ($reading->facebook_user_name ?? 'ไม่ระบุชื่อ'))

@section('content')
{{--
    หน้าเทคโอเวอร์ห้องแชท (ธีม V4 นวลทองคำ)
    🔴 หน้านี้เป็น live-chat + polling — คง endpoint/payload/interval เดิมเป๊ะ
    เปลี่ยนแค่ markup/สไตล์ ไม่แตะ JS logic การส่ง/รับข้อความ
--}}
<div style="display:flex; flex-direction:column; gap:18px;"
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

    {{-- ===== Header ===== --}}
    <div style="display:flex; flex-wrap:wrap; align-items:flex-end; justify-content:space-between; gap:14px;">
        <div>
            <div style="font-size:11px; color:var(--ink2); font-weight:600; letter-spacing:.4px;">
                หลังบ้าน · ระบบดูดวง · เทคโอเวอร์ห้องแชท
            </div>
            <h1 class="tp-num" style="font-size:clamp(22px,4vw,28px); font-weight:800; margin:4px 0 0;">
                {{ $reading->facebook_user_name ?? 'ไม่ระบุชื่อ' }} 💬
            </h1>
            <div style="display:flex; align-items:center; flex-wrap:wrap; gap:8px; margin-top:8px;">
                @if($reading->platform === 'line')
                    <span class="tp-pill" style="color:#5aa07e;">💚 LINE</span>
                @else
                    <span class="tp-pill" style="color:#5689b8;">🔵 Facebook</span>
                @endif
                <span class="tp-pill tp-pill-soft tp-num" style="font-size:12px;">{{ $reading->platform_user_id ?: $reading->facebook_user_id }}</span>
                @if($reading->bill_reference)
                    <span class="tp-pill tp-pill-soft tp-num">Bill: {{ $reading->bill_reference }}</span>
                @endif
            </div>
        </div>
        <div style="display:flex; align-items:center; gap:9px;">
            <a href="{{ route('admin.fortune.takeover.index') }}" class="tp-btn">
                <i class="fas fa-arrow-left"></i> กลับไปรายการ
            </a>
        </div>
    </div>

    {{-- ===== Flash messages (session-based — สำหรับ ban redirect) ===== --}}
    @if (session('success'))
        <div class="tp-card" style="border-left:4px solid #5aa07e; padding:14px 18px; color:var(--ink); font-size:14px;">
            <i class="fas fa-circle-check" style="color:#5aa07e; margin-right:8px;"></i>{{ session('success') }}
        </div>
    @endif
    @if (session('error'))
        <div class="tp-card" style="border-left:4px solid #d9534f; padding:14px 18px; color:var(--ink); font-size:14px;">
            <i class="fas fa-circle-exclamation" style="color:#d9534f; margin-right:8px;"></i>{{ session('error') }}
        </div>
    @endif
    @if ($errors->any())
        <div class="tp-card" style="border-left:4px solid #d9534f; padding:14px 18px; color:var(--ink);">
            <ul style="list-style:disc; padding-left:20px; font-size:14px; margin:0;">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- ===== master-detail grid ===== --}}
    <div class="tp-takeover-grid">
        {{-- ================= LEFT: Control Panel ================= --}}
        <div style="display:flex; flex-direction:column; gap:18px;">

            {{-- ----- Status Card ----- --}}
            <div class="tp-card" style="padding:0; overflow:hidden;">
                {{-- ส่วนสถานะ (เปลี่ยนสีตาม active) --}}
                <div style="padding:22px;"
                     :style="isActive
                        ? 'background:linear-gradient(135deg,var(--deep1),var(--deep2));'
                        : ''">
                    {{-- กำลังเทคโอเวอร์ --}}
                    <template x-if="isActive">
                        <div>
                            <div style="font-size:12px; color:#fff; opacity:.85; font-weight:600;">สถานะ</div>
                            <div style="font-size:22px; font-weight:800; margin-top:4px; color:#fff;">🎯 กำลังเทคโอเวอร์</div>
                            <div style="display:flex; align-items:baseline; gap:8px; margin-top:14px;">
                                <span class="tp-num" style="font-size:40px; font-weight:800; color:#fff;" x-text="formatRemaining()"></span>
                                <span style="font-size:13px; color:#fff; opacity:.85;">เหลือ</span>
                            </div>
                        </div>
                    </template>
                    {{-- AI ทำงานปกติ --}}
                    <template x-if="! isActive">
                        <div>
                            <div style="font-size:12px; color:var(--ink2); font-weight:600;">สถานะ</div>
                            <div style="font-size:22px; font-weight:800; margin-top:4px; color:var(--ink);">🤖 AI กำลังทำงาน</div>
                            <div style="font-size:13px; color:var(--ink2); margin-top:14px; line-height:1.6;">
                                กดปุ่มด้านล่างเพื่อเทคโอเวอร์ AI จะหยุดตอบให้แอดมินคุยเอง
                            </div>
                        </div>
                    </template>
                </div>

                <div style="padding:18px; display:flex; flex-direction:column; gap:12px;">
                    {{-- Takeover (ถ้ายังไม่ active) --}}
                    <template x-if="! isActive">
                        <div style="display:flex; flex-direction:column; gap:10px;">
                            <label style="font-size:12px; font-weight:600; color:var(--ink2);">ระยะเวลา (นาที)</label>
                            <div class="tp-well tp-input">
                                <input type="number" x-model.number="minutesInput" min="1" max="1440"
                                       class="tp-num"
                                       style="width:100%; background:transparent; border:0; outline:0; color:var(--ink); font-size:14px;">
                            </div>
                            <button @click="doTakeover()"
                                    :disabled="busy"
                                    class="tp-btn tp-btn-primary"
                                    style="width:100%; justify-content:center;"
                                    :style="busy ? 'opacity:.5; cursor:not-allowed;' : ''">
                                🎯 เทคโอเวอร์ — หยุด AI
                            </button>
                        </div>
                    </template>

                    {{-- Extend + Resume (ถ้า active) --}}
                    <template x-if="isActive">
                        <div style="display:flex; flex-direction:column; gap:10px;">
                            <div style="display:flex; gap:8px;">
                                <div class="tp-well tp-input" style="flex:1;">
                                    <input type="number" x-model.number="extendInput" min="1" max="1440" placeholder="นาที"
                                           class="tp-num"
                                           style="width:100%; background:transparent; border:0; outline:0; color:var(--ink); font-size:14px;">
                                </div>
                                <button @click="doExtend()"
                                        :disabled="busy"
                                        class="tp-btn"
                                        style="white-space:nowrap; color:#5689b8;"
                                        :style="busy ? 'opacity:.5;' : ''">
                                    ⏱ ต่อเวลา
                                </button>
                            </div>
                            <button @click="doResume()"
                                    :disabled="busy"
                                    class="tp-btn"
                                    style="width:100%; justify-content:center; color:#5aa07e;"
                                    :style="busy ? 'opacity:.5;' : ''">
                                ✨ ให้ AI กลับมาทำงาน
                            </button>
                        </div>
                    </template>

                    {{-- Flash Message (in-panel จาก AJAX) --}}
                    <div x-show="flashMessage"
                         x-transition
                         x-cloak
                         class="tp-inset-sm"
                         style="padding:12px 14px; border-radius:12px; font-size:13px; white-space:pre-line;"
                         :style="flashType === 'success'
                            ? 'color:#5aa07e;'
                            : 'color:#d9534f;'">
                        <span x-text="flashMessage"></span>
                    </div>
                </div>
            </div>

            {{-- ----- Customer Info ----- --}}
            <div class="tp-card">
                <div class="tp-section-h" style="margin-bottom:14px;">
                    <i class="fas fa-user-circle" style="color:var(--accent1);"></i> ข้อมูลลูกค้า
                </div>
                <div style="display:flex; flex-direction:column; gap:10px; font-size:14px;">
                    <div style="display:flex; justify-content:space-between; gap:10px;">
                        <span class="tp-muted">สถานะ Conversation</span>
                        <span style="font-weight:600; color:var(--ink);">{{ $reading->conversation_status }}</span>
                    </div>
                    @if($reading->birth_date)
                    <div style="display:flex; justify-content:space-between; gap:10px;">
                        <span class="tp-muted">วันเกิด</span>
                        <span class="tp-num" style="font-weight:600; color:var(--ink);">{{ $reading->birth_date->format('d/m/Y') }}</span>
                    </div>
                    @endif
                    @if($reading->is_paid)
                    <div style="display:flex; justify-content:space-between; gap:10px;">
                        <span class="tp-muted">ชำระแล้ว</span>
                        <span class="tp-num" style="font-weight:700; color:#5aa07e;">{{ number_format($reading->amount_paid, 2) }} ฿</span>
                    </div>
                    @endif
                    <div style="display:flex; justify-content:space-between; gap:10px;">
                        <span class="tp-muted">สร้างเมื่อ</span>
                        <span class="tp-num" style="font-weight:600; color:var(--ink);">{{ $reading->created_at->format('d/m/Y H:i') }}</span>
                    </div>
                </div>

                @if($reading->takeoverAdmin)
                <div class="tp-divider" style="margin:14px 0;"></div>
                <div>
                    <p class="tp-muted" style="font-size:12px; margin:0;">เทคโอเวอร์ล่าสุดโดย</p>
                    <p style="font-size:14px; font-weight:600; color:var(--ink); margin:2px 0 0;">{{ $reading->takeoverAdmin->name }}</p>
                    @if($reading->admin_takeover_reason)
                    <p class="tp-muted" style="font-size:12px; margin:2px 0 0;">
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

            {{-- ----- 🚫 (2026-05-23) Ban Danger Zone ----- --}}
            <div x-data="{ banDuration: 'permanent' }"
                 class="tp-card" style="padding:0; overflow:hidden; border:2px solid rgba(217,83,79,.35);">
                <div style="background:rgba(217,83,79,.1); padding:14px 18px; border-bottom:1px solid rgba(217,83,79,.25);">
                    <div style="font-weight:700; color:#d9534f; display:flex; align-items:center; gap:8px;">
                        🚫 ระบบคุก — แบน user คนนี้
                    </div>
                    <p style="font-size:12px; color:#d9534f; opacity:.85; margin:4px 0 0;">
                        บอทจะไม่ตอบ user คนนี้อีก (admin ยังคุยผ่าน Page Inbox / LINE OA ได้)
                    </p>
                </div>
                <form method="POST" action="{{ route('admin.fortune.takeover.ban', $reading) }}"
                      onsubmit="return confirm('ยืนยันการแบน user คนนี้?');">
                    @csrf
                    <input type="hidden" name="from" value="show">

                    <div style="padding:18px; display:flex; flex-direction:column; gap:12px;">
                        <div>
                            <label style="display:block; font-size:12px; font-weight:600; color:var(--ink2); margin-bottom:10px;">
                                ระยะเวลาแบน
                            </label>
                            <div style="display:flex; flex-direction:column; gap:8px;">
                                <template x-for="opt in [
                                    {value: '10m', label: '⏱ 10 นาที — เตือนสั้นๆ', danger: false},
                                    {value: '1h', label: '⏰ 1 ชั่วโมง', danger: false},
                                    {value: '24h', label: '📅 24 ชั่วโมง', danger: false},
                                    {value: '7d', label: '🗓️ 7 วัน', danger: false},
                                    {value: 'permanent', label: '🔒 ถาวร', danger: true}
                                ]" :key="opt.value">
                                    <label style="display:flex; align-items:center; gap:10px; padding:10px 12px; border-radius:12px; cursor:pointer; font-size:14px; transition:.15s; border:2px solid transparent;"
                                           :style="banDuration === opt.value
                                                ? 'border-color:#d9534f; background:rgba(217,83,79,.1);'
                                                : 'box-shadow:var(--inset-sm);'">
                                        <input type="radio" name="duration" :value="opt.value" x-model="banDuration"
                                               style="accent-color:#d9534f;">
                                        <span :style="opt.danger ? 'font-weight:700; color:#d9534f;' : 'color:var(--ink);'"
                                              x-text="opt.label"></span>
                                    </label>
                                </template>
                            </div>
                        </div>

                        <button type="submit"
                                class="tp-btn"
                                style="width:100%; justify-content:center; background:#d9534f; color:#fff; font-weight:700; border:0;">
                            🚫 ยืนยันแบน user คนนี้
                        </button>
                    </div>
                </form>
            </div>
        </div>

        {{-- ================= RIGHT: Conversation + Reply Box ================= --}}
        <div style="display:flex; flex-direction:column; gap:18px;">

            {{-- ----- Questions ----- --}}
            @if(! empty($reading->questions))
            <div class="tp-card">
                <div class="tp-section-h" style="margin-bottom:12px;">
                    💭 คำถามของลูกค้า
                </div>
                <ul style="list-style:disc; padding-left:20px; display:flex; flex-direction:column; gap:6px; font-size:14px; color:var(--ink); margin:0;">
                    @foreach($reading->questions as $question)
                        <li>{{ $question }}</li>
                    @endforeach
                </ul>
            </div>
            @endif

            {{-- ----- AI Responses ----- --}}
            @if($reading->basic_response)
            <div class="tp-card">
                <div class="tp-section-h" style="margin-bottom:12px;">
                    🔮 คำทำนายพื้นฐาน
                </div>
                <div class="tp-inset" style="padding:16px; border-radius:14px; font-size:14px; line-height:1.7; color:var(--ink); white-space:pre-wrap;">{{ $reading->basic_response }}</div>
            </div>
            @endif

            @if($reading->deep_response)
            <div class="tp-card">
                <div class="tp-section-h" style="margin-bottom:12px;">
                    ✨ คำทำนายเชิงลึก
                </div>
                <div class="tp-inset" style="padding:16px; border-radius:14px; font-size:14px; line-height:1.7; color:var(--ink); white-space:pre-wrap;">{{ $reading->deep_response }}</div>
            </div>
            @endif

            {{-- ----- Send Message Box ----- --}}
            <div class="tp-card">
                <div class="tp-section-h" style="margin-bottom:10px;">
                    💬 ส่งข้อความถึงลูกค้า
                </div>
                <p class="tp-muted" style="font-size:12px; margin:0 0 12px; line-height:1.6;">
                    ข้อความจะส่งผ่าน {{ $reading->platform === 'line' ? 'LINE' : 'Facebook Messenger' }} —
                    ถ้ายังไม่ได้เทคโอเวอร์ ระบบจะเทคโอเวอร์ให้อัตโนมัติก่อนส่ง
                </p>
                <div class="tp-well tp-input" style="padding:0;">
                    <textarea x-model="messageText"
                              rows="4"
                              placeholder="พิมพ์ข้อความที่อยากส่ง..."
                              style="width:100%; background:transparent; border:0; outline:0; padding:12px 14px; color:var(--ink); font-size:14px; resize:vertical; font-family:inherit;"></textarea>
                </div>
                <div style="display:flex; justify-content:space-between; align-items:center; margin-top:12px;">
                    <p class="tp-muted tp-num" style="font-size:12px; margin:0;" x-text="`${messageText.length} / 2000`"></p>
                    <button @click="doSendMessage()"
                            :disabled="busy || messageText.trim().length === 0 || messageText.length > 2000"
                            class="tp-btn tp-btn-primary"
                            :style="(busy || messageText.trim().length === 0 || messageText.length > 2000) ? 'opacity:.5; cursor:not-allowed;' : ''">
                        ส่งข้อความ <i class="fas fa-arrow-right"></i>
                    </button>
                </div>
            </div>

            {{-- ----- Takeover History ----- --}}
            @if($reading->takeoverLogs->isNotEmpty())
            <div class="tp-card">
                <div class="tp-section-h" style="margin-bottom:14px;">
                    📜 ประวัติการเทคโอเวอร์
                </div>
                <div style="display:flex; flex-direction:column; gap:10px; max-height:420px; overflow-y:auto;">
                    @foreach($reading->takeoverLogs as $log)
                    <div class="tp-inset-sm" style="padding:12px 14px; border-radius:12px; font-size:14px;">
                        <div style="display:flex; align-items:center; justify-content:space-between; gap:8px;">
                            <span style="font-weight:600; color:var(--ink);">{{ $log->getActionLabel() }}</span>
                            <span class="tp-muted" style="font-size:12px;">{{ $log->created_at->diffForHumans() }}</span>
                        </div>
                        <div class="tp-muted" style="font-size:12px; margin-top:6px; display:flex; align-items:center; gap:8px; flex-wrap:wrap;">
                            @if($log->user)
                                <span>👤 {{ $log->user->name }}</span>
                            @else
                                <span>🤖 ระบบ</span>
                            @endif
                            @if($log->reason)
                                <span>• {{ $log->getReasonLabel() }}</span>
                            @endif
                            @if($log->duration_minutes)
                                <span class="tp-num">• {{ $log->duration_minutes }} นาที</span>
                            @endif
                        </div>
                        @if($log->message)
                            <div class="tp-inset" style="margin-top:8px; font-size:12px; color:var(--ink); font-style:italic; padding:8px 10px; border-radius:10px;">
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
@endsection

@push('scripts')
{{-- CSS เฉพาะหน้า (ใส่ใน @push('scripts') เพื่อให้ render ท้าย body ทำงานได้) --}}
<style>
    /* master-detail grid — mobile-first */
    .tp-takeover-grid {
        display: grid;
        grid-template-columns: 1fr;
        gap: 18px;
    }
    @media (min-width: 1024px) {
        .tp-takeover-grid {
            grid-template-columns: minmax(330px, 1fr) minmax(330px, 1.6fr);
        }
    }
    [x-cloak] { display: none !important; }
</style>

{{--
    Alpine Component — ⚠️ ห้ามแตะ logic การส่ง/รับข้อความ
    คง endpoint/payload/interval เดิมเป๊ะ (poll 15s, countdown 1s)
--}}
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
@endpush
