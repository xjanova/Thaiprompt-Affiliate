{{--
/**
 * หน้าหลัก Label Designer - ระบบออกแบบฉลาก บาร์โค้ด QR Code
 *
 * เครื่องมือทันสมัยสำหรับร้านค้าทุกร้าน:
 * - ออกแบบฉลากแบบ drag & drop
 * - รองรับบาร์โค้ดหลากหลายรูปแบบ
 * - รองรับ QR Code
 * - Preview ก่อนพิมพ์ตรงจริง
 * - รองรับเครื่องพิมพ์หลากหลาย
 *
 * @author Claude AI
 */
--}}

@extends('layouts.app')

@section('title', 'ออกแบบฉลาก & บาร์โค้ด')

@section('content')
<div x-data="labelDesignerApp()" x-init="init()" class="min-h-screen bg-gradient-to-br from-gray-50 to-gray-100 dark:from-gray-900 dark:to-gray-800">
    {{-- Hero Section --}}
    <div class="relative overflow-hidden bg-gradient-to-r from-indigo-600 via-purple-600 to-pink-500 text-white">
        <div class="absolute inset-0 opacity-10">
            <div class="absolute top-0 left-0 w-96 h-96 bg-white rounded-full mix-blend-overlay filter blur-3xl animate-pulse"></div>
            <div class="absolute bottom-0 right-0 w-96 h-96 bg-white rounded-full mix-blend-overlay filter blur-3xl animate-pulse" style="animation-delay: 1s;"></div>
        </div>

        <div class="relative container mx-auto px-4 py-12 md:py-16">
            <div class="flex flex-col md:flex-row items-center justify-between gap-8">
                <div class="flex-1">
                    <div class="inline-flex items-center gap-2 px-4 py-2 bg-white/20 backdrop-blur-sm rounded-full text-sm font-medium mb-4">
                        <span class="w-2 h-2 bg-green-400 rounded-full animate-pulse"></span>
                        เครื่องมือฟรีสำหรับร้านค้าทุกร้าน
                    </div>
                    <h1 class="text-3xl md:text-5xl font-bold mb-4">
                        ออกแบบฉลาก & บาร์โค้ด
                    </h1>
                    <p class="text-lg md:text-xl text-white/90 mb-6 max-w-2xl">
                        สร้างฉลากสินค้า สติ๊กเกอร์ ป้ายราคา บาร์โค้ด และ QR Code ได้ง่ายๆ
                        รองรับเครื่องพิมพ์หลากหลาย พร้อม Preview ก่อนพิมพ์ตรงจริง
                    </p>
                    <div class="flex flex-wrap gap-4">
                        <a href="{{ route('label-designer.create') }}"
                           class="inline-flex items-center gap-2 px-6 py-3 bg-white text-indigo-600 rounded-xl font-bold hover:bg-white/90 transition shadow-lg hover:shadow-xl transform hover:-translate-y-0.5">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                            </svg>
                            สร้างฉลากใหม่
                        </a>
                        <button @click="showTemplates = true"
                                class="inline-flex items-center gap-2 px-6 py-3 bg-white/20 backdrop-blur-sm text-white rounded-xl font-bold hover:bg-white/30 transition border border-white/30">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 5a1 1 0 011-1h14a1 1 0 011 1v2a1 1 0 01-1 1H5a1 1 0 01-1-1V5zM4 13a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H5a1 1 0 01-1-1v-6zM16 13a1 1 0 011-1h2a1 1 0 011 1v6a1 1 0 01-1 1h-2a1 1 0 01-1-1v-6z"></path>
                            </svg>
                            ดู Templates
                        </button>
                    </div>
                </div>

                {{-- Feature Icons --}}
                <div class="hidden md:grid grid-cols-2 gap-4">
                    <div class="p-6 bg-white/10 backdrop-blur-sm rounded-2xl border border-white/20">
                        <div class="w-12 h-12 bg-white/20 rounded-xl flex items-center justify-center mb-3">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"></path>
                            </svg>
                        </div>
                        <h3 class="font-bold mb-1">QR Code</h3>
                        <p class="text-sm text-white/80">สร้าง QR Code สำหรับลิงก์สินค้า</p>
                    </div>
                    <div class="p-6 bg-white/10 backdrop-blur-sm rounded-2xl border border-white/20">
                        <div class="w-12 h-12 bg-white/20 rounded-xl flex items-center justify-center mb-3">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17V7m0 10a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2h2a2 2 0 012 2m0 10a2 2 0 002 2h2a2 2 0 002-2M9 7a2 2 0 012-2h2a2 2 0 012 2m0 10V7m0 10a2 2 0 002 2h2a2 2 0 002-2V7a2 2 0 00-2-2h-2a2 2 0 00-2 2"></path>
                            </svg>
                        </div>
                        <h3 class="font-bold mb-1">บาร์โค้ด</h3>
                        <p class="text-sm text-white/80">รองรับ CODE128, EAN13, UPC-A</p>
                    </div>
                    <div class="p-6 bg-white/10 backdrop-blur-sm rounded-2xl border border-white/20">
                        <div class="w-12 h-12 bg-white/20 rounded-xl flex items-center justify-center mb-3">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path>
                            </svg>
                        </div>
                        <h3 class="font-bold mb-1">หลายเครื่องพิมพ์</h3>
                        <p class="text-sm text-white/80">Zebra, DYMO, Brother, ความร้อน</p>
                    </div>
                    <div class="p-6 bg-white/10 backdrop-blur-sm rounded-2xl border border-white/20">
                        <div class="w-12 h-12 bg-white/20 rounded-xl flex items-center justify-center mb-3">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                            </svg>
                        </div>
                        <h3 class="font-bold mb-1">Preview ตรงจริง</h3>
                        <p class="text-sm text-white/80">ดูตัวอย่างก่อนพิมพ์</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Quick Stats --}}
    <div class="container mx-auto px-4 -mt-8 relative z-10">
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-6 border border-gray-200 dark:border-gray-700">
                <div class="flex items-center gap-4">
                    <div class="w-12 h-12 bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl flex items-center justify-center text-white">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"></path>
                        </svg>
                    </div>
                    <div>
                        <p class="text-2xl font-bold text-gray-900 dark:text-white" x-text="stats.templates">--</p>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Templates</p>
                    </div>
                </div>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-6 border border-gray-200 dark:border-gray-700">
                <div class="flex items-center gap-4">
                    <div class="w-12 h-12 bg-gradient-to-br from-green-500 to-green-600 rounded-xl flex items-center justify-center text-white">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                    </div>
                    <div>
                        <p class="text-2xl font-bold text-gray-900 dark:text-white" x-text="stats.paperSizes">--</p>
                        <p class="text-sm text-gray-500 dark:text-gray-400">ขนาดกระดาษ</p>
                    </div>
                </div>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-6 border border-gray-200 dark:border-gray-700">
                <div class="flex items-center gap-4">
                    <div class="w-12 h-12 bg-gradient-to-br from-purple-500 to-purple-600 rounded-xl flex items-center justify-center text-white">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17V7m0 10a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2h2a2 2 0 012 2m0 10a2 2 0 002 2h2a2 2 0 002-2M9 7a2 2 0 012-2h2a2 2 0 012 2m0 10V7m0 10a2 2 0 002 2h2a2 2 0 002-2V7a2 2 0 00-2-2h-2a2 2 0 00-2 2"></path>
                        </svg>
                    </div>
                    <div>
                        <p class="text-2xl font-bold text-gray-900 dark:text-white">8</p>
                        <p class="text-sm text-gray-500 dark:text-gray-400">รูปแบบบาร์โค้ด</p>
                    </div>
                </div>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-6 border border-gray-200 dark:border-gray-700">
                <div class="flex items-center gap-4">
                    <div class="w-12 h-12 bg-gradient-to-br from-orange-500 to-orange-600 rounded-xl flex items-center justify-center text-white">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path>
                        </svg>
                    </div>
                    <div>
                        <p class="text-2xl font-bold text-gray-900 dark:text-white">20+</p>
                        <p class="text-sm text-gray-500 dark:text-gray-400">เครื่องพิมพ์</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Main Content --}}
    <div class="container mx-auto px-4 py-12">
        {{-- Tab Navigation --}}
        <div class="flex flex-wrap items-center gap-4 mb-8">
            <button @click="activeTab = 'templates'"
                    :class="activeTab === 'templates' ? 'bg-indigo-600 text-white' : 'bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700'"
                    class="px-6 py-3 rounded-xl font-medium transition shadow-sm border border-gray-200 dark:border-gray-700">
                <span class="flex items-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 5a1 1 0 011-1h14a1 1 0 011 1v2a1 1 0 01-1 1H5a1 1 0 01-1-1V5zM4 13a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H5a1 1 0 01-1-1v-6zM16 13a1 1 0 011-1h2a1 1 0 011 1v6a1 1 0 01-1 1h-2a1 1 0 01-1-1v-6z"></path>
                    </svg>
                    Templates ยอดนิยม
                </span>
            </button>
            <button @click="activeTab = 'paper-sizes'"
                    :class="activeTab === 'paper-sizes' ? 'bg-indigo-600 text-white' : 'bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700'"
                    class="px-6 py-3 rounded-xl font-medium transition shadow-sm border border-gray-200 dark:border-gray-700">
                <span class="flex items-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                    ขนาดกระดาษ
                </span>
            </button>
            <button @click="activeTab = 'barcode-formats'"
                    :class="activeTab === 'barcode-formats' ? 'bg-indigo-600 text-white' : 'bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700'"
                    class="px-6 py-3 rounded-xl font-medium transition shadow-sm border border-gray-200 dark:border-gray-700">
                <span class="flex items-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17V7m0 10a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2h2a2 2 0 012 2m0 10a2 2 0 002 2h2a2 2 0 002-2M9 7a2 2 0 012-2h2a2 2 0 012 2m0 10V7m0 10a2 2 0 002 2h2a2 2 0 002-2V7a2 2 0 00-2-2h-2a2 2 0 00-2 2"></path>
                    </svg>
                    รูปแบบบาร์โค้ด
                </span>
            </button>
            @auth
            <button @click="activeTab = 'my-labels'"
                    :class="activeTab === 'my-labels' ? 'bg-indigo-600 text-white' : 'bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700'"
                    class="px-6 py-3 rounded-xl font-medium transition shadow-sm border border-gray-200 dark:border-gray-700">
                <span class="flex items-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
                    </svg>
                    ฉลากของฉัน
                </span>
            </button>
            @endauth
        </div>

        {{-- Templates Tab --}}
        <div x-show="activeTab === 'templates'" x-transition>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
                <template x-for="template in templates" :key="template.id">
                    <div class="group bg-white dark:bg-gray-800 rounded-2xl shadow-lg border border-gray-200 dark:border-gray-700 overflow-hidden hover:shadow-xl transition-all hover:-translate-y-1">
                        {{-- Template Preview --}}
                        <div class="relative h-40 bg-gradient-to-br from-gray-100 to-gray-200 dark:from-gray-700 dark:to-gray-600 p-4">
                            <div class="absolute inset-4 bg-white dark:bg-gray-800 rounded-lg shadow-inner flex items-center justify-center">
                                <div class="text-center">
                                    <svg class="w-10 h-10 mx-auto text-gray-400 dark:text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"></path>
                                    </svg>
                                    <p class="text-xs text-gray-400 mt-1" x-text="template.paper_width + ' x ' + template.paper_height + ' mm'"></p>
                                </div>
                            </div>
                            <div class="absolute top-2 right-2">
                                <span x-show="template.is_system" class="px-2 py-1 bg-indigo-500 text-white text-xs font-medium rounded-full">
                                    ระบบ
                                </span>
                            </div>
                        </div>

                        {{-- Template Info --}}
                        <div class="p-4">
                            <h3 class="font-bold text-gray-900 dark:text-white mb-1" x-text="template.name"></h3>
                            <p class="text-sm text-gray-500 dark:text-gray-400 mb-3 line-clamp-2" x-text="template.description || 'ไม่มีคำอธิบาย'"></p>

                            <div class="flex items-center justify-between">
                                <span class="text-xs text-gray-400 dark:text-gray-500">
                                    ใช้งาน <span x-text="template.usage_count"></span> ครั้ง
                                </span>
                                <a :href="'{{ route('label-designer.create') }}?template=' + template.id"
                                   class="inline-flex items-center gap-1 px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-lg transition">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path>
                                    </svg>
                                    ใช้
                                </a>
                            </div>
                        </div>
                    </div>
                </template>

                {{-- Empty State --}}
                <div x-show="templates.length === 0" class="col-span-full py-12 text-center">
                    <svg class="w-16 h-16 mx-auto text-gray-300 dark:text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 5a1 1 0 011-1h14a1 1 0 011 1v2a1 1 0 01-1 1H5a1 1 0 01-1-1V5zM4 13a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H5a1 1 0 01-1-1v-6zM16 13a1 1 0 011-1h2a1 1 0 011 1v6a1 1 0 01-1 1h-2a1 1 0 01-1-1v-6z"></path>
                    </svg>
                    <p class="text-gray-500 dark:text-gray-400 mt-4">ยังไม่มี templates</p>
                </div>
            </div>
        </div>

        {{-- Paper Sizes Tab --}}
        <div x-show="activeTab === 'paper-sizes'" x-transition>
            <div class="space-y-8">
                <template x-for="(sizes, category) in paperSizes" :key="category">
                    <div>
                        <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                            <span class="w-8 h-8 bg-indigo-100 dark:bg-indigo-900/50 rounded-lg flex items-center justify-center">
                                <svg class="w-4 h-4 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                </svg>
                            </span>
                            <span x-text="categories[category] || category"></span>
                        </h3>

                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
                            <template x-for="size in sizes" :key="size.id">
                                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-4 hover:shadow-md transition">
                                    <div class="flex items-start justify-between mb-3">
                                        <div>
                                            <h4 class="font-bold text-gray-900 dark:text-white" x-text="size.name_th || size.name"></h4>
                                            <p class="text-sm text-gray-500 dark:text-gray-400" x-text="size.width + ' x ' + size.height + ' mm'"></p>
                                        </div>
                                        <div class="w-10 h-10 bg-gray-100 dark:bg-gray-700 rounded-lg flex items-center justify-center"
                                             :style="{ aspectRatio: size.width + '/' + size.height }">
                                            <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                            </svg>
                                        </div>
                                    </div>

                                    <p x-show="size.description" class="text-xs text-gray-500 dark:text-gray-400 mb-3" x-text="size.description"></p>

                                    <div x-show="size.labels_per_row > 1 || size.labels_per_column > 1" class="text-xs text-indigo-600 dark:text-indigo-400 mb-2">
                                        <span x-text="size.labels_per_row * size.labels_per_column"></span> ฉลาก/แผ่น
                                    </div>

                                    <a :href="'{{ route('label-designer.create') }}?width=' + size.width + '&height=' + size.height"
                                       class="w-full inline-flex items-center justify-center gap-1 px-4 py-2 bg-gray-100 dark:bg-gray-700 hover:bg-indigo-100 dark:hover:bg-indigo-900/30 text-gray-700 dark:text-gray-300 hover:text-indigo-600 dark:hover:text-indigo-400 text-sm font-medium rounded-lg transition">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                                        </svg>
                                        ใช้ขนาดนี้
                                    </a>
                                </div>
                            </template>
                        </div>
                    </div>
                </template>
            </div>
        </div>

        {{-- Barcode Formats Tab --}}
        <div x-show="activeTab === 'barcode-formats'" x-transition>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
                <template x-for="format in barcodeFormats" :key="format.code">
                    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg border border-gray-200 dark:border-gray-700 p-6 hover:shadow-xl transition">
                        <div class="w-12 h-12 bg-gradient-to-br from-purple-500 to-pink-500 rounded-xl flex items-center justify-center text-white mb-4">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17V7m0 10a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2h2a2 2 0 012 2m0 10a2 2 0 002 2h2a2 2 0 002-2M9 7a2 2 0 012-2h2a2 2 0 012 2m0 10V7m0 10a2 2 0 002 2h2a2 2 0 002-2V7a2 2 0 00-2-2h-2a2 2 0 00-2 2"></path>
                            </svg>
                        </div>

                        <h3 class="font-bold text-xl text-gray-900 dark:text-white mb-2" x-text="format.name"></h3>
                        <p class="text-gray-500 dark:text-gray-400 text-sm mb-4" x-text="format.description"></p>

                        <div class="space-y-2 text-sm">
                            <div class="flex items-center justify-between">
                                <span class="text-gray-400 dark:text-gray-500">รองรับ:</span>
                                <span class="text-gray-700 dark:text-gray-300" x-text="format.characters"></span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-gray-400 dark:text-gray-500">ความยาว:</span>
                                <span class="text-gray-700 dark:text-gray-300" x-text="format.length"></span>
                            </div>
                        </div>
                    </div>
                </template>
            </div>
        </div>

        {{-- My Labels Tab --}}
        @auth
        <div x-show="activeTab === 'my-labels'" x-transition>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
                <template x-for="label in myLabels" :key="label.id">
                    <div class="group bg-white dark:bg-gray-800 rounded-2xl shadow-lg border border-gray-200 dark:border-gray-700 overflow-hidden hover:shadow-xl transition">
                        <div class="relative h-32 bg-gradient-to-br from-gray-100 to-gray-200 dark:from-gray-700 dark:to-gray-600 p-3">
                            <div class="absolute inset-3 bg-white dark:bg-gray-800 rounded-lg shadow-inner flex items-center justify-center">
                                <span class="text-sm text-gray-400" x-text="label.width + ' x ' + label.height + ' mm'"></span>
                            </div>
                            <button @click="toggleFavorite(label.id)"
                                    class="absolute top-2 right-2 p-2 rounded-full bg-white/80 dark:bg-gray-800/80 hover:bg-white dark:hover:bg-gray-800 transition">
                                <svg :class="label.is_favorite ? 'text-yellow-500' : 'text-gray-400'" class="w-5 h-5" :fill="label.is_favorite ? 'currentColor' : 'none'" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"></path>
                                </svg>
                            </button>
                        </div>

                        <div class="p-4">
                            <h3 class="font-bold text-gray-900 dark:text-white mb-1" x-text="label.name"></h3>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mb-3">
                                พิมพ์แล้ว <span x-text="label.print_count"></span> ครั้ง
                            </p>

                            <div class="flex items-center gap-2">
                                <a :href="'{{ route('label-designer.edit', '') }}/' + label.id"
                                   class="flex-1 inline-flex items-center justify-center gap-1 px-3 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-lg transition">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path>
                                    </svg>
                                    แก้ไข
                                </a>
                                <button @click="duplicateLabel(label.id)"
                                        class="p-2 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 text-gray-600 dark:text-gray-400 rounded-lg transition"
                                        title="คัดลอก">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
                                    </svg>
                                </button>
                                <button @click="deleteLabel(label.id)"
                                        class="p-2 bg-red-50 dark:bg-red-900/20 hover:bg-red-100 dark:hover:bg-red-900/40 text-red-600 dark:text-red-400 rounded-lg transition"
                                        title="ลบ">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                    </svg>
                                </button>
                            </div>
                        </div>
                    </div>
                </template>

                {{-- Empty State --}}
                <div x-show="myLabels.length === 0" class="col-span-full py-12 text-center">
                    <svg class="w-16 h-16 mx-auto text-gray-300 dark:text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
                    </svg>
                    <p class="text-gray-500 dark:text-gray-400 mt-4 mb-4">คุณยังไม่มีฉลากที่บันทึกไว้</p>
                    <a href="{{ route('label-designer.create') }}"
                       class="inline-flex items-center gap-2 px-6 py-3 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl font-medium transition">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                        </svg>
                        สร้างฉลากแรกของคุณ
                    </a>
                </div>
            </div>
        </div>
        @endauth
    </div>

    {{-- Features Section --}}
    <div class="bg-white dark:bg-gray-800 border-t border-gray-200 dark:border-gray-700">
        <div class="container mx-auto px-4 py-16">
            <h2 class="text-3xl font-bold text-center text-gray-900 dark:text-white mb-12">
                เครื่องมือครบครันสำหรับทุกร้านค้า
            </h2>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                <div class="text-center">
                    <div class="w-16 h-16 mx-auto mb-4 bg-gradient-to-br from-blue-500 to-blue-600 rounded-2xl flex items-center justify-center text-white">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 5a1 1 0 011-1h14a1 1 0 011 1v2a1 1 0 01-1 1H5a1 1 0 01-1-1V5zM4 13a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H5a1 1 0 01-1-1v-6zM16 13a1 1 0 011-1h2a1 1 0 011 1v6a1 1 0 01-1 1h-2a1 1 0 01-1-1v-6z"></path>
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-2">Drag & Drop</h3>
                    <p class="text-gray-500 dark:text-gray-400">ลากวางองค์ประกอบง่ายๆ ไม่ต้องมีความรู้ด้านดีไซน์</p>
                </div>

                <div class="text-center">
                    <div class="w-16 h-16 mx-auto mb-4 bg-gradient-to-br from-green-500 to-green-600 rounded-2xl flex items-center justify-center text-white">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"></path>
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-2">หลายรูปแบบ</h3>
                    <p class="text-gray-500 dark:text-gray-400">รองรับ QR Code และบาร์โค้ดหลากหลายมาตรฐาน</p>
                </div>

                <div class="text-center">
                    <div class="w-16 h-16 mx-auto mb-4 bg-gradient-to-br from-purple-500 to-purple-600 rounded-2xl flex items-center justify-center text-white">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path>
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-2">หลายเครื่องพิมพ์</h3>
                    <p class="text-gray-500 dark:text-gray-400">รองรับ Zebra, DYMO, Brother และเครื่องพิมพ์ความร้อน</p>
                </div>

                <div class="text-center">
                    <div class="w-16 h-16 mx-auto mb-4 bg-gradient-to-br from-orange-500 to-orange-600 rounded-2xl flex items-center justify-center text-white">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-2">Preview ตรงจริง</h3>
                    <p class="text-gray-500 dark:text-gray-400">ดูตัวอย่างก่อนพิมพ์ พิมพ์ออกมาตรงตามที่ออกแบบ</p>
                </div>

                <div class="text-center">
                    <div class="w-16 h-16 mx-auto mb-4 bg-gradient-to-br from-pink-500 to-pink-600 rounded-2xl flex items-center justify-center text-white">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"></path>
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-2">Export PDF</h3>
                    <p class="text-gray-500 dark:text-gray-400">ดาวน์โหลดเป็น PDF เพื่อพิมพ์หรือแชร์</p>
                </div>

                <div class="text-center">
                    <div class="w-16 h-16 mx-auto mb-4 bg-gradient-to-br from-teal-500 to-teal-600 rounded-2xl flex items-center justify-center text-white">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"></path>
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-2">ฟรี 100%</h3>
                    <p class="text-gray-500 dark:text-gray-400">ใช้งานฟรีไม่จำกัด สำหรับร้านค้าทุกขนาด</p>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
