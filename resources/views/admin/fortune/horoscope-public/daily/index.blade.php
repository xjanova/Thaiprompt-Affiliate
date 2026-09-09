{{-- ดวงรายวัน 7+1 วันเกิด — ธีม V4 นวลทองคำ --}}
@extends('layouts.admin-v4')

@section('title', $pageTitle)

@section('content')
@php
    // สรุป KPI จากรายการในหน้านี้
    $rows       = collect($predictions->items());
    $countTotal = $predictions->total();
    $countGen   = $rows->where('status', 'generated')->count();
    $sumViews   = $rows->sum('view_count');

    // วันนี้ครบหรือยัง — ตัวเลขที่ต้องมองทุกเช้า
    $todayComplete = $readyToday >= $expectedToday;
@endphp

<div style="display:flex;flex-direction:column;gap:18px;">

    {{-- ===== HEADER ===== --}}
    <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:16px;flex-wrap:wrap;">
        <div>
            <div class="tp-muted" style="font-size:12px;letter-spacing:.04em;margin-bottom:4px;">
                หลังบ้าน · ระบบดูดวง · ดวงรายวัน
            </div>
            <h1 class="tp-num" style="font-size:26px;font-weight:800;margin:0;display:flex;align-items:center;gap:10px;">
                <i class="fas fa-calendar-day" style="color:var(--accent1);"></i>
                ดวงรายวัน (7+1 วันเกิด)
            </h1>
            <div class="tp-muted" style="font-size:13px;margin-top:4px;">
                cron สร้างให้อัตโนมัติทุกวัน 00:01 น. · ยามตรวจซ้ำ 00:20 และ 06:00
            </div>
        </div>
        @if(Route::has('admin.fortune.horoscope-public.settings'))
            <a href="{{ route('admin.fortune.horoscope-public.settings') }}" class="tp-btn">
                <i class="fas fa-gear"></i> ตั้งค่า
            </a>
        @endif
    </div>

    {{-- ===== KPI SUMMARY ===== --}}
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:14px;">
        {{-- วันนี้ --}}
        <div class="tp-card">
            <div style="display:flex;align-items:center;gap:12px;">
                <div class="tp-tile" style="width:46px;height:46px;display:flex;align-items:center;justify-content:center;font-size:18px;color:{{ $todayComplete ? '#5aa07e' : '#d9534f' }};">
                    <i class="fas {{ $todayComplete ? 'fa-circle-check' : 'fa-triangle-exclamation' }}"></i>
                </div>
                <div>
                    <div class="tp-muted" style="font-size:12px;">ดวงวันนี้</div>
                    <div class="tp-num" style="font-size:22px;font-weight:800;color:{{ $todayComplete ? '#5aa07e' : '#d9534f' }};">
                        {{ $readyToday }}<span class="tp-muted" style="font-size:16px;font-weight:600;">/{{ $expectedToday }}</span>
                    </div>
                </div>
            </div>
        </div>

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

    {{-- ===== สร้างซ้ำด้วยมือ ===== --}}
    <div class="tp-card tp-raise">
        <div class="tp-section-h" style="display:flex;align-items:center;gap:8px;margin-bottom:4px;">
            <i class="fas fa-robot" style="color:var(--accent1);"></i>
            <span>สร้างดวงรายวันด้วยมือ</span>
        </div>
        <div class="tp-muted" style="font-size:13px;margin-bottom:14px;">
            ใช้เมื่อรอบอัตโนมัติล้ม — ใบที่มีอยู่แล้วจะถูกข้าม ไม่ยิง AI ซ้ำ
            (ถ้าอยากให้สร้างใหม่จริง ให้ลบใบนั้นก่อน)
        </div>

        <form action="{{ route('admin.fortune.horoscope-public.daily.generate') }}" method="POST"
              style="display:flex;flex-wrap:wrap;gap:12px;align-items:flex-end;"
              onsubmit="this.querySelector('button[type=submit]').disabled = true;">
            @csrf

            <div style="flex:1;min-width:180px;">
                <label class="tp-muted" style="display:block;font-size:12px;font-weight:600;margin-bottom:6px;">
                    <i class="fas fa-calendar"></i> วันที่
                </label>
                <div class="tp-well">
                    <input type="date" name="target_date" value="{{ today()->format('Y-m-d') }}"
                           class="tp-input" style="width:100%;">
                </div>
            </div>

            <button type="submit" class="tp-btn tp-btn-primary"
                    onclick="return confirm('สั่งสร้างดวงรายวันของวันที่เลือก? (ใบที่มีอยู่แล้วจะถูกข้าม)')">
                <i class="fas fa-wand-magic-sparkles"></i> สร้างดวงรายวัน
            </button>
        </form>
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

            {{-- สถานะ --}}
            <div style="flex:1;min-width:160px;">
                <label class="tp-muted" style="font-size:12px;display:block;margin-bottom:6px;">สถานะ</label>
                <div class="tp-well" style="padding:0;">
                    <select name="status" class="tp-input" style="background:transparent;border:0;width:100%;">
                        <option value="">— ทุกสถานะ —</option>
                        <option value="generated" {{ request('status') === 'generated' ? 'selected' : '' }}>✅ สร้างแล้ว</option>
                        <option value="generating" {{ request('status') === 'generating' ? 'selected' : '' }}>⏳ กำลังสร้าง</option>
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
                <a href="{{ route('admin.fortune.horoscope-public.daily.index') }}" class="tp-btn">
                    <i class="fas fa-eraser"></i> ล้าง
                </a>
            </div>
        </form>
    </div>

    {{-- ===== ตาราง ===== --}}
    <div class="tp-card" style="padding:0;overflow:hidden;">
        <div style="overflow-x:auto;">
            <table style="width:100%;border-collapse:collapse;min-width:720px;">
                <thead>
                    <tr style="text-align:left;">
                        <th style="padding:14px 16px;font-size:12px;font-weight:700;color:var(--ink2);text-transform:uppercase;letter-spacing:.03em;">วันที่</th>
                        <th style="padding:14px 16px;font-size:12px;font-weight:700;color:var(--ink2);text-transform:uppercase;letter-spacing:.03em;">วันเกิด</th>
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

                            {{-- วันเกิด (index 7 = พุธกลางคืน) --}}
                            <td style="padding:14px 16px;">
                                <span style="font-size:14px;color:var(--ink);">
                                    📅 วัน{{ \App\Services\Fortune\DailyArticleMirror::dayName((int) $pred->birth_day) }}
                                </span>
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
                                @elseif(in_array($pred->status, ['pending', 'generating'], true))
                                    <span class="tp-pill" style="color:#e0a52e;"><i class="fas fa-clock"></i> {{ $pred->status === 'generating' ? 'กำลังสร้าง' : 'รอ' }}</span>
                                @else
                                    <span class="tp-pill" style="color:#d9534f;"><i class="fas fa-circle-xmark"></i> ล้มเหลว</span>
                                @endif
                            </td>

                            {{-- จัดการ (ลบ) --}}
                            <td style="padding:14px 16px;text-align:right;">
                                <form action="{{ route('admin.fortune.horoscope-public.daily.destroy', $pred) }}" method="POST" style="display:inline;"
                                      onsubmit="return confirm('ลบดวงใบนี้? รอบถัดไปจะสร้างใหม่ให้')">
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
                            <td colspan="7" style="padding:48px 16px;text-align:center;">
                                <div class="tp-muted" style="display:flex;flex-direction:column;align-items:center;gap:10px;">
                                    <i class="fas fa-inbox" style="font-size:32px;opacity:.5;"></i>
                                    <span>ยังไม่มีดวงรายวันตามเงื่อนไขที่กรอง</span>
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
