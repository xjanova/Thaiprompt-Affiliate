@extends('layouts.user-arrow-x')

@section('title', 'รายละเอียด ' . $token->name . ' - TPIX')

@section('content')
{{-- Alpine.js Component สำหรับ Token Details --}}
<div x-data="tokenDetails()" x-init="init()" class="min-h-screen bg-gradient-to-br from-gray-50 to-gray-100 dark:from-gray-900 dark:to-gray-800 py-8">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

        {{-- Token Header Card --}}
        <div class="relative overflow-hidden rounded-3xl bg-gradient-to-r from-blue-600 via-purple-600 to-pink-600 p-1 mb-8">
            <div class="bg-white dark:bg-gray-900 rounded-[22px] p-6 md:p-8">
                <div class="grid grid-cols-1 md:grid-cols-12 gap-6 items-center">
                    {{-- Token Logo --}}
                    <div class="md:col-span-2 flex justify-center md:justify-start">
                        @if($token->logo)
                            <img src="{{ asset('storage/' . $token->logo) }}"
                                 alt="{{ $token->symbol }}"
                                 class="w-24 h-24 md:w-32 md:h-32 rounded-full ring-4 ring-white dark:ring-gray-800 shadow-2xl object-cover">
                        @else
                            <div class="w-24 h-24 md:w-32 md:h-32 rounded-full bg-gradient-to-br from-blue-500 to-purple-600 flex items-center justify-center text-white text-5xl font-bold ring-4 ring-white dark:ring-gray-800 shadow-2xl">
                                {{ substr($token->symbol, 0, 1) }}
                            </div>
                        @endif
                    </div>

                    {{-- Token Info --}}
                    <div class="md:col-span-6 text-center md:text-left">
                        <div class="flex flex-col md:flex-row items-center md:items-start gap-3 mb-3">
                            <h1 class="text-3xl md:text-4xl font-bold text-gray-900 dark:text-white">{{ $token->name }}</h1>
                            <div class="flex items-center gap-2">
                                <span class="px-3 py-1 bg-blue-100 dark:bg-blue-900 text-blue-800 dark:text-blue-200 text-sm font-semibold rounded-full">
                                    {{ $token->symbol }}
                                </span>
                                @if($token->is_verified)
                                    <span class="inline-flex items-center gap-1 px-3 py-1 bg-green-100 dark:bg-green-900 text-green-800 dark:text-green-200 text-sm font-semibold rounded-full">
                                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M6.267 3.455a3.066 3.066 0 001.745-.723 3.066 3.066 0 013.976 0 3.066 3.066 0 001.745.723 3.066 3.066 0 012.812 2.812c.051.643.304 1.254.723 1.745a3.066 3.066 0 010 3.976 3.066 3.066 0 00-.723 1.745 3.066 3.066 0 01-2.812 2.812 3.066 3.066 0 00-1.745.723 3.066 3.066 0 01-3.976 0 3.066 3.066 0 00-1.745-.723 3.066 3.066 0 01-2.812-2.812 3.066 3.066 0 00-.723-1.745 3.066 3.066 0 010-3.976 3.066 3.066 0 00.723-1.745 3.066 3.066 0 012.812-2.812zm7.44 5.252a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                        </svg>
                                        ตรวจสอบแล้ว
                                    </span>
                                @endif
                                @if($token->is_featured)
                                    <span class="inline-flex items-center gap-1 px-3 py-1 bg-gradient-to-r from-yellow-400 to-orange-500 text-white text-sm font-semibold rounded-full">
                                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                            <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                                        </svg>
                                        แนะนำ
                                    </span>
                                @endif
                            </div>
                        </div>
                        <div class="flex flex-wrap items-center justify-center md:justify-start gap-3 text-sm text-gray-600 dark:text-gray-400">
                            <span class="flex items-center gap-1">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                </svg>
                                ผู้สร้าง: {{ $token->creator->name ?? 'ไม่ทราบ' }}
                            </span>
                            <span class="flex items-center gap-1">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                {{ $token->created_at->format('d M Y H:i') }}
                            </span>
                        </div>
                    </div>

                    {{-- Price Info --}}
                    <div class="md:col-span-4 text-center md:text-right">
                        <div class="inline-block p-6 bg-gradient-to-br from-blue-50 to-purple-50 dark:from-blue-900/20 dark:to-purple-900/20 rounded-2xl">
                            <p class="text-sm text-gray-600 dark:text-gray-400 mb-1">ราคาปัจจุบัน</p>
                            <p class="text-4xl font-bold text-transparent bg-clip-text bg-gradient-to-r from-blue-600 to-purple-600 dark:from-blue-400 dark:to-purple-400">
                                ฿{{ number_format($token->current_price_tpix ?? 0, 4) }}
                            </p>
                            @if(isset($token->current_price_usd))
                                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">~${{ number_format($token->current_price_usd, 2) }}</p>
                            @endif
                            @if($token->price_change_24h)
                                <p class="text-lg font-semibold mt-2 {{ $token->price_change_24h > 0 ? 'text-green-500' : 'text-red-500' }}">
                                    {{ $token->price_change_24h > 0 ? '+' : '' }}{{ number_format($token->price_change_24h, 2) }}%
                                </p>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            {{-- Left Column: Main Content --}}
            <div class="lg:col-span-2 space-y-6">
                {{-- Stats Cards --}}
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div class="bg-white dark:bg-gray-800 rounded-2xl p-6 border border-gray-200 dark:border-gray-700 hover:shadow-xl transition duration-200">
                        <p class="text-sm text-gray-500 dark:text-gray-400 mb-2">Market Cap</p>
                        <p class="text-2xl font-bold text-gray-900 dark:text-white">฿{{ number_format($token->market_cap_tpix ?? 0, 0) }}</p>
                    </div>
                    <div class="bg-white dark:bg-gray-800 rounded-2xl p-6 border border-gray-200 dark:border-gray-700 hover:shadow-xl transition duration-200">
                        <p class="text-sm text-gray-500 dark:text-gray-400 mb-2">Volume 24h</p>
                        <p class="text-2xl font-bold text-gray-900 dark:text-white">฿{{ number_format($token->volume_24h_tpix ?? 0, 0) }}</p>
                    </div>
                    <div class="bg-white dark:bg-gray-800 rounded-2xl p-6 border border-gray-200 dark:border-gray-700 hover:shadow-xl transition duration-200">
                        <p class="text-sm text-gray-500 dark:text-gray-400 mb-2">Holders</p>
                        <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ number_format($token->holders_count ?? 0) }}</p>
                    </div>
                </div>

                {{-- Description --}}
                @if($token->description)
                <div class="bg-white dark:bg-gray-800 rounded-2xl p-6 border border-gray-200 dark:border-gray-700 shadow-lg">
                    <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                        <svg class="w-6 h-6 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        เกี่ยวกับ Token
                    </h2>
                    <p class="text-gray-700 dark:text-gray-300 leading-relaxed">{{ $token->description }}</p>
                    @if($token->website)
                        <div class="mt-4">
                            <a href="{{ $token->website }}" target="_blank"
                               class="inline-flex items-center gap-2 px-4 py-2 bg-blue-50 dark:bg-blue-900/20 text-blue-600 dark:text-blue-400 hover:bg-blue-100 dark:hover:bg-blue-900/40 rounded-xl transition duration-200">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9"/>
                                </svg>
                                เว็บไซต์
                            </a>
                        </div>
                    @endif
                </div>
                @endif

                {{-- Token Details Table --}}
                <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 shadow-lg overflow-hidden">
                    <div class="px-6 py-4 bg-gradient-to-r from-blue-50 to-purple-50 dark:from-blue-900/20 dark:to-purple-900/20 border-b border-gray-200 dark:border-gray-700">
                        <h2 class="text-xl font-bold text-gray-900 dark:text-white flex items-center gap-2">
                            <svg class="w-6 h-6 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                            </svg>
                            รายละเอียด Token
                        </h2>
                    </div>
                    <div class="p-6">
                        <div class="space-y-4">
                            <div class="flex justify-between items-center py-3 border-b border-gray-100 dark:border-gray-700">
                                <span class="text-gray-600 dark:text-gray-400">Contract Address</span>
                                <span class="font-semibold text-gray-900 dark:text-white">
                                    @if($token->contract_address)
                                        <a href="{{ config('tpix.blockchain.explorer_url', '#') }}/address/{{ $token->contract_address }}"
                                           target="_blank"
                                           class="text-blue-600 dark:text-blue-400 hover:underline inline-flex items-center gap-1">
                                            {{ substr($token->contract_address, 0, 10) }}...{{ substr($token->contract_address, -8) }}
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                                            </svg>
                                        </a>
                                    @else
                                        <span class="text-gray-500 dark:text-gray-400">ยังไม่ Deploy</span>
                                    @endif
                                </span>
                            </div>
                            <div class="flex justify-between items-center py-3 border-b border-gray-100 dark:border-gray-700">
                                <span class="text-gray-600 dark:text-gray-400">Total Supply</span>
                                <span class="font-semibold text-gray-900 dark:text-white">{{ number_format($token->total_supply, 0) }} {{ $token->symbol }}</span>
                            </div>
                            <div class="flex justify-between items-center py-3 border-b border-gray-100 dark:border-gray-700">
                                <span class="text-gray-600 dark:text-gray-400">Circulating Supply</span>
                                <span class="font-semibold text-gray-900 dark:text-white">{{ number_format($token->circulating_supply ?? $token->total_supply, 0) }} {{ $token->symbol }}</span>
                            </div>
                            <div class="flex justify-between items-center py-3">
                                <span class="text-gray-600 dark:text-gray-400">Decimals</span>
                                <span class="font-semibold text-gray-900 dark:text-white">{{ $token->decimals }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Token Features --}}
                <div class="bg-white dark:bg-gray-800 rounded-2xl p-6 border border-gray-200 dark:border-gray-700 shadow-lg">
                    <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                        <svg class="w-6 h-6 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        คุณสมบัติ Token
                    </h2>
                    <div class="grid grid-cols-2 gap-4">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-full {{ $token->is_burnable ? 'bg-green-100 dark:bg-green-900/30' : 'bg-gray-100 dark:bg-gray-700' }} flex items-center justify-center">
                                <svg class="w-5 h-5 {{ $token->is_burnable ? 'text-green-600 dark:text-green-400' : 'text-gray-400' }}" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                </svg>
                            </div>
                            <span class="text-gray-700 dark:text-gray-300 font-medium">Burnable</span>
                        </div>
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-full {{ $token->is_mintable ? 'bg-green-100 dark:bg-green-900/30' : 'bg-gray-100 dark:bg-gray-700' }} flex items-center justify-center">
                                <svg class="w-5 h-5 {{ $token->is_mintable ? 'text-green-600 dark:text-green-400' : 'text-gray-400' }}" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                </svg>
                            </div>
                            <span class="text-gray-700 dark:text-gray-300 font-medium">Mintable</span>
                        </div>
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-full {{ $token->is_pausable ? 'bg-green-100 dark:bg-green-900/30' : 'bg-gray-100 dark:bg-gray-700' }} flex items-center justify-center">
                                <svg class="w-5 h-5 {{ $token->is_pausable ? 'text-green-600 dark:text-green-400' : 'text-gray-400' }}" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                </svg>
                            </div>
                            <span class="text-gray-700 dark:text-gray-300 font-medium">Pausable</span>
                        </div>
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-full {{ $token->can_freeze_addresses ?? false ? 'bg-green-100 dark:bg-green-900/30' : 'bg-gray-100 dark:bg-gray-700' }} flex items-center justify-center">
                                <svg class="w-5 h-5 {{ $token->can_freeze_addresses ?? false ? 'text-green-600 dark:text-green-400' : 'text-gray-400' }}" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                </svg>
                            </div>
                            <span class="text-gray-700 dark:text-gray-300 font-medium">Freezable Addresses</span>
                        </div>
                    </div>
                </div>

                {{-- Recent Transactions --}}
                <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 shadow-lg overflow-hidden">
                    <div class="px-6 py-4 bg-gradient-to-r from-purple-50 to-pink-50 dark:from-purple-900/20 dark:to-pink-900/20 border-b border-gray-200 dark:border-gray-700">
                        <h2 class="text-xl font-bold text-gray-900 dark:text-white flex items-center gap-2">
                            <svg class="w-6 h-6 text-pink-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/>
                            </svg>
                            ธุรกรรมล่าสุด
                        </h2>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead class="bg-gray-50 dark:bg-gray-900/50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">ประเภท</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">จาก</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">ถึง</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">จำนวน</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">เวลา</th>
                                </tr>
                            </thead>
                            <tbody id="recentTransactions" class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                <tr>
                                    <td colspan="5" class="px-6 py-8 text-center text-gray-500 dark:text-gray-400">
                                        <svg class="animate-spin h-8 w-8 text-gray-400 mx-auto mb-2" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                        </svg>
                                        กำลังโหลด...
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            {{-- Right Column: Actions & Info --}}
            <div class="lg:col-span-1 space-y-6">
                {{-- Your Balance --}}
                <div class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-blue-600 via-purple-600 to-pink-600 p-1">
                    <div class="bg-white dark:bg-gray-900 rounded-[14px] p-6 text-center">
                        <p class="text-sm text-gray-600 dark:text-gray-400 mb-2">ยอดคงเหลือของคุณ</p>
                        <p class="text-4xl font-bold text-transparent bg-clip-text bg-gradient-to-r from-blue-600 to-purple-600 dark:from-blue-400 dark:to-purple-400" x-text="userBalance">
                            -
                        </p>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">{{ $token->symbol }}</p>
                    </div>
                </div>

                {{-- Quick Actions --}}
                <div class="bg-white dark:bg-gray-800 rounded-2xl p-6 border border-gray-200 dark:border-gray-700 shadow-lg">
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                        <svg class="w-5 h-5 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                        </svg>
                        การกระทำด่วน
                    </h3>
                    <div class="space-y-3">
                        <button @click="showBuyModal = true"
                                class="w-full py-3 px-4 bg-gradient-to-r from-green-500 to-emerald-600 hover:from-green-600 hover:to-emerald-700 text-white font-semibold rounded-xl transition duration-200 flex items-center justify-center gap-2">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/>
                            </svg>
                            ซื้อ {{ $token->symbol }}
                        </button>
                        <button @click="showSellModal = true"
                                class="w-full py-3 px-4 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-200 font-semibold rounded-xl border border-gray-200 dark:border-gray-600 transition duration-200 flex items-center justify-center gap-2">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            ขาย {{ $token->symbol }}
                        </button>
                        <button @click="showTransferModal = true"
                                class="w-full py-3 px-4 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-200 font-semibold rounded-xl border border-gray-200 dark:border-gray-600 transition duration-200 flex items-center justify-center gap-2">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/>
                            </svg>
                            โอนย้าย
                        </button>
                    </div>
                </div>

                {{-- Links --}}
                @if($token->website || $token->whitepaper_url || $token->contract_address)
                <div class="bg-white dark:bg-gray-800 rounded-2xl p-6 border border-gray-200 dark:border-gray-700 shadow-lg">
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                        <svg class="w-5 h-5 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/>
                        </svg>
                        ลิงก์
                    </h3>
                    <div class="space-y-2">
                        @if($token->contract_address)
                            <a href="{{ config('tpix.blockchain.explorer_url', '#') }}/token/{{ $token->contract_address }}"
                               target="_blank"
                               class="flex items-center gap-2 p-3 bg-gray-50 dark:bg-gray-700 hover:bg-gray-100 dark:hover:bg-gray-600 rounded-xl transition duration-200 text-gray-700 dark:text-gray-200">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                                </svg>
                                ดูบน Explorer
                            </a>
                        @endif
                        @if($token->website)
                            <a href="{{ $token->website }}" target="_blank"
                               class="flex items-center gap-2 p-3 bg-gray-50 dark:bg-gray-700 hover:bg-gray-100 dark:hover:bg-gray-600 rounded-xl transition duration-200 text-gray-700 dark:text-gray-200">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9"/>
                                </svg>
                                เว็บไซต์
                            </a>
                        @endif
                        @if($token->whitepaper_url ?? false)
                            <a href="{{ $token->whitepaper_url }}" target="_blank"
                               class="flex items-center gap-2 p-3 bg-gray-50 dark:bg-gray-700 hover:bg-gray-100 dark:hover:bg-gray-600 rounded-xl transition duration-200 text-gray-700 dark:text-gray-200">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                                </svg>
                                Whitepaper
                            </a>
                        @endif
                    </div>
                </div>
                @endif
            </div>
        </div>
    </div>

    {{-- Modals (Simple placeholders - implement based on your needs) --}}
    <div x-show="showBuyModal" style="display: none;" class="fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
        <div @click.away="showBuyModal = false" class="bg-white dark:bg-gray-800 rounded-2xl p-6 max-w-md w-full">
            <h3 class="text-2xl font-bold text-gray-900 dark:text-white mb-4">ซื้อ {{ $token->symbol }}</h3>
            <p class="text-gray-600 dark:text-gray-400 mb-4">คุณสมบัตินี้กำลังพัฒนา...</p>
            <button @click="showBuyModal = false" class="w-full py-2 bg-gray-200 dark:bg-gray-700 text-gray-800 dark:text-gray-200 rounded-xl hover:bg-gray-300 dark:hover:bg-gray-600 transition">
                ปิด
            </button>
        </div>
    </div>

    <div x-show="showSellModal" style="display: none;" class="fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
        <div @click.away="showSellModal = false" class="bg-white dark:bg-gray-800 rounded-2xl p-6 max-w-md w-full">
            <h3 class="text-2xl font-bold text-gray-900 dark:text-white mb-4">ขาย {{ $token->symbol }}</h3>
            <p class="text-gray-600 dark:text-gray-400 mb-4">คุณสมบัตินี้กำลังพัฒนา...</p>
            <button @click="showSellModal = false" class="w-full py-2 bg-gray-200 dark:bg-gray-700 text-gray-800 dark:text-gray-200 rounded-xl hover:bg-gray-300 dark:hover:bg-gray-600 transition">
                ปิด
            </button>
        </div>
    </div>

    <div x-show="showTransferModal" style="display: none;" class="fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
        <div @click.away="showTransferModal = false" class="bg-white dark:bg-gray-800 rounded-2xl p-6 max-w-md w-full">
            <h3 class="text-2xl font-bold text-gray-900 dark:text-white mb-4">โอนย้าย {{ $token->symbol }}</h3>
            <p class="text-gray-600 dark:text-gray-400 mb-4">คุณสมบัตินี้กำลังพัฒนา...</p>
            <button @click="showTransferModal = false" class="w-full py-2 bg-gray-200 dark:bg-gray-700 text-gray-800 dark:text-gray-200 rounded-xl hover:bg-gray-300 dark:hover:bg-gray-600 transition">
                ปิด
            </button>
        </div>
    </div>
