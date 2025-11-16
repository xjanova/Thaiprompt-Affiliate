@extends('layouts.admin-v3')

@section('title', 'Cookie Settings')

@section('content')
<div class="p-6">
    <div class="mb-6">
        <h1 class="text-3xl font-bold text-gray-900 dark:text-white">ตั้งค่าคุกกี้</h1>
        <p class="text-gray-600 dark:text-gray-400 dark:text-gray-400 mt-1">จัดการการแสดงผลและข้อความของระบบคุกกี้ PDPA</p>
    </div>

    @if(session('success'))
    <div class="mb-6 p-4 bg-green-100 dark:bg-green-900 text-green-800 dark:text-green-200 rounded-xl">
        {{ session('success') }}
    </div>
    @endif

    <form action="{{ route('admin.cookie-analytics.settings.update') }}" method="POST" class="space-y-6">
        @csrf
        @method('PUT')

        <div class="glass-fusion dark:bg-gray-800 rounded-xl shadow p-6" hover:scale-105 transition-transform border border-white/20 dark:border-white/10>
            <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-4">การตั้งค่าทั่วไป</h2>

            <div class="space-y-4">
                <!-- Enable/Disable Banner -->
                <div class="flex items-center justify-between p-4 bg-gray-100/50 dark:bg-gray-800/50/50 dark:bg-gray-800/50 dark:bg-gray-700 rounded-xl">
                    <div>
                        <label class="text-gray-900 dark:text-white font-medium">เปิดใช้งาน Cookie Banner</label>
                        <p class="text-sm text-gray-600 dark:text-gray-400 dark:text-gray-400 mt-1">แสดงป๊อปอัพขอความยินยอมคุกกี้</p>
                    </div>
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="hidden" name="cookie_banner_enabled" value="0">
                        <input type="checkbox" name="cookie_banner_enabled" value="1" {{ $settings['cookie_banner_enabled'] ? 'checked' : '' }} class="sr-only peer">
                        <div class="w-11 h-6 bg-gray-200 dark:bg-gray-700 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-indigo-300 dark:peer-focus:ring-indigo-800 rounded-full peer dark:bg-gray-600 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:glass-fusion after:border-gray-300 dark:border-gray-600 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-indigo-600" border border-white/20 dark:border-white/10></div>
                    </label>
                </div>

                <!-- Auto Block Without Consent -->
                <div class="flex items-center justify-between p-4 bg-gray-100/50 dark:bg-gray-800/50/50 dark:bg-gray-800/50 dark:bg-gray-700 rounded-xl">
                    <div>
                        <label class="text-gray-900 dark:text-white font-medium">บล็อกการติดตามอัตโนมัติ</label>
                        <p class="text-sm text-gray-600 dark:text-gray-400 dark:text-gray-400 mt-1">บล็อกการติดตามจนกว่าจะได้รับความยินยอม</p>
                    </div>
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="hidden" name="auto_block_without_consent" value="0">
                        <input type="checkbox" name="auto_block_without_consent" value="1" {{ $settings['auto_block_without_consent'] ? 'checked' : '' }} class="sr-only peer">
                        <div class="w-11 h-6 bg-gray-200 dark:bg-gray-700 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-indigo-300 dark:peer-focus:ring-indigo-800 rounded-full peer dark:bg-gray-600 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:glass-fusion after:border-gray-300 dark:border-gray-600 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-indigo-600" border border-white/20 dark:border-white/10></div>
                    </label>
                </div>

                <!-- Cookie Expiry Days -->
                <div>
                    <label class="block text-gray-900 dark:text-white font-medium mb-2">
                        อายุของคุกกี้ (วัน)
                    </label>
                    <input type="number" name="cookie_expiry_days" value="{{ $settings['cookie_expiry_days'] }}" min="1" max="730" class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 dark:border-gray-600 rounded-xl dark:bg-gray-700 dark:text-white" required>
                    <p class="text-sm text-gray-600 dark:text-gray-400 dark:text-gray-400 mt-1">ระยะเวลาที่คุกกี้จะถูกเก็บไว้ (1-730 วัน)</p>
                    @error('cookie_expiry_days')
                    <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>
            </div>
        </div>

        <div class="glass-fusion dark:bg-gray-800 rounded-xl shadow p-6" hover:scale-105 transition-transform border border-white/20 dark:border-white/10>
            <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-4">ข้อความแบนเนอร์</h2>

            <div class="space-y-4">
                <!-- Banner Title -->
                <div>
                    <label class="block text-gray-900 dark:text-white font-medium mb-2">
                        หัวข้อแบนเนอร์
                    </label>
                    <input type="text" name="cookie_banner_title" value="{{ $settings['cookie_banner_title'] }}" class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 dark:border-gray-600 rounded-xl dark:bg-gray-700 dark:text-white" required>
                    @error('cookie_banner_title')
                    <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Banner Description -->
                <div>
                    <label class="block text-gray-900 dark:text-white font-medium mb-2">
                        คำอธิบาย
                    </label>
                    <textarea name="cookie_banner_description" rows="4" class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 dark:border-gray-600 rounded-xl dark:bg-gray-700 dark:text-white" required>{{ $settings['cookie_banner_description'] }}</textarea>
                    <p class="text-sm text-gray-600 dark:text-gray-400 dark:text-gray-400 mt-1">คำอธิบายที่แสดงในแบนเนอร์คุกกี้</p>
                    @error('cookie_banner_description')
                    <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Cookie Policy URL -->
                <div>
                    <label class="block text-gray-900 dark:text-white font-medium mb-2">
                        URL นโยบายคุกกี้
                    </label>
                    <input type="text" name="cookie_policy_url" value="{{ $settings['cookie_policy_url'] }}" class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 dark:border-gray-600 rounded-xl dark:bg-gray-700 dark:text-white" required>
                    <p class="text-sm text-gray-600 dark:text-gray-400 dark:text-gray-400 mt-1">URL ของหน้านโยบายคุกกี้</p>
                    @error('cookie_policy_url')
                    <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>
            </div>
        </div>

        <!-- Preview -->
        <div class="glass-fusion dark:bg-gray-800 rounded-xl shadow p-6" hover:scale-105 transition-transform border border-white/20 dark:border-white/10>
            <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-4">ตัวอย่าง</h2>
            <div class="p-6 bg-gray-100/50 dark:bg-gray-800/50 dark:bg-gray-700 rounded-xl border-4 border-indigo-600">
                <div class="flex items-center gap-3 mb-2">
                    <svg class="w-6 h-6 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4"></path>
                    </svg>
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white" id="preview-title">
                        {{ $settings['cookie_banner_title'] }}
                    </h3>
                </div>
                <p class="text-sm text-gray-600 dark:text-gray-400 dark:text-gray-300" id="preview-description">
                    {{ $settings['cookie_banner_description'] }}
                </p>
                <a href="{{ $settings['cookie_policy_url'] }}" target="_blank" class="text-sm text-indigo-600 hover:text-indigo-800 underline mt-1 inline-block">
                    อ่านนโยบายคุกกี้
                </a>
            </div>
        </div>

        <div class="flex gap-4">
            <button type="submit" class="px-6 py-3 bg-indigo-600 text-white rounded-xl hover:bg-indigo-700 transition-colors">
                บันทึกการตั้งค่า
            </button>
            <a href="{{ route('admin.cookie-analytics.index') }}" class="px-6 py-3 bg-gray-300 dark:bg-gray-600 text-gray-900 dark:text-white rounded-xl hover:bg-gray-400 dark:hover:bg-gray-500 transition-colors">
                ยกเลิก
            </a>
        </div>
    </form>
</div>

<script>
// Live Preview
document.querySelector('[name="cookie_banner_title"]').addEventListener('input', function(e) {
    document.getElementById('preview-title').textContent = e.target.value;
});

document.querySelector('[name="cookie_banner_description"]').addEventListener('input', function(e) {
    document.getElementById('preview-description').textContent = e.target.value;
});
</script>
@endsection
