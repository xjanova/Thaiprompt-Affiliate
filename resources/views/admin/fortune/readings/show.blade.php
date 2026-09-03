{{-- 🔮 ประวัติการทำนาย — รายละเอียด (ธีม V4 นวลทองคำ) --}}
@extends('layouts.admin-v4')

@section('title', 'รายละเอียดการทำนาย #' . $reading->id)

@section('content')
@php
    // 🏷️ (2026-05-25) Cancellation detection — ดึงเหตุผลยกเลิกจาก conversation_state
    $cancellationLabel = $reading->getCancellationReasonLabelOrNull();
    $cancellationReason = data_get($reading->conversation_state, 'cancellation_reason');

    // 🎨 สีสถานะคงที่ที่เข้าธีมนวลทองคำ
    $okColor   = '#5aa07e'; // เขียว/สำเร็จ
    $warnColor = '#e0a52e'; // เหลือง/เตือน
    $dangColor = '#d9534f'; // แดง/อันตราย
    $infoColor = '#5689b8'; // ฟ้า/info

    // 🔄 ลำดับสถานะ conversation (timeline)
    $statuses = [
        'new'                   => ['label' => 'ใหม่',        'icon' => 'fa-star'],
        'awaiting_confirmation' => ['label' => 'รอยืนยัน',    'icon' => 'fa-hourglass-half'],
        'basic_done'            => ['label' => 'Basic เสร็จ', 'icon' => 'fa-wand-magic-sparkles'],
        'collecting_birthdate'  => ['label' => 'รับวันเกิด',  'icon' => 'fa-calendar-day'],
        'collecting_questions'  => ['label' => 'รับคำถาม',    'icon' => 'fa-circle-question'],
        'pending_payment'       => ['label' => 'รอชำระ',      'icon' => 'fa-credit-card'],
        'paid'                  => ['label' => 'ชำระแล้ว',    'icon' => 'fa-circle-check'],
        'completed'             => ['label' => 'เสร็จสิ้น',   'icon' => 'fa-flag-checkered'],
    ];
    $currentStatus = $reading->conversation_status ?? 'new';
    $statusOrder   = array_keys($statuses);
    $currentIndex  = array_search($currentStatus, $statusOrder);

    // 💳 จัดการบล็อก manual action (deep reading)
    $showDeepActions = $reading->reading_type === 'deep'
        && ($reading->is_paid || ! empty($reading->deep_response) || $reading->conversation_status === 'paid');

    // ⏳ ตอนนี้ AI กำลังสร้าง (paid แต่ยังไม่มี deep_response)
    $aiProcessing = $reading->conversation_status === 'paid' && empty($reading->deep_response);
    $paidElapsedSec  = $aiProcessing ? ($reading->updated_at?->diffInSeconds(now()) ?? 0) : 0;
    $primaryProvider = $aiProcessing
        ? strtoupper(\App\Models\FortuneTellingSetting::getSettings()->ai_provider ?? 'gemini')
        : '';
@endphp

