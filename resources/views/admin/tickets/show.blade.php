@extends('layouts.admin-v4')

@section('title', 'รายละเอียด Ticket #' . $ticket->ticket_number)

@section('content')
{{--
    🎫 รายละเอียด Ticket (ธีม V4 นวลทองคำ)
    คง route/ฟอร์ม/Alpine auto-submit เดิม 100%
    ของเดิมมี HTML พังหลายจุด (style="..." ซ้อนอยู่ใน class="...", attribute หลุดนอก class) — แก้หมดในรอบนี้
--}}
@php
    $prioMeta = [
        'critical' => ['#d9534f', '🔴 วิกฤต'],
        'high'     => ['#d6824a', '🟠 สูง'],
        'medium'   => ['#5689b8', '🟡 ปานกลาง'],
        'low'      => ['#9a8f7c', '⚪ ต่ำ'],
    ];
    $statusMeta = [
        'open'             => ['#5689b8', '🟢 เปิด'],
        'in_progress'      => ['#e0a52e', '🔵 กำลังดำเนินการ'],
        'waiting_customer' => ['#b79ae8', '🟣 รอลูกค้า'],
        'resolved'         => ['#5aa07e', '✅ แก้ไขแล้ว'],
        'closed'           => ['#9a8f7c', '⚫ ปิด'],
    ];
    [$prioColor, $prioLabel] = $prioMeta[$ticket->priority] ?? ['#9a8f7c', $ticket->priority];
    [$statColor, $statLabel] = $statusMeta[$ticket->status] ?? ['#9a8f7c', $ticket->status];
@endphp

