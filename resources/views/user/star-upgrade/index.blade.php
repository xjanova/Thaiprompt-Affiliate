@extends('layouts.user-v4')

@section('title', 'อัพเกรดดาว')

@section('content')
{{--
    ⭐ หน้าอัพเกรดดาว (ธีม V4 นวลทองคำ)

    ของเดิมพัง 3 อย่างพร้อมกัน แก้ในรอบนี้:
      1. ไม่มี @extends/@section เลย → หน้าเรนเดอร์เป็นเศษ HTML ไม่มี head/CSS/เมนู
         (แต่มี @endsection ค้างท้ายไฟล์ = ถูกลบหัวไฟล์ทิ้งไปตอนแก้ครั้งก่อน)
      2. มี markup ค้าง </h1> + <p> ที่ไม่มีตัวเปิด
      3. ไม่มี x-data="starUpgrade()" → ปุ่ม "ซื้อเลย" กับโมดัลไม่ถูก Alpine wire = กดไม่ติด
--}}
@php
    // path ของไอคอนดาว — ใช้ซ้ำหลายที่ เก็บไว้ตัวแปรเดียว
    $starPath = 'M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z';
    $purchasedColor = $starColors[$videoLevel->purchased_star_color ?? 'bronze'] ?? $starColors['bronze'];
@endphp

