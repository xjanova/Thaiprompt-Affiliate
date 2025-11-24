@extends('layouts.admin')

@section('title', $pageTitle)

@push('styles')
<style>
    /* Custom styles for toggle switches */
    .toggle-checkbox:checked {
        @apply right-0 border-green-500;
    }
    .toggle-checkbox:checked + .toggle-label {
        @apply bg-green-500;
    }
</style>
@endpush

@section('content')
<div class="container mx-auto px-4 py-8" x-data="settingsManager()">
    {{-- Header --}}
    <div class="mb-8">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div>
                <h1 class="text-3xl font-bold text-gray-900 dark:text-white mb-2">
                    ⚙️ ตั้งค่าระบบสมัครสมาชิก LINE
                </h1>
                <p class="text-gray-600 dark:text-gray-400">
                    จัดการการตั้งค่าต่างๆ ของระบบ AI-Powered Signup System
                </p>
            </div>
            <div class="flex gap-3">
                <button
                    @click="testConnection()"
                    type="button"
                    class="px-4 py-2 bg-blue-600 hover:bg-blue-700 dark:bg-blue-500 dark:hover:bg-blue-600 text-white rounded-lg transition flex items-center gap-2"
                    :disabled="testing"
                >
                    <span x-show="!testing">🔗 ทดสอบการเชื่อมต่อ LINE</span>
                    <span x-show="testing">⏳ กำลังทดสอบ...</span>
                </button>
                <button
                    @click="saveSettings()"
                    type="button"
                    class="px-4 py-2 bg-green-600 hover:bg-green-700 dark:bg-green-500 dark:hover:bg-green-600 text-white rounded-lg transition flex items-center gap-2"
                    :disabled="saving"
                >
                    <span x-show="!saving">💾 บันทึกการตั้งค่า</span>
                    <span x-show="saving">⏳ กำลังบันทึก...</span>
                </button>
            </div>
        </div>
    </div>

    {{-- Success/Error Messages --}}
    @if(session('success'))
    <div class="mb-6 p-4 bg-green-100 dark:bg-green-900/30 border border-green-400 dark:border-green-600 text-green-700 dark:text-green-300 rounded-lg flex items-center gap-3">
        <span class="text-2xl">✅</span>
        <span>{{ session('success') }}</span>
    </div>
    @endif

    @if(session('error') || $errors->any())
    <div class="mb-6 p-4 bg-red-100 dark:bg-red-900/30 border border-red-400 dark:border-red-600 text-red-700 dark:text-red-300 rounded-lg">
        <div class="flex items-center gap-3 mb-2">
            <span class="text-2xl">❌</span>
            <span class="font-bold">เกิดข้อผิดพลาด:</span>
        </div>
        <ul class="list-disc list-inside ml-8">
            @if(session('error'))
            <li>{{ session('error') }}</li>
            @endif
            @foreach($errors->all() as $error)
            <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
    @endif

    {{-- Stats Cards --}}
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4 mb-8">
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6 border border-gray-200 dark:border-gray-700">
            <div class="text-sm text-gray-600 dark:text-gray-400 mb-1">ทั้งหมด</div>
            <div class="text-2xl font-bold text-gray-900 dark:text-white">{{ number_format($stats['total_sessions']) }}</div>
            <div class="text-xs text-gray-500 dark:text-gray-500 mt-1">Sessions</div>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6 border border-gray-200 dark:border-gray-700">
            <div class="text-sm text-gray-600 dark:text-gray-400 mb-1">สำเร็จ</div>
            <div class="text-2xl font-bold text-green-600 dark:text-green-400">{{ number_format($stats['completed_sessions']) }}</div>
            <div class="text-xs text-gray-500 dark:text-gray-500 mt-1">Completed</div>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6 border border-gray-200 dark:border-gray-700">
            <div class="text-sm text-gray-600 dark:text-gray-400 mb-1">กำลังทำ</div>
            <div class="text-2xl font-bold text-blue-600 dark:text-blue-400">{{ number_format($stats['active_sessions']) }}</div>
            <div class="text-xs text-gray-500 dark:text-gray-500 mt-1">Active</div>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6 border border-gray-200 dark:border-gray-700">
            <div class="text-sm text-gray-600 dark:text-gray-400 mb-1">ละทิ้ง</div>
            <div class="text-2xl font-bold text-orange-600 dark:text-orange-400">{{ number_format($stats['abandoned_sessions']) }}</div>
            <div class="text-xs text-gray-500 dark:text-gray-500 mt-1">Abandoned</div>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6 border border-gray-200 dark:border-gray-700">
            <div class="text-sm text-gray-600 dark:text-gray-400 mb-1">อัตราสำเร็จ</div>
            <div class="text-2xl font-bold text-purple-600 dark:text-purple-400">{{ $stats['conversion_rate'] }}%</div>
            <div class="text-xs text-gray-500 dark:text-gray-500 mt-1">Conversion Rate</div>
        </div>
    </div>

    {{-- Settings Form --}}
    <form action="{{ route('admin.line-membership-signup.settings.update') }}" method="POST" id="settingsForm">
        @csrf

        {{-- Settings Groups --}}
        <div class="space-y-6">
            @foreach($settings as $group => $groupSettings)
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow border border-gray-200 dark:border-gray-700 overflow-hidden">
                {{-- Group Header --}}
                <div class="bg-gradient-to-r from-blue-500 to-blue-600 dark:from-blue-600 dark:to-blue-700 px-6 py-4 flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <span class="text-2xl">
                            @switch($group)
                                @case('signup') 📝 @break
                                @case('otp') 🔐 @break
                                @case('validation') ✅ @break
                                @case('rewards') 🎁 @break
                                @case('gamification') 🎮 @break
                                @case('session') ⏱️ @break
                                @case('integration') 🔌 @break
                                @default ⚙️
                            @endswitch
                        </span>
                        <h3 class="text-xl font-bold text-white">
                            @switch($group)
                                @case('signup') การตั้งค่าทั่วไป @break
                                @case('otp') ระบบ OTP @break
                                @case('validation') การตรวจสอบข้อมูล @break
                                @case('rewards') ระบบรางวัล @break
                                @case('gamification') เกมมิฟิเคชั่น @break
                                @case('session') จัดการ Session @break
                                @case('integration') การเชื่อมต่อ LINE @break
                                @default {{ ucfirst($group) }}
                            @endswitch
                        </h3>
                    </div>
                    <button
                        type="button"
                        @click="confirmReset('{{ $group }}')"
                        class="px-3 py-1 bg-white/20 hover:bg-white/30 text-white text-sm rounded transition"
                    >
                        🔄 รีเซ็ต
                    </button>
                </div>

                {{-- Group Settings --}}
                <div class="p-6 space-y-4">
                    @foreach($groupSettings as $setting)
                    <div class="border-b border-gray-200 dark:border-gray-700 pb-4 last:border-b-0 last:pb-0">
                        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                            <div class="flex-1">
                                <label class="block font-medium text-gray-900 dark:text-white mb-1">
                                    {{ ucfirst(str_replace(['_', '.'], ' ', $setting->key)) }}
                                </label>
                                @if($setting->description)
                                <p class="text-sm text-gray-600 dark:text-gray-400">
                                    {{ $setting->description }}
                                </p>
                                @endif
                            </div>

                            <div class="md:w-64">
                                @if($setting->type === 'boolean')
                                    {{-- Toggle Switch --}}
                                    <div class="relative inline-block w-16 align-middle select-none">
                                        <input
                                            type="checkbox"
                                            name="settings[{{ $setting->key }}]"
                                            id="setting_{{ $setting->id }}"
                                            value="1"
                                            {{ $setting->getCastedValue() ? 'checked' : '' }}
                                            {{ !$setting->is_editable ? 'disabled' : '' }}
                                            class="toggle-checkbox absolute block w-8 h-8 rounded-full bg-white border-4 appearance-none cursor-pointer transition-all duration-200 ease-in-out"
                                        >
                                        <label
                                            for="setting_{{ $setting->id }}"
                                            class="toggle-label block overflow-hidden h-8 rounded-full bg-gray-300 dark:bg-gray-600 cursor-pointer transition-all duration-200 ease-in-out"
                                        ></label>
                                    </div>
                                @elseif($setting->type === 'integer')
                                    <input
                                        type="number"
                                        name="settings[{{ $setting->key }}]"
                                        value="{{ $setting->getCastedValue() }}"
                                        {{ !$setting->is_editable ? 'readonly' : '' }}
                                        class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 focus:border-transparent transition"
                                    >
                                @elseif($setting->type === 'json')
                                    <textarea
                                        name="settings[{{ $setting->key }}]"
                                        rows="3"
                                        {{ !$setting->is_editable ? 'readonly' : '' }}
                                        class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 focus:border-transparent transition font-mono text-sm"
                                    >{{ json_encode($setting->getCastedValue(), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</textarea>
                                @else
                                    <input
                                        type="text"
                                        name="settings[{{ $setting->key }}]"
                                        value="{{ $setting->value }}"
                                        {{ !$setting->is_editable ? 'readonly' : '' }}
                                        class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 focus:border-transparent transition"
                                    >
                                @endif
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
            @endforeach
        </div>

        {{-- Submit Button (Bottom) --}}
        <div class="mt-8 flex justify-end gap-3">
            <button
                type="button"
                @click="confirmResetAll()"
                class="px-6 py-3 bg-gray-600 hover:bg-gray-700 dark:bg-gray-500 dark:hover:bg-gray-600 text-white rounded-lg transition"
            >
                🔄 รีเซ็ตทั้งหมด
            </button>
            <button
                type="submit"
                class="px-6 py-3 bg-green-600 hover:bg-green-700 dark:bg-green-500 dark:hover:bg-green-600 text-white rounded-lg transition flex items-center gap-2"
            >
                💾 บันทึกการตั้งค่า
            </button>
        </div>
    </form>
</div>

@push('scripts')
<script>
function settingsManager() {
    return {
        saving: false,
        testing: false,

        /**
         * บันทึกการตั้งค่า
         */
        saveSettings() {
            if (this.saving) return;

            this.saving = true;
            document.getElementById('settingsForm').submit();
        },

        /**
         * ทดสอบการเชื่อมต่อ LINE
         */
        async testConnection() {
            if (this.testing) return;

            this.testing = true;

            try {
                const response = await fetch('{{ route('admin.line-membership-signup.settings.test-connection') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    }
                });

                const data = await response.json();

                if (data.success) {
                    alert('✅ เชื่อมต่อ LINE สำเร็จ!\n\n' + JSON.stringify(data.data, null, 2));
                } else {
                    alert('❌ เชื่อมต่อ LINE ล้มเหลว!\n\n' + data.message);
                }
            } catch (error) {
                alert('❌ เกิดข้อผิดพลาด: ' + error.message);
            } finally {
                this.testing = false;
            }
        },

        /**
         * ยืนยันการรีเซ็ตกลุ่ม
         */
        confirmReset(group) {
            if (confirm(`คุณต้องการรีเซ็ตการตั้งค่ากลุ่ม "${group}" กลับเป็นค่าเริ่มต้นใช่หรือไม่?`)) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = '{{ route('admin.line-membership-signup.settings.reset') }}';

                const csrfInput = document.createElement('input');
                csrfInput.type = 'hidden';
                csrfInput.name = '_token';
                csrfInput.value = '{{ csrf_token() }}';
                form.appendChild(csrfInput);

                const groupInput = document.createElement('input');
                groupInput.type = 'hidden';
                groupInput.name = 'group';
                groupInput.value = group;
                form.appendChild(groupInput);

                document.body.appendChild(form);
                form.submit();
            }
        },

        /**
         * ยืนยันการรีเซ็ตทั้งหมด
         */
        confirmResetAll() {
            if (confirm('⚠️ คุณต้องการรีเซ็ตการตั้งค่าทั้งหมดกลับเป็นค่าเริ่มต้นใช่หรือไม่?\n\n(ยกเว้นการตั้งค่าการเชื่อมต่อ LINE)')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = '{{ route('admin.line-membership-signup.settings.reset') }}';

                const csrfInput = document.createElement('input');
                csrfInput.type = 'hidden';
                csrfInput.name = '_token';
                csrfInput.value = '{{ csrf_token() }}';
                form.appendChild(csrfInput);

                document.body.appendChild(form);
                form.submit();
            }
        }
    }
}
</script>
@endpush
@endsection
