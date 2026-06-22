@extends('layouts.admin-v4')

@section('title', $pageTitle)

@section('content')
{{-- 📖 พจนานุกรมทำนายฝัน — รายการสัญลักษณ์ฝัน · ธีม V4 นวลทองคำ --}}
<div style="display:flex;flex-direction:column;gap:18px;">

    {{-- ===== Header ===== --}}
    <div style="display:flex;flex-wrap:wrap;align-items:flex-start;justify-content:space-between;gap:14px;">
        <div>
            <div class="tp-muted" style="font-size:12px;letter-spacing:.5px;text-transform:uppercase;margin-bottom:6px;">
                หลังบ้าน · ระบบดูดวง · พจนานุกรมทำนายฝัน
            </div>
            <h1 class="tp-num" style="font-size:26px;font-weight:800;color:var(--ink);margin:0;">
                <i class="fas fa-book-open" style="color:var(--accent1);margin-right:8px;"></i>พจนานุกรมทำนายฝัน
            </h1>
            <p class="tp-muted" style="font-size:13px;margin:8px 0 0;max-width:680px;line-height:1.6;">
                จัดการสัญลักษณ์ฝัน + เลขเด็ด — แม่หมอใช้ทำนายฝันให้ลูกค้า · แก้ไขแล้วใช้ได้ทันที
            </p>
        </div>
        <div style="display:flex;flex-wrap:wrap;gap:10px;align-items:center;">
            @if(Route::has('admin.fortune.horoscope-public.dream.categories'))
                <a href="{{ route('admin.fortune.horoscope-public.dream.categories') }}" class="tp-btn">
                    <i class="fas fa-folder-tree"></i> หมวดหมู่
                </a>
            @endif
            @if(Route::has('admin.fortune.horoscope-public.dream.readings'))
                <a href="{{ route('admin.fortune.horoscope-public.dream.readings') }}" class="tp-btn">
                    <i class="fas fa-list-check"></i> ผลทำนาย
                </a>
            @endif
            <a href="{{ route('admin.fortune.horoscope-public.dream.create') }}" class="tp-btn tp-btn-primary">
                <i class="fas fa-plus"></i> เพิ่มสัญลักษณ์
            </a>
        </div>
    </div>

    {{-- ===== KPI / สถิติ ===== --}}
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:14px;">
        {{-- จำนวนสัญลักษณ์ทั้งหมด --}}
        <div class="tp-card tp-raise" style="padding:18px;">
            <div style="display:flex;align-items:center;gap:12px;">
                <div class="tp-tile" style="width:44px;height:44px;display:flex;align-items:center;justify-content:center;font-size:18px;color:var(--accent1);">
                    <i class="fas fa-moon"></i>
                </div>
                <div>
                    <div class="tp-muted" style="font-size:12px;">สัญลักษณ์ฝันทั้งหมด</div>
                    <div class="tp-num" style="font-size:26px;font-weight:800;color:var(--ink);line-height:1.1;">
                        {{ number_format($symbols->total()) }}
                    </div>
                </div>
            </div>
        </div>

        {{-- จำนวนหมวดหมู่ --}}
        <div class="tp-card tp-raise" style="padding:18px;">
            <div style="display:flex;align-items:center;gap:12px;">
                <div class="tp-tile" style="width:44px;height:44px;display:flex;align-items:center;justify-content:center;font-size:18px;color:#5689b8;">
                    <i class="fas fa-folder-tree"></i>
                </div>
                <div>
                    <div class="tp-muted" style="font-size:12px;">หมวดหมู่ฝัน</div>
                    <div class="tp-num" style="font-size:26px;font-weight:800;color:#5689b8;line-height:1.1;">
                        {{ number_format($categories->count()) }}
                    </div>
                </div>
            </div>
        </div>

        {{-- รายชื่อหมวด (breakdown) --}}
        <div class="tp-card tp-raise" style="padding:18px;grid-column:span 2;min-width:0;">
            <div class="tp-muted" style="font-size:12px;margin-bottom:10px;">
                <i class="fas fa-tags" style="margin-right:6px;"></i>หมวดหมู่ที่มี
            </div>
            <div style="display:flex;flex-wrap:wrap;gap:8px;">
                @forelse($categories as $cat)
                    <span class="tp-pill tp-pill-soft" style="font-size:12px;">
                        {{ $cat->icon }} {{ $cat->name_th }}
                    </span>
                @empty
                    <span class="tp-muted" style="font-size:12px;">ยังไม่มีหมวดหมู่</span>
                @endforelse
            </div>
        </div>
    </div>

    {{-- ===== ตัวกรอง / ค้นหา ===== --}}
    <form method="GET" class="tp-card" style="padding:18px;">
        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:12px;align-items:stretch;">
            {{-- ค้นหาสัญลักษณ์ --}}
            <div class="tp-well tp-input" style="padding:0;display:flex;align-items:center;gap:8px;padding-left:14px;grid-column:1 / -1;">
                <i class="fas fa-magnifying-glass tp-muted" style="font-size:13px;"></i>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="ค้นหาสัญลักษณ์..."
                       style="width:100%;background:transparent;border:0;outline:0;padding:11px 14px 11px 0;color:var(--ink);font-size:14px;">
            </div>

            {{-- หมวดหมู่ --}}
            <div class="tp-well tp-input" style="padding:0;">
                <select name="category_id" style="width:100%;background:transparent;border:0;outline:0;padding:11px 14px;color:var(--ink);font-size:14px;cursor:pointer;">
                    <option value="">— ทุกหมวด —</option>
                    @foreach($categories as $cat)
                        <option value="{{ $cat->id }}" @selected(request('category_id') == $cat->id)>
                            {{ $cat->icon }} {{ $cat->name_th }}
                        </option>
                    @endforeach
                </select>
            </div>

            {{-- ลางบอกเหตุ --}}
            <div class="tp-well tp-input" style="padding:0;">
                <select name="omen" style="width:100%;background:transparent;border:0;outline:0;padding:11px 14px;color:var(--ink);font-size:14px;cursor:pointer;">
                    <option value="">— ทุกลาง —</option>
                    <option value="good" @selected(request('omen') === 'good')>✅ ลางดี</option>
                    <option value="bad" @selected(request('omen') === 'bad')>⚠️ ระวัง</option>
                </select>
            </div>

            {{-- สถานะ --}}
            <div class="tp-well tp-input" style="padding:0;">
                <select name="status" style="width:100%;background:transparent;border:0;outline:0;padding:11px 14px;color:var(--ink);font-size:14px;cursor:pointer;">
                    <option value="">— ทุกสถานะ —</option>
                    <option value="active" @selected(request('status') === 'active')>✅ เปิด</option>
                    <option value="inactive" @selected(request('status') === 'inactive')>⏸️ ปิด</option>
                </select>
            </div>

            {{-- ปุ่มค้นหา + ล้าง --}}
            <div style="display:flex;gap:10px;">
                <button type="submit" class="tp-btn tp-btn-primary" style="flex:1;justify-content:center;">
                    <i class="fas fa-magnifying-glass"></i> ค้นหา
                </button>
                <a href="{{ route('admin.fortune.horoscope-public.dream.index') }}" class="tp-btn" style="justify-content:center;">
                    <i class="fas fa-rotate-left"></i> ล้าง
                </a>
            </div>
        </div>
    </form>

    {{-- ===== ตารางสัญลักษณ์ฝัน ===== --}}
    <div class="tp-card" style="padding:0;overflow:hidden;">
        {{-- หัวตาราง --}}
        <div style="padding:14px 18px;box-shadow:var(--inset-sm);">
            <span class="tp-muted" style="font-size:13px;">
                <i class="fas fa-moon" style="margin-right:6px;color:var(--accent1);"></i>
                ทั้งหมด <b class="tp-num" style="color:var(--ink);">{{ number_format($symbols->total()) }}</b> สัญลักษณ์
            </span>
        </div>

        <div style="overflow-x:auto;">
            <table style="min-width:100%;border-collapse:collapse;">
                <thead>
                    <tr style="text-align:left;">
                        <th style="padding:14px 16px;font-size:12px;font-weight:600;color:var(--ink2);text-transform:uppercase;letter-spacing:.4px;">สัญลักษณ์</th>
                        <th style="padding:14px 16px;font-size:12px;font-weight:600;color:var(--ink2);text-transform:uppercase;letter-spacing:.4px;">ความหมาย</th>
                        <th style="padding:14px 16px;font-size:12px;font-weight:600;color:var(--ink2);text-transform:uppercase;letter-spacing:.4px;text-align:center;">หมวด</th>
                        <th style="padding:14px 16px;font-size:12px;font-weight:600;color:var(--ink2);text-transform:uppercase;letter-spacing:.4px;text-align:center;">ลาง</th>
                        <th style="padding:14px 16px;font-size:12px;font-weight:600;color:var(--ink2);text-transform:uppercase;letter-spacing:.4px;text-align:center;">เลขเด็ด</th>
                        <th style="padding:14px 16px;font-size:12px;font-weight:600;color:var(--ink2);text-transform:uppercase;letter-spacing:.4px;text-align:center;">ระดับ</th>
                        <th style="padding:14px 16px;font-size:12px;font-weight:600;color:var(--ink2);text-transform:uppercase;letter-spacing:.4px;text-align:center;">ค้นหา</th>
                        <th style="padding:14px 16px;font-size:12px;font-weight:600;color:var(--ink2);text-transform:uppercase;letter-spacing:.4px;text-align:right;">จัดการ</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($symbols as $symbol)
                        <tr style="box-shadow:var(--inset-sm);">
                            {{-- สัญลักษณ์ --}}
                            <td style="padding:14px 16px;vertical-align:top;">
                                <span style="font-weight:700;color:var(--ink);font-size:14px;">
                                    ฝันเห็น{{ $symbol->keyword_th }}
                                </span>
                            </td>

                            {{-- ความหมาย --}}
                            <td style="padding:14px 16px;vertical-align:top;max-width:360px;">
                                <div class="tp-muted" style="font-size:13px;line-height:1.5;">
                                    {{ Str::limit($symbol->meaning_th, 70) }}
                                </div>
                            </td>

                            {{-- หมวด --}}
                            <td style="padding:14px 16px;vertical-align:top;text-align:center;">
                                @if($symbol->category)
                                    <span class="tp-pill tp-pill-gold" style="font-size:12px;white-space:nowrap;" title="{{ $symbol->category->name_th }}">
                                        {{ $symbol->category->icon }} {{ $symbol->category->name_th }}
                                    </span>
                                @else
                                    <span class="tp-muted" style="font-size:13px;">—</span>
                                @endif
                            </td>

                            {{-- ลางบอกเหตุ --}}
                            <td style="padding:14px 16px;vertical-align:top;text-align:center;">
                                @if($symbol->is_good_omen)
                                    <span class="tp-pill" style="font-size:12px;color:#5aa07e;background:var(--inset);white-space:nowrap;">
                                        <i class="fas fa-circle-check" style="margin-right:4px;"></i> ดี
                                    </span>
                                @else
                                    <span class="tp-pill" style="font-size:12px;color:#e0a52e;background:var(--inset);white-space:nowrap;">
                                        <i class="fas fa-triangle-exclamation" style="margin-right:4px;"></i> ระวัง
                                    </span>
                                @endif
                            </td>

                            {{-- เลขเด็ด --}}
                            <td style="padding:14px 16px;vertical-align:top;text-align:center;">
                                @if(!empty($symbol->lucky_numbers))
                                    <div style="display:flex;flex-wrap:wrap;justify-content:center;gap:4px;">
                                        @foreach(array_slice($symbol->lucky_numbers, 0, 3) as $num)
                                            <span class="tp-num" style="padding:3px 8px;border-radius:6px;background:var(--inset);box-shadow:var(--inset-sm);color:var(--accent1);font-size:12px;font-weight:700;">{{ $num }}</span>
                                        @endforeach
                                    </div>
                                @else
                                    <span class="tp-muted" style="font-size:13px;">—</span>
                                @endif
                            </td>

                            {{-- ระดับความเข้ม (intensity) --}}
                            <td style="padding:14px 16px;vertical-align:top;text-align:center;">
                                <div style="display:inline-flex;gap:3px;align-items:center;">
                                    @for($i = 1; $i <= 5; $i++)
                                        <span style="width:8px;height:8px;border-radius:50%;background:{{ $i <= $symbol->intensity ? 'var(--accent1)' : 'var(--inset)' }};box-shadow:{{ $i <= $symbol->intensity ? 'none' : 'var(--inset-sm)' }};"></span>
                                    @endfor
                                </div>
                            </td>

                            {{-- จำนวนการค้นหา --}}
                            <td style="padding:14px 16px;vertical-align:top;text-align:center;">
                                <span class="tp-num" style="font-size:13px;color:var(--ink2);">
                                    {{ number_format($symbol->search_count) }}
                                </span>
                            </td>

                            {{-- จัดการ --}}
                            <td style="padding:14px 16px;vertical-align:top;text-align:right;white-space:nowrap;">
                                <div style="display:inline-flex;align-items:center;gap:8px;">
                                    <a href="{{ route('admin.fortune.horoscope-public.dream.edit', $symbol) }}" class="tp-icon-btn" title="แก้ไข">
                                        <i class="fas fa-pen"></i>
                                    </a>
                                    <form action="{{ route('admin.fortune.horoscope-public.dream.destroy', $symbol) }}" method="POST" style="margin:0;display:inline;"
                                          onsubmit="return confirm('ลบสัญลักษณ์ «{{ $symbol->keyword_th }}»?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="tp-icon-btn" title="ลบ" style="color:#d9534f;">
                                            <i class="fas fa-trash-can"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" style="padding:48px 16px;text-align:center;">
                                <div style="font-size:40px;color:var(--ink2);opacity:.5;margin-bottom:12px;">
                                    <i class="fas fa-inbox"></i>
                                </div>
                                <div class="tp-muted" style="font-size:14px;">
                                    ไม่พบสัญลักษณ์ฝัน — กด "เพิ่มสัญลักษณ์" เพื่อเริ่ม
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- ===== Pagination ===== --}}
    @if($symbols->hasPages())
        <div>{{ $symbols->withQueryString()->links() }}</div>
    @endif

</div>
@endsection
