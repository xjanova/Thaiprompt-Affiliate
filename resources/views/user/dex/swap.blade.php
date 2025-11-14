@extends('layouts.user')

@section('title', 'แลกเปลี่ยน Token - TPIX DEX')

@section('content')
{{-- Hero Section with Gradient --}}
<div class="relative overflow-hidden rounded-3xl bg-gradient-to-br from-purple-600 via-pink-600 to-blue-600 dark:from-purple-900 dark:via-pink-900 dark:to-blue-900 p-12 mb-8 shadow-2xl">
    <div class="absolute inset-0 bg-black/10 dark:bg-white/5"></div>
    <div class="relative z-10 text-center">
        <div class="inline-flex items-center gap-3 bg-white/20 dark:bg-black/30 backdrop-blur-sm rounded-full px-6 py-2 mb-4">
            <i class="fas fa-exchange-alt text-2xl text-white"></i>
            <span class="text-white font-bold text-xl">TPIX DEX</span>
        </div>
        <h1 class="text-4xl md:text-5xl font-bold text-white mb-3 drop-shadow-lg">
            แลกเปลี่ยน Token ได้ทันที
        </h1>
        <p class="text-white/90 text-lg mb-2">ด้วยเทคโนโลยี AMM (Automated Market Maker)</p>
        <p class="text-white/80 text-sm">ค่าธรรมเนียม 0.3% • ไม่มีค่าแก๊สเพิ่มเติม</p>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
    {{-- Swap Card --}}
    <div class="lg:col-span-2">
        <div class="bg-white dark:bg-gray-800 rounded-3xl shadow-xl border border-gray-200 dark:border-gray-700 overflow-hidden transition-all duration-300">
            {{-- Card Header --}}
            <div class="bg-gradient-to-r from-purple-50 to-pink-50 dark:from-gray-900 dark:to-gray-800 border-b border-gray-200 dark:border-gray-700 p-6">
                <div class="flex items-center justify-between">
                    <h2 class="text-2xl font-bold text-gray-900 dark:text-white flex items-center gap-3">
                        <span class="w-10 h-10 rounded-full bg-gradient-to-br from-purple-600 to-pink-600 flex items-center justify-center">
                            <i class="fas fa-exchange-alt text-white text-lg"></i>
                        </span>
                        แลกเปลี่ยน Token
                    </h2>
                    <button type="button"
                            class="w-10 h-10 rounded-full bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 text-gray-600 dark:text-gray-300 transition-all duration-200 flex items-center justify-center"
                            onclick="openSettings()">
                        <i class="fas fa-cog text-lg"></i>
                    </button>
                </div>
            </div>

            {{-- Card Body --}}
            <div class="p-8">
                <form id="swapForm">
                    @csrf

                    {{-- From Token --}}
                    <div class="bg-gray-50 dark:bg-gray-900 rounded-2xl p-6 mb-4">
                        <div class="flex justify-between items-center mb-3">
                            <label class="text-sm font-medium text-gray-600 dark:text-gray-400">จาก</label>
                            <span class="text-sm text-gray-500 dark:text-gray-400">
                                คงเหลือ: <span id="fromBalance" class="font-semibold text-gray-900 dark:text-white">0.00</span>
                            </span>
                        </div>
                        <div class="flex items-center gap-4">
                            <input type="number"
                                   id="amountIn"
                                   class="flex-1 bg-transparent text-3xl font-bold text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-gray-600 outline-none border-0 p-0"
                                   placeholder="0.0"
                                   step="0.00000001">
                            <button type="button"
                                    class="group flex items-center gap-3 px-6 py-3 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700 rounded-xl shadow-md transition-all duration-200 border-2 border-gray-200 dark:border-gray-600"
                                    onclick="selectToken('from')">
                                <span id="fromToken" class="flex items-center gap-2">
                                    <i class="fas fa-coins text-purple-600 dark:text-purple-400 text-xl"></i>
                                    <span class="font-semibold text-gray-900 dark:text-white">เลือก Token</span>
                                </span>
                                <i class="fas fa-chevron-down text-gray-400 group-hover:text-gray-600 dark:group-hover:text-gray-300 transition-colors"></i>
                            </button>
                        </div>
                        <button type="button"
                                id="maxButton"
                                class="mt-3 text-sm text-purple-600 dark:text-purple-400 hover:text-purple-700 dark:hover:text-purple-300 font-medium transition-colors">
                            ใช้ทั้งหมด
                        </button>
                    </div>

                    {{-- Swap Direction Button --}}
                    <div class="flex justify-center -my-2 relative z-10">
                        <button type="button"
                                id="swapDirection"
                                class="group w-12 h-12 bg-white dark:bg-gray-800 hover:bg-gradient-to-br hover:from-purple-600 hover:to-pink-600 rounded-full border-4 border-gray-100 dark:border-gray-900 shadow-lg transition-all duration-300 flex items-center justify-center hover:rotate-180">
                            <i class="fas fa-arrow-down text-gray-600 dark:text-gray-400 group-hover:text-white text-lg transition-colors"></i>
                        </button>
                    </div>

                    {{-- To Token --}}
                    <div class="bg-gray-50 dark:bg-gray-900 rounded-2xl p-6 mb-6">
                        <div class="flex justify-between items-center mb-3">
                            <label class="text-sm font-medium text-gray-600 dark:text-gray-400">ไปยัง</label>
                            <span class="text-sm text-gray-500 dark:text-gray-400">
                                คงเหลือ: <span id="toBalance" class="font-semibold text-gray-900 dark:text-white">0.00</span>
                            </span>
                        </div>
                        <div class="flex items-center gap-4">
                            <input type="number"
                                   id="amountOut"
                                   class="flex-1 bg-transparent text-3xl font-bold text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-gray-600 outline-none border-0 p-0"
                                   placeholder="0.0"
                                   readonly>
                            <button type="button"
                                    class="group flex items-center gap-3 px-6 py-3 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700 rounded-xl shadow-md transition-all duration-200 border-2 border-gray-200 dark:border-gray-600"
                                    onclick="selectToken('to')">
                                <span id="toToken" class="flex items-center gap-2">
                                    <i class="fas fa-coins text-green-600 dark:text-green-400 text-xl"></i>
                                    <span class="font-semibold text-gray-900 dark:text-white">เลือก Token</span>
                                </span>
                                <i class="fas fa-chevron-down text-gray-400 group-hover:text-gray-600 dark:group-hover:text-gray-300 transition-colors"></i>
                            </button>
                        </div>
                    </div>

                    {{-- Price Info --}}
                    <div id="priceInfo" class="hidden mb-6">
                        <div class="bg-gradient-to-br from-blue-50 to-purple-50 dark:from-gray-900 dark:to-gray-800 rounded-2xl p-6 border border-blue-100 dark:border-gray-700">
                            <div class="space-y-3">
                                <div class="flex justify-between items-center">
                                    <span class="text-sm text-gray-600 dark:text-gray-400">ราคา</span>
                                    <span id="price" class="text-sm font-semibold text-gray-900 dark:text-white">-</span>
                                </div>
                                <div class="flex justify-between items-center">
                                    <span class="text-sm text-gray-600 dark:text-gray-400">Slippage Tolerance</span>
                                    <span id="slippageDisplay" class="text-sm font-medium text-gray-900 dark:text-white">0.5%</span>
                                </div>
                                <div class="flex justify-between items-center">
                                    <span class="text-sm text-gray-600 dark:text-gray-400">Price Impact</span>
                                    <span id="priceImpact" class="text-sm font-semibold text-green-600 dark:text-green-400">-</span>
                                </div>
                                <div class="flex justify-between items-center">
                                    <span class="text-sm text-gray-600 dark:text-gray-400">ค่าธรรมเนียม (0.3%)</span>
                                    <span id="fee" class="text-sm font-medium text-gray-900 dark:text-white">-</span>
                                </div>
                                <div class="border-t border-gray-200 dark:border-gray-700 pt-3 mt-3">
                                    <div class="flex justify-between items-center">
                                        <span class="text-sm font-medium text-gray-900 dark:text-white">คุณจะได้รับอย่างน้อย</span>
                                        <span id="minimumReceived" class="text-sm font-bold text-purple-600 dark:text-purple-400">-</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Warning Message --}}
                    <div id="warningMessage" class="hidden mb-6 bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-700 rounded-xl p-4">
                        <div class="flex items-start gap-3">
                            <i class="fas fa-exclamation-triangle text-yellow-600 dark:text-yellow-500 text-xl"></i>
                            <p class="text-sm text-yellow-800 dark:text-yellow-300 font-medium"></p>
                        </div>
                    </div>

                    {{-- Swap Button --}}
                    <button type="submit"
                            id="swapButton"
                            class="w-full bg-gradient-to-r from-purple-600 to-pink-600 hover:from-purple-700 hover:to-pink-700 disabled:from-gray-400 disabled:to-gray-500 text-white font-bold text-lg py-5 rounded-2xl shadow-lg hover:shadow-xl transition-all duration-300 transform hover:scale-[1.02] active:scale-[0.98] disabled:cursor-not-allowed disabled:hover:scale-100"
                            disabled>
                        <span class="flex items-center justify-center gap-2">
                            <i class="fas fa-exchange-alt"></i>
                            <span>เลือก Token</span>
                        </span>
                    </button>
                </form>
            </div>
        </div>
    </div>

    {{-- Recent Swaps Sidebar --}}
    <div class="lg:col-span-1">
        <div class="bg-white dark:bg-gray-800 rounded-3xl shadow-xl border border-gray-200 dark:border-gray-700 overflow-hidden sticky top-24">
            {{-- Header --}}
            <div class="bg-gradient-to-r from-gray-50 to-gray-100 dark:from-gray-900 dark:to-gray-800 border-b border-gray-200 dark:border-gray-700 p-6">
                <h3 class="text-lg font-bold text-gray-900 dark:text-white flex items-center gap-2">
                    <i class="fas fa-history text-purple-600 dark:text-purple-400"></i>
                    การแลกเปลี่ยนล่าสุด
                </h3>
            </div>

            {{-- Content --}}
            <div id="recentSwaps" class="divide-y divide-gray-200 dark:divide-gray-700 max-h-[600px] overflow-y-auto">
                <div class="flex flex-col items-center justify-center py-12 px-6 text-center">
                    <i class="fas fa-exchange-alt text-6xl text-gray-300 dark:text-gray-600 mb-4"></i>
                    <p class="text-sm text-gray-500 dark:text-gray-400">ยังไม่มีประวัติการแลกเปลี่ยน</p>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Token Select Modal --}}
