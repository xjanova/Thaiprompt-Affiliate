{{--
 | ตั้งค่าระบบตลาดสด (admin.fresh-market.settings) — ธีม V4
 | ตัวแปรจาก Admin\FreshMarketController@settings: $settings (FreshMarketSetting), $lineSecretMasked, $lineTokenMasked,
 |   $marketSettings [pending_expiry_minutes, auto_complete_hours, auto_approve_sellers, rider_dispatch_on_accept, referral_fee_amount,
 |                    line_basic_id, default_line_stock, max_seller_gp_debt]
 | ฟอร์มเดียว PUT settings.update — ชื่อฟิลด์ทั้งหมดตรงกับ validate ใน updateSettings()
 | 🔐 line_channel_secret / line_channel_access_token ห้ามใส่ค่าจริงใน value (เว้นว่าง = ใช้ค่าเดิม) — แสดงแค่ค่าแบบปิดบัง
 | checkbox ทุกตัวใช้ hidden 0 + checkbox 1 · ค่าตลาดส่งเป็น market[<key>]
--}}
@extends('layouts.admin-v4')

@section('title', 'ตั้งค่าตลาดสด')

@section('content')
@include('admin.riders.partials.v4-kit')
@php
    $flexColor = $settings->line_flex_primary_color ?: '#06C755';
    $menuLabels = $settings->menu_button_labels ?? [];
    $menuDefaults = [
        'buy' => ['🛒 ซื้อของ', 'ปุ่มซื้อของ'],
        'sell' => ['🏷️ ลงขาย', 'ปุ่มลงขาย'],
        'rider' => ['🏍️ ไรเดอร์', 'ปุ่มไรเดอร์'],
        'chat_ai' => ['💬 คุยกับ AI', 'ปุ่มคุยกับ AI'],
        'help' => ['📋 ช่วยเหลือ', 'ปุ่มช่วยเหลือ'],
    ];
    $dataToggles = [
        ['ai_can_access_listings', 'รายการสินค้า', true],
        ['ai_can_access_pricing', 'ราคาสินค้า', true],
        ['ai_can_access_orders', 'คำสั่งซื้อ', false],
        ['ai_can_access_user_profile', 'โปรไฟล์ผู้ใช้', false],
        ['ai_can_access_sellers', 'ข้อมูลผู้ขาย', false],
    ];
    $featureToggles = [
        ['escrow_enabled', 'ระบบพักเงิน (Escrow)', 'ถือเงินผู้ซื้อไว้จนผู้ซื้อยืนยันรับของ แล้วค่อยโอนให้ร้าน', false],
        ['cod_enabled', 'เก็บเงินปลายทาง (COD)', 'ผู้ซื้อจ่ายเงินสดตอนรับของ — ร้านรับเงินเอง ค่า GP ตั้งเป็นยอดค้าง', false],
        ['rider_enabled', 'ส่งด้วยไรเดอร์', 'ผู้ซื้อเลือกให้ไรเดอร์ส่งได้ (ค่าส่งตามหน้า "ตั้งค่าค่าส่ง")', false],
        ['mlm_commission_enabled', 'ค่าแนะนำสายงาน', 'คำนวณค่าแนะนำเมื่อออเดอร์เสร็จสิ้น', false],
        ['cashback_enabled', 'แคชแบ็คผู้ซื้อ', 'คืนเงินเข้า Wallet ผู้ซื้อตามที่ร้านตั้งไว้', false],
    ];
    $marketFields = [
        ['pending_expiry_minutes', 'ยกเลิกออเดอร์ที่ร้านไม่รับอัตโนมัติหลัง', 'นาที', 'number', 5, 1440, 1],
        ['auto_complete_hours', 'ปิดออเดอร์ที่ส่งถึงแล้วอัตโนมัติหลัง', 'ชั่วโมง', 'number', 1, 720, 1],
        ['referral_fee_amount', 'ค่าแนะนำร้านใหม่ต่อร้าน', 'บาท', 'number', 0, 1000, 0.01],
        ['max_seller_gp_debt', 'ค่า GP ค้างสูงสุดก่อนงดรับ COD', 'บาท', 'number', 0, 100000, 0.01],
        ['default_line_stock', 'จำนวนสต็อกเริ่มต้นเมื่อลงขายผ่าน LINE', 'ชิ้น', 'number', 1, 10000, 1],
        ['line_basic_id', 'LINE Basic ID (สำหรับปุ่มแอดไลน์)', 'เช่น @taladsod', 'text', null, null, null],
    ];
    $marketToggles = [
        ['auto_approve_sellers', 'อนุมัติร้านใหม่อัตโนมัติ', 'ปิด = สินค้าของร้านจะไม่แสดงจนกว่าแอดมินจะกด "ยืนยันร้าน"'],
        ['rider_dispatch_on_accept', 'เรียกไรเดอร์ทันทีที่ร้านรับออเดอร์', 'ปิด = เรียกไรเดอร์เมื่อร้านกด "พร้อมส่ง"'],
    ];
    try {
        $currentGpRate = app(\App\Services\Pricing\PricingEngine::class)->gpRateForFreshListing(new \App\Models\FreshMarketListing);
    } catch (\Throwable) {
        $currentGpRate = null;
    }
    $gpOverride = \App\Models\Setting::get('pricing.fresh_market_gp_rate');
    // ตรวจแค่ว่ามีค่าหรือไม่ — ไม่พิมพ์ค่าจริงลงหน้าเว็บ (maskSecret คืนข้อความเสมอ จึงใช้ตัดสินไม่ได้)
    $hasLineSecret = filled($settings->line_channel_secret ?? null);
    $hasLineToken = filled($settings->line_channel_access_token ?? null);
    $tabs = [
        'fees' => ['fa-percent', 'ค่าธรรมเนียม'],
        'market' => ['fa-store', 'ระบบตลาด'],
        'toggles' => ['fa-toggle-on', 'เปิด/ปิดฟีเจอร์'],
        'line' => ['fa-comment-dots', 'LINE OA'],
        'ai' => ['fa-robot', 'AI บอท'],
        'search' => ['fa-location-crosshairs', 'การค้นหา'],
        'branding' => ['fa-palette', 'แบรนด์'],
    ];
