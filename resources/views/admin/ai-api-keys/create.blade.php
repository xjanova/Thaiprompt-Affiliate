@extends('layouts.admin-v4')

@section('title', $pageTitle)

@section('content')
{{-- ════════════════════════════════════════════════════════════
     หน้า: เพิ่ม AI API Key (ธีม V4 "นวลทองคำ")
     ── ฟอร์มเพิ่ม key พร้อม cascading model/base-url ตาม provider
     ── คงฟังก์ชัน/field/route/AJAX/Alpine เดิม 100%
     ════════════════════════════════════════════════════════════ --}}
<div x-data="createApiKey()" style="max-width:740px;margin:0 auto;display:flex;flex-direction:column;gap:18px;">

    {{-- ── Header: eyebrow + h1 + ปุ่มกลับ ── --}}
    <div style="display:flex;align-items:center;gap:14px;">
        {{-- ปุ่มกลับไปหน้า index --}}
        <a href="{{ route('admin.ai-api-keys.index') }}" class="tp-icon-btn" title="กลับ">
            <i class="fas fa-arrow-left"></i>
        </a>
        <div>
            <div class="tp-muted" style="font-size:.72rem;letter-spacing:.08em;text-transform:uppercase;margin-bottom:4px;">
                หลังบ้าน · AI · API Keys · เพิ่มใหม่
            </div>
            <h1 class="tp-num" style="font-size:1.6rem;font-weight:800;color:var(--ink);margin:0;">{{ $pageTitle }}</h1>
        </div>
    </div>

    {{-- ── ฟอร์มหลัก ── --}}
    <div class="tp-card">
        <form @submit.prevent="submitForm()" style="display:flex;flex-direction:column;gap:18px;">

            {{-- ชื่อ Key --}}
            <div>
                <label class="tp-muted" style="display:block;font-weight:700;font-size:.82rem;margin-bottom:6px;color:var(--ink);">
                    ชื่อ Key <span style="color:#d46a6a;">*</span>
                </label>
                <input type="text" x-model="form.name" required
                       placeholder="เช่น Grok Key #1, Production Key"
                       class="tp-input" style="width:100%;">
            </div>

            {{-- Provider --}}
            <div>
                <label class="tp-muted" style="display:block;font-weight:700;font-size:.82rem;margin-bottom:6px;color:var(--ink);">
                    Provider <span style="color:#d46a6a;">*</span>
                </label>
                <select x-model="form.provider" @change="onProviderChange()" required class="tp-input" style="width:100%;">
                    <option value="">-- เลือก Provider --</option>
                    @foreach($providers as $key => $name)
                    <option value="{{ $key }}" {{ $provider === $key ? 'selected' : '' }}>{{ $name }}</option>
                    @endforeach
                </select>
            </div>

            {{-- Model — cascading dropdown ตาม provider --}}
            <div x-show="form.provider && availableModels.length > 0" x-cloak>
                <label class="tp-muted" style="display:block;font-weight:700;font-size:.82rem;margin-bottom:6px;color:var(--ink);">
                    Model <span class="tp-muted" style="font-weight:400;font-size:.72rem;">(ค่า default ของ provider จะใช้ตัวแรก)</span>
                </label>
                <select x-model="form.model" class="tp-input" style="width:100%;">
                    <option value="">-- ใช้ค่า default ของ provider --</option>
                    <template x-for="m in availableModels" :key="m">
                        <option :value="m" x-text="m"></option>
                    </template>
                </select>
                <p class="tp-muted" style="margin-top:6px;font-size:.76rem;">
                    เลือก model ที่จะใช้กับ key นี้ — ถ้าไม่เลือก จะใช้ตัวแรก:
                    <span style="font-family:monospace;color:var(--accent1);" x-text="availableModels[0] || '—'"></span>
                </p>
            </div>

            {{-- Base URL — สำหรับ provider ที่มี endpoint ต่างกัน (Xiaomi, custom proxy) --}}
            <div x-show="form.provider" x-cloak>
                <label class="tp-muted" style="display:block;font-weight:700;font-size:.82rem;margin-bottom:6px;color:var(--ink);">
                    Custom Base URL
                    <span class="tp-muted" style="font-weight:400;font-size:.72rem;">(เลือกใช้ — ปล่อยว่างเพื่อใช้ default)</span>
                </label>
                <input type="url" x-model="form.base_url"
                       :placeholder="defaultBaseUrl || 'https://api.example.com/v1'"
                       class="tp-input" style="width:100%;font-family:monospace;font-size:.82rem;">
                <p class="tp-muted" style="margin-top:6px;font-size:.76rem;">
                    Default ของ provider:
                    <span style="font-family:monospace;color:var(--accent1);" x-text="defaultBaseUrl || '—'"></span>
                </p>
            </div>

            {{-- API Key --}}
            <div>
                <label class="tp-muted" style="display:block;font-weight:700;font-size:.82rem;margin-bottom:6px;color:var(--ink);">
                    API Key <span style="color:#d46a6a;">*</span>
                </label>
                <div style="position:relative;">
                    <input :type="showApiKey ? 'text' : 'password'" x-model="form.api_key" required
                           placeholder="sk-xxxx... หรือ gsk_xxxx..."
                           class="tp-input" style="width:100%;padding-right:42px;font-family:monospace;font-size:.82rem;">
                    {{-- ปุ่มตา: แสดง/ซ่อน api key --}}
                    <button type="button" @click="showApiKey = !showApiKey"
                            style="position:absolute;right:12px;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;color:var(--ink2);">
                        <i class="fas" :class="showApiKey ? 'fa-eye-slash' : 'fa-eye'"></i>
                    </button>
                </div>
                <p class="tp-muted" style="margin-top:6px;font-size:.76rem;">API Key จะถูกเข้ารหัสก่อนบันทึก</p>
            </div>

            {{-- Priority --}}
            <div>
                <label class="tp-muted" style="display:block;font-weight:700;font-size:.82rem;margin-bottom:6px;color:var(--ink);">Priority</label>
                <input type="number" x-model="form.priority" min="0" max="100"
                       placeholder="0-100 (สูง = ใช้ก่อน)"
                       class="tp-input" style="width:100%;">
                <p class="tp-muted" style="margin-top:6px;font-size:.76rem;">ค่า priority สูงจะถูกเลือกใช้ก่อนในโหมด Priority และ Failover</p>
            </div>

            {{-- 🎯 (2026-05-02) Purpose — จำกัดการใช้ key ตามวัตถุประสงค์ --}}
            <div>
                <label class="tp-muted" style="display:block;font-weight:700;font-size:.82rem;margin-bottom:6px;color:var(--ink);">
                    🎯 วัตถุประสงค์ (Purpose)
                </label>
                <select x-model="form.purpose" class="tp-input" style="width:100%;">
                    {{-- 🚫 (2026-05-24) ลบ 'any' ออกจาก PURPOSES — บังคับ admin เลือก purpose เจาะจง --}}
                    <option value="">— กรุณาเลือกวัตถุประสงค์ —</option>
                    @foreach(\App\Models\AiApiKey::PURPOSES as $val => $label)
                        <option value="{{ $val }}">{{ $label }}</option>
                    @endforeach
                </select>
                {{-- บล็อกอธิบาย Hierarchy การเลือก key --}}
                <div class="tp-inset-sm" style="border-radius:12px;padding:12px;margin-top:8px;">
                    <p class="tp-muted" style="margin:0;font-size:.76rem;line-height:1.7;">
                        📋 <strong style="color:var(--ink);">Hierarchy การเลือก key</strong> (เจาะจง → ทั่วไป):<br>
                        💎 <strong style="color:var(--ink);">prediction_celtic</strong> → Celtic 99฿ ก่อน, fallback → prediction<br>
                        🌟 <strong style="color:var(--ink);">prediction_deep</strong> → Deep 39฿ ก่อน, fallback → prediction<br>
                        🔮 <strong style="color:var(--ink);">prediction</strong> = legacy — ใช้ทั้ง Deep + Celtic (ถ้าไม่อยากแยก)<br>
                        🎁 <strong style="color:var(--ink);">free_card</strong> → ทำนายฟรี (boost +1000 — สงวน paid keys)<br>
                        💬 <strong style="color:var(--ink);">chat</strong> → แชทสนทนา (FREE) — ไม่กระทบ pool ทำนาย<br>
                        <span style="color:var(--accent1);">💙 <strong>chat_paid</strong> → แชทพิเศษ (สีฟ้า, paid) — LAST RESORT หลัง free หมด</span><br>
                        🌟 <strong style="color:var(--ink);">sensitive</strong> = STRICT — Pro Session (Bill / Celtic Premium) ไม่ fallback<br>
                        🎙️ <strong style="color:var(--ink);">tts</strong> = STRICT — เฉพาะสังเคราะห์เสียง<br>
                        🔼 <strong style="color:var(--ink);">Priority</strong> สูง = เลือกก่อนใน purpose เดียวกัน (100 = สูงสุด)
                    </p>
                </div>
            </div>

            {{-- Limits — grid 2 คอลัมน์ --}}
            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:14px;">
                <div>
                    <label class="tp-muted" style="display:block;font-weight:700;font-size:.82rem;margin-bottom:6px;color:var(--ink);">Daily Token Limit</label>
                    <input type="number" x-model="form.tokens_limit_daily" min="0"
                           placeholder="ว่าง = ไม่จำกัด"
                           class="tp-input" style="width:100%;">
                </div>
                <div>
                    <label class="tp-muted" style="display:block;font-weight:700;font-size:.82rem;margin-bottom:6px;color:var(--ink);">Monthly Token Limit</label>
                    <input type="number" x-model="form.tokens_limit_monthly" min="0"
                           placeholder="ว่าง = ไม่จำกัด"
                           class="tp-input" style="width:100%;">
                </div>
            </div>

            {{-- Rate Limit --}}
            <div>
                <label class="tp-muted" style="display:block;font-weight:700;font-size:.82rem;margin-bottom:6px;color:var(--ink);">Rate Limit (requests/minute)</label>
                <input type="number" x-model="form.rate_limit_per_minute" min="0"
                       placeholder="ว่าง = ไม่จำกัด"
                       class="tp-input" style="width:100%;">
            </div>

            {{-- Notes --}}
            <div>
                <label class="tp-muted" style="display:block;font-weight:700;font-size:.82rem;margin-bottom:6px;color:var(--ink);">หมายเหตุ</label>
                <textarea x-model="form.notes" rows="3"
                          placeholder="หมายเหตุเพิ่มเติม..."
                          class="tp-input" style="width:100%;resize:vertical;"></textarea>
            </div>

            {{-- Submit --}}
            <div class="tp-divider"></div>
            <div style="display:flex;align-items:center;gap:12px;">
                <button type="submit" :disabled="submitting"
                        class="tp-btn tp-btn-primary"
                        style="flex:1;justify-content:center;">
                    <i class="fas fa-circle-notch fa-spin" x-show="submitting" x-cloak></i>
                    <i class="fas fa-floppy-disk" x-show="!submitting"></i>
                    <span x-text="submitting ? 'กำลังบันทึก...' : 'บันทึก API Key'"></span>
                </button>
                <a href="{{ route('admin.ai-api-keys.index') }}" class="tp-btn">
                    <i class="fas fa-xmark"></i>
                    <span>ยกเลิก</span>
                </a>
            </div>
        </form>
    </div>
