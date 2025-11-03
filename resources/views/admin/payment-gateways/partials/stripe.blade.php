<!-- Stripe Configuration -->
<div class="bg-white rounded-2xl shadow-lg border border-gray-100 overflow-hidden">
    <div class="bg-gradient-to-r from-purple-600 to-pink-600 px-6 py-4">
        <h3 class="text-lg font-bold text-white flex items-center">
            <i class="fab fa-stripe mr-2"></i>
            การตั้งค่า Stripe
        </h3>
    </div>
    <div class="p-6 space-y-4">
        <div class="p-4 bg-purple-50 rounded-xl border border-purple-200 mb-4">
            <p class="text-sm text-purple-800">
                <i class="fas fa-info-circle mr-1"></i>
                <strong>คำแนะนำ:</strong> Stripe เป็น Payment Gateway ระดับโลก รองรับบัตรเครดิต/เดบิตทั่วโลก
                <a href="https://dashboard.stripe.com/apikeys" target="_blank" class="text-purple-600 hover:underline font-semibold">
                    ดูคีย์ของคุณที่นี่ <i class="fas fa-external-link-alt text-xs"></i>
                </a>
            </p>
        </div>

        <div>
            <label class="block text-sm font-semibold text-gray-700 mb-2">
                <i class="fas fa-key text-purple-500 mr-1"></i> Publishable Key <span class="text-red-500">*</span>
            </label>
            <input type="text" name="credentials[api_key]"
                   value="{{ old('credentials.api_key', $paymentGateway->getCredential('api_key')) }}"
                   class="w-full px-4 py-3 border border-gray-200 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500 font-mono text-sm"
                   placeholder="pk_live_..."
                   required>
            <p class="text-xs text-gray-500 mt-1">
                <i class="fas fa-arrow-right mr-1"></i>
                Stripe Dashboard → Developers → API Keys → Publishable key
            </p>
        </div>

        <div>
            <label class="block text-sm font-semibold text-gray-700 mb-2">
                <i class="fas fa-lock text-purple-500 mr-1"></i> Secret Key <span class="text-red-500">*</span>
            </label>
            <input type="password" name="credentials[secret_key]"
                   value="{{ old('credentials.secret_key', $paymentGateway->getCredential('secret_key')) }}"
                   class="w-full px-4 py-3 border border-gray-200 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500 font-mono text-sm"
                   placeholder="sk_live_..."
                   required>
            <p class="text-xs text-gray-500 mt-1">
                <i class="fas fa-arrow-right mr-1"></i>
                Stripe Dashboard → Developers → API Keys → Secret key
            </p>
        </div>

        <div>
            <label class="block text-sm font-semibold text-gray-700 mb-2">
                <i class="fas fa-webhook text-purple-500 mr-1"></i> Webhook Secret
            </label>
            <input type="password" name="credentials[webhook_secret]"
                   value="{{ old('credentials.webhook_secret', $paymentGateway->getCredential('webhook_secret')) }}"
                   class="w-full px-4 py-3 border border-gray-200 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500 font-mono text-sm"
                   placeholder="whsec_...">
            <p class="text-xs text-gray-500 mt-1">
                <i class="fas fa-arrow-right mr-1"></i>
                Stripe Dashboard → Developers → Webhooks → Add endpoint
            </p>
            <div class="mt-2 p-2 bg-purple-50 rounded text-xs">
                <strong>Webhook URL:</strong>
                <code class="bg-white px-2 py-1 rounded ml-1">{{ url('/api/webhooks/stripe') }}</code>
            </div>
        </div>

        <div x-show="testMode" class="border-t border-gray-200 pt-4">
            <h4 class="font-bold text-gray-900 mb-3">
                <i class="fas fa-flask text-orange-500 mr-1"></i> Test Mode Credentials
            </h4>

            <div class="space-y-3">
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Test Publishable Key</label>
                    <input type="text" name="test_credentials[api_key]"
                           value="{{ old('test_credentials.api_key', $paymentGateway->test_credentials['api_key'] ?? '') }}"
                           class="w-full px-4 py-3 border border-gray-200 rounded-lg focus:ring-2 focus:ring-orange-500 font-mono text-sm"
                           placeholder="pk_test_...">
                </div>

                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Test Secret Key</label>
                    <input type="password" name="test_credentials[secret_key]"
                           value="{{ old('test_credentials.secret_key', $paymentGateway->test_credentials['secret_key'] ?? '') }}"
                           class="w-full px-4 py-3 border border-gray-200 rounded-lg focus:ring-2 focus:ring-orange-500 font-mono text-sm"
                           placeholder="sk_test_...">
                </div>
            </div>
        </div>

        <div class="mt-6 p-4 bg-gradient-to-r from-purple-50 to-pink-50 rounded-xl border border-purple-200">
            <div class="flex items-start gap-3">
                <i class="fas fa-lightbulb text-purple-500 text-xl mt-0.5"></i>
                <div class="text-sm">
                    <p class="font-semibold text-purple-900 mb-2">วิธีการรับ API Keys</p>
                    <ol class="text-purple-700 space-y-1 list-decimal list-inside">
                        <li>เข้าสู่ระบบ <a href="https://dashboard.stripe.com" target="_blank" class="underline">Stripe Dashboard</a></li>
                        <li>คลิกที่ Developers → API Keys</li>
                        <li>คัดลอก Publishable key และ Secret key</li>
                        <li>สำหรับ Webhook: Developers → Webhooks → Add endpoint</li>
                        <li>ใส่ URL: <code class="bg-white px-1 rounded">{{ url('/api/webhooks/stripe') }}</code></li>
                    </ol>
                </div>
            </div>
        </div>
    </div>
</div>
