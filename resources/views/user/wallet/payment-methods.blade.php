@extends('layouts.user')

@section('title', 'ช่องทางรับเงิน')

@section('content')
<div class="space-y-6 pb-20 lg:pb-6">
    <!-- Header -->
    <div class="bg-gradient-to-r from-orange-600 via-amber-600 to-yellow-600 rounded-2xl shadow-2xl p-8 text-white relative overflow-hidden">
        <div class="absolute top-0 right-0 -mt-4 -mr-4 w-40 h-40 bg-white opacity-10 rounded-full"></div>
        <div class="relative z-10">
            <div class="flex items-center gap-3 mb-4">
                <a href="{{ route('user.wallet.index') }}" class="p-2 bg-white bg-opacity-20 hover:bg-opacity-30 rounded-lg transition">
                    ← กลับ
                </a>
                <h1 class="text-3xl md:text-4xl font-bold">🏦 ช่องทางรับเงิน</h1>
            </div>
            <p class="text-orange-100">จัดการบัญชีสำหรับรับเงินจากการถอน</p>
        </div>
    </div>

    <!-- Add Payment Method Button -->
    <div class="bg-gradient-to-r from-green-500 to-emerald-600 rounded-xl shadow-lg p-4">
        <div class="flex items-center justify-between">
            <div class="text-white">
                <h3 class="font-bold text-lg mb-1">เพิ่มช่องทางรับเงินใหม่</h3>
                <p class="text-sm text-green-100">เพิ่มบัญชีธนาคารหรือช่องทางรับเงินอื่นๆ</p>
            </div>
            <button onclick="showAddForm()"
                    class="px-6 py-3 bg-white text-green-600 font-semibold rounded-lg hover:bg-green-50 transition whitespace-nowrap">
                ➕ เพิ่มช่องทาง
            </button>
        </div>
    </div>

    <!-- Add Payment Method Form -->
    <div id="add-form" class="bg-white rounded-2xl shadow-xl p-6 hidden">
        <h2 class="text-xl font-bold text-gray-900 mb-6">เพิ่มช่องทางรับเงินใหม่</h2>

        <form method="POST" action="{{ route('user.wallet.payment-method.store') }}" class="space-y-6">
            @csrf

            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">ประเภทช่องทางรับเงิน</label>
                <select name="type"
                        id="payment-type"
                        required
                        onchange="togglePaymentFields()"
                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                    <option value="">-- เลือกประเภท --</option>
                    <option value="promptpay">💳 พร้อมเพย์ (PromptPay)</option>
                    <option value="bank_transfer">🏦 โอนผ่านธนาคาร</option>
                    <option value="paypal">💰 PayPal</option>
                </select>
            </div>

            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">ชื่อช่องทาง</label>
                <input type="text"
                       name="name"
                       required
                       class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                       placeholder="เช่น บัญชีหลัก, บัญชีสำรอง">
            </div>

            <!-- PromptPay & Bank Transfer Fields -->
            <div id="promptpay-fields" class="space-y-4 hidden">
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">ชื่อบัญชี</label>
                    <input type="text"
                           name="account_name"
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                           placeholder="ชื่อเจ้าของบัญชี">
                </div>

                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">หมายเลขบัญชี / เบอร์โทรศัพท์</label>
                    <input type="text"
                           name="account_number"
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                           placeholder="หมายเลขบัญชี หรือ เบอร์โทรศัพท์">
                </div>
            </div>

            <!-- Bank Transfer Additional Fields -->
            <div id="bank-fields" class="hidden">
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">ชื่อธนาคาร</label>
                    <select name="bank_name"
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
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

            <!-- PayPal Fields -->
            <div id="paypal-fields" class="hidden">
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">อีเมล PayPal</label>
                    <input type="email"
                           name="paypal_email"
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                           placeholder="your-email@example.com">
                </div>
            </div>

            <div class="flex items-center gap-2">
                <input type="checkbox"
                       name="is_default"
                       value="1"
                       id="is_default"
                       class="w-4 h-4 text-indigo-600 border-gray-300 rounded focus:ring-indigo-500">
                <label for="is_default" class="text-sm font-medium text-gray-700">ตั้งเป็นช่องทางเริ่มต้น</label>
            </div>

            <div class="flex gap-3">
                <button type="button"
                        onclick="hideAddForm()"
                        class="flex-1 px-6 py-3 bg-gray-200 text-gray-700 font-semibold rounded-lg hover:bg-gray-300 transition">
                    ยกเลิก
                </button>
                <button type="submit"
                        class="flex-1 px-6 py-3 bg-gradient-to-r from-indigo-600 to-purple-600 text-white font-semibold rounded-lg hover:opacity-90 transition">
                    เพิ่มช่องทาง
                </button>
            </div>
        </form>
    </div>

    <!-- Payment Methods List -->
    <div class="bg-white rounded-2xl shadow-xl p-6">
        <h2 class="text-xl font-bold text-gray-900 mb-6">ช่องทางรับเงินทั้งหมด</h2>

        @if($paymentMethods->isEmpty())
            <div class="text-center py-12">
                <span class="text-6xl block mb-4">🏦</span>
                <h3 class="text-lg font-bold text-gray-900 mb-2">ยังไม่มีช่องทางรับเงิน</h3>
                <p class="text-gray-600 mb-4">เพิ่มช่องทางรับเงินเพื่อสามารถถอนเงินได้</p>
                <button onclick="showAddForm()"
                        class="inline-block px-6 py-3 bg-gradient-to-r from-indigo-600 to-purple-600 text-white font-semibold rounded-lg hover:opacity-90 transition">
                    ➕ เพิ่มช่องทางรับเงิน
                </button>
            </div>
        @else
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                @foreach($paymentMethods as $method)
                    <div class="border-2 {{ $method->is_default ? 'border-green-500' : 'border-gray-200' }} rounded-xl p-6 relative">
                        @if($method->is_default)
                            <div class="absolute top-3 right-3">
                                <span class="px-3 py-1 bg-green-100 text-green-800 rounded-full text-xs font-semibold">
                                    ⭐ ค่าเริ่มต้น
                                </span>
                            </div>
                        @endif

                        <div class="flex items-start gap-4 mb-4">
                            <div class="text-4xl">
                                @if($method->type === 'promptpay') 💳
                                @elseif($method->type === 'bank_transfer') 🏦
                                @elseif($method->type === 'paypal') 💰
                                @else 💵
                                @endif
                            </div>
                            <div class="flex-1">
                                <h3 class="text-lg font-bold text-gray-900 mb-1">{{ $method->name }}</h3>

                                @if($method->type === 'bank_transfer')
                                    <p class="text-sm text-gray-600">{{ $method->bank_name }}</p>
                                    <p class="text-sm font-medium text-gray-800 mt-2">{{ $method->account_name }}</p>
                                    <p class="text-sm text-gray-600">{{ $method->account_number }}</p>
                                @elseif($method->type === 'promptpay')
                                    <p class="text-sm text-gray-600">พร้อมเพย์</p>
                                    <p class="text-sm font-medium text-gray-800 mt-2">{{ $method->account_name }}</p>
                                    <p class="text-sm text-gray-600">{{ $method->account_number }}</p>
                                @elseif($method->type === 'paypal')
                                    <p class="text-sm text-gray-600">PayPal</p>
                                    <p class="text-sm font-medium text-gray-800 mt-2">{{ $method->paypal_email }}</p>
                                @endif

                                <div class="flex items-center gap-2 mt-3">
                                    <span class="px-2 py-1 rounded text-xs font-semibold {{ $method->status === 'active' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800' }}">
                                        {{ $method->status === 'active' ? '✓ ใช้งาน' : '✗ ปิดใช้งาน' }}
                                    </span>
                                    <span class="text-xs text-gray-500">
                                        เพิ่มเมื่อ: {{ $method->created_at->format('d/m/Y') }}
                                    </span>
                                </div>
                            </div>
                        </div>

                        <div class="flex gap-2 pt-4 border-t">
                            @if(!$method->is_default)
                                <form method="POST" action="{{ route('user.wallet.payment-method.set-default', $method->id) }}" class="flex-1">
                                    @csrf
                                    <button type="submit"
                                            class="w-full px-4 py-2 bg-blue-100 text-blue-700 rounded-lg text-sm font-semibold hover:bg-blue-200 transition">
                                        ตั้งเป็นค่าเริ่มต้น
                                    </button>
                                </form>
                            @endif

                            <form method="POST" action="{{ route('user.wallet.payment-method.delete', $method->id) }}" class="flex-1">
                                @csrf
                                @method('DELETE')
                                <button type="submit"
                                        onclick="return confirm('คุณแน่ใจหรือไม่ที่จะลบช่องทางนี้?')"
                                        class="w-full px-4 py-2 bg-red-100 text-red-700 rounded-lg text-sm font-semibold hover:bg-red-200 transition">
                                    🗑️ ลบ
                                </button>
                            </form>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>

    <!-- Information -->
    <div class="bg-gradient-to-r from-indigo-500 to-purple-600 rounded-2xl shadow-xl p-6 text-white">
        <h3 class="text-xl font-bold mb-4">📋 ข้อมูลสำคัญ</h3>
        <ul class="space-y-2 text-sm">
            <li class="flex items-start gap-2">
                <span>✅</span>
                <span>ช่องทางรับเงินจะถูกใช้สำหรับการถอนเงินเท่านั้น</span>
            </li>
            <li class="flex items-start gap-2">
                <span>✅</span>
                <span>ตรวจสอบข้อมูลบัญชีให้ถูกต้องก่อนบันทึก</span>
            </li>
            <li class="flex items-start gap-2">
                <span>✅</span>
                <span>คุณสามารถมีช่องทางรับเงินได้หลายช่องทาง</span>
            </li>
            <li class="flex items-start gap-2">
                <span>✅</span>
                <span>ช่องทางที่ตั้งเป็นค่าเริ่มต้นจะถูกเลือกอัตโนมัติเมื่อถอนเงิน</span>
            </li>
            <li class="flex items-start gap-2">
                <span>✅</span>
                <span>สามารถแก้ไขหรือลบช่องทางได้ตลอดเวลา</span>
            </li>
        </ul>
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

    // Hide all fields first
    document.getElementById('promptpay-fields').classList.add('hidden');
    document.getElementById('bank-fields').classList.add('hidden');
    document.getElementById('paypal-fields').classList.add('hidden');

    // Remove required from all fields
    document.querySelectorAll('#promptpay-fields input, #bank-fields select, #paypal-fields input').forEach(input => {
        input.removeAttribute('required');
    });

    // Show relevant fields
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
