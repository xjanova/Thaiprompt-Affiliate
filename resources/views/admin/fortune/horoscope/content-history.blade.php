{{-- resources/views/admin/fortune/horoscope/content-history.blade.php --}}
{{-- ประวัติเนื้อหาที่ AI สร้าง (7 วันเกิด) — ธีม V4 นวลทองคำ --}}

@extends('layouts.admin-v4')

@section('title', 'เนื้อหาดวง - ' . $campaign->name)

@php
    use Illuminate\Support\Str;

    // สีประจำวันเกิด (โทนไทย) + อิโมจิ — index = birth_day (0=อาทิตย์)
    $dayMeta = [
        0 => ['#d9534f', '☀️'],
        1 => ['#e0a52e', '🌙'],
        2 => ['#cf6f9c', '🔴'],
        3 => ['#5aa07e', '🟢'],
        4 => ['#d6824a', '🟠'],
        5 => ['#5689b8', '🔵'],
        6 => ['#b79ae8', '🟣'],
    ];
    // สีสถานะการสร้างเนื้อหา
    $genStatusMeta = [
        'generated'  => ['#5aa07e', '✅ สำเร็จ'],
        'generating' => ['#5689b8', '⏳ กำลังสร้าง'],
        'pending'    => ['#e0a52e', '⏸ รอ'],
        'failed'     => ['#d9534f', '❌ ล้มเหลว'],
    ];
@endphp

