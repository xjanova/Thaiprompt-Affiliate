@extends('layouts.user-v4')

@section('title', 'จัดการกระเป๋าคริปโต')

@section('content')
<div style="display:flex; flex-direction:column; gap:18px;">

    {{-- ── Hero ─────────────────────────────────────────────── --}}
    <div class="tp-card" style="padding:22px 24px; background:linear-gradient(120deg, color-mix(in srgb, var(--accent1) 16%, transparent), transparent 70%);">
        <div style="display:flex; flex-wrap:wrap; align-items:center; justify-content:space-between; gap:14px;">
            <div style="display:flex; align-items:center; gap:14px;">
                <span class="tp-tile" style="width:56px; height:56px; border-radius:18px; display:flex; align-items:center; justify-content:center; font-size:24px; color:var(--deep1);"><i class="fas fa-briefcase"></i></span>
                <div>
                    <h1 style="font-size:clamp(19px,3.5vw,26px); font-weight:800; margin:0; color:var(--ink);">👛 จัดการกระเป๋าคริปโต</h1>
                    <div style="font-size:13px; color:var(--ink2); margin-top:2px;">กระเป๋าทั้งหมดของคุณ</div>
                </div>
            </div>
            <a href="{{ route('user.crypto-wallet.index') }}" class="tp-btn" style="display:inline-flex; align-items:center; gap:8px; padding:10px 16px; border-radius:13px; background:var(--surf); box-shadow:var(--inset-sm); color:var(--ink); font-weight:600; font-size:13.5px; text-decoration:none;">
                <i class="fas fa-arrow-left"></i> <span>กลับหน้าหลัก</span>
            </a>
        </div>
    </div>

    {{-- ── Flash ─────────────────────────────────────────────── --}}
    @if(session('success'))
        <div class="tp-card" style="padding:14px 18px; background:color-mix(in srgb, #5aa07e 12%, transparent); border:1px solid color-mix(in srgb, #5aa07e 30%, transparent); color:var(--ink); font-size:14px;">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="tp-card" style="padding:14px 18px; background:color-mix(in srgb, #d9534f 10%, transparent); border:1px solid color-mix(in srgb, #d9534f 30%, transparent); color:#d9534f; font-size:14px;">{{ session('error') }}</div>
    @endif

    {{-- ── ปุ่มเพิ่มกระเป๋า ───────────────────────────────────── --}}
    <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(260px,1fr)); gap:14px;">
        <button type="button" onclick="document.getElementById('createWalletModal').classList.remove('hidden')"
                class="tp-card" style="padding:20px 22px; text-align:left; border:none; cursor:pointer; box-shadow:var(--raise); background:linear-gradient(120deg, color-mix(in srgb, #5aa07e 18%, transparent), transparent 70%);">
            <div style="display:flex; align-items:center; gap:16px;">
                <span class="tp-tile" style="width:56px; height:56px; border-radius:50%; display:flex; align-items:center; justify-content:center; font-size:28px;">🆕</span>
                <div>
                    <h3 style="font-size:17px; font-weight:800; color:var(--ink); margin:0 0 3px;">สร้างกระเป๋าใหม่</h3>
                    <p style="font-size:12.5px; color:var(--ink2); margin:0;">Custodial Wallet - ระบบจัดการให้</p>
                </div>
            </div>
        </button>
        <button type="button" onclick="alert('ฟีเจอร์นี้จะพร้อมใช้งานเร็วๆ นี้')"
                class="tp-card" style="padding:20px 22px; text-align:left; border:none; cursor:pointer; box-shadow:var(--inset-sm);">
            <div style="display:flex; align-items:center; gap:16px;">
                <span class="tp-tile" style="width:56px; height:56px; border-radius:50%; display:flex; align-items:center; justify-content:center; font-size:28px;">🦊</span>
                <div>
                    <h3 style="font-size:17px; font-weight:800; color:var(--ink); margin:0 0 3px;">เชื่อมต่อกระเป๋า</h3>
                    <p style="font-size:12.5px; color:var(--ink2); margin:0;">MetaMask, WalletConnect</p>
                </div>
            </div>
        </button>
    </div>

    {{-- ── รายการกระเป๋า ─────────────────────────────────────── --}}
    @forelse($wallets as $wallet)
        <div class="tp-card" style="padding:0; overflow:hidden;">
            {{-- header --}}
            <div style="padding:20px 22px; background:linear-gradient(120deg, color-mix(in srgb, var(--accent1) {{ $wallet->is_default ? '22' : '10' }}%, transparent), transparent 72%);">
                <div style="display:flex; align-items:center; justify-content:space-between; gap:12px; flex-wrap:wrap;">
                    <div>
                        <div style="display:flex; align-items:center; gap:9px; margin-bottom:5px;">
                            <h3 style="font-size:20px; font-weight:800; color:var(--ink); margin:0;">{{ $wallet->name }}</h3>
                            @if($wallet->is_default)<span class="tp-pill" style="background:color-mix(in srgb, var(--accent1) 22%, transparent); color:var(--deep1); font-size:11px; font-weight:700;">กระเป๋าหลัก</span>@endif
                        </div>
                        <p style="font-size:13px; color:var(--ink2); margin:0;">{{ $wallet->wallet_type === 'custodial' ? '🔐 Custodial Wallet' : '🦊 External Wallet' }}</p>
                    </div>
                    <div style="text-align:right;">
                        <div style="font-size:12px; color:var(--ink2); margin-bottom:4px;">สถานะ</div>
                        @if($wallet->status === 'active')
                            <span class="tp-pill" style="background:color-mix(in srgb, #5aa07e 18%, transparent); color:#5aa07e; font-size:12px; font-weight:700;">✓ ใช้งาน</span>
                        @elseif($wallet->status === 'locked')
                            <span class="tp-pill" style="background:color-mix(in srgb, #d9534f 18%, transparent); color:#d9534f; font-size:12px; font-weight:700;">🔒 ล็อค</span>
                        @else
                            <span class="tp-pill" style="background:color-mix(in srgb, var(--ink2) 14%, transparent); color:var(--ink2); font-size:12px; font-weight:700;">{{ $wallet->status }}</span>
                        @endif
                    </div>
                </div>
            </div>

            {{-- body --}}
            <div style="padding:22px;">
                <h4 style="font-weight:800; color:var(--ink); margin:0 0 12px; font-size:14.5px;">ที่อยู่กระเป๋า (Addresses)</h4>
                @if($wallet->cryptoAddresses->count() > 0)
                    <div style="display:flex; flex-direction:column; gap:8px; margin-bottom:16px;">
                        @foreach($wallet->cryptoAddresses->take(5) as $address)
                            @php $addrIconPath = public_path('icons/cryptocurrency/' . strtolower($address->currency->code) . '.svg'); @endphp
                            <div class="tp-card" style="padding:12px 14px; box-shadow:var(--inset-sm); display:flex; align-items:center; justify-content:space-between; gap:12px;">
                                <div style="display:flex; align-items:center; gap:12px; min-width:0; flex:1;">
                                    @if(file_exists($addrIconPath))
                                        <img src="{{ asset('icons/cryptocurrency/' . strtolower($address->currency->code) . '.svg') }}" alt="{{ $address->currency->code }}" style="width:40px; height:40px; flex-shrink:0;">
                                    @else
                                        <span class="tp-tile" style="width:40px; height:40px; border-radius:50%; display:flex; align-items:center; justify-content:center; font-weight:800; color:var(--deep1); flex-shrink:0;">{{ substr($address->currency->code, 0, 1) }}</span>
                                    @endif
                                    <div style="min-width:0; flex:1;">
                                        <div style="font-weight:700; color:var(--ink); font-size:14px;">{{ $address->currency->code }}</div>
                                        <div style="font-family:monospace; font-size:11.5px; color:var(--ink2); overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">{{ $address->address }}</div>
                                    </div>
                                </div>
                                <div style="text-align:right; flex-shrink:0;">
                                    <div class="tp-num" style="font-weight:800; color:var(--ink); font-size:14px;">{{ number_format($address->balance, 8) }}</div>
                                    @if($address->balance > 0)<div style="font-size:11.5px; color:var(--ink2);">≈ ฿{{ number_format($address->balance_in_thb, 2) }}</div>@endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div style="text-align:center; padding:24px 0; margin-bottom:16px;">
                        <div style="font-size:36px; margin-bottom:8px;">📭</div>
                        <p style="font-size:13px; color:var(--ink2); margin:0;">ยังไม่มีที่อยู่ในกระเป๋านี้</p>
                    </div>
                @endif

                {{-- stats --}}
                <div class="tp-card" style="padding:14px; box-shadow:var(--inset-sm); display:grid; grid-template-columns:repeat(3,1fr); gap:12px; margin-bottom:16px;">
                    <div style="text-align:center;">
                        <div class="tp-num" style="font-size:20px; font-weight:800; color:var(--ink);">{{ $wallet->cryptoAddresses->count() }}</div>
                        <div style="font-size:11.5px; color:var(--ink2);">Addresses</div>
                    </div>
                    <div style="text-align:center;">
                        <div class="tp-num" style="font-size:20px; font-weight:800; color:var(--ink);">{{ $wallet->total_transactions }}</div>
                        <div style="font-size:11.5px; color:var(--ink2);">Transactions</div>
                    </div>
                    <div style="text-align:center;">
                        <div style="font-size:13px; font-weight:700; color:var(--ink); line-height:1.3; padding-top:4px;">{{ $wallet->last_activity_at ? $wallet->last_activity_at->diffForHumans() : '-' }}</div>
                        <div style="font-size:11.5px; color:var(--ink2);">Last Activity</div>
                    </div>
                </div>

                {{-- actions --}}
                <div style="display:flex; flex-wrap:wrap; gap:9px;">
                    @if(!$wallet->is_default)
                        <form action="{{ route('user.crypto-wallet.wallet.set-default', $wallet->id) }}" method="POST" style="flex:1; min-width:160px;">
                            @csrf
                            <button type="submit" class="tp-btn" style="width:100%; padding:10px; border-radius:12px; background:color-mix(in srgb, var(--accent1) 16%, transparent); color:var(--deep1); font-weight:600; font-size:13px; border:none; cursor:pointer;">⭐ ตั้งเป็นกระเป๋าหลัก</button>
                        </form>
                    @endif
                    @if($wallet->wallet_type === 'custodial')
                        <button type="button" onclick="alert('ฟีเจอร์ Export Private Key จะพร้อมใช้งานเร็วๆ นี้')" class="tp-btn" style="flex:1; min-width:130px; padding:10px; border-radius:12px; background:var(--surf); box-shadow:var(--inset-sm); color:var(--ink); font-weight:600; font-size:13px; border:none; cursor:pointer;">🔑 Export Key</button>
                    @endif
                    <form action="{{ route('user.crypto-wallet.wallet.delete', $wallet->id) }}" method="POST"
                          onsubmit="return confirm('คุณแน่ใจที่จะลบกระเป๋านี้? (ต้องไม่มียอดเงิน)')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="tp-btn" style="padding:10px 15px; border-radius:12px; background:color-mix(in srgb, #d9534f 16%, transparent); color:#d9534f; font-weight:600; font-size:13px; border:none; cursor:pointer;">🗑️ ลบ</button>
                    </form>
                </div>
            </div>
        </div>
    @empty
        <div class="tp-card" style="padding:52px 24px; text-align:center;">
            <div style="font-size:56px; margin-bottom:14px;">👛</div>
            <p style="color:var(--ink2); font-size:14px; margin:0 0 20px;">คุณยังไม่มีกระเป๋าคริปโต</p>
            <button type="button" onclick="document.getElementById('createWalletModal').classList.remove('hidden')"
                    class="tp-btn" style="padding:11px 24px; border-radius:13px; background:linear-gradient(135deg, var(--accent1), var(--accent2)); color:#fff; font-weight:700; font-size:14px; box-shadow:var(--raise); border:none; cursor:pointer;">สร้างกระเป๋าใหม่</button>
        </div>
    @endforelse
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
                <input type="text" name="name" value="My Crypto Wallet" required style="width:100%; padding:11px 14px; border-radius:12px; background:var(--surf); box-shadow:var(--inset-sm); border:1px solid transparent; color:var(--ink); font-size:14px;">
            </div>
            <div style="margin-bottom:14px;">
                <label style="display:block; font-size:13px; font-weight:700; color:var(--ink); margin-bottom:7px;">PIN (4-6 หลัก)</label>
                <input type="password" name="pin" required minlength="4" maxlength="6" style="width:100%; padding:11px 14px; border-radius:12px; background:var(--surf); box-shadow:var(--inset-sm); border:1px solid transparent; color:var(--ink); font-size:14px;" placeholder="ตั้ง PIN สำหรับกระเป๋านี้">
            </div>
            <div style="margin-bottom:18px;">
                <label style="display:block; font-size:13px; font-weight:700; color:var(--ink); margin-bottom:7px;">ยืนยัน PIN</label>
                <input type="password" name="pin_confirmation" required minlength="4" maxlength="6" style="width:100%; padding:11px 14px; border-radius:12px; background:var(--surf); box-shadow:var(--inset-sm); border:1px solid transparent; color:var(--ink); font-size:14px;" placeholder="ยืนยัน PIN อีกครั้ง">
            </div>
            <div class="tp-card" style="padding:12px 14px; box-shadow:var(--inset-sm); margin-bottom:16px;">
                <p style="font-size:13px; color:var(--ink2); margin:0;">⚠️ กรุณาจดจำ PIN ของคุณ จะต้องใช้ทุกครั้งที่ทำธุรกรรม</p>
            </div>
            <div style="display:flex; gap:10px;">
                <button type="button" onclick="document.getElementById('createWalletModal').classList.add('hidden')" class="tp-btn" style="flex:1; padding:11px; border-radius:13px; background:var(--surf); box-shadow:var(--inset-sm); color:var(--ink); font-weight:600; font-size:14px; border:none; cursor:pointer;">ยกเลิก</button>
                <button type="submit" class="tp-btn" style="flex:1; padding:11px; border-radius:13px; background:linear-gradient(135deg, var(--accent1), var(--accent2)); color:#fff; font-weight:700; font-size:14px; box-shadow:var(--raise); border:none; cursor:pointer;">สร้างกระเป๋า</button>
            </div>
        </form>
    </div>
</div>
@endsection