<div id="tokenSelectModal" class="fixed inset-0 bg-black/50 dark:bg-black/70 backdrop-blur-sm z-50 hidden flex items-center justify-center p-4">
    <div class="bg-white dark:bg-gray-800 rounded-3xl shadow-2xl max-w-md w-full max-h-[80vh] overflow-hidden transform transition-all">
        <div class="bg-gradient-to-r from-purple-50 to-pink-50 dark:from-gray-900 dark:to-gray-800 border-b border-gray-200 dark:border-gray-700 p-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-xl font-bold text-gray-900 dark:text-white">เลือก Token</h3>
                <button onclick="closeTokenModal()" class="w-8 h-8 rounded-full bg-gray-200 dark:bg-gray-700 hover:bg-gray-300 dark:hover:bg-gray-600 text-gray-600 dark:text-gray-300 transition-colors flex items-center justify-center">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <input type="text"
                   id="tokenSearch"
                   class="w-full bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl px-4 py-3 text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-gray-600 focus:ring-2 focus:ring-purple-500 focus:border-transparent"
                   placeholder="ค้นหา Token...">
        </div>
        <div id="tokenList" class="p-4 overflow-y-auto max-h-[500px]">
            <div class="flex items-center justify-center py-12">
                <div class="animate-spin rounded-full h-12 w-12 border-4 border-purple-600 border-t-transparent"></div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
// จะเพิ่ม JavaScript สำหรับ DEX swap ในภายหลัง
// ตอนนี้ให้แสดง UI ที่สวยงามก่อน
console.log('TPIX DEX Swap - View loaded');
</script>
@endpush
@endsection
