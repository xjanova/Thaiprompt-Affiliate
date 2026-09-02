@extends('layouts.admin-v4')

@section('title', 'จัดการ Ticket')

@section('content')
{{-- 🎫 ระบบ Ticket Support (ธีม V4 นวลทองคำ) — คงตัวกรอง/ตาราง/ลิงก์เดิม 100% --}}
@php
    // สีความสำคัญ / สถานะ — ธีม V4 ใช้ inline style ไม่ใช่คลาส utility
    $prioColors = [
        'critical' => '#d9534f',
        'high'     => '#d6824a',
        'medium'   => '#5689b8',
        'low'      => '#9a8f7c',
    ];
    $statusColors = [
        'open'             => '#5689b8',
        'in_progress'      => '#e0a52e',
        'waiting_customer' => '#b79ae8',
        'resolved'         => '#5aa07e',
        'closed'           => '#9a8f7c',
    ];
    // เมนูจัดการระบบ 8 รายการ: [route, ไอคอน, ป้าย, สี]
    $manageLinks = [
        ['admin.tickets.analytics',              'fa-chart-line',  'Analytics',        '#5689b8'],
        ['admin.tickets.ratings',                'fa-star',        'ความพึงพอใจ',      '#e0a52e'],
        ['admin.tickets.categories.index',       'fa-folder',      'หมวดหมู่',          '#b79ae8'],
        ['admin.tickets.canned-responses.index', 'fa-comment-dots','ข้อความสำเร็จรูป',  '#5aa07e'],
        ['admin.tickets.sla-policies.index',     'fa-clock',       'SLA Policies',     '#d9534f'],
        ['admin.tickets.assignment-rules.index', 'fa-user-check',  'กฎการมอบหมาย',     '#4fa3a3'],
        ['admin.tickets.kb-articles.index',      'fa-book',        'ฐานความรู้',        '#d6824a'],
        ['admin.tickets.settings',               'fa-cog',         'ตั้งค่า',           '#9a8f7c'],
    ];
    // KPI 5 ใบ: [ค่า, ป้าย, ไอคอน, สี]
    $kpis = [
        [$stats['total'],       'ทั้งหมด',           'fa-ticket',                null],
        [$stats['open'],        'เปิดอยู่',          'fa-folder-open',           '#5689b8'],
        [$stats['in_progress'], 'กำลังดำเนินการ',    'fa-spinner',               '#e0a52e'],
        [$stats['critical'],    'วิกฤต',             'fa-triangle-exclamation',  '#d9534f'],
        [$stats['my_tickets'],  'ของฉัน',            'fa-user',                  '#5aa07e'],
    ];
@endphp

