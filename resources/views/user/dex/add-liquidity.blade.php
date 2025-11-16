@extends('layouts.user-arrow-x')

@section('title', 'เพิ่ม Liquidity - TPIX DEX')

@section('content')
<div class="min-h-screen">
    {{-- Header Section --}}
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-8 gap-4">
        <div>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white mb-2 flex items-center">
                <svg class="w-8 h-8 mr-3 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                </svg>
                เพิ่ม Liquidity
            </h1>
            <p class="text-gray-600 dark:text-gray-400">เพิ่มสภาพคล่องและรับส่วนแบ่งค่าธรรมเนียม 0.3%</p>
        </div>
        <a href="{{ route('user.dex.pools') }}"
           class="inline-flex items-center px-6 py-3 bg-gray-100 hover:bg-gray-200 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 rounded-xl font-medium transition-colors duration-200">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
            </svg>
            กลับ
        </a>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        {{-- Main Form --}}
        <div class="lg:col-span-2">
            <div class="bg-white dark:bg-gray-800 rounded-3xl shadow-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
                {{-- Card Header --}}
                <div class="bg-gradient-to-r from-blue-50 to-purple-50 dark:from-gray-900 dark:to-gray-800 border-b border-gray-200 dark:border-gray-700 p-6">
                    <h2 class="text-xl font-bold text-gray-900 dark:text-white">เลือกคู่ Token</h2>
                </div>

                {{-- Card Body --}}
                <div class="p-8">
                    {{-- Token A --}}
                    <div class="bg-gray-50 dark:bg-gray-900 rounded-2xl p-6 mb-4">
                        <div class="flex justify-between items-center mb-3">
                            <label class="text-sm font-medium text-gray-600 dark:text-gray-400">Token A</label>
                            <span class="text-sm text-gray-500 dark:text-gray-400">
                                คงเหลือ: <span id="balanceA" class="font-semibold text-gray-900 dark:text-white">0.00</span>
                            </span>
                        </div>
                        <div class="flex items-center gap-4">
                            <input type="number"
                                   id="amountA"
                                   class="flex-1 bg-transparent text-3xl font-bold text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-gray-600 outline-none border-0 p-0"
                                   placeholder="0.0"
                                   step="0.00000001">
                            <button type="button"
                                    class="group flex items-center gap-3 px-6 py-3 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700 rounded-xl shadow-md transition-all duration-200 border-2 border-gray-200 dark:border-gray-600"
                                    onclick="openTokenModal('a')">
                                <span id="tokenA" class="flex items-center gap-2">
                                    <svg class="w-6 h-6 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                    <span class="font-semibold text-gray-900 dark:text-white">เลือก Token</span>
                                </span>
                                <svg class="w-5 h-5 text-gray-400 group-hover:text-gray-600 dark:group-hover:text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                </svg>
                            </button>
                        </div>
                        <button type="button"
                                id="maxButtonA"
                                class="mt-3 text-sm text-blue-600 dark:text-blue-400 hover:text-blue-700 dark:hover:text-blue-300 font-medium transition-colors">
                            ใช้ทั้งหมด
                        </button>
                    </div>

                    {{-- Plus Icon --}}
                    <div class="flex justify-center -my-2 relative z-10">
                        <div class="w-12 h-12 bg-white dark:bg-gray-800 rounded-full border-4 border-gray-100 dark:border-gray-900 shadow-lg flex items-center justify-center">
                            <svg class="w-6 h-6 text-gray-400 dark:text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                            </svg>
                        </div>
                    </div>

                    {{-- Token B --}}
                    <div class="bg-gray-50 dark:bg-gray-900 rounded-2xl p-6 mb-6">
                        <div class="flex justify-between items-center mb-3">
                            <label class="text-sm font-medium text-gray-600 dark:text-gray-400">Token B</label>
                            <span class="text-sm text-gray-500 dark:text-gray-400">
                                คงเหลือ: <span id="balanceB" class="font-semibold text-gray-900 dark:text-white">0.00</span>
                            </span>
                        </div>
                        <div class="flex items-center gap-4">
                            <input type="number"
                                   id="amountB"
                                   class="flex-1 bg-transparent text-3xl font-bold text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-gray-600 outline-none border-0 p-0"
                                   placeholder="0.0"
                                   step="0.00000001">
                            <button type="button"
                                    class="group flex items-center gap-3 px-6 py-3 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700 rounded-xl shadow-md transition-all duration-200 border-2 border-gray-200 dark:border-gray-600"
                                    onclick="openTokenModal('b')">
                                <span id="tokenB" class="flex items-center gap-2">
                                    <svg class="w-6 h-6 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                    <span class="font-semibold text-gray-900 dark:text-white">เลือก Token</span>
                                </span>
                                <svg class="w-5 h-5 text-gray-400 group-hover:text-gray-600 dark:group-hover:text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                </svg>
                            </button>
                        </div>
                        <button type="button"
                                id="maxButtonB"
                                class="mt-3 text-sm text-blue-600 dark:text-blue-400 hover:text-blue-700 dark:hover:text-blue-300 font-medium transition-colors">
                            ใช้ทั้งหมด
                        </button>
                    </div>

                    {{-- Pool Info --}}
                    <div id="poolInfo" class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-2xl p-6 mb-6" style="display: none;">
                        <h3 class="font-bold text-gray-900 dark:text-white mb-4 flex items-center">
                            <svg class="w-5 h-5 mr-2 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            ข้อมูล Pool
                        </h3>
                        <div class="space-y-3">
                            <div class="flex justify-between items-center">
                                <span class="text-sm text-gray-600 dark:text-gray-400">สภาพคล่องทั้งหมด (TVL)</span>
                                <span class="font-bold text-gray-900 dark:text-white" id="poolTVL">-</span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-sm text-gray-600 dark:text-gray-400">อัตราส่วน</span>
                                <span class="font-medium text-gray-900 dark:text-white" id="poolRatio">-</span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-sm text-gray-600 dark:text-gray-400">APY</span>
                                <span class="font-bold text-green-600 dark:text-green-400" id="poolAPY">-</span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-sm text-gray-600 dark:text-gray-400">ส่วนแบ่งของคุณ</span>
                                <span class="font-medium text-gray-900 dark:text-white" id="yourShare">0%</span>
                            </div>
                        </div>
                    </div>

                    {{-- New Pool Notice --}}
                    <div id="newPoolNotice" class="bg-purple-50 dark:bg-purple-900/20 border border-purple-200 dark:border-purple-800 rounded-2xl p-6 mb-6" style="display: none;">
                        <div class="flex items-start">
                            <svg class="w-6 h-6 text-purple-600 dark:text-purple-400 mr-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            <div>
                                <h4 class="font-bold text-purple-900 dark:text-purple-200 mb-1">Pool ใหม่</h4>
                                <p class="text-sm text-purple-700 dark:text-purple-300">คุณกำลังสร้าง Liquidity Pool ใหม่สำหรับคู่ token นี้</p>
                            </div>
                        </div>
                    </div>

                    {{-- Expected Output --}}
                    <div id="expectedOutput" class="bg-gradient-to-br from-green-50 to-blue-50 dark:from-green-900/20 dark:to-blue-900/20 border border-green-200 dark:border-green-800 rounded-2xl p-6 mb-6" style="display: none;">
                        <h3 class="font-bold text-gray-900 dark:text-white mb-4">คุณจะได้รับ</h3>
                        <div class="space-y-3">
                            <div class="flex justify-between items-center">
                                <span class="text-sm text-gray-600 dark:text-gray-400">LP Tokens</span>
                                <span class="font-bold text-gray-900 dark:text-white" id="lpTokenAmount">-</span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-sm text-gray-600 dark:text-gray-400">ส่วนแบ่งใน Pool</span>
                                <span class="font-bold text-green-600 dark:text-green-400" id="sharePercent">-</span>
                            </div>
                        </div>
                    </div>

                    {{-- Estimated Earnings --}}
                    <div id="estimatedEarnings" class="grid grid-cols-3 gap-4 bg-gray-50 dark:bg-gray-900 rounded-2xl p-6 mb-6" style="display: none;">
                        <div class="text-center">
                            <p class="text-xs text-gray-500 dark:text-gray-400 mb-1">รายได้/วัน</p>
                            <p class="font-bold text-green-600 dark:text-green-400" id="dailyEarnings">-</p>
                        </div>
                        <div class="text-center">
                            <p class="text-xs text-gray-500 dark:text-gray-400 mb-1">รายได้/เดือน</p>
                            <p class="font-bold text-green-600 dark:text-green-400" id="monthlyEarnings">-</p>
                        </div>
                        <div class="text-center">
                            <p class="text-xs text-gray-500 dark:text-gray-400 mb-1">รายได้/ปี</p>
                            <p class="font-bold text-green-600 dark:text-green-400" id="yearlyEarnings">-</p>
                        </div>
                    </div>

                    {{-- Submit Button --}}
                    <button type="submit"
                            id="addLiquidityBtn"
                            disabled
                            class="w-full px-6 py-4 bg-gradient-to-r from-blue-600 to-purple-600 hover:from-blue-700 hover:to-purple-700 disabled:from-gray-400 disabled:to-gray-500 disabled:cursor-not-allowed text-white font-bold rounded-xl transform hover:scale-105 disabled:transform-none transition-all duration-200 shadow-lg flex items-center justify-center">
                        <svg class="w-6 h-6 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                        </svg>
                        เพิ่ม Liquidity
                    </button>

                    {{-- Warning Box --}}
                    <div class="mt-6 bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-2xl p-6">
                        <h4 class="font-bold text-yellow-900 dark:text-yellow-200 mb-3 flex items-center">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                            </svg>
                            คำเตือนสำคัญ
                        </h4>
                        <ul class="space-y-2 text-sm text-yellow-800 dark:text-yellow-300">
                            <li class="flex items-start">
                                <span class="mr-2">•</span>
                                <span>การเพิ่ม Liquidity มีความเสี่ยงจาก <strong>Impermanent Loss</strong></span>
                            </li>
                            <li class="flex items-start">
                                <span class="mr-2">•</span>
                                <span>ควรศึกษาข้อมูลก่อนลงทุน</span>
                            </li>
                            <li class="flex items-start">
                                <span class="mr-2">•</span>
                                <span>รายได้ที่แสดงเป็นเพียงการประมาณการ</span>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>

            {{-- Educational Section --}}
            <div class="mt-6 bg-white dark:bg-gray-800 rounded-2xl shadow-lg border border-gray-200 dark:border-gray-700 p-6">
                <h3 class="font-bold text-gray-900 dark:text-white mb-4 flex items-center">
                    <svg class="w-6 h-6 mr-2 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path d="M12 14l9-5-9-5-9 5 9 5z"/>
                        <path d="M12 14l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l9-5-9-5-9 5 9 5zm0 0l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14zm-4 6v-7.5l4-2.222"/>
                    </svg>
                    ทำไมต้องเพิ่ม Liquidity?
                </h3>
                <ul class="space-y-3">
                    <li class="flex items-start">
                        <svg class="w-5 h-5 text-green-600 dark:text-green-400 mr-3 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <span class="text-sm text-gray-700 dark:text-gray-300">รับส่วนแบ่งค่าธรรมเนียม 0.3% จากทุก swap</span>
                    </li>
                    <li class="flex items-start">
                        <svg class="w-5 h-5 text-green-600 dark:text-green-400 mr-3 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <span class="text-sm text-gray-700 dark:text-gray-300">สร้างรายได้แบบ Passive Income</span>
                    </li>
                    <li class="flex items-start">
                        <svg class="w-5 h-5 text-green-600 dark:text-green-400 mr-3 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <span class="text-sm text-gray-700 dark:text-gray-300">ถอนได้ทุกเมื่อ ไม่มีการล็อก</span>
                    </li>
                    <li class="flex items-start">
                        <svg class="w-5 h-5 text-blue-600 dark:text-blue-400 mr-3 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <span class="text-sm text-gray-700 dark:text-gray-300">รายได้ขึ้นอยู่กับปริมาณการซื้อขายใน Pool</span>
                    </li>
                </ul>
            </div>
        </div>

        {{-- Sidebar --}}
        <div class="lg:col-span-1 space-y-6">
            {{-- My LP Positions Summary --}}
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg border border-gray-200 dark:border-gray-700 p-6">
                <h3 class="font-bold text-gray-900 dark:text-white mb-4 flex items-center">
                    <svg class="w-5 h-5 mr-2 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
                    </svg>
                    ตำแหน่ง Liquidity ของคุณ
                </h3>
                <div class="text-center py-6 mb-4">
                    <h2 class="text-3xl font-bold text-gray-900 dark:text-white mb-1" id="myTotalLiquidity">฿0.00</h2>
                    <p class="text-sm text-gray-500 dark:text-gray-400">มูลค่าสภาพคล่องทั้งหมด</p>
                </div>
                <div class="border-t border-gray-200 dark:border-gray-700 pt-4 space-y-3">
                    <div class="flex justify-between items-center">
                        <span class="text-sm text-gray-600 dark:text-gray-400">จำนวน Pools</span>
                        <span class="font-bold text-gray-900 dark:text-white" id="myPoolsCount">0</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-sm text-gray-600 dark:text-gray-400">รายได้รวม (24ชม.)</span>
                        <span class="font-bold text-green-600 dark:text-green-400" id="myEarnings24h">฿0.00</span>
                    </div>
                </div>
                <a href="{{ route('user.dex.my-positions') }}"
                   class="mt-4 w-full inline-flex items-center justify-center px-4 py-2 bg-blue-100 hover:bg-blue-200 dark:bg-blue-900 dark:hover:bg-blue-800 text-blue-700 dark:text-blue-300 rounded-xl font-medium transition-colors duration-200">
                    ดูทั้งหมด
                </a>
            </div>

            {{-- Top Pools --}}
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg border border-gray-200 dark:border-gray-700 p-6">
                <h3 class="font-bold text-gray-900 dark:text-white mb-4 flex items-center">
                    <svg class="w-5 h-5 mr-2 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 18.657A8 8 0 016.343 7.343S7 9 9 10c0-2 .5-5 2.986-7C14 5 16.09 5.777 17.656 7.343A7.975 7.975 0 0120 13a7.975 7.975 0 01-2.343 5.657z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.879 16.121A3 3 0 1012.015 11L11 14H9c0 .768.293 1.536.879 2.121z"/>
                    </svg>
                    Pools ยอดนิยม
                </h3>
                <div id="topPools">
                    <div class="text-center py-8">
                        <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600 dark:border-blue-500 mx-auto"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Token Select Modal --}}