</div>

@push('scripts')
<script>
/**
 * Alpine.js Component สำหรับ Token Details
 */
function tokenDetails() {
    return {
        // State
        userBalance: '-',
        showBuyModal: false,
        showSellModal: false,
        showTransferModal: false,

        /**
         * เริ่มต้น component
         */
        init() {
            console.log('Token Details initialized');
            this.loadUserBalance();
            this.loadRecentTransactions();
        },

        /**
         * โหลดยอดคงเหลือของผู้ใช้
         */
        async loadUserBalance() {
            try {
                const response = await fetch('/api/v1/tpix/balances');
                if (!response.ok) throw new Error('Failed to load balance');

                const data = await response.json();
                const balance = data.data?.find(b => b.token_id === {{ $token->id }});
                this.userBalance = balance ? parseFloat(balance.balance).toFixed(4) : '0.0000';
            } catch (error) {
                console.error('Error loading balance:', error);
                this.userBalance = '0.0000';
            }
        },

        /**
         * โหลดธุรกรรมล่าสุด
         */
        async loadRecentTransactions() {
            try {
                const response = await fetch('/api/v1/tpix/tokens/{{ $token->id }}/transactions?per_page=10');
                if (!response.ok) throw new Error('Failed to load transactions');

                const data = await response.json();
                this.displayTransactions(data.data || []);
            } catch (error) {
                console.error('Error loading transactions:', error);
                this.displayTransactions([]);
            }
        },

        /**
         * แสดงธุรกรรม
         */
        displayTransactions(transactions) {
            const tbody = document.getElementById('recentTransactions');

            if (!transactions || transactions.length === 0) {
                tbody.innerHTML = `
                    <tr>
                        <td colspan="5" class="px-6 py-8 text-center text-gray-500 dark:text-gray-400">
                            ยังไม่มีธุรกรรม
                        </td>
                    </tr>
                `;
                return;
            }

            const html = transactions.map(tx => `
                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700 transition">
                    <td class="px-6 py-4 whitespace-nowrap">
                        <span class="px-2 py-1 text-xs font-semibold rounded-full bg-blue-100 dark:bg-blue-900 text-blue-800 dark:text-blue-200">
                            ${tx.type}
                        </span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100 font-mono">
                        ${tx.from_address?.substring(0, 8) || 'N/A'}...
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100 font-mono">
                        ${tx.to_address?.substring(0, 8) || 'N/A'}...
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold text-gray-900 dark:text-gray-100">
                        ${parseFloat(tx.amount).toFixed(4)}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                        ${this.timeAgo(tx.created_at)}
                    </td>
                </tr>
            `).join('');

            tbody.innerHTML = html;
        },

        /**
         * แปลงเวลาเป็น "XX ago" format
         */
        timeAgo(timestamp) {
            const seconds = Math.floor((new Date() - new Date(timestamp)) / 1000);
            if (seconds < 60) return seconds + 's ago';
            if (seconds < 3600) return Math.floor(seconds / 60) + 'm ago';
            if (seconds < 86400) return Math.floor(seconds / 3600) + 'h ago';
            return Math.floor(seconds / 86400) + 'd ago';
        }
    };
}
</script>
@endpush
@endsection