</div>
@endsection

{{-- scripts stack: Alpine createApiKey logic (คง state/method/endpoint/payload เดิม 100%) --}}
@push('scripts')
<script>
function createApiKey() {
    return {
        showApiKey: false,
        submitting: false,
        // 🎯 (2026-05-01) provider → models map (cascading dropdown)
        modelsByProvider: @json($modelsByProvider),
        defaultBaseUrls: @json($defaultBaseUrls),
        availableModels: [],
        defaultBaseUrl: '',
        form: {
            name: '',
            provider: '{{ $provider ?? '' }}',
            model: '',
            base_url: '',
            api_key: '',
            priority: 0,
            purpose: '',
            tokens_limit_daily: '',
            tokens_limit_monthly: '',
            rate_limit_per_minute: '',
            notes: ''
        },

        init() {
            this.onProviderChange();
        },

        onProviderChange() {
            const p = this.form.provider;
            this.availableModels = this.modelsByProvider[p] || [];
            this.defaultBaseUrl = this.defaultBaseUrls[p] || '';
            // ถ้า model ที่เลือกอยู่ ไม่อยู่ใน list ใหม่ → reset
            if (this.form.model && !this.availableModels.includes(this.form.model)) {
                this.form.model = '';
            }
        },

        async submitForm() {
            if (!this.form.name || !this.form.provider || !this.form.api_key) {
                alert('กรุณากรอกข้อมูลที่จำเป็น');
                return;
            }

            this.submitting = true;
            try {
                const response = await fetch('{{ route('admin.ai-api-keys.store') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify(this.form)
                });
                const data = await response.json();

                if (data.success) {
                    window.location.href = '{{ route('admin.ai-api-keys.provider', '') }}/' + this.form.provider;
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
