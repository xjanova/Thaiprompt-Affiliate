@extends('layouts.user-v4')

@section('title', 'ประวัติธุรกรรมคริปโต')

@section('content')
<div style="display:flex; flex-direction:column; gap:18px;">

    {{-- ── Hero ─────────────────────────────────────────────── --}}
    <div class="tp-card" style="padding:22px 24px; background:linear-gradient(120deg, color-mix(in srgb, var(--accent1) 16%, transparent), transparent 70%);">
        <div style="display:flex; flex-wrap:wrap; align-items:center; justify-content:space-between; gap:14px;">
            <div style="display:flex; align-items:center; gap:14px;">
                <span class="tp-tile" style="width:56px; height:56px; border-radius:18px; display:flex; align-items:center; justify-content:center; font-size:24px; color:var(--deep1);"><i class="fas fa-list-alt"></i></span>
                <div>
                    <h1 style="font-size:clamp(19px,3.5vw,26px); font-weight:800; margin:0; color:var(--ink);">📋 ประวัติธุรกรรม</h1>
                    <div style="font-size:13px; color:var(--ink2); margin-top:2px;">ธุรกรรมคริปโตทั้งหมดของคุณ</div>
                </div>
            </div>
            <a href="{{ route('user.crypto-wallet.index') }}" class="tp-btn" style="display:inline-flex; align-items:center; gap:8px; padding:10px 16px; border-radius:13px; background:var(--surf); box-shadow:var(--inset-sm); color:var(--ink); font-weight:600; font-size:13.5px; text-decoration:none;">
                <i class="fas fa-arrow-left"></i> <span>กลับหน้าหลัก</span>
            </a>
        </div>
    </div>

    {{-- ── ตัวกรอง ───────────────────────────────────────────── --}}
    <div class="tp-card" style="padding:20px 22px;">
        <form method="GET" action="{{ route('user.crypto-wallet.transactions') }}" style="display:grid; grid-template-columns:repeat(auto-fit,minmax(160px,1fr)); gap:14px; align-items:end;">
            <div>
                <label style="display:block; font-size:12.5px; font-weight:700; color:var(--ink); margin-bottom:6px;">ประเภท</label>
                <select name="type" style="width:100%; padding:10px 12px; border-radius:11px; background:var(--surf); box-shadow:var(--inset-sm); border:1px solid transparent; color:var(--ink); font-size:13.5px;">
                    <option value="">ทั้งหมด</option>
                    <option value="deposit" {{ request('type') === 'deposit' ? 'selected' : '' }}>ฝาก</option>
                    <option value="withdrawal" {{ request('type') === 'withdrawal' ? 'selected' : '' }}>ถอน</option>
                    <option value="exchange_buy" {{ request('type') === 'exchange_buy' ? 'selected' : '' }}>ซื้อ (Exchange)</option>
                    <option value="exchange_sell" {{ request('type') === 'exchange_sell' ? 'selected' : '' }}>ขาย (Exchange)</option>
                </select>
            </div>
            <div>
                <label style="display:block; font-size:12.5px; font-weight:700; color:var(--ink); margin-bottom:6px;">สกุลเงิน</label>
                <select name="currency" style="width:100%; padding:10px 12px; border-radius:11px; background:var(--surf); box-shadow:var(--inset-sm); border:1px solid transparent; color:var(--ink); font-size:13.5px;">
                    <option value="">ทั้งหมด</option>
                    @foreach($currencies as $curr)
                        <option value="{{ $curr->code }}" {{ request('currency') === $curr->code ? 'selected' : '' }}>{{ $curr->code }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label style="display:block; font-size:12.5px; font-weight:700; color:var(--ink); margin-bottom:6px;">สถานะ</label>
                <select name="status" style="width:100%; padding:10px 12px; border-radius:11px; background:var(--surf); box-shadow:var(--inset-sm); border:1px solid transparent; color:var(--ink); font-size:13.5px;">
                    <option value="">ทั้งหมด</option>
                    <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>รอดำเนินการ</option>
                    <option value="completed" {{ request('status') === 'completed' ? 'selected' : '' }}>สำเร็จ</option>
                    <option value="failed" {{ request('status') === 'failed' ? 'selected' : '' }}>ล้มเหลว</option>
                </select>
            </div>
            <button type="submit" class="tp-btn" style="padding:11px; border-radius:12px; background:linear-gradient(135deg, var(--accent1), var(--accent2)); color:#fff; font-weight:700; font-size:14px; box-shadow:var(--raise);">🔍 กรอง</button>
        </form>
    </div>

    {{-- ── ตาราง ─────────────────────────────────────────────── --}}
    <div class="tp-card" style="padding:0; overflow:hidden;">
        @if($transactions->count() > 0)
            <div style="overflow-x:auto;">
                <table style="width:100%; border-collapse:collapse; min-width:760px;">
                    <thead>
                        <tr style="background:color-mix(in srgb, var(--ink2) 8%, transparent);">
                            <th style="padding:14px 20px; text-align:left; font-size:11px; font-weight:700; color:var(--ink2); text-transform:uppercase;">TX ID</th>
                            <th style="padding:14px 20px; text-align:left; font-size:11px; font-weight:700; color:var(--ink2); text-transform:uppercase;">ประเภท</th>
                            <th style="padding:14px 20px; text-align:left; font-size:11px; font-weight:700; color:var(--ink2); text-transform:uppercase;">สกุลเงิน</th>
                            <th style="padding:14px 20px; text-align:right; font-size:11px; font-weight:700; color:var(--ink2); text-transform:uppercase;">จำนวน</th>
                            <th style="padding:14px 20px; text-align:left; font-size:11px; font-weight:700; color:var(--ink2); text-transform:uppercase;">สถานะ</th>
                            <th style="padding:14px 20px; text-align:left; font-size:11px; font-weight:700; color:var(--ink2); text-transform:uppercase;">เวลา</th>
                            <th style="padding:14px 20px;"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($transactions as $tx)
                            @php $txIconPath = public_path('icons/cryptocurrency/' . strtolower($tx->currency->code) . '.svg'); @endphp
                            <tr style="border-top:1px solid color-mix(in srgb, var(--ink2) 13%, transparent);">
                                <td style="padding:14px 20px;"><span style="font-family:monospace; font-size:11.5px; color:var(--ink2);">{{ substr($tx->transaction_id, 0, 12) }}...</span></td>
                                <td style="padding:14px 20px;">
                                    <span class="tp-pill" style="background:color-mix(in srgb, var(--ink2) 12%, transparent); color:var(--ink); font-size:11.5px; font-weight:600;">
                                        @switch($tx->type)
                                            @case('deposit') 📥 ฝาก @break
                                            @case('withdrawal') 📤 ถอน @break
                                            @case('exchange_buy') 🛒 ซื้อ @break
                                            @case('exchange_sell') 💰 ขาย @break
                                            @default {{ $tx->type }}
                                        @endswitch
                                    </span>
                                </td>
                                <td style="padding:14px 20px;">
                                    <div style="display:flex; align-items:center; gap:8px;">
                                        @if(file_exists($txIconPath))<img src="{{ asset('icons/cryptocurrency/' . strtolower($tx->currency->code) . '.svg') }}" alt="{{ $tx->currency->code }}" style="width:24px; height:24px;">@endif
                                        <div>
                                            <div style="font-weight:700; color:var(--ink); font-size:13.5px;">{{ $tx->currency->code }}</div>
                                            <div style="font-size:11.5px; color:var(--ink2);">{{ $tx->network }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td style="padding:14px 20px; text-align:right;">
                                    <div class="tp-num" style="font-weight:800; font-size:14px; color:{{ $tx->is_incoming ? '#5aa07e' : '#d9534f' }};">{{ $tx->is_incoming ? '+' : '-' }}{{ number_format($tx->amount, 8) }}</div>
                                    @if($tx->amount_thb)<div style="font-size:11.5px; color:var(--ink2);">≈ ฿{{ number_format($tx->amount_thb, 2) }}</div>@endif
                                </td>
                                <td style="padding:14px 20px;">
                                    @if($tx->status === 'completed')
                                        <span class="tp-pill" style="background:color-mix(in srgb, #5aa07e 18%, transparent); color:#5aa07e; font-size:11px; font-weight:700;">✓ สำเร็จ</span>
                                    @elseif($tx->status === 'pending' || $tx->status === 'confirming')
                                        <span class="tp-pill" style="background:color-mix(in srgb, #e0a52e 18%, transparent); color:#e0a52e; font-size:11px; font-weight:700;">⏳ รอดำเนินการ</span>
                                        @if($tx->confirmations > 0)<div style="font-size:11px; color:var(--ink2); margin-top:3px;">{{ $tx->confirmations }}/{{ $tx->confirmations_required }} conf.</div>@endif
                                    @elseif($tx->status === 'failed')
                                        <span class="tp-pill" style="background:color-mix(in srgb, #d9534f 18%, transparent); color:#d9534f; font-size:11px; font-weight:700;">✕ ล้มเหลว</span>
                                    @else
                                        <span class="tp-pill" style="background:color-mix(in srgb, var(--ink2) 14%, transparent); color:var(--ink2); font-size:11px;">{{ $tx->status }}</span>
                                    @endif
                                </td>
                                <td style="padding:14px 20px; color:var(--ink2); font-size:13px;">
                                    <div style="color:var(--ink);">{{ $tx->created_at->format('d/m/Y') }}</div>
                                    <div style="font-size:11.5px;">{{ $tx->created_at->format('H:i') }}</div>
                                </td>
                                <td style="padding:14px 20px; text-align:right;">
                                    @if($tx->tx_hash && $tx->currency->explorer_url)
                                        <a href="{{ $tx->getExplorerUrl() }}" target="_blank" style="color:var(--deep1); font-size:13px; font-weight:700; text-decoration:none;">🔗 Explorer</a>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div style="padding:16px 20px; border-top:1px solid color-mix(in srgb, var(--ink2) 13%, transparent);">{{ $transactions->links() }}</div>
        @else
            <div style="text-align:center; padding:52px 24px;">
                <div style="font-size:56px; margin-bottom:14px;">📝</div>
                <p style="color:var(--ink); font-size:15px; margin:0 0 4px;">ไม่พบธุรกรรม</p>
                <p style="font-size:13px; color:var(--ink2); margin:0;">เริ่มต้นด้วยการฝากหรือแลกเปลี่ยนสกุลเงิน</p>
            </div>
        @endif
    </div>
</div>
@endsection
