{{--
    User Rider Status - ติดตามสถานะการสมัครไรเดอร์ (Theme V4 "นวลทองคำ")
    แสดงสถานะและขั้นตอนการสมัครของไรเดอร์
--}}

@extends('layouts.user-v4')

@section('title', 'ติดตามสถานะการสมัคร')

@php
    // ── เอกสารที่อัพโหลดแล้วหรือยัง (ใช้คุม progress) ─────────────
    $hasDocuments = $rider->id_card_image || $rider->driver_license_image;

    // ── map สถานะ → ข้อมูลแสดงผล (ไอคอน, สี hex, ป้าย, คำอธิบาย) ──
    $statusMap = [
        'pending' => [
            'icon'  => 'fa-clock',
            'color' => '#e0a52e',
            'label' => 'รอตรวจสอบ',
            'desc'  => 'ทีมงานกำลังตรวจสอบข้อมูลของคุณ',
        ],
        'rejected' => [
            'icon'  => 'fa-times-circle',
            'color' => '#d9534f',
            'label' => 'ไม่ผ่านการอนุมัติ',
            'desc'  => $rider->rejection_reason ?? 'กรุณาติดต่อทีมงานเพื่อขอข้อมูลเพิ่มเติม',
        ],
        'suspended' => [
            'icon'  => 'fa-ban',
            'color' => 'var(--ink2)',
            'label' => 'ถูกระงับ',
            'desc'  => 'บัญชีของคุณถูกระงับชั่วคราว กรุณาติดต่อทีมงาน',
        ],
        'approved' => [
            'icon'  => 'fa-circle-check',
            'color' => '#5aa07e',
            'label' => 'อนุมัติแล้ว',
            'desc'  => 'ยินดีด้วย! คุณพร้อมเริ่มรับงานแล้ว',
        ],
    ];
    $cur = $statusMap[$rider->status] ?? $statusMap['pending'];

    // ── ความกว้างแถบความคืบหน้า ───────────────────────────────
    $progressWidth = $rider->status === 'approved' ? '100%' : ($hasDocuments ? '50%' : '25%');

    // ── เอกสารที่ต้องอัพโหลด ──────────────────────────────────
    $docList = [
        ['key' => 'id_card_image',             'label' => 'บัตรประชาชน', 'icon' => 'fa-id-card'],
        ['key' => 'driver_license_image',      'label' => 'ใบขับขี่',     'icon' => 'fa-car'],
        ['key' => 'vehicle_registration_image','label' => 'ทะเบียนรถ',   'icon' => 'fa-file-alt'],
        ['key' => 'profile_image',             'label' => 'รูปโปรไฟล์',   'icon' => 'fa-user'],
    ];
@endphp