<div style="display:flex; flex-direction:column; gap:18px;">

    {{-- ===== Header ===== --}}
    <div class="tp-card" style="padding:0; overflow:hidden;">
        <div style="padding:22px 24px; background:linear-gradient(120deg, color-mix(in srgb, {{ $prioColor }} 20%, transparent), transparent 70%);">
            <div style="display:flex; flex-wrap:wrap; align-items:flex-start; justify-content:space-between; gap:14px;">
                <div style="min-width:0;">
                    <div style="display:flex; flex-wrap:wrap; gap:8px; margin-bottom:10px;">
                        <span class="tp-pill tp-pill-gold" style="font-family:monospace; font-weight:700;">#{{ $ticket->ticket_number }}</span>
                        <span class="tp-pill" style="background:color-mix(in srgb, {{ $prioColor }} 18%, transparent); color:{{ $prioColor }}; font-weight:700;">{{ $prioLabel }}</span>
                        <span class="tp-pill" style="background:color-mix(in srgb, {{ $statColor }} 18%, transparent); color:{{ $statColor }}; font-weight:700;">{{ $statLabel }}</span>
                    </div>
                    <h1 style="font-size:clamp(19px,3.5vw,26px); font-weight:800; margin:0; color:var(--ink);">{{ $ticket->subject }}</h1>
                    <div style="font-size:12.5px; color:var(--ink2); margin-top:6px;">
                        สร้างโดย {{ $ticket->user?->name ?: 'ไม่ระบุชื่อ' }} • {{ $ticket->created_at->format('d/m/Y H:i') }} • {{ $ticket->created_at->diffForHumans() }}
                    </div>
                </div>
                <a href="{{ route('admin.tickets.index') }}" class="tp-btn tp-btn-sm">
                    <i class="fa-solid fa-arrow-left"></i> กลับ
                </a>
            </div>
        </div>
    </div>

    <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(320px,1fr)); gap:18px; align-items:start;">

        {{-- ===================== คอลัมน์ซ้าย: บทสนทนา ===================== --}}
        <div style="display:flex; flex-direction:column; gap:16px; grid-column:span 2; min-width:0;">

            {{-- ข้อความต้นเรื่อง --}}
            <div class="tp-card" style="padding:20px; border-left:4px solid #5689b8;">
                <div style="display:flex; align-items:flex-start; gap:14px;">
                    <span class="tp-tile" style="width:48px; height:48px; border-radius:50%; font-size:19px; font-weight:800; display:flex; align-items:center; justify-content:center; flex-shrink:0; background:#5689b8;">
                        {{ mb_strtoupper(mb_substr($ticket->user?->name ?: '?', 0, 1)) }}
                    </span>
                    <div style="flex:1; min-width:0;">
                        <div style="display:flex; flex-wrap:wrap; align-items:flex-start; justify-content:space-between; gap:10px; margin-bottom:8px;">
                            <div>
                                <div style="font-size:15px; font-weight:800; color:var(--ink);">{{ $ticket->user?->name ?: 'ไม่ระบุชื่อ' }}</div>
                                <div style="font-size:12px; color:var(--ink2);">{{ $ticket->user?->email ?: '-' }}</div>
                            </div>
                            <div style="text-align:right; white-space:nowrap;">
                                <div style="font-size:11.5px; color:var(--ink2);">{{ $ticket->created_at->format('d/m/Y') }}</div>
                                <div style="font-size:11.5px; color:var(--ink2); opacity:.8;">{{ $ticket->created_at->format('H:i') }}</div>
                            </div>
                        </div>
                        <div class="tp-well" style="padding:14px;">
                            <p style="margin:0; font-size:13.5px; color:var(--ink); white-space:pre-wrap; line-height:1.7;">{{ $ticket->description }}</p>
                        </div>
                    </div>
                </div>
            </div>

            {{-- ไทม์ไลน์การตอบกลับ --}}
            @foreach($ticket->replies as $reply)
                @php
                    $isStaff = $reply->isFromStaff();
                    $accent = $reply->is_internal_note ? '#e0a52e' : ($isStaff ? '#b79ae8' : '#5aa07e');
                @endphp
                <div class="tp-card" style="padding:20px; border-left:4px solid {{ $accent }};">
                    @if($reply->is_internal_note)
                        <div style="margin-bottom:12px;">
                            <span class="tp-pill" style="background:rgba(224,165,46,.18); color:#a87d1e; font-weight:700;">
                                <i class="fa-solid fa-lock"></i> บันทึกภายใน (เฉพาะพนักงาน)
                            </span>
                        </div>
                    @endif

                    <div style="display:flex; align-items:flex-start; gap:14px;">
                        <span class="tp-tile" style="width:48px; height:48px; border-radius:50%; font-size:19px; font-weight:800; display:flex; align-items:center; justify-content:center; flex-shrink:0; background:{{ $isStaff ? '#b79ae8' : '#5aa07e' }};">
                            {{ mb_strtoupper(mb_substr($reply->user?->name ?: '?', 0, 1)) }}
                        </span>
                        <div style="flex:1; min-width:0;">
                            <div style="display:flex; flex-wrap:wrap; align-items:flex-start; justify-content:space-between; gap:10px; margin-bottom:8px;">
                                <div>
                                    <div style="display:flex; flex-wrap:wrap; align-items:center; gap:8px;">
                                        <span style="font-size:15px; font-weight:800; color:var(--ink);">{{ $reply->user?->name ?: 'ไม่ระบุชื่อ' }}</span>
                                        @if($isStaff)
                                            <span class="tp-pill" style="background:rgba(183,154,232,.18); color:#7a5db8; font-weight:700;">
                                                <i class="fa-solid fa-shield-halved"></i> พนักงาน
                                            </span>
                                        @else
                                            <span class="tp-pill" style="background:rgba(90,160,126,.18); color:#3f7a5c; font-weight:700;">
                                                <i class="fa-solid fa-user"></i> ลูกค้า
                                            </span>
                                        @endif
                                    </div>
                                    <div style="font-size:12px; color:var(--ink2); margin-top:3px;">{{ $reply->user?->email ?: '-' }}</div>
                                </div>
                                <div style="text-align:right; white-space:nowrap;">
                                    <div style="font-size:11.5px; color:var(--ink2);">{{ $reply->created_at->format('d/m/Y') }}</div>
                                    <div style="font-size:11.5px; color:var(--ink2); opacity:.8;">{{ $reply->created_at->format('H:i') }}</div>
                                </div>
                            </div>
                            <div class="tp-well" style="padding:14px;">
                                <p style="margin:0; font-size:13.5px; color:var(--ink); white-space:pre-wrap; line-height:1.7;">{{ $reply->message }}</p>
                            </div>
                            <div style="font-size:11.5px; color:var(--ink2); margin-top:7px;">{{ $reply->created_at->diffForHumans() }}</div>
                        </div>
                    </div>
                </div>
            @endforeach

            {{-- ฟอร์มตอบกลับ --}}
            @if(!$ticket->isClosed())
                <div class="tp-card" style="padding:20px;">
                    <div class="tp-section-h" style="margin-bottom:14px;"><i class="fa-solid fa-reply"></i> ตอบกลับ Ticket</div>

                    <form method="POST" action="{{ route('admin.tickets.reply', $ticket->id) }}">
                        @csrf
                        <div style="margin-bottom:14px;">
                            <label style="display:block; font-size:12.5px; color:var(--ink2); font-weight:600; margin-bottom:6px;">ข้อความ</label>
                            <div class="tp-well tp-input" style="padding:0;">
                                <textarea name="message" rows="6" required placeholder="พิมพ์ข้อความตอบกลับ..."
                                          style="width:100%; background:transparent; border:none; outline:none; padding:12px; color:var(--ink); font-size:14px; resize:vertical; font-family:inherit; line-height:1.6;"></textarea>
                            </div>
                        </div>

                        <div style="display:flex; flex-wrap:wrap; align-items:center; justify-content:space-between; gap:12px;">
                            <label class="tp-well" style="display:flex; align-items:center; gap:9px; padding:9px 14px; cursor:pointer;">
                                <input type="checkbox" name="is_internal_note" value="1" style="accent-color:#e0a52e; width:15px; height:15px; cursor:pointer;">
                                <span style="font-size:12.5px; font-weight:600; color:#a87d1e;">
                                    <i class="fa-solid fa-lock"></i> บันทึกภายใน (เฉพาะพนักงานเห็น)
                                </span>
                            </label>
                            <button type="submit" class="tp-btn tp-btn-primary" style="font-weight:700;">
                                <i class="fa-solid fa-paper-plane"></i> ส่งข้อความ
                            </button>
                        </div>
                    </form>
                </div>
            @else
                <div class="tp-card" style="padding:40px 20px; text-align:center;">
                    <i class="fa-solid fa-lock" style="font-size:40px; color:var(--ink2); opacity:.5; display:block; margin-bottom:14px;"></i>
                    <div style="font-size:17px; font-weight:800; color:var(--ink); margin-bottom:6px;">Ticket นี้ถูกปิดแล้ว</div>
                    <p style="margin:0; font-size:13px; color:var(--ink2);">ไม่สามารถตอบกลับได้ กรุณาเปิด Ticket ใหม่หากต้องการติดต่อ</p>
                </div>
            @endif
        </div>

        {{-- ===================== คอลัมน์ขวา: แผงควบคุม ===================== --}}
        <div style="display:flex; flex-direction:column; gap:16px; min-width:0;">

            {{-- ข้อมูล Ticket + ฟอร์มเปลี่ยนค่า (auto-submit ตอน change เหมือนเดิม) --}}
            <div class="tp-card" style="padding:20px;">
                <div class="tp-section-h" style="margin-bottom:16px;"><i class="fa-solid fa-circle-info"></i> ข้อมูล Ticket</div>

                <div style="display:flex; flex-direction:column; gap:14px;">
                    {{-- สถานะ --}}
                    <div>
                        <label style="display:block; font-size:12.5px; color:var(--ink2); font-weight:600; margin-bottom:6px;">สถานะ</label>
                        <form method="POST" action="{{ route('admin.tickets.update-status', $ticket->id) }}" x-data="{}">
                            @csrf
                            @method('PUT')
                            <div class="tp-well tp-input" style="padding:0;">
                                <select name="status" @change="$el.form.submit()"
                                        style="width:100%; background:transparent; border:none; outline:none; padding:10px 12px; color:var(--ink); font-size:14px; font-weight:600;">
                                    @foreach($statusMeta as $key => [$c, $label])
                                        <option value="{{ $key }}" {{ $ticket->status === $key ? 'selected' : '' }}>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </form>
                    </div>

                    {{-- ความสำคัญ --}}
                    <div>
                        <label style="display:block; font-size:12.5px; color:var(--ink2); font-weight:600; margin-bottom:6px;">ความสำคัญ</label>
                        <form method="POST" action="{{ route('admin.tickets.update-priority', $ticket->id) }}" x-data="{}">
                            @csrf
                            @method('PUT')
                            <div class="tp-well tp-input" style="padding:0;">
                                <select name="priority" @change="$el.form.submit()"
                                        style="width:100%; background:transparent; border:none; outline:none; padding:10px 12px; color:var(--ink); font-size:14px; font-weight:600;">
                                    @foreach(['low','medium','high','critical'] as $key)
                                        <option value="{{ $key }}" {{ $ticket->priority === $key ? 'selected' : '' }}>{{ $prioMeta[$key][1] }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </form>
                    </div>

                    {{-- หมวดหมู่ --}}
                    <div>
                        <label style="display:block; font-size:12.5px; color:var(--ink2); font-weight:600; margin-bottom:6px;">หมวดหมู่</label>
                        <form method="POST" action="{{ route('admin.tickets.update-category', $ticket->id) }}" x-data="{}">
                            @csrf
                            @method('PUT')
                            <div class="tp-well tp-input" style="padding:0;">
                                <select name="category_id" @change="$el.form.submit()"
                                        style="width:100%; background:transparent; border:none; outline:none; padding:10px 12px; color:var(--ink); font-size:14px; font-weight:600;">
                                    <option value="">เลือกหมวดหมู่</option>
                                    @foreach($categories as $category)
                                        <option value="{{ $category->id }}" {{ (int) $ticket->category_id === (int) $category->id ? 'selected' : '' }}>{{ $category->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </form>
                    </div>

                    {{-- มอบหมายให้ --}}
                    <div>
                        <label style="display:block; font-size:12.5px; color:var(--ink2); font-weight:600; margin-bottom:6px;">มอบหมายให้</label>
                        <form method="POST" action="{{ route('admin.tickets.assign', $ticket->id) }}" x-data="{}">
                            @csrf
                            <div class="tp-well tp-input" style="padding:0;">
                                <select name="assigned_to" @change="$el.form.submit()"
                                        style="width:100%; background:transparent; border:none; outline:none; padding:10px 12px; color:var(--ink); font-size:14px; font-weight:600;">
                                    <option value="">ไม่มอบหมาย</option>
                                    @foreach($staffUsers as $staff)
                                        <option value="{{ $staff->id }}" {{ (int) $ticket->assigned_to === (int) $staff->id ? 'selected' : '' }}>{{ $staff->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </form>
                    </div>

                    <div class="tp-divider" style="margin:2px 0;"></div>

                    {{-- ไทม์ไลน์ --}}
                    @php
                        $timeline = [];
                        $timeline[] = ['fa-clock', '#5689b8', 'สร้างเมื่อ', $ticket->created_at];
                        if ($ticket->last_reply_at) {
                            $timeline[] = ['fa-comment', '#5aa07e', 'ตอบกลับล่าสุด', $ticket->last_reply_at];
                        }
                        if ($ticket->resolved_at) {
                            $timeline[] = ['fa-circle-check', '#b79ae8', 'แก้ไขเมื่อ', $ticket->resolved_at];
                        }
                    @endphp
                    <div style="display:flex; flex-direction:column; gap:12px;">
                        @foreach($timeline as [$icon, $color, $label, $when])
                            <div style="display:flex; align-items:center; gap:11px;">
                                <span class="tp-tile" style="width:32px; height:32px; border-radius:10px; font-size:13px; display:flex; align-items:center; justify-content:center; flex-shrink:0; background:{{ $color }};">
                                    <i class="fa-solid {{ $icon }}" style="color:#fff;"></i>
                                </span>
                                <div style="min-width:0;">
                                    <div style="font-size:11px; font-weight:700; color:var(--ink2);">{{ $label }}</div>
                                    <div style="font-size:13px; font-weight:700; color:var(--ink);">{{ $when->format('d/m/Y H:i') }}</div>
                                    <div style="font-size:11px; color:var(--ink2);">{{ $when->diffForHumans() }}</div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

            {{-- ข้อมูลผู้ใช้ --}}
            <div class="tp-card" style="padding:20px;">
                <div class="tp-section-h" style="margin-bottom:16px;"><i class="fa-solid fa-user"></i> ข้อมูลผู้ใช้</div>

                <div style="display:flex; align-items:center; gap:14px; margin-bottom:16px;">
                    <span class="tp-tile" style="width:54px; height:54px; border-radius:50%; font-size:21px; font-weight:800; display:flex; align-items:center; justify-content:center; flex-shrink:0; background:#5689b8;">
                        {{ mb_strtoupper(mb_substr($ticket->user?->name ?: '?', 0, 1)) }}
                    </span>
                    <div style="min-width:0;">
                        <div style="font-size:15px; font-weight:800; color:var(--ink);">{{ $ticket->user?->name ?: 'ไม่ระบุชื่อ' }}</div>
                        <div style="font-size:12px; color:var(--ink2); word-break:break-all;">{{ $ticket->user?->email ?: '-' }}</div>
                    </div>
                </div>

                @if($ticket->user && Route::has('admin.users.show'))
                    <a href="{{ route('admin.users.show', $ticket->user->id) }}" class="tp-btn"
                       style="width:100%; justify-content:center; font-weight:700;">
                        <i class="fa-solid fa-up-right-from-square"></i> ดูโปรไฟล์ผู้ใช้
                    </a>
                @endif
            </div>

            {{-- การดำเนินการ --}}
            <div class="tp-card" style="padding:20px; border-left:4px solid #d9534f;">
                <div class="tp-section-h" style="margin-bottom:14px;"><i class="fa-solid fa-bolt"></i> การดำเนินการ</div>
                <form method="POST" action="{{ route('admin.tickets.destroy', $ticket->id) }}"
                      onsubmit="return confirm('คุณแน่ใจหรือไม่ว่าต้องการลบ Ticket นี้?')">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="tp-btn"
                            style="width:100%; justify-content:center; background:#d9534f; color:#fff; border-color:#d9534f; font-weight:700;">
                        <i class="fa-solid fa-trash"></i> ลบ Ticket
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
