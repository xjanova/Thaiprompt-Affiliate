@extends('layouts.user-v4')

@section('title', 'กระเป๋าเงิน')

@php
    use Illuminate\Support\Str;

    // สีสถานะธุรกรรม (map จาก status_color → hex ใช้ใน V4 แทน dynamic tailwind class)
    $txColor = function ($c) {
        return match ($c) {
            'green' => '#5aa07e', 'yellow', 'amber' => '#e0a52e',
            'red', 'rose' => '#d9534f', 'blue', 'indigo' => '#5689b8',
            default => 'var(--ink2)',
        };
    };
    $incomeTypes = ['deposit', 'transfer_in', 'commission', 'refund', 'bonus'];
@endphp

@section('content')
<div style="display:flex; flex-direction:column; gap:18px;" x-data="{}">

    {{-- ── การ์ดยอดเงิน (Hero) ──────────────────────────────── --}}
    <div class="tp-card" style="padding:0; overflow:hidden;">
        <div style="padding:24px; background:linear-gradient(120deg, color-mix(in srgb, var(--accent1) 18%, transparent), transparent 72%);">
            <div style="display:flex; flex-wrap:wrap; align-items:flex-start; justify-content:space-between; gap:16px;">
                <div style="display:flex; align-items:center; gap:14px;">
                    <span class="tp-tile" style="width:54px; height:54px; border-radius:17px; font-size:24px;"><i class="fas fa-wallet" style="color:#fff;"></i></span>
                    <div>
                        <h1 style="font-size:clamp(19px,4vw,24px); font-weight:800; margin:0;">กระเป๋าเงินของฉัน</h1>
                        <div style="font-size:12.5px; color:var(--ink2); margin-top:2px;">จัดการการเงินของคุณ</div>
                    </div>
                </div>
                @if($wallet->wallet_address)
                <div style="min-width:0;">
                    <div style="font-size:11px; color:var(--ink2); margin-bottom:4px;">Wallet Address</div>
                    <div style="display:flex; align-items:center; gap:8px;">
                        <code class="tp-num" style="padding:7px 12px; border-radius:10px; box-shadow:var(--inset-sm); font-size:12px; color:var(--ink); max-width:240px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">{{ $wallet->wallet_address }}</code>
                        <button type="button" onclick="copyWalletAddress()" class="tp-icon-btn" title="คัดลอก"><i class="fas fa-copy"></i></button>
                    </div>
                </div>
                @endif
            </div>

            {{-- ยอดคงเหลือ --}}
            <div style="margin-top:18px; padding:20px 22px; border-radius:18px; box-shadow:var(--inset);">
                <div style="font-size:12.5px; color:var(--ink2);">ยอดเงินคงเหลือ</div>
                <div class="tp-num" style="font-size:clamp(34px,7vw,52px); font-weight:800; line-height:1.1; margin-top:4px; color:var(--deep1);">฿{{ number_format($wallet->balance, 2) }}</div>
                <div style="font-size:12px; color:var(--ink2); margin-top:4px;">{{ $wallet->currency }}</div>
            </div>
        </div>
    </div>

    {{-- ── เมนูด่วน ──────────────────────────────────────────── --}}
    @php
        $walletActions = [
            ['💵', 'ฝากเงิน', 'Deposit', 'user.wallet.deposit'],
            ['💸', 'ถอนเงิน', 'Withdraw', 'user.wallet.withdraw'],
            ['📤', 'โอนเงิน', 'Transfer', 'user.wallet.transfer'],
            ['📝', 'ประวัติ', 'History', 'user.wallet.transactions'],
            ['🔳', 'QR Code ของฉัน', 'รับเงิน', 'user.wallet.qr-code'],
            ['📷', 'สแกนจ่าย', 'โอนเงิน', 'user.wallet.qr-transfer'],
        ];
    @endphp
    <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(140px,1fr)); gap:12px;">
        @foreach($walletActions as [$emoji, $label, $sub, $route])
            @if(\Illuminate\Support\Facades\Route::has($route))
                <a href="{{ route($route) }}" class="tp-card tp-card-hover" style="display:flex; flex-direction:column; align-items:center; gap:7px; padding:16px 10px; text-decoration:none; color:inherit;">
                    <span class="tp-tile" style="width:46px; height:46px; border-radius:15px; font-size:21px;">{{ $emoji }}</span>
                    <span style="font-size:13px; font-weight:700; text-align:center;">{{ $label }}</span>
                    <span style="font-size:10px; color:var(--ink2);">{{ $sub }}</span>
                </a>
            @endif
        @endforeach
    </div>

    {{-- ── เตือนหนี้ค้างชำระ ─────────────────────────────────── --}}
    @if(isset($debtSummary) && ($debtSummary['has_active_debt'] ?? false))
    <a href="{{ route('user.wallet.debts') }}" class="tp-card tp-card-hover" style="display:flex; flex-wrap:wrap; align-items:center; gap:14px; padding:16px 18px; text-decoration:none; color:inherit; box-shadow:var(--inset-sm); border-left:4px solid #d9534f;">
        <span class="tp-tile" style="width:44px; height:44px; border-radius:13px; font-size:18px; background:#d9534f;"><i class="fas fa-file-invoice-dollar" style="color:#fff;"></i></span>
        <div style="flex:1; min-width:180px;">
            <div style="font-weight:700; font-size:14px;">คุณมีหนี้ค้างชำระ</div>
            <div style="font-size:12px; color:var(--ink2); margin-top:2px;">{{ $debtSummary['debt_count'] ?? 0 }} รายการ · ระบบจะหักจากรายได้อัตโนมัติ 50%</div>
        </div>
        <div style="text-align:right;">
            <div class="tp-num" style="font-size:22px; font-weight:800; color:#d9534f;">฿{{ number_format($debtSummary['total_debt'] ?? 0, 2) }}</div>
            <div style="font-size:11px; color:var(--ink2);">ดูรายละเอียด →</div>
        </div>
    </a>
    @endif

    {{-- ── สถิติ 4 ใบ ───────────────────────────────────────── --}}
    @php
        $wStats = [
            ['รายรับทั้งหมด', '฿' . number_format($wallet->total_income, 2), 'fa-arrow-down', '#5aa07e'],
            ['รายจ่ายทั้งหมด', '฿' . number_format($wallet->total_expense, 2), 'fa-arrow-up', '#d9534f'],
            ['ยอดเงินคงเหลือ', '฿' . number_format($wallet->balance, 2), 'fa-wallet', '#5689b8'],
            ['Cashback ทั้งหมด', '฿' . number_format($cashbackStats['total'] ?? 0, 2), 'fa-gift', '#e0a52e'],
        ];
    @endphp
    <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(200px,1fr)); gap:16px;">
        @foreach($wStats as [$label, $val, $icon, $color])
            <div class="tp-card" style="padding:18px;">
                <div style="display:flex; align-items:center; justify-content:space-between; gap:8px;">
                    <div style="font-size:12.5px; color:var(--ink2); font-weight:600;">{{ $label }}</div>
                    <span class="tp-tile" style="width:38px; height:38px; border-radius:11px; font-size:15px; background:color-mix(in srgb, {{ $color }} 18%, transparent);"><i class="fas {{ $icon }}" style="color:{{ $color }};"></i></span>
                </div>
                <div class="tp-num" style="font-size:24px; font-weight:800; margin-top:10px;">{{ $val }}</div>
                @if($label === 'Cashback ทั้งหมด')
                    <div style="font-size:11px; color:var(--ink2); margin-top:2px;">{{ $cashbackStats['count'] ?? 0 }} ครั้ง</div>
                @endif
            </div>
        @endforeach
    </div>

    {{-- ── เงินจากแอดมิน/ระบบ ────────────────────────────────── --}}
    @if(isset($adminStats) && ($adminStats['total'] ?? 0) > 0)
    <div class="tp-card" style="padding:20px;">
        <div style="display:flex; align-items:center; gap:10px;">
            <span class="tp-tile" style="width:42px; height:42px; border-radius:13px; font-size:18px;">⚡</span>
            <div>
                <div style="font-size:12.5px; color:var(--ink2);">เงินที่ได้จากแอดมิน/ระบบ</div>
                <div class="tp-num" style="font-size:28px; font-weight:800; color:var(--deep1);">฿{{ number_format($adminStats['total'] ?? 0, 2) }}</div>
            </div>
            <span class="tp-pill tp-pill-soft" style="margin-left:auto;">{{ $adminStats['count'] ?? 0 }} ครั้ง</span>
        </div>
        <div style="display:grid; grid-template-columns:1fr 1fr; gap:12px; margin-top:14px; padding-top:14px; border-top:1px solid color-mix(in srgb, var(--ink2) 13%, transparent);">
            <div><div style="font-size:11px; color:var(--ink2);">เดือนนี้</div><div class="tp-num" style="font-weight:700;">฿{{ number_format($adminStats['this_month'] ?? 0, 2) }}</div></div>
            <div><div style="font-size:11px; color:var(--ink2);">30 วันล่าสุด</div><div class="tp-num" style="font-weight:700;">฿{{ number_format($adminStats['last_30_days'] ?? 0, 2) }}</div></div>
        </div>
    </div>
    @endif

    {{-- ── สถิติ Cashback ─────────────────────────────────────── --}}
    @if(isset($cashbackStats) && ($cashbackStats['total'] ?? 0) > 0)
    <div class="tp-card" style="padding:18px;">
        <div class="tp-section-h" style="margin-bottom:14px;">💸 สถิติ Cashback</div>
        <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(150px,1fr)); gap:12px;">
            <div style="padding:13px; border-radius:13px; box-shadow:var(--inset-sm);"><div style="font-size:12px; color:var(--ink2);">Cashback เดือนนี้</div><div class="tp-num" style="font-size:20px; font-weight:800; color:#5aa07e;">฿{{ number_format($cashbackStats['this_month'] ?? 0, 2) }}</div></div>
            <div style="padding:13px; border-radius:13px; box-shadow:var(--inset-sm);"><div style="font-size:12px; color:var(--ink2);">30 วันล่าสุด</div><div class="tp-num" style="font-size:20px; font-weight:800; color:#5aa07e;">฿{{ number_format($cashbackStats['last_30_days'] ?? 0, 2) }}</div></div>
            <div style="padding:13px; border-radius:13px; box-shadow:var(--inset-sm);"><div style="font-size:12px; color:var(--ink2);">Cashback เฉลี่ย</div><div class="tp-num" style="font-size:20px; font-weight:800; color:#5aa07e;">฿{{ ($cashbackStats['count'] ?? 0) > 0 ? number_format($cashbackStats['total'] / $cashbackStats['count'], 2) : '0.00' }}</div></div>
        </div>
    </div>
    @endif

    {{-- ── สถิติเพิ่มเติม ──────────────────────────────────────── --}}
    @if(isset($statistics) && !empty($statistics))
    <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(170px,1fr)); gap:12px;">
        @foreach($statistics as $key => $stat)
            <div class="tp-card" style="padding:15px; display:flex; align-items:center; gap:11px;">
                <span class="tp-tile" style="width:40px; height:40px; border-radius:12px; font-size:18px;">{{ is_array($stat) ? ($stat['icon'] ?? '📊') : '📊' }}</span>
                <div style="min-width:0;">
                    <div style="font-size:11px; color:var(--ink2);">{{ is_array($stat) ? ($stat['label'] ?? Str::headline((string) $key)) : Str::headline((string) $key) }}</div>
                    <div class="tp-num" style="font-size:18px; font-weight:800;">{{ is_array($stat) ? ($stat['value'] ?? '0') : $stat }}</div>
                </div>
            </div>
        @endforeach
    </div>
    @endif

    {{-- ── รายการธุรกรรมล่าสุด ──────────────────────────────── --}}
    <div class="tp-card" style="padding:0; overflow:hidden;">
        <div style="display:flex; align-items:center; justify-content:space-between; gap:10px; padding:18px 20px; border-bottom:1px solid color-mix(in srgb, var(--ink2) 13%, transparent);">
            <div>
                <div class="tp-section-h">🕑 รายการธุรกรรมล่าสุด</div>
                <div style="font-size:11px; color:var(--ink2); margin-top:2px;">10 รายการล่าสุด</div>
            </div>
            <a href="{{ route('user.wallet.transactions') }}" class="tp-btn tp-btn-sm">ดูทั้งหมด →</a>
        </div>
        <div style="overflow-x:auto;">
            <table style="width:100%; border-collapse:collapse; font-size:13px;">
                <thead>
                    <tr style="text-align:left; color:var(--ink2); box-shadow:var(--inset-sm);">
                        <th style="padding:12px 16px; font-size:10.5px; font-weight:700; text-transform:uppercase; letter-spacing:.4px;">ประเภท</th>
                        <th style="padding:12px 16px; font-size:10.5px; font-weight:700; text-transform:uppercase; letter-spacing:.4px;">รายละเอียด</th>
                        <th style="padding:12px 16px; font-size:10.5px; font-weight:700; text-transform:uppercase; letter-spacing:.4px; text-align:right;">จำนวนเงิน</th>
                        <th style="padding:12px 16px; font-size:10.5px; font-weight:700; text-transform:uppercase; letter-spacing:.4px; text-align:center;">สถานะ</th>
                        <th style="padding:12px 16px; font-size:10.5px; font-weight:700; text-transform:uppercase; letter-spacing:.4px; text-align:right;">วันที่</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($recentTransactions as $transaction)
                        <tr style="border-top:1px solid color-mix(in srgb, var(--ink2) 13%, transparent);">
                            <td style="padding:12px 16px; white-space:nowrap;">
                                <div style="display:flex; align-items:center; gap:8px;">
                                    <span style="font-size:18px;">{{ $transaction->type_icon }}</span>
                                    <span style="font-size:12.5px; font-weight:600;">{{ $transaction->type_label }}</span>
                                </div>
                            </td>
                            <td style="padding:12px 16px;">
                                <div style="font-size:12.5px; font-weight:600;">{{ $transaction->description }}</div>
                                @if($transaction->relatedWallet)
                                    <div style="font-size:11px; color:var(--ink2);">{{ $transaction->type === 'transfer_in' ? 'จาก' : 'ถึง' }}: {{ $transaction->relatedWallet->user->name ?? '—' }}</div>
                                @endif
                                <div class="tp-num" style="font-size:10.5px; color:var(--ink2);">ID: {{ $transaction->transaction_id }}</div>
                            </td>
                            <td class="tp-num" style="padding:12px 16px; text-align:right; white-space:nowrap; font-weight:700; color:{{ in_array($transaction->type, $incomeTypes) ? '#5aa07e' : '#d9534f' }};">{{ $transaction->formatted_amount }}</td>
                            <td style="padding:12px 16px; text-align:center; white-space:nowrap;">
                                <span class="tp-pill" style="color:{{ $txColor($transaction->status_color) }}; background:color-mix(in srgb, {{ $txColor($transaction->status_color) }} 16%, transparent);">{{ $transaction->status_label }}</span>
                            </td>
                            <td class="tp-num" style="padding:12px 16px; text-align:right; white-space:nowrap;">
                                <div>{{ $transaction->created_at->format('d/m/Y') }}</div>
                                <div style="font-size:11px; color:var(--ink2);">{{ $transaction->created_at->format('H:i') }}</div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" style="padding:48px 20px; text-align:center; color:var(--ink2);"><div style="font-size:46px; opacity:.5;">📭</div><div style="margin-top:8px; font-weight:600;">ยังไม่มีรายการธุรกรรม</div></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- ── ช่องทางรับเงิน ─────────────────────────────────────── --}}
    @if($paymentMethods->isNotEmpty())
    <div class="tp-card" style="padding:18px;">
        <div style="display:flex; align-items:center; justify-content:space-between; gap:10px; margin-bottom:14px;">
            <div>
                <div class="tp-section-h">🏦 ช่องทางรับเงิน</div>
                <div style="font-size:11px; color:var(--ink2); margin-top:2px;">บัญชีสำหรับถอนเงิน</div>
            </div>
            <a href="{{ route('user.wallet.payment-methods') }}" class="tp-btn tp-btn-sm">จัดการ →</a>
        </div>
        <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(220px,1fr)); gap:12px;">
            @foreach($paymentMethods as $method)
                <div style="padding:14px; border-radius:14px; box-shadow:var(--inset-sm);">
                    <div style="display:flex; align-items:center; justify-content:space-between; gap:8px; margin-bottom:8px;">
                        <div style="display:flex; align-items:center; gap:8px;">
                            <span style="font-size:18px;">@if($method->type === 'promptpay')💳@elseif($method->type === 'bank_transfer')🏦@elseif($method->type === 'paypal')💰@else💵@endif</span>
                            <span style="font-size:13px; font-weight:700;">{{ $method->name }}</span>
                        </div>
                        @if($method->is_default)<span class="tp-pill" style="color:#fff; background:#5aa07e;">ค่าเริ่มต้น</span>@endif
                    </div>
                    @if($method->type === 'bank_transfer')
                        <div style="font-size:11px; color:var(--ink2);">{{ $method->bank_name }}</div>
                        <div style="font-size:13px; font-weight:600;">{{ $method->account_name }}</div>
                        <div class="tp-num" style="font-size:12px; color:var(--ink2);">{{ $method->account_number }}</div>
                    @elseif($method->type === 'promptpay')
                        <div style="font-size:13px; font-weight:600;">{{ $method->account_name }}</div>
                        <div class="tp-num" style="font-size:12px; color:var(--ink2);">{{ $method->account_number }}</div>
                    @elseif($method->type === 'paypal')
                        <div style="font-size:13px; font-weight:600;">{{ $method->paypal_email }}</div>
                    @endif
                </div>
            @endforeach
        </div>
    </div>
    @endif

    {{-- ── ช่องทางการฝากเงิน ─────────────────────────────────── --}}
    @if(isset($availableGateways) && !empty($availableGateways))
    <div class="tp-card" style="padding:18px;">
        <div class="tp-section-h" style="margin-bottom:14px;">💳 ช่องทางการฝากเงิน</div>
        <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(130px,1fr)); gap:12px;">
            @foreach($availableGateways as $gateway)
                <div style="padding:15px 10px; border-radius:13px; box-shadow:var(--inset-sm); text-align:center;">
                    <div style="font-size:28px;">{{ $gateway['icon'] ?? '💰' }}</div>
                    <div style="font-size:12.5px; font-weight:600; margin-top:4px;">{{ $gateway['name'] ?? 'Gateway' }}</div>
                </div>
            @endforeach
        </div>
    </div>
    @endif

    {{-- ── แจ้งกระเป๋าถูกล็อก ─────────────────────────────────── --}}
    @if($wallet->isLocked())
    <div class="tp-card" style="padding:16px 18px; display:flex; align-items:center; gap:14px; box-shadow:var(--inset-sm); border-left:4px solid #d9534f;">
        <span class="tp-tile" style="width:44px; height:44px; border-radius:13px; font-size:20px; background:#d9534f;">🔒</span>
        <div>
            <div style="font-weight:700;">กระเป๋าเงินของคุณถูกล็อก</div>
            <div style="font-size:12.5px; color:var(--ink2);">กรุณาติดต่อฝ่ายสนับสนุนเพื่อปลดล็อก</div>
            @if($wallet->locked_until)<div style="font-size:11px; color:var(--ink2); margin-top:2px;">ล็อกจนถึง: {{ $wallet->locked_until->format('d/m/Y H:i') }}</div>@endif
        </div>
    </div>
    @endif
</div>

@push('scripts')
<script>
function copyWalletAddress() {
    var addr = @json($wallet->wallet_address ?? '');
    if (!addr) return;
    navigator.clipboard.writeText(addr).then(function () {
        if (window.showNotification) window.showNotification('คัดลอก Wallet Address สำเร็จ!', 'success');
    }).catch(function () {
        if (window.showNotification) window.showNotification('คัดลอกไม่สำเร็จ', 'error');
    });
}
</script>
@endpush
@endsection
