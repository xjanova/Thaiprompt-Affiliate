{{--
 | รายละเอียดออเดอร์ตลาดสด (admin.fresh-market.orders.show) — ธีม V4
 | ตัวแปรจาก Admin\FreshMarketController@showOrder:
 |   $order (with buyer, seller.user, listing, riderJob.rider), $allowedActions (role admin จาก FreshMarketOrder::ACTIONS), $canRedispatch,
 |   $walletTransactions, $platformTransactions, $gpDebt (WalletDebt|null), $history (status_history)
 | การกระทำ (ทุกปุ่มมีโมดัลยืนยัน):
 |   POST orders.cancel {reason* ≥3} — คืนเงินเฉพาะยอดที่เก็บเข้าระบบจริง · POST orders.complete {reason?} — ปล่อยเงินให้ร้าน
 |   POST orders.redispatch — สร้างงานไรเดอร์ใหม่ (ออเดอร์พร้อมส่ง/ส่งไม่สำเร็จ)
--}}
@extends('layouts.admin-v4')

@section('title', 'ออเดอร์ #'.$order->order_number.' · ตลาดสด')

@section('content')
@include('admin.riders.partials.v4-kit')
@php
    $paidIntoSystem = $order->payment_status === 'paid';
    $refundPreview = $paidIntoSystem ? max(0, round((float) $order->total_amount + (float) $order->delivery_fee - (float) $order->refunded_amount, 2)) : 0;
    $refTypeLabels = [
        \App\Services\FreshMarketService::REF_PAYMENT => 'ผู้ซื้อชำระเงิน',
        \App\Services\FreshMarketService::REF_REFUND => 'คืนเงินผู้ซื้อ',
        \App\Services\FreshMarketService::REF_PAYOUT => 'โอนเงินให้ร้าน',
        \App\Services\FreshMarketService::REF_CASHBACK => 'แคชแบ็คผู้ซื้อ',
        \App\Services\FreshMarketService::REF_COD_GP => 'หักค่า GP (COD)',
    ];
    $platformLabels = [
        \App\Services\FreshMarketService::PLATFORM_GP => 'ค่า GP',
        \App\Services\FreshMarketService::PLATFORM_GP_DEBT => 'ค่า GP (เก็บจากหนี้ค้าง)',
        \App\Services\FreshMarketService::PLATFORM_CASHBACK => 'จ่ายแคชแบ็ค',
        \App\Services\FreshMarketService::PLATFORM_REFERRAL => 'ค่าแนะนำ',
    ];
    $actorLabels = ['buyer' => 'ผู้ซื้อ', 'seller' => 'ร้านค้า', 'admin' => 'แอดมิน', 'system' => 'ระบบ', 'rider' => 'ไรเดอร์'];
    $txUserIds = $walletTransactions->pluck('user_id')->filter()->unique()->values()->all();
    $txUsers = $txUserIds !== [] ? \App\Models\User::withTrashed()->whereIn('id', $txUserIds)->pluck('name', 'id') : collect();
    $riderJob = $order->riderJob;
    // รายการสินค้าทั้งหมด (ออเดอร์หลายรายการ) — ออเดอร์เก่าที่ไม่มีแถว items โมเดลสร้างรายการเสมือนจากคอลัมน์เดิมให้
    $lineItems = $order->lineItems();
    // ปุ่มเรียกไรเดอร์ใหม่แสดงเฉพาะเมื่อไม่มีงานไรเดอร์ที่ยังวิ่งอยู่ (service ตรวจซ้ำอีกชั้น — RIDER_JOB_ACTIVE)
    $showRedispatch = $canRedispatch && (! $riderJob || $riderJob->isTerminal());
    $timeMarks = [
        ['created_at', 'สั่งซื้อ'], ['paid_at', 'ชำระเงิน'], ['accepted_at', 'ร้านรับออเดอร์'], ['preparing_at', 'เริ่มเตรียมของ'],
        ['ready_at', 'พร้อมส่ง/พร้อมรับ'], ['delivered_at', 'ส่งถึงผู้ซื้อ'], ['completed_at', 'เสร็จสิ้น'], ['cancelled_at', 'ยกเลิก'],
    ];
