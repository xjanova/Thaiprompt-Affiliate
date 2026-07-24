@extends('layouts.user-v4')

@section('title', 'TPIX Wallet - กระเป๋าเงิน TPIX')

@section('content')
<div style="display:flex; flex-direction:column; gap:18px;">

    {{-- ── Hero ─────────────────────────────────────────────── --}}
    <div class="tp-card" style="padding:28px; background:linear-gradient(120deg, color-mix(in srgb, var(--accent1) 18%, transparent), transparent 72%);">
        <div style="display:flex; flex-wrap:wrap; align-items:center; justify-content:space-between; gap:14px; margin-bottom:20px;">
            <div>
                <div style="display:flex; align-items:center; gap:12px; margin-bottom:10px;">
                    <span class="tp-tile" style="width:48px; height:48px; border-radius:50%; display:flex; align-items:center; justify-content:center; font-size:20px; color:var(--deep1);"><i class="fas fa-wallet"></i></span>
                    <h1 style="font-size:clamp(22px,4vw,30px); font-weight:800; margin:0; color:var(--ink);">TPIX Wallet</h1>
                </div>
                <p style="font-size:12.5px; color:var(--ink2); margin:0;">Blockchain Address: <span style="font-family:monospace; background:var(--surf); box-shadow:var(--inset-sm); padding:4px 10px; border-radius:8px;">0x...</span></p>
            </div>
            <button type="button" class="tp-btn" style="display:inline-flex; align-items:center; gap:8px; padding:11px 18px; border-radius:13px; background:var(--surf); box-shadow:var(--inset-sm); color:var(--ink); font-weight:600; font-size:13.5px; border:none; cursor:pointer;">
                <i class="fas fa-qrcode"></i> <span>แสดง QR Code</span>
            </button>
        </div>
        <div style="display:grid; grid-template-columns:2fr 1fr; gap:16px;">
            <div class="tp-card" style="padding:26px; box-shadow:var(--inset-sm); min-width:0;">
                <div style="font-size:13px; color:var(--ink2); margin-bottom:8px;">ยอดคงเหลือทั้งหมด</div>
                <div style="display:flex; align-items:baseline; gap:10px; margin-bottom:8px;">
                    <div id="totalBalance" class="tp-num" style="font-size:clamp(32px,6vw,46px); font-weight:800; color:var(--deep1); line-height:1;">0.00</div>
                    <div style="font-size:20px; color:var(--ink2);">TPIX</div>
                </div>
                <div style="font-size:13px; color:var(--ink2);">≈ $<span id="totalBalanceUSD">0.00</span> USD</div>
            </div>
            <div class="tp-card" style="padding:18px; box-shadow:var(--inset-sm);">
                <div style="font-size:12.5px; color:var(--ink2); margin-bottom:12px;">ดำเนินการด่วน</div>
                <div style="display:flex; flex-direction:column; gap:9px;">
                    <a href="{{ route('user.tpix.send') }}" style="display:flex; align-items:center; gap:11px; padding:11px 13px; border-radius:11px; background:var(--surf); box-shadow:var(--inset-sm); color:var(--ink); font-weight:600; font-size:13.5px; text-decoration:none;"><i class="fas fa-paper-plane" style="width:18px; color:var(--accent1);"></i> ส่ง TPIX</a>
                    <a href="{{ route('user.tpix.deposit') }}" style="display:flex; align-items:center; gap:11px; padding:11px 13px; border-radius:11px; background:var(--surf); box-shadow:var(--inset-sm); color:var(--ink); font-weight:600; font-size:13.5px; text-decoration:none;"><i class="fas fa-download" style="width:18px; color:var(--accent1);"></i> ฝาก</a>
                    <a href="{{ route('user.tpix.withdrawal') }}" style="display:flex; align-items:center; gap:11px; padding:11px 13px; border-radius:11px; background:var(--surf); box-shadow:var(--inset-sm); color:var(--ink); font-weight:600; font-size:13.5px; text-decoration:none;"><i class="fas fa-upload" style="width:18px; color:var(--accent1);"></i> ถอน</a>
                </div>
            </div>
        </div>
    </div>

    {{-- ── Quick stats ───────────────────────────────────────── --}}
    <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(180px,1fr)); gap:16px;">
        @php
            $tpxStats = [
                ['fas fa-coins', 'TPIX Price', 'tpixPrice', '$0.00', '+12.5%'],
                ['fas fa-chart-line', 'กำไร/ขาดทุน', 'profitLoss', '+$0.00', null],
                ['fas fa-layer-group', 'Total Staked', 'totalStaked', '0.00', null],
                ['fas fa-gift', 'Rewards Earned', 'rewardsEarned', '0.00', null],
            ];
        @endphp
        @foreach($tpxStats as [$icon, $label, $id, $val, $badge])
            <div class="tp-card" style="padding:22px;">
                <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:14px;">
                    <span class="tp-tile" style="width:46px; height:46px; border-radius:50%; display:flex; align-items:center; justify-content:center; font-size:18px; color:var(--deep1);"><i class="{{ $icon }}"></i></span>
                    @if($badge)<span class="tp-pill" style="background:color-mix(in srgb, #5aa07e 18%, transparent); color:#5aa07e; font-size:11px; font-weight:700;">{{ $badge }}</span>@endif
                </div>
                <div style="font-size:13px; color:var(--ink2); margin-bottom:4px;">{{ $label }}</div>
                <div id="{{ $id }}" class="tp-num" style="font-size:22px; font-weight:800; color:{{ $id === 'profitLoss' ? '#5aa07e' : 'var(--ink)' }};">{{ $val }}</div>
            </div>
        @endforeach
    </div>

    {{-- ── Holdings + Sidebar ─────────────────────────────────── --}}
    <div style="display:grid; grid-template-columns:2fr 1fr; gap:18px; align-items:start;">
        <div style="display:flex; flex-direction:column; gap:18px; min-width:0;">
            {{-- Token holdings --}}
            <div class="tp-card" style="padding:0; overflow:hidden;">
                <div style="padding:20px 24px; border-bottom:1px solid color-mix(in srgb, var(--ink2) 13%, transparent); display:flex; align-items:center; justify-content:space-between;">
                    <h2 style="font-size:18px; font-weight:800; color:var(--ink); margin:0; display:flex; align-items:center; gap:10px;"><span class="tp-tile" style="width:38px; height:38px; border-radius:50%; display:flex; align-items:center; justify-content:center; color:var(--deep1);"><i class="fas fa-coins"></i></span> Token ที่ถือครอง</h2>
                    <a href="{{ route('user.tokens.index') }}" style="color:var(--deep1); font-size:13px; font-weight:600; text-decoration:none;">ดูทั้งหมด <i class="fas fa-arrow-right"></i></a>
                </div>
                <div id="tokenHoldings">
                    <div style="display:flex; align-items:center; justify-content:center; padding:48px 0;">
                        <div style="width:44px; height:44px; border:4px solid color-mix(in srgb, var(--accent1) 30%, transparent); border-top-color:var(--accent1); border-radius:50%; animation:tpixspin 0.8s linear infinite;"></div>
                    </div>
                </div>
            </div>

            {{-- Recent transactions --}}
            <div class="tp-card" style="padding:0; overflow:hidden;">
                <div style="padding:20px 24px; border-bottom:1px solid color-mix(in srgb, var(--ink2) 13%, transparent); display:flex; align-items:center; justify-content:space-between;">
                    <h2 style="font-size:18px; font-weight:800; color:var(--ink); margin:0; display:flex; align-items:center; gap:10px;"><span class="tp-tile" style="width:38px; height:38px; border-radius:50%; display:flex; align-items:center; justify-content:center; color:var(--deep1);"><i class="fas fa-history"></i></span> ธุรกรรมล่าสุด</h2>
                    <a href="{{ route('user.tpix.transactions') }}" style="color:var(--deep1); font-size:13px; font-weight:600; text-decoration:none;">ดูทั้งหมด <i class="fas fa-arrow-right"></i></a>
                </div>
                <div id="recentTransactions" style="max-height:500px; overflow-y:auto;">
                    <div style="display:flex; flex-direction:column; align-items:center; justify-content:center; padding:48px 24px; text-align:center;">
                        <i class="fas fa-receipt" style="font-size:52px; color:color-mix(in srgb, var(--ink2) 40%, transparent); margin-bottom:14px;"></i>
                        <p style="font-size:13px; color:var(--ink2); margin:0;">ยังไม่มีธุรกรรม</p>
                    </div>
                </div>
            </div>
        </div>

        {{-- Sidebar --}}
        <div style="display:flex; flex-direction:column; gap:18px;">
            <div class="tp-card" style="padding:0; overflow:hidden;">
                <div style="padding:18px 22px; border-bottom:1px solid color-mix(in srgb, var(--ink2) 13%, transparent);">
                    <h3 style="font-size:15px; font-weight:800; color:var(--ink); margin:0; display:flex; align-items:center; gap:8px;"><i class="fas fa-bolt" style="color:var(--accent1);"></i> TPIX Ecosystem</h3>
                </div>
                <div style="padding:18px; display:flex; flex-direction:column; gap:11px;">
                    @php
                        $tpxEco = [
                            ['user.dex.swap', 'fas fa-exchange-alt', 'DEX Swap', 'แลกเปลี่ยน Token'],
                            ['user.staking.index', 'fas fa-coins', 'Staking', 'Stake รับรางวัล'],
                            ['user.dex.pools', 'fas fa-water', 'Liquidity Pools', 'เพิ่มสภาพคล่อง'],
                            ['user.tokens.index', 'fas fa-store', 'Token Marketplace', 'ซื้อขาย Token'],
                        ];
                    @endphp
                    @foreach($tpxEco as [$route, $icon, $title, $sub])
                        @if(\Illuminate\Support\Facades\Route::has($route))
                            <a href="{{ route($route) }}" class="tp-card" style="display:flex; align-items:center; justify-content:space-between; padding:14px; box-shadow:var(--inset-sm); text-decoration:none;">
                                <div style="display:flex; align-items:center; gap:11px;">
                                    <span class="tp-tile" style="width:40px; height:40px; border-radius:11px; display:flex; align-items:center; justify-content:center; color:var(--deep1);"><i class="{{ $icon }}"></i></span>
                                    <div><div style="font-weight:700; color:var(--ink); font-size:14px;">{{ $title }}</div><div style="font-size:11.5px; color:var(--ink2);">{{ $sub }}</div></div>
                                </div>
                                <i class="fas fa-arrow-right" style="color:var(--ink2);"></i>
                            </a>
                        @endif
                    @endforeach
                </div>
            </div>

            {{-- Network status --}}
            <div class="tp-card" style="padding:22px;">
                <h3 style="font-size:13px; font-weight:700; color:var(--ink); margin:0 0 14px; display:flex; align-items:center; gap:8px;"><i class="fas fa-network-wired"></i> Network Status</h3>
                <div style="display:flex; flex-direction:column; gap:11px; font-size:13.5px;">
                    <div style="display:flex; justify-content:space-between; align-items:center;">
                        <span style="color:var(--ink2);">สถานะ</span>
                        <span style="display:flex; align-items:center; gap:7px; color:#5aa07e; font-weight:600;"><span style="width:8px; height:8px; background:#5aa07e; border-radius:50%;"></span> Online</span>
                    </div>
                    <div style="display:flex; justify-content:space-between; align-items:center;"><span style="color:var(--ink2);">Block Height</span><span id="blockHeight" style="color:var(--ink); font-family:monospace;">-</span></div>
                    <div style="display:flex; justify-content:space-between; align-items:center;"><span style="color:var(--ink2);">Gas Price</span><span id="gasPrice" style="color:var(--ink); font-family:monospace;">- Gwei</span></div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('styles')
<style>@keyframes tpixspin { to { transform: rotate(360deg); } }</style>
@endpush

@push('scripts')
<script>
/**
 * TPIX Wallet Dashboard
 * โหลดข้อมูลและแสดงผล
 */
console.log('TPIX Wallet Dashboard - View loaded');
</script>
@endpush
@endsection
