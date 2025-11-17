@extends('layouts.admin-v3')

@section('title', 'รายละเอียดสินค้า/บริการ')

@section('content')
<div class="min-h-screen bg-gradient-to-br from-emerald-50 via-teal-50 to-cyan-50 dark:from-gray-900 dark:via-gray-800 dark:to-gray-900 p-6">
    <!-- Language Switcher -->
    <div class="flex justify-end mb-6" x-data="{ open: false }">
        <div class="relative">
            <button @click="open = !open"
                    class="flex items-center gap-2 px-4 py-2 bg-white/80 dark:bg-gray-800/80 backdrop-blur-sm rounded-xl border-2 border-emerald-200 dark:border-emerald-700 hover:border-emerald-400 dark:hover:border-emerald-500 transition-all shadow-lg">
                <svg class="w-5 h-5 text-emerald-600 dark:text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5h12M9 3v2m1.048 9.5A18.022 18.022 0 016.412 9m6.088 9h7M11 21l5-10 5 10M12.751 5C11.783 10.77 8.07 15.61 3 18.129"></path>
                </svg>
                <span class="font-medium text-gray-700 dark:text-gray-300" data-translate>ภาษา</span>
                <svg class="w-4 h-4 text-gray-500 dark:text-gray-400" :class="{ 'rotate-180': open }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                </svg>
            </button>

            <div x-show="open"
                 @click.away="open = false"
                 x-transition:enter="transition ease-out duration-200"
                 x-transition:enter-start="opacity-0 scale-95"
                 x-transition:enter-end="opacity-100 scale-100"
                 x-transition:leave="transition ease-in duration-150"
                 x-transition:leave-start="opacity-100 scale-100"
                 x-transition:leave-end="opacity-0 scale-95"
                 class="absolute right-0 mt-2 w-48 bg-white dark:bg-gray-800 rounded-xl shadow-2xl border-2 border-emerald-100 dark:border-emerald-900 overflow-hidden z-50">
                <button onclick="switchLanguage('th')" class="w-full px-4 py-3 text-left hover:bg-emerald-50 dark:hover:bg-emerald-900/20 flex items-center gap-3 transition-colors border-b border-gray-100 dark:border-gray-700">
                    <span class="text-2xl">🇹🇭</span>
                    <span class="font-medium text-gray-700 dark:text-gray-300">ไทย</span>
                </button>
                <button onclick="switchLanguage('en')" class="w-full px-4 py-3 text-left hover:bg-emerald-50 dark:hover:bg-emerald-900/20 flex items-center gap-3 transition-colors border-b border-gray-100 dark:border-gray-700">
                    <span class="text-2xl">🇬🇧</span>
                    <span class="font-medium text-gray-700 dark:text-gray-300">English</span>
                </button>
                <button onclick="switchLanguage('zh')" class="w-full px-4 py-3 text-left hover:bg-emerald-50 dark:hover:bg-emerald-900/20 flex items-center gap-3 transition-colors border-b border-gray-100 dark:border-gray-700">
                    <span class="text-2xl">🇨🇳</span>
                    <span class="font-medium text-gray-700 dark:text-gray-300">中文</span>
                </button>
                <button onclick="switchLanguage('ja')" class="w-full px-4 py-3 text-left hover:bg-emerald-50 dark:hover:bg-emerald-900/20 flex items-center gap-3 transition-colors">
                    <span class="text-2xl">🇯🇵</span>
                    <span class="font-medium text-gray-700 dark:text-gray-300">日本語</span>
                </button>
            </div>
        </div>
    </div>

    <!-- Header -->
    <div class="mb-8 relative overflow-hidden bg-gradient-to-r from-emerald-500 via-teal-600 to-cyan-600 rounded-3xl shadow-2xl p-8">
        <div class="absolute top-0 right-0 -mt-4 -mr-4 w-32 h-32 bg-white/10 rounded-full blur-3xl"></div>
        <div class="absolute bottom-0 left-0 -mb-4 -ml-4 w-40 h-40 bg-teal-400/10 rounded-full blur-3xl"></div>

        <div class="relative flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
            <div class="flex items-center gap-4">
                <a href="{{ route('admin.accounting.products.index') }}"
                   class="p-3 bg-white/20 hover:bg-white/30 backdrop-blur-sm rounded-xl transition-all group">
                    <svg class="w-6 h-6 text-white group-hover:-translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                    </svg>
                </a>
                <div>
                    <h1 class="text-4xl font-bold text-white mb-2 flex items-center gap-3">
                        <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                        </svg>
                        <span data-translate>รายละเอียดสินค้า/บริการ</span>
                    </h1>
                    <p class="text-emerald-50 text-lg flex items-center gap-2">
                        <span data-translate>รหัส:</span>
                        <span class="font-bold">{{ $product->code }}</span>
                    </p>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="flex flex-wrap gap-3">
                @if(auth()->user()->hasPermission('accounting.edit_products') || auth()->user()->isSuperAdmin())
                <a href="{{ route('admin.accounting.products.edit', $product) }}"
                   class="px-6 py-3 bg-white/20 hover:bg-white/30 backdrop-blur-sm text-white rounded-xl transition-all font-medium flex items-center gap-2 group">
                    <svg class="w-5 h-5 group-hover:scale-110 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                    </svg>
                    <span data-translate>แก้ไข</span>
                </a>
                @endif

                @if(auth()->user()->hasPermission('accounting.delete_products') || auth()->user()->isSuperAdmin())
                <form action="{{ route('admin.accounting.products.destroy', $product) }}" method="POST"
                      onsubmit="return confirm('ยืนยันการลบสินค้า/บริการนี้?');">
                    @csrf
                    @method('DELETE')
                    <button type="submit"
                            class="px-6 py-3 bg-red-500/80 hover:bg-red-600 backdrop-blur-sm text-white rounded-xl transition-all font-medium flex items-center gap-2 group">
                        <svg class="w-5 h-5 group-hover:scale-110 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                        </svg>
                        <span data-translate>ลบ</span>
                    </button>
                </form>
                @endif
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 max-w-7xl mx-auto">
        <!-- Product Details (Left Column - 2/3) -->
        <div class="lg:col-span-2">
            <div class="bg-white/80 dark:bg-gray-800/80 backdrop-blur-sm rounded-2xl shadow-2xl overflow-hidden border-2 border-emerald-100 dark:border-emerald-900">
                <div class="bg-gradient-to-r from-emerald-500 to-teal-600 px-6 py-4">
                    <h2 class="text-2xl font-bold text-white flex items-center gap-2">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                        </svg>
                        <span data-translate>ข้อมูลสินค้า/บริการ</span>
                    </h2>
                </div>

                <div class="p-8 space-y-6">
                    <!-- Code and Type Row -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="bg-gradient-to-br from-blue-50 to-blue-100 dark:from-blue-900/20 dark:to-blue-800/20 rounded-xl p-5 border-2 border-blue-200 dark:border-blue-800">
                            <label class="text-sm font-medium text-blue-600 dark:text-blue-400 flex items-center gap-2 mb-2">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 20l4-16m2 16l4-16M6 9h14M4 15h14"></path>
                                </svg>
                                <span data-translate>รหัสสินค้า</span>
                            </label>
                            <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $product->code }}</p>
                        </div>

                        <div class="bg-gradient-to-br from-purple-50 to-purple-100 dark:from-purple-900/20 dark:to-purple-800/20 rounded-xl p-5 border-2 border-purple-200 dark:border-purple-800">
                            <label class="text-sm font-medium text-purple-600 dark:text-purple-400 flex items-center gap-2 mb-2">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"></path>
                                </svg>
                                <span data-translate>ประเภท</span>
                            </label>
                            <p class="text-2xl font-bold text-gray-900 dark:text-white flex items-center gap-2">
                                @if($product->type === 'goods')
                                    <svg class="w-6 h-6 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                                    </svg>
                                    <span data-translate>สินค้า</span>
                                @else
                                    <svg class="w-6 h-6 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                                    </svg>
                                    <span data-translate>บริการ</span>
                                @endif
                            </p>
                        </div>
                    </div>

                    <!-- Name -->
                    <div class="bg-gradient-to-br from-emerald-50 to-teal-50 dark:from-emerald-900/20 dark:to-teal-900/20 rounded-xl p-5 border-2 border-emerald-200 dark:border-emerald-800">
                        <label class="text-sm font-medium text-emerald-600 dark:text-emerald-400 flex items-center gap-2 mb-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path>
                            </svg>
                            <span data-translate>ชื่อสินค้า/บริการ</span>
                        </label>
                        <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $product->name }}</p>
                    </div>

                    <!-- Description -->
                    @if($product->description)
                    <div class="bg-gradient-to-br from-gray-50 to-gray-100 dark:from-gray-700/50 dark:to-gray-800/50 rounded-xl p-5 border-2 border-gray-200 dark:border-gray-700">
                        <label class="text-sm font-medium text-gray-600 dark:text-gray-400 flex items-center gap-2 mb-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                            </svg>
                            <span data-translate>รายละเอียด</span>
                        </label>
                        <p class="text-lg text-gray-900 dark:text-white leading-relaxed">{{ $product->description }}</p>
                    </div>
                    @endif

                    <!-- Prices Row -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="bg-gradient-to-br from-green-50 to-emerald-100 dark:from-green-900/20 dark:to-emerald-800/20 rounded-xl p-5 border-2 border-green-200 dark:border-green-800">
                            <label class="text-sm font-medium text-green-600 dark:text-green-400 flex items-center gap-2 mb-2">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                                <span data-translate>ราคาขาย</span>
                            </label>
                            <p class="text-3xl font-bold text-green-600 dark:text-green-400">
                                ฿{{ number_format($product->sale_price, 2) }}
                            </p>
                        </div>

                        <div class="bg-gradient-to-br from-orange-50 to-orange-100 dark:from-orange-900/20 dark:to-orange-800/20 rounded-xl p-5 border-2 border-orange-200 dark:border-orange-800">
                            <label class="text-sm font-medium text-orange-600 dark:text-orange-400 flex items-center gap-2 mb-2">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
                                </svg>
                                <span data-translate>ราคาต้นทุน</span>
                            </label>
                            <p class="text-3xl font-bold text-orange-600 dark:text-orange-400">
                                ฿{{ number_format($product->cost_price ?? 0, 2) }}
                            </p>
                        </div>
                    </div>

                    <!-- Status with Animation -->
                    <div class="bg-gradient-to-br from-indigo-50 to-indigo-100 dark:from-indigo-900/20 dark:to-indigo-800/20 rounded-xl p-5 border-2 border-indigo-200 dark:border-indigo-800">
                        <label class="text-sm font-medium text-indigo-600 dark:text-indigo-400 flex items-center gap-2 mb-3">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                            </svg>
                            <span data-translate>สถานะ</span>
                        </label>
                        <div class="inline-flex items-center gap-3 px-6 py-3 bg-white/50 dark:bg-gray-800/50 backdrop-blur-sm rounded-xl">
                            <span class="relative flex h-4 w-4">
                                @if($product->is_active)
                                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-green-400 opacity-75"></span>
                                <span class="relative inline-flex rounded-full h-4 w-4 bg-green-500"></span>
                                @else
                                <span class="relative inline-flex rounded-full h-4 w-4 bg-gray-400"></span>
                                @endif
                            </span>
                            <span class="text-xl font-bold {{ $product->is_active ? 'text-green-600 dark:text-green-400' : 'text-gray-600 dark:text-gray-400' }}" data-translate>
                                {{ $product->is_active ? 'ใช้งาน' : 'ไม่ใช้งาน' }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Additional Info (Right Column - 1/3) -->
        <div>
            <div class="bg-white/80 dark:bg-gray-800/80 backdrop-blur-sm rounded-2xl shadow-2xl overflow-hidden border-2 border-emerald-100 dark:border-emerald-900">
                <div class="bg-gradient-to-r from-emerald-500 to-teal-600 px-6 py-4">
                    <h2 class="text-2xl font-bold text-white flex items-center gap-2">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <span data-translate>ข้อมูลเพิ่มเติม</span>
                    </h2>
                </div>

                <div class="p-6 space-y-5">
                    <!-- Created At -->
                    <div class="bg-gradient-to-br from-blue-50 to-indigo-100 dark:from-blue-900/20 dark:to-indigo-800/20 rounded-xl p-4 border-2 border-blue-200 dark:border-blue-800">
                        <label class="text-sm font-medium text-blue-600 dark:text-blue-400 flex items-center gap-2 mb-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                            </svg>
                            <span data-translate>สร้างเมื่อ</span>
                        </label>
                        <p class="text-lg font-semibold text-gray-900 dark:text-white">
                            {{ $product->created_at->format('d/m/Y H:i') }}
                        </p>
                    </div>

                    <!-- Updated At -->
                    <div class="bg-gradient-to-br from-purple-50 to-pink-100 dark:from-purple-900/20 dark:to-pink-800/20 rounded-xl p-4 border-2 border-purple-200 dark:border-purple-800">
                        <label class="text-sm font-medium text-purple-600 dark:text-purple-400 flex items-center gap-2 mb-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                            </svg>
                            <span data-translate>แก้ไขล่าสุด</span>
                        </label>
                        <p class="text-lg font-semibold text-gray-900 dark:text-white">
                            {{ $product->updated_at->format('d/m/Y H:i') }}
                        </p>
                    </div>

                    <!-- Creator (if exists) -->
                    @if(isset($product->creator))
                    <div class="bg-gradient-to-br from-emerald-50 to-green-100 dark:from-emerald-900/20 dark:to-green-800/20 rounded-xl p-4 border-2 border-emerald-200 dark:border-emerald-800">
                        <label class="text-sm font-medium text-emerald-600 dark:text-emerald-400 flex items-center gap-2 mb-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                            </svg>
                            <span data-translate>ผู้สร้าง</span>
                        </label>
                        <p class="text-lg font-semibold text-gray-900 dark:text-white">
                            {{ $product->creator->name }}
                        </p>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
