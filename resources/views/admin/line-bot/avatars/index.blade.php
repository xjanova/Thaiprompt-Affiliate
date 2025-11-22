@extends('layouts.admin-v3')

@section('title', 'จัดการ LINE Avatar')

@section('content')
<div class="min-h-screen bg-gray-100/50 dark:bg-slate-900 py-6 px-4 sm:px-6 lg:px-8" x-data="{
    showUploadModal: false,
    uploadType: 'image',
    sourceType: 'upload',
    selectedAvatar: null,
    showPreview: false,
    filterType: 'all',
    get filteredAvatars() {
        if (this.filterType === 'all') return {{ Js::from($avatars) }};
        return {{ Js::from($avatars) }}.filter(avatar => avatar.type === this.filterType);
    }
}">

    <!-- LINE-Themed Header -->
    <div class="mb-8 relative overflow-hidden rounded-3xl bg-gradient-to-br from-[#00B900] via-[#00E600] to-[#00C900] p-8 shadow-2xl shadow-green-500/30">
        <!-- Decorative Background Pattern -->
        <div class="absolute inset-0 opacity-10">
            <div class="absolute top-0 left-0 w-64 h-64 glass-fusion rounded-full -translate-x-32 -translate-y-32 border border-white/20 dark:border-white/10"></div>
            <div class="absolute bottom-0 right-0 w-96 h-96 glass-fusion rounded-full translate-x-48 translate-y-48 border border-white/20 dark:border-white/10"></div>
        </div>

        <div class="relative flex flex-col md:flex-row items-start md:items-center justify-between gap-4">
            <div class="flex items-center gap-4">
                <div class="w-16 h-16 rounded-2xl glass-fusion backdrop-blur-md flex items-center justify-center shadow-xl border border-white/20 dark:border-white/10">
                    <svg class="w-10 h-10 text-white" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 3c1.66 0 3 1.34 3 3s-1.34 3-3 3-3-1.34-3-3 1.34-3 3-3zm0 14.2c-2.5 0-4.71-1.28-6-3.22.03-1.99 4-3.08 6-3.08 1.99 0 5.97 1.09 6 3.08-1.29 1.94-3.5 3.22-6 3.22z"/>
                    </svg>
                </div>
                <div>
                    <h1 class="text-4xl font-bold text-white mb-1 flex items-center gap-2">
                        จัดการ LINE Avatar
                    </h1>
                    <p class="text-white/90 text-lg">
                        อัพโหลดและจัดการภาพ Avatar สำหรับ LINE Bot
                    </p>
                </div>
            </div>
            <button @click="showUploadModal = true"
                    class="px-6 py-3 glass-fusion backdrop-blur-md border-2 border-white/40 text-white rounded-xl hover:glass-fusion transition-all duration-300 font-bold flex items-center gap-2 shadow-lg hover:shadow-xl hover:scale-105">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
                </svg>
                อัพโหลด Avatar
            </button>
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

    <!-- Filter Tabs -->
    <div class="mb-6 glass-fusion dark:bg-slate-800 rounded-2xl shadow-xl border border-gray-100 dark:border-slate-700 p-2">
        <div class="flex flex-wrap gap-2">
            <button @click="filterType = 'all'"
                    :class="filterType === 'all' ? 'bg-gradient-to-r from-[#00B900] to-[#00E600] text-white shadow-lg' : 'text-gray-600 dark:text-gray-400 hover:bg-gray-100/50 dark:hover:bg-slate-700'"
                    class="px-6 py-3 rounded-xl font-bold transition-all duration-300 flex items-center gap-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"/>
                </svg>
                ทั้งหมด ({{ count($avatars) }})
            </button>
            <button @click="filterType = 'image'"
                    :class="filterType === 'image' ? 'bg-gradient-to-r from-blue-500 to-cyan-500 text-white shadow-lg' : 'text-gray-600 dark:text-gray-400 hover:bg-gray-100/50 dark:hover:bg-slate-700'"
                    class="px-6 py-3 rounded-xl font-bold transition-all duration-300 flex items-center gap-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                </svg>
                รูปภาพ
            </button>
            <button @click="filterType = 'gif'"
                    :class="filterType === 'gif' ? 'bg-gradient-to-r from-purple-500 to-pink-500 text-white shadow-lg' : 'text-gray-600 dark:text-gray-400 hover:bg-gray-100/50 dark:hover:bg-slate-700'"
                    class="px-6 py-3 rounded-xl font-bold transition-all duration-300 flex items-center gap-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 4v16M17 4v16M3 8h4m10 0h4M3 12h18M3 16h4m10 0h4M4 20h16a1 1 0 001-1V5a1 1 0 00-1-1H4a1 1 0 00-1 1v14a1 1 0 001 1z"/>
                </svg>
                GIF
            </button>
            <button @click="filterType = 'lottie'"
                    :class="filterType === 'lottie' ? 'bg-gradient-to-r from-orange-500 to-red-500 text-white shadow-lg' : 'text-gray-600 dark:text-gray-400 hover:bg-gray-100/50 dark:hover:bg-slate-700'"
                    class="px-6 py-3 rounded-xl font-bold transition-all duration-300 flex items-center gap-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                Lottie
            </button>
            <button @click="filterType = 'video'"
                    :class="filterType === 'video' ? 'bg-gradient-to-r from-indigo-500 to-purple-500 text-white shadow-lg' : 'text-gray-600 dark:text-gray-400 hover:bg-gray-100/50 dark:hover:bg-slate-700'"
                    class="px-6 py-3 rounded-xl font-bold transition-all duration-300 flex items-center gap-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                </svg>
                วิดีโอ
            </button>
        </div>
    </div>

    <!-- Avatar Gallery -->
    @if(count($avatars) > 0)
        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-6 gap-4">
            @foreach($avatars as $avatar)
                <div x-show="filterType === 'all' || filterType === '{{ $avatar->type }}'"
                     class="group glass-fusion dark:bg-slate-800 rounded-2xl shadow-lg border border-gray-100 dark:border-slate-700 overflow-hidden hover:shadow-2xl hover:scale-105 transition-all duration-300">
                    <!-- Avatar Image/Preview -->
                    <div class="aspect-square bg-gradient-to-br from-gray-100 to-gray-200 dark:from-slate-700 dark:to-slate-600 flex items-center justify-center relative overflow-hidden">
                        @if($avatar->type === 'image' || $avatar->type === 'gif')
                            <img src="{{ $avatar->file_url ?? Storage::url($avatar->file_path) }}"
                                 alt="{{ $avatar->name }}"
                                 class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-300">
                        @elseif($avatar->type === 'lottie')
                            <div class="flex flex-col items-center justify-center gap-2 text-purple-500 dark:text-purple-400">
                                <svg class="w-16 h-16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                <p class="text-sm font-bold">Lottie</p>
                            </div>
                        @else
                            <video src="{{ $avatar->file_url ?? Storage::url($avatar->file_path) }}"
                                   class="w-full h-full object-cover"
                                   autoplay loop muted playsinline></video>
                        @endif

                        <!-- Default Badge -->
                        @if($avatar->is_default)
                            <div class="absolute top-2 right-2 px-3 py-1 bg-gradient-to-r from-[#00B900] to-[#00E600] text-white rounded-full text-xs font-bold shadow-lg flex items-center gap-1">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                </svg>
                                ค่าเริ่มต้น
                            </div>
                        @endif

                        <!-- Active Badge -->
                        @if($avatar->is_active)
                            <div class="absolute top-2 left-2 w-3 h-3 bg-green-500 rounded-full shadow-lg animate-pulse"></div>
                        @endif
                    </div>

                    <!-- Avatar Info -->
                    <div class="p-4">
                        <h3 class="font-bold text-gray-900 dark:text-white truncate mb-2">{{ $avatar->name }}</h3>

                        <div class="flex items-center justify-between mb-3">
                            <span class="px-3 py-1 rounded-full text-xs font-bold
                                @if($avatar->type === 'image') bg-blue-100 dark:bg-blue-900 text-blue-800 dark:text-blue-200
                                @elseif($avatar->type === 'gif') bg-purple-100 dark:bg-purple-900 text-purple-800 dark:text-purple-200
                                @elseif($avatar->type === 'lottie') bg-orange-100 dark:bg-orange-900 text-orange-800 dark:text-orange-200
                                @else bg-indigo-100 dark:bg-indigo-900 text-indigo-800 dark:text-indigo-200
                                @endif">
                                {{ strtoupper($avatar->type) }}
                            </span>

                            @if($avatar->file_size)
                                <span class="text-xs text-gray-500 dark:text-gray-400">
                                    {{ number_format($avatar->file_size / 1024, 1) }} KB
                                </span>
                            @endif
                        </div>

                        <!-- Actions -->
                        <div class="flex items-center gap-2">
                            @if(!$avatar->is_default)
                                <form action="{{ route('admin.line-bot.avatars.set-default', $avatar->id) }}" method="POST" class="flex-1">
                                    @csrf
                                    <button type="submit"
                                            class="w-full px-3 py-2 bg-gradient-to-r from-[#00B900] to-[#00E600] text-white rounded-xl hover:shadow-lg transition-all duration-300 text-sm font-bold flex items-center justify-center gap-1">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                        </svg>
                                        ตั้งค่าเริ่มต้น
                                    </button>
                                </form>
                            @else
                                <div class="flex-1 px-3 py-2 bg-gray-100/50 dark:bg-slate-700 text-gray-400 dark:text-gray-500 rounded-xl text-sm font-bold text-center">
                                    ค่าเริ่มต้น
                                </div>
                            @endif

                            <form action="{{ route('admin.line-bot.avatars.destroy', $avatar->id) }}" method="POST"
                                  onsubmit="return confirm('คุณแน่ใจหรือไม่ที่จะลบ Avatar นี้?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit"
                                        class="px-3 py-2 bg-red-500 text-white rounded-xl hover:bg-red-600 transition-all duration-300">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                    </svg>
                                </button>
                            </form>
                        </div>

                        @if($avatar->description)
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-2 line-clamp-2">{{ $avatar->description }}</p>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    @else
        <!-- Empty State -->
        <div class="glass-fusion dark:bg-slate-800 rounded-3xl shadow-xl border border-gray-100 dark:border-slate-700 p-12 text-center">
            <div class="w-32 h-32 rounded-full bg-gradient-to-br from-[#00B900] to-[#00E600] opacity-20 flex items-center justify-center mx-auto mb-6">
                <svg class="w-16 h-16 text-gray-400 dark:text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 3c1.66 0 3 1.34 3 3s-1.34 3-3 3-3-1.34-3-3 1.34-3 3-3zm0 14.2c-2.5 0-4.71-1.28-6-3.22.03-1.99 4-3.08 6-3.08 1.99 0 5.97 1.09 6 3.08-1.29 1.94-3.5 3.22-6 3.22z"/>
                </svg>
            </div>
            <h3 class="text-2xl font-bold text-gray-900 dark:text-white mb-2">ยังไม่มี Avatar</h3>
            <p class="text-gray-600 dark:text-gray-400 mb-6">เริ่มต้นด้วยการอัพโหลด Avatar แรกของคุณ</p>
            <button @click="showUploadModal = true"
                    class="px-6 py-3 bg-gradient-to-r from-[#00B900] to-[#00E600] text-white rounded-xl hover:shadow-2xl hover:shadow-green-500/50 transition-all duration-300 font-bold inline-flex items-center gap-2 transform hover:-translate-y-1">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
                </svg>
                อัพโหลด Avatar
            </button>
        </div>
    @endif

    <!-- Upload Modal -->
    <div x-show="showUploadModal"
         x-cloak
         @click.self="showUploadModal = false"
         class="fixed inset-0 bg-black/60 backdrop-blur-sm z-50 flex items-center justify-center p-4 animate-fade-in">
        <div class="glass-fusion dark:bg-slate-800 rounded-3xl shadow-2xl max-w-2xl w-full overflow-hidden border border-white/20 dark:border-white/10 transform transition-all duration-300"
             @click.away="showUploadModal = false">
            <!-- Modal Header -->
            <div class="bg-gradient-to-r from-[#00B900] to-[#00E600] px-6 py-5">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl glass-fusion backdrop-blur-sm flex items-center justify-center border border-white/20 dark:border-white/10">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
                            </svg>
                        </div>
                        <h3 class="text-2xl font-bold text-white">อัพโหลด Avatar ใหม่</h3>
                    </div>
                    <button @click="showUploadModal = false"
                            class="text-white/80 hover:text-white transition-colors">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>
            </div>

            <!-- Modal Body -->
            <form method="POST" action="{{ route('admin.line-bot.avatars.store') }}" enctype="multipart/form-data" class="p-6 space-y-6">
                @csrf

                <!-- Avatar Name -->
                <div>
                    <label class="flex items-center gap-2 text-sm font-bold text-gray-700 dark:text-gray-300 mb-2">
                        <svg class="w-5 h-5 text-[#00B900]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/>
                        </svg>
                        ชื่อ Avatar
                        <span class="text-red-500">*</span>
                    </label>
                    <input type="text" name="name" required
                           placeholder="เช่น: Bot Avatar แบบมีชีวิตชีวา"
                           class="w-full px-4 py-3 border-2 border-gray-200 dark:border-gray-700 dark:border-slate-600 glass-fusion dark:bg-slate-700 text-gray-900 dark:text-white rounded-xl focus:ring-4 focus:ring-green-500/20 focus:border-[#00B900] transition-all duration-300">
                </div>

                <!-- Type Selection -->
                <div>
                    <label class="flex items-center gap-2 text-sm font-bold text-gray-700 dark:text-gray-300 mb-3">
                        <svg class="w-5 h-5 text-[#00B900]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                        </svg>
                        ประเภท Avatar
                    </label>
                    <div class="grid grid-cols-4 gap-3">
                        <button type="button" @click="uploadType = 'image'"
                                :class="uploadType === 'image' ? 'bg-gradient-to-br from-blue-500 to-cyan-500 text-white shadow-lg scale-105' : 'bg-gray-100/50 dark:bg-slate-700 text-gray-600 dark:text-gray-400 hover:bg-gray-200 dark:hover:bg-slate-600'"
                                class="p-4 rounded-xl transition-all duration-300 flex flex-col items-center gap-2 transform">
                            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                            </svg>
                            <span class="text-xs font-bold">รูปภาพ</span>
                        </button>
                        <button type="button" @click="uploadType = 'gif'"
                                :class="uploadType === 'gif' ? 'bg-gradient-to-br from-purple-500 to-pink-500 text-white shadow-lg scale-105' : 'bg-gray-100/50 dark:bg-slate-700 text-gray-600 dark:text-gray-400 hover:bg-gray-200 dark:hover:bg-slate-600'"
                                class="p-4 rounded-xl transition-all duration-300 flex flex-col items-center gap-2 transform">
                            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 4v16M17 4v16M3 8h4m10 0h4M3 12h18M3 16h4m10 0h4M4 20h16a1 1 0 001-1V5a1 1 0 00-1-1H4a1 1 0 00-1 1v14a1 1 0 001 1z"/>
                            </svg>
                            <span class="text-xs font-bold">GIF</span>
                        </button>
                        <button type="button" @click="uploadType = 'lottie'"
                                :class="uploadType === 'lottie' ? 'bg-gradient-to-br from-orange-500 to-red-500 text-white shadow-lg scale-105' : 'bg-gray-100/50 dark:bg-slate-700 text-gray-600 dark:text-gray-400 hover:bg-gray-200 dark:hover:bg-slate-600'"
                                class="p-4 rounded-xl transition-all duration-300 flex flex-col items-center gap-2 transform">
                            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            <span class="text-xs font-bold">Lottie</span>
                        </button>
                        <button type="button" @click="uploadType = 'video'"
                                :class="uploadType === 'video' ? 'bg-gradient-to-br from-indigo-500 to-purple-500 text-white shadow-lg scale-105' : 'bg-gray-100/50 dark:bg-slate-700 text-gray-600 dark:text-gray-400 hover:bg-gray-200 dark:hover:bg-slate-600'"
                                class="p-4 rounded-xl transition-all duration-300 flex flex-col items-center gap-2 transform">
                            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                            </svg>
                            <span class="text-xs font-bold">วิดีโอ</span>
                        </button>
                    </div>
                    <input type="hidden" name="type" x-model="uploadType">
                </div>

                <!-- Source Type -->
                <div>
                    <label class="flex items-center gap-2 text-sm font-bold text-gray-700 dark:text-gray-300 mb-3">
                        แหล่งที่มา
                    </label>
                    <div class="flex gap-3">
                        <button type="button" @click="sourceType = 'upload'"
                                :class="sourceType === 'upload' ? 'bg-[#00B900] text-white' : 'bg-gray-100/50 dark:bg-slate-700 text-gray-600 dark:text-gray-400'"
                                class="flex-1 px-4 py-3 rounded-xl font-bold transition-all duration-300">
                            อัพโหลดไฟล์
                        </button>
                        <button type="button" @click="sourceType = 'url'"
                                :class="sourceType === 'url' ? 'bg-[#00B900] text-white' : 'bg-gray-100/50 dark:bg-slate-700 text-gray-600 dark:text-gray-400'"
                                class="flex-1 px-4 py-3 rounded-xl font-bold transition-all duration-300">
                            URL ลิงก์
                        </button>
                    </div>
                    <input type="hidden" name="source_type" x-model="sourceType">
                </div>

                <!-- File Upload -->
                <div x-show="sourceType === 'upload'">
                    <label class="flex items-center gap-2 text-sm font-bold text-gray-700 dark:text-gray-300 mb-2">
                        อัพโหลดไฟล์
                    </label>
                    <input type="file" name="file" accept="image/*,video/*,.gif,.json"
                           class="w-full px-4 py-3 border-2 border-dashed border-gray-300 dark:border-slate-600 bg-gray-100/50 dark:bg-slate-700 text-gray-900 dark:text-white rounded-xl focus:ring-4 focus:ring-green-500/20 focus:border-[#00B900] transition-all duration-300">
                    <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">รองรับ: JPG, PNG, GIF, MP4, WebM, JSON (Lottie) • สูงสุด 5MB</p>
                </div>

                <!-- URL Input -->
                <div x-show="sourceType === 'url'" x-cloak>
                    <label class="flex items-center gap-2 text-sm font-bold text-gray-700 dark:text-gray-300 mb-2">
                        URL ลิงก์
                    </label>
                    <input type="url" name="file_url" placeholder="https://example.com/avatar.png"
                           class="w-full px-4 py-3 border-2 border-gray-200 dark:border-slate-600 glass-fusion dark:bg-slate-700 text-gray-900 dark:text-white rounded-xl focus:ring-4 focus:ring-green-500/20 focus:border-[#00B900] transition-all duration-300">
                </div>

                <!-- Description -->
                <div>
                    <label class="flex items-center gap-2 text-sm font-bold text-gray-700 dark:text-gray-300 mb-2">
                        คำอธิบาย (ไม่บังคับ)
                    </label>
                    <textarea name="description" rows="3" placeholder="อธิบายเกี่ยวกับ Avatar นี้..."
                              class="w-full px-4 py-3 border-2 border-gray-200 dark:border-slate-600 glass-fusion dark:bg-slate-700 text-gray-900 dark:text-white rounded-xl focus:ring-4 focus:ring-green-500/20 focus:border-[#00B900] transition-all duration-300"></textarea>
                </div>

                <!-- Checkboxes -->
                <div class="space-y-3">
                    <label class="flex items-center gap-3 cursor-pointer group">
                        <input type="checkbox" name="is_active" value="1" checked
                               class="w-5 h-5 text-[#00B900] bg-gray-100/50 border-gray-300 dark:border-gray-600 rounded focus:ring-green-500 dark:focus:ring-green-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700">
                        <span class="text-sm font-semibold text-gray-700 dark:text-gray-300 group-hover:text-[#00B900] dark:group-hover:text-[#00E600]">เปิดใช้งาน Avatar นี้</span>
                    </label>
                    <label class="flex items-center gap-3 cursor-pointer group">
                        <input type="checkbox" name="is_default" value="1"
                               class="w-5 h-5 text-[#00B900] bg-gray-100/50 border-gray-300 dark:border-gray-600 rounded focus:ring-green-500 dark:focus:ring-green-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700">
                        <span class="text-sm font-semibold text-gray-700 dark:text-gray-300 group-hover:text-[#00B900] dark:group-hover:text-[#00E600]">ตั้งเป็น Avatar เริ่มต้น</span>
                    </label>
                </div>

                <!-- Actions -->
                <div class="flex gap-3 pt-4 border-t border-gray-200 dark:border-slate-700">
                    <button type="button" @click="showUploadModal = false"
                            class="flex-1 px-6 py-3 bg-gray-200 dark:bg-slate-700 text-gray-700 dark:text-gray-300 rounded-xl hover:bg-gray-300 dark:hover:bg-slate-600 transition-all duration-300 font-bold">
                        ยกเลิก
                    </button>
                    <button type="submit"
                            class="flex-1 px-6 py-3 bg-gradient-to-r from-[#00B900] to-[#00E600] text-white rounded-xl hover:shadow-2xl hover:shadow-green-500/50 transition-all duration-300 font-bold flex items-center justify-center gap-2 transform hover:-translate-y-1">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
                        </svg>
                        อัพโหลด
                    </button>
                </div>
            </form>
        </div>
    </div>

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
