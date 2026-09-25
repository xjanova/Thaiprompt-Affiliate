@extends('layouts.seller-v4')

@section('title', 'รายได้จากการขาย / ค่าแนะนำ')

@push('styles')
    @include('seller.partials.v4-styles')
@endpush

@php
    use App\Support\Seller\SellerUi;

    $summary = $earningsSummary ?? ['pending_amount' => 0, 'pending_count' => 0, 'waiting_delivery_count' => 0, 'next_release_at' => null, 'paid_amount' => 0, 'paid_count' => 0, 'holding_days' => 0];
    $referral = $referralSummary ?? ['pending_amount' => 0, 'paid_amount' => 0, 'count' => 0];
    $tabs = ['sale' => '🛒 รายได้จากการขาย', 'referral' => '🤝 ค่าแนะนำ', 'all' => 'ทั้งหมด'];
    $currentType = $type ?? 'sale';
@endphp

@section('content')
<div class="sv4-page">

    <x-seller-v4.header title="รายได้จากการขาย / ค่าแนะนำ" subtitle="ทุกบาทที่ร้านได้รับ แยกค่า GP, VAT และค่าแนะนำให้เห็นชัด" icon="💵">
        <a href="{{ route('seller.wallet.index') }}" class="tp-btn tp-btn-sm">👛 กระเป๋าเงิน</a>
        <a href="{{ route('seller.wallet.withdraw') }}" class="tp-btn tp-btn-sm tp-btn-primary">💸 ถอนเงิน</a>
    </x-seller-v4.header>

    <div class="sv4-stats">
        <x-seller-v4.stat label="รายได้รอโอน" :value="'฿' . number_format((float) $summary['pending_amount'], 2)" icon="⏳" :color="SellerUi::WARN"
                          :hint="number_format((int) $summary['pending_count']) . ' รายการ' . ((int) $summary['waiting_delivery_count'] > 0 ? ' · รอส่งถึง ' . (int) $summary['waiting_delivery_count'] : '')" />
        <x-seller-v4.stat label="โอนเข้ากระเป๋าแล้ว" :value="'฿' . number_format((float) $summary['paid_amount'], 2)" icon="✅" :color="SellerUi::OK"
                          :hint="number_format((int) $summary['paid_count']) . ' รายการ'" />
        <x-seller-v4.stat label="ค่าแนะนำรอโอน" :value="'฿' . number_format((float) $referral['pending_amount'], 2)" icon="🤝" :color="SellerUi::INFO" />
        <x-seller-v4.stat label="ค่าแนะนำที่ได้รับแล้ว" :value="'฿' . number_format((float) $referral['paid_amount'], 2)" icon="🎁" :color="SellerUi::VIOLET" />
    </div>

    <div class="sv4-note" style="--c:var(--accent1);">
        💡 รายได้จากการขายเข้ากระเป๋าอัตโนมัติหลังลูกค้าได้รับสินค้า + พักเงิน {{ (int) $summary['holding_days'] }} วัน
        @if(! empty($summary['next_release_at']))
            · รอบถัดไปประมาณ <b>{{ \Illuminate\Support\Carbon::parse($summary['next_release_at'])->timezone(config('app.timezone'))->format('d/m/Y H:i') }}</b>
        @endif
    </div>

    <nav class="sv4-tabs" aria-label="ประเภทรายได้">
        @foreach($tabs as $key => $label)
            <a href="{{ route('seller.commissions', ['type' => $key]) }}" class="sv4-tab {{ $currentType === $key ? 'on' : '' }}">{{ $label }}</a>
        @endforeach
    </nav>

    <div class="tp-card" style="padding:0; overflow:hidden;">
        @if($earnings->count() > 0)
            <div class="sv4-table-wrap">
                <table class="sv4-table">
                    <thead>
                        <tr>
                            <th>รายการ</th>
                            <th style="text-align:right;">ยอดขาย</th>
                            <th class="sv4-hide-sm" style="text-align:right;">ค่า GP</th>
                            <th class="sv4-hide-sm" style="text-align:right;">VAT</th>
                            <th class="sv4-hide-sm" style="text-align:right;">ค่าแนะนำ</th>
                            <th style="text-align:right;">ร้านได้สุทธิ</th>
                            <th style="text-align:center;">สถานะ</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($earnings as $row)
                            @php
                                $isSale = $row->earning_type === \App\Models\EarningsLedger::TYPE_SELLER_SALE;
                                $statusColor = SellerUi::earningStatusColor($row->status);
                            @endphp
                            <tr>
                                <td>
                                    <div style="font-weight:700; overflow-wrap:anywhere;">{{ $row->description ?: ($isSale ? 'รายได้จากการขาย' : 'ค่าแนะนำ') }}</div>
                                    <div style="font-size:11px; color:var(--ink2);">
                                        <span class="sv4-pill" style="{{ SellerUi::pill($isSale ? SellerUi::GOLD : SellerUi::INFO) }} padding:3px 8px;">{{ $isSale ? 'ขายสินค้า' : 'ค่าแนะนำ' }}</span>
                                        <span class="tp-num">{{ $row->created_at?->format('d/m/Y H:i') }}</span>
                                    </div>
                                </td>
                                <td class="tp-num" style="text-align:right; white-space:nowrap;">฿{{ number_format((float) $row->gross_amount, 2) }}</td>
                                <td class="sv4-hide-sm tp-num" style="text-align:right; white-space:nowrap; color:{{ (float) $row->platform_fee > 0 ? SellerUi::BAD : 'var(--ink2)' }};">
                                    {{ (float) $row->platform_fee > 0 ? '−฿' . number_format((float) $row->platform_fee, 2) : '—' }}
                                </td>
                                <td class="sv4-hide-sm tp-num" style="text-align:right; white-space:nowrap; color:{{ (float) $row->vat_amount > 0 ? SellerUi::BAD : 'var(--ink2)' }};">
                                    {{ (float) $row->vat_amount > 0 ? '−฿' . number_format((float) $row->vat_amount, 2) : '—' }}
                                </td>
                                <td class="sv4-hide-sm tp-num" style="text-align:right; white-space:nowrap; color:{{ (float) $row->mlm_commission > 0 ? SellerUi::BAD : 'var(--ink2)' }};">
                                    {{ (float) $row->mlm_commission > 0 ? '−฿' . number_format((float) $row->mlm_commission, 2) : '—' }}
                                </td>
                                <td class="tp-num" style="text-align:right; white-space:nowrap; font-weight:800; color:{{ SellerUi::OK }};">฿{{ number_format((float) $row->net_amount, 2) }}</td>
                                <td style="text-align:center;">
                                    <span class="sv4-pill" style="{{ SellerUi::pill($statusColor) }}">{{ $row->status_label }}</span>
                                    @if($row->status === 'pending' && $row->available_at)
                                        <div style="font-size:10.5px; color:var(--ink2); margin-top:3px;">โอน {{ $row->available_at->format('d/m') }}</div>
                                    @elseif($row->paid_at)
                                        <div style="font-size:10.5px; color:var(--ink2); margin-top:3px;">{{ $row->paid_at->format('d/m/Y') }}</div>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div style="padding:14px 18px; border-top:1px solid color-mix(in srgb, var(--ink2) 13%, transparent);">
                {{ $earnings->links('vendor.pagination.tp-v4') }}
            </div>
        @else
            <x-seller-v4.empty icon="💵" :title="$currentType === 'referral' ? 'ยังไม่มีค่าแนะนำ' : 'ยังไม่มีรายได้'"
                               :text="$currentType === 'referral' ? 'เมื่อมีคนซื้อสินค้าผ่านการแนะนำของคุณ ค่าแนะนำจะแสดงที่นี่' : 'เมื่อมีออเดอร์ที่ชำระเงินแล้ว รายได้แต่ละออเดอร์จะแสดงที่นี่ พร้อมรายละเอียดค่าธรรมเนียม'">
                <a href="{{ route('seller.products.index') }}" class="tp-btn tp-btn-sm">📦 จัดการสินค้า</a>
            </x-seller-v4.empty>
        @endif
    </div>
</div>
@endsection
