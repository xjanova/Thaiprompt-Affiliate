<!-- PromptPay Configuration -->
<div class="bg-white rounded-2xl shadow-lg border border-gray-100 overflow-hidden">
    <div class="bg-gradient-to-r from-blue-600 to-cyan-600 px-6 py-4">
        <h3 class="text-lg font-bold text-white flex items-center">
            <i class="fas fa-qrcode mr-2"></i>
            การตั้งค่า PromptPay
        </h3>
    </div>
    <div class="p-6 space-y-4">
        <div class="p-4 bg-blue-50 rounded-xl border border-blue-200 mb-4">
            <p class="text-sm text-blue-800">
                <i class="fas fa-info-circle mr-1"></i>
                <strong>คำแนะนำ:</strong> PromptPay เป็นระบบชำระเงินของธนาคารแห่งประเทศไทย สามารถรับเงินผ่าน QR Code โดยใช้เบอร์โทรศัพท์หรือเลขบัตรประชาชน
            </p>
        </div>

        <div>
            <label class="block text-sm font-semibold text-gray-700 mb-2">
                <i class="fas fa-hashtag text-blue-500 mr-1"></i> PromptPay ID <span class="text-red-500">*</span>
            </label>
            <input type="text" name="credentials[promptpay_id]"
                   value="{{ old('credentials.promptpay_id', $paymentGateway->getCredential('promptpay_id')) }}"
                   class="w-full px-4 py-3 border border-gray-200 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                   placeholder="0812345678 หรือ 1234567890123"
                   required>
            <p class="text-xs text-gray-500 mt-1">เบอร์โทรศัพท์มือถือ (10 หลัก) หรือเลขบัตรประชาชน (13 หลัก)</p>
        </div>

        <div>
            <label class="block text-sm font-semibold text-gray-700 mb-2">
                <i class="fas fa-user text-blue-500 mr-1"></i> ชื่อบัญชี PromptPay <span class="text-red-500">*</span>
            </label>
            <input type="text" name="credentials[promptpay_name]"
                   value="{{ old('credentials.promptpay_name', $paymentGateway->getCredential('promptpay_name')) }}"
                   class="w-full px-4 py-3 border border-gray-200 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                   placeholder="นายสมชาย ใจดี"
                   required>
            <p class="text-xs text-gray-500 mt-1">ชื่อ-นามสกุล เจ้าของบัญชี PromptPay</p>
        </div>

        <div>
            <label class="block text-sm font-semibold text-gray-700 mb-2">
                <i class="fas fa-list text-blue-500 mr-1"></i> ประเภท PromptPay
            </label>
            <select name="credentials[promptpay_type]"
                    class="w-full px-4 py-3 border border-gray-200 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                <option value="phone" {{ old('credentials.promptpay_type', $paymentGateway->getCredential('promptpay_type', 'phone')) === 'phone' ? 'selected' : '' }}>
                    เบอร์โทรศัพท์
                </option>
                <option value="citizen_id" {{ old('credentials.promptpay_type', $paymentGateway->getCredential('promptpay_type', 'phone')) === 'citizen_id' ? 'selected' : '' }}>
                    เลขบัตรประชาชน
                </option>
                <option value="ewallet" {{ old('credentials.promptpay_type', $paymentGateway->getCredential('promptpay_type', 'phone')) === 'ewallet' ? 'selected' : '' }}>
                    E-Wallet Tax ID
                </option>
            </select>
        </div>

        <div class="mt-6 p-4 bg-gradient-to-r from-blue-50 to-cyan-50 rounded-xl border border-blue-200">
            <div class="flex items-start gap-3">
                <i class="fas fa-lightbulb text-blue-500 text-xl mt-0.5"></i>
                <div class="text-sm">
                    <p class="font-semibold text-blue-900 mb-2">วิธีการใช้งาน</p>
                    <ol class="text-blue-700 space-y-1 list-decimal list-inside">
                        <li>กรอก PromptPay ID (เบอร์โทรหรือเลขบัตรประชาชน)</li>
                        <li>ระบุชื่อเจ้าของบัญชี</li>
                        <li>ระบบจะสร้าง QR Code อัตโนมัติเมื่อลูกค้าฝากเงิน</li>
                        <li>ลูกค้าสแกน QR Code และชำระเงินผ่านแอปธนาคาร</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>
</div>
