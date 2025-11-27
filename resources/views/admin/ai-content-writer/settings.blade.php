@extends('layouts.admin')

@section('title', $pageTitle ?? 'ตั้งค่า AI Content Writer')

@section('content')
<div class="space-y-6" x-data="settingsForm()">

    {{-- Header --}}
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">ตั้งค่า AI Content Writer</h1>
            <p class="text-gray-600 dark:text-gray-400 mt-1">กำหนด API Keys สำหรับ OpenAI, Claude, Gemini</p>
        </div>
        <a href="{{ route('admin.ai-content-writer.dashboard') }}" class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">
            <i class="fas fa-arrow-left mr-2"></i> กลับ
        </a>
    </div>

    {{-- Settings Form --}}
    <form @submit.prevent="saveSettings">
        @foreach($groups as $groupKey => $groupLabel)
        @if(isset($settingsByGroup[$groupKey]) && $settingsByGroup[$groupKey]->count() > 0)
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 mb-6">
            <div class="p-4 border-b border-gray-200 dark:border-gray-700">
                <h3 class="font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                    @if($groupKey === 'openai')
                        <i class="fas fa-robot text-green-500"></i>
                    @elseif($groupKey === 'claude')
                        <i class="fas fa-brain text-purple-500"></i>
                    @elseif($groupKey === 'gemini')
                        <i class="fas fa-gem text-blue-500"></i>
                    @else
                        <i class="fas fa-cog text-gray-500"></i>
                    @endif
                    {{ $groupLabel }}
                </h3>
            </div>
            <div class="p-4 space-y-4">
                @foreach($settingsByGroup[$groupKey] as $setting)
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        {{ $setting->label ?? $setting->key }}
                        @if($setting->description)
                            <span class="text-xs text-gray-400 block">{{ $setting->description }}</span>
                        @endif
                    </label>

                    @if($setting->type === 'encrypted')
                        <div class="relative">
                            <input :type="showPassword['{{ $setting->key }}'] ? 'text' : 'password'"
                                   name="settings[{{ $setting->key }}]"
                                   value="{{ $setting->decoded_value }}"
                                   class="w-full px-4 py-2 pr-10 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-purple-500 focus:border-transparent"
                                   placeholder="sk-...">
                            <button type="button"
                                    @click="showPassword['{{ $setting->key }}'] = !showPassword['{{ $setting->key }}']"
                                    class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600">
                                <i :class="showPassword['{{ $setting->key }}'] ? 'fas fa-eye-slash' : 'fas fa-eye'"></i>
                            </button>
                        </div>
                    @elseif($setting->type === 'boolean')
                        <label class="inline-flex items-center">
                            <input type="checkbox"
                                   name="settings[{{ $setting->key }}]"
                                   value="1"
                                   {{ $setting->decoded_value ? 'checked' : '' }}
                                   class="rounded border-gray-300 text-purple-600 focus:ring-purple-500">
                            <span class="ml-2 text-sm text-gray-600 dark:text-gray-400">เปิดใช้งาน</span>
                        </label>
                    @elseif($setting->type === 'integer')
                        <input type="number"
                               name="settings[{{ $setting->key }}]"
                               value="{{ $setting->decoded_value }}"
                               class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                    @else
                        <input type="text"
                               name="settings[{{ $setting->key }}]"
                               value="{{ $setting->decoded_value }}"
                               class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                    @endif
                </div>
                @endforeach

                {{-- Test Connection Button --}}
                @if(in_array($groupKey, ['openai', 'claude', 'gemini']))
                <div class="pt-2">
                    <button type="button"
                            @click="testConnection('{{ $groupKey }}')"
                            :disabled="testing === '{{ $groupKey }}'"
                            class="px-4 py-2 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600 transition disabled:opacity-50">
                        <span x-show="testing !== '{{ $groupKey }}'">
                            <i class="fas fa-plug mr-2"></i> ทดสอบการเชื่อมต่อ
                        </span>
                        <span x-show="testing === '{{ $groupKey }}'" x-cloak>
                            <i class="fas fa-spinner fa-spin mr-2"></i> กำลังทดสอบ...
                        </span>
                    </button>
                </div>
                @endif
            </div>
        </div>
        @endif
        @endforeach

        {{-- Save Button --}}
        <div class="flex justify-end">
            <button type="submit"
                    :disabled="saving"
                    class="px-6 py-2.5 bg-purple-600 hover:bg-purple-700 text-white font-semibold rounded-xl transition disabled:opacity-50">
                <span x-show="!saving">
                    <i class="fas fa-save mr-2"></i> บันทึกการตั้งค่า
                </span>
                <span x-show="saving" x-cloak>
                    <i class="fas fa-spinner fa-spin mr-2"></i> กำลังบันทึก...
                </span>
            </button>
        </div>
    </form>
</div>

@push('scripts')
<script>
function settingsForm() {
    return {
        showPassword: {},
        saving: false,
        testing: null,

        async saveSettings() {
            this.saving = true;
            const form = document.querySelector('form');
            const formData = new FormData(form);

            try {
                const response = await fetch('{{ route("admin.ai-content-writer.settings.save") }}', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json',
                    },
                    body: formData
                });

                const result = await response.json();

                if (result.success) {
                    alert('บันทึกเรียบร้อยแล้ว');
                } else {
                    alert(result.message || 'เกิดข้อผิดพลาด');
                }
            } catch (error) {
                alert('เกิดข้อผิดพลาดในการบันทึก');
            }

            this.saving = false;
        },

        async testConnection(provider) {
            this.testing = provider;

            try {
                const response = await fetch(`{{ url('admin/ai-content-writer/settings/test') }}/${provider}`, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json',
                    }
                });

                const result = await response.json();
                alert(result.message);
            } catch (error) {
                alert('เกิดข้อผิดพลาดในการทดสอบ');
            }

            this.testing = null;
        }
    }
}
</script>
@endpush
@endsection
