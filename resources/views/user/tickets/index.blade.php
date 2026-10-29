{{--
    ตั๋วช่วยเหลือของฉัน — รายการ + ตัวกรองสถานะ (ธีมโนวาเต็ม)
    ตัวกรอง active/done/สถานะเดี่ยว มาจาก TicketController::index (ตัวเลข "เปิดอยู่" = ทุกตั๋วที่ยังไม่ปิด)
--}}
@extends('layouts.user-nova')

@section('title', 'ตั๋วช่วยเหลือ')

@php
    // ภาพประกอบชุดบัญชี — ถ้ายังไม่มีไฟล์ใหม่ ใช้ภาพทองชุดเดิมแทน
    $nvArt = fn (string $name, string $fallback) => is_file(public_path("images/nova/account/{$name}.webp"))
        ? asset("images/nova/account/{$name}.webp") : asset("images/nova/{$fallback}.webp");
    $filter = $filters['status'] ?? null;
    $tabs = [
        '' => 'ทั้งหมด',
        'active' => 'เปิดอยู่',
        'waiting_customer' => 'รอข้อมูลจากคุณ',
        'in_progress' => 'กำลังดำเนินการ',
        'resolved' => 'แก้ไขแล้ว',
        'done' => 'ปิดแล้ว',
    ];
    $tone = fn ($status) => match ($status) {
        'open' => 'info',
        'in_progress' => 'warn',
        'waiting_customer' => 'bad',
        'resolved' => 'ok',
        default => '',
    };
    $prTone = fn ($priority) => match ($priority) {
        'critical', 'high' => 'bad',
        'medium' => 'info',
        default => '',
    };
@endphp

@section('content')

<section class="nvm-ph" aria-labelledby="nvm-tk-h">
    <div class="nvm-ph__in">
        <div class="nvm-ph__txt">
            <div class="nvm-eyebrow nvm-latin">Support tickets</div>
            <h1 id="nvm-tk-h">ตั๋วช่วยเหลือ</h1>
            <p class="nvm-ph__lead">แจ้งปัญหาหรือสอบถามทีมงาน แล้วติดตามคำตอบได้ที่นี่ · ตอบกลับในตั๋วเดิมได้เรื่อยๆ จนกว่าจะปิด</p>
            <div class="nvm-bal">
                <small>ตั๋วที่ยังเปิดอยู่</small>
                <b class="nvm-foil nvm-num">{{ number_format($stats['open']) }}<span> ใบ</span></b>
            </div>
            <div class="nvm-gap-t">
                <a class="nvm-btn nvm-btn--gold" href="{{ route('user.tickets.create') }}"><svg class="nvm-ico"><use href="#nvm-i-plus"/></svg>สร้างตั๋วใหม่</a>
            </div>
        </div>
        <img class="nvm-ph__art" src="{{ $nvArt('support', 'deco/scroll') }}" alt="" width="200" height="200">
    </div>
</section>

<div class="nvm-kpis">
    <div class="nvm-card nvm-kpi">
        <img class="nvm-kpi__img" src="{{ $nvArt('ticket', 'deco/scroll') }}" alt="" width="48" height="48" loading="lazy">
        <div class="nvm-kpi__lbl">ทั้งหมด <span class="nvm-kpi__en nvm-latin">All</span></div>
        <div class="nvm-kpi__val nvm-num">{{ number_format($stats['total']) }}</div>
        <div class="nvm-kpi__foot"><span class="nvm-chip">ตั้งแต่เริ่มใช้งาน</span></div>
    </div>
    <div class="nvm-card nvm-kpi">
        <img class="nvm-kpi__img" src="{{ $nvArt('chat', 'deco/lamp') }}" alt="" width="48" height="48" loading="lazy">
        <div class="nvm-kpi__lbl">เปิดอยู่ <span class="nvm-kpi__en nvm-latin">Open</span></div>
        <div class="nvm-kpi__val nvm-num">{{ number_format($stats['open']) }}</div>
        <div class="nvm-kpi__foot"><span class="nvm-chip nvm-chip--info">ทีมงานกำลังดูแล</span></div>
    </div>
    <div class="nvm-card nvm-kpi">
        <img class="nvm-kpi__img" src="{{ asset('images/nova/wallet/hourglass.webp') }}" alt="" width="48" height="48" loading="lazy">
        <div class="nvm-kpi__lbl">รอข้อมูลจากคุณ <span class="nvm-kpi__en nvm-latin">Your turn</span></div>
        <div class="nvm-kpi__val nvm-num">{{ number_format($stats['waiting'] ?? 0) }}</div>
        <div class="nvm-kpi__foot"><span class="nvm-chip {{ ($stats['waiting'] ?? 0) > 0 ? 'nvm-chip--bad' : 'nvm-chip--ok' }}">{{ ($stats['waiting'] ?? 0) > 0 ? 'ตอบกลับทีมงาน' : 'ไม่มีค้าง' }}</span></div>
    </div>
    <div class="nvm-card nvm-kpi">
        <img class="nvm-kpi__img" src="{{ asset('images/nova/deco/lotus.webp') }}" alt="" width="48" height="48" loading="lazy">
        <div class="nvm-kpi__lbl">ปิดแล้ว <span class="nvm-kpi__en nvm-latin">Done</span></div>
        <div class="nvm-kpi__val nvm-num">{{ number_format($stats['closed']) }}</div>
        <div class="nvm-kpi__foot"><span class="nvm-chip nvm-chip--ok">แก้ไขเรียบร้อย</span></div>
    </div>
