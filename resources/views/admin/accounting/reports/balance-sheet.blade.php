@extends('layouts.admin')

@section('title', 'งบดุล')

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
                 x-transition
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
    <div class="mb-8 relative overflow-hidden bg-gradient-to-r from-blue-500 via-indigo-600 to-purple-600 rounded-3xl shadow-2xl p-8">
        <div class="absolute top-0 right-0 -mt-4 -mr-4 w-32 h-32 bg-white/10 rounded-full blur-3xl"></div>
        <div class="absolute bottom-0 left-0 -mb-4 -ml-4 w-40 h-40 bg-indigo-400/10 rounded-full blur-3xl"></div>

        <div class="relative flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
            <div class="flex items-center gap-4">
                <a href="{{ route('admin.accounting.reports.index') }}"
                   class="p-3 bg-white/20 hover:bg-white/30 backdrop-blur-sm rounded-xl transition-all group">
                    <svg class="w-6 h-6 text-white group-hover:-translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                    </svg>
                </a>
                <div>
                    <h1 class="text-4xl font-bold text-white mb-2 flex items-center gap-3">
                        <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 6l3 1m0 0l-3 9a5.002 5.002 0 006.001 0M6 7l3 9M6 7l6-2m6 2l3-1m-3 1l-3 9a5.002 5.002 0 006.001 0M18 7l3 9m-3-9l-6-2m0-2v2m0 16V5m0 16H9m3 0h3"></path>
                        </svg>
                        <span data-translate>งบดุล</span>
                    </h1>
                    <p class="text-blue-50 text-lg">Balance Sheet</p>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="flex flex-wrap gap-3">
                <button onclick="window.print()"
                        class="px-6 py-3 bg-white/20 hover:bg-white/30 backdrop-blur-sm text-white rounded-xl transition-all font-medium flex items-center gap-2 group">
                    <svg class="w-5 h-5 group-hover:scale-110 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path>
                    </svg>
                    <span data-translate>พิมพ์</span>
                </button>
                <button onclick="exportPDF()"
                        class="px-6 py-3 bg-green-500/80 hover:bg-green-600 backdrop-blur-sm text-white rounded-xl transition-all font-medium flex items-center gap-2 group">
                    <svg class="w-5 h-5 group-hover:scale-110 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M9 19l3 3m0 0l3-3m-3 3V10"></path>
                    </svg>
                    <span data-translate>ส่งออก PDF</span>
                </button>
            </div>
        </div>
    </div>

    <div class="max-w-7xl mx-auto space-y-6">
        <!-- Date Filter -->
        <div class="bg-white/80 dark:bg-gray-800/80 backdrop-blur-sm rounded-2xl shadow-xl p-6 border-2 border-blue-100 dark:border-blue-900">
            <form method="GET" class="flex flex-col md:flex-row gap-4">
                <div class="flex-1">
                    <label class="block text-sm font-bold text-gray-900 dark:text-white mb-2" data-translate>ณ วันที่</label>
                    <input type="date" name="as_of_date" value="{{ request('as_of_date', now()->format('Y-m-d')) }}"
                           class="w-full px-4 py-3 bg-white dark:bg-gray-700 border-2 border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:text-white transition-all">
                </div>
                <div class="md:self-end">
                    <button type="submit"
                            class="w-full md:w-auto px-8 py-3 bg-gradient-to-r from-blue-500 to-indigo-600 hover:from-blue-600 hover:to-indigo-700 text-white rounded-xl shadow-lg hover:shadow-xl transition-all font-bold flex items-center justify-center gap-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                        </svg>
                        <span data-translate>ค้นหา</span>
                    </button>
                </div>
            </form>
        </div>

        <!-- Report -->
        <div class="bg-white/80 dark:bg-gray-800/80 backdrop-blur-sm rounded-2xl shadow-2xl overflow-hidden border-2 border-blue-100 dark:border-blue-900">
            <div class="bg-gradient-to-r from-blue-500 to-indigo-600 px-8 py-6">
                <h2 class="text-3xl font-bold text-white text-center mb-1" data-translate>งบดุล</h2>
                <p class="text-blue-100 text-center" data-translate>
                    ณ วันที่ {{ request('as_of_date', now()->format('d/m/Y')) }}
                </p>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 p-8">
                <!-- Assets Column -->
                <div>
                    <h3 class="text-2xl font-bold text-gray-900 dark:text-white mb-6 pb-3 border-b-4 border-blue-500 flex items-center gap-2">
                        <svg class="w-7 h-7 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path>
                        </svg>
                        <span data-translate>สินทรัพย์</span>
                    </h3>

                    <!-- Current Assets -->
                    <div class="mb-6 bg-gradient-to-br from-blue-50 to-indigo-50 dark:from-blue-900/20 dark:to-indigo-900/20 rounded-xl p-5 border-2 border-blue-200 dark:border-blue-800">
                        <h4 class="font-bold text-lg text-blue-900 dark:text-blue-100 mb-3" data-translate>สินทรัพย์หมุนเวียน</h4>
                        <div class="space-y-2 ml-4">
                            <div class="flex justify-between text-sm text-gray-700 dark:text-gray-300">
                                <span data-translate>เงินสดและเงินฝากธนาคาร</span>
                                <span class="font-semibold">฿{{ number_format($report['cash'] ?? 0, 2) }}</span>
                            </div>
                            <div class="flex justify-between text-sm text-gray-700 dark:text-gray-300">
                                <span data-translate>ลูกหนี้การค้า</span>
                                <span class="font-semibold">฿{{ number_format($report['accounts_receivable'] ?? 0, 2) }}</span>
                            </div>
                            <div class="flex justify-between text-sm text-gray-700 dark:text-gray-300">
                                <span data-translate>สินค้าคงเหลือ</span>
                                <span class="font-semibold">฿{{ number_format($report['inventory'] ?? 0, 2) }}</span>
                            </div>
                            <div class="flex justify-between text-base font-bold text-blue-900 dark:text-blue-100 border-t-2 border-blue-300 dark:border-blue-700 pt-2 mt-2">
                                <span data-translate>รวมสินทรัพย์หมุนเวียน</span>
                                <span>฿{{ number_format($report['total_current_assets'] ?? 0, 2) }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Fixed Assets -->
                    <div class="mb-6 bg-gradient-to-br from-indigo-50 to-purple-50 dark:from-indigo-900/20 dark:to-purple-900/20 rounded-xl p-5 border-2 border-indigo-200 dark:border-indigo-800">
                        <h4 class="font-bold text-lg text-indigo-900 dark:text-indigo-100 mb-3" data-translate>สินทรัพย์ไม่หมุนเวียน</h4>
                        <div class="space-y-2 ml-4">
                            <div class="flex justify-between text-sm text-gray-700 dark:text-gray-300">
                                <span data-translate>ที่ดิน อาคาร และอุปกรณ์</span>
                                <span class="font-semibold">฿{{ number_format($report['fixed_assets'] ?? 0, 2) }}</span>
                            </div>
                            <div class="flex justify-between text-sm text-gray-700 dark:text-gray-300">
                                <span data-translate>หัก: ค่าเสื่อมราคาสะสม</span>
                                <span class="font-semibold text-red-600">(฿{{ number_format($report['accumulated_depreciation'] ?? 0, 2) }})</span>
                            </div>
                            <div class="flex justify-between text-base font-bold text-indigo-900 dark:text-indigo-100 border-t-2 border-indigo-300 dark:border-indigo-700 pt-2 mt-2">
                                <span data-translate>รวมสินทรัพย์ไม่หมุนเวียน</span>
                                <span>฿{{ number_format($report['total_fixed_assets'] ?? 0, 2) }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Total Assets -->
                    <div class="bg-gradient-to-r from-blue-500 to-indigo-600 rounded-xl p-5 shadow-xl">
                        <div class="flex justify-between text-2xl font-bold text-white">
                            <span data-translate>รวมสินทรัพย์</span>
                            <span>฿{{ number_format($report['total_assets'] ?? 0, 2) }}</span>
                        </div>
                    </div>
                </div>

                <!-- Liabilities & Equity Column -->
                <div>
                    <h3 class="text-2xl font-bold text-gray-900 dark:text-white mb-6 pb-3 border-b-4 border-red-500 flex items-center gap-2">
                        <svg class="w-7 h-7 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                        <span data-translate>หนี้สินและส่วนของเจ้าของ</span>
                    </h3>

                    <!-- Current Liabilities -->
                    <div class="mb-6 bg-gradient-to-br from-red-50 to-orange-50 dark:from-red-900/20 dark:to-orange-900/20 rounded-xl p-5 border-2 border-red-200 dark:border-red-800">
                        <h4 class="font-bold text-lg text-red-900 dark:text-red-100 mb-3" data-translate>หนี้สินหมุนเวียน</h4>
                        <div class="space-y-2 ml-4">
                            <div class="flex justify-between text-sm text-gray-700 dark:text-gray-300">
                                <span data-translate>เจ้าหนี้การค้า</span>
                                <span class="font-semibold">฿{{ number_format($report['accounts_payable'] ?? 0, 2) }}</span>
                            </div>
                            <div class="flex justify-between text-sm text-gray-700 dark:text-gray-300">
                                <span data-translate>ภาษีค้างจ่าย</span>
                                <span class="font-semibold">฿{{ number_format($report['tax_payable'] ?? 0, 2) }}</span>
                            </div>
                            <div class="flex justify-between text-sm text-gray-700 dark:text-gray-300">
                                <span data-translate>หนี้สินหมุนเวียนอื่น</span>
                                <span class="font-semibold">฿{{ number_format($report['other_current_liabilities'] ?? 0, 2) }}</span>
                            </div>
                            <div class="flex justify-between text-base font-bold text-red-900 dark:text-red-100 border-t-2 border-red-300 dark:border-red-700 pt-2 mt-2">
                                <span data-translate>รวมหนี้สินหมุนเวียน</span>
                                <span>฿{{ number_format($report['total_current_liabilities'] ?? 0, 2) }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Long-term Liabilities -->
                    <div class="mb-6 bg-gradient-to-br from-orange-50 to-yellow-50 dark:from-orange-900/20 dark:to-yellow-900/20 rounded-xl p-5 border-2 border-orange-200 dark:border-orange-800">
                        <h4 class="font-bold text-lg text-orange-900 dark:text-orange-100 mb-3" data-translate>หนี้สินไม่หมุนเวียน</h4>
                        <div class="space-y-2 ml-4">
                            <div class="flex justify-between text-sm text-gray-700 dark:text-gray-300">
                                <span data-translate>เงินกู้ยืมระยะยาว</span>
                                <span class="font-semibold">฿{{ number_format($report['long_term_debt'] ?? 0, 2) }}</span>
                            </div>
                            <div class="flex justify-between text-base font-bold text-orange-900 dark:text-orange-100 border-t-2 border-orange-300 dark:border-orange-700 pt-2 mt-2">
                                <span data-translate>รวมหนี้สินไม่หมุนเวียน</span>
                                <span>฿{{ number_format($report['total_long_term_liabilities'] ?? 0, 2) }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Total Liabilities -->
                    <div class="mb-6 bg-gradient-to-r from-red-100 to-orange-100 dark:from-red-900/40 dark:to-orange-900/40 rounded-xl p-4 border-2 border-red-300 dark:border-red-700">
                        <div class="flex justify-between text-lg font-bold text-red-900 dark:text-red-100">
                            <span data-translate>รวมหนี้สิน</span>
                            <span>฿{{ number_format($report['total_liabilities'] ?? 0, 2) }}</span>
                        </div>
                    </div>

                    <!-- Equity -->
                    <div class="mb-6 bg-gradient-to-br from-green-50 to-emerald-50 dark:from-green-900/20 dark:to-emerald-900/20 rounded-xl p-5 border-2 border-green-200 dark:border-green-800">
                        <h4 class="font-bold text-lg text-green-900 dark:text-green-100 mb-3" data-translate>ส่วนของเจ้าของ</h4>
                        <div class="space-y-2 ml-4">
                            <div class="flex justify-between text-sm text-gray-700 dark:text-gray-300">
                                <span data-translate>ทุนจดทะเบียน</span>
                                <span class="font-semibold">฿{{ number_format($report['capital'] ?? 0, 2) }}</span>
                            </div>
                            <div class="flex justify-between text-sm text-gray-700 dark:text-gray-300">
                                <span data-translate>กำไรสะสม</span>
                                <span class="font-semibold">฿{{ number_format($report['retained_earnings'] ?? 0, 2) }}</span>
                            </div>
                            <div class="flex justify-between text-base font-bold text-green-900 dark:text-green-100 border-t-2 border-green-300 dark:border-green-700 pt-2 mt-2">
                                <span data-translate>รวมส่วนของเจ้าของ</span>
                                <span>฿{{ number_format($report['total_equity'] ?? 0, 2) }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Total Liabilities & Equity -->
                    <div class="bg-gradient-to-r from-blue-500 to-indigo-600 rounded-xl p-5 shadow-xl">
                        <div class="flex justify-between text-2xl font-bold text-white">
                            <span data-translate>รวมหนี้สินและส่วนของเจ้าของ</span>
                            <span>฿{{ number_format($report['total_liabilities_equity'] ?? 0, 2) }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Footer -->
            <div class="bg-gradient-to-r from-gray-50 to-gray-100 dark:from-gray-900/50 dark:to-gray-800/50 px-6 py-4 border-t-2 border-blue-200 dark:border-blue-900">
                <p class="text-sm text-gray-600 dark:text-gray-400 text-center flex items-center justify-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <span data-translate>สร้างรายงานเมื่อ:</span>
                    {{ now()->format('d/m/Y H:i:s') }}
                </p>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
function exportPDF() {
    alert('ฟังก์ชันส่งออก PDF จะถูกพัฒนาในอนาคต');
}
</script>
@endpush
@endsection
