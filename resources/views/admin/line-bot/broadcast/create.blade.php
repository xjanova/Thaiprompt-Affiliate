@extends('layouts.admin-v3')

@section('title', 'สร้าง Broadcast ใหม่')

@section('content')
<div class="container-fluid px-4 py-6" x-data="broadcastCreator()">
    {{-- Header --}}
    <div class="mb-6">
        <div class="flex items-center gap-3">
            <a href="{{ route('admin.line-bot.broadcast.index') }}"
               class="flex items-center justify-center w-12 h-12 rounded-xl glass-fusion border-2 border-gray-200 dark:border-gray-700 text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white shadow-lg hover:shadow-xl transition-all transform hover:scale-105">
                <i class="fas fa-arrow-left"></i>
            </a>
            <div>
                <h1 class="text-3xl font-black bg-gradient-to-r from-[#06C755] via-emerald-600 to-teal-600 bg-clip-text text-transparent">
                    ✨ สร้าง Broadcast ใหม่
                </h1>
                <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">ส่งข้อความไปยังผู้ใช้หลายคนพร้อมกัน</p>
            </div>
        </div>
    </div>

    {{-- Error Messages --}}
    @if($errors->any())
        <div class="mb-6 rounded-2xl glass-fusion border-2 border-red-200 dark:border-red-800 p-6 shadow-xl">
            <div class="flex items-start gap-3">
                <div class="w-12 h-12 rounded-full bg-gradient-to-br from-red-500 to-pink-600 flex items-center justify-center shadow-lg">
                    <i class="fas fa-exclamation-circle text-white text-xl"></i>
                </div>
                <div class="flex-1">
                    <h4 class="font-bold text-red-900 dark:text-red-100 mb-2">พบข้อผิดพลาด:</h4>
                    <ul class="list-disc list-inside text-sm text-red-700 dark:text-red-400 space-y-1">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </div>
    @endif

    {{-- Progress Steps --}}
    <div class="mb-8 glass-fusion rounded-2xl shadow-lg border border-white/20 dark:border-white/10 p-6">
        <div class="flex items-center justify-between relative">
            {{-- Progress Line --}}
            <div class="absolute top-6 left-0 right-0 h-1 bg-gray-200 dark:bg-gray-700 rounded-full -z-10">
                <div class="h-full bg-gradient-to-r from-[#06C755] to-emerald-600 rounded-full transition-all duration-500"
                     :style="`width: ${((currentStep - 1) / 2) * 100}%`"></div>
            </div>

            {{-- Step 1: กลุ่มเป้าหมาย --}}
            <div class="flex flex-col items-center flex-1 cursor-pointer" @click="currentStep = 1">
                <div class="w-12 h-12 rounded-full flex items-center justify-center font-bold text-lg mb-2 transition-all duration-300"
                     :class="currentStep >= 1 ? 'bg-gradient-to-br from-[#06C755] to-emerald-600 text-white shadow-lg scale-110' : 'bg-gray-200 dark:bg-gray-700 text-gray-500'">
                    <i class="fas fa-users"></i>
                </div>
                <span class="text-sm font-semibold text-center"
                      :class="currentStep >= 1 ? 'text-[#06C755] dark:text-emerald-400' : 'text-gray-500'">
                    กลุ่มเป้าหมาย
                </span>
            </div>

            {{-- Step 2: เนื้อหา --}}
            <div class="flex flex-col items-center flex-1 cursor-pointer" @click="currentStep = 2">
                <div class="w-12 h-12 rounded-full flex items-center justify-center font-bold text-lg mb-2 transition-all duration-300"
                     :class="currentStep >= 2 ? 'bg-gradient-to-br from-[#06C755] to-emerald-600 text-white shadow-lg scale-110' : 'bg-gray-200 dark:bg-gray-700 text-gray-500'">
                    <i class="fas fa-edit"></i>
                </div>
                <span class="text-sm font-semibold text-center"
                      :class="currentStep >= 2 ? 'text-[#06C755] dark:text-emerald-400' : 'text-gray-500'">
                    เนื้อหาข้อความ
                </span>
            </div>

            {{-- Step 3: ตั้งเวลา & ส่ง --}}
            <div class="flex flex-col items-center flex-1 cursor-pointer" @click="currentStep = 3">
                <div class="w-12 h-12 rounded-full flex items-center justify-center font-bold text-lg mb-2 transition-all duration-300"
                     :class="currentStep >= 3 ? 'bg-gradient-to-br from-[#06C755] to-emerald-600 text-white shadow-lg scale-110' : 'bg-gray-200 dark:bg-gray-700 text-gray-500'">
                    <i class="fas fa-paper-plane"></i>
                </div>
                <span class="text-sm font-semibold text-center"
                      :class="currentStep >= 3 ? 'text-[#06C755] dark:text-emerald-400' : 'text-gray-500'">
                    ตั้งเวลา & ส่ง
                </span>
            </div>
        </div>
    </div>

    {{-- Form --}}
    <form method="POST" action="{{ route('admin.line-bot.broadcast.store') }}" enctype="multipart/form-data">
        @csrf

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            {{-- Main Content --}}
            <div class="lg:col-span-2 space-y-6">
                {{-- STEP 1: Target Audience --}}
                <div x-show="currentStep === 1" x-transition class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-purple-500 via-pink-500 to-red-500 p-8 shadow-2xl">
                    <div class="absolute inset-0 bg-[url('/images/patterns/topography.svg')] opacity-10"></div>
                    <div class="relative glass-fusion backdrop-blur-xl rounded-2xl p-6 border border-white/20">
                        <h3 class="text-2xl font-black text-white mb-6 flex items-center gap-2">
                            <i class="fas fa-users"></i>
                            STEP 1: เลือกกลุ่มเป้าหมาย
                        </h3>

                        <div class="space-y-5">
                            {{-- Broadcast Name --}}
                            <div>
                                <label class="block text-sm font-semibold text-white mb-2">
                                    <i class="fas fa-tag mr-1"></i> ชื่อแคมเปญ *
                                </label>
                                <input type="text"
                                       name="name"
                                       x-model="formData.name"
                                       value="{{ old('name') }}"
                                       required
                                       placeholder="เช่น: โปรโมชั่นพิเศษ, ข่าวสารใหม่"
                                       class="w-full px-4 py-3 rounded-xl bg-white/90 dark:bg-gray-800 text-gray-900 dark:text-white border-0 focus:ring-2 focus:ring-white/50 placeholder-gray-500 dark:placeholder-gray-400">
                            </div>

                            {{-- Target Audience --}}
                            <div>
                                <label class="block text-sm font-semibold text-white mb-3">
                                    <i class="fas fa-bullseye mr-1"></i> กลุ่มเป้าหมาย *
                                </label>
                                <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                                    <button type="button"
                                            @click="formData.targetType = 'all'; updateRecipientCount()"
                                            :class="formData.targetType === 'all' ? 'bg-white text-purple-600 shadow-2xl scale-105 ring-4 ring-white/30' : 'bg-white/20 text-white hover:bg-white/30'"
                                            class="px-4 py-4 rounded-xl font-semibold transition-all transform">
                                        <div class="text-2xl mb-1">👥</div>
                                        <div class="text-sm">ทั้งหมด</div>
                                    </button>
                                    <button type="button"
                                            @click="formData.targetType = 'users'; updateRecipientCount()"
                                            :class="formData.targetType === 'users' ? 'bg-white text-purple-600 shadow-2xl scale-105 ring-4 ring-white/30' : 'bg-white/20 text-white hover:bg-white/30'"
                                            class="px-4 py-4 rounded-xl font-semibold transition-all transform">
                                        <div class="text-2xl mb-1">🧑</div>
                                        <div class="text-sm">สมาชิก</div>
                                    </button>
                                    <button type="button"
                                            @click="formData.targetType = 'sellers'; updateRecipientCount()"
                                            :class="formData.targetType === 'sellers' ? 'bg-white text-purple-600 shadow-2xl scale-105 ring-4 ring-white/30' : 'bg-white/20 text-white hover:bg-white/30'"
                                            class="px-4 py-4 rounded-xl font-semibold transition-all transform">
                                        <div class="text-2xl mb-1">🏪</div>
                                        <div class="text-sm">ผู้ขาย</div>
                                    </button>
                                    <button type="button"
                                            @click="formData.targetType = 'custom'; updateRecipientCount()"
                                            :class="formData.targetType === 'custom' ? 'bg-white text-purple-600 shadow-2xl scale-105 ring-4 ring-white/30' : 'bg-white/20 text-white hover:bg-white/30'"
                                            class="px-4 py-4 rounded-xl font-semibold transition-all transform">
                                        <div class="text-2xl mb-1">🎯</div>
                                        <div class="text-sm">กำหนดเอง</div>
                                    </button>
                                </div>
                                <input type="hidden" name="target_type" x-model="formData.targetType">
                            </div>

                            {{-- Recipient Count Preview --}}
                            <div class="bg-white/20 backdrop-blur-sm rounded-xl p-5 border border-white/30">
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center gap-3">
                                        <div class="w-12 h-12 rounded-full bg-white/30 flex items-center justify-center">
                                            <i class="fas fa-users text-white text-xl"></i>
                                        </div>
                                        <div>
                                            <p class="text-xs text-white/80 font-medium">จำนวนผู้รับทั้งหมด</p>
                                            <p class="text-3xl font-black text-white" x-text="recipientCount.toLocaleString()">0</p>
                                        </div>
                                    </div>
                                    <button type="button"
                                            @click="updateRecipientCount()"
                                            class="px-4 py-2 bg-white/30 hover:bg-white/40 text-white rounded-lg transition-all font-semibold text-sm">
                                        <i class="fas fa-sync-alt mr-1"></i> รีเฟรช
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- STEP 2: Message Content --}}
                <div x-show="currentStep === 2" x-transition class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-blue-500 via-indigo-500 to-purple-500 p-8 shadow-2xl">
                    <div class="absolute inset-0 bg-[url('/images/patterns/circuit-board.svg')] opacity-10"></div>
                    <div class="relative glass-fusion backdrop-blur-xl rounded-2xl p-6 border border-white/20">
                        <h3 class="text-2xl font-black text-white mb-6 flex items-center gap-2">
                            <i class="fas fa-edit"></i>
                            STEP 2: เนื้อหาข้อความ
                        </h3>

                        <div class="space-y-5">
                            {{-- Message Type Selection --}}
                            <div>
                                <label class="block text-sm font-semibold text-white mb-3">
                                    <i class="fas fa-comment-alt mr-1"></i> ประเภทข้อความ *
                                </label>
                                <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                                    <button type="button"
                                            @click="formData.messageType = 'text'"
                                            :class="formData.messageType === 'text' ? 'bg-white text-blue-600 shadow-2xl scale-105 ring-4 ring-white/30' : 'bg-white/20 text-white hover:bg-white/30'"
                                            class="px-4 py-4 rounded-xl font-semibold transition-all transform">
                                        <div class="text-2xl mb-1">💬</div>
                                        <div class="text-sm">ข้อความ</div>
                                    </button>
                                    <button type="button"
                                            @click="formData.messageType = 'image'"
                                            :class="formData.messageType === 'image' ? 'bg-white text-blue-600 shadow-2xl scale-105 ring-4 ring-white/30' : 'bg-white/20 text-white hover:bg-white/30'"
                                            class="px-4 py-4 rounded-xl font-semibold transition-all transform">
                                        <div class="text-2xl mb-1">🖼️</div>
                                        <div class="text-sm">รูปภาพ</div>
                                    </button>
                                    <button type="button"
                                            @click="formData.messageType = 'flex'"
                                            :class="formData.messageType === 'flex' ? 'bg-white text-blue-600 shadow-2xl scale-105 ring-4 ring-white/30' : 'bg-white/20 text-white hover:bg-white/30'"
                                            class="px-4 py-4 rounded-xl font-semibold transition-all transform">
                                        <div class="text-2xl mb-1">🎨</div>
                                        <div class="text-sm">Flex</div>
                                    </button>
                                    <button type="button"
                                            @click="formData.messageType = 'video'"
                                            :class="formData.messageType === 'video' ? 'bg-white text-blue-600 shadow-2xl scale-105 ring-4 ring-white/30' : 'bg-white/20 text-white hover:bg-white/30'"
                                            class="px-4 py-4 rounded-xl font-semibold transition-all transform">
                                        <div class="text-2xl mb-1">🎥</div>
                                        <div class="text-sm">วิดีโอ</div>
                                    </button>
                                </div>
                                <input type="hidden" name="message_type" x-model="formData.messageType">
                            </div>

                            {{-- Text Message Content --}}
                            <div x-show="formData.messageType === 'text'" x-transition>
                                <label class="block text-sm font-semibold text-white mb-2">
                                    <i class="fas fa-keyboard mr-1"></i> เนื้อหาข้อความ *
                                </label>
                                <textarea name="content"
                                          x-model="formData.content"
                                          rows="6"
                                          x-bind:required="formData.messageType === 'text'"
                                          @input="updatePreview()"
                                          placeholder="พิมพ์ข้อความที่ต้องการส่ง... (รองรับ Emoji 😊)"
                                          class="w-full px-4 py-3 rounded-xl bg-white/90 dark:bg-gray-800 text-gray-900 dark:text-white border-0 focus:ring-2 focus:ring-white/50 placeholder-gray-500 dark:placeholder-gray-400 font-thai">{{ old('content') }}</textarea>
                                <p class="text-xs text-white/80 mt-2 flex items-center gap-2">
                                    <i class="fas fa-info-circle"></i>
                                    <span x-text="`${formData.content.length} / 5000 ตัวอักษร`"></span>
                                </p>
                            </div>

                            {{-- Image Upload --}}
                            <div x-show="formData.messageType === 'image'" x-transition>
                                <label class="block text-sm font-semibold text-white mb-2">
                                    <i class="fas fa-image mr-1"></i> อัพโหลดรูปภาพ *
                                </label>
                                <div class="bg-white/90 dark:bg-gray-800 rounded-xl p-6 border-2 border-dashed border-white/50">
                                    <input type="file"
                                           name="image"
                                           accept="image/*"
                                           x-bind:required="formData.messageType === 'image'"
                                           @change="previewImage($event)"
                                           class="w-full">
                                    <div x-show="imagePreview" class="mt-4">
                                        <img :src="imagePreview" class="max-h-64 mx-auto rounded-xl shadow-lg">
                                    </div>
                                </div>
                            </div>

                            {{-- Flex Template Selection --}}
                            <div x-show="formData.messageType === 'flex'" x-transition>
                                <label class="block text-sm font-semibold text-white mb-2">
                                    <i class="fas fa-palette mr-1"></i> เลือก Flex Template *
                                </label>
                                <select name="flex_template_id"
                                        x-bind:required="formData.messageType === 'flex'"
                                        class="w-full px-4 py-3 rounded-xl bg-white/90 dark:bg-gray-800 text-gray-900 dark:text-white border-0 focus:ring-2 focus:ring-white/50">
                                    <option value="">-- เลือก Template --</option>
                                    @foreach($flexTemplates ?? [] as $template)
                                        <option value="{{ $template->id }}">{{ $template->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- STEP 3: Schedule & Send --}}
                <div x-show="currentStep === 3" x-transition class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-emerald-500 via-teal-600 to-cyan-700 p-8 shadow-2xl">
                    <div class="absolute inset-0 bg-[url('/images/patterns/wiggle.svg')] opacity-10"></div>
                    <div class="relative glass-fusion backdrop-blur-xl rounded-2xl p-6 border border-white/20">
                        <h3 class="text-2xl font-black text-white mb-6 flex items-center gap-2">
                            <i class="fas fa-clock"></i>
                            STEP 3: ตั้งเวลา & ส่ง
                        </h3>

                        <div class="space-y-5">
                            {{-- Schedule Type Selection --}}
                            <div class="grid grid-cols-2 gap-4">
                                <button type="button"
                                        @click="formData.scheduleType = 'now'"
                                        :class="formData.scheduleType === 'now' ? 'bg-white text-emerald-600 shadow-2xl scale-105 ring-4 ring-white/30' : 'bg-white/20 text-white hover:bg-white/30'"
                                        class="px-6 py-5 rounded-xl font-semibold transition-all transform">
                                    <div class="text-4xl mb-2">🚀</div>
                                    <div class="font-bold">ส่งทันที</div>
                                    <div class="text-xs opacity-75 mt-1">ส่งเมื่อกด Submit</div>
                                </button>
                                <button type="button"
                                        @click="formData.scheduleType = 'scheduled'"
                                        :class="formData.scheduleType === 'scheduled' ? 'bg-white text-emerald-600 shadow-2xl scale-105 ring-4 ring-white/30' : 'bg-white/20 text-white hover:bg-white/30'"
                                        class="px-6 py-5 rounded-xl font-semibold transition-all transform">
                                    <div class="text-4xl mb-2">⏰</div>
                                    <div class="font-bold">ตั้งเวลา</div>
                                    <div class="text-xs opacity-75 mt-1">กำหนดวัน-เวลาส่ง</div>
                                </button>
                            </div>

                            {{-- Datetime Picker --}}
                            <div x-show="formData.scheduleType === 'scheduled'"
                                 x-transition
                                 class="bg-white/20 rounded-xl p-5 border border-white/30">
                                <label class="block text-sm font-semibold text-white mb-3">
                                    <i class="fas fa-calendar-alt mr-1"></i> เลือกวันและเวลา
                                </label>
                                <input type="datetime-local"
                                       name="scheduled_at"
                                       value="{{ old('scheduled_at') }}"
                                       min="{{ now()->addMinutes(5)->format('Y-m-d\TH:i') }}"
                                       x-bind:required="formData.scheduleType === 'scheduled'"
                                       class="w-full px-4 py-3 rounded-lg bg-white/90 dark:bg-gray-800 text-gray-900 dark:text-white border-0 focus:ring-2 focus:ring-emerald-400 text-lg">
                                <p class="text-xs text-white/80 mt-2">
                                    <i class="fas fa-info-circle mr-1"></i>
                                    ระบบจะส่งข้อความอัตโนมัติในเวลาที่กำหนด
                                </p>
                            </div>

                            {{-- Test Send --}}
                            <div class="bg-white/20 rounded-xl p-5 border border-white/30">
                                <h4 class="text-sm font-semibold text-white mb-3">
                                    <i class="fas fa-vial mr-1"></i> ทดสอบส่งข้อความ
                                </h4>
                                <p class="text-xs text-white/80 mb-3">ส่งข้อความทดสอบไปยัง LINE ID ของคุณก่อนส่งจริง</p>
                                <button type="button"
                                        @click="testSend()"
                                        class="w-full px-4 py-3 bg-gradient-to-r from-yellow-400 to-orange-500 hover:from-yellow-500 hover:to-orange-600 text-white rounded-xl transition-all shadow-lg font-bold">
                                    <i class="fas fa-flask mr-2"></i>
                                    ส่งทดสอบ
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Sidebar: Preview & Tips --}}
            <div class="lg:col-span-1">
                <div class="sticky top-6 space-y-6">
                    {{-- Message Preview (LINE-style) --}}
                    <div class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-gray-800 to-gray-900 p-6 shadow-2xl">
                        <h3 class="font-bold text-white mb-4 flex items-center gap-2">
                            <i class="fas fa-mobile-alt text-[#06C755]"></i>
                            ตัวอย่างใน LINE
                        </h3>

                        {{-- LINE Chat Interface --}}
                        <div class="bg-[#E5DDD5] rounded-2xl p-4 min-h-[300px] relative" style="background-image: url('data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMTAwIiBoZWlnaHQ9IjEwMCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48ZGVmcz48cGF0dGVybiBpZD0ic3F1YXJlcyIgd2lkdGg9IjEwMCIgaGVpZ2h0PSIxMDAiIHBhdHRlcm5Vbml0cz0idXNlclNwYWNlT25Vc2UiPjxyZWN0IHdpZHRoPSIxMDAiIGhlaWdodD0iMTAwIiBmaWxsPSJ3aGl0ZSIgZmlsbC1vcGFjaXR5PSIwLjAzIi8+PC9wYXR0ZXJuPjwvZGVmcz48cmVjdCB3aWR0aD0iMTAwJSIgaGVpZ2h0PSIxMDAlIiBmaWxsPSJ1cmwoI3NxdWFyZXMpIi8+PC9zdmc+');">
                            {{-- System Message --}}
                            <div class="mb-4 text-center">
                                <span class="inline-block px-3 py-1 bg-white/60 backdrop-blur-sm rounded-full text-xs text-gray-600">
                                    วันนี้
                                </span>
                            </div>

                            {{-- LINE Message Bubble --}}
                            <div class="flex items-start gap-2" x-show="formData.content || imagePreview">
                                <div class="w-10 h-10 rounded-full bg-gradient-to-br from-[#06C755] to-emerald-600 flex items-center justify-center shadow-lg flex-shrink-0">
                                    <i class="fas fa-bullhorn text-white text-sm"></i>
                                </div>
                                <div class="flex-1">
                                    <div class="bg-white rounded-2xl rounded-tl-none p-4 shadow-lg max-w-xs">
                                        {{-- Image Preview --}}
                                        <template x-if="formData.messageType === 'image' && imagePreview">
                                            <img :src="imagePreview" class="w-full rounded-xl mb-2">
                                        </template>

                                        {{-- Text Preview --}}
                                        <template x-if="formData.messageType === 'text' && formData.content">
                                            <p class="text-gray-900 text-sm whitespace-pre-wrap" x-text="formData.content"></p>
                                        </template>

                                        {{-- Empty State --}}
                                        <template x-if="!formData.content && !imagePreview">
                                            <p class="text-gray-400 text-sm italic">พิมพ์ข้อความเพื่อดูตัวอย่าง...</p>
                                        </template>
                                    </div>
                                    <p class="text-xs text-gray-600 mt-1 ml-2" x-text="new Date().toLocaleTimeString('th-TH', { hour: '2-digit', minute: '2-digit' })"></p>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Tips Card --}}
                    <div class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-[#06C755] to-emerald-700 p-6 shadow-2xl">
                        <div class="absolute inset-0 bg-[url('/images/patterns/wiggle.svg')] opacity-10"></div>
                        <div class="relative">
                            <h3 class="font-bold text-white mb-4 flex items-center gap-2">
                                <i class="fas fa-lightbulb text-yellow-300 text-xl"></i>
                                เคล็ดลับ Broadcast
                            </h3>

                            <div class="space-y-3 text-sm">
                                <div class="bg-white/20 backdrop-blur-sm rounded-xl p-4 border border-white/30">
                                    <p class="font-semibold text-white mb-2">✨ Best Practices:</p>
                                    <ul class="text-xs text-white/90 space-y-1.5">
                                        <li class="flex items-start gap-2">
                                            <span class="text-emerald-300 mt-0.5">•</span>
                                            <span>ใช้ข้อความสั้นๆ ได้ใจความ</span>
                                        </li>
                                        <li class="flex items-start gap-2">
                                            <span class="text-emerald-300 mt-0.5">•</span>
                                            <span>เพิ่ม emoji เพื่อดึงดูดสายตา</span>
                                        </li>
                                        <li class="flex items-start gap-2">
                                            <span class="text-emerald-300 mt-0.5">•</span>
                                            <span>ใส่ Call-to-Action ที่ชัดเจน</span>
                                        </li>
                                        <li class="flex items-start gap-2">
                                            <span class="text-emerald-300 mt-0.5">•</span>
                                            <span>ทดสอบส่งก่อนส่งจริง</span>
                                        </li>
                                    </ul>
                                </div>

                                <div class="bg-white/20 backdrop-blur-sm rounded-xl p-4 border border-white/30">
                                    <p class="font-semibold text-white mb-2">⏰ เวลาที่เหมาะสม:</p>
                                    <p class="text-xs text-white/90">ส่งในช่วง 9:00 - 21:00 น. เพื่อ engagement ที่ดีกว่า</p>
                                </div>

                                <div class="bg-white/20 backdrop-blur-sm rounded-xl p-4 border border-white/30">
                                    <p class="font-semibold text-white mb-2">🎯 เลือกกลุ่มเป้าหมาย:</p>
                                    <p class="text-xs text-white/90">แบ่งกลุ่มผู้รับเพื่อส่งข้อความที่ตรงใจมากขึ้น</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Action Buttons --}}
        <div class="mt-8 sticky bottom-0 glass-fusion backdrop-blur-xl rounded-2xl shadow-2xl p-6 border-2 border-[#06C755]/30 dark:border-emerald-800">
            <div class="flex items-center justify-between">
                {{-- Navigation Buttons --}}
                <div class="flex gap-3">
                    <button type="button"
                            x-show="currentStep > 1"
                            @click="currentStep--"
                            class="px-6 py-3 glass-fusion border-2 border-gray-200 dark:border-gray-700 text-gray-700 dark:text-gray-300 rounded-xl hover:border-gray-400 dark:hover:border-gray-500 transition-all shadow-lg font-bold">
                        <i class="fas fa-arrow-left mr-2"></i>
                        ย้อนกลับ
                    </button>
                </div>

                <div class="flex gap-3">
                    {{-- Save as Draft --}}
                    <button type="submit" name="action" value="draft"
                            class="px-8 py-3 bg-gradient-to-r from-gray-500 to-gray-600 text-white rounded-xl hover:from-gray-600 hover:to-gray-700 transition-all shadow-lg font-bold transform hover:scale-105">
                        <i class="fas fa-save mr-2"></i>
                        บันทึกแบบร่าง
                    </button>

                    {{-- Next Step / Send --}}
                    <button type="button"
                            x-show="currentStep < 3"
                            @click="currentStep++"
                            class="px-8 py-3 bg-gradient-to-r from-[#06C755] via-emerald-600 to-teal-600 text-white rounded-xl hover:from-emerald-600 hover:to-[#06C755] transition-all shadow-2xl font-bold transform hover:scale-105 animate-pulse-slow">
                        <span>ถัดไป</span>
                        <i class="fas fa-arrow-right ml-2"></i>
                    </button>

                    <button type="submit" name="action" value="send"
                            x-show="currentStep === 3"
                            class="px-8 py-3 bg-gradient-to-r from-[#06C755] via-emerald-600 to-teal-600 text-white rounded-xl hover:from-emerald-600 hover:to-[#06C755] transition-all shadow-2xl font-bold transform hover:scale-105 animate-pulse-slow">
                        <i class="fas fa-paper-plane mr-2"></i>
                        <span x-text="formData.scheduleType === 'now' ? 'ส่งทันที' : 'ตั้งเวลาส่ง'"></span>
                    </button>
                </div>
            </div>
        </div>
    </form>
