@extends('layouts.user-v4')

@section('title', 'Ticket ของฉัน')

@section('content')
<div style="display:flex; flex-direction:column; gap:18px;">

    {{-- ── Hero + สถิติ ──────────────────────────────────────── --}}
    <div class="tp-card" style="padding:0; overflow:hidden; position:relative;">
        {{-- ภาพประกอบหัวเรื่อง (เจนเอง เก็บที่ public/images/art) --}}
        <x-art.hero-art image="usr-tickets" />
        <div style="padding:20px 24px; background:linear-gradient(120deg, color-mix(in srgb, #5689b8 18%, transparent), transparent 70%);">
            <div style="display:flex; flex-wrap:wrap; align-items:center; justify-content:space-between; gap:14px; margin-bottom:18px;">
                <div style="display:flex; align-items:center; gap:14px;">
                    <span class="tp-tile" style="width:52px; height:52px; border-radius:16px; font-size:23px; background:#5689b8;"><i class="fas fa-headset" style="color:#fff;"></i></span>
                    <div>
                        <h1 style="font-size:clamp(20px,4vw,26px); font-weight:800; margin:0;">Ticket Support</h1>
                        <div style="font-size:12.5px; color:var(--ink2); margin-top:3px;">ติดตามและจัดการคำขอความช่วยเหลือของคุณ</div>
                    </div>
                </div>
                <a href="{{ route('user.tickets.create') }}" class="tp-btn tp-btn-primary" style="background:#5689b8; border-color:#5689b8;"><i class="fa-solid fa-plus"></i> สร้าง Ticket ใหม่</a>
            </div>

            <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(140px,1fr)); gap:12px;">
                <div class="tp-card" style="padding:16px;">
                    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:4px;">
                        <span style="font-size:11px; font-weight:600; color:var(--ink2);">ทั้งหมด</span>
                        <i class="fa-solid fa-ticket" style="color:#5689b8;"></i>
                    </div>
                    <div class="tp-num" style="font-size:28px; font-weight:800; color:var(--deep1);">{{ number_format($stats['total']) }}</div>
                </div>
                <div class="tp-card" style="padding:16px;">
                    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:4px;">
                        <span style="font-size:11px; font-weight:600; color:var(--ink2);">เปิดอยู่</span>
                        <i class="fa-solid fa-folder-open" style="color:#5689b8;"></i>
                    </div>
                    <div class="tp-num" style="font-size:28px; font-weight:800; color:#5689b8;">{{ number_format($stats['open']) }}</div>
                </div>
                <div class="tp-card" style="padding:16px;">
                    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:4px;">
                        <span style="font-size:11px; font-weight:600; color:var(--ink2);">ปิดแล้ว</span>
                        <i class="fa-solid fa-check-circle" style="color:#5aa07e;"></i>
                    </div>
                    <div class="tp-num" style="font-size:28px; font-weight:800; color:#5aa07e;">{{ number_format($stats['closed']) }}</div>
                </div>
            </div>
        </div>
    </div>

    {{-- ── ตัวกรอง ───────────────────────────────────────────── --}}
    <div style="display:flex; flex-wrap:wrap; gap:8px;">
        <a href="{{ route('user.tickets.index') }}" class="tp-btn tp-btn-sm {{ !$filters['status'] ? 'tp-btn-primary' : '' }}"><i class="fa-solid fa-list"></i> ทั้งหมด</a>
        <a href="{{ route('user.tickets.index', ['status' => 'open']) }}" class="tp-btn tp-btn-sm" style="{{ $filters['status'] == 'open' ? 'background:#5689b8; border-color:#5689b8; color:#fff;' : '' }}"><i class="fa-solid fa-folder-open"></i> เปิดอยู่</a>
        <a href="{{ route('user.tickets.index', ['status' => 'in_progress']) }}" class="tp-btn tp-btn-sm" style="{{ $filters['status'] == 'in_progress' ? 'background:#d9a441; border-color:#d9a441; color:#fff;' : '' }}"><i class="fa-solid fa-spinner"></i> กำลังดำเนินการ</a>
        <a href="{{ route('user.tickets.index', ['status' => 'resolved']) }}" class="tp-btn tp-btn-sm" style="{{ $filters['status'] == 'resolved' ? 'background:#5aa07e; border-color:#5aa07e; color:#fff;' : '' }}"><i class="fa-solid fa-check"></i> แก้ไขแล้ว</a>
    </div>

    {{-- ── รายการ Ticket ─────────────────────────────────────── --}}
    <div style="display:flex; flex-direction:column; gap:14px;">
        @forelse($tickets as $ticket)
            @php
                $stColor = match($ticket->status) {
                    'open' => '#5689b8', 'in_progress' => '#d9a441',
                    'waiting_customer' => '#7c5cbf', 'resolved' => '#5aa07e',
                    default => '#8a8a8a',
                };
                $prColor = match($ticket->priority) {
                    'critical' => '#d9534f', 'high' => '#e08a3c',
                    'medium' => '#5689b8', default => '#8a8a8a',
                };
            @endphp
            <div class="tp-card" style="padding:20px;">
                <div style="display:flex; flex-wrap:wrap; align-items:center; gap:8px; margin-bottom:10px;">
                    <span style="font-family:monospace; font-weight:800; color:var(--deep1);">{{ $ticket->ticket_number }}</span>
                    @if($ticket->category)
                        <span style="display:inline-flex; align-items:center; padding:2px 10px; border-radius:999px; font-size:11px; font-weight:600; background:{{ $ticket->category->color }}20; color:{{ $ticket->category->color }};">
                            @if($ticket->category->icon)<i class="{{ $ticket->category->icon }}" style="margin-right:4px;"></i>@endif
                            {{ $ticket->category->name }}
                        </span>
                    @endif
                    <span style="display:inline-flex; padding:2px 10px; border-radius:999px; font-size:11px; font-weight:600; color:{{ $stColor }}; background:color-mix(in srgb, {{ $stColor }} 16%, transparent);">{{ $ticket->status_label }}</span>
                    <span style="display:inline-flex; padding:2px 10px; border-radius:999px; font-size:11px; font-weight:600; color:{{ $prColor }}; background:color-mix(in srgb, {{ $prColor }} 16%, transparent);">{{ $ticket->priority_label }}</span>
                </div>
                <h3 style="font-size:18px; font-weight:800; color:var(--ink); margin:0 0 6px;">{{ $ticket->subject }}</h3>
                <p style="font-size:13px; color:var(--ink2); margin:0; display:-webkit-box; -webkit-line-clamp:2; -webkit-box-orient:vertical; overflow:hidden;">{{ $ticket->description }}</p>

                <div style="display:flex; flex-wrap:wrap; align-items:center; justify-content:space-between; gap:12px; padding-top:14px; margin-top:14px; border-top:1px solid color-mix(in srgb, var(--ink2) 12%, transparent);">
                    <div style="display:flex; flex-wrap:wrap; gap:16px; font-size:12px; color:var(--ink2);">
                        <span><i class="fa-solid fa-clock" style="margin-right:4px;"></i>{{ $ticket->created_at->format('d/m/Y H:i') }}</span>
                        @if($ticket->assignedTo)
                            <span><i class="fa-solid fa-user" style="margin-right:4px;"></i>มอบหมายให้: {{ $ticket->assignedTo->name }}</span>
                        @endif
                        @if($ticket->last_reply_at)
                            <span><i class="fa-solid fa-reply" style="margin-right:4px;"></i>ตอบกลับล่าสุด: {{ $ticket->last_reply_at->diffForHumans() }}</span>
                        @endif
                    </div>
                    <a href="{{ route('user.tickets.show', $ticket->id) }}" class="tp-btn tp-btn-sm tp-btn-primary" style="background:#5689b8; border-color:#5689b8;"><i class="fa-solid fa-eye"></i> ดูรายละเอียด</a>
                </div>
            </div>
        @empty
            <div class="tp-card" style="padding:48px; text-align:center;">
                <i class="fa-solid fa-ticket" style="font-size:56px; color:var(--ink2); opacity:.5; margin-bottom:16px;"></i>
                <h3 style="font-size:20px; font-weight:800; color:var(--ink); margin:0 0 8px;">ยังไม่มี Ticket</h3>
                <p style="color:var(--ink2); margin:0 0 20px;">คุณยังไม่มี Ticket ในระบบ สร้าง Ticket ใหม่เพื่อรับความช่วยเหลือ</p>
                <a href="{{ route('user.tickets.create') }}" class="tp-btn tp-btn-primary" style="background:#5689b8; border-color:#5689b8;"><i class="fa-solid fa-plus"></i> สร้าง Ticket ใหม่</a>
            </div>
        @endforelse
    </div>

    @if($tickets->hasPages())
        <div style="display:flex; justify-content:center;">{{ $tickets->links() }}</div>
    @endif
</div>
@endsection
