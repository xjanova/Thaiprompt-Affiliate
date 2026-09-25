{{--
    แบนเนอร์แคมเปญแอป — รายการ + พรีวิวในกรอบมือถือ (ธีม V4 นวลทองคำ)

    ตัวแปรจาก Admin\AppCampaignBannerController::index():
      $banners           Collection<MobileBanner> ตามแท็บที่เลือก
      $placement         แท็บที่เลือก ('all' หรือ key ของตำแหน่ง)
      $placements        MobileBanner::PLACEMENTS [key => ชื่อไทย]
      $audiences         MobileBanner::AUDIENCES [key => ชื่อไทย]
      $counts            [placement => จำนวน]
      $preview           [placement => array ของ toAppApi() ที่กำลังแสดงจริง]
      $previewPlacement  ตำแหน่งที่พรีวิวตอนเปิดหน้า
      $stats             {total, showing, scheduled, views, clicks, ctr}
      $apiUrl            GET /api/v1/banners
    ปุ่ม: POST admin.app-banners.toggle / admin.app-banners.move {direction: up|down} / DELETE admin.app-banners.destroy (ยืนยันก่อนลบ)
--}}
@extends('layouts.admin-v4')

@section('title', $pageTitle ?? 'แบนเนอร์แคมเปญแอป')

