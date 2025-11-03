<!-- TrueMoney Configuration -->
<div class="bg-white rounded-2xl shadow-lg border border-gray-100 overflow-hidden">
    <div class="bg-gradient-to-r from-orange-600 to-red-600 px-6 py-4">
        <h3 class="text-lg font-bold text-white flex items-center">
            <i class="fas fa-wallet mr-2"></i>
            การตั้งค่า TrueMoney Wallet
        </h3>
    </div>
    <div class="p-6 space-y-4">
        <div class="p-4 bg-orange-50 rounded-xl border border-orange-200 mb-4">
            <p class="text-sm text-orange-800">
                <i class="fas fa-info-circle mr-1"></i>
                <strong>คำแนะนำ:</strong> TrueMoney Wallet เป็นกระเป๋าเงินอิเล็กทรอนิกส์ที่ได้รับความนิยมในไทย
            </p>
        </div>

        <div>
            <label class="block text-sm font-semibold text-gray-700 mb-2">
                <i class="fas fa-id-card text-orange-500 mr-1"></i> App ID <span class="text-red-500">*</span>
            </label>
            <input type="text" name="credentials[app_id]"
                   value="{{ old('credentials.app_id', $paymentGateway->getCredential('app_id')) }}"
                   class="w-full px-4 py-3 border border-gray-200 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-orange-500 font-mono text-sm"
                   placeholder="Your App ID..."
                   required>
            <p class="text-xs text-gray-500 mt-1">
                ติดต่อทีม TrueMoney Business เพื่อรับ App ID
            </p>
        </div>

        <div>
            <label class="block text-sm font-semibold text-gray-700 mb-2">
                <i class="fas fa-lock text-orange-500 mr-1"></i> App Secret <span class="text-red-500">*</span>
            </label>
            <input type="password" name="credentials[app_secret]"
                   value="{{ old('credentials.app_secret', $paymentGateway->getCredential('app_secret')) }}"
                   class="w-full px-4 py-3 border border-gray-200 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-orange-500 font-mono text-sm"
                   placeholder="Your App Secret..."
                   required>
        </div>

        <div>
            <label class="block text-sm font-semibold text-gray-700 mb-2">
                <i class="fas fa-key text-orange-500 mr-1"></i> App Token
            </label>
            <input type="password" name="credentials[app_token]"
                   value="{{ old('credentials.app_token', $paymentGateway->getCredential('app_token')) }}"
                   class="w-full px-4 py-3 border border-gray-200 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-orange-500 font-mono text-sm"
                   placeholder="Optional App Token...">
        </div>

        <div class="mt-6 p-4 bg-gradient-to-r from-orange-50 to-red-50 rounded-xl border border-orange-200">
            <div class="flex items-start gap-3">
                <i class="fas fa-lightbulb text-orange-500 text-xl mt-0.5"></i>
                <div class="text-sm">
                    <p class="font-semibold text-orange-900 mb-2">วิธีการสมัครใช้งาน</p>
                    <ol class="text-orange-700 space-y-1 list-decimal list-inside">
                        <li>ติดต่อทีม TrueMoney Business</li>
                        <li>ส่งเอกสารสำหรับการสมัคร Merchant</li>
                        <li>รอการอนุมัติ (ประมาณ 5-7 วัน)</li>
                        <li>รับ App ID, App Secret, และ App Token</li>
                        <li>ทดสอบในโหมด Sandbox ก่อนใช้งานจริง</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>
</div>
