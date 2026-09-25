@extends('layouts.seller-v4')

@section('title', 'กระเป๋าเงิน')

@push('styles')
    @include('seller.partials.v4-styles')
@endpush

@php
    use App\Support\Seller\SellerUi;

    // ประเภทธุรกรรมที่เป็นเงินเข้า (wallet_transactions.type) — เดิมเช็ค 'income' ซึ่งไม่มีอยู่จริง ทุกรายการเลยแสดงเป็นเงินออก
    $incomeTypes = ['deposit', 'transfer_in', 'commission', 'refund', 'bonus', 'cashback'];
    $summary = $earningsSummary ?? null;
    $quickActions = [
        ['seller.wallet.withdraw', '💸', 'ถอนเงิน'],
        ['seller.wallet.withdrawals', '📋', 'ประวัติถอนเงิน'],
        ['seller.commissions', '💵', 'รายได้จากการขาย'],
        ['seller.reports.sales', '📊', 'รายงานยอดขาย'],
    ];
@endphp

@section('content')
<div class="sv4-page">

    {{-- ── ยอดเงินคงเหลือ ─────────────────────────────────── --}}
    <div class="tp-card" style="padding:24px 26px; background:linear-gradient(120deg, color-mix(in srgb, var(--accent1) 20%, transparent), transparent 72%);">
        <div style="display:flex; flex-wrap:wrap; align-items:center; justify-content:space-between; gap:16px;">
            <div style="display:flex; align-items:center; gap:12px;">
                <span class="tp-tile" style="width:52px; height:52px; border-radius:16px; font-size:22px;"><i class="fas fa-wallet"></i></span>
                <div>
                    <div style="font-size:11px; color:var(--ink2); font-weight:600;">ร้านค้าของฉัน</div>
                    <h1 style="font-size:clamp(19px,3.5vw,24px); font-weight:800; margin:2px 0 0;">กระเป๋าเงินของร้าน</h1>
                </div>
            </div>
            <div style="display:flex; flex-wrap:wrap; gap:8px;">
                <a href="{{ route('seller.wallet.withdraw') }}" class="tp-btn tp-btn-primary">💸 ถอนเงิน</a>
            </div>
        </div>
        <div class="sv4-well" style="margin-top:16px; padding:20px 22px;">
            <div style="font-size:12px; color:var(--ink2);">ยอดเงินคงเหลือ (ถอนได้)</div>
            <div class="tp-num" style="font-size:clamp(34px,7vw,50px); font-weight:800; line-height:1.1; color:var(--deep1);">฿{{ number_format((float) $balance, 2) }}</div>
        </div>
    </div>

    @if(isset($pendingWithdrawals) && $pendingWithdrawals > 0)
        <div class="sv4-note" style="--c:{{ SellerUi::WARN }};">
            ⏳ มีคำขอถอนเงิน <b class="tp-num">฿{{ number_format((float) $pendingWithdrawals, 2) }}</b> กำลังรอดำเนินการ
            · <a href="{{ route('seller.wallet.withdrawals') }}" class="sv4-link">ดูรายละเอียด</a>
        </div>
    @endif

    {{-- ── รายได้จากการขาย (รอโอน / โอนแล้ว) ─────────────────── --}}
    @if($summary)
        <div class="sv4-stats">
            <x-seller-v4.stat label="รายได้รอโอน" :value="'฿' . number_format((float) $summary['pending_amount'], 2)" icon="⏳" :color="SellerUi::WARN"
                              :hint="number_format((int) $summary['pending_count']) . ' ออเดอร์' . ((int) $summary['waiting_delivery_count'] > 0 ? ' · รอส่งถึง ' . (int) $summary['waiting_delivery_count'] : '')"
                              :href="route('seller.commissions')" />
            <x-seller-v4.stat label="โอนเข้ากระเป๋าแล้ว" :value="'฿' . number_format((float) $summary['paid_amount'], 2)" icon="✅" :color="SellerUi::OK"
                              :hint="number_format((int) $summary['paid_count']) . ' ออเดอร์'" :href="route('seller.commissions')" />
        </div>
        <div class="sv4-note" style="--c:var(--accent1);">
            💡 รายได้จากการขายเข้ากระเป๋าอัตโนมัติหลังลูกค้าได้รับสินค้า + พักเงิน {{ (int) $summary['holding_days'] }} วัน
            @if(! empty($summary['next_release_at']))
                · รอบถัดไปประมาณ <b>{{ \Illuminate\Support\Carbon::parse($summary['next_release_at'])->timezone(config('app.timezone'))->format('d/m/Y H:i') }}</b>
            @endif
        </div>
    @endif

    {{-- ── เมนูด่วน ─────────────────────────────────────────── --}}
    <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(140px, 1fr)); gap:14px;">
        @foreach($quickActions as [$routeName, $icon, $label])
            @if(\Illuminate\Support\Facades\Route::has($routeName))
                <a href="{{ route($routeName) }}" class="tp-card tp-card-hover" style="padding:18px 14px; text-align:center; text-decoration:none; color:var(--ink);">
                    <div style="font-size:30px;">{{ $icon }}</div>
                    <div style="font-weight:800; font-size:13px; margin-top:6px;">{{ $label }}</div>
                </a>
            @endif
        @endforeach
    </div>

    {{-- ── รายได้ล่าสุดจากการขาย ─────────────────────────────── --}}
    @if(isset($recentEarnings) && $recentEarnings->count() > 0)
        <div class="tp-card" style="padding:0; overflow:hidden;">
            <div class="sv4-row" style="padding:16px 20px;">
                <div class="sv4-h2">🛒 รายได้จากการขายล่าสุด</div>
                <a href="{{ route('seller.commissions') }}" class="tp-btn tp-btn-sm">ดูทั้งหมด →</a>
            </div>
            <div class="sv4-table-wrap">
                <table class="sv4-table">
                    <thead><tr><th>รายการ</th><th style="text-align:right;">ยอดขาย</th><th class="sv4-hide-sm" style="text-align:right;">หัก</th><th style="text-align:right;">สุทธิ</th><th style="text-align:center;">สถานะ</th></tr></thead>
                    <tbody>
                        @foreach($recentEarnings as $earning)
                            @php
                                $deduct = (float) $earning->platform_fee + (float) $earning->vat_amount + (float) $earning->mlm_commission;
                            @endphp
                            <tr>
                                <td>
                                    <div style="font-weight:700; overflow-wrap:anywhere;">{{ $earning->description ?: 'รายได้จากการขาย' }}</div>
                                    <div class="tp-num" style="font-size:11px; color:var(--ink2);">{{ $earning->created_at?->format('d/m/Y H:i') }}</div>
                                </td>
                                <td class="tp-num" style="text-align:right; white-space:nowrap;">฿{{ number_format((float) $earning->gross_amount, 2) }}</td>
                                <td class="sv4-hide-sm tp-num" style="text-align:right; white-space:nowrap; color:{{ $deduct > 0 ? SellerUi::BAD : 'var(--ink2)' }};">{{ $deduct > 0 ? '−฿' . number_format($deduct, 2) : '—' }}</td>
                                <td class="tp-num" style="text-align:right; white-space:nowrap; font-weight:800; color:{{ SellerUi::OK }};">฿{{ number_format((float) $earning->net_amount, 2) }}</td>
                                <td style="text-align:center;"><span class="sv4-pill" style="{{ SellerUi::pill(SellerUi::earningStatusColor($earning->status)) }}">{{ $earning->status_label }}</span></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    {{-- ── ธุรกรรมล่าสุดในกระเป๋า ───────────────────────────── --}}
    <div class="tp-card" style="padding:20px;">
        <div class="sv4-h2">📝 ธุรกรรมล่าสุดในกระเป๋า</div>
        @if(empty($transactions) || count($transactions) === 0)
            <x-seller-v4.empty icon="💼" title="ยังไม่มีธุรกรรม" text="เงินเข้า-ออกของกระเป๋าร้านจะแสดงที่นี่" />
        @else
            <div style="display:flex; flex-direction:column; margin-top:8px;">
                @foreach($transactions as $transaction)
                    @php
                        $isIncome = in_array($transaction->type, $incomeTypes, true);
                    @endphp
                    <div style="padding:13px 0; display:flex; align-items:center; justify-content:space-between; gap:12px; {{ ! $loop->last ? 'border-bottom:1px solid color-mix(in srgb, var(--ink2) 15%, transparent);' : '' }}">
                        <div style="display:flex; align-items:center; gap:12px; min-width:0;">
                            <span style="width:42px; height:42px; border-radius:50%; display:grid; place-items:center; font-size:18px; flex:none; background:color-mix(in srgb, {{ $isIncome ? SellerUi::OK : SellerUi::BAD }} 14%, transparent);">{{ $transaction->type_icon }}</span>
                            <div style="min-width:0;">
                                <div style="font-weight:700; font-size:13.5px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">{{ $transaction->description ?: $transaction->type_label }}</div>
                                <div style="font-size:11.5px; color:var(--ink2);">{{ $transaction->type_label }} · <span class="tp-num">{{ $transaction->created_at->format('d/m/Y H:i') }}</span></div>
                            </div>
                        </div>
                        <div style="text-align:right; flex:none;">
                            <div class="tp-num" style="font-weight:800; font-size:14.5px; color:{{ $isIncome ? SellerUi::OK : SellerUi::BAD }};">
                                {{ $isIncome ? '+' : '−' }}฿{{ number_format(abs((float) $transaction->amount), 2) }}
                            </div>
                            <div style="font-size:11px; color:var(--ink2);">{{ $transaction->status_label }}</div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</div>
@endsection
