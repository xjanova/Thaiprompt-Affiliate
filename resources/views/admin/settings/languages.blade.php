@extends('layouts.admin')

@section('title', 'จัดการภาษา')

@section('content')
<div class="min-h-screen bg-gradient-to-br from-gray-50 via-blue-50 to-indigo-50 dark:from-gray-900 dark:via-gray-800 dark:to-gray-900 py-8">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

        <!-- Alpine.js Language Switcher -->
        <div x-data="{ open: false, currentLang: '🇹🇭 ไทย' }" class="flex justify-end mb-6">
            <div class="relative">
                <button @click="open = !open" class="flex items-center space-x-2 px-4 py-2 bg-white dark:bg-gray-800 rounded-xl shadow-lg hover:shadow-xl transition-all border border-gray-200 dark:border-gray-700">
                    <span class="text-sm font-medium text-gray-700 dark:text-gray-300" x-text="currentLang"></span>
                    <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                </button>
                <div x-show="open" @click.away="open = false" x-transition class="absolute right-0 mt-2 w-48 bg-white dark:bg-gray-800 rounded-xl shadow-2xl border border-gray-200 dark:border-gray-700 py-2 z-50">
                    <a href="#" @click.prevent="currentLang = '🇹🇭 ไทย'; open = false" class="block px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-blue-50 dark:hover:bg-gray-700">🇹🇭 ไทย</a>
                    <a href="#" @click.prevent="currentLang = '🇬🇧 English'; open = false" class="block px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-blue-50 dark:hover:bg-gray-700">🇬🇧 English</a>
                    <a href="#" @click.prevent="currentLang = '🇨🇳 中文'; open = false" class="block px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-blue-50 dark:hover:bg-gray-700">🇨🇳 中文</a>
                    <a href="#" @click.prevent="currentLang = '🇯🇵 日本語'; open = false" class="block px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-blue-50 dark:hover:bg-gray-700">🇯🇵 日本語</a>
                </div>
            </div>
        </div>

        <div x-data="languageSettings()" class="space-y-6">
            <!-- Header -->
            <div class="relative overflow-hidden bg-gradient-to-br from-blue-600 via-indigo-600 to-purple-600 rounded-3xl shadow-2xl p-8">
                <div class="absolute inset-0 bg-black/10"></div>
                <div class="relative flex items-center justify-between">
                    <div class="flex items-center">
                        <div class="w-16 h-16 bg-white/20 backdrop-blur-sm rounded-2xl flex items-center justify-center mr-4">
                            <svg class="w-9 h-9 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5h12M9 3v2m1.048 9.5A18.022 18.022 0 016.412 9m6.088 9h7M11 21l5-10 5 10M12.751 5C11.783 10.77 8.07 15.61 3 18.129"></path>
                            </svg>
                        </div>
                        <div>
                            <h1 class="text-4xl font-bold text-white mb-2" data-translate>จัดการภาษา</h1>
                            <p class="text-blue-100" data-translate>ตั้งค่าภาษาที่จะแสดงในเว็บไซต์ และปรับแต่งรูปแบบการแสดงผล</p>
                        </div>
                    </div>
                    <button @click="saveAll()" :disabled="saving"
                            class="relative overflow-hidden group px-8 py-3 bg-white/20 backdrop-blur-sm hover:bg-white/30 rounded-xl transition-all text-white font-semibold flex items-center disabled:opacity-50 disabled:cursor-not-allowed shadow-lg">
                        <div class="absolute inset-0 bg-white/10 opacity-0 group-hover:opacity-100 transition-opacity"></div>
                        <div class="relative flex items-center">
                            <span x-show="!saving" class="flex items-center">
                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"/>
                                </svg>
                                <span data-translate>บันทึกการตั้งค่า</span>
                            </span>
                            <span x-show="saving" class="flex items-center">
                                <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                <span data-translate>กำลังบันทึก...</span>
                            </span>
                        </div>
                    </button>
                </div>
            </div>

            <!-- Tabs -->
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-2xl border border-gray-200 dark:border-gray-700 overflow-hidden">
                <div class="border-b border-gray-200 dark:border-gray-700 bg-gradient-to-r from-blue-500/10 via-indigo-500/10 to-purple-500/10 dark:from-blue-500/5 dark:via-indigo-500/5 dark:to-purple-500/5">
                    <nav class="flex -mb-px">
                        <button @click="activeTab = 'languages'"
                                :class="{ 'border-indigo-500 text-indigo-600 dark:text-indigo-400 bg-white dark:bg-gray-800': activeTab === 'languages', 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 hover:border-gray-300 dark:hover:border-gray-600': activeTab !== 'languages' }"
                                class="px-8 py-4 text-sm font-semibold border-b-2 transition-all duration-200">
                            <span class="flex items-center">
                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5h12M9 3v2m1.048 9.5A18.022 18.022 0 016.412 9m6.088 9h7M11 21l5-10 5 10M12.751 5C11.783 10.77 8.07 15.61 3 18.129"></path>
                                </svg>
                                <span data-translate>จัดการภาษา</span>
                            </span>
                        </button>

                        <button @click="activeTab = 'display'"
                                :class="{ 'border-indigo-500 text-indigo-600 dark:text-indigo-400 bg-white dark:bg-gray-800': activeTab === 'display', 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 hover:border-gray-300 dark:hover:border-gray-600': activeTab !== 'display' }"
                                class="px-8 py-4 text-sm font-semibold border-b-2 transition-all duration-200">
                            <span class="flex items-center">
                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zm0 0h12a2 2 0 002-2v-4a2 2 0 00-2-2h-2.343M11 7.343l1.657-1.657a2 2 0 012.828 0l2.829 2.829a2 2 0 010 2.828l-8.486 8.485M7 17h.01"></path>
                                </svg>
                                <span data-translate>รูปแบบการแสดงผล</span>
                            </span>
                        </button>
                    </nav>
                </div>

                <!-- Tab Content -->
                <div class="p-8">
                    <!-- Languages Tab -->
                    <div x-show="activeTab === 'languages'" class="space-y-6">
                        <div class="bg-blue-50 dark:bg-blue-900/20 border-l-4 border-blue-500 dark:border-blue-400 rounded-lg p-4">
                            <p class="text-sm text-gray-700 dark:text-gray-300" data-translate>
                                เลือกภาษาที่ต้องการให้แสดงในเว็บไซต์ คุณสามารถลากเพื่อเปลี่ยนลำดับการแสดงผลได้
                            </p>
                        </div>

                        <div class="space-y-3">
                            <template x-for="(lang, index) in languages" :key="lang.code">
                                <div class="flex items-center justify-between p-6 bg-gray-50 dark:bg-gray-700/50 rounded-xl border-2 border-gray-200 dark:border-gray-600 hover:border-indigo-400 dark:hover:border-indigo-500 transition-all"
                                     :class="{ 'bg-gradient-to-r from-indigo-50 to-purple-50 dark:from-indigo-900/30 dark:to-purple-900/30 border-indigo-400 dark:border-indigo-500': lang.is_enabled }">
                                    <!-- Drag Handle -->
                                    <div class="flex items-center space-x-4 flex-1">
                                        <div class="cursor-move text-gray-400 dark:text-gray-500 hover:text-gray-600 dark:hover:text-gray-300 transition-colors">
                                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8h16M4 16h16"></path>
                                            </svg>
                                        </div>

                                        <!-- Flag & Language Info -->
                                        <div class="flex items-center space-x-4 flex-1">
                                            <div class="w-14 h-14 bg-gradient-to-br from-blue-500 to-indigo-600 rounded-xl flex items-center justify-center shadow-lg">
                                                <span class="text-3xl" x-text="lang.flag_emoji || '🌐'"></span>
                                            </div>
                                            <div>
                                                <div class="font-semibold text-gray-900 dark:text-gray-100 text-lg">
                                                    <span x-text="lang.native_name"></span>
                                                    <span class="text-gray-500 dark:text-gray-400 text-sm ml-2">(<span x-text="lang.name"></span>)</span>
                                                </div>
                                                <div class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                                                    Code: <span x-text="lang.code" class="font-mono bg-gray-200 dark:bg-gray-600 px-2 py-1 rounded"></span>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Default Badge -->
                                        <div x-show="lang.is_default" class="px-4 py-2 bg-gradient-to-r from-yellow-400 to-orange-500 text-white text-xs font-bold rounded-lg shadow-md">
                                            <span data-translate>ภาษาเริ่มต้น</span>
                                        </div>

                                        <!-- Enable Toggle -->
                                        <label class="flex items-center cursor-pointer">
                                            <div class="relative">
                                                <input type="checkbox" x-model="lang.is_enabled" class="sr-only">
                                                <div class="block w-16 h-9 rounded-full shadow-inner transition-colors"
                                                     :class="lang.is_enabled ? 'bg-gradient-to-r from-indigo-500 to-purple-600' : 'bg-gray-300 dark:bg-gray-600'"></div>
                                                <div class="dot absolute left-1 top-1 bg-white w-7 h-7 rounded-full transition-transform shadow-md"
                                                     :class="{ 'transform translate-x-7': lang.is_enabled }"></div>
                                            </div>
                                            <div class="ml-3 text-sm font-semibold text-gray-700 dark:text-gray-300">
                                                <span x-text="lang.is_enabled ? 'เปิดใช้งาน' : 'ปิดใช้งาน'" data-translate></span>
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
                        <div class="bg-white dark:bg-gray-800 border-2 border-gray-200 dark:border-gray-700 rounded-2xl p-8 shadow-lg">
                            <label class="block text-lg font-bold text-gray-900 dark:text-gray-100 mb-6" data-translate>รูปแบบการแสดงผล</label>
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                                <!-- Dropdown Style -->
                                <label class="relative flex flex-col items-center p-8 border-2 rounded-2xl cursor-pointer transition-all hover:shadow-xl"
                                       :class="settings.style === 'dropdown' ? 'border-indigo-600 dark:border-indigo-500 bg-gradient-to-br from-indigo-50 to-purple-50 dark:from-indigo-900/30 dark:to-purple-900/30 shadow-lg' : 'border-gray-200 dark:border-gray-700 hover:border-indigo-300 dark:hover:border-indigo-600 bg-white dark:bg-gray-800'">
                                    <input type="radio" name="style" value="dropdown" x-model="settings.style" class="sr-only">
                                    <div class="w-16 h-16 mb-4 bg-gradient-to-br from-indigo-500 to-purple-600 rounded-xl flex items-center justify-center shadow-lg">
                                        <svg class="w-9 h-9 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                        </svg>
                                    </div>
                                    <span class="text-base font-bold text-gray-900 dark:text-gray-100" data-translate>Dropdown</span>
                                    <span class="text-xs text-gray-500 dark:text-gray-400 text-center mt-2" data-translate>แสดงเป็นเมนูแบบดรอปดาวน์</span>
                                    <div x-show="settings.style === 'dropdown'" class="absolute top-3 right-3">
                                        <div class="w-8 h-8 bg-gradient-to-br from-green-400 to-emerald-600 rounded-full flex items-center justify-center shadow-lg">
                                            <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                                            </svg>
                                        </div>
                                    </div>
                                </label>

                                <!-- Flags Style -->
                                <label class="relative flex flex-col items-center p-8 border-2 rounded-2xl cursor-pointer transition-all hover:shadow-xl"
                                       :class="settings.style === 'flags' ? 'border-indigo-600 dark:border-indigo-500 bg-gradient-to-br from-indigo-50 to-purple-50 dark:from-indigo-900/30 dark:to-purple-900/30 shadow-lg' : 'border-gray-200 dark:border-gray-700 hover:border-indigo-300 dark:hover:border-indigo-600 bg-white dark:bg-gray-800'">
                                    <input type="radio" name="style" value="flags" x-model="settings.style" class="sr-only">
                                    <div class="text-4xl mb-4">🇹🇭 🇬🇧 🇨🇳</div>
                                    <span class="text-base font-bold text-gray-900 dark:text-gray-100" data-translate>Flags</span>
                                    <span class="text-xs text-gray-500 dark:text-gray-400 text-center mt-2" data-translate>แสดงธงเรียงแนวนอน</span>
                                    <div x-show="settings.style === 'flags'" class="absolute top-3 right-3">
                                        <div class="w-8 h-8 bg-gradient-to-br from-green-400 to-emerald-600 rounded-full flex items-center justify-center shadow-lg">
                                            <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                                            </svg>
                                        </div>
                                    </div>
                                </label>

                                <!-- Compact Style -->
                                <label class="relative flex flex-col items-center p-8 border-2 rounded-2xl cursor-pointer transition-all hover:shadow-xl"
                                       :class="settings.style === 'compact' ? 'border-indigo-600 dark:border-indigo-500 bg-gradient-to-br from-indigo-50 to-purple-50 dark:from-indigo-900/30 dark:to-purple-900/30 shadow-lg' : 'border-gray-200 dark:border-gray-700 hover:border-indigo-300 dark:hover:border-indigo-600 bg-white dark:bg-gray-800'">
                                    <input type="radio" name="style" value="compact" x-model="settings.style" class="sr-only">
                                    <div class="w-16 h-16 mb-4 bg-gradient-to-br from-blue-500 to-indigo-600 rounded-xl flex items-center justify-center shadow-lg">
                                        <svg class="w-9 h-9 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"></path>
                                        </svg>
                                    </div>
                                    <span class="text-base font-bold text-gray-900 dark:text-gray-100" data-translate>Compact</span>
                                    <span class="text-xs text-gray-500 dark:text-gray-400 text-center mt-2" data-translate>แสดงแบบกะทัดรัด</span>
                                    <div x-show="settings.style === 'compact'" class="absolute top-3 right-3">
                                        <div class="w-8 h-8 bg-gradient-to-br from-green-400 to-emerald-600 rounded-full flex items-center justify-center shadow-lg">
                                            <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                                            </svg>
                                        </div>
                                    </div>
                                </label>
                            </div>
                        </div>

                        <!-- Display Options -->
                        <div class="bg-white dark:bg-gray-800 border-2 border-gray-200 dark:border-gray-700 rounded-2xl p-8 space-y-8 shadow-lg">
                            <h3 class="text-lg font-bold text-gray-900 dark:text-gray-100 mb-6" data-translate>ตัวเลือกการแสดงผล</h3>

                            <!-- Show Flags Toggle -->
                            <div class="flex items-center justify-between p-4 bg-gray-50 dark:bg-gray-700/50 rounded-xl">
                                <div>
                                    <label class="text-base font-semibold text-gray-900 dark:text-gray-100" data-translate>แสดงธง</label>
                                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1" data-translate>แสดงธงประเทศข้างชื่อภาษา</p>
                                </div>
                                <label class="flex items-center cursor-pointer">
                                    <div class="relative">
                                        <input type="checkbox" x-model="settings.show_flags" class="sr-only">
                                        <div class="block w-16 h-9 rounded-full shadow-inner transition-colors"
                                             :class="settings.show_flags ? 'bg-gradient-to-r from-indigo-500 to-purple-600' : 'bg-gray-300 dark:bg-gray-600'"></div>
                                        <div class="dot absolute left-1 top-1 bg-white w-7 h-7 rounded-full transition-transform shadow-md"
                                             :class="{ 'transform translate-x-7': settings.show_flags }"></div>
                                    </div>
                                </label>
                            </div>

                            <!-- Flag Size Slider -->
                            <div x-show="settings.show_flags" class="p-4 bg-gradient-to-r from-blue-50 to-indigo-50 dark:from-blue-900/20 dark:to-indigo-900/20 rounded-xl">
                                <label class="block text-base font-semibold text-gray-900 dark:text-gray-100 mb-4">
                                    <span data-translate>ขนาดธง:</span> <span x-text="settings.flag_size"></span>px
                                </label>
                                <input type="range" x-model.number="settings.flag_size" min="16" max="64" step="4"
                                       class="w-full h-3 bg-gray-200 dark:bg-gray-600 rounded-lg appearance-none cursor-pointer accent-indigo-600">
                                <div class="flex justify-between text-xs text-gray-500 dark:text-gray-400 mt-2">
                                    <span>16px</span>
                                    <span>64px</span>
                                </div>
                                <!-- Preview -->
                                <div class="mt-4 p-4 bg-white dark:bg-gray-800 rounded-lg shadow flex items-center space-x-3">
                                    <span class="text-sm font-medium text-gray-600 dark:text-gray-400" data-translate>ตัวอย่าง:</span>
                                    <span :style="'font-size: ' + settings.flag_size + 'px'">🇹🇭</span>
                                    <span :style="'font-size: ' + settings.flag_size + 'px'">🇬🇧</span>
                                    <span :style="'font-size: ' + settings.flag_size + 'px'">🇨🇳</span>
                                </div>
                            </div>

                            <!-- Show Name Toggle -->
                            <div class="flex items-center justify-between p-4 bg-gray-50 dark:bg-gray-700/50 rounded-xl">
                                <div>
                                    <label class="text-base font-semibold text-gray-900 dark:text-gray-100" data-translate>แสดงชื่อภาษา</label>
                                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1" data-translate>แสดงชื่อภาษาข้างธง</p>
                                </div>
                                <label class="flex items-center cursor-pointer">
                                    <div class="relative">
                                        <input type="checkbox" x-model="settings.show_name" class="sr-only">
                                        <div class="block w-16 h-9 rounded-full shadow-inner transition-colors"
                                             :class="settings.show_name ? 'bg-gradient-to-r from-indigo-500 to-purple-600' : 'bg-gray-300 dark:bg-gray-600'"></div>
                                        <div class="dot absolute left-1 top-1 bg-white w-7 h-7 rounded-full transition-transform shadow-md"
                                             :class="{ 'transform translate-x-7': settings.show_name }"></div>
                                    </div>
                                </label>
                            </div>
                        </div>

                        <!-- Live Preview -->
                        <div class="relative overflow-hidden bg-gradient-to-br from-indigo-600 via-purple-600 to-pink-600 rounded-2xl shadow-2xl p-8">
                            <div class="absolute inset-0 bg-black/10"></div>
                            <div class="relative">
                                <h3 class="text-lg font-bold text-white mb-4" data-translate>ตัวอย่างการแสดงผล</h3>
                                <div class="bg-white dark:bg-gray-800 rounded-xl p-8 shadow-xl">
                                    <div class="flex justify-center">
                                        <div x-html="getPreviewHTML()"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>