</div>

@if(($stats['waiting'] ?? 0) > 0 && $filter !== 'waiting_customer')
    <a class="nvm-card nvm-alert" href="{{ route('user.tickets.index', ['status' => 'waiting_customer']) }}">
        <img class="nvm-alert__img" src="{{ asset('images/nova/wallet/hourglass.webp') }}" alt="" width="56" height="56" loading="lazy">
        <div class="nvm-alert__txt">
            <b>ทีมงานรอข้อมูลจากคุณ {{ number_format($stats['waiting']) }} ตั๋ว</b>
            <p>ตอบกลับเพื่อให้ทีมงานช่วยต่อได้ — แตะเพื่อดูตั๋วที่รอคุณอยู่</p>
        </div>
        <svg class="nvm-ico"><use href="#nvm-i-chev"/></svg>
    </a>
@endif

<section class="nvm-card nvm-box" aria-labelledby="nvm-tkl-h">
    <div class="nvm-sec-h">
        <div><small class="nvm-latin">My tickets</small><h2 id="nvm-tkl-h">ตั๋วของฉัน</h2></div>
        <nav class="nvm-tabs" aria-label="กรองสถานะ">
            @foreach($tabs as $key => $label)
                <a href="{{ route('user.tickets.index', $key ? ['status' => $key] : []) }}" class="{{ (string) $filter === (string) $key ? 'is-on' : '' }}" @if((string) $filter === (string) $key) aria-current="page" @endif>
                    {{ $label }}@if($key === 'waiting_customer' && ($stats['waiting'] ?? 0) > 0)<span class="nvm-chip nvm-chip--bad nvm-num">{{ $stats['waiting'] }}</span>@endif
                </a>
            @endforeach
        </nav>
    </div>

    @if($tickets->count())
        <div class="nvm-txs nvm-tks">
            @foreach($tickets as $ticket)
                <a class="nvm-tx nvm-tk" href="{{ route('user.tickets.show', $ticket->id) }}">
                    <span class="nvm-tx__ic {{ $ticket->status === 'waiting_customer' ? 'is-due' : ($ticket->isClosed() ? 'is-in' : '') }}" @if($ticket->category?->color) style="--tk-c: {{ $ticket->category->color }}" @endif>
                        <i class="{{ $ticket->category?->icon ?: 'fa-solid fa-life-ring' }}" aria-hidden="true"></i>
                    </span>
                    <div class="nvm-tx__t">
                        <b>{{ $ticket->subject }}</b>
                        <small class="nvm-num">#{{ $ticket->ticket_number }}@if($ticket->category) · {{ $ticket->category->name }}@endif · {{ $ticket->created_at->format('d/m/Y H:i') }}</small>
                        <small>
                            @if($ticket->last_reply_at)ตอบล่าสุด {{ $ticket->last_reply_at->diffForHumans() }}@else{{ \Illuminate\Support\Str::limit($ticket->description, 80) }}@endif
                            @if($ticket->assignedTo) · ดูแลโดย {{ $ticket->assignedTo->name }}@endif
                        </small>
                    </div>
                    <div class="nvm-tx__r">
                        <span class="nvm-chip {{ $tone($ticket->status) ? 'nvm-chip--'.$tone($ticket->status) : '' }}">{{ $ticket->status_label }}</span>
                        <span class="nvm-chip {{ $prTone($ticket->priority) ? 'nvm-chip--'.$prTone($ticket->priority) : '' }}">{{ $ticket->priority_label }}</span>
                    </div>
                </a>
            @endforeach
        </div>
        @if($tickets->hasPages())
            <div class="nvm-pager nvm-gap-t">{{ $tickets->withQueryString()->links() }}</div>
        @endif
    @else
        <div class="nvm-empty">
            <img src="{{ $nvArt('support', 'wallet/empty') }}" alt="" width="86" height="86" loading="lazy">
            <b>{{ $filter ? 'ไม่มีตั๋วในสถานะนี้' : 'ยังไม่มีตั๋วช่วยเหลือ' }}</b>
            {{ $filter ? 'ลองเลือกสถานะอื่น' : 'มีปัญหาหรือข้อสงสัย สร้างตั๋วเพื่อคุยกับทีมงานได้เลย' }}
            @unless($filter)
                <div><a class="nvm-btn nvm-btn--gold" href="{{ route('user.tickets.create') }}"><svg class="nvm-ico"><use href="#nvm-i-plus"/></svg>สร้างตั๋วใหม่</a></div>
            @endunless
        </div>
    @endif
</section>
@endsection
