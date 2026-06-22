{{-- ดวงรายวันทั้งหมด — ธีม V4 นวลทองคำ --}}
@extends('layouts.admin-v4')

@section('title', $pageTitle)

@section('content')
@php
    // ชื่อวันเกิด (index 0=อาทิตย์)
    $dayNames = ['อาทิตย์', 'จันทร์', 'อังคาร', 'พุธ', 'พฤหัสบดี', 'ศุกร์', 'เสาร์'];

    // คำนวณสรุป KPI จากรายการในหน้านี้
    $rows         = collect($predictions->items());
    $countTotal   = $predictions->total();
    $countZodiac  = $rows->where('prediction_type', 'zodiac')->count();
    $countBirth   = $rows->where('prediction_type', 'birth_day')->count();
    $countGen     = $rows->where('status', 'generated')->count();
    $sumViews     = $rows->sum('view_count');
@endphp

<div style="display:flex;flex-direction:column;gap:18px;">

    {{-- ===== HEADER ===== --}}
    <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:16px;flex-wrap:wrap;">
        <div>
            <div class="tp-muted" style="font-size:12px;letter-spacing:.04em;margin-bottom:4px;">
                หลังบ้าน · ระบบดูดวง · ดวงรายวัน
            </div>
            <h1 class="tp-num" style="font-size:26px;font-weight:800;margin:0;display:flex;align-items:center;gap:10px;">
                <i class="fas fa-calendar-star" style="color:var(--accent1);"></i>
                ดวงรายวันทั้งหมด
            </h1>
            <div class="tp-muted" style="font-size:13px;margin-top:4px;">รายการดวงรายวันที่สร้างจาก AI</div>
        </div>
        <a href="{{ route('admin.fortune.horoscope-public.zodiac.index') }}" class="tp-btn">
            <i class="fas fa-arrow-left"></i> จัดการราศี
        </a>
    </div>

    {{-- ===== KPI SUMMARY ===== --}}
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:14px;">
        {{-- ทั้งหมด --}}
        <div class="tp-card">
            <div style="display:flex;align-items:center;gap:12px;">
                <div class="tp-tile" style="width:46px;height:46px;display:flex;align-items:center;justify-content:center;font-size:18px;color:var(--accent1);">
                    <i class="fas fa-layer-group"></i>
                </div>
                <div>
                    <div class="tp-muted" style="font-size:12px;">ทั้งหมด (ในระบบ)</div>
                    <div class="tp-num" style="font-size:22px;font-weight:800;">{{ number_format($countTotal) }}</div>
                </div>
            </div>
        </div>

        {{-- ราศี --}}
        <div class="tp-card">
            <div style="display:flex;align-items:center;gap:12px;">
                <div class="tp-tile" style="width:46px;height:46px;display:flex;align-items:center;justify-content:center;font-size:18px;color:#b79ae8;">
                    <i class="fas fa-star"></i>
                </div>
                <div>
                    <div class="tp-muted" style="font-size:12px;">ราศี (หน้านี้)</div>
                    <div class="tp-num" style="font-size:22px;font-weight:800;">{{ number_format($countZodiac) }}</div>
                </div>
            </div>
        </div>

        {{-- วันเกิด --}}
        <div class="tp-card">
            <div style="display:flex;align-items:center;gap:12px;">
                <div class="tp-tile" style="width:46px;height:46px;display:flex;align-items:center;justify-content:center;font-size:18px;color:#5689b8;">
                    <i class="fas fa-calendar-day"></i>
                </div>
                <div>
                    <div class="tp-muted" style="font-size:12px;">วันเกิด (หน้านี้)</div>
                    <div class="tp-num" style="font-size:22px;font-weight:800;">{{ number_format($countBirth) }}</div>
                </div>
            </div>
        </div>

        {{-- สร้างแล้ว --}}
        <div class="tp-card">
            <div style="display:flex;align-items:center;gap:12px;">
                <div class="tp-tile" style="width:46px;height:46px;display:flex;align-items:center;justify-content:center;font-size:18px;color:#5aa07e;">
                    <i class="fas fa-circle-check"></i>
                </div>
                <div>
                    <div class="tp-muted" style="font-size:12px;">สร้างแล้ว (หน้านี้)</div>
                    <div class="tp-num" style="font-size:22px;font-weight:800;">{{ number_format($countGen) }}</div>
                </div>
            </div>
        </div>

        {{-- ยอดวิว --}}
        <div class="tp-card">
            <div style="display:flex;align-items:center;gap:12px;">
                <div class="tp-tile" style="width:46px;height:46px;display:flex;align-items:center;justify-content:center;font-size:18px;color:var(--accent2);">
                    <i class="fas fa-eye"></i>
                </div>
                <div>
                    <div class="tp-muted" style="font-size:12px;">ยอดวิว (หน้านี้)</div>
                    <div class="tp-num" style="font-size:22px;font-weight:800;">{{ number_format($sumViews) }}</div>
                </div>
            </div>
        </div>
    </div>

    {{-- ===== ตัวกรอง ===== --}}
    <div class="tp-card">
        <div class="tp-section-h" style="margin-bottom:14px;display:flex;align-items:center;gap:8px;">
            <i class="fas fa-filter" style="color:var(--accent1);"></i> ตัวกรอง
        </div>
        <form method="GET" style="display:flex;flex-wrap:wrap;gap:12px;align-items:flex-end;">
            {{-- วันที่ --}}
            <div style="flex:1;min-width:160px;">
                <label class="tp-muted" style="font-size:12px;display:block;margin-bottom:6px;">วันที่</label>
                <div class="tp-well">
                    <input type="date" name="date" value="{{ request('date') }}" class="tp-input">
                </div>
            </div>

            {{-- ประเภท --}}
            <div style="flex:1;min-width:160px;">
                <label class="tp-muted" style="font-size:12px;display:block;margin-bottom:6px;">ประเภท</label>
                <div class="tp-well" style="padding:0;">
                    <select name="type" class="tp-input" style="background:transparent;border:0;width:100%;">
                        <option value="">— ทุกประเภท —</option>
                        <option value="zodiac" {{ request('type') === 'zodiac' ? 'selected' : '' }}>⭐ 12 ราศี</option>
                        <option value="birth_day" {{ request('type') === 'birth_day' ? 'selected' : '' }}>📅 7 วันเกิด</option>
                    </select>
                </div>
            </div>

            {{-- สถานะ --}}
            <div style="flex:1;min-width:160px;">
                <label class="tp-muted" style="font-size:12px;display:block;margin-bottom:6px;">สถานะ</label>
                <div class="tp-well" style="padding:0;">
                    <select name="status" class="tp-input" style="background:transparent;border:0;width:100%;">
                        <option value="">— ทุกสถานะ —</option>
                        <option value="generated" {{ request('status') === 'generated' ? 'selected' : '' }}>✅ สร้างแล้ว</option>
                        <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>⏳ รอดำเนินการ</option>
                        <option value="failed" {{ request('status') === 'failed' ? 'selected' : '' }}>❌ ล้มเหลว</option>
                    </select>
                </div>
            </div>

            {{-- ปุ่ม --}}
            <div style="display:flex;gap:8px;">
                <button type="submit" class="tp-btn tp-btn-primary">
                    <i class="fas fa-magnifying-glass"></i> กรอง
                </button>
                <a href="{{ route('admin.fortune.horoscope-public.zodiac.predictions') }}" class="tp-btn">
                    <i class="fas fa-eraser"></i> ล้าง
                </a>
            </div>
        </form>
    </div>

    {{-- ===== ตาราง ===== --}}
    <div class="tp-card" style="padding:0;overflow:hidden;">
        <div style="overflow-x:auto;">
            <table style="width:100%;border-collapse:collapse;min-width:760px;">
                <thead>
                    <tr style="text-align:left;">
                        <th style="padding:14px 16px;font-size:12px;font-weight:700;color:var(--ink2);text-transform:uppercase;letter-spacing:.03em;">วันที่</th>
                        <th style="padding:14px 16px;font-size:12px;font-weight:700;color:var(--ink2);text-transform:uppercase;letter-spacing:.03em;">ราศี/วันเกิด</th>
                        <th style="padding:14px 16px;font-size:12px;font-weight:700;color:var(--ink2);text-transform:uppercase;letter-spacing:.03em;text-align:center;">ประเภท</th>
                        <th style="padding:14px 16px;font-size:12px;font-weight:700;color:var(--ink2);text-transform:uppercase;letter-spacing:.03em;text-align:center;">คะแนนรวม</th>
                        <th style="padding:14px 16px;font-size:12px;font-weight:700;color:var(--ink2);text-transform:uppercase;letter-spacing:.03em;text-align:center;">Views</th>
                        <th style="padding:14px 16px;font-size:12px;font-weight:700;color:var(--ink2);text-transform:uppercase;letter-spacing:.03em;text-align:center;">AI</th>
                        <th style="padding:14px 16px;font-size:12px;font-weight:700;color:var(--ink2);text-transform:uppercase;letter-spacing:.03em;text-align:center;">สถานะ</th>
                        <th style="padding:14px 16px;font-size:12px;font-weight:700;color:var(--ink2);text-transform:uppercase;letter-spacing:.03em;text-align:right;">จัดการ</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($predictions as $pred)
                        <tr style="box-shadow:var(--inset-sm);">
                            {{-- วันที่ --}}
                            <td style="padding:14px 16px;font-size:14px;color:var(--ink);white-space:nowrap;">
                                {{ \Carbon\Carbon::parse($pred->target_date)->locale('th')->translatedFormat('j M Y') }}
                            </td>

                            {{-- ราศี/วันเกิด --}}
                            <td style="padding:14px 16px;">
                                @if($pred->prediction_type === 'zodiac' && $pred->zodiacSign)
                                    <div style="display:flex;align-items:center;gap:8px;">
                                        <span style="font-size:18px;">{{ $pred->zodiacSign->symbol_emoji }}</span>
                                        <span style="font-size:14px;font-weight:600;color:var(--ink);">{{ $pred->zodiacSign->name_th }}</span>
                                    </div>
                                @elseif($pred->prediction_type === 'birth_day')
                                    <span style="font-size:14px;color:var(--ink);">📅 วัน{{ $dayNames[$pred->birth_day] ?? $pred->birth_day }}</span>
                                @else
                                    <span class="tp-muted">—</span>
                                @endif
                            </td>

                            {{-- ประเภท --}}
                            <td style="padding:14px 16px;text-align:center;">
                                @if($pred->prediction_type === 'zodiac')
                                    <span class="tp-pill" style="color:#b79ae8;">⭐ ราศี</span>
                                @else
                                    <span class="tp-pill" style="color:#5689b8;">📅 วันเกิด</span>
                                @endif
                            </td>

                            {{-- คะแนนรวม --}}
                            <td style="padding:14px 16px;text-align:center;">
                                <div style="display:flex;align-items:center;justify-content:center;gap:4px;">
                                    @for($i = 1; $i <= 5; $i++)
                                        <span style="width:8px;height:8px;border-radius:50%;display:inline-block;
                                            background:{{ $i <= ($pred->overall_score ?? 0) ? 'var(--accent1)' : 'var(--sd)' }};
                                            box-shadow:{{ $i <= ($pred->overall_score ?? 0) ? 'none' : 'var(--inset-sm)' }};"></span>
                                    @endfor
                                    <span class="tp-muted" style="margin-left:4px;font-size:12px;">{{ $pred->overall_score ?? '-' }}</span>
                                </div>
                            </td>

                            {{-- Views --}}
                            <td style="padding:14px 16px;text-align:center;font-size:14px;color:var(--ink2);">
                                {{ number_format($pred->view_count) }}
                            </td>

                            {{-- AI --}}
                            <td style="padding:14px 16px;text-align:center;font-size:12px;color:var(--ink2);">
                                {{ $pred->ai_provider_used ?? '—' }}
                            </td>

                            {{-- สถานะ --}}
                            <td style="padding:14px 16px;text-align:center;">
                                @if($pred->status === 'generated')
                                    <span class="tp-pill" style="color:#5aa07e;"><i class="fas fa-circle-check"></i> สร้างแล้ว</span>
                                @elseif($pred->status === 'pending')
                                    <span class="tp-pill" style="color:#e0a52e;"><i class="fas fa-clock"></i> รอ</span>
                                @else
                                    <span class="tp-pill" style="color:#d9534f;"><i class="fas fa-circle-xmark"></i> ล้มเหลว</span>
                                @endif
                            </td>

                            {{-- จัดการ (ลบ) --}}
                            <td style="padding:14px 16px;text-align:right;">
                                <form action="{{ route('admin.fortune.horoscope-public.zodiac.predictions.destroy', $pred) }}" method="POST" style="display:inline;"
                                      onsubmit="return confirm('ลบ prediction นี้?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="tp-icon-btn" style="color:#d9534f;" title="ลบรายการ">
                                        <i class="fas fa-trash-can"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" style="padding:48px 16px;text-align:center;">
                                <div class="tp-muted" style="display:flex;flex-direction:column;align-items:center;gap:10px;">
                                    <i class="fas fa-inbox" style="font-size:32px;opacity:.5;"></i>
                                    <span>ยังไม่มี prediction</span>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- ===== Pagination ===== --}}
    @if($predictions->hasPages())
        <div>{{ $predictions->withQueryString()->links() }}</div>
    @endif
</div>
@endsection