<div x-data="{ showModal: false, currentTarget: '' }"
     @show-token-modal.window="showModal = true; currentTarget = $event.detail.target"
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
        <div class="relative bg-white dark:bg-gray-800 rounded-2xl shadow-2xl max-w-md w-full mx-auto"
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
                <h3 class="text-xl font-bold text-white">เลือก Token</h3>
                <button @click="showModal = false"
                        class="text-white/80 hover:text-white transition-colors">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            {{-- Content --}}
            <div class="p-6">
                <input type="text"
                       id="tokenSearch"
                       class="w-full px-4 py-3 bg-gray-50 dark:bg-gray-900 border border-gray-300 dark:border-gray-700 rounded-xl text-gray-900 dark:text-white placeholder-gray-500 dark:placeholder-gray-400 focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-600 focus:border-transparent transition-all duration-200 mb-4"
                       placeholder="ค้นหา Token...">
                <div id="tokenList" class="max-h-96 overflow-y-auto space-y-2">
                    {{-- Tokens loaded via AJAX --}}
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Styles --}}
<style>
/**
 * Alpine.js x-cloak
 */
[x-cloak] {
    display: none !important;
}

/**
 * Token item hover effect
 */
.token-item {
    transition: all 0.2s ease;
}

.token-item:hover {
    transform: translateX(4px);
}
</style>

