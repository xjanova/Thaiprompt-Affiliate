<!-- Omise Configuration -->
<div class="bg-white rounded-2xl shadow-lg border border-gray-100 overflow-hidden">
    <div class="bg-gradient-to-r from-cyan-600 to-blue-600 px-6 py-4">
        <h3 class="text-lg font-bold text-white flex items-center">
            <i class="fas fa-credit-card mr-2"></i>
            การตั้งค่า Omise
        </h3>
    </div>
    <div class="p-6 space-y-4">
        <div class="p-4 bg-cyan-50 rounded-xl border border-cyan-200 mb-4">
            <p class="text-sm text-cyan-800">
                <i class="fas fa-info-circle mr-1"></i>
                <strong>คำแนะนำ:</strong> Omise เป็น Payment Gateway ที่ได้รับความนิยมในเอเชียตะวันออกเฉียงใต้
                <a href="https://dashboard.omise.co/keys" target="_blank" class="text-cyan-600 hover:underline font-semibold">
                    ดูคีย์ของคุณที่นี่ <i class="fas fa-external-link-alt text-xs"></i>
                </a>
            </p>
        </div>

        <div>
            <label class="block text-sm font-semibold text-gray-700 mb-2">
                <i class="fas fa-key text-cyan-500 mr-1"></i> Public Key <span class="text-red-500">*</span>
            </label>
            <input type="text" name="credentials[public_key]"
                   value="{{ old('credentials.public_key', $paymentGateway->getCredential('public_key')) }}"
                   class="w-full px-4 py-3 border border-gray-200 rounded-lg focus:ring-2 focus:ring-cyan-500 focus:border-cyan-500 font-mono text-sm"
                   placeholder="pkey_..."
                   required>
            <p class="text-xs text-gray-500 mt-1">
                <i class="fas fa-arrow-right mr-1"></i>
                Omise Dashboard → API Keys → Public key
            </p>
        </div>

        <div>
            <label class="block text-sm font-semibold text-gray-700 mb-2">
                <i class="fas fa-lock text-cyan-500 mr-1"></i> Secret Key <span class="text-red-500">*</span>
            </label>
            <input type="password" name="credentials[secret_key]"
                   value="{{ old('credentials.secret_key', $paymentGateway->getCredential('secret_key')) }}"
                   class="w-full px-4 py-3 border border-gray-200 rounded-lg focus:ring-2 focus:ring-cyan-500 focus:border-cyan-500 font-mono text-sm"
                   placeholder="skey_..."
                   required>
            <p class="text-xs text-gray-500 mt-1">
                <i class="fas fa-arrow-right mr-1"></i>
                Omise Dashboard → API Keys → Secret key
            </p>
        </div>

        <div class="mt-6 p-4 bg-gradient-to-r from-cyan-50 to-blue-50 rounded-xl border border-cyan-200">
            <div class="flex items-start gap-3">
                <i class="fas fa-lightbulb text-cyan-500 text-xl mt-0.5"></i>
                <div class="text-sm">
                    <p class="font-semibold text-cyan-900 mb-2">วิธีการรับ API Keys</p>
                    <ol class="text-cyan-700 space-y-1 list-decimal list-inside">
                        <li>เข้าสู่ระบบ <a href="https://dashboard.omise.co" target="_blank" class="underline">Omise Dashboard</a></li>
                        <li>ไปที่เมนู API Keys</li>
                        <li>คัดลอก Public key และ Secret key</li>
                        <li>ใช้ Test keys สำหรับการทดสอบ</li>
                        <li>ใช้ Live keys สำหรับการใช้งานจริง</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>
</div>