@push('styles')
<style>
    .ab-page {
        --ab-ok: var(--tp-ok, #4f9e7e);
        --ab-bad: var(--tp-bad, #d9534f);
        --ab-warn: var(--tp-warn, #d99a2b);
        --ab-info: var(--tp-info, #5b87c9);
        --ab-on: var(--tp-on-accent, #fff);
    }
    .ab-tab { display:inline-flex; align-items:center; gap:7px; min-height:40px; padding:0 14px; border-radius:13px; font-size:12.5px; font-weight:700; text-decoration:none; color:var(--ink2); background:var(--surf); box-shadow:var(--raise); white-space:nowrap; }
    .ab-tab.on { color:var(--ab-on); background:linear-gradient(135deg, var(--accent1), var(--accent2)); }
    .ab-tab .n { font-family:var(--tp-font-num); font-size:11px; padding:2px 7px; border-radius:10px; background:color-mix(in srgb, var(--ink2) 16%, transparent); }
    .ab-tab.on .n { background:rgba(255,255,255,.25); }
    .ab-thumb { position:relative; flex:0 0 260px; max-width:100%; aspect-ratio:2 / 1; overflow:hidden; background:var(--surf); }
    .ab-thumb img { position:absolute; inset:0; width:100%; height:100%; object-fit:cover; }
    .ab-thumb .scrim { position:absolute; inset:0; background:linear-gradient(90deg, rgba(0,0,0,.62), rgba(0,0,0,.18) 60%, rgba(0,0,0,0)); }
    .ab-thumb .copy { position:absolute; left:12px; right:30%; top:10px; bottom:10px; display:flex; flex-direction:column; justify-content:center; gap:3px; color:var(--ab-on); text-shadow:0 1px 3px rgba(0,0,0,.45); }
    .ab-state { display:inline-flex; align-items:center; gap:5px; font-size:11px; font-weight:700; padding:4px 10px; border-radius:20px; }
    .ab-state.showing { color:var(--ab-ok); background:color-mix(in srgb, var(--ab-ok) 16%, transparent); }
    .ab-state.scheduled { color:var(--ab-info); background:color-mix(in srgb, var(--ab-info) 16%, transparent); }
    .ab-state.expired { color:var(--ab-warn); background:color-mix(in srgb, var(--ab-warn) 18%, transparent); }
    .ab-state.inactive { color:var(--ink2); background:color-mix(in srgb, var(--ink2) 14%, transparent); }
    .ab-metric { display:flex; flex-direction:column; gap:2px; min-width:70px; }
    .ab-metric b { font-family:var(--tp-font-num); font-size:15px; }
    .ab-metric span { font-size:11px; color:var(--ink2); }
    .ab-act { min-width:44px; min-height:44px; }
    .ab-modal-bg { position:fixed; inset:0; z-index:60; background:rgba(0,0,0,.45); display:grid; place-items:center; padding:16px; }
</style>
@endpush

@section('content')
<div class="ab-page" x-data="bannerIndex()" style="display:flex; flex-direction:column; gap:18px;">

    {{-- ===== Header ===== --}}
    <div style="display:flex; flex-wrap:wrap; align-items:flex-end; justify-content:space-between; gap:14px;">
        <div>
            <div style="font-size:11px; color:var(--ink2); font-weight:600; letter-spacing:.4px;">หลังบ้าน · แอปมือถือ · แบนเนอร์แคมเปญ</div>
            <h1 class="tp-num" style="font-size:clamp(22px,4vw,28px); font-weight:800; margin:4px 0 0;">แบนเนอร์แคมเปญแอป 📣</h1>
            <div style="font-size:12.5px; color:var(--ink2); margin-top:4px; max-width:640px;">
                แบนเนอร์บนหน้าแรก ตลาดสด ไรเดอร์ และร้านค้าในแอป — รูปไม่มีตัวหนังสือ แอปวางหัวข้อ ข้อความ และปุ่มทับให้เอง
            </div>
        </div>
        <a href="{{ route('admin.app-banners.create', $placement !== 'all' ? ['placement' => $placement] : []) }}" class="tp-btn tp-btn-primary" style="min-height:44px;">
            <i class="fas fa-plus"></i> สร้างแบนเนอร์
        </a>
    </div>

    @if(session('success'))
        <div class="tp-card" style="padding:14px 18px; border-left:4px solid var(--ab-ok);">
            <i class="fas fa-circle-check" style="color:var(--ab-ok);"></i> {{ session('success') }}
        </div>
    @endif
    @if(session('error'))
        <div class="tp-card" style="padding:14px 18px; border-left:4px solid var(--ab-bad);">
            <i class="fas fa-circle-exclamation" style="color:var(--ab-bad);"></i> {{ session('error') }}
        </div>
    @endif

    {{-- ===== KPI ===== --}}
    <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(180px,1fr)); gap:16px;">
        @foreach([
            ['icon' => 'fa-images', 'value' => number_format($stats['total']), 'label' => 'แบนเนอร์ทั้งหมด'],
            ['icon' => 'fa-eye', 'value' => number_format($stats['showing']), 'label' => 'กำลังแสดงในแอป'],
            ['icon' => 'fa-calendar-days', 'value' => number_format($stats['scheduled']), 'label' => 'ตั้งเวลาไว้ล่วงหน้า'],
            ['icon' => 'fa-hand-pointer', 'value' => number_format($stats['clicks']), 'label' => 'คลิกรวม · CTR '.rtrim(rtrim(number_format($stats['ctr'], 2), '0'), '.').'%'],
        ] as $kpi)
            <div class="tp-card" style="padding:18px;">
                <div style="display:flex; align-items:center; gap:12px;">
                    <div class="tp-tile" style="width:44px; height:44px; font-size:18px;"><i class="fas {{ $kpi['icon'] }}"></i></div>
                    <div>
                        <div class="tp-num" style="font-size:26px; font-weight:800; line-height:1;">{{ $kpi['value'] }}</div>
                        <div style="font-size:12px; color:var(--ink2); margin-top:3px;">{{ $kpi['label'] }}</div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    {{-- ===== แท็บตำแหน่ง ===== --}}
    <div style="display:flex; gap:10px; overflow-x:auto; padding:4px 2px 8px;">
        <a href="{{ route('admin.app-banners.index') }}" class="ab-tab {{ $placement === 'all' ? 'on' : '' }}">
            ทั้งหมด <span class="n">{{ $stats['total'] }}</span>
        </a>
        @foreach($placements as $key => $label)
            <a href="{{ route('admin.app-banners.index', ['placement' => $key]) }}" class="ab-tab {{ $placement === $key ? 'on' : '' }}">
                {{ $label }} <span class="n">{{ $counts[$key] ?? 0 }}</span>
            </a>
        @endforeach
    </div>

    <div style="display:flex; flex-wrap:wrap; gap:18px; align-items:flex-start;">

        {{-- ===== รายการแบนเนอร์ ===== --}}
        <div style="flex:1 1 520px; min-width:0; display:flex; flex-direction:column; gap:14px;">
            @forelse($banners as $banner)
                @php
                    $state = $banner->scheduleState();
                    $ctr = $banner->view_count > 0 ? round($banner->click_count / $banner->view_count * 100, 2) : 0;
                    $siblings = $banners->where('position', $banner->position)->values();
                    $posIndex = $siblings->search(fn ($b) => $b->id === $banner->id);
                @endphp
                <div class="tp-card tp-card-hover" style="padding:0; overflow:hidden; display:flex; flex-wrap:wrap;">
                    {{-- ภาพย่อ + ข้อความทับ --}}
                    <div class="ab-thumb">
                        @if($banner->imageUrl())
                            <img src="{{ $banner->imageUrl() }}" alt="{{ $banner->title }}" loading="lazy">
                        @endif
                        <div class="scrim"></div>
                        <div class="copy">
                            <div style="font-weight:800; font-size:13.5px; line-height:1.25;">{{ $banner->title }}</div>
                            @if($banner->subtitle)
                                <div style="font-size:11px; line-height:1.35;">{{ $banner->subtitle }}</div>
                            @endif
                            @if($banner->cta_label)
                                <span class="tp-pill tp-pill-gold" style="align-self:flex-start; margin-top:3px;">{{ $banner->cta_label }}</span>
                            @endif
                        </div>
                    </div>

                    {{-- รายละเอียด --}}
                    <div style="flex:1 1 260px; min-width:0; padding:14px 16px; display:flex; flex-direction:column; gap:10px;">
                        <div style="display:flex; flex-wrap:wrap; gap:6px; align-items:center;">
                            <span class="ab-state {{ $state }}"><i class="fas fa-circle" style="font-size:7px;"></i> {{ $banner->scheduleStateLabel() }}</span>
                            <span class="tp-pill tp-pill-soft"><i class="fas fa-location-dot"></i> {{ $banner->placementLabel() }}</span>
                            <span class="tp-pill tp-pill-soft"><i class="fas fa-users"></i> {{ $audiences[$banner->audience ?: 'all'] ?? $banner->audience }}</span>
                            <span class="tp-pill" style="color:var(--ink2);">ลำดับ {{ $banner->sort_order }}</span>
                        </div>

                        <div style="font-size:12px; color:var(--ink2); line-height:1.6;">
                            <div>
                                <i class="fas fa-calendar"></i>
                                {{ $banner->start_date ? $banner->start_date->format('d/m/Y H:i') : 'เริ่มทันที' }}
                                →
                                {{ $banner->end_date ? $banner->end_date->format('d/m/Y H:i') : 'ไม่มีกำหนดสิ้นสุด' }}
                            </div>
                            <div style="overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">
                                <i class="fas fa-arrow-pointer"></i>
                                @if($banner->cta_type === 'screen')
                                    เปิดหน้าจอแอป: <span class="tp-num" style="color:var(--ink);">{{ $banner->cta_value }}</span>
                                @elseif($banner->cta_type === 'url')
                                    เปิดลิงก์: <span class="tp-num" style="color:var(--ink);">{{ $banner->cta_value }}</span>
                                @elseif($banner->link)
                                    ลิงก์เดิม: <span class="tp-num" style="color:var(--ink);">{{ $banner->link }}</span>
                                @else
                                    ไม่มีปุ่ม
                                @endif
                            </div>
                        </div>

                        <div style="display:flex; gap:16px; flex-wrap:wrap;">
                            <div class="ab-metric"><b>{{ number_format($banner->view_count) }}</b><span>แสดงผล</span></div>
                            <div class="ab-metric"><b>{{ number_format($banner->click_count) }}</b><span>คลิก</span></div>
                            <div class="ab-metric"><b>{{ rtrim(rtrim(number_format($ctr, 2), '0'), '.') }}%</b><span>CTR</span></div>
                        </div>

                        <div style="display:flex; flex-wrap:wrap; gap:8px; margin-top:auto;">
                            <form method="POST" action="{{ route('admin.app-banners.move', $banner) }}">
                                @csrf
                                <input type="hidden" name="direction" value="up">
                                <button type="submit" class="tp-icon-btn ab-act" title="เลื่อนขึ้น" aria-label="เลื่อนขึ้น" @disabled($posIndex === 0)><i class="fas fa-arrow-up"></i></button>
                            </form>
                            <form method="POST" action="{{ route('admin.app-banners.move', $banner) }}">
                                @csrf
                                <input type="hidden" name="direction" value="down">
                                <button type="submit" class="tp-icon-btn ab-act" title="เลื่อนลง" aria-label="เลื่อนลง" @disabled($posIndex === $siblings->count() - 1)><i class="fas fa-arrow-down"></i></button>
                            </form>
                            <form method="POST" action="{{ route('admin.app-banners.toggle', $banner) }}">
                                @csrf
                                <button type="submit" class="tp-btn tp-btn-sm ab-act" style="min-height:44px;">
                                    <i class="fas {{ $banner->is_active ? 'fa-toggle-on' : 'fa-toggle-off' }}" style="color:{{ $banner->is_active ? 'var(--ab-ok)' : 'var(--ink2)' }};"></i>
                                    {{ $banner->is_active ? 'ปิดแบนเนอร์' : 'เปิดแบนเนอร์' }}
                                </button>
                            </form>
                            <button type="button" class="tp-btn tp-btn-sm ab-act" style="min-height:44px;" @click="showPreview(@js($banner->position))">
                                <i class="fas fa-mobile-screen"></i> ดูในแอป
                            </button>
                            <a href="{{ route('admin.app-banners.edit', $banner) }}" class="tp-btn tp-btn-sm tp-btn-primary ab-act" style="min-height:44px;">
                                <i class="fas fa-pen"></i> แก้ไข
                            </a>
                            <button type="button" class="tp-btn tp-btn-sm ab-act" style="min-height:44px; color:var(--ab-bad);"
                                    @click="askDelete(@js($banner->title), @js(route('admin.app-banners.destroy', $banner)))">
                                <i class="fas fa-trash"></i> ลบ
                            </button>
                        </div>
                    </div>
                </div>
            @empty
                <div class="tp-card" style="text-align:center; padding:40px 20px; color:var(--ink2);">
                    <i class="fas fa-images" style="font-size:34px; opacity:.5; display:block; margin-bottom:10px;"></i>
                    ยังไม่มีแบนเนอร์ในตำแหน่งนี้
                    <div style="margin-top:14px;">
                        <a href="{{ route('admin.app-banners.create', $placement !== 'all' ? ['placement' => $placement] : []) }}" class="tp-btn tp-btn-primary" style="min-height:44px;">
                            <i class="fas fa-plus"></i> สร้างแบนเนอร์แรก
                        </a>
                    </div>
                </div>
            @endforelse
        </div>

        {{-- ===== พรีวิวในแอป ===== --}}
        <div style="flex:0 1 340px; min-width:0; display:flex; flex-direction:column; gap:14px; position:sticky; top:84px;">
            <div class="tp-card" style="padding:18px;">
                <div class="tp-section-h" style="margin-bottom:10px;"><i class="fas fa-mobile-screen" style="color:var(--accent1);"></i> ตัวอย่างในแอป</div>
                <div style="display:flex; flex-wrap:wrap; gap:6px; margin-bottom:14px;">
                    @foreach($placements as $key => $label)
                        <button type="button" class="tp-btn tp-btn-sm" style="min-height:36px;"
                                :class="placementKey === @js($key) ? 'tp-btn-primary' : ''"
                                @click="showPreview(@js($key))">{{ $label }}</button>
                    @endforeach
                </div>
                @include('admin.app-banners._phone')
                <div style="font-size:11.5px; color:var(--ink2); margin-top:12px; text-align:center;">แสดงเฉพาะแบนเนอร์ที่ "กำลังแสดง" ตามลำดับ เหมือนที่แอปได้รับ</div>
            </div>

            <div class="tp-card" style="padding:16px;">
                <div style="font-weight:700; font-size:13px; margin-bottom:6px;"><i class="fas fa-plug" style="color:var(--accent2);"></i> API สำหรับแอป</div>
                <div class="tp-inset-sm tp-num" style="padding:10px 12px; border-radius:12px; font-size:11.5px; word-break:break-all;">
                    GET {{ $apiUrl }}?placement=home
                </div>
                <div style="font-size:11.5px; color:var(--ink2); margin-top:6px; line-height:1.55;">
                    placement: home · taladsod · rider · merchant — ข้อมูลอัปเดตในแอปภายใน 5 นาที (แก้ในหน้านี้ล้าง cache ทันที)
                </div>
            </div>
        </div>
    </div>

    {{-- ===== กล่องยืนยันการลบ ===== --}}
    <div class="ab-modal-bg" x-show="deleteTarget" x-cloak style="display:none;" @keydown.escape.window="deleteTarget = null" @click.self="deleteTarget = null">
        <div class="tp-card" style="width:min(420px,100%); padding:22px;" role="dialog" aria-modal="true" aria-labelledby="ab-del-title">
            <div id="ab-del-title" style="font-weight:800; font-size:16px; margin-bottom:8px;"><i class="fas fa-triangle-exclamation" style="color:var(--ab-bad);"></i> ลบแบนเนอร์นี้?</div>
            <div style="font-size:13px; color:var(--ink2); line-height:1.6;">
                "<span style="color:var(--ink); font-weight:700;" x-text="deleteTarget ? deleteTarget.title : ''"></span>" จะหายจากแอปทันทีและกู้คืนไม่ได้ ถ้าแค่อยากหยุดชั่วคราว ให้กด "ปิดแบนเนอร์" แทน
            </div>
            <form method="POST" :action="deleteTarget ? deleteTarget.action : '#'" style="display:flex; gap:10px; justify-content:flex-end; flex-wrap:wrap; margin-top:18px;">
                @csrf
                @method('DELETE')
                <button type="button" class="tp-btn" style="min-height:44px;" @click="deleteTarget = null">ยกเลิก</button>
                <button type="submit" class="tp-btn" style="min-height:44px; color:var(--ab-on); background:var(--ab-bad);">
                    <i class="fas fa-trash"></i> ลบถาวร
                </button>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function bannerIndex() {
    return {
        preview: @js($preview),
        labels: @js($placements),
        placementKey: @js($previewPlacement),
        current: 0,
        timer: null,
        deleteTarget: null,

        get slides() {
            return this.preview[this.placementKey] || [];
        },

        get placementLabel() {
            return this.labels[this.placementKey] || '';
        },

        init() {
            this.startAuto();
        },

        startAuto() {
            clearInterval(this.timer);
            this.timer = setInterval(() => {
                if (this.slides.length > 1) {
                    this.current = (this.current + 1) % this.slides.length;
                }
            }, 4000);
        },

        go(i) {
            this.current = i;
            this.startAuto();
        },

        showPreview(key) {
            this.placementKey = key;
            this.current = 0;
            this.startAuto();
        },

        askDelete(title, action) {
            this.deleteTarget = { title, action };
        },
    };
}
</script>
@endpush
