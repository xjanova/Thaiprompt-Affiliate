@extends('layouts.user-v4')

@section('title', 'กระเป๋าคริปโต')

@section('content')
<div style="display:flex; flex-direction:column; gap:18px;">

    {{-- ── Hero + ยอดรวม + เมนูด่วน ───────────────────────────── --}}
    <div class="tp-card" style="padding:24px 26px; background:linear-gradient(120deg, color-mix(in srgb, var(--accent1) 18%, transparent), transparent 72%);">
        <div style="display:flex; flex-wrap:wrap; align-items:center; justify-content:space-between; gap:18px; margin-bottom:18px;">
            <div style="display:flex; align-items:center; gap:14px;">
                <span class="tp-tile" style="width:56px; height:56px; border-radius:18px; display:flex; align-items:center; justify-content:center; font-size:24px; color:var(--deep1);">
                    <i class="fas fa-coins"></i>
                </span>
                <div>
                    <h1 style="font-size:clamp(19px,3.5vw,26px); font-weight:800; margin:0; color:var(--ink);">💰 กระเป๋าคริปโต</h1>
                    <div style="font-size:13px; color:var(--ink2); margin-top:2px;">จัดการเหรียญคริปโตของคุณ</div>
                </div>
            </div>
            <div class="tp-card" style="padding:18px 22px; min-width:230px; box-shadow:var(--inset-sm);">
                <div style="font-size:12px; color:var(--ink2); margin-bottom:5px;">ยอดรวมทั้งหมด</div>
                <div class="tp-num" style="font-size:30px; font-weight:800; line-height:1; color:var(--deep1);">฿{{ number_format($totalValueTHB, 2) }}</div>
                <div style="font-size:12px; color:var(--ink2); margin-top:5px;">≈ ${{ number_format($totalValueTHB / 33, 2) }} USD</div>
            </div>
        </div>

        @php
            $cwActions = [
                ['user.crypto-wallet.deposit',      '📥', 'ฝากเหรียญ'],
                ['user.crypto-wallet.withdraw',     '📤', 'ถอนเหรียญ'],
                ['user.crypto-wallet.exchange',     '💱', 'แลกเปลี่ยน'],
                ['user.crypto-wallet.transactions', '📝', 'ธุรกรรม'],
            ];
        @endphp
        <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(120px,1fr)); gap:12px;">
            @foreach($cwActions as [$route, $icon, $label])
                @if(\Illuminate\Support\Facades\Route::has($route))
                    <a href="{{ route($route) }}" class="tp-card" style="padding:15px 12px; text-align:center; text-decoration:none; box-shadow:var(--inset-sm);">
                        <div style="font-size:24px; margin-bottom:4px;">{{ $icon }}</div>
                        <div style="font-size:13px; font-weight:600; color:var(--ink);">{{ $label }}</div>
                    </a>
                @endif
            @endforeach
        </div>
    </div>

    {{-- ── Flash ─────────────────────────────────────────────── --}}
    @if(session('success'))
        <div class="tp-card" style="padding:14px 18px; background:color-mix(in srgb, #5aa07e 12%, transparent); border:1px solid color-mix(in srgb, #5aa07e 30%, transparent); color:var(--ink); font-size:14px;">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="tp-card" style="padding:14px 18px; background:color-mix(in srgb, #d9534f 10%, transparent); border:1px solid color-mix(in srgb, #d9534f 30%, transparent); color:#d9534f; font-size:14px;">{{ session('error') }}</div>
    @endif

    <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(300px,1fr)); gap:18px;">
        {{-- ── ยอดคงเหลือแต่ละเหรียญ ─────────────────────────── --}}
        <div class="tp-card" style="padding:22px 24px;">
            <h3 style="font-size:16px; font-weight:800; color:var(--ink); margin:0 0 16px;">ยอดคงเหลือ</h3>
            @if($wallet && count($balances) > 0)
                <div style="display:flex; flex-direction:column; gap:10px;">
                    @foreach($balances as $code => $balance)
                        @php $iconPath = public_path('icons/cryptocurrency/' . strtolower($code) . '.svg'); @endphp
                        <div class="tp-card" style="padding:13px 15px; box-shadow:var(--inset-sm); display:flex; align-items:center; justify-content:space-between; gap:12px;">
                            <div style="display:flex; align-items:center; gap:12px; min-width:0;">
                                @if(file_exists($iconPath))
                                    <img src="{{ asset('icons/cryptocurrency/' . strtolower($code) . '.svg') }}" alt="{{ $code }}" style="width:40px; height:40px; flex-shrink:0;">
                                @else
                                    <span class="tp-tile" style="width:40px; height:40px; border-radius:50%; display:flex; align-items:center; justify-content:center; font-weight:800; color:var(--deep1); flex-shrink:0;">{{ substr($code, 0, 1) }}</span>
                                @endif
                                <div style="min-width:0;">
                                    <div style="font-weight:700; color:var(--ink); font-size:14px;">{{ $balance['currency']->name }}</div>
                                    <div style="font-size:12.5px; color:var(--ink2);">{{ $code }}</div>
                                </div>
                            </div>
                            <div style="text-align:right; flex-shrink:0;">
                                <div class="tp-num" style="font-weight:800; color:var(--ink); font-size:14px;">{{ number_format($balance['balance'], 8) }}</div>
                                <div style="font-size:12px; color:var(--ink2);">≈ ฿{{ number_format($balance['balance_thb'], 2) }}</div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div style="text-align:center; padding:32px 0;">
                    <div style="font-size:52px; margin-bottom:14px;">🪙</div>
                    <p style="color:var(--ink2); font-size:14px; margin:0 0 18px;">ยังไม่มียอดคงเหลือ</p>
                    @if(\Illuminate\Support\Facades\Route::has('user.crypto-wallet.deposit'))
                        <a href="{{ route('user.crypto-wallet.deposit') }}" class="tp-btn" style="display:inline-block; padding:10px 22px; border-radius:13px; background:linear-gradient(135deg, var(--accent1), var(--accent2)); color:#fff; font-weight:700; font-size:14px; box-shadow:var(--raise);">ฝากเหรียญ</a>
                    @endif
                </div>
            @endif
        </div>

        {{-- ── ข้อมูลกระเป๋า ─────────────────────────────────── --}}
        <div class="tp-card" style="padding:22px 24px;">
            <h3 style="font-size:16px; font-weight:800; color:var(--ink); margin:0 0 16px;">กระเป๋าของฉัน</h3>
            @if($wallet)
                <div class="tp-card" style="padding:16px 18px; box-shadow:var(--inset-sm); margin-bottom:14px;">
                    <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:10px;">
                        <span style="font-weight:600; color:var(--ink2); font-size:13.5px;">ชื่อกระเป๋า</span>
                        <span style="font-weight:700; color:var(--ink); font-size:14px;">{{ $wallet->name }}</span>
                    </div>
                    <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:10px;">
                        <span style="font-weight:600; color:var(--ink2); font-size:13.5px;">ประเภท</span>
                        <span class="tp-pill" style="background:color-mix(in srgb, var(--accent1) 18%, transparent); color:var(--deep1); font-size:12px; font-weight:700;">{{ $wallet->wallet_type === 'custodial' ? '🔐 Custodial' : '🦊 External' }}</span>
                    </div>
                    <div style="display:flex; align-items:center; justify-content:space-between;">
                        <span style="font-weight:600; color:var(--ink2); font-size:13.5px;">สถานะ</span>
                        @php $wActive = $wallet->status === 'active'; @endphp
                        <span class="tp-pill" style="background:color-mix(in srgb, {{ $wActive ? '#5aa07e' : '#d9534f' }} 18%, transparent); color:{{ $wActive ? '#5aa07e' : '#d9534f' }}; font-size:12px; font-weight:700;">{{ $wActive ? '✓ ใช้งาน' : '✕ ไม่ใช้งาน' }}</span>
                    </div>
                </div>
                @if(\Illuminate\Support\Facades\Route::has('user.crypto-wallet.wallets'))
                    <a href="{{ route('user.crypto-wallet.wallets') }}" class="tp-btn" style="display:block; text-align:center; padding:11px; border-radius:13px; background:var(--surf); box-shadow:var(--inset-sm); color:var(--ink); font-weight:600; font-size:14px; text-decoration:none;">จัดการกระเป๋า</a>
                @endif
            @else
                <div style="text-align:center; padding:26px 0;">
                    <div style="font-size:44px; margin-bottom:12px;">🆕</div>
                    <p style="color:var(--ink2); font-size:14px; margin:0 0 18px;">คุณยังไม่มีกระเป๋าคริปโต</p>
                    <button type="button" onclick="document.getElementById('createWalletModal').classList.remove('hidden')"
                            class="tp-btn" style="padding:10px 22px; border-radius:13px; background:linear-gradient(135deg, var(--accent1), var(--accent2)); color:#fff; font-weight:700; font-size:14px; box-shadow:var(--raise); border:none; cursor:pointer;">สร้างกระเป๋าใหม่</button>
                </div>
            @endif
        </div>
    </div>

    {{-- ── ธุรกรรมล่าสุด ─────────────────────────────────────── --}}
    <div class="tp-card" style="padding:22px 24px;">
        <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:16px;">
            <h3 style="font-size:16px; font-weight:800; color:var(--ink); margin:0;">ธุรกรรมล่าสุด</h3>
            @if(\Illuminate\Support\Facades\Route::has('user.crypto-wallet.transactions'))
                <a href="{{ route('user.crypto-wallet.transactions') }}" style="color:var(--deep1); font-weight:700; font-size:13px; text-decoration:none;">ดูทั้งหมด →</a>
            @endif
        </div>
        @if($recentTransactions->count() > 0)
            <div style="overflow-x:auto;">
                <table style="width:100%; border-collapse:collapse; min-width:560px;">
                    <thead>
                        <tr style="border-bottom:1px solid color-mix(in srgb, var(--ink2) 18%, transparent);">
                            <th style="padding:0 8px 12px 0; text-align:left; font-size:11px; font-weight:700; color:var(--ink2); text-transform:uppercase;">ประเภท</th>
                            <th style="padding:0 8px 12px; text-align:left; font-size:11px; font-weight:700; color:var(--ink2); text-transform:uppercase;">สกุลเงิน</th>
                            <th style="padding:0 8px 12px; text-align:left; font-size:11px; font-weight:700; color:var(--ink2); text-transform:uppercase;">จำนวน</th>
                            <th style="padding:0 8px 12px; text-align:left; font-size:11px; font-weight:700; color:var(--ink2); text-transform:uppercase;">สถานะ</th>
                            <th style="padding:0 0 12px 8px; text-align:left; font-size:11px; font-weight:700; color:var(--ink2); text-transform:uppercase;">เวลา</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($recentTransactions as $tx)
                            <tr style="border-bottom:1px solid color-mix(in srgb, var(--ink2) 12%, transparent);">
                                <td style="padding:12px 8px 12px 0;"><span class="tp-pill" style="background:color-mix(in srgb, var(--ink2) 12%, transparent); color:var(--ink2); font-size:11px;">{{ $tx->type }}</span></td>
                                <td style="padding:12px 8px; font-weight:700; color:var(--ink); font-size:13.5px;">{{ $tx->currency->code }}</td>
                                <td class="tp-num" style="padding:12px 8px; font-weight:700; font-size:13.5px; color:{{ $tx->is_incoming ? '#5aa07e' : '#d9534f' }};">{{ $tx->is_incoming ? '+' : '-' }}{{ number_format($tx->amount, 8) }}</td>
                                <td style="padding:12px 8px; font-size:13px;">
                                    @if($tx->status === 'completed')
                                        <span style="color:#5aa07e;">✓ สำเร็จ</span>
                                    @elseif($tx->status === 'pending')
                                        <span style="color:#e0a52e;">⏳ รอดำเนินการ</span>
                                    @else
                                        <span style="color:var(--ink2);">{{ $tx->status }}</span>
                                    @endif
                                </td>
                                <td style="padding:12px 0 12px 8px; color:var(--ink2); font-size:12.5px;">{{ $tx->created_at->diffForHumans() }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div style="text-align:center; padding:32px 0;">
                <div style="font-size:44px; margin-bottom:12px;">📝</div>
                <p style="color:var(--ink2); font-size:14px; margin:0;">ยังไม่มีธุรกรรม</p>
            </div>
        @endif
    </div>
</div>

{{-- ── Modal สร้างกระเป๋า ────────────────────────────────────── --}}
<div id="createWalletModal" class="hidden" style="position:fixed; inset:0; background:rgba(0,0,0,.5); display:flex; align-items:center; justify-content:center; z-index:120; padding:16px;"
     onclick="if(event.target === this) this.classList.add('hidden')">
    <div class="tp-card" style="max-width:440px; width:100%; padding:26px;" onclick="event.stopPropagation()">
        <h3 style="font-size:19px; font-weight:800; color:var(--ink); margin:0 0 18px;">สร้างกระเป๋าคริปโต</h3>
        <form action="{{ route('user.crypto-wallet.create-wallet') }}" method="POST">
            @csrf
            <div style="margin-bottom:14px;">
                <label style="display:block; font-size:13px; font-weight:700; color:var(--ink); margin-bottom:7px;">ชื่อกระเป๋า</label>
                <input type="text" name="name" value="My Crypto Wallet" required
                       style="width:100%; padding:11px 14px; border-radius:12px; background:var(--surf); box-shadow:var(--inset-sm); border:1px solid transparent; color:var(--ink); font-size:14px;">
            </div>
            <div style="margin-bottom:14px;">
                <label style="display:block; font-size:13px; font-weight:700; color:var(--ink); margin-bottom:7px;">PIN (4-6 หลัก)</label>
                <input type="password" name="pin" required minlength="4" maxlength="6"
                       style="width:100%; padding:11px 14px; border-radius:12px; background:var(--surf); box-shadow:var(--inset-sm); border:1px solid transparent; color:var(--ink); font-size:14px;"
                       placeholder="ตั้ง PIN สำหรับกระเป๋านี้">
            </div>
            <div style="margin-bottom:18px;">
                <label style="display:block; font-size:13px; font-weight:700; color:var(--ink); margin-bottom:7px;">ยืนยัน PIN</label>
                <input type="password" name="pin_confirmation" required minlength="4" maxlength="6"
                       style="width:100%; padding:11px 14px; border-radius:12px; background:var(--surf); box-shadow:var(--inset-sm); border:1px solid transparent; color:var(--ink); font-size:14px;"
                       placeholder="ยืนยัน PIN อีกครั้ง">
            </div>
            <div class="tp-card" style="padding:12px 14px; box-shadow:var(--inset-sm); margin-bottom:16px;">
                <p style="font-size:13px; color:var(--ink2); margin:0;">⚠️ กรุณาจดจำ PIN ของคุณ จะต้องใช้ทุกครั้งที่ทำธุรกรรม</p>
            </div>
            <div style="display:flex; gap:10px;">
                <button type="button" onclick="document.getElementById('createWalletModal').classList.add('hidden')"
                        class="tp-btn" style="flex:1; padding:11px; border-radius:13px; background:var(--surf); box-shadow:var(--inset-sm); color:var(--ink); font-weight:600; font-size:14px; border:none; cursor:pointer;">ยกเลิก</button>
                <button type="submit"
                        class="tp-btn" style="flex:1; padding:11px; border-radius:13px; background:linear-gradient(135deg, var(--accent1), var(--accent2)); color:#fff; font-weight:700; font-size:14px; box-shadow:var(--raise); border:none; cursor:pointer;">สร้างกระเป๋า</button>
            </div>
        </form>
    </div>
</div>
@endsection
