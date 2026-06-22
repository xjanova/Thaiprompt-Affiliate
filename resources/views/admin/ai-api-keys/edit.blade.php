@extends('layouts.admin-v4')

@section('title', $pageTitle)

@section('content')
{{-- ════════════════════════════════════════════════════════════
     หน้า: แก้ไข AI API Key (ธีม V4 "นวลทองคำ")
     ── ฟอร์มแก้ไข key + สถิติการใช้งาน + ปลด critical
     ── คงฟังก์ชัน/AJAX/Alpine/field/route เดิม 100%
     ════════════════════════════════════════════════════════════ --}}
<div x-data="editApiKey()" style="max-width:760px;margin:0 auto;display:flex;flex-direction:column;gap:18px;">

    {{-- ── Header: ปุ่ม back + eyebrow + h1 ── --}}
    <div style="display:flex;align-items:center;gap:14px;">
        {{-- ปุ่มย้อนกลับไปหน้า provider เดิม --}}
        <a href="{{ route('admin.ai-api-keys.provider', $key->provider) }}" class="tp-icon-btn" title="ย้อนกลับ">
            <i class="fas fa-arrow-left"></i>
        </a>
        <div>
            <div class="tp-muted" style="font-size:.72rem;letter-spacing:.08em;text-transform:uppercase;margin-bottom:4px;">
                หลังบ้าน · AI · API Keys · แก้ไข
            </div>
            <h1 class="tp-num" style="font-size:1.6rem;font-weight:800;color:var(--ink);margin:0;">{{ $pageTitle }}</h1>
        </div>
    </div>

    {{-- 🔴 กล่องเตือน Critical (แสดงเฉพาะ key ที่ติด critical) --}}
    @if($key->is_critical)
    <div class="tp-card" style="border:1px solid #d46a6a;">
        <div style="display:flex;align-items:flex-start;gap:14px;">
            <div class="tp-tile" style="width:44px;height:44px;border-radius:12px;font-size:18px;background:#d46a6a;flex:none;">
                <i class="fas fa-triangle-exclamation"></i>
            </div>
            <div style="flex:1;min-width:0;">
                <h4 style="margin:0;font-weight:800;color:#d46a6a;font-size:1rem;">Critical State</h4>
                <p class="tp-muted" style="margin:6px 0 0;font-size:.88rem;line-height:1.5;">
                    Key นี้ติด critical (3 strikes) — ระบบไม่ใช้อัตโนมัติ
                    ถ้าตรวจสอบแล้วใช้ได้ กดปุ่ม "ปลด Critical" เพื่อเปิดกลับ
                </p>
                @if($key->last_error)
                <div class="tp-inset-sm" style="border-radius:10px;padding:10px 12px;margin-top:10px;">
                    <span class="tp-muted" style="font-size:.78rem;font-weight:700;">Last error: </span>
                    <span class="tp-num" style="font-size:.78rem;color:var(--ink2);word-break:break-all;">{{ Str::limit($key->last_error, 200) }}</span>
                </div>
                @endif
                <button type="button" @click="clearCritical()" class="tp-btn tp-btn-sm" style="margin-top:12px;color:#d46a6a;border-color:#d46a6a;">
                    <i class="fas fa-unlock"></i>
                    <span>ปลด Critical</span>
                </button>
            </div>
        </div>
    </div>
    @endif

    {{-- ── ฟอร์มแก้ไข key ── --}}
    <div class="tp-card">
        <form @submit.prevent="submitForm()" style="display:flex;flex-direction:column;gap:18px;">

            {{-- ชื่อ Key --}}
            <div>
                <label class="tp-muted" style="display:block;font-size:.82rem;font-weight:700;margin-bottom:6px;">
                    ชื่อ Key <span style="color:#d46a6a;">*</span>
                </label>
                <input type="text" x-model="form.name" required class="tp-input" style="width:100%;">
            </div>

            {{-- Provider (readonly แสดงชื่อ provider) --}}
            <div>
                <label class="tp-muted" style="display:block;font-size:.82rem;font-weight:700;margin-bottom:6px;">Provider</label>
                <input type="text" value="{{ $key->provider_name }}" readonly class="tp-input" style="width:100%;color:var(--ink2);">
            </div>

            {{-- 🎯 Model — per-key override --}}
            @if(!empty($modelsByProvider[$key->provider]))
            <div>
                <label class="tp-muted" style="display:block;font-size:.82rem;font-weight:700;margin-bottom:6px;">
                    Model <span class="tp-muted" style="font-weight:400;font-size:.75rem;">(ค่า default = ตัวแรก)</span>
                </label>
                <select x-model="form.model" class="tp-input" style="width:100%;">
                    <option value="">-- ใช้ค่า default ของ provider --</option>
                    @foreach($modelsByProvider[$key->provider] as $m)
                        <option value="{{ $m }}" {{ $key->model === $m ? 'selected' : '' }}>{{ $m }}</option>
                    @endforeach
                </select>
                <p class="tp-muted" style="margin:6px 0 0;font-size:.78rem;">
                    Default: <span class="tp-num" style="color:var(--accent1);">{{ $modelsByProvider[$key->provider][0] ?? '—' }}</span>
                </p>
            </div>
            @endif

            {{-- 🌐 Custom Base URL --}}
            <div>
                <label class="tp-muted" style="display:block;font-size:.82rem;font-weight:700;margin-bottom:6px;">
                    Custom Base URL <span class="tp-muted" style="font-weight:400;font-size:.75rem;">(เลือกใช้)</span>
                </label>
                <input type="url" x-model="form.base_url"
                       placeholder="{{ $defaultBaseUrls[$key->provider] ?? 'https://api.example.com/v1' }}"
                       class="tp-input" style="width:100%;font-size:.85rem;">
                <p class="tp-muted" style="margin:6px 0 0;font-size:.78rem;">
                    Default: <span class="tp-num" style="color:var(--accent1);">{{ $defaultBaseUrls[$key->provider] ?? '—' }}</span>
                </p>
            </div>

            {{-- API Key --}}
            <div>
                <label class="tp-muted" style="display:block;font-size:.82rem;font-weight:700;margin-bottom:6px;">API Key</label>
                <div style="position:relative;">
                    <input :type="showApiKey ? 'text' : 'password'" x-model="form.api_key"
                           placeholder="ปล่อยว่างถ้าไม่ต้องการเปลี่ยน"
                           class="tp-input" style="width:100%;padding-right:42px;font-size:.85rem;">
                    <button type="button" @click="showApiKey = !showApiKey"
                            style="position:absolute;right:12px;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;color:var(--ink2);">
                        <i :class="showApiKey ? 'fas fa-eye-slash' : 'fas fa-eye'"></i>
                    </button>
                </div>
                <p class="tp-muted" style="margin:6px 0 0;font-size:.78rem;">Key ปัจจุบัน: {{ $key->masked_key }}</p>
            </div>

            {{-- เปิดใช้งาน --}}
            <div style="display:flex;align-items:center;gap:10px;">
                <input type="checkbox" x-model="form.is_active" id="is_active" class="tp-sw">
                <label for="is_active" class="tp-muted" style="font-size:.88rem;font-weight:700;cursor:pointer;">เปิดใช้งาน</label>
            </div>

            {{-- Priority --}}
            <div>
                <label class="tp-muted" style="display:block;font-size:.82rem;font-weight:700;margin-bottom:6px;">Priority</label>
                <input type="number" x-model="form.priority" min="0" max="100" class="tp-input" style="width:100%;">
            </div>

            {{-- 🎯 (2026-05-02) Purpose — จำกัดการใช้ key ตามวัตถุประสงค์ --}}
            <div>
                <label class="tp-muted" style="display:block;font-size:.82rem;font-weight:700;margin-bottom:6px;">
                    🎯 วัตถุประสงค์ (Purpose)
                </label>
                {{-- 🚫 (2026-05-24) ลบ 'any' ออกจาก PURPOSES — บังคับ admin เลือก purpose เจาะจง
                     key เก่าที่เคย purpose='any' จะแสดง blank — admin ต้องเลือกใหม่ก่อนบันทึก --}}
                <select x-model="form.purpose" class="tp-input" style="width:100%;">
                    <option value="">— กรุณาเลือกวัตถุประสงค์ —</option>
                    @foreach(\App\Models\AiApiKey::PURPOSES as $val => $label)
                        <option value="{{ $val }}">{{ $label }}</option>
                    @endforeach
                </select>
                @if($key->purpose === 'any')
                <p style="margin:6px 0 0;font-size:.78rem;font-weight:700;color:#d46a6a;">
                    ⚠️ Key นี้ใช้ <code>purpose='any'</code> ซึ่งถูกยกเลิกแล้ว — Pool จะไม่เลือก key นี้
                    จนกว่าจะตั้งวัตถุประสงค์ใหม่
                </p>
                @endif
                <div class="tp-inset-sm" style="border-radius:12px;padding:12px 14px;margin-top:10px;">
                    <p class="tp-muted" style="margin:0;font-size:.78rem;line-height:1.6;">
                        📋 <strong>Hierarchy การเลือก key</strong> (เจาะจง → ทั่วไป):<br>
                        💎 <strong>prediction_celtic</strong> → Celtic 99฿ ก่อน, fallback → prediction<br>
                        🌟 <strong>prediction_deep</strong> → Deep 39฿ ก่อน, fallback → prediction<br>
                        🔮 <strong>prediction</strong> = legacy — ใช้ทั้ง Deep + Celtic (ถ้าไม่อยากแยก)<br>
                        🎁 <strong>free_card</strong> → ทำนายฟรี (boost +1000 — สงวน paid keys)<br>
                        💬 <strong>chat</strong> → แชทสนทนา (FREE) — ไม่กระทบ pool ทำนาย<br>
                        <span style="color:var(--accent1);">💙 <strong>chat_paid</strong> → แชทพิเศษ (สีฟ้า, paid) — LAST RESORT หลัง free หมด</span><br>
                        🌟 <strong>sensitive</strong> = STRICT — Pro Session (Bill / Celtic Premium) ไม่ fallback<br>
                        🎙️ <strong>tts</strong> = STRICT — เฉพาะสังเคราะห์เสียง<br>
                        🔼 <strong>Priority</strong> สูง = เลือกก่อนใน purpose เดียวกัน (100 = สูงสุด)
                    </p>
                </div>
            </div>

            {{-- Limits --}}
            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:14px;">
                <div>
                    <label class="tp-muted" style="display:block;font-size:.82rem;font-weight:700;margin-bottom:6px;">Daily Token Limit</label>
                    <input type="number" x-model="form.tokens_limit_daily" min="0"
                           placeholder="ว่าง = ไม่จำกัด" class="tp-input" style="width:100%;">
                </div>
                <div>
                    <label class="tp-muted" style="display:block;font-size:.82rem;font-weight:700;margin-bottom:6px;">Monthly Token Limit</label>
                    <input type="number" x-model="form.tokens_limit_monthly" min="0"
                           placeholder="ว่าง = ไม่จำกัด" class="tp-input" style="width:100%;">
                </div>
            </div>

            {{-- Rate Limit --}}
            <div>
                <label class="tp-muted" style="display:block;font-size:.82rem;font-weight:700;margin-bottom:6px;">Rate Limit (requests/minute)</label>
                <input type="number" x-model="form.rate_limit_per_minute" min="0"
                       placeholder="ว่าง = ไม่จำกัด" class="tp-input" style="width:100%;">
            </div>

            {{-- หมายเหตุ --}}
            <div>
                <label class="tp-muted" style="display:block;font-size:.82rem;font-weight:700;margin-bottom:6px;">หมายเหตุ</label>
                <textarea x-model="form.notes" rows="3" class="tp-input" style="width:100%;resize:vertical;"></textarea>
            </div>

            {{-- ── กล่องสถิติการใช้งาน ── --}}
            <div class="tp-inset-sm" style="border-radius:12px;padding:16px;">
                <h4 class="tp-section-h" style="margin:0 0 14px;display:flex;align-items:center;gap:8px;">
                    <i class="fas fa-chart-simple" style="color:var(--accent1);"></i>
                    <span>สถิติการใช้งาน</span>
                </h4>
                <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(140px,1fr));gap:14px;">
                    <div>
                        <span class="tp-muted" style="font-size:.78rem;">Tokens วันนี้</span>
                        <p class="tp-num" style="margin:2px 0 0;font-weight:700;color:var(--ink);">{{ number_format($key->tokens_used_today) }}</p>
                    </div>
                    <div>
                        <span class="tp-muted" style="font-size:.78rem;">Tokens เดือนนี้</span>
                        <p class="tp-num" style="margin:2px 0 0;font-weight:700;color:var(--ink);">{{ number_format($key->tokens_used_month) }}</p>
                    </div>
                    <div>
                        <span class="tp-muted" style="font-size:.78rem;">Tokens รวม</span>
                        <p class="tp-num" style="margin:2px 0 0;font-weight:700;color:var(--ink);">{{ number_format($key->tokens_used_total) }}</p>
                    </div>
                    <div>
                        <span class="tp-muted" style="font-size:.78rem;">Requests วันนี้</span>
                        <p class="tp-num" style="margin:2px 0 0;font-weight:700;color:var(--ink);">{{ number_format($key->requests_today) }}</p>
                    </div>
                    <div>
                        <span class="tp-muted" style="font-size:.78rem;">RPM ปัจจุบัน (real-time)</span>
                        <p class="tp-num" style="margin:2px 0 0;font-weight:700;color:var(--ink);">
                            {{ number_format($key->current_rpm) }}
                            @if ($key->rate_limit_per_minute)
                                <span class="tp-muted" style="font-size:.72rem;font-weight:400;">/ {{ $key->rate_limit_per_minute }}</span>
                            @endif
                        </p>
                    </div>
                    <div>
                        <span class="tp-muted" style="font-size:.78rem;">In-flight (concurrent)</span>
                        <p class="tp-num" style="margin:2px 0 0;font-weight:700;color:var(--ink);">{{ number_format($key->current_inflight) }}</p>
                    </div>
                </div>
            </div>

            {{-- ── ปุ่ม submit + ยกเลิก ── --}}
            <div class="tp-divider"></div>
            <div style="display:flex;align-items:center;gap:12px;">
                <button type="submit" :disabled="submitting" class="tp-btn tp-btn-primary" style="flex:1;justify-content:center;">
                    <i class="fas fa-spinner fa-spin" x-show="submitting"></i>
                    <i class="fas fa-floppy-disk" x-show="!submitting"></i>
                    <span x-text="submitting ? 'กำลังบันทึก...' : 'บันทึก'"></span>
                </button>
                <a href="{{ route('admin.ai-api-keys.provider', $key->provider) }}" class="tp-btn">
                    <i class="fas fa-xmark"></i>
                    <span>ยกเลิก</span>
                </a>
            </div>
        </form>
    </div>
