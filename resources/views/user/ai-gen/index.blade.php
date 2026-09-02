@extends('layouts.user-v4')

@section('title', 'AI Gen - สร้างสรรค์ผลงานด้วย AI')

@section('content')
<div x-data="aiGenApp()" x-init="init()" class="min-h-screen">

    {{-- ===== Hero Header ===== --}}
    <div class="relative overflow-hidden bg-gradient-to-br from-violet-600 via-purple-600 to-fuchsia-600 dark:from-violet-800 dark:via-purple-900 dark:to-fuchsia-900 rounded-2xl shadow-2xl p-6 md:p-10 mb-6">
        {{-- พื้นหลังอนิเมชัน --}}
        <div class="absolute inset-0 overflow-hidden pointer-events-none">
            <div class="absolute -top-20 -right-20 w-80 h-80 bg-white/10 rounded-full blur-3xl animate-pulse"></div>
            <div class="absolute -bottom-20 -left-20 w-80 h-80 bg-pink-300/10 rounded-full blur-3xl animate-pulse" style="animation-delay:1s"></div>
            <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[600px] h-[600px] bg-gradient-to-r from-violet-500/5 to-fuchsia-500/5 rounded-full blur-3xl animate-spin" style="animation-duration:30s"></div>
        </div>
        {{-- ไอคอนลอย --}}
        <div class="absolute top-8 right-12 text-white/10 text-7xl hidden md:block" style="animation:aiFloat 6s ease-in-out infinite">
            <i class="fas fa-wand-magic-sparkles"></i>
        </div>
        <div class="absolute bottom-8 right-40 text-white/5 text-5xl hidden lg:block" style="animation:aiFloat 8s ease-in-out infinite 2s">
            <i class="fas fa-palette"></i>
        </div>

        <div class="relative z-10">
            <div class="flex flex-col md:flex-row items-center gap-6 md:gap-10">
                <div class="flex-1 text-center md:text-left">
                    <div class="inline-flex items-center gap-2 px-4 py-2 bg-white/15 backdrop-blur-sm border border-white/20 rounded-full text-white/90 text-sm mb-4">
                        <i class="fas fa-sparkles text-yellow-300"></i>
                        <span>AI Image Generation</span>
                    </div>
                    <h1 class="text-3xl md:text-5xl font-extrabold text-white mb-3 leading-tight">
                        สร้างสรรค์ผลงาน<br>
                        <span class="bg-clip-text text-transparent bg-gradient-to-r from-pink-200 to-yellow-200">สุดล้ำด้วย AI</span>
                    </h1>
                    <p class="text-purple-100 text-base md:text-lg mb-6 max-w-lg">
                        เปลี่ยนไอเดียของคุณให้เป็นภาพสวยงาม ด้วยเทคโนโลยี AI ล่าสุด
                    </p>
                    <button @click="showCreateModal = true"
                        class="inline-flex items-center gap-2.5 px-8 py-4 bg-white/20 hover:bg-white/30 backdrop-blur-sm border border-white/30 text-white rounded-xl transition-all duration-300 font-bold text-lg shadow-lg hover:shadow-xl hover:scale-105 active:scale-95">
                        <i class="fas fa-magic"></i>
                        <span>เริ่มสร้างเลย</span>
                    </button>
                </div>
                {{-- สถิติมินิ --}}
                <div class="grid grid-cols-2 gap-3 md:gap-4 w-full md:w-auto md:min-w-[280px]">
                    <div class="bg-white/10 backdrop-blur-sm border border-white/20 rounded-xl p-4 text-center hover:bg-white/15 transition">
                        <div class="text-3xl font-bold text-white" x-text="stats.remainingImages == -1 ? '∞' : stats.remainingImages">{{ ($stats['remaining_images'] ?? 0) == -1 ? '∞' : ($stats['remaining_images'] ?? 0) }}</div>
                        <div class="text-purple-200 text-xs mt-1"><i class="fas fa-image mr-1"></i>เครดิตภาพ</div>
                    </div>
                    <div class="bg-white/10 backdrop-blur-sm border border-white/20 rounded-xl p-4 text-center hover:bg-white/15 transition">
                        <div class="text-3xl font-bold text-white" x-text="stats.remainingVideos == -1 ? '∞' : stats.remainingVideos">{{ ($stats['remaining_videos'] ?? 0) == -1 ? '∞' : ($stats['remaining_videos'] ?? 0) }}</div>
                        <div class="text-purple-200 text-xs mt-1"><i class="fas fa-video mr-1"></i>เครดิตวิดีโอ</div>
                    </div>
                    <div class="bg-white/10 backdrop-blur-sm border border-white/20 rounded-xl p-4 text-center hover:bg-white/15 transition">
                        <div class="text-3xl font-bold text-white" x-text="stats.totalGenerated">{{ $stats['total_generated'] ?? 0 }}</div>
                        <div class="text-purple-200 text-xs mt-1"><i class="fas fa-chart-line mr-1"></i>สร้างแล้ว</div>
                    </div>
                    <div class="bg-white/10 backdrop-blur-sm border border-white/20 rounded-xl p-4 text-center hover:bg-white/15 transition">
                        <div class="text-2xl font-bold text-white" x-text="stats.planName">{{ $package_name ?? 'Free' }}</div>
                        <div class="text-purple-200 text-xs mt-1"><i class="fas fa-crown mr-1"></i>แพ็กเกจ</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ===== แท็บนำทาง ===== --}}
    <div class="flex items-center gap-2 mb-6 overflow-x-auto pb-2 scrollbar-hide">
        <template x-for="tab in tabs" :key="tab.id">
            <button @click="activeTab = tab.id"
                :class="activeTab === tab.id
                    ? 'bg-purple-600 text-white shadow-lg shadow-purple-500/25 dark:bg-purple-500'
                    : 'bg-white dark:bg-gray-800 text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 border border-gray-200 dark:border-gray-700'"
                class="inline-flex items-center gap-2 px-5 py-3 rounded-xl font-semibold text-sm transition-all duration-200 whitespace-nowrap">
                <i :class="tab.icon"></i>
                <span x-text="tab.label"></span>
            </button>
        </template>
        {{-- ปุ่มสร้างใหม่ --}}
        <button @click="showCreateModal = true"
            class="ml-auto inline-flex items-center gap-2 px-5 py-3 bg-gradient-to-r from-purple-600 to-fuchsia-600 hover:from-purple-700 hover:to-fuchsia-700 text-white rounded-xl font-semibold text-sm shadow-lg shadow-purple-500/25 transition-all duration-200 hover:scale-105 active:scale-95 whitespace-nowrap">
            <i class="fas fa-plus"></i>
            <span>สร้างใหม่</span>
        </button>
    </div>

    {{-- ===== แท็บ: ผลงานของฉัน ===== --}}
    <div x-show="activeTab === 'creations'" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 translate-y-2" x-transition:enter-end="opacity-100 translate-y-0">
        {{-- ตัวกรอง --}}
        <div class="flex flex-wrap items-center gap-2 mb-5">
            <template x-for="f in creationFilters" :key="f.value">
                <button @click="filterCreations(f.value)"
                    :class="currentFilter === f.value
                        ? 'bg-purple-100 dark:bg-purple-900/50 text-purple-700 dark:text-purple-300 border-purple-300 dark:border-purple-600'
                        : 'bg-white dark:bg-gray-800 text-gray-600 dark:text-gray-400 border-gray-200 dark:border-gray-700 hover:border-purple-300'"
                    class="inline-flex items-center gap-1.5 px-4 py-2 rounded-lg text-sm font-medium border transition">
                    <i :class="f.icon" class="text-xs"></i>
                    <span x-text="f.label"></span>
                </button>
            </template>
        </div>

        {{-- แกลเลอรี --}}
        <div x-show="!loadingCreations && generations.length > 0"
            class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-4">
            <template x-for="gen in generations" :key="gen.id">
                <div @click="viewGeneration(gen)"
                    class="tp-card group relative rounded-xl overflow-hidden hover:shadow-xl border border-gray-100 dark:border-gray-700 transition-all duration-300 cursor-pointer hover:-translate-y-1">
                    {{-- รูปภาพ --}}
                    <div class="aspect-square overflow-hidden bg-gray-100 dark:bg-gray-900">
                        <template x-if="gen.file_url || gen.thumbnail_url">
                            <img :src="gen.thumbnail_url || gen.file_url" :alt="gen.prompt"
                                class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-500"
                                loading="lazy">
                        </template>
                        <template x-if="!gen.file_url && !gen.thumbnail_url">
                            <div class="w-full h-full flex flex-col items-center justify-center text-gray-400 dark:text-gray-600">
                                <template x-if="gen.status === 'pending' || gen.status === 'processing'">
                                    <div class="text-center">
                                        <i class="fas fa-spinner fa-spin text-3xl text-purple-400 mb-2"></i>
                                        <p class="text-xs">กำลังสร้าง...</p>
                                    </div>
                                </template>
                                <template x-if="gen.status === 'failed'">
                                    <div class="text-center">
                                        <i class="fas fa-exclamation-triangle text-3xl text-red-400 mb-2"></i>
                                        <p class="text-xs text-red-400">ล้มเหลว</p>
                                    </div>
                                </template>
                                <template x-if="gen.status !== 'pending' && gen.status !== 'processing' && gen.status !== 'failed'">
                                    <div class="text-center">
                                        <i class="fas fa-image text-3xl mb-2"></i>
                                        <p class="text-xs">ไม่มีรูปภาพ</p>
                                    </div>
                                </template>
                            </div>
                        </template>
                    </div>
                    {{-- โอเวอร์เลย์ --}}
                    <div class="absolute inset-0 bg-gradient-to-t from-black/70 via-transparent to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300">
                        <div class="absolute bottom-0 left-0 right-0 p-3">
                            <p class="text-white text-xs line-clamp-2" x-text="gen.prompt"></p>
                            <div class="flex items-center justify-between mt-2">
                                <span class="text-white/70 text-[10px]" x-text="gen.provider?.name || ''"></span>
                                <div class="flex items-center gap-1">
                                    <template x-if="gen.is_favorite">
                                        <i class="fas fa-heart text-red-400 text-xs"></i>
                                    </template>
                                    <span class="text-white/70 text-[10px]" x-text="formatDate(gen.created_at)"></span>
                                </div>
                            </div>
                        </div>
                    </div>
                    {{-- แบดจ์ประเภท --}}
                    <div class="absolute top-2 left-2">
                        <span :class="gen.type === 'image' ? 'bg-blue-500' : 'bg-pink-500'"
                            class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-white text-[10px] font-medium backdrop-blur-sm bg-opacity-80">
                            <i :class="gen.type === 'image' ? 'fas fa-image' : 'fas fa-video'" class="text-[8px]"></i>
                            <span x-text="gen.type === 'image' ? 'ภาพ' : 'วิดีโอ'"></span>
                        </span>
                    </div>
                </div>
            </template>
        </div>

        {{-- สถานะว่าง --}}
        <div x-show="!loadingCreations && generations.length === 0"
            class="tp-card text-center py-16 rounded-2xl border border-gray-100 dark:border-gray-700">
            <div class="inline-flex items-center justify-center w-20 h-20 bg-purple-100 dark:bg-purple-900/30 rounded-full mb-4">
                <i class="fas fa-wand-magic-sparkles text-3xl text-purple-500"></i>
            </div>
            <h3 class="text-lg font-bold text-gray-800 dark:text-white mb-2">ยังไม่มีผลงาน</h3>
            <p class="text-gray-500 dark:text-gray-400 mb-6 text-sm">เริ่มสร้างสรรค์ผลงานสุดเจ๋งด้วย AI กันเลย!</p>
            <button @click="showCreateModal = true"
                class="inline-flex items-center gap-2 px-6 py-3 bg-gradient-to-r from-purple-600 to-fuchsia-600 text-white rounded-xl font-semibold text-sm hover:shadow-lg transition">
                <i class="fas fa-plus"></i> สร้างผลงานแรก
            </button>
        </div>

        {{-- กำลังโหลด --}}
        <div x-show="loadingCreations" class="flex flex-col items-center justify-center py-16">
            <div class="w-12 h-12 border-4 border-purple-200 dark:border-purple-800 border-t-purple-600 dark:border-t-purple-400 rounded-full animate-spin mb-4"></div>
            <p class="text-gray-500 dark:text-gray-400 text-sm">กำลังโหลดผลงาน...</p>
        </div>
    </div>

    {{-- ===== แท็บ: สำรวจ ===== --}}
    <div x-show="activeTab === 'explore'" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 translate-y-2" x-transition:enter-end="opacity-100 translate-y-0">
        <div class="flex flex-wrap items-center gap-2 mb-5">
            <template x-for="f in exploreFilters" :key="f.value">
                <button @click="filterExplore(f.value)"
                    :class="currentExploreFilter === f.value
                        ? 'bg-purple-100 dark:bg-purple-900/50 text-purple-700 dark:text-purple-300 border-purple-300 dark:border-purple-600'
                        : 'bg-white dark:bg-gray-800 text-gray-600 dark:text-gray-400 border-gray-200 dark:border-gray-700 hover:border-purple-300'"
                    class="inline-flex items-center gap-1.5 px-4 py-2 rounded-lg text-sm font-medium border transition">
                    <i :class="f.icon" class="text-xs"></i>
                    <span x-text="f.label"></span>
                </button>
            </template>
        </div>

        <div x-show="!loadingExplore && exploreItems.length > 0"
            class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-4">
            <template x-for="gen in exploreItems" :key="gen.id">
                <div @click="viewGeneration(gen)"
                    class="tp-card group relative rounded-xl overflow-hidden hover:shadow-xl border border-gray-100 dark:border-gray-700 transition-all duration-300 cursor-pointer hover:-translate-y-1">
                    <div class="aspect-square overflow-hidden bg-gray-100 dark:bg-gray-900">
                        <img :src="gen.thumbnail_url || gen.file_url" :alt="gen.prompt"
                            class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-500"
                            loading="lazy">
                    </div>
                    <div class="absolute inset-0 bg-gradient-to-t from-black/70 via-transparent to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300">
                        <div class="absolute bottom-0 left-0 right-0 p-3">
                            <p class="text-white text-xs line-clamp-2" x-text="gen.prompt"></p>
                        </div>
                    </div>
                </div>
            </template>
        </div>

        <div x-show="!loadingExplore && exploreItems.length === 0"
            class="tp-card text-center py-16 rounded-2xl border border-gray-100 dark:border-gray-700">
            <div class="inline-flex items-center justify-center w-20 h-20 bg-purple-100 dark:bg-purple-900/30 rounded-full mb-4">
                <i class="fas fa-compass text-3xl text-purple-500"></i>
            </div>
            <h3 class="text-lg font-bold text-gray-800 dark:text-white mb-2">ยังไม่มีผลงานให้สำรวจ</h3>
            <p class="text-gray-500 dark:text-gray-400 text-sm">ผลงานจากชุมชนจะปรากฏที่นี่</p>
        </div>

        <div x-show="loadingExplore" class="flex flex-col items-center justify-center py-16">
            <div class="w-12 h-12 border-4 border-purple-200 dark:border-purple-800 border-t-purple-600 dark:border-t-purple-400 rounded-full animate-spin mb-4"></div>
            <p class="text-gray-500 dark:text-gray-400 text-sm">กำลังโหลด...</p>
        </div>
    </div>

    {{-- ===== แท็บ: แพ็กเกจ ===== --}}
    <div x-show="activeTab === 'packages'" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 translate-y-2" x-transition:enter-end="opacity-100 translate-y-0">
        <div x-show="!loadingPackages && packages.length > 0"
            class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <template x-for="pkg in packages" :key="pkg.id">
                <div :class="pkg.is_popular ? 'ring-2 ring-purple-500 dark:ring-purple-400' : ''"
                    class="tp-card relative rounded-2xl hover:shadow-xl border border-gray-100 dark:border-gray-700 p-6 transition-all duration-300 hover:-translate-y-1">
                    {{-- แบดจ์ยอดนิยม --}}
                    <template x-if="pkg.is_popular">
                        <div class="absolute -top-3 left-1/2 -translate-x-1/2">
                            <span class="inline-flex items-center gap-1 px-4 py-1 bg-gradient-to-r from-purple-600 to-fuchsia-600 text-white text-xs font-bold rounded-full shadow-lg">
                                <i class="fas fa-fire"></i> ยอดนิยม
                            </span>
                        </div>
                    </template>
                    <div class="text-center mb-5 pt-2">
                        <h3 class="text-xl font-bold text-gray-900 dark:text-white" x-text="pkg.name"></h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1" x-text="pkg.description || ''"></p>
                    </div>
                    <div class="text-center mb-6">
                        <span class="text-4xl font-extrabold text-gray-900 dark:text-white">
                            ฿<span x-text="Number(pkg.price).toLocaleString()"></span>
                        </span>
                        <span class="text-gray-500 dark:text-gray-400 text-sm" x-text="'/' + (pkg.duration_days || 30) + ' วัน'"></span>
                    </div>
                    <ul class="space-y-3 mb-6">
                        <li class="flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300">
                            <i class="fas fa-image text-blue-500 w-5 text-center"></i>
                            <span>ภาพ <strong x-text="pkg.image_credits?.toLocaleString() || '0'"></strong> เครดิต</span>
                        </li>
                        <li class="flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300">
                            <i class="fas fa-video text-pink-500 w-5 text-center"></i>
                            <span>วิดีโอ <strong x-text="pkg.video_credits?.toLocaleString() || '0'"></strong> เครดิต</span>
                        </li>
                        <template x-if="pkg.features">
                            <template x-for="feat in (typeof pkg.features === 'string' ? JSON.parse(pkg.features) : pkg.features)" :key="feat">
                                <li class="flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300">
                                    <i class="fas fa-check text-green-500 w-5 text-center"></i>
                                    <span x-text="feat"></span>
                                </li>
                            </template>
                        </template>
                    </ul>
                    <button @click="purchasePackage(pkg)"
                        :class="pkg.is_popular
                            ? 'bg-gradient-to-r from-purple-600 to-fuchsia-600 hover:from-purple-700 hover:to-fuchsia-700 text-white shadow-lg shadow-purple-500/25'
                            : 'bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 text-gray-800 dark:text-white'"
                        class="w-full py-3 rounded-xl font-semibold text-sm transition-all duration-200 hover:scale-[1.02] active:scale-95">
                        <i class="fas fa-shopping-cart mr-1"></i> ซื้อแพ็กเกจ
                    </button>
                </div>
            </template>
        </div>

        <div x-show="!loadingPackages && packages.length === 0"
            class="tp-card text-center py-16 rounded-2xl border border-gray-100 dark:border-gray-700">
            <div class="inline-flex items-center justify-center w-20 h-20 bg-purple-100 dark:bg-purple-900/30 rounded-full mb-4">
                <i class="fas fa-box-open text-3xl text-purple-500"></i>
            </div>
            <h3 class="text-lg font-bold text-gray-800 dark:text-white mb-2">ยังไม่มีแพ็กเกจ</h3>
            <p class="text-gray-500 dark:text-gray-400 text-sm">แพ็กเกจจะปรากฏที่นี่เมื่อเปิดให้บริการ</p>
        </div>

        <div x-show="loadingPackages" class="flex flex-col items-center justify-center py-16">
            <div class="w-12 h-12 border-4 border-purple-200 dark:border-purple-800 border-t-purple-600 dark:border-t-purple-400 rounded-full animate-spin mb-4"></div>
            <p class="text-gray-500 dark:text-gray-400 text-sm">กำลังโหลดแพ็กเกจ...</p>
        </div>
    </div>

    {{-- ===== Modal สร้างผลงาน ===== --}}
    <div x-show="showCreateModal" x-cloak
        class="fixed inset-0 z-50 flex items-center justify-center p-4"
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0">
        {{-- พื้นหลังมืด --}}
        <div class="absolute inset-0 bg-black/60 backdrop-blur-sm" @click="showCreateModal = false"></div>

        {{-- เนื้อหา Modal --}}
        <div class="tp-card relative w-full max-w-2xl max-h-[90vh] overflow-y-auto rounded-2xl border border-gray-200 dark:border-gray-700"
            x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0 scale-95 translate-y-4"
            x-transition:enter-end="opacity-100 scale-100 translate-y-0"
            x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="opacity-100 scale-100"
            x-transition:leave-end="opacity-0 scale-95"
            @click.stop>

            {{-- Header --}}
            <div class="flex items-center justify-between p-6 border-b border-gray-100 dark:border-gray-700">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 bg-gradient-to-br from-purple-500 to-fuchsia-500 rounded-xl flex items-center justify-center">
                        <i class="fas fa-magic text-white"></i>
                    </div>
                    <div>
                        <h3 class="text-lg font-bold text-gray-900 dark:text-white">สร้างผลงานด้วย AI</h3>
                        <p class="text-xs text-gray-500 dark:text-gray-400">อธิบายสิ่งที่คุณต้องการ แล้ว AI จะสร้างให้</p>
                    </div>
                </div>
                <button @click="showCreateModal = false" class="w-8 h-8 flex items-center justify-center text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            {{-- Body --}}
            <div class="p-6 space-y-5">
                {{-- ประเภท --}}
                <div>
                    <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                        <i class="fas fa-shapes mr-1 text-purple-500"></i> ประเภท
                    </label>
                    <div class="grid grid-cols-2 gap-3">
                        <button @click="form.type = 'image'"
                            :class="form.type === 'image'
                                ? 'bg-purple-50 dark:bg-purple-900/30 border-purple-500 dark:border-purple-400 text-purple-700 dark:text-purple-300 ring-2 ring-purple-500/20'
                                : 'bg-white dark:bg-gray-700 border-gray-200 dark:border-gray-600 text-gray-600 dark:text-gray-400 hover:border-purple-300'"
                            class="flex items-center gap-3 p-4 rounded-xl border-2 transition-all duration-200 cursor-pointer">
                            <i class="fas fa-image text-xl"></i>
                            <div class="text-left">
                                <div class="font-semibold text-sm">ภาพ</div>
                                <div class="text-xs opacity-70">สร้างภาพด้วย AI</div>
                            </div>
                        </button>
                        <button @click="form.type = 'video'"
                            :class="form.type === 'video'
                                ? 'bg-pink-50 dark:bg-pink-900/30 border-pink-500 dark:border-pink-400 text-pink-700 dark:text-pink-300 ring-2 ring-pink-500/20'
                                : 'bg-white dark:bg-gray-700 border-gray-200 dark:border-gray-600 text-gray-600 dark:text-gray-400 hover:border-pink-300'"
                            class="flex items-center gap-3 p-4 rounded-xl border-2 transition-all duration-200 cursor-pointer">
                            <i class="fas fa-video text-xl"></i>
                            <div class="text-left">
                                <div class="font-semibold text-sm">วิดีโอ</div>
                                <div class="text-xs opacity-70">สร้างวิดีโอด้วย AI</div>
                            </div>
                        </button>
                    </div>
                </div>

                {{-- AI Provider --}}
                <div>
                    <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                        <i class="fas fa-robot mr-1 text-purple-500"></i> AI Provider
                    </label>
                    <div class="space-y-2">
                        <template x-for="prov in availableProviders" :key="prov.slug">
                            <button @click="form.provider = prov.slug"
                                x-show="prov.type === form.type || prov.type === 'both'"
                                :class="form.provider === prov.slug
                                    ? 'bg-purple-50 dark:bg-purple-900/30 border-purple-500 dark:border-purple-400 ring-2 ring-purple-500/20'
                                    : 'bg-white dark:bg-gray-700 border-gray-200 dark:border-gray-600 hover:border-purple-300'"
                                class="w-full flex items-center gap-3 p-3 rounded-xl border-2 transition-all duration-200 cursor-pointer text-left">
                                <div class="w-9 h-9 bg-gradient-to-br from-purple-500 to-fuchsia-500 rounded-lg flex items-center justify-center flex-shrink-0">
                                    <i class="fas fa-microchip text-white text-sm"></i>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center gap-2">
                                        <span class="font-semibold text-sm text-gray-900 dark:text-white" x-text="prov.name"></span>
                                        <template x-if="prov.slug === 'together-ai'">
                                            <span class="px-1.5 py-0.5 bg-green-100 dark:bg-green-900/50 text-green-700 dark:text-green-300 text-[10px] font-bold rounded-full">ฟรี</span>
                                        </template>
                                    </div>
                                    <p class="text-xs text-gray-500 dark:text-gray-400 truncate" x-text="prov.description || ''"></p>
                                </div>
                                <template x-if="form.provider === prov.slug">
                                    <i class="fas fa-check-circle text-purple-500 text-lg flex-shrink-0"></i>
                                </template>
                            </button>
                        </template>
                    </div>
                    <template x-if="availableProviders.filter(p => p.type === form.type || p.type === 'both').length === 0">
                        <p class="text-sm text-gray-500 dark:text-gray-400 mt-2 text-center py-4">
                            <i class="fas fa-info-circle mr-1"></i>
                            ยังไม่มี provider สำหรับประเภทนี้
                        </p>
                    </template>
                </div>

                {{-- Prompt --}}
                <div>
                    <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                        <i class="fas fa-pencil mr-1 text-purple-500"></i> อธิบายสิ่งที่ต้องการ
                    </label>
                    <textarea x-model="form.prompt" rows="4"
                        class="w-full px-4 py-3 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-purple-500 focus:border-transparent transition placeholder:text-gray-400"
                        placeholder="เช่น: ภาพวิวพระอาทิตย์ตกริมทะเล มีโลมากระโดด สไตล์สีน้ำ..."
                        required></textarea>
                    <div class="flex items-center justify-between mt-1.5">
                        <p class="text-xs text-gray-400">
                            <i class="fas fa-lightbulb text-yellow-500 mr-1"></i>
                            ยิ่งอธิบายละเอียด ผลลัพธ์ยิ่งดี
                        </p>
                        <span class="text-xs" :class="form.prompt.length > 900 ? 'text-red-500' : 'text-gray-400'"
                            x-text="form.prompt.length + '/1000'"></span>
                    </div>
                </div>

                {{-- สไตล์ & ขนาด --}}
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                            <i class="fas fa-palette mr-1 text-purple-500"></i> สไตล์
                        </label>
                        <select x-model="form.style"
                            class="w-full px-4 py-3 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-purple-500 focus:border-transparent transition">
                            <option value="realistic">สมจริง (Realistic)</option>
                            <option value="artistic">ศิลปะ (Artistic)</option>
                            <option value="anime">อนิเมะ (Anime)</option>
                            <option value="cartoon">การ์ตูน (Cartoon)</option>
                            <option value="3d">3D Render</option>
                        </select>
                    </div>
                    <div x-show="form.type === 'image'">
                        <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                            <i class="fas fa-expand mr-1 text-purple-500"></i> ขนาด
                        </label>
                        <select x-model="form.size"
                            class="w-full px-4 py-3 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-purple-500 focus:border-transparent transition">
                            <option value="1024x1024">สี่เหลี่ยม (1024x1024)</option>
                            <option value="1024x768">แนวนอน (1024x768)</option>
                            <option value="768x1024">แนวตั้ง (768x1024)</option>
                        </select>
                    </div>
                </div>

                {{-- ข้อมูลเครดิต --}}
                <div class="bg-purple-50 dark:bg-purple-900/20 border border-purple-200 dark:border-purple-800 rounded-xl p-4">
                    <div class="flex items-start gap-3">
                        <i class="fas fa-info-circle text-purple-500 mt-0.5"></i>
                        <div class="text-sm">
                            <p class="text-purple-800 dark:text-purple-200">
                                <span x-text="form.type === 'image' ? 'เครดิตภาพคงเหลือ: ' + (stats.remainingImages == -1 ? '∞ (ไม่จำกัด)' : stats.remainingImages) : 'เครดิตวิดีโอคงเหลือ: ' + (stats.remainingVideos == -1 ? '∞ (ไม่จำกัด)' : stats.remainingVideos)"></span>
                            </p>
                            <p class="text-purple-600 dark:text-purple-300 text-xs mt-1">การสร้างแต่ละครั้งใช้ 1 เครดิต</p>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Footer --}}
            <div class="flex items-center justify-end gap-3 p-6 border-t border-gray-100 dark:border-gray-700">
                <button @click="showCreateModal = false"
                    class="px-5 py-2.5 text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-xl text-sm font-medium transition">
                    ยกเลิก
                </button>
                <button @click="generate()"
                    :disabled="generating || !form.prompt.trim() || !form.provider"
                    :class="generating || !form.prompt.trim() || !form.provider ? 'opacity-50 cursor-not-allowed' : 'hover:shadow-lg hover:scale-[1.02] active:scale-95'"
                    class="inline-flex items-center gap-2 px-6 py-2.5 bg-gradient-to-r from-purple-600 to-fuchsia-600 text-white rounded-xl text-sm font-semibold shadow-lg shadow-purple-500/25 transition-all duration-200">
                    <template x-if="!generating">
                        <span class="contents"><i class="fas fa-magic"></i> สร้างเลย</span>
                    </template>
                    <template x-if="generating">
                        <span class="contents"><i class="fas fa-spinner fa-spin"></i> กำลังสร้าง...</span>
                    </template>
                </button>
            </div>
        </div>
    </div>

    {{-- ===== Modal ดูผลงาน ===== --}}
    <div x-show="showViewModal" x-cloak
        class="fixed inset-0 z-50 flex items-center justify-center p-4"
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0">
        <div class="absolute inset-0 bg-black/70 backdrop-blur-sm" @click="showViewModal = false"></div>

        <div class="tp-card relative w-full max-w-5xl max-h-[90vh] overflow-y-auto rounded-2xl border border-gray-200 dark:border-gray-700"
            x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0 scale-95"
            x-transition:enter-end="opacity-100 scale-100"
            @click.stop>

            {{-- Header --}}
            <div class="flex items-center justify-between p-5 border-b border-gray-100 dark:border-gray-700">
                <div>
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white line-clamp-1" x-text="viewItem?.prompt || 'ผลงาน'"></h3>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5" x-text="viewItem ? formatDate(viewItem.created_at) : ''"></p>
                </div>
                <button @click="showViewModal = false" class="w-8 h-8 flex items-center justify-center text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            {{-- Body --}}
            <div class="p-5">
                <div class="flex flex-col lg:flex-row gap-6">
                    {{-- รูปภาพ --}}
                    <div class="flex-1 min-w-0">
                        <div class="bg-gray-100 dark:bg-gray-900 rounded-xl overflow-hidden">
                            <template x-if="viewItem?.file_url">
                                <img :src="viewItem.file_url" :alt="viewItem.prompt"
                                    class="w-full h-auto max-h-[60vh] object-contain mx-auto">
                            </template>
                            <template x-if="!viewItem?.file_url">
                                <div class="flex items-center justify-center h-64 text-gray-400">
                                    <div class="text-center">
                                        <i class="fas fa-image text-4xl mb-2"></i>
                                        <p class="text-sm">ไม่มีรูปภาพ</p>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>

                    {{-- รายละเอียด --}}
                    <div class="w-full lg:w-72 flex-shrink-0 space-y-4">
                        <div class="bg-gray-50 dark:bg-gray-700/50 rounded-xl p-4 space-y-3">
                            <h4 class="font-semibold text-gray-900 dark:text-white text-sm">รายละเอียด</h4>
                            <div class="space-y-2 text-sm">
                                <div class="flex justify-between">
                                    <span class="text-gray-500 dark:text-gray-400">ประเภท</span>
                                    <span class="font-medium text-gray-900 dark:text-white" x-text="viewItem?.type === 'image' ? 'ภาพ' : 'วิดีโอ'"></span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-500 dark:text-gray-400">Provider</span>
                                    <span class="font-medium text-gray-900 dark:text-white" x-text="viewItem?.provider?.name || '-'"></span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-500 dark:text-gray-400">สถานะ</span>
                                    <span class="font-medium" :class="{
                                        'text-green-600 dark:text-green-400': viewItem?.status === 'completed',
                                        'text-yellow-600 dark:text-yellow-400': viewItem?.status === 'pending' || viewItem?.status === 'processing',
                                        'text-red-600 dark:text-red-400': viewItem?.status === 'failed'
                                    }" x-text="statusLabel(viewItem?.status)"></span>
                                </div>
                            </div>
                        </div>

                        <div class="bg-gray-50 dark:bg-gray-700/50 rounded-xl p-4">
                            <h4 class="font-semibold text-gray-900 dark:text-white text-sm mb-2">Prompt</h4>
                            <p class="text-sm text-gray-600 dark:text-gray-300 leading-relaxed" x-text="viewItem?.prompt || '-'"></p>
                        </div>

                        {{-- ปุ่มจัดการ --}}
                        <div class="space-y-2">
                            <template x-if="viewItem?.file_url">
                                <a :href="viewItem.file_url" download
                                    class="flex items-center justify-center gap-2 w-full py-2.5 bg-purple-600 hover:bg-purple-700 text-white rounded-xl text-sm font-semibold transition">
                                    <i class="fas fa-download"></i> ดาวน์โหลด
                                </a>
                            </template>
                            <button @click="toggleFavorite()"
                                class="flex items-center justify-center gap-2 w-full py-2.5 border-2 rounded-xl text-sm font-semibold transition"
                                :class="viewItem?.is_favorite
                                    ? 'border-red-300 dark:border-red-600 text-red-500 bg-red-50 dark:bg-red-900/20'
                                    : 'border-gray-200 dark:border-gray-600 text-gray-600 dark:text-gray-400 hover:border-red-300 hover:text-red-500'">
                                <i :class="viewItem?.is_favorite ? 'fas fa-heart' : 'far fa-heart'"></i>
                                <span x-text="viewItem?.is_favorite ? 'เอาออกจากรายการโปรด' : 'เพิ่มในรายการโปรด'"></span>
                            </button>
                            <button @click="deleteGeneration()"
                                class="flex items-center justify-center gap-2 w-full py-2.5 border-2 border-gray-200 dark:border-gray-600 text-gray-500 dark:text-gray-400 hover:border-red-300 hover:text-red-500 rounded-xl text-sm font-semibold transition">
                                <i class="fas fa-trash"></i> ลบ
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ===== Toast Notification ===== --}}
    <div x-show="toast.show" x-cloak
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0 translate-y-4"
        x-transition:enter-end="opacity-100 translate-y-0"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="opacity-100 translate-y-0"
        x-transition:leave-end="opacity-0 translate-y-4"
        class="fixed bottom-6 right-6 z-[60] max-w-sm">
        <div :class="{
            'bg-green-600': toast.type === 'success',
            'bg-red-600': toast.type === 'error',
            'bg-blue-600': toast.type === 'info'
        }" class="flex items-center gap-3 px-5 py-3.5 rounded-xl text-white shadow-2xl">
            <i :class="{
                'fas fa-check-circle': toast.type === 'success',
                'fas fa-exclamation-circle': toast.type === 'error',
                'fas fa-info-circle': toast.type === 'info'
            }"></i>
            <span class="text-sm font-medium" x-text="toast.message"></span>
            <button @click="toast.show = false" class="ml-2 text-white/70 hover:text-white">
                <i class="fas fa-times text-xs"></i>
            </button>
        </div>
    </div>

