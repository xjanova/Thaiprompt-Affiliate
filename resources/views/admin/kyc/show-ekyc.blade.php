@extends('layouts.admin-v4')

@section('title', 'ตรวจยืนยันตัวตน eKYC')

@section('content')
{{-- 🪪 หน้าตรวจ AI eKYC (ธีม V4) — รูปหน้าจากบัตร ↔ ใบหน้าตอนยืนยัน · คะแนน AI · ข้อมูลจากบัตร · ตัดสิน --}}
@php
    $kyc = $kycVerification;
    $user = $kyc->user;
    $t = $ekyc['thresholds'];

    $thaiMonths = [1 => 'ม.ค.', 'ก.พ.', 'มี.ค.', 'เม.ย.', 'พ.ค.', 'มิ.ย.', 'ก.ค.', 'ส.ค.', 'ก.ย.', 'ต.ค.', 'พ.ย.', 'ธ.ค.'];
    $thaiDate = function ($date) use ($thaiMonths) {
        return $date ? $date->day.' '.$thaiMonths[$date->month].' '.($date->year + 543) : '-';
    };

    // สีตามคะแนน: ผ่านเกณฑ์ = เขียว · ใกล้เกณฑ์ = ทอง · ต่ำ = แดง
    $tone = function ($value, $pass, $warn = null) {
        if ($value === null) {
            return ['#8a8f98', 'rgba(138,143,152,.18)'];
        }
        if ($value >= $pass) {
            return ['#2f8a5f', 'rgba(47,138,95,.16)'];
        }
        if ($warn !== null && $value >= $warn) {
            return ['#b7791f', 'rgba(183,121,31,.16)'];
        }

        return ['#c0392b', 'rgba(192,57,43,.14)'];
    };

    $cosine = $ekyc['cosine'];
    $matchDisplay = $ekyc['match_score'] ?? $cosine;
    $matchTone = $tone($cosine, $t['auto_approve_match'], $t['review_match']);
    $liveTone = $tone($ekyc['liveness'], $t['min_liveness']);
    $ocrTone = $tone($ekyc['ocr'], $t['min_ocr']);
    $cardTone = $tone($ekyc['card_real'], $t['min_card_real']);
    $realTone = $tone($ekyc['real'], $t['min_real']);

    $challengeTotal = count($ekyc['challenges']);
    $challengePassed = count(array_filter($ekyc['challenges']));
    $challengeNames = \App\Services\Ekyc\EkycService::CHALLENGE_LABELS;

    $statusPill = match ($kyc->status) {
        'pending' => ['⏳ รอตรวจ', 'rgba(224,165,46,.18)', '#a87d1e'],
        'approved' => ['✅ อนุมัติแล้ว', 'rgba(90,160,126,.18)', '#3f7a5c'],
        'rejected' => ['❌ ปฏิเสธ', 'rgba(217,83,79,.16)', '#d9534f'],
        'retake' => ['📸 ให้ถ่ายใหม่', 'rgba(86,137,184,.18)', '#3f6a96'],
        default => [$kyc->status, 'rgba(138,143,152,.18)', '#6b7280'],
    };
    $decisionLabel = match ($kyc->ai_decision) {
        'approved' => 'AI อนุมัติเอง',
        'review' => 'AI ส่งให้คนตรวจ',
        'retake' => 'AI ให้ถ่ายใหม่',
        default => 'ยังไม่ประมวลผล',
    };
    $mainReason = $ekyc['reason_texts'][0] ?? null;
    $corrections = $ekyc['corrections'];
    $imgCardFace = $ekyc['has_card_face'] ? route('admin.kyc.image', [$kyc, 'card_face']) : null;
    $imgBest = $ekyc['has_best_frame'] ? route('admin.kyc.image', [$kyc, 'best_frame']) : null;
    $imgCard = $ekyc['has_card'] ? route('admin.kyc.image', [$kyc, 'card']) : null;
    $canManage = auth()->user()->isSuperAdmin() || auth()->user()->hasPermission('manage_kyc');
    $imageCards = [
        ['รูปจากบัตร (AI ตัดอัตโนมัติ)', $imgCardFace, '3/4'],
        ['ใบหน้าตอนยืนยัน (เฟรมที่ดีที่สุด)', $imgBest, '3/4'],
        ['บัตรประชาชน (รูปที่ผู้ใช้ถ่าย)', $imgCard, '16/10'],
    ];
