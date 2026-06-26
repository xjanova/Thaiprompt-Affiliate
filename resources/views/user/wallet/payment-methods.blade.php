@extends('layouts.user-v4')

@section('title', 'ช่องทางรับเงิน')

@section('content')
<div style="display:flex; flex-direction:column; gap:18px;">

    {{-- ── หัวข้อ (Hero) ─────────────────────────────────────── --}}
    <div class="tp-card" style="padding:0; overflow:hidden;">
        <div style="display:flex; flex-wrap:wrap; align-items:center; gap:16px; padding:20px 24px;
                    background:linear-gradient(120deg, color-mix(in srgb, var(--accent1) 16%, transparent), transparent 70%);">
            @if(\Illuminate\Support\Facades\Route::has('user.wallet.index'))
                <a href="{{ route('user.wallet.index') }}" class="tp-icon-btn" title="กลับ"><i class="fas fa-arrow-left"></i></a>
            @endif
            <span class="tp-tile" style="width:52px; height:52px; border-radius:16px; font-size:24px;"><i class="fas fa-credit-card" style="color:#fff;"></i></span>
            <div style="flex:1; min-width:200px;">
                <h1 style="font-size:clamp(20px,4vw,26px); font-weight:800; margin:0;">ช่องทางรับเงิน</h1>
                <div style="font-size:12.5px; color:var(--ink2); margin-top:3px;">จัดการบัญชีสำหรับรับเงินจากการถอน</div>
            </div>
            <button type="button" onclick="showAddForm()" class="tp-btn tp-btn-primary" style="white-space:nowrap;">
                <i class="fas fa-plus"></i> เพิ่มช่องทาง
            </button>
        </div>
    </div>

    {{-- ── ฟอร์มเพิ่มช่องทางรับเงินใหม่ ───────────────────────── --}}
    <div id="add-form" class="tp-card hidden" style="padding:20px;">
        <div class="tp-section-h" style="margin-bottom:18px;">เพิ่มช่องทางรับเงินใหม่</div>

        <form method="POST" action="{{ route('user.wallet.payment-method.store') }}" style="display:flex; flex-direction:column; gap:16px;">
            @csrf

            <div>
                <label style="display:block; font-size:11.5px; font-weight:600; color:var(--ink2); margin-bottom:6px;">ประเภทช่องทางรับเงิน</label>
                <select name="type"
                        id="payment-type"
                        required
                        onchange="togglePaymentFields()"
                        class="tp-input">
                    <option value="">-- เลือกประเภท --</option>
                    <option value="promptpay">💳 พร้อมเพย์ (PromptPay)</option>
                    <option value="bank_transfer">🏦 โอนผ่านธนาคาร</option>
                    <option value="paypal">💰 PayPal</option>
                </select>
            </div>

            <div>
                <label style="display:block; font-size:11.5px; font-weight:600; color:var(--ink2); margin-bottom:6px;">ชื่อช่องทาง</label>
                <input type="text"
                       name="name"
                       required
                       class="tp-input"
                       placeholder="เช่น บัญชีหลัก, บัญชีสำรอง">
            </div>

            {{-- PromptPay & Bank Transfer Fields --}}
            <div id="promptpay-fields" class="hidden" style="display:flex; flex-direction:column; gap:16px;">
                <div>
                    <label style="display:block; font-size:11.5px; font-weight:600; color:var(--ink2); margin-bottom:6px;">ชื่อบัญชี</label>
                    <input type="text"
                           name="account_name"
                           class="tp-input"
                           placeholder="ชื่อเจ้าของบัญชี">
                </div>

                <div>
                    <label style="display:block; font-size:11.5px; font-weight:600; color:var(--ink2); margin-bottom:6px;">หมายเลขบัญชี / เบอร์โทรศัพท์</label>
                    <input type="text"
                           name="account_number"
                           class="tp-input"
                           placeholder="หมายเลขบัญชี หรือ เบอร์โทรศัพท์">
                </div>
            </div>

            {{-- Bank Transfer Additional Fields --}}
            <div id="bank-fields" class="hidden">
                <div>
                    <label style="display:block; font-size:11.5px; font-weight:600; color:var(--ink2); margin-bottom:6px;">ชื่อธนาคาร</label>
                    <select name="bank_name" class="tp-input">
                        <option value="">-- เลือกธนาคาร --</option>
                        <option value="ธนาคารกรุงเทพ">ธนาคารกรุงเทพ</option>
                        <option value="ธนาคารกสิกรไทย">ธนาคารกสิกรไทย</option>
                        <option value="ธนาคารไทยพาณิชย์">ธนาคารไทยพาณิชย์</option>
                        <option value="ธนาคารกรุงไทย">ธนาคารกรุงไทย</option>
                        <option value="ธนาคารกรุงศรีอยุธยา">ธนาคารกรุงศรีอยุธยา</option>
                        <option value="ธนาคารทหารไทยธนชาต">ธนาคารทหารไทยธนชาต</option>
                        <option value="ธนาคารออมสิน">ธนาคารออมสิน</option>
                        <option value="ธนาคารอาคารสงเคราะห์">ธนาคารอาคารสงเคราะห์</option>
                        <option value="ธนาคารเกียรตินาคินภัทร">ธนาคารเกียรตินาคินภัทร</option>
                        <option value="ธนาคารซีไอเอ็มบีไทย">ธนาคารซีไอเอ็มบีไทย</option>
                        <option value="ธนาคารทิสโก้">ธนาคารทิสโก้</option>
                        <option value="ธนาคารธนชาต">ธนาคารธนชาต</option>
                        <option value="ธนาคารยูโอบี">ธนาคารยูโอบี</option>
                        <option value="ธนาคารแลนด์ แอนด์ เฮ้าส์">ธนาคารแลนด์ แอนด์ เฮ้าส์</option>
                        <option value="ธนาคารไอซีบีซี">ธนาคารไอซีบีซี (ไทย)</option>
                    </select>
                </div>
            </div>

            {{-- PayPal Fields --}}
            <div id="paypal-fields" class="hidden">
                <div>
                    <label style="display:block; font-size:11.5px; font-weight:600; color:var(--ink2); margin-bottom:6px;">อีเมล PayPal</label>
                    <input type="email"
                           name="paypal_email"
                           class="tp-input"
                           placeholder="your-email@example.com">
                </div>
            </div>

            <div style="display:flex; align-items:center; gap:8px;">
                <input type="checkbox"
                       name="is_default"
                       value="1"
                       id="is_default"
                       style="width:16px; height:16px; accent-color:var(--deep1); cursor:pointer;">
                <label for="is_default" style="font-size:13px; font-weight:600; color:var(--ink); cursor:pointer;">ตั้งเป็นช่องทางเริ่มต้น</label>
            </div>

            <div style="display:flex; gap:12px; flex-wrap:wrap;">
                <button type="button"
                        onclick="hideAddForm()"
                        class="tp-btn"
                        style="flex:1; min-width:120px; justify-content:center;">
                    ยกเลิก
                </button>
                <button type="submit"
                        class="tp-btn tp-btn-primary"
                        style="flex:1; min-width:120px; justify-content:center;">
                    เพิ่มช่องทาง
                </button>
            </div>
        </form>
    </div>

    {{-- ── รายการช่องทางรับเงินทั้งหมด ────────────────────────── --}}
    <div class="tp-card" style="padding:18px;">
        <div class="tp-section-h" style="margin-bottom:16px;">ช่องทางรับเงินทั้งหมด</div>

        @if($paymentMethods->isEmpty())
            <div style="text-align:center; padding:48px 20px;">
                <div style="font-size:52px; opacity:.5;">🏦</div>
                <div style="font-weight:700; font-size:17px; margin-top:10px;">ยังไม่มีช่องทางรับเงิน</div>
                <div style="font-size:13px; color:var(--ink2); margin-top:4px;">เพิ่มช่องทางรับเงินเพื่อสามารถถอนเงินได้</div>
                <button type="button" onclick="showAddForm()" class="tp-btn tp-btn-primary" style="margin-top:16px;">
                    <i class="fas fa-plus"></i> เพิ่มช่องทางรับเงิน
                </button>
            </div>
        @else
            <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(280px,1fr)); gap:16px;">
                @foreach($paymentMethods as $method)
                    <div style="position:relative; padding:18px; border-radius:16px; box-shadow:var(--inset-sm); {{ $method->is_default ? 'border:1px solid #5aa07e;' : '' }}">
                        @if($method->is_default)
                            <div style="position:absolute; top:12px; right:12px;">
                                <span class="tp-pill" style="color:#fff; background:#5aa07e;">⭐ ค่าเริ่มต้น</span>
                            </div>
                        @endif

                        <div style="display:flex; align-items:flex-start; gap:14px; margin-bottom:14px;">
                            <div style="font-size:32px; line-height:1;">
                                @if($method->type === 'promptpay') 💳
                                @elseif($method->type === 'bank_transfer') 🏦
                                @elseif($method->type === 'paypal') 💰
                                @else 💵
                                @endif
                            </div>
                            <div style="flex:1; min-width:0;">
                                <div style="font-size:15px; font-weight:700; margin-bottom:4px;">{{ $method->name }}</div>

                                @if($method->type === 'bank_transfer')
                                    <div style="font-size:12.5px; color:var(--ink2);">{{ $method->bank_name }}</div>
                                    <div style="font-size:13px; font-weight:600; margin-top:6px;">{{ $method->account_name }}</div>
                                    <div class="tp-num" style="font-size:12.5px; color:var(--ink2);">{{ $method->account_number }}</div>
                                @elseif($method->type === 'promptpay')
                                    <div style="font-size:12.5px; color:var(--ink2);">พร้อมเพย์</div>
                                    <div style="font-size:13px; font-weight:600; margin-top:6px;">{{ $method->account_name }}</div>
                                    <div class="tp-num" style="font-size:12.5px; color:var(--ink2);">{{ $method->account_number }}</div>
                                @elseif($method->type === 'paypal')
                                    <div style="font-size:12.5px; color:var(--ink2);">PayPal</div>
                                    <div style="font-size:13px; font-weight:600; margin-top:6px;">{{ $method->paypal_email }}</div>
                                @endif

                                <div style="display:flex; align-items:center; flex-wrap:wrap; gap:8px; margin-top:12px;">
                                    @if($method->status === 'active')
                                        <span class="tp-pill" style="color:#5aa07e; background:rgba(90,160,126,.16);">✓ ใช้งาน</span>
                                    @else
                                        <span class="tp-pill" style="color:var(--ink2); background:color-mix(in srgb, var(--ink2) 16%, transparent);">✗ ปิดใช้งาน</span>
                                    @endif
                                    <span class="tp-num" style="font-size:11px; color:var(--ink2);">
                                        เพิ่มเมื่อ: {{ $method->created_at->format('d/m/Y') }}
                                    </span>
                                </div>
                            </div>
                        </div>

                        <div style="display:flex; gap:10px; padding-top:14px; border-top:1px solid color-mix(in srgb, var(--ink2) 13%, transparent);">
                            @if(!$method->is_default)
                                <form method="POST" action="{{ route('user.wallet.payment-method.set-default', $method->id) }}" style="flex:1;">
                                    @csrf
                                    <button type="submit"
                                            class="tp-btn tp-btn-sm"
                                            style="width:100%; justify-content:center; color:#5689b8; background:rgba(86,137,184,.14);">
                                        ตั้งเป็นค่าเริ่มต้น
                                    </button>
                                </form>
                            @endif

                            <form method="POST" action="{{ route('user.wallet.payment-method.delete', $method->id) }}" style="flex:1;">
                                @csrf
                                @method('DELETE')
                                <button type="submit"
                                        onclick="return confirm('คุณแน่ใจหรือไม่ที่จะลบช่องทางนี้?')"
                                        class="tp-btn tp-btn-sm"
                                        style="width:100%; justify-content:center; color:#d9534f; background:rgba(217,83,79,.14);">
                                    🗑️ ลบ
                                </button>
                            </form>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>

    {{-- ── ข้อมูลสำคัญ ───────────────────────────────────────── --}}
    <div class="tp-card" style="padding:0; overflow:hidden;">
        <div style="padding:20px 22px; background:linear-gradient(120deg, color-mix(in srgb, var(--accent1) 14%, transparent), transparent 70%);">
            <div class="tp-section-h" style="margin-bottom:14px;">📋 ข้อมูลสำคัญ</div>
            <ul style="list-style:none; padding:0; margin:0; display:flex; flex-direction:column; gap:10px;">
                @foreach([
                    'ช่องทางรับเงินจะถูกใช้สำหรับการถอนเงินเท่านั้น',
                    'ตรวจสอบข้อมูลบัญชีให้ถูกต้องก่อนบันทึก',
                    'คุณสามารถมีช่องทางรับเงินได้หลายช่องทาง',
                    'ช่องทางที่ตั้งเป็นค่าเริ่มต้นจะถูกเลือกอัตโนมัติเมื่อถอนเงิน',
                    'สามารถแก้ไขหรือลบช่องทางได้ตลอดเวลา',
                ] as $info)
                    <li style="display:flex; align-items:flex-start; gap:10px; font-size:13px; color:var(--ink);">
                        <span style="color:#5aa07e; flex-shrink:0;">✅</span>
                        <span>{{ $info }}</span>
                    </li>
                @endforeach
            </ul>
        </div>
    </div>