</div>

{{-- scripts stack: Alpine logic (คง endpoint/payload/method เดิม 100%) --}}
@push('scripts')
<script>
function editApiKey() {
    return {
        showApiKey: false,
        submitting: false,
        form: {
            name: @json($key->name),
            model: @json($key->model ?? ''),
            base_url: @json($key->base_url ?? ''),
            api_key: '',
            is_active: {{ $key->is_active ? 'true' : 'false' }},
            priority: {{ $key->priority ?? 0 }},
            purpose: @json($key->purpose === 'any' ? '' : ($key->purpose ?? '')),
            tokens_limit_daily: {{ $key->tokens_limit_daily ?? 'null' }},
            tokens_limit_monthly: {{ $key->tokens_limit_monthly ?? 'null' }},
            rate_limit_per_minute: {{ $key->rate_limit_per_minute ?? 'null' }},
            notes: @json($key->notes ?? '')
        },

        async clearCritical() {
            if (!confirm('ปลด critical state? Key จะกลับมาใช้งานได้')) return;
            try {
                const response = await fetch('{{ route('admin.ai-api-keys.clear-critical', $key->id) }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    }
                });
                const data = await response.json();
                if (data.success) {
                    alert(data.message);
                    window.location.reload();
                } else {
                    alert(data.message || 'เกิดข้อผิดพลาด');
                }
            } catch (e) {
                alert('เกิดข้อผิดพลาด: ' + e.message);
            }
        },

        async submitForm() {
            if (!this.form.name) {
                alert('กรุณากรอกชื่อ Key');
                return;
            }

            this.submitting = true;
            try {
                const response = await fetch('{{ route('admin.ai-api-keys.update', $key->id) }}', {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify(this.form)
                });
                const data = await response.json();

                if (data.success) {
                    window.location.href = '{{ route('admin.ai-api-keys.provider', $key->provider) }}';
                } else {
                    alert(data.message || 'เกิดข้อผิดพลาด');
                }
            } catch (error) {
                console.error('Error:', error);
                alert('เกิดข้อผิดพลาดในการบันทึก');
            } finally {
                this.submitting = false;
            }
        }
    };
}
</script>
@endpush
@endsection