@endphp

<div class="grid items-start gap-[18px] lg:grid-cols-[280px_minmax(0,1fr)]">

    {{-- ===== คิวตรวจ eKYC (ซ้ายบนจอใหญ่ · ล่างสุดบนมือถือ) ===== --}}
    <aside class="tp-card order-2 lg:order-1" style="padding:18px; min-width:0;">
        <div style="display:flex; align-items:center; justify-content:space-between; gap:10px;">
            <div class="tp-section-h" style="margin:0;"><i class="fas fa-list-check"></i> คิวตรวจ eKYC</div>
            <span class="tp-pill tp-pill-gold">{{ number_format($queue->count()) }} รอตรวจ</span>
        </div>
        <div style="font-size:12px; color:var(--ink2); margin:6px 0 14px;">
            AI อนุมัติเองวันนี้ {{ number_format($aiApprovedToday) }} ราย · รอคน {{ number_format($queue->count()) }} ราย
        </div>

        <div style="display:flex; flex-direction:column; gap:8px;">
            @forelse($queue as $item)
                @php
                    $active = $item->id === $kyc->id;
                    $itemReason = \App\Services\Ekyc\EkycService::reasonTexts((array) ($item->ai_reasons ?? []))[0] ?? 'รอตรวจ';
                    $itemCos = $item->ai_face_match !== null ? number_format($item->ai_face_match, 2) : '-';
                @endphp
                <a href="{{ route('admin.kyc.show', $item) }}" class="tp-well"
                   style="display:flex; align-items:center; gap:10px; padding:10px 12px; text-decoration:none; color:var(--ink); border:1px solid {{ $active ? '#d4a73a' : 'transparent' }};">
                    <span class="tp-tile" style="width:34px; height:34px; border-radius:50%; font-size:13px; font-weight:800; display:flex; align-items:center; justify-content:center; flex-shrink:0;">
                        {{ mb_strtoupper(mb_substr($item->user?->name ?: '?', 0, 1)) }}
                    </span>
                    <span style="min-width:0;">
                        <span style="display:block; font-weight:700; font-size:13.5px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">{{ $item->user?->name ?: 'ไม่ระบุชื่อ' }}</span>
                        <span style="display:block; font-size:11.5px; color:var(--ink2); overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">
                            {{ $itemReason }} · cos {{ $itemCos }} · {{ $item->processed_at?->diffForHumans() }}
                        </span>
                    </span>
                </a>
            @empty
                <div style="text-align:center; color:var(--ink2); font-size:13px; padding:18px 0;">
                    <i class="fas fa-mug-hot" style="display:block; font-size:22px; opacity:.5; margin-bottom:6px;"></i>
                    ไม่มีเคสค้างตรวจ
                </div>
            @endforelse
        </div>

        <div class="tp-divider" style="margin:14px 0;"></div>
        <a href="{{ route('admin.kyc.index', ['ai' => 'review']) }}" class="tp-btn tp-btn-sm" style="width:100%; justify-content:center;">
            <i class="fas fa-filter"></i> ดูทั้งหมดที่ AI ส่งตรวจ
        </a>
    </aside>

    {{-- ===== รายละเอียด (ขวา) ===== --}}
    <div class="order-1 lg:order-2" style="display:flex; flex-direction:column; gap:16px; min-width:0;">

        {{-- หัวเรื่อง --}}
        <div style="display:flex; flex-wrap:wrap; align-items:center; justify-content:space-between; gap:12px;">
            <div style="display:flex; align-items:center; gap:12px; min-width:0;">
                <a href="{{ route('admin.kyc.index') }}" class="tp-icon-btn" title="กลับไปหน้ารายการ"><i class="fas fa-arrow-left"></i></a>
                <div style="min-width:0;">
                    <div style="font-size:11px; color:var(--ink2); font-weight:600; letter-spacing:.4px;">หลังบ้าน · ยืนยันตัวตน · AI eKYC</div>
                    <h1 class="tp-num" style="font-size:clamp(20px,3.6vw,26px); font-weight:800; margin:4px 0 0;">
                        ตรวจยืนยันตัวตน · {{ $user?->name ?: 'ไม่ระบุชื่อ' }}
                    </h1>
                </div>
            </div>
            <div style="display:flex; flex-wrap:wrap; gap:8px; align-items:center;">
                @if($kyc->status === 'pending' && $mainReason)
                    <span class="tp-pill" style="background:rgba(224,165,46,.18); color:#a87d1e; font-weight:700;">
                        <i class="fas fa-circle-info"></i> AI ไม่มั่นใจ: {{ $mainReason }}
                    </span>
                @endif
                <span class="tp-pill" style="background:{{ $statusPill[1] }}; color:{{ $statusPill[2] }}; font-weight:700;">{{ $statusPill[0] }}</span>
                <span class="tp-pill tp-pill-soft">{{ $decisionLabel }}</span>
            </div>
        </div>

        {{-- ข้อความแจ้งผล --}}
        @if(session('success'))
            <div class="tp-card" style="padding:14px 18px; border-left:4px solid #5aa07e; font-size:13.5px;">
                <i class="fas fa-circle-check" style="color:#5aa07e;"></i> {{ session('success') }}
            </div>
        @endif
        @if(session('error'))
            <div class="tp-card" style="padding:14px 18px; border-left:4px solid #d9534f; font-size:13.5px;">
                <i class="fas fa-circle-exclamation" style="color:#d9534f;"></i> {{ session('error') }}
            </div>
        @endif

        {{-- รูป: หน้าจากบัตร · ใบหน้าตอนยืนยัน · บัตร --}}
        <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(min(100%,220px),1fr)); gap:14px;">
            @foreach($imageCards as [$label, $src, $ratio])
                <div class="tp-card" style="padding:14px;">
                    <div style="font-size:12.5px; color:var(--ink2); margin-bottom:10px;">{{ $label }}</div>
                    @if($src)
                        <div style="border-radius:12px; overflow:hidden; background:var(--bg); box-shadow:var(--inset-sm); aspect-ratio:{{ $ratio }}; display:flex; align-items:center; justify-content:center; cursor:zoom-in;"
                             onclick="openEkycImage(this.querySelector('img').src)">
                            <img src="{{ $src }}" alt="{{ $label }}" loading="lazy" referrerpolicy="no-referrer"
                                 style="max-width:100%; max-height:100%; object-fit:contain; display:block;">
                        </div>
                    @else
                        <div class="tp-well" style="aspect-ratio:{{ $ratio }}; display:flex; flex-direction:column; align-items:center; justify-content:center; color:var(--ink2); font-size:12.5px; gap:6px;">
                            <i class="fas fa-image" style="font-size:22px; opacity:.5;"></i> ไม่มีรูปนี้
                        </div>
                    @endif
                </div>
            @endforeach
        </div>

        {{-- คะแนน AI --}}
        <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(min(100%,190px),1fr)); gap:14px;">
            {{-- ใบหน้าตรงกับบัตร --}}
            <div class="tp-card" style="padding:16px;">
                <div style="font-size:12.5px; color:var(--ink2);">ใบหน้าตรงกับบัตร</div>
                <div class="tp-num" style="font-size:30px; font-weight:800; color:{{ $matchTone[0] }}; margin:4px 0 8px;">
                    {{ $matchDisplay !== null ? number_format($matchDisplay, 2) : '-' }}
                </div>
                <div style="height:8px; border-radius:99px; background:var(--bg); box-shadow:var(--inset-sm); overflow:hidden;">
                    <div style="height:100%; width:{{ (int) round(max(0, min(1, (float) ($matchDisplay ?? 0))) * 100) }}%; background:{{ $matchTone[0] }};"></div>
                </div>
                <div style="font-size:11.5px; color:var(--ink2); margin-top:8px; line-height:1.5;">
                    cosine {{ $cosine !== null ? number_format($cosine, 3) : '-' }} · อนุมัติเองเมื่อ ≥ {{ number_format($t['auto_approve_match'], 2) }} · ให้ถ่ายใหม่เมื่อ &lt; {{ number_format($t['retake_match'], 2) }}
                </div>
            </div>

            {{-- เป็นคนจริง --}}
            <div class="tp-card" style="padding:16px;">
                <div style="font-size:12.5px; color:var(--ink2);">เป็นคนจริง (liveness)</div>
                <div class="tp-num" style="font-size:30px; font-weight:800; color:{{ $liveTone[0] }}; margin:4px 0 8px;">
                    {{ $ekyc['liveness'] !== null ? number_format($ekyc['liveness'], 2) : '-' }}
                </div>
                <div style="height:8px; border-radius:99px; background:var(--bg); box-shadow:var(--inset-sm); overflow:hidden;">
                    <div style="height:100%; width:{{ (int) round(max(0, min(1, (float) ($ekyc['liveness'] ?? 0))) * 100) }}%; background:{{ $liveTone[0] }};"></div>
                </div>
                <div style="font-size:11.5px; color:var(--ink2); margin-top:8px; line-height:1.5;">
                    ทำครบ {{ $challengePassed }}/{{ $challengeTotal }} คำสั่ง · หน้าจริง {{ $ekyc['real'] !== null ? number_format($ekyc['real'], 2) : '-' }}
                    <span style="color:{{ $realTone[0] }};">({{ ($ekyc['real'] ?? 0) >= $t['min_real'] ? 'ไม่พบจอ/กระดาษ' : 'สงสัยรูปจากจอ/กระดาษ' }})</span>
                </div>
                @if($challengeTotal > 0)
                    <div style="display:flex; flex-wrap:wrap; gap:5px; margin-top:8px;">
                        @foreach($ekyc['challenges'] as $label => $passed)
                            <span class="tp-pill" style="font-size:11px; background:{{ $passed ? 'rgba(47,138,95,.14)' : 'rgba(192,57,43,.12)' }}; color:{{ $passed ? '#2f8a5f' : '#c0392b' }};">
                                {{ $passed ? '✓' : '✗' }} {{ $challengeNames[$label] ?? $label }}
                            </span>
                        @endforeach
                    </div>
                @endif
            </div>

            {{-- อ่านบัตร --}}
            <div class="tp-card" style="padding:16px;">
                <div style="font-size:12.5px; color:var(--ink2);">อ่านบัตร (OCR)</div>
                <div class="tp-num" style="font-size:30px; font-weight:800; color:{{ $ocrTone[0] }}; margin:4px 0 8px;">
                    {{ $ekyc['ocr'] !== null ? number_format($ekyc['ocr'] * 100).'%' : '-' }}
                </div>
                <div style="height:8px; border-radius:99px; background:var(--bg); box-shadow:var(--inset-sm); overflow:hidden;">
                    <div style="height:100%; width:{{ (int) round(max(0, min(1, (float) ($ekyc['ocr'] ?? 0))) * 100) }}%; background:{{ $ocrTone[0] }};"></div>
                </div>
                <div style="font-size:11.5px; color:var(--ink2); margin-top:8px;">
                    @if($ekyc['checksum_ok'] === true)
                        เลข 13 หลักผ่านหลักตรวจสอบ
                    @elseif($ekyc['checksum_ok'] === false)
                        <span style="color:#c0392b;">เลข 13 หลักไม่ผ่านหลักตรวจสอบ</span>
                    @else
                        อ่านเลขบัตรไม่ได้
                    @endif
                </div>
            </div>

            {{-- บัตรของจริง --}}
            <div class="tp-card" style="padding:16px;">
                <div style="font-size:12.5px; color:var(--ink2);">บัตรของจริง</div>
                <div class="tp-num" style="font-size:30px; font-weight:800; color:{{ $cardTone[0] }}; margin:4px 0 8px;">
                    {{ $ekyc['card_real'] !== null ? number_format($ekyc['card_real'], 2) : '-' }}
                </div>
                <div style="height:8px; border-radius:99px; background:var(--bg); box-shadow:var(--inset-sm); overflow:hidden;">
                    <div style="height:100%; width:{{ (int) round(max(0, min(1, (float) ($ekyc['card_real'] ?? 0))) * 100) }}%; background:{{ $cardTone[0] }};"></div>
                </div>
                <div style="font-size:11.5px; color:var(--ink2); margin-top:8px;">
                    {{ ($ekyc['card_real'] ?? 0) >= $t['min_card_real'] ? 'ไม่พบแสงจอ/ขอบกระดาษ' : 'สงสัยถ่ายจากจอหรือสำเนา' }}
                </div>
            </div>
        </div>

        {{-- ข้อมูลจากบัตร --}}
        <div class="tp-card" style="padding:18px;">
            <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(min(100%,170px),1fr)); gap:14px;">
                <div>
                    <div style="font-size:12px; color:var(--ink2);">ชื่อตามบัตร</div>
                    <div style="font-weight:800; font-size:15px; margin-top:3px;">{{ $kyc->name_th ?: '-' }}</div>
                    @if(isset($corrections['name_th']))
                        <div style="font-size:11.5px; color:#b7791f; margin-top:3px;">
                            ✎ ผู้ใช้แก้เอง (AI อ่านได้: {{ $corrections['name_th']['ocr'] ?? '-' }})
                        </div>
                    @endif
                    @if($kyc->name_en)
                        <div style="font-size:12px; color:var(--ink2); margin-top:2px;">{{ $kyc->name_en }}</div>
                    @endif
                </div>
                <div>
                    <div style="font-size:12px; color:var(--ink2);">ชื่อบัญชี</div>
                    <div style="font-weight:800; font-size:15px; margin-top:3px;">
                        {{ $user?->name ?: '-' }}
                        <span style="font-size:12px; font-weight:700; color:{{ $ekyc['name_matches_account'] ? '#2f8a5f' : '#b7791f' }};">
                            · {{ $ekyc['name_matches_account'] ? 'ตรงกัน' : 'ไม่ตรงกัน' }}
                        </span>
                    </div>
                </div>
                <div>
                    <div style="font-size:12px; color:var(--ink2);">วันเกิด</div>
                    <div style="font-weight:800; font-size:15px; margin-top:3px;">{{ $thaiDate($kyc->birth_date) }}</div>
                    @if(isset($corrections['birth_date']))
                        <div style="font-size:11.5px; color:#b7791f; margin-top:3px;">✎ ผู้ใช้แก้เอง (AI อ่านได้: {{ $corrections['birth_date']['ocr'] ?? '-' }})</div>
                    @endif
                </div>
                <div>
                    <div style="font-size:12px; color:var(--ink2);">บัตรหมดอายุ</div>
                    <div style="font-weight:800; font-size:15px; margin-top:3px;">
                        {{ $ekyc['lifelong'] && ! $kyc->card_expiry ? 'ตลอดชีพ' : $thaiDate($kyc->card_expiry) }}
                    </div>
                </div>
                <div>
                    <div style="font-size:12px; color:var(--ink2);">เลขบัตร (ปิดบางส่วน)</div>
                    <div class="tp-num" style="font-weight:800; font-size:15px; margin-top:3px; letter-spacing:.5px;">{{ $ekyc['id_masked'] ?: '-' }}</div>
                </div>
            </div>
        </div>

        {{-- เหตุผลของ AI + ตัวช่วยตรวจตัวตน --}}
        <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(min(100%,300px),1fr)); gap:14px;">
            <div class="tp-card" style="padding:18px;">
                <div class="tp-section-h" style="margin-bottom:12px;"><i class="fas fa-robot"></i> เหตุผลจาก AI</div>
                @if(empty($ekyc['reason_texts']))
                    <div style="font-size:13px; color:var(--ink2);">ไม่มีข้อสังเกต</div>
                @else
                    <div style="display:flex; flex-wrap:wrap; gap:6px;">
                        @foreach($ekyc['reason_texts'] as $text)
                            <span class="tp-pill" style="background:rgba(224,165,46,.16); color:#8a6514;">{{ $text }}</span>
                        @endforeach
                    </div>
                @endif
                <div style="font-size:11.5px; color:var(--ink2); margin-top:12px;">
                    ประมวลผล {{ $kyc->processed_at ? $kyc->processed_at->format('d/m/Y H:i') : '-' }}
                    · ยินยอม PDPA {{ $kyc->consent_at ? $kyc->consent_at->format('d/m/Y H:i') : '-' }} (ฉบับ {{ $kyc->consent_version ?: '-' }})
                    @if($kyc->ai_model_version)
                        · โมเดล {{ $kyc->ai_model_version }}
                    @endif
                </div>
            </div>

            <div class="tp-card" style="padding:18px;">
                <div class="tp-section-h" style="margin-bottom:12px;"><i class="fas fa-user-check"></i> ชื่อบนบัตรเทียบข้อมูลการเงิน</div>
                @forelse($identityChecks as $check)
                    @php
                        $checkIcon = ['pass' => '✅', 'warn' => '⚠️', 'fail' => '⛔'][$check['status']] ?? '❔';
                    @endphp
                    <div class="tp-well" style="display:flex; gap:10px; padding:10px 12px; margin-bottom:8px;">
                        <span>{{ $checkIcon }}</span>
                        <div style="min-width:0;">
                            <div style="font-size:13px; font-weight:700;">{{ $check['label'] }}</div>
                            <div style="font-size:11.5px; color:var(--ink2);">{{ $check['detail'] }}</div>
                        </div>
                    </div>
                @empty
                    <div style="font-size:13px; color:var(--ink2);">ไม่มีข้อมูลบัญชีธนาคารหรือสลิปให้เทียบ</div>
                @endforelse
            </div>
        </div>

        {{-- การดำเนินการ --}}
        @if($kyc->status === 'pending')
            <div style="display:flex; flex-wrap:wrap; align-items:center; gap:12px;">
                <form action="{{ route('admin.kyc.approve', $kyc) }}" method="POST"
                      onsubmit="return confirm('ยืนยันอนุมัติการยืนยันตัวตนของผู้ใช้นี้?')">
                    @csrf
                    <button type="submit" class="tp-btn tp-btn-primary" style="padding:12px 26px; font-weight:800;">
                        <i class="fas fa-circle-check"></i> อนุมัติ
                    </button>
                </form>
                <button type="button" class="tp-btn" onclick="toggleEkycModal('ekycRejectModal', true)"
                        style="padding:12px 22px; color:#c0392b; border-color:rgba(192,57,43,.45); font-weight:700;">
                    <i class="fas fa-circle-xmark"></i> ปฏิเสธพร้อมเหตุผล
                </button>
                <button type="button" class="tp-btn" onclick="toggleEkycModal('ekycRetakeModal', true)"
                        style="padding:12px 22px; font-weight:700;">
                    <i class="fas fa-camera-rotate"></i> ขอให้ถ่ายหน้าใหม่
                </button>
                <span style="margin-left:auto; font-size:12px; color:var(--ink2);">
                    <i class="fas fa-shield-halved"></i> เปิดดูรูปถูกบันทึกในประวัติการเข้าถึง (PDPA)
                </span>
            </div>
        @else
            <div class="tp-card" style="padding:16px 18px; font-size:13px; color:var(--ink2);">
                ตัดสินแล้ว{{ $kyc->reviewer ? 'โดย '.$kyc->reviewer->name : 'โดย AI' }}
                {{ $kyc->reviewed_at ? ' เมื่อ '.$kyc->reviewed_at->format('d/m/Y H:i') : '' }}
                @if($kyc->rejection_reason)
                    · หมายเหตุ: {{ $kyc->rejection_reason }}
                @endif
            </div>
        @endif

        {{-- ประวัติการเปิดดูรูป (PDPA) --}}
        <div class="tp-card" style="padding:18px;">
            <div class="tp-section-h" style="margin-bottom:10px;"><i class="fas fa-clock-rotate-left"></i> ประวัติการเปิดดูรูปล่าสุด</div>
            @forelse($accessLogs as $log)
                <div style="display:flex; justify-content:space-between; gap:10px; font-size:12.5px; padding:6px 0; border-bottom:1px dashed rgba(138,143,152,.25);">
                    <span>{{ $log->viewer?->name ?? 'ไม่ทราบ' }} · {{ ['card' => 'รูปบัตร', 'card_face' => 'หน้าจากบัตร', 'best_frame' => 'ใบหน้า'][$log->kind] ?? $log->kind }}</span>
                    <span style="color:var(--ink2); white-space:nowrap;">{{ $log->created_at?->format('d/m/Y H:i') }}</span>
                </div>
            @empty
                <div style="font-size:12.5px; color:var(--ink2);">ยังไม่มีการเปิดดู (การเปิดหน้านี้จะถูกบันทึก)</div>
            @endforelse
        </div>

        {{-- ลบข้อมูล --}}
        @if($canManage)
            <div class="tp-card" style="padding:18px; border-left:4px solid #d9534f;">
                <div class="tp-section-h" style="margin-bottom:8px; color:#d9534f;"><i class="fas fa-trash"></i> ลบข้อมูล</div>
                <p style="font-size:12.5px; color:var(--ink2); margin:0 0 12px;">ลบแถวนี้พร้อมรูปบัตรและใบหน้าที่เข้ารหัสไว้ (ย้อนกลับไม่ได้)</p>
                <form action="{{ route('admin.kyc.destroy', $kyc) }}" method="POST"
                      onsubmit="return confirm('ลบข้อมูลการยืนยันตัวตนนี้พร้อมรูปทั้งหมด? การดำเนินการนี้ย้อนกลับไม่ได้')">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="tp-btn tp-btn-sm" style="background:#d9534f; color:#fff; border-color:#d9534f; font-weight:700;">
                        <i class="fas fa-trash"></i> ลบข้อมูลการยืนยันตัวตน
                    </button>
                </form>
            </div>
        @endif
    </div>
