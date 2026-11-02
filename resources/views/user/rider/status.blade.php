{{--
    ติดตามสถานะการสมัครไรเดอร์ (user.rider.status) — ธีมโนวาเต็ม
    Controller: User\RiderController@status (อนุมัติแล้ว → แดชบอร์ด)
    ตัวแปร: rider, riderData, documents [{type,label,uploaded,required,url}], missingDocuments, missingDocumentLabels,
            canReapply, pageTitle
--}}
@extends('layouts.user-nova')

@section('title', $pageTitle ?? 'ติดตามสถานะการสมัคร')

@push('styles')
    <link rel="stylesheet" href="{{ asset('theme-nova/member/rider.css') }}?v={{ filemtime(public_path('theme-nova/member/rider.css')) }}">
@endpush

@php
    $ui = \App\Support\RiderWebUi::class;
    $docsComplete = count($missingDocuments) === 0;

    // สถานะ → ไอคอน / โทนสี (pill + badge) / ข้อความ
    $statusMap = [
        'pending' => ['icon' => 'clock', 'pill' => 'warn', 'badge' => '', 'label' => 'รอตรวจสอบ',
            'desc' => $docsComplete ? 'ทีมงานกำลังตรวจสอบใบสมัครและเอกสารของคุณ จะแจ้งผลผ่านการแจ้งเตือนโดยเร็ว' : 'อัปโหลดเอกสารให้ครบ ทีมงานจึงจะเริ่มตรวจใบสมัครได้'],
        'rejected' => ['icon' => 'x', 'pill' => 'bad', 'badge' => 'is-bad', 'label' => 'ไม่ผ่านการอนุมัติ',
            'desc' => $rider->rejection_reason ?: 'กรุณาแก้ไขข้อมูลแล้วส่งใบสมัครใหม่ หรือติดต่อทีมงานเพื่อสอบถาม'],
        'suspended' => ['icon' => 'lock', 'pill' => 'bad', 'badge' => 'is-bad', 'label' => 'ถูกระงับ',
            'desc' => $rider->suspension_reason ?: 'บัญชีไรเดอร์ถูกระงับชั่วคราว กรุณาติดต่อทีมงาน'],
        'inactive' => ['icon' => 'moon', 'pill' => 'glass', 'badge' => 'is-mute', 'label' => 'ไม่ได้ใช้งาน',
            'desc' => 'บัญชีไรเดอร์ปิดอยู่ ส่งใบสมัครใหม่เพื่อกลับมารับงาน'],
        'approved' => ['icon' => 'check', 'pill' => 'ok', 'badge' => 'is-ok', 'label' => 'อนุมัติแล้ว',
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
@endphp

@section('content')
<div class="nvm-stack">
    @include('user.rider.partials.nav', ['rider' => $rider, 'active' => 'status'])

    {{-- ── ฮีโร่: สถานะปัจจุบัน ──────────────────────────────── --}}
    <section class="nvm-ph" aria-labelledby="nvm-rd-st-h">
        <div class="nvm-ph__in">
            <div class="nvm-ph__txt">
                <div class="nvm-eyebrow nvm-latin">Rider application</div>
                <h1 id="nvm-rd-st-h">สถานะใบสมัครไรเดอร์</h1>
                <p class="nvm-ph__lead">ติดตามขั้นตอนการตรวจใบสมัครและเอกสารของคุณ</p>
                <div class="nvm-pills">
                    <span class="nvm-pill nvm-pill--{{ $cur['pill'] }}"><svg class="nvm-ico"><use href="#nvm-i-{{ $cur['icon'] }}"/></svg>{{ $cur['label'] }}</span>
                </div>
            </div>
            <img class="nvm-ph__art" src="{{ asset('images/nova/svc/rider.webp') }}" alt="" width="200" height="200">
        </div>
    </section>

    <section class="nvm-card nvm-box" aria-labelledby="nvm-rd-cur-h">
        <div class="nvm-rd-head">
            <span class="nvm-rd-badge {{ $cur['badge'] }}"><svg class="nvm-ico"><use href="#nvm-i-{{ $cur['icon'] }}"/></svg></span>
            <div>
                <h2 id="nvm-rd-cur-h">{{ $cur['label'] }}</h2>
                <p>{{ $cur['desc'] }}</p>
            </div>
        </div>
        <div class="nvm-rd-acts nvm-gap-t">
            @if($canReapply)
                <a href="{{ route('user.rider.register') }}" class="nvm-btn nvm-btn--gold"><svg class="nvm-ico"><use href="#nvm-i-send"/></svg>แก้ไขและส่งใบสมัครใหม่</a>
            @elseif(!$docsComplete)
                <a href="{{ route('user.rider.documents') }}" class="nvm-btn nvm-btn--gold"><svg class="nvm-ico"><use href="#nvm-i-up"/></svg>อัปโหลดเอกสารที่ขาด</a>
            @elseif($rider->status === 'pending')
                <a href="{{ route('user.rider.register') }}" class="nvm-btn nvm-btn--line"><svg class="nvm-ico"><use href="#nvm-i-send"/></svg>แก้ไขใบสมัคร</a>
            @endif
            @if($rider->status === 'suspended')
                <a href="{{ route('user.rider.dashboard') }}" class="nvm-btn nvm-btn--line"><svg class="nvm-ico"><use href="#nvm-i-home"/></svg>ไปแดชบอร์ด</a>
            @endif
        </div>
    </section>

    {{-- ── ขั้นตอนการสมัคร ──────────────────────────────────── --}}
    <section class="nvm-card nvm-box" aria-labelledby="nvm-rd-steps-h">
        <div class="nvm-sec-h"><div><small class="nvm-latin">Steps</small><h2 id="nvm-rd-steps-h">ขั้นตอนการสมัคร</h2></div></div>
        @include('user.rider.partials.steps', ['steps' => $steps, 'stepsLabel' => 'ขั้นตอนการสมัคร'])
    </section>

    <div class="nvm-split">
        {{-- ── เอกสาร ────────────────────────────────────────── --}}
        <section class="nvm-card nvm-box" aria-labelledby="nvm-rd-docs-h">
            <div class="nvm-sec-h">
                <div><small class="nvm-latin">Documents</small><h2 id="nvm-rd-docs-h">เอกสาร</h2></div>
                <a href="{{ route('user.rider.documents') }}">จัดการเอกสาร<svg class="nvm-ico"><use href="#nvm-i-chev"/></svg></a>
            </div>
            @if(!$docsComplete)
                <div class="nvm-status is-warn">
                    <svg class="nvm-ico"><use href="#nvm-i-info"/></svg>
                    <span>ยังขาด: {{ $missingDocumentLabels }}</span>
                </div>
            @endif
            <ul class="nvm-txs">
                @foreach($documents as $doc)
                    <li class="nvm-tx">
                        <span class="nvm-tx__ic {{ $doc['uploaded'] ? 'is-in' : ($doc['required'] ? 'is-due' : '') }}"><svg class="nvm-ico"><use href="#nvm-i-{{ $ui::novaDocumentIcon($doc['type']) }}"/></svg></span>
                        <div class="nvm-tx__t"><b>{{ $doc['label'] }}</b><small>{{ $doc['required'] ? 'จำเป็น' : 'ไม่บังคับ' }}</small></div>
                        <div class="nvm-tx__r">
                            <span class="nvm-chip {{ $doc['uploaded'] ? 'nvm-chip--ok' : ($doc['required'] ? 'nvm-chip--warn' : '') }}">
                                {{ $doc['uploaded'] ? 'อัปโหลดแล้ว' : ($doc['required'] ? 'ยังไม่อัปโหลด' : 'ไม่บังคับ') }}
                            </span>
                        </div>
                    </li>
                @endforeach
            </ul>
        </section>

        <div class="nvm-stack">
            {{-- ── ข้อมูลการสมัคร ─────────────────────────────── --}}
            <section class="nvm-card nvm-box" aria-labelledby="nvm-rd-app-h">
                <div class="nvm-sec-h"><div><small class="nvm-latin">Application</small><h2 id="nvm-rd-app-h">ข้อมูลการสมัคร</h2></div></div>
                <dl class="nvm-kv">
                    <dt>ชื่อ-นามสกุล</dt><dd>{{ $rider->full_name ?: '—' }}</dd>
                    <dt>เบอร์โทร</dt><dd class="nvm-num">{{ $rider->phone ?: '—' }}</dd>
                    <dt>ยานพาหนะ</dt><dd>{{ $rider->vehicle_type_text.($rider->vehicle_plate ? ' · '.$rider->vehicle_plate : '') }}</dd>
                    <dt>วันที่สมัคร</dt><dd class="nvm-num">{{ $ui::date($rider->created_at) }}</dd>
                </dl>
            </section>

            {{-- ── ช่วยเหลือ ──────────────────────────────────── --}}
            <a class="nvm-card nvm-alert nvm-alert--info" href="{{ route('user.tickets.create') }}">
                <img class="nvm-alert__img" src="{{ asset('images/nova/account/support.webp') }}" alt="" width="58" height="58" loading="lazy">
                <div class="nvm-alert__txt"><b>มีคำถามเรื่องการสมัคร?</b><p>ติดต่อทีมงานได้ทันที เราพร้อมช่วยให้คุณเริ่มรับงานได้เร็วที่สุด</p></div>
            </a>
        </div>
    </div>
</div>
@endsection