<div style="display:flex; flex-direction:column; gap:18px;">

    {{-- ===== Header ===== --}}
    <div style="display:flex; flex-wrap:wrap; align-items:flex-end; justify-content:space-between; gap:14px;">
        <div>
            <div style="font-size:11px; color:var(--ink2); font-weight:600; letter-spacing:.4px;">หลังบ้าน · ศูนย์ช่วยเหลือ</div>
            <h1 class="tp-num" style="font-size:clamp(22px,4vw,28px); font-weight:800; margin:4px 0 0;">ระบบ Ticket Support 🎫</h1>
            <div style="font-size:12.5px; color:var(--ink2); margin-top:4px;">จัดการและตอบกลับ Ticket จากผู้ใช้งานทั้งหมด</div>
        </div>
    </div>

    {{-- ===== KPI ===== --}}
    <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(180px,1fr)); gap:16px;">
        @foreach($kpis as [$value, $label, $icon, $color])
            <div class="tp-card" style="padding:18px;">
                <div style="display:flex; align-items:center; gap:12px;">
                    <div class="tp-tile" style="width:42px; height:42px; border-radius:12px; font-size:18px; display:flex; align-items:center; justify-content:center;{{ $color ? ' background:'.$color.';' : '' }}">
                        <i class="fa-solid {{ $icon }}"></i>
                    </div>
                    <div>
                        <div class="tp-num" style="font-size:26px; font-weight:800; line-height:1;">{{ number_format($value) }}</div>
                        <div style="font-size:12px; color:var(--ink2); margin-top:3px;">{{ $label }}</div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    {{-- ===== เมนูจัดการระบบ ===== --}}
    <div class="tp-card" style="padding:20px;">
        <div class="tp-section-h" style="margin-bottom:14px;"><i class="fa-solid fa-cog"></i> จัดการระบบ Ticket</div>
        <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(130px,1fr)); gap:12px;">
            @foreach($manageLinks as [$route, $icon, $label, $color])
                @if(Route::has($route))
                    <a href="{{ route($route) }}" class="tp-well"
                       style="display:flex; flex-direction:column; align-items:center; justify-content:center; gap:8px; padding:16px 10px; text-decoration:none; text-align:center;">
                        <span class="tp-tile" style="width:40px; height:40px; border-radius:12px; font-size:17px; display:flex; align-items:center; justify-content:center; background:{{ $color }};">
                            <i class="fa-solid {{ $icon }}" style="color:#fff;"></i>
                        </span>
                        <span style="font-size:12px; font-weight:600; color:var(--ink);">{{ $label }}</span>
                    </a>
                @endif
            @endforeach
        </div>
    </div>

    {{-- ===== ทางลัดกรองด่วน ===== --}}
    <div style="display:flex; flex-wrap:wrap; gap:10px;">
        <a href="{{ route('admin.tickets.index', ['status' => 'open']) }}" class="tp-btn{{ $filters['status'] == 'open' ? ' tp-btn-primary' : '' }}">
            <i class="fa-solid fa-folder-open"></i> เปิดอยู่ ({{ number_format($stats['open']) }})
        </a>
        <a href="{{ route('admin.tickets.index', ['status' => 'in_progress']) }}" class="tp-btn{{ $filters['status'] == 'in_progress' ? ' tp-btn-primary' : '' }}">
            <i class="fa-solid fa-spinner"></i> กำลังดำเนินการ ({{ number_format($stats['in_progress']) }})
        </a>
        <a href="{{ route('admin.tickets.index', ['priority' => 'critical']) }}" class="tp-btn{{ $filters['priority'] == 'critical' ? ' tp-btn-primary' : '' }}" style="color:#d9534f;">
            <i class="fa-solid fa-triangle-exclamation"></i> วิกฤต ({{ number_format($stats['critical']) }})
        </a>
        <a href="{{ route('admin.tickets.index', ['unassigned' => 1]) }}" class="tp-btn{{ $filters['unassigned'] ? ' tp-btn-primary' : '' }}">
            <i class="fa-solid fa-user-slash"></i> ยังไม่มอบหมาย ({{ number_format($stats['unassigned']) }})
        </a>
    </div>

    {{-- ===== ตัวกรอง ===== --}}
    <div class="tp-card" style="padding:20px;">
        <div class="tp-section-h" style="margin-bottom:14px;"><i class="fa-solid fa-filter"></i> กรองข้อมูล</div>
        <form method="GET" action="{{ route('admin.tickets.index') }}"
              style="display:grid; grid-template-columns:repeat(auto-fit,minmax(200px,1fr)); gap:16px;">

            <div>
                <label style="display:block; font-size:12.5px; color:var(--ink2); font-weight:600; margin-bottom:6px;">สถานะ</label>
                <div class="tp-well tp-input" style="padding:0;">
                    <select name="status" style="width:100%; background:transparent; border:none; outline:none; padding:10px 12px; color:var(--ink); font-size:14px;">
                        <option value="">ทั้งหมด</option>
                        <option value="open" {{ $filters['status'] == 'open' ? 'selected' : '' }}>เปิด</option>
                        <option value="in_progress" {{ $filters['status'] == 'in_progress' ? 'selected' : '' }}>กำลังดำเนินการ</option>
                        <option value="waiting_customer" {{ $filters['status'] == 'waiting_customer' ? 'selected' : '' }}>รอลูกค้า</option>
                        <option value="resolved" {{ $filters['status'] == 'resolved' ? 'selected' : '' }}>แก้ไขแล้ว</option>
                        <option value="closed" {{ $filters['status'] == 'closed' ? 'selected' : '' }}>ปิด</option>
                    </select>
                </div>
            </div>

            <div>
                <label style="display:block; font-size:12.5px; color:var(--ink2); font-weight:600; margin-bottom:6px;">ความสำคัญ</label>
                <div class="tp-well tp-input" style="padding:0;">
                    <select name="priority" style="width:100%; background:transparent; border:none; outline:none; padding:10px 12px; color:var(--ink); font-size:14px;">
                        <option value="">ทั้งหมด</option>
                        <option value="low" {{ $filters['priority'] == 'low' ? 'selected' : '' }}>ต่ำ</option>
                        <option value="medium" {{ $filters['priority'] == 'medium' ? 'selected' : '' }}>ปานกลาง</option>
                        <option value="high" {{ $filters['priority'] == 'high' ? 'selected' : '' }}>สูง</option>
                        <option value="critical" {{ $filters['priority'] == 'critical' ? 'selected' : '' }}>วิกฤต</option>
                    </select>
                </div>
            </div>

            <div>
                <label style="display:block; font-size:12.5px; color:var(--ink2); font-weight:600; margin-bottom:6px;">หมวดหมู่</label>
                <div class="tp-well tp-input" style="padding:0;">
                    <select name="category_id" style="width:100%; background:transparent; border:none; outline:none; padding:10px 12px; color:var(--ink); font-size:14px;">
                        <option value="">ทั้งหมด</option>
                        @foreach($categories as $category)
                            <option value="{{ $category->id }}" {{ $filters['category_id'] == $category->id ? 'selected' : '' }}>{{ $category->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div>
                <label style="display:block; font-size:12.5px; color:var(--ink2); font-weight:600; margin-bottom:6px;">ผู้ดูแล</label>
                <div class="tp-well tp-input" style="padding:0;">
                    <select name="assigned_to" style="width:100%; background:transparent; border:none; outline:none; padding:10px 12px; color:var(--ink); font-size:14px;">
                        <option value="">ทั้งหมด</option>
                        @foreach($staffUsers as $staff)
                            <option value="{{ $staff->id }}" {{ $filters['assigned_to'] == $staff->id ? 'selected' : '' }}>{{ $staff->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div>
                <label style="display:block; font-size:12.5px; color:var(--ink2); font-weight:600; margin-bottom:6px;">🔍 ค้นหา</label>
                <div class="tp-well tp-input" style="padding:0;">
                    <input type="text" name="search" value="{{ $filters['search'] }}" placeholder="หมายเลข, หัวข้อ..."
                           style="width:100%; background:transparent; border:none; outline:none; padding:10px 12px; color:var(--ink); font-size:14px;">
                </div>
            </div>

            <div style="grid-column:1 / -1; display:flex; flex-wrap:wrap; gap:10px; padding-top:4px;">
                <button type="submit" class="tp-btn tp-btn-primary"><i class="fa-solid fa-magnifying-glass"></i> ค้นหา</button>
                <a href="{{ route('admin.tickets.index') }}" class="tp-btn"><i class="fa-solid fa-rotate-left"></i> รีเซ็ต</a>
            </div>
        </form>
    </div>

    {{-- ===== ตาราง Ticket ===== --}}
    <div class="tp-card" style="padding:0; overflow:hidden;">
        <div style="overflow-x:auto;">
            <table style="min-width:100%; border-collapse:collapse;">
                <thead>
                    <tr>
                        @foreach(['หมายเลข','หัวข้อ','ผู้ใช้','หมวดหมู่','ความสำคัญ','สถานะ','ผู้ดูแล','สร้างเมื่อ'] as $th)
                            <th style="padding:14px 16px; text-align:left; font-size:11px; font-weight:700; color:var(--ink2); text-transform:uppercase; letter-spacing:.4px; white-space:nowrap;">{{ $th }}</th>
                        @endforeach
                        <th style="padding:14px 16px; text-align:right; font-size:11px; font-weight:700; color:var(--ink2); text-transform:uppercase; letter-spacing:.4px;">จัดการ</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($tickets as $ticket)
                        @php
                            $pc = $prioColors[$ticket->priority] ?? '#9a8f7c';
                            $sc = $statusColors[$ticket->status] ?? '#9a8f7c';
                        @endphp
                        <tr style="box-shadow:var(--inset-sm); transition:background .15s;"
                            onmouseover="this.style.background='var(--bg)'" onmouseout="this.style.background='transparent'">
                            {{-- หมายเลข --}}
                            <td style="padding:14px 16px; white-space:nowrap;">
                                <span style="font-family:monospace; font-size:13px; font-weight:700; color:var(--deep1);">{{ $ticket->ticket_number }}</span>
                            </td>
                            {{-- หัวข้อ --}}
                            <td style="padding:14px 16px;">
                                <div style="font-size:13.5px; font-weight:600; color:var(--ink);">{{ Str::limit($ticket->subject, 50) }}</div>
                                <div style="font-size:11.5px; color:var(--ink2); margin-top:2px;">{{ Str::limit($ticket->description, 60) }}</div>
                            </td>
                            {{-- ผู้ใช้ --}}
                            <td style="padding:14px 16px; white-space:nowrap;">
                                <div style="font-size:13.5px; color:var(--ink);">{{ $ticket->user?->name ?: 'ไม่ระบุชื่อ' }}</div>
                                <div style="font-size:11.5px; color:var(--ink2);">{{ $ticket->user?->email ?: '-' }}</div>
                            </td>
                            {{-- หมวดหมู่ --}}
                            <td style="padding:14px 16px; white-space:nowrap;">
                                @if($ticket->category)
                                    <span class="tp-pill" style="background:color-mix(in srgb, {{ $ticket->category->color }} 18%, transparent); color:{{ $ticket->category->color }};">
                                        @if($ticket->category->icon)<i class="{{ $ticket->category->icon }}"></i>@endif
                                        {{ $ticket->category->name }}
                                    </span>
                                @else
                                    <span style="color:var(--ink2); font-size:12px;">-</span>
                                @endif
                            </td>
                            {{-- ความสำคัญ --}}
                            <td style="padding:14px 16px; white-space:nowrap;">
                                <span class="tp-pill" style="background:color-mix(in srgb, {{ $pc }} 18%, transparent); color:{{ $pc }}; font-weight:700;">
                                    {{ $ticket->priority_label }}
                                </span>
                            </td>
                            {{-- สถานะ --}}
                            <td style="padding:14px 16px; white-space:nowrap;">
                                <span class="tp-pill" style="background:color-mix(in srgb, {{ $sc }} 18%, transparent); color:{{ $sc }};">
                                    {{ $ticket->status_label }}
                                </span>
                            </td>
                            {{-- ผู้ดูแล --}}
                            <td style="padding:14px 16px; white-space:nowrap; font-size:13.5px;">
                                @if($ticket->assignedTo)
                                    <span style="color:var(--ink);">{{ $ticket->assignedTo->name }}</span>
                                @else
                                    <span style="color:var(--ink2); font-style:italic;">ไม่ได้มอบหมาย</span>
                                @endif
                            </td>
                            {{-- สร้างเมื่อ --}}
                            <td style="padding:14px 16px; white-space:nowrap;">
                                <div style="font-size:13px; color:var(--ink);">{{ $ticket->created_at->format('d/m/Y') }}</div>
                                <div style="font-size:11.5px; color:var(--ink2);">{{ $ticket->created_at->format('H:i') }}</div>
                            </td>
                            {{-- จัดการ --}}
                            <td style="padding:14px 16px; text-align:right; white-space:nowrap;">
                                <a href="{{ route('admin.tickets.show', $ticket->id) }}" class="tp-btn tp-btn-sm tp-btn-primary">
                                    <i class="fa-solid fa-eye"></i> ดู
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" style="padding:0;">
                                <div style="text-align:center; color:var(--ink2); padding:40px 0;">
                                    <i class="fa-solid fa-ticket" style="font-size:32px; display:block; margin-bottom:8px; opacity:.5;"></i>
                                    ไม่พบ Ticket
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- ===== Pagination ===== --}}
    @if($tickets->hasPages())
        <div>{{ $tickets->appends(request()->query())->links() }}</div>
    @endif

</div>
@endsection
