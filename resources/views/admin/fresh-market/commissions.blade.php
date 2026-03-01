@extends('layouts.admin-v3')

@section('title', 'รายงานค่าธรรมเนียม - ตลาดสดไทยพร้อม')

@section('content')
<div class="space-y-6">
    {{-- ส่วนหัว --}}
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <div>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">รายงานค่าธรรมเนียม - ตลาดสดไทยพร้อม</h1>
            <p class="text-gray-600 dark:text-gray-400 mt-1">สรุปค่าธรรมเนียมแพลตฟอร์ม แคชแบ็ก และคอมมิชชั่น MLM</p>
        </div>
        <a href="{{ route('admin.fresh-market.dashboard') }}"
           class="inline-flex items-center px-4 py-2 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-xl hover:bg-gray-300 dark:hover:bg-gray-600 transition">
            <i class="fas fa-arrow-left mr-2"></i> กลับแดชบอร์ด
        </a>
    </div>

    {{-- การ์ดสถิติ --}}
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-6">
        {{-- ค่าธรรมเนียมแพลตฟอร์ม --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6 border-l-4 border-green-500">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-500 dark:text-gray-400">ค่าธรรมเนียมแพลตฟอร์มรวม</p>
                    <p class="text-3xl font-bold text-gray-900 dark:text-white mt-2">
                        {{ number_format($stats['total_platform_fees'] ?? 0, 2) }}
                    </p>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">บาท</p>
                </div>
                <div class="w-14 h-14 bg-green-100 dark:bg-green-900/30 rounded-xl flex items-center justify-center">
                    <i class="fas fa-hand-holding-usd text-2xl text-green-600 dark:text-green-400"></i>
                </div>
            </div>
        </div>

        {{-- แคชแบ็กรวม --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6 border-l-4 border-blue-500">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-500 dark:text-gray-400">แคชแบ็กที่จ่ายรวม</p>
                    <p class="text-3xl font-bold text-gray-900 dark:text-white mt-2">
                        {{ number_format($stats['total_cashback'] ?? 0, 2) }}
                    </p>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">บาท</p>
                </div>
                <div class="w-14 h-14 bg-blue-100 dark:bg-blue-900/30 rounded-xl flex items-center justify-center">
                    <i class="fas fa-undo text-2xl text-blue-600 dark:text-blue-400"></i>
                </div>
            </div>
        </div>

        {{-- MLM ที่ประมวลผลแล้ว --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6 border-l-4 border-purple-500">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-500 dark:text-gray-400">คอมมิชชั่น MLM ที่ประมวลผล</p>
                    <p class="text-3xl font-bold text-gray-900 dark:text-white mt-2">
                        {{ number_format($stats['total_mlm_processed'] ?? 0, 2) }}
                    </p>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">บาท</p>
                </div>
                <div class="w-14 h-14 bg-purple-100 dark:bg-purple-900/30 rounded-xl flex items-center justify-center">
                    <i class="fas fa-sitemap text-2xl text-purple-600 dark:text-purple-400"></i>
                </div>
            </div>
        </div>
    </div>

    {{-- ตารางคำสั่งซื้อที่สำเร็จล่าสุด --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white">คำสั่งซื้อที่สำเร็จล่าสุด (พร้อมข้อมูลค่าธรรมเนียม)</h2>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">แสดงคำสั่งซื้อที่เสร็จสิ้นและประมวลผลค่าธรรมเนียมแล้ว</p>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-700">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">เลขที่</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">ร้านค้า</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">ยอดรวม</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">ค่าธรรมเนียม</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">แคชแบ็ก</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">MLM</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">ผู้ขายได้รับ</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">วันที่</th>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse($recentOrders as $order)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700 transition">
                            <td class="px-4 py-4 whitespace-nowrap">
                                <span class="text-sm font-semibold text-green-600 dark:text-green-400">{{ $order->order_number }}</span>
                            </td>
                            <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-600 dark:text-gray-300">
                                {{ $order->seller->shop_name ?? '-' }}
                            </td>
                            <td class="px-4 py-4 whitespace-nowrap text-right text-sm font-semibold text-gray-900 dark:text-white">
                                {{ number_format($order->total_amount, 2) }}
                            </td>
                            <td class="px-4 py-4 whitespace-nowrap text-right text-sm text-green-600 dark:text-green-400 font-medium">
                                {{ number_format($order->platform_fee ?? 0, 2) }}
                            </td>
                            <td class="px-4 py-4 whitespace-nowrap text-right text-sm text-blue-600 dark:text-blue-400 font-medium">
                                {{ number_format($order->cashback_amount ?? 0, 2) }}
                            </td>
                            <td class="px-4 py-4 whitespace-nowrap text-right text-sm text-purple-600 dark:text-purple-400 font-medium">
                                {{ number_format($order->mlm_commission ?? 0, 2) }}
                            </td>
                            <td class="px-4 py-4 whitespace-nowrap text-right text-sm font-semibold text-gray-900 dark:text-white">
                                {{ number_format($order->seller_amount ?? 0, 2) }}
                            </td>
                            <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-600 dark:text-gray-400">
                                {{ $order->completed_at ? $order->completed_at->format('d/m/Y H:i') : $order->updated_at->format('d/m/Y H:i') }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-4 py-12 text-center text-gray-500 dark:text-gray-400">
                                <i class="fas fa-chart-line text-4xl mb-3 block"></i>
                                ยังไม่มีคำสั่งซื้อที่สำเร็จ
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- คำอธิบายค่าธรรมเนียม --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">คำอธิบายการคำนวณค่าธรรมเนียม</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="p-4 bg-green-50 dark:bg-green-900/20 rounded-xl">
                <div class="flex items-center gap-2 mb-2">
                    <div class="w-3 h-3 bg-green-500 rounded-full"></div>
                    <span class="text-sm font-medium text-green-800 dark:text-green-400">ค่าธรรมเนียมแพลตฟอร์ม</span>
                </div>
                <p class="text-xs text-green-700 dark:text-green-300">หักจากยอดขายตามอัตราที่กำหนด เป็นรายได้ของแพลตฟอร์ม</p>
            </div>
            <div class="p-4 bg-blue-50 dark:bg-blue-900/20 rounded-xl">
                <div class="flex items-center gap-2 mb-2">
                    <div class="w-3 h-3 bg-blue-500 rounded-full"></div>
                    <span class="text-sm font-medium text-blue-800 dark:text-blue-400">แคชแบ็ก</span>
                </div>
                <p class="text-xs text-blue-700 dark:text-blue-300">จำนวนเงินที่คืนให้ผู้ซื้อเข้ากระเป๋าเงินในระบบ</p>
            </div>
            <div class="p-4 bg-purple-50 dark:bg-purple-900/20 rounded-xl">
                <div class="flex items-center gap-2 mb-2">
                    <div class="w-3 h-3 bg-purple-500 rounded-full"></div>
                    <span class="text-sm font-medium text-purple-800 dark:text-purple-400">คอมมิชชั่น MLM</span>
                </div>
                <p class="text-xs text-purple-700 dark:text-purple-300">คอมมิชชั่นที่จ่ายให้สายงาน MLM ตามโครงสร้าง</p>
            </div>
            <div class="p-4 bg-gray-50 dark:bg-gray-700/50 rounded-xl">
                <div class="flex items-center gap-2 mb-2">
                    <div class="w-3 h-3 bg-gray-500 rounded-full"></div>
                    <span class="text-sm font-medium text-gray-800 dark:text-gray-300">ผู้ขายได้รับ</span>
                </div>
                <p class="text-xs text-gray-700 dark:text-gray-400">ยอดขายหลังหักค่าธรรมเนียมทั้งหมด ที่โอนเข้ากระเป๋าเงินผู้ขาย</p>
            </div>
        </div>
    </div>
</div>
@endsection