@section('content')
<div style="display:flex; flex-direction:column; gap:18px;">

    {{-- หัวข้อ + เลือกวันที่ --}}
    <div style="display:flex; flex-wrap:wrap; align-items:flex-end; justify-content:space-between; gap:14px;">
        <div>
            <div style="display:flex; align-items:center; gap:10px;">
                <a href="{{ route('admin.fortune.horoscope.index') }}" class="tp-btn tp-btn-sm"><i class="fas fa-arrow-left"></i> กลับ</a>
                <div style="font-size:11px; color:var(--ink2); font-weight:600; letter-spacing:.4px;">หลังบ้าน · ระบบดูดวง · เนื้อหาดวง</div>
            </div>
            <h1 class="tp-num" style="font-size:clamp(22px,4vw,28px); font-weight:800; margin:6px 0 0;">🤖 เนื้อหาที่ AI สร้าง</h1>
            <p class="tp-muted" style="margin:6px 0 0; font-size:13px;">แคมเปญ: <span style="color:var(--deep1); font-weight:600;">{{ $campaign->name }}</span></p>
        </div>
        <form method="GET" action="{{ route('admin.fortune.horoscope.content-history', $campaign) }}" style="display:flex; align-items:center; gap:9px;">
            <label style="font-size:12.5px; color:var(--ink2); font-weight:600;">วันที่:</label>
            <div class="tp-well" style="padding:2px 10px;">
                <input type="date" name="date" value="{{ $selectedDate->format('Y-m-d') }}" onchange="this.form.submit()"
                       class="tp-input" style="box-shadow:none; background:transparent; padding:8px 4px; color-scheme:var(--scheme, light);">
            </div>
        </form>
    </div>

    {{-- แถบวันที่ --}}
    <div class="tp-card" style="padding:14px 18px; display:flex; align-items:center; gap:13px;">
        <span class="tp-tile" style="width:42px; height:42px; border-radius:13px; font-size:18px;">📅</span>
        <div>
            <div class="tp-num" style="font-size:16px; font-weight:800;">วันที่ {{ $selectedDate->format('d/m/') }}{{ $selectedDate->year + 543 }}</div>
            <div style="font-size:12px; color:var(--ink2);">พบเนื้อหา {{ $contents->count() }} รายการ จาก 7 วันเกิด</div>
        </div>
    </div>

    {{-- empty: ปุ่มสร้างเนื้อหา --}}
    @if($contents->isEmpty())
        <div class="tp-card" style="padding:36px 20px; text-align:center;">
            <i class="fas fa-wand-magic-sparkles" style="font-size:34px; color:var(--accent1); display:block; margin-bottom:12px;"></i>
            <div class="tp-num" style="font-size:16px; font-weight:800;">ยังไม่มีเนื้อหาสำหรับวันนี้</div>
            <div style="font-size:13px; color:var(--ink2); margin:6px 0 16px;">กดปุ่มด้านล่างเพื่อให้ AI สร้างคำทำนายทันที</div>
            <form action="{{ route('admin.fortune.horoscope.generate-now', $campaign) }}" method="POST" style="display:inline;"
                  onsubmit="return confirm('ต้องการสร้างเนื้อหา AI ทันทีหรือไม่? อาจใช้เวลา 2-5 นาที')">
                @csrf
                <input type="hidden" name="date" value="{{ $selectedDate->format('Y-m-d') }}">
                <button type="submit" class="tp-btn tp-btn-primary" style="display:inline-flex;"><i class="fas fa-bolt"></i> สร้างเนื้อหา AI ทันที</button>
            </form>
        </div>
    @endif

    {{-- การ์ดเนื้อหา 7 วันเกิด --}}
    @if($contents->isNotEmpty())
        <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(260px,1fr)); gap:16px;">
            @foreach($contents as $content)
                @php
                    $dm = $dayMeta[$content->birth_day] ?? ['#9a8f7c', '⭐'];
                    $gs = $genStatusMeta[$content->status] ?? ['#9a8f7c', $content->status];
                @endphp
                <div class="tp-card tp-card-hover" style="padding:0; overflow:hidden;">
                    {{-- หัวการ์ด (สีวันเกิด) --}}
                    <div style="padding:14px 16px; color:#fff; background:linear-gradient(135deg, {{ $dm[0] }}, color-mix(in srgb, {{ $dm[0] }} 65%, #000)); display:flex; align-items:center; justify-content:space-between;">
                        <div style="display:flex; align-items:center; gap:8px;">
                            <span style="font-size:20px;">{{ $dm[1] }}</span>
                            <span class="tp-num" style="font-size:15px; font-weight:800; text-shadow:0 1px 2px rgba(0,0,0,.2);">วัน{{ $content->birth_day_name }}</span>
                        </div>
                        <span style="padding:3px 9px; border-radius:20px; font-size:10.5px; font-weight:700; background:rgba(255,255,255,.22);">{{ $gs[1] }}</span>
                    </div>

                    {{-- รูปภาพ --}}
                    @if($content->image_url)
                        <div style="aspect-ratio:1/1; overflow:hidden; box-shadow:var(--inset-sm);">
                            <img src="{{ $content->image_url }}" alt="ดวงวัน{{ $content->birth_day_name }}" loading="lazy"
                                 style="width:100%; height:100%; object-fit:cover;"
                                 onerror="this.parentElement.innerHTML='<div style=\'display:flex;align-items:center;justify-content:center;height:100%;color:var(--ink2);font-size:34px;\'>🖼</div>'">
                        </div>
                    @endif

                    {{-- คำทำนาย --}}
                    <div style="padding:14px 16px;">
                        @if($content->ai_prediction)
                            <p style="font-size:13px; color:var(--ink); line-height:1.6; margin:0; display:-webkit-box; -webkit-line-clamp:4; -webkit-box-orient:vertical; overflow:hidden;">{{ $content->ai_prediction }}</p>
                        @else
                            <p style="font-size:13px; color:var(--ink2); font-style:italic; margin:0;">ยังไม่มีคำทำนาย</p>
                        @endif

                        @if($content->lucky_color || $content->lucky_number || $content->lucky_direction)
                            <div style="margin-top:12px; padding-top:12px; box-shadow:inset 0 1px 0 color-mix(in srgb, var(--ink2) 18%, transparent); display:flex; flex-wrap:wrap; gap:6px;">
                                @if($content->lucky_color)
                                    <span class="tp-pill tp-pill-soft" style="font-size:11px;">🎨 {{ $content->lucky_color }}</span>
                                @endif
                                @if($content->lucky_number)
                                    <span class="tp-pill tp-pill-soft" style="font-size:11px;">🔢 {{ $content->lucky_number }}</span>
                                @endif
                                @if($content->lucky_direction)
                                    <span class="tp-pill tp-pill-soft" style="font-size:11px;">🧭 {{ $content->lucky_direction }}</span>
                                @endif
                            </div>
                        @endif

                        @if($content->error_message)
                            <div style="margin-top:12px; padding:8px 10px; border-radius:9px; box-shadow:var(--inset-sm); font-size:11.5px; color:#d9534f;">⚠️ {{ Str::limit($content->error_message, 100) }}</div>
                        @endif
                    </div>

                    {{-- โหราศาสตร์ --}}
                    @if($content->chaochana_data)
                        @php $chaochana = $content->chaochana_data; @endphp
                        <div style="padding:11px 16px; box-shadow:var(--inset-sm); font-size:11.5px; color:var(--ink2);">
                            @if(isset($chaochana['main_planet']))
                                ดาว: <span style="font-weight:700; color:var(--ink);">{{ $chaochana['main_planet'] }}</span>
                            @endif
                            @if(isset($chaochana['element']))
                                · ธาตุ: <span style="font-weight:700; color:var(--ink);">{{ $chaochana['element'] }}</span>
                            @endif
                        </div>
                    @endif
                </div>
            @endforeach
        </div>

        {{-- ปุ่มลัด --}}
        <div style="display:flex; flex-wrap:wrap; gap:10px;">
            <form action="{{ route('admin.fortune.horoscope.generate-now', $campaign) }}" method="POST" style="display:inline;"
                  onsubmit="return confirm('สร้างเนื้อหาใหม่จะเขียนทับเนื้อหาที่มีอยู่ ต้องการดำเนินการ?')">
                @csrf
                <input type="hidden" name="date" value="{{ $selectedDate->format('Y-m-d') }}">
                <button type="submit" class="tp-btn tp-btn-primary"><i class="fas fa-arrows-rotate"></i> สร้างเนื้อหาใหม่</button>
            </form>
            <form action="{{ route('admin.fortune.horoscope.publish-now', $campaign) }}" method="POST" style="display:inline;"
                  onsubmit="return confirm('ต้องการโพสเนื้อหาทันทีหรือไม่?')">
                @csrf
                <button type="submit" class="tp-btn"><i class="fas fa-rocket" style="color:#d6824a;"></i> โพสทันที</button>
            </form>
            <a href="{{ route('admin.fortune.horoscope.post-history', $campaign) }}" class="tp-btn"><i class="fas fa-paper-plane" style="color:#5689b8;"></i> ดูประวัติโพส</a>
        </div>
    @endif
</div>
@endsection
