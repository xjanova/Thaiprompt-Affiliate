@extends('layouts.admin-v4')

@section('title', 'Analytics ระบบ Ticket')

@section('content')
{{--
    📊 Analytics ระบบ Ticket (ธีม V4 นวลทองคำ)
    กราฟใช้แถบ CSS ล้วน ไม่พึ่ง Chart.js — คงตัวกรองช่วงวันที่และข้อมูลเดิม 100%
--}}
@php
    $totalTickets = (int) ($analytics['total_tickets'] ?? 0);
    $divisor = $totalTickets > 0 ? $totalTickets : 1;

    $statusColors = [
        'open' => '#5689b8', 'in_progress' => '#e0a52e', 'waiting_customer' => '#b79ae8',
        'resolved' => '#5aa07e', 'closed' => '#9a8f7c',
    ];
    $statusLabels = [
        'open' => 'เปิด', 'in_progress' => 'กำลังดำเนินการ', 'waiting_customer' => 'รอลูกค้า',
        'resolved' => 'แก้ไขแล้ว', 'closed' => 'ปิด',
    ];
    $priorityColors = ['critical' => '#d9534f', 'high' => '#d6824a', 'medium' => '#e0a52e', 'low' => '#5689b8'];
    $priorityLabels = ['critical' => 'วิกฤต', 'high' => 'สูง', 'medium' => 'ปานกลาง', 'low' => 'ต่ำ'];

    $slaCompliance = (float) ($analytics['sla_compliance'] ?? 100);
    $topAgents = $analytics['top_agents'] ?? [];
    $maxResolved = ($topAgents[0]->resolved_count ?? 0) ?: 1;
@endphp