</div>

@push('scripts')
<script>
/**
 * Broadcast Creator Alpine.js Component
 *
 * จัดการ state และ logic สำหรับการสร้าง broadcast
 */
function broadcastCreator() {
    return {
        // ขั้นตอนปัจจุบัน (1-3)
        currentStep: 1,

        // ข้อมูลฟอร์ม
        formData: {
            name: '',
            targetType: 'all',
            messageType: 'text',
            content: '',
            scheduleType: 'now'
        },

        // จำนวนผู้รับ
        recipientCount: 0,

        // Image preview URL
        imagePreview: null,

        /**
         * อัพเดทจำนวนผู้รับตาม target type
         */
        updateRecipientCount() {
            // TODO: เรียก API เพื่อดึงจำนวนผู้รับจริง
            const counts = {
                'all': 1250,
                'users': 1000,
                'sellers': 250,
                'custom': 0
            };
            this.recipientCount = counts[this.formData.targetType] || 0;
        },

        /**
         * อัพเดทตัวอย่างข้อความ
         */
        updatePreview() {
            // Preview จะอัพเดทอัตโนมัติผ่าน x-model
        },

        /**
         * Preview รูปภาพที่อัพโหลด
         */
        previewImage(event) {
            const file = event.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = (e) => {
                    this.imagePreview = e.target.result;
                };
                reader.readAsDataURL(file);
            }
        },

        /**
         * ส่งข้อความทดสอบ
         */
        async testSend() {
            if (!this.formData.content && !this.imagePreview) {
                alert('กรุณากรอกข้อความหรืออัพโหลดรูปภาพก่อน');
                return;
            }

            if (confirm('ส่งข้อความทดสอบไปยัง LINE ของคุณหรือไม่?')) {
                // TODO: Implement test send API
                alert('กำลังส่งข้อความทดสอบ...\n\n(ฟีเจอร์นี้กำลังพัฒนา)');
            }
        },

        /**
         * Initialize component
         */
        init() {
            this.updateRecipientCount();
        }
    }
}
</script>
@endpush
@endsection
