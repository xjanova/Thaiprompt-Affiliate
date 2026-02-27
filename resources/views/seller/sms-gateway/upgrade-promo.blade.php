@extends('layouts.seller')

@section('title', 'อัพเกรดเป็น Enterprise - รับชำระเงินตรง')

@section('content')
<div class="container mx-auto px-4 py-8 max-w-4xl">

    {{-- Header --}}
    <div class="text-center mb-10">
        <h1 class="text-3xl font-bold text-gray-900 dark:text-white">
            รับชำระเงินเข้าบัญชีตนเอง
        </h1>
        <p class="text-gray-600 dark:text-gray-400 mt-2 text-lg">
            เพิ่มยอดขายด้วยระบบรับชำระเงินตรง พร้อมยืนยันอัตโนมัติ
        </p>
    </div>

    {{-- Hero Banner --}}
    <div class="bg-gradient-to-br from-yellow-400 via-amber-500 to-orange-500 rounded-2xl p-8 mb-10 text-white shadow-xl relative overflow-hidden">
        <div class="absolute top-0 right-0 opacity-10 text-[120px] leading-none">&#x1F451;</div>
        <div class="relative">
            <span class="inline-block px-3 py-1 bg-white/20 rounded-full text-sm font-medium mb-4">Enterprise Package Only</span>
            <h2 class="text-2xl font-bold mb-3">ปลดล็อคพลังเต็มของร้านคุณ</h2>
            <p class="text-yellow-100 mb-6 max-w-2xl">
                ด้วยแพ็คเกจ Enterprise ร้านค้าของคุณสามารถรับชำระเงินเข้าบัญชีธนาคารตนเองได้โดยตรง
                พร้อมแอป SMS Checker ที่ยืนยันการโอนเงินอัตโนมัติ ไม่ต้องรอตรวจสอบ
            </p>
            <a href="{{ route('seller.packages') }}"
               class="inline-flex items-center gap-2 px-8 py-3 bg-white text-amber-600 font-bold rounded-xl hover:bg-yellow-50 transition shadow-lg">
                อัพเกรดเป็น Enterprise
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/>
                </svg>
            </a>
        </div>
    </div>

    {{-- ประโยชน์ --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-10">
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-6 text-center">
            <div class="text-4xl mb-3">&#x1F4B0;</div>
            <h3 class="font-semibold text-gray-900 dark:text-white mb-2">รับเงินทันที</h3>
            <p class="text-sm text-gray-600 dark:text-gray-400">ลูกค้าโอนเงินเข้าบัญชีคุณโดยตรง ไม่ต้องรอ platform หักค่าธรรมเนียม</p>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-6 text-center">
            <div class="text-4xl mb-3">&#x26A1;</div>
            <h3 class="font-semibold text-gray-900 dark:text-white mb-2">ยืนยันอัตโนมัติ</h3>
            <p class="text-sm text-gray-600 dark:text-gray-400">แอป SMS Checker ส่งข้อมูลยืนยันอัตโนมัติ ไม่ต้องอัพโหลดสลิปด้วยตนเอง</p>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-6 text-center">
            <div class="text-4xl mb-3">&#x1F4C9;</div>
            <h3 class="font-semibold text-gray-900 dark:text-white mb-2">ค่าคอมมิชชั่นต่ำ</h3>
            <p class="text-sm text-gray-600 dark:text-gray-400">
                Enterprise เสียค่าคอมมิชชั่นเพียง
                @if($enterprise_package)
                    {{ number_format($enterprise_package->commission_rate, 0) }}%
                @else
                    5%
                @endif
                ต่ำที่สุดในทุกแพ็คเกจ
            </p>
        </div>
    </div>

    {{-- เปรียบเทียบ --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow overflow-hidden mb-10">
        <div class="px-6 py-4 bg-gray-50 dark:bg-gray-700 border-b border-gray-200 dark:border-gray-600">
            <h3 class="font-semibold text-gray-900 dark:text-white">เปรียบเทียบแพ็คเกจ</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead>
                    <tr class="border-b border-gray-200 dark:border-gray-700">
                        <th class="px-6 py-3 text-left text-sm font-medium text-gray-500 dark:text-gray-400">ฟีเจอร์</th>
                        <th class="px-6 py-3 text-center text-sm font-medium text-gray-500 dark:text-gray-400">
                            {{ $current_package->display_name ?? 'แพ็คเกจปัจจุบัน' }}
                        </th>
                        <th class="px-6 py-3 text-center text-sm font-medium text-amber-600 dark:text-amber-400">
                            Enterprise
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                    <tr>
                        <td class="px-6 py-3 text-sm text-gray-700 dark:text-gray-300">รับชำระเงินตรง</td>
                        <td class="px-6 py-3 text-center text-red-500">&#x2716;</td>
                        <td class="px-6 py-3 text-center text-green-500 font-bold">&#x2714;</td>
                    </tr>
                    <tr>
                        <td class="px-6 py-3 text-sm text-gray-700 dark:text-gray-300">จับคู่แอป SMS Checker</td>
                        <td class="px-6 py-3 text-center text-red-500">&#x2716;</td>
                        <td class="px-6 py-3 text-center text-green-500 font-bold">&#x2714;</td>
                    </tr>
                    <tr>
                        <td class="px-6 py-3 text-sm text-gray-700 dark:text-gray-300">ยืนยันอัตโนมัติ</td>
                        <td class="px-6 py-3 text-center text-red-500">&#x2716;</td>
                        <td class="px-6 py-3 text-center text-green-500 font-bold">&#x2714;</td>
                    </tr>
                    <tr>
                        <td class="px-6 py-3 text-sm text-gray-700 dark:text-gray-300">ค่าคอมมิชชั่น</td>
                        <td class="px-6 py-3 text-center text-sm text-gray-600 dark:text-gray-400">
                            {{ $current_package ? number_format($current_package->commission_rate, 0).'%' : '-' }}
                        </td>
                        <td class="px-6 py-3 text-center text-sm font-bold text-amber-600 dark:text-amber-400">
                            {{ $enterprise_package ? number_format($enterprise_package->commission_rate, 0).'%' : '5%' }}
                        </td>
                    </tr>
                    <tr>
                        <td class="px-6 py-3 text-sm text-gray-700 dark:text-gray-300">สินค้า</td>
                        <td class="px-6 py-3 text-center text-sm text-gray-600 dark:text-gray-400">
                            {{ $current_package->max_products ?? 'จำกัด' }}
                        </td>
                        <td class="px-6 py-3 text-center text-sm font-bold text-amber-600 dark:text-amber-400">ไม่จำกัด</td>
                    </tr>
                    <tr>
                        <td class="px-6 py-3 text-sm text-gray-700 dark:text-gray-300">คำสั่งซื้อ/เดือน</td>
                        <td class="px-6 py-3 text-center text-sm text-gray-600 dark:text-gray-400">
                            {{ $current_package->max_monthly_orders ?? 'จำกัด' }}
                        </td>
                        <td class="px-6 py-3 text-center text-sm font-bold text-amber-600 dark:text-amber-400">ไม่จำกัด</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    {{-- CTA --}}
    <div class="text-center">
        <a href="{{ route('seller.packages') }}"
           class="inline-flex items-center gap-2 px-10 py-4 bg-gradient-to-r from-yellow-500 to-amber-500 hover:from-yellow-600 hover:to-amber-600 text-white font-bold text-lg rounded-xl transition shadow-lg">
            อัพเกรดเป็น Enterprise เลย
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/>
            </svg>
        </a>
        <p class="text-sm text-gray-500 dark:text-gray-400 mt-3">
            แพ็คเกจปัจจุบันของคุณ: <strong>{{ $current_package->display_name ?? 'ไม่มี' }}</strong>
            — การชำระเงินผ่านบัญชีแพลตฟอร์มเท่านั้น
        </p>
    </div>
</div>
@endsection