</div>

{{-- โมดัลปฏิเสธ --}}
<div id="ekycRejectModal" class="fixed inset-0 z-50 hidden items-center justify-center p-4" style="background:rgba(0,0,0,.55);"
     onclick="if (event.target === this) toggleEkycModal('ekycRejectModal', false)">
    <div class="tp-card" style="max-width:460px; width:100%; padding:22px;">
        <div class="tp-section-h" style="margin-bottom:14px;"><i class="fas fa-circle-xmark"></i> ปฏิเสธการยืนยันตัวตน</div>
        <form action="{{ route('admin.kyc.reject', $kyc) }}" method="POST">
            @csrf
            <label for="ekyc_rejection_reason" style="display:block; font-size:12.5px; color:var(--ink2); font-weight:600; margin-bottom:6px;">
                เหตุผล (ผู้ใช้จะเห็นข้อความนี้) <span style="color:#d9534f;">*</span>
            </label>
            <div class="tp-well tp-input" style="padding:0;">
                <textarea name="rejection_reason" id="ekyc_rejection_reason" rows="4" required maxlength="1000"
                          placeholder="เช่น บัตรไม่ใช่ของเจ้าของบัญชี"
                          style="width:100%; background:transparent; border:none; outline:none; padding:10px 12px; color:var(--ink); font-size:14px; resize:vertical; font-family:inherit;"></textarea>
            </div>
            @error('rejection_reason')
                <p style="font-size:12px; color:#d9534f; margin:6px 0 0;">{{ $message }}</p>
            @enderror
            <div style="display:flex; gap:10px; margin-top:16px;">
                <button type="button" class="tp-btn" style="flex:1; justify-content:center;" onclick="toggleEkycModal('ekycRejectModal', false)">ยกเลิก</button>
                <button type="submit" class="tp-btn" style="flex:1; justify-content:center; background:#d9534f; color:#fff; border-color:#d9534f; font-weight:700;">ปฏิเสธ</button>
            </div>
        </form>
    </div>
