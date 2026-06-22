{{-- resources/views/admin/fortune/horoscope/form.blade.php --}}
{{-- ฟอร์มสร้าง/แก้ไขแคมเปญดวงรายวัน — ธีม V4 นวลทองคำ (create+edit ร่วมไฟล์) --}}

@extends('layouts.admin-v4')

@section('title', $pageTitle)

@php
    // ค่าคงที่สไตล์ field V4 (อ้าง var ทั้งหมด ห้าม hardcode เทา/ขาว)
    // label ของ field
    $lblStyle = 'display:block; font-size:13px; font-weight:600; color:var(--ink); margin-bottom:6px;';
    // คำอธิบายย่อย
    $hintStyle = 'font-size:12px; color:var(--ink2); margin-top:6px; line-height:1.5;';
    // กล่อง input (.tp-well .tp-input) — input/textarea วางข้างใน transparent
    $fieldInner = 'width:100%; background:transparent; border:0; outline:0; padding:11px 14px; color:var(--ink); font-size:14px;';
    // select ข้างใน well
    $selInner = 'width:100%; background:transparent; border:0; outline:0; padding:11px 14px; color:var(--ink); font-size:14px; cursor:pointer;';
@endphp

@section('content')
<div style="display:flex; flex-direction:column; gap:18px;" x-data="horoscopeCampaignForm()">

    {{-- หัวข้อ --}}
    <div style="display:flex; flex-wrap:wrap; align-items:flex-end; justify-content:space-between; gap:14px;">
        <div>
            <div style="font-size:11px; color:var(--ink2); font-weight:600; letter-spacing:.4px;">หลังบ้าน · ระบบดูดวง · ดวงรายวันอัตโนมัติ</div>
            <h1 class="tp-num" style="font-size:clamp(22px,4vw,28px); font-weight:800; margin:4px 0 0;">🔮 {{ $pageTitle }}</h1>
            <p class="tp-muted" style="margin:6px 0 0; font-size:13px;">ตั้งค่าแพลตฟอร์ม · AI provider · เวลาโพส · prompt template</p>
        </div>
        <a href="{{ route('admin.fortune.horoscope.index') }}" class="tp-btn">
            <i class="fas fa-arrow-left"></i> กลับ
        </a>
    </div>

    {{-- แจ้งข้อผิดพลาด validation --}}
    @if($errors->any())
        <div class="tp-card" style="border-left:4px solid #d9534f;">
            <div style="display:flex; align-items:center; gap:8px; margin-bottom:8px;">
                <i class="fas fa-circle-exclamation" style="color:#d9534f;"></i>
                <span style="font-weight:700; color:#d9534f;">กรุณาแก้ไขข้อผิดพลาด</span>
            </div>
            <ul style="margin:0; padding-left:20px; color:var(--ink2); font-size:13px; line-height:1.7;">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST"
          action="{{ $isEdit ? route('admin.fortune.horoscope.update', $campaign) : route('admin.fortune.horoscope.store') }}">
        @csrf
        @if($isEdit)
            @method('PUT')
        @endif

        <div style="display:flex; flex-direction:column; gap:18px;">

            {{-- ============================================================ --}}
            {{-- Section 1: ข้อมูลทั่วไป --}}
            {{-- ============================================================ --}}
            <div class="tp-card">
                <div class="tp-section-h" style="margin-bottom:16px;">
                    <i class="fas fa-pen-to-square" style="color:var(--accent2);"></i>
                    <span>ข้อมูลทั่วไป</span>
                </div>

                <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(260px,1fr)); gap:16px;">
                    {{-- ชื่อแคมเปญ --}}
                    <div>
                        <label style="{{ $lblStyle }}">ชื่อแคมเปญ <span style="color:#d9534f;">*</span></label>
                        <div class="tp-well tp-input" style="padding:0;">
                            <input type="text" name="name"
                                   value="{{ old('name', $campaign->name) }}"
                                   style="{{ $fieldInner }}"
                                   placeholder="เช่น ดวงรายวัน หน้าเพจหลัก"
                                   required>
                        </div>
                    </div>

                    {{-- คำอธิบาย --}}
                    <div>
                        <label style="{{ $lblStyle }}">คำอธิบาย</label>
                        <div class="tp-well tp-input" style="padding:0;">
                            <input type="text" name="description"
                                   value="{{ old('description', $campaign->description) }}"
                                   style="{{ $fieldInner }}"
                                   placeholder="คำอธิบายสั้นๆ">
                        </div>
                    </div>
                </div>
            </div>

            {{-- ============================================================ --}}
            {{-- Section 2: แพลตฟอร์มที่จะโพส --}}
            {{-- ============================================================ --}}
            <div class="tp-card">
                <div class="tp-section-h" style="margin-bottom:16px;">
                    <i class="fas fa-tower-broadcast" style="color:var(--accent2);"></i>
                    <span>แพลตฟอร์มที่จะโพส</span>
                </div>

                {{-- Toggle: ใช้ Token จากการตั้งค่าดูดวง --}}
                <label class="tp-inset-sm" style="display:flex; align-items:flex-start; gap:12px; cursor:pointer; padding:14px; border-left:3px solid #5689b8; margin-bottom:18px;">
                    <input type="hidden" name="use_fortune_settings_tokens" value="0">
                    <input type="checkbox" name="use_fortune_settings_tokens" value="1"
                           x-model="useFortuneTokens"
                           style="width:18px; height:18px; margin-top:2px; accent-color:#5689b8; cursor:pointer;"
                           {{ old('use_fortune_settings_tokens', $campaign->use_fortune_settings_tokens ?? true) ? 'checked' : '' }}>
                    <div>
                        <span style="font-weight:700; color:var(--ink);">ใช้ Token จากการตั้งค่าดูดวง</span>
                        <p style="{{ $hintStyle }}">ดึง Facebook Page Token / LINE Channel Access Token จากหน้าตั้งค่าระบบดูดวงอัตโนมัติ</p>
                    </div>
                </label>

                <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(300px,1fr)); gap:16px;">
                    {{-- Facebook --}}
                    <div class="tp-inset" style="padding:16px;">
                        <label style="display:flex; align-items:center; gap:10px; cursor:pointer; margin-bottom:14px;">
                            <input type="hidden" name="post_to_facebook" value="0">
                            <input type="checkbox" name="post_to_facebook" value="1"
                                   x-model="postToFacebook"
                                   style="width:18px; height:18px; accent-color:#5689b8; cursor:pointer;"
                                   {{ old('post_to_facebook', $campaign->post_to_facebook) ? 'checked' : '' }}>
                            <span style="font-weight:700; color:var(--ink);">📘 โพสลง Facebook Page</span>
                        </label>

                        <div x-show="postToFacebook && !useFortuneTokens" x-transition style="display:flex; flex-direction:column; gap:12px;">
                            <div>
                                <label style="{{ $lblStyle }}">Facebook Page ID</label>
                                <div class="tp-well tp-input" style="padding:0;">
                                    <input type="text" name="facebook_page_id"
                                           value="{{ old('facebook_page_id', $campaign->facebook_page_id) }}"
                                           style="{{ $fieldInner }}"
                                           placeholder="123456789">
                                </div>
                            </div>
                            <div>
                                <label style="{{ $lblStyle }}">Page Access Token</label>
                                <div class="tp-well tp-input" style="padding:0;">
                                    <input type="password" name="facebook_page_token"
                                           value="{{ old('facebook_page_token') }}"
                                           style="{{ $fieldInner }}"
                                           placeholder="{{ $isEdit && $campaign->facebook_page_token ? '••••••• (มีค่าอยู่แล้ว)' : 'EAAx...' }}">
                                </div>
                            </div>
                        </div>
                        <div x-show="postToFacebook && useFortuneTokens" x-transition>
                            <p style="{{ $hintStyle }} color:#5689b8;">ℹ️ จะใช้ Token จากหน้าตั้งค่าดูดวงอัตโนมัติ</p>
                        </div>
                    </div>

                    {{-- LINE --}}
                    <div class="tp-inset" style="padding:16px;">
                        <label style="display:flex; align-items:center; gap:10px; cursor:pointer; margin-bottom:14px;">
                            <input type="hidden" name="post_to_line" value="0">
                            <input type="checkbox" name="post_to_line" value="1"
                                   x-model="postToLine"
                                   style="width:18px; height:18px; accent-color:#5aa07e; cursor:pointer;"
                                   {{ old('post_to_line', $campaign->post_to_line) ? 'checked' : '' }}>
                            <span style="font-weight:700; color:var(--ink);">💚 โพสลง LINE OA (Broadcast)</span>
                        </label>

                        {{-- LINE Quota Warning --}}
                        <div x-show="postToLine" x-transition
                             class="tp-inset-sm" style="padding:12px; border-left:3px solid #e0a52e; margin-bottom:14px;">
                            <div style="display:flex; align-items:flex-start; gap:8px;">
                                <span style="color:#e0a52e; font-size:16px;">⚠️</span>
                                <div style="font-size:12px;">
                                    <p style="font-weight:700; color:#e0a52e; margin:0 0 4px;">ข้อจำกัดโควต้า LINE Broadcast</p>
                                    <ul style="margin:0; padding-left:18px; color:var(--ink2); line-height:1.7;">
                                        <li><strong>แพลนฟรี</strong>: ส่งได้ 500 ข้อความ/เดือน</li>
                                        <li><strong>Light</strong>: 5,000 ข้อความ/เดือน</li>
                                        <li><strong>Standard</strong>: 30,000 ข้อความ/เดือน</li>
                                        <li>1 broadcast = จำนวนผู้ติดตาม × 1 ข้อความ</li>
                                    </ul>
                                    @if($isEdit && $campaign->post_to_line)
                                        <div style="margin-top:8px; padding-top:8px; border-top:1px solid var(--sd);">
                                            <p style="font-weight:700; color:var(--ink); margin:0;">
                                                📊 ใช้ไปเดือนนี้: {{ $campaign->line_used_this_month }}/{{ $campaign->line_monthly_quota }}
                                                <span style="color:{{ $campaign->isLineQuotaLow() ? '#d9534f' : '#5aa07e' }};">
                                                    (เหลือ {{ $campaign->line_quota_remaining }})
                                                </span>
                                            </p>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>

                        {{-- LINE Quota Settings --}}
                        <div x-show="postToLine" x-transition style="display:grid; grid-template-columns:1fr 1fr; gap:10px; margin-bottom:14px;">
                            <div>
                                <label style="{{ $lblStyle }}">โควต้าต่อเดือน</label>
                                <div class="tp-well tp-input" style="padding:0;">
                                    <input type="number" name="line_monthly_quota"
                                           value="{{ old('line_monthly_quota', $campaign->line_monthly_quota ?? 500) }}"
                                           style="{{ $fieldInner }}"
                                           min="0" step="1" placeholder="500">
                                </div>
                            </div>
                            <div>
                                <label style="{{ $lblStyle }}">เตือนเมื่อเหลือน้อยกว่า</label>
                                <div class="tp-well tp-input" style="padding:0;">
                                    <input type="number" name="line_quota_warning_threshold"
                                           value="{{ old('line_quota_warning_threshold', $campaign->line_quota_warning_threshold ?? 50) }}"
                                           style="{{ $fieldInner }}"
                                           min="0" step="1" placeholder="50">
                                </div>
                            </div>
                        </div>

                        <div x-show="postToLine && !useFortuneTokens" x-transition>
                            <label style="{{ $lblStyle }}">LINE Channel Access Token</label>
                            <div class="tp-well tp-input" style="padding:0;">
                                <input type="password" name="line_channel_access_token"
                                       value="{{ old('line_channel_access_token') }}"
                                       style="{{ $fieldInner }}"
                                       placeholder="{{ $isEdit && $campaign->line_channel_access_token ? '••••••• (มีค่าอยู่แล้ว)' : 'Token...' }}">
                            </div>
                        </div>
                        <div x-show="postToLine && useFortuneTokens" x-transition>
                            <p style="{{ $hintStyle }} color:#5aa07e;">ℹ️ จะใช้ Token จากหน้าตั้งค่าดูดวงอัตโนมัติ</p>
                        </div>
                    </div>
                </div>
            </div>

            {{-- ============================================================ --}}
            {{-- Section 3: ตั้งค่า AI Provider --}}
            {{-- ============================================================ --}}
            <div class="tp-card">
                <div class="tp-section-h" style="margin-bottom:16px;">
                    <i class="fas fa-robot" style="color:var(--accent2);"></i>
                    <span>ตั้งค่า AI Provider</span>
                </div>

                <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(260px,1fr)); gap:16px;">
                    {{-- AI Text Provider --}}
                    <div>
                        <label style="{{ $lblStyle }}">AI สร้างคำทำนาย</label>
                        <div class="tp-well tp-input" style="padding:0;">
                            <select name="ai_text_provider" style="{{ $selInner }}">
                                <option value="auto" {{ old('ai_text_provider', $campaign->ai_text_provider) === 'auto' ? 'selected' : '' }}>🔄 อัตโนมัติ (ลอง key ทุกตัว)</option>
                                <option value="openai" {{ old('ai_text_provider', $campaign->ai_text_provider) === 'openai' ? 'selected' : '' }}>OpenAI (GPT)</option>
                                <option value="gemini" {{ old('ai_text_provider', $campaign->ai_text_provider) === 'gemini' ? 'selected' : '' }}>Google Gemini</option>
                                <option value="typhoon" {{ old('ai_text_provider', $campaign->ai_text_provider) === 'typhoon' ? 'selected' : '' }}>Typhoon (ไทย)</option>
                                <option value="deepseek" {{ old('ai_text_provider', $campaign->ai_text_provider) === 'deepseek' ? 'selected' : '' }}>DeepSeek</option>
                            </select>
                        </div>
                        <p style="{{ $hintStyle }}">"อัตโนมัติ" จะ rotate key ตามที่ตั้งค่าในระบบ FortuneAI</p>
                    </div>

                    {{-- AI Image Provider --}}
                    <div>
                        <label style="{{ $lblStyle }}">AI สร้างรูปภาพ</label>
                        <div class="tp-well tp-input" style="padding:0;">
                            <select name="ai_image_provider" style="{{ $selInner }}">
                                <option value="pollinations" {{ old('ai_image_provider', $campaign->ai_image_provider) === 'pollinations' ? 'selected' : '' }}>🆓 Pollinations (ฟรี)</option>
                                <option value="openai" {{ old('ai_image_provider', $campaign->ai_image_provider) === 'openai' ? 'selected' : '' }}>OpenAI DALL-E</option>
                                <option value="huggingface" {{ old('ai_image_provider', $campaign->ai_image_provider) === 'huggingface' ? 'selected' : '' }}>🤗 Hugging Face (ฟรี)</option>
                            </select>
                        </div>
                    </div>

                    {{-- Image Model --}}
                    <div>
                        <label style="{{ $lblStyle }}">Image Model</label>
                        <div class="tp-well tp-input" style="padding:0;">
                            <input type="text" name="ai_image_model"
                                   value="{{ old('ai_image_model', $campaign->ai_image_model ?? 'flux') }}"
                                   style="{{ $fieldInner }}"
                                   placeholder="flux">
                        </div>
                    </div>

                    {{-- Image Size --}}
                    <div>
                        <label style="{{ $lblStyle }}">ขนาดรูป</label>
                        <div class="tp-well tp-input" style="padding:0;">
                            <select name="image_size" style="{{ $selInner }}">
                                <option value="1024x1024" {{ old('image_size', $campaign->image_size) === '1024x1024' ? 'selected' : '' }}>1024x1024 (สี่เหลี่ยม)</option>
                                <option value="1024x768" {{ old('image_size', $campaign->image_size) === '1024x768' ? 'selected' : '' }}>1024x768 (แนวนอน)</option>
                                <option value="768x1024" {{ old('image_size', $campaign->image_size) === '768x1024' ? 'selected' : '' }}>768x1024 (แนวตั้ง)</option>
                            </select>
                        </div>
                    </div>

                    {{-- Image Style --}}
                    <div style="grid-column:1/-1;">
                        <label style="{{ $lblStyle }}">สไตล์รูปภาพ</label>
                        <div class="tp-well tp-input" style="padding:0;">
                            <input type="text" name="image_style"
                                   value="{{ old('image_style', $campaign->image_style) }}"
                                   style="{{ $fieldInner }}"
                                   placeholder="เช่น thai-zodiac, mystical, anime">
                        </div>
                    </div>
                </div>

                {{-- ตัวเลือก --}}
                <div style="margin-top:18px; display:flex; flex-wrap:wrap; gap:20px;">
                    <label style="display:flex; align-items:center; gap:8px; cursor:pointer;">
                        <input type="hidden" name="include_image" value="0">
                        <input type="checkbox" name="include_image" value="1"
                               style="width:18px; height:18px; accent-color:var(--accent2); cursor:pointer;"
                               {{ old('include_image', $campaign->include_image ?? true) ? 'checked' : '' }}>
                        <span style="color:var(--ink);">🖼 สร้างรูปภาพ AI</span>
                    </label>

                    <label style="display:flex; align-items:center; gap:8px; cursor:pointer;">
                        <input type="hidden" name="include_lucky_info" value="0">
                        <input type="checkbox" name="include_lucky_info" value="1"
                               style="width:18px; height:18px; accent-color:var(--accent2); cursor:pointer;"
                               {{ old('include_lucky_info', $campaign->include_lucky_info ?? true) ? 'checked' : '' }}>
                        <span style="color:var(--ink);">🍀 แทรกสีมงคล/เลขมงคล/ทิศมงคล</span>
                    </label>
                </div>
            </div>

            {{-- ============================================================ --}}
            {{-- Section 4: เวลาและกำหนดการ --}}
            {{-- ============================================================ --}}
            <div class="tp-card">
                <div class="tp-section-h" style="margin-bottom:16px;">
                    <i class="fas fa-clock" style="color:var(--accent2);"></i>
                    <span>เวลาและกำหนดการ</span>
                </div>

                <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(220px,1fr)); gap:16px;">
                    {{-- เวลาโพส --}}
                    <div>
                        <label style="{{ $lblStyle }}">เวลาโพส</label>
                        <div class="tp-well tp-input" style="padding:0;">
                            <input type="time" name="schedule_time"
                                   value="{{ old('schedule_time', $campaign->schedule_time ?? '06:00') }}"
                                   style="{{ $fieldInner }}">
                        </div>
                    </div>

                    {{-- Timezone --}}
                    <div>
                        <label style="{{ $lblStyle }}">Timezone</label>
                        <div class="tp-well tp-input" style="padding:0;">
                            <select name="timezone" style="{{ $selInner }}">
                                <option value="Asia/Bangkok" {{ old('timezone', $campaign->timezone) === 'Asia/Bangkok' ? 'selected' : '' }}>Asia/Bangkok (ICT +7)</option>
                                <option value="UTC" {{ old('timezone', $campaign->timezone) === 'UTC' ? 'selected' : '' }}>UTC</option>
                            </select>
                        </div>
                    </div>

                    {{-- Post Format --}}
                    <div>
                        <label style="{{ $lblStyle }}">รูปแบบโพส</label>
                        <div class="tp-well tp-input" style="padding:0;">
                            <select name="post_format" style="{{ $selInner }}">
                                <option value="combined" {{ old('post_format', $campaign->post_format) === 'combined' ? 'selected' : '' }}>รวม 7 วันเป็น 1 โพส</option>
                                <option value="single" {{ old('post_format', $campaign->post_format) === 'single' ? 'selected' : '' }}>แยก 7 โพส (แต่ละวันเกิด)</option>
                            </select>
                        </div>
                    </div>
                </div>

                {{-- วันที่ใช้งาน --}}
                <div style="margin-top:18px;">
                    <label style="{{ $lblStyle }}">วันที่โพส (เว้นว่าง = ทุกวัน)</label>
                    <div style="display:flex; flex-wrap:wrap; gap:10px;">
                        @php
                            $activeDays = old('active_days', $campaign->active_days ?? []);
                        @endphp
                        @foreach(\App\Models\FortuneHoroscopeCampaign::THAI_DAYS as $idx => $dayName)
                            <label class="tp-inset-sm" style="display:flex; align-items:center; gap:8px; cursor:pointer; padding:9px 14px;">
                                <input type="checkbox" name="active_days[]" value="{{ $idx }}"
                                       style="width:16px; height:16px; accent-color:var(--accent2); cursor:pointer;"
                                       {{ is_array($activeDays) && in_array($idx, $activeDays) ? 'checked' : '' }}>
                                <span style="font-size:13px; color:var(--ink);">{{ $dayName }}</span>
                            </label>
                        @endforeach
                    </div>
                </div>

                {{-- วันเกิดที่สร้าง --}}
                <div style="margin-top:18px;">
                    <label style="{{ $lblStyle }}">วันเกิดที่สร้างคำทำนาย (เว้นว่าง = ทุกวันเกิด)</label>
                    <div style="display:flex; flex-wrap:wrap; gap:10px;">
                        @php
                            $targetBirthDays = old('target_birth_days', $campaign->target_birth_days ?? []);
                        @endphp
                        @foreach(\App\Models\FortuneHoroscopeCampaign::THAI_DAYS as $idx => $dayName)
                            <label class="tp-inset-sm" style="display:flex; align-items:center; gap:8px; cursor:pointer; padding:9px 14px;">
                                <input type="checkbox" name="target_birth_days[]" value="{{ $idx }}"
                                       style="width:16px; height:16px; accent-color:var(--accent2); cursor:pointer;"
                                       {{ is_array($targetBirthDays) && in_array($idx, $targetBirthDays) ? 'checked' : '' }}>
                                <span style="font-size:13px; color:var(--ink);">{{ $dayName }}</span>
                            </label>
                        @endforeach
                    </div>
                </div>
            </div>

            {{-- ============================================================ --}}
            {{-- Section 5: การตลาดอัจฉริยะ (Smart Marketing) --}}
            {{-- ============================================================ --}}
            <div class="tp-card">
                <div class="tp-section-h" style="margin-bottom:6px;">
                    <i class="fas fa-rocket" style="color:var(--accent2);"></i>
                    <span>การตลาดอัจฉริยะ (Smart Marketing)</span>
                </div>
                <p style="{{ $hintStyle }} margin-bottom:18px;">ระบบจะใช้ AI สร้าง hashtags, CTA, engagement hooks อัตโนมัติทุกโพส เพื่อเพิ่ม reach และ engagement</p>

                {{-- Page/Brand Info --}}
                <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(260px,1fr)); gap:16px; margin-bottom:18px;">
                    <div>
                        <label style="{{ $lblStyle }}">ชื่อเพจ/แบรนด์</label>
                        <div class="tp-well tp-input" style="padding:0;">
                            <input type="text" name="page_name"
                                   value="{{ old('page_name', $campaign->page_name) }}"
                                   style="{{ $fieldInner }}"
                                   placeholder="เช่น แม่หมอจันทรา">
                        </div>
                        <p style="{{ $hintStyle }}">ใช้สร้าง #hashtag แบรนด์อัตโนมัติ</p>
                    </div>
                    <div>
                        <label style="{{ $lblStyle }}">@mention เพจ (สำหรับ CTA)</label>
                        <div class="tp-well tp-input" style="padding:0;">
                            <input type="text" name="page_mention"
                                   value="{{ old('page_mention', $campaign->page_mention) }}"
                                   style="{{ $fieldInner }}"
                                   placeholder="เช่น @แม่หมอจันทรา">
                        </div>
                    </div>
                </div>

                {{-- Toggle Options --}}
                <div style="display:flex; flex-direction:column; gap:14px; margin-bottom:18px;">
                    {{-- Auto Hashtags --}}
                    <div class="tp-inset" style="padding:16px;">
                        <label style="display:flex; align-items:flex-start; gap:12px; cursor:pointer;">
                            <input type="hidden" name="enable_auto_hashtags" value="0">
                            <input type="checkbox" name="enable_auto_hashtags" value="1"
                                   x-model="enableAutoHashtags"
                                   style="width:18px; height:18px; margin-top:2px; accent-color:var(--accent2); cursor:pointer;"
                                   {{ old('enable_auto_hashtags', $campaign->enable_auto_hashtags ?? true) ? 'checked' : '' }}>
                            <div>
                                <span style="font-weight:700; color:var(--ink);">#️⃣ Smart Hashtags อัตโนมัติ</span>
                                <p style="{{ $hintStyle }}">AI สร้าง hashtags อัจฉริยะตามวัน/เดือน/เทรนด์ เช่น #ดวงรายวัน #คนเกิดวันจันทร์ #ดวง2569</p>
                            </div>
                        </label>

                        <div x-show="enableAutoHashtags" x-transition style="margin-top:14px;">
                            <label style="{{ $lblStyle }}">Custom Hashtags เพิ่มเติม (ใส่ทุกโพส)</label>
                            <div class="tp-well tp-input" style="padding:0;">
                                <textarea name="custom_hashtags" rows="2"
                                          style="{{ $fieldInner }} resize:vertical;"
                                          placeholder="#แม่หมอจันทรา #ดูดวงออนไลน์ #หมอดูAI">{{ old('custom_hashtags', $campaign->custom_hashtags) }}</textarea>
                            </div>
                            <p style="{{ $hintStyle }}">คั่นด้วยช่องว่างหรือเครื่องหมาย , (จะเติม # ให้อัตโนมัติ)</p>
                        </div>
                    </div>

                    {{-- Engagement Hooks --}}
                    <div class="tp-inset" style="padding:16px;">
                        <label style="display:flex; align-items:flex-start; gap:12px; cursor:pointer;">
                            <input type="hidden" name="enable_engagement_hooks" value="0">
                            <input type="checkbox" name="enable_engagement_hooks" value="1"
                                   style="width:18px; height:18px; margin-top:2px; accent-color:var(--accent2); cursor:pointer;"
                                   {{ old('enable_engagement_hooks', $campaign->enable_engagement_hooks ?? true) ? 'checked' : '' }}>
                            <div>
                                <span style="font-weight:700; color:var(--ink);">💬 Engagement Hooks (ข้อความกระตุ้น)</span>
                                <p style="{{ $hintStyle }}">เพิ่มข้อความชวนคอมเมนต์/แชร์/แท็กเพื่อน เช่น "คนเกิดวันจันทร์ คอมเมนต์บอกหน่อย ตรงไหม?"</p>
                            </div>
                        </label>
                    </div>

                    {{-- CTA --}}
                    <div class="tp-inset" style="padding:16px;">
                        <label style="display:flex; align-items:flex-start; gap:12px; cursor:pointer;">
                            <input type="hidden" name="enable_cta" value="0">
                            <input type="checkbox" name="enable_cta" value="1"
                                   x-model="enableCta"
                                   style="width:18px; height:18px; margin-top:2px; accent-color:var(--accent2); cursor:pointer;"
                                   {{ old('enable_cta', $campaign->enable_cta ?? true) ? 'checked' : '' }}>
                            <div>
                                <span style="font-weight:700; color:var(--ink);">📢 Call-to-Action (CTA)</span>
                                <p style="{{ $hintStyle }}">เพิ่มข้อความชวนทักมาดูดวงส่วนตัว เช่น "อยากรู้ดวงละเอียดกว่านี้? ทักมาเลย"</p>
                            </div>
                        </label>

                        <div x-show="enableCta" x-transition style="margin-top:14px;">
                            <label style="{{ $lblStyle }}">ข้อความ CTA กำหนดเอง (เว้นว่าง = ใช้ default)</label>
                            <div class="tp-well tp-input" style="padding:0;">
                                <textarea name="cta_text" rows="2"
                                          style="{{ $fieldInner }} resize:vertical;"
                                          placeholder="🔮 อยากรู้ดวงละเอียดกว่านี้? ทักมาเลย @เพจ">{{ old('cta_text', $campaign->cta_text) }}</textarea>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Preview Smart Tags Example --}}
                <div class="tp-inset-sm" style="padding:14px; border-left:3px solid var(--accent2);">
                    <p style="font-weight:700; color:var(--accent2); margin:0 0 6px; font-size:13px;">🔮 ตัวอย่าง Hashtags ที่จะถูกสร้างอัตโนมัติ:</p>
                    <p style="font-size:12px; color:var(--ink2); line-height:1.7; margin:0;">
                        #ดวงรายวัน #ดูดวง #โหราศาสตร์ไทย #หมอดู #คนเกิดวันจันทร์ #ดวงวันจันทร์ #วันศุกร์
                        #ดวงกุมภาพันธ์ #ดวง2569 #ดวงดี #โชคลาภ #สีมงคล #ดูดวงฟรี #TGIF
                    </p>
                    <p style="font-size:12px; color:var(--ink2); margin:8px 0 0;">* Hashtags จะเปลี่ยนตามวัน/เดือน/เทรนด์อัตโนมัติทุกโพส (สูงสุด 30 tags)</p>
                </div>
            </div>

            {{-- ============================================================ --}}
            {{-- Section 6: Prompt Templates --}}
            {{-- ============================================================ --}}
            <div class="tp-card">
                <div class="tp-section-h" style="margin-bottom:16px;">
                    <i class="fas fa-file-lines" style="color:var(--accent2);"></i>
                    <span>Prompt Templates</span>
                </div>

                {{-- Text Prompt --}}
                <div style="margin-bottom:18px;">
                    <label style="{{ $lblStyle }}">Prompt สร้างคำทำนาย (Text)</label>
                    <div class="tp-well tp-input" style="padding:0;">
                        <textarea name="text_prompt_template" rows="8"
                                  style="{{ $fieldInner }} font-family:monospace; resize:vertical;"
                                  placeholder="สร้างคำทำนายดวงรายวัน...">{{ old('text_prompt_template', $campaign->text_prompt_template) }}</textarea>
                    </div>
                    <div class="tp-inset-sm" style="margin-top:10px; padding:12px; font-size:12px; color:var(--ink2); line-height:1.8;">
                        <p style="font-weight:700; margin:0 0 4px; color:var(--ink);">ตัวแปรที่ใช้ได้:</p>
                        <code style="color:var(--accent2);">{target_date}</code> วันที่เป้าหมาย,
                        <code style="color:var(--accent2);">{birth_day_name}</code> ชื่อวันเกิด,
                        <code style="color:var(--accent2);">{main_planet}</code> ดาวประจำวัน,
                        <code style="color:var(--accent2);">{element}</code> ธาตุ,
                        <code style="color:var(--accent2);">{lucky_color}</code> สีมงคล,
                        <code style="color:var(--accent2);">{friend_planets}</code> ดาวมิตร,
                        <code style="color:var(--accent2);">{enemy_planets}</code> ดาวศัตรู,
                        <code style="color:var(--accent2);">{planet_positions}</code> ตำแหน่งดาว
                    </div>
                </div>

                {{-- Image Prompt --}}
                <div style="margin-bottom:18px;">
                    <label style="{{ $lblStyle }}">Prompt สร้างรูปภาพ (Image)</label>
                    <div class="tp-well tp-input" style="padding:0;">
                        <textarea name="image_prompt_template" rows="4"
                                  style="{{ $fieldInner }} font-family:monospace; resize:vertical;"
                                  placeholder="Thai astrology zodiac card...">{{ old('image_prompt_template', $campaign->image_prompt_template) }}</textarea>
                    </div>
                    <div class="tp-inset-sm" style="margin-top:10px; padding:12px; font-size:12px; color:var(--ink2); line-height:1.8;">
                        <p style="font-weight:700; margin:0 0 4px; color:var(--ink);">ตัวแปรที่ใช้ได้:</p>
                        <code style="color:var(--accent2);">{birth_day_name}</code>,
                        <code style="color:var(--accent2);">{main_planet}</code>,
                        <code style="color:var(--accent2);">{element}</code>,
                        <code style="color:var(--accent2);">{lucky_color}</code>,
                        <code style="color:var(--accent2);">{image_style}</code>
                    </div>
                </div>

                {{-- Post Header/Footer --}}
                <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(260px,1fr)); gap:16px;">
                    <div>
                        <label style="{{ $lblStyle }}">Header โพส</label>
                        <div class="tp-well tp-input" style="padding:0;">
                            <textarea name="post_header_template" rows="3"
                                      style="{{ $fieldInner }} resize:vertical;"
                                      placeholder="🔮✨ ดวงรายวัน {target_date} ✨🔮">{{ old('post_header_template', $campaign->post_header_template) }}</textarea>
                        </div>
                    </div>
                    <div>
                        <label style="{{ $lblStyle }}">Footer โพส</label>
                        <div class="tp-well tp-input" style="padding:0;">
                            <textarea name="post_footer_template" rows="3"
                                      style="{{ $fieldInner }} resize:vertical;"
                                      placeholder="#ดวงรายวัน #ดูดวง">{{ old('post_footer_template', $campaign->post_footer_template) }}</textarea>
                        </div>
                    </div>
                </div>
            </div>

            {{-- ============================================================ --}}
            {{-- ปุ่มบันทึก --}}
            {{-- ============================================================ --}}
            <div style="display:flex; flex-wrap:wrap; justify-content:flex-end; gap:12px;">
                <a href="{{ route('admin.fortune.horoscope.index') }}" class="tp-btn">ยกเลิก</a>
                <button type="submit" class="tp-btn tp-btn-primary">
                    {{ $isEdit ? '💾 บันทึกการแก้ไข' : '✨ สร้างแคมเปญ' }}
                </button>
            </div>

        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
// state ของฟอร์มแคมเปญดวงรายวัน — toggle แสดง/ซ่อนช่อง token + section ย่อย
function horoscopeCampaignForm() {
    return {
        postToFacebook: {{ old('post_to_facebook', $campaign->post_to_facebook) ? 'true' : 'false' }},
        postToLine: {{ old('post_to_line', $campaign->post_to_line) ? 'true' : 'false' }},
        useFortuneTokens: {{ old('use_fortune_settings_tokens', $campaign->use_fortune_settings_tokens ?? true) ? 'true' : 'false' }},
        enableAutoHashtags: {{ old('enable_auto_hashtags', $campaign->enable_auto_hashtags ?? true) ? 'true' : 'false' }},
        enableCta: {{ old('enable_cta', $campaign->enable_cta ?? true) ? 'true' : 'false' }},
    };
}
</script>
@endpush
