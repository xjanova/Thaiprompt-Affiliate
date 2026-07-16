@extends('layouts.user-v4')

@section('title', 'Ticket #' . $ticket->ticket_number)

@section('content')
<div style="display:flex; flex-direction:column; gap:18px; max-width:1000px; margin-inline:auto; width:100%;">

    {{-- ── Hero ─────────────────────────────────────────────── --}}
    @php
        $stColor = match($ticket->status) {
            'open' => '#5689b8', 'in_progress' => '#d9a441',
            'waiting_customer' => '#7c5cbf', 'resolved' => '#5aa07e',
            default => '#8a8a8a',
        };
    @endphp
    <div class="tp-card" style="padding:0; overflow:hidden;">
        <div style="padding:20px 24px; background:linear-gradient(120deg, color-mix(in srgb, #5689b8 18%, transparent), transparent 70%);">
            <div style="display:flex; flex-wrap:wrap; align-items:flex-start; justify-content:space-between; gap:14px;">
                <div style="flex:1; min-width:220px;">
                    <h1 style="font-size:clamp(19px,3.5vw,26px); font-weight:800; margin:0 0 10px;">{{ $ticket->subject }}</h1>
                    <div style="display:flex; flex-wrap:wrap; align-items:center; gap:8px;">
                        <span style="font-family:monospace; font-size:12px; box-shadow:var(--inset-sm); padding:3px 10px; border-radius:8px; color:var(--ink);">{{ $ticket->ticket_number }}</span>
                        @if($ticket->category)
                            <span style="display:inline-flex; align-items:center; padding:3px 10px; border-radius:8px; font-size:12px; background:{{ $ticket->category->color }}20; color:{{ $ticket->category->color }};">
                                @if($ticket->category->icon)<i class="{{ $ticket->category->icon }}" style="margin-right:5px;"></i>@endif{{ $ticket->category->name }}
                            </span>
                        @endif
                        <span style="display:inline-flex; padding:3px 10px; border-radius:8px; font-size:12px; font-weight:600; color:{{ $stColor }}; background:color-mix(in srgb, {{ $stColor }} 16%, transparent);">{{ $ticket->status_label }}</span>
                    </div>
                </div>
                <div style="display:flex; gap:8px; flex-wrap:wrap;">
                    @if(!$ticket->isClosed())
                        <form method="POST" action="{{ route('user.tickets.close', $ticket->id) }}" onsubmit="return confirm('คุณแน่ใจหรือไม่ว่าต้องการปิด Ticket นี้?')">
                            @csrf
                            <button type="submit" class="tp-btn tp-btn-sm"><i class="fa-solid fa-times-circle"></i> ปิด Ticket</button>
                        </form>
                    @endif
                    <a href="{{ route('user.tickets.index') }}" class="tp-btn tp-btn-sm"><i class="fa-solid fa-arrow-left"></i> กลับ</a>
                </div>
            </div>
        </div>
    </div>

    {{-- ── สถานะ ─────────────────────────────────────────────── --}}
    @if($ticket->status == 'resolved')
        <div class="tp-card" style="padding:20px; border-left:4px solid #5aa07e;">
            <div style="display:flex; align-items:center; gap:16px;">
                <i class="fa-solid fa-check-circle" style="font-size:32px; color:#5aa07e;"></i>
                <div><div style="font-weight:800; color:var(--ink); margin-bottom:2px;">Ticket นี้ได้รับการแก้ไขแล้ว</div><div style="font-size:13px; color:var(--ink2);">หากคุณยังมีปัญหา สามารถตอบกลับเพื่อเปิด Ticket ใหม่อีกครั้ง</div></div>
            </div>
        </div>
    @elseif($ticket->status == 'waiting_customer')
        <div class="tp-card" style="padding:20px; border-left:4px solid #7c5cbf;">
            <div style="display:flex; align-items:center; gap:16px;">
                <i class="fa-solid fa-hourglass-half" style="font-size:32px; color:#7c5cbf;"></i>
                <div><div style="font-weight:800; color:var(--ink); margin-bottom:2px;">รอข้อมูลเพิ่มเติมจากคุณ</div><div style="font-size:13px; color:var(--ink2);">ทีมงานกำลังรอคำตอบหรือข้อมูลเพิ่มเติมจากคุณ</div></div>
            </div>
        </div>
    @elseif($ticket->status == 'in_progress')
        <div class="tp-card" style="padding:20px; border-left:4px solid #d9a441;">
            <div style="display:flex; align-items:center; gap:16px;">
                <i class="fa-solid fa-spinner fa-spin" style="font-size:32px; color:#d9a441;"></i>
                <div><div style="font-weight:800; color:var(--ink); margin-bottom:2px;">กำลังดำเนินการ</div><div style="font-size:13px; color:var(--ink2);">ทีมงานกำลังตรวจสอบและแก้ไขปัญหาของคุณ @if($ticket->assignedTo) โดย {{ $ticket->assignedTo->name }} @endif</div></div>
            </div>
        </div>
    @endif

    {{-- ── บทสนทนา ───────────────────────────────────────────── --}}
    <div style="display:flex; flex-direction:column; gap:14px;">
        {{-- ข้อความแรก --}}
        <div class="tp-card" style="padding:20px;">
            <div style="display:flex; gap:14px; align-items:flex-start;">
                <div style="flex-shrink:0; width:46px; height:46px; border-radius:50%; background:linear-gradient(135deg, #5689b8, #7c5cbf); display:flex; align-items:center; justify-content:center; color:#fff; font-size:18px; font-weight:800;">{{ substr($ticket->user->name, 0, 1) }}</div>
                <div style="flex:1; min-width:0;">
                    <div style="display:flex; align-items:center; justify-content:space-between; gap:10px; margin-bottom:8px; flex-wrap:wrap;">
                        <div style="font-weight:800; color:var(--ink);">{{ $ticket->user->name }} <span style="font-size:12px; font-weight:400; color:var(--ink2);">(คุณ)</span></div>
                        <span style="font-size:12px; color:var(--ink2);">{{ $ticket->created_at->format('d/m/Y H:i') }}</span>
                    </div>
                    <div style="border-radius:10px; box-shadow:var(--inset-sm); padding:14px;">
                        <p style="color:var(--ink); white-space:pre-wrap; margin:0;">{{ $ticket->description }}</p>
                    </div>
                </div>
            </div>
        </div>

        {{-- ตอบกลับ --}}
        @foreach($ticket->publicReplies as $reply)
            @php $staff = $reply->isFromStaff(); $accent = $staff ? '#7c5cbf' : '#5689b8'; @endphp
            <div class="tp-card" style="padding:20px;">
                <div style="display:flex; gap:14px; align-items:flex-start;">
                    <div style="flex-shrink:0; width:46px; height:46px; border-radius:50%; background:linear-gradient(135deg, {{ $accent }}, {{ $staff ? '#c05a8f' : '#7c5cbf' }}); display:flex; align-items:center; justify-content:center; color:#fff; font-size:18px; font-weight:800;">{{ substr($reply->user->name, 0, 1) }}</div>
                    <div style="flex:1; min-width:0;">
                        <div style="display:flex; align-items:center; justify-content:space-between; gap:10px; margin-bottom:8px; flex-wrap:wrap;">
                            <div style="font-weight:800; color:var(--ink);">
                                {{ $reply->user->name }}
                                @if($staff)
                                    <span style="margin-left:6px; display:inline-flex; padding:2px 8px; border-radius:6px; font-size:11px; font-weight:600; color:#7c5cbf; background:color-mix(in srgb, #7c5cbf 16%, transparent);"><i class="fa-solid fa-shield-halved" style="margin-right:4px;"></i>ทีมงาน</span>
                                @else
                                    <span style="font-size:12px; font-weight:400; color:var(--ink2);">(คุณ)</span>
                                @endif
                            </div>
                            <span style="font-size:12px; color:var(--ink2);">{{ $reply->created_at->format('d/m/Y H:i') }}</span>
                        </div>
                        <div style="border-radius:10px; padding:14px; background:color-mix(in srgb, {{ $accent }} 8%, transparent); box-shadow:var(--inset-sm);">
                            <p style="color:var(--ink); white-space:pre-wrap; margin:0;">{{ $reply->message }}</p>
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    {{-- ── ฟอร์มตอบกลับ ──────────────────────────────────────── --}}
    @if(!$ticket->isClosed())
        <div class="tp-card" style="padding:20px;">
            <div class="tp-section-h" style="margin-bottom:14px;"><i class="fa-solid fa-reply" style="margin-right:6px;"></i>ตอบกลับ</div>
            <form method="POST" action="{{ route('user.tickets.reply', $ticket->id) }}">
                @csrf
                <textarea name="message" rows="4" required class="tp-input" style="resize:vertical; margin-bottom:12px;" placeholder="พิมพ์ข้อความของคุณที่นี่..."></textarea>
                <div style="display:flex; align-items:center; justify-content:space-between; gap:12px; flex-wrap:wrap;">
                    <p style="font-size:13px; color:var(--ink2); margin:0;"><i class="fa-solid fa-info-circle" style="margin-right:4px;"></i>ทีมงานจะตอบกลับโดยเร็วที่สุด</p>
                    <button type="submit" class="tp-btn tp-btn-primary" style="background:#5689b8; border-color:#5689b8;"><i class="fa-solid fa-paper-plane"></i> ส่งข้อความ</button>
                </div>
            </form>
        </div>
    @else
        <div class="tp-card" style="padding:32px; text-align:center;">
            <i class="fa-solid fa-lock" style="font-size:44px; color:var(--ink2); opacity:.5; margin-bottom:16px;"></i>
            <h3 style="font-size:19px; font-weight:800; color:var(--ink); margin:0 0 8px;">Ticket นี้ถูกปิดแล้ว</h3>
            <p style="color:var(--ink2); margin:0 0 20px;">หากคุณต้องการความช่วยเหลือเพิ่มเติม กรุณาสร้าง Ticket ใหม่</p>
            <a href="{{ route('user.tickets.create') }}" class="tp-btn tp-btn-primary" style="background:#5689b8; border-color:#5689b8;"><i class="fa-solid fa-plus"></i> สร้าง Ticket ใหม่</a>
        </div>
    @endif
</div>
@endsection