<div style="display:flex; flex-direction:column; gap:18px;">

    {{-- ════════ HEADER ════════ --}}
    <div style="display:flex; flex-wrap:wrap; align-items:flex-end; justify-content:space-between; gap:14px;">
        <div>
            <div style="font-size:11px; color:var(--ink2); font-weight:600; letter-spacing:.4px;">
                <a href="{{ route('admin.fortune.readings.index') }}" style="color:var(--ink2); text-decoration:none;">
                    <i class="fas fa-arrow-left" style="margin-right:5px;"></i>หลังบ้าน · ระบบดูดวง · ประวัติการทำนาย
                </a>
            </div>
            <h1 class="tp-num" style="font-size:clamp(22px,4vw,28px); font-weight:800; margin:4px 0 0;">
                รายละเอียดการทำนาย #{{ $reading->id }} 🔮
            </h1>
        </div>
        <div style="display:flex; align-items:center; gap:9px;">
            <a href="{{ route('admin.fortune.readings.edit', $reading) }}" class="tp-btn tp-btn-sm">
                <i class="fas fa-pen"></i> แก้ไข
            </a>
            {{-- 🗑️ ลบการทำนาย (DELETE) — ยืนยันก่อนลบ --}}
            <form action="{{ route('admin.fortune.readings.destroy', $reading) }}" method="POST"
                  onsubmit="return confirm('ยืนยันลบการทำนาย #{{ $reading->id }} หรือไม่? การลบไม่สามารถย้อนกลับได้');"
                  style="margin:0;">
                @csrf
                @method('DELETE')
                <button type="submit" class="tp-btn tp-btn-sm" style="color:{{ $dangColor }};">
                    <i class="fas fa-trash"></i> ลบ
                </button>
            </form>
        </div>
    </div>

    {{-- ════════ ROW: ข้อมูลผู้ใช้ + timeline สถานะ ════════ --}}
    <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(330px,1fr)); gap:16px; align-items:start;">

        {{-- 👤 ข้อมูลผู้ใช้ --}}
        <div class="tp-card" style="padding:20px; grid-column:span 2; min-width:0;">
            <div class="tp-section-h" style="display:flex; align-items:center; gap:10px; margin-bottom:16px;">
                <span class="tp-tile" style="width:34px; height:34px; border-radius:11px; font-size:15px; display:inline-flex; align-items:center; justify-content:center;">
                    <i class="fas fa-user"></i>
                </span>
                <span class="tp-num" style="font-weight:800; font-size:16px;">ข้อมูลผู้ใช้</span>
            </div>
            <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(200px,1fr)); gap:14px;">
                <div>
                    <div class="tp-muted" style="font-size:12px;">ชื่อ</div>
                    <div style="font-weight:700; color:var(--ink); margin-top:2px;">{{ $reading->facebook_user_name ?? 'ไม่ระบุ' }}</div>
                </div>
                <div>
                    <div class="tp-muted" style="font-size:12px;">Facebook ID</div>
                    <div style="font-family:monospace; font-size:13px; color:var(--ink); margin-top:2px; word-break:break-all;">{{ $reading->facebook_user_id }}</div>
                </div>
                <div>
                    <div class="tp-muted" style="font-size:12px;">วันที่</div>
                    <div style="color:var(--ink); margin-top:2px;">{{ $reading->created_at->format('d/m/Y H:i:s') }}</div>
                </div>
                <div>
                    <div class="tp-muted" style="font-size:12px;">ช่องทาง</div>
                    <div style="margin-top:4px;">
                        @if($reading->response_type === 'comment')
                            <span class="tp-pill" style="color:{{ $infoColor }};"><i class="fas fa-comment"></i> Comment</span>
                        @else
                            <span class="tp-pill" style="color:#b79ae8;"><i class="fas fa-envelope"></i> Private Message</span>
                        @endif
                    </div>
                </div>
                <div>
                    <div class="tp-muted" style="font-size:12px;">ประเภทคำทำนาย</div>
                    <div style="margin-top:4px;">
                        @if($reading->reading_type === 'deep')
                            <span class="tp-pill tp-pill-gold"><i class="fas fa-star"></i> เชิงลึก</span>
                        @else
                            <span class="tp-pill tp-pill-soft"><i class="fas fa-wand-magic-sparkles"></i> พื้นฐาน</span>
                        @endif
                    </div>
                </div>
                @if($reading->birth_date)
                    <div>
                        <div class="tp-muted" style="font-size:12px;">วันเกิด</div>
                        <div style="font-weight:700; color:var(--ink); margin-top:2px;">
                            {{ $reading->birth_date->format('d/m/Y') }}
                            @if($reading->birthTimeIsKnown())
                                <span style="font-weight:500;">🕛 {{ substr((string) $reading->birth_time, 0, 5) }} น.</span>
                            @elseif($reading->birth_time)
                                <span style="font-weight:500;opacity:.7;">🕛 {{ substr((string) $reading->birth_time, 0, 5) }} น. <em>(ค่ามาตรฐาน)</em></span>
                            @else
                                <span class="tp-muted" style="font-weight:500; font-size:11px;">⏱️ ไม่ทราบเวลา (ใช้ 12:00)</span>
                            @endif
                        </div>
                    </div>
                @endif
                @if(!empty($reading->categories))
                    <div style="grid-column:1 / -1;">
                        <div class="tp-muted" style="font-size:12px; margin-bottom:5px;">หมวดหมู่</div>
                        <div style="display:flex; flex-wrap:wrap; gap:6px;">
                            @foreach($reading->categories as $cat)
                                <span class="tp-pill tp-pill-soft">{{ $cat }}</span>
                            @endforeach
                        </div>
                    </div>
                @endif
                @if($reading->bill_reference)
                    <div>
                        <div class="tp-muted" style="font-size:12px;">เลขที่บิล</div>
                        <div style="font-family:monospace; font-size:13px; color:var(--ink); margin-top:2px;">{{ $reading->bill_reference }}</div>
                    </div>
                @endif
            </div>
        </div>

        {{-- 🔄 สถานะ Conversation (timeline แนวตั้ง) --}}
        <div class="tp-card" style="padding:20px; min-width:0;">
            <div class="tp-section-h" style="display:flex; align-items:center; gap:10px; margin-bottom:16px;">
                <span class="tp-tile" style="width:34px; height:34px; border-radius:11px; font-size:14px; display:inline-flex; align-items:center; justify-content:center;">
                    <i class="fas fa-arrows-rotate"></i>
                </span>
                <span class="tp-num" style="font-weight:800; font-size:16px;">สถานะ Conversation</span>
            </div>
            <div style="display:flex; flex-direction:column; gap:10px;">
                @foreach($statuses as $key => $status)
                    @php
                        $index     = array_search($key, $statusOrder);
                        $isPast    = $index < $currentIndex;
                        $isCurrent = $key === $currentStatus;
                        // 🎨 สีไอคอนวงกลม: ปัจจุบัน=ทอง / ผ่านแล้ว=เขียว / ยังไม่ถึง=จาง
                        $circleStyle = $isCurrent
                            ? 'background:linear-gradient(135deg,var(--accent1),var(--accent2)); color:#fff; box-shadow:var(--raise);'
                            : ($isPast
                                ? 'background:transparent; color:'.$okColor.'; box-shadow:var(--inset-sm);'
                                : 'background:transparent; color:var(--ink2); box-shadow:var(--inset-sm); opacity:.6;');
                        $textColor = $isCurrent ? 'var(--ink)' : ($isPast ? $okColor : 'var(--ink2)');
                    @endphp
                    <div style="display:flex; align-items:center; gap:11px;">
                        <span style="width:30px; height:30px; border-radius:50%; display:inline-flex; align-items:center; justify-content:center; font-size:12px; flex-shrink:0; {{ $circleStyle }}">
                            <i class="fas {{ $isPast ? 'fa-check' : $status['icon'] }}"></i>
                        </span>
                        <span style="font-size:13.5px; color:{{ $textColor }}; {{ $isCurrent ? 'font-weight:800;' : 'font-weight:500;' }}">
                            {{ $status['label'] }}
                        </span>
                        @if($isCurrent)
                            <span class="tp-pill tp-pill-gold" style="margin-left:auto; font-size:10.5px;">ปัจจุบัน</span>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    {{-- ════════ คำถาม ════════ --}}
    <div class="tp-card" style="padding:20px;">
        <div class="tp-section-h" style="display:flex; align-items:center; gap:10px; margin-bottom:14px;">
            <span class="tp-tile" style="width:34px; height:34px; border-radius:11px; font-size:14px; display:inline-flex; align-items:center; justify-content:center;">
                <i class="fas fa-circle-question"></i>
            </span>
            <span class="tp-num" style="font-weight:800; font-size:16px;">คำถาม</span>
        </div>
        @if(is_iterable($reading->questions) && count($reading->questions) > 0)
            <div style="display:flex; flex-direction:column; gap:9px;">
                @foreach($reading->questions as $i => $question)
                    <div class="tp-well" style="display:flex; align-items:flex-start; gap:11px; padding:12px 14px; border-radius:13px;">
                        <span class="tp-num tp-pill-gold" style="width:24px; height:24px; border-radius:50%; display:inline-flex; align-items:center; justify-content:center; font-size:12px; font-weight:800; flex-shrink:0;">{{ $i + 1 }}</span>
                        <span style="color:var(--ink); line-height:1.6;">{{ $question }}</span>
                    </div>
                @endforeach
            </div>
        @else
            {{-- empty state --}}
            <div style="text-align:center; color:var(--ink2); padding:30px 0;">
                <i class="fas fa-inbox" style="font-size:28px; display:block; margin-bottom:8px; opacity:.5;"></i>
                ไม่มีคำถาม
            </div>
        @endif
    </div>

    {{-- ════════ รูปภาพจากผู้ใช้ ════════ --}}
    @if($reading->user_image_url)
        <div class="tp-card" style="padding:20px;">
            <div class="tp-section-h" style="display:flex; align-items:center; gap:10px; margin-bottom:14px;">
                <span class="tp-tile" style="width:34px; height:34px; border-radius:11px; font-size:14px; display:inline-flex; align-items:center; justify-content:center;">
                    <i class="fas fa-image"></i>
                </span>
                <span class="tp-num" style="font-weight:800; font-size:16px;">รูปภาพจากผู้ใช้</span>
            </div>
            <div class="tp-inset" style="display:inline-block; padding:8px; border-radius:14px;">
                <img src="{{ $reading->user_image_url }}" alt="รูปจากผู้ใช้"
                     style="border-radius:10px; max-height:24rem; object-fit:contain; display:block; max-width:100%;"
                     loading="lazy">
            </div>
        </div>
    @endif

    {{-- ════════ Birth Chart + คำทำนาย ════════ --}}
    <div style="display:grid; grid-template-columns:{{ $reading->reading_image_url ? 'minmax(260px,1fr) minmax(340px,2fr)' : '1fr' }}; gap:16px; align-items:start;">

        {{-- 🎨 Birth Chart Image --}}
        @if($reading->reading_image_url)
            <div class="tp-card" style="padding:20px; display:flex; flex-direction:column; align-items:center; justify-content:center; gap:12px; min-width:0;">
                <div class="tp-num" style="font-weight:800; font-size:15px; color:var(--ink); display:flex; align-items:center; gap:8px;">
                    <i class="fas fa-palette" style="color:var(--deep1);"></i> Birth Chart
                </div>
                <div class="tp-inset" style="padding:8px; border-radius:16px;">
                    <img src="{{ $reading->reading_image_url }}" alt="Birth Chart"
                         style="border-radius:12px; max-width:100%; max-height:20rem; object-fit:contain; display:block;"
                         loading="lazy">
                </div>
                <p class="tp-muted" style="font-size:11.5px; margin:0;">ภาพดวงดาวที่ส่งให้ผู้ใช้</p>
            </div>
        @endif

        {{-- 🔮 คำทำนาย --}}
        <div class="tp-card" style="padding:20px; min-width:0;">
            <div style="display:flex; flex-wrap:wrap; align-items:center; justify-content:space-between; gap:10px; margin-bottom:14px;">
                <div class="tp-section-h" style="display:flex; align-items:center; gap:10px;">
                    <span class="tp-tile" style="width:34px; height:34px; border-radius:11px; font-size:14px; display:inline-flex; align-items:center; justify-content:center;">
                        <i class="fas fa-wand-magic-sparkles"></i>
                    </span>
                    <span class="tp-num" style="font-weight:800; font-size:16px;">คำทำนาย</span>
                </div>
                <div style="display:flex; align-items:center; gap:8px; flex-wrap:wrap;">
                    <span class="tp-pill tp-pill-soft" style="font-family:monospace;">
                        {{ strtoupper($reading->ai_provider) }} / {{ $reading->ai_model }}
                    </span>
                    @if($reading->tokens_used)
                        <span class="tp-pill" style="color:var(--ink2);">{{ number_format($reading->tokens_used) }} tokens</span>
                    @endif
                </div>
            </div>

            {{-- คำทำนายหลัก --}}
            <div class="tp-well" style="padding:16px 18px; border-radius:14px;">
                <div style="white-space:pre-wrap; color:var(--ink); line-height:1.75;">{{ $reading->ai_response }}</div>
            </div>

            {{-- 📝 คำทำนายพื้นฐาน --}}
            @if($reading->basic_response && $reading->basic_response !== $reading->ai_response)
                <div style="margin-top:16px;">
                    <h4 class="tp-num" style="font-weight:700; color:var(--ink); margin:0 0 8px; font-size:14px;">
                        <i class="fas fa-pen-nib" style="color:var(--deep1); margin-right:6px;"></i>คำทำนายพื้นฐาน
                    </h4>
                    <div class="tp-inset" style="padding:14px; border-radius:13px; font-size:13.5px;">
                        <div style="white-space:pre-wrap; color:var(--ink2);">{{ Str::limit($reading->basic_response, 500) }}</div>
                    </div>
                </div>
            @endif

            {{-- 🌟 คำทำนายเชิงลึก — แสดงเต็ม + ปุ่มคัดลอก + scrollable --}}
            @if($reading->deep_response)
                <div style="margin-top:16px;" x-data="{ expanded: false }">
                    <div style="display:flex; flex-wrap:wrap; align-items:center; justify-content:space-between; gap:10px; margin-bottom:10px;">
                        <h4 class="tp-num" style="font-weight:700; color:var(--ink); margin:0; font-size:14px;">
                            <i class="fas fa-star" style="color:var(--deep1); margin-right:6px;"></i>คำทำนายเชิงลึก
                            <span class="tp-muted" style="font-size:11.5px; font-weight:400; margin-left:6px;">
                                ({{ number_format(mb_strlen($reading->deep_response)) }} ตัวอักษร)
                            </span>
                        </h4>
                        <div style="display:flex; gap:8px;">
                            <button type="button" @click="expanded = !expanded" class="tp-btn tp-btn-sm">
                                <span x-show="!expanded"><i class="fas fa-book-open"></i> อ่านเต็ม</span>
                                <span x-show="expanded" x-cloak><i class="fas fa-book"></i> ย่อกลับ</span>
                            </button>
                            <button type="button"
                                    @click="navigator.clipboard.writeText($refs.deepText.textContent); $dispatch('notify', { type:'success', message:'คัดลอกคำทำนายแล้ว' })"
                                    class="tp-btn tp-btn-sm">
                                <i class="fas fa-copy"></i> คัดลอก
                            </button>
                        </div>
                    </div>

                    {{-- Preview (collapsed) --}}
                    <div x-show="!expanded" class="tp-inset" style="padding:16px; border-radius:13px; border-left:4px solid var(--accent1); font-size:13.5px;">
                        <div style="white-space:pre-wrap; color:var(--ink2); line-height:1.7;">{{ Str::limit($reading->deep_response, 500) }}</div>
                        @if(mb_strlen($reading->deep_response) > 500)
                            <button type="button" @click="expanded = true"
                                    style="margin-top:10px; background:none; border:none; padding:0; cursor:pointer; color:var(--deep1); font-size:12.5px; font-weight:700;">
                                <i class="fas fa-chevron-down"></i> กดอ่านส่วนที่เหลือ ({{ number_format(mb_strlen($reading->deep_response) - 500) }} ตัวอักษร)
                            </button>
                        @endif
                    </div>

                    {{-- Full (expanded) --}}
                    <div x-show="expanded" x-cloak x-transition class="tp-well" style="padding:18px; border-radius:14px; font-size:13.5px;">
                        <div x-ref="deepText" style="white-space:pre-wrap; color:var(--ink); line-height:1.9; max-height:600px; overflow-y:auto; padding-right:8px;">{{ $reading->deep_response }}</div>
                    </div>
                </div>
            @endif
        </div>
    </div>

    {{-- ════════ ข้อมูลการชำระเงิน + Manual Actions ════════ --}}
    <div class="tp-card" style="padding:20px;">
        <div class="tp-section-h" style="display:flex; align-items:center; gap:10px; margin-bottom:16px;">
            <span class="tp-tile" style="width:34px; height:34px; border-radius:11px; font-size:14px; display:inline-flex; align-items:center; justify-content:center;">
                <i class="fas fa-credit-card"></i>
            </span>
            <span class="tp-num" style="font-weight:800; font-size:16px;">ข้อมูลการชำระเงิน</span>
        </div>

        <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(180px,1fr)); gap:14px;">
            {{-- สถานะ --}}
            <div>
                <div class="tp-muted" style="font-size:12px; margin-bottom:5px;">สถานะ</div>
                <div style="display:flex; flex-wrap:wrap; gap:6px; align-items:center;">
                    @if($reading->is_paid)
                        <span class="tp-pill" style="color:{{ $okColor }};"><i class="fas fa-circle-check"></i> ชำระแล้ว</span>
                    @elseif($reading->conversation_status === 'pending_payment')
                        <span class="tp-pill" style="color:{{ $warnColor }};"><i class="fas fa-credit-card"></i> รอชำระ</span>
                    @elseif($cancellationLabel)
                        <span class="tp-pill" style="color:{{ $dangColor }};" title="reason key: {{ $cancellationReason }}">
                            <i class="fas fa-circle-xmark"></i> {{ $cancellationLabel }}
                        </span>
                    @elseif($reading->reading_type === 'deep' && $reading->amount_paid > 0)
                        <span class="tp-pill" style="color:{{ $warnColor }};"><i class="fas fa-clock"></i> รอชำระ</span>
                    @else
                        <span class="tp-pill" style="color:var(--ink2);"><i class="fas fa-gift"></i> ฟรี</span>
                    @endif

                    {{-- 🛑 (2026-05-07) Pay-First badge --}}
                    @if($reading->reading_type === 'deep' && ! $reading->is_paid && $reading->amount_paid > 0)
                        <span class="tp-pill" style="color:{{ $infoColor }};" title="ลูกค้าต้องจ่ายก่อนถึงจะได้รับคำทำนาย — UPA หมดอายุ 30 นาที">
                            <i class="fas fa-credit-card"></i> จ่ายก่อนดู
                        </span>
                    @endif
                </div>
            </div>
            {{-- จำนวนเงิน --}}
            <div>
                <div class="tp-muted" style="font-size:12px; margin-bottom:5px;">จำนวนเงิน</div>
                <div class="tp-num" style="font-weight:800; font-size:20px; color:var(--deep1);">฿{{ number_format($reading->amount_paid, 2) }}</div>
            </div>
            {{-- วันที่ชำระ --}}
            <div>
                <div class="tp-muted" style="font-size:12px; margin-bottom:5px;">วันที่ชำระ</div>
                <div style="color:var(--ink); padding-top:3px;">{{ $reading->paid_at ? $reading->paid_at->format('d/m/Y H:i') : '-' }}</div>
            </div>
        </div>

        {{-- ════════ Manual Action สำหรับ Admin (deep reading) ════════ --}}
        {{-- 🩹 (2026-05-04) เปิดให้กดได้รวม Pay-Later flow (is_paid=false แต่มี deep_response แล้ว) --}}
        @if($showDeepActions)
            <div class="tp-divider" style="margin:18px 0;"></div>

            {{-- 🛟 (2026-05-14) Pay-First incomplete — ลูกค้าจ่ายแต่ยังไม่กรอกข้อมูล --}}
            @if($reading->is_paid && empty($reading->birth_date))
                <div class="tp-inset" style="padding:14px 16px; border-radius:13px; border-left:4px solid {{ $warnColor }}; margin-bottom:14px;">
                    <div style="display:flex; align-items:flex-start; gap:10px;">
                        <span style="color:{{ $warnColor }}; font-size:18px;"><i class="fas fa-life-ring"></i></span>
                        <div style="flex:1; min-width:0;">
                            <p style="color:var(--ink); font-weight:700; margin:0;">รอข้อมูลจากลูกค้า — ยังสร้างคำทำนายไม่ได้</p>
                            <p style="color:var(--ink2); font-size:13px; margin:5px 0 0;">
                                ลูกค้า<strong>จ่าย {{ number_format($reading->amount_paid ?? 39, 0) }}฿ แล้ว</strong> (Pay-First Deep) แต่ยังไม่กรอก
                                <strong>วันเดือนปีเกิด</strong> → AI ทำนายไม่ได้
                            </p>
                            <ul style="color:var(--ink2); font-size:12px; margin:8px 0 0; padding-left:18px; list-style:disc;">
                                <li>วันเกิด: {{ $reading->birth_date ? $reading->birth_date->format('d/m/Y') : '❌ ยังไม่กรอก' }}</li>
                                <li>คำถาม: {{ is_array($reading->questions) && count($reading->questions) > 0 ? count($reading->questions).' คำถาม' : '❌ ยังไม่ถาม' }}</li>
                            </ul>
                            <p style="color:var(--ink); font-size:13px; margin:8px 0 0;">
                                💡 ใช้ปุ่ม <strong>"ส่งขอวันเกิดใหม่"</strong> ด้านล่างเพื่อ push message ให้ลูกค้ากรอกข้อมูล
                            </p>
                        </div>
                    </div>
                </div>
            @endif

            {{-- ⚠️ ชำระแล้วแต่ AI สร้างคำทำนายไม่สำเร็จ (มีวันเกิดแล้ว แต่ deep_response ว่าง) --}}
            @if($reading->is_paid && $reading->conversation_status === 'completed' && empty($reading->deep_response) && ! empty($reading->birth_date))
                <div class="tp-inset" style="padding:14px 16px; border-radius:13px; border-left:4px solid {{ $dangColor }}; margin-bottom:14px;">
                    <div style="display:flex; align-items:flex-start; gap:10px;">
                        <span style="color:{{ $dangColor }}; font-size:18px;"><i class="fas fa-triangle-exclamation"></i></span>
                        <div style="min-width:0;">
                            <p style="color:var(--ink); font-weight:700; margin:0;">ลูกค้าชำระเงินแล้วแต่ยังไม่มีคำทำนายเชิงลึก!</p>
                            <p style="color:var(--ink2); font-size:13px; margin:5px 0 0;">อาจเกิดจาก: AI API quota หมด, API key หมดอายุ, หรือ provider ล่ม กรุณากดปุ่ม "สร้างคำทำนายเชิงลึก" ด้านล่าง</p>
                            <p style="color:var(--ink2); font-size:11.5px; margin:5px 0 0;">ตรวจสอบ log: <code style="background:var(--bg); padding:1px 5px; border-radius:4px;">storage/logs/fortune-deep-{{ $reading->id }}.log</code></p>
                        </div>
                    </div>
                </div>
            @endif

            <p class="tp-muted" style="font-size:13px; margin:0 0 12px;">จัดการคำทำนายเชิงลึก (Manual)</p>

            {{-- ⏳ AI กำลังสร้าง (paid แต่ยังไม่มี deep_response) --}}
            @if($aiProcessing)
                <div id="ai-processing-banner" class="tp-inset" style="padding:18px; border-radius:16px; border:2px solid var(--accent1); margin-bottom:16px;">
                    <div style="display:flex; align-items:flex-start; gap:16px;">
                        <div style="flex-shrink:0;">
                            <div style="width:34px; height:34px; border:3px solid var(--accent1); border-top-color:transparent; border-radius:50%; animation:spin 1s linear infinite;"></div>
                        </div>
                        <div style="flex:1; min-width:0;">
                            <div style="display:flex; align-items:center; gap:9px; margin-bottom:5px; flex-wrap:wrap;">
                                <p class="tp-num" style="color:var(--ink); font-weight:800; font-size:17px; margin:0;">🔮 AI กำลังสร้างคำทำนาย</p>
                                <span class="tp-pill tp-pill-gold">{{ $primaryProvider }}</span>
                            </div>
                            <p style="color:var(--ink2); font-size:13px; margin:0;">
                                ระบบกำลังประมวลผลคำทำนายเชิงลึก — <b>แอดมินไม่ต้องทำอะไร</b> หน้าจะรีเฟรชอัตโนมัติเมื่อเสร็จ
                            </p>
                            <p style="color:var(--ink2); font-size:11.5px; margin:5px 0 0;">
                                ⏱️ รอมาแล้ว <span id="ai-elapsed">{{ $paidElapsedSec }}</span> วินาที (ปกติใช้ 30-60 วินาที)
                            </p>
                        </div>
                    </div>
                    <div style="margin-top:14px;">
                        <div style="width:100%; background:var(--bg); border-radius:999px; height:8px; box-shadow:var(--inset-sm);">
                            <div id="ai-progress-bar" style="background:linear-gradient(90deg,var(--accent1),var(--accent2)); height:8px; border-radius:999px; transition:all 1s; width:5%;"></div>
                        </div>
                    </div>
                </div>
            @endif

            {{-- ปุ่ม Action (POST forms) --}}
            <div style="display:flex; flex-wrap:wrap; gap:12px; align-items:center;" x-data="{ submitting: false }">
                @if(empty($reading->deep_response))
                    {{-- 🛟 (2026-05-14) ลูกค้าจ่ายแล้วแต่ยังไม่กรอกวันเกิด → recover แทน retry --}}
                    @if($reading->is_paid && empty($reading->birth_date))
                        <form action="{{ route('admin.fortune.readings.recover-pay-first', $reading) }}"
                              method="POST"
                              @submit="if(!confirm('ส่งข้อความ \'ขอวันเกิด\' ให้ลูกค้าใหม่หรือไม่?\n\nระบบจะ push message ผ่าน POST_PURCHASE_UPDATE — ลูกค้าจะได้รับใน Messenger/LINE')) { $event.preventDefault(); return; } submitting = true;"
                              style="margin:0;">
                            @csrf
                            <button type="submit" :disabled="submitting" class="tp-btn" style="color:{{ $warnColor }};">
                                <template x-if="!submitting"><span><i class="fas fa-life-ring"></i> ส่งขอวันเกิดใหม่</span></template>
                                <template x-if="submitting"><span style="display:inline-flex; align-items:center; gap:8px;"><span style="width:15px; height:15px; border:2px solid currentColor; border-top-color:transparent; border-radius:50%; animation:spin 1s linear infinite;"></span> กำลังส่งคำสั่ง...</span></template>
                            </button>
                        </form>
                        <p class="tp-muted" style="font-size:12px; margin:0;">💡 ยังสร้างคำทำนายไม่ได้ — รอลูกค้ากรอกวันเกิดก่อน</p>
                    @else
                        {{-- มีข้อมูลครบ → ปุ่มสร้างใหม่ (เด่น) --}}
                        <form action="{{ route('admin.fortune.readings.retry-deep', $reading) }}"
                              method="POST"
                              @submit="if(!confirm('ต้องการสร้างคำทำนายเชิงลึกและส่งให้ลูกค้าหรือไม่?\n\nระบบจะสร้างคำทำนายใหม่โดย AI และส่งให้ลูกค้าอัตโนมัติ (ใช้เวลา 30-60 วินาที)')) { $event.preventDefault(); return; } submitting = true;"
                              style="margin:0;">
                            @csrf
                            <button type="submit" :disabled="submitting" class="tp-btn tp-btn-primary">
                                <template x-if="!submitting"><span><i class="fas fa-arrows-rotate"></i> สร้างคำทำนายเชิงลึก + ส่งให้ลูกค้า</span></template>
                                <template x-if="submitting"><span style="display:inline-flex; align-items:center; gap:8px;"><span style="width:15px; height:15px; border:2px solid #fff; border-top-color:transparent; border-radius:50%; animation:spin 1s linear infinite;"></span> กำลังส่งคำสั่ง...</span></template>
                            </button>
                        </form>
                    @endif
                @else
                    {{-- มีคำทำนายแล้ว → ปุ่มส่งซ้ำ + ปุ่มสร้างใหม่ --}}
                    <form action="{{ route('admin.fortune.readings.resend-deep', $reading) }}"
                          method="POST"
                          @submit="if(!confirm('ส่งคำทำนายที่มีอยู่ให้ลูกค้าซ้ำหรือไม่?')) { $event.preventDefault(); return; } submitting = true;"
                          style="margin:0;">
                        @csrf
                        <button type="submit" :disabled="submitting" class="tp-btn" style="color:{{ $okColor }};">
                            <template x-if="!submitting"><span><i class="fas fa-paper-plane"></i> ส่งคำทำนายซ้ำให้ลูกค้า</span></template>
                            <template x-if="submitting"><span style="display:inline-flex; align-items:center; gap:8px;"><span style="width:15px; height:15px; border:2px solid currentColor; border-top-color:transparent; border-radius:50%; animation:spin 1s linear infinite;"></span> กำลังส่ง...</span></template>
                        </button>
                    </form>
                    <form action="{{ route('admin.fortune.readings.retry-deep', $reading) }}"
                          method="POST"
                          @submit="if(!confirm('ต้องการสร้างคำทำนายเชิงลึกใหม่ทั้งหมดหรือไม่?\n\n⚠️ คำทำนายเดิมจะถูกลบ และระบบจะสร้างคำทำนายใหม่โดย AI (ใช้เวลา 30-60 วินาที)')) { $event.preventDefault(); return; } submitting = true;"
                          style="margin:0;">
                        @csrf
                        <button type="submit" :disabled="submitting" class="tp-btn" style="color:#d6824a;">
                            <template x-if="!submitting"><span><i class="fas fa-arrows-rotate"></i> สร้างคำทำนายใหม่</span></template>
                            <template x-if="submitting"><span style="display:inline-flex; align-items:center; gap:8px;"><span style="width:15px; height:15px; border:2px solid currentColor; border-top-color:transparent; border-radius:50%; animation:spin 1s linear infinite;"></span> กำลังส่งคำสั่ง...</span></template>
                        </button>
                    </form>
                @endif
            </div>
        @endif
    </div>

    {{-- ===== 🤖 (2026-09-02) Admin Ask AI — เลน Deep 39 (คู่แฝดหน้า Celtic) ===== --}}
    @if ($reading->reading_type === 'deep' && $reading->is_paid && ! empty($reading->deep_response) && ! empty($reading->birth_date))
        @php $deepAskAiUrl = route('admin.fortune.readings.ask-ai', $reading); @endphp
        <div x-data="adminAskAiDeep()" class="tp-card" style="padding:24px;">
            <div style="display:flex; align-items:flex-start; justify-content:space-between; margin-bottom:16px; flex-wrap:wrap; gap:10px;">
                <div>
                    <h2 class="tp-section-h" style="font-size:18px; font-weight:800; display:flex; align-items:center; gap:9px; margin:0;">
                        🤖 Admin Ask AI
                        <span class="tp-pill tp-pill-soft" style="font-size:11px; font-weight:500;">ดูดวง 39฿ · sync</span>
                    </h2>
                    <p class="tp-muted" style="font-size:13px; margin:6px 0 0;">
                        แอดมินพิมพ์คำถามแทนลูกค้า → แม่หมอตอบจากดวงจริง + ไพ่ + คำทำนายเดิมของบิลนี้ → ส่งให้ลูกค้าทันที
                        · ไม่ตัดเวลา/โควตาลูกค้า · ไม่เปิดหรือต่อ session
                    </p>
                </div>
                <div style="text-align:right; font-size:12px; color:var(--ink2);">
                    🕛 เวลาเกิดที่ใช้ผูกดวง: <strong>{{ substr((string) ($reading->birth_time ?: '12:00'), 0, 5) }} น.</strong>
                    <span style="opacity:.75;">{{ $reading->birthTimeIsKnown() ? '(เจ้าชะตาบอกเอง)' : '(ค่ามาตรฐาน — ยังไม่ได้บอกเวลาเกิด)' }}</span>
                </div>
            </div>

            <label style="display:block; font-size:12px; font-weight:700; color:var(--ink2); margin-bottom:8px;">
                📝 คำถามที่จะส่งให้แม่หมอตอบ (สูงสุด 1000 ตัวอักษร)
            </label>
            <div class="tp-well tp-input" style="padding:0;">
                <textarea x-model="question" rows="3" maxlength="1000" :disabled="running"
                          placeholder="เช่น ปีนี้จะได้ย้ายงานไหม / ความรักช่วงนี้เป็นยังไง"
                          style="width:100%; background:transparent; border:0; outline:0; padding:11px 13px; color:var(--ink); font-size:14px; resize:vertical;"></textarea>
            </div>
            <div style="display:flex; gap:12px; align-items:center; margin-top:12px; flex-wrap:wrap;">
                <button type="button" class="tp-btn tp-btn-primary" @click="run()" :disabled="running || question.trim().length < 3">
                    <template x-if="!running"><span><i class="fas fa-paper-plane"></i> ส่งให้แม่หมอตอบ + ส่งถึงลูกค้า</span></template>
                    <template x-if="running"><span style="display:inline-flex; align-items:center; gap:8px;"><span style="width:15px; height:15px; border:2px solid #fff; border-top-color:transparent; border-radius:50%; animation:spin 1s linear infinite;"></span> แม่หมอกำลังตอบ… <span x-text="elapsedDisplay"></span></span></template>
                </button>
                <span class="tp-muted" style="font-size:12px;">รอ 30-60 วินาที</span>
            </div>

            <template x-if="result">
                <div style="margin-top:16px;">
                    <template x-if="result.success">
                        <div class="tp-well" style="padding:14px; border-left:4px solid #2e9e5b;">
                            <div style="font-weight:700; color:#2e9e5b; margin-bottom:6px;">
                                ✅ ตอบแล้ว <span x-text="result.pushed ? '· ส่งถึงลูกค้าแล้ว (' + (result.platform || '') + ')' : '· ⚠️ ส่งถึงลูกค้าไม่สำเร็จ'"></span>
                                <span class="tp-muted" style="font-weight:500;" x-text="'· ' + (result.response_len || 0) + ' ตัวอักษร · ' + Math.round((result.elapsed_ms || 0)/1000) + 's'"></span>
                            </div>
                            <div style="white-space:pre-wrap; font-size:13.5px; line-height:1.6; color:var(--ink);" x-text="result.response_full"></div>
                        </div>
                    </template>
                    <template x-if="!result.success">
                        <div class="tp-well" style="padding:14px; border-left:4px solid #d64545; color:#d64545; font-weight:600;">
                            ❌ <span x-text="result.message || 'ไม่สำเร็จ'"></span>
                        </div>
                    </template>
                </div>
            </template>
        </div>
    @endif

    {{-- ════════ สถิติ ════════ --}}
    <div class="tp-card" style="padding:20px;">
        <div class="tp-section-h" style="display:flex; align-items:center; gap:10px; margin-bottom:16px;">
            <span class="tp-tile" style="width:34px; height:34px; border-radius:11px; font-size:14px; display:inline-flex; align-items:center; justify-content:center;">
                <i class="fas fa-chart-simple"></i>
            </span>
            <span class="tp-num" style="font-weight:800; font-size:16px;">สถิติ</span>
        </div>
        <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(180px,1fr)); gap:14px;">
            <div>
                <div class="tp-muted" style="font-size:12px; margin-bottom:5px;">จำนวนการดู</div>
                <div class="tp-num" style="font-weight:800; font-size:20px; color:var(--ink);">{{ number_format($reading->view_count) }}</div>
            </div>
            <div>
                <div class="tp-muted" style="font-size:12px; margin-bottom:5px;">คะแนน</div>
                <div style="color:var(--ink); padding-top:3px; font-size:15px;">{{ $reading->rating ? $reading->getRatingStars() : '-' }}</div>
            </div>
            <div>
                <div class="tp-muted" style="font-size:12px; margin-bottom:5px;">ตอบกลับเมื่อ</div>
                <div style="color:var(--ink); padding-top:3px;">{{ $reading->responded_at ? $reading->responded_at->format('d/m/Y H:i') : 'ยังไม่ตอบ' }}</div>
            </div>
        </div>
    </div>

