{{--
    รายละเอียดตั๋ว + บทสนทนากับทีมงาน (ธีมโนวาเต็ม)
    - ตอบกลับได้ทุกสถานะยกเว้น "ปิด" (ตอบตั๋วที่แก้ไขแล้ว = เปิดใหม่ — TicketService::addReply)
    - ลูกค้ากดปิดตั๋วเองได้ (ต้องกดยืนยันอีกครั้ง)
--}}
@extends('layouts.user-nova')

@section('title', 'ตั๋ว #'.$ticket->ticket_number)

@php
    $nvArt = fn (string $name, string $fallback) => is_file(public_path("images/nova/account/{$name}.webp"))
        ? asset("images/nova/account/{$name}.webp") : asset("images/nova/{$fallback}.webp");
    $isClosed = $ticket->status === 'closed';
    $stTone = match ($ticket->status) {
        'open' => 'glass',
        'in_progress', 'waiting_customer' => 'warn',
        'resolved' => 'ok',
        default => 'glass',
    };
    $statusNote = match ($ticket->status) {
        'waiting_customer' => ['', 'ทีมงานรอข้อมูลจากคุณ', 'ตอบกลับด้านล่างเพื่อให้ทีมงานช่วยต่อได้', 'wallet/hourglass'],
        'in_progress' => ['nvm-alert--info', 'ทีมงานกำลังดำเนินการ', 'กำลังตรวจสอบและแก้ไขปัญหาของคุณ'.($ticket->assignedTo ? ' โดย '.$ticket->assignedTo->name : ''), 'deco/lamp'],
        'resolved' => ['nvm-alert--ok', 'ตั๋วนี้แก้ไขแล้ว', 'ถ้ายังมีปัญหา ตอบกลับด้านล่างได้เลย ระบบจะเปิดตั๋วให้ใหม่', 'deco/lotus'],
        'closed' => ['nvm-alert--info', 'ตั๋วนี้ปิดแล้ว', 'ถ้ายังต้องการความช่วยเหลือ สร้างตั๋วใหม่ได้ทุกเมื่อ', 'deco/lotus'],
        default => null,
    };
    $initial = fn ($name) => mb_strtoupper(mb_substr(trim((string) $name) ?: '?', 0, 1));
@endphp

@section('content')

<section class="nvm-ph" aria-labelledby="nvm-tks-h">
    <div class="nvm-ph__in">
        <div class="nvm-ph__txt">
            <a class="nvm-ph__back" href="{{ route('user.tickets.index') }}"><svg class="nvm-ico"><use href="#nvm-i-arrow"/></svg>ตั๋วของฉัน</a>
            <div class="nvm-eyebrow nvm-latin">Ticket #{{ $ticket->ticket_number }}</div>
            <h1 id="nvm-tks-h">{{ $ticket->subject }}</h1>
            <div class="nvm-pills">
                <span class="nvm-pill nvm-pill--{{ $stTone }}">{{ $ticket->status_label }}</span>
                @if($ticket->category)
                    <span class="nvm-pill nvm-pill--glass"><i class="{{ $ticket->category->icon ?: 'fa-solid fa-folder' }}" aria-hidden="true"></i>{{ $ticket->category->name }}</span>
                @endif
                <span class="nvm-pill nvm-pill--glass">ความเร่งด่วน: {{ $ticket->priority_label }}</span>
            </div>
        </div>
        <img class="nvm-ph__art" src="{{ $nvArt('chat', 'deco/scroll') }}" alt="" width="200" height="200">
    </div>
</section>

@if($statusNote)
    <section class="nvm-card nvm-alert {{ $statusNote[0] }}">
        <img class="nvm-alert__img" src="{{ asset('images/nova/'.$statusNote[3].'.webp') }}" alt="" width="56" height="56" loading="lazy">
        <div class="nvm-alert__txt"><b>{{ $statusNote[1] }}</b><p>{{ $statusNote[2] }}</p></div>
        @if($isClosed)
            <a class="nvm-btn nvm-btn--gold nvm-btn--sm" href="{{ route('user.tickets.create') }}"><svg class="nvm-ico"><use href="#nvm-i-plus"/></svg>สร้างตั๋วใหม่</a>
        @endif
    </section>
@endif

