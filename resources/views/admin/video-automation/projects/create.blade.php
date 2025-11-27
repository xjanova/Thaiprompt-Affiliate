@extends('layouts.admin')

@section('title', 'สร้างโปรเจกต์ - Video Automation')

@section('styles')
<style>
    /* การ์ด Glassmorphism */
    .glass-card {
        background: rgba(255, 255, 255, 0.8);
        backdrop-filter: blur(10px);
        border: 1px solid rgba(255, 255, 255, 0.2);
    }
    .dark .glass-card {
        background: rgba(30, 41, 59, 0.8);
        border: 1px solid rgba(255, 255, 255, 0.1);
    }

    /* ปุ่ม Gradient */
    .btn-gradient-primary {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        transition: all 0.3s ease;
    }
    .btn-gradient-primary:hover {
        transform: translateY(-2px);
        box-shadow: 0 10px 20px rgba(102, 126, 234, 0.3);
    }

    .btn-gradient-success {
        background: linear-gradient(135deg, #10b981 0%, #059669 100%);
        transition: all 0.3s ease;
    }
    .btn-gradient-success:hover {
        transform: translateY(-2px);
        box-shadow: 0 10px 20px rgba(16, 185, 129, 0.3);
    }

    /* Input Focus Effect */
    .input-modern:focus {
        box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.2);
    }

    /* Template Card */
    .template-select-card {
        cursor: pointer;
        transition: all 0.3s ease;
        border: 2px solid transparent;
    }
    .template-select-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
    }
    .template-select-card.selected {
        border-color: #667eea;
        background: rgba(102, 126, 234, 0.05);
    }
    .dark .template-select-card.selected {
        background: rgba(102, 126, 234, 0.1);
    }

    /* Step Indicator */
    .step-indicator {
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }
    .step-dot {
        width: 2rem;
        height: 2rem;
        border-radius: 9999px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 600;
        font-size: 0.875rem;
        transition: all 0.3s ease;
    }
    .step-dot.active {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
    }
    .step-dot.completed {
        background: #10b981;
        color: white;
    }
    .step-dot.inactive {
        background: rgba(156, 163, 175, 0.2);
        color: #9ca3af;
    }
    .step-line {
        flex: 1;
        height: 2px;
        background: rgba(156, 163, 175, 0.2);
    }
    .step-line.active {
        background: linear-gradient(90deg, #667eea 0%, #764ba2 100%);
    }
</style>
@endsection

@section('content')
<div class="container mx-auto px-4 py-8" x-data="projectForm()">
    {{-- Header --}}
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-8">
        <div>
            <div class="flex items-center gap-3 mb-2">
                <a href="{{ route('admin.video-automation.projects.index') }}"
                   class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200 transition">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                    </svg>
                </a>
                <h1 class="text-3xl font-bold text-gray-900 dark:text-white">
                    สร้างโปรเจกต์ใหม่
                </h1>
            </div>
            <p class="text-gray-600 dark:text-gray-400">
                สร้างวิดีโอใหม่ด้วยระบบอัตโนมัติ
            </p>
        </div>
    </div>

    {{-- Step Indicator --}}
    <div class="glass-card rounded-2xl p-6 mb-8">
        <div class="flex items-center">
            <div class="step-indicator flex-1">
                <div class="step-dot" :class="currentStep >= 1 ? (currentStep > 1 ? 'completed' : 'active') : 'inactive'">
                    <span x-show="currentStep <= 1">1</span>
                    <svg x-show="currentStep > 1" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/>
                    </svg>
                </div>
                <span class="text-sm font-medium" :class="currentStep >= 1 ? 'text-gray-900 dark:text-white' : 'text-gray-400'">เลือก Template</span>
            </div>

            <div class="step-line mx-4" :class="currentStep > 1 ? 'active' : ''"></div>

            <div class="step-indicator flex-1">
                <div class="step-dot" :class="currentStep >= 2 ? (currentStep > 2 ? 'completed' : 'active') : 'inactive'">
                    <span x-show="currentStep <= 2">2</span>
                    <svg x-show="currentStep > 2" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/>
                    </svg>
                </div>
                <span class="text-sm font-medium" :class="currentStep >= 2 ? 'text-gray-900 dark:text-white' : 'text-gray-400'">ตั้งค่าโปรเจกต์</span>
            </div>

            <div class="step-line mx-4" :class="currentStep > 2 ? 'active' : ''"></div>

            <div class="step-indicator flex-1">
                <div class="step-dot" :class="currentStep >= 3 ? 'active' : 'inactive'">3</div>
                <span class="text-sm font-medium" :class="currentStep >= 3 ? 'text-gray-900 dark:text-white' : 'text-gray-400'">ยืนยัน</span>
            </div>
        </div>
    </div>

    <form action="{{ route('admin.video-automation.projects.store') }}" method="POST" @submit="handleSubmit">
        @csrf

        {{-- Step 1: Select Template --}}
        <div x-show="currentStep === 1" x-transition>
            <div class="glass-card rounded-2xl p-6 mb-6">
                <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-6 flex items-center gap-3">
                    <span class="w-10 h-10 rounded-xl bg-gradient-to-br from-purple-500 to-pink-500 flex items-center justify-center">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 5a1 1 0 011-1h14a1 1 0 011 1v2a1 1 0 01-1 1H5a1 1 0 01-1-1V5zM4 13a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H5a1 1 0 01-1-1v-6zM16 13a1 1 0 011-1h2a1 1 0 011 1v6a1 1 0 01-1 1h-2a1 1 0 01-1-1v-6z"/>
                        </svg>
                    </span>
                    เลือก Template
                </h2>

                @if($templates->count() > 0)
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        @foreach($templates as $template)
                            <div class="template-select-card glass-card rounded-xl p-4"
                                 :class="{ 'selected': selectedTemplate === {{ $template->id }} }"
                                 @click="selectTemplate({{ $template->id }}, {{ json_encode($template) }})">
                                <input type="radio" name="template_id" value="{{ $template->id }}"
                                       x-model="selectedTemplate"
                                       class="hidden"
                                       {{ request('template') == $template->id ? 'checked' : '' }}>

                                <div class="flex items-start justify-between mb-3">
                                    <h3 class="font-semibold text-gray-900 dark:text-white">{{ $template->name }}</h3>
                                    <div class="w-5 h-5 rounded-full border-2 flex items-center justify-center"
                                         :class="selectedTemplate === {{ $template->id }} ? 'border-purple-500 bg-purple-500' : 'border-gray-300 dark:border-gray-600'">
                                        <svg x-show="selectedTemplate === {{ $template->id }}" class="w-3 h-3 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/>
                                        </svg>
                                    </div>
                                </div>

                                @if($template->description)
                                    <p class="text-sm text-gray-600 dark:text-gray-400 mb-3 line-clamp-2">{{ $template->description }}</p>
                                @endif

                                <div class="flex flex-wrap gap-2">
                                    <span class="px-2 py-1 bg-purple-100 dark:bg-purple-900/30 text-purple-700 dark:text-purple-400 text-xs rounded-full">
                                        {{ \App\Models\VideoAutoTemplate::GENRES[$template->music_genre] ?? $template->music_genre }}
                                    </span>
                                    <span class="px-2 py-1 bg-cyan-100 dark:bg-cyan-900/30 text-cyan-700 dark:text-cyan-400 text-xs rounded-full">
                                        {{ \App\Models\VideoAutoTemplate::IMAGE_STYLES[$template->image_style] ?? $template->image_style }}
                                    </span>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <div class="mt-4 text-center">
                        <a href="{{ route('admin.video-automation.templates.create') }}"
                           class="text-purple-600 dark:text-purple-400 hover:underline text-sm">
                            + สร้าง Template ใหม่
                        </a>
                    </div>
                @else
                    <div class="text-center py-12">
                        <p class="text-gray-500 dark:text-gray-400 mb-4">ยังไม่มี Template</p>
                        <a href="{{ route('admin.video-automation.templates.create') }}"
                           class="btn-gradient-primary inline-flex items-center gap-2 px-6 py-3 text-white rounded-xl font-semibold">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                            </svg>
                            สร้าง Template แรก
                        </a>
                    </div>
                @endif
            </div>

            <div class="flex justify-end">
                <button type="button"
                        @click="nextStep"
                        :disabled="!selectedTemplate"
                        class="btn-gradient-primary px-8 py-3 text-white rounded-xl font-semibold disabled:opacity-50 disabled:cursor-not-allowed">
                    ถัดไป
                    <svg class="w-5 h-5 inline ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                    </svg>
                </button>
            </div>
        </div>

        {{-- Step 2: Project Settings --}}
        <div x-show="currentStep === 2" x-transition>
            <div class="glass-card rounded-2xl p-6 mb-6">
                <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-6 flex items-center gap-3">
                    <span class="w-10 h-10 rounded-xl bg-gradient-to-br from-blue-500 to-cyan-500 flex items-center justify-center">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                    </span>
                    ตั้งค่าโปรเจกต์
                </h2>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    {{-- Project Name --}}
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            ชื่อโปรเจกต์ <span class="text-red-500">*</span>
                        </label>
                        <input type="text"
                               name="name"
                               x-model="projectName"
                               required
                               class="input-modern w-full px-4 py-3 rounded-xl border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-purple-500 focus:border-transparent transition"
                               placeholder="เช่น เพลง Pop สำหรับ YouTube">
                    </div>

                    {{-- Music Prompt Override --}}
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Prompt เพลง (ปรับแต่งเพิ่มเติม)
                        </label>
                        <textarea name="music_prompt"
                                  rows="3"
                                  class="input-modern w-full px-4 py-3 rounded-xl border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-purple-500 focus:border-transparent transition resize-none"
                                  placeholder="ปล่อยว่างเพื่อใช้ค่าจาก Template"></textarea>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                            Template: <span x-text="templateData?.music_prompt || 'ไม่มี prompt'"></span>
                        </p>
                    </div>

                    {{-- Image Prompt Override --}}
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Prompt ภาพ (ปรับแต่งเพิ่มเติม)
                        </label>
                        <textarea name="image_prompt"
                                  rows="3"
                                  class="input-modern w-full px-4 py-3 rounded-xl border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-purple-500 focus:border-transparent transition resize-none"
                                  placeholder="ปล่อยว่างเพื่อใช้ค่าจาก Template"></textarea>
                    </div>

                    {{-- Video Title --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            ชื่อวิดีโอ (สำหรับเผยแพร่)
                        </label>
                        <input type="text"
                               name="video_title"
                               class="input-modern w-full px-4 py-3 rounded-xl border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-purple-500 focus:border-transparent transition"
                               placeholder="ปล่อยว่างเพื่อสร้างอัตโนมัติ">
                    </div>

                    {{-- Privacy --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            ความเป็นส่วนตัว
                        </label>
                        <select name="privacy"
                                class="input-modern w-full px-4 py-3 rounded-xl border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-purple-500 focus:border-transparent transition">
                            <option value="public">สาธารณะ (Public)</option>
                            <option value="unlisted">ไม่แสดง (Unlisted)</option>
                            <option value="private">ส่วนตัว (Private)</option>
                        </select>
                    </div>

                    {{-- Video Description --}}
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            คำอธิบายวิดีโอ
                        </label>
                        <textarea name="video_description"
                                  rows="4"
                                  class="input-modern w-full px-4 py-3 rounded-xl border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-purple-500 focus:border-transparent transition resize-none"
                                  placeholder="ปล่อยว่างเพื่อสร้างอัตโนมัติจาก Template"></textarea>
                    </div>

                    {{-- Tags --}}
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            แท็ก
                        </label>
                        <input type="text"
                               name="tags"
                               class="input-modern w-full px-4 py-3 rounded-xl border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-purple-500 focus:border-transparent transition"
                               placeholder="คั่นด้วยเครื่องหมายจุลภาค (,)">
                    </div>
                </div>
            </div>

            {{-- Platform Selection --}}
            <div class="glass-card rounded-2xl p-6 mb-6">
                <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-6 flex items-center gap-3">
                    <span class="w-10 h-10 rounded-xl bg-gradient-to-br from-orange-500 to-red-500 flex items-center justify-center">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
                        </svg>
                    </span>
                    เลือกแพลตฟอร์มเผยแพร่
                </h2>

                <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
                    @foreach($platforms ?? [] as $platform)
                        <label class="flex items-center gap-3 p-4 rounded-xl border border-gray-200 dark:border-gray-700 hover:border-purple-500 dark:hover:border-purple-500 cursor-pointer transition">
                            <input type="checkbox" name="platforms[]" value="{{ $platform->id }}" class="rounded text-purple-600 focus:ring-purple-500">
                            <div class="flex items-center gap-2">
                                @if($platform->platform_type == 'youtube')
                                    <svg class="w-6 h-6 text-red-600" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M23.498 6.186a3.016 3.016 0 0 0-2.122-2.136C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.377.505A3.017 3.017 0 0 0 .502 6.186C0 8.07 0 12 0 12s0 3.93.502 5.814a3.016 3.016 0 0 0 2.122 2.136c1.871.505 9.376.505 9.376.505s7.505 0 9.377-.505a3.015 3.015 0 0 0 2.122-2.136C24 15.93 24 12 24 12s0-3.93-.502-5.814zM9.545 15.568V8.432L15.818 12l-6.273 3.568z"/>
                                    </svg>
                                @elseif($platform->platform_type == 'facebook')
                                    <svg class="w-6 h-6 text-blue-600" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/>
                                    </svg>
                                @elseif($platform->platform_type == 'tiktok')
                                    <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M19.59 6.69a4.83 4.83 0 0 1-3.77-4.25V2h-3.45v13.67a2.89 2.89 0 0 1-5.2 1.74 2.89 2.89 0 0 1 2.31-4.64 2.93 2.93 0 0 1 .88.13V9.4a6.84 6.84 0 0 0-1-.05A6.33 6.33 0 0 0 5 20.1a6.34 6.34 0 0 0 10.86-4.43v-7a8.16 8.16 0 0 0 4.77 1.52v-3.4a4.85 4.85 0 0 1-1-.1z"/>
                                    </svg>
                                @else
                                    <svg class="w-6 h-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9"/>
                                    </svg>
                                @endif
                                <span class="text-sm font-medium text-gray-900 dark:text-white">{{ $platform->account_name ?? ucfirst($platform->platform_type) }}</span>
                            </div>
                        </label>
                    @endforeach
                </div>

                @if(empty($platforms) || $platforms->count() === 0)
                    <p class="text-center text-gray-500 dark:text-gray-400 py-4">
                        ยังไม่มีแพลตฟอร์มที่เชื่อมต่อ
                        <a href="{{ route('admin.video-automation.settings') }}" class="text-purple-600 hover:underline">ตั้งค่าแพลตฟอร์ม</a>
                    </p>
                @endif
            </div>

            <div class="flex justify-between">
                <button type="button"
                        @click="prevStep"
                        class="px-6 py-3 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-xl hover:bg-gray-300 dark:hover:bg-gray-600 transition">
                    <svg class="w-5 h-5 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                    </svg>
                    ย้อนกลับ
                </button>
                <button type="button"
                        @click="nextStep"
                        :disabled="!projectName"
                        class="btn-gradient-primary px-8 py-3 text-white rounded-xl font-semibold disabled:opacity-50 disabled:cursor-not-allowed">
                    ถัดไป
                    <svg class="w-5 h-5 inline ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                    </svg>
                </button>
            </div>
        </div>

        {{-- Step 3: Confirmation --}}
        <div x-show="currentStep === 3" x-transition>
            <div class="glass-card rounded-2xl p-6 mb-6">
                <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-6 flex items-center gap-3">
                    <span class="w-10 h-10 rounded-xl bg-gradient-to-br from-green-500 to-emerald-500 flex items-center justify-center">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </span>
                    ยืนยันการสร้างโปรเจกต์
                </h2>

                <div class="space-y-4">
                    <div class="flex items-center justify-between py-3 border-b border-gray-200 dark:border-gray-700">
                        <span class="text-gray-600 dark:text-gray-400">ชื่อโปรเจกต์</span>
                        <span class="font-semibold text-gray-900 dark:text-white" x-text="projectName || '-'"></span>
                    </div>
                    <div class="flex items-center justify-between py-3 border-b border-gray-200 dark:border-gray-700">
                        <span class="text-gray-600 dark:text-gray-400">Template</span>
                        <span class="font-semibold text-gray-900 dark:text-white" x-text="templateData?.name || '-'"></span>
                    </div>
                    <div class="flex items-center justify-between py-3 border-b border-gray-200 dark:border-gray-700">
                        <span class="text-gray-600 dark:text-gray-400">แนวเพลง</span>
                        <span class="font-semibold text-gray-900 dark:text-white" x-text="templateData?.music_genre || '-'"></span>
                    </div>
                    <div class="flex items-center justify-between py-3 border-b border-gray-200 dark:border-gray-700">
                        <span class="text-gray-600 dark:text-gray-400">สไตล์ภาพ</span>
                        <span class="font-semibold text-gray-900 dark:text-white" x-text="templateData?.image_style || '-'"></span>
                    </div>
                </div>

                {{-- Workflow Preview --}}
                <div class="mt-8">
                    <h3 class="font-semibold text-gray-900 dark:text-white mb-4">ขั้นตอนการทำงาน</h3>
                    <div class="flex items-center justify-between text-sm">
                        <div class="flex flex-col items-center">
                            <div class="w-10 h-10 rounded-full bg-purple-100 dark:bg-purple-900/30 flex items-center justify-center mb-2">
                                <svg class="w-5 h-5 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19V6l12-3v13M9 19c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zm12-3c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zM9 10l12-3"/>
                                </svg>
                            </div>
                            <span class="text-gray-600 dark:text-gray-400">สร้างเพลง</span>
                        </div>
                        <div class="flex-1 h-0.5 bg-gray-200 dark:bg-gray-700 mx-2"></div>
                        <div class="flex flex-col items-center">
                            <div class="w-10 h-10 rounded-full bg-cyan-100 dark:bg-cyan-900/30 flex items-center justify-center mb-2">
                                <svg class="w-5 h-5 text-cyan-600 dark:text-cyan-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                </svg>
                            </div>
                            <span class="text-gray-600 dark:text-gray-400">สร้างภาพ</span>
                        </div>
                        <div class="flex-1 h-0.5 bg-gray-200 dark:bg-gray-700 mx-2"></div>
                        <div class="flex flex-col items-center">
                            <div class="w-10 h-10 rounded-full bg-green-100 dark:bg-green-900/30 flex items-center justify-center mb-2">
                                <svg class="w-5 h-5 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                                </svg>
                            </div>
                            <span class="text-gray-600 dark:text-gray-400">สร้างวิดีโอ</span>
                        </div>
                        <div class="flex-1 h-0.5 bg-gray-200 dark:bg-gray-700 mx-2"></div>
                        <div class="flex flex-col items-center">
                            <div class="w-10 h-10 rounded-full bg-orange-100 dark:bg-orange-900/30 flex items-center justify-center mb-2">
                                <svg class="w-5 h-5 text-orange-600 dark:text-orange-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
                                </svg>
                            </div>
                            <span class="text-gray-600 dark:text-gray-400">เผยแพร่</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="flex justify-between">
                <button type="button"
                        @click="prevStep"
                        class="px-6 py-3 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-xl hover:bg-gray-300 dark:hover:bg-gray-600 transition">
                    <svg class="w-5 h-5 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                    </svg>
                    ย้อนกลับ
                </button>
                <div class="flex gap-4">
                    <button type="submit"
                            name="action"
                            value="draft"
                            class="px-6 py-3 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-xl hover:bg-gray-300 dark:hover:bg-gray-600 transition">
                        บันทึกแบบร่าง
                    </button>
                    <button type="submit"
                            name="action"
                            value="start"
                            class="btn-gradient-success px-8 py-3 text-white rounded-xl font-semibold">
                        <svg class="w-5 h-5 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        สร้างและเริ่มทันที
                    </button>
                </div>
            </div>
        </div>
    </form>
</div>
@endsection

@section('scripts')
<script>
function projectForm() {
    return {
        currentStep: 1,
        selectedTemplate: {{ request('template') ?: 'null' }},
        templateData: null,
        projectName: '',

        selectTemplate(id, data) {
            this.selectedTemplate = id;
            this.templateData = data;
        },

        nextStep() {
            if (this.currentStep < 3) {
                this.currentStep++;
            }
        },

        prevStep() {
            if (this.currentStep > 1) {
                this.currentStep--;
            }
        },

        handleSubmit(e) {
            if (!this.selectedTemplate || !this.projectName) {
                e.preventDefault();
                alert('กรุณากรอกข้อมูลให้ครบถ้วน');
            }
        }
    }
}
</script>
@endsection
