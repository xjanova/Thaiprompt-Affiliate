@extends('layouts.user-v4')

@section('title', 'แลกเปลี่ยน THB ↔ Crypto')

@section('content')
<div style="display:flex; flex-direction:column; gap:18px; max-width:960px; margin-inline:auto; width:100%;">

    {{-- ── Hero + ยอด 2 กระเป๋า ───────────────────────────────── --}}
    <div class="tp-card" style="padding:22px 24px; background:linear-gradient(120deg, color-mix(in srgb, var(--accent1) 16%, transparent), transparent 70%);">
        <div style="display:flex; flex-wrap:wrap; align-items:center; justify-content:space-between; gap:14px;">
            <div style="display:flex; align-items:center; gap:14px;">
                <span class="tp-tile" style="width:56px; height:56px; border-radius:18px; display:flex; align-items:center; justify-content:center; font-size:24px; color:var(--deep1);"><i class="fas fa-exchange-alt"></i></span>
                <div>
                    <h1 style="font-size:clamp(19px,3.5vw,26px); font-weight:800; margin:0; color:var(--ink);">💱 แลกเปลี่ยนสกุลเงิน</h1>
                    <div style="font-size:13px; color:var(--ink2); margin-top:2px;">ซื้อ/ขาย Crypto ด้วยบาทไทย</div>
                </div>
            </div>
            <a href="{{ route('user.crypto-wallet.index') }}" class="tp-btn" style="display:inline-flex; align-items:center; gap:8px; padding:10px 16px; border-radius:13px; background:var(--surf); box-shadow:var(--inset-sm); color:var(--ink); font-weight:600; font-size:13.5px; text-decoration:none;">
                <i class="fas fa-arrow-left"></i> <span>กลับหน้าหลัก</span>
            </a>
        </div>
        <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(240px,1fr)); gap:12px; margin-top:16px;">
            <div class="tp-card" style="padding:14px 16px; box-shadow:var(--inset-sm); display:flex; align-items:center; justify-content:space-between; gap:12px;">
                <div style="display:flex; align-items:center; gap:12px;">
                    <span class="tp-tile" style="width:44px; height:44px; border-radius:50%; display:flex; align-items:center; justify-content:center; font-size:18px; color:var(--deep1);">฿</span>
                    <div>
                        <p style="font-size:11.5px; color:var(--ink2); margin:0;">กระเป๋าบาท (THB)</p>
                        <p class="tp-num" style="font-size:19px; font-weight:800; color:var(--ink); margin:2px 0 0;">฿{{ number_format($thbWallet->balance ?? 0, 2) }}</p>
                    </div>
                </div>
                <a href="{{ route('user.wallet.index') }}" style="color:var(--deep1);"><i class="fas fa-arrow-right"></i></a>
            </div>
            <div class="tp-card" style="padding:14px 16px; box-shadow:var(--inset-sm); display:flex; align-items:center; justify-content:space-between; gap:12px;">
                <div style="display:flex; align-items:center; gap:12px;">
                    <span class="tp-tile" style="width:44px; height:44px; border-radius:50%; display:flex; align-items:center; justify-content:center; font-size:18px; color:var(--deep1);">₿</span>
                    <div>
                        <p style="font-size:11.5px; color:var(--ink2); margin:0;">กระเป๋าคริปโต</p>
                        <p class="tp-num" style="font-size:19px; font-weight:800; color:var(--ink); margin:2px 0 0;">{{ count($cryptoBalances) }} สกุล</p>
                    </div>
                </div>
                <a href="{{ route('user.crypto-wallet.index') }}" style="color:var(--deep1);"><i class="fas fa-arrow-right"></i></a>
            </div>
        </div>
    </div>

    @if(session('success'))
        <div class="tp-card" style="padding:14px 18px; background:color-mix(in srgb, #5aa07e 12%, transparent); border:1px solid color-mix(in srgb, #5aa07e 30%, transparent); color:var(--ink); font-size:14px;">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="tp-card" style="padding:14px 18px; background:color-mix(in srgb, #d9534f 10%, transparent); border:1px solid color-mix(in srgb, #d9534f 30%, transparent); color:#d9534f; font-size:14px;">{{ session('error') }}</div>
    @endif

    {{-- ── แท็บซื้อ/ขาย (Alpine) ─────────────────────────────── --}}
    <div class="tp-card" style="padding:0; overflow:hidden;" x-data="{ tab: 'buy' }">
        <div style="display:flex; border-bottom:1px solid color-mix(in srgb, var(--ink2) 15%, transparent);">
            <button type="button" @click="tab = 'buy'"
                    :style="tab === 'buy' ? 'background:color-mix(in srgb, #5aa07e 12%, transparent); color:#5aa07e; border-bottom:2px solid #5aa07e;' : 'color:var(--ink2); border-bottom:2px solid transparent;'"
                    style="flex:1; padding:15px; font-weight:800; font-size:15px; background:none; border:none; cursor:pointer;">🛒 ซื้อ Crypto</button>
            <button type="button" @click="tab = 'sell'"
                    :style="tab === 'sell' ? 'background:color-mix(in srgb, #d9534f 12%, transparent); color:#d9534f; border-bottom:2px solid #d9534f;' : 'color:var(--ink2); border-bottom:2px solid transparent;'"
                    style="flex:1; padding:15px; font-weight:800; font-size:15px; background:none; border:none; cursor:pointer;">💰 ขาย Crypto</button>
        </div>

        {{-- แท็บซื้อ --}}
        <div x-show="tab === 'buy'" style="padding:24px;">
            <form action="{{ route('user.crypto-wallet.exchange.buy') }}" method="POST">
                @csrf
                <div style="margin-bottom:18px;">
                    <label style="display:block; font-size:13px; font-weight:700; color:var(--ink); margin-bottom:7px;">เลือกสกุลเงินที่ต้องการซื้อ</label>
                    <select name="currency_id" required style="width:100%; padding:12px 14px; border-radius:12px; background:var(--surf); box-shadow:var(--inset-sm); border:1px solid transparent; color:var(--ink); font-size:14px;">
                        <option value="">-- เลือกสกุลเงิน --</option>
                        @foreach($currencies as $curr)
                            @if($curr->exchange_enabled)
                                <option value="{{ $curr->id }}">{{ $curr->code }} ({{ $curr->name }})@if(isset($prices[$curr->code])) - ราคา ฿{{ number_format($prices[$curr->code]['buy_price'], 2) }}@endif</option>
                            @endif
                        @endforeach
                    </select>
                </div>
                <div style="margin-bottom:18px;">
                    <label style="display:block; font-size:13px; font-weight:700; color:var(--ink); margin-bottom:7px;">จำนวนเงินบาทที่ต้องการใช้</label>
                    <div style="position:relative;">
                        <input type="number" name="thb_amount" step="0.01" required min="1" style="width:100%; padding:12px 60px 12px 14px; border-radius:12px; background:var(--surf); box-shadow:var(--inset-sm); border:1px solid transparent; color:var(--ink); font-size:14px;" placeholder="0.00">
                        <span style="position:absolute; right:16px; top:50%; transform:translateY(-50%); color:var(--ink2); font-weight:600; font-size:13px;">THB</span>
                    </div>
                    @if($thbWallet)<p style="font-size:12.5px; color:var(--ink2); margin:7px 0 0;">มีอยู่: ฿{{ number_format($thbWallet->balance, 2) }}</p>@endif
                </div>
                <div style="margin-bottom:18px;">
                    <label style="display:block; font-size:13px; font-weight:700; color:var(--ink); margin-bottom:7px;">PIN กระเป๋าเงินบาท</label>
                    <input type="password" name="pin" required minlength="4" maxlength="6" style="width:100%; padding:12px 14px; border-radius:12px; background:var(--surf); box-shadow:var(--inset-sm); border:1px solid transparent; color:var(--ink); font-size:14px;" placeholder="กรอก PIN">
                </div>
                <label style="display:flex; align-items:flex-start; gap:10px; margin-bottom:20px; cursor:pointer;">
                    <input type="checkbox" name="accept_terms" required style="margin-top:3px; width:18px; height:18px; accent-color:var(--accent1); flex-shrink:0;">
                    <span style="font-size:13px; color:var(--ink2);">ฉันยอมรับว่าราคาอาจเปลี่ยนแปลงได้ และ ค่าธรรมเนียมการแลกเปลี่ยนจะถูกหักออกจากยอดเงิน</span>
                </label>
                <button type="submit" class="tp-btn" style="width:100%; padding:15px; border-radius:14px; background:linear-gradient(135deg, #5aa07e, #4f9e7e); color:#fff; font-weight:800; font-size:16px; box-shadow:var(--raise); border:none; cursor:pointer;">🛒 ซื้อ Crypto ด้วยบาท</button>
            </form>
        </div>

        {{-- แท็บขาย --}}
        <div x-show="tab === 'sell'" style="padding:24px;">
            <form action="{{ route('user.crypto-wallet.exchange.sell') }}" method="POST">
                @csrf
                <div style="margin-bottom:18px;">
                    <label style="display:block; font-size:13px; font-weight:700; color:var(--ink); margin-bottom:7px;">เลือกสกุลเงินที่ต้องการขาย</label>
                    <select name="currency_id" required style="width:100%; padding:12px 14px; border-radius:12px; background:var(--surf); box-shadow:var(--inset-sm); border:1px solid transparent; color:var(--ink); font-size:14px;">
                        <option value="">-- เลือกสกุลเงิน --</option>
                        @foreach($currencies as $curr)
                            @if($curr->exchange_enabled && isset($cryptoBalances[$curr->code]) && $cryptoBalances[$curr->code]['balance'] > 0)
                                <option value="{{ $curr->id }}">{{ $curr->code }} - มี {{ number_format($cryptoBalances[$curr->code]['balance'], 8) }}@if(isset($prices[$curr->code])) (ราคา ฿{{ number_format($prices[$curr->code]['sell_price'], 2) }})@endif</option>
                            @endif
                        @endforeach
                    </select>
                </div>
                <div style="margin-bottom:18px;">
                    <label style="display:block; font-size:13px; font-weight:700; color:var(--ink); margin-bottom:7px;">จำนวน Crypto ที่ต้องการขาย</label>
                    <input type="number" name="crypto_amount" step="0.00000001" required min="0.00000001" style="width:100%; padding:12px 14px; border-radius:12px; background:var(--surf); box-shadow:var(--inset-sm); border:1px solid transparent; color:var(--ink); font-size:14px;" placeholder="0.00000000">
                </div>
                <div style="margin-bottom:18px;">
                    <label style="display:block; font-size:13px; font-weight:700; color:var(--ink); margin-bottom:7px;">PIN กระเป๋าคริปโต</label>
                    <input type="password" name="pin" required minlength="4" maxlength="6" style="width:100%; padding:12px 14px; border-radius:12px; background:var(--surf); box-shadow:var(--inset-sm); border:1px solid transparent; color:var(--ink); font-size:14px;" placeholder="กรอก PIN">
                </div>
                <label style="display:flex; align-items:flex-start; gap:10px; margin-bottom:20px; cursor:pointer;">
                    <input type="checkbox" name="accept_terms" required style="margin-top:3px; width:18px; height:18px; accent-color:var(--accent1); flex-shrink:0;">
                    <span style="font-size:13px; color:var(--ink2);">ฉันยอมรับว่าราคาอาจเปลี่ยนแปลงได้ และ ค่าธรรมเนียมการแลกเปลี่ยนจะถูกหักออกจากยอดเงินบาทที่ได้รับ</span>
                </label>
                <button type="submit" class="tp-btn" style="width:100%; padding:15px; border-radius:14px; background:linear-gradient(135deg, #d9534f, #cf6f7c); color:#fff; font-weight:800; font-size:16px; box-shadow:var(--raise); border:none; cursor:pointer;">💰 ขาย Crypto รับบาท</button>
            </form>
        </div>
    </div>

    {{-- ── ตารางราคา ─────────────────────────────────────────── --}}
    <div class="tp-card" style="padding:22px 24px;">
        <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:16px;">
            <h3 style="font-size:15px; font-weight:800; color:var(--ink); margin:0;">ราคาปัจจุบัน</h3>
            <a href="{{ route('user.crypto-wallet.exchange.history') }}" style="color:var(--deep1); font-size:13px; font-weight:700; text-decoration:none;">ประวัติการแลกเปลี่ยน →</a>
        </div>
        <div style="overflow-x:auto;">
            <table style="width:100%; border-collapse:collapse; min-width:520px;">
                <thead>
                    <tr style="border-bottom:1px solid color-mix(in srgb, var(--ink2) 15%, transparent);">
                        <th style="padding:0 8px 12px 0; text-align:left; font-size:11px; font-weight:700; color:var(--ink2); text-transform:uppercase;">สกุลเงิน</th>
                        <th style="padding:0 8px 12px; text-align:right; font-size:11px; font-weight:700; color:var(--ink2); text-transform:uppercase;">ราคาซื้อ</th>
                        <th style="padding:0 8px 12px; text-align:right; font-size:11px; font-weight:700; color:var(--ink2); text-transform:uppercase;">ราคาขาย</th>
                        <th style="padding:0 0 12px 8px; text-align:right; font-size:11px; font-weight:700; color:var(--ink2); text-transform:uppercase;">เปลี่ยนแปลง 24h</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($currencies->take(5) as $curr)
                        @if(isset($prices[$curr->code]))
                            @php $exIconPath = public_path('icons/cryptocurrency/' . strtolower($curr->code) . '.svg'); @endphp
                            <tr style="border-bottom:1px solid color-mix(in srgb, var(--ink2) 10%, transparent);">
                                <td style="padding:12px 8px 12px 0;">
                                    <div style="display:flex; align-items:center; gap:10px;">
                                        @if(file_exists($exIconPath))<img src="{{ asset('icons/cryptocurrency/' . strtolower($curr->code) . '.svg') }}" alt="{{ $curr->code }}" style="width:30px; height:30px;">@endif
                                        <div><div style="font-weight:800; color:var(--ink); font-size:13.5px;">{{ $curr->code }}</div><div style="font-size:11.5px; color:var(--ink2);">{{ $curr->name }}</div></div>
                                    </div>
                                </td>
                                <td class="tp-num" style="padding:12px 8px; text-align:right; font-weight:700; color:#5aa07e; font-size:13.5px;">฿{{ number_format($prices[$curr->code]['buy_price'], 2) }}</td>
                                <td class="tp-num" style="padding:12px 8px; text-align:right; font-weight:700; color:#d9534f; font-size:13.5px;">฿{{ number_format($prices[$curr->code]['sell_price'], 2) }}</td>
                                <td style="padding:12px 0 12px 8px; text-align:right; font-size:13px;">
                                    @if(isset($prices[$curr->code]['change_24h']))
                                        <span style="color:{{ $prices[$curr->code]['change_24h'] >= 0 ? '#5aa07e' : '#d9534f' }};">{{ $prices[$curr->code]['change_24h'] >= 0 ? '+' : '' }}{{ number_format($prices[$curr->code]['change_24h'], 2) }}%</span>
                                    @else
                                        <span style="color:var(--ink2);">-</span>
                                    @endif
                                </td>
                            </tr>
                        @endif
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    {{-- ── ข้อมูล ─────────────────────────────────────────────── --}}
    <div class="tp-card" style="padding:20px 22px; box-shadow:var(--inset-sm);">
        <h4 style="font-weight:800; color:var(--ink); margin:0 0 10px; font-size:14.5px;">ข้อมูลการแลกเปลี่ยน</h4>
        <ul style="margin:0; padding-left:18px; color:var(--ink2); font-size:13px; display:flex; flex-direction:column; gap:5px;">
            <li>ราคาอ้างอิงจากตลาดโลก อัพเดททุก 5 นาที</li>
            <li>ค่าธรรมเนียมการแลกเปลี่ยนแตกต่างกันไปตามสกุลเงิน</li>
            <li>การแลกเปลี่ยนจะเสร็จสิ้นทันที ไม่ต้องรอยืนยันจาก Blockchain</li>
            <li>ยอดเงินจะถูกโอนเข้ากระเป๋าที่เลือกโดยอัตโนมัติ</li>
        </ul>
    </div>
</div>
@endsection
