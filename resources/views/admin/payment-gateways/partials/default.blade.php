<!-- Default Gateway Configuration -->
<div class="bg-white rounded-2xl shadow-lg border border-gray-100 overflow-hidden">
    <div class="bg-gradient-to-r from-gray-600 to-slate-600 px-6 py-4">
        <h3 class="text-lg font-bold text-white flex items-center">
            <i class="fas fa-cog mr-2"></i>
            การตั้งค่า {{ $paymentGateway->name }}
        </h3>
    </div>
    <div class="p-6 space-y-4">
        <div class="p-4 bg-gray-50 rounded-xl border border-gray-200 mb-4">
            <p class="text-sm text-gray-700">
                <i class="fas fa-info-circle mr-1"></i>
                <strong>คำแนะนำ:</strong> กำหนดค่าสำหรับ {{ $paymentGateway->name }}
            </p>
        </div>

        <div>
            <label class="block text-sm font-semibold text-gray-700 mb-2">
                ชื่อที่แสดง
            </label>
            <input type="text" name="name"
                   value="{{ old('name', $paymentGateway->name) }}"
                   class="w-full px-4 py-3 border border-gray-200 rounded-lg focus:ring-2 focus:ring-gray-500 focus:border-gray-500">
        </div>

        <div>
            <label class="block text-sm font-semibold text-gray-700 mb-2">
                คำอธิบาย
            </label>
            <textarea name="description" rows="3"
                      class="w-full px-4 py-3 border border-gray-200 rounded-lg focus:ring-2 focus:ring-gray-500 focus:border-gray-500">{{ old('description', $paymentGateway->description) }}</textarea>
        </div>

        <div>
            <label class="block text-sm font-semibold text-gray-700 mb-2">
                คำแนะนำการตั้งค่า
            </label>
            <textarea name="instructions" rows="5"
                      class="w-full px-4 py-3 border border-gray-200 rounded-lg focus:ring-2 focus:ring-gray-500 focus:border-gray-500">{{ old('instructions', $paymentGateway->instructions) }}</textarea>
        </div>

        <div>
            <label class="block text-sm font-semibold text-gray-700 mb-2">
                ลิงก์เอกสารช่วยเหลือ
            </label>
            <input type="url" name="help_url"
                   value="{{ old('help_url', $paymentGateway->help_url) }}"
                   class="w-full px-4 py-3 border border-gray-200 rounded-lg focus:ring-2 focus:ring-gray-500 focus:border-gray-500"
                   placeholder="https://...">
        </div>
    </div>
</div>
