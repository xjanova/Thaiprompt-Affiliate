@extends('layouts.admin-v4')

@section('title', 'ความพึงพอใจ Ticket')

@section('content')
{{-- ⭐ ความพึงพอใจและ Feedback (ธีม V4 นวลทองคำ) — คงลิงก์/ข้อมูลเดิม 100% --}}
@php
    $avg = (float) ($stats['average_rating'] ?? 0);
    $subScores = [
        ['average_response_speed',    'ความเร็วในการตอบ',   'fa-gauge-high',   '#5aa07e'],
        ['average_solution_quality',  'คุณภาพการแก้ปัญหา',  'fa-circle-check', '#5689b8'],
    ];
@endphp

<div style="display:flex; flex-direction:column; gap:18px;">

    {{-- ===== Header ===== --}}
    <div style="display:flex; flex-wrap:wrap; align-items:flex-end; justify-content:space-between; gap:14px;">
        <div>
            <div style="font-size:11px; color:var(--ink2); font-weight:600; letter-spacing:.4px;">หลังบ้าน · ศูนย์ช่วยเหลือ · ความพึงพอใจ</div>
            <h1 class="tp-num" style="font-size:clamp(22px,4vw,28px); font-weight:800; margin:4px 0 0;">ความพึงพอใจ &amp; Feedback ⭐</h1>
            <div style="font-size:12.5px; color:var(--ink2); margin-top:4px;">ติดตามคะแนนและความคิดเห็นจากผู้ใช้งาน</div>
        </div>
        <a href="{{ route('admin.tickets.index') }}" class="tp-btn tp-btn-sm"><i class="fas fa-arrow-left"></i> กลับหน้าหลัก</a>
    </div>

    {{-- ===== KPI ===== --}}
    <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(220px,1fr)); gap:16px;">
        {{-- คะแนนเฉลี่ย --}}
        <div class="tp-card" style="padding:18px;">
            <div style="display:flex; align-items:center; gap:12px;">
                <div class="tp-tile" style="width:42px; height:42px; border-radius:12px; font-size:18px; display:flex; align-items:center; justify-content:center;">
                    <i class="fas fa-star"></i>
                </div>
                <div>
                    <div style="display:flex; align-items:baseline; gap:8px;">
                        <div class="tp-num" style="font-size:26px; font-weight:800; line-height:1;">{{ number_format($avg, 1) }}</div>
                        <div style="white-space:nowrap;">
                            @for($i = 1; $i <= 5; $i++)
                                <i class="fas fa-star" style="font-size:12px; color:{{ $i <= round($avg) ? '#e0a52e' : 'color-mix(in srgb, var(--ink2) 35%, transparent)' }};"></i>
                            @endfor
                        </div>
                    </div>
                    <div style="font-size:12px; color:var(--ink2); margin-top:3px;">คะแนนเฉลี่ย (เต็ม 5)</div>
                </div>
            </div>
        </div>

        {{-- จำนวนรีวิว --}}
        <div class="tp-card" style="padding:18px;">
            <div style="display:flex; align-items:center; gap:12px;">
                <div class="tp-tile" style="width:42px; height:42px; border-radius:12px; font-size:18px; display:flex; align-items:center; justify-content:center; background:#4fa3a3;">
                    <i class="fas fa-comments"></i>
                </div>
                <div>
                    <div class="tp-num" style="font-size:26px; font-weight:800; line-height:1;">{{ number_format($stats['total_ratings'] ?? 0) }}</div>
                    <div style="font-size:12px; color:var(--ink2); margin-top:3px;">จำนวนรีวิวทั้งหมด</div>
                </div>
            </div>
        </div>

        {{-- คะแนนย่อย 2 ตัว พร้อมแถบสัดส่วน --}}
        @foreach($subScores as [$key, $label, $icon, $color])
            @php $val = (float) ($stats[$key] ?? 0); @endphp
            <div class="tp-card" style="padding:18px;">
                <div style="display:flex; align-items:center; gap:12px; margin-bottom:10px;">
                    <div class="tp-tile" style="width:42px; height:42px; border-radius:12px; font-size:18px; display:flex; align-items:center; justify-content:center; background:{{ $color }};">
                        <i class="fas {{ $icon }}"></i>
                    </div>
                    <div>
                        <div class="tp-num" style="font-size:26px; font-weight:800; line-height:1;">{{ number_format($val, 1) }}</div>
                        <div style="font-size:12px; color:var(--ink2); margin-top:3px;">{{ $label }}</div>
                    </div>
                </div>
                <div class="tp-well" style="height:7px; border-radius:99px; overflow:hidden; padding:0;">
                    <div style="height:100%; width:{{ max(0, min(100, ($val / 5) * 100)) }}%; background:{{ $color }}; border-radius:99px;"></div>
                </div>
            </div>
        @endforeach
    </div>

    {{-- ===== ตาราง ===== --}}
    <div class="tp-card" style="padding:0; overflow:hidden;">
        <div style="padding:18px 20px 0;">
            <div class="tp-section-h" style="margin:0;"><i class="fas fa-list"></i> รีวิวล่าสุด</div>
        </div>

        <div style="overflow-x:auto; margin-top:14px;">
            <table style="min-width:100%; border-collapse:collapse;">
                <thead>
                    <tr>
                        @foreach(['Ticket','ผู้ใช้','คะแนนรวม','ความเร็วตอบ','คุณภาพแก้ปัญหา','ความเป็นมิตร','ความคิดเห็น','วันที่'] as $th)
                            <th style="padding:14px 16px; text-align:left; font-size:11px; font-weight:700; color:var(--ink2); text-transform:uppercase; letter-spacing:.4px; white-space:nowrap;">{{ $th }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @forelse($ratings as $rating)
                        <tr style="box-shadow:var(--inset-sm); transition:background .15s;"
                            onmouseover="this.style.background='var(--bg)'" onmouseout="this.style.background='transparent'">
                            {{-- Ticket --}}
                            <td style="padding:14px 16px; white-space:nowrap;">
                                <a href="{{ route('admin.tickets.show', $rating->ticket_id) }}"
                                   style="color:var(--deep1); font-weight:700; font-family:monospace; font-size:13px; text-decoration:none;">
                                    <i class="fas fa-ticket"></i> {{ $rating->ticket->ticket_number ?? '—' }}
                                </a>
                            </td>
                            {{-- ผู้ใช้ --}}
                            <td style="padding:14px 16px; white-space:nowrap;">
                                <div style="display:flex; align-items:center; gap:9px;">
                                    <span class="tp-tile" style="width:30px; height:30px; border-radius:50%; font-size:12px; font-weight:800; display:flex; align-items:center; justify-content:center; flex-shrink:0; background:#b79ae8;">
                                        {{ mb_strtoupper(mb_substr($rating->user?->name ?: '?', 0, 1)) }}
                                    </span>
                                    <span style="font-size:13px; color:var(--ink);">{{ $rating->user?->name ?: '—' }}</span>
                                </div>
                            </td>
                            {{-- คะแนนรวม --}}
                            <td style="padding:14px 16px; white-space:nowrap;">
                                <div style="display:flex; align-items:center; gap:7px;">
                                    <span>
                                        @for($i = 1; $i <= 5; $i++)
                                            <i class="fas fa-star" style="font-size:12px; color:{{ $i <= $rating->rating ? '#e0a52e' : 'color-mix(in srgb, var(--ink2) 35%, transparent)' }};"></i>
                                        @endfor
                                    </span>
                                    <span style="font-size:12.5px; font-weight:700; color:var(--ink);">{{ $rating->rating }}/5</span>
                                </div>
                            </td>
                            {{-- ความเร็วตอบ --}}
                            <td style="padding:14px 16px; white-space:nowrap;">
                                <span class="tp-pill" style="background:rgba(90,160,126,.18); color:#3f7a5c;">{{ $rating->response_speed ?? '-' }}/5</span>
                            </td>
                            {{-- คุณภาพแก้ปัญหา --}}
                            <td style="padding:14px 16px; white-space:nowrap;">
                                <span class="tp-pill" style="background:rgba(86,137,184,.18); color:#3f6a96;">{{ $rating->solution_quality ?? '-' }}/5</span>
                            </td>
                            {{-- ความเป็นมิตร --}}
                            <td style="padding:14px 16px; white-space:nowrap;">
                                <span class="tp-pill" style="background:rgba(183,154,232,.18); color:#7a5db8;">{{ $rating->staff_friendliness ?? '-' }}/5</span>
                            </td>
                            {{-- ความคิดเห็น --}}
                            <td style="padding:14px 16px; font-size:13px; color:var(--ink);">
                                @if($rating->feedback)
                                    {{ Str::limit($rating->feedback, 50) }}
                                @else
                                    <span style="color:var(--ink2); font-style:italic;">ไม่มีความคิดเห็น</span>
                                @endif
                            </td>
                            {{-- วันที่ --}}
                            <td style="padding:14px 16px; white-space:nowrap;">
                                <div style="font-size:13px; color:var(--ink);">{{ $rating->created_at->format('d/m/Y') }}</div>
                                <div style="font-size:11.5px; color:var(--ink2);">{{ $rating->created_at->format('H:i') }}</div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" style="padding:0;">
                                <div style="text-align:center; color:var(--ink2); padding:44px 0;">
                                    <i class="fas fa-star" style="font-size:34px; display:block; margin-bottom:10px; opacity:.5;"></i>
                                    <div style="font-size:14px; font-weight:600;">ยังไม่มีรีวิว</div>
                                    <div style="font-size:12px; margin-top:4px;">คะแนนจะแสดงที่นี่เมื่อลูกค้าให้ความคิดเห็น</div>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- ===== Pagination (ถ้า controller ส่งมาเป็น paginator) ===== --}}
    @if($ratings instanceof \Illuminate\Contracts\Pagination\Paginator && $ratings->hasPages())
        <div>{{ $ratings->appends(request()->query())->links() }}</div>
    @endif

</div>
@endsection
