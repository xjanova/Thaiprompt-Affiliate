@extends('layouts.user-v4')

@section('title', 'Portfolio Management')

@section('content')
<div style="display:flex; flex-direction:column; gap:18px;" x-data="portfolioManager()">

    {{-- ── Hero + สรุป ───────────────────────────────────────── --}}
    <div class="tp-card" style="padding:26px 28px; background:linear-gradient(120deg, color-mix(in srgb, var(--accent1) 18%, transparent), transparent 72%);">
        <div style="display:flex; flex-wrap:wrap; justify-content:space-between; align-items:flex-start; gap:18px;">
            <div>
                <h1 style="font-size:clamp(24px,5vw,34px); font-weight:800; margin:0; color:var(--ink);">Portfolio Overview</h1>
                <p style="font-size:15px; color:var(--ink2); margin:4px 0 0;">Manage your cryptocurrency investments</p>
            </div>
            <div style="text-align:right;">
                <div style="font-size:12px; color:var(--ink2); margin-bottom:5px;">Total Portfolio Value</div>
                <div class="tp-num" style="font-size:clamp(30px,6vw,44px); font-weight:800; color:var(--deep1); line-height:1;">฿{{ number_format($totalValueTHB ?? 0, 2) }}</div>
                <div style="display:flex; align-items:center; justify-content:flex-end; gap:12px; margin-top:8px; font-size:13px;">
                    <span class="tp-pill" style="background:color-mix(in srgb, #5aa07e 20%, transparent); color:#5aa07e; font-weight:700;">+15.8% ↑ Today</span>
                    <span style="color:var(--ink2);">≈ ${{ number_format(($totalValueTHB ?? 0) / 33, 2) }} USD</span>
                </div>
            </div>
        </div>
        {{-- stat 4 ใบ --}}
        <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(150px,1fr)); gap:12px; margin-top:20px;">
            @php
                $pfStats = [
                    ['24h Change', '+฿12,456', '+8.2%', '#5aa07e'],
                    ['Total Invested', '฿145,000', 'All time', 'var(--ink2)'],
                    ['Total P&L', '+฿23,890', '+16.5%', '#5aa07e'],
                    ['Holdings', '8', 'Assets', 'var(--ink2)'],
                ];
            @endphp
            @foreach($pfStats as [$label, $val, $sub, $color])
                <div class="tp-card" style="padding:16px 18px; box-shadow:var(--inset-sm);">
                    <div style="font-size:12px; color:var(--ink2); margin-bottom:5px;">{{ $label }}</div>
                    <div class="tp-num" style="font-size:22px; font-weight:800; color:{{ $color }};">{{ $val }}</div>
                    <div style="font-size:11px; color:var(--ink2); margin-top:2px;">{{ $sub }}</div>
                </div>
            @endforeach
        </div>
    </div>

    {{-- ── เนื้อหาหลัก ────────────────────────────────────────── --}}
    <div style="display:grid; grid-template-columns:1fr; gap:18px;">
        <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(300px,1fr)); gap:18px;">

            {{-- Asset Allocation --}}
            <div class="tp-card" style="padding:24px;">
                <h2 style="font-size:17px; font-weight:800; color:var(--ink); margin:0 0 20px; display:flex; align-items:center; gap:10px;">
                    <span style="width:11px; height:11px; background:var(--accent1); border-radius:50%;"></span> Asset Allocation
                </h2>
                {{-- Donut (CSS conic-gradient แทน canvas) --}}
                @php
                    $donutColors = ['#f59e0b', '#3b82f6', '#10b981', '#8b5cf6', '#ef4444'];
                    $assetCount = count($balances ?? []);
                    $seg = $assetCount > 0 ? 100 / $assetCount : 100;
                    $gradientStops = [];
                    $acc = 0;
                    foreach (($balances ?? []) as $i => $b) {
                        $c = $donutColors[$i % 5];
                        $gradientStops[] = "$c {$acc}%";
                        $acc += $seg;
                        $gradientStops[] = "$c {$acc}%";
                    }
                    $donutGradient = $assetCount > 0 ? 'conic-gradient(' . implode(',', $gradientStops) . ')' : 'conic-gradient(var(--accent1) 0%, var(--accent2) 100%)';
                @endphp
                <div style="position:relative; width:200px; height:200px; margin:0 auto 22px;">
                    <div style="width:100%; height:100%; border-radius:50%; background:{{ $donutGradient }};"></div>
                    <div style="position:absolute; inset:26px; border-radius:50%; background:var(--surf); box-shadow:var(--inset-sm); display:flex; align-items:center; justify-content:center;">
                        <div style="text-align:center;"><div class="tp-num" style="font-size:26px; font-weight:800; color:var(--ink);">{{ $assetCount }}</div><div style="font-size:11px; color:var(--ink2);">Assets</div></div>
                    </div>
                </div>
                {{-- Asset list --}}
                <div style="display:flex; flex-direction:column; gap:9px;">
                    @foreach($balances ?? [] as $balance)
                        <div class="tp-card" style="padding:12px 14px; box-shadow:var(--inset-sm); display:flex; align-items:center; justify-content:space-between;">
                            <div style="display:flex; align-items:center; gap:10px;">
                                <span style="width:11px; height:11px; border-radius:50%; background:{{ $donutColors[$loop->index % 5] }};"></span>
                                <span style="font-weight:700; color:var(--ink); font-size:14px;">{{ $balance['code'] ?? '' }}</span>
                            </div>
                            <span class="tp-num" style="color:var(--ink2); font-size:13.5px;">{{ rand(15, 45) }}%</span>
                        </div>
                    @endforeach
                </div>
                <button type="button" class="tp-btn" style="margin-top:18px; width:100%; padding:13px; border-radius:14px; background:linear-gradient(135deg, var(--accent1), var(--accent2)); color:#fff; font-weight:700; font-size:14.5px; box-shadow:var(--raise); border:none; cursor:pointer;">⚖️ Rebalance Portfolio</button>
            </div>

            {{-- Performance --}}
            <div class="tp-card" style="padding:24px;">
                <div style="display:flex; align-items:center; justify-content:space-between; gap:12px; margin-bottom:18px; flex-wrap:wrap;">
                    <h2 style="font-size:17px; font-weight:800; color:var(--ink); margin:0;">Performance Analytics</h2>
                    <div style="display:flex; gap:6px;">
                        @foreach(['7D' => true, '30D' => false, '1Y' => false, 'ALL' => false] as $period => $active)
                            <button type="button" class="tp-btn" style="padding:7px 13px; border-radius:10px; font-size:12.5px; font-weight:600; border:none; cursor:pointer; {{ $active ? 'background:linear-gradient(135deg, var(--accent1), var(--accent2)); color:#fff;' : 'background:var(--surf); box-shadow:var(--inset-sm); color:var(--ink2);' }}">{{ $period }}</button>
                        @endforeach
                    </div>
                </div>
                {{-- CSS bars แทน canvas (demo) --}}
                <div class="tp-card" style="padding:20px; box-shadow:var(--inset-sm); height:230px; display:flex; align-items:flex-end; justify-content:space-between; gap:6px;">
                    @php $bars = [42, 55, 48, 63, 58, 71, 66, 80, 74, 88, 82, 95]; @endphp
                    @foreach($bars as $h)
                        <div style="flex:1; height:{{ $h }}%; border-radius:6px 6px 0 0; background:linear-gradient(180deg, var(--accent1), color-mix(in srgb, var(--accent2) 60%, transparent));"></div>
                    @endforeach
                </div>
            </div>
        </div>

        {{-- Holdings table --}}
        <div class="tp-card" style="padding:0; overflow:hidden;">
            <div style="padding:20px 24px; background:linear-gradient(120deg, color-mix(in srgb, var(--accent1) 12%, transparent), transparent 72%); border-bottom:1px solid color-mix(in srgb, var(--ink2) 13%, transparent);">
                <h2 style="font-size:17px; font-weight:800; color:var(--ink); margin:0;">Your Holdings</h2>
            </div>
            <div style="overflow-x:auto;">
                <table style="width:100%; border-collapse:collapse; min-width:760px;">
                    <thead>
                        <tr style="background:color-mix(in srgb, var(--ink2) 8%, transparent);">
                            <th style="padding:14px 20px; text-align:left; font-size:11px; font-weight:700; color:var(--ink2); text-transform:uppercase;">Asset</th>
                            <th style="padding:14px 20px; text-align:right; font-size:11px; font-weight:700; color:var(--ink2); text-transform:uppercase;">Holdings</th>
                            <th style="padding:14px 20px; text-align:right; font-size:11px; font-weight:700; color:var(--ink2); text-transform:uppercase;">Value (THB)</th>
                            <th style="padding:14px 20px; text-align:right; font-size:11px; font-weight:700; color:var(--ink2); text-transform:uppercase;">24h Change</th>
                            <th style="padding:14px 20px; text-align:right; font-size:11px; font-weight:700; color:var(--ink2); text-transform:uppercase;">P&L</th>
                            <th style="padding:14px 20px; text-align:center; font-size:11px; font-weight:700; color:var(--ink2); text-transform:uppercase;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($balances ?? [] as $balance)
                            @php $pfIconPath = public_path('icons/cryptocurrency/' . strtolower($balance['code'] ?? 'x') . '.svg'); @endphp
                            <tr style="border-top:1px solid color-mix(in srgb, var(--ink2) 13%, transparent);">
                                <td style="padding:14px 20px;">
                                    <div style="display:flex; align-items:center; gap:12px;">
                                        @if(file_exists($pfIconPath))
                                            <img src="{{ asset('icons/cryptocurrency/' . strtolower($balance['code']) . '.svg') }}" alt="{{ $balance['code'] }}" style="width:44px; height:44px;">
                                        @else
                                            <span class="tp-tile" style="width:44px; height:44px; border-radius:50%; display:flex; align-items:center; justify-content:center; font-weight:800; font-size:17px; color:var(--deep1);">{{ substr($balance['code'] ?? 'X', 0, 1) }}</span>
                                        @endif
                                        <div><div style="font-weight:800; color:var(--ink); font-size:15px;">{{ $balance['code'] ?? '' }}</div><div style="font-size:11.5px; color:var(--ink2);">{{ $balance['name'] ?? '' }}</div></div>
                                    </div>
                                </td>
                                <td style="padding:14px 20px; text-align:right;">
                                    <div class="tp-num" style="font-weight:800; color:var(--ink); font-size:14px;">{{ number_format($balance['balance'] ?? 0, 8) }}</div>
                                    <div style="font-size:11.5px; color:var(--ink2);">${{ number_format(rand(100, 5000), 2) }}</div>
                                </td>
                                <td style="padding:14px 20px; text-align:right;"><div class="tp-num" style="font-weight:800; color:var(--ink); font-size:15px;">฿{{ number_format($balance['balance_thb'] ?? 0, 2) }}</div></td>
                                <td style="padding:14px 20px; text-align:right;">
                                    @if(rand(0, 1))
                                        <span class="tp-pill" style="background:color-mix(in srgb, #5aa07e 18%, transparent); color:#5aa07e; font-size:12px; font-weight:700;">+{{ rand(1, 15) }}.{{ rand(0, 9) }}% ↑</span>
                                    @else
                                        <span class="tp-pill" style="background:color-mix(in srgb, #d9534f 18%, transparent); color:#d9534f; font-size:12px; font-weight:700;">-{{ rand(1, 8) }}.{{ rand(0, 9) }}% ↓</span>
                                    @endif
                                </td>
                                <td style="padding:14px 20px; text-align:right;">
                                    @if(rand(0, 1))
                                        <div style="color:#5aa07e; font-weight:800; font-size:14px;">+฿{{ number_format(rand(100, 5000), 2) }}</div>
                                        <div style="font-size:11.5px; color:#5aa07e;">+{{ rand(5, 20) }}%</div>
                                    @else
                                        <div style="color:#d9534f; font-weight:800; font-size:14px;">-฿{{ number_format(rand(100, 2000), 2) }}</div>
                                        <div style="font-size:11.5px; color:#d9534f;">-{{ rand(1, 10) }}%</div>
                                    @endif
                                </td>
                                <td style="padding:14px 20px; text-align:center;">
                                    <a href="{{ route('user.crypto-wallet.exchange') }}" class="tp-btn" style="padding:8px 16px; border-radius:11px; background:color-mix(in srgb, var(--accent1) 16%, transparent); color:var(--deep1); font-size:13px; font-weight:600; text-decoration:none;">Trade</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Quick actions --}}
        <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(150px,1fr)); gap:14px;">
            @php
                $pfActions = [
                    ['user.crypto-wallet.deposit', '📥', 'Deposit', 'Add funds'],
                    ['user.crypto-wallet.withdraw', '📤', 'Withdraw', 'Cash out'],
                    ['user.crypto-wallet.exchange', '💱', 'Exchange', 'Swap coins'],
                ];
            @endphp
            @foreach($pfActions as [$route, $icon, $label, $sub])
                @if(\Illuminate\Support\Facades\Route::has($route))
                    <a href="{{ route($route) }}" class="tp-card" style="padding:22px 16px; text-align:center; text-decoration:none;">
                        <div style="font-size:36px; margin-bottom:8px;">{{ $icon }}</div>
                        <div style="font-weight:800; color:var(--ink); font-size:16px;">{{ $label }}</div>
                        <div style="font-size:12px; color:var(--ink2); margin-top:2px;">{{ $sub }}</div>
                    </a>
                @endif
            @endforeach
        </div>
    </div>
</div>

<script>
function portfolioManager() {
    return {
        init() {
            this.initCharts();
        },
        initCharts() {
            // Initialize charts here
            console.log('Portfolio charts initialized');
        }
    }
}
</script>
@endsection
