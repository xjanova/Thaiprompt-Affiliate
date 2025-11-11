@extends('layouts.admin')

@section('title', 'สร้าง LINE Avatar ใหม่')

@section('content')
<div class="min-h-screen py-6" x-data="{
    avatarType: 'image',
    sourceType: 'upload',
    previewUrl: null,
    fileName: '',
    fileSize: 0,
    showPreview: false,
    handleFileSelect(event) {
        const file = event.target.files[0];
        if (file) {
            this.fileName = file.name;
            this.fileSize = (file.size / 1024).toFixed(2);
            this.showPreview = true;

            // Create preview for images
            if (file.type.startsWith('image/')) {
                const reader = new FileReader();
                reader.onload = (e) => {
                    this.previewUrl = e.target.result;
                };
                reader.readAsDataURL(file);
            } else if (file.type.startsWith('video/')) {
                this.previewUrl = URL.createObjectURL(file);
            }
        }
    }
}">
    <!-- Stunning LINE-Themed Header -->
    <div class="mb-8 relative overflow-hidden rounded-3xl bg-gradient-to-br from-[#00B900] via-[#00E600] to-[#00C900] p-10 shadow-2xl shadow-green-500/30">
        <!-- Animated Background -->
        <div class="absolute inset-0 opacity-10">
            <div class="absolute top-0 left-0 w-96 h-96 bg-white rounded-full -translate-x-48 -translate-y-48 animate-pulse"></div>
            <div class="absolute bottom-0 right-0 w-64 h-64 bg-white rounded-full translate-x-32 translate-y-32 animate-pulse" style="animation-delay: 0.5s"></div>
            <div class="absolute top-1/2 left-1/2 w-48 h-48 bg-white rounded-full -translate-x-24 -translate-y-24 animate-pulse" style="animation-delay: 0.3s"></div>
        </div>

        <!-- Floating Avatar Icons -->
        <div class="absolute inset-0 pointer-events-none">
            <div class="absolute top-10 right-20 animate-bounce" style="animation-delay: 0.2s">
                <i class="fas fa-user-circle text-white/20 text-7xl"></i>
            </div>
            <div class="absolute bottom-10 left-20 animate-bounce" style="animation-delay: 0.8s">
                <i class="fas fa-smile text-white/20 text-6xl"></i>
            </div>
        </div>

        <div class="relative">
            <div class="flex items-start justify-between gap-4">
                <div class="flex items-center gap-4 flex-1">
                    <div class="w-20 h-20 rounded-3xl bg-white/25 backdrop-blur-md flex items-center justify-center shadow-2xl border-2 border-white/30">
                        <i class="fas fa-image text-white text-4xl drop-shadow-lg"></i>
                    </div>
                    <div class="flex-1">
                        <h1 class="text-5xl font-black text-white mb-2 drop-shadow-lg tracking-tight">🎭 สร้าง Avatar ใหม่</h1>
                        <p class="text-white/95 text-xl font-medium">อัพโหลดภาพ Avatar สำหรับ LINE Bot ของคุณ</p>
                        <div class="flex items-center gap-3 mt-3">
                            <span class="px-4 py-2 bg-white/20 backdrop-blur-sm rounded-full text-white text-sm font-bold border border-white/30">
                                <i class="fas fa-check-circle mr-1"></i>รองรับหลายรูปแบบ
                            </span>
                            <span class="px-4 py-2 bg-white/20 backdrop-blur-sm rounded-full text-white text-sm font-bold border border-white/30">
                                <i class="fas fa-magic mr-1"></i>เปลี่ยนได้ทันที
                            </span>
                        </div>
                    </div>
                </div>
                <a href="{{ route('admin.line-bot.avatar.index') }}"
                   class="px-8 py-4 bg-white/20 backdrop-blur-md border-2 border-white/40 text-white rounded-2xl hover:bg-white/30 transition-all duration-300 font-bold flex items-center gap-3 shadow-xl hover:scale-105">
                    <i class="fas fa-arrow-left text-xl"></i>
                    <span class="text-lg">กลับ</span>
                </a>
            </div>
        </div>
    </div>

    <!-- Main Form -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Form Section -->
        <div class="lg:col-span-2">
            <div class="bg-white dark:bg-slate-800 rounded-3xl shadow-2xl border border-gray-100 dark:border-slate-700 overflow-hidden">
                <div class="bg-gradient-to-r from-gray-50 to-slate-50 dark:from-slate-700 dark:to-slate-600 px-8 py-6 border-b-2 border-gray-200 dark:border-slate-600">
                    <h2 class="text-2xl font-bold text-gray-900 dark:text-white flex items-center gap-3">
                        <i class="fas fa-cog text-[#00B900]"></i>
                        ข้อมูล Avatar
                    </h2>
                </div>

                <form action="{{ route('admin.line-bot.avatars.store') }}" method="POST" enctype="multipart/form-data" class="p-8 space-y-8">
                    @csrf

                    <!-- Avatar Name -->
                    <div class="space-y-3">
                        <label class="flex items-center gap-2 text-lg font-bold text-gray-800 dark:text-white">
                            <i class="fas fa-tag text-[#00B900]"></i>
                            ชื่อ Avatar
                            <span class="text-red-500">*</span>
                        </label>
                        <input type="text" name="name" required value="{{ old('name') }}"
                               placeholder="เช่น: Avatar แบบมีชีวิตชีวา, รูปโปรไฟล์บอท"
                               class="w-full px-6 py-4 text-lg border-2 border-gray-200 dark:border-slate-600 bg-white dark:bg-slate-700 text-gray-900 dark:text-white rounded-2xl focus:ring-4 focus:ring-green-500/20 focus:border-[#00B900] transition-all duration-300 placeholder-gray-400 dark:placeholder-gray-500">
                        @error('name')
                            <p class="mt-2 text-sm text-red-600 dark:text-red-400 flex items-center gap-2">
                                <i class="fas fa-exclamation-circle"></i>{{ $message }}
                            </p>
                        @enderror
                    </div>

                    <!-- Avatar Type Selection -->
                    <div class="space-y-4">
                        <label class="flex items-center gap-2 text-lg font-bold text-gray-800 dark:text-white">
                            <i class="fas fa-layer-group text-[#00B900]"></i>
                            ประเภท Avatar
                            <span class="text-red-500">*</span>
                        </label>

                        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                            <!-- Image Type -->
                            <button type="button" @click="avatarType = 'image'"
                                    :class="avatarType === 'image' ? 'bg-gradient-to-br from-blue-600 to-cyan-600 text-white shadow-2xl shadow-blue-500/40 scale-105 border-transparent' : 'bg-white dark:bg-slate-700 text-gray-700 dark:text-gray-300 border-gray-200 dark:border-slate-600 hover:border-blue-400'"
                                    class="group relative border-2 rounded-2xl p-6 transition-all duration-300 hover:scale-105">
                                <div class="flex flex-col items-center gap-3">
                                    <div class="w-16 h-16 rounded-xl flex items-center justify-center"
                                         :class="avatarType === 'image' ? 'bg-white/25' : 'bg-blue-50 dark:bg-blue-900/30'">
                                        <i class="fas fa-image text-3xl" :class="avatarType === 'image' ? 'text-white' : 'text-blue-600'"></i>
                                    </div>
                                    <div class="text-center">
                                        <p class="font-bold text-lg">รูปภาพ</p>
                                        <p class="text-xs opacity-80 mt-1">JPG, PNG, WebP</p>
                                    </div>
                                </div>
                                <div x-show="avatarType === 'image'" x-transition
                                     class="absolute top-2 right-2 w-6 h-6 bg-white rounded-full flex items-center justify-center">
                                    <i class="fas fa-check text-blue-600 text-sm"></i>
                                </div>
                            </button>

                            <!-- GIF Type -->
                            <button type="button" @click="avatarType = 'gif'"
                                    :class="avatarType === 'gif' ? 'bg-gradient-to-br from-purple-600 to-pink-600 text-white shadow-2xl shadow-purple-500/40 scale-105 border-transparent' : 'bg-white dark:bg-slate-700 text-gray-700 dark:text-gray-300 border-gray-200 dark:border-slate-600 hover:border-purple-400'"
                                    class="group relative border-2 rounded-2xl p-6 transition-all duration-300 hover:scale-105">
                                <div class="flex flex-col items-center gap-3">
                                    <div class="w-16 h-16 rounded-xl flex items-center justify-center"
                                         :class="avatarType === 'gif' ? 'bg-white/25' : 'bg-purple-50 dark:bg-purple-900/30'">
                                        <i class="fas fa-film text-3xl" :class="avatarType === 'gif' ? 'text-white' : 'text-purple-600'"></i>
                                    </div>
                                    <div class="text-center">
                                        <p class="font-bold text-lg">GIF</p>
                                        <p class="text-xs opacity-80 mt-1">ภาพเคลื่อนไหว</p>
                                    </div>
                                </div>
                                <div x-show="avatarType === 'gif'" x-transition
                                     class="absolute top-2 right-2 w-6 h-6 bg-white rounded-full flex items-center justify-center">
                                    <i class="fas fa-check text-purple-600 text-sm"></i>
                                </div>
                            </button>

                            <!-- Lottie Type -->
                            <button type="button" @click="avatarType = 'lottie'"
                                    :class="avatarType === 'lottie' ? 'bg-gradient-to-br from-orange-600 to-red-600 text-white shadow-2xl shadow-orange-500/40 scale-105 border-transparent' : 'bg-white dark:bg-slate-700 text-gray-700 dark:text-gray-300 border-gray-200 dark:border-slate-600 hover:border-orange-400'"
                                    class="group relative border-2 rounded-2xl p-6 transition-all duration-300 hover:scale-105">
                                <div class="flex flex-col items-center gap-3">
                                    <div class="w-16 h-16 rounded-xl flex items-center justify-center"
                                         :class="avatarType === 'lottie' ? 'bg-white/25' : 'bg-orange-50 dark:bg-orange-900/30'">
                                        <i class="fas fa-play-circle text-3xl" :class="avatarType === 'lottie' ? 'text-white' : 'text-orange-600'"></i>
                                    </div>
                                    <div class="text-center">
                                        <p class="font-bold text-lg">Lottie</p>
                                        <p class="text-xs opacity-80 mt-1">Animation JSON</p>
                                    </div>
                                </div>
                                <div x-show="avatarType === 'lottie'" x-transition
                                     class="absolute top-2 right-2 w-6 h-6 bg-white rounded-full flex items-center justify-center">
                                    <i class="fas fa-check text-orange-600 text-sm"></i>
                                </div>
                            </button>

                            <!-- Video Type -->
                            <button type="button" @click="avatarType = 'video'"
                                    :class="avatarType === 'video' ? 'bg-gradient-to-br from-indigo-600 to-purple-600 text-white shadow-2xl shadow-indigo-500/40 scale-105 border-transparent' : 'bg-white dark:bg-slate-700 text-gray-700 dark:text-gray-300 border-gray-200 dark:border-slate-600 hover:border-indigo-400'"
                                    class="group relative border-2 rounded-2xl p-6 transition-all duration-300 hover:scale-105">
                                <div class="flex flex-col items-center gap-3">
                                    <div class="w-16 h-16 rounded-xl flex items-center justify-center"
                                         :class="avatarType === 'video' ? 'bg-white/25' : 'bg-indigo-50 dark:bg-indigo-900/30'">
                                        <i class="fas fa-video text-3xl" :class="avatarType === 'video' ? 'text-white' : 'text-indigo-600'"></i>
                                    </div>
                                    <div class="text-center">
                                        <p class="font-bold text-lg">วิดีโอ</p>
                                        <p class="text-xs opacity-80 mt-1">MP4, WebM</p>
                                    </div>
                                </div>
                                <div x-show="avatarType === 'video'" x-transition
                                     class="absolute top-2 right-2 w-6 h-6 bg-white rounded-full flex items-center justify-center">
                                    <i class="fas fa-check text-indigo-600 text-sm"></i>
                                </div>
                            </button>
                        </div>
                        <input type="hidden" name="type" x-model="avatarType">
                    </div>

                    <!-- Source Type Selection -->
                    <div class="space-y-4">
                        <label class="flex items-center gap-2 text-lg font-bold text-gray-800 dark:text-white">
                            <i class="fas fa-source text-[#00B900]"></i>
                            แหล่งที่มาของไฟล์
                        </label>
                        <div class="grid grid-cols-2 gap-4">
                            <button type="button" @click="sourceType = 'upload'"
                                    :class="sourceType === 'upload' ? 'bg-gradient-to-r from-[#00B900] to-[#00E600] text-white shadow-2xl shadow-green-500/40 scale-105' : 'bg-white dark:bg-slate-700 text-gray-700 dark:text-gray-300 border-2 border-gray-200 dark:border-slate-600'"
                                    class="px-6 py-4 rounded-2xl font-bold transition-all duration-300 hover:scale-105 flex items-center justify-center gap-3">
                                <i class="fas fa-cloud-upload-alt text-2xl"></i>
                                <span>อัพโหลดไฟล์</span>
                            </button>
                            <button type="button" @click="sourceType = 'url'"
                                    :class="sourceType === 'url' ? 'bg-gradient-to-r from-[#00B900] to-[#00E600] text-white shadow-2xl shadow-green-500/40 scale-105' : 'bg-white dark:bg-slate-700 text-gray-700 dark:text-gray-300 border-2 border-gray-200 dark:border-slate-600'"
                                    class="px-6 py-4 rounded-2xl font-bold transition-all duration-300 hover:scale-105 flex items-center justify-center gap-3">
                                <i class="fas fa-link text-2xl"></i>
                                <span>URL ลิงก์</span>
                            </button>
                        </div>
                        <input type="hidden" name="source_type" x-model="sourceType">
                    </div>

                    <!-- File Upload Area -->
                    <div x-show="sourceType === 'upload'" x-transition class="space-y-3">
                        <label class="flex items-center gap-2 text-sm font-bold text-gray-700 dark:text-gray-300">
                            <i class="fas fa-file-upload text-[#00B900]"></i>
                            เลือกไฟล์
                            <span class="text-red-500">*</span>
                        </label>
                        <div class="relative border-3 border-dashed border-gray-300 dark:border-slate-600 rounded-3xl p-12 bg-gradient-to-br from-green-50 via-emerald-50 to-teal-50 dark:from-green-900/10 dark:via-emerald-900/10 dark:to-teal-900/10 hover:border-[#00B900] dark:hover:border-[#00E600] transition-all duration-300">
                            <input type="file" name="file" accept="image/*,video/*,.gif,.json"
                                   @change="handleFileSelect($event)"
                                   class="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-10"
                                   :required="sourceType === 'upload'">
                            <div class="text-center">
                                <div class="w-24 h-24 rounded-full bg-gradient-to-br from-[#00B900] to-[#00E600] mx-auto mb-6 flex items-center justify-center shadow-2xl shadow-green-500/40 animate-pulse">
                                    <i class="fas fa-cloud-upload-alt text-white text-4xl"></i>
                                </div>
                                <p class="text-2xl font-bold text-gray-700 dark:text-gray-300 mb-3">คลิกหรือลากไฟล์มาที่นี่</p>
                                <p class="text-base text-gray-600 dark:text-gray-400 mb-4">รองรับ JPG, PNG, GIF, MP4, WebM, JSON (Lottie)</p>
                                <div class="flex items-center justify-center gap-4 text-sm text-gray-500 dark:text-gray-400">
                                    <span class="px-3 py-1 bg-white dark:bg-slate-700 rounded-full"><i class="fas fa-file-image mr-1"></i>รูปภาพ</span>
                                    <span class="px-3 py-1 bg-white dark:bg-slate-700 rounded-full"><i class="fas fa-file-video mr-1"></i>วิดีโอ</span>
                                    <span class="px-3 py-1 bg-white dark:bg-slate-700 rounded-full"><i class="fas fa-code mr-1"></i>JSON</span>
                                </div>
                                <p class="text-xs text-gray-400 dark:text-gray-500 mt-4"><i class="fas fa-info-circle mr-1"></i>ขนาดไฟล์สูงสุด: 5MB</p>

                                <!-- File Selected Indicator -->
                                <div x-show="showPreview" class="mt-6 p-4 bg-white dark:bg-slate-700 rounded-2xl shadow-lg">
                                    <div class="flex items-center gap-3">
                                        <i class="fas fa-check-circle text-green-600 text-2xl"></i>
                                        <div class="flex-1 text-left">
                                            <p class="font-bold text-gray-900 dark:text-white" x-text="fileName"></p>
                                            <p class="text-sm text-gray-600 dark:text-gray-400"><span x-text="fileSize"></span> KB</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- URL Input -->
                    <div x-show="sourceType === 'url'" x-transition x-cloak class="space-y-3">
                        <label class="flex items-center gap-2 text-sm font-bold text-gray-700 dark:text-gray-300">
                            <i class="fas fa-link text-[#00B900]"></i>
                            URL ลิงก์ไฟล์
                            <span class="text-red-500">*</span>
                        </label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-6 flex items-center pointer-events-none">
                                <i class="fas fa-globe text-gray-400 dark:text-gray-500 text-xl"></i>
                            </div>
                            <input type="url" name="file_url"
                                   placeholder="https://example.com/avatar.png"
                                   :required="sourceType === 'url'"
                                   class="w-full pl-16 pr-6 py-4 text-lg border-2 border-gray-200 dark:border-slate-600 bg-white dark:bg-slate-700 text-gray-900 dark:text-white rounded-2xl focus:ring-4 focus:ring-green-500/20 focus:border-[#00B900] transition-all duration-300 placeholder-gray-400 dark:placeholder-gray-500">
                        </div>
                    </div>

                    <!-- Description -->
                    <div class="space-y-3">
                        <label class="flex items-center gap-2 text-sm font-bold text-gray-700 dark:text-gray-300">
                            <i class="fas fa-align-left text-[#00B900]"></i>
                            คำอธิบาย (ไม่บังคับ)
                        </label>
                        <textarea name="description" rows="4"
                                  placeholder="อธิบายเกี่ยวกับ Avatar นี้... เช่น ใช้สำหรับกรณีใด, สีสัน, บุคลิก"
                                  class="w-full px-6 py-4 text-base border-2 border-gray-200 dark:border-slate-600 bg-white dark:bg-slate-700 text-gray-900 dark:text-white rounded-2xl focus:ring-4 focus:ring-green-500/20 focus:border-[#00B900] transition-all duration-300 placeholder-gray-400 dark:placeholder-gray-500">{{ old('description') }}</textarea>
                    </div>

                    <!-- Checkboxes -->
                    <div class="space-y-4">
                        <div class="bg-gradient-to-r from-green-50 to-emerald-50 dark:from-green-900/20 dark:to-emerald-900/20 border-2 border-green-200 dark:border-green-800 rounded-2xl p-6">
                            <label class="flex items-start gap-4 cursor-pointer group">
                                <input type="checkbox" name="is_active" value="1" checked
                                       class="mt-1 w-6 h-6 text-[#00B900] bg-white dark:bg-slate-700 border-2 border-gray-300 dark:border-slate-600 rounded-lg focus:ring-4 focus:ring-green-500/20">
                                <div class="flex-1">
                                    <p class="text-lg font-bold text-gray-800 dark:text-gray-200 group-hover:text-[#00B900] dark:group-hover:text-[#00E600] transition-colors">
                                        <i class="fas fa-toggle-on mr-2"></i>เปิดใช้งาน Avatar นี้
                                    </p>
                                    <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">Avatar จะพร้อมใช้งานทันทีหลังจากสร้าง</p>
                                </div>
                            </label>
                        </div>

                        <div class="bg-gradient-to-r from-yellow-50 to-orange-50 dark:from-yellow-900/20 dark:to-orange-900/20 border-2 border-yellow-200 dark:border-yellow-800 rounded-2xl p-6">
                            <label class="flex items-start gap-4 cursor-pointer group">
                                <input type="checkbox" name="is_default" value="1"
                                       class="mt-1 w-6 h-6 text-yellow-600 bg-white dark:bg-slate-700 border-2 border-gray-300 dark:border-slate-600 rounded-lg focus:ring-4 focus:ring-yellow-500/20">
                                <div class="flex-1">
                                    <p class="text-lg font-bold text-gray-800 dark:text-gray-200 group-hover:text-yellow-600 dark:group-hover:text-yellow-400 transition-colors">
                                        <i class="fas fa-star mr-2"></i>ตั้งเป็น Avatar เริ่มต้น
                                    </p>
                                    <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">Avatar นี้จะถูกใช้เป็นค่าเริ่มต้นสำหรับ LINE Bot</p>
                                </div>
                            </label>
                        </div>
                    </div>

                    <!-- Submit Buttons -->
                    <div class="flex items-center gap-4 pt-6 border-t-2 border-gray-200 dark:border-slate-700">
                        <a href="{{ route('admin.line-bot.avatar.index') }}"
                           class="flex-1 px-8 py-5 bg-gray-200 dark:bg-slate-700 text-gray-700 dark:text-gray-300 rounded-2xl hover:bg-gray-300 dark:hover:bg-slate-600 transition-all duration-300 text-center text-lg font-bold flex items-center justify-center gap-3">
                            <i class="fas fa-times-circle text-xl"></i>
                            <span>ยกเลิก</span>
                        </a>
                        <button type="submit"
                                class="flex-1 px-8 py-5 bg-gradient-to-r from-[#00B900] to-[#00E600] text-white rounded-2xl hover:shadow-2xl hover:shadow-green-500/50 transition-all duration-300 transform hover:-translate-y-1 text-center text-lg font-bold flex items-center justify-center gap-3">
                            <i class="fas fa-check-circle text-xl"></i>
                            <span>สร้าง Avatar</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Preview Section -->
        <div class="lg:col-span-1">
            <div class="bg-white dark:bg-slate-800 rounded-3xl shadow-2xl border border-gray-100 dark:border-slate-700 overflow-hidden sticky top-6">
                <div class="bg-gradient-to-r from-purple-600 to-pink-600 px-6 py-4">
                    <h3 class="text-xl font-bold text-white flex items-center gap-2">
                        <i class="fas fa-eye"></i>
                        ตัวอย่าง
                    </h3>
                </div>
                <div class="p-6">
                    <!-- Preview Area -->
                    <div class="aspect-square bg-gradient-to-br from-gray-100 to-gray-200 dark:from-slate-700 dark:to-slate-600 rounded-3xl flex items-center justify-center overflow-hidden mb-6 border-4 border-gray-300 dark:border-slate-600">
                        <div x-show="!previewUrl" class="text-center p-8">
                            <i class="fas fa-image text-gray-400 dark:text-gray-600 text-6xl mb-4"></i>
                            <p class="text-gray-500 dark:text-gray-400 font-semibold">อัพโหลดไฟล์เพื่อดูตัวอย่าง</p>
                        </div>
                        <img x-show="previewUrl && (avatarType === 'image' || avatarType === 'gif')"
                             :src="previewUrl"
                             alt="Preview"
                             class="w-full h-full object-cover">
                        <video x-show="previewUrl && avatarType === 'video'"
                               :src="previewUrl"
                               class="w-full h-full object-cover"
                               autoplay loop muted playsinline></video>
                        <div x-show="previewUrl && avatarType === 'lottie'" class="text-center">
                            <i class="fas fa-play-circle text-purple-600 dark:text-purple-400 text-6xl mb-4"></i>
                            <p class="text-gray-600 dark:text-gray-400 font-semibold">Lottie Animation</p>
                        </div>
                    </div>

                    <!-- Info Cards -->
                    <div class="space-y-3">
                        <div class="p-4 bg-blue-50 dark:bg-blue-900/20 rounded-2xl border border-blue-200 dark:border-blue-800">
                            <div class="flex items-center gap-3">
                                <i class="fas fa-info-circle text-blue-600 dark:text-blue-400 text-2xl"></i>
                                <div class="flex-1">
                                    <p class="text-sm font-bold text-blue-900 dark:text-blue-100">ประเภทที่รองรับ</p>
                                    <p class="text-xs text-blue-700 dark:text-blue-300 mt-1">รูปภาพ, GIF, วิดีโอ, Lottie</p>
                                </div>
                            </div>
                        </div>

                        <div class="p-4 bg-purple-50 dark:bg-purple-900/20 rounded-2xl border border-purple-200 dark:border-purple-800">
                            <div class="flex items-center gap-3">
                                <i class="fas fa-magic text-purple-600 dark:text-purple-400 text-2xl"></i>
                                <div class="flex-1">
                                    <p class="text-sm font-bold text-purple-900 dark:text-purple-100">ขนาดแนะนำ</p>
                                    <p class="text-xs text-purple-700 dark:text-purple-300 mt-1">500x500px หรือ 1:1 Ratio</p>
                                </div>
                            </div>
                        </div>

                        <div class="p-4 bg-green-50 dark:bg-green-900/20 rounded-2xl border border-green-200 dark:border-green-800">
                            <div class="flex items-center gap-3">
                                <i class="fas fa-check-circle text-green-600 dark:text-green-400 text-2xl"></i>
                                <div class="flex-1">
                                    <p class="text-sm font-bold text-green-900 dark:text-green-100">รองรับ LINE</p>
                                    <p class="text-xs text-green-700 dark:text-green-300 mt-1">เหมาะกับ LINE Official Account</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
[x-cloak] { display: none !important; }

@keyframes bounce {
    0%, 100% { transform: translateY(0); }
    50% { transform: translateY(-20px); }
}
</style>
@endsection