@endphp
<div x-data="{ tab: (location.hash || '').replace('#', '') || 'fees', saving: false }"
     x-init="$watch('tab', (value) => history.replaceState(null, '', '#' + value))"
     style="display:flex; flex-direction:column; gap:18px;">

    {{-- ===== หัวเรื่อง ===== --}}
    <div style="display:flex; flex-wrap:wrap; align-items:flex-end; justify-content:space-between; gap:14px;">
        <div style="display:flex; align-items:center; gap:14px;">
            <a href="{{ route('admin.fresh-market.dashboard') }}" class="tp-icon-btn" title="กลับแดชบอร์ดตลาดสด"><i class="fas fa-arrow-left"></i></a>
            <div>
                <div style="font-size:11px; color:var(--ink2); font-weight:600; letter-spacing:.4px;">หลังบ้าน · ตลาดสด · ตั้งค่า</div>
                <h1 class="tp-num" style="font-size:clamp(22px,4vw,28px); font-weight:800; margin:4px 0 0;">ตั้งค่าตลาดสด ⚙️</h1>
                <div style="font-size:12.5px; color:var(--ink2); margin-top:4px;">ค่าธรรมเนียม ระบบออเดอร์ LINE OA และบอท AI — บันทึกครั้งเดียวทุกแท็บ</div>
            </div>
        </div>
        <a href="{{ route('admin.fresh-market.test-line') }}" class="tp-btn tp-btn-sm"><i class="fas fa-vial"></i> ทดสอบ LINE</a>
    </div>

    @include('admin.riders.partials.flash')

    {{-- ===== แท็บ ===== --}}
    <div class="tp-card" style="padding:8px; display:flex; gap:6px; overflow-x:auto;">
        @foreach ($tabs as $tabKey => [$tabIcon, $tabLabel])
            <button type="button" class="tp-btn tp-btn-sm" style="flex:none;"
                    :class="tab === '{{ $tabKey }}' ? 'tp-btn-primary' : ''" @click="tab = '{{ $tabKey }}'">
                <i class="fas {{ $tabIcon }}"></i> {{ $tabLabel }}
            </button>
        @endforeach
    </div>

    {{-- novalidate: ช่องในแท็บที่ซ่อนอยู่ถ้าติด min/required เบราว์เซอร์จะกันส่งแบบเงียบๆ — ให้เซิร์ฟเวอร์ตรวจแล้วแจ้งเป็นภาษาไทยแทน --}}
    <form method="POST" action="{{ route('admin.fresh-market.settings.update') }}" novalidate @submit="saving = true" style="display:flex; flex-direction:column; gap:18px;">
        @csrf
        @method('PUT')

        {{-- ================= ค่าธรรมเนียม ================= --}}
        <section class="tp-card" style="padding:20px;" x-show="tab === 'fees'">
            <div class="tp-section-h" style="margin-bottom:4px;"><i class="fas fa-percent"></i> ค่าธรรมเนียมร้านค้า</div>
            <div style="font-size:12.5px; color:var(--ink2); margin-bottom:14px;">
                ค่า GP ที่ใช้กับออเดอร์ใหม่ตอนนี้ <b class="tp-num" style="color:var(--ink);">{{ $currentGpRate !== null ? rtrim(rtrim(number_format($currentGpRate, 2), '0'), '.').'%' : '-' }}</b>
                @if ($gpOverride !== null && $gpOverride !== '')
                    — ใช้ค่าจากหน้า "ส่วนแบ่งรายได้ & GP" ({{ $gpOverride }}%) แทนช่องด้านล่าง
                @endif
                (ช่วงโปร GP ฟรีของแพลตฟอร์ม ระบบคิด 0% อัตโนมัติ)
                @if (\Illuminate\Support\Facades\Route::has('admin.pricing.settings'))
                    · <a href="{{ route('admin.pricing.settings') }}" class="w1-link">ไปหน้า ส่วนแบ่งรายได้ & GP →</a>
                @endif
            </div>
            <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(min(100%,220px),1fr)); gap:14px;">
                <label>
                    <span style="display:block; font-size:12.5px; color:var(--ink2); font-weight:600; margin-bottom:6px;">ค่า GP ตลาดสด (%)</span>
                    <input type="number" name="platform_fee_percentage" step="0.01" min="0" max="50" required class="tp-input" value="{{ old('platform_fee_percentage', $settings->platform_fee_percentage ?? 5) }}">
                    <span style="display:block; font-size:11px; color:var(--ink2); margin-top:4px;">ตั้งได้ 0 – 50%</span>
                </label>
                <label>
                    <span style="display:block; font-size:12.5px; color:var(--ink2); font-weight:600; margin-bottom:6px;">ค่าสมาชิกรายเดือน (บาท)</span>
                    <input type="number" name="monthly_subscription_fee" step="0.01" min="0" required class="tp-input" value="{{ old('monthly_subscription_fee', $settings->monthly_subscription_fee ?? 0) }}">
                </label>
                <label>
                    <span style="display:block; font-size:12.5px; color:var(--ink2); font-weight:600; margin-bottom:6px;">ทดลองใช้ฟรี (วัน)</span>
                    <input type="number" name="free_trial_days" min="0" required class="tp-input" value="{{ old('free_trial_days', $settings->free_trial_days ?? 30) }}">
                </label>
                <label>
                    <span style="display:block; font-size:12.5px; color:var(--ink2); font-weight:600; margin-bottom:6px;">ลงขายได้ (ร้านฟรี)</span>
                    <input type="number" name="max_listings_free" min="1" required class="tp-input" value="{{ old('max_listings_free', $settings->max_listings_free ?? 5) }}">
                </label>
                <label>
                    <span style="display:block; font-size:12.5px; color:var(--ink2); font-weight:600; margin-bottom:6px;">ลงขายได้ (สมาชิก)</span>
                    <input type="number" name="max_listings_subscribed" min="0" required class="tp-input" value="{{ old('max_listings_subscribed', $settings->max_listings_subscribed ?? 0) }}">
                    <span style="display:block; font-size:11px; color:var(--ink2); margin-top:4px;">0 = ไม่จำกัด</span>
                </label>
            </div>
            <div style="margin-top:16px;">
                <span style="display:block; font-size:12.5px; color:var(--ink2); font-weight:600; margin-bottom:8px;">รูปแบบการเก็บค่าธรรมเนียม</span>
                @php $feeMode = old('fee_mode', $settings->fee_mode ?? 'both'); @endphp
                <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(min(100%,210px),1fr)); gap:10px;">
                    @foreach (['percentage' => ['เก็บ GP ต่อออเดอร์', 'ไม่มีค่าสมาชิก ลงขายได้ไม่จำกัด'], 'subscription' => ['ค่าสมาชิกรายเดือน', 'ไม่หัก GP ต่อออเดอร์'], 'both' => ['ผสม', 'ค่าสมาชิก + หัก GP']] as $modeValue => [$modeLabel, $modeHint])
                        <label style="display:flex; gap:10px; align-items:flex-start; padding:12px 14px; border-radius:14px; box-shadow:var(--inset-sm); cursor:pointer;">
                            <input type="radio" name="fee_mode" value="{{ $modeValue }}" @checked($feeMode === $modeValue) style="margin-top:3px; accent-color:var(--accent1);">
                            <span><b style="font-size:13px;">{{ $modeLabel }}</b><span style="display:block; font-size:11.5px; color:var(--ink2);">{{ $modeHint }}</span></span>
                        </label>
                    @endforeach
                </div>
            </div>
        </section>

        {{-- ================= ระบบตลาด (market[...]) ================= --}}
        <section class="tp-card" style="padding:20px;" x-show="tab === 'market'" x-cloak>
            <div class="tp-section-h" style="margin-bottom:14px;"><i class="fas fa-store"></i> ระบบออเดอร์และร้านค้า</div>
            <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(min(100%,240px),1fr)); gap:14px;">
                @foreach ($marketFields as [$mKey, $mLabel, $mUnit, $mType, $mMin, $mMax, $mStep])
                    <label>
                        <span style="display:block; font-size:12.5px; color:var(--ink2); font-weight:600; margin-bottom:6px;">{{ $mLabel }} @if ($mType === 'number')<span style="font-weight:500;">({{ $mUnit }})</span>@endif</span>
                        <input type="{{ $mType }}" name="market[{{ $mKey }}]" class="tp-input"
                               value="{{ old('market.'.$mKey, $marketSettings[$mKey] ?? '') }}"
                               @if ($mType === 'number') min="{{ $mMin }}" max="{{ $mMax }}" step="{{ $mStep }}" @else maxlength="50" placeholder="{{ $mUnit }}" @endif>
                    </label>
                @endforeach
            </div>
            <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(min(100%,280px),1fr)); gap:10px; margin-top:16px;">
                @foreach ($marketToggles as [$mKey, $mLabel, $mHint])
                    <label style="display:flex; gap:10px; align-items:flex-start; padding:12px 14px; border-radius:14px; box-shadow:var(--inset-sm); cursor:pointer;">
                        <input type="hidden" name="market[{{ $mKey }}]" value="0">
                        <input type="checkbox" name="market[{{ $mKey }}]" value="1" @checked(filter_var(old('market.'.$mKey, $marketSettings[$mKey] ?? false), FILTER_VALIDATE_BOOLEAN)) style="margin-top:3px; width:18px; height:18px; accent-color:var(--accent1);">
                        <span><b style="font-size:13px;">{{ $mLabel }}</b><span style="display:block; font-size:11.5px; color:var(--ink2);">{{ $mHint }}</span></span>
                    </label>
                @endforeach
            </div>
        </section>

        {{-- ================= เปิด/ปิดฟีเจอร์ ================= --}}
        <section class="tp-card" style="padding:20px;" x-show="tab === 'toggles'" x-cloak>
            <div class="tp-section-h" style="margin-bottom:14px;"><i class="fas fa-toggle-on"></i> เปิด/ปิดฟีเจอร์</div>
            <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(min(100%,280px),1fr)); gap:10px;">
                @foreach ($featureToggles as [$fKey, $fLabel, $fHint, $fDefault])
                    <label style="display:flex; gap:10px; align-items:flex-start; padding:12px 14px; border-radius:14px; box-shadow:var(--inset-sm); cursor:pointer;">
                        <input type="hidden" name="{{ $fKey }}" value="0">
                        <input type="checkbox" name="{{ $fKey }}" value="1" @checked((bool) old($fKey, $settings->{$fKey} ?? $fDefault)) style="margin-top:3px; width:18px; height:18px; accent-color:var(--accent1);">
                        <span><b style="font-size:13px;">{{ $fLabel }}</b><span style="display:block; font-size:11.5px; color:var(--ink2);">{{ $fHint }}</span></span>
                    </label>
                @endforeach
            </div>
            <p style="font-size:12px; color:var(--ink2); margin:12px 0 0;"><i class="fas fa-circle-info"></i> ค่าส่งและส่วนแบ่งไรเดอร์ตั้งที่ <a href="{{ route('admin.riders.settings') }}" class="w1-link">ตั้งค่าค่าส่งไรเดอร์</a></p>
        </section>

        {{-- ================= LINE OA ================= --}}
        <section class="tp-card" style="padding:20px;" x-show="tab === 'line'" x-cloak>
            <div class="tp-section-h" style="margin-bottom:4px;"><i class="fas fa-comment-dots"></i> LINE Official Account</div>
            <div style="font-size:12.5px; color:var(--ink2); margin-bottom:14px;">ช่อง Secret/Token เว้นว่าง = ใช้ค่าเดิม (ระบบไม่แสดงค่าจริงในหน้าเว็บ)</div>
            <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(min(100%,260px),1fr)); gap:14px;">
                <label>
                    <span style="display:block; font-size:12.5px; color:var(--ink2); font-weight:600; margin-bottom:6px;">Channel ID</span>
                    <input type="text" name="line_channel_id" maxlength="50" class="tp-input" value="{{ old('line_channel_id', $settings->line_channel_id ?? '') }}" placeholder="กรอก Channel ID">
                </label>
                <label>
                    <span style="display:block; font-size:12.5px; color:var(--ink2); font-weight:600; margin-bottom:6px;">Channel Secret</span>
                    <input type="password" name="line_channel_secret" value="" maxlength="100" autocomplete="new-password" class="tp-input"
                           placeholder="{{ $hasLineSecret ? $lineSecretMasked.' — เว้นว่างเพื่อใช้ค่าเดิม' : 'ยังไม่ได้ตั้งค่า — กรอกเพื่อบันทึก' }}">
                </label>
                <label style="grid-column:1 / -1;">
                    <span style="display:block; font-size:12.5px; color:var(--ink2); font-weight:600; margin-bottom:6px;">Channel Access Token</span>
                    <input type="password" name="line_channel_access_token" value="" maxlength="500" autocomplete="new-password" class="tp-input"
                           placeholder="{{ $hasLineToken ? $lineTokenMasked.' — เว้นว่างเพื่อใช้ค่าเดิม' : 'ยังไม่ได้ตั้งค่า — กรอกเพื่อบันทึก' }}">
                </label>
            </div>
            <div style="display:flex; flex-wrap:wrap; gap:8px; margin-top:14px;">
                @include('admin.riders.partials.pill', ['pillTone' => $hasLineSecret ? 'ok' : 'warn', 'pillText' => $hasLineSecret ? 'มี Secret แล้ว' : 'ยังไม่มี Secret', 'pillIcon' => 'fa-key', 'pillTitle' => null])
                @include('admin.riders.partials.pill', ['pillTone' => $hasLineToken ? 'ok' : 'warn', 'pillText' => $hasLineToken ? 'มี Access Token แล้ว' : 'ยังไม่มี Access Token', 'pillIcon' => 'fa-key', 'pillTitle' => null])
            </div>
            <p style="font-size:12px; color:var(--ink2); margin:12px 0 0;"><i class="fas fa-triangle-exclamation" style="color:var(--w-warn);"></i> ระบบตอบลูกค้าด้วย reply (ฟรี) เป็นหลัก — push มีโควต้าจำกัดต่อเดือน</p>
        </section>

        {{-- ================= AI บอท ================= --}}
        <section class="tp-card" style="padding:20px;" x-show="tab === 'ai'" x-cloak
                 x-data="{
                    allowedTopics: @js(array_values((array) ($settings->ai_allowed_topics ?? []))),
                    blockedTopics: @js(array_values((array) ($settings->ai_blocked_topics ?? []))),
                    newAllowed: '',
                    newBlocked: '',
                    temp: @js((float) ($settings->bot_temperature ?? 0.7)),
                    add(list, key) { const v = this[key].trim(); if (v && !this[list].includes(v)) this[list].push(v); this[key] = ''; },
                 }">
            <input type="hidden" name="ai_allowed_topics" :value="JSON.stringify(allowedTopics)">
            <input type="hidden" name="ai_blocked_topics" :value="JSON.stringify(blockedTopics)">

            <div class="tp-section-h" style="margin-bottom:14px;"><i class="fas fa-robot"></i> บุคลิกและผู้ให้บริการ AI</div>
            <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(min(100%,240px),1fr)); gap:14px;">
                <label>
                    <span style="display:block; font-size:12.5px; color:var(--ink2); font-weight:600; margin-bottom:6px;">ชื่อบอท</span>
                    <input type="text" name="bot_name" maxlength="50" class="tp-input" value="{{ old('bot_name', $settings->bot_name ?? 'พี่ตลาด') }}">
                </label>
                <label>
                    <span style="display:block; font-size:12.5px; color:var(--ink2); font-weight:600; margin-bottom:6px;">สไตล์การตอบ</span>
                    @php $responseStyle = old('bot_response_style', $settings->bot_response_style ?? 'friendly'); @endphp
                    <select name="bot_response_style" class="tp-input">
                        @foreach (['friendly' => 'ใจดี เป็นกันเอง', 'formal' => 'เป็นทางการ สุภาพ', 'casual' => 'สบายๆ ชิลล์', 'funny' => 'ตลก ขี้เล่น'] as $styleValue => $styleLabel)
                            <option value="{{ $styleValue }}" @selected($responseStyle === $styleValue)>{{ $styleLabel }}</option>
                        @endforeach
                    </select>
                </label>
                <label>
                    <span style="display:block; font-size:12.5px; color:var(--ink2); font-weight:600; margin-bottom:6px;">ผู้ให้บริการ AI</span>
                    @php $aiProvider = old('ai_provider', $settings->ai_provider ?? 'groq'); @endphp
                    <select name="ai_provider" class="tp-input" required>
                        @foreach (['groq' => 'Groq (แนะนำ)', 'openrouter' => 'OpenRouter', 'openai' => 'OpenAI'] as $providerValue => $providerLabel)
                            <option value="{{ $providerValue }}" @selected($aiProvider === $providerValue)>{{ $providerLabel }}</option>
                        @endforeach
                    </select>
                </label>
                <label>
                    <span style="display:block; font-size:12.5px; color:var(--ink2); font-weight:600; margin-bottom:6px;">โมเดล AI</span>
                    <input type="text" name="ai_model" maxlength="100" required class="tp-input" value="{{ old('ai_model', $settings->ai_model ?? 'llama-3.3-70b-versatile') }}">
                </label>
                <label style="grid-column:1 / -1;">
                    <span style="display:block; font-size:12.5px; color:var(--ink2); font-weight:600; margin-bottom:6px;">บุคลิกภาพ / อารมณ์</span>
                    <textarea name="bot_personality" rows="2" maxlength="1000" class="tp-input" style="resize:vertical;" placeholder="เช่น ร่าเริง อบอุ่น พูดสั้นกระชับ">{{ old('bot_personality', $settings->bot_personality ?? '') }}</textarea>
                </label>
                <label style="grid-column:1 / -1;">
                    <span style="display:block; font-size:12.5px; color:var(--ink2); font-weight:600; margin-bottom:6px;">ความสร้างสรรค์ของคำตอบ: <b class="tp-num" style="color:var(--ink);" x-text="Number(temp).toFixed(2)"></b></span>
                    <input type="range" name="bot_temperature" min="0" max="1" step="0.05" x-model="temp" class="tp-range">
                    <span style="display:flex; justify-content:space-between; font-size:11px; color:var(--ink2); margin-top:4px;"><span>0 ตรงประเด็น</span><span>1 สร้างสรรค์</span></span>
                </label>
                <label style="grid-column:1 / -1; display:flex; gap:10px; align-items:center; cursor:pointer; font-size:13px;">
                    <input type="hidden" name="use_global_ai_settings" value="0">
                    <input type="checkbox" name="use_global_ai_settings" value="1" @checked((bool) old('use_global_ai_settings', $settings->use_global_ai_settings ?? true)) style="width:18px; height:18px; accent-color:var(--accent1);">
                    ใช้ API Key Pool จากระบบหลัก
                </label>
                <label style="grid-column:1 / -1;">
                    <span style="display:block; font-size:12.5px; color:var(--ink2); font-weight:600; margin-bottom:6px;">System Prompt</span>
                    <textarea name="ai_system_prompt" rows="5" class="tp-input" style="resize:vertical; font-family:var(--tp-font);" placeholder="บทบาทและคำสั่งหลักของ AI">{{ old('ai_system_prompt', $settings->ai_system_prompt ?? '') }}</textarea>
                </label>
            </div>

            <div class="tp-divider" style="margin:18px 0;"></div>
            <div class="tp-section-h" style="margin-bottom:12px;"><i class="fas fa-hand-sparkles"></i> ข้อความต้อนรับและปุ่มเมนู</div>
            <label style="display:block; margin-bottom:12px;">
                <span style="display:block; font-size:12.5px; color:var(--ink2); font-weight:600; margin-bottom:6px;">ข้อความต้อนรับ (เมื่อผู้ใช้พิมพ์ตอนยังไม่ได้เลือกเมนู)</span>
                <textarea name="greeting_message_template" rows="3" maxlength="2000" class="tp-input" style="resize:vertical;">{{ old('greeting_message_template', $settings->greeting_message_template ?? '') }}</textarea>
            </label>
            <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(min(100%,160px),1fr)); gap:10px;">
                @foreach ($menuDefaults as $menuKey => [$menuDefault, $menuDesc])
                    <label>
                        <span style="display:block; font-size:11.5px; color:var(--ink2); font-weight:600; margin-bottom:5px;">{{ $menuDesc }}</span>
                        <input type="text" name="menu_label_{{ $menuKey }}" maxlength="20" class="tp-input" value="{{ old('menu_label_'.$menuKey, $menuLabels[$menuKey] ?? $menuDefault) }}">
                    </label>
                @endforeach
            </div>
            <label style="display:flex; gap:10px; align-items:center; cursor:pointer; font-size:13px; margin-top:12px;">
                <input type="hidden" name="ai_enabled_in_idle" value="0">
                <input type="checkbox" name="ai_enabled_in_idle" value="1" @checked((bool) old('ai_enabled_in_idle', $settings->ai_enabled_in_idle ?? false)) style="width:18px; height:18px; accent-color:var(--accent1);">
                ให้ AI ตอบข้อความทั่วไปได้เลย (ไม่บังคับกดปุ่ม)
            </label>

            <div class="tp-divider" style="margin:18px 0;"></div>
            <div class="tp-section-h" style="margin-bottom:12px;"><i class="fas fa-shield-halved"></i> ขอบเขตการตอบ</div>
            <label style="display:block; margin-bottom:12px;">
                <span style="display:block; font-size:12.5px; color:var(--ink2); font-weight:600; margin-bottom:6px;">ขอบเขตการตอบ</span>
                <textarea name="ai_scope_description" rows="2" maxlength="2000" class="tp-input" style="resize:vertical;" placeholder="เช่น ตอบเฉพาะเรื่องตลาดสด สินค้าเกษตร อาหาร และการส่งของ">{{ old('ai_scope_description', $settings->ai_scope_description ?? '') }}</textarea>
            </label>
            <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(min(100%,280px),1fr)); gap:14px;">
                <div>
                    <span style="display:block; font-size:12.5px; color:var(--ink2); font-weight:600; margin-bottom:6px;">หัวข้อที่ตอบได้</span>
                    <div style="display:flex; flex-wrap:wrap; gap:6px; margin-bottom:8px; min-height:22px;">
                        <template x-for="(topic, i) in allowedTopics" :key="'a' + i">
                            <span class="tp-pill tp-pill-soft"><span x-text="topic"></span> <button type="button" @click="allowedTopics.splice(i, 1)" style="border:0; background:none; cursor:pointer; color:inherit; padding:0 0 0 2px;" title="ลบ">×</button></span>
                        </template>
                    </div>
                    <div style="display:flex; gap:8px;">
                        <input type="text" x-model="newAllowed" @keydown.enter.prevent="add('allowedTopics', 'newAllowed')" class="tp-input" placeholder="พิมพ์หัวข้อแล้วกด Enter">
                        <button type="button" class="tp-btn tp-btn-sm" @click="add('allowedTopics', 'newAllowed')">เพิ่ม</button>
                    </div>
                </div>
                <div>
                    <span style="display:block; font-size:12.5px; color:var(--ink2); font-weight:600; margin-bottom:6px;">หัวข้อที่ห้ามตอบ</span>
                    <div style="display:flex; flex-wrap:wrap; gap:6px; margin-bottom:8px; min-height:22px;">
                        <template x-for="(topic, i) in blockedTopics" :key="'b' + i">
                            <span class="tp-pill" style="background:color-mix(in srgb, var(--w-bad) 16%, transparent); color:color-mix(in srgb, var(--w-bad) 74%, var(--ink));"><span x-text="topic"></span> <button type="button" @click="blockedTopics.splice(i, 1)" style="border:0; background:none; cursor:pointer; color:inherit; padding:0 0 0 2px;" title="ลบ">×</button></span>
                        </template>
                    </div>
                    <div style="display:flex; gap:8px;">
                        <input type="text" x-model="newBlocked" @keydown.enter.prevent="add('blockedTopics', 'newBlocked')" class="tp-input" placeholder="พิมพ์หัวข้อแล้วกด Enter">
                        <button type="button" class="tp-btn tp-btn-sm" @click="add('blockedTopics', 'newBlocked')">เพิ่ม</button>
                    </div>
                </div>
                <label style="grid-column:1 / -1;">
                    <span style="display:block; font-size:12.5px; color:var(--ink2); font-weight:600; margin-bottom:6px;">ข้อความเมื่อถามนอกขอบเขต</span>
                    <input type="text" name="ai_off_topic_message" maxlength="500" class="tp-input" value="{{ old('ai_off_topic_message', $settings->ai_off_topic_message ?? '') }}">
                </label>
            </div>

            <div class="tp-divider" style="margin:18px 0;"></div>
            <div class="tp-section-h" style="margin-bottom:12px;"><i class="fas fa-database"></i> ปุ่มอัจฉริยะและสิทธิ์เข้าถึงข้อมูล</div>
            <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(min(100%,220px),1fr)); gap:12px;">
                <label style="display:flex; gap:10px; align-items:center; padding:11px 13px; border-radius:13px; box-shadow:var(--inset-sm); cursor:pointer; font-size:13px;">
                    <input type="hidden" name="ai_can_suggest_buttons" value="0">
                    <input type="checkbox" name="ai_can_suggest_buttons" value="1" @checked((bool) old('ai_can_suggest_buttons', $settings->ai_can_suggest_buttons ?? true)) style="width:18px; height:18px; accent-color:var(--accent1);">
                    AI แนะนำปุ่มตามบริบทสนทนา
                </label>
                <label>
                    <span style="display:block; font-size:12.5px; color:var(--ink2); font-weight:600; margin-bottom:6px;">จำนวนปุ่มสูงสุด</span>
                    <input type="number" name="ai_max_buttons" min="1" max="13" class="tp-input" value="{{ old('ai_max_buttons', $settings->ai_max_buttons ?? 4) }}">
                </label>
                <label>
                    <span style="display:block; font-size:12.5px; color:var(--ink2); font-weight:600; margin-bottom:6px;">ข้อความย้อนหลังที่ AI เห็น</span>
                    <input type="number" name="ai_max_context_messages" min="1" max="50" class="tp-input" value="{{ old('ai_max_context_messages', $settings->ai_max_context_messages ?? 10) }}">
                </label>
            </div>
            <div style="display:flex; flex-wrap:wrap; gap:8px; margin-top:12px;">
                @foreach ($dataToggles as [$dKey, $dLabel, $dDefault])
                    <label class="tp-btn tp-btn-sm" style="cursor:pointer;">
                        <input type="hidden" name="{{ $dKey }}" value="0">
                        <input type="checkbox" name="{{ $dKey }}" value="1" @checked((bool) old($dKey, $settings->{$dKey} ?? $dDefault)) style="accent-color:var(--accent1);">
                        {{ $dLabel }}
                    </label>
                @endforeach
            </div>
        </section>

        {{-- ================= การค้นหา ================= --}}
        <section class="tp-card" style="padding:20px;" x-show="tab === 'search'" x-cloak>
            <div class="tp-section-h" style="margin-bottom:14px;"><i class="fas fa-location-crosshairs"></i> รัศมีค้นหาร้าน/สินค้าใกล้ผู้ซื้อ</div>
            <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(min(100%,240px),1fr)); gap:14px;">
                <label>
                    <span style="display:block; font-size:12.5px; color:var(--ink2); font-weight:600; margin-bottom:6px;">รัศมีเริ่มต้น (กม.)</span>
                    <input type="number" name="default_search_radius_km" step="0.1" min="1" max="100" required class="tp-input" value="{{ old('default_search_radius_km', $settings->default_search_radius_km ?? 5) }}">
                </label>
                <label>
                    <span style="display:block; font-size:12.5px; color:var(--ink2); font-weight:600; margin-bottom:6px;">รัศมีสูงสุดที่ผู้ใช้เลือกได้ (กม.)</span>
                    <input type="number" name="max_search_radius_km" step="0.1" min="1" max="200" required class="tp-input" value="{{ old('max_search_radius_km', $settings->max_search_radius_km ?? 50) }}">
                </label>
            </div>
        </section>

        {{-- ================= แบรนด์ ================= --}}
        <section class="tp-card" style="padding:20px;" x-show="tab === 'branding'" x-cloak x-data="{ flex: @js(old('line_flex_primary_color', $flexColor)) }">
            <div class="tp-section-h" style="margin-bottom:14px;"><i class="fas fa-palette"></i> แบรนด์และข้อความต้อนรับ</div>
            <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(min(100%,240px),1fr)); gap:14px;">
                <label>
                    <span style="display:block; font-size:12.5px; color:var(--ink2); font-weight:600; margin-bottom:6px;">ชื่อแบรนด์</span>
                    <input type="text" name="brand_name" maxlength="100" required class="tp-input" value="{{ old('brand_name', $settings->brand_name ?? 'ตลาดสดไทยพร๊อม') }}">
                </label>
                <label>
                    <span style="display:block; font-size:12.5px; color:var(--ink2); font-weight:600; margin-bottom:6px;">สีหลักของการ์ด LINE (Flex)</span>
                    <span style="display:flex; gap:10px; align-items:center;">
                        <input type="color" name="line_flex_primary_color" x-model="flex" style="width:52px; height:42px; border:0; border-radius:12px; padding:3px; background:var(--surf); box-shadow:var(--raise); cursor:pointer;">
                        <input type="text" class="tp-input tp-num" x-model="flex" readonly aria-label="รหัสสี">
                    </span>
                </label>
                <label style="grid-column:1 / -1;">
                    <span style="display:block; font-size:12.5px; color:var(--ink2); font-weight:600; margin-bottom:6px;">ข้อความต้อนรับเมื่อเพิ่มเพื่อน LINE OA</span>
                    <textarea name="welcome_message" rows="4" class="tp-input" style="resize:vertical;">{{ old('welcome_message', $settings->welcome_message ?? '') }}</textarea>
                </label>
            </div>
        </section>

        {{-- ===== แถบบันทึก ===== --}}
        <div class="tp-card" style="padding:14px 18px; display:flex; flex-wrap:wrap; align-items:center; justify-content:space-between; gap:12px; position:sticky; bottom:12px; z-index:5;">
            <span style="font-size:12.5px; color:var(--ink2);">บันทึกทุกแท็บพร้อมกัน — ช่อง Secret/Token ที่เว้นว่างจะคงค่าเดิม</span>
            <button type="submit" class="tp-btn tp-btn-primary" :disabled="saving">
                <i class="fas" :class="saving ? 'fa-spinner fa-spin' : 'fa-floppy-disk'"></i>
                <span x-text="saving ? 'กำลังบันทึก...' : 'บันทึกการตั้งค่า'"></span>
            </button>
        </div>
    </form>
</div>
@endsection
