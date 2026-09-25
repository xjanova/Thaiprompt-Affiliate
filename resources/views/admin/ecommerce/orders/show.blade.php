@extends('layouts.admin-v4')

@section('title', 'คำสั่งซื้อ #' . $order->order_number)

@php
    $c = [
        'ok' => 'var(--tp-ok,#5aa07e)',
        'bad' => 'var(--tp-bad,#d9534f)',
        'warn' => 'var(--tp-warn,#e0a52e)',
        'info' => 'var(--tp-info,#5689b8)',
        'violet' => 'var(--tp-violet,#8c6fd6)',
        'mute' => 'var(--ink2)',
    ];
    $pill = fn (string $color) => "background:color-mix(in srgb, {$color} 16%, transparent); color:{$color};";
    $statusColor = [
        'pending' => $c['warn'], 'paid' => $c['info'], 'processing' => $c['info'],
        'shipped' => $c['violet'], 'delivered' => $c['ok'], 'completed' => $c['ok'],
        'cancelled' => $c['bad'], 'refunded' => $c['mute'],
    ];
    $paymentColor = ['pending' => $c['warn'], 'paid' => $c['ok'], 'failed' => $c['bad'], 'refunded' => $c['mute']];

    $th = 'padding:11px 14px; text-align:left; font-size:11px; font-weight:700; color:var(--ink2); letter-spacing:.3px; white-space:nowrap;';
    $td = 'padding:11px 14px; font-size:13px; color:var(--ink); vertical-align:middle;';
    $lbl = 'display:block; font-size:12px; color:var(--ink2); font-weight:600; margin-bottom:6px;';
    $row = 'display:flex; justify-content:space-between; gap:12px; font-size:13px; padding:6px 0;';

    $ledgers = $ledgers ?? collect();
    $platformTransactions = $platformTransactions ?? collect();
    $paymentAudits = $paymentAudits ?? collect();
    $riderSummary = $riderSummary ?? null;
    $isSuperAdmin = $isSuperAdmin ?? false;

    $shipping = \App\Services\Shop\ShopPresenter::shipping($order);
    $isRider = ($order->delivery_method ?? 'parcel') === 'rider';
    $money = fn ($v) => '฿' . number_format((float) $v, 2);

    // สถานะที่แอดมินเลือกได้ (ตรงกับ validation ของ updateOrderStatus)
    $statusChoices = [
        'pending' => 'รอดำเนินการ',
        'processing' => 'กำลังเตรียมสินค้า',
        'shipped' => 'จัดส่งแล้ว',
        'completed' => 'สำเร็จ',
        'cancelled' => 'ยกเลิก',
        'refunded' => 'คืนเงิน',
    ];
    $paymentChoices = ['pending' => 'รอชำระเงิน', 'paid' => 'ชำระแล้ว', 'failed' => 'ชำระไม่สำเร็จ', 'refunded' => 'คืนเงินแล้ว'];

    $canRefund = $order->payment_status === 'paid' && $order->canBeRefunded();
    $totalGp = (float) $ledgers->sum('platform_fee');
    $totalVat = (float) $ledgers->sum('vat_amount');
    $totalPool = (float) $ledgers->sum('mlm_commission');
    $totalNet = (float) $ledgers->sum('net_amount');
@endphp

