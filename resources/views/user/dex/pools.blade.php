@extends('layouts.user-arrow-x')

@section('title', 'Liquidity Pools - TPIX DEX')

@section('content')
<div class="min-h-screen">
    {{-- Hero Section พร้อม Gradient สวยงาม --}}
    <div class="relative overflow-hidden rounded-3xl mb-8 bg-gradient-to-br from-blue-600 via-purple-600 to-pink-600 dark:from-blue-800 dark:via-purple-800 dark:to-pink-800">
        {{-- Background Pattern --}}
        <div class="absolute inset-0 opacity-10">
            <div class="absolute inset-0" style="background-image: url('data:image/svg+xml,%3Csvg width=&quot;60&quot; height=&quot;60&quot; viewBox=&quot;0 0 60 60&quot; xmlns=&quot;http://www.w3.org/2000/svg&quot;%3E%3Cg fill=&quot;none&quot; fill-rule=&quot;evenodd&quot;%3E%3Cg fill=&quot;%23ffffff&quot; fill-opacity=&quot;1&quot;%3E%3Cpath d=&quot;M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z&quot;/%3E%3C/g%3E%3C/g%3E%3C/svg%3E');"></div>
        </div>

        {{-- Content --}}
        <div class="relative px-6 py-12 md:py-16 text-center text-white">
            <div class="max-w-4xl mx-auto">
                {{-- Icon --}}
                <div class="inline-flex items-center justify-center w-20 h-20 mb-6 rounded-full bg-white/20 backdrop-blur-sm">
                    <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                    </svg>
                </div>

                {{-- Title --}}
                <h1 class="text-4xl md:text-5xl lg:text-6xl font-bold mb-4 bg-clip-text text-transparent bg-gradient-to-r from-white to-gray-100">
                    Liquidity Pools
                </h1>

                {{-- Description --}}
                <p class="text-lg md:text-xl mb-8 text-white/90">
                    เพิ่ม Liquidity และรับค่าธรรมเนียม <span class="font-bold text-yellow-300">0.25%</span> จากทุกการแลกเปลี่ยน
                </p>

                {{-- CTA Button --}}
                <a href="{{ route('user.dex.add-liquidity') }}" class="inline-flex items-center px-8 py-4 bg-white text-blue-600 font-bold rounded-xl hover:bg-gray-100 transform hover:scale-105 transition-all duration-200 shadow-2xl hover:shadow-white/20">
                    <svg class="w-6 h-6 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                    </svg>
                    เพิ่ม Liquidity
                </a>
            </div>
        </div>

        {{-- Animated Waves --}}
        <div class="absolute bottom-0 left-0 right-0">
            <svg class="w-full h-16" viewBox="0 0 1440 120" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M0 120L60 105C120 90 240 60 360 45C480 30 600 30 720 37.5C840 45 960 60 1080 67.5C1200 75 1320 75 1380 75L1440 75V120H1380C1320 120 1200 120 1080 120C960 120 840 120 720 120C600 120 480 120 360 120C240 120 120 120 60 120H0Z" class="fill-white dark:fill-gray-900"/>
            </svg>
        </div>
    </div>

    {{-- Stats Cards พร้อม Animation --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-8" x-data="{ loaded: false }" x-init="setTimeout(() => loaded = true, 100)">
        {{-- Total TVL --}}
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg hover:shadow-2xl transition-all duration-300 overflow-hidden group"
             x-show="loaded"
             x-transition:enter="transform transition ease-out duration-500"
             x-transition:enter-start="opacity-0 translate-y-8"
             x-transition:enter-end="opacity-100 translate-y-0"
             style="transition-delay: 0ms;">
            <div class="p-6">
                <div class="flex items-center justify-between mb-4">
                    <div class="flex items-center justify-center w-12 h-12 rounded-xl bg-gradient-to-br from-blue-500 to-blue-600 dark:from-blue-600 dark:to-blue-700 group-hover:scale-110 transition-transform duration-300">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/>
                        </svg>
                    </div>
                </div>
                <h3 class="text-3xl font-bold text-gray-900 dark:text-white mb-2" id="totalTVL">
                    <span class="inline-block w-24 h-8 bg-gray-200 dark:bg-gray-700 animate-pulse rounded"></span>
                </h3>
                <p class="text-sm text-gray-500 dark:text-gray-400 font-medium">Total TVL</p>
            </div>
            <div class="h-1 bg-gradient-to-r from-blue-500 to-blue-600"></div>
        </div>

        {{-- Volume 24h --}}
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg hover:shadow-2xl transition-all duration-300 overflow-hidden group"
             x-show="loaded"
             x-transition:enter="transform transition ease-out duration-500"
             x-transition:enter-start="opacity-0 translate-y-8"
             x-transition:enter-end="opacity-100 translate-y-0"
             style="transition-delay: 100ms;">
            <div class="p-6">
                <div class="flex items-center justify-between mb-4">
                    <div class="flex items-center justify-center w-12 h-12 rounded-xl bg-gradient-to-br from-green-500 to-green-600 dark:from-green-600 dark:to-green-700 group-hover:scale-110 transition-transform duration-300">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/>
                        </svg>
                    </div>
                </div>
                <h3 class="text-3xl font-bold text-gray-900 dark:text-white mb-2" id="totalVolume24h">
                    <span class="inline-block w-24 h-8 bg-gray-200 dark:bg-gray-700 animate-pulse rounded"></span>
                </h3>
                <p class="text-sm text-gray-500 dark:text-gray-400 font-medium">Volume 24h</p>
            </div>
            <div class="h-1 bg-gradient-to-r from-green-500 to-green-600"></div>
        </div>

        {{-- Active Pools --}}
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg hover:shadow-2xl transition-all duration-300 overflow-hidden group"
             x-show="loaded"
             x-transition:enter="transform transition ease-out duration-500"
             x-transition:enter-start="opacity-0 translate-y-8"
             x-transition:enter-end="opacity-100 translate-y-0"
             style="transition-delay: 200ms;">
            <div class="p-6">
                <div class="flex items-center justify-between mb-4">
                    <div class="flex items-center justify-center w-12 h-12 rounded-xl bg-gradient-to-br from-yellow-500 to-yellow-600 dark:from-yellow-600 dark:to-yellow-700 group-hover:scale-110 transition-transform duration-300">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                        </svg>
                    </div>
                </div>
                <h3 class="text-3xl font-bold text-gray-900 dark:text-white mb-2" id="totalPools">
                    <span class="inline-block w-16 h-8 bg-gray-200 dark:bg-gray-700 animate-pulse rounded"></span>
                </h3>
                <p class="text-sm text-gray-500 dark:text-gray-400 font-medium">Active Pools</p>
            </div>
            <div class="h-1 bg-gradient-to-r from-yellow-500 to-yellow-600"></div>
        </div>

        {{-- Fees Collected --}}
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg hover:shadow-2xl transition-all duration-300 overflow-hidden group"
             x-show="loaded"
             x-transition:enter="transform transition ease-out duration-500"
             x-transition:enter-start="opacity-0 translate-y-8"
             x-transition:enter-end="opacity-100 translate-y-0"
             style="transition-delay: 300ms;">
            <div class="p-6">
                <div class="flex items-center justify-between mb-4">
                    <div class="flex items-center justify-center w-12 h-12 rounded-xl bg-gradient-to-br from-purple-500 to-purple-600 dark:from-purple-600 dark:to-purple-700 group-hover:scale-110 transition-transform duration-300">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                </div>
                <h3 class="text-3xl font-bold text-gray-900 dark:text-white mb-2" id="totalFees">
                    <span class="inline-block w-24 h-8 bg-gray-200 dark:bg-gray-700 animate-pulse rounded"></span>
                </h3>
                <p class="text-sm text-gray-500 dark:text-gray-400 font-medium">Fees Collected</p>
            </div>
            <div class="h-1 bg-gradient-to-r from-purple-500 to-purple-600"></div>
        </div>
    </div>

    {{-- Filters & Search --}}
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-6 mb-8">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            {{-- Search Input --}}
            <div class="md:col-span-1">
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                        <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                    </div>
                    <input type="text"
                           id="searchPool"
                           class="w-full pl-12 pr-4 py-3 bg-gray-50 dark:bg-gray-900 border border-gray-300 dark:border-gray-700 rounded-xl text-gray-900 dark:text-white placeholder-gray-500 dark:placeholder-gray-400 focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-600 focus:border-transparent transition-all duration-200"
                           placeholder="ค้นหา Pool...">
                </div>
            </div>

            {{-- Sort Dropdown --}}
            <div class="md:col-span-1">
                <select id="sortBy"
                        class="w-full px-4 py-3 bg-gray-50 dark:bg-gray-900 border border-gray-300 dark:border-gray-700 rounded-xl text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-600 focus:border-transparent transition-all duration-200">
                    <option value="tvl">🏆 TVL สูงสุด</option>
                    <option value="volume">📊 Volume 24h สูงสุด</option>
                    <option value="apy">💰 APY สูงสุด</option>
                    <option value="newest">✨ Pool ใหม่ล่าสุด</option>
                </select>
            </div>

            {{-- My Pools Only Toggle --}}
            <div class="md:col-span-1 flex items-center">
                <label class="flex items-center cursor-pointer w-full">
                    <div class="relative">
                        <input type="checkbox" id="myPoolsOnly" class="sr-only peer">
                        <div class="w-14 h-8 bg-gray-300 dark:bg-gray-600 rounded-full peer peer-checked:bg-blue-600 dark:peer-checked:bg-blue-500 transition-all duration-300"></div>
                        <div class="absolute left-1 top-1 bg-white w-6 h-6 rounded-full transition-all duration-300 peer-checked:translate-x-6 shadow-md"></div>
                    </div>
                    <span class="ml-3 text-gray-700 dark:text-gray-300 font-medium">Pool ของฉันเท่านั้น</span>
                </label>
            </div>
        </div>
    </div>

    {{-- Pools List --}}
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg overflow-hidden mb-8">
        {{-- Header --}}
        <div class="bg-gradient-to-r from-gray-50 to-gray-100 dark:from-gray-900 dark:to-gray-800 px-6 py-4 border-b border-gray-200 dark:border-gray-700">
            <h2 class="text-xl font-bold text-gray-900 dark:text-white flex items-center">
                <svg class="w-6 h-6 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                </svg>
                All Liquidity Pools
            </h2>
        </div>

        {{-- Table --}}
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50 dark:bg-gray-900">
                    <tr>
                        <th class="px-6 py-4 text-left text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider">Pool</th>
                        <th class="px-6 py-4 text-right text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider">TVL</th>
                        <th class="px-6 py-4 text-right text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider hidden md:table-cell">Volume 24h</th>
                        <th class="px-6 py-4 text-right text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider">APY</th>
                        <th class="px-6 py-4 text-center text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider hidden lg:table-cell">My Liquidity</th>
                        <th class="px-6 py-4 text-center text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider">Action</th>
                    </tr>
                </thead>
                <tbody id="poolsList" class="divide-y divide-gray-200 dark:divide-gray-700">
                    <tr>
                        <td colspan="6" class="px-6 py-12 text-center">
                            <div class="flex flex-col items-center">
                                <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600 dark:border-blue-500"></div>
                                <p class="mt-4 text-gray-500 dark:text-gray-400 font-medium">กำลังโหลดข้อมูล...</p>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    {{-- My Positions --}}
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg overflow-hidden">
        {{-- Header --}}
        <div class="bg-gradient-to-r from-gray-50 to-gray-100 dark:from-gray-900 dark:to-gray-800 px-6 py-4 border-b border-gray-200 dark:border-gray-700">
            <h2 class="text-xl font-bold text-gray-900 dark:text-white flex items-center">
                <svg class="w-6 h-6 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
                </svg>
                My Liquidity Positions
            </h2>
        </div>

        {{-- Content --}}
        <div id="myPositions" class="p-6">
            <div class="text-center py-12">
                <div class="inline-flex items-center justify-center w-20 h-20 mb-4 rounded-full bg-gray-100 dark:bg-gray-700">
                    <svg class="w-10 h-10 text-gray-400 dark:text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/>
                    </svg>
                </div>
                <p class="text-gray-500 dark:text-gray-400 mb-6">คุณยังไม่มี Liquidity Position</p>
                <a href="{{ route('user.dex.add-liquidity') }}"
                   class="inline-flex items-center px-6 py-3 bg-gradient-to-r from-blue-600 to-purple-600 hover:from-blue-700 hover:to-purple-700 text-white font-bold rounded-xl transform hover:scale-105 transition-all duration-200 shadow-lg">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                    </svg>
                    เพิ่ม Liquidity เลย
                </a>
            </div>
        </div>
    </div>
</div>

{{-- Pool Details Modal --}}
<div x-data="{ showModal: false, poolData: null }"
     @show-pool-modal.window="showModal = true; poolData = $event.detail"
     x-show="showModal"
     x-cloak
     class="fixed inset-0 z-50 overflow-y-auto"
     style="display: none;">
    {{-- Backdrop --}}
    <div class="fixed inset-0 bg-black/50 backdrop-blur-sm transition-opacity"
         @click="showModal = false"
         x-show="showModal"
         x-transition:enter="ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"></div>

    {{-- Modal --}}
    <div class="flex items-center justify-center min-h-screen p-4">
        <div class="relative bg-white dark:bg-gray-800 rounded-2xl shadow-2xl max-w-4xl w-full mx-auto"
             @click.away="showModal = false"
             x-show="showModal"
             x-transition:enter="ease-out duration-300"
             x-transition:enter-start="opacity-0 translate-y-8 scale-95"
             x-transition:enter-end="opacity-100 translate-y-0 scale-100"
             x-transition:leave="ease-in duration-200"
             x-transition:leave-start="opacity-100 translate-y-0 scale-100"
             x-transition:leave-end="opacity-0 translate-y-8 scale-95">

            {{-- Header --}}
            <div class="bg-gradient-to-r from-blue-600 to-purple-600 dark:from-blue-800 dark:to-purple-800 px-6 py-4 rounded-t-2xl flex items-center justify-between">
                <h3 class="text-xl font-bold text-white" id="poolPairName">Pool Details</h3>
                <button @click="showModal = false"
                        class="text-white/80 hover:text-white transition-colors">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            {{-- Content --}}
            <div id="poolDetailsContent" class="p-6">
                {{-- Content loaded via AJAX --}}
            </div>
        </div>
    </div>
</div>

{{-- Styles สำหรับ Pool Logos --}}
<style>
/**
 * สไตล์สำหรับโลโก้ Pool
 */
.pool-logo {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    border: 3px solid white;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

.dark .pool-logo {
    border-color: #1e293b;
}

.pool-logo-group {
    display: flex;
    align-items: center;
}

.pool-logo-group .pool-logo:not(:first-child) {
    margin-left: -12px;
}

/**
 * Badge สำหรับ APY
 */
.apy-badge {
    font-size: 1rem;
    font-weight: 700;
    padding: 0.5rem 1rem;
    border-radius: 0.75rem;
}

/**
 * Highlight สำหรับ Pool ของฉัน
 */
tr.my-pool {
    background: linear-gradient(to right, rgba(59, 130, 246, 0.05), rgba(147, 51, 234, 0.05));
}

.dark tr.my-pool {
    background: linear-gradient(to right, rgba(59, 130, 246, 0.1), rgba(147, 51, 234, 0.1));
}

/**
 * Alpine.js x-cloak
 */
[x-cloak] {
    display: none !important;
}
</style>

@push('scripts')
<script>
/**
 * ตัวแปร Global สำหรับเก็บข้อมูล Pools และ Positions
 */
let allPools = [];
let myPositions = [];

/**
 * เริ่มต้นระบบเมื่อ DOM พร้อม
 */
$(document).ready(function() {
    loadStats();
    loadPools();
    loadMyPositions();

    // Event listeners สำหรับ filters
    $('#searchPool').on('input', debounce(filterPools, 300));
    $('#sortBy').change(filterPools);
    $('#myPoolsOnly').change(filterPools);
});

/**
 * โหลดสถิติ DEX
 */
function loadStats() {
    $.ajax({
        url: '/api/v1/tpix/dex/statistics',
        method: 'GET',
        headers: {
            'Authorization': 'Bearer ' + localStorage.getItem('api_token')
        },
        success: function(response) {
            const data = response.data;
            $('#totalTVL').html(formatNumber(data.total_tvl) + ' <span class="text-sm font-normal text-gray-500 dark:text-gray-400">TPIX</span>');
            $('#totalVolume24h').html(formatNumber(data.total_volume_24h) + ' <span class="text-sm font-normal text-gray-500 dark:text-gray-400">TPIX</span>');
            $('#totalPools').text(data.total_pools);
            $('#totalFees').html(formatNumber(data.total_fees_collected) + ' <span class="text-sm font-normal text-gray-500 dark:text-gray-400">TPIX</span>');
        },
        error: function() {
            // แสดงข้อมูล placeholder แทน
            $('#totalTVL').text('N/A');
            $('#totalVolume24h').text('N/A');
            $('#totalPools').text('0');
            $('#totalFees').text('N/A');
        }
    });
}

/**
 * โหลดรายการ Pools ทั้งหมด
 */
function loadPools() {
    $.ajax({
        url: '/api/v1/tpix/dex/pools',
        method: 'GET',
        data: { per_page: 100 },
        headers: {
            'Authorization': 'Bearer ' + localStorage.getItem('api_token')
        },
        success: function(response) {
            allPools = response.data;
            displayPools(allPools);
        },
        error: function() {
            $('#poolsList').html(`
                <tr>
                    <td colspan="6" class="px-6 py-12 text-center">
                        <div class="text-red-500 dark:text-red-400">
                            <svg class="w-12 h-12 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            <p class="font-medium">ไม่สามารถโหลดข้อมูลได้</p>
                        </div>
                    </td>
                </tr>
            `);
        }
    });
}

/**
 * แสดงรายการ Pools
 *
 * @param {Array} pools - รายการ pools ที่จะแสดง
 */
function displayPools(pools) {
    if (pools.length === 0) {
        $('#poolsList').html(`
            <tr>
                <td colspan="6" class="px-6 py-12 text-center">
                    <div class="text-gray-500 dark:text-gray-400">
                        <svg class="w-12 h-12 mx-auto mb-3 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/>
                        </svg>
                        <p class="font-medium">ไม่พบ Liquidity Pool</p>
                    </div>
                </td>
            </tr>
        `);
        return;
    }

    const html = pools.map(pool => {
        const hasPosition = myPositions.some(p => p.pool.id === pool.id);
        const apyClass = pool.apy >= 50 ? 'from-green-500 to-green-600' :
                        pool.apy >= 20 ? 'from-blue-500 to-blue-600' :
                        'from-gray-500 to-gray-600';

        return `
            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors duration-150 ${hasPosition ? 'my-pool' : ''}">
                <td class="px-6 py-4">
                    <div class="flex items-center space-x-3">
                        <div class="pool-logo-group">
                            <img src="${pool.token_a.logo || '/images/default-token.png'}"
                                 alt="${pool.token_a.symbol}"
                                 class="pool-logo"
                                 onerror="this.src='/images/default-token.png'">
                            <img src="${pool.token_b.logo || '/images/default-token.png'}"
                                 alt="${pool.token_b.symbol}"
                                 class="pool-logo"
                                 onerror="this.src='/images/default-token.png'">
                        </div>
                        <div>
                            <div class="font-bold text-gray-900 dark:text-white">${pool.pair}</div>
                            <div class="text-xs text-gray-500 dark:text-gray-400">Fee: ${pool.fee_percentage}%</div>
                        </div>
                    </div>
                </td>
                <td class="px-6 py-4 text-right">
                    <div class="font-bold text-gray-900 dark:text-white">฿${formatNumber(pool.tvl)}</div>
                </td>
                <td class="px-6 py-4 text-right hidden md:table-cell">
                    <div class="text-gray-700 dark:text-gray-300">฿${formatNumber(pool.volume_24h)}</div>
                </td>
                <td class="px-6 py-4 text-right">
                    <span class="inline-flex items-center px-3 py-1 rounded-lg bg-gradient-to-r ${apyClass} text-white font-bold text-sm shadow-lg">
                        ${pool.apy.toFixed(2)}%
                    </span>
                </td>
                <td class="px-6 py-4 text-center hidden lg:table-cell">
                    ${hasPosition
                        ? '<span class="inline-flex items-center px-3 py-1 rounded-lg bg-green-100 dark:bg-green-900 text-green-800 dark:text-green-200 text-xs font-medium">✓ มี Position</span>'
                        : '<span class="text-gray-400 dark:text-gray-500">-</span>'}
                </td>
                <td class="px-6 py-4">
                    <div class="flex items-center justify-center space-x-2">
                        <button onclick="viewPoolDetails(${pool.id})"
                                class="px-3 py-2 bg-gray-100 hover:bg-gray-200 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 rounded-lg transition-colors duration-200">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                            </svg>
                        </button>
                        <a href="/user/dex/add-liquidity?pool=${pool.id}"
                           class="px-4 py-2 bg-gradient-to-r from-blue-600 to-purple-600 hover:from-blue-700 hover:to-purple-700 text-white rounded-lg font-medium transition-all duration-200 transform hover:scale-105 shadow-lg">
                            <svg class="w-5 h-5 inline-block mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                            </svg>
                            Add
                        </a>
                    </div>
                </td>
            </tr>
        `;
    }).join('');

    $('#poolsList').html(html);
}

/**
 * โหลด Positions ของผู้ใช้
 */
function loadMyPositions() {
    $.ajax({
        url: '/api/v1/tpix/dex/my/positions',
        method: 'GET',
        headers: {
            'Authorization': 'Bearer ' + localStorage.getItem('api_token')
        },
        success: function(response) {
            myPositions = response.data;

            if (myPositions.length > 0) {
                displayMyPositions(myPositions);
            }
        }
    });
}

/**
 * แสดง Positions ของผู้ใช้
 *
 * @param {Array} positions - รายการ positions
 */
function displayMyPositions(positions) {
    const html = positions.map(position => `
        <div class="border border-gray-200 dark:border-gray-700 rounded-xl p-6 hover:shadow-lg transition-shadow duration-200 mb-4">
            <div class="grid grid-cols-1 md:grid-cols-6 gap-4 items-center">
                {{-- Pool Info --}}
                <div class="md:col-span-2">
                    <div class="flex items-center space-x-3">
                        <div class="pool-logo-group">
                            <img src="${position.pool.token_a.logo || '/images/default-token.png'}"
                                 alt="${position.pool.token_a.symbol}"
                                 class="pool-logo">
                            <img src="${position.pool.token_b.logo || '/images/default-token.png'}"
                                 alt="${position.pool.token_b.symbol}"
                                 class="pool-logo">
                        </div>
                        <div>
                            <div class="font-bold text-gray-900 dark:text-white">${position.pool.pair}</div>
                            <div class="text-xs text-gray-500 dark:text-gray-400">Share: ${position.share_percentage.toFixed(4)}%</div>
                        </div>
                    </div>
                </div>

                {{-- Pooled Token A --}}
                <div class="text-center">
                    <div class="text-xs text-gray-500 dark:text-gray-400 mb-1">Pooled ${position.token_a.symbol}</div>
                    <div class="font-bold text-gray-900 dark:text-white">${formatNumber(position.token_a.amount)}</div>
                </div>

                {{-- Pooled Token B --}}
                <div class="text-center">
                    <div class="text-xs text-gray-500 dark:text-gray-400 mb-1">Pooled ${position.token_b.symbol}</div>
                    <div class="font-bold text-gray-900 dark:text-white">${formatNumber(position.token_b.amount)}</div>
                </div>

                {{-- Value --}}
                <div class="text-center">
                    <div class="text-xs text-gray-500 dark:text-gray-400 mb-1">Value</div>
                    <div class="font-bold text-gray-900 dark:text-white">฿${formatNumber(position.current_value_tpix)}</div>
                </div>

                {{-- Fees Earned & Action --}}
                <div class="flex flex-col items-center space-y-2">
                    <div class="text-center">
                        <div class="text-xs text-gray-500 dark:text-gray-400 mb-1">Fees Earned</div>
                        <div class="font-bold text-green-600 dark:text-green-400">+฿${formatNumber(position.unclaimed_fees)}</div>
                    </div>
                    <a href="/user/dex/remove-liquidity/${position.id}"
                       class="px-4 py-2 bg-red-100 hover:bg-red-200 dark:bg-red-900 dark:hover:bg-red-800 text-red-700 dark:text-red-300 rounded-lg font-medium transition-colors duration-200">
                        <svg class="w-5 h-5 inline-block" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4"/>
                        </svg>
                        Remove
                    </a>
                </div>
            </div>
        </div>
    `).join('');

    $('#myPositions').html(html);
}

/**
 * แสดงรายละเอียด Pool ใน Modal
 *
 * @param {number} poolId - ID ของ pool
 */
function viewPoolDetails(poolId) {
    $.ajax({
        url: `/api/v1/tpix/dex/pools/${poolId}`,
        method: 'GET',
        headers: {
            'Authorization': 'Bearer ' + localStorage.getItem('api_token')
        },
        success: function(response) {
            const pool = response.data;

            $('#poolPairName').text(pool.pair);

            const html = `
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    {{-- Pool Information --}}
                    <div class="bg-gray-50 dark:bg-gray-900 rounded-xl p-6">
                        <h4 class="font-bold text-gray-900 dark:text-white mb-4 flex items-center">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            Pool Information
                        </h4>
                        <div class="space-y-3">
                            <div class="flex justify-between items-center py-2 border-b border-gray-200 dark:border-gray-700">
                                <span class="text-gray-600 dark:text-gray-400">Total TVL</span>
                                <span class="font-bold text-gray-900 dark:text-white">฿${formatNumber(pool.tvl)}</span>
                            </div>
                            <div class="flex justify-between items-center py-2 border-b border-gray-200 dark:border-gray-700">
                                <span class="text-gray-600 dark:text-gray-400">Volume 24h</span>
                                <span class="font-bold text-gray-900 dark:text-white">฿${formatNumber(pool.volume_24h)}</span>
                            </div>
                            <div class="flex justify-between items-center py-2 border-b border-gray-200 dark:border-gray-700">
                                <span class="text-gray-600 dark:text-gray-400">Volume 7d</span>
                                <span class="font-bold text-gray-900 dark:text-white">฿${formatNumber(pool.volume_7d)}</span>
                            </div>
                            <div class="flex justify-between items-center py-2 border-b border-gray-200 dark:border-gray-700">
                                <span class="text-gray-600 dark:text-gray-400">Fees Collected</span>
                                <span class="font-bold text-gray-900 dark:text-white">฿${formatNumber(pool.fees_collected)}</span>
                            </div>
                            <div class="flex justify-between items-center py-2 border-b border-gray-200 dark:border-gray-700">
                                <span class="text-gray-600 dark:text-gray-400">Total Swaps</span>
                                <span class="font-bold text-gray-900 dark:text-white">${pool.tx_count.toLocaleString()}</span>
                            </div>
                            <div class="flex justify-between items-center py-2">
                                <span class="text-gray-600 dark:text-gray-400">APY</span>
                                <span class="inline-flex items-center px-3 py-1 rounded-lg bg-gradient-to-r from-green-500 to-green-600 text-white font-bold">${pool.apy.toFixed(2)}%</span>
                            </div>
                        </div>
                    </div>

                    {{-- Reserves & Action --}}
                    <div class="bg-gray-50 dark:bg-gray-900 rounded-xl p-6">
                        <h4 class="font-bold text-gray-900 dark:text-white mb-4 flex items-center">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                            </svg>
                            Reserves
                        </h4>
                        <div class="space-y-3 mb-6">
                            <div class="flex justify-between items-center py-2 border-b border-gray-200 dark:border-gray-700">
                                <span class="text-gray-600 dark:text-gray-400">${pool.token_a.symbol}</span>
                                <span class="font-bold text-gray-900 dark:text-white">${formatNumber(pool.reserves.token_a)}</span>
                            </div>
                            <div class="flex justify-between items-center py-2 border-b border-gray-200 dark:border-gray-700">
                                <span class="text-gray-600 dark:text-gray-400">${pool.token_b.symbol}</span>
                                <span class="font-bold text-gray-900 dark:text-white">${formatNumber(pool.reserves.token_b)}</span>
                            </div>
                            <div class="flex justify-between items-center py-2">
                                <span class="text-gray-600 dark:text-gray-400">Current Price</span>
                                <span class="font-medium text-gray-900 dark:text-white text-sm">1 ${pool.token_a.symbol} = ${parseFloat(pool.price).toFixed(6)} ${pool.token_b.symbol}</span>
                            </div>
                        </div>

                        <a href="/user/dex/add-liquidity?pool=${pool.id}"
                           class="w-full inline-flex items-center justify-center px-6 py-3 bg-gradient-to-r from-blue-600 to-purple-600 hover:from-blue-700 hover:to-purple-700 text-white font-bold rounded-xl transform hover:scale-105 transition-all duration-200 shadow-lg">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                            </svg>
                            Add Liquidity
                        </a>
                    </div>
                </div>

                {{-- Recent Swaps --}}
                <div class="mt-6">
                    <h4 class="font-bold text-gray-900 dark:text-white mb-4 flex items-center">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/>
                        </svg>
                        Recent Swaps
                    </h4>
                    <div class="bg-gray-50 dark:bg-gray-900 rounded-xl overflow-hidden">
                        <div class="overflow-x-auto">
                            <table class="w-full">
                                <thead class="bg-gray-100 dark:bg-gray-800">
                                    <tr>
                                        <th class="px-4 py-3 text-left text-xs font-bold text-gray-700 dark:text-gray-300">Direction</th>
                                        <th class="px-4 py-3 text-right text-xs font-bold text-gray-700 dark:text-gray-300">Amount In</th>
                                        <th class="px-4 py-3 text-right text-xs font-bold text-gray-700 dark:text-gray-300">Amount Out</th>
                                        <th class="px-4 py-3 text-right text-xs font-bold text-gray-700 dark:text-gray-300">Price Impact</th>
                                        <th class="px-4 py-3 text-right text-xs font-bold text-gray-700 dark:text-gray-300">Time</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                    ${pool.recent_swaps.map(swap => `
                                        <tr class="hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors">
                                            <td class="px-4 py-3 text-sm text-gray-900 dark:text-white">${swap.direction}</td>
                                            <td class="px-4 py-3 text-sm text-right text-gray-900 dark:text-white">${formatNumber(swap.amount_in)}</td>
                                            <td class="px-4 py-3 text-sm text-right text-gray-900 dark:text-white">${formatNumber(swap.amount_out)}</td>
                                            <td class="px-4 py-3 text-sm text-right">
                                                <span class="${swap.price_impact > 5 ? 'text-red-600 dark:text-red-400' : 'text-green-600 dark:text-green-400'} font-medium">
                                                    ${swap.price_impact.toFixed(2)}%
                                                </span>
                                            </td>
                                            <td class="px-4 py-3 text-sm text-right text-gray-600 dark:text-gray-400">${timeAgo(swap.timestamp)}</td>
                                        </tr>
                                    `).join('')}
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            `;

            $('#poolDetailsContent').html(html);

            // แสดง modal ด้วย Alpine.js event
            window.dispatchEvent(new CustomEvent('show-pool-modal', { detail: pool }));
        }
    });
}

/**
 * กรองและเรียงลำดับ Pools ตาม Filter
 */
function filterPools() {
    let filtered = [...allPools];

    // ค้นหาตามชื่อ Pool หรือ Token
    const search = $('#searchPool').val().toLowerCase();
    if (search) {
        filtered = filtered.filter(pool =>
            pool.pair.toLowerCase().includes(search) ||
            pool.token_a.symbol.toLowerCase().includes(search) ||
            pool.token_b.symbol.toLowerCase().includes(search)
        );
    }

    // กรองเฉพาะ Pool ของฉัน
    if ($('#myPoolsOnly').is(':checked')) {
        filtered = filtered.filter(pool =>
            myPositions.some(p => p.pool.id === pool.id)
        );
    }

    // เรียงลำดับตามที่เลือก
    const sortBy = $('#sortBy').val();
    filtered.sort((a, b) => {
        switch(sortBy) {
            case 'tvl':
                return parseFloat(b.tvl) - parseFloat(a.tvl);
            case 'volume':
                return parseFloat(b.volume_24h) - parseFloat(a.volume_24h);
            case 'apy':
                return b.apy - a.apy;
            case 'newest':
                return b.id - a.id;
            default:
                return 0;
        }
    });

    displayPools(filtered);
}

/**
 * จัดรูปแบบตัวเลขให้อ่านง่าย
 *
 * @param {number} num - ตัวเลขที่ต้องการจัดรูปแบบ
 * @return {string} - ตัวเลขที่จัดรูปแบบแล้ว
 */
function formatNumber(num) {
    return parseFloat(num).toLocaleString('en-US', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    });
}

/**
 * แปลง timestamp เป็น "X ago" format
 *
 * @param {string} timestamp - Timestamp ที่ต้องการแปลง
 * @return {string} - ข้อความ "X ago"
 */
function timeAgo(timestamp) {
    const seconds = Math.floor((new Date() - new Date(timestamp)) / 1000);
    if (seconds < 60) return seconds + 's ago';
    if (seconds < 3600) return Math.floor(seconds / 60) + 'm ago';
    if (seconds < 86400) return Math.floor(seconds / 3600) + 'h ago';
    return Math.floor(seconds / 86400) + 'd ago';
}

/**
 * Debounce function สำหรับจำกัดการเรียกฟังก์ชันบ่อยเกินไป
 *
 * @param {Function} func - ฟังก์ชันที่ต้องการ debounce
 * @param {number} wait - เวลารอ (milliseconds)
 * @return {Function} - ฟังก์ชันที่ debounce แล้ว
 */
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}
</script>
@endpush
@endsection