</div>
@endsection

@push('styles')
<style>
    [x-cloak] { display: none !important; }
    @keyframes aiFloat {
        0%, 100% { transform: translateY(0px) rotate(0deg); }
        50% { transform: translateY(-15px) rotate(5deg); }
    }
    .scrollbar-hide::-webkit-scrollbar { display: none; }
    .scrollbar-hide { -ms-overflow-style: none; scrollbar-width: none; }
    .line-clamp-2 { display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
</style>
@endpush

@push('scripts')
<script>
    /**
     * Alpine.js component สำหรับหน้า AI Gen
     * จัดการการสร้างภาพ/วิดีโอด้วย AI
     */
    function aiGenApp() {
        return {
            // สถานะ
            activeTab: 'creations',
            showCreateModal: false,
            showViewModal: false,
            generating: false,
            loadingCreations: true,
            loadingExplore: false,
            loadingPackages: false,
            currentFilter: 'all',
            currentExploreFilter: 'all',

            // ข้อมูล
            stats: {
                remainingImages: {{ $stats['remaining_images'] ?? 0 }},
                remainingVideos: {{ $stats['remaining_videos'] ?? 0 }},
                totalGenerated: {{ $stats['total_generated'] ?? 0 }},
                planName: '{{ $package_name ?? "Free" }}'
            },
            generations: [],
            exploreItems: [],
            packages: [],
            availableProviders: [],
            viewItem: null,
            pollingIntervals: {},

            // ฟอร์ม
            form: {
                type: 'image',
                provider: '',
                prompt: '',
                style: 'realistic',
                size: '1024x1024'
            },

            // Toast notification
            toast: { show: false, message: '', type: 'info' },

            // ตั้งค่าแท็บ
            tabs: [
                { id: 'creations', label: 'ผลงานของฉัน', icon: 'fas fa-images' },
                { id: 'explore', label: 'สำรวจ', icon: 'fas fa-compass' },
                { id: 'packages', label: 'แพ็กเกจ', icon: 'fas fa-box' }
            ],

            // ตัวกรอง
            creationFilters: [
                { value: 'all', label: 'ทั้งหมด', icon: 'fas fa-th' },
                { value: 'image', label: 'ภาพ', icon: 'fas fa-image' },
                { value: 'video', label: 'วิดีโอ', icon: 'fas fa-video' },
                { value: 'favorite', label: 'โปรด', icon: 'fas fa-heart' }
            ],
            exploreFilters: [
                { value: 'all', label: 'ทั้งหมด', icon: 'fas fa-th' },
                { value: 'image', label: 'ภาพ', icon: 'fas fa-image' },
                { value: 'video', label: 'วิดีโอ', icon: 'fas fa-video' }
            ],

            // API Token
            apiToken: '{{ $api_token }}',

            // เมธอดเริ่มต้น
            init() {
                // เก็บ token สำหรับ API calls
                localStorage.setItem('token', this.apiToken);
                this.loadDashboard();
                this.loadCreations();

                // เมื่อเปลี่ยนแท็บ โหลดข้อมูลที่เกี่ยวข้อง
                this.$watch('activeTab', (tab) => {
                    if (tab === 'explore' && this.exploreItems.length === 0) this.loadExplore();
                    if (tab === 'packages' && this.packages.length === 0) this.loadPackages();
                });
            },

            // ===== API Calls =====

            async apiCall(url, options = {}) {
                const headers = {
                    'Authorization': 'Bearer ' + this.apiToken,
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    ...options.headers
                };
                try {
                    const response = await fetch(url, { ...options, headers });
                    const data = await response.json();
                    if (!response.ok) throw new Error(data.error || data.message || 'เกิดข้อผิดพลาด');
                    return data;
                } catch (error) {
                    throw error;
                }
            },

            async loadDashboard() {
                try {
                    const res = await this.apiCall('/api/v1/ai-gen/dashboard');
                    if (res.success && res.data) {
                        const d = res.data;
                        this.availableProviders = d.providers || [];
                        this.stats.totalGenerated = d.stats?.total_generations || 0;
                        this.stats.planName = d.subscription?.package_name || 'Free';
                        // ตั้ง provider เริ่มต้น
                        if (this.availableProviders.length > 0 && !this.form.provider) {
                            const imgProviders = this.availableProviders.filter(p => p.type === 'image' || p.type === 'both');
                            if (imgProviders.length > 0) this.form.provider = imgProviders[0].slug;
                        }
                    }
                } catch (e) {
                    console.error('โหลด dashboard ไม่สำเร็จ:', e);
                }
            },

            async loadCreations(filter) {
                this.loadingCreations = true;
                try {
                    let url = '/api/v1/ai-gen/generations?per_page=50';
                    if (filter && filter !== 'all') {
                        if (filter === 'favorite') url += '&is_favorite=1';
                        else url += '&type=' + filter;
                    }
                    const res = await this.apiCall(url);
                    if (res.success) {
                        this.generations = res.data?.data || res.data || [];
                        // ตรวจสอบสถานะ pending/processing
                        this.generations.forEach(gen => {
                            if (gen.status === 'pending' || gen.status === 'processing') {
                                this.pollStatus(gen.id);
                            }
                        });
                    }
                } catch (e) {
                    console.error('โหลดผลงานไม่สำเร็จ:', e);
                } finally {
                    this.loadingCreations = false;
                }
            },

            async loadExplore(filter) {
                this.loadingExplore = true;
                try {
                    let url = '/api/v1/ai-gen/generations?status=completed&per_page=50';
                    if (filter && filter !== 'all') url += '&type=' + filter;
                    const res = await this.apiCall(url);
                    if (res.success) {
                        this.exploreItems = res.data?.data || res.data || [];
                    }
                } catch (e) {
                    console.error('โหลด explore ไม่สำเร็จ:', e);
                } finally {
                    this.loadingExplore = false;
                }
            },

            async loadPackages() {
                this.loadingPackages = true;
                try {
                    const res = await this.apiCall('/api/v1/ai-gen/packages');
                    if (res.success) {
                        this.packages = res.data?.data || res.data || [];
                    }
                } catch (e) {
                    console.error('โหลดแพ็กเกจไม่สำเร็จ:', e);
                } finally {
                    this.loadingPackages = false;
                }
            },

            // ===== การสร้างภาพ/วิดีโอ =====

            async generate() {
                if (this.generating || !this.form.prompt.trim() || !this.form.provider) return;

                this.generating = true;
                try {
                    const payload = {
                        provider: this.form.provider,
                        type: this.form.type,
                        prompt: this.form.prompt.trim(),
                        parameters: {
                            style: this.form.style,
                            size: this.form.size
                        }
                    };

                    const res = await this.apiCall('/api/v1/ai-gen/generate', {
                        method: 'POST',
                        body: JSON.stringify(payload)
                    });

                    if (res.success) {
                        this.showCreateModal = false;
                        this.showToast('เริ่มสร้างผลงานแล้ว! รอสักครู่...', 'success');
                        this.form.prompt = '';
                        this.loadCreations(this.currentFilter !== 'all' ? this.currentFilter : undefined);
                        this.loadDashboard();
                        // Poll status
                        if (res.generation_id) {
                            this.pollStatus(res.generation_id);
                        }
                    } else {
                        this.showToast(res.error || 'สร้างผลงานไม่สำเร็จ', 'error');
                    }
                } catch (e) {
                    this.showToast(e.message || 'เกิดข้อผิดพลาด กรุณาลองใหม่', 'error');
                } finally {
                    this.generating = false;
                }
            },

            // ===== Poll สถานะ =====

            pollStatus(generationId) {
                if (this.pollingIntervals[generationId]) return;

                this.pollingIntervals[generationId] = setInterval(async () => {
                    try {
                        const res = await this.apiCall('/api/v1/ai-gen/generations/' + generationId + '/status');
                        if (res.success && (res.status === 'completed' || res.status === 'failed')) {
                            clearInterval(this.pollingIntervals[generationId]);
                            delete this.pollingIntervals[generationId];

                            if (res.status === 'completed') {
                                this.showToast('สร้างผลงานสำเร็จ!', 'success');
                            } else {
                                this.showToast('สร้างผลงานไม่สำเร็จ', 'error');
                            }
                            this.loadCreations(this.currentFilter !== 'all' ? this.currentFilter : undefined);
                            this.loadDashboard();
                        }
                    } catch (e) {
                        console.error('Poll status error:', e);
                    }
                }, 5000);
            },

            // ===== จัดการผลงาน =====

            viewGeneration(gen) {
                this.viewItem = gen;
                this.showViewModal = true;
            },

            async toggleFavorite() {
                if (!this.viewItem) return;
                try {
                    const res = await this.apiCall('/api/v1/ai-gen/generations/' + this.viewItem.id + '/favorite', {
                        method: 'POST'
                    });
                    if (res.success) {
                        this.viewItem.is_favorite = res.is_favorite;
                        // อัพเดทใน list ด้วย
                        const gen = this.generations.find(g => g.id === this.viewItem.id);
                        if (gen) gen.is_favorite = res.is_favorite;
                        this.showToast(res.is_favorite ? 'เพิ่มในรายการโปรดแล้ว' : 'เอาออกจากรายการโปรดแล้ว', 'success');
                    }
                } catch (e) {
                    this.showToast('เกิดข้อผิดพลาด', 'error');
                }
            },

            async deleteGeneration() {
                if (!this.viewItem) return;
                if (!confirm('ต้องการลบผลงานนี้หรือไม่?')) return;
                try {
                    const res = await this.apiCall('/api/v1/ai-gen/generations/' + this.viewItem.id, {
                        method: 'DELETE'
                    });
                    if (res.success) {
                        this.showViewModal = false;
                        this.showToast('ลบผลงานแล้ว', 'success');
                        this.generations = this.generations.filter(g => g.id !== this.viewItem.id);
                        this.viewItem = null;
                    }
                } catch (e) {
                    this.showToast('ลบไม่สำเร็จ', 'error');
                }
            },

            async purchasePackage(pkg) {
                if (!confirm('ต้องการซื้อแพ็กเกจ "' + pkg.name + '" ราคา ฿' + Number(pkg.price).toLocaleString() + ' หรือไม่?')) return;
                try {
                    const res = await this.apiCall('/api/v1/ai-gen/packages/' + pkg.id + '/purchase', {
                        method: 'POST'
                    });
                    if (res.success) {
                        this.showToast('ซื้อแพ็กเกจสำเร็จ!', 'success');
                        this.loadDashboard();
                    } else {
                        this.showToast(res.error || 'ซื้อแพ็กเกจไม่สำเร็จ', 'error');
                    }
                } catch (e) {
                    this.showToast(e.message || 'เกิดข้อผิดพลาด', 'error');
                }
            },

            // ===== ตัวกรอง =====

            filterCreations(filter) {
                this.currentFilter = filter;
                if (filter === 'all') this.loadCreations();
                else if (filter === 'favorite') this.loadCreations('favorite');
                else this.loadCreations(filter);
            },

            filterExplore(filter) {
                this.currentExploreFilter = filter;
                this.loadExplore(filter === 'all' ? undefined : filter);
            },

            // ===== Helpers =====

            formatDate(dateStr) {
                if (!dateStr) return '';
                const d = new Date(dateStr);
                const now = new Date();
                const diffMs = now - d;
                const diffMins = Math.floor(diffMs / 60000);
                const diffHours = Math.floor(diffMs / 3600000);
                const diffDays = Math.floor(diffMs / 86400000);

                if (diffMins < 1) return 'เมื่อสักครู่';
                if (diffMins < 60) return diffMins + ' นาทีที่แล้ว';
                if (diffHours < 24) return diffHours + ' ชั่วโมงที่แล้ว';
                if (diffDays < 7) return diffDays + ' วันที่แล้ว';
                return d.toLocaleDateString('th-TH', { day: 'numeric', month: 'short', year: 'numeric' });
            },

            statusLabel(status) {
                const map = {
                    'completed': 'สำเร็จ',
                    'pending': 'รอดำเนินการ',
                    'processing': 'กำลังสร้าง',
                    'failed': 'ล้มเหลว'
                };
                return map[status] || status || '-';
            },

            showToast(message, type = 'info') {
                this.toast = { show: true, message, type };
                setTimeout(() => { this.toast.show = false; }, 4000);
            }
        }
    }
</script>
@endpush