</div>

{{-- โมดัลขอให้ถ่ายใหม่ --}}
<div id="ekycRetakeModal" class="fixed inset-0 z-50 hidden items-center justify-center p-4" style="background:rgba(0,0,0,.55);"
     onclick="if (event.target === this) toggleEkycModal('ekycRetakeModal', false)">
    <div class="tp-card" style="max-width:460px; width:100%; padding:22px;">
        <div class="tp-section-h" style="margin-bottom:14px;"><i class="fas fa-camera-rotate"></i> ขอให้ถ่ายบัตรและใบหน้าใหม่</div>
        <form action="{{ route('admin.kyc.request-retake', $kyc) }}" method="POST">
            @csrf
            <label for="ekyc_retake_note" style="display:block; font-size:12.5px; color:var(--ink2); font-weight:600; margin-bottom:6px;">
                ข้อความถึงผู้ใช้ (ไม่บังคับ)
            </label>
            <div class="tp-well tp-input" style="padding:0;">
                <textarea name="retake_note" id="ekyc_retake_note" rows="3" maxlength="500"
                          placeholder="เช่น ถ่ายในที่สว่างขึ้น และไม่สวมแว่นกันแดด"
                          style="width:100%; background:transparent; border:none; outline:none; padding:10px 12px; color:var(--ink); font-size:14px; resize:vertical; font-family:inherit;"></textarea>
            </div>
            <div style="display:flex; gap:10px; margin-top:16px;">
                <button type="button" class="tp-btn" style="flex:1; justify-content:center;" onclick="toggleEkycModal('ekycRetakeModal', false)">ยกเลิก</button>
                <button type="submit" class="tp-btn tp-btn-primary" style="flex:1; justify-content:center; font-weight:700;">ส่งคำขอ</button>
            </div>
        </form>
    </div>
</div>

{{-- โมดัลดูรูปเต็ม --}}
<div id="ekycImageModal" class="fixed inset-0 z-50 hidden items-center justify-center p-4" style="background:rgba(0,0,0,.9);"
     onclick="toggleEkycModal('ekycImageModal', false)">
    <img id="ekycImageFull" src="" alt="รูปเต็ม" style="max-width:100%; max-height:92vh; border-radius:12px; display:block;">
</div>

@push('scripts')
<script>
function toggleEkycModal(id, open) {
    const modal = document.getElementById(id);
    if (!modal) return;
    modal.classList.toggle('hidden', !open);
    modal.classList.toggle('flex', open);
}

function openEkycImage(src) {
    document.getElementById('ekycImageFull').src = src;
    toggleEkycModal('ekycImageModal', true);
}
</script>
@endpush
@endsection