@push('scripts')
<script>
/**
 * ตัวแปร Global สำหรับเก็บข้อมูล
 */
let selectedTarget = '';
let tokenAData = null;
let tokenBData = null;
let poolData = null;

/**
 * เปิด Token Modal
 *
 * @param {string} target - 'a' หรือ 'b'
 */
function openTokenModal(target) {
    selectedTarget = target;
    window.dispatchEvent(new CustomEvent('show-token-modal', { detail: { target } }));
    loadTokens();
}

/**
 * โหลดรายการ Tokens ทั้งหมด
 */
function loadTokens() {
    $.ajax({
        url: '/api/v1/tpix/tokens',
        method: 'GET',
        headers: {
            'Authorization': 'Bearer ' + localStorage.getItem('api_token')
        },
        success: function(response) {
            let html = '';
            response.data.forEach(token => {
                html += `
                    <div class="token-item p-4 bg-gray-50 dark:bg-gray-900 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-xl cursor-pointer border border-gray-200 dark:border-gray-700"
                         onclick='selectToken(${JSON.stringify(token)})'>
                        <div class="flex items-center">
                            <img src="${token.logo_url}"
                                 class="w-10 h-10 rounded-full mr-3 border-2 border-white dark:border-gray-800"
                                 onerror="this.src='/images/default-token.png'">
                            <div class="flex-1">
                                <div class="font-bold text-gray-900 dark:text-white">
                                    ${token.name}
                                    <span class="text-sm text-gray-500 dark:text-gray-400">${token.symbol}</span>
                                </div>
                                <div class="text-sm text-gray-600 dark:text-gray-400">
                                    คงเหลือ: ${token.balance || '0.00'}
                                </div>
                            </div>
                        </div>
                    </div>
                `;
            });
            $('#tokenList').html(html);
        },
        error: function() {
            $('#tokenList').html(`
                <div class="text-center py-8 text-red-500 dark:text-red-400">
                    <p>ไม่สามารถโหลดรายการ Token ได้</p>
                </div>
            `);
        }
    });
}