<div style="display:flex; flex-direction:column; gap:18px;">

    {{-- ===== Header ===== --}}
    <div style="display:flex; flex-wrap:wrap; align-items:flex-end; justify-content:space-between; gap:14px;">
        <div>
            <div style="font-size:11px; color:var(--ink2); font-weight:600; letter-spacing:.4px;">หลังบ้าน · ศูนย์ช่วยเหลือ · Analytics</div>
            <h1 class="tp-num" style="font-size:clamp(22px,4vw,28px); font-weight:800; margin:4px 0 0;">Analytics ระบบ Ticket 📊</h1>
            <div style="font-size:12.5px; color:var(--ink2); margin-top:4px;">
                ช่วง {{ $dateFrom->format('d/m/Y') }} – {{ $dateTo->format('d/m/Y') }}
            </div>
        </div>
        <a href="{{ route('admin.tickets.index') }}" class="tp-btn tp-btn-sm"><i class="fas fa-arrow-left"></i> กลับหน้าหลัก</a>
    </div>

    {{-- ===== ตัวกรองช่วงวันที่ ===== --}}
    <div class="tp-card" style="padding:20px;">
        <div class="tp-section-h" style="margin-bottom:14px;"><i class="fas fa-calendar-days"></i> ช่วงเวลา</div>
        <form method="GET" action="{{ route('admin.tickets.analytics') }}"
              style="display:grid; grid-template-columns:repeat(auto-fit,minmax(200px,1fr)); gap:16px;">
            <div>
                <label style="display:block; font-size:12.5px; color:var(--ink2); font-weight:600; margin-bottom:6px;">วันที่เริ่มต้น</label>
                <div class="tp-well tp-input" style="padding:0;">
                    <input type="date" name="date_from" value="{{ request('date_from', $dateFrom->format('Y-m-d')) }}"
                           style="width:100%; background:transparent; border:none; outline:none; padding:10px 12px; color:var(--ink); font-size:14px;">
                </div>
            </div>
            <div>
                <label style="display:block; font-size:12.5px; color:var(--ink2); font-weight:600; margin-bottom:6px;">วันที่สิ้นสุด</label>
                <div class="tp-well tp-input" style="padding:0;">
                    <input type="date" name="date_to" value="{{ request('date_to', $dateTo->format('Y-m-d')) }}"
                           style="width:100%; background:transparent; border:none; outline:none; padding:10px 12px; color:var(--ink); font-size:14px;">
                </div>
            </div>
            <div style="grid-column:1 / -1; display:flex; flex-wrap:wrap; gap:10px; padding-top:4px;">
                <button type="submit" class="tp-btn tp-btn-primary"><i class="fas fa-magnifying-glass"></i> ดูข้อมูล</button>
                <a href="{{ route('admin.tickets.analytics') }}" class="tp-btn"><i class="fas fa-rotate-left"></i> รีเซ็ต</a>
            </div>
        </form>
    </div>

    {{-- ===== KPI ===== --}}
    @php
        $kpis = [
            [number_format($totalTickets),                                  'Ticket ทั้งหมด',      'fa-layer-group',  null],
            [number_format($analytics['avg_response_time'] ?? 0).' นาที',   'เวลาตอบกลับเฉลี่ย',   'fa-hourglass-start', '#5689b8'],
            [number_format($analytics['avg_resolution_time'] ?? 0).' นาที', 'เวลาปิดงานเฉลี่ย',    'fa-hourglass-end',   '#5aa07e'],
        ];
    @endphp
    <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(220px,1fr)); gap:16px;">
        @foreach($kpis as [$value, $label, $icon, $color])
            <div class="tp-card" style="padding:18px;">
                <div style="display:flex; align-items:center; gap:12px;">
                    <div class="tp-tile" style="width:42px; height:42px; border-radius:12px; font-size:18px; display:flex; align-items:center; justify-content:center;{{ $color ? ' background:'.$color.';' : '' }}">
                        <i class="fas {{ $icon }}"></i>
                    </div>
                    <div>
                        <div class="tp-num" style="font-size:24px; font-weight:800; line-height:1.1;">{{ $value }}</div>
                        <div style="font-size:12px; color:var(--ink2); margin-top:3px;">{{ $label }}</div>
                    </div>
                </div>
            </div>
        @endforeach

        {{-- SLA พร้อมแถบสัดส่วน --}}
        <div class="tp-card" style="padding:18px;">
            <div style="display:flex; align-items:center; gap:12px; margin-bottom:10px;">
                <div class="tp-tile" style="width:42px; height:42px; border-radius:12px; font-size:18px; display:flex; align-items:center; justify-content:center; background:#e0a52e;">
                    <i class="fas fa-shield-halved"></i>
                </div>
                <div>
                    <div class="tp-num" style="font-size:24px; font-weight:800; line-height:1.1;">{{ number_format($slaCompliance) }}%</div>
                    <div style="font-size:12px; color:var(--ink2); margin-top:3px;">ทำได้ตาม SLA</div>
                </div>
            </div>
            <div class="tp-well" style="height:7px; border-radius:99px; overflow:hidden; padding:0;">
                <div style="height:100%; width:{{ max(0, min(100, $slaCompliance)) }}%; background:#e0a52e; border-radius:99px;"></div>
            </div>
        </div>
    </div>

    {{-- ===== สัดส่วนตามสถานะ / ความสำคัญ ===== --}}
    <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(320px,1fr)); gap:18px;">

        {{-- ตามสถานะ --}}
        <div class="tp-card" style="padding:20px;">
            <div class="tp-section-h" style="margin-bottom:16px;"><i class="fas fa-chart-pie"></i> แยกตามสถานะ</div>
            @if(!empty($analytics['tickets_by_status']) && $analytics['tickets_by_status']->isNotEmpty())
                <div style="display:flex; flex-direction:column; gap:13px;">
                    @foreach($analytics['tickets_by_status'] as $status => $count)
                        @php
                            $color = $statusColors[$status] ?? '#9a8f7c';
                            $pct = round(($count / $divisor) * 100);
                        @endphp
                        <div>
                            <div style="display:flex; justify-content:space-between; align-items:center; gap:10px; margin-bottom:6px;">
                                <span style="font-size:13px; font-weight:600; color:var(--ink);">{{ $statusLabels[$status] ?? $status }}</span>
                                <span style="font-size:12.5px; color:var(--ink2);">
                                    <strong style="color:var(--ink);">{{ number_format($count) }}</strong> ({{ $pct }}%)
                                </span>
                            </div>
                            <div class="tp-well" style="height:8px; border-radius:99px; overflow:hidden; padding:0;">
                                <div style="height:100%; width:{{ $pct }}%; background:{{ $color }}; border-radius:99px;"></div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div style="text-align:center; color:var(--ink2); padding:28px 0; font-size:13px;">ไม่มีข้อมูลในช่วงนี้</div>
            @endif
        </div>

        {{-- ตามความสำคัญ --}}
        <div class="tp-card" style="padding:20px;">
            <div class="tp-section-h" style="margin-bottom:16px;"><i class="fas fa-flag"></i> แยกตามความสำคัญ</div>
            @if(!empty($analytics['tickets_by_priority']) && $analytics['tickets_by_priority']->isNotEmpty())
                <div style="display:flex; flex-direction:column; gap:13px;">
                    @foreach($analytics['tickets_by_priority'] as $priority => $count)
                        @php
                            $color = $priorityColors[$priority] ?? '#9a8f7c';
                            $pct = round(($count / $divisor) * 100);
                        @endphp
                        <div>
                            <div style="display:flex; justify-content:space-between; align-items:center; gap:10px; margin-bottom:6px;">
                                <span style="font-size:13px; font-weight:600; color:var(--ink);">{{ $priorityLabels[$priority] ?? $priority }}</span>
                                <span style="font-size:12.5px; color:var(--ink2);">
                                    <strong style="color:var(--ink);">{{ number_format($count) }}</strong> ({{ $pct }}%)
                                </span>
                            </div>
                            <div class="tp-well" style="height:8px; border-radius:99px; overflow:hidden; padding:0;">
                                <div style="height:100%; width:{{ $pct }}%; background:{{ $color }}; border-radius:99px;"></div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div style="text-align:center; color:var(--ink2); padding:28px 0; font-size:13px;">ไม่มีข้อมูลในช่วงนี้</div>
            @endif
        </div>
    </div>

    {{-- ===== เจ้าหน้าที่ที่ปิดงานมากที่สุด ===== --}}
    <div class="tp-card" style="padding:20px;">
        <div class="tp-section-h" style="margin-bottom:16px;"><i class="fas fa-trophy"></i> เจ้าหน้าที่ที่ปิดงานมากที่สุด</div>

        @forelse($topAgents as $index => $agent)
            @php $performance = round(($agent->resolved_count / $maxResolved) * 100); @endphp
            <div style="display:flex; align-items:center; gap:13px; padding:12px 0;{{ $index > 0 ? ' box-shadow:var(--inset-sm);' : '' }}">
                {{-- อันดับ --}}
                <span class="tp-tile" style="width:34px; height:34px; border-radius:50%; font-size:13px; font-weight:800; display:flex; align-items:center; justify-content:center; flex-shrink:0;{{ $index === 0 ? '' : ' background:#9a8f7c;' }}">
                    {{ $index + 1 }}
                </span>

                {{-- ชื่อ --}}
                <div style="flex:1; min-width:0;">
                    <div style="font-size:13.5px; font-weight:700; color:var(--ink); overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">
                        {{ $agent->assignedTo->name ?? '—' }}
                    </div>
                    <div style="font-size:11.5px; color:var(--ink2); overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">
                        {{ $agent->assignedTo->email ?? '' }}
                    </div>
                    <div class="tp-well" style="height:6px; border-radius:99px; overflow:hidden; padding:0; margin-top:7px;">
                        <div style="height:100%; width:{{ $performance }}%; background:#b79ae8; border-radius:99px;"></div>
                    </div>
                </div>

                {{-- ตัวเลข --}}
                <div style="text-align:right; white-space:nowrap;">
                    <div class="tp-num" style="font-size:19px; font-weight:800; color:var(--ink);">{{ number_format($agent->resolved_count) }}</div>
                    <div style="font-size:11.5px; color:var(--ink2);">{{ $performance }}%</div>
                </div>
            </div>
        @empty
            <div style="text-align:center; color:var(--ink2); padding:36px 0;">
                <i class="fas fa-trophy" style="font-size:30px; display:block; margin-bottom:10px; opacity:.5;"></i>
                <div style="font-size:14px; font-weight:600;">ยังไม่มีข้อมูล</div>
                <div style="font-size:12px; margin-top:4px;">สถิติเจ้าหน้าที่จะแสดงเมื่อมี Ticket ถูกปิดงาน</div>
            </div>
        @endforelse
    </div>

</div>
@endsection
