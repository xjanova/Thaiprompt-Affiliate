@extends('layouts.admin')

@section('title', 'เพิ่มการตั้งค่า MLM ใหม่')

@section('content')
<div class="max-w-4xl mx-auto">
    <!-- Header -->
    <div class="bg-white rounded-lg shadow-md p-6 mb-6">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-2xl font-bold text-gray-800">
                    ⚙️ เพิ่มการตั้งค่า MLM ใหม่
                </h2>
                <p class="text-sm text-gray-600 mt-1">
                    สร้างการตั้งค่าใหม่สำหรับระบบ MLM
                </p>
            </div>
            <a href="{{ route('admin.mlm.settings.index') }}"
               class="px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-700 rounded-lg transition-all duration-200">
                ← กลับ
            </a>
        </div>
    </div>

    <!-- Info Alert -->
    <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
        <div class="flex items-start">
            <svg class="w-5 h-5 text-blue-500 mt-0.5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <div>
                <h3 class="text-sm font-semibold text-blue-800">คำแนะนำการตั้งค่า</h3>
                <p class="text-sm text-blue-700 mt-1">
                    Key ควรเป็นชื่อที่ไม่ซ้ำกันและเป็นภาษาอังกฤษแบบ snake_case (เช่น auto_approve_commissions)
                    การตั้งค่าเหล่านี้จะส่งผลกระทบต่อการทำงานของระบบ MLM
                </p>
            </div>
        </div>
    </div>

    <!-- Form -->
    <form action="{{ route('admin.mlm.settings.store') }}" method="POST">
        @csrf

        <div class="bg-white rounded-lg shadow-md p-6 space-y-6">
            <!-- Basic Information -->
            <div>
                <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center">
                    <span class="mr-2">📝</span>
                    ข้อมูลการตั้งค่า
                </h3>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Key <span class="text-red-500">*</span>
                        </label>
                        <input type="text" name="key" value="{{ old('key') }}"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500"
                               placeholder="e.g., auto_approve_commissions" required>
                        <p class="text-xs text-gray-500 mt-1">ชื่อเฉพาะสำหรับการตั้งค่า (ภาษาอังกฤษ, snake_case)</p>
                        @error('key')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Group <span class="text-red-500">*</span>
                        </label>
                        <input type="text" name="group" value="{{ old('group') }}"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500"
                               placeholder="e.g., general, calculation, payout" required>
                        <p class="text-xs text-gray-500 mt-1">กลุ่มของการตั้งค่า (ใช้ในการจัดหมวดหมู่)</p>
                        @error('group')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Type <span class="text-red-500">*</span>
                        </label>
                        <select name="type" id="type-select"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500"
                                required>
                            <option value="">เลือกประเภท...</option>
                            <option value="string" {{ old('type') === 'string' ? 'selected' : '' }}>String (ข้อความ)</option>
                            <option value="number" {{ old('type') === 'number' ? 'selected' : '' }}>Number (ตัวเลข)</option>
                            <option value="boolean" {{ old('type') === 'boolean' ? 'selected' : '' }}>Boolean (จริง/เท็จ)</option>
                            <option value="json" {{ old('type') === 'json' ? 'selected' : '' }}>JSON (ข้อมูลซับซ้อน)</option>
                        </select>
                        <p class="text-xs text-gray-500 mt-1">ประเภทของข้อมูล</p>
                        @error('type')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div id="value-container">
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Value <span class="text-red-500">*</span>
                        </label>
                        <input type="text" name="value" id="value-input" value="{{ old('value') }}"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500"
                               placeholder="ค่าของการตั้งค่า" required>
                        <p class="text-xs text-gray-500 mt-1" id="value-hint">ค่าของการตั้งค่า</p>
                        @error('value')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div class="mt-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Description (EN)
                    </label>
                    <textarea name="description" rows="3"
                              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500"
                              placeholder="Description of this setting in English...">{{ old('description') }}</textarea>
                    @error('description')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div class="mt-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Description (TH)
                    </label>
                    <textarea name="description_th" rows="3"
                              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500"
                              placeholder="คำอธิบายของการตั้งค่านี้เป็นภาษาไทย...">{{ old('description_th') }}</textarea>
                    @error('description_th')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <!-- Examples -->
            <div class="border-t border-gray-200 pt-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center">
                    <span class="mr-2">💡</span>
                    ตัวอย่างการตั้งค่า
                </h3>

                <div class="bg-gradient-to-r from-purple-50 to-pink-50 rounded-lg p-4">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                        <div>
                            <h4 class="font-medium text-gray-700 mb-2">String (ข้อความ)</h4>
                            <ul class="space-y-1 text-gray-600">
                                <li>• Key: <code class="bg-white px-2 py-0.5 rounded">default_currency</code></li>
                                <li>• Value: <code class="bg-white px-2 py-0.5 rounded">THB</code></li>
                            </ul>
                        </div>
                        <div>
                            <h4 class="font-medium text-gray-700 mb-2">Number (ตัวเลข)</h4>
                            <ul class="space-y-1 text-gray-600">
                                <li>• Key: <code class="bg-white px-2 py-0.5 rounded">minimum_payout</code></li>
                                <li>• Value: <code class="bg-white px-2 py-0.5 rounded">500</code></li>
                            </ul>
                        </div>
                        <div>
                            <h4 class="font-medium text-gray-700 mb-2">Boolean (จริง/เท็จ)</h4>
                            <ul class="space-y-1 text-gray-600">
                                <li>• Key: <code class="bg-white px-2 py-0.5 rounded">auto_approve_commissions</code></li>
                                <li>• Value: <code class="bg-white px-2 py-0.5 rounded">true</code> หรือ <code class="bg-white px-2 py-0.5 rounded">false</code></li>
                            </ul>
                        </div>
                        <div>
                            <h4 class="font-medium text-gray-700 mb-2">JSON (ข้อมูลซับซ้อน)</h4>
                            <ul class="space-y-1 text-gray-600">
                                <li>• Key: <code class="bg-white px-2 py-0.5 rounded">commission_tiers</code></li>
                                <li>• Value: <code class="bg-white px-2 py-0.5 rounded">{"tier1": 10, "tier2": 5}</code></li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Submit Buttons -->
            <div class="border-t border-gray-200 pt-6">
                <div class="flex items-center justify-end gap-3">
                    <a href="{{ route('admin.mlm.settings.index') }}"
                       class="px-6 py-2 bg-gray-200 hover:bg-gray-300 text-gray-700 rounded-lg transition-all duration-200">
                        ยกเลิก
                    </a>
                    <button type="submit"
                            class="px-6 py-2 bg-gradient-to-r from-purple-600 to-pink-600 hover:from-purple-700 hover:to-pink-700 text-white rounded-lg shadow-lg transition-all duration-200 flex items-center gap-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                        สร้างการตั้งค่า
                    </button>
                </div>
            </div>
        </div>
    </form>