</div>
@endsection

@push('scripts')
{{-- 🎡 keyframe spin สำหรับ spinner (กันกรณี theme-v4.css ไม่มี) --}}
<style>
    @keyframes spin { to { transform: rotate(360deg); } }
    [x-cloak] { display: none !important; }
</style>

{{-- ⏳ Polling: เช็ค status endpoint ทุก 3 วิ — reload เฉพาะตอน deep_response มี --}}
{{-- (เดิม reload ทุก 5 วิ — รบกวน scroll/copy ของ admin) --}}
@if($aiProcessing)
<script>
    (function () {
        // 🔗 endpoint สถานะ + element references
        const statusUrl       = '{{ route('admin.fortune.readings.status', $reading) }}';
        const progressBar     = document.getElementById('ai-progress-bar');
        const banner          = document.getElementById('ai-processing-banner');
        const elapsedDisplay  = document.getElementById('ai-elapsed');
        const initialElapsed  = {{ $paidElapsedSec ?? 0 }};
        const startedAt       = Date.now();
        const maxWaitMs       = 180 * 1000; // 3 นาที

        const tick = setInterval(async () => {
            const elapsedSec   = (Date.now() - startedAt) / 1000;
            const totalElapsed = Math.round(initialElapsed + elapsedSec);

            // อัพเดท elapsed counter ทุก 1 วินาที
            if (elapsedDisplay) {
                elapsedDisplay.textContent = totalElapsed;
            }

            // อัพเดท progress bar (visual only)
            if (progressBar) {
                const progress = Math.min(95, (totalElapsed / 60) * 100);
                progressBar.style.width = progress + '%';
            }

            // ตรวจ timeout
            if (Date.now() - startedAt > maxWaitMs) {
                clearInterval(tick);
                if (banner) {
                    banner.innerHTML = '<p style="color:#d6824a; font-weight:600; margin:0;">⚠️ AI ใช้เวลานานกว่าปกติ — กรุณารีเฟรชหน้าด้วยตัวเอง หรือกดปุ่มสร้างคำทำนายใหม่</p>';
                }
                return;
            }

            // เช็ค status (silent — ไม่รบกวน UI)
            try {
                const res = await fetch(statusUrl, {
                    headers: { 'Accept': 'application/json' },
                    cache: 'no-store',
                });
                if (!res.ok) return;
                const data = await res.json();
                if (data.ready) {
                    clearInterval(tick);
                    if (progressBar) progressBar.style.width = '100%';
                    if (banner) {
                        banner.innerHTML = '<p style="color:#5aa07e; font-weight:600; margin:0;">✅ คำทำนายเสร็จแล้ว — กำลังโหลดผล...</p>';
                    }
                    // หน่วงสั้น ๆ ให้ user เห็น "เสร็จแล้ว" ก่อน reload
                    setTimeout(() => window.location.reload(), 600);
                }
            } catch (e) {
                // เงียบ — รอบหน้าจะลองใหม่
            }
        }, 3000);
    })();