/**
 * เลือก Token
 *
 * @param {Object} token - ข้อมูล token
 */
function selectToken(token) {
    if (selectedTarget === 'a') {
        tokenAData = token;
        $('#tokenA').html(`
            <img src="${token.logo_url}"
                 width="24"
                 height="24"
                 class="rounded-full mr-2"
                 onerror="this.src='/images/default-token.png'">
            <span class="font-semibold text-gray-900 dark:text-white">${token.symbol}</span>
        `);
        $('#balanceA').text(token.balance || '0.00');
    } else if (selectedTarget === 'b') {
        tokenBData = token;
        $('#tokenB').html(`
            <img src="${token.logo_url}"
                 width="24"
                 height="24"
                 class="rounded-full mr-2"
                 onerror="this.src='/images/default-token.png'">
            <span class="font-semibold text-gray-900 dark:text-white">${token.symbol}</span>
        `);
        $('#balanceB').text(token.balance || '0.00');
    }

    // ปิด modal
    window.dispatchEvent(new CustomEvent('show-token-modal', { detail: { target: '' } }));

    checkBothTokensSelected();
}

/**
 * ตรวจสอบว่าเลือก Token ครบทั้ง 2 ตัวหรือยัง
 */
function checkBothTokensSelected() {
    if (tokenAData && tokenBData) {
        checkPool();
        $('#addLiquidityBtn').prop('disabled', false);
    }
}

