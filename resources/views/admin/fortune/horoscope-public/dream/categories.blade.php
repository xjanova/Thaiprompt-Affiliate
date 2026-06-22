{{-- หมวดหมู่ทำนายฝัน — ธีม V4 "นวลทองคำ" --}}
@extends('layouts.admin-v4')

@section('title', $pageTitle)

@section('content')
@php
    // คำนวณสรุปตัวเลข (KPI) จากหน้าปัจจุบัน
    $catCollection = $categories->getCollection();
    $totalCats = $categories->total();
    $activeCats = $catCollection->where('is_active', true)->count();
    $inactiveCats = $catCollection->where('is_active', false)->count();
    $sumSymbols = $catCollection->sum('dream_symbols_count');
    $sumActiveSymbols = $catCollection->sum('active_symbols_count');

    // เตรียมข้อมูลกราฟ CSS — สัญลักษณ์ต่อหมวด (สูงสุด 8 หมวดบนหน้านี้)
    $chartRows = $catCollection->sortByDesc('dream_symbols_count')->take(8);
    $chartMax = max(1, (int) $chartRows->max('dream_symbols_count'));
@endphp

<div style="display:flex; flex-direction:column; gap:18px;">

    {{-- ===== Header (ธีม V4) ===== --}}
    <div style="display:flex; flex-wrap:wrap; align-items:flex-end; justify-content:space-between; gap:14px;">
        <div>
            <div style="font-size:11px; color:var(--ink2); font-weight:600; letter-spacing:.4px;">
                หลังบ้าน · ระบบดูดวง · หมวดหมู่ทำนายฝัน
            </div>
            <h1 class="tp-num" style="font-size:clamp(22px,4vw,28px); font-weight:800; margin:4px 0 0;">
                📂 หมวดหมู่ทำนายฝัน
            </h1>
            <p class="tp-muted" style="margin:4px 0 0; font-size:13px;">จัดการหมวดหมู่สำหรับจัดกลุ่มสัญลักษณ์ฝัน</p>
        </div>
        <div style="display:flex; gap:10px; flex-wrap:wrap;">
            <a href="{{ route('admin.fortune.horoscope-public.dream.index') }}" class="tp-btn tp-btn-sm">
                <i class="fas fa-book"></i> พจนานุกรม
            </a>
            <a href="{{ route('admin.fortune.horoscope-public.dream.categories.create') }}" class="tp-btn tp-btn-primary">
                <i class="fas fa-plus"></i> เพิ่มหมวดหมู่
            </a>
        </div>
    </div>

    {{-- ===== KPI Grid ===== --}}
    <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(200px,1fr)); gap:14px;">
        <div class="tp-card tp-raise">
            <div class="tp-muted" style="font-size:12px; font-weight:600;">หมวดหมู่ทั้งหมด</div>
            <div class="tp-num" style="font-size:26px; font-weight:800; margin-top:6px;">{{ number_format($totalCats) }}</div>
            <div style="margin-top:8px;"><span class="tp-pill tp-pill-gold"><i class="fas fa-folder"></i> หมวดฝัน</span></div>
        </div>
        <div class="tp-card tp-raise">
            <div class="tp-muted" style="font-size:12px; font-weight:600;">เปิดใช้งาน</div>
            <div class="tp-num" style="font-size:26px; font-weight:800; margin-top:6px; color:#5aa07e;">{{ number_format($activeCats) }}</div>
            <div style="margin-top:8px;"><span class="tp-pill tp-pill-soft" style="color:#5aa07e;"><i class="fas fa-circle-check"></i> ในหน้านี้</span></div>
        </div>
        <div class="tp-card tp-raise">
            <div class="tp-muted" style="font-size:12px; font-weight:600;">ปิดใช้งาน</div>
            <div class="tp-num" style="font-size:26px; font-weight:800; margin-top:6px; color:#9a8f7c;">{{ number_format($inactiveCats) }}</div>
            <div style="margin-top:8px;"><span class="tp-pill tp-pill-soft" style="color:#9a8f7c;"><i class="fas fa-circle-pause"></i> ในหน้านี้</span></div>
        </div>
        <div class="tp-card tp-raise">
            <div class="tp-muted" style="font-size:12px; font-weight:600;">สัญลักษณ์ (ในหน้านี้)</div>
            <div class="tp-num" style="font-size:26px; font-weight:800; margin-top:6px;">{{ number_format($sumActiveSymbols) }}<span class="tp-muted" style="font-size:15px; font-weight:600;"> / {{ number_format($sumSymbols) }}</span></div>
            <div style="margin-top:8px;"><span class="tp-pill tp-pill-soft" style="color:#5689b8;"><i class="fas fa-star"></i> เปิด / ทั้งหมด</span></div>
        </div>
    </div>

    {{-- ===== กราฟ CSS: สัญลักษณ์ต่อหมวด ===== --}}
    @if($chartRows->count() > 0)
        <div class="tp-card">
            <div class="tp-section-h" style="margin-bottom:14px;">
                <i class="fas fa-chart-simple"></i> จำนวนสัญลักษณ์ต่อหมวด (Top {{ $chartRows->count() }})
            </div>
            <div class="tp-bars">
                @foreach($chartRows as $row)
                    @php
                        // ความสูงสัมพัทธ์ของแท่งกราฟ (เทียบกับค่าสูงสุด)
                        $totalPct = (int) round(($row->dream_symbols_count / $chartMax) * 100);
                        $activePct = $row->dream_symbols_count > 0
                            ? (int) round(($row->active_symbols_count / $chartMax) * 100)
                            : 0;
                        $catColor = $row->color_hex ?: 'var(--accent1)';
                    @endphp
                    <div class="col">
                        <div class="stack" style="height:140px;">
                            <div class="bar b" style="height:{{ $totalPct }}%;" title="ทั้งหมด {{ $row->dream_symbols_count }}"></div>
                            <div class="bar a" style="height:{{ $activePct }}%; background:{{ $catColor }};" title="เปิดใช้งาน {{ $row->active_symbols_count }}"></div>
                        </div>
                        <div class="lbl" style="display:flex; flex-direction:column; align-items:center; gap:2px;">
                            <span style="font-size:15px;">{{ $row->icon }}</span>
                            <span class="tp-num" style="font-weight:700;">{{ $row->dream_symbols_count }}</span>
                            <span class="tp-muted" style="font-size:10px; max-width:64px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">{{ $row->name_th }}</span>
                        </div>
                    </div>
                @endforeach
            </div>
            <div class="tp-muted" style="margin-top:10px; font-size:11px; display:flex; gap:14px; flex-wrap:wrap;">
                <span><i class="fas fa-square" style="color:var(--accent1);"></i> เปิดใช้งาน (ตามสีหมวด)</span>
                <span><i class="fas fa-square" style="opacity:.35;"></i> ทั้งหมด</span>
            </div>
        </div>
    @endif

    {{-- ===== ตารางหมวดหมู่ ===== --}}
    <div class="tp-card" style="padding:0;">
        <div style="overflow-x:auto;">
            <table style="width:100%; border-collapse:separate; border-spacing:0 8px; padding:0 14px;">
                <thead>
                    <tr style="text-align:left;">
                        <th style="padding:10px 12px; font-size:11px; font-weight:700; color:var(--ink2); text-transform:uppercase; letter-spacing:.4px;">ลำดับ</th>
                        <th style="padding:10px 12px; font-size:11px; font-weight:700; color:var(--ink2); text-transform:uppercase; letter-spacing:.4px;">หมวดหมู่</th>
                        <th style="padding:10px 12px; font-size:11px; font-weight:700; color:var(--ink2); text-transform:uppercase; letter-spacing:.4px;">สี</th>
                        <th style="padding:10px 12px; font-size:11px; font-weight:700; color:var(--ink2); text-transform:uppercase; letter-spacing:.4px; text-align:center;">สัญลักษณ์</th>
                        <th style="padding:10px 12px; font-size:11px; font-weight:700; color:var(--ink2); text-transform:uppercase; letter-spacing:.4px; text-align:center;">สถานะ</th>
                        <th style="padding:10px 12px; font-size:11px; font-weight:700; color:var(--ink2); text-transform:uppercase; letter-spacing:.4px; text-align:right;">จัดการ</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($categories as $category)
                        <tr style="background:var(--surf); box-shadow:var(--inset-sm); border-radius:12px;">
                            {{-- ลำดับ --}}
                            <td style="padding:14px 12px; border-radius:12px 0 0 12px;">
                                <span class="tp-num" style="font-weight:700; color:var(--ink2);">{{ $category->sort_order }}</span>
                            </td>

                            {{-- หมวดหมู่ (ไอคอน + ชื่อ + slug) --}}
                            <td style="padding:14px 12px;">
                                <div style="display:flex; align-items:center; gap:12px;">
                                    <span style="font-size:22px;">{{ $category->icon }}</span>
                                    <div>
                                        <div style="font-size:14px; font-weight:700; color:var(--ink);">{{ $category->name_th }}</div>
                                        <div class="tp-muted" style="font-size:11px;">{{ $category->slug }}</div>
                                    </div>
                                </div>
                            </td>

                            {{-- สี --}}
                            <td style="padding:14px 12px;">
                                <div style="display:inline-flex; align-items:center; gap:8px;">
                                    <span style="display:inline-block; width:24px; height:24px; border-radius:8px; box-shadow:var(--inset-sm); background-color:{{ $category->color_hex }};"></span>
                                    <span class="tp-num tp-muted" style="font-size:11px;">{{ $category->color_hex }}</span>
                                </div>
                            </td>

                            {{-- สัญลักษณ์ เปิด / ทั้งหมด --}}
                            <td style="padding:14px 12px; text-align:center;">
                                <span class="tp-num" style="font-size:14px; font-weight:700; color:var(--ink);">{{ $category->active_symbols_count }}</span>
                                <span class="tp-num tp-muted" style="font-size:12px;"> / {{ $category->dream_symbols_count }}</span>
                            </td>

                            {{-- สถานะ --}}
                            <td style="padding:14px 12px; text-align:center;">
                                @if($category->is_active)
                                    <span class="tp-pill tp-pill-soft" style="color:#5aa07e;"><i class="fas fa-circle-check"></i> เปิด</span>
                                @else
                                    <span class="tp-pill tp-pill-soft" style="color:#9a8f7c;"><i class="fas fa-circle-pause"></i> ปิด</span>
                                @endif
                            </td>

                            {{-- จัดการ (แก้ไข / ลบ) --}}
                            <td style="padding:14px 12px; text-align:right; border-radius:0 12px 12px 0;">
                                <div style="display:inline-flex; justify-content:flex-end; gap:8px;" x-data="{}">
                                    <a href="{{ route('admin.fortune.horoscope-public.dream.categories.edit', $category) }}"
                                       class="tp-icon-btn" title="แก้ไข">
                                        <i class="fas fa-pen"></i>
                                    </a>
                                    <form action="{{ route('admin.fortune.horoscope-public.dream.categories.destroy', $category) }}"
                                          method="POST" style="display:inline;"
                                          @submit="if (!confirm('ลบหมวดหมู่ «{{ $category->name_th }}»?')) $event.preventDefault();">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="tp-icon-btn" title="ลบ" style="color:#d9534f;">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" style="padding:48px 12px; text-align:center;">
                                <div class="tp-muted" style="display:flex; flex-direction:column; align-items:center; gap:10px;">
                                    <i class="fas fa-inbox" style="font-size:34px; opacity:.5;"></i>
                                    <span>ไม่พบหมวดหมู่</span>
                                    <a href="{{ route('admin.fortune.horoscope-public.dream.categories.create') }}" class="tp-btn tp-btn-sm tp-btn-primary" style="margin-top:6px;">
                                        <i class="fas fa-plus"></i> เพิ่มหมวดหมู่แรก
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- ===== Pagination ===== --}}
    @if($categories->hasPages())
        <div class="tp-card" style="padding:12px 18px;">
            {{ $categories->links() }}
        </div>
    @endif

</div>
@endsection
