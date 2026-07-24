@extends('layouts.user-v4')

@section('title', 'Wallet Management')

@section('content')
<div style="display:flex; flex-direction:column; gap:18px;" x-data="walletManagement()">

    {{-- ── Header + Stats ─────────────────────────────────────── --}}
    <div class="tp-card" style="padding:26px 28px; background:linear-gradient(120deg, color-mix(in srgb, var(--accent1) 18%, transparent), transparent 72%);">
        <div style="display:flex; flex-wrap:wrap; justify-content:space-between; align-items:flex-start; gap:18px;">
            <div>
                <h1 style="font-size:clamp(24px,5vw,34px); font-weight:800; margin:0; color:var(--ink);">💼 Wallet Management</h1>
                <p style="font-size:15px; color:var(--ink2); margin:4px 0 0;">จัดการกระเป๋าเงินคริปโตของคุณ</p>
            </div>
            <button type="button" @click="showCreateModal = true" class="tp-btn" style="display:inline-flex; align-items:center; gap:8px; padding:13px 22px; border-radius:14px; background:linear-gradient(135deg, var(--accent1), var(--accent2)); color:#fff; font-weight:700; font-size:14.5px; box-shadow:var(--raise); border:none; cursor:pointer;">
                <i class="fas fa-plus"></i> สร้างกระเป๋าใหม่
            </button>
        </div>
        <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(150px,1fr)); gap:12px; margin-top:20px;">
            @php
                $wmStats = [
                    ['Total Wallets', count($wallets ?? []), 'var(--ink)'],
                    ['Active Wallets', $wallets->where('status', 'active')->count(), '#5aa07e'],
                    ['Total Assets', $totalAssets ?? 0, 'var(--ink)'],
                    ['Total Value', '฿' . number_format($totalValue ?? 0, 2), 'var(--ink)'],
                ];
            @endphp
            @foreach($wmStats as [$label, $val, $color])
                <div class="tp-card" style="padding:16px 18px; box-shadow:var(--inset-sm);">
                    <div style="font-size:12px; color:var(--ink2); margin-bottom:5px;">{{ $label }}</div>
                    <div class="tp-num" style="font-size:24px; font-weight:800; color:{{ $color }};">{{ $val }}</div>
                </div>
            @endforeach
        </div>
    </div>

    {{-- ── Wallets Grid ───────────────────────────────────────── --}}
    <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(340px,1fr)); gap:18px;">
        @forelse($wallets ?? [] as $wallet)
            <div class="tp-card" style="padding:24px; {{ $wallet->is_default ? 'box-shadow:var(--card-shadow), 0 0 0 3px color-mix(in srgb, var(--accent1) 45%, transparent);' : '' }}">
                {{-- header --}}
                <div style="display:flex; align-items:flex-start; justify-content:space-between; gap:12px; margin-bottom:20px;">
                    <div style="display:flex; align-items:center; gap:14px;">
                        <span class="tp-tile" style="width:60px; height:60px; border-radius:18px; display:flex; align-items:center; justify-content:center; font-size:28px;">{{ $wallet->type === 'custodial' ? '🔒' : '🌐' }}</span>
                        <div>
                            <h3 style="font-size:20px; font-weight:800; color:var(--ink); margin:0 0 6px;">{{ $wallet->name ?? 'Wallet #' . $wallet->id }}</h3>
                            <div style="display:flex; align-items:center; gap:6px; flex-wrap:wrap;">
                                @php $wmType = $wallet->type === 'custodial' ? '#5689b8' : '#d6824a'; $wmActive = $wallet->status === 'active'; @endphp
                                <span class="tp-pill" style="background:color-mix(in srgb, {{ $wmType }} 18%, transparent); color:{{ $wmType }}; font-size:11px; font-weight:700;">{{ ucfirst($wallet->type) }}</span>
                                <span class="tp-pill" style="background:color-mix(in srgb, {{ $wmActive ? '#5aa07e' : '#d9534f' }} 18%, transparent); color:{{ $wmActive ? '#5aa07e' : '#d9534f' }}; font-size:11px; font-weight:700;">{{ ucfirst($wallet->status) }}</span>
                                @if($wallet->is_default)<span class="tp-pill" style="background:color-mix(in srgb, var(--accent1) 20%, transparent); color:var(--deep1); font-size:11px; font-weight:700;">⭐ Default</span>@endif
                            </div>
                        </div>
                    </div>
                    {{-- dropdown --}}
                    <div x-data="{ open: false }" style="position:relative;">
                        <button type="button" @click="open = !open" class="tp-btn" style="width:38px; height:38px; border-radius:11px; background:var(--surf); box-shadow:var(--inset-sm); color:var(--ink2); border:none; cursor:pointer;"><i class="fas fa-ellipsis-vertical"></i></button>
                        <div x-show="open" x-cloak @click.away="open = false" x-transition.opacity
                             class="tp-card" style="position:absolute; right:0; margin-top:8px; width:190px; padding:6px; z-index:20; box-shadow:var(--card-shadow-hover);">
                            @if(!$wallet->is_default)
                                <form action="{{ route('user.crypto-wallet.wallet.set-default', $wallet->id) }}" method="POST">
                                    @csrf
                                    <button type="submit" style="width:100%; text-align:left; padding:9px 12px; border-radius:8px; font-size:13px; color:var(--ink); background:none; border:none; cursor:pointer;">⭐ Set as Default</button>
                                </form>
                            @endif
                            <button type="button" @click="editWallet({{ $wallet->id }})" style="width:100%; text-align:left; padding:9px 12px; border-radius:8px; font-size:13px; color:var(--ink); background:none; border:none; cursor:pointer;">✏️ Edit Name</button>
                            <button type="button" @click="viewDetails({{ $wallet->id }})" style="width:100%; text-align:left; padding:9px 12px; border-radius:8px; font-size:13px; color:var(--ink); background:none; border:none; cursor:pointer;">👁️ View Details</button>
                            <div style="border-top:1px solid color-mix(in srgb, var(--ink2) 15%, transparent); margin:5px 0;"></div>
                            <button type="button" @click="deleteWallet({{ $wallet->id }})" style="width:100%; text-align:left; padding:9px 12px; border-radius:8px; font-size:13px; color:#d9534f; background:none; border:none; cursor:pointer;">🗑️ Delete Wallet</button>
                        </div>
                    </div>
                </div>

                {{-- address (external) --}}
                @if($wallet->type === 'external')
                    <div style="margin-bottom:20px;">
                        <div style="font-size:11.5px; color:var(--ink2); margin-bottom:6px;">Wallet Address</div>
                        <div class="tp-card" style="padding:11px 13px; box-shadow:var(--inset-sm); display:flex; align-items:center; gap:9px;">
                            <code style="flex:1; font-size:12.5px; color:var(--ink); font-family:monospace; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">{{ $wallet->addresses->first()->address ?? 'N/A' }}</code>
                            <button type="button" @click="copyToClipboard('{{ $wallet->addresses->first()->address ?? '' }}')" class="tp-btn" style="padding:6px 13px; border-radius:9px; background:color-mix(in srgb, var(--accent1) 16%, transparent); color:var(--deep1); font-size:12px; font-weight:600; border:none; cursor:pointer;">Copy</button>
                        </div>
                    </div>
                @endif

                {{-- assets --}}
                <div style="margin-bottom:20px;">
                    <div style="font-size:13px; color:var(--ink2); margin-bottom:11px;">Assets</div>
                    @php $walletBalances = $balancesByWallet[$wallet->id] ?? []; @endphp
                    <div style="display:grid; grid-template-columns:repeat(3,1fr); gap:10px;">
                        @forelse($walletBalances as $balance)
                            <div class="tp-card" style="padding:11px; text-align:center; box-shadow:var(--inset-sm);">
                                <div style="font-size:11px; color:var(--ink2);">{{ $balance['code'] }}</div>
                                <div class="tp-num" style="font-size:13.5px; font-weight:800; color:var(--ink); margin-top:3px;">{{ number_format($balance['balance'], 4) }}</div>
                            </div>
                        @empty
                            <div style="grid-column:1/-1; text-align:center; color:var(--ink2); font-size:13px; padding:16px 0;">No assets in this wallet</div>
                        @endforelse
                    </div>
                </div>

                {{-- stats --}}
                <div style="display:grid; grid-template-columns:repeat(3,1fr); gap:14px; padding-top:18px; border-top:1px solid color-mix(in srgb, var(--ink2) 15%, transparent);">
                    <div style="text-align:center;"><div style="font-size:11px; color:var(--ink2); margin-bottom:4px;">Created</div><div style="font-size:12.5px; font-weight:700; color:var(--ink);">{{ $wallet->created_at->diffForHumans() }}</div></div>
                    <div style="text-align:center;"><div style="font-size:11px; color:var(--ink2); margin-bottom:4px;">Last Used</div><div style="font-size:12.5px; font-weight:700; color:var(--ink);">{{ $wallet->updated_at->diffForHumans() }}</div></div>
                    <div style="text-align:center;"><div style="font-size:11px; color:var(--ink2); margin-bottom:4px;">Value</div><div class="tp-num" style="font-size:12.5px; font-weight:700; color:#5aa07e;">฿{{ number_format($walletBalances->sum('balance_thb') ?? 0, 2) }}</div></div>
                </div>
            </div>
        @empty
            <div class="tp-card" style="grid-column:1/-1; padding:64px 24px; text-align:center;">
                <div style="font-size:56px; margin-bottom:14px;">💳</div>
                <h3 style="font-size:20px; font-weight:800; color:var(--ink); margin:0 0 6px;">No Wallets Yet</h3>
                <p style="color:var(--ink2); font-size:14px; margin:0 0 22px;">Create your first crypto wallet to get started</p>
                <button type="button" @click="showCreateModal = true" class="tp-btn" style="padding:13px 26px; border-radius:14px; background:linear-gradient(135deg, var(--accent1), var(--accent2)); color:#fff; font-weight:700; font-size:15px; box-shadow:var(--raise); border:none; cursor:pointer;">🚀 Create Wallet</button>
            </div>
        @endforelse
    </div>

    {{-- ── Create Modal ───────────────────────────────────────── --}}
    <div x-show="showCreateModal" x-cloak @keydown.escape.window="showCreateModal = false"
         style="position:fixed; inset:0; z-index:120; display:flex; align-items:center; justify-content:center; padding:16px;">
        <div @click="showCreateModal = false" x-show="showCreateModal" x-transition.opacity style="position:fixed; inset:0; background:rgba(0,0,0,.55);"></div>
        <div class="tp-card" x-show="showCreateModal" x-transition style="position:relative; max-width:520px; width:100%; padding:26px; z-index:1;">
            <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:20px;">
                <h3 style="font-size:24px; font-weight:800; color:var(--ink); margin:0;">Create New Wallet</h3>
                <button type="button" @click="showCreateModal = false" class="tp-btn" style="width:36px; height:36px; border-radius:11px; background:var(--surf); box-shadow:var(--inset-sm); color:var(--ink2); border:none; cursor:pointer;"><i class="fas fa-xmark"></i></button>
            </div>
            <div style="display:flex; flex-direction:column; gap:14px;">
                <button type="button" onclick="window.location='{{ route('user.crypto-wallet.index') }}#create-custodial'" class="tp-card" style="width:100%; display:flex; align-items:center; justify-content:space-between; padding:20px; box-shadow:var(--inset-sm); border:none; cursor:pointer;">
                    <div style="display:flex; align-items:center; gap:14px;">
                        <span class="tp-tile" style="width:56px; height:56px; border-radius:16px; display:flex; align-items:center; justify-content:center; font-size:26px;">🔒</span>
                        <div style="text-align:left;"><h4 style="font-size:18px; font-weight:700; color:var(--ink); margin:0;">Custodial Wallet</h4><p style="font-size:12.5px; color:var(--ink2); margin:3px 0 0;">ระบบจัดการให้ ปลอดภัย มี PIN ป้องกัน</p></div>
                    </div>
                    <i class="fas fa-chevron-right" style="color:var(--ink2);"></i>
                </button>
                <button type="button" onclick="window.location='{{ route('user.crypto-wallet.index') }}#connect-external'" class="tp-card" style="width:100%; display:flex; align-items:center; justify-content:space-between; padding:20px; box-shadow:var(--inset-sm); border:none; cursor:pointer;">
                    <div style="display:flex; align-items:center; gap:14px;">
                        <span class="tp-tile" style="width:56px; height:56px; border-radius:16px; display:flex; align-items:center; justify-content:center; font-size:26px;">🌐</span>
                        <div style="text-align:left;"><h4 style="font-size:18px; font-weight:700; color:var(--ink); margin:0;">External Wallet</h4><p style="font-size:12.5px; color:var(--ink2); margin:3px 0 0;">เชื่อมต่อ MetaMask หรือ WalletConnect</p></div>
                    </div>
                    <i class="fas fa-chevron-right" style="color:var(--ink2);"></i>
                </button>
            </div>
        </div>
    </div>
</div>

<script>
function walletManagement() {
    return {
        showCreateModal: false,

        copyToClipboard(text) {
            navigator.clipboard.writeText(text).then(() => {
                alert('คัดลอกแล้ว!');
            });
        },

        editWallet(walletId) {
            const newName = prompt('Enter new wallet name:');
            if (newName) {
                console.log('Rename wallet', walletId, 'to', newName);
            }
        },

        viewDetails(walletId) {
            window.location.href = `/user/crypto-wallet/wallets/${walletId}`;
        },

        async deleteWallet(walletId) {
            if (!confirm('Are you sure you want to delete this wallet? This action cannot be undone.')) {
                return;
            }
            try {
                const response = await fetch(`/user/crypto-wallet/wallet/${walletId}`, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    }
                });
                if (response.ok) {
                    alert('Wallet deleted successfully');
                    window.location.reload();
                } else {
                    alert('Failed to delete wallet');
                }
            } catch (error) {
                console.error('Delete failed:', error);
                alert('An error occurred');
            }
        }
    };
}
</script>
@endsection