</div>

@push('scripts')
<script>
function showAddForm() {
    document.getElementById('add-form').classList.remove('hidden');
    document.getElementById('add-form').scrollIntoView({ behavior: 'smooth' });
}

function hideAddForm() {
    document.getElementById('add-form').classList.add('hidden');
    document.getElementById('payment-type').value = '';
    togglePaymentFields();
}

function togglePaymentFields() {
    const type = document.getElementById('payment-type').value;

    // ซ่อนทุก field ก่อน
    document.getElementById('promptpay-fields').classList.add('hidden');
    document.getElementById('bank-fields').classList.add('hidden');
    document.getElementById('paypal-fields').classList.add('hidden');

    // เอา required ออกจากทุก field
    document.querySelectorAll('#promptpay-fields input, #bank-fields select, #paypal-fields input').forEach(input => {
        input.removeAttribute('required');
    });

    // แสดง field ที่เกี่ยวข้อง
    if (type === 'promptpay') {
        document.getElementById('promptpay-fields').classList.remove('hidden');
        document.querySelectorAll('#promptpay-fields input').forEach(input => {
            input.setAttribute('required', 'required');
        });
    } else if (type === 'bank_transfer') {
        document.getElementById('promptpay-fields').classList.remove('hidden');
        document.getElementById('bank-fields').classList.remove('hidden');
        document.querySelectorAll('#promptpay-fields input').forEach(input => {
            input.setAttribute('required', 'required');
        });
        document.querySelector('#bank-fields select').setAttribute('required', 'required');
    } else if (type === 'paypal') {
        document.getElementById('paypal-fields').classList.remove('hidden');
        document.querySelectorAll('#paypal-fields input').forEach(input => {
            input.setAttribute('required', 'required');
        });
    }
}
</script>
@endpush
@endsection