<div class="nvm-split">
    <div class="nvm-stack">
        <section class="nvm-card nvm-box" aria-labelledby="nvm-tkth-h">
            <div class="nvm-sec-h"><div><small class="nvm-latin">Conversation</small><h2 id="nvm-tkth-h">บทสนทนา</h2></div></div>
            <ol class="nvm-thread">
                <li class="nvm-msg is-me">
                    <span class="nvm-msg__av" aria-hidden="true">{{ $initial($ticket->user?->name) }}</span>
                    <div class="nvm-msg__body">
                        <div class="nvm-msg__who"><b>คุณ</b><time class="nvm-num" datetime="{{ $ticket->created_at->toIso8601String() }}">{{ $ticket->created_at->format('d/m/Y H:i') }}</time></div>
                        <p>{{ $ticket->description }}</p>
                    </div>
                </li>
                @foreach($ticket->publicReplies as $reply)
                    {{-- ไม่ใช่เจ้าของตั๋ว = ทีมงาน (isFromStaff ดูแค่ role admin/moderator — ทีมงานที่ได้สิทธิ์ผ่าน permission จะกลายเป็น "คุณ") --}}
                    @php $staff = $reply->user_id !== $ticket->user_id; @endphp
                    <li class="nvm-msg {{ $staff ? 'is-staff' : 'is-me' }}">
                        <span class="nvm-msg__av" aria-hidden="true">{{ $staff ? 'ท' : $initial($reply->user?->name) }}</span>
                        <div class="nvm-msg__body">
                            <div class="nvm-msg__who">
                                <b>{{ $staff ? ($reply->user?->name ?? 'ทีมงาน') : 'คุณ' }}</b>
                                @if($staff)<span class="nvm-chip nvm-chip--gold">ทีมงาน</span>@endif
                                <time class="nvm-num" datetime="{{ $reply->created_at->toIso8601String() }}">{{ $reply->created_at->format('d/m/Y H:i') }}</time>
                            </div>
                            <p>{{ $reply->message }}</p>
                        </div>
                    </li>
                @endforeach
            </ol>
        </section>

        @unless($isClosed)
            <section class="nvm-card nvm-box" aria-labelledby="nvm-tkr-h" x-data="{ len: @js(mb_strlen((string) old('message', ''))), busy: false }" @pageshow.window="busy = false">
                <div class="nvm-sec-h"><div><small class="nvm-latin">Reply</small><h2 id="nvm-tkr-h">{{ $ticket->status === 'resolved' ? 'ยังมีปัญหาอยู่? ตอบกลับ' : 'ตอบกลับทีมงาน' }}</h2></div></div>
                <form method="POST" action="{{ route('user.tickets.reply', $ticket->id) }}" class="nvm-form" @submit="busy = true">
                    @csrf
                    <div class="nvm-field">
                        <label class="nvm-sr" for="tk-msg">ข้อความ</label>
                        <textarea id="tk-msg" name="message" required maxlength="5000" rows="4" class="nvm-input nvm-textarea @error('message') is-bad @enderror" placeholder="พิมพ์ข้อความถึงทีมงาน…" @input="len = $event.target.value.length">{{ old('message') }}</textarea>
                        <span class="nvm-help nvm-num"><span x-text="len.toLocaleString()">0</span> / 5,000 ตัวอักษร · ห้ามใส่รหัสผ่าน PIN หรือ OTP</span>
                        @error('message')<span class="nvm-err">{{ $message }}</span>@enderror
                    </div>
                    <div class="nvm-filter__btns">
                        <button type="submit" class="nvm-btn nvm-btn--gold" :disabled="busy"><svg class="nvm-ico"><use href="#nvm-i-send"/></svg><span x-text="busy ? 'กำลังส่ง…' : 'ส่งข้อความ'">ส่งข้อความ</span></button>
                    </div>
                </form>
            </section>
        @endunless
    </div>

    <aside class="nvm-card nvm-box" aria-labelledby="nvm-tki-h">
        <div class="nvm-sec-h"><div><small class="nvm-latin">Ticket info</small><h2 id="nvm-tki-h">ข้อมูลตั๋ว</h2></div></div>
        <dl class="nvm-kv">
            <dt>เลขที่ตั๋ว</dt><dd class="nvm-num">#{{ $ticket->ticket_number }}</dd>
            <dt>สถานะ</dt><dd>{{ $ticket->status_label }}</dd>
            @if($ticket->category)<dt>เรื่อง</dt><dd>{{ $ticket->category->name }}</dd>@endif
            <dt>ความเร่งด่วน</dt><dd>{{ $ticket->priority_label }}</dd>
            <dt>สร้างเมื่อ</dt><dd class="nvm-num">{{ $ticket->created_at->format('d/m/Y H:i') }}</dd>
            @if($ticket->last_reply_at)<dt>ตอบล่าสุด</dt><dd>{{ $ticket->last_reply_at->diffForHumans() }}</dd>@endif
            @if($ticket->assignedTo)<dt>ผู้ดูแล</dt><dd>{{ $ticket->assignedTo->name }}</dd>@endif
        </dl>

        @unless($isClosed)
            {{-- ปิดตั๋วต้องกดยืนยันอีกครั้ง --}}
            <div class="nvm-gap-t" x-data="{ ask: false, busy: false }" @pageshow.window="busy = false">
                <button type="button" class="nvm-btn nvm-btn--line nvm-btn--sm" x-show="! ask" @click="ask = true"><svg class="nvm-ico"><use href="#nvm-i-check"/></svg>ปัญหาหมดแล้ว ปิดตั๋ว</button>
                <form method="POST" action="{{ route('user.tickets.close', $ticket->id) }}" class="nvm-confirm" x-show="ask" x-cloak @submit="busy = true">
                    @csrf
                    <p>ปิดตั๋วนี้? ปิดแล้วจะตอบกลับต่อไม่ได้ ต้องสร้างตั๋วใหม่</p>
                    <div class="nvm-filter__btns">
                        <button type="button" class="nvm-btn nvm-btn--line nvm-btn--sm" @click="ask = false" :disabled="busy">ไม่ใช่</button>
                        <button type="submit" class="nvm-btn nvm-btn--gold nvm-btn--sm" :disabled="busy" x-text="busy ? 'กำลังปิด…' : 'ยืนยันปิดตั๋ว'">ยืนยันปิดตั๋ว</button>
                    </div>
                </form>
            </div>
        @endunless
    </aside>
</div>
@endsection
