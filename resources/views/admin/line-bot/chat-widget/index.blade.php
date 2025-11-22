@extends('layouts.admin-v3')

@section('title', 'ตั้งค่า Chat Widget')

@section('content')
<div class="min-h-screen bg-gray-100/50 dark:bg-slate-900 py-6 px-4 sm:px-6 lg:px-8" x-data="{
    showWidget: false,
    widgetPosition: 'bottom-right',
    primaryColor: '{{ old('primary_color', $settings->primary_color ?? '#06C755') }}',
    widgetTitle: '{{ old('widget_title', $settings->widget_title ?? 'พูดคุยกับเรา') }}',
    welcomeMessage: '{{ old('welcome_message', $settings->welcome_message ?? 'สวัสดีค่ะ! มีอะไรให้ช่วยไหมคะ?') }}',
    isEnabled: {{ old('is_enabled', $settings->is_enabled ?? false) ? 'true' : 'false' }}
}">

    <!-- LINE-Themed Header -->
    <div class="mb-8 relative overflow-hidden rounded-3xl bg-gradient-to-br from-[#00B900] via-[#00E600] to-[#00C900] p-8 shadow-2xl shadow-green-500/30">
        <!-- Decorative Background -->
        <div class="absolute inset-0 opacity-10">
            <div class="absolute top-0 left-0 w-64 h-64 glass-fusion rounded-full -translate-x-32 -translate-y-32 border border-white/20 dark:border-white/10"></div>
            <div class="absolute bottom-0 right-0 w-96 h-96 glass-fusion rounded-full translate-x-48 translate-y-48 border border-white/20 dark:border-white/10"></div>
        </div>

        <div class="relative flex flex-col md:flex-row items-start md:items-center justify-between gap-4">
            <div class="flex items-center gap-4">
                <div class="w-16 h-16 rounded-2xl glass-fusion backdrop-blur-md flex items-center justify-center shadow-xl border border-white/20 dark:border-white/10">
                    <svg class="w-10 h-10 text-white" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M20 2H4c-1.1 0-2 .9-2 2v18l4-4h14c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2zm0 14H6l-2 2V4h16v12z"/>
                    </svg>
                </div>
                <div>
                    <h1 class="text-4xl font-bold text-white mb-1 flex items-center gap-2">
                        ตั้งค่า Chat Widget
                    </h1>
                    <p class="text-white/90 text-lg">
                        กำหนดค่า Widget แชทสำหรับเว็บไซต์ของคุณ
                    </p>
                </div>
            </div>
        </div>
    </div>

    <!-- Success Message -->
    @if(session('success'))
        <div class="mb-6 rounded-2xl bg-green-50 dark:bg-green-900/20 border-2 border-green-200 dark:border-green-800 p-6 shadow-lg animate-fade-in">
            <div class="flex items-center gap-4">
                <div class="w-12 h-12 rounded-full bg-green-500 flex items-center justify-center flex-shrink-0">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                </div>
                <p class="text-green-800 dark:text-green-200 font-semibold">{{ session('success') }}</p>
            </div>
        </div>
    @endif

    <form method="POST" action="{{ route('admin.line-bot.chat-widget.update') }}">
        @csrf
        @method('PUT')

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Settings Section -->
            <div class="lg:col-span-2 space-y-6">
                <!-- General Settings -->
                <div class="glass-fusion dark:bg-slate-800 rounded-2xl shadow-xl border border-gray-100 dark:border-slate-700 p-6">
                    <h3 class="text-2xl font-bold text-gray-900 dark:text-white mb-6 flex items-center gap-3">
                        <svg class="w-6 h-6 text-[#00B900]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                        การตั้งค่าทั่วไป
                    </h3>

                    <div class="space-y-6">
                        <!-- Enable Widget -->
                        <div class="flex items-center justify-between p-4 bg-gradient-to-r from-green-50 to-emerald-50 dark:from-green-900/20 dark:to-emerald-900/20 border-2 border-green-200 dark:border-green-800 rounded-2xl">
                            <div class="flex-1">
                                <h4 class="font-bold text-gray-900 dark:text-white">เปิดใช้งาน Chat Widget</h4>
                                <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">แสดง Widget บนเว็บไซต์ของคุณ</p>
                            </div>
                            <label class="relative inline-flex items-center cursor-pointer">
                                <input type="checkbox" name="is_enabled" value="1"
                                       x-model="isEnabled"
                                       {{ old('is_enabled', $settings->is_enabled ?? false) ? 'checked' : '' }}
                                       class="sr-only peer">
                                <div class="w-14 h-8 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-green-300 dark:peer-focus:ring-green-800 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-6 peer-checked:after:border-white after:content-[''] after:absolute after:top-1 after:left-1 after:bg-white after:border-gray-300 after:border after:rounded-full after:h-6 after:w-6 after:transition-all dark:border-gray-600 peer-checked:bg-gradient-to-r peer-checked:from-[#00B900] peer-checked:to-[#00E600]"></div>
                            </label>
                        </div>

                        <!-- Widget Title -->
                        <div>
                            <label class="flex items-center gap-2 text-sm font-bold text-gray-700 dark:text-gray-300 mb-2">
                                <svg class="w-5 h-5 text-[#00B900]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z"/>
                                </svg>
                                ชื่อ Widget
                            </label>
                            <input type="text" name="widget_title"
                                   x-model="widgetTitle"
                                   value="{{ old('widget_title', $settings->widget_title ?? 'พูดคุยกับเรา') }}"
                                   class="w-full px-4 py-3 border-2 border-gray-200 dark:border-slate-600 glass-fusion dark:bg-slate-700 text-gray-900 dark:text-white rounded-xl focus:ring-4 focus:ring-green-500/20 focus:border-[#00B900] transition-all duration-300">
                        </div>

                        <!-- Welcome Message -->
                        <div>
                            <label class="flex items-center gap-2 text-sm font-bold text-gray-700 dark:text-gray-300 mb-2">
                                <svg class="w-5 h-5 text-[#00B900]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
                                </svg>
                                ข้อความต้อนรับ
                            </label>
                            <textarea name="welcome_message" rows="3"
                                      x-model="welcomeMessage"
                                      class="w-full px-4 py-3 border-2 border-gray-200 dark:border-slate-600 glass-fusion dark:bg-slate-700 text-gray-900 dark:text-white rounded-xl focus:ring-4 focus:ring-green-500/20 focus:border-[#00B900] transition-all duration-300">{{ old('welcome_message', $settings->welcome_message ?? 'สวัสดีค่ะ! มีอะไรให้ช่วยไหมคะ?') }}</textarea>
                        </div>

                        <!-- Position -->
                        <div>
                            <label class="flex items-center gap-2 text-sm font-bold text-gray-700 dark:text-gray-300 mb-3">
                                <svg class="w-5 h-5 text-[#00B900]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                                </svg>
                                ตำแหน่ง Widget
                            </label>
                            <div class="grid grid-cols-2 gap-4">
                                <button type="button" @click="widgetPosition = 'bottom-right'"
                                        :class="widgetPosition === 'bottom-right' ? 'bg-gradient-to-r from-[#00B900] to-[#00E600] text-white shadow-lg scale-105' : 'bg-gray-100/50 dark:bg-slate-700 text-gray-700 dark:text-gray-300'"
                                        class="px-6 py-4 rounded-xl font-bold transition-all duration-300 flex items-center justify-center gap-2 transform">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 13h6m-3-3v6m5 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                    </svg>
                                    ล่างขวา
                                </button>
                                <button type="button" @click="widgetPosition = 'bottom-left'"
                                        :class="widgetPosition === 'bottom-left' ? 'bg-gradient-to-r from-[#00B900] to-[#00E600] text-white shadow-lg scale-105' : 'bg-gray-100/50 dark:bg-slate-700 text-gray-700 dark:text-gray-300'"
                                        class="px-6 py-4 rounded-xl font-bold transition-all duration-300 flex items-center justify-center gap-2 transform">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 13h6m-3-3v6m5 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                    </svg>
                                    ล่างซ้าย
                                </button>
                            </div>
                            <input type="hidden" name="position" x-model="widgetPosition">
                        </div>

                        <!-- Primary Color -->
                        <div>
                            <label class="flex items-center gap-2 text-sm font-bold text-gray-700 dark:text-gray-300 mb-2">
                                <svg class="w-5 h-5 text-[#00B900]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zm0 0h12a2 2 0 002-2v-4a2 2 0 00-2-2h-2.343M11 7.343l1.657-1.657a2 2 0 012.828 0l2.829 2.829a2 2 0 010 2.828l-8.486 8.485M7 17h.01"/>
                                </svg>
                                สีหลัก (LINE Green แนะนำ)
                            </label>
                            <div class="flex gap-3">
                                <input type="color" name="primary_color"
                                       x-model="primaryColor"
                                       value="{{ old('primary_color', $settings->primary_color ?? '#06C755') }}"
                                       class="w-20 h-12 border-2 border-gray-200 dark:border-slate-600 rounded-xl cursor-pointer">
                                <input type="text"
                                       x-model="primaryColor"
                                       readonly
                                       class="flex-1 px-4 py-3 border-2 border-gray-200 dark:border-slate-600 glass-fusion dark:bg-slate-700 text-gray-900 dark:text-white rounded-xl font-mono">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Save Button -->
                <div class="glass-fusion dark:bg-slate-800 rounded-2xl shadow-xl border border-gray-100 dark:border-slate-700 p-6">
                    <div class="flex justify-end">
                        <button type="submit"
                                class="px-8 py-4 bg-gradient-to-r from-[#00B900] to-[#00E600] text-white rounded-xl hover:shadow-2xl hover:shadow-green-500/50 transition-all duration-300 font-bold flex items-center gap-3 transform hover:-translate-y-1">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"/>
                            </svg>
                            บันทึกการตั้งค่า
                        </button>
                    </div>
                </div>
            </div>

            <!-- Preview Section -->
            <div class="lg:col-span-1">
                <div class="glass-fusion dark:bg-slate-800 rounded-2xl shadow-xl border border-gray-100 dark:border-slate-700 overflow-hidden sticky top-6">
                    <div class="bg-gradient-to-r from-purple-600 to-pink-600 px-6 py-4">
                        <h3 class="text-xl font-bold text-white flex items-center gap-2">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                            </svg>
                            ตัวอย่าง
                        </h3>
                    </div>
                    <div class="p-6">
                        <!-- Preview Area -->
                        <div class="relative bg-gradient-to-br from-gray-100 to-gray-200 dark:from-slate-700 dark:to-slate-600 rounded-2xl p-8 min-h-[400px]">
                            <!-- Mock Website Content -->
                            <div class="text-gray-500 dark:text-gray-600 text-sm">
                                <div class="h-4 bg-gray-300 dark:bg-gray-700 rounded w-3/4 mb-3"></div>
                                <div class="h-4 bg-gray-300 dark:bg-gray-700 rounded w-full mb-3"></div>
                                <div class="h-4 bg-gray-300 dark:bg-gray-700 rounded w-2/3"></div>
                            </div>

                            <!-- Chat Widget -->
                            <div :class="widgetPosition === 'bottom-right' ? 'bottom-6 right-6' : 'bottom-6 left-6'"
                                 class="absolute z-10">
                                <!-- Widget Button -->
                                <button @click="showWidget = !showWidget"
                                        class="w-16 h-16 rounded-full shadow-2xl flex items-center justify-center transition-all duration-300 transform hover:scale-110"
                                        :style="'background: linear-gradient(135deg, ' + primaryColor + ', ' + primaryColor + '99)'">
                                    <svg class="w-8 h-8 text-white" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M19.365 9.863c.349 0 .63.285.63.631 0 .345-.281.63-.63.63H17.61v1.125h1.755c.349 0 .63.283.63.63 0 .344-.281.629-.63.629h-2.386c-.345 0-.627-.285-.627-.629V8.108c0-.345.282-.63.63-.63h2.386c.346 0 .627.285.627.63 0 .349-.281.63-.63.63H17.61v1.125h1.755zm-3.855 3.016c0 .27-.174.51-.432.596-.064.021-.133.031-.199.031-.211 0-.391-.09-.51-.25l-2.443-3.317v2.94c0 .344-.279.629-.631.629-.346 0-.626-.285-.626-.629V8.108c0-.27.173-.51.43-.595.06-.023.136-.033.194-.033.195 0 .375.104.495.254l2.462 3.33V8.108c0-.345.282-.63.63-.63.345 0 .63.285.63.63v4.771zm-5.741 0c0 .344-.282.629-.631.629-.345 0-.627-.285-.627-.629V8.108c0-.345.282-.63.63-.63.346 0 .628.285.628.63v4.771zm-2.466.629H4.917c-.345 0-.63-.285-.63-.629V8.108c0-.345.285-.63.63-.63.348 0 .63.285.63.63v4.141h1.756c.348 0 .629.283.629.63 0 .344-.282.629-.629.629M24 10.314C24 4.943 18.615.572 12 .572S0 4.943 0 10.314c0 4.811 4.27 8.842 10.035 9.608.391.082.923.258 1.058.59.12.301.079.766.038 1.08l-.164 1.02c-.045.301-.24 1.186 1.049.645 1.291-.539 6.916-4.078 9.436-6.975C23.176 14.393 24 12.458 24 10.314"/>
                                    </svg>
                                </button>

                                <!-- Chat Window -->
                                <div x-show="showWidget"
                                     x-transition
                                     class="absolute bottom-20 w-80 glass-fusion backdrop-blur-xl bg-white/95 dark:bg-slate-800/95 rounded-2xl shadow-2xl border border-white/20 dark:border-slate-700/50 overflow-hidden"
                                     :class="widgetPosition === 'bottom-right' ? 'right-0' : 'left-0'">
                                    <!-- Widget Header -->
                                    <div class="p-4 flex items-center justify-between"
                                         :style="'background: linear-gradient(135deg, ' + primaryColor + ', ' + primaryColor + '99)'">
                                        <div class="flex items-center gap-3">
                                            <div class="w-10 h-10 bg-white rounded-full overflow-hidden flex items-center justify-center">
                                                <svg class="w-6 h-6" :style="'color: ' + primaryColor" fill="currentColor" viewBox="0 0 24 24">
                                                    <path d="M19.365 9.863c.349 0 .63.285.63.631 0 .345-.281.63-.63.63H17.61v1.125h1.755c.349 0 .63.283.63.63 0 .344-.281.629-.63.629h-2.386c-.345 0-.627-.285-.627-.629V8.108c0-.345.282-.63.63-.63h2.386c.346 0 .627.285.627.63 0 .349-.281.63-.63.63H17.61v1.125h1.755zm-3.855 3.016c0 .27-.174.51-.432.596-.064.021-.133.031-.199.031-.211 0-.391-.09-.51-.25l-2.443-3.317v2.94c0 .344-.279.629-.631.629-.346 0-.626-.285-.626-.629V8.108c0-.27.173-.51.43-.595.06-.023.136-.033.194-.033.195 0 .375.104.495.254l2.462 3.33V8.108c0-.345.282-.63.63-.63.345 0 .63.285.63.63v4.771zm-5.741 0c0 .344-.282.629-.631.629-.345 0-.627-.285-.627-.629V8.108c0-.345.282-.63.63-.63.346 0 .628.285.628.63v4.771zm-2.466.629H4.917c-.345 0-.63-.285-.63-.629V8.108c0-.345.285-.63.63-.63.348 0 .63.285.63.63v4.141h1.756c.348 0 .629.283.629.63 0 .344-.282.629-.629.629M24 10.314C24 4.943 18.615.572 12 .572S0 4.943 0 10.314c0 4.811 4.27 8.842 10.035 9.608.391.082.923.258 1.058.59.12.301.079.766.038 1.08l-.164 1.02c-.045.301-.24 1.186 1.049.645 1.291-.539 6.916-4.078 9.436-6.975C23.176 14.393 24 12.458 24 10.314"/>
                                                </svg>
                                            </div>
                                            <div>
                                                <h4 class="text-white font-bold" x-text="widgetTitle">LINE Bot</h4>
                                                <p class="text-white/80 text-xs">ออนไลน์</p>
                                            </div>
                                        </div>
                                        <button @click="showWidget = false" class="text-white hover:bg-white/20 rounded-lg p-1">
                                            ✕
                                        </button>
                                    </div>

                                    <!-- Widget Body -->
                                    <div class="p-4 h-64 bg-gradient-to-b from-gray-50 to-gray-100 dark:from-slate-900 dark:to-slate-800">
                                        <!-- Greeting Message -->
                                        <div class="bg-white dark:bg-slate-700 rounded-2xl rounded-tl-none p-4 shadow-lg max-w-[80%]">
                                            <p class="text-gray-900 dark:text-white text-sm" x-text="welcomeMessage">
                                                สวัสดีค่ะ! มีอะไรให้ช่วยไหมคะ?
                                            </p>
                                        </div>
                                    </div>

                                    <!-- Widget Input -->
                                    <div class="p-3 bg-white dark:bg-slate-800 border-t border-gray-200 dark:border-slate-700">
                                        <div class="flex gap-2">
                                            <input type="text" placeholder="พิมพ์ข้อความ..."
                                                   class="flex-1 px-3 py-2 bg-gray-100 dark:bg-slate-700 rounded-lg text-sm">
                                            <button class="px-4 py-2 rounded-lg text-white"
                                                    :style="'background: linear-gradient(135deg, ' + primaryColor + ', ' + primaryColor + '99)'">
                                                ส่ง
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<style>
[x-cloak] { display: none !important; }

@keyframes fade-in {
    from {
        opacity: 0;
        transform: translateY(-10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.animate-fade-in {
    animation: fade-in 0.3s ease-out;
}
</style>
@endsection