@section('content')
<div style="display:flex; flex-direction:column; gap:18px;">

    {{-- ── Hero ─────────────────────────────────────────────── --}}
    <div class="tp-card" style="padding:0; overflow:hidden;">
        <div style="display:flex; flex-wrap:wrap; align-items:center; gap:16px; padding:20px 24px;
                    background:linear-gradient(120deg, color-mix(in srgb, var(--accent1) 16%, transparent), transparent 70%);">
            <span class="tp-tile" style="width:52px; height:52px; border-radius:16px; font-size:24px;"><i class="fas fa-motorcycle" style="color:#fff;"></i></span>
            <div style="flex:1; min-width:200px;">
                <h1 style="font-size:clamp(20px,4vw,26px); font-weight:800; margin:0;">ติดตามสถานะการสมัครไรเดอร์</h1>
                <div style="font-size:12.5px; color:var(--ink2); margin-top:3px;">ข้อมูลการสมัครของคุณกำลังอยู่ระหว่างการตรวจสอบ</div>
            </div>
            <span class="tp-pill" style="color:#fff; background:{{ $cur['color'] }};"><i class="fas {{ $cur['icon'] }}" style="font-size:10px;"></i> {{ $cur['label'] }}</span>
        </div>
    </div>

    {{-- ── การ์ดสถานะปัจจุบัน ───────────────────────────────── --}}
    <div class="tp-card" style="padding:28px 24px; text-align:center;">
        <span class="tp-tile" style="width:88px; height:88px; border-radius:28px; font-size:40px; margin:0 auto; background:color-mix(in srgb, {{ $cur['color'] }} 18%, transparent);">
            <i class="fas {{ $cur['icon'] }}" style="color:{{ $cur['color'] }};"></i>
        </span>
        <h2 style="font-size:clamp(20px,4vw,24px); font-weight:800; margin:16px 0 0; color:{{ $cur['color'] }};">{{ $cur['label'] }}</h2>
        <p style="font-size:13.5px; color:var(--ink2); margin:8px 0 0; max-width:520px; margin-left:auto; margin-right:auto;">{{ $cur['desc'] }}</p>
    </div>

    {{-- ── Timeline / ขั้นตอนการสมัคร ────────────────────────── --}}
    @php
        // ── นิยาม 4 ขั้นตอน พร้อมสถานะ done/active/pending ──────
        $steps = [
            [
                'label' => 'ส่งใบสมัคร',
                'icon'  => 'fa-check',
                'state' => 'done',
                'sub'   => optional($rider->created_at)->thaidate('j M Y'),
            ],
            [
                'label' => 'อัพโหลดเอกสาร',
                'icon'  => $hasDocuments ? 'fa-check' : 'fa-file-alt',
                'state' => $hasDocuments ? 'done' : 'pending',
                'sub'   => null,
            ],
            [
                'label' => $rider->status === 'pending' ? 'รอตรวจสอบ' : ($rider->status === 'approved' ? 'อนุมัติแล้ว' : 'ไม่อนุมัติ'),
                'icon'  => $rider->status === 'approved' ? 'fa-check' : ($rider->status === 'rejected' ? 'fa-times' : 'fa-hourglass-half'),
                'state' => $rider->status === 'approved' ? 'done' : ($rider->status === 'rejected' ? 'rejected' : 'active'),
                'sub'   => null,
            ],
            [
                'label' => 'เริ่มรับงาน',
                'icon'  => 'fa-motorcycle',
                'state' => $rider->status === 'approved' ? 'done' : 'pending',
                'sub'   => null,
            ],
        ];

        // ── สี hex ตาม state ของแต่ละ step ─────────────────────
        $stepColor = function ($state) {
            return match ($state) {
                'done'     => '#5aa07e',
                'active'   => '#e0a52e',
                'rejected' => '#d9534f',
                default    => 'var(--ink2)',
            };
        };
    @endphp
    <div class="tp-card" style="padding:24px;">
        <div class="tp-section-h" style="margin-bottom:20px;">📋 ขั้นตอนการสมัคร</div>

        <div style="position:relative;">
            {{-- เส้นพื้นหลัง --}}
            <div style="position:absolute; top:24px; left:24px; right:24px; height:3px; border-radius:3px; background:color-mix(in srgb, var(--ink2) 18%, transparent);"></div>
            {{-- เส้นความคืบหน้า (ทอง) --}}
            <div style="position:absolute; top:24px; left:24px; height:3px; border-radius:3px; width:calc(({{ $progressWidth }} - 48px) + 24px); max-width:calc(100% - 48px); background:linear-gradient(90deg, var(--accent1), var(--accent2));"></div>

            <div style="position:relative; display:flex; justify-content:space-between; gap:6px;">
                @foreach($steps as $step)
                    @php $sc = $stepColor($step['state']); @endphp
                    <div style="display:flex; flex-direction:column; align-items:center; flex:1; min-width:0; text-align:center;">
                        <span class="tp-tile" style="width:48px; height:48px; border-radius:16px; font-size:18px; background:{{ $sc }};">
                            <i class="fas {{ $step['icon'] }}" style="color:#fff;"></i>
                        </span>
                        <div style="font-size:12px; font-weight:700; margin-top:9px; color:var(--ink);">{{ $step['label'] }}</div>
                        @if($step['sub'])
                            <div class="tp-num" style="font-size:10.5px; color:var(--ink2); margin-top:2px;">{{ $step['sub'] }}</div>
                        @endif
                        @if($loop->index === 1 && !$hasDocuments)
                            <a href="{{ route('user.rider.documents') }}" style="font-size:10.5px; color:var(--deep1); font-weight:700; margin-top:2px; text-decoration:none;">อัพโหลดเลย →</a>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    {{-- ── ข้อมูลการสมัคร ────────────────────────────────────── --}}
    @php
        $infoItems = [
            ['ชื่อ-นามสกุล', $rider->full_name],
            ['เบอร์โทร',      $rider->phone],
            ['ยานพาหนะ',     $rider->vehicle_type_text],
            ['วันที่สมัคร',   optional($rider->created_at)->thaidate('j M Y H:i')],
        ];
    @endphp
    <div class="tp-card" style="padding:22px;">
        <div class="tp-section-h" style="margin-bottom:16px;">👤 ข้อมูลการสมัคร</div>
        <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(200px,1fr)); gap:16px;">
            @foreach($infoItems as [$label, $value])
                <div style="padding:14px 16px; border-radius:14px; box-shadow:var(--inset-sm);">
                    <div style="font-size:11.5px; color:var(--ink2);">{{ $label }}</div>
                    <div style="font-size:14px; font-weight:700; color:var(--ink); margin-top:3px;">{{ $value ?? '—' }}</div>
                </div>
            @endforeach
        </div>
    </div>

    {{-- ── เอกสารที่อัพโหลด ──────────────────────────────────── --}}
    <div class="tp-card" style="padding:22px;">
        <div class="tp-section-h" style="margin-bottom:16px;">📎 เอกสารที่อัพโหลด</div>
        <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(150px,1fr)); gap:14px;">
            @foreach($docList as $doc)
                @php $uploaded = (bool) $rider->{$doc['key']}; @endphp
                <div style="padding:18px 14px; border-radius:16px; text-align:center;
                            box-shadow:var(--inset-sm);
                            border:1.5px solid {{ $uploaded ? 'color-mix(in srgb, #5aa07e 45%, transparent)' : 'color-mix(in srgb, var(--ink2) 18%, transparent)' }};">
                    <span class="tp-tile" style="width:46px; height:46px; border-radius:15px; font-size:19px; margin:0 auto;
                          background:{{ $uploaded ? 'color-mix(in srgb, #5aa07e 18%, transparent)' : 'color-mix(in srgb, var(--ink2) 14%, transparent)' }};">
                        <i class="fas {{ $doc['icon'] }}" style="color:{{ $uploaded ? '#5aa07e' : 'var(--ink2)' }};"></i>
                    </span>
                    <div style="font-size:12.5px; font-weight:700; margin-top:10px; color:{{ $uploaded ? '#5aa07e' : 'var(--ink2)' }};">{{ $doc['label'] }}</div>
                    @if($uploaded)
                        <span class="tp-pill" style="margin-top:8px; color:#5aa07e; background:color-mix(in srgb, #5aa07e 16%, transparent);"><i class="fas fa-check" style="font-size:9px;"></i> อัพโหลดแล้ว</span>
                    @else
                        <span class="tp-pill" style="margin-top:8px; color:var(--ink2); background:color-mix(in srgb, var(--ink2) 12%, transparent);">ยังไม่อัพโหลด</span>
                    @endif
                </div>
            @endforeach
        </div>

        @if(!$rider->id_card_image || !$rider->driver_license_image)
            <div style="text-align:center; margin-top:18px;">
                <a href="{{ route('user.rider.documents') }}" class="tp-btn tp-btn-primary">
                    <i class="fas fa-upload"></i> อัพโหลดเอกสาร
                </a>
            </div>
        @endif
    </div>

    {{-- ── ต้องการความช่วยเหลือ ──────────────────────────────── --}}
    <div class="tp-card" style="padding:22px; box-shadow:var(--inset-sm); border-left:4px solid #5689b8;">
        <div style="display:flex; flex-wrap:wrap; align-items:center; gap:14px;">
            <span class="tp-tile" style="width:46px; height:46px; border-radius:15px; font-size:19px; background:color-mix(in srgb, #5689b8 18%, transparent);">
                <i class="fas fa-question-circle" style="color:#5689b8;"></i>
            </span>
            <div style="flex:1; min-width:220px;">
                <div style="font-size:15px; font-weight:800; color:var(--ink);">มีคำถามหรือต้องการความช่วยเหลือ?</div>
                <div style="font-size:12.5px; color:var(--ink2); margin-top:3px;">หากมีข้อสงสัยเกี่ยวกับการสมัครหรือสถานะ สามารถติดต่อทีมงานได้ทันที</div>
            </div>
            <a href="{{ route('user.tickets.create') }}" class="tp-btn tp-btn-primary">
                <i class="fas fa-headset"></i> ติดต่อทีมงาน
            </a>
        </div>
    </div>

</div>
@endsection
