{{--
    ติดตามสถานะการสมัครไรเดอร์ (user.rider.status) — Theme V4 นวลทองคำ
    Controller: User\RiderController@status (อนุมัติแล้ว → แดชบอร์ด)
    ตัวแปร: rider, riderData, documents [{type,label,uploaded,required,url}], missingDocuments, missingDocumentLabels,
            canReapply, pageTitle
--}}
@extends('layouts.user-v4')

@section('title', $pageTitle ?? 'ติดตามสถานะการสมัคร')

@push('styles')
    @include('user.rider.partials.styles')
@endpush

@php
    $ui = \App\Support\RiderWebUi::class;
    $docsComplete = count($missingDocuments) === 0;

    // สถานะ → ไอคอน / โทนสี / ข้อความ
    $statusMap = [
        'pending' => ['icon' => 'fa-hourglass-half', 'tone' => 'warn', 'label' => 'รอตรวจสอบ',
            'desc' => $docsComplete ? 'ทีมงานกำลังตรวจสอบใบสมัครและเอกสารของคุณ จะแจ้งผลผ่านการแจ้งเตือนโดยเร็ว' : 'อัปโหลดเอกสารให้ครบ ทีมงานจึงจะเริ่มตรวจใบสมัครได้'],
        'rejected' => ['icon' => 'fa-circle-xmark', 'tone' => 'bad', 'label' => 'ไม่ผ่านการอนุมัติ',
            'desc' => $rider->rejection_reason ?: 'กรุณาแก้ไขข้อมูลแล้วส่งใบสมัครใหม่ หรือติดต่อทีมงานเพื่อสอบถาม'],
        'suspended' => ['icon' => 'fa-ban', 'tone' => 'bad', 'label' => 'ถูกระงับ',
            'desc' => $rider->suspension_reason ?: 'บัญชีไรเดอร์ถูกระงับชั่วคราว กรุณาติดต่อทีมงาน'],
        'inactive' => ['icon' => 'fa-moon', 'tone' => 'muted', 'label' => 'ไม่ได้ใช้งาน',
            'desc' => 'บัญชีไรเดอร์ปิดอยู่ ส่งใบสมัครใหม่เพื่อกลับมารับงาน'],
        'approved' => ['icon' => 'fa-circle-check', 'tone' => 'ok', 'label' => 'อนุมัติแล้ว',
            'desc' => 'ยินดีด้วย! คุณพร้อมเริ่มรับงานแล้ว'],
    ];
    $cur = $statusMap[$rider->status] ?? $statusMap['pending'];

    // ขั้นตอนการสมัคร 4 ขั้น
    $steps = [
        ['label' => 'ส่งใบสมัคร', 'state' => 'done', 'sub' => $ui::date($rider->created_at, false)],
        ['label' => 'อัปโหลดเอกสาร', 'state' => $docsComplete ? 'done' : 'current', 'sub' => $docsComplete ? 'ครบแล้ว' : 'ยังขาด '.count($missingDocuments).' รายการ'],
        ['label' => 'ทีมงานตรวจสอบ', 'state' => match ($rider->status) {
            'approved' => 'done',
            'rejected', 'suspended', 'inactive' => 'stopped',
            default => $docsComplete ? 'current' : 'todo',
        }, 'sub' => $cur['label']],
        ['label' => 'เริ่มรับงาน', 'state' => $rider->status === 'approved' ? 'done' : 'todo', 'sub' => null],
    ];

    $infoItems = [
        ['ชื่อ-นามสกุล', $rider->full_name],
        ['เบอร์โทร', $rider->phone],
        ['ยานพาหนะ', $rider->vehicle_type_text.($rider->vehicle_plate ? ' · '.$rider->vehicle_plate : '')],
        ['วันที่สมัคร', $ui::date($rider->created_at)],
    ];
@endphp

