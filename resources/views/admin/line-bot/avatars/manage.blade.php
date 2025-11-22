@extends('layouts.admin-v3')

@section('title', 'จัดการ LINE Avatar ขั้นสูง')

@section('content')
<div class="min-h-screen bg-gray-100/50 dark:bg-slate-900 py-6 px-4 sm:px-6 lg:px-8" x-data="{
    selectedAvatars: [],
    viewMode: 'grid',
    showBulkActions: false,
    sortBy: 'created_at',
    sortOrder: 'desc',
    searchQuery: '',
    get allSelected() {
        return this.selectedAvatars.length === {{ count($avatars ?? []) }};
    },
    toggleAll() {
        if (this.allSelected) {
            this.selectedAvatars = [];
        } else {
            this.selectedAvatars = {{ Js::from($avatars->pluck('id') ?? []) }};
        }
    },
    toggleSelection(id) {
        const index = this.selectedAvatars.indexOf(id);
        if (index > -1) {
            this.selectedAvatars.splice(index, 1);
        } else {
            this.selectedAvatars.push(id);
        }
    },
    isSelected(id) {
        return this.selectedAvatars.includes(id);
    }
}">

    <!-- Header -->
    <div class="mb-8">
        <div class="flex items-center gap-4 mb-4">
            <a href="{{ route('admin.line-bot.avatars.index') }}"
               class="flex items-center justify-center w-12 h-12 rounded-xl glass-fusion dark:bg-slate-800 shadow-lg hover:shadow-xl transition-all duration-300 group">
                <svg class="w-6 h-6 text-gray-600 dark:text-gray-400 dark:text-gray-300 group-hover:text-[#00B900] transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                </svg>
            </a>
            <div class="flex-1">
                <h1 class="text-3xl font-bold bg-gradient-to-r from-[#00B900] to-[#00E600] bg-clip-text text-transparent">
                    จัดการ Avatar ขั้นสูง
                </h1>
                <p class="text-gray-600 dark:text-gray-400 mt-1">
                    จัดการ Avatar แบบกลุ่ม พร้อมฟีเจอร์ขั้นสูง
                </p>
            </div>
        </div>
    </div>

    <!-- Toolbar -->
    <div class="mb-6 glass-fusion dark:bg-slate-800 rounded-2xl shadow-xl border border-gray-100 dark:border-slate-700 p-4">
        <div class="flex flex-col lg:flex-row items-center gap-4">
            <!-- Search -->
            <div class="flex-1 w-full">
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                    </div>
                    <input type="text" x-model="searchQuery" placeholder="ค้นหา Avatar..."
                           class="w-full pl-10 pr-4 py-3 border-2 border-gray-200 dark:border-gray-700 dark:border-slate-600 glass-fusion dark:bg-slate-700 text-gray-900 dark:text-white rounded-xl focus:ring-4 focus:ring-green-500/20 focus:border-[#00B900] transition-all duration-300">
                </div>
            </div>

            <!-- View Mode Toggle -->
            <div class="flex items-center gap-2 bg-gray-100/50 dark:bg-slate-700 rounded-xl p-1">
                <button @click="viewMode = 'grid'"
                        :class="viewMode === 'grid' ? 'glass-fusion dark:bg-slate-600 shadow-md' : 'text-gray-500 dark:text-gray-400'"
                        class="px-4 py-2 rounded-xl transition-all duration-300">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/>
                    </svg>
                </button>
                <button @click="viewMode = 'list'"
                        :class="viewMode === 'list' ? 'glass-fusion dark:bg-slate-600 shadow-md' : 'text-gray-500 dark:text-gray-400'"
                        class="px-4 py-2 rounded-xl transition-all duration-300">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                    </svg>
                </button>
            </div>

            <!-- Select All -->
            <label class="flex items-center gap-3 cursor-pointer bg-gray-100/50 dark:bg-slate-700 px-4 py-3 rounded-xl hover:bg-gray-200 dark:hover:bg-slate-600 transition-all duration-300">
                <input type="checkbox" :checked="allSelected" @change="toggleAll()"
                       class="w-5 h-5 text-[#00B900] bg-gray-100/50 dark:bg-gray-800/50 border-gray-300 dark:border-gray-600 rounded focus:ring-green-500">
                <span class="text-sm font-bold text-gray-700 dark:text-gray-300">เลือกทั้งหมด</span>
            </label>
        </div>

        <!-- Bulk Actions Bar -->
        <div x-show="selectedAvatars.length > 0" x-transition class="mt-4 p-4 bg-gradient-to-r from-[#00B900]/10 to-[#00E600]/10 dark:from-[#00B900]/20 dark:to-[#00E600]/20 rounded-xl border-2 border-[#00B900]/30">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-full bg-gradient-to-r from-[#00B900] to-[#00E600] flex items-center justify-center">
                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                    </div>
                    <div>
                        <p class="font-bold text-gray-900 dark:text-white">
                            เลือกแล้ว <span x-text="selectedAvatars.length"></span> รายการ
                        </p>
                        <p class="text-sm text-gray-600 dark:text-gray-400">เลือกการดำเนินการที่ต้องการ</p>
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <button @click="selectedAvatars = []"
                            class="px-4 py-2 glass-fusion dark:bg-slate-700 text-gray-700 dark:text-gray-300 rounded-xl hover:bg-gray-100/50 dark:hover:bg-slate-600 transition-all duration-300 font-bold flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                        ยกเลิก
                    </button>
                    <button onclick="if(confirm('คุณแน่ใจหรือไม่ที่จะลบ Avatar ที่เลือก?')) { alert('Bulk delete feature coming soon!') }"
                            class="px-4 py-2 bg-red-500 text-white rounded-xl hover:bg-red-600 transition-all duration-300 font-bold flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                        </svg>
                        ลบที่เลือก
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Grid View -->
    <div x-show="viewMode === 'grid'" class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-6 gap-4">
        @foreach($avatars ?? [] as $avatar)
            <div class="group glass-fusion dark:bg-slate-800 rounded-2xl shadow-lg border border-gray-100 dark:border-slate-700 overflow-hidden transition-all duration-300 relative transform hover:shadow-2xl hover:scale-105">
                <!-- Selection Checkbox -->
                <div class="absolute top-2 left-2 z-10">
                    <label class="flex items-center justify-center w-8 h-8 rounded-full cursor-pointer"
                           :class="isSelected({{ $avatar->id }}) ? 'bg-gradient-to-r from-[#00B900] to-[#00E600]' : 'glass-fusion dark:bg-slate-800/80 backdrop-blur-sm'">
                        <input type="checkbox" class="hidden" :checked="isSelected({{ $avatar->id }})" @change="toggleSelection({{ $avatar->id }})">
                        <svg x-show="isSelected({{ $avatar->id }})" class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/>
                        </svg>
                        <svg x-show="!isSelected({{ $avatar->id }})" class="w-5 h-5 text-gray-400 dark:text-gray-600 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <circle cx="12" cy="12" r="10" stroke-width="2"/>
                        </svg>
                    </label>
                </div>

                <!-- Avatar Preview -->
                <div class="aspect-square bg-gradient-to-br from-gray-100 to-gray-200 dark:from-slate-700 dark:to-slate-600 flex items-center justify-center relative overflow-hidden">
                    @if($avatar->type === 'image' || $avatar->type === 'gif')
                        <img src="{{ $avatar->file_url ?? Storage::url($avatar->file_path) }}"
                             alt="{{ $avatar->name }}"
                             class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-300">
                    @elseif($avatar->type === 'lottie')
                        <div class="flex flex-col items-center justify-center gap-2 text-purple-500">
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

                    @if($avatar->is_default)
                        <div class="absolute top-2 right-2 px-2 py-1 bg-gradient-to-r from-[#00B900] to-[#00E600] text-white rounded-full text-xs font-bold shadow-lg">
                            ค่าเริ่มต้น
                        </div>
                    @endif
                </div>

                <!-- Avatar Info -->
                <div class="p-4">
                    <h3 class="font-bold text-gray-900 dark:text-white truncate mb-2">{{ $avatar->name }}</h3>
                    <div class="flex items-center justify-between">
                        <span class="px-2 py-1 rounded-full text-xs font-bold
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
                </div>
            </div>
        @endforeach
    </div>

    <!-- List View -->
    <div x-show="viewMode === 'list'" x-cloak class="glass-fusion dark:bg-slate-800 rounded-2xl shadow-xl border border-gray-100 dark:border-slate-700 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gradient-to-r from-[#00B900] to-[#00E600]">
                    <tr>
                        <th class="px-6 py-4 text-left">
                            <input type="checkbox" :checked="allSelected" @change="toggleAll()"
                                   class="w-5 h-5 text-white glass-fusion border-white/30 rounded focus:ring-green-500">
                        </th>
                        <th class="px-6 py-4 text-left text-white font-bold">ตัวอย่าง</th>
                        <th class="px-6 py-4 text-left text-white font-bold">ชื่อ</th>
                        <th class="px-6 py-4 text-left text-white font-bold">ประเภท</th>
                        <th class="px-6 py-4 text-left text-white font-bold">ขนาด</th>
                        <th class="px-6 py-4 text-left text-white font-bold">สถานะ</th>
                        <th class="px-6 py-4 text-left text-white font-bold">สร้างเมื่อ</th>
                        <th class="px-6 py-4 text-center text-white font-bold">การจัดการ</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-slate-700">
                    @foreach($avatars ?? [] as $avatar)
                        <tr class="hover:bg-gray-100/50 dark:bg-gray-800/50/50 dark:bg-gray-800/50 dark:hover:bg-slate-700/50 transition-colors duration-200"
                            :class="isSelected({{ $avatar->id }}) ? 'bg-green-50 dark:bg-green-900/20' : ''">
                            <td class="px-6 py-4">
                                <input type="checkbox" :checked="isSelected({{ $avatar->id }})" @change="toggleSelection({{ $avatar->id }})"
                                       class="w-5 h-5 text-[#00B900] bg-gray-100/50 dark:bg-gray-800/50 border-gray-300 dark:border-gray-600 rounded focus:ring-green-500">
                            </td>
                            <td class="px-6 py-4">
                                <div class="w-16 h-16 rounded-xl overflow-hidden bg-gray-100/50 dark:bg-slate-700 flex items-center justify-center">
                                    @if($avatar->type === 'image' || $avatar->type === 'gif')
                                        <img src="{{ $avatar->file_url ?? Storage::url($avatar->file_path) }}"
                                             alt="{{ $avatar->name }}"
                                             class="w-full h-full object-cover">
                                    @elseif($avatar->type === 'lottie')
                                        <svg class="w-8 h-8 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"/>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                        </svg>
                                    @else
                                        <video src="{{ $avatar->file_url ?? Storage::url($avatar->file_path) }}"
                                               class="w-full h-full object-cover"
                                               autoplay loop muted playsinline></video>
                                    @endif
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <div>
                                    <p class="font-bold text-gray-900 dark:text-white">{{ $avatar->name }}</p>
                                    @if($avatar->description)
                                        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1 line-clamp-1">{{ $avatar->description }}</p>
                                    @endif
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <span class="px-3 py-1 rounded-full text-xs font-bold
                                    @if($avatar->type === 'image') bg-blue-100 dark:bg-blue-900 text-blue-800 dark:text-blue-200
                                    @elseif($avatar->type === 'gif') bg-purple-100 dark:bg-purple-900 text-purple-800 dark:text-purple-200
                                    @elseif($avatar->type === 'lottie') bg-orange-100 dark:bg-orange-900 text-orange-800 dark:text-orange-200
                                    @else bg-indigo-100 dark:bg-indigo-900 text-indigo-800 dark:text-indigo-200
                                    @endif">
                                    {{ strtoupper($avatar->type) }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-gray-600 dark:text-gray-400 text-sm">
                                @if($avatar->file_size)
                                    {{ number_format($avatar->file_size / 1024, 1) }} KB
                                @else
                                    -
                                @endif
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-2">
                                    @if($avatar->is_active)
                                        <span class="px-3 py-1 bg-green-100 dark:bg-green-900 text-green-800 dark:text-green-200 rounded-full text-xs font-bold flex items-center gap-1">
                                            <span class="w-2 h-2 bg-green-500 rounded-full animate-pulse"></span>
                                            เปิดใช้งาน
                                        </span>
                                    @else
                                        <span class="px-3 py-1 bg-gray-100/50 dark:bg-gray-900 text-gray-600 dark:text-gray-400 rounded-full text-xs font-bold">
                                            ปิดใช้งาน
                                        </span>
                                    @endif
                                    @if($avatar->is_default)
                                        <span class="px-3 py-1 bg-gradient-to-r from-[#00B900] to-[#00E600] text-white rounded-full text-xs font-bold">
                                            ค่าเริ่มต้น
                                        </span>
                                    @endif
                                </div>
                            </td>
                            <td class="px-6 py-4 text-gray-600 dark:text-gray-400 text-sm">
                                {{ $avatar->created_at->format('d/m/Y H:i') }}
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex items-center justify-center gap-2">
                                    <a href="{{ route('admin.line-bot.avatars.edit', $avatar->id) }}"
                                       class="px-3 py-2 bg-blue-500 text-white rounded-xl hover:bg-blue-600 transition-all duration-300">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                        </svg>
                                    </a>
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
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <!-- Empty State -->
    @if(count($avatars ?? []) === 0)
        <div class="glass-fusion dark:bg-slate-800 rounded-3xl shadow-xl border border-gray-100 dark:border-slate-700 p-12 text-center">
            <div class="w-32 h-32 rounded-full bg-gradient-to-br from-[#00B900] to-[#00E600] opacity-20 flex items-center justify-center mx-auto mb-6">
                <svg class="w-16 h-16 text-gray-400 dark:text-gray-600 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 3c1.66 0 3 1.34 3 3s-1.34 3-3 3-3-1.34-3-3 1.34-3 3-3zm0 14.2c-2.5 0-4.71-1.28-6-3.22.03-1.99 4-3.08 6-3.08 1.99 0 5.97 1.09 6 3.08-1.29 1.94-3.5 3.22-6 3.22z"/>
                </svg>
            </div>
            <h3 class="text-2xl font-bold text-gray-900 dark:text-white mb-2">ยังไม่มี Avatar</h3>
            <p class="text-gray-600 dark:text-gray-400 mb-6">กลับไปหน้าแรกเพื่ออัพโหลด Avatar</p>
            <a href="{{ route('admin.line-bot.avatars.index') }}"
               class="inline-flex items-center gap-2 px-6 py-3 bg-gradient-to-r from-[#00B900] to-[#00E600] text-white rounded-xl hover:shadow-2xl hover:shadow-green-500/50 transition-all duration-300 transform hover:-translate-y-1 font-bold">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                </svg>
                กลับไปหน้าแรก
            </a>
        </div>
    @endif

    <!-- Statistics Panel -->
    <div class="mt-8 grid grid-cols-1 md:grid-cols-4 gap-4">
        <div class="bg-gradient-to-br from-blue-500 to-cyan-500 rounded-2xl p-6 text-white shadow-xl">
            <div class="flex items-center justify-between mb-2">
                <h3 class="font-bold text-white/80">ทั้งหมด</h3>
                <svg class="w-8 h-8 text-white/60" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                </svg>
            </div>
            <p class="text-4xl font-bold">{{ count($avatars ?? []) }}</p>
        </div>

        <div class="bg-gradient-to-br from-green-500 to-emerald-500 rounded-2xl p-6 text-white shadow-xl">
            <div class="flex items-center justify-between mb-2">
                <h3 class="font-bold text-white/80">เปิดใช้งาน</h3>
                <svg class="w-8 h-8 text-white/60" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
            <p class="text-4xl font-bold">{{ collect($avatars ?? [])->where('is_active', true)->count() }}</p>
        </div>

        <div class="bg-gradient-to-br from-purple-500 to-pink-500 rounded-2xl p-6 text-white shadow-xl">
            <div class="flex items-center justify-between mb-2">
                <h3 class="font-bold text-white/80">ขนาดรวม</h3>
                <svg class="w-8 h-8 text-white/60" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4"/>
                </svg>
            </div>
            <p class="text-4xl font-bold">
                {{ number_format(collect($avatars ?? [])->sum('file_size') / 1024 / 1024, 2) }} MB
            </p>
        </div>

        <div class="bg-gradient-to-br from-orange-500 to-red-500 rounded-2xl p-6 text-white shadow-xl">
            <div class="flex items-center justify-between mb-2">
                <h3 class="font-bold text-white/80">เลือกแล้ว</h3>
                <svg class="w-8 h-8 text-white/60" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                </svg>
            </div>
            <p class="text-4xl font-bold" x-text="selectedAvatars.length"></p>
        </div>
    </div>

</div>

<style>
[x-cloak] { display: none !important; }
</style>
@endsection
