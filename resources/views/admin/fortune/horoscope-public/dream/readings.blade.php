{{-- ผลทำนายฝัน — รายการ (ธีม V4 นวลทองคำ) --}}
@extends('layouts.admin-v4')

@section('title', $pageTitle)

@section('content')
{{-- คอนเทนเนอร์หลัก จัดเรียงแนวตั้ง เว้นระยะ 18px --}}
<div style="display:flex;flex-direction:column;gap:18px;">

    {{-- ส่วนหัว: ป้ายนำทาง + ชื่อหน้า + ปุ่มกลับพจนานุกรม --}}
    <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:16px;flex-wrap:wrap;">
        <div>
            <div class="tp-muted" style="font-size:12px;letter-spacing:.08em;text-transform:uppercase;margin-bottom:6px;">
                หลังบ้าน · ระบบดูดวง · ผลทำนายฝัน
            </div>
            <h1 class="tp-num" style="font-size:26px;margin:0;display:flex;align-items:center;gap:10px;">
                <i class="fas fa-moon" style="color:var(--accent1);"></i>
                ประวัติการทำนายฝัน
            </h1>
            <p class="tp-muted" style="margin:6px 0 0;font-size:13px;">
                ดูผลทำนายฝัน AI ทั้งหมดจากผู้ใช้
            </p>
        </div>
        <a href="{{ route('admin.fortune.horoscope-public.dream.index') }}" class="tp-btn">
            <i class="fas fa-book"></i>
            พจนานุกรม
        </a>
    </div>

    {{-- การ์ด KPI สรุปภาพรวม --}}
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:14px;">

        {{-- ทั้งหมด --}}
        <div class="tp-card tp-raise">
            <div style="display:flex;align-items:center;justify-content:space-between;gap:10px;">
                <div>
                    <div class="tp-muted" style="font-size:12px;margin-bottom:6px;">ทั้งหมด</div>
                    <div class="tp-num" style="font-size:26px;font-weight:700;">{{ number_format($stats['total']) }}</div>
                </div>
                <div class="tp-tile" style="width:44px;height:44px;display:flex;align-items:center;justify-content:center;">
                    <i class="fas fa-cloud-moon" style="color:var(--accent1);font-size:18px;"></i>
                </div>
            </div>
        </div>

        {{-- วันนี้ --}}
        <div class="tp-card tp-raise">
            <div style="display:flex;align-items:center;justify-content:space-between;gap:10px;">
                <div>
                    <div class="tp-muted" style="font-size:12px;margin-bottom:6px;">วันนี้</div>
                    <div class="tp-num" style="font-size:26px;font-weight:700;color:#5689b8;">{{ number_format($stats['today']) }}</div>
                </div>
                <div class="tp-tile" style="width:44px;height:44px;display:flex;align-items:center;justify-content:center;">
                    <i class="fas fa-calendar-day" style="color:#5689b8;font-size:18px;"></i>
                </div>
            </div>
        </div>

        {{-- ใช้ฟรี --}}
        <div class="tp-card tp-raise">
            <div style="display:flex;align-items:center;justify-content:space-between;gap:10px;">
                <div>
                    <div class="tp-muted" style="font-size:12px;margin-bottom:6px;">ใช้ฟรี</div>
                    <div class="tp-num" style="font-size:26px;font-weight:700;color:#5aa07e;">{{ number_format($stats['free']) }}</div>
                </div>
                <div class="tp-tile" style="width:44px;height:44px;display:flex;align-items:center;justify-content:center;">
                    <i class="fas fa-gift" style="color:#5aa07e;font-size:18px;"></i>
                </div>
            </div>
        </div>

        {{-- ยอดเข้าชมรวม --}}
        <div class="tp-card tp-raise">
            <div style="display:flex;align-items:center;justify-content:space-between;gap:10px;">
                <div>
                    <div class="tp-muted" style="font-size:12px;margin-bottom:6px;">ยอดเข้าชมรวม</div>
                    <div class="tp-num" style="font-size:26px;font-weight:700;color:#e0a52e;">{{ number_format($stats['views']) }}</div>
                </div>
                <div class="tp-tile" style="width:44px;height:44px;display:flex;align-items:center;justify-content:center;">
                    <i class="fas fa-eye" style="color:#e0a52e;font-size:18px;"></i>
                </div>
            </div>
        </div>
    </div>

    {{-- กล่องค้นหา (คงฟอร์ม GET + ชื่อ field search เดิม) --}}
    <div class="tp-card">
        <form method="GET" action="{{ route('admin.fortune.horoscope-public.dream.readings') }}"
              style="display:flex;gap:12px;align-items:center;flex-wrap:wrap;">
            <div class="tp-well tp-input" style="flex:1;min-width:220px;display:flex;align-items:center;gap:10px;">
                <i class="fas fa-magnifying-glass tp-muted"></i>
                <input type="text" name="search" value="{{ request('search') }}"
                       placeholder="ค้นหาตามเรื่องฝัน..."
                       style="flex:1;border:0;background:transparent;outline:none;color:var(--ink);font-size:14px;">
            </div>
            <button type="submit" class="tp-btn tp-btn-primary">
                <i class="fas fa-magnifying-glass"></i>
                ค้นหา
            </button>
            @if(request('search'))
                <a href="{{ route('admin.fortune.horoscope-public.dream.readings') }}" class="tp-btn tp-btn-sm">
                    <i class="fas fa-xmark"></i>
                    ล้าง
                </a>
            @endif
        </form>
    </div>

    {{-- ตารางผลทำนายฝัน (การ์ดเต็มขอบ padding:0) --}}
    <div class="tp-card" style="padding:0;overflow:hidden;">
        <div style="overflow-x:auto;">
            <table style="width:100%;border-collapse:collapse;font-size:14px;">
                <thead>
                    <tr style="text-align:left;">
                        <th style="padding:14px 16px;color:var(--ink2);font-weight:600;font-size:12px;text-transform:uppercase;letter-spacing:.04em;">ID</th>
                        <th style="padding:14px 16px;color:var(--ink2);font-weight:600;font-size:12px;text-transform:uppercase;letter-spacing:.04em;">เรื่องฝัน</th>
                        <th style="padding:14px 16px;color:var(--ink2);font-weight:600;font-size:12px;text-transform:uppercase;letter-spacing:.04em;text-align:center;">คำสำคัญ</th>
                        <th style="padding:14px 16px;color:var(--ink2);font-weight:600;font-size:12px;text-transform:uppercase;letter-spacing:.04em;text-align:center;">เลขเด็ด</th>
                        <th style="padding:14px 16px;color:var(--ink2);font-weight:600;font-size:12px;text-transform:uppercase;letter-spacing:.04em;text-align:center;">AI</th>
                        <th style="padding:14px 16px;color:var(--ink2);font-weight:600;font-size:12px;text-transform:uppercase;letter-spacing:.04em;text-align:center;">เข้าชม</th>
                        <th style="padding:14px 16px;color:var(--ink2);font-weight:600;font-size:12px;text-transform:uppercase;letter-spacing:.04em;">วันที่</th>
                        <th style="padding:14px 16px;color:var(--ink2);font-weight:600;font-size:12px;text-transform:uppercase;letter-spacing:.04em;text-align:right;">จัดการ</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($readings as $reading)
                        <tr style="box-shadow:var(--inset-sm);">
                            {{-- ID --}}
                            <td style="padding:14px 16px;color:var(--ink2);" class="tp-num">#{{ $reading->id }}</td>

                            {{-- เรื่องฝัน --}}
                            <td style="padding:14px 16px;color:var(--ink);max-width:320px;">
                                <span style="display:block;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">
                                    {{ Str::limit($reading->dream_description, 60) }}
                                </span>
                            </td>

                            {{-- จำนวนคำสำคัญที่ตรง --}}
                            <td style="padding:14px 16px;text-align:center;">
                                <span class="tp-pill tp-pill-soft tp-num">{{ count($reading->matched_keywords ?? []) }}</span>
                            </td>

                            {{-- เลขเด็ด (แสดง 3 ตัวแรก) --}}
                            <td style="padding:14px 16px;text-align:center;">
                                @if(!empty($reading->lucky_numbers))
                                    <div style="display:flex;flex-wrap:wrap;justify-content:center;gap:4px;">
                                        @foreach(array_slice($reading->lucky_numbers, 0, 3) as $num)
                                            <span class="tp-pill tp-pill-gold tp-num">{{ $num }}</span>
                                        @endforeach
                                    </div>
                                @else
                                    <span class="tp-muted">—</span>
                                @endif
                            </td>

                            {{-- ผู้ให้บริการ AI --}}
                            <td style="padding:14px 16px;text-align:center;font-size:12px;color:var(--ink2);">
                                {{ $reading->ai_provider_used ?? '—' }}
                            </td>

                            {{-- ยอดเข้าชม --}}
                            <td style="padding:14px 16px;text-align:center;color:var(--ink2);" class="tp-num">
                                {{ number_format($reading->view_count) }}
                            </td>

                            {{-- วันที่ (แบบอ่านง่ายภาษาไทย) --}}
                            <td style="padding:14px 16px;color:var(--ink2);font-size:13px;">
                                {{ $reading->created_at->locale('th')->diffForHumans() }}
                            </td>

                            {{-- ปุ่มจัดการ: ดูรายละเอียด + ลบ --}}
                            <td style="padding:14px 16px;text-align:right;">
                                <div style="display:inline-flex;gap:6px;align-items:center;justify-content:flex-end;">
                                    @if(Route::has('admin.fortune.horoscope-public.dream.readings.show'))
                                        <a href="{{ route('admin.fortune.horoscope-public.dream.readings.show', $reading) }}"
                                           class="tp-icon-btn" title="ดูรายละเอียด">
                                            <i class="fas fa-magnifying-glass"></i>
                                        </a>
                                    @endif

                                    {{-- ฟอร์มลบ (คง @csrf + @method DELETE + ยืนยันก่อนลบ) --}}
                                    <form action="{{ route('admin.fortune.horoscope-public.dream.readings.destroy', $reading) }}"
                                          method="POST" style="display:inline;"
                                          onsubmit="return confirm('ลบผลทำนาย #{{ $reading->id }}?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="tp-icon-btn" title="ลบ"
                                                style="color:#d9534f;">
                                            <i class="fas fa-trash-can"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        {{-- สถานะว่าง --}}
                        <tr>
                            <td colspan="8" style="padding:48px 16px;text-align:center;">
                                <div class="tp-muted" style="display:flex;flex-direction:column;align-items:center;gap:10px;">
                                    <i class="fas fa-inbox" style="font-size:32px;opacity:.5;"></i>
                                    <span>ยังไม่มีผลทำนายฝัน</span>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- หน้าเพจ (คง query string ตอนค้นหา) --}}
    @if($readings->hasPages())
        <div>{{ $readings->withQueryString()->links() }}</div>
    @endif

</div>
@endsection