/**
 * Label Designer App - Alpine.js Component
 *
 * จัดการการแสดงผลและ interaction ของหน้า Label Designer
 */
function labelDesignerApp() {
    return {
        // State
        activeTab: 'templates',
        showTemplates: false,
        templates: [],
        paperSizes: {},
        categories: {},
        barcodeFormats: [],
        myLabels: [],
        stats: {
            templates: 0,
            paperSizes: 0
        },

        /**
         * เริ่มต้น component
         */
        async init() {
            await this.loadTemplates();
            await this.loadPaperSizes();
            await this.loadBarcodeFormats();
            @auth
            await this.loadMyLabels();
            @endauth
        },

        /**
         * โหลด templates
         */
        async loadTemplates() {
            try {
                const response = await fetch('{{ route('label-designer.api.templates') }}');
                const data = await response.json();
                if (data.success) {
                    this.templates = data.data;
                    this.stats.templates = this.templates.length;
                }
            } catch (error) {
                console.error('Error loading templates:', error);
            }
        },

        /**
         * โหลดขนาดกระดาษ
         */
        async loadPaperSizes() {
            try {
                const response = await fetch('{{ route('label-designer.api.paper-sizes') }}');
                const data = await response.json();
                if (data.success) {
                    // จัดกลุ่มตาม category
                    const grouped = {};
                    let total = 0;
                    data.data.forEach(size => {
                        if (!grouped[size.category]) {
                            grouped[size.category] = [];
                        }
                        grouped[size.category].push(size);
                        total++;
                    });
                    this.paperSizes = grouped;
                    this.categories = data.categories;
                    this.stats.paperSizes = total;
                }
            } catch (error) {
                console.error('Error loading paper sizes:', error);
            }
        },

        /**
         * โหลดรูปแบบบาร์โค้ด
         */
        async loadBarcodeFormats() {
            try {
                const response = await fetch('{{ route('label-designer.api.barcode-formats') }}');
                const data = await response.json();
                if (data.success) {
                    this.barcodeFormats = data.data;
                }
            } catch (error) {
                console.error('Error loading barcode formats:', error);
            }
        },

        /**
         * โหลดฉลากของ user
         */
        async loadMyLabels() {
            try {
                const response = await fetch('{{ route('label-designer.my-labels') }}', {
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });
                const data = await response.json();
                if (data.success) {
                    this.myLabels = data.data.data || [];
                }
            } catch (error) {
                console.error('Error loading my labels:', error);
            }
        },

        /**
         * สลับสถานะรายการโปรด
         */
        async toggleFavorite(id) {
            try {
                const response = await fetch(`/label-designer/favorite/${id}`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    }
                });
                const data = await response.json();
                if (data.success) {
                    // อัปเดต state
                    const label = this.myLabels.find(l => l.id === id);
                    if (label) {
                        label.is_favorite = data.is_favorite;
                    }
                }
            } catch (error) {
                console.error('Error toggling favorite:', error);
            }
        },

        /**
         * คัดลอกฉลาก
         */
        async duplicateLabel(id) {
            try {
                const response = await fetch(`/label-designer/duplicate/${id}`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    }
                });
                const data = await response.json();
                if (data.success) {
                    // โหลดรายการใหม่
                    await this.loadMyLabels();
                    alert('คัดลอกฉลากสำเร็จ');
                }
            } catch (error) {
                console.error('Error duplicating label:', error);
            }
        },

        /**
         * ลบฉลาก
         */
        async deleteLabel(id) {
            if (!confirm('ต้องการลบฉลากนี้หรือไม่?')) return;

            try {
                const response = await fetch(`/label-designer/delete/${id}`, {
                    method: 'DELETE',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    }
                });
                const data = await response.json();
                if (data.success) {
                    // ลบออกจาก state
                    this.myLabels = this.myLabels.filter(l => l.id !== id);
                }
            } catch (error) {
                console.error('Error deleting label:', error);
            }
        }
    };
}
</script>
@endpush
@endsection
