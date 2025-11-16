@extends('layouts.user-arrow-x')

@section('title', 'ตำแหน่ง Liquidity ของฉัน - TPIX DEX')

@section('content')
<div class="min-h-screen">
    {{-- Header --}}
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-8 gap-4">
        <div>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white mb-2 flex items-center">
                <svg class="w-8 h-8 mr-3 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
                </svg>
                ตำแหน่ง Liquidity ของฉัน
            </h1>
            <p class="text-gray-600 dark:text-gray-400">จัดการสภาพคล่องของคุณใน DEX</p>
        </div>
        <a href="{{ route('user.dex.add-liquidity') }}"
           class="inline-flex items-center px-6 py-3 bg-gradient-to-r from-blue-600 to-purple-600 hover:from-blue-700 hover:to-purple-700 text-white rounded-xl font-bold transition-all duration-200 transform hover:scale-105 shadow-lg">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
            </svg>
            เพิ่ม Liquidity
        </a>
    </div>

    {{-- Summary Cards --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        {{-- Total Liquidity --}}
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg border border-gray-200 dark:border-gray-700 p-6 hover:shadow-xl transition-shadow duration-300">
            <div class="flex justify-between items-start mb-4">
                <p class="text-sm text-gray-600 dark:text-gray-400">มูลค่าสภาพคล่องทั้งหมด</p>
                <div class="w-10 h-10 rounded-xl bg-blue-100 dark:bg-blue-900 flex items-center justify-center">
                    <svg class="w-6 h-6 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
            </div>
            <h3 class="text-2xl font-bold text-gray-900 dark:text-white mb-1" id="totalLiquidity">฿0.00</h3>
            <p class="text-sm font-medium" id="totalLiquidityChange">+0.00%</p>
        </div>

        {{-- Total Earnings --}}
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg border border-gray-200 dark:border-gray-700 p-6 hover:shadow-xl transition-shadow duration-300">
            <div class="flex justify-between items-start mb-4">
                <p class="text-sm text-gray-600 dark:text-gray-400">รายได้รวม (24ชม.)</p>
                <div class="w-10 h-10 rounded-xl bg-green-100 dark:bg-green-900 flex items-center justify-center">
                    <svg class="w-6 h-6 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/>
                    </svg>
                </div>
            </div>
            <h3 class="text-2xl font-bold text-gray-900 dark:text-white mb-1" id="totalEarnings24h">฿0.00</h3>
            <p class="text-sm text-gray-500 dark:text-gray-400" id="earningsAPY">APY: 0.00%</p>
        </div>

        {{-- Total Pools --}}
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg border border-gray-200 dark:border-gray-700 p-6 hover:shadow-xl transition-shadow duration-300">
            <div class="flex justify-between items-start mb-4">
                <p class="text-sm text-gray-600 dark:text-gray-400">จำนวน Pools</p>
                <div class="w-10 h-10 rounded-xl bg-cyan-100 dark:bg-cyan-900 flex items-center justify-center">
                    <svg class="w-6 h-6 text-cyan-600 dark:text-cyan-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                    </svg>
                </div>
            </div>
            <h3 class="text-2xl font-bold text-gray-900 dark:text-white mb-1" id="totalPools">0</h3>
            <p class="text-sm text-gray-500 dark:text-gray-400" id="activePools">Active: 0</p>
        </div>

        {{-- Average ROI --}}
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg border border-gray-200 dark:border-gray-700 p-6 hover:shadow-xl transition-shadow duration-300">
            <div class="flex justify-between items-start mb-4">
                <p class="text-sm text-gray-600 dark:text-gray-400">ROI เฉลี่ย</p>
                <div class="w-10 h-10 rounded-xl bg-yellow-100 dark:bg-yellow-900 flex items-center justify-center">
                    <svg class="w-6 h-6 text-yellow-600 dark:text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                    </svg>
                </div>
            </div>
            <h3 class="text-2xl font-bold text-gray-900 dark:text-white mb-1" id="avgROI">0.00%</h3>
            <p class="text-sm text-gray-500 dark:text-gray-400" id="totalProfit">Profit: ฿0.00</p>
        </div>
    </div>

    {{-- Filters & Search --}}
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg border border-gray-200 dark:border-gray-700 p-6 mb-8">
        <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
            <div class="md:col-span-2">
                <input type="text"
                       id="searchPool"
                       class="w-full px-4 py-3 bg-gray-50 dark:bg-gray-900 border border-gray-300 dark:border-gray-700 rounded-xl text-gray-900 dark:text-white placeholder-gray-500 dark:placeholder-gray-400 focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-600 focus:border-transparent transition-all duration-200"
                       placeholder="ค้นหา Pool...">
            </div>
            <div>
                <select id="statusFilter"
                        class="w-full px-4 py-3 bg-gray-50 dark:bg-gray-900 border border-gray-300 dark:border-gray-700 rounded-xl text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-600 focus:border-transparent transition-all duration-200">
                    <option value="">ทุกสถานะ</option>
                    <option value="active">Active</option>
                    <option value="withdrawn">Withdrawn</option>
                </select>
            </div>
            <div>
                <select id="sortBy"
                        class="w-full px-4 py-3 bg-gray-50 dark:bg-gray-900 border border-gray-300 dark:border-gray-700 rounded-xl text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-600 focus:border-transparent transition-all duration-200">
                    <option value="value_desc">มูลค่า: สูง-ต่ำ</option>
                    <option value="value_asc">มูลค่า: ต่ำ-สูง</option>
                    <option value="roi_desc">ROI: สูง-ต่ำ</option>
                    <option value="date_desc">วันที่: ใหม่-เก่า</option>
                    <option value="date_asc">วันที่: เก่า-ใหม่</option>
                </select>
            </div>
            <div>
                <button id="refreshBtn"
                        class="w-full px-4 py-3 bg-gray-100 hover:bg-gray-200 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 rounded-xl font-medium transition-colors duration-200 flex items-center justify-center">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                    </svg>
                    รีเฟรช
                </button>
            </div>
        </div>
    </div>

    {{-- Positions List --}}
    <div id="positionsList" class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        {{-- Loading state --}}
        <div class="col-span-full flex flex-col items-center justify-center py-12">
            <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600 dark:border-blue-500 mb-4"></div>
            <p class="text-gray-500 dark:text-gray-400">กำลังโหลดข้อมูล...</p>
        </div>
    </div>

    {{-- Empty State --}}
    <div id="emptyState" class="text-center py-16" style="display: none;">
        <div class="inline-flex items-center justify-center w-24 h-24 mb-6 rounded-full bg-gray-100 dark:bg-gray-700">
            <svg class="w-12 h-12 text-gray-400 dark:text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
            </svg>
        </div>
        <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-2">ยังไม่มีตำแหน่ง Liquidity</h3>
        <p class="text-gray-600 dark:text-gray-400 mb-6">เริ่มต้นเพิ่มสภาพคล่องและรับรายได้จากค่าธรรมเนียม</p>
        <a href="{{ route('user.dex.add-liquidity') }}"
           class="inline-flex items-center px-6 py-3 bg-gradient-to-r from-blue-600 to-purple-600 hover:from-blue-700 hover:to-purple-700 text-white rounded-xl font-bold transition-all duration-200 transform hover:scale-105 shadow-lg">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
            </svg>
            เพิ่ม Liquidity ครั้งแรก
        </a>
    </div>
</div>

@endsection

@push('scripts')
<script>
/**
 * ตัวแปร Global
 */
let allPositions = [];

/**
 * เริ่มต้นเมื่อ DOM พร้อม
 */
$(document).ready(function() {
    loadPositions();

    // Event listeners สำหรับ filters
    $('#searchPool, #statusFilter, #sortBy').on('change input', filterAndSort);

    // Refresh button
    $('#refreshBtn').click(function() {
        $(this).find('svg').addClass('animate-spin');
        loadPositions();
        setTimeout(() => {
            $(this).find('svg').removeClass('animate-spin');
        }, 1000);
    });
});

/**
 * โหลดข้อมูล Positions
 */
function loadPositions() {
    $.ajax({
        url: '/api/v1/tpix/dex/my-positions',
        method: 'GET',
        headers: {
            'Authorization': 'Bearer ' + localStorage.getItem('api_token')
        },
        success: function(response) {
            allPositions = response.data;
            displaySummary(response.summary);
            displayPositions(allPositions);
        },
        error: function() {
            $('#positionsList').html(`
                <div class="col-span-full">
                    <div class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-2xl p-6">
                        <div class="flex items-center">
                            <svg class="w-6 h-6 text-red-600 dark:text-red-400 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            <p class="text-red-800 dark:text-red-300 font-medium">
                                ไม่สามารถโหลดข้อมูลได้ กรุณาลองใหม่อีกครั้ง
                            </p>
                        </div>
                    </div>
                </div>
            `);
        }
    });
}

/**
 * แสดงสถิติสรุป
 *
 * @param {Object} summary - ข้อมูลสถิติ
 */
function displaySummary(summary) {
    $('#totalLiquidity').text('฿' + formatNumber(summary.total_liquidity));

    const changeClass = summary.change_24h >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400';
    $('#totalLiquidityChange')
        .text((summary.change_24h >= 0 ? '+' : '') + summary.change_24h.toFixed(2) + '%')
        .attr('class', 'text-sm font-medium ' + changeClass);

    $('#totalEarnings24h').text('฿' + formatNumber(summary.earnings_24h));
    $('#earningsAPY').text('APY: ' + summary.avg_apy.toFixed(2) + '%');

    $('#totalPools').text(summary.total_pools);
    $('#activePools').text('Active: ' + summary.active_pools);

    const roiClass = summary.avg_roi >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400';
    $('#avgROI')
        .text(summary.avg_roi.toFixed(2) + '%')
        .attr('class', 'text-2xl font-bold mb-1 ' + roiClass);
    $('#totalProfit').text('Profit: ฿' + formatNumber(summary.total_profit));
}

/**
 * แสดงรายการ Positions
 *
 * @param {Array} positions - รายการ positions
 */
function displayPositions(positions) {
    if (positions.length === 0) {
        $('#positionsList').hide();
        $('#emptyState').show();
        return;
    }

    $('#positionsList').show();
    $('#emptyState').hide();

    const html = positions.map(position => {
        const isProfit = position.roi_percent >= 0;
        const statusBadge = position.status === 'active'
            ? 'bg-green-100 dark:bg-green-900 text-green-800 dark:text-green-200'
            : 'bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-200';

        return `
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg border border-gray-200 dark:border-gray-700 p-6 hover:shadow-xl transition-shadow duration-300">
                {{-- Header --}}
                <div class="flex justify-between items-start mb-6">
                    <div class="flex items-center">
                        <div class="flex items-center mr-3">
                            <img src="${position.pool.token_a.logo_url}"
                                 class="w-10 h-10 rounded-full border-2 border-white dark:border-gray-800"
                                 onerror="this.src='/images/default-token.png'">
                            <img src="${position.pool.token_b.logo_url}"
                                 class="w-10 h-10 rounded-full border-2 border-white dark:border-gray-800 -ml-3"
                                 onerror="this.src='/images/default-token.png'">
                        </div>
                        <div>
                            <h3 class="font-bold text-gray-900 dark:text-white">${position.pool.pair}</h3>
                            <p class="text-sm text-gray-500 dark:text-gray-400">Pool #${position.pool.id}</p>
                        </div>
                    </div>
                    <span class="px-3 py-1 rounded-lg text-xs font-bold ${statusBadge}">
                        ${position.status === 'active' ? 'Active' : 'Withdrawn'}
                    </span>
                </div>

                {{-- Stats Grid --}}
                <div class="grid grid-cols-2 gap-3 mb-6">
                    <div class="bg-gray-50 dark:bg-gray-900 rounded-xl p-3">
                        <p class="text-xs text-gray-600 dark:text-gray-400 mb-1">มูลค่าปัจจุบัน</p>
                        <p class="font-bold text-blue-600 dark:text-blue-400">฿${formatNumber(position.current_value)}</p>
                    </div>
                    <div class="bg-gray-50 dark:bg-gray-900 rounded-xl p-3">
                        <p class="text-xs text-gray-600 dark:text-gray-400 mb-1">LP Tokens</p>
                        <p class="font-bold text-gray-900 dark:text-white">${formatNumber(position.lp_token_balance)}</p>
                    </div>
                    <div class="bg-gray-50 dark:bg-gray-900 rounded-xl p-3">
                        <p class="text-xs text-gray-600 dark:text-gray-400 mb-1">ส่วนแบ่งใน Pool</p>
                        <p class="font-bold text-gray-900 dark:text-white">${position.share_percentage.toFixed(4)}%</p>
                    </div>
                    <div class="bg-gray-50 dark:bg-gray-900 rounded-xl p-3">
                        <p class="text-xs text-gray-600 dark:text-gray-400 mb-1">APY</p>
                        <p class="font-bold text-green-600 dark:text-green-400">${position.pool.apy.toFixed(2)}%</p>
                    </div>
                </div>

                {{-- Token Amounts --}}
                <div class="border border-gray-200 dark:border-gray-700 rounded-xl p-4 mb-6">
                    <div class="flex justify-between items-center mb-2">
                        <div class="flex items-center">
                            <img src="${position.pool.token_a.logo_url}"
                                 class="w-5 h-5 rounded-full mr-2"
                                 onerror="this.src='/images/default-token.png'">
                            <span class="text-sm text-gray-700 dark:text-gray-300">${position.pool.token_a.symbol}</span>
                        </div>
                        <span class="font-bold text-gray-900 dark:text-white">${formatNumber(position.token_a_amount)}</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <div class="flex items-center">
                            <img src="${position.pool.token_b.logo_url}"
                                 class="w-5 h-5 rounded-full mr-2"
                                 onerror="this.src='/images/default-token.png'">
                            <span class="text-sm text-gray-700 dark:text-gray-300">${position.pool.token_b.symbol}</span>
                        </div>
                        <span class="font-bold text-gray-900 dark:text-white">${formatNumber(position.token_b_amount)}</span>
                    </div>
                </div>

                {{-- ROI & Profit --}}
                <div class="grid grid-cols-3 gap-4 mb-6">
                    <div>
                        <p class="text-xs text-gray-600 dark:text-gray-400 mb-1">ROI</p>
                        <p class="font-bold ${isProfit ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400'}">
                            ${isProfit ? '+' : ''}${position.roi_percent.toFixed(2)}%
                        </p>
                    </div>
                    <div class="text-right">
                        <p class="text-xs text-gray-600 dark:text-gray-400 mb-1">กำไร/ขาดทุน</p>
                        <p class="font-bold ${isProfit ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400'}">
                            ฿${formatNumber(position.profit_loss)}
                        </p>
                    </div>
                    <div class="text-right">
                        <p class="text-xs text-gray-600 dark:text-gray-400 mb-1">ค่าธรรมเนียม</p>
                        <p class="font-bold text-blue-600 dark:text-blue-400">฿${formatNumber(position.fees_earned)}</p>
                    </div>
                </div>

                ${position.impermanent_loss && position.impermanent_loss.loss_percentage < -1 ? `
                    <div class="bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-xl p-3 mb-6">
                        <div class="flex items-center">
                            <svg class="w-5 h-5 text-yellow-600 dark:text-yellow-400 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                            </svg>
                            <span class="text-sm text-yellow-800 dark:text-yellow-300">
                                Impermanent Loss: ${position.impermanent_loss.loss_percentage.toFixed(2)}%
                            </span>
                        </div>
                    </div>
                ` : ''}

                {{-- Actions --}}
                <div class="flex gap-2 mb-4">
                    ${position.status === 'active' ? `
                        <a href="/user/dex/remove-liquidity/${position.id}"
                           class="flex-1 px-4 py-2 bg-red-100 hover:bg-red-200 dark:bg-red-900 dark:hover:bg-red-800 text-red-700 dark:text-red-300 rounded-xl font-medium transition-colors duration-200 text-center">
                            <svg class="w-4 h-4 inline-block mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4"/>
                            </svg>
                            ถอน
                        </a>
                        <button class="flex-1 px-4 py-2 bg-blue-100 hover:bg-blue-200 dark:bg-blue-900 dark:hover:bg-blue-800 text-blue-700 dark:text-blue-300 rounded-xl font-medium transition-colors duration-200 add-more-btn"
                                data-pool-id="${position.pool.id}">
                            <svg class="w-4 h-4 inline-block mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                            </svg>
                            เพิ่ม
                        </button>
                    ` : ''}
                    <button class="flex-1 px-4 py-2 bg-gray-100 hover:bg-gray-200 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 rounded-xl font-medium transition-colors duration-200 view-details-btn"
                            data-position-id="${position.id}">
                        <svg class="w-4 h-4 inline-block mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                        </svg>
                        รายละเอียด
                    </button>
                </div>

                {{-- Footer Info --}}
                <div class="pt-4 border-t border-gray-200 dark:border-gray-700">
                    <div class="flex justify-between text-sm text-gray-500 dark:text-gray-400">
                        <span>
                            <svg class="w-4 h-4 inline-block mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                            </svg>
                            ${new Date(position.created_at).toLocaleDateString('th-TH')}
                        </span>
                        <span>
                            <svg class="w-4 h-4 inline-block mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            ${calculateDuration(position.created_at)}
                        </span>
                    </div>
                </div>
            </div>
        `;
    }).join('');

    $('#positionsList').html(html);

    // Bind event handlers
    $('.add-more-btn').click(function() {
        const poolId = $(this).data('pool-id');
        window.location.href = '{{ route("user.dex.add-liquidity") }}?pool_id=' + poolId;
    });

    $('.view-details-btn').click(function() {
        const positionId = $(this).data('position-id');
        showPositionDetails(positionId);
    });
}

/**
 * แสดงรายละเอียด Position
 *
 * @param {number} positionId - ID ของ position
 */
function showPositionDetails(positionId) {
    alert('Position details modal - To be implemented');
}

/**
 * กรองและเรียงลำดับ Positions
 */
function filterAndSort() {
    let filtered = allPositions;

    // ค้นหา
    const searchTerm = $('#searchPool').val().toLowerCase();
    if (searchTerm) {
        filtered = filtered.filter(p =>
            p.pool.pair.toLowerCase().includes(searchTerm) ||
            p.pool.token_a.symbol.toLowerCase().includes(searchTerm) ||
            p.pool.token_b.symbol.toLowerCase().includes(searchTerm)
        );
    }

    // กรองตามสถานะ
    const status = $('#statusFilter').val();
    if (status) {
        filtered = filtered.filter(p => p.status === status);
    }

    // เรียงลำดับ
    const sortBy = $('#sortBy').val();
    filtered.sort((a, b) => {
        switch(sortBy) {
            case 'value_desc': return b.current_value - a.current_value;
            case 'value_asc': return a.current_value - b.current_value;
            case 'roi_desc': return b.roi_percent - a.roi_percent;
            case 'date_desc': return new Date(b.created_at) - new Date(a.created_at);
            case 'date_asc': return new Date(a.created_at) - new Date(b.created_at);
            default: return 0;
        }
    });

    displayPositions(filtered);
}

/**
 * คำนวณระยะเวลา
 *
 * @param {string} createdAt - วันที่สร้าง
 * @return {string} - ระยะเวลา
 */
function calculateDuration(createdAt) {
    const now = new Date();
    const created = new Date(createdAt);
    const diff = now - created;
    const days = Math.floor(diff / (1000 * 60 * 60 * 24));
    if (days === 0) return 'วันนี้';
    return days + ' วัน';
}

/**
 * จัดรูปแบบตัวเลข
 *
 * @param {number} num - ตัวเลข
 * @return {string} - ตัวเลขที่จัดรูปแบบแล้ว
 */
function formatNumber(num) {
    return parseFloat(num).toLocaleString('en-US', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 8
    });
}
</script>
@endpush
