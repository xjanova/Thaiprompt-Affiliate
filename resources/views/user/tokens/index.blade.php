@extends('layouts.user-arrow-x')

@section('title', 'ตลาด TPIX Token')

@section('content')
{{-- Alpine.js Component สำหรับ Token Marketplace --}}
<div x-data="tokenMarketplace()" x-init="init()" class="min-h-screen bg-gradient-to-br from-gray-50 to-gray-100 dark:from-gray-900 dark:to-gray-800 py-8">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

        {{-- Hero Section with Search --}}
        <div class="relative overflow-hidden rounded-3xl bg-gradient-to-r from-blue-600 via-purple-600 to-pink-600 p-1 mb-8">
            <div class="bg-white dark:bg-gray-900 rounded-[22px] p-8 md:p-12">
                {{-- Header --}}
                <div class="text-center mb-8">
                    <div class="inline-flex items-center justify-center w-20 h-20 rounded-full bg-gradient-to-br from-yellow-400 to-orange-500 shadow-xl mb-4 animate-pulse">
                        <svg class="w-10 h-10 text-white" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M8.433 7.418c.155-.103.346-.196.567-.267v1.698a2.305 2.305 0 01-.567-.267C8.07 8.34 8 8.114 8 8c0-.114.07-.34.433-.582zM11 12.849v-1.698c.22.071.412.164.567.267.364.243.433.468.433.582 0 .114-.07.34-.433.582a2.305 2.305 0 01-.567.267z"/>
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-13a1 1 0 10-2 0v.092a4.535 4.535 0 00-1.676.662C6.602 6.234 6 7.009 6 8c0 .99.602 1.765 1.324 2.246.48.32 1.054.545 1.676.662v1.941c-.391-.127-.68-.317-.843-.504a1 1 0 10-1.51 1.31c.562.649 1.413 1.076 2.353 1.253V15a1 1 0 102 0v-.092a4.535 4.535 0 001.676-.662C13.398 13.766 14 12.991 14 12c0-.99-.602-1.765-1.324-2.246A4.535 4.535 0 0011 9.092V7.151c.391.127.68.317.843.504a1 1 0 101.511-1.31c-.563-.649-1.413-1.076-2.354-1.253V5z" clip-rule="evenodd"/>
                        </svg>
                    </div>
                    <h1 class="text-4xl md:text-5xl font-bold text-transparent bg-clip-text bg-gradient-to-r from-blue-600 to-purple-600 dark:from-blue-400 dark:to-purple-400 mb-3">
                        ตลาด TPIX Token
                    </h1>
                    <p class="text-xl text-gray-600 dark:text-gray-300 max-w-2xl mx-auto">
                        ค้นพบ ซื้อขาย และสร้าง Token ของคุณเองบน TPIX Blockchain
                    </p>
                </div>

                {{-- Search Bar --}}
                <div class="max-w-3xl mx-auto mb-6">
                    <form method="GET" class="relative">
                        <div class="relative group">
                            <div class="absolute inset-0 bg-gradient-to-r from-blue-500 to-purple-500 rounded-2xl blur opacity-25 group-hover:opacity-40 transition duration-200"></div>
                            <div class="relative flex items-center bg-white dark:bg-gray-800 rounded-2xl shadow-xl overflow-hidden">
                                <input
                                    type="text"
                                    name="search"
                                    x-model="searchQuery"
                                    placeholder="ค้นหา Token ด้วยชื่อหรือสัญลักษณ์..."
                                    value="{{ request('search') }}"
                                    class="w-full px-6 py-4 text-lg bg-transparent border-0 text-gray-900 dark:text-white placeholder-gray-400 focus:outline-none focus:ring-0"
                                />
                                <button type="submit" class="px-8 py-4 bg-gradient-to-r from-blue-600 to-purple-600 hover:from-blue-700 hover:to-purple-700 text-white font-semibold transition duration-200 flex items-center gap-2">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                                    </svg>
                                    <span class="hidden sm:inline">ค้นหา</span>
                                </button>
                            </div>
                        </div>
                    </form>
                </div>

                {{-- Quick Actions --}}
                <div class="flex flex-wrap justify-center gap-3">
                    <a href="{{ route('user.tokens.create') }}"
                       class="group relative inline-flex items-center gap-2 px-6 py-3 bg-gradient-to-r from-green-500 to-emerald-600 text-white font-semibold rounded-xl shadow-lg hover:shadow-xl hover:scale-[1.02] transition duration-200">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                        </svg>
                        สร้าง Token ของคุณ
                    </a>
                    <a href="{{ route('user.tokens.my-tokens') }}"
                       class="group relative inline-flex items-center gap-2 px-6 py-3 bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-200 font-semibold rounded-xl shadow-lg hover:shadow-xl hover:scale-[1.02] transition duration-200 border border-gray-200 dark:border-gray-700">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9"/>
                        </svg>
                        Token ของฉัน
                    </a>
                </div>
            </div>
        </div>

        {{-- Filter Categories --}}
        <div class="mb-8">
            <div class="flex items-center gap-3 overflow-x-auto pb-2 scrollbar-hide">
                <button @click="filterCategory('')"
                        :class="currentCategory === '' ? 'bg-gradient-to-r from-blue-600 to-purple-600 text-white' : 'bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 hover:shadow-lg'"
                        class="flex-shrink-0 px-6 py-3 rounded-xl font-medium transition duration-200 shadow-md border border-gray-200 dark:border-gray-700">
                    ทั้งหมด
                </button>
                <button @click="filterCategory('verified')"
                        :class="currentCategory === 'verified' ? 'bg-gradient-to-r from-blue-600 to-purple-600 text-white' : 'bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 hover:shadow-lg'"
                        class="flex-shrink-0 px-6 py-3 rounded-xl font-medium transition duration-200 shadow-md border border-gray-200 dark:border-gray-700 flex items-center gap-2">
                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M6.267 3.455a3.066 3.066 0 001.745-.723 3.066 3.066 0 013.976 0 3.066 3.066 0 001.745.723 3.066 3.066 0 012.812 2.812c.051.643.304 1.254.723 1.745a3.066 3.066 0 010 3.976 3.066 3.066 0 00-.723 1.745 3.066 3.066 0 01-2.812 2.812 3.066 3.066 0 00-1.745.723 3.066 3.066 0 01-3.976 0 3.066 3.066 0 00-1.745-.723 3.066 3.066 0 01-2.812-2.812 3.066 3.066 0 00-.723-1.745 3.066 3.066 0 010-3.976 3.066 3.066 0 00.723-1.745 3.066 3.066 0 012.812-2.812zm7.44 5.252a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                    </svg>
                    ตัวตรวจสอบแล้ว
                </button>
                <template x-for="cat in categories" :key="cat.value">
                    <button @click="filterCategory(cat.value)"
                            :class="currentCategory === cat.value ? 'bg-gradient-to-r from-blue-600 to-purple-600 text-white' : 'bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 hover:shadow-lg'"
                            class="flex-shrink-0 px-6 py-3 rounded-xl font-medium transition duration-200 shadow-md border border-gray-200 dark:border-gray-700"
                            x-text="cat.emoji + ' ' + cat.label"></button>
                </template>
            </div>
        </div>

        {{-- Sorting --}}
        <div class="flex justify-between items-center mb-6">
            <h2 class="text-2xl font-bold text-gray-900 dark:text-white flex items-center gap-2">
                <svg class="w-6 h-6 text-orange-500" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M12.395 2.553a1 1 0 00-1.45-.385c-.345.23-.614.558-.822.88-.214.33-.403.713-.57 1.116-.334.804-.614 1.768-.84 2.734a31.365 31.365 0 00-.613 3.58 2.64 2.64 0 01-.945-1.067c-.328-.68-.398-1.534-.398-2.654A1 1 0 005.05 6.05 6.981 6.981 0 003 11a7 7 0 1011.95-4.95c-.592-.591-.98-.985-1.348-1.467-.363-.476-.724-1.063-1.207-2.03zM12.12 15.12A3 3 0 017 13s.879.5 2.5.5c0-1 .5-4 1.25-4.5.5 1 .786 1.293 1.371 1.879A2.99 2.99 0 0113 13a2.99 2.99 0 01-.879 2.121z" clip-rule="evenodd"/>
                </svg>
                Token ทั้งหมด
            </h2>
            <div class="relative" x-data="{ open: false }">
                <button @click="open = !open"
                        class="flex items-center gap-2 px-4 py-2 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl shadow-md hover:shadow-lg transition duration-200 text-gray-700 dark:text-gray-300">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4h13M3 8h9m-9 4h6m4 0l4-4m0 0l4 4m-4-4v12"/>
                    </svg>
                    <span x-text="currentSort"></span>
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                    </svg>
                </button>
                <div x-show="open" @click.away="open = false"
                     class="absolute right-0 mt-2 w-48 bg-white dark:bg-gray-800 rounded-xl shadow-xl border border-gray-200 dark:border-gray-700 z-10 overflow-hidden"
                     style="display: none;">
                    <a @click="sortTokens('market_cap'); open = false"
                       class="block px-4 py-3 hover:bg-gray-50 dark:hover:bg-gray-700 cursor-pointer text-gray-700 dark:text-gray-300 transition">
                        Market Cap สูงสุด
                    </a>
                    <a @click="sortTokens('volume'); open = false"
                       class="block px-4 py-3 hover:bg-gray-50 dark:hover:bg-gray-700 cursor-pointer text-gray-700 dark:text-gray-300 transition">
                        Volume สูงสุด
                    </a>
                    <a @click="sortTokens('price'); open = false"
                       class="block px-4 py-3 hover:bg-gray-50 dark:hover:bg-gray-700 cursor-pointer text-gray-700 dark:text-gray-300 transition">
                        ราคาสูงสุด
                    </a>
                    <a @click="sortTokens('holders'); open = false"
                       class="block px-4 py-3 hover:bg-gray-50 dark:hover:bg-gray-700 cursor-pointer text-gray-700 dark:text-gray-300 transition">
                        Holders มากสุด
                    </a>
                </div>
            </div>
        </div>

        {{-- Featured Tokens --}}
        @if($featuredTokens->isNotEmpty())
        <div class="mb-10">
            <h3 class="text-2xl font-bold text-gray-900 dark:text-white mb-6 flex items-center gap-2">
                <svg class="w-6 h-6 text-yellow-500" fill="currentColor" viewBox="0 0 20 20">
                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                </svg>
                Token แนะนำ
            </h3>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                @foreach($featuredTokens as $token)
                <div class="group relative">
                    {{-- Gradient Border Effect --}}
                    <div class="absolute -inset-0.5 bg-gradient-to-r from-pink-500 via-purple-500 to-blue-500 rounded-2xl blur opacity-30 group-hover:opacity-60 transition duration-300"></div>

                    {{-- Card Content --}}
                    <div class="relative bg-white dark:bg-gray-800 rounded-2xl p-6 hover:shadow-2xl transition duration-300">
                        {{-- Featured Badge --}}
                        <div class="absolute -top-3 -right-3 px-4 py-1 bg-gradient-to-r from-yellow-400 to-orange-500 text-white text-xs font-bold rounded-full shadow-lg">
                            แนะนำ
                        </div>

                        {{-- Token Header --}}
                        <div class="flex items-start gap-4 mb-4">
                            @if($token->logo)
                                <img src="{{ asset('storage/' . $token->logo) }}"
                                     alt="{{ $token->symbol }}"
                                     class="w-16 h-16 rounded-full ring-4 ring-blue-100 dark:ring-blue-900 object-cover">
                            @else
                                <div class="w-16 h-16 rounded-full bg-gradient-to-br from-blue-500 to-purple-600 flex items-center justify-center text-white text-2xl font-bold ring-4 ring-blue-100 dark:ring-blue-900">
                                    {{ substr($token->symbol, 0, 1) }}
                                </div>
                            @endif
                            <div class="flex-1">
                                <div class="flex items-center gap-2 mb-1">
                                    <h4 class="text-lg font-bold text-gray-900 dark:text-white">{{ $token->name }}</h4>
                                    @if($token->is_verified)
                                        <svg class="w-5 h-5 text-blue-500" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M6.267 3.455a3.066 3.066 0 001.745-.723 3.066 3.066 0 013.976 0 3.066 3.066 0 001.745.723 3.066 3.066 0 012.812 2.812c.051.643.304 1.254.723 1.745a3.066 3.066 0 010 3.976 3.066 3.066 0 00-.723 1.745 3.066 3.066 0 01-2.812 2.812 3.066 3.066 0 00-1.745.723 3.066 3.066 0 01-3.976 0 3.066 3.066 0 00-1.745-.723 3.066 3.066 0 01-2.812-2.812 3.066 3.066 0 00-.723-1.745 3.066 3.066 0 010-3.976 3.066 3.066 0 00.723-1.745 3.066 3.066 0 012.812-2.812zm7.44 5.252a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                        </svg>
                                    @endif
                                </div>
                                <p class="text-sm text-gray-500 dark:text-gray-400">{{ $token->symbol }}</p>
                            </div>
                        </div>

                        {{-- Stats --}}
                        <div class="grid grid-cols-2 gap-4 mb-4">
                            <div>
                                <p class="text-xs text-gray-500 dark:text-gray-400 mb-1">ราคา</p>
                                <p class="text-lg font-bold text-gray-900 dark:text-white">{{ number_format($token->current_price_tpix, 4) }}</p>
                                @if($token->price_change_24h)
                                    <p class="text-xs {{ $token->price_change_24h > 0 ? 'text-green-500' : 'text-red-500' }}">
                                        {{ $token->price_change_24h > 0 ? '+' : '' }}{{ number_format($token->price_change_24h, 2) }}%
                                    </p>
                                @endif
                            </div>
                            <div>
                                <p class="text-xs text-gray-500 dark:text-gray-400 mb-1">Market Cap</p>
                                <p class="text-lg font-bold text-gray-900 dark:text-white">{{ number_format($token->market_cap_tpix ?? 0, 0) }}</p>
                            </div>
                        </div>

                        {{-- View Button --}}
                        <a href="{{ route('user.tokens.show', $token->id) }}"
                           class="block w-full py-3 bg-gradient-to-r from-blue-600 to-purple-600 hover:from-blue-700 hover:to-purple-700 text-white text-center font-semibold rounded-xl transition duration-200">
                            ดูรายละเอียด
                        </a>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
        @endif

        {{-- All Tokens Grid --}}
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6 mb-8">
            @forelse($tokens as $token)
            <div class="group relative bg-white dark:bg-gray-800 rounded-2xl p-6 border border-gray-200 dark:border-gray-700 hover:shadow-2xl  transition duration-300">
                {{-- Token Header --}}
                <div class="flex items-center gap-3 mb-4">
                    @if($token->logo)
                        <img src="{{ asset('storage/' . $token->logo) }}"
                             alt="{{ $token->symbol }}"
                             class="w-12 h-12 rounded-full ring-2 ring-gray-200 dark:ring-gray-700 object-cover">
                    @else
                        <div class="w-12 h-12 rounded-full bg-gradient-to-br from-blue-400 to-purple-500 flex items-center justify-center text-white text-lg font-bold ring-2 ring-gray-200 dark:ring-gray-700">
                            {{ substr($token->symbol, 0, 1) }}
                        </div>
                    @endif
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-1 mb-0.5">
                            <h5 class="font-bold text-gray-900 dark:text-white truncate">{{ $token->name }}</h5>
                            @if($token->is_verified)
                                <svg class="w-4 h-4 text-blue-500 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M6.267 3.455a3.066 3.066 0 001.745-.723 3.066 3.066 0 013.976 0 3.066 3.066 0 001.745.723 3.066 3.066 0 012.812 2.812c.051.643.304 1.254.723 1.745a3.066 3.066 0 010 3.976 3.066 3.066 0 00-.723 1.745 3.066 3.066 0 01-2.812 2.812 3.066 3.066 0 00-1.745.723 3.066 3.066 0 01-3.976 0 3.066 3.066 0 00-1.745-.723 3.066 3.066 0 01-2.812-2.812 3.066 3.066 0 00-.723-1.745 3.066 3.066 0 010-3.976 3.066 3.066 0 00.723-1.745 3.066 3.066 0 012.812-2.812zm7.44 5.252a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                </svg>
                            @endif
                        </div>
                        <p class="text-sm text-gray-500 dark:text-gray-400">{{ $token->symbol }}</p>
                    </div>
                </div>

                {{-- Stats --}}
                <div class="space-y-2 mb-4">
                    <div class="flex justify-between items-center">
                        <span class="text-xs text-gray-500 dark:text-gray-400">ราคา</span>
                        <span class="font-semibold text-gray-900 dark:text-white">{{ number_format($token->current_price_tpix, 4) }}</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-xs text-gray-500 dark:text-gray-400">เปลี่ยนแปลง 24h</span>
                        <span class="font-semibold {{ ($token->price_change_24h ?? 0) > 0 ? 'text-green-500' : 'text-red-500' }}">
                            {{ ($token->price_change_24h ?? 0) > 0 ? '+' : '' }}{{ number_format($token->price_change_24h ?? 0, 2) }}%
                        </span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-xs text-gray-500 dark:text-gray-400">Holders</span>
                        <span class="font-semibold text-gray-900 dark:text-white">{{ number_format($token->holders_count ?? 0) }}</span>
                    </div>
                </div>

                {{-- View Button --}}
                <a href="{{ route('user.tokens.show', $token->id) }}"
                   class="block w-full py-2.5 bg-gray-100 dark:bg-gray-700 hover:bg-gradient-to-r hover:from-blue-600 hover:to-purple-600 text-gray-700 dark:text-gray-200 hover:text-white text-center font-medium rounded-xl transition duration-200 flex items-center justify-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                    </svg>
                    ดูรายละเอียด
                </a>
            </div>
            @empty
            <div class="col-span-full">
                <div class="bg-white dark:bg-gray-800 rounded-2xl p-12 text-center border border-gray-200 dark:border-gray-700">
                    <svg class="w-20 h-20 text-gray-300 dark:text-gray-600 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                    <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-2">ไม่พบ Token</h3>
                    <p class="text-gray-500 dark:text-gray-400">ลองปรับเปลี่ยนคำค้นหาหรือตัวกรอง</p>
                </div>
            </div>
            @endforelse
        </div>

        {{-- Pagination --}}
        <div class="flex justify-center">
            {{ $tokens->links() }}
        </div>
    </div>