</div>

<!-- Dynamic Value Input Script -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const typeSelect = document.getElementById('type-select');
    const valueContainer = document.getElementById('value-container');
    const valueHint = document.getElementById('value-hint');

    typeSelect.addEventListener('change', function() {
        const type = this.value;
        const oldValue = document.getElementById('value-input')?.value || '';

        let inputHTML = '';
        let hintText = '';

        switch(type) {
            case 'string':
                inputHTML = `<input type="text" name="value" id="value-input" value="${oldValue}"
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500"
                           placeholder="ข้อความ" required>`;
                hintText = 'ใส่ข้อความ (เช่น THB, USD, enabled)';
                break;

            case 'number':
                inputHTML = `<input type="number" name="value" id="value-input" value="${oldValue}" step="any"
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500"
                           placeholder="ตัวเลข" required>`;
                hintText = 'ใส่ตัวเลข (เช่น 100, 5.5, 10.25)';
                break;

            case 'boolean':
                const isTrue = oldValue === 'true' || oldValue === '1';
                inputHTML = `<select name="value" id="value-input"
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500"
                           required>
                    <option value="true" ${isTrue ? 'selected' : ''}>True (เปิด/จริง)</option>
                    <option value="false" ${!isTrue ? 'selected' : ''}>False (ปิด/เท็จ)</option>
                </select>`;
                hintText = 'เลือก True หรือ False';
                break;

            case 'json':
                inputHTML = `<textarea name="value" id="value-input" rows="4"
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 font-mono text-sm"
                           placeholder='{"key": "value"}' required>${oldValue}</textarea>`;
                hintText = 'ใส่ข้อมูล JSON ที่ถูกต้อง (เช่น {"tier1": 10, "tier2": 5})';
                break;

            default:
                inputHTML = `<input type="text" name="value" id="value-input" value="${oldValue}"
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500"
                           placeholder="ค่าของการตั้งค่า" required>`;
                hintText = 'ค่าของการตั้งค่า';
        }

        // Update the value input
        const labelHTML = `<label class="block text-sm font-medium text-gray-700 mb-2">
            Value <span class="text-red-500">*</span>
        </label>`;

        valueContainer.innerHTML = labelHTML + inputHTML +
            `<p class="text-xs text-gray-500 mt-1" id="value-hint">${hintText}</p>`;
    });

    // Trigger change event if there's an old type value
    if (typeSelect.value) {
        typeSelect.dispatchEvent(new Event('change'));
    }
});
</script>
@endsection
