@extends('layouts.admin-v4')

@section('title', 'รับชำระด้วยเหรียญ TPIX')

@section('content')
{{-- ════════════════════════════════════════════════════════════
     หน้า: รับชำระด้วยเหรียญ TPIX (ธีม V4)
     ข้อมูลจาก Admin\TpixPayAdminController@index:
       $settings, $paymentsEnabled, $spot|null, $twap|null, $lastSnapshot, $payments (paginate), $stats, $status, $search, $statuses
     ════════════════════════════════════════════════════════════ --}}
@php
    $tpxFmt = fn ($v, $d = 4) => \App\Services\TpixPay\TpixAmount::display((string) $v, $d);
    $tpxBadge = [
        'pending' => ['#b7791f', 'รอโอน'],
        'confirming' => ['#2b6cb0', 'รอยืนยันบนเชน'],
        'paid' => ['#2f855a', 'ชำระแล้ว'],
        'credited_wallet' => ['#2c7a7b', 'เติมเข้ากระเป๋า'],
        'late' => ['#c05621', 'โอนหลังหมดเวลา'],
        'expired' => ['#718096', 'หมดเวลา'],
        'cancelled' => ['#718096', 'ยกเลิก'],
        'failed' => ['#c53030', 'ไม่ผ่าน'],
    ];