</div>

@push('scripts')
<script>
/**
 * Alpine.js Component สำหรับ Token Marketplace
 */
function tokenMarketplace() {
    return {
        // State
        searchQuery: '{{ request('search') }}',
        currentCategory: '{{ request('category') }}',
        currentSort: 'Market Cap สูงสุด',

        // Categories
        categories: [
            { value: 'defi', label: 'DeFi', emoji: '🏦' },
            { value: 'gamefi', label: 'GameFi', emoji: '🎮' },
            { value: 'meme', label: 'Meme', emoji: '😂' },
            { value: 'utility', label: 'Utility', emoji: '🔧' },
            { value: 'nft', label: 'NFT', emoji: '🎨' },
        ],

        /**
         * เริ่มต้น component
         */
        init() {
            console.log('Token Marketplace initialized');
        },

        /**
         * กรอง Token ตาม category
         */
        filterCategory(category) {
            this.currentCategory = category;
            const url = new URL(window.location.href);

            if (category === 'verified') {
                url.searchParams.set('verified', '1');
                url.searchParams.delete('category');
            } else if (category) {
                url.searchParams.set('category', category);
                url.searchParams.delete('verified');
            } else {
                url.searchParams.delete('category');
                url.searchParams.delete('verified');
            }

            window.location.href = url.toString();
        },

        /**
         * เรียงลำดับ Token
         */
        sortTokens(sort) {
            const url = new URL(window.location.href);
            url.searchParams.set('sort', sort);

            // อัพเดท label
            const sortLabels = {
                'market_cap': 'Market Cap สูงสุด',
                'volume': 'Volume สูงสุด',
                'price': 'ราคาสูงสุด',
                'holders': 'Holders มากสุด'
            };
            this.currentSort = sortLabels[sort] || 'Market Cap สูงสุด';

            window.location.href = url.toString();
        }
    };
}
</script>
@endpush

{{-- Custom Styles (minimal, เฉพาะที่จำเป็น) --}}
@push('styles')
<style>
/* Hide scrollbar สำหรับ category filter */
.scrollbar-hide::-webkit-scrollbar {
    display: none;
}
.scrollbar-hide {
    -ms-overflow-style: none;
    scrollbar-width: none;
}
</style>
@endpush
@endsection
