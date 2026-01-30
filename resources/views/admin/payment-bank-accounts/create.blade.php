@extends('layouts.admin-v3')

@section('title', isset($account) ? 'แก้ไขบัญชีธนาคาร' : 'เพิ่มบัญชีธนาคาร')

@section('content')
<div class="space-y-6 max-w-3xl mx-auto">
    {{-- Header --}}
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">
                {{ isset($account) ? '✏️ แก้ไขบัญชีธนาคาร' : '➕ เพิ่มบัญชีธนาคาร' }}
            </h1>
            <p class="text-gray-500 dark:text-gray-400 mt-1">
                กรอกข้อมูลบัญชีธนาคารสำหรับรับชำระเงิน
            </p>
        </div>
        <a href="{{ route('admin.payment-bank-accounts.index') }}"
           class="px-4 py-2 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-300 dark:hover:bg-gray-600 transition">
            ← กลับ
        </a>
    </div>

    {{-- Validation Errors --}}
    @if($errors->any())
    <div class="bg-red-100 dark:bg-red-900 border border-red-400 dark:border-red-700 text-red-700 dark:text-red-300 px-4 py-3 rounded-lg">
        <ul class="list-disc pl-5">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
    @endif

    <form method="POST"
          action="{{ isset($account) ? route('admin.payment-bank-accounts.update', $account) : route('admin.payment-bank-accounts.store') }}"
          enctype="multipart/form-data"
          class="space-y-6">
        @csrf
        @if(isset($account))
            @method('PUT')
        @endif

        {{-- ข้อมูลธนาคาร --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6 space-y-4">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white">🏦 ข้อมูลธนาคาร</h2>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                {{-- ธนาคาร --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">ธนาคาร *</label>
                    <select name="bank_code" id="bank_code"
                            class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500"
                            onchange="updateBankName(this)" required>
                        <option value="">-- เลือกธนาคาร --</option>
                        @foreach($banks as $code => $name)
                            <option value="{{ $code }}"
                                    data-name="{{ $name }}"
                                    {{ old('bank_code', $account->bank_code ?? '') === $code ? 'selected' : '' }}>
                                {{ $name }} ({{ $code }})
                            </option>
                        @endforeach
                    </select>
                    <input type="hidden" name="bank_name" id="bank_name"
                           value="{{ old('bank_name', $account->bank_name ?? '') }}">
                </div>

                {{-- สาขา --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">สาขา</label>
                    <input type="text" name="branch"
                           value="{{ old('branch', $account->branch ?? '') }}"
                           class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500"
                           placeholder="เช่น สาขาเซ็นทรัลเวิลด์">
                </div>

                {{-- เลขที่บัญชี --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">เลขที่บัญชี *</label>
                    <input type="text" name="account_number"
                           value="{{ old('account_number', $account->account_number ?? '') }}"
                           class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500"
                           placeholder="เช่น 123-4-56789-0" required>
                </div>

                {{-- ชื่อบัญชี --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">ชื่อบัญชี *</label>
                    <input type="text" name="account_name"
                           value="{{ old('account_name', $account->account_name ?? '') }}"
                           class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500"
                           placeholder="เช่น นายสมชาย ใจดี" required>
                </div>
            </div>
        </div>

        {{-- PromptPay --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6 space-y-4">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white">💜 PromptPay (ไม่บังคับ)</h2>
            <p class="text-sm text-gray-500 dark:text-gray-400">
                เชื่อม PromptPay กับบัญชีนี้ เพื่อให้ลูกค้าโอนผ่าน QR Code PromptPay ได้
            </p>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                {{-- PromptPay ID --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">หมายเลข PromptPay</label>
                    <input type="text" name="promptpay_id"
                           value="{{ old('promptpay_id', $account->promptpay_id ?? '') }}"
                           class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500"
                           placeholder="เบอร์โทร หรือ เลขบัตร ปชช.">
                </div>

                {{-- PromptPay Type --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">ประเภท PromptPay</label>
                    <select name="promptpay_type"
                            class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500">
                        <option value="">-- ไม่ใช้ PromptPay --</option>
                        <option value="phone" {{ old('promptpay_type', $account->promptpay_type ?? '') === 'phone' ? 'selected' : '' }}>
                            📱 เบอร์โทรศัพท์
                        </option>
                        <option value="citizen_id" {{ old('promptpay_type', $account->promptpay_type ?? '') === 'citizen_id' ? 'selected' : '' }}>
                            🪪 เลขบัตรประจำตัวประชาชน
                        </option>
                        <option value="ewallet" {{ old('promptpay_type', $account->promptpay_type ?? '') === 'ewallet' ? 'selected' : '' }}>
                            💳 e-Wallet ID
                        </option>
                    </select>
                </div>
            </div>
        </div>

        {{-- QR Code & SMS Checker --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6 space-y-4">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white">📷 QR Code & 📱 SMS Checker</h2>

            {{-- QR Code --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">QR Code สำหรับชำระเงิน</label>
                @if(isset($account) && $account->qr_image)
                    <div class="mb-2">
                        <img src="{{ $account->qr_image_url }}" alt="QR Code" class="w-32 h-32 rounded border">
                    </div>
                @endif
                <input type="file" name="qr_image" accept="image/*"
                       class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                    อัพโหลดรูป QR Code ของบัญชีนี้ (PromptPay QR หรือ QR อื่น) - ไม่บังคับ
                </p>
            </div>

            {{-- SMS Checker --}}
            <div class="flex items-center space-x-3 bg-blue-50 dark:bg-blue-900/30 rounded-lg p-4">
                <input type="hidden" name="sms_checker_enabled" value="0">
                <input type="checkbox" name="sms_checker_enabled" value="1" id="sms_checker_enabled"
                       {{ old('sms_checker_enabled', $account->sms_checker_enabled ?? false) ? 'checked' : '' }}
                       class="w-5 h-5 text-blue-600 rounded border-gray-300 dark:border-gray-600 focus:ring-blue-500">
                <label for="sms_checker_enabled" class="cursor-pointer">
                    <p class="text-sm font-medium text-blue-900 dark:text-blue-200">
                        📱 เปิดใช้ SMS Checker ยืนยันอัตโนมัติ
                    </p>
                    <p class="text-xs text-blue-700 dark:text-blue-400">
                        เมื่อมีเงินโอนเข้าบัญชีนี้ ระบบจะตรวจจับ SMS จากธนาคารและยืนยันอัตโนมัติ
                    </p>
                </label>
            </div>
        </div>

        {{-- ตั้งค่า --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6 space-y-4">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white">⚙️ ตั้งค่า</h2>

            <div class="space-y-3">
                <div class="flex items-center space-x-3">
                    <input type="hidden" name="is_active" value="0">
                    <input type="checkbox" name="is_active" value="1" id="is_active"
                           {{ old('is_active', $account->is_active ?? true) ? 'checked' : '' }}
                           class="w-5 h-5 text-green-600 rounded border-gray-300 dark:border-gray-600 focus:ring-green-500">
                    <label for="is_active" class="text-sm text-gray-700 dark:text-gray-300 cursor-pointer">
                        เปิดใช้งานบัญชีนี้
                    </label>
                </div>

                <div class="flex items-center space-x-3">
                    <input type="hidden" name="is_default" value="0">
                    <input type="checkbox" name="is_default" value="1" id="is_default"
                           {{ old('is_default', $account->is_default ?? false) ? 'checked' : '' }}
                           class="w-5 h-5 text-yellow-600 rounded border-gray-300 dark:border-gray-600 focus:ring-yellow-500">
                    <label for="is_default" class="text-sm text-gray-700 dark:text-gray-300 cursor-pointer">
                        ⭐ ตั้งเป็นบัญชีหลัก (default)
                    </label>
                </div>
            </div>

            {{-- หมายเหตุ --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">หมายเหตุ (admin only)</label>
                <textarea name="notes" rows="2"
                          class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500"
                          placeholder="หมายเหตุสำหรับ admin">{{ old('notes', $account->notes ?? '') }}</textarea>
            </div>
        </div>

        {{-- Buttons --}}
        <div class="flex justify-end space-x-3">
            <a href="{{ route('admin.payment-bank-accounts.index') }}"
               class="px-6 py-2 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-300 dark:hover:bg-gray-600 transition">
                ยกเลิก
            </a>
            <button type="submit"
                    class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition">
                {{ isset($account) ? '💾 บันทึก' : '➕ เพิ่มบัญชี' }}
            </button>
        </div>
    </form>
</div>

<script>
    // อัพเดทชื่อธนาคารเมื่อเลือก dropdown
    function updateBankName(select) {
        const selected = select.options[select.selectedIndex];
        document.getElementById('bank_name').value = selected.dataset.name || '';
    }
</script>
@endsection
