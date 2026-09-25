@extends('layouts.seller-v4')

@section('title', 'รายงาน POS')

@php
    $payLabels = ['cash' => ['💵', 'เงินสด'], 'card' => ['💳', 'บัตร'], 'qr' => ['📱', 'QR'], 'bank_transfer' => ['🏦', 'โอนเงิน'], 'e-wallet' => ['👛', 'e-Wallet'], 'credit' => ['🧾', 'เครดิต'], 'multiple' => ['🔀', 'หลายช่องทาง'], 'other' => ['•', 'อื่น ๆ']];
    // สีโดนัทสัดส่วนวิธีชำระ — ใช้ตัวแปรธีม + ค่าสำรอง
    $slicePalette = ['var(--accent1)', 'var(--accent2)', 'var(--tp-ok, #5aa07e)', 'var(--tp-info, #5689b8)', 'var(--tp-violet, #8b6bb8)', 'var(--deep2)', 'var(--ink2)', 'var(--deep1)'];

    $byPayment = collect($analytics['sales_by_payment'] ?? []);
    $byDevice = collect($analytics['sales_by_device'] ?? [])->sortByDesc('total_sales')->values();
    $topProducts = collect($analytics['top_products'] ?? []);
    $hourly = collect($analytics['hourly_sales'] ?? [])->keyBy(fn ($r) => (int) $r->hour);

    $grandTotal = (float) $byPayment->sum('total');
    $grandCount = (int) $byPayment->sum('count');
    $avgTicket = $grandCount > 0 ? $grandTotal / $grandCount : 0;

    // โดนัท conic-gradient
    $acc = 0;
    $slices = [];
    foreach ($byPayment->values() as $i => $p) {
        $pct = $grandTotal > 0 ? ((float) $p->total / $grandTotal) * 100 : 0;
        $slices[] = $slicePalette[$i % count($slicePalette)].' '.round($acc, 2).'% '.round($acc + $pct, 2).'%';
        $acc += $pct;
    }
    $donut = $slices ? 'conic-gradient('.implode(', ', $slices).')' : 'conic-gradient(var(--sd) 0 100%)';

    // กราฟรายชั่วโมง 0–23
    $hourMax = (float) ($hourly->max('total') ?: 0);
    $peakHour = $hourly->sortByDesc('total')->first();

    $th = 'padding:11px 14px; text-align:left; font-size:10.5px; font-weight:700; color:var(--ink2); text-transform:uppercase; letter-spacing:.4px; white-space:nowrap;';
    $td = 'padding:12px 14px; font-size:13px; color:var(--ink);';
    $row = 'border-top:1px solid color-mix(in srgb, var(--ink2) 13%, transparent);';
    $medal = fn ($i) => [0 => '🥇', 1 => '🥈', 2 => '🥉'][$i] ?? ($i + 1);
@endphp

