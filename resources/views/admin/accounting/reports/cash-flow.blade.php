@extends('layouts.admin')

@section('title', 'รายงานกระแสเงินสด')

@section('content')
<div class="min-h-screen bg-gradient-to-br from-emerald-50 via-teal-50 to-cyan-50 dark:from-gray-900 dark:via-gray-800 dark:to-gray-900 p-6">
    <!-- Language Switcher -->
    <div class="flex justify-end mb-6" x-data="{ open: false }">
        <div class="relative">
            <button @click="open = !open" class="flex items-center gap-2 px-4 py-2 bg-white/80 dark:bg-gray-800/80 backdrop-blur-sm rounded-xl border-2 border-emerald-200 dark:border-emerald-700 hover:border-emerald-400 dark:hover:border-emerald-500 transition-all shadow-lg">
                <svg class="w-5 h-5 text-emerald-600 dark:text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5h12M9 3v2m1.048 9.5A18.022 18.022 0 016.412 9m6.088 9h7M11 21l5-10 5 10M12.751 5C11.783 10.77 8.07 15.61 3 18.129"></path></svg>
                <span class="font-medium text-gray-700 dark:text-gray-300" data-translate>ภาษา</span>
                <svg class="w-4 h-4 text-gray-500 dark:text-gray-400" :class="{ 'rotate-180': open }" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
            </button>
            <div x-show="open" @click.away="open = false" x-transition class="absolute right-0 mt-2 w-48 bg-white dark:bg-gray-800 rounded-xl shadow-2xl border-2 border-emerald-100 dark:border-emerald-900 overflow-hidden z-50">
                <button onclick="switchLanguage('th')" class="w-full px-4 py-3 text-left hover:bg-emerald-50 dark:hover:bg-emerald-900/20 flex items-center gap-3 transition-colors border-b border-gray-100 dark:border-gray-700"><span class="text-2xl">🇹🇭</span><span class="font-medium text-gray-700 dark:text-gray-300">ไทย</span></button>
                <button onclick="switchLanguage('en')" class="w-full px-4 py-3 text-left hover:bg-emerald-50 dark:hover:bg-emerald-900/20 flex items-center gap-3 transition-colors border-b border-gray-100 dark:border-gray-700"><span class="text-2xl">🇬🇧</span><span class="font-medium text-gray-700 dark:text-gray-300">English</span></button>
                <button onclick="switchLanguage('zh')" class="w-full px-4 py-3 text-left hover:bg-emerald-50 dark:hover:bg-emerald-900/20 flex items-center gap-3 transition-colors border-b border-gray-100 dark:border-gray-700"><span class="text-2xl">🇨🇳</span><span class="font-medium text-gray-700 dark:text-gray-300">中文</span></button>
                <button onclick="switchLanguage('ja')" class="w-full px-4 py-3 text-left hover:bg-emerald-50 dark:hover:bg-emerald-900/20 flex items-center gap-3 transition-colors"><span class="text-2xl">🇯🇵</span><span class="font-medium text-gray-700 dark:text-gray-300">日本語</span></button>
            </div>
        </div>
    </div>

    <!-- Header -->
    <div class="mb-8 relative overflow-hidden bg-gradient-to-r from-purple-500 via-pink-600 to-rose-600 rounded-3xl shadow-2xl p-8">
        <div class="absolute top-0 right-0 -mt-4 -mr-4 w-32 h-32 bg-white/10 rounded-full blur-3xl"></div>
        <div class="absolute bottom-0 left-0 -mb-4 -ml-4 w-40 h-40 bg-pink-400/10 rounded-full blur-3xl"></div>
        <div class="relative flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
            <div class="flex items-center gap-4">
                <a href="{{ route('admin.accounting.reports.index') }}" class="p-3 bg-white/20 hover:bg-white/30 backdrop-blur-sm rounded-xl transition-all group">
                    <svg class="w-6 h-6 text-white group-hover:-translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                </a>
                <div>
                    <h1 class="text-4xl font-bold text-white mb-2 flex items-center gap-3">
                        <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4"></path></svg>
                        <span data-translate>รายงานกระแสเงินสด</span>
                    </h1>
                    <p class="text-purple-50 text-lg">Cash Flow Statement</p>
                </div>
            </div>
            <div class="flex flex-wrap gap-3">
                <button onclick="window.print()" class="px-6 py-3 bg-white/20 hover:bg-white/30 backdrop-blur-sm text-white rounded-xl transition-all font-medium flex items-center gap-2 group">
                    <svg class="w-5 h-5 group-hover:scale-110 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>
                    <span data-translate>พิมพ์</span>
                </button>
                <button onclick="exportPDF()" class="px-6 py-3 bg-green-500/80 hover:bg-green-600 backdrop-blur-sm text-white rounded-xl transition-all font-medium flex items-center gap-2 group">
                    <svg class="w-5 h-5 group-hover:scale-110 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M9 19l3 3m0 0l3-3m-3 3V10"></path></svg>
                    <span data-translate>ส่งออก PDF</span>
                </button>
            </div>
        </div>
    </div>

    <div class="max-w-6xl mx-auto space-y-6">
        <!-- Filters -->
        <div class="bg-white/80 dark:bg-gray-800/80 backdrop-blur-sm rounded-2xl shadow-xl p-6 border-2 border-purple-100 dark:border-purple-900">
            <form method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div><label class="block text-sm font-bold text-gray-900 dark:text-white mb-2" data-translate>ช่วงเวลา</label>
                    <select name="period" class="w-full px-4 py-3 bg-white dark:bg-gray-700 border-2 border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-purple-500 focus:border-purple-500 dark:text-white transition-all">
                        <option value="this_month" {{ request('period') === 'this_month' ? 'selected' : '' }}>เดือนนี้</option>
                        <option value="last_month" {{ request('period') === 'last_month' ? 'selected' : '' }}>เดือนที่แล้ว</option>
                        <option value="this_quarter" {{ request('period') === 'this_quarter' ? 'selected' : '' }}>ไตรมาสนี้</option>
                        <option value="this_year" {{ request('period') === 'this_year' ? 'selected' : '' }}>ปีนี้</option>
                    </select>
                </div>
                <div><label class="block text-sm font-bold text-gray-900 dark:text-white mb-2" data-translate>จากวันที่</label>
                    <input type="date" name="from_date" value="{{ request('from_date', now()->startOfMonth()->format('Y-m-d')) }}" class="w-full px-4 py-3 bg-white dark:bg-gray-700 border-2 border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-purple-500 focus:border-purple-500 dark:text-white transition-all">
                </div>
                <div><label class="block text-sm font-bold text-gray-900 dark:text-white mb-2" data-translate>ถึงวันที่</label>
                    <input type="date" name="to_date" value="{{ request('to_date', now()->format('Y-m-d')) }}" class="w-full px-4 py-3 bg-white dark:bg-gray-700 border-2 border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-purple-500 focus:border-purple-500 dark:text-white transition-all">
                </div>
                <div class="md:self-end">
                    <button type="submit" class="w-full px-8 py-3 bg-gradient-to-r from-purple-500 to-pink-600 hover:from-purple-600 hover:to-pink-700 text-white rounded-xl shadow-lg hover:shadow-xl transition-all font-bold flex items-center justify-center gap-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                        <span data-translate>ค้นหา</span>
                    </button>
                </div>
            </form>
        </div>

        <!-- Report -->
        <div class="bg-white/80 dark:bg-gray-800/80 backdrop-blur-sm rounded-2xl shadow-2xl overflow-hidden border-2 border-purple-100 dark:border-purple-900">
            <div class="bg-gradient-to-r from-purple-500 to-pink-600 px-8 py-6">
                <h2 class="text-3xl font-bold text-white text-center mb-1" data-translate>รายงานกระแสเงินสด</h2>
                <p class="text-purple-100 text-center" data-translate>ระหว่างวันที่ {{ request('from_date', now()->startOfMonth()->format('d/m/Y')) }} ถึง {{ request('to_date', now()->format('d/m/Y')) }}</p>
            </div>

            <div class="p-8 space-y-8">
                <!-- Operating Activities -->
                <div class="bg-gradient-to-br from-green-50 to-emerald-50 dark:from-green-900/20 dark:to-emerald-900/20 rounded-xl p-6 border-2 border-green-200 dark:border-green-800">
                    <h3 class="text-xl font-bold text-green-900 dark:text-green-100 mb-4 pb-3 border-b-2 border-green-300 dark:border-green-700" data-translate>กระแสเงินสดจากกิจกรรมดำเนินงาน</h3>
                    <div class="space-y-3 ml-4">
                        <div class="flex justify-between text-gray-700 dark:text-gray-300"><span data-translate>เงินสดรับจากลูกค้า</span><span class="font-semibold text-green-600">฿{{ number_format($report['cash_from_customers'] ?? 0, 2) }}</span></div>
                        <div class="flex justify-between text-gray-700 dark:text-gray-300"><span data-translate>เงินสดจ่ายให้ผู้ขาย</span><span class="font-semibold text-red-600">(฿{{ number_format($report['cash_to_suppliers'] ?? 0, 2) }})</span></div>
                        <div class="flex justify-between text-gray-700 dark:text-gray-300"><span data-translate>เงินสดจ่ายค่าใช้จ่ายดำเนินงาน</span><span class="font-semibold text-red-600">(฿{{ number_format($report['operating_expenses_paid'] ?? 0, 2) }})</span></div>
                        <div class="flex justify-between text-gray-700 dark:text-gray-300"><span data-translate>เงินสดจ่ายภาษีเงินได้</span><span class="font-semibold text-red-600">(฿{{ number_format($report['tax_paid'] ?? 0, 2) }})</span></div>
                        <div class="flex justify-between text-lg font-bold text-green-900 dark:text-green-100 border-t-2 border-green-300 dark:border-green-700 pt-3 mt-3">
                            <span data-translate>เงินสดสุทธิจากกิจกรรมดำเนินงาน</span>
                            <span class="{{ ($report['net_cash_operating'] ?? 0) >= 0 ? 'text-green-600' : 'text-red-600' }}">฿{{ number_format($report['net_cash_operating'] ?? 0, 2) }}</span>
                        </div>
                    </div>
                </div>

                <!-- Investing Activities -->
                <div class="bg-gradient-to-br from-blue-50 to-indigo-50 dark:from-blue-900/20 dark:to-indigo-900/20 rounded-xl p-6 border-2 border-blue-200 dark:border-blue-800">
                    <h3 class="text-xl font-bold text-blue-900 dark:text-blue-100 mb-4 pb-3 border-b-2 border-blue-300 dark:border-blue-700" data-translate>กระแสเงินสดจากกิจกรรมลงทุน</h3>
                    <div class="space-y-3 ml-4">
                        <div class="flex justify-between text-gray-700 dark:text-gray-300"><span data-translate>เงินสดจ่ายซื้อสินทรัพย์</span><span class="font-semibold text-red-600">(฿{{ number_format($report['purchase_of_assets'] ?? 0, 2) }})</span></div>
                        <div class="flex justify-between text-gray-700 dark:text-gray-300"><span data-translate>เงินสดรับจากขายสินทรัพย์</span><span class="font-semibold text-green-600">฿{{ number_format($report['sale_of_assets'] ?? 0, 2) }}</span></div>
                        <div class="flex justify-between text-lg font-bold text-blue-900 dark:text-blue-100 border-t-2 border-blue-300 dark:border-blue-700 pt-3 mt-3">
                            <span data-translate>เงินสดสุทธิจากกิจกรรมลงทุน</span>
                            <span class="{{ ($report['net_cash_investing'] ?? 0) >= 0 ? 'text-green-600' : 'text-red-600' }}">฿{{ number_format($report['net_cash_investing'] ?? 0, 2) }}</span>
                        </div>
                    </div>
                </div>

                <!-- Financing Activities -->
                <div class="bg-gradient-to-br from-orange-50 to-red-50 dark:from-orange-900/20 dark:to-red-900/20 rounded-xl p-6 border-2 border-orange-200 dark:border-orange-800">
                    <h3 class="text-xl font-bold text-orange-900 dark:text-orange-100 mb-4 pb-3 border-b-2 border-orange-300 dark:border-orange-700" data-translate>กระแสเงินสดจากกิจกรรมจัดหาเงิน</h3>
                    <div class="space-y-3 ml-4">
                        <div class="flex justify-between text-gray-700 dark:text-gray-300"><span data-translate>เงินสดรับจากเงินกู้</span><span class="font-semibold text-green-600">฿{{ number_format($report['loan_proceeds'] ?? 0, 2) }}</span></div>
                        <div class="flex justify-between text-gray-700 dark:text-gray-300"><span data-translate>เงินสดจ่ายชำระเงินกู้</span><span class="font-semibold text-red-600">(฿{{ number_format($report['loan_repayment'] ?? 0, 2) }})</span></div>
                        <div class="flex justify-between text-gray-700 dark:text-gray-300"><span data-translate>เงินสดจ่ายเงินปันผล</span><span class="font-semibold text-red-600">(฿{{ number_format($report['dividends_paid'] ?? 0, 2) }})</span></div>
                        <div class="flex justify-between text-lg font-bold text-orange-900 dark:text-orange-100 border-t-2 border-orange-300 dark:border-orange-700 pt-3 mt-3">
                            <span data-translate>เงินสดสุทธิจากกิจกรรมจัดหาเงิน</span>
                            <span class="{{ ($report['net_cash_financing'] ?? 0) >= 0 ? 'text-green-600' : 'text-red-600' }}">฿{{ number_format($report['net_cash_financing'] ?? 0, 2) }}</span>
                        </div>
                    </div>
                </div>

                <!-- Summary -->
                <div class="bg-gradient-to-br from-purple-50 to-pink-50 dark:from-purple-900/20 dark:to-pink-900/20 rounded-xl p-6 border-2 border-purple-200 dark:border-purple-800 space-y-3">
                    <div class="flex justify-between text-lg font-bold text-purple-900 dark:text-purple-100">
                        <span data-translate>เงินสดและรายการเทียบเท่าเงินสดเพิ่มขึ้น(ลดลง)สุทธิ</span>
                        <span class="{{ ($report['net_change_in_cash'] ?? 0) >= 0 ? 'text-green-600' : 'text-red-600' }}">฿{{ number_format($report['net_change_in_cash'] ?? 0, 2) }}</span>
                    </div>
                    <div class="flex justify-between text-gray-700 dark:text-gray-300"><span data-translate>เงินสดต้นงวด</span><span class="font-semibold">฿{{ number_format($report['beginning_cash'] ?? 0, 2) }}</span></div>
                    <div class="bg-gradient-to-r from-purple-500 to-pink-600 rounded-xl p-4 shadow-xl">
                        <div class="flex justify-between text-2xl font-bold text-white">
                            <span data-translate>เงินสดปลายงวด</span>
                            <span>฿{{ number_format($report['ending_cash'] ?? 0, 2) }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Footer -->
            <div class="bg-gradient-to-r from-gray-50 to-gray-100 dark:from-gray-900/50 dark:to-gray-800/50 px-6 py-4 border-t-2 border-purple-200 dark:border-purple-900">
                <p class="text-sm text-gray-600 dark:text-gray-400 text-center flex items-center justify-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    <span data-translate>สร้างรายงานเมื่อ:</span> {{ now()->format('d/m/Y H:i:s') }}
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
