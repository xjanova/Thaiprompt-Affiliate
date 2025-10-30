@extends('layouts.admin')

@section('title', 'จัดการภาษา')

@section('content')
<div x-data="languageSettings()" class="space-y-6">
    <!-- Header -->
    <div class="bg-white rounded-lg shadow-md p-6">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">จัดการภาษา</h1>
                <p class="mt-1 text-sm text-gray-600">
                    ตั้งค่าภาษาที่จะแสดงในเว็บไซต์ และปรับแต่งรูปแบบการแสดงผล
                </p>
            </div>
            <button @click="saveAll()" :disabled="saving"
                    class="px-6 py-3 bg-indigo-600 text-white font-medium rounded-lg hover:bg-indigo-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors">
                <span x-show="!saving">บันทึกการตั้งค่า</span>
                <span x-show="saving" class="flex items-center">
                    <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    กำลังบันทึก...
                </span>
            </button>
        </div>
    </div>

    <!-- Tabs -->
    <div class="bg-white rounded-lg shadow-md">
        <div class="border-b border-gray-200">
            <nav class="flex -mb-px">
                <button @click="activeTab = 'languages'"
                        :class="{ 'border-indigo-500 text-indigo-600': activeTab === 'languages', 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300': activeTab !== 'languages' }"
                        class="px-6 py-4 text-sm font-medium border-b-2 transition-colors duration-200">
                    <span class="flex items-center">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5h12M9 3v2m1.048 9.5A18.022 18.022 0 016.412 9m6.088 9h7M11 21l5-10 5 10M12.751 5C11.783 10.77 8.07 15.61 3 18.129"></path>
                        </svg>
                        จัดการภาษา
                    </span>
                </button>

                <button @click="activeTab = 'display'"
                        :class="{ 'border-indigo-500 text-indigo-600': activeTab === 'display', 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300': activeTab !== 'display' }"
                        class="px-6 py-4 text-sm font-medium border-b-2 transition-colors duration-200">
                    <span class="flex items-center">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zm0 0h12a2 2 0 002-2v-4a2 2 0 00-2-2h-2.343M11 7.343l1.657-1.657a2 2 0 012.828 0l2.829 2.829a2 2 0 010 2.828l-8.486 8.485M7 17h.01"></path>
                        </svg>
                        รูปแบบการแสดงผล
                    </span>
                </button>
            </nav>
        </div>

        <!-- Tab Content -->
        <div class="p-6">
            <!-- Languages Tab -->
            <div x-show="activeTab === 'languages'" class="space-y-4">
                <div class="mb-4">
                    <p class="text-sm text-gray-600">
                        เลือกภาษาที่ต้องการให้แสดงในเว็บไซต์ คุณสามารถลากเพื่อเปลี่ยนลำดับการแสดงผลได้
                    </p>
                </div>

                <div class="space-y-3">
                    <template x-for="(lang, index) in languages" :key="lang.code">
                        <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg border border-gray-200 hover:border-indigo-300 transition-colors"
                             :class="{ 'bg-indigo-50 border-indigo-300': lang.is_enabled }">
                            <!-- Drag Handle -->
                            <div class="flex items-center space-x-4 flex-1">
                                <div class="cursor-move text-gray-400 hover:text-gray-600">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8h16M4 16h16"></path>
                                    </svg>
                                </div>

                                <!-- Flag & Language Info -->
                                <div class="flex items-center space-x-3 flex-1">
                                    <span class="text-3xl" x-text="lang.flag_emoji || '🌐'"></span>
                                    <div>
                                        <div class="font-medium text-gray-900">
                                            <span x-text="lang.native_name"></span>
                                            <span class="text-gray-500 text-sm ml-2">(<span x-text="lang.name"></span>)</span>
                                        </div>
                                        <div class="text-sm text-gray-500">
                                            Code: <span x-text="lang.code" class="font-mono"></span>
                                        </div>
                                    </div>
                                </div>

                                <!-- Default Badge -->
                                <div x-show="lang.is_default" class="px-3 py-1 bg-yellow-100 text-yellow-800 text-xs font-medium rounded-full">
                                    ภาษาเริ่มต้น
                                </div>

                                <!-- Enable Toggle -->
                                <label class="flex items-center cursor-pointer">
                                    <div class="relative">
                                        <input type="checkbox" x-model="lang.is_enabled" class="sr-only">
                                        <div class="block w-14 h-8 rounded-full transition-colors"
                                             :class="lang.is_enabled ? 'bg-indigo-600' : 'bg-gray-300'"></div>
                                        <div class="dot absolute left-1 top-1 bg-white w-6 h-6 rounded-full transition-transform"
                                             :class="{ 'transform translate-x-6': lang.is_enabled }"></div>
                                    </div>
                                    <div class="ml-3 text-sm font-medium text-gray-700">
                                        <span x-text="lang.is_enabled ? 'เปิดใช้งาน' : 'ปิดใช้งาน'"></span>
                                    </div>
                                </label>
                            </div>
                        </div>
                    </template>
                </div>
            </div>

            <!-- Display Settings Tab -->
            <div x-show="activeTab === 'display'" class="space-y-6">
                <!-- Style Selection -->
                <div class="bg-white border border-gray-200 rounded-lg p-6">
                    <label class="block text-sm font-medium text-gray-900 mb-4">รูปแบบการแสดงผล</label>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <!-- Dropdown Style -->
                        <label class="relative flex flex-col items-center p-6 border-2 rounded-lg cursor-pointer transition-all"
                               :class="settings.style === 'dropdown' ? 'border-indigo-600 bg-indigo-50' : 'border-gray-200 hover:border-indigo-300'">
                            <input type="radio" name="style" value="dropdown" x-model="settings.style" class="sr-only">
                            <svg class="w-12 h-12 mb-3 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                            </svg>
                            <span class="text-sm font-medium text-gray-900">Dropdown</span>
                            <span class="text-xs text-gray-500 text-center mt-1">แสดงเป็นเมนูแบบดรอปดาวน์</span>
                            <div x-show="settings.style === 'dropdown'" class="absolute top-2 right-2">
                                <svg class="w-6 h-6 text-indigo-600" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                                </svg>
                            </div>
                        </label>

                        <!-- Flags Style -->
                        <label class="relative flex flex-col items-center p-6 border-2 rounded-lg cursor-pointer transition-all"
                               :class="settings.style === 'flags' ? 'border-indigo-600 bg-indigo-50' : 'border-gray-200 hover:border-indigo-300'">
                            <input type="radio" name="style" value="flags" x-model="settings.style" class="sr-only">
                            <div class="text-2xl mb-3">🇹🇭 🇬🇧 🇨🇳</div>
                            <span class="text-sm font-medium text-gray-900">Flags</span>
                            <span class="text-xs text-gray-500 text-center mt-1">แสดงธงเรียงแนวนอน</span>
                            <div x-show="settings.style === 'flags'" class="absolute top-2 right-2">
                                <svg class="w-6 h-6 text-indigo-600" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                                </svg>
                            </div>
                        </label>

                        <!-- Compact Style -->
                        <label class="relative flex flex-col items-center p-6 border-2 rounded-lg cursor-pointer transition-all"
                               :class="settings.style === 'compact' ? 'border-indigo-600 bg-indigo-50' : 'border-gray-200 hover:border-indigo-300'">
                            <input type="radio" name="style" value="compact" x-model="settings.style" class="sr-only">
                            <svg class="w-12 h-12 mb-3 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"></path>
                            </svg>
                            <span class="text-sm font-medium text-gray-900">Compact</span>
                            <span class="text-xs text-gray-500 text-center mt-1">แสดงแบบกะทัดรัด</span>
                            <div x-show="settings.style === 'compact'" class="absolute top-2 right-2">
                                <svg class="w-6 h-6 text-indigo-600" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                                </svg>
                            </div>
                        </label>
                    </div>
                </div>

                <!-- Display Options -->
                <div class="bg-white border border-gray-200 rounded-lg p-6 space-y-6">
                    <h3 class="text-sm font-medium text-gray-900 mb-4">ตัวเลือกการแสดงผล</h3>

                    <!-- Show Flags Toggle -->
                    <div class="flex items-center justify-between">
                        <div>
                            <label class="text-sm font-medium text-gray-900">แสดงธง</label>
                            <p class="text-xs text-gray-500 mt-1">แสดงธงประเทศ ข้างชื่อภาษา</p>
                        </div>
                        <label class="flex items-center cursor-pointer">
                            <div class="relative">
                                <input type="checkbox" x-model="settings.show_flags" class="sr-only">
                                <div class="block w-14 h-8 rounded-full transition-colors"
                                     :class="settings.show_flags ? 'bg-indigo-600' : 'bg-gray-300'"></div>
                                <div class="dot absolute left-1 top-1 bg-white w-6 h-6 rounded-full transition-transform"
                                     :class="{ 'transform translate-x-6': settings.show_flags }"></div>
                            </div>
                        </label>
                    </div>

                    <!-- Flag Size Slider -->
                    <div x-show="settings.show_flags">
                        <label class="block text-sm font-medium text-gray-900 mb-2">
                            ขนาดธง: <span x-text="settings.flag_size"></span>px
                        </label>
                        <input type="range" x-model.number="settings.flag_size" min="16" max="64" step="4"
                               class="w-full h-2 bg-gray-200 rounded-lg appearance-none cursor-pointer">
                        <div class="flex justify-between text-xs text-gray-500 mt-1">
                            <span>16px</span>
                            <span>64px</span>
                        </div>
                        <!-- Preview -->
                        <div class="mt-3 p-3 bg-gray-50 rounded flex items-center space-x-2">
                            <span class="text-sm text-gray-600">ตัวอย่าง:</span>
                            <span :style="'font-size: ' + settings.flag_size + 'px'">🇹🇭</span>
                            <span :style="'font-size: ' + settings.flag_size + 'px'">🇬🇧</span>
                            <span :style="'font-size: ' + settings.flag_size + 'px'">🇨🇳</span>
                        </div>
                    </div>

                    <!-- Show Name Toggle -->
                    <div class="flex items-center justify-between">
                        <div>
                            <label class="text-sm font-medium text-gray-900">แสดงชื่อภาษา</label>
                            <p class="text-xs text-gray-500 mt-1">แสดงชื่อภาษาข้างธง</p>
                        </div>
                        <label class="flex items-center cursor-pointer">
                            <div class="relative">
                                <input type="checkbox" x-model="settings.show_name" class="sr-only">
                                <div class="block w-14 h-8 rounded-full transition-colors"
                                     :class="settings.show_name ? 'bg-indigo-600' : 'bg-gray-300'"></div>
                                <div class="dot absolute left-1 top-1 bg-white w-6 h-6 rounded-full transition-transform"
                                     :class="{ 'transform translate-x-6': settings.show_name }"></div>
                            </div>
                        </label>
                    </div>
                </div>

                <!-- Live Preview -->
                <div class="bg-gradient-to-r from-indigo-50 to-purple-50 border border-indigo-200 rounded-lg p-6">
                    <h3 class="text-sm font-medium text-gray-900 mb-4">ตัวอย่างการแสดงผล</h3>
                    <div class="bg-white rounded-lg p-6 shadow-sm">
                        <div class="flex justify-center">
                            <div x-html="getPreviewHTML()"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
