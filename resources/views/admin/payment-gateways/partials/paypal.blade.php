<!-- PayPal Configuration -->
<div class="bg-white rounded-2xl shadow-lg border border-gray-100 overflow-hidden">
    <div class="bg-gradient-to-r from-blue-600 to-indigo-600 px-6 py-4">
        <h3 class="text-lg font-bold text-white flex items-center">
            <i class="fab fa-paypal mr-2"></i>
            การตั้งค่า PayPal
        </h3>
    </div>
    <div class="p-6 space-y-4">
        <div class="p-4 bg-blue-50 rounded-xl border border-blue-200 mb-4">
            <p class="text-sm text-blue-800">
                <i class="fas fa-info-circle mr-1"></i>
                <strong>คำแนะนำ:</strong> PayPal เป็น Payment Gateway ที่ได้รับความนิยมทั่วโลก
                <a href="https://developer.paypal.com/dashboard/applications" target="_blank" class="text-blue-600 hover:underline font-semibold">
                    ดูแอพของคุณที่นี่ <i class="fas fa-external-link-alt text-xs"></i>
                </a>
            </p>
        </div>

        <div>
            <label class="block text-sm font-semibold text-gray-700 mb-2">
                <i class="fas fa-id-card text-blue-500 mr-1"></i> Client ID <span class="text-red-500">*</span>
            </label>
            <input type="text" name="credentials[client_id]"
                   value="{{ old('credentials.client_id', $paymentGateway->getCredential('client_id')) }}"
                   class="w-full px-4 py-3 border border-gray-200 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 font-mono text-sm"
                   placeholder="AYourClientID..."
                   required>
            <p class="text-xs text-gray-500 mt-1">
                <i class="fas fa-arrow-right mr-1"></i>
                PayPal Developer → My Apps & Credentials → Client ID
            </p>
        </div>

        <div>
            <label class="block text-sm font-semibold text-gray-700 mb-2">
                <i class="fas fa-lock text-blue-500 mr-1"></i> Client Secret <span class="text-red-500">*</span>
            </label>
            <input type="password" name="credentials[client_secret]"
                   value="{{ old('credentials.client_secret', $paymentGateway->getCredential('client_secret')) }}"
                   class="w-full px-4 py-3 border border-gray-200 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 font-mono text-sm"
                   placeholder="EYourClientSecret..."
                   required>
            <p class="text-xs text-gray-500 mt-1">
                <i class="fas fa-arrow-right mr-1"></i>
                PayPal Developer → My Apps & Credentials → Secret
            </p>
        </div>

        <div>
            <label class="block text-sm font-semibold text-gray-700 mb-2">
                <i class="fas fa-cog text-blue-500 mr-1"></i> โหมด
            </label>
            <select name="credentials[mode]"
                    class="w-full px-4 py-3 border border-gray-200 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                <option value="sandbox" {{ old('credentials.mode', $paymentGateway->getCredential('mode', 'sandbox')) === 'sandbox' ? 'selected' : '' }}>
                    Sandbox (ทดสอบ)
                </option>
                <option value="live" {{ old('credentials.mode', $paymentGateway->getCredential('mode', 'sandbox')) === 'live' ? 'selected' : '' }}>
                    Live (จริง)
                </option>
            </select>
            <p class="text-xs text-gray-500 mt-1">ใช้ Sandbox สำหรับการทดสอบ และ Live สำหรับการใช้งานจริง</p>
        </div>

        <div class="mt-6 p-4 bg-gradient-to-r from-blue-50 to-indigo-50 rounded-xl border border-blue-200">
            <div class="flex items-start gap-3">
                <i class="fas fa-lightbulb text-blue-500 text-xl mt-0.5"></i>
                <div class="text-sm">
                    <p class="font-semibold text-blue-900 mb-2">วิธีการรับ API Credentials</p>
                    <ol class="text-blue-700 space-y-1 list-decimal list-inside">
                        <li>ไปที่ <a href="https://developer.paypal.com" target="_blank" class="underline">PayPal Developer</a></li>
                        <li>Log in to Dashboard</li>
                        <li>คลิก My Apps & Credentials</li>
                        <li>สร้าง App ใหม่หรือเลือก App ที่มีอยู่</li>
                        <li>คัดลอก Client ID และ Secret</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>
</div>