</script>
@endif
<script>
// 🤖 (2026-09-02) Admin Ask AI — เลน Deep 39 (โครงเดียวกับ adminAskAi ของหน้า Celtic)
function adminAskAiDeep() {
    return {
        question: '',
        running: false,
        result: null,
        startedAt: null,
        elapsedDisplay: '0s',
        timerInterval: null,

        async run() {
            if (this.running || this.question.trim().length < 3) return;
            if (!confirm('ส่งคำถามนี้ให้แม่หมอตอบแทนลูกค้า?\n\nคำถาม: ' + this.question.substring(0, 200) +
                         '\n\nไม่ตัดเวลา/โควตาลูกค้า\nตอบเสร็จส่งถึงลูกค้าทาง LINE/FB อัตโนมัติ\nรอ 30-60 วินาที')) {
                return;
            }

            this.running = true;
            this.result = null;
            this.startedAt = Date.now();
            this.elapsedDisplay = '0s';
            this.timerInterval = setInterval(() => {
                this.elapsedDisplay = Math.floor((Date.now() - this.startedAt) / 1000) + 's';
            }, 1000);

            try {
                const res = await fetch('{{ $deepAskAiUrl ?? '' }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({ question: this.question }),
                });

                if (!res.ok) {
                    const errBody = await res.json().catch(() => ({ message: 'HTTP ' + res.status }));
                    this.result = { success: false, message: errBody.message || ('HTTP ' + res.status) };
                } else {
                    this.result = await res.json();
                    if (this.result.success) this.question = '';
                }
            } catch (e) {
                this.result = { success: false, message: 'Network error: ' + e.message };
            } finally {
                this.running = false;
                if (this.timerInterval) { clearInterval(this.timerInterval); this.timerInterval = null; }
            }
        },
    };
}
</script>
@endpush
