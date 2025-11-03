<!-- Razorpay Configuration -->
<div class="bg-white rounded-2xl shadow-lg border border-gray-100 overflow-hidden">
    <div class="bg-gradient-to-r from-indigo-600 to-blue-600 px-6 py-4">
        <h3 class="text-lg font-bold text-white flex items-center">
            <i class="fas fa-bolt mr-2"></i>
            การตั้งค่า Razorpay
        </h3>
    </div>
    <div class="p-6 space-y-4">
        <div class="p-4 bg-indigo-50 rounded-xl border border-indigo-200 mb-4">
            <p class="text-sm text-indigo-800">
                <i class="fas fa-info-circle mr-1"></i>
                <strong>คำแนะนำ:</strong> Razorpay เป็น Payment Gateway ที่ได้รับความนิยมในอินเดีย รองรับหลายสกุลเงิน
                <a href="https://dashboard.razorpay.com/app/keys" target="_blank" class="text-indigo-600 hover:underline font-semibold">
                    ดูคีย์ของคุณที่นี่ <i class="fas fa-external-link-alt text-xs"></i>
                </a>
            </p>
        </div>

        <div>
            <label class="block text-sm font-semibold text-gray-700 mb-2">
                <i class="fas fa-key text-indigo-500 mr-1"></i> Key ID <span class="text-red-500">*</span>
            </label>
            <input type="text" name="credentials[key_id]"
                   value="{{ old('credentials.key_id', $paymentGateway->getCredential('key_id')) }}"
                   class="w-full px-4 py-3 border border-gray-200 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 font-mono text-sm"
                   placeholder="rzp_live_..."
                   required>
            <p class="text-xs text-gray-500 mt-1">
                <i class="fas fa-arrow-right mr-1"></i>
                Razorpay Dashboard → Settings → API Keys → Key ID
            </p>
        </div>

        <div>
            <label class="block text-sm font-semibold text-gray-700 mb-2">
                <i class="fas fa-lock text-indigo-500 mr-1"></i> Key Secret <span class="text-red-500">*</span>
            </label>
            <input type="password" name="credentials[key_secret]"
                   value="{{ old('credentials.key_secret', $paymentGateway->getCredential('key_secret')) }}"
                   class="w-full px-4 py-3 border border-gray-200 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 font-mono text-sm"
                   placeholder="Your Key Secret..."
                   required>
            <p class="text-xs text-gray-500 mt-1">
                <i class="fas fa-arrow-right mr-1"></i>
                Razorpay Dashboard → Settings → API Keys → Key Secret
            </p>
        </div>

        <div>
            <label class="block text-sm font-semibold text-gray-700 mb-2">
                <i class="fas fa-webhook text-indigo-500 mr-1"></i> Webhook Secret
            </label>
            <input type="password" name="credentials[webhook_secret]"
                   value="{{ old('credentials.webhook_secret', $paymentGateway->getCredential('webhook_secret')) }}"
                   class="w-full px-4 py-3 border border-gray-200 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 font-mono text-sm"
                   placeholder="Webhook Secret...">
            <div class="mt-2 p-2 bg-indigo-50 rounded text-xs">
                <strong>Webhook URL:</strong>
                <code class="bg-white px-2 py-1 rounded ml-1">{{ url('/api/webhooks/razorpay') }}</code>
            </div>
        </div>

        <div class="mt-6 p-4 bg-gradient-to-r from-indigo-50 to-blue-50 rounded-xl border border-indigo-200">
            <div class="flex items-start gap-3">
                <i class="fas fa-lightbulb text-indigo-500 text-xl mt-0.5"></i>
                <div class="text-sm">
                    <p class="font-semibold text-indigo-900 mb-2">วิธีการรับ API Keys</p>
                    <ol class="text-indigo-700 space-y-1 list-decimal list-inside">
                        <li>เข้าสู่ระบบ <a href="https://dashboard.razorpay.com" target="_blank" class="underline">Razorpay Dashboard</a></li>
                        <li>ไปที่ Settings → API Keys</li>
                        <li>สร้าง API Key ใหม่ (ถ้ายังไม่มี)</li>
                        <li>คัดลอก Key ID และ Key Secret</li>
                        <li>สำหรับ Webhook: Settings → Webhooks → Add Webhook</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>
</div>