@endphp
<div style="max-width:1180px;margin:0 auto;display:flex;flex-direction:column;gap:18px;">

    <div style="display:flex;align-items:center;gap:14px;flex-wrap:wrap;">
        <div style="flex:1;min-width:240px;">
            <div class="tp-muted" style="font-size:.72rem;letter-spacing:.08em;text-transform:uppercase;margin-bottom:4px;">หลังบ้าน · การเงิน · TPIX Chain {{ config('tpix_pay.chain_id') }}</div>
            <h1 class="tp-num" style="font-size:1.6rem;font-weight:800;color:var(--ink);margin:0;">🪙 รับชำระด้วยเหรียญ TPIX</h1>
        </div>
        <span class="tp-pill" style="background:{{ $paymentsEnabled ? '#2f855a' : '#718096' }};color:#fff;">{{ $paymentsEnabled ? 'เปิดรับชำระอยู่' : 'ปิดรับชำระ' }}</span>
    </div>

    @if(session('success'))
        <div class="tp-card" style="border-left:4px solid #2f855a;">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="tp-card" style="border-left:4px solid #c53030;">{{ session('error') }}</div>
    @endif

    {{-- ── ราคาจริงตอนนี้ ── --}}
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(210px,1fr));gap:14px;">
        <div class="tp-card">
            <div class="tp-muted" style="font-size:.8rem;">ราคา TPIX ตอนนี้ (พูล DEX)</div>
            @if($spot)
                <div class="tp-num" style="font-size:1.5rem;font-weight:800;color:var(--ink);">{{ \App\Services\TpixPay\TpixPriceService::formatRate($spot['price_thb']) }}</div>
                <div class="tp-muted" style="font-size:.78rem;">${{ $tpxFmt($spot['price_usd'], 6) }} × ฿{{ $tpxFmt($spot['usd_thb'], 4) }}/USD · {{ date('H:i:s', $spot['at']) }}</div>
            @else
                <div style="font-weight:800;color:#c53030;">ดึงราคาไม่ได้</div>
                <div class="tp-muted" style="font-size:.78rem;">RPC ล่ม / พูลบางเกิน / ไม่มีอัตราแลกเปลี่ยน — ระบบซ่อนราคาและไม่ออกยอดใหม่</div>
            @endif
        </div>
        <div class="tp-card">
            <div class="tp-muted" style="font-size:.8rem;">ค่าเฉลี่ย {{ config('tpix_pay.twap_minutes') }} นาที (ใช้คิดเงินถ้าต่ำกว่า)</div>
            <div class="tp-num" style="font-size:1.5rem;font-weight:800;color:var(--ink);">{{ $twap ? \App\Services\TpixPay\TpixPriceService::formatRate($twap) : '—' }}</div>
            <div class="tp-muted" style="font-size:.78rem;">กันการปั่นพูลบางให้ได้ยอดเหรียญน้อย</div>
        </div>
        <div class="tp-card">
            <div class="tp-muted" style="font-size:.8rem;">สภาพคล่องในพูล</div>
            @if($spot)
                <div class="tp-num" style="font-size:1.2rem;font-weight:800;color:var(--ink);">{{ $tpxFmt($spot['reserve_tpix'], 2) }} TPIX</div>
                <div class="tp-num tp-muted" style="font-size:.85rem;">{{ $tpxFmt($spot['reserve_usdt'], 2) }} USDT</div>
            @else
                <div>—</div>
            @endif
        </div>
        <div class="tp-card">
            <div class="tp-muted" style="font-size:.8rem;">รับแล้ว 30 วัน</div>
            <div class="tp-num" style="font-size:1.2rem;font-weight:800;color:var(--ink);">฿{{ number_format($stats['paid_thb'], 2) }}</div>
            <div class="tp-muted tp-num" style="font-size:.8rem;">{{ $stats['paid_count'] }} รายการ · {{ $tpxFmt($stats['paid_tpix'] ?: '0', 2) }} TPIX · เปิดอยู่ {{ $stats['open_count'] }}</div>
        </div>
    </div>

    @if($stats['review_count'] > 0)
        <div class="tp-card" style="border-left:4px solid #c05621;">
            <strong>มี {{ $stats['review_count'] }} รายการรอแอดมินอนุมัติ</strong> — เหรียญเข้ามาแล้วแต่ติดเพดานต่อวัน (โอนช้า/โอนซ้ำ) ตรวจแล้วกด "อนุมัติเติมเข้ากระเป๋า" ในตารางด้านล่าง
        </div>
    @endif

    @if($spot && (float) $spot['reserve_usdt'] < 1000)
        <div class="tp-card" style="border-left:4px solid #c05621;font-size:.88rem;line-height:1.6;">
            <strong>⚠️ ความเสี่ยงสภาพคล่อง:</strong> พูล TPIX DEX มีเงินฝั่ง USDT เพียง {{ $tpxFmt($spot['reserve_usdt'], 2) }} USDT —
            เหรียญที่รับชำระมาอาจขายคืนเป็นเงินบาทตามราคานี้ได้ไม่หมด จึงตั้งเพดานต่อรายการ/ต่อวันไว้ ปรับได้ด้านล่าง
        </div>
    @endif

    {{-- ── ตั้งค่า ── --}}
    <form method="POST" action="{{ route('admin.tpix-pay.update') }}" class="tp-card" style="display:flex;flex-direction:column;gap:16px;">
        @csrf
        <h2 style="margin:0;font-size:1.05rem;font-weight:800;color:var(--ink);">ตั้งค่า</h2>

        <label style="display:flex;align-items:center;gap:10px;font-weight:700;color:var(--ink);">
            <input type="checkbox" name="enabled" value="1" @checked(old('enabled', $settings['enabled']))>
            เปิดรับชำระด้วยเหรียญ TPIX (ร้านค้า · แอป · เติมเงินเข้ากระเป๋า)
        </label>
        <label style="display:flex;align-items:center;gap:10px;font-weight:700;color:var(--ink);">
            <input type="checkbox" name="show_prices" value="1" @checked(old('show_prices', $settings['show_prices']))>
            แสดงราคาเป็น TPIX บนหน้าสินค้าทุกหน้า (เว็บ + แอป)
        </label>

        <div>
            <label for="tpx-addr" style="display:block;font-weight:700;font-size:.85rem;margin-bottom:6px;color:var(--ink);">กระเป๋ารับเงิน TPIX (บนเชน TPIX {{ config('tpix_pay.chain_id') }})</label>
            <input id="tpx-addr" name="receive_address" type="text" class="tp-input tp-num" style="width:100%;" maxlength="42"
                   placeholder="0x…" value="{{ old('receive_address', $settings['receive_address']) }}">
            <div class="tp-muted" style="font-size:.78rem;margin-top:4px;">ใช้กระเป๋าที่คุณถือคีย์เอง (เช่น MetaMask / กระเป๋าคลัง) — ระบบไม่เก็บคีย์ส่วนตัว และไม่โอนออกจากกระเป๋านี้</div>
            @error('receive_address')<div style="color:#c53030;font-size:.8rem;margin-top:4px;">{{ $message }}</div>@enderror
        </div>

        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:14px;">
            <div>
                <label for="tpx-conf" style="display:block;font-weight:700;font-size:.85rem;margin-bottom:6px;color:var(--ink);">จำนวน confirmation</label>
                <input id="tpx-conf" name="confirmations" type="number" min="1" max="64" class="tp-input tp-num" style="width:100%;" value="{{ old('confirmations', $settings['confirmations']) }}">
                <div class="tp-muted" style="font-size:.78rem;margin-top:4px;">บล็อกละ ~2 วินาที · 3 = ~6 วินาที</div>
            </div>
            <div>
                <label for="tpx-max1" style="display:block;font-weight:700;font-size:.85rem;margin-bottom:6px;color:var(--ink);">เพดานต่อรายการ (บาท)</label>
                <input id="tpx-max1" name="max_per_payment_thb" type="number" min="0" step="0.01" class="tp-input tp-num" style="width:100%;" value="{{ old('max_per_payment_thb', $settings['max_per_payment_thb']) }}">
                <div class="tp-muted" style="font-size:.78rem;margin-top:4px;">0 = ไม่จำกัด</div>
            </div>
            <div>
                <label for="tpx-max2" style="display:block;font-weight:700;font-size:.85rem;margin-bottom:6px;color:var(--ink);">เพดานต่อผู้ใช้ต่อ 24 ชม. (บาท)</label>
                <input id="tpx-max2" name="max_per_user_day_thb" type="number" min="0" step="0.01" class="tp-input tp-num" style="width:100%;" value="{{ old('max_per_user_day_thb', $settings['max_per_user_day_thb']) }}">
                <div class="tp-muted" style="font-size:.78rem;margin-top:4px;">0 = ไม่จำกัด</div>
            </div>
            <div>
                <label for="tpx-pool" style="display:block;font-weight:700;font-size:.85rem;margin-bottom:6px;color:var(--ink);">เพดานตามสภาพคล่อง (เท่าของพูล)</label>
                <input id="tpx-pool" name="max_pool_ratio" type="number" min="0" step="0.1" class="tp-input tp-num" style="width:100%;" value="{{ old('max_pool_ratio', $settings['max_pool_ratio']) }}">
                <div class="tp-muted" style="font-size:.78rem;margin-top:4px;">
                    ยอดต่อรายการ ≤ ค่านี้ × มูลค่า USDT ในพูล · 0 = ไม่จำกัด
                    @if($spot && $settings['max_pool_ratio'] > 0)
                        · ตอนนี้สูงสุด ฿{{ number_format((float) $spot['reserve_usdt'] * (float) $spot['usd_thb'] * $settings['max_pool_ratio'], 2) }}
                    @endif
                </div>
            </div>
        </div>

        <div class="tp-muted" style="font-size:.8rem;line-height:1.6;">
            ราคา: พูล TPIX DEX (WTPIX/USDT {{ config('tpix_pay.dex.pair') }}) × อัตรา USD/THB · ใบเสนอยอดใช้ราคาที่ต่ำกว่าระหว่างราคาตอนนี้กับค่าเฉลี่ย {{ config('tpix_pay.twap_minutes') }} นาที ·
            ยอดมีเลขท้ายเฉพาะใบ (ทศนิยม 5–8) · หมดเวลา {{ config('tpix_pay.quote_ttl_minutes') }} นาที · ตัวสแกน <code>tpix:pay-scan</code> ทำงานทุกนาที
        </div>

        <div><button type="submit" class="tp-btn tp-btn-primary">บันทึก</button></div>
    </form>

    {{-- ── รายการชำระ ── --}}
    <div class="tp-card" style="display:flex;flex-direction:column;gap:12px;">
        <form method="GET" style="display:flex;gap:10px;flex-wrap:wrap;align-items:center;">
            <h2 style="margin:0;font-size:1.05rem;font-weight:800;color:var(--ink);flex:1;min-width:160px;">รายการชำระด้วย TPIX</h2>
            <select name="status" class="tp-input" style="min-width:170px;">
                <option value="">ทุกสถานะ</option>
                @foreach($statuses as $st)
                    <option value="{{ $st }}" @selected($status === $st)>{{ $tpxBadge[$st][1] ?? $st }}</option>
                @endforeach
            </select>
            <input type="search" name="q" value="{{ $search }}" placeholder="รหัส / tx hash / กระเป๋า / เลขคำสั่งซื้อ" class="tp-input" style="min-width:240px;">
            <button type="submit" class="tp-btn">ค้นหา</button>
        </form>

        <div style="overflow-x:auto;">
            <table style="width:100%;border-collapse:collapse;font-size:.85rem;">
                <thead>
                    <tr class="tp-muted" style="text-align:left;">
                        <th style="padding:8px;">รายการ</th>
                        <th style="padding:8px;">ผู้ใช้</th>
                        <th style="padding:8px;text-align:right;">บาท</th>
                        <th style="padding:8px;text-align:right;">TPIX</th>
                        <th style="padding:8px;">สถานะ</th>
                        <th style="padding:8px;">ธุรกรรม</th>
                        <th style="padding:8px;">เวลา</th>
                        <th style="padding:8px;"></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($payments as $p)
                        <tr style="border-top:1px solid var(--line, rgba(0,0,0,.08));vertical-align:top;">
                            <td style="padding:8px;">
                                <div class="tp-num" style="font-weight:700;color:var(--ink);">{{ $p->reference }}</div>
                                <div class="tp-muted" style="font-size:.78rem;">
                                    @if($p->purpose === 'order')
                                        คำสั่งซื้อ #{{ $p->meta['order_number'] ?? $p->payable_id }}
                                    @elseif($p->isCheckoutPurpose())
                                        {{ $p->meta['title'] ?? ($p->purpose === 'fresh_market' ? 'ตลาดสด' : 'จองบริการ') }}
                                        @php($tpxF = $p->fulfilmentStatus())
                                        @if($tpxF === 'placed') · สั่งสำเร็จ {{ $p->meta['fulfilment']['result_number'] ?? '' }}
                                        @elseif($tpxF === 'failed') · สั่งไม่สำเร็จ (เงินอยู่ในกระเป๋า)
                                        @elseif($tpxF) · กำลังสั่ง ({{ $tpxF }})
                                        @endif
                                    @else
                                        เติมเงินเข้ากระเป๋า
                                    @endif
                                    @if(! empty($p->meta['late'])) · โอนช้า @endif
                                    @if(! empty($p->meta['duplicate_of'])) · โอนซ้ำของ {{ $p->meta['duplicate_of'] }} @endif
                                </div>
                            </td>
                            <td style="padding:8px;">{{ $p->user?->name ?? '—' }}<div class="tp-muted" style="font-size:.75rem;">#{{ $p->user_id }}</div></td>
                            <td class="tp-num" style="padding:8px;text-align:right;">฿{{ number_format((float) $p->amount_thb, 2) }}</td>
                            <td class="tp-num" style="padding:8px;text-align:right;">{{ $tpxFmt($p->amount_tpix, 8) }}<div class="tp-muted" style="font-size:.72rem;">@ {{ \App\Services\TpixPay\TpixPriceService::formatRate((string) $p->rate_thb) }}</div></td>
                            <td style="padding:8px;">
                                <span class="tp-pill" style="background:{{ $tpxBadge[$p->status][0] ?? '#718096' }};color:#fff;">{{ $tpxBadge[$p->status][1] ?? $p->status }}</span>
                                @if($p->failure_reason)<div class="tp-muted" style="font-size:.72rem;max-width:220px;">{{ $p->failure_reason }}</div>@endif
                            </td>
                            <td style="padding:8px;max-width:200px;">
                                @if($p->tx_hash)
                                    <a href="{{ $p->explorerTxUrl() }}" target="_blank" rel="noopener" class="tp-num" style="font-size:.75rem;word-break:break-all;">{{ \Illuminate\Support\Str::limit($p->tx_hash, 22) }}</a>
                                    <div class="tp-muted" style="font-size:.72rem;">conf {{ $p->confirmations }} · จาก {{ \Illuminate\Support\Str::limit((string) $p->from_address, 14) }}</div>
                                @else
                                    <span class="tp-muted">—</span>
                                @endif
                            </td>
                            <td class="tp-muted" style="padding:8px;font-size:.75rem;white-space:nowrap;">
                                {{ $p->created_at?->format('d/m H:i') }}
                                @if($p->paid_at)<div>จ่าย {{ $p->paid_at->format('d/m H:i') }}</div>@endif
                            </td>
                            <td style="padding:8px;min-width:200px;">
                                @if($p->status === 'late' && ! empty($p->meta['needs_review']))
                                    <form method="POST" action="{{ route('admin.tpix-pay.approve', $p->id) }}" style="margin-bottom:6px;"
                                          onsubmit="return confirm('ยืนยันเติมมูลค่า TPIX ที่ได้รับ (ตามราคาปัจจุบัน) เข้ากระเป๋าผู้ใช้?');">
                                        @csrf
                                        <button type="submit" class="tp-btn tp-btn-sm tp-btn-primary">อนุมัติเติมเข้ากระเป๋า</button>
                                    </form>
                                @endif
                                @if(! $p->isSettled())
                                    <form method="POST" action="{{ route('admin.tpix-pay.recheck', $p->id) }}" style="display:flex;gap:6px;flex-wrap:wrap;">
                                        @csrf
                                        <input type="text" name="tx_hash" placeholder="tx hash (ถ้ามี)" class="tp-input tp-num" style="width:130px;font-size:.75rem;">
                                        <button type="submit" class="tp-btn tp-btn-sm">ตรวจบนเชน</button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="tp-muted" style="padding:24px;text-align:center;">ยังไม่มีรายการ</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div>{{ $payments->links() }}</div>
    </div>
</div>
@endsection