<div x-data="starUpgrade()" style="display:flex; flex-direction:column; gap:18px;">

    {{-- ===== Hero ===== --}}
    <div class="tp-card" style="padding:0; overflow:hidden;">
        <div style="padding:22px 24px; background:linear-gradient(120deg, color-mix(in srgb, #e0a52e 22%, transparent), transparent 70%);">
            <div style="display:flex; flex-wrap:wrap; align-items:center; justify-content:space-between; gap:16px;">
                <div style="display:flex; align-items:center; gap:14px;">
                    <span class="tp-tile" style="width:52px; height:52px; border-radius:16px; font-size:23px; display:flex; align-items:center; justify-content:center;">
                        <i class="fas fa-star" style="color:#fff;"></i>
                    </span>
                    <div>
                        <h1 style="font-size:clamp(20px,4vw,26px); font-weight:800; margin:0;">อัพเกรดดาว</h1>
                        <div style="font-size:12.5px; color:var(--ink2); margin-top:3px;">ซื้อดาวเพิ่มเพื่อแสดงสถานะพิเศษและรับสิทธิประโยชน์</div>
                    </div>
                </div>

                {{-- ยอด Coins --}}
                <div class="tp-well" style="padding:14px 18px; display:flex; align-items:center; gap:12px; min-width:200px;">
                    <span class="tp-tile" style="width:42px; height:42px; border-radius:50%; font-size:17px; font-weight:800; display:flex; align-items:center; justify-content:center;">C</span>
                    <div>
                        <div style="font-size:11.5px; color:var(--ink2);">Coins ของคุณ</div>
                        <div class="tp-num" style="font-size:22px; font-weight:800; line-height:1.1;">{{ number_format($coinBalance, 2) }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ===== ดาวปัจจุบัน 3 แบบ ===== --}}
    <div class="tp-card" style="padding:20px;">
        <div class="tp-section-h" style="margin-bottom:16px;"><i class="fas fa-star"></i> ดาวปัจจุบันของคุณ</div>

        <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(220px,1fr)); gap:14px;">

            {{-- ดาวจาก Level --}}
            <div class="tp-well" style="padding:18px; text-align:center;">
                <div style="font-size:12px; color:var(--ink2); margin-bottom:8px;">ดาวจาก Level</div>
                <div style="display:flex; justify-content:center; align-items:center; gap:3px; margin-bottom:8px;">
                    @for($i = 0; $i < 5; $i++)
                        <svg viewBox="0 0 20 20" fill="{{ $i < $levelStars ? '#e0a52e' : 'color-mix(in srgb, var(--ink2) 30%, transparent)' }}" style="width:28px; height:28px;">
                            <path d="{{ $starPath }}"/>
                        </svg>
                    @endfor
                </div>
                <div class="tp-num" style="font-size:22px; font-weight:800;">{{ $levelStars }} ดาว</div>
                <div style="font-size:11.5px; color:var(--ink2); margin-top:3px;">Level {{ $videoLevel->currentLevel?->level ?? 1 }}</div>
            </div>

            {{-- ดาวที่ซื้อ --}}
            <div class="tp-well" style="padding:18px; text-align:center; border-top:3px solid {{ $purchasedColor['hex'] }};">
                <div style="font-size:12px; color:var(--ink2); margin-bottom:8px;">ดาวที่ซื้อ</div>
                <div style="display:flex; justify-content:center; align-items:center; gap:3px; margin-bottom:8px;">
                    @for($i = 0; $i < 5; $i++)
                        <svg viewBox="0 0 20 20" fill="{{ $i < $currentPurchasedStars ? $purchasedColor['hex'] : 'color-mix(in srgb, var(--ink2) 30%, transparent)' }}" style="width:28px; height:28px;">
                            <path d="{{ $starPath }}"/>
                        </svg>
                    @endfor
                </div>
                <div class="tp-num" style="font-size:22px; font-weight:800;">{{ $currentPurchasedStars }} ดาว</div>
                <div style="font-size:11.5px; color:var(--ink2); margin-top:3px;">
                    {{ $currentPurchasedStars > 0 ? $purchasedColor['name'] : 'ยังไม่ได้ซื้อ' }}
                </div>
            </div>

            {{-- ดาวที่แสดงจริง --}}
            <div class="tp-well" style="padding:18px; text-align:center; border-top:3px solid #8a63c9;">
                <div style="font-size:12px; color:var(--ink2); margin-bottom:8px;">ดาวที่แสดง</div>
                <div style="display:flex; justify-content:center; align-items:center; gap:3px; margin-bottom:8px;">
                    @for($i = 0; $i < 5; $i++)
                        <svg viewBox="0 0 20 20" fill="{{ $i < $effectiveStars ? '#e0a52e' : 'color-mix(in srgb, var(--ink2) 30%, transparent)' }}" style="width:28px; height:28px;">
                            <path d="{{ $starPath }}"/>
                        </svg>
                    @endfor
                </div>
                <div class="tp-num" style="font-size:22px; font-weight:800;">{{ $effectiveStars }} ดาว</div>
                <div style="font-size:11.5px; color:var(--ink2); margin-top:3px;">สูงสุดของทั้งสอง</div>
            </div>
        </div>
    </div>

    {{-- ===== ตัวเลือกอัพเกรด ===== --}}
    <div>
        <div class="tp-section-h" style="margin-bottom:14px;"><i class="fas fa-wand-magic-sparkles"></i> ตัวเลือกอัพเกรดดาว</div>

        <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(230px,1fr)); gap:14px;">
            @foreach($upgradeOptions as $option)
                @php $optColor = $option['color_info']['hex'] ?? '#CD7F32'; @endphp
                <div class="tp-card" style="padding:0; overflow:hidden; position:relative;{{ $option['can_upgrade'] || $option['is_owned'] ? '' : ' opacity:.6;' }}{{ $option['is_owned'] ? ' box-shadow:0 0 0 2px #5aa07e inset;' : '' }}">

                    {{-- หัวการ์ด --}}
                    <div style="padding:18px 16px; text-align:center; background:linear-gradient(160deg, {{ $optColor }}, color-mix(in srgb, {{ $optColor }} 55%, #000));">
                        <div style="display:flex; justify-content:center; align-items:center; gap:2px; margin-bottom:8px;">
                            @for($i = 0; $i < $option['stars']; $i++)
                                <svg viewBox="0 0 20 20" fill="#fff" style="width:22px; height:22px; filter:drop-shadow(0 1px 2px rgba(0,0,0,.35));">
                                    <path d="{{ $starPath }}"/>
                                </svg>
                            @endfor
                        </div>
                        <div style="font-size:15px; font-weight:800; color:#fff;">{{ $option['name'] }}</div>
                        <div style="font-size:11.5px; color:rgba(255,255,255,.82); margin-top:2px;">{{ $option['color_info']['name'] }}</div>
                    </div>

                    {{-- เนื้อการ์ด --}}
                    <div style="padding:16px;">
                        <div style="text-align:center; margin-bottom:14px;">
                            <div style="font-size:11.5px; color:var(--ink2);">ราคา</div>
                            <div class="tp-num" style="font-size:22px; font-weight:800; color:var(--deep1);">
                                {{ number_format($option['price'], 0) }} <span style="font-size:12px; font-weight:600;">Coins</span>
                            </div>
                        </div>

                        <div style="display:flex; flex-direction:column; gap:7px; margin-bottom:14px; font-size:12.5px; color:var(--ink2);">
                            <div style="display:flex; align-items:center; gap:7px;">
                                <span style="color:#5aa07e;">✓</span> EXP x{{ number_format($option['exp_multiplier'], 2) }}
                            </div>
                            <div style="display:flex; align-items:center; gap:7px;">
                                <span style="color:#5aa07e;">✓</span> Coins x{{ number_format($option['coin_multiplier'], 2) }}
                            </div>
                            <div style="display:flex; align-items:center; gap:7px;">
                                <span style="color:#5689b8;">📊</span> Level ขั้นต่ำ: {{ $option['min_level'] }}
                            </div>
                        </div>

                        {{-- ปุ่ม --}}
                        @if($option['is_owned'])
                            <button type="button" disabled class="tp-btn"
                                    style="width:100%; justify-content:center; padding:11px; background:#5aa07e; border-color:#5aa07e; color:#fff; font-weight:700; cursor:not-allowed;">
                                ✓ เป็นเจ้าของแล้ว
                            </button>
                        @elseif($option['can_upgrade'])
                            <button type="button" class="tp-btn tp-btn-primary"
                                    style="width:100%; justify-content:center; padding:11px; font-weight:700;"
                                    @click="confirmUpgrade({{ $option['stars'] }}, @js($option['name']), {{ $option['price'] }}, @js($optColor))">
                                ซื้อเลย
                            </button>
                        @else
                            <button type="button" disabled class="tp-btn"
                                    style="width:100%; justify-content:center; padding:11px; font-size:12px; opacity:.75; cursor:not-allowed;">
                                {{ $option['reason'] ?? 'ไม่สามารถซื้อได้' }}
                            </button>
                        @endif
                    </div>

                    {{-- ป้าย "ปัจจุบัน" --}}
                    @if($option['is_current'])
                        <span class="tp-pill" style="position:absolute; top:10px; right:10px; background:#5aa07e; color:#fff; font-weight:700;">ปัจจุบัน</span>
                    @endif
                </div>
            @endforeach
        </div>
    </div>

    {{-- ===== ประวัติล่าสุด ===== --}}
    @if($recentUpgrades->count() > 0)
        <div class="tp-card" style="padding:0; overflow:hidden;">
            <div style="display:flex; flex-wrap:wrap; align-items:center; justify-content:space-between; gap:10px; padding:18px 20px 0;">
                <div class="tp-section-h" style="margin:0;"><i class="fas fa-clock-rotate-left"></i> ประวัติอัพเกรดล่าสุด</div>
                <a href="{{ route('user.star-upgrade.history') }}" class="tp-btn tp-btn-sm">
                    <i class="fas fa-list"></i> ดูทั้งหมด
                </a>
            </div>

            <div style="overflow-x:auto; margin-top:14px;">
                <table style="min-width:100%; border-collapse:collapse;">
                    <thead>
                        <tr>
                            <th style="padding:12px 16px; text-align:left; font-size:11px; font-weight:700; color:var(--ink2); text-transform:uppercase; letter-spacing:.4px;">วันที่</th>
                            <th style="padding:12px 16px; text-align:center; font-size:11px; font-weight:700; color:var(--ink2); text-transform:uppercase; letter-spacing:.4px;">จาก</th>
                            <th style="padding:12px 16px; text-align:center; font-size:11px; font-weight:700; color:var(--ink2); text-transform:uppercase; letter-spacing:.4px;">เป็น</th>
                            <th style="padding:12px 16px; text-align:right; font-size:11px; font-weight:700; color:var(--ink2); text-transform:uppercase; letter-spacing:.4px;">ราคา</th>
                            <th style="padding:12px 16px; text-align:center; font-size:11px; font-weight:700; color:var(--ink2); text-transform:uppercase; letter-spacing:.4px;">สถานะ</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($recentUpgrades as $upgrade)
                            <tr style="box-shadow:var(--inset-sm);">
                                <td style="padding:12px 16px; font-size:13px; color:var(--ink); white-space:nowrap;">{{ $upgrade->created_at->format('d/m/Y H:i') }}</td>
                                <td style="padding:12px 16px; text-align:center; white-space:nowrap;">
                                    @for($i = 0; $i < $upgrade->from_stars; $i++)
                                        <svg viewBox="0 0 20 20" fill="#e0a52e" style="width:15px; height:15px; display:inline-block;"><path d="{{ $starPath }}"/></svg>
                                    @endfor
                                    @if($upgrade->from_stars < 1)<span style="color:var(--ink2);">-</span>@endif
                                </td>
                                <td style="padding:12px 16px; text-align:center; white-space:nowrap;">
                                    @for($i = 0; $i < $upgrade->to_stars; $i++)
                                        <svg viewBox="0 0 20 20" fill="#e0a52e" style="width:15px; height:15px; display:inline-block;"><path d="{{ $starPath }}"/></svg>
                                    @endfor
                                </td>
                                <td style="padding:12px 16px; text-align:right; font-size:13px; font-weight:700; color:var(--deep1); white-space:nowrap;">
                                    {{ number_format($upgrade->coins_paid, 0) }} C
                                </td>
                                <td style="padding:12px 16px; text-align:center; white-space:nowrap;">
                                    @if($upgrade->status === 'completed')
                                        <span class="tp-pill" style="background:rgba(90,160,126,.18); color:#3f7a5c;">สำเร็จ</span>
                                    @else
                                        <span class="tp-pill" style="background:rgba(217,83,79,.16); color:#d9534f;">คืนเงิน</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    {{-- ===== โมดัลยืนยันการซื้อ ===== --}}
    <div x-show="showModal" x-cloak
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
         style="position:fixed; inset:0; z-index:50; overflow-y:auto;" aria-modal="true">

        <div style="display:flex; align-items:center; justify-content:center; min-height:100vh; padding:16px;">
            {{-- ฉากหลัง --}}
            <div style="position:fixed; inset:0; background:rgba(0,0,0,.55);" @click="showModal = false"></div>

            <div class="tp-card" style="position:relative; max-width:420px; width:100%; padding:0; overflow:hidden;">
                {{-- หัวโมดัล — ใช้ object form กัน Alpine ลบ style ตัวอื่นทิ้ง --}}
                <div style="padding:22px 18px; text-align:center;"
                     :style="{ background: 'linear-gradient(160deg, ' + selectedColor + ', color-mix(in srgb, ' + selectedColor + ' 55%, #000))' }">
                    <div style="display:flex; justify-content:center; align-items:center; gap:2px; margin-bottom:8px;">
                        <template x-for="i in selectedStars" :key="i">
                            <svg viewBox="0 0 20 20" fill="#fff" style="width:26px; height:26px; filter:drop-shadow(0 1px 2px rgba(0,0,0,.35));">
                                <path d="{{ $starPath }}"/>
                            </svg>
                        </template>
                    </div>
                    <div style="font-size:17px; font-weight:800; color:#fff;" x-text="selectedName"></div>
                </div>

                <div style="padding:20px;">
                    <div style="text-align:center; margin-bottom:18px;">
                        <div style="font-size:12.5px; color:var(--ink2); margin-bottom:4px;">คุณต้องการอัพเกรดเป็น</div>
                        <div class="tp-num" style="font-size:28px; font-weight:800; color:var(--deep1);">
                            <span x-text="selectedPrice.toLocaleString()"></span> Coins
                        </div>
                    </div>

                    <div class="tp-well" style="padding:12px 14px; margin-bottom:18px; border-left:3px solid #e0a52e;">
                        <p style="margin:0; font-size:12px; color:var(--ink2);">
                            <strong style="color:var(--ink);">หมายเหตุ:</strong> การซื้อดาวไม่สามารถคืนเงินได้ กรุณาตรวจสอบให้แน่ใจก่อนยืนยัน
                        </p>
                    </div>

                    <div style="display:flex; gap:10px;">
                        <button type="button" @click="showModal = false" class="tp-btn" style="flex:1; justify-content:center;">
                            ยกเลิก
                        </button>
                        <form method="POST" action="{{ route('user.star-upgrade.upgrade') }}" style="flex:1;">
                            @csrf
                            <input type="hidden" name="target_stars" x-model="selectedStars">
                            <button type="submit" class="tp-btn tp-btn-primary" style="width:100%; justify-content:center; font-weight:700;">
                                ยืนยันซื้อ
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
function starUpgrade() {
    return {
        showModal: false,
        selectedStars: 0,
        selectedName: '',
        selectedPrice: 0,
        selectedColor: '#CD7F32',

        confirmUpgrade(stars, name, price, color) {
            this.selectedStars = stars;
            this.selectedName = name;
            this.selectedPrice = price;
            this.selectedColor = color;
            this.showModal = true;
        }
    };
}
</script>
@endpush
@endsection