function languageSettings() {
    return {
        activeTab: 'languages',
        saving: false,
        languages: @json($languages),
        settings: @json($switcherSettings),

        async saveAll() {
            this.saving = true;

            try {
                const formData = {
                    languages: this.languages.map(lang => ({
                        code: lang.code,
                        is_enabled: lang.is_enabled,
                        sort_order: lang.sort_order
                    })),
                    switcher_style: this.settings.style,
                    show_flags: this.settings.show_flags,
                    flag_size: this.settings.flag_size,
                    show_name: this.settings.show_name,
                    position: this.settings.position
                };

                const response = await fetch('{{ route('admin.settings.languages.update') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content
                    },
                    body: JSON.stringify(formData)
                });

                if (response.ok) {
                    window.location.href = '{{ route('admin.settings.languages') }}?success=1';
                } else {
                    alert('เกิดข้อผิดพลาด กรุณาลองใหม่อีกครั้ง');
                }
            } catch (error) {
                console.error('Error:', error);
                alert('เกิดข้อผิดพลาด: ' + error.message);
            } finally {
                this.saving = false;
            }
        },

        getPreviewHTML() {
            const enabledLangs = this.languages.filter(l => l.is_enabled);

            if (this.settings.style === 'dropdown') {
                return this.getDropdownPreview(enabledLangs);
            } else if (this.settings.style === 'flags') {
                return this.getFlagsPreview(enabledLangs);
            } else {
                return this.getCompactPreview(enabledLangs);
            }
        },

        getDropdownPreview(langs) {
            let html = '<div class="relative inline-block">';
            html += '<button class="px-4 py-2 border border-gray-300 rounded-lg flex items-center space-x-2">';

            if (this.settings.show_flags) {
                html += `<span style="font-size: ${this.settings.flag_size}px">${langs[0]?.flag_emoji || '🌐'}</span>`;
            }

            if (this.settings.show_name) {
                html += `<span>${langs[0]?.native_name || 'Language'}</span>`;
            }

            html += '<svg class="w-4 h-4 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>';
            html += '</button></div>';

            return html;
        },

        getFlagsPreview(langs) {
            let html = '<div class="flex items-center space-x-3">';

            langs.slice(0, 5).forEach(lang => {
                html += '<button class="flex items-center space-x-2 px-3 py-2 rounded-lg hover:bg-gray-100 transition-colors">';

                if (this.settings.show_flags) {
                    html += `<span style="font-size: ${this.settings.flag_size}px">${lang.flag_emoji || '🌐'}</span>`;
                }

                if (this.settings.show_name) {
                    html += `<span class="text-sm">${lang.native_name}</span>`;
                }

                html += '</button>';
            });

            html += '</div>';
            return html;
        },

        getCompactPreview(langs) {
            let html = '<div class="flex items-center space-x-2">';

            langs.slice(0, 4).forEach(lang => {
                if (this.settings.show_flags) {
                    html += `<span style="font-size: ${this.settings.flag_size}px" class="cursor-pointer hover:scale-110 transition-transform">${lang.flag_emoji || '🌐'}</span>`;
                } else {
                    html += `<span class="text-sm px-2 py-1 bg-gray-100 rounded cursor-pointer hover:bg-gray-200">${lang.code.toUpperCase()}</span>`;
                }
            });

            html += '</div>';
            return html;
        }
    }
}
</script>
@endpush
@endsection
