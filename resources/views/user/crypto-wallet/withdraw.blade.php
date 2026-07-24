@extends('layouts.user-v4')

@section('title', 'ถอนเหรียญคริปโต')

@section('content')
<div style="display:flex; flex-direction:column; gap:18px; max-width:720px; margin-inline:auto; width:100%;">

    {{-- ── Hero ─────────────────────────────────────────────── --}}
    <div class="tp-card" style="padding:22px 24px; background:linear-gradient(120deg, color-mix(in srgb, var(--accent1) 16%, transparent), transparent 70%);">
        <div style="display:flex; flex-wrap:wrap; align-items:center; justify-content:space-between; gap:14px;">
            <div style="display:flex; align-items:center; gap:14px;">
                <span class="tp-tile" style="width:56px; height:56px; border-radius:18px; display:flex; align-items:center; justify-content:center; font-size:24px; color:var(--deep1);"><i class="fas fa-arrow-up"></i></span>
                <div>
                    <h1 style="font-size:clamp(19px,3.5vw,26px); font-weight:800; margin:0; color:var(--ink);">📤 ถอนเหรียญคริปโต</h1>
                    <div style="font-size:13px; color:var(--ink2); margin-top:2px;">โอนเหรียญไปยังกระเป๋าภายนอก</div>
                </div>
            </div>
            <a href="{{ route('user.crypto-wallet.index') }}" class="tp-btn" style="display:inline-flex; align-items:center; gap:8px; padding:10px 16px; border-radius:13px; background:var(--surf); box-shadow:var(--inset-sm); color:var(--ink); font-weight:600; font-size:13.5px; text-decoration:none;">
                <i class="fas fa-arrow-left"></i> <span>กลับหน้าหลัก</span>
            </a>
        </div>
    </div>

    @if(session('success'))
        <div class="tp-card" style="padding:14px 18px; background:color-mix(in srgb, #5aa07e 12%, transparent); border:1px solid color-mix(in srgb, #5aa07e 30%, transparent); color:var(--ink); font-size:14px;">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="tp-card" style="padding:14px 18px; background:color-mix(in srgb, #d9534f 10%, transparent); border:1px solid color-mix(in srgb, #d9534f 30%, transparent); color:#d9534f; font-size:14px;">{{ session('error') }}</div>
    @endif

    {{-- ── ยอดคงเหลือ ─────────────────────────────────────────── --}}
    <div class="tp-card" style="padding:22px 24px;">
        <h3 style="font-weight:800; color:var(--ink); margin:0 0 14px; font-size:15px;">ยอดคงเหลือของคุณ</h3>
        @if(count($balances) > 0)
            <div style="display:flex; flex-direction:column; gap:10px;">
                @foreach($balances as $code => $balance)
                    @php $wIconPath = public_path('icons/cryptocurrency/' . strtolower($code) . '.svg'); @endphp
                    <div class="tp-card" style="padding:13px 15px; box-shadow:var(--inset-sm); display:flex; align-items:center; justify-content:space-between; gap:12px;">
                        <div style="display:flex; align-items:center; gap:12px; min-width:0;">
                            @if(file_exists($wIconPath))
                                <img src="{{ asset('icons/cryptocurrency/' . strtolower($code) . '.svg') }}" alt="{{ $code }}" style="width:40px; height:40px; flex-shrink:0;">
                            @else
                                <span class="tp-tile" style="width:40px; height:40px; border-radius:50%; display:flex; align-items:center; justify-content:center; font-weight:800; color:var(--deep1); flex-shrink:0;">{{ substr($code, 0, 1) }}</span>
                            @endif
                            <div>
                                <div style="font-weight:700; color:var(--ink); font-size:14px;">{{ $balance['currency']->name }}</div>
                                <div style="font-size:12.5px; color:var(--ink2);">{{ $code }}</div>
                            </div>
                        </div>
                        <div style="text-align:right;">
                            <div class="tp-num" style="font-weight:800; color:var(--ink); font-size:14px;">{{ number_format($balance['balance'], 8) }}</div>
                            <div style="font-size:12px; color:var(--ink2);">≈ ฿{{ number_format($balance['balance_thb'], 2) }}</div>
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <div style="text-align:center; padding:24px 0;">
                <div style="font-size:44px; margin-bottom:10px;">💰</div>
                <p style="color:var(--ink2); font-size:14px; margin:0;">ไม่มียอดคงเหลือ</p>
            </div>
        @endif
    </div>

    {{-- ── ฟอร์มถอน (Alpine — preserve logic) ─────────────────── --}}
    <div class="tp-card" style="padding:26px 24px;">
        <h3 style="font-size:16px; font-weight:800; color:var(--ink); margin:0 0 20px;">ฟอร์มถอนเหรียญ</h3>

        <form action="{{ route('user.crypto-wallet.withdraw.submit') }}" method="POST" x-data="{ selectedCurrency: null, amount: '', fee: 0, netAmount: 0 }">
            @csrf

            {{-- เลือกสกุลเงิน --}}
            <div style="margin-bottom:18px;">
                <label style="display:block; font-size:13px; font-weight:700; color:var(--ink); margin-bottom:7px;">เลือกสกุลเงิน</label>
                <select name="currency_id" required
                        @change="selectedCurrency = $event.target.options[$event.target.selectedIndex].dataset.currency ? JSON.parse($event.target.options[$event.target.selectedIndex].dataset.currency) : null; fee = selectedCurrency ? parseFloat(selectedCurrency.withdrawal_fee) : 0; calculateNet()"
                        style="width:100%; padding:12px 14px; border-radius:12px; background:var(--surf); box-shadow:var(--inset-sm); border:1px solid transparent; color:var(--ink); font-size:14px;">
                    <option value="">-- เลือกสกุลเงิน --</option>
                    @foreach($currencies as $curr)
                        @php
                            // สร้าง JSON สำหรับ data attribute (แทน @json เพื่อเลี่ยง nested-directive edge case)
                            $currData = json_encode([
                                'code' => $curr->code,
                                'min_withdrawal' => $curr->min_withdrawal,
                                'max_withdrawal' => $curr->max_withdrawal,
                                'withdrawal_fee' => $curr->withdrawal_fee,
                                'network' => $curr->network,
                            ], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT);
                        @endphp
                        <option value="{{ $curr->id }}" data-currency='{!! $currData !!}'>
                            {{ $curr->code }} ({{ strtoupper($curr->network) }})
                            @if(isset($balances[$curr->code]))
                                - มี {{ number_format($balances[$curr->code]['balance'], 8) }}
                            @endif
                        </option>
                    @endforeach
                </select>
            </div>

            {{-- ที่อยู่ปลายทาง --}}
            <div style="margin-bottom:18px;">
                <label style="display:block; font-size:13px; font-weight:700; color:var(--ink); margin-bottom:7px;">ที่อยู่ปลายทาง (Destination Address)</label>
                <input type="text" name="to_address" required
                       style="width:100%; padding:12px 14px; border-radius:12px; background:var(--surf); box-shadow:var(--inset-sm); border:1px solid transparent; color:var(--ink); font-family:monospace; font-size:13px;"
                       placeholder="0x...">
                <p style="font-size:11.5px; color:var(--ink2); margin:7px 0 0;">กรุณาตรวจสอบที่อยู่ให้ถูกต้อง การถอนไปที่อยู่ผิดจะไม่สามารถกู้คืนได้</p>
            </div>

            {{-- จำนวน --}}
            <div style="margin-bottom:18px;">
                <label style="display:block; font-size:13px; font-weight:700; color:var(--ink); margin-bottom:7px;">จำนวนที่ต้องการถอน</label>
                <input type="number" name="amount" step="0.00000001" required x-model="amount" @input="calculateNet()"
                       style="width:100%; padding:12px 14px; border-radius:12px; background:var(--surf); box-shadow:var(--inset-sm); border:1px solid transparent; color:var(--ink); font-size:14px;"
                       placeholder="0.00000000">
                <template x-if="selectedCurrency">
                    <div style="margin-top:8px; font-size:12.5px; color:var(--ink2);">
                        <div>ขั้นต่ำ: <span x-text="selectedCurrency.min_withdrawal"></span> <span x-text="selectedCurrency.code"></span></div>
                        <div x-show="selectedCurrency.max_withdrawal">สูงสุด: <span x-text="selectedCurrency.max_withdrawal"></span> <span x-text="selectedCurrency.code"></span></div>
                    </div>
                </template>
            </div>

            {{-- แสดงค่าธรรมเนียม --}}
            <div class="tp-card" style="padding:16px 18px; box-shadow:var(--inset-sm); margin-bottom:18px;" x-show="selectedCurrency && amount > 0">
                <div style="display:flex; flex-direction:column; gap:8px; font-size:13.5px;">
                    <div style="display:flex; justify-content:space-between;">
                        <span style="color:var(--ink2);">จำนวนถอน:</span>
                        <span style="font-weight:700; color:var(--ink);"><span x-text="parseFloat(amount).toFixed(8)"></span> <span x-text="selectedCurrency ? selectedCurrency.code : ''"></span></span>
                    </div>
                    <div style="display:flex; justify-content:space-between;">
                        <span style="color:var(--ink2);">ค่าธรรมเนียมเครือข่าย:</span>
                        <span style="font-weight:700; color:#d9534f;">- <span x-text="fee.toFixed(8)"></span> <span x-text="selectedCurrency ? selectedCurrency.code : ''"></span></span>
                    </div>
                    <div style="border-top:1px solid color-mix(in srgb, var(--ink2) 15%, transparent); padding-top:8px; display:flex; justify-content:space-between;">
                        <span style="font-weight:800; color:var(--ink);">จำนวนที่จะได้รับ:</span>
                        <span style="font-weight:800; color:#5aa07e;"><span x-text="netAmount.toFixed(8)"></span> <span x-text="selectedCurrency ? selectedCurrency.code : ''"></span></span>
                    </div>
                </div>
            </div>

            {{-- PIN --}}
            <div style="margin-bottom:18px;">
                <label style="display:block; font-size:13px; font-weight:700; color:var(--ink); margin-bottom:7px;">PIN กระเป๋า</label>
                <input type="password" name="pin" required minlength="4" maxlength="6"
                       style="width:100%; padding:12px 14px; border-radius:12px; background:var(--surf); box-shadow:var(--inset-sm); border:1px solid transparent; color:var(--ink); font-size:14px;"
                       placeholder="กรอก PIN ของคุณ">
            </div>

            {{-- หมายเหตุ --}}
            <div style="margin-bottom:18px;">
                <label style="display:block; font-size:13px; font-weight:700; color:var(--ink); margin-bottom:7px;">หมายเหตุ (ถ้ามี)</label>
                <textarea name="note" rows="2"
                          style="width:100%; padding:12px 14px; border-radius:12px; background:var(--surf); box-shadow:var(--inset-sm); border:1px solid transparent; color:var(--ink); font-size:14px; resize:vertical;"
                          placeholder="หมายเหตุส่วนตัว..."></textarea>
            </div>

            {{-- ยืนยันเงื่อนไข --}}
            <label style="display:flex; align-items:flex-start; gap:10px; margin-bottom:20px; cursor:pointer;">
                <input type="checkbox" required style="margin-top:3px; width:18px; height:18px; accent-color:var(--accent1); flex-shrink:0;">
                <span style="font-size:13px; color:var(--ink2);">ฉันยืนยันว่าได้ตรวจสอบที่อยู่ปลายทางและเครือข่ายแล้ว และเข้าใจว่าการส่งไปที่อยู่ผิดจะไม่สามารถกู้คืนได้</span>
            </label>

            <button type="submit" class="tp-btn" style="width:100%; padding:15px; border-radius:14px; background:linear-gradient(135deg, var(--accent1), var(--accent2)); color:#fff; font-weight:800; font-size:16px; box-shadow:var(--raise); border:none; cursor:pointer;">ส่งคำขอถอนเงิน</button>
        </form>
    </div>

    {{-- ── คำเตือน ───────────────────────────────────────────── --}}
    <div class="tp-card" style="padding:20px 22px; box-shadow:var(--inset-sm); background:color-mix(in srgb, #d9534f 8%, transparent);">
        <h4 style="font-weight:800; color:#d9534f; margin:0 0 10px; font-size:14.5px; display:flex; align-items:center; gap:8px;"><span>⚠️</span> คำเตือนสำคัญ!</h4>
        <ul style="margin:0; padding-left:18px; color:#d9534f; font-size:13px; display:flex; flex-direction:column; gap:5px;">
            <li>ตรวจสอบที่อยู่ปลายทางและเครือข่ายให้ถูกต้องก่อนยืนยัน</li>
            <li>การถอนไปที่อยู่ผิดหรือเครือข่ายผิดจะทำให้เหรียญสูญหายถาวร</li>
            <li>คำขอถอนจำนวนมากอาจต้องใช้เวลาตรวจสอบเพิ่มเติม</li>
            <li>ค่าธรรมเนียมเครือข่ายขึ้นอยู่กับความหนาแน่นของ Blockchain</li>
        </ul>
    </div>
</div>

<script>
function calculateNet() {
    const amount = parseFloat(this.amount) || 0;
    const fee = parseFloat(this.fee) || 0;
    this.netAmount = Math.max(0, amount - fee);
}
</script>
@endsection