/**
 * ตรวจสอบว่า Pool มีอยู่หรือไม่
 */
function checkPool() {
    $.ajax({
        url: '/api/v1/tpix/dex/pools/find',
        data: {
            token_a_id: tokenAData.id,
            token_b_id: tokenBData.id
        },
        headers: {
            'Authorization': 'Bearer ' + localStorage.getItem('api_token')
        },
        success: function(response) {
            if (response.data) {
                poolData = response.data;
                displayPoolInfo();
                $('#newPoolNotice').hide();
            } else {
                poolData = null;
                $('#poolInfo').hide();
                $('#newPoolNotice').show();
            }
        }
    });
}

/**
 * แสดงข้อมูล Pool
 */
function displayPoolInfo() {
    $('#poolInfo').show();
    $('#poolTVL').text('฿' + formatNumber(poolData.tvl));
    $('#poolRatio').text(`1 ${tokenAData.symbol} = ${poolData.price} ${tokenBData.symbol}`);
    $('#poolAPY').text(poolData.apy.toFixed(2) + '%');
}

/**
 * คำนวณ Liquidity
 */
function calculateLiquidity() {
    const amountA = parseFloat($('#amountA').val()) || 0;
    const amountB = parseFloat($('#amountB').val()) || 0;

    if (amountA > 0 && amountB > 0 && poolData) {
        $('#expectedOutput, #estimatedEarnings').show();

        // คำนวณ LP tokens (แบบง่าย)
        const lpTokens = Math.sqrt(amountA * amountB);
        $('#lpTokenAmount').text(formatNumber(lpTokens));

        // คำนวณส่วนแบ่ง
        const totalLiquidity = parseFloat(poolData.total_liquidity) || 1;
        const share = ((lpTokens / (totalLiquidity + lpTokens)) * 100).toFixed(4);
        $('#sharePercent').text(share + '%');

        // ประมาณการรายได้
        const totalValue = amountA * (tokenAData.price || 1) + amountB * (tokenBData.price || 1);
        const yearlyEarning = totalValue * (poolData.apy / 100);
        $('#dailyEarnings').text('฿' + formatNumber(yearlyEarning / 365));
        $('#monthlyEarnings').text('฿' + formatNumber(yearlyEarning / 12));
        $('#yearlyEarnings').text('฿' + formatNumber(yearlyEarning));
    }
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

/**
 * โหลด Top Pools
 */
function loadTopPools() {
    $.ajax({
        url: '/api/v1/tpix/dex/pools/top',
        headers: {
            'Authorization': 'Bearer ' + localStorage.getItem('api_token')
        },
        success: function(response) {
            let html = '';
            response.data.slice(0, 5).forEach(pool => {
                html += `
                    <div class="flex justify-between items-center p-3 bg-gray-50 dark:bg-gray-900 rounded-xl mb-2 border border-gray-200 dark:border-gray-700">
                        <div class="flex-1">
                            <div class="font-bold text-sm text-gray-900 dark:text-white">${pool.pair}</div>
                            <div class="text-xs text-gray-500 dark:text-gray-400">TVL: ฿${formatNumber(pool.tvl)}</div>
                        </div>
                        <span class="inline-flex items-center px-3 py-1 rounded-lg bg-gradient-to-r from-green-500 to-green-600 text-white font-bold text-xs">
                            ${pool.apy.toFixed(2)}%
                        </span>
                    </div>
                `;
            });
            $('#topPools').html(html);
        },
        error: function() {
            $('#topPools').html(`
                <div class="text-center py-4 text-gray-500 dark:text-gray-400">
                    <p class="text-sm">ไม่สามารถโหลดข้อมูลได้</p>
                </div>
            `);
        }
    });
}

/**
 * เริ่มต้นเมื่อ DOM พร้อม
 */
$(document).ready(function() {
    // Event listeners
    $('#amountA, #amountB').on('input', calculateLiquidity);

    // Max buttons
    $('#maxButtonA').click(function() {
        if (tokenAData) {
            $('#amountA').val(tokenAData.balance);
            calculateLiquidity();
        }
    });

    $('#maxButtonB').click(function() {
        if (tokenBData) {
            $('#amountB').val(tokenBData.balance);
            calculateLiquidity();
        }
    });

    // Submit button
    $('#addLiquidityBtn').click(function() {
        const amountA = $('#amountA').val();
        const amountB = $('#amountB').val();

        if (!amountA || !amountB) {
            alert('กรุณากรอกจำนวน Token ทั้งสอง');
            return;
        }

        if (!tokenAData || !tokenBData) {
            alert('กรุณาเลือก Token ทั้งสอง');
            return;
        }

        $.ajax({
            url: '/api/v1/tpix/dex/liquidity/add',
            method: 'POST',
            data: {
                token_a_id: tokenAData.id,
                token_b_id: tokenBData.id,
                amount_a: amountA,
                amount_b: amountB,
                _token: $('meta[name="csrf-token"]').attr('content')
            },
            headers: {
                'Authorization': 'Bearer ' + localStorage.getItem('api_token')
            },
            success: function(response) {
                alert('เพิ่ม Liquidity สำเร็จ!');
                window.location.href = '{{ route("user.dex.my-positions") }}';
            },
            error: function(xhr) {
                alert('เกิดข้อผิดพลาด: ' + (xhr.responseJSON?.message || 'Unknown error'));
            }
        });
    });

    // โหลด Top Pools
    loadTopPools();
});
</script>
@endpush
@endsection
