{{-- หน้าฟอร์มแคมเปญการตลาดดูดวง (create + edit ร่วม) — ธีม V4 "นวลทองคำ" (Nuan Gold Clay) --}}
@extends('layouts.admin-v4')

@section('title', $pageTitle)

@section('content')
{{-- คอนเทนเนอร์หลัก: ผูก Alpine สำหรับ toggle AI + พรีวิว AI (AJAX) + สลับโหมดตั้งเวลา --}}
<div x-data="campaignForm()" style="display:flex; flex-direction:column; gap:18px;">

    {{-- ===== HEADER ===== --}}
    <div style="display:flex; flex-wrap:wrap; align-items:flex-end; justify-content:space-between; gap:14px;">
        <div>
            <div class="tp-muted" style="font-size:11px; font-weight:600; letter-spacing:.4px; text-transform:uppercase;">
                หลังบ้าน · ระบบดูดวง · การตลาดอัตโนมัติ
            </div>
            <h1 class="tp-num" style="font-size:clamp(22px,4vw,28px); font-weight:800; margin:5px 0 0; display:flex; align-items:center; gap:10px;">
                <i class="fas {{ $campaign ? 'fa-pen-to-square' : 'fa-bullhorn' }}" style="color:var(--accent1);"></i>
                {{ $pageTitle }}
            </h1>
            <div class="tp-muted" style="font-size:13px; margin-top:5px;">
                ตั้งหัวข้อ + เวลา + เป้าหมาย แล้วให้แม่หมอจันทรา (AI) เขียนข้อความเชิญชวนส่งให้ลูกค้าอัตโนมัติ
            </div>
        </div>
        <div style="display:flex; align-items:center; gap:9px;">
            @if(Route::has('admin.fortune.marketing.index'))
            <a href="{{ route('admin.fortune.marketing.index') }}" class="tp-btn tp-btn-sm">
                <i class="fas fa-arrow-left"></i>&nbsp; กลับรายการ
            </a>
            @endif
            {{-- ปุ่มบันทึก (ผูกกับฟอร์มด้วย attribute form) --}}
            <button type="submit" form="campaignFormEl" class="tp-btn tp-btn-primary tp-btn-sm">
                <i class="fas fa-floppy-disk"></i>&nbsp; {{ $campaign ? 'อัปเดตแคมเปญ' : 'สร้างแคมเปญ' }}
            </button>
        </div>
    </div>

    {{-- ===== แจ้งเตือน validation รวม (ถ้ามี) ===== --}}
    @if($errors->any())
    <div class="tp-card tp-inset" style="padding:16px 18px; border-left:4px solid #d9534f;">
        <div style="display:flex; align-items:center; gap:10px; color:#d9534f; font-weight:700; margin-bottom:8px;">
            <i class="fas fa-triangle-exclamation"></i>
            <span>กรุณาตรวจสอบข้อมูล</span>
        </div>
        <ul style="margin:0; padding-left:20px; font-size:13px; color:var(--ink2); line-height:1.7;">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
    @endif

    {{-- ===== ฟอร์มหลัก: คง action/method/@csrf/@method/ชื่อ field 100% ===== --}}
    <form id="campaignFormEl"
          action="{{ $campaign ? route('admin.fortune.marketing.update', $campaign) : route('admin.fortune.marketing.store') }}"
          method="POST">
        @csrf
        @if($campaign) @method('PUT') @endif

        {{-- เลย์เอาต์ 2 คอลัมน์: ข้อมูล/ข้อความ (ซ้าย) + ตั้งค่า (ขวา) --}}
        <div style="display:grid; grid-template-columns:minmax(330px,2fr) minmax(280px,1fr); gap:18px; align-items:start;">

            {{-- ========== คอลัมน์ซ้าย ========== --}}
            <div style="display:flex; flex-direction:column; gap:18px;">

                {{-- ----- การ์ด: ข้อมูลแคมเปญ ----- --}}
                <div class="tp-card tp-raise" style="padding:24px;">
                    <div class="tp-section-h" style="display:flex; align-items:center; gap:8px; margin-bottom:18px;">
                        <i class="fas fa-clipboard-list" style="color:var(--deep1);"></i>
                        <span style="font-weight:700; color:var(--ink);">ข้อมูลแคมเปญ</span>
                    </div>

                    <div style="display:flex; flex-direction:column; gap:16px;">

                        {{-- ชื่อแคมเปญ (required) --}}
                        <div>
                            <label for="mk-name" style="display:block; font-size:12.5px; font-weight:600; color:var(--ink2); margin-bottom:7px;">
                                ชื่อแคมเปญ <span style="color:#d9534f;">*</span>
                            </label>
                            <div class="tp-well tp-input">
                                <input type="text" id="mk-name" name="name" required
                                       value="{{ old('name', $campaign?->name) }}"
                                       placeholder="เช่น โปรโมชั่นดูดวงปีใหม่"
                                       style="width:100%; background:transparent; border:none; outline:none; color:var(--ink); font-size:14px;">
                            </div>
                            @error('name')
                            <p style="margin:6px 2px 0; font-size:12px; color:#d9534f;">
                                <i class="fas fa-circle-exclamation"></i> {{ $message }}
                            </p>
                            @enderror
                        </div>

                        {{-- หัวข้อ/เรื่อง (สำหรับ AI) --}}
                        <div>
                            <label for="mk-topic" style="display:block; font-size:12.5px; font-weight:600; color:var(--ink2); margin-bottom:7px;">
                                หัวข้อ/เรื่อง <span class="tp-muted" style="font-weight:400;">(สำหรับ AI สร้างข้อความ)</span>
                            </label>
                            <div class="tp-well tp-input">
                                <input type="text" id="mk-topic" name="topic" x-model="topic"
                                       value="{{ old('topic', $campaign?->topic) }}"
                                       placeholder="เช่น ดวงราศีประจำเดือน, วันวาเลนไทน์, ตรุษจีน"
                                       style="width:100%; background:transparent; border:none; outline:none; color:var(--ink); font-size:14px;">
                            </div>
                            @error('topic')
                            <p style="margin:6px 2px 0; font-size:12px; color:#d9534f;">
                                <i class="fas fa-circle-exclamation"></i> {{ $message }}
                            </p>
                            @enderror
                        </div>

                        {{-- คำอธิบาย --}}
                        <div>
                            <label for="mk-desc" style="display:block; font-size:12.5px; font-weight:600; color:var(--ink2); margin-bottom:7px;">
                                คำอธิบาย <span class="tp-muted" style="font-weight:400;">(แอดมินเห็น ไม่ได้ส่งให้ผู้ใช้)</span>
                            </label>
                            <div class="tp-well tp-input">
                                <textarea id="mk-desc" name="description" rows="2"
                                          placeholder="คำอธิบายเพิ่มเติม"
                                          style="width:100%; background:transparent; border:none; outline:none; resize:vertical; color:var(--ink); font-size:14px; line-height:1.6;">{{ old('description', $campaign?->description) }}</textarea>
                            </div>
                            @error('description')
                            <p style="margin:6px 2px 0; font-size:12px; color:#d9534f;">
                                <i class="fas fa-circle-exclamation"></i> {{ $message }}
                            </p>
                            @enderror
                        </div>

                    </div>
                </div>

                {{-- ----- การ์ด: ข้อความที่จะส่ง ----- --}}
                <div class="tp-card tp-raise" style="padding:24px;">
                    <div class="tp-section-h" style="display:flex; align-items:center; gap:8px; margin-bottom:18px;">
                        <i class="fas fa-comment-dots" style="color:var(--deep1);"></i>
                        <span style="font-weight:700; color:var(--ink);">ข้อความที่จะส่ง</span>
                    </div>

                    {{-- toggle: ใช้ AI สร้างข้อความ — checkbox จริง name=use_ai_generate value=1 --}}
                    <label class="tp-raise" style="display:flex; align-items:center; gap:12px; cursor:pointer; padding:13px 15px; border-radius:12px; background:var(--surf); margin-bottom:18px;">
                        <input type="checkbox" name="use_ai_generate" value="1" x-model="useAI"
                               {{ old('use_ai_generate', $campaign?->use_ai_generate ?? true) ? 'checked' : '' }}
                               style="width:18px; height:18px; accent-color:var(--accent1); cursor:pointer;">
                        <span style="display:flex; align-items:center; gap:8px; font-size:13.5px; color:var(--ink); font-weight:600;">
                            <i class="fas fa-robot" style="color:var(--accent2);"></i>
                            ให้ AI สร้างข้อความอัตโนมัติ (จันทราจะเขียนให้)
                        </span>
                        <span class="tp-pill" :class="useAI ? 'tp-pill-gold' : 'tp-pill-soft'" style="margin-left:auto;"
                              x-text="useAI ? 'AI' : 'เขียนเอง'"></span>
                    </label>

                    {{-- ----- โหมด AI ----- --}}
                    <div x-show="useAI" x-cloak style="display:flex; flex-direction:column; gap:16px;">
                        {{-- Prompt สำหรับ AI --}}
                        <div>
                            <label for="mk-ai-prompt" style="display:block; font-size:12.5px; font-weight:600; color:var(--ink2); margin-bottom:7px;">
                                Prompt สำหรับ AI <span class="tp-muted" style="font-weight:400;">(ไม่ใส่ = ใช้ค่าเริ่มต้น)</span>
                            </label>
                            <div class="tp-well tp-input">
                                <textarea id="mk-ai-prompt" name="ai_prompt" rows="4"
                                          placeholder="ปล่อยว่าง = AI จะใช้หัวข้อด้านบนสร้างข้อความการตลาดแบบแม่หมอจันทราอัตโนมัติ&#10;&#10;หรือใส่ prompt เองเพื่อกำหนดรูปแบบข้อความที่ต้องการ..."
                                          style="width:100%; background:transparent; border:none; outline:none; resize:vertical; color:var(--ink); font-size:14px; line-height:1.6;">{{ old('ai_prompt', $campaign?->ai_prompt) }}</textarea>
                            </div>
                            @error('ai_prompt')
                            <p style="margin:6px 2px 0; font-size:12px; color:#d9534f;">
                                <i class="fas fa-circle-exclamation"></i> {{ $message }}
                            </p>
                            @enderror
                        </div>

                        {{-- ปุ่มทดสอบ AI สร้างข้อความ (AJAX) --}}
                        <div>
                            <button type="button" @click="previewAI()" :disabled="generating"
                                    class="tp-btn"
                                    :style="generating ? 'opacity:.65; cursor:wait;' : ''"
                                    style="background:linear-gradient(135deg,var(--accent2),var(--deep2)); color:#fff;">
                                <i class="fas" :class="generating ? 'fa-spinner fa-spin' : 'fa-wand-magic-sparkles'"></i>&nbsp;
                                <span x-text="generating ? 'AI กำลังสร้าง...' : 'ทดสอบ AI สร้างข้อความ'"></span>
                            </button>
                        </div>

                        {{-- ตัวอย่างจาก AI (live) --}}
                        <div x-show="aiPreview" x-cloak class="tp-inset" style="border-radius:14px; padding:16px 18px; border-left:4px solid var(--accent2);">
                            <div style="font-size:11.5px; font-weight:700; color:var(--accent2); margin-bottom:8px; display:flex; align-items:center; gap:6px;">
                                <i class="fas fa-sparkles"></i> ตัวอย่างจาก AI:
                            </div>
                            <div style="font-size:13.5px; color:var(--ink); line-height:1.7; white-space:pre-wrap;" x-text="aiPreview"></div>
                        </div>

                        {{-- ข้อความ AI ที่บันทึกไว้ (เฉพาะตอน edit) --}}
                        @if($campaign?->ai_generated_message)
                        <div class="tp-inset" style="border-radius:14px; padding:16px 18px; border-left:4px solid #5aa07e;">
                            <div style="font-size:11.5px; font-weight:700; color:#5aa07e; margin-bottom:8px; display:flex; align-items:center; gap:6px;">
                                <i class="fas fa-circle-check"></i> ข้อความ AI ที่สร้างไว้:
                            </div>
                            <div style="font-size:13.5px; color:var(--ink); line-height:1.7; white-space:pre-wrap;">{{ $campaign->ai_generated_message }}</div>
                        </div>
                        @endif
                    </div>

                    {{-- ----- โหมดเขียนเอง ----- --}}
                    <div x-show="!useAI" x-cloak>
                        <label for="mk-message" style="display:block; font-size:12.5px; font-weight:600; color:var(--ink2); margin-bottom:7px;">
                            ข้อความที่ต้องการส่ง <span style="color:#d9534f;">*</span>
                        </label>
                        <div class="tp-well tp-input">
                            <textarea id="mk-message" name="message_template" rows="6"
                                      placeholder="พิมพ์ข้อความที่ต้องการส่งถึงผู้ใช้..."
                                      style="width:100%; background:transparent; border:none; outline:none; resize:vertical; color:var(--ink); font-size:14px; line-height:1.7;">{{ old('message_template', $campaign?->message_template) }}</textarea>
                        </div>
                        @error('message_template')
                        <p style="margin:6px 2px 0; font-size:12px; color:#d9534f;">
                            <i class="fas fa-circle-exclamation"></i> {{ $message }}
                        </p>
                        @enderror
                    </div>
                </div>

            </div>

            {{-- ========== คอลัมน์ขวา ========== --}}
            <div style="display:flex; flex-direction:column; gap:18px;">

                {{-- ----- การ์ด: เป้าหมาย ----- --}}
                <div class="tp-card tp-raise" style="padding:24px;">
                    <div class="tp-section-h" style="display:flex; align-items:center; gap:8px; margin-bottom:18px;">
                        <i class="fas fa-bullseye" style="color:var(--deep1);"></i>
                        <span style="font-weight:700; color:var(--ink);">เป้าหมาย</span>
                    </div>

                    <div style="display:flex; flex-direction:column; gap:16px;">

                        {{-- กลุ่มเป้าหมาย --}}
                        <div>
                            <label style="display:block; font-size:12.5px; font-weight:600; color:var(--ink2); margin-bottom:7px;">
                                กลุ่มเป้าหมาย
                            </label>
                            <div class="tp-well tp-input" style="padding:0;">
                                <select name="target_audience"
                                        style="width:100%; background:transparent; border:0; outline:0; padding:11px 14px; color:var(--ink); font-size:14px; cursor:pointer;">
                                    <option value="all" {{ old('target_audience', $campaign?->target_audience) === 'all' ? 'selected' : '' }}>ทุกคนที่เคยดูดวง</option>
                                    <option value="paid" {{ old('target_audience', $campaign?->target_audience) === 'paid' ? 'selected' : '' }}>ลูกค้าจ่ายเงิน</option>
                                    <option value="recent" {{ old('target_audience', $campaign?->target_audience) === 'recent' ? 'selected' : '' }}>ดูดวงภายใน 7 วัน</option>
                                    <option value="new" {{ old('target_audience', $campaign?->target_audience) === 'new' ? 'selected' : '' }}>ผู้ใช้ใหม่ (ดู 1 ครั้ง)</option>
                                </select>
                            </div>
                            @error('target_audience')
                            <p style="margin:6px 2px 0; font-size:12px; color:#d9534f;">
                                <i class="fas fa-circle-exclamation"></i> {{ $message }}
                            </p>
                            @enderror
                        </div>

                        {{-- Platform --}}
                        <div>
                            <label style="display:block; font-size:12.5px; font-weight:600; color:var(--ink2); margin-bottom:7px;">
                                Platform
                            </label>
                            <div class="tp-well tp-input" style="padding:0;">
                                <select name="target_platform"
                                        style="width:100%; background:transparent; border:0; outline:0; padding:11px 14px; color:var(--ink); font-size:14px; cursor:pointer;">
                                    <option value="all" {{ old('target_platform', $campaign?->target_platform) === 'all' ? 'selected' : '' }}>ทุก Platform</option>
                                    <option value="facebook" {{ old('target_platform', $campaign?->target_platform) === 'facebook' ? 'selected' : '' }}>Facebook</option>
                                    <option value="line" {{ old('target_platform', $campaign?->target_platform) === 'line' ? 'selected' : '' }}>LINE</option>
                                </select>
                            </div>
                            @error('target_platform')
                            <p style="margin:6px 2px 0; font-size:12px; color:#d9534f;">
                                <i class="fas fa-circle-exclamation"></i> {{ $message }}
                            </p>
                            @enderror
                        </div>

                        {{-- จำนวนสูงสุดที่ส่ง --}}
                        <div>
                            <label for="mk-limit" style="display:block; font-size:12.5px; font-weight:600; color:var(--ink2); margin-bottom:7px;">
                                จำนวนสูงสุดที่ส่ง
                            </label>
                            <div class="tp-well tp-input">
                                <input type="number" id="mk-limit" name="target_limit" min="1" max="10000"
                                       value="{{ old('target_limit', $campaign?->target_limit) }}"
                                       placeholder="ไม่จำกัด"
                                       style="width:100%; background:transparent; border:none; outline:none; color:var(--ink); font-size:14px;">
                            </div>
                            <p class="tp-muted" style="margin-top:6px; font-size:11.5px;">ปล่อยว่าง = ส่งทุกคนในกลุ่ม</p>
                            @error('target_limit')
                            <p style="margin:6px 2px 0; font-size:12px; color:#d9534f;">
                                <i class="fas fa-circle-exclamation"></i> {{ $message }}
                            </p>
                            @enderror
                        </div>

                    </div>
                </div>

                {{-- ----- การ์ด: ตั้งเวลาส่ง ----- --}}
                <div class="tp-card tp-raise" style="padding:24px;">
                    <div class="tp-section-h" style="display:flex; align-items:center; gap:8px; margin-bottom:18px;">
                        <i class="fas fa-clock" style="color:var(--deep1);"></i>
                        <span style="font-weight:700; color:var(--ink);">ตั้งเวลาส่ง</span>
                    </div>

                    <div style="display:flex; flex-direction:column; gap:16px;">

                        {{-- ประเภท --}}
                        <div>
                            <label style="display:block; font-size:12.5px; font-weight:600; color:var(--ink2); margin-bottom:7px;">
                                ประเภท
                            </label>
                            <div class="tp-well tp-input" style="padding:0;">
                                <select name="schedule_type" x-model="scheduleType"
                                        style="width:100%; background:transparent; border:0; outline:0; padding:11px 14px; color:var(--ink); font-size:14px; cursor:pointer;">
                                    <option value="once" {{ old('schedule_type', $campaign?->schedule_type) === 'once' ? 'selected' : '' }}>ส่งครั้งเดียว</option>
                                    <option value="daily" {{ old('schedule_type', $campaign?->schedule_type) === 'daily' ? 'selected' : '' }}>ส่งทุกวัน (AI สร้างใหม่ทุกวัน)</option>
                                    <option value="weekly" {{ old('schedule_type', $campaign?->schedule_type) === 'weekly' ? 'selected' : '' }}>ส่งทุกสัปดาห์ (AI สร้างใหม่ทุกสัปดาห์)</option>
                                </select>
                            </div>
                            @error('schedule_type')
                            <p style="margin:6px 2px 0; font-size:12px; color:#d9534f;">
                                <i class="fas fa-circle-exclamation"></i> {{ $message }}
                            </p>
                            @enderror
                        </div>

                        {{-- วัน-เวลา --}}
                        <div>
                            <label style="display:block; font-size:12.5px; font-weight:600; color:var(--ink2); margin-bottom:7px;">
                                <span x-text="scheduleType === 'once' ? 'วัน-เวลาที่ส่ง' : 'เริ่มส่งตั้งแต่'">วัน-เวลาที่ส่ง</span>
                            </label>
                            <div class="tp-well tp-input">
                                <input type="datetime-local" name="scheduled_at"
                                       value="{{ old('scheduled_at', $campaign?->scheduled_at?->format('Y-m-d\TH:i')) }}"
                                       style="width:100%; background:transparent; border:none; outline:none; color:var(--ink); font-size:14px;">
                            </div>
                            @error('scheduled_at')
                            <p style="margin:6px 2px 0; font-size:12px; color:#d9534f;">
                                <i class="fas fa-circle-exclamation"></i> {{ $message }}
                            </p>
                            @enderror
                        </div>

                        {{-- คำอธิบายโหมดวน --}}
                        <div x-show="scheduleType !== 'once'" x-cloak class="tp-inset-sm" style="border-radius:12px; padding:13px 15px;">
                            <p x-show="scheduleType === 'daily'" style="font-size:12px; color:var(--ink2); line-height:1.7; margin:0;">
                                <i class="fas fa-rotate" style="color:#5689b8;"></i>
                                จะส่งทุกวันตามเวลาที่ตั้ง และ AI จะสร้างข้อความใหม่ทุกครั้ง (อ้างอิงดวงประจำวัน)
                            </p>
                            <p x-show="scheduleType === 'weekly'" style="font-size:12px; color:var(--ink2); line-height:1.7; margin:0;">
                                <i class="fas fa-rotate" style="color:#5689b8;"></i>
                                จะส่งทุกสัปดาห์ตามเวลาที่ตั้ง และ AI จะสร้างข้อความใหม่ทุกครั้ง
                            </p>
                        </div>

                    </div>
                </div>

                {{-- ----- การ์ด: ปุ่ม action ----- --}}
                <div class="tp-card" style="padding:20px;">
                    <div style="display:flex; flex-direction:column; gap:11px;">
                        <button type="submit" class="tp-btn tp-btn-primary" style="width:100%; justify-content:center;">
                            <i class="fas fa-floppy-disk"></i>&nbsp; {{ $campaign ? 'อัปเดตแคมเปญ' : 'สร้างแคมเปญ' }}
                        </button>
                        @if(Route::has('admin.fortune.marketing.index'))
                        <a href="{{ route('admin.fortune.marketing.index') }}" class="tp-btn" style="width:100%; justify-content:center; text-align:center;">
                            <i class="fas fa-xmark"></i>&nbsp; ยกเลิก
                        </a>
                        @endif
                    </div>
                </div>

            </div>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
    // ===== Alpine component: ฟอร์มแคมเปญการตลาด (create + edit) =====
    // จัดการ toggle AI + สลับโหมดตั้งเวลา + พรีวิว AI ผ่าน AJAX (endpoint/payload เดิม 100%)
    function campaignForm() {
        return {
            // ค่าเริ่มต้นอ้างอิง old() เพื่อให้ state ตรงหลัง validation fail
            useAI: {{ old('use_ai_generate', $campaign?->use_ai_generate ?? true) ? 'true' : 'false' }},
            topic: @js(old('topic', $campaign?->topic ?? '')),
            scheduleType: @js(old('schedule_type', $campaign?->schedule_type ?? 'once')),
            aiPreview: '',
            generating: false,

            // เรียก AI สร้างตัวอย่างข้อความ (AJAX POST ไป endpoint preview เดิม)
            async previewAI() {
                if (this.generating) return;
                this.generating = true;
                this.aiPreview = '';

                try {
                    const response = await fetch(@js(route('admin.fortune.marketing.preview')), {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || @js(csrf_token()),
                        },
                        body: JSON.stringify({
                            topic: this.topic || 'ดูดวงฟรี',
                            ai_prompt: document.querySelector('[name="ai_prompt"]')?.value || '',
                        })
                    });

                    const data = await response.json();
                    if (data.success) {
                        this.aiPreview = data.message;
                    } else {
                        this.aiPreview = '❌ ' + (data.error || 'เกิดข้อผิดพลาด');
                        this.$dispatch('notify', { type: 'error', message: data.error || 'AI สร้างข้อความไม่สำเร็จ' });
                    }
                } catch (e) {
                    this.aiPreview = '❌ ไม่สามารถเชื่อมต่อ AI ได้: ' + e.message;
                    this.$dispatch('notify', { type: 'error', message: 'เชื่อมต่อ AI ไม่ได้: ' + e.message });
                } finally {
                    this.generating = false;
                }
            }
        }
    }
</script>
@endpush