<script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>

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
            html += '<button class="px-4 py-2 border-2 border-gray-300 dark:border-gray-600 rounded-lg flex items-center space-x-2 hover:border-indigo-400 transition-colors">';

            if (this.settings.show_flags) {
                html += `<span style="font-size: ${this.settings.flag_size}px">${langs[0]?.flag_emoji || '🌐'}</span>`;
            }

            if (this.settings.show_name) {
                html += `<span class="text-gray-900 dark:text-gray-100">${langs[0]?.native_name || 'Language'}</span>`;
            }

            html += '<svg class="w-4 h-4 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>';
            html += '</button></div>';

            return html;
        },

        getFlagsPreview(langs) {
            let html = '<div class="flex items-center space-x-3">';

            langs.slice(0, 5).forEach(lang => {
                html += '<button class="flex items-center space-x-2 px-3 py-2 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors">';

                if (this.settings.show_flags) {
                    html += `<span style="font-size: ${this.settings.flag_size}px">${lang.flag_emoji || '🌐'}</span>`;
                }

                if (this.settings.show_name) {
                    html += `<span class="text-sm text-gray-900 dark:text-gray-100">${lang.native_name}</span>`;
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
                    html += `<span class="text-sm px-2 py-1 bg-gray-100 dark:bg-gray-700 rounded cursor-pointer hover:bg-gray-200 dark:hover:bg-gray-600 text-gray-900 dark:text-gray-100">${lang.code.toUpperCase()}</span>`;
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
