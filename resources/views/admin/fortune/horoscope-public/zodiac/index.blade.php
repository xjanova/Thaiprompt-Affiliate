{{-- จัดการ 12 ราศี + Generate ดวงรายวัน AI — ธีม V4 "นวลทองคำ" --}}
@extends('layouts.admin-v4')

@section('title', $pageTitle)

@section('content')
<div x-data="zodiacAdmin()" style="display:flex; flex-direction:column; gap:18px;">

    {{-- ==================== Header ==================== --}}
    <div style="display:flex; justify-content:space-between; align-items:flex-start; gap:16px; flex-wrap:wrap;">
        <div>
            <div class="tp-muted" style="font-size:.72rem; letter-spacing:.08em; text-transform:uppercase; margin-bottom:4px;">
                หลังบ้าน · ระบบดูดวง · 12 ราศี
            </div>
            <h1 class="tp-num" style="font-size:1.55rem; font-weight:800; display:flex; align-items:center; gap:10px;">
                <i class="fas fa-star" style="color:var(--accent1);"></i>
                จัดการ 12 ราศี
            </h1>
            <div class="tp-muted" style="font-size:.85rem; margin-top:4px;">
                แก้ไขข้อมูลราศี และสั่ง generate ดวงรายวันด้วย AI
            </div>
        </div>

        <div style="display:flex; flex-wrap:wrap; gap:10px;">
            @if(Route::has('admin.fortune.horoscope-public.settings'))
                <a href="{{ route('admin.fortune.horoscope-public.settings') }}" class="tp-btn">
                    <i class="fas fa-gear"></i> ตั้งค่า
                </a>
            @endif
            @if(Route::has('admin.fortune.horoscope-public.zodiac.predictions'))
                <a href="{{ route('admin.fortune.horoscope-public.zodiac.predictions') }}" class="tp-btn tp-btn-primary">
                    <i class="fas fa-chart-line"></i> ดวงทั้งหมด
                </a>
            @endif
        </div>
    </div>

    {{-- ==================== KPI ภาพรวม ==================== --}}
    @php
        // คำนวณตัวเลขสรุปจากข้อมูลที่ controller ส่งมา (ไม่ query เพิ่ม)
        $totalZodiacs = $zodiacs->count();
        $activeZodiacs = $zodiacs->where('is_active', true)->count();
        $todayCount = $todayPredictions->count();
        $totalPredictions = $zodiacs->sum('daily_predictions_count');
    @endphp
    <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(200px,1fr)); gap:14px;">
        <div class="tp-card">
            <div class="tp-muted" style="font-size:.78rem; display:flex; align-items:center; gap:6px;">
                <i class="fas fa-star" style="color:var(--accent1);"></i> ราศีทั้งหมด
            </div>
            <div class="tp-num" style="font-size:1.7rem; font-weight:800; margin-top:6px;">{{ number_format($totalZodiacs) }}</div>
            <div class="tp-muted" style="font-size:.72rem; margin-top:2px;">ราศี</div>
        </div>
        <div class="tp-card">
            <div class="tp-muted" style="font-size:.78rem; display:flex; align-items:center; gap:6px;">
                <i class="fas fa-circle-check" style="color:#5aa07e;"></i> เปิดใช้งาน
            </div>
            <div class="tp-num" style="font-size:1.7rem; font-weight:800; margin-top:6px; color:#5aa07e;">{{ number_format($activeZodiacs) }}</div>
            <div class="tp-muted" style="font-size:.72rem; margin-top:2px;">จาก {{ number_format($totalZodiacs) }} ราศี</div>
        </div>
        <div class="tp-card">
            <div class="tp-muted" style="font-size:.78rem; display:flex; align-items:center; gap:6px;">
                <i class="fas fa-calendar-day" style="color:#5689b8;"></i> ดวงวันนี้
            </div>
            <div class="tp-num" style="font-size:1.7rem; font-weight:800; margin-top:6px; color:#5689b8;">{{ number_format($todayCount) }}</div>
            <div class="tp-muted" style="font-size:.72rem; margin-top:2px;">ราศีที่มีดวงแล้ว</div>
        </div>
        <div class="tp-card">
            <div class="tp-muted" style="font-size:.78rem; display:flex; align-items:center; gap:6px;">
                <i class="fas fa-layer-group" style="color:#b79ae8;"></i> ดวงสะสม
            </div>
            <div class="tp-num" style="font-size:1.7rem; font-weight:800; margin-top:6px;">{{ number_format($totalPredictions) }}</div>
            <div class="tp-muted" style="font-size:.72rem; margin-top:2px;">รายการทั้งหมด</div>
        </div>
    </div>

    {{-- ==================== Generate ดวงรายวัน AI ==================== --}}
    <div class="tp-card tp-raise">
        <div class="tp-section-h" style="display:flex; align-items:center; gap:8px; margin-bottom:4px;">
            <i class="fas fa-robot" style="color:var(--accent1);"></i>
            <span>สร้างดวงรายวันด้วย AI</span>
        </div>
        <div class="tp-muted" style="font-size:.82rem; margin-bottom:14px;">
            ระบบจะใช้ AI สร้างคำทำนายรายวันตามวันที่และประเภทที่เลือก
        </div>

        <form action="{{ route('admin.fortune.horoscope-public.zodiac.generate-daily') }}" method="POST"
              style="display:flex; flex-wrap:wrap; gap:12px; align-items:flex-end;">
            @csrf

            {{-- วันที่ --}}
            <div style="flex:1; min-width:180px;">
                <label class="tp-muted" style="display:block; font-size:.8rem; font-weight:600; margin-bottom:6px;">
                    <i class="fas fa-calendar"></i> วันที่
                </label>
                <div class="tp-well">
                    <input type="date" name="target_date" value="{{ today()->format('Y-m-d') }}"
                           class="tp-input" style="width:100%;">
                </div>
            </div>

            {{-- ประเภท --}}
            <div style="flex:1; min-width:200px;">
                <label class="tp-muted" style="display:block; font-size:.8rem; font-weight:600; margin-bottom:6px;">
                    <i class="fas fa-list"></i> ประเภท
                </label>
                <div class="tp-well" style="padding:0;">
                    <select name="type" class="tp-input"
                            style="width:100%; padding:10px 12px; background:transparent; border:none;">
                        <option value="both">🔮 ทั้งหมด (12 ราศี + 7 วันเกิด)</option>
                        <option value="zodiac">⭐ 12 ราศี เท่านั้น</option>
                        <option value="birth_day">📅 7 วันเกิด เท่านั้น</option>
                    </select>
                </div>
            </div>

            {{-- ปุ่มสั่งสร้าง --}}
            <div>
                <button type="submit" class="tp-btn tp-btn-primary"
                        style="white-space:nowrap;"
                        onclick="return confirm('ต้องการสร้างดวงรายวัน AI? (อาจใช้เวลาสักครู่)')">
                    <i class="fas fa-rocket"></i> สร้างดวง
                </button>
            </div>
        </form>
    </div>

    {{-- ==================== การ์ดราศี (Grid) ==================== --}}
    <div class="tp-section-h" style="display:flex; align-items:center; gap:8px;">
        <i class="fas fa-th-large" style="color:var(--accent1);"></i>
        <span>ราศีทั้งหมด</span>
    </div>

    @if($zodiacs->isEmpty())
        {{-- empty state --}}
        <div class="tp-card" style="text-align:center; padding:48px 18px;">
            <i class="fas fa-inbox" style="font-size:2.4rem; color:var(--ink2); opacity:.5;"></i>
            <div class="tp-muted" style="margin-top:12px;">ยังไม่มีข้อมูลราศี</div>
        </div>
    @else
        <div style="display:grid; grid-template-columns:repeat(auto-fill, minmax(300px,1fr)); gap:14px;">
            @foreach($zodiacs as $zodiac)
                @php
                    // ดวงวันนี้ของราศีนี้ (key = zodiac_sign_id)
                    $todayPrediction = $todayPredictions->get($zodiac->id);
                    $score = $todayPrediction ? (int) ($todayPrediction->overall_score ?? 0) : 0;

                    // สีธาตุ — map ตาม STATUS palette V4
                    $elementColor = match($zodiac->element) {
                        'ไฟ' => '#d6824a',
                        'ดิน' => '#e0a52e',
                        'ลม' => '#5689b8',
                        'น้ำ' => '#5689b8',
                        default => '#9a8f7c',
                    };
                @endphp

                <div class="tp-card tp-card-hover" style="display:flex; flex-direction:column; gap:12px;">

                    {{-- หัวการ์ด: สัญลักษณ์ + ชื่อ + สถานะ --}}
                    <div style="display:flex; align-items:center; gap:12px;">
                        <div style="width:48px; height:48px; border-radius:14px; display:flex; align-items:center; justify-content:center; font-size:1.5rem; flex-shrink:0; box-shadow:var(--inset-sm); background:{{ $zodiac->color_hex }}1a; border:1px solid {{ $zodiac->color_hex }}40;">
                            {{ $zodiac->symbol_emoji }}
                        </div>
                        <div style="flex:1; min-width:0;">
                            <div class="tp-num" style="font-size:1rem; font-weight:700; line-height:1.2;">{{ $zodiac->name_th }}</div>
                            <div class="tp-muted" style="font-size:.74rem;">{{ $zodiac->name_en }}</div>
                        </div>
                        @if($zodiac->is_active)
                            <span class="tp-pill" style="color:#5aa07e; border-color:#5aa07e55;">
                                <i class="fas fa-circle-check"></i> เปิด
                            </span>
                        @else
                            <span class="tp-pill tp-pill-soft">
                                <i class="fas fa-circle-pause"></i> ปิด
                            </span>
                        @endif
                    </div>

                    {{-- ข้อมูลย่อย: ช่วงวันเกิด + ธาตุ --}}
                    <div class="tp-inset-sm" style="border-radius:12px; padding:10px 12px; display:flex; flex-direction:column; gap:8px;">
                        <div style="display:flex; align-items:center; gap:8px; font-size:.8rem;">
                            <i class="fas fa-calendar-days tp-muted" style="width:16px; text-align:center;"></i>
                            <span class="tp-muted">{{ $zodiac->date_range_display_th ?? "{$zodiac->date_range_start} — {$zodiac->date_range_end}" }}</span>
                        </div>
                        <div style="display:flex; align-items:center; gap:8px; font-size:.8rem;">
                            <i class="fas fa-fire-flame-curved tp-muted" style="width:16px; text-align:center;"></i>
                            <span class="tp-pill" style="color:{{ $elementColor }}; border-color:{{ $elementColor }}55;">{{ $zodiac->element }}</span>
                        </div>
                    </div>

                    {{-- ดวงวันนี้ (คะแนน 1-5 เป็นจุด CSS) + จำนวน predictions --}}
                    <div style="display:flex; align-items:center; justify-content:space-between; gap:10px;">
                        <div style="display:flex; align-items:center; gap:8px;">
                            <span class="tp-muted" style="font-size:.74rem;">ดวงวันนี้</span>
                            @if($todayPrediction)
                                <span style="display:inline-flex; align-items:center; gap:3px;">
                                    @for($i = 1; $i <= 5; $i++)
                                        <span style="width:8px; height:8px; border-radius:50%; background:{{ $i <= $score ? 'var(--accent1)' : 'var(--ink2)' }}; opacity:{{ $i <= $score ? '1' : '.3' }};"></span>
                                    @endfor
                                    <span class="tp-muted" style="font-size:.72rem; margin-left:4px;">{{ $todayPrediction->overall_score ?? '-' }}/5</span>
                                </span>
                            @else
                                <span class="tp-muted" style="font-size:.74rem; opacity:.7;">— ยังไม่มี —</span>
                            @endif
                        </div>
                        <span class="tp-pill tp-pill-soft" title="จำนวน predictions ทั้งหมด">
                            <i class="fas fa-layer-group"></i> {{ number_format($zodiac->daily_predictions_count) }}
                        </span>
                    </div>

                    <div class="tp-divider"></div>

                    {{-- ปุ่มจัดการ --}}
                    <div style="display:flex; gap:8px;">
                        <a href="{{ route('admin.fortune.horoscope-public.zodiac.edit', $zodiac) }}"
                           class="tp-btn tp-btn-sm" style="flex:1; justify-content:center;">
                            <i class="fas fa-pen-to-square"></i> แก้ไข
                        </a>
                    </div>
                </div>
            @endforeach
        </div>
    @endif

</div>
@endsection

@push('scripts')
<script>
    // Alpine: state ของหน้าจัดการราศี (คงโครงเดิม — ไม่มี logic AJAX ในหน้านี้)
    function zodiacAdmin() {
        return {};
    }
</script>
@endpush
