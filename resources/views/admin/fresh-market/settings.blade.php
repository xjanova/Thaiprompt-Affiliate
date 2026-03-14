@extends('layouts.admin-v3')

@section('title', 'ตั้งค่าระบบ - ตลาดสดไทยพร๊อม')

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
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">ตั้งค่าระบบ - ตลาดสดไทยพร๊อม</h1>
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
            <div x-show="activeTab === 'ai'" x-transition x-data="{
                allowedTopics: {{ json_encode($settings->ai_allowed_topics ?? []) }},
                blockedTopics: {{ json_encode($settings->ai_blocked_topics ?? []) }},
                newAllowed: '',
                newBlocked: '',
                addAllowed() {
                    if (this.newAllowed.trim() && !this.allowedTopics.includes(this.newAllowed.trim())) {
                        this.allowedTopics.push(this.newAllowed.trim());
                        this.newAllowed = '';
                    }
                },
                addBlocked() {
                    if (this.newBlocked.trim() && !this.blockedTopics.includes(this.newBlocked.trim())) {
                        this.blockedTopics.push(this.newBlocked.trim());
                        this.newBlocked = '';
                    }
                },
                removeAllowed(i) { this.allowedTopics.splice(i, 1); },
                removeBlocked(i) { this.blockedTopics.splice(i, 1); },
                tempSlider: {{ $settings->bot_temperature ?? 0.70 }}
            }">
                {{-- Hidden fields สำหรับ JSON arrays --}}
                <input type="hidden" name="ai_allowed_topics" :value="JSON.stringify(allowedTopics)">
                <input type="hidden" name="ai_blocked_topics" :value="JSON.stringify(blockedTopics)">

                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">ตั้งค่า AI บอท "พี่ตลาด"</h3>
                <p class="text-sm text-gray-500 dark:text-gray-400 mb-6">ควบคุมบุคลิกภาพ ขอบเขต และพฤติกรรมของบอท AI ทั้งหมดจากที่นี่</p>

                {{-- Section A: Bot Identity --}}
                <div class="mb-8 p-4 bg-purple-50 dark:bg-purple-900/20 rounded-xl border border-purple-200 dark:border-purple-800">
                    <h4 class="text-md font-semibold text-purple-800 dark:text-purple-300 mb-4">
                        <i class="fas fa-user-robot mr-2"></i>บุคลิกภาพบอท
                    </h4>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">ชื่อบอท</label>
                            <input type="text" name="bot_name"
                                   value="{{ $settings->bot_name ?? 'พี่ตลาด' }}"
                                   class="w-full rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-purple-500 focus:ring-purple-500"
                                   placeholder="เช่น พี่ตลาด, น้องสด">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">สไตล์การตอบ</label>
                            <select name="bot_response_style"
                                    class="w-full rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-purple-500 focus:ring-purple-500">
                                <option value="friendly" {{ ($settings->bot_response_style ?? 'friendly') === 'friendly' ? 'selected' : '' }}>ใจดี เป็นกันเอง</option>
                                <option value="formal" {{ ($settings->bot_response_style ?? '') === 'formal' ? 'selected' : '' }}>เป็นทางการ สุภาพ</option>
                                <option value="casual" {{ ($settings->bot_response_style ?? '') === 'casual' ? 'selected' : '' }}>สบายๆ ชิลล์</option>
                                <option value="funny" {{ ($settings->bot_response_style ?? '') === 'funny' ? 'selected' : '' }}>ตลก ขี้เล่น</option>
                            </select>
                        </div>
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">บุคลิกภาพ / อารมณ์</label>
                            <textarea name="bot_personality" rows="3"
                                      class="w-full rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-purple-500 focus:ring-purple-500"
                                      placeholder="เช่น ร่าเริง อบอุ่น ชอบช่วยเหลือ พูดสั้นกระชับ ใช้อิโมจิบ้าง">{{ $settings->bot_personality ?? '' }}</textarea>
                        </div>
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                อุณหภูมิ AI (ความคิดสร้างสรรค์):
                                <span class="text-purple-600 dark:text-purple-400 font-bold" x-text="tempSlider"></span>
                            </label>
                            <input type="range" name="bot_temperature" min="0" max="1" step="0.05"
                                   x-model="tempSlider"
                                   class="w-full h-2 bg-gray-200 dark:bg-gray-600 rounded-lg appearance-none cursor-pointer accent-purple-600">
                            <div class="flex justify-between text-xs text-gray-400 mt-1">
                                <span>0.0 (ตรงประเด็น)</span>
                                <span>0.5 (สมดุล)</span>
                                <span>1.0 (สร้างสรรค์)</span>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Section B: AI Provider --}}
                <div class="mb-8 p-4 bg-blue-50 dark:bg-blue-900/20 rounded-xl border border-blue-200 dark:border-blue-800">
                    <h4 class="text-md font-semibold text-blue-800 dark:text-blue-300 mb-4">
                        <i class="fas fa-microchip mr-2"></i>ผู้ให้บริการ AI
                    </h4>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Provider</label>
                            <select name="ai_provider"
                                    class="w-full rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                <option value="groq" {{ ($settings->ai_provider ?? 'groq') === 'groq' ? 'selected' : '' }}>Groq (แนะนำ)</option>
                                <option value="openrouter" {{ ($settings->ai_provider ?? '') === 'openrouter' ? 'selected' : '' }}>OpenRouter</option>
                                <option value="openai" {{ ($settings->ai_provider ?? '') === 'openai' ? 'selected' : '' }}>OpenAI</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">โมเดล AI</label>
                            <input type="text" name="ai_model"
                                   value="{{ $settings->ai_model ?? 'llama-3.3-70b-versatile' }}"
                                   class="w-full rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                   placeholder="เช่น llama-3.3-70b-versatile, gpt-4o-mini">
                        </div>
                        <div class="md:col-span-2">
                            <label class="flex items-center gap-2">
                                <input type="hidden" name="use_global_ai_settings" value="0">
                                <input type="checkbox" name="use_global_ai_settings" value="1"
                                       {{ ($settings->use_global_ai_settings ?? true) ? 'checked' : '' }}
                                       class="rounded border-gray-300 dark:border-gray-600 text-blue-600 focus:ring-blue-500">
                                <span class="text-sm text-gray-700 dark:text-gray-300">ใช้ API Key Pool จากระบบหลัก</span>
                            </label>
                        </div>
                    </div>
                </div>

                {{-- Section C: System Prompt --}}
                <div class="mb-8 p-4 bg-gray-50 dark:bg-gray-700/50 rounded-xl border border-gray-200 dark:border-gray-600">
                    <h4 class="text-md font-semibold text-gray-800 dark:text-gray-200 mb-4">
                        <i class="fas fa-terminal mr-2"></i>System Prompt
                    </h4>
                    <textarea name="ai_system_prompt" rows="6"
                              class="w-full rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-green-500 focus:ring-green-500"
                              placeholder="กำหนดบทบาทและคำสั่งสำหรับ AI แชทบอท...">{{ $settings->ai_system_prompt ?? '' }}</textarea>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Prompt หลักที่กำหนดพฤติกรรมของ AI</p>
                </div>

                {{-- Section D: Greeting & Menu --}}
                <div class="mb-8 p-4 bg-green-50 dark:bg-green-900/20 rounded-xl border border-green-200 dark:border-green-800">
                    <h4 class="text-md font-semibold text-green-800 dark:text-green-300 mb-4">
                        <i class="fas fa-hand-wave mr-2"></i>ข้อความต้อนรับ & เมนูปุ่ม
                    </h4>
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">ข้อความต้อนรับ (แสดงเมื่อผู้ใช้พิมพ์ใน idle)</label>
                            <textarea name="greeting_message_template" rows="3"
                                      class="w-full rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-green-500 focus:ring-green-500"
                                      placeholder="สวัสดีค่ะ! พี่ตลาดพร้อมช่วยเหลือค่ะ 😊&#10;&#10;เลือกสิ่งที่ต้องการได้เลยนะคะ:">{{ $settings->greeting_message_template ?? '' }}</textarea>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
                            @php
                                $labels = $settings->menu_button_labels ?? [];
                                $defaultLabels = [
                                    'buy' => ['default' => '🛒 ซื้อของ', 'desc' => 'ปุ่มซื้อของ'],
                                    'sell' => ['default' => '🏷️ ลงขาย', 'desc' => 'ปุ่มลงขาย'],
                                    'rider' => ['default' => '🏍️ ไรเดอร์', 'desc' => 'ปุ่มไรเดอร์'],
                                    'chat_ai' => ['default' => '💬 คุยกับ AI', 'desc' => 'ปุ่มคุยกับ AI'],
                                    'help' => ['default' => '📋 ช่วยเหลือ', 'desc' => 'ปุ่มช่วยเหลือ'],
                                ];
                            @endphp
                            @foreach($defaultLabels as $key => $info)
                                <div>
                                    <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">{{ $info['desc'] }}</label>
                                    <input type="text" name="menu_label_{{ $key }}"
                                           value="{{ $labels[$key] ?? $info['default'] }}"
                                           class="w-full text-sm rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-green-500 focus:ring-green-500"
                                           maxlength="20">
                                </div>
                            @endforeach
                        </div>

                        <div class="flex items-center gap-4 pt-2">
                            <label class="flex items-center gap-2">
                                <input type="hidden" name="ai_enabled_in_idle" value="0">
                                <input type="checkbox" name="ai_enabled_in_idle" value="1"
                                       {{ ($settings->ai_enabled_in_idle ?? false) ? 'checked' : '' }}
                                       class="rounded border-gray-300 dark:border-gray-600 text-green-600 focus:ring-green-500">
                                <span class="text-sm text-gray-700 dark:text-gray-300">AI ตอบ free text ใน idle (ไม่บังคับกดปุ่ม)</span>
                            </label>
                        </div>
                    </div>
                </div>

                {{-- Section E: AI Scope & Boundaries --}}
                <div class="mb-8 p-4 bg-orange-50 dark:bg-orange-900/20 rounded-xl border border-orange-200 dark:border-orange-800">
                    <h4 class="text-md font-semibold text-orange-800 dark:text-orange-300 mb-4">
                        <i class="fas fa-shield-alt mr-2"></i>ขอบเขต AI & หัวข้อ
                    </h4>
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">ขอบเขตการตอบ</label>
                            <textarea name="ai_scope_description" rows="2"
                                      class="w-full rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-orange-500 focus:ring-orange-500"
                                      placeholder="เช่น ตอบเฉพาะเรื่องตลาดสด สินค้าเกษตร อาหาร และบริการส่งของ">{{ $settings->ai_scope_description ?? '' }}</textarea>
                        </div>

                        {{-- Allowed Topics Tag Input --}}
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">หัวข้อที่ตอบได้</label>
                            <div class="flex flex-wrap gap-2 mb-2">
                                <template x-for="(topic, i) in allowedTopics" :key="'a'+i">
                                    <span class="inline-flex items-center gap-1 px-3 py-1 bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400 text-sm rounded-full">
                                        <span x-text="topic"></span>
                                        <button type="button" @click="removeAllowed(i)" class="text-green-500 hover:text-red-500">&times;</button>
                                    </span>
                                </template>
                            </div>
                            <div class="flex gap-2">
                                <input type="text" x-model="newAllowed" @keydown.enter.prevent="addAllowed()"
                                       class="flex-1 text-sm rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-orange-500 focus:ring-orange-500"
                                       placeholder="พิมพ์หัวข้อ แล้วกด Enter">
                                <button type="button" @click="addAllowed()"
                                        class="px-3 py-1 bg-green-600 text-white text-sm rounded-lg hover:bg-green-700">เพิ่ม</button>
                            </div>
                        </div>

                        {{-- Blocked Topics Tag Input --}}
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">หัวข้อที่ห้ามตอบ</label>
                            <div class="flex flex-wrap gap-2 mb-2">
                                <template x-for="(topic, i) in blockedTopics" :key="'b'+i">
                                    <span class="inline-flex items-center gap-1 px-3 py-1 bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-400 text-sm rounded-full">
                                        <span x-text="topic"></span>
                                        <button type="button" @click="removeBlocked(i)" class="text-red-500 hover:text-red-700">&times;</button>
                                    </span>
                                </template>
                            </div>
                            <div class="flex gap-2">
                                <input type="text" x-model="newBlocked" @keydown.enter.prevent="addBlocked()"
                                       class="flex-1 text-sm rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-orange-500 focus:ring-orange-500"
                                       placeholder="พิมพ์หัวข้อ แล้วกด Enter">
                                <button type="button" @click="addBlocked()"
                                        class="px-3 py-1 bg-red-600 text-white text-sm rounded-lg hover:bg-red-700">เพิ่ม</button>
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">ข้อความเมื่อถามนอกขอบเขต</label>
                            <input type="text" name="ai_off_topic_message"
                                   value="{{ $settings->ai_off_topic_message ?? '' }}"
                                   class="w-full rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-orange-500 focus:ring-orange-500"
                                   placeholder="เช่น ขอโทษค่ะ หัวข้อนี้อยู่นอกขอบเขตบริการค่ะ">
                        </div>
                    </div>
                </div>

                {{-- Section F: AI Dynamic Buttons & Data Access --}}
                <div class="mb-8 p-4 bg-indigo-50 dark:bg-indigo-900/20 rounded-xl border border-indigo-200 dark:border-indigo-800">
                    <h4 class="text-md font-semibold text-indigo-800 dark:text-indigo-300 mb-4">
                        <i class="fas fa-database mr-2"></i>ปุ่มอัจฉริยะ & สิทธิ์เข้าถึงข้อมูล
                    </h4>
                    <div class="space-y-4">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="flex items-center justify-between p-3 bg-white dark:bg-gray-800 rounded-lg">
                                <div>
                                    <p class="text-sm font-medium text-gray-900 dark:text-white">AI สร้างปุ่มอัตโนมัติ</p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">AI แนะนำปุ่มตามบริบทสนทนา</p>
                                </div>
                                <label class="relative inline-flex items-center cursor-pointer">
                                    <input type="hidden" name="ai_can_suggest_buttons" value="0">
                                    <input type="checkbox" name="ai_can_suggest_buttons" value="1"
                                           {{ ($settings->ai_can_suggest_buttons ?? true) ? 'checked' : '' }}
                                           class="sr-only peer">
                                    <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-indigo-300 dark:peer-focus:ring-indigo-800 rounded-full peer dark:bg-gray-600 peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-500 peer-checked:bg-indigo-600"></div>
                                </label>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">จำนวนปุ่มสูงสุดที่ AI แนะนำ</label>
                                <input type="number" name="ai_max_buttons" min="1" max="13"
                                       value="{{ $settings->ai_max_buttons ?? 4 }}"
                                       class="w-full rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            </div>
                        </div>

                        <p class="text-sm font-medium text-gray-700 dark:text-gray-300 pt-2">สิทธิ์เข้าถึงข้อมูล (AI เข้าถึงได้เฉพาะที่เปิด)</p>
                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
                            @php
                                $dataToggles = [
                                    ['name' => 'ai_can_access_listings', 'label' => 'รายการสินค้า', 'default' => true],
                                    ['name' => 'ai_can_access_pricing', 'label' => 'ราคาสินค้า', 'default' => true],
                                    ['name' => 'ai_can_access_orders', 'label' => 'คำสั่งซื้อ', 'default' => false],
                                    ['name' => 'ai_can_access_user_profile', 'label' => 'โปรไฟล์ผู้ใช้', 'default' => false],
                                    ['name' => 'ai_can_access_sellers', 'label' => 'ข้อมูลผู้ขาย', 'default' => false],
                                ];
                            @endphp
                            @foreach($dataToggles as $toggle)
                                <label class="flex items-center gap-2 p-3 bg-white dark:bg-gray-800 rounded-lg cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-750 transition">
                                    <input type="hidden" name="{{ $toggle['name'] }}" value="0">
                                    <input type="checkbox" name="{{ $toggle['name'] }}" value="1"
                                           {{ ($settings->{$toggle['name']} ?? $toggle['default']) ? 'checked' : '' }}
                                           class="rounded border-gray-300 dark:border-gray-600 text-indigo-600 focus:ring-indigo-500">
                                    <span class="text-sm text-gray-700 dark:text-gray-300">{{ $toggle['label'] }}</span>
                                </label>
                            @endforeach
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">จำนวน context messages สูงสุดที่ AI เห็น</label>
                            <input type="number" name="ai_max_context_messages" min="1" max="50"
                                   value="{{ $settings->ai_max_context_messages ?? 10 }}"
                                   class="w-32 rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        </div>
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
                               value="{{ $settings->brand_name ?? 'ตลาดสดไทยพร๊อม' }}"
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