@section('content')
<div style="display:flex; flex-direction:column; gap:18px;">

    <x-seller-kit.header title="รายงานยอดขายหน้าร้าน" icon="📈" crumb="ร้านค้า · POS"
                         :subtitle="'ช่วงวันที่ '.$dateFrom->format('d/m/Y').' – '.$dateTo->format('d/m/Y')" />

    @include('seller.pos.partials.nav')

    <form method="GET" action="{{ route('seller.pos.analytics') }}" class="tp-card" style="display:grid; grid-template-columns:repeat(auto-fit,minmax(170px,1fr)); gap:12px; align-items:end;">
        <div>
            <label for="a-from" style="font-size:12px; font-weight:700; color:var(--ink2);">ตั้งแต่วันที่</label>
            <input id="a-from" type="date" name="date_from" value="{{ $dateFrom->format('Y-m-d') }}" class="tp-input" style="margin-top:6px;">
        </div>
        <div>
            <label for="a-to" style="font-size:12px; font-weight:700; color:var(--ink2);">ถึงวันที่</label>
            <input id="a-to" type="date" name="date_to" value="{{ $dateTo->format('Y-m-d') }}" class="tp-input" style="margin-top:6px;">
        </div>
        <div style="display:flex; gap:8px;">
            <button type="submit" class="tp-btn tp-btn-primary" style="flex:1;">📊 ดูรายงาน</button>
            <a href="{{ route('seller.pos.analytics') }}" class="tp-btn">30 วันล่าสุด</a>
        </div>
    </form>

    <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(190px,1fr)); gap:14px;">
        <x-seller-kit.stat label="ยอดขายรวม" :value="'฿'.number_format($grandTotal, 2)" icon="💰" tone="gold" />
        <x-seller-kit.stat label="จำนวนบิล" :value="number_format($grandCount)" icon="🧾" tone="info" />
        <x-seller-kit.stat label="เฉลี่ยต่อบิล" :value="'฿'.number_format($avgTicket, 2)" icon="🧮" tone="violet" />
        <x-seller-kit.stat label="ชั่วโมงขายดี" :value="$peakHour ? str_pad((string) $peakHour->hour, 2, '0', STR_PAD_LEFT).':00 น.' : '—'" icon="⏰" tone="ok"
                           :hint="$peakHour ? '฿'.number_format((float) $peakHour->total, 2) : 'ยังไม่มีข้อมูล'" />
    </div>

    @if($grandCount === 0)
        <div class="tp-card">
            <x-seller-kit.empty icon="📊" title="ยังไม่มียอดขายในช่วงนี้" text="ลองเลือกช่วงวันที่อื่น หรือเริ่มขายที่หน้าขายหน้าร้าน">
                <a href="{{ route('seller.pos.terminal') }}" class="tp-btn tp-btn-primary tp-btn-sm">🛒 เปิดหน้าขาย</a>
            </x-seller-kit.empty>
        </div>
    @else
        <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(300px,1fr)); gap:16px;">
            {{-- สัดส่วนวิธีชำระ (โดนัท CSS) --}}
            <div class="tp-card">
                <div class="tp-section-h">💳 สัดส่วนวิธีชำระเงิน</div>
                <div style="display:flex; flex-wrap:wrap; align-items:center; gap:20px; margin-top:14px;">
                    <div style="position:relative; width:150px; height:150px; flex:none;">
                        <div style="width:100%; height:100%; border-radius:50%; background:{{ $donut }}; box-shadow:var(--raise);"></div>
                        <div class="tp-inset" style="position:absolute; inset:28px; border-radius:50%; background:var(--surf); display:grid; place-items:center; text-align:center;">
                            <div>
                                <div class="tp-num" style="font-weight:800; font-size:18px;">{{ number_format($grandCount) }}</div>
                                <div style="font-size:10.5px; color:var(--ink2);">บิล</div>
                            </div>
                        </div>
                    </div>
                    <div style="flex:1; min-width:150px; display:flex; flex-direction:column; gap:8px;">
                        @foreach($byPayment->values() as $i => $p)
                            @php
                                [$pIcon, $pLabel] = $payLabels[$p->payment_method] ?? ['•', $p->payment_method];
                                $pPct = $grandTotal > 0 ? ((float) $p->total / $grandTotal) * 100 : 0;
                            @endphp
                            <div style="display:flex; align-items:center; gap:8px; font-size:12.5px;">
                                <span style="width:10px; height:10px; border-radius:3px; flex:none; background:{{ $slicePalette[$i % count($slicePalette)] }};"></span>
                                <span style="flex:1;">{{ $pIcon }} {{ $pLabel }} <span style="color:var(--ink2);">({{ number_format($p->count) }})</span></span>
                                <span class="tp-num" style="font-weight:800;">{{ number_format($pPct, 1) }}%</span>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

            {{-- ยอดขายรายชั่วโมง --}}
            <div class="tp-card">
                <div class="tp-section-h">🕐 ยอดขายรายชั่วโมง</div>
                <div style="overflow-x:auto; margin-top:14px;">
                    <div class="tp-bars" style="height:170px; gap:4px; min-width:520px;">
                        @for($h = 0; $h < 24; $h++)
                            @php
                                $hRow = $hourly->get($h);
                                $hTotal = $hRow ? (float) $hRow->total : 0;
                                $hPct = $hourMax > 0 ? max(2, ($hTotal / $hourMax) * 100) : 2;
                            @endphp
                            <div class="col" title="{{ str_pad((string) $h, 2, '0', STR_PAD_LEFT) }}:00 · ฿{{ number_format($hTotal, 2) }} · {{ $hRow ? $hRow->count : 0 }} บิล">
                                <div class="stack"><div class="bar a" style="height:{{ $hPct }}%; width:70%; {{ $hTotal > 0 ? '' : 'opacity:.25;' }} animation-delay:{{ $h * 20 }}ms;"></div></div>
                                <div class="lbl tp-num">{{ $h % 3 === 0 ? str_pad((string) $h, 2, '0', STR_PAD_LEFT) : '' }}</div>
                            </div>
                        @endfor
                    </div>
                </div>
                <div style="font-size:11px; color:var(--ink2); margin-top:8px;">ความสูงแท่ง = ยอดขาย (บาท) · แตะค้างที่แท่งเพื่อดูตัวเลข</div>
            </div>
        </div>

        {{-- อุปกรณ์ --}}
        @if($byDevice->count() > 0)
            <div class="tp-card" style="padding:0; overflow:hidden;">
                <div class="tp-section-h" style="padding:16px 18px;">🏆 ยอดขายแยกตามอุปกรณ์</div>
                <div style="overflow-x:auto;">
                    <table style="width:100%; border-collapse:collapse; min-width:560px;">
                        <thead>
                            <tr style="background:color-mix(in srgb, var(--ink2) 8%, transparent);">
                                <th style="{{ $th }}">#</th>
                                <th style="{{ $th }}">อุปกรณ์</th>
                                <th style="{{ $th }} text-align:right;">บิล</th>
                                <th style="{{ $th }} text-align:right;">ยอดขาย</th>
                                <th style="{{ $th }} text-align:right;">เฉลี่ย/บิล</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($byDevice as $i => $dev)
                                <tr style="{{ $row }}">
                                    <td style="{{ $td }} font-size:16px;">{{ $medal($i) }}</td>
                                    <td style="{{ $td }}">
                                        <div style="font-weight:700;">{{ $dev->device_name }}</div>
                                        <div class="tp-num" style="font-size:11px; color:var(--ink2);">{{ $dev->device_code }}</div>
                                    </td>
                                    <td style="{{ $td }} text-align:right;" class="tp-num">{{ number_format((int) $dev->transaction_count) }}</td>
                                    <td style="{{ $td }} text-align:right; font-weight:800; color:var(--deep1);" class="tp-num">฿{{ number_format((float) $dev->total_sales, 2) }}</td>
                                    <td style="{{ $td }} text-align:right;" class="tp-num">฿{{ number_format($dev->transaction_count > 0 ? $dev->total_sales / $dev->transaction_count : 0, 2) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif

        {{-- สินค้าขายดี --}}
        @if($topProducts->count() > 0)
            @php
                $topMax = (float) ($topProducts->max('total_sales') ?: 1);
            @endphp
            <div class="tp-card">
                <div class="tp-section-h">🌟 สินค้าขายดี (สูงสุด 20 รายการ)</div>
                <div style="display:flex; flex-direction:column; gap:10px; margin-top:14px;">
                    @foreach($topProducts as $i => $p)
                        <div style="display:flex; align-items:center; gap:12px;">
                            <span style="width:28px; text-align:center; font-size:{{ $i < 3 ? '18px' : '12px' }}; color:var(--ink2); font-weight:700;">{{ $medal($i) }}</span>
                            <div style="flex:1; min-width:0;">
                                <div style="display:flex; justify-content:space-between; gap:8px; font-size:12.5px;">
                                    <span style="font-weight:700; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">{{ $p->product_name }}</span>
                                    <span class="tp-num" style="font-weight:800; white-space:nowrap;">฿{{ number_format((float) $p->total_sales, 2) }}</span>
                                </div>
                                <div class="tp-inset-sm" style="height:8px; border-radius:99px; margin-top:5px; overflow:hidden;">
                                    <div style="height:100%; width:{{ max(3, ((float) $p->total_sales / $topMax) * 100) }}%; border-radius:99px; background:linear-gradient(90deg, var(--accent1), var(--accent2));"></div>
                                </div>
                                <div style="font-size:11px; color:var(--ink2); margin-top:3px;">ขายได้ {{ number_format((float) $p->total_quantity) }} ชิ้น</div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
    @endif
</div>
@endsection