@endphp
<div x-data="{}" style="display:flex; flex-direction:column; gap:18px;">

    {{-- ===== หัวเรื่อง ===== --}}
    <div style="display:flex; flex-wrap:wrap; align-items:flex-end; justify-content:space-between; gap:14px;">
        <div style="display:flex; align-items:center; gap:14px; min-width:0;">
            <a href="{{ route('admin.fresh-market.orders') }}" class="tp-icon-btn" title="กลับรายการออเดอร์"><i class="fas fa-arrow-left"></i></a>
            <div style="min-width:0;">
                <div style="font-size:11px; color:var(--ink2); font-weight:600; letter-spacing:.4px;">หลังบ้าน · ตลาดสด · ออเดอร์</div>
                <h1 class="tp-num" style="font-size:clamp(20px,4vw,27px); font-weight:800; margin:4px 0 0; display:flex; flex-wrap:wrap; gap:9px; align-items:center;">
                    #{{ $order->order_number }}
                    @include('admin.riders.partials.status', ['statusKind' => 'fm_order', 'statusValue' => $order->order_status, 'statusLabel' => $order->status_label])
                    @include('admin.riders.partials.status', ['statusKind' => 'payment', 'statusValue' => $order->payment_status, 'statusLabel' => $order->payment_status_label])
                </h1>
                <div style="font-size:12.5px; color:var(--ink2); margin-top:4px;">สั่งเมื่อ {{ $order->created_at?->thaidate('j M Y H:i') }} · {{ $order->payment_method_label }}</div>
            </div>
        </div>
    </div>

    @include('admin.riders.partials.flash')

    @if ($order->cancel_reason)
        <div class="tp-card" style="padding:14px 18px; border-left:4px solid var(--w-bad);">
            <div style="font-weight:700; font-size:13.5px;"><i class="fas fa-ban" style="color:var(--w-bad);"></i> ยกเลิกโดย{{ $actorLabels[$order->cancelled_by] ?? ($order->cancelled_by ?: 'ไม่ทราบ') }}</div>
            <div style="font-size:13px; margin-top:4px;">{{ $order->cancel_reason }}</div>
        </div>
    @endif

    <div style="display:flex; flex-wrap:wrap; gap:16px; align-items:flex-start;">

        {{-- ================= คอลัมน์หลัก ================= --}}
        <div style="flex:3 1 560px; min-width:0; display:flex; flex-direction:column; gap:16px;">

            {{-- สินค้า + ยอดเงิน --}}
            <div class="tp-card" style="padding:20px;">
                <div class="tp-section-h" style="margin-bottom:12px;"><i class="fas fa-basket-shopping"></i> สินค้าและยอดเงิน</div>
                <div style="display:flex; flex-direction:column; gap:10px;">
                    @foreach ($lineItems as $item)
                        <div style="display:flex; gap:12px; align-items:center;">
                            @if ($item->image_url)
                                <img src="{{ $item->image_url }}" alt="" loading="lazy" style="width:56px; height:56px; border-radius:14px; object-fit:cover; box-shadow:var(--raise); flex:none;">
                            @else
                                <span class="tp-well" style="width:56px; height:56px; border-radius:14px; display:grid; place-items:center; color:var(--ink2); flex:none;"><i class="fas fa-carrot"></i></span>
                            @endif
                            <div style="flex:1; min-width:0;">
                                @if ($item->listing_id)
                                    <a href="{{ route('admin.fresh-market.listings.show', $item->listing_id) }}" class="w1-link" style="color:var(--ink); font-size:14px;">{{ $item->title }}</a>
                                @else
                                    <span style="font-weight:700;">{{ $item->title }}</span>
                                @endif
                                @if ($item->optionsLabel() !== '')
                                    <div style="font-size:12px; color:var(--ink2);"><i class="fas fa-list-check"></i> {{ $item->optionsLabel() }}</div>
                                @endif
                                @if ($item->note)
                                    <div style="font-size:12px; color:var(--ink2);"><i class="fas fa-note-sticky"></i> {{ $item->note }}</div>
                                @endif
                                <div style="font-size:12.5px; color:var(--ink2);">{{ number_format((int) $item->quantity) }} × ฿{{ number_format((float) $item->unit_price, 2) }}{{ $item->unit ? ' / '.$item->unit : '' }}</div>
                            </div>
                            <b class="tp-num" style="font-size:15px;">฿{{ number_format((float) $item->line_total, 2) }}</b>
                        </div>
                    @endforeach
                </div>

                <div class="tp-divider" style="margin:14px 0;"></div>
                <div style="display:grid; grid-template-columns:1fr auto; gap:7px 12px; font-size:13px;">
                    <span style="color:var(--ink2);">ค่าสินค้า</span><span class="tp-num">฿{{ number_format((float) $order->total_amount, 2) }}</span>
                    <span style="color:var(--ink2);">ค่าส่ง{{ $order->delivery_type === 'rider' ? ' (ไรเดอร์)' : '' }}</span><span class="tp-num">฿{{ number_format((float) $order->delivery_fee, 2) }}</span>
                    <span style="font-weight:700;">ผู้ซื้อจ่ายรวม</span><b class="tp-num" style="font-size:16px;">฿{{ number_format($order->grand_total, 2) }}</b>
                </div>
                <div class="tp-well" style="padding:12px 14px; margin-top:12px; display:grid; grid-template-columns:1fr auto; gap:6px 12px; font-size:12.5px;">
                    <span style="color:var(--ink2);">ค่า GP แพลตฟอร์ม ({{ $order->gp_rate !== null ? rtrim(rtrim(number_format((float) $order->gp_rate, 2), '0'), '.') : '-' }}%)</span><span class="tp-num">฿{{ number_format((float) $order->platform_fee, 2) }}</span>
                    <span style="color:var(--ink2);">ร้านได้รับสุทธิ</span><b class="tp-num">฿{{ number_format((float) $order->seller_earning, 2) }}</b>
                    @if ((float) $order->cashback_amount > 0)
                        <span style="color:var(--ink2);">แคชแบ็คผู้ซื้อ {{ $order->cashback_processed ? '(จ่ายแล้ว)' : '(รอจ่ายเมื่อออเดอร์เสร็จ)' }}</span><span class="tp-num">฿{{ number_format((float) $order->cashback_amount, 2) }}</span>
                    @endif
                    @if ((float) $order->refunded_amount > 0)
                        <span style="color:var(--ink2);">คืนเงินผู้ซื้อแล้ว</span><b class="tp-num" style="color:color-mix(in srgb, var(--w-violet) 74%, var(--ink));">฿{{ number_format((float) $order->refunded_amount, 2) }}</b>
                    @endif
                    <span style="color:var(--ink2);">สถานะเงินพัก (escrow)</span><span>{{ ['held' => 'ระบบถือเงินไว้', 'released' => 'ปล่อยให้ร้านแล้ว', 'refunded' => 'คืนผู้ซื้อแล้ว'][$order->escrow_status] ?? ($order->escrow_status ?: 'ไม่มี (เก็บเงินปลายทาง)') }}</span>
                </div>
            </div>

            {{-- การจัดส่ง --}}
            <div class="tp-card" style="padding:20px;">
                <div class="tp-section-h" style="margin-bottom:12px;"><i class="fas {{ $order->delivery_type === 'rider' ? 'fa-motorcycle' : 'fa-person-walking' }}"></i> การจัดส่ง · {{ $order->delivery_type === 'rider' ? 'ไรเดอร์ส่ง' : 'ลูกค้ามารับเอง' }}</div>
                @if ($order->delivery_type === 'rider')
                    <div style="display:grid; grid-template-columns:minmax(90px,auto) 1fr; gap:7px 12px; font-size:13px;">
                        <span style="color:var(--ink2);">ที่อยู่ส่ง</span><span>{{ $order->delivery_address ?: '-' }}</span>
                        <span style="color:var(--ink2);">พิกัด</span>
                        <span class="tp-num">
                            @if ($order->buyer_latitude && $order->buyer_longitude)
                                <a href="https://www.openstreetmap.org/?mlat={{ (float) $order->buyer_latitude }}&mlon={{ (float) $order->buyer_longitude }}#map=17/{{ (float) $order->buyer_latitude }}/{{ (float) $order->buyer_longitude }}" target="_blank" rel="noopener" class="w1-link">{{ number_format((float) $order->buyer_latitude, 5) }}, {{ number_format((float) $order->buyer_longitude, 5) }}</a>
                            @else
                                -
                            @endif
                        </span>
                        <span style="color:var(--ink2);">ระยะทาง</span><span class="tp-num">{{ $order->delivery_distance_km ? number_format((float) $order->delivery_distance_km, 2).' กม.' : '-' }}</span>
                        @if ($order->delivery_notes)
                            <span style="color:var(--ink2);">หมายเหตุ</span><span>{{ $order->delivery_notes }}</span>
                        @endif
                    </div>
                    @if ($riderJob)
                        <a href="{{ route('admin.rider-jobs.show', $riderJob) }}" class="tp-card tp-card-hover" style="margin-top:14px; padding:14px; display:flex; align-items:center; gap:12px; text-decoration:none; color:var(--ink); border-left:4px solid var(--w-violet);">
                            <span class="tp-tile" style="width:40px; height:40px; background:linear-gradient(135deg, var(--w-violet), color-mix(in srgb, var(--w-violet) 70%, var(--ink)));"><i class="fas fa-motorcycle"></i></span>
                            <span style="flex:1; min-width:0;">
                                <b class="tp-num">งานไรเดอร์ #{{ $riderJob->job_number }}</b>
                                <span style="display:block; font-size:12px; color:var(--ink2);">{{ $riderJob->rider?->full_name ?? 'ยังไม่มีไรเดอร์รับงาน' }}{{ $riderJob->rider?->phone ? ' · '.$riderJob->rider->phone : '' }}</span>
                            </span>
                            @include('admin.riders.partials.status', ['statusKind' => 'job', 'statusValue' => $riderJob->status, 'statusLabel' => $riderJob->status_text])
                        </a>
                    @else
                        <p style="font-size:12.5px; color:var(--ink2); margin:12px 0 0;">ยังไม่มีงานไรเดอร์ — ระบบสร้างงานเมื่อร้านกด "พร้อมส่ง"</p>
                    @endif
                @else
                    <p style="font-size:13px; color:var(--ink2); margin:0;">ผู้ซื้อมารับที่ร้านเอง — ร้านกด "ส่งมอบสินค้าแล้ว" เมื่อผู้ซื้อรับของ</p>
                @endif
            </div>

            {{-- รายการเงิน --}}
            <div class="tp-card" style="padding:0; overflow:hidden;">
                <div class="tp-section-h" style="padding:14px 18px;"><i class="fas fa-money-bill-transfer"></i> รายการเงินของออเดอร์นี้</div>
                @if ($walletTransactions->isEmpty() && $platformTransactions->isEmpty())
                    <p style="padding:0 18px 18px; margin:0; font-size:13px; color:var(--ink2);">ยังไม่มีรายการเงิน{{ $order->payment_method === 'cod' ? ' (เก็บเงินปลายทาง — เงินไม่ผ่านระบบจนกว่าจะปิดออเดอร์)' : '' }}</p>
                @else
                    <div style="overflow-x:auto;">
                        <table style="width:100%; min-width:560px; border-collapse:collapse; font-size:13px;">
                            <thead>
                                <tr style="text-align:left; font-size:11px; color:var(--ink2); text-transform:uppercase; letter-spacing:.4px;">
                                    <th style="padding:9px 18px;">รายการ</th>
                                    <th style="padding:9px 12px;">บัญชี</th>
                                    <th style="padding:9px 12px; text-align:right;">จำนวน</th>
                                    <th style="padding:9px 18px;">เวลา</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($walletTransactions as $tx)
                                    <tr style="box-shadow:inset 0 1px 0 color-mix(in srgb, var(--ink2) 14%, transparent);">
                                        <td style="padding:9px 18px;">{{ $refTypeLabels[$tx->reference_type] ?? $tx->reference_type }} <span style="font-size:11px; color:var(--ink2);">({{ $tx->type }})</span></td>
                                        <td style="padding:9px 12px;">วอลเลต {{ $txUsers[$tx->user_id] ?? '#'.$tx->user_id }}</td>
                                        <td style="padding:9px 12px; text-align:right;" class="tp-num">฿{{ number_format((float) $tx->amount, 2) }}</td>
                                        <td style="padding:9px 18px; color:var(--ink2); white-space:nowrap;">{{ $tx->created_at?->thaidate('j M H:i') }}</td>
                                    </tr>
                                @endforeach
                                @foreach ($platformTransactions as $tx)
                                    <tr style="box-shadow:inset 0 1px 0 color-mix(in srgb, var(--ink2) 14%, transparent);">
                                        <td style="padding:9px 18px;">{{ $platformLabels[$tx->sub_type] ?? $tx->sub_type }} <span style="font-size:11px; color:var(--ink2);">({{ $tx->type }})</span></td>
                                        <td style="padding:9px 12px;">กระเป๋าแพลตฟอร์ม</td>
                                        <td style="padding:9px 12px; text-align:right;" class="tp-num">฿{{ number_format((float) $tx->amount, 2) }}</td>
                                        <td style="padding:9px 18px; color:var(--ink2); white-space:nowrap;">{{ $tx->created_at?->thaidate('j M H:i') }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
                @if ($gpDebt)
                    <div class="tp-well" style="margin:0 18px 18px; padding:10px 12px; font-size:12.5px; border-left:3px solid var(--w-warn);">
                        <i class="fas fa-file-invoice-dollar" style="color:var(--w-warn);"></i>
                        ค่า GP ค้างจากออเดอร์นี้ ฿{{ number_format((float) $gpDebt->original_amount, 2) }} · คงค้าง <b class="tp-num">฿{{ number_format((float) $gpDebt->remaining_amount, 2) }}</b> · {{ $gpDebt->status_label }}
                    </div>
                @endif
            </div>

            {{-- ประวัติสถานะ --}}
            <div class="tp-card" style="padding:20px;">
                <div class="tp-section-h" style="margin-bottom:12px;"><i class="fas fa-clock-rotate-left"></i> ประวัติการเปลี่ยนสถานะ</div>
                @forelse (array_reverse($history) as $entry)
                    <div style="display:flex; gap:12px; padding:9px 0; box-shadow:inset 0 -1px 0 color-mix(in srgb, var(--ink2) 12%, transparent);">
                        <span class="tp-num" style="font-size:11.5px; color:var(--ink2); width:92px; flex:none;">{{ isset($entry['at']) ? \Illuminate\Support\Carbon::parse($entry['at'])->timezone(config('app.timezone'))->format('d/m H:i:s') : '-' }}</span>
                        <div style="min-width:0; font-size:13px;">
                            <b>{{ \App\Models\FreshMarketOrder::actionLabel((string) ($entry['action'] ?? '-')) }}</b>
                            @if (! empty($entry['to']))
                                <span style="color:var(--ink2);">→ {{ \App\Models\FreshMarketOrder::statusLabel($entry['to']) }}</span>
                            @endif
                            <span style="color:var(--ink2);">โดย{{ $actorLabels[$entry['by'] ?? ''] ?? ($entry['by'] ?? '-') }}</span>
                            @if (! empty($entry['reason']))
                                <div style="font-size:12px; color:var(--ink2);">“{{ $entry['reason'] }}”</div>
                            @endif
                        </div>
                    </div>
                @empty
                    <p style="font-size:13px; color:var(--ink2); margin:0;">ยังไม่มีประวัติ</p>
                @endforelse
            </div>
        </div>

        {{-- ================= คอลัมน์ข้าง ================= --}}
        <div style="flex:1 1 300px; min-width:0; display:flex; flex-direction:column; gap:16px;">

            {{-- การจัดการ --}}
            <div class="tp-card" style="padding:18px;">
                <div class="tp-section-h" style="margin-bottom:12px;"><i class="fas fa-screwdriver-wrench"></i> จัดการออเดอร์</div>
                <div style="display:flex; flex-direction:column; gap:9px;">
                    @if (in_array('complete', $allowedActions, true))
                        <button type="button" class="tp-btn" style="width:100%; color:var(--w-on); background:linear-gradient(135deg, var(--w-ok), color-mix(in srgb, var(--w-ok) 72%, var(--ink)));"
                                @click="$dispatch('w1-action', @js(['url' => route('admin.fresh-market.orders.complete', $order), 'title' => 'ปิดออเดอร์แทนผู้ซื้อ', 'message' => 'ระบบจะปล่อยเงินให้ร้าน ฿'.number_format((float) $order->seller_earning, 2).' (หลังหัก GP) และจ่ายแคชแบ็ค/ค่าแนะนำตามเงื่อนไข — ทำซ้ำไม่ได้', 'reason' => 'optional', 'reasonLabel' => 'หมายเหตุ (บันทึกในประวัติ)', 'reasonValue' => 'แอดมินปิดออเดอร์แทนผู้ซื้อ', 'confirm' => 'ปิดออเดอร์', 'tone' => 'ok', 'icon' => 'fa-circle-check']))">
                            <i class="fas fa-circle-check"></i> ปิดออเดอร์ (ปล่อยเงินให้ร้าน)
                        </button>
                    @endif
                    @if ($showRedispatch)
                        <button type="button" class="tp-btn tp-btn-primary" style="width:100%;"
                                @click="$dispatch('w1-action', @js(['url' => route('admin.fresh-market.orders.redispatch', $order), 'title' => 'เรียกไรเดอร์ใหม่', 'message' => 'ระบบจะสร้างงานไรเดอร์ใหม่ให้ออเดอร์นี้และแจ้งไรเดอร์ใกล้ร้าน', 'reason' => 'none', 'confirm' => 'เรียกไรเดอร์ใหม่', 'tone' => 'gold', 'icon' => 'fa-motorcycle']))">
                            <i class="fas fa-motorcycle"></i> เรียกไรเดอร์ใหม่
                        </button>
                    @endif
                    @if (in_array('cancel', $allowedActions, true))
                        <button type="button" class="tp-btn" style="width:100%; color:var(--w-bad);"
                                @click="$dispatch('w1-action', @js(['url' => route('admin.fresh-market.orders.cancel', $order), 'title' => 'ยกเลิกออเดอร์ #'.$order->order_number, 'message' => $paidIntoSystem ? 'ระบบจะคืนเงินประมาณ ฿'.number_format($refundPreview, 2).' เข้า Wallet ผู้ซื้อ คืนสต็อกสินค้า และยกเลิกงานไรเดอร์ที่ยังไม่รับของ' : 'ออเดอร์นี้ยังไม่ได้เก็บเงินเข้าระบบ (เก็บเงินปลายทาง) จึงไม่มีการคืนเงิน — ระบบจะคืนสต็อกและยกเลิกงานไรเดอร์ที่ยังไม่รับของ', 'reason' => 'required', 'reasonLabel' => 'เหตุผลที่ยกเลิก (ผู้ซื้อและร้านจะเห็น)', 'confirm' => $paidIntoSystem ? 'ยกเลิกและคืนเงิน' : 'ยกเลิกออเดอร์', 'tone' => 'bad', 'icon' => 'fa-ban']))">
                            <i class="fas fa-ban"></i> {{ $paidIntoSystem ? 'ยกเลิก + คืนเงิน' : 'ยกเลิกออเดอร์' }}
                        </button>
                    @endif
                    @if (! in_array('complete', $allowedActions, true) && ! in_array('cancel', $allowedActions, true) && ! $showRedispatch)
                        <p style="font-size:13px; color:var(--ink2); margin:0;">ออเดอร์สถานะ "{{ $order->status_label }}" ไม่มีการกระทำที่แอดมินทำได้</p>
                    @endif
                </div>
                @if ($riderJob && in_array($riderJob->status, ['picked_up', 'delivering'], true))
                    <p style="font-size:11.5px; color:var(--w-warn); margin:12px 0 0;"><i class="fas fa-triangle-exclamation"></i> ไรเดอร์ถือของอยู่ — ถ้าต้องเปลี่ยนไรเดอร์ให้ทำที่หน้างานไรเดอร์</p>
                @endif
            </div>

            {{-- คู่ค้า --}}
            <div class="tp-card" style="padding:18px;">
                <div class="tp-section-h" style="margin-bottom:10px;"><i class="fas fa-user"></i> ผู้ซื้อ</div>
                <div style="font-weight:700;">{{ $order->buyer?->name ?? 'ผู้ใช้ที่ลบบัญชี' }} <span style="font-weight:500; color:var(--ink2); font-size:12px;">#{{ $order->buyer_id }}</span></div>
                @if ($order->buyer?->phone)<a href="tel:{{ $order->buyer->phone }}" class="w1-link" style="font-size:12.5px;">{{ $order->buyer->phone }}</a>@endif
                <div style="font-size:12px; color:var(--ink2);">{{ $order->buyer?->email }}</div>
                @if ($order->buyer_rating)
                    <div style="font-size:12.5px; margin-top:8px;"><i class="fas fa-star" style="color:var(--accent1);"></i> ให้คะแนนร้าน {{ $order->buyer_rating }}/5 @if ($order->buyer_review)— “{{ $order->buyer_review }}”@endif</div>
                @endif

                <div class="tp-divider" style="margin:14px 0;"></div>
                <div class="tp-section-h" style="margin-bottom:10px;"><i class="fas fa-store"></i> ร้าน</div>
                @if ($order->seller)
                    <a href="{{ route('admin.fresh-market.sellers.show', $order->seller_id) }}" class="w1-link" style="color:var(--ink);">{{ $order->seller->shop_name }}</a>
                    <div style="font-size:12px; color:var(--ink2);">เจ้าของ {{ $order->seller->user?->name ?? '-' }}</div>
                    @if ($order->seller->phone)<a href="tel:{{ $order->seller->phone }}" class="w1-link" style="font-size:12.5px;">{{ $order->seller->phone }}</a>@endif
                @else
                    <span style="color:var(--ink2);">-</span>
                @endif
            </div>

            {{-- เวลาสำคัญ --}}
            <div class="tp-card" style="padding:18px;">
                <div class="tp-section-h" style="margin-bottom:10px;"><i class="fas fa-clock"></i> เวลาสำคัญ</div>
                <div style="display:grid; grid-template-columns:auto 1fr; gap:6px 12px; font-size:12.5px;">
                    @foreach ($timeMarks as [$markColumn, $markLabel])
                        @continue(! $order->{$markColumn})
                        <span style="color:var(--ink2);">{{ $markLabel }}</span>
                        <span class="tp-num">{{ $order->{$markColumn}->thaidate('j M Y H:i') }}</span>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