@section('content')
<div style="display:flex; flex-direction:column; gap:18px;">

    {{-- ===== หัวหน้า ===== --}}
    <div style="display:flex; flex-wrap:wrap; align-items:flex-end; justify-content:space-between; gap:14px;">
        <div style="min-width:0;">
            <div style="font-size:11px; color:var(--ink2); font-weight:600; letter-spacing:.4px;">หลังบ้าน · อีคอมเมิร์ซ · คำสั่งซื้อ</div>
            <h1 class="tp-num" style="font-size:clamp(20px,4vw,27px); font-weight:800; margin:4px 0 0; overflow-wrap:anywhere;">#{{ $order->order_number }}</h1>
            <div style="display:flex; flex-wrap:wrap; gap:7px; margin-top:8px;">
                <span class="tp-pill" style="{{ $pill($statusColor[$order->status] ?? $c['mute']) }}">{{ $order->status_label }}</span>
                <span class="tp-pill" style="{{ $pill($paymentColor[$order->payment_status] ?? $c['mute']) }}">💳 {{ \App\Services\Shop\ShopPresenter::paymentStatusLabel($order->payment_status) }}</span>
                <span class="tp-pill tp-pill-soft">{{ $isRider ? '🛵 ส่งด้วยไรเดอร์' : '📦 ส่งพัสดุ' }}</span>
                @if($order->has_unread_messages)
                    <span class="tp-pill" style="{{ $pill($c['bad']) }}"><i class="fas fa-comment-dots"></i> มีข้อความใหม่</span>
                @endif
            </div>
        </div>
        <div style="display:flex; gap:9px; flex-wrap:wrap;">
            <a href="{{ route('admin.ecommerce.orders.tracking', $order) }}" class="tp-btn tp-btn-sm tp-btn-primary"><i class="fas fa-truck"></i> จัดการพัสดุ / ข้อความ</a>
            <a href="{{ route('admin.ecommerce.orders.index') }}" class="tp-btn tp-btn-sm"><i class="fas fa-arrow-left"></i> กลับรายการ</a>
        </div>
    </div>

    {{-- ===== ข้อผิดพลาดจากฟอร์ม ===== --}}
    @if($errors->any())
        <div class="tp-card" style="padding:14px 18px; border-left:4px solid {{ $c['bad'] }};">
            <div style="font-weight:700; color:{{ $c['bad'] }}; margin-bottom:6px;"><i class="fas fa-circle-exclamation"></i> บันทึกไม่สำเร็จ</div>
            <ul style="margin:0; padding-left:18px; font-size:13px; color:var(--ink);">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- ===== ข้อมูลหลัก 3 การ์ด ===== --}}
    <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(260px,1fr)); gap:16px;">
        <div class="tp-card">
            <div class="tp-section-h" style="margin-bottom:10px;"><i class="fas fa-user" style="color:var(--accent1);"></i> ลูกค้า</div>
            <div style="{{ $row }}"><span style="color:var(--ink2);">ชื่อ</span><span style="font-weight:600; text-align:right;">{{ $order->user?->name ?: 'ไม่ระบุ' }}</span></div>
            <div style="{{ $row }}"><span style="color:var(--ink2);">อีเมล</span><span style="text-align:right; overflow-wrap:anywhere;">{{ $order->user?->email ?: '-' }}</span></div>
            <div style="{{ $row }}"><span style="color:var(--ink2);">โทร</span><span class="tp-num">{{ $shipping['phone'] ?? ($order->shippingAddress?->phone_number ?: '-') }}</span></div>
            @if($order->user)
                <a href="{{ route('admin.users.show', $order->user->id) }}" style="display:inline-block; margin-top:6px; font-size:12.5px; color:var(--deep1);">ดูข้อมูลผู้ใช้ →</a>
            @endif
        </div>

        <div class="tp-card">
            <div class="tp-section-h" style="margin-bottom:10px;"><i class="fas fa-calendar-days" style="color:var(--accent1);"></i> เวลาและการชำระเงิน</div>
            <div style="{{ $row }}"><span style="color:var(--ink2);">สั่งซื้อ</span><span class="tp-num">{{ $order->created_at?->format('d/m/Y H:i') }}</span></div>
            @if($order->paid_at)<div style="{{ $row }}"><span style="color:var(--ink2);">ชำระเงิน</span><span class="tp-num">{{ $order->paid_at->format('d/m/Y H:i') }}</span></div>@endif
            @if($order->shipped_at)<div style="{{ $row }}"><span style="color:var(--ink2);">จัดส่ง</span><span class="tp-num">{{ $order->shipped_at->format('d/m/Y H:i') }}</span></div>@endif
            @if($order->delivered_at)<div style="{{ $row }}"><span style="color:var(--ink2);">ส่งถึง</span><span class="tp-num">{{ $order->delivered_at->format('d/m/Y H:i') }}</span></div>@endif
            <div style="{{ $row }}"><span style="color:var(--ink2);">วิธีชำระ</span><span>{{ \App\Support\Shop\PaymentMethod::labelTh($order->payment_method) }}</span></div>
            @if($order->payment_reference)
                <div style="{{ $row }}"><span style="color:var(--ink2);">เลขอ้างอิง</span><span class="tp-num" style="overflow-wrap:anywhere; text-align:right;">{{ $order->payment_reference }}</span></div>
            @endif
        </div>

        <div class="tp-card">
            <div class="tp-section-h" style="margin-bottom:10px;"><i class="fas fa-location-dot" style="color:var(--accent1);"></i> ที่อยู่จัดส่ง</div>
            @if($shipping)
                <div style="font-size:13px; line-height:1.7;">
                    <div style="font-weight:700;">{{ $shipping['name'] ?? '-' }} <span class="tp-num" style="font-weight:500; color:var(--ink2);">{{ $shipping['phone'] ?? '' }}</span></div>
                    <div>{{ $shipping['full_address'] ?? trim(implode(' ', array_filter([$shipping['address'] ?? null, $shipping['address_line_2'] ?? null, $shipping['subdistrict'] ?? null, $shipping['district'] ?? null, $shipping['province'] ?? null, $shipping['postal_code'] ?? null]))) }}</div>
                    @if(!empty($shipping['notes']))<div style="color:var(--ink2); font-style:italic;">📝 {{ $shipping['notes'] }}</div>@endif
                </div>
            @elseif($order->shippingAddress)
                <div style="font-size:13px; line-height:1.7;">
                    <div style="font-weight:700;">{{ $order->shippingAddress->recipient_name }} <span class="tp-num" style="font-weight:500; color:var(--ink2);">{{ $order->shippingAddress->phone_number }}</span></div>
                    <div>{{ $order->shippingAddress->full_address }}</div>
                    @if($order->shippingAddress->notes)<div style="color:var(--ink2); font-style:italic;">📝 {{ $order->shippingAddress->notes }}</div>@endif
                </div>
            @else
                <div style="color:var(--ink2); font-size:13px;">ไม่มีข้อมูลที่อยู่จัดส่ง</div>
            @endif
            @if($order->tracking_number)
                <div class="tp-divider" style="margin:10px 0;"></div>
                <div style="font-size:12.5px;">📦 {{ $order->shippingProvider?->name ?? $order->shipping_provider ?? 'ขนส่ง' }} · <span class="tp-num" style="font-weight:700;">{{ $order->tracking_number }}</span></div>
            @endif
        </div>
    </div>

    {{-- ===== ไรเดอร์ ===== --}}
    @if($isRider && $riderSummary)
        <div class="tp-card" style="display:flex; flex-wrap:wrap; justify-content:space-between; gap:12px; align-items:center;">
            <div style="display:flex; align-items:center; gap:12px;">
                <span class="tp-tile" style="width:42px; height:42px; font-size:20px;">🛵</span>
                <div>
                    <div class="tp-section-h">งานไรเดอร์ {{ $riderSummary['job_number'] ?? '' }}</div>
                    <div style="font-size:12.5px; color:var(--ink2);">
                        {{ $riderSummary['status_label'] ?? '-' }}
                        @if(!empty($riderSummary['rider']['name'])) · ไรเดอร์ {{ $riderSummary['rider']['name'] }} ({{ $riderSummary['rider']['vehicle_plate'] ?? '-' }}) @endif
                    </div>
                </div>
            </div>
            <div style="display:flex; gap:8px; flex-wrap:wrap;">
                @if(!empty($riderSummary['job_id']) && \Illuminate\Support\Facades\Route::has('admin.rider-jobs.show'))
                    <a href="{{ route('admin.rider-jobs.show', $riderSummary['job_id']) }}" class="tp-btn tp-btn-sm"><i class="fas fa-route"></i> ดูงานไรเดอร์</a>
                @endif
                @if(!empty($riderSummary['tracking_url']))
                    <a href="{{ $riderSummary['tracking_url'] }}" target="_blank" rel="noopener" class="tp-btn tp-btn-sm"><i class="fas fa-location-crosshairs"></i> ติดตามสด</a>
                @endif
            </div>
        </div>
    @endif

    {{-- ===== รายการสินค้า ===== --}}
    <div class="tp-card" style="padding:0; overflow:hidden;">
        <div style="padding:14px 18px; box-shadow:var(--inset-sm);">
            <div class="tp-section-h"><i class="fas fa-bag-shopping" style="color:var(--accent1);"></i> รายการสินค้า</div>
        </div>
        <div style="overflow-x:auto;">
            <table style="width:100%; min-width:760px; border-collapse:collapse;">
                <thead>
                    <tr>
                        <th style="{{ $th }}">สินค้า</th>
                        <th style="{{ $th }}">ผู้ขาย</th>
                        <th style="{{ $th }} text-align:right;">ราคา/ชิ้น</th>
                        <th style="{{ $th }} text-align:center;">จำนวน</th>
                        <th style="{{ $th }} text-align:right;">รวม</th>
                        <th style="{{ $th }} text-align:right;" title="ค่า GP ที่บันทึกไว้ตอนสั่งซื้อ">GP (ตอนสั่ง)</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($order->items as $item)
                        @php
                            $img = \App\Services\Shop\ShopPresenter::imageUrl($item->product_image) ?? $item->product?->primary_image_url;
                        @endphp
                        <tr style="border-top:1px solid color-mix(in srgb, var(--ink2) 12%, transparent);">
                            <td style="{{ $td }}">
                                <div style="display:flex; align-items:center; gap:10px; min-width:0;">
                                    @if($img)
                                        <img src="{{ $img }}" alt="" loading="lazy" style="width:44px; height:44px; border-radius:11px; object-fit:cover; box-shadow:var(--inset-sm); flex:none;">
                                    @else
                                        <span class="tp-well" style="width:44px; height:44px; border-radius:11px; display:grid; place-items:center; color:var(--ink2); flex:none;"><i class="fas fa-image"></i></span>
                                    @endif
                                    <div style="min-width:0;">
                                        <div style="font-weight:600; max-width:280px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">{{ $item->product_name ?: ($item->product?->name ?? 'ไม่ระบุ') }}</div>
                                        <div style="font-size:11.5px; color:var(--ink2);">SKU: {{ $item->product_sku ?: ($item->product?->sku ?? '-') }}
                                            @if(is_array($item->product_attributes) && count($item->product_attributes))
                                                · {{ collect($item->product_attributes)->map(fn ($v, $k) => is_scalar($v) ? (is_string($k) ? "$k: $v" : $v) : null)->filter()->implode(', ') }}
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td style="{{ $td }} color:var(--ink2);">{{ $item->seller?->name ?? '-' }}</td>
                            <td style="{{ $td }} text-align:right;" class="tp-num">{{ $money($item->unit_price) }}</td>
                            <td style="{{ $td }} text-align:center;" class="tp-num">{{ number_format((int) $item->quantity) }}</td>
                            <td style="{{ $td }} text-align:right; font-weight:700;" class="tp-num">{{ $money($item->total ?: $item->subtotal) }}</td>
                            <td style="{{ $td }} text-align:right; color:var(--ink2);" class="tp-num">
                                {{ $money($item->commission_amount) }}
                                @if((float) $item->commission_rate > 0)<div style="font-size:11px;">{{ rtrim(rtrim(number_format((float) $item->commission_rate, 2), '0'), '.') }}%</div>@endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div style="padding:14px 18px; display:flex; justify-content:flex-end;">
            <div style="width:100%; max-width:340px;">
                <div style="{{ $row }}"><span style="color:var(--ink2);">ยอดสินค้า</span><span class="tp-num">{{ $money($order->subtotal) }}</span></div>
                @if((float) $order->discount_amount > 0)
                    <div style="{{ $row }}"><span style="color:var(--ink2);">ส่วนลด</span><span class="tp-num" style="color:{{ $c['bad'] }};">-{{ $money($order->discount_amount) }}</span></div>
                @endif
                <div style="{{ $row }}"><span style="color:var(--ink2);">ค่าจัดส่ง</span><span class="tp-num">{{ $money($order->shipping_fee) }}</span></div>
                @if((float) $order->tax_amount > 0)
                    <div style="{{ $row }}"><span style="color:var(--ink2);">ภาษี</span><span class="tp-num">{{ $money($order->tax_amount) }}</span></div>
                @endif
                <div class="tp-divider" style="margin:6px 0;"></div>
                <div style="{{ $row }} font-size:16px;"><span style="font-weight:700;">ยอดรวมทั้งสิ้น</span><span class="tp-num" style="font-weight:800; color:var(--deep1);">{{ $money($order->total_amount) }}</span></div>
            </div>
        </div>
    </div>

    {{-- ===== การแบ่งเงิน (GP / VAT / ค่าแนะนำ / ผู้ขาย) ===== --}}
    <div class="tp-card">
        <div style="display:flex; justify-content:space-between; gap:10px; flex-wrap:wrap; align-items:center; margin-bottom:12px;">
            <div class="tp-section-h"><i class="fas fa-scale-balanced" style="color:var(--accent1);"></i> การแบ่งเงินของออเดอร์</div>
            @if($ledgers->isEmpty())
                <span class="tp-pill tp-pill-soft">ยังไม่แบ่งเงิน</span>
            @else
                <span class="tp-pill" style="{{ $pill($c['ok']) }}">แบ่งเงินแล้ว</span>
            @endif
        </div>

        @if($ledgers->isEmpty())
            <div style="font-size:13px; color:var(--ink2); line-height:1.7;">
                ระบบแบ่งเงินอัตโนมัติเมื่อออเดอร์ชำระเงินแล้ว (GP เข้ากระเป๋าแพลตฟอร์ม · ค่าแนะนำเข้ากองทุนผู้แนะนำ · ส่วนที่เหลือพักไว้ให้ผู้ขายจนลูกค้าได้รับของ)
                @if($order->payment_status === 'paid')
                    <br>ออเดอร์นี้ชำระแล้วแต่ยังไม่มีรายการแบ่งเงิน — ระบบจะเก็บตกให้อัตโนมัติตามรอบ
                @endif
            </div>
        @else
            <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(150px,1fr)); gap:12px; margin-bottom:14px;">
                @foreach([
                    ['ค่า GP แพลตฟอร์ม', $totalGp, $c['info']],
                    ['VAT ที่เก็บ', $totalVat, $c['violet']],
                    ['กองทุนผู้แนะนำ', $totalPool, $c['warn']],
                    ['ผู้ขายได้สุทธิ', $totalNet, $c['ok']],
                ] as [$label, $value, $color])
                    <div class="tp-well" style="padding:12px 14px;">
                        <div style="font-size:11.5px; color:var(--ink2); font-weight:600;">{{ $label }}</div>
                        <div class="tp-num" style="font-size:19px; font-weight:800; color:{{ $color }}; margin-top:2px;">{{ $money($value) }}</div>
                    </div>
                @endforeach
            </div>
            <div style="overflow-x:auto;">
                <table style="width:100%; min-width:720px; border-collapse:collapse;">
                    <thead>
                        <tr>
                            <th style="{{ $th }}">ผู้ขาย</th>
                            <th style="{{ $th }} text-align:right;">ยอดขาย</th>
                            <th style="{{ $th }} text-align:right;">GP</th>
                            <th style="{{ $th }} text-align:right;">VAT</th>
                            <th style="{{ $th }} text-align:right;">ค่าแนะนำ</th>
                            <th style="{{ $th }} text-align:right;">หักอื่น</th>
                            <th style="{{ $th }} text-align:right;">สุทธิ</th>
                            <th style="{{ $th }}">สถานะเงิน</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($ledgers as $ledger)
                            <tr style="border-top:1px solid color-mix(in srgb, var(--ink2) 12%, transparent);">
                                <td style="{{ $td }}">{{ $ledger->user?->name ?? ('#'.$ledger->user_id) }}</td>
                                <td style="{{ $td }} text-align:right;" class="tp-num">{{ $money($ledger->gross_amount) }}</td>
                                <td style="{{ $td }} text-align:right;" class="tp-num">{{ $money($ledger->platform_fee) }}</td>
                                <td style="{{ $td }} text-align:right;" class="tp-num">{{ $money($ledger->vat_amount) }}</td>
                                <td style="{{ $td }} text-align:right;" class="tp-num">{{ $money($ledger->mlm_commission) }}</td>
                                <td style="{{ $td }} text-align:right;" class="tp-num">{{ $money((float) $ledger->other_deductions + (float) $ledger->debt_deduction) }}</td>
                                <td style="{{ $td }} text-align:right; font-weight:700;" class="tp-num">{{ $money($ledger->net_amount) }}</td>
                                <td style="{{ $td }} white-space:nowrap;">
                                    <span class="tp-pill tp-pill-soft">{{ $ledger->trashed() ? 'ยกเลิก (คืนเงิน)' : $ledger->status_label }}</span>
                                    @if($ledger->available_at && ! $ledger->paid_at)
                                        <div style="font-size:11px; color:var(--ink2); margin-top:3px;">โอนได้ {{ $ledger->available_at->format('d/m/Y') }}</div>
                                    @elseif($ledger->paid_at)
                                        <div style="font-size:11px; color:var(--ink2); margin-top:3px;">โอนแล้ว {{ $ledger->paid_at->format('d/m/Y') }}</div>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif

        @if($platformTransactions->isNotEmpty())
            <div class="tp-divider" style="margin:14px 0 10px;"></div>
            <div style="font-size:12px; color:var(--ink2); font-weight:700; margin-bottom:6px;">รายการเงินในกระเป๋าแพลตฟอร์ม</div>
            @foreach($platformTransactions as $pt)
                <div style="{{ $row }} border-top:1px dashed color-mix(in srgb, var(--ink2) 18%, transparent);">
                    <span>{{ $pt->wallet?->name ?? 'กระเป๋าแพลตฟอร์ม' }} · <span style="color:var(--ink2);">{{ $pt->sub_type_label }}</span></span>
                    <span class="tp-num" style="font-weight:700; color:{{ in_array($pt->type, ['income', 'transfer_in'], true) ? $c['ok'] : $c['bad'] }};">
                        {{ in_array($pt->type, ['income', 'transfer_in'], true) ? '+' : '-' }}{{ $money(abs((float) $pt->amount)) }}
                    </span>
                </div>
            @endforeach
        @endif
    </div>

    {{-- ===== ฟอร์มจัดการ ===== --}}
    <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(min(100%,300px),1fr)); gap:16px;">

        {{-- สถานะคำสั่งซื้อ --}}
        <div class="tp-card">
            <div class="tp-section-h" style="margin-bottom:12px;"><i class="fas fa-list-check" style="color:var(--accent1);"></i> อัปเดตสถานะคำสั่งซื้อ</div>
            <form method="POST" action="{{ route('admin.ecommerce.orders.status.update', $order) }}"
                  x-data="{ selected: @js(array_key_exists($order->status, $statusChoices) ? $order->status : ''), busy: false }"
                  @submit="
                    if (selected === 'cancelled' && !confirm(@js('ยืนยันยกเลิกคำสั่งซื้อ #'.$order->order_number.' ?'."\n".($order->payment_status === 'paid' ? '- คืนเงิน '.$money($order->total_amount).' เข้ากระเป๋าลูกค้า'."\n".'- ดึงค่าคอม/เงินคืนที่จ่ายไปแล้วกลับ'."\n" : '').'- คืนสต็อกสินค้า'))) { $event.preventDefault(); return; }
                    if (selected === 'refunded' && !confirm(@js('ยืนยันคืนเงินเต็มจำนวน '.$money($order->total_amount).' ?'."\n".'- เงินเข้ากระเป๋าลูกค้า'."\n".'- ดึงรายได้ผู้ขาย/ค่าคอมกลับ (อาจเกิดหนี้ถ้าผู้ขายถอนไปแล้ว)'))) { $event.preventDefault(); return; }
                    busy = true">
                @csrf
                <label style="{{ $lbl }}">สถานะใหม่</label>
                <select name="status" x-model="selected" class="tp-input" required>
                    @unless(array_key_exists($order->status, $statusChoices))
                        <option value="" disabled>ปัจจุบัน: {{ $order->status_label }}</option>
                    @endunless
                    @foreach($statusChoices as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </select>

                <div x-show="selected === 'cancelled'" x-cloak class="tp-well" style="margin-top:10px; padding:10px 12px; font-size:12.5px; color:{{ $c['bad'] }};">
                    ยกเลิกแล้วระบบคืนสต็อก{{ $order->payment_status === 'paid' ? ' และคืนเงินเข้ากระเป๋าลูกค้าอัตโนมัติ' : '' }}
                    @unless($order->canBeCancelled()) <br><strong>สถานะนี้ยกเลิกไม่ได้แล้ว (ส่งของ/ไรเดอร์รับของไปแล้ว)</strong>@endunless
                </div>
                <div x-show="selected === 'refunded'" x-cloak class="tp-well" style="margin-top:10px; padding:10px 12px; font-size:12.5px; color:{{ $c['warn'] }};">
                    คืนเงินจริงผ่านระบบคืนเงิน: เงินเข้ากระเป๋าลูกค้า + ดึงรายได้ผู้ขาย/ค่าคอมกลับ
                    @unless($canRefund) <br><strong>ออเดอร์นี้ยังคืนเงินไม่ได้ (ต้องชำระแล้วและยังไม่จบ)</strong>@endunless
                </div>

                <label style="{{ $lbl }} margin-top:12px;">หมายเหตุ / เหตุผล <span x-show="selected === 'cancelled' || selected === 'refunded'" style="color:{{ $c['bad'] }};">*</span>
                    <span style="font-weight:500;">(ต่อท้ายบันทึกของแอดมินเดิม ไม่เขียนทับ)</span></label>
                <textarea name="admin_notes" rows="2" class="tp-input"
                          :required="selected === 'cancelled' || selected === 'refunded'"
                          :placeholder="(selected === 'cancelled' || selected === 'refunded') ? 'กรุณาระบุเหตุผล (ลูกค้าจะเห็นในประวัติ)' : 'หมายเหตุเพิ่มเติม (ไม่บังคับ)'"></textarea>

                <button type="submit" class="tp-btn tp-btn-primary" style="width:100%; margin-top:12px;" :disabled="busy || !selected"
                        :style="{ background: selected === 'cancelled' ? 'var(--tp-bad,#d9534f)' : '' }">
                    <i class="fas fa-floppy-disk"></i>
                    <span x-text="selected === 'cancelled' ? 'ยกเลิกคำสั่งซื้อ' : (selected === 'refunded' ? 'คืนเงินคำสั่งซื้อ' : 'บันทึกสถานะ')"></span>
                </button>
            </form>
        </div>

        {{-- สถานะการชำระเงิน (ต้องมีเหตุผล · audit) --}}
        <div class="tp-card">
            <div class="tp-section-h" style="margin-bottom:12px;"><i class="fas fa-money-check-dollar" style="color:var(--accent1);"></i> สถานะการชำระเงิน</div>
            <form method="POST" action="{{ route('admin.ecommerce.orders.payment-status.update', $order) }}"
                  x-data="{ ps: @js(old('payment_status', $order->payment_status)), busy: false }"
                  @submit="
                    if (ps === 'paid' && !confirm(@js('ยืนยันว่าได้รับเงิน '.$money($order->total_amount).' แล้ว?'."\n".'ระบบจะแบ่งเงินให้ผู้ขายทันที (เงินจริง)'))) { $event.preventDefault(); return; }
                    if (ps === 'refunded' && !confirm(@js('ยืนยันคืนเงิน '.$money($order->total_amount).' เข้ากระเป๋าลูกค้า?'))) { $event.preventDefault(); return; }
                    busy = true">
                @csrf
                <label style="{{ $lbl }}">สถานะการชำระเงิน</label>
                <select name="payment_status" x-model="ps" class="tp-input" required>
                    @foreach($paymentChoices as $value => $label)
                        <option value="{{ $value }}">{{ $label }}{{ in_array($value, ['paid', 'refunded'], true) ? ' (Super Admin)' : '' }}</option>
                    @endforeach
                </select>

                <label style="{{ $lbl }} margin-top:12px;">เหตุผล <span style="color:{{ $c['bad'] }};">*</span></label>
                <textarea name="reason" rows="2" class="tp-input" required minlength="5" maxlength="1000"
                          placeholder="เช่น ตรวจสลิปโอนเงินแล้ว ยอดตรง">{{ old('reason') }}</textarea>

                <div x-show="ps === 'paid'" x-cloak>
                    <label style="{{ $lbl }} margin-top:12px;">เลขอ้างอิงสลิป/ธุรกรรม <span style="color:{{ $c['bad'] }};">*</span></label>
                    <input type="text" name="payment_reference" maxlength="255" class="tp-input" value="{{ old('payment_reference') }}"
                           placeholder="เลขที่รายการจากสลิป" :required="ps === 'paid'">
                </div>

                <div class="tp-well" style="margin-top:12px; padding:10px 12px; font-size:12px; color:var(--ink2); line-height:1.6;">
                    ยอด <span class="tp-num" style="font-weight:700; color:var(--ink);">{{ $money($order->total_amount) }}</span> · {{ \App\Support\Shop\PaymentMethod::labelTh($order->payment_method) }}<br>
                    "ชำระแล้ว" และ "คืนเงินแล้ว" ทำได้เฉพาะ Super Admin · ย้อนจากชำระแล้วกลับไม่ได้ · ทุกครั้งถูกบันทึกประวัติ
                    @unless($isSuperAdmin)<br><strong style="color:{{ $c['warn'] }};">บัญชีของคุณไม่ใช่ Super Admin</strong>@endunless
                </div>

                <button type="submit" class="tp-btn tp-btn-primary" style="width:100%; margin-top:12px;" :disabled="busy">
                    <i class="fas fa-shield-halved"></i> บันทึกสถานะการชำระเงิน
                </button>
            </form>
        </div>
    </div>

    {{-- ===== คืนเงินเต็มจำนวน (ปุ่มลัด) ===== --}}
    @if($canRefund)
        <div class="tp-card" style="border-left:4px solid {{ $c['warn'] }};" x-data="{ open: false, busy: false }">
            <div style="display:flex; justify-content:space-between; gap:12px; flex-wrap:wrap; align-items:center;">
                <div>
                    <div class="tp-section-h"><i class="fas fa-rotate-left" style="color:{{ $c['warn'] }};"></i> คืนเงินเต็มจำนวน</div>
                    <div style="font-size:12.5px; color:var(--ink2); margin-top:3px;">คืน {{ $money($order->total_amount) }} เข้ากระเป๋าลูกค้า และดึงรายได้ผู้ขาย/ค่าคอมที่จ่ายไปแล้วกลับ</div>
                </div>
                <button type="button" class="tp-btn tp-btn-sm" @click="open = !open"><i class="fas fa-hand-holding-dollar"></i> เริ่มคืนเงิน</button>
            </div>
            <form x-show="open" x-cloak method="POST" action="{{ route('admin.ecommerce.orders.status.update', $order) }}" class="flex"
                  style="margin-top:12px; gap:10px; flex-wrap:wrap; align-items:flex-start;"
                  @submit="if (!confirm(@js('ยืนยันคืนเงิน '.$money($order->total_amount).' ให้ออเดอร์ #'.$order->order_number.' ? ย้อนกลับไม่ได้'))) { $event.preventDefault(); return; } busy = true">
                @csrf
                <input type="hidden" name="status" value="refunded">
                <textarea name="admin_notes" rows="2" class="tp-input" required minlength="5" maxlength="1000" style="flex:1; min-width:240px;"
                          placeholder="เหตุผลการคืนเงิน (บังคับ)"></textarea>
                <button type="submit" class="tp-btn tp-btn-sm" style="background:{{ $c['warn'] }}; color:var(--tp-on-accent,#fff);" :disabled="busy">ยืนยันคืนเงิน</button>
            </form>
        </div>
    @endif

    {{-- ===== ข้อมูลการยกเลิก/คืนเงิน ===== --}}
    @if(in_array($order->status, ['cancelled', 'refunded'], true) || $order->refunded_at || $order->cancelled_at)
        <div class="tp-card" style="border-left:4px solid {{ $c['bad'] }};">
            <div class="tp-section-h" style="margin-bottom:8px;">{{ $order->status === 'refunded' || $order->refunded_at ? 'ข้อมูลการคืนเงิน' : 'ข้อมูลการยกเลิก' }}</div>
            @if($order->cancellation_reason)<div style="{{ $row }}"><span style="color:var(--ink2);">เหตุผลยกเลิก</span><span style="text-align:right;">{{ $order->cancellation_reason }}</span></div>@endif
            @if($order->refund_reason)<div style="{{ $row }}"><span style="color:var(--ink2);">เหตุผลคืนเงิน</span><span style="text-align:right;">{{ $order->refund_reason }}</span></div>@endif
            @if($order->cancelled_at)<div style="{{ $row }}"><span style="color:var(--ink2);">ยกเลิกเมื่อ</span><span class="tp-num">{{ $order->cancelled_at->format('d/m/Y H:i') }}</span></div>@endif
            @if($order->refunded_at)<div style="{{ $row }}"><span style="color:var(--ink2);">คืนเงินเมื่อ</span><span class="tp-num">{{ $order->refunded_at->format('d/m/Y H:i') }}</span></div>@endif
            @if($order->refunded_by)
                <div style="{{ $row }}"><span style="color:var(--ink2);">คืนเงินโดย</span><span>{{ \App\Models\User::withTrashed()->find($order->refunded_by)?->name ?? ('#'.$order->refunded_by) }}</span></div>
            @endif
        </div>
    @endif

    {{-- ===== ประวัติการเปลี่ยนสถานะชำระเงิน ===== --}}
    <div class="tp-card">
        <div class="tp-section-h" style="margin-bottom:10px;"><i class="fas fa-clock-rotate-left" style="color:var(--accent1);"></i> ประวัติการเปลี่ยนสถานะชำระเงินด้วยมือ</div>
        @forelse($paymentAudits as $log)
            <div style="padding:10px 0; border-top:1px dashed color-mix(in srgb, var(--ink2) 18%, transparent); font-size:13px;">
                <div style="display:flex; justify-content:space-between; gap:10px; flex-wrap:wrap;">
                    <span>
                        <span class="tp-pill tp-pill-soft">{{ \App\Services\Shop\ShopPresenter::paymentStatusLabel($log->old_values['payment_status'] ?? null) }}</span>
                        <i class="fas fa-arrow-right" style="color:var(--ink2); font-size:11px;"></i>
                        <span class="tp-pill tp-pill-gold">{{ \App\Services\Shop\ShopPresenter::paymentStatusLabel($log->new_values['payment_status'] ?? null) }}</span>
                    </span>
                    <span style="color:var(--ink2); font-size:12px;">{{ $log->user?->name ?? 'ระบบ' }} · <span class="tp-num">{{ $log->created_at?->format('d/m/Y H:i') }}</span></span>
                </div>
                <div style="margin-top:5px; color:var(--ink); overflow-wrap:anywhere;">{{ $log->description }}</div>
                @if(!empty($log->new_values['payment_reference']))
                    <div style="font-size:12px; color:var(--ink2);">อ้างอิง: <span class="tp-num">{{ $log->new_values['payment_reference'] }}</span></div>
                @endif
            </div>
        @empty
            <div style="font-size:13px; color:var(--ink2);">ยังไม่มีการเปลี่ยนสถานะชำระเงินด้วยมือ</div>
        @endforelse
    </div>

    {{-- ===== หมายเหตุ ===== --}}
    @if($order->customer_notes || $order->admin_notes)
        <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(280px,1fr)); gap:16px;">
            @if($order->customer_notes)
                <div class="tp-card">
                    <div class="tp-section-h" style="margin-bottom:8px;"><i class="fas fa-comment" style="color:var(--accent1);"></i> หมายเหตุจากลูกค้า</div>
                    <div style="font-size:13px; white-space:pre-wrap; overflow-wrap:anywhere;">{{ $order->customer_notes }}</div>
                </div>
            @endif
            @if($order->admin_notes)
                <div class="tp-card">
                    <div class="tp-section-h" style="margin-bottom:8px;"><i class="fas fa-note-sticky" style="color:var(--accent1);"></i> บันทึกของแอดมิน</div>
                    <div style="font-size:13px; white-space:pre-wrap; overflow-wrap:anywhere; max-height:260px; overflow:auto;">{{ $order->admin_notes }}</div>
                </div>
            @endif
        </div>
    @endif
</div>
@endsection