@section('content')
<div class="rd-scope">
    @include('user.rider.partials.nav', ['rider' => $rider, 'active' => 'status'])

    {{-- ── การ์ดสถานะปัจจุบัน ───────────────────────────────── --}}
    <section class="tp-card rd-hero rd-tone-{{ $cur['tone'] }}">
        <div class="rd-hero-in" style="flex-direction:column; text-align:center; padding-top:30px; padding-bottom:30px;">
            <span class="rd-ring rd-tone-{{ $cur['tone'] }}" style="--p:100; width:96px; height:96px;">
                <span style="font-size:34px; color:var(--tone);"><i class="fas {{ $cur['icon'] }}"></i></span>
            </span>
            <div>
                <div class="rd-muted rd-small">สถานะใบสมัครไรเดอร์</div>
                <h1 class="rd-h1" style="color:var(--tone);">{{ $cur['label'] }}</h1>
                <p class="rd-muted" style="font-size:13.5px; margin:8px auto 0; max-width:520px; line-height:1.6;">{{ $cur['desc'] }}</p>
            </div>
            <div class="rd-row" style="justify-content:center;">
                @if($canReapply)
                    <a href="{{ route('user.rider.register') }}" class="rd-btn3d rd-tone-gold"><i class="fas fa-paper-plane"></i> แก้ไขและส่งใบสมัครใหม่</a>
                @elseif(!$docsComplete)
                    <a href="{{ route('user.rider.documents') }}" class="rd-btn3d rd-tone-gold"><i class="fas fa-cloud-arrow-up"></i> อัปโหลดเอกสารที่ขาด</a>
                @elseif($rider->status === 'pending')
                    <a href="{{ route('user.rider.register') }}" class="tp-btn"><i class="fas fa-pen-to-square"></i> แก้ไขใบสมัคร</a>
                @endif
                @if($rider->status === 'suspended')
                    <a href="{{ route('user.rider.dashboard') }}" class="tp-btn"><i class="fas fa-gauge-high"></i> ไปแดชบอร์ด</a>
                @endif
            </div>
        </div>
    </section>

    {{-- ── ขั้นตอนการสมัคร ──────────────────────────────────── --}}
    <section class="tp-card rd-stack">
        <h2 class="rd-h2"><i class="fas fa-list-check" style="color:var(--accent1);"></i> ขั้นตอนการสมัคร</h2>
        <div class="rd-steps">
            @foreach($steps as $step)
                <div class="st {{ $step['state'] }}">
                    <div class="bar"></div>
                    <div class="lb">{{ $step['label'] }}@if($step['sub'])<br><span class="rd-muted" style="font-weight:600;">{{ $step['sub'] }}</span>@endif</div>
                </div>
            @endforeach
        </div>
    </section>

    {{-- ── เอกสาร ──────────────────────────────────────────────── --}}
    <section class="tp-card rd-stack">
        <div class="rd-row" style="justify-content:space-between;">
            <h2 class="rd-h2"><i class="fas fa-folder-open" style="color:var(--accent1);"></i> เอกสาร</h2>
            <a href="{{ route('user.rider.documents') }}" class="rd-link rd-small">จัดการเอกสาร <i class="fas fa-arrow-right"></i></a>
        </div>
        @if(!$docsComplete)
            <div class="rd-alert rd-tone-warn">
                <i class="fas fa-circle-exclamation"></i>
                <div><b>ยังขาด:</b> {{ $missingDocumentLabels }}</div>
            </div>
        @endif
        <div class="rd-grid" style="--rd-min:150px; gap:12px;">
            @foreach($documents as $doc)
                @php $docTone = $doc['uploaded'] ? 'ok' : ($doc['required'] ? 'warn' : 'muted'); @endphp
                <div class="rd-item rd-tone-{{ $docTone }}" style="border-top:0; padding:12px; border-radius:16px; box-shadow:var(--inset-sm);">
                    <span class="ic"><i class="fas {{ $ui::documentIcon($doc['type']) }}"></i></span>
                    <span class="main">
                        <span class="ttl" style="display:block; white-space:normal;">{{ $doc['label'] }}</span>
                        <span class="sub" style="display:block; color:var(--tone); font-weight:700;">
                            {{ $doc['uploaded'] ? 'อัปโหลดแล้ว' : ($doc['required'] ? 'ยังไม่อัปโหลด' : 'ไม่บังคับ') }}
                        </span>
                    </span>
                </div>
            @endforeach
        </div>
    </section>

    {{-- ── ข้อมูลการสมัคร ───────────────────────────────────── --}}
    <section class="tp-card rd-stack">
        <h2 class="rd-h2"><i class="fas fa-user" style="color:var(--accent1);"></i> ข้อมูลการสมัคร</h2>
        <div class="rd-grid" style="--rd-min:200px; gap:12px;">
            @foreach($infoItems as [$label, $value])
                <div class="rd-meta" style="flex-direction:column; align-items:flex-start; padding:12px 14px;">
                    <span>{{ $label }}</span>
                    <b style="color:var(--ink); font-size:14px;">{{ $value ?: '—' }}</b>
                </div>
            @endforeach
        </div>
    </section>

    {{-- ── ช่วยเหลือ ─────────────────────────────────────────── --}}
    <section class="rd-alert rd-tone-info">
        <i class="fas fa-circle-question"></i>
        <div style="flex:1;">
            <b>มีคำถามเรื่องการสมัคร?</b>
            <div class="rd-small" style="margin-top:3px;">ติดต่อทีมงานได้ทันที เราพร้อมช่วยให้คุณเริ่มรับงานได้เร็วที่สุด</div>
            <a href="{{ route('user.tickets.create') }}" class="tp-btn tp-btn-sm" style="margin-top:10px;"><i class="fas fa-headset"></i> ติดต่อทีมงาน</a>
        </div>
    </section>
</div>
@endsection
