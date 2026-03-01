@extends('layouts.admin-v3')

@section('title', 'ตั้งค่าระบบ - ตลาดสดไทยพร้อม')

@section('content')
<div class="space-y-6" x-data="{
    activeTab: 'line',
    saving: false,
    saved: false,
    showSaved() {
        this.saved = true;
        setTimeout(() => this.saved = false, 3000);
    }
}">
    {{-- ส่วนหัว --}}
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <div>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">ตั้งค่าระบบ - ตลาดสดไทยพร้อม</h1>
            <p class="text-gray-600 dark:text-gray-400 mt-1">กำหนดค่าต่างๆ สำหรับระบบตลาดสด</p>
        </div>
        <a href="{{ route('admin.fresh-market.dashboard') }}"
           class="inline-flex items-center px-4 py-2 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-xl hover:bg-gray-300 dark:hover:bg-gray-600 transition">
            <i class="fas fa-arrow-left mr-2"></i> กลับแดชบอร์ด
        </a>
    </div>

    {{-- แจ้งเตือนบันทึกสำเร็จ --}}
    @if(session('success'))
        <div class="bg-green-100 dark:bg-green-900/30 border border-green-400 dark:border-green-600 text-green-700 dark:text-green-400 px-4 py-3 rounded-xl" role="alert">
            <span class="block sm:inline">{{ session('success') }}</span>
        </div>
    @endif

    @if(session('error'))
        <div class="bg-red-100 dark:bg-red-900/30 border border-red-400 dark:border-red-600 text-red-700 dark:text-red-400 px-4 py-3 rounded-xl" role="alert">
            <span class="block sm:inline">{{ session('error') }}</span>
        </div>
    @endif

    {{-- แจ้งเตือนบันทึกสำเร็จ (Alpine) --}}
    <div x-show="saved" x-transition class="bg-green-100 dark:bg-green-900/30 border border-green-400 dark:border-green-600 text-green-700 dark:text-green-400 px-4 py-3 rounded-xl">
        บันทึกการตั้งค่าสำเร็จ
    </div>

    {{-- แท็บเมนู --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg">
        <div class="border-b border-gray-200 dark:border-gray-700">
            <nav class="flex overflow-x-auto -mb-px">
                <button @click="activeTab = 'line'"
                        :class="activeTab === 'line' ? 'border-green-500 text-green-600 dark:text-green-400' : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300'"
                        class="whitespace-nowrap py-4 px-6 border-b-2 font-medium text-sm transition">
                    <i class="fab fa-line mr-2"></i>LINE OA
                </button>
                <button @click="activeTab = 'ai'"
                        :class="activeTab === 'ai' ? 'border-green-500 text-green-600 dark:text-green-400' : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300'"
                        class="whitespace-nowrap py-4 px-6 border-b-2 font-medium text-sm transition">
                    <i class="fas fa-robot mr-2"></i>AI
                </button>
                <button @click="activeTab = 'fees'"
                        :class="activeTab === 'fees' ? 'border-green-500 text-green-600 dark:text-green-400' : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300'"
                        class="whitespace-nowrap py-4 px-6 border-b-2 font-medium text-sm transition">
                    <i class="fas fa-percentage mr-2"></i>ค่าธรรมเนียม
                </button>
                <button @click="activeTab = 'search'"
                        :class="activeTab === 'search' ? 'border-green-500 text-green-600 dark:text-green-400' : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300'"
                        class="whitespace-nowrap py-4 px-6 border-b-2 font-medium text-sm transition">
                    <i class="fas fa-search-location mr-2"></i>การค้นหา
                </button>
                <button @click="activeTab = 'toggles'"
                        :class="activeTab === 'toggles' ? 'border-green-500 text-green-600 dark:text-green-400' : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300'"
                        class="whitespace-nowrap py-4 px-6 border-b-2 font-medium text-sm transition">
                    <i class="fas fa-toggle-on mr-2"></i>เปิด/ปิดฟีเจอร์
                </button>
                <button @click="activeTab = 'branding'"
                        :class="activeTab === 'branding' ? 'border-green-500 text-green-600 dark:text-green-400' : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300'"
                        class="whitespace-nowrap py-4 px-6 border-b-2 font-medium text-sm transition">
                    <i class="fas fa-palette mr-2"></i>แบรนด์
                </button>
            </nav>
        </div>

        <form method="POST" action="{{ route('admin.fresh-market.settings.update') }}" class="p-6">
            @csrf
            @method('PUT')

            {{-- แท็บ LINE OA --}}
            <div x-show="activeTab === 'line'" x-transition>
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">ตั้งค่า LINE Official Account</h3>
                <p class="text-sm text-gray-500 dark:text-gray-400 mb-6">กำหนดค่าการเชื่อมต่อ LINE OA สำหรับระบบตลาดสด</p>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Channel ID</label>
                        <input type="text" name="line_channel_id"
                               value="{{ $settings->line_channel_id ?? '' }}"
                               class="w-full rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-green-500 focus:ring-green-500"
                               placeholder="กรอก Channel ID">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Channel Secret</label>
                        <input type="password" name="line_channel_secret"
                               value="{{ $settings->line_channel_secret ?? '' }}"
                               class="w-full rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-green-500 focus:ring-green-500"
                               placeholder="กรอก Channel Secret">
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Channel Access Token</label>
                        <input type="password" name="line_channel_access_token"
                               value="{{ $settings->line_channel_access_token ?? '' }}"
                               class="w-full rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-green-500 focus:ring-green-500"
                               placeholder="กรอก Channel Access Token">
                    </div>
                </div>

                <div class="mt-4 p-4 bg-blue-50 dark:bg-blue-900/20 rounded-xl">
                    <p class="text-sm text-blue-700 dark:text-blue-400">
                        <i class="fas fa-info-circle mr-1"></i>
                        สามารถทดสอบการส่งข้อความ LINE ได้ที่หน้า
                        <a href="{{ route('admin.fresh-market.test-line') }}" class="underline font-medium">ทดสอบ LINE</a>
                    </p>
                </div>
            </div>

            {{-- แท็บ AI --}}
            <div x-show="activeTab === 'ai'" x-transition>
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">ตั้งค่า AI</h3>
                <p class="text-sm text-gray-500 dark:text-gray-400 mb-6">กำหนดค่าผู้ให้บริการ AI สำหรับแชทบอทตลาดสด</p>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">ผู้ให้บริการ AI</label>
                        <select name="ai_provider"
                                class="w-full rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-green-500 focus:ring-green-500">
                            <option value="groq" {{ ($settings->ai_provider ?? 'groq') === 'groq' ? 'selected' : '' }}>Groq (แนะนำ)</option>
                            <option value="openrouter" {{ ($settings->ai_provider ?? '') === 'openrouter' ? 'selected' : '' }}>OpenRouter</option>
                            <option value="openai" {{ ($settings->ai_provider ?? '') === 'openai' ? 'selected' : '' }}>OpenAI</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">โมเดล AI</label>
                        <input type="text" name="ai_model"
                               value="{{ $settings->ai_model ?? 'llama-3.3-70b-versatile' }}"
                               class="w-full rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-green-500 focus:ring-green-500"
                               placeholder="เช่น llama-3.3-70b-versatile, gpt-4o-mini">
                    </div>
                    <div class="md:col-span-2">
                        <label class="flex items-center gap-2 mb-4">
                            <input type="hidden" name="use_global_ai_settings" value="0">
                            <input type="checkbox" name="use_global_ai_settings" value="1"
                                   {{ ($settings->use_global_ai_settings ?? true) ? 'checked' : '' }}
                                   class="rounded border-gray-300 dark:border-gray-600 text-green-600 focus:ring-green-500">
                            <span class="text-sm text-gray-700 dark:text-gray-300">ใช้ API Key Pool จากระบบหลัก (AiApiKeyPoolService)</span>
                        </label>
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">System Prompt (บทบาท "พี่ตลาด")</label>
                        <textarea name="ai_system_prompt" rows="8"
                                  class="w-full rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-green-500 focus:ring-green-500"
                                  placeholder="กำหนดบทบาทและคำสั่งสำหรับ AI แชทบอท...">{{ $settings->ai_system_prompt ?? '' }}</textarea>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">กำหนดบทบาทและพฤติกรรมของ AI ในการตอบคำถามลูกค้า</p>
                    </div>
                </div>
            </div>

            {{-- แท็บค่าธรรมเนียม --}}
            <div x-show="activeTab === 'fees'" x-transition>
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">ตั้งค่าค่าธรรมเนียม</h3>
                <p class="text-sm text-gray-500 dark:text-gray-400 mb-6">กำหนดอัตราค่าธรรมเนียมและรูปแบบการเรียกเก็บ</p>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">ค่าธรรมเนียมแพลตฟอร์ม (%)</label>
                        <input type="number" name="platform_fee_percentage" step="0.01" min="0" max="100"
                               value="{{ $settings->platform_fee_percentage ?? 5 }}"
                               class="w-full rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-green-500 focus:ring-green-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">ค่าสมาชิกรายเดือน (บาท)</label>
                        <input type="number" name="monthly_subscription_fee" step="0.01" min="0"
                               value="{{ $settings->monthly_subscription_fee ?? 0 }}"
                               class="w-full rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-green-500 focus:ring-green-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">จำนวนวันทดลองใช้ฟรี</label>
                        <input type="number" name="free_trial_days" min="0"
                               value="{{ $settings->free_trial_days ?? 30 }}"
                               class="w-full rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-green-500 focus:ring-green-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">รูปแบบการเก็บค่าธรรมเนียม</label>
                        <div class="mt-2 space-y-3">
                            <label class="flex items-center">
                                <input type="radio" name="fee_mode" value="percentage"
                                       {{ ($settings->fee_mode ?? 'both') === 'percentage' ? 'checked' : '' }}
                                       class="text-green-600 focus:ring-green-500 dark:bg-gray-700 dark:border-gray-600">
                                <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">เก็บเปอร์เซ็นต์ต่อคำสั่งซื้อ</span>
                            </label>
                            <label class="flex items-center">
                                <input type="radio" name="fee_mode" value="subscription"
                                       {{ ($settings->fee_mode ?? '') === 'subscription' ? 'checked' : '' }}
                                       class="text-green-600 focus:ring-green-500 dark:bg-gray-700 dark:border-gray-600">
                                <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">สมัครสมาชิกรายเดือน</span>
                            </label>
                            <label class="flex items-center">
                                <input type="radio" name="fee_mode" value="both"
                                       {{ ($settings->fee_mode ?? 'both') === 'both' ? 'checked' : '' }}
                                       class="text-green-600 focus:ring-green-500 dark:bg-gray-700 dark:border-gray-600">
                                <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">ผสม (สมาชิก + เปอร์เซ็นต์)</span>
                            </label>
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">จำนวนรายการขายฟรี (แพ็กเกจฟรี)</label>
                        <input type="number" name="max_listings_free" min="1"
                               value="{{ $settings->max_listings_free ?? 5 }}"
                               class="w-full rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-green-500 focus:ring-green-500">
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">จำนวนรายการสินค้าที่ผู้ขายฟรีสามารถลงได้</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">จำนวนรายการขาย (สมาชิก)</label>
                        <input type="number" name="max_listings_subscribed" min="0"
                               value="{{ $settings->max_listings_subscribed ?? 0 }}"
                               class="w-full rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-green-500 focus:ring-green-500">
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">0 = ไม่จำกัด</p>
                    </div>
                </div>
            </div>

            {{-- แท็บการค้นหา --}}
            <div x-show="activeTab === 'search'" x-transition>
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">ตั้งค่าการค้นหา</h3>
                <p class="text-sm text-gray-500 dark:text-gray-400 mb-6">กำหนดรัศมีการค้นหาร้านค้าและสินค้า</p>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">รัศมีค้นหาเริ่มต้น (กม.)</label>
                        <input type="number" name="default_search_radius_km" step="0.1" min="0.1"
                               value="{{ $settings->default_search_radius_km ?? 5 }}"
                               class="w-full rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-green-500 focus:ring-green-500">
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">รัศมีเริ่มต้นที่ใช้ค้นหาร้านค้าใกล้เคียง</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">รัศมีค้นหาสูงสุด (กม.)</label>
                        <input type="number" name="max_search_radius_km" step="0.1" min="1"
                               value="{{ $settings->max_search_radius_km ?? 50 }}"
                               class="w-full rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-green-500 focus:ring-green-500">
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">รัศมีสูงสุดที่ผู้ใช้สามารถกำหนดได้</p>
                    </div>
                </div>
            </div>

            {{-- แท็บเปิด/ปิดฟีเจอร์ --}}
            <div x-show="activeTab === 'toggles'" x-transition>
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">เปิด/ปิดฟีเจอร์</h3>
                <p class="text-sm text-gray-500 dark:text-gray-400 mb-6">เปิดหรือปิดฟีเจอร์ต่างๆ ของระบบตลาดสด</p>

                <div class="space-y-4">
                    {{-- Escrow --}}
                    <div class="flex items-center justify-between p-4 bg-gray-50 dark:bg-gray-700/50 rounded-xl">
                        <div>
                            <p class="text-sm font-medium text-gray-900 dark:text-white">ระบบ Escrow (พักเงิน)</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">พักเงินไว้จนกว่าผู้ซื้อจะยืนยันรับสินค้า</p>
                        </div>
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="hidden" name="escrow_enabled" value="0">
                            <input type="checkbox" name="escrow_enabled" value="1"
                                   {{ ($settings->escrow_enabled ?? false) ? 'checked' : '' }}
                                   class="sr-only peer">
                            <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-green-300 dark:peer-focus:ring-green-800 rounded-full peer dark:bg-gray-600 peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-500 peer-checked:bg-green-600"></div>
                        </label>
                    </div>

                    {{-- COD --}}
                    <div class="flex items-center justify-between p-4 bg-gray-50 dark:bg-gray-700/50 rounded-xl">
                        <div>
                            <p class="text-sm font-medium text-gray-900 dark:text-white">ชำระเงินปลายทาง (COD)</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">อนุญาตให้ผู้ซื้อชำระเงินตอนรับสินค้า</p>
                        </div>
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="hidden" name="cod_enabled" value="0">
                            <input type="checkbox" name="cod_enabled" value="1"
                                   {{ ($settings->cod_enabled ?? false) ? 'checked' : '' }}
                                   class="sr-only peer">
                            <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-green-300 dark:peer-focus:ring-green-800 rounded-full peer dark:bg-gray-600 peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-500 peer-checked:bg-green-600"></div>
                        </label>
                    </div>

                    {{-- Rider --}}
                    <div class="flex items-center justify-between p-4 bg-gray-50 dark:bg-gray-700/50 rounded-xl">
                        <div>
                            <p class="text-sm font-medium text-gray-900 dark:text-white">ระบบไรเดอร์</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">เปิดใช้งานระบบจัดส่งโดยไรเดอร์</p>
                        </div>
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="hidden" name="rider_enabled" value="0">
                            <input type="checkbox" name="rider_enabled" value="1"
                                   {{ ($settings->rider_enabled ?? false) ? 'checked' : '' }}
                                   class="sr-only peer">
                            <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-green-300 dark:peer-focus:ring-green-800 rounded-full peer dark:bg-gray-600 peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-500 peer-checked:bg-green-600"></div>
                        </label>
                    </div>

                    {{-- MLM Commission --}}
                    <div class="flex items-center justify-between p-4 bg-gray-50 dark:bg-gray-700/50 rounded-xl">
                        <div>
                            <p class="text-sm font-medium text-gray-900 dark:text-white">คอมมิชชั่น MLM</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">เปิดระบบคอมมิชชั่นสายงาน MLM</p>
                        </div>
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="hidden" name="mlm_commission_enabled" value="0">
                            <input type="checkbox" name="mlm_commission_enabled" value="1"
                                   {{ ($settings->mlm_commission_enabled ?? false) ? 'checked' : '' }}
                                   class="sr-only peer">
                            <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-green-300 dark:peer-focus:ring-green-800 rounded-full peer dark:bg-gray-600 peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-500 peer-checked:bg-green-600"></div>
                        </label>
                    </div>

                    {{-- Cashback --}}
                    <div class="flex items-center justify-between p-4 bg-gray-50 dark:bg-gray-700/50 rounded-xl">
                        <div>
                            <p class="text-sm font-medium text-gray-900 dark:text-white">แคชแบ็ก (Cashback)</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">เปิดระบบคืนเงินให้ผู้ซื้อ</p>
                        </div>
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="hidden" name="cashback_enabled" value="0">
                            <input type="checkbox" name="cashback_enabled" value="1"
                                   {{ ($settings->cashback_enabled ?? false) ? 'checked' : '' }}
                                   class="sr-only peer">
                            <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-green-300 dark:peer-focus:ring-green-800 rounded-full peer dark:bg-gray-600 peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-500 peer-checked:bg-green-600"></div>
                        </label>
                    </div>
                </div>
            </div>

            {{-- แท็บแบรนด์ --}}
            <div x-show="activeTab === 'branding'" x-transition>
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">ตั้งค่าแบรนด์</h3>
                <p class="text-sm text-gray-500 dark:text-gray-400 mb-6">กำหนดชื่อแบรนด์ สี และข้อความต้อนรับ</p>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">ชื่อแบรนด์</label>
                        <input type="text" name="brand_name"
                               value="{{ $settings->brand_name ?? 'ตลาดสดไทยพร้อม' }}"
                               class="w-full rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-green-500 focus:ring-green-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">สีหลัก LINE Flex Message</label>
                        <div class="flex items-center gap-3">
                            <input type="color" name="line_flex_primary_color"
                                   value="{{ $settings->line_flex_primary_color ?? '#06C755' }}"
                                   class="w-12 h-10 rounded-lg border-gray-300 dark:border-gray-600 cursor-pointer">
                            <input type="text" value="{{ $settings->line_flex_primary_color ?? '#06C755' }}"
                                   class="w-full rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-green-500 focus:ring-green-500"
                                   readonly>
                        </div>
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">ข้อความต้อนรับ</label>
                        <textarea name="welcome_message" rows="4"
                                  class="w-full rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-green-500 focus:ring-green-500"
                                  placeholder="ข้อความต้อนรับเมื่อผู้ใช้เพิ่มเพื่อน LINE OA...">{{ $settings->welcome_message ?? '' }}</textarea>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">ข้อความนี้จะแสดงเมื่อผู้ใช้เพิ่มเพื่อน LINE OA ของตลาดสด</p>
                    </div>
                </div>
            </div>

            {{-- ปุ่มบันทึก --}}
            <div class="mt-8 pt-6 border-t border-gray-200 dark:border-gray-700 flex justify-end">
                <button type="submit"
                        class="inline-flex items-center px-8 py-3 bg-green-600 hover:bg-green-700 text-white font-semibold rounded-xl shadow-lg hover:shadow-xl transition-all duration-200">
                    <i class="fas fa-save mr-2"></i> บันทึกการตั้งค่า
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
