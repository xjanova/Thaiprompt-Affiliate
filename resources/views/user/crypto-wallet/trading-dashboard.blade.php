@extends('layouts.user-v4')

@section('title', 'Crypto Trading Dashboard')

@section('content')
<div style="display:flex; flex-direction:column; gap:18px;" x-data="tradingDashboard()">

    {{-- ── Header + Stats ─────────────────────────────────────── --}}
    <div class="tp-card" style="padding:26px 28px; background:linear-gradient(120deg, color-mix(in srgb, var(--accent1) 18%, transparent), transparent 72%);">
        <div style="display:flex; flex-wrap:wrap; justify-content:space-between; align-items:flex-start; gap:18px;">
            <div>
                <h1 style="font-size:clamp(22px,4.5vw,32px); font-weight:800; margin:0; color:var(--ink);">Premium Trading Dashboard</h1>
                <p style="font-size:14.5px; color:var(--ink2); margin:4px 0 0;">Real-time cryptocurrency exchange platform</p>
            </div>
            <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(130px,1fr)); gap:12px;">
                @php
                    $tdStats = [
                        ['Total Balance', '฿' . number_format($totalValueTHB ?? 0, 2), '+12.5% ↑', '#5aa07e'],
                        ['24h Volume', '฿1.2M', 'Trading', '#5689b8'],
                        ['P&L Today', '+฿8,456', '+8.2% ↑', '#5aa07e'],
                    ];
                @endphp
                @foreach($tdStats as [$label, $val, $sub, $color])
                    <div class="tp-card" style="padding:14px 16px; box-shadow:var(--inset-sm);">
                        <div style="font-size:11.5px; color:var(--ink2); margin-bottom:4px;">{{ $label }}</div>
                        <div class="tp-num" style="font-size:19px; font-weight:800; color:var(--ink);">{{ $val }}</div>
                        <div style="font-size:11px; color:{{ $color }}; margin-top:2px;">{{ $sub }}</div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    {{-- ── Trading Interface ──────────────────────────────────── --}}
    <div style="display:grid; grid-template-columns:1fr; gap:18px;">
        <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(280px,1fr)); gap:18px; align-items:start;">

            {{-- Market Watch --}}
            <div class="tp-card" style="padding:0; overflow:hidden;">
                <div style="padding:18px 20px; border-bottom:1px solid color-mix(in srgb, var(--ink2) 13%, transparent);">
                    <h2 style="font-size:16px; font-weight:800; color:var(--ink); margin:0; display:flex; align-items:center; gap:10px;"><span style="width:9px; height:9px; background:#5aa07e; border-radius:50%;"></span> Live Markets</h2>
                </div>
                <div style="padding:14px; display:flex; flex-direction:column; gap:8px; max-height:600px; overflow-y:auto;">
                    @foreach($currencies ?? [] as $currency)
                        @php $tdIconPath = public_path('icons/cryptocurrency/' . strtolower($currency->code) . '.svg'); @endphp
                        <div class="tp-card" style="padding:14px; box-shadow:var(--inset-sm); cursor:pointer;" @click="selectCurrency('{{ $currency->code }}')">
                            <div style="display:flex; align-items:center; justify-content:space-between;">
                                <div style="display:flex; align-items:center; gap:10px;">
                                    @if(file_exists($tdIconPath))
                                        <img src="{{ asset('icons/cryptocurrency/' . strtolower($currency->code) . '.svg') }}" alt="{{ $currency->code }}" style="width:38px; height:38px; border-radius:50%;">
                                    @else
                                        <span class="tp-tile" style="width:38px; height:38px; border-radius:50%; display:flex; align-items:center; justify-content:center; font-weight:800; color:var(--deep1);">{{ substr($currency->code, 0, 1) }}</span>
                                    @endif
                                    <div><div style="font-weight:800; color:var(--ink); font-size:14px;">{{ $currency->code }}</div><div style="font-size:11.5px; color:var(--ink2);">{{ $currency->name }}</div></div>
                                </div>
                                <div style="text-align:right;">
                                    <div class="tp-num" style="font-weight:800; color:#5aa07e; font-size:13.5px;">฿{{ number_format(rand(1000, 50000), 2) }}</div>
                                    <div style="font-size:11.5px; color:#5aa07e;">+{{ number_format(rand(1, 15), 2) }}%</div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            {{-- Center: Chart + Exchange --}}
            <div style="display:flex; flex-direction:column; gap:18px; grid-column:span 2; min-width:0;">
                {{-- Price Chart (CSS sparkline แทน canvas) --}}
                <div class="tp-card" style="padding:0; overflow:hidden;">
                    <div style="padding:18px 22px; border-bottom:1px solid color-mix(in srgb, var(--ink2) 13%, transparent); display:flex; align-items:center; justify-content:space-between; gap:12px; flex-wrap:wrap;">
                        <div style="display:flex; align-items:center; gap:12px;">
                            <h2 style="font-size:20px; font-weight:800; color:var(--ink); margin:0;" x-text="selectedCurrency + '/THB'">BTC/THB</h2>
                            <span class="tp-pill" style="background:color-mix(in srgb, #5aa07e 18%, transparent); color:#5aa07e; font-size:12px; font-weight:700;">+5.2%</span>
                        </div>
                        <div style="display:flex; gap:6px;">
                            @foreach(['1H' => false, '24H' => true, '7D' => false, '1M' => false] as $p => $act)
                                <button type="button" class="tp-btn" style="padding:7px 12px; border-radius:10px; font-size:12.5px; font-weight:600; border:none; cursor:pointer; {{ $act ? 'background:linear-gradient(135deg, var(--accent1), var(--accent2)); color:#fff;' : 'background:var(--surf); box-shadow:var(--inset-sm); color:var(--ink2);' }}">{{ $p }}</button>
                            @endforeach
                        </div>
                    </div>
                    <div style="padding:22px;">
                        <div class="tp-card" style="position:relative; height:300px; box-shadow:var(--inset-sm); overflow:hidden; display:flex; align-items:flex-end; gap:3px; padding:16px;">
                            @php $tdBars = [50,58,54,62,57,68,64,72,66,78,71,80,74,86,79,90,84,95,88,92]; @endphp
                            @foreach($tdBars as $h)
                                <div style="flex:1; height:{{ $h }}%; border-radius:3px 3px 0 0; background:linear-gradient(180deg, var(--accent1), color-mix(in srgb, var(--accent2) 55%, transparent));"></div>
                            @endforeach
                            <div class="tp-card" style="position:absolute; top:14px; left:14px; padding:12px 14px; box-shadow:var(--card-shadow-sm); display:grid; grid-template-columns:repeat(4,auto); gap:14px;">
                                <div><div style="font-size:11px; color:var(--ink2);">Open</div><div class="tp-num" style="font-weight:800; color:var(--ink); font-size:13px;">฿1,234,567</div></div>
                                <div><div style="font-size:11px; color:var(--ink2);">High</div><div class="tp-num" style="font-weight:800; color:#5aa07e; font-size:13px;">฿1,245,000</div></div>
                                <div><div style="font-size:11px; color:var(--ink2);">Low</div><div class="tp-num" style="font-weight:800; color:#d9534f; font-size:13px;">฿1,220,000</div></div>
                                <div><div style="font-size:11px; color:var(--ink2);">Volume</div><div class="tp-num" style="font-weight:800; color:#5689b8; font-size:13px;">45.2M</div></div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Exchange Interface (Alpine) --}}
                <div class="tp-card" style="padding:22px;">
                    <div style="display:flex; gap:8px; margin-bottom:20px;">
                        <button type="button" @click="exchangeMode = 'buy'"
                                :style="exchangeMode === 'buy' ? 'background:linear-gradient(135deg, #5aa07e, #4f9e7e); color:#fff;' : 'background:var(--surf); box-shadow:var(--inset-sm); color:var(--ink2);'"
                                style="flex:1; padding:13px; border-radius:13px; font-weight:800; font-size:14.5px; border:none; cursor:pointer;">🔥 Buy Crypto</button>
                        <button type="button" @click="exchangeMode = 'sell'"
                                :style="exchangeMode === 'sell' ? 'background:linear-gradient(135deg, #d9534f, #cf6f7c); color:#fff;' : 'background:var(--surf); box-shadow:var(--inset-sm); color:var(--ink2);'"
                                style="flex:1; padding:13px; border-radius:13px; font-weight:800; font-size:14.5px; border:none; cursor:pointer;">💎 Sell Crypto</button>
                    </div>

                    {{-- Buy --}}
                    <div x-show="exchangeMode === 'buy'" x-transition style="display:flex; flex-direction:column; gap:16px;">
                        <div>
                            <label style="display:block; font-size:13px; font-weight:700; color:var(--ink); margin-bottom:7px;">Select Cryptocurrency</label>
                            <select style="width:100%; padding:12px 14px; border-radius:12px; background:var(--surf); box-shadow:var(--inset-sm); border:1px solid transparent; color:var(--ink); font-size:14px;">
                                @foreach($currencies ?? [] as $currency)<option value="{{ $currency->code }}">{{ $currency->code }} - {{ $currency->name }}</option>@endforeach
                            </select>
                        </div>
                        <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(180px,1fr)); gap:14px;">
                            <div>
                                <label style="display:block; font-size:13px; font-weight:700; color:var(--ink); margin-bottom:7px;">Amount (THB)</label>
                                <input type="number" x-model="buyAmount" style="width:100%; padding:12px 14px; border-radius:12px; background:var(--surf); box-shadow:var(--inset-sm); border:1px solid transparent; color:var(--ink); font-size:15px; font-weight:700;" placeholder="0.00">
                                <div style="font-size:11.5px; color:var(--ink2); margin-top:7px;">Available: ฿{{ number_format($thbWallet->balance ?? 0, 2) }}</div>
                            </div>
                            <div>
                                <label style="display:block; font-size:13px; font-weight:700; color:var(--ink); margin-bottom:7px;">You'll Receive</label>
                                <input type="text" readonly :value="calculateReceive(buyAmount)" style="width:100%; padding:12px 14px; border-radius:12px; background:var(--surf); box-shadow:var(--inset-sm); border:1px solid transparent; color:var(--ink); font-size:15px; font-weight:700;" placeholder="0.00000000">
                                <div style="font-size:11.5px; color:var(--ink2); margin-top:7px;">Rate: 1 BTC = ฿1,234,567</div>
                            </div>
                        </div>
                        <div style="display:flex; gap:8px;">
                            @foreach([1000, 5000, 10000, 50000] as $amt)
                                <button type="button" @click="buyAmount = {{ $amt }}" class="tp-btn" style="flex:1; padding:9px; border-radius:11px; background:var(--surf); box-shadow:var(--inset-sm); color:var(--ink2); font-size:12.5px; border:none; cursor:pointer;">฿{{ number_format($amt) }}</button>
                            @endforeach
                        </div>
                        <div class="tp-card" style="padding:14px 16px; box-shadow:var(--inset-sm);">
                            <div style="display:flex; flex-direction:column; gap:7px; font-size:13px;">
                                <div style="display:flex; justify-content:space-between;"><span style="color:var(--ink2);">Exchange Rate</span><span style="font-weight:700; color:var(--ink);">฿1,234,567.00</span></div>
                                <div style="display:flex; justify-content:space-between;"><span style="color:var(--ink2);">Platform Fee (0.5%)</span><span style="font-weight:700; color:var(--ink);">฿25.00</span></div>
                                <div style="border-top:1px solid color-mix(in srgb, var(--ink2) 15%, transparent); padding-top:7px; display:flex; justify-content:space-between;"><span style="font-weight:800; color:var(--ink);">Total</span><span style="font-weight:800; color:#5aa07e; font-size:15px;">฿5,025.00</span></div>
                            </div>
                        </div>
                        <button type="button" class="tp-btn" style="width:100%; padding:15px; border-radius:14px; background:linear-gradient(135deg, #5aa07e, #4f9e7e); color:#fff; font-weight:800; font-size:16px; box-shadow:var(--raise); border:none; cursor:pointer;">🚀 Buy Now</button>
                    </div>

                    {{-- Sell --}}
                    <div x-show="exchangeMode === 'sell'" x-transition style="display:flex; flex-direction:column; gap:16px;">
                        <div>
                            <label style="display:block; font-size:13px; font-weight:700; color:var(--ink); margin-bottom:7px;">Select Cryptocurrency</label>
                            <select style="width:100%; padding:12px 14px; border-radius:12px; background:var(--surf); box-shadow:var(--inset-sm); border:1px solid transparent; color:var(--ink); font-size:14px;">
                                @foreach($currencies ?? [] as $currency)<option value="{{ $currency->code }}">{{ $currency->code }} - {{ $currency->name }}</option>@endforeach
                            </select>
                        </div>
                        <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(180px,1fr)); gap:14px;">
                            <div>
                                <label style="display:block; font-size:13px; font-weight:700; color:var(--ink); margin-bottom:7px;">Amount (Crypto)</label>
                                <input type="number" x-model="sellAmount" style="width:100%; padding:12px 14px; border-radius:12px; background:var(--surf); box-shadow:var(--inset-sm); border:1px solid transparent; color:var(--ink); font-size:15px; font-weight:700;" placeholder="0.00000000">
                                <div style="font-size:11.5px; color:var(--ink2); margin-top:7px;">Available: 0.5000 BTC</div>
                            </div>
                            <div>
                                <label style="display:block; font-size:13px; font-weight:700; color:var(--ink); margin-bottom:7px;">You'll Receive (THB)</label>
                                <input type="text" readonly :value="calculateSell(sellAmount)" style="width:100%; padding:12px 14px; border-radius:12px; background:var(--surf); box-shadow:var(--inset-sm); border:1px solid transparent; color:var(--ink); font-size:15px; font-weight:700;" placeholder="0.00">
                                <div style="font-size:11.5px; color:var(--ink2); margin-top:7px;">Rate: 1 BTC = ฿1,234,567</div>
                            </div>
                        </div>
                        <button type="button" class="tp-btn" style="width:100%; padding:15px; border-radius:14px; background:linear-gradient(135deg, #d9534f, #cf6f7c); color:#fff; font-weight:800; font-size:16px; box-shadow:var(--raise); border:none; cursor:pointer;">💰 Sell Now</button>
                    </div>
                </div>
            </div>

            {{-- Right: Order Book + Recent Trades --}}
            <div style="display:flex; flex-direction:column; gap:18px;">
                <div class="tp-card" style="padding:0; overflow:hidden;">
                    <div style="padding:14px 18px; border-bottom:1px solid color-mix(in srgb, var(--ink2) 13%, transparent);"><h3 style="font-weight:800; color:var(--ink); margin:0; font-size:14.5px;">Order Book</h3></div>
                    <div style="padding:14px;">
                        <div style="display:flex; flex-direction:column; gap:3px; margin-bottom:12px;">
                            @for($i = 0; $i < 5; $i++)
                                <div style="display:flex; justify-content:space-between; font-size:11.5px; padding:6px 8px; border-radius:6px; background:color-mix(in srgb, #d9534f 8%, transparent);">
                                    <span style="color:#d9534f;">฿{{ number_format(1234567 + ($i * 100), 2) }}</span>
                                    <span style="color:var(--ink2);">{{ number_format(rand(1, 10) / 10, 4) }}</span>
                                    <span style="color:var(--ink2);">฿{{ number_format(rand(1000, 5000), 2) }}</span>
                                </div>
                            @endfor
                        </div>
                        <div class="tp-card" style="padding:12px; text-align:center; box-shadow:var(--inset-sm); background:color-mix(in srgb, #5aa07e 12%, transparent); margin-bottom:12px;">
                            <div class="tp-num" style="font-size:20px; font-weight:800; color:#5aa07e;">฿1,234,567</div>
                            <div style="font-size:11px; color:var(--ink2);">$41,234.56 USD</div>
                        </div>
                        <div style="display:flex; flex-direction:column; gap:3px;">
                            @for($i = 0; $i < 5; $i++)
                                <div style="display:flex; justify-content:space-between; font-size:11.5px; padding:6px 8px; border-radius:6px; background:color-mix(in srgb, #5aa07e 8%, transparent);">
                                    <span style="color:#5aa07e;">฿{{ number_format(1234567 - ($i * 100), 2) }}</span>
                                    <span style="color:var(--ink2);">{{ number_format(rand(1, 10) / 10, 4) }}</span>
                                    <span style="color:var(--ink2);">฿{{ number_format(rand(1000, 5000), 2) }}</span>
                                </div>
                            @endfor
                        </div>
                    </div>
                </div>

                <div class="tp-card" style="padding:0; overflow:hidden;">
                    <div style="padding:14px 18px; border-bottom:1px solid color-mix(in srgb, var(--ink2) 13%, transparent);"><h3 style="font-weight:800; color:var(--ink); margin:0; font-size:14.5px;">Recent Trades</h3></div>
                    <div style="padding:14px; display:flex; flex-direction:column; gap:7px; max-height:256px; overflow-y:auto;">
                        @for($i = 0; $i < 10; $i++)
                            <div style="display:flex; justify-content:space-between; font-size:11.5px;">
                                <span :style="Math.random() > 0.5 ? 'color:#5aa07e' : 'color:#d9534f'">฿{{ number_format(rand(1234000, 1235000), 2) }}</span>
                                <span style="color:var(--ink2);">{{ number_format(rand(1, 100) / 100, 4) }}</span>
                                <span style="color:var(--ink2);">{{ date('H:i:s') }}</span>
                            </div>
                        @endfor
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function tradingDashboard() {
    return {
        exchangeMode: 'buy',
        selectedCurrency: 'BTC',
        buyAmount: 0,
        sellAmount: 0,
        selectCurrency(code) {
            this.selectedCurrency = code;
        },
        calculateReceive(thb) {
            return (thb / 1234567).toFixed(8);
        },
        calculateSell(crypto) {
            return (crypto * 1234567).toFixed(2);
        }
    }
}
</script>
@endsection
