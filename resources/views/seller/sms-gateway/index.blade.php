@extends('layouts.seller')

@section('title', 'รับชำระเงินตรง - SMS Payment Gateway')

@section('content')
<div class="container mx-auto px-4 py-8 max-w-5xl">

    {{-- Header --}}
    <div class="mb-8">
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
            รับชำระเงินตรง
        </h1>
        <p class="text-gray-600 dark:text-gray-400 mt-1">
            จัดการระบบรับชำระเงินเข้าบัญชีร้านค้าของคุณโดยตรง ผ่านแอป SMS Checker
        </p>
    </div>

    {{-- Flash Messages --}}
    @if(session('success'))
    <div class="bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-700 rounded-lg p-4 mb-6">
        <p class="text-sm text-green-800 dark:text-green-300">{{ session('success') }}</p>
    </div>
    @endif
    @if(session('error'))
    <div class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-700 rounded-lg p-4 mb-6">
        <p class="text-sm text-red-800 dark:text-red-300">{{ session('error') }}</p>
    </div>
    @endif

    {{-- สถานะ Enterprise --}}
    <div class="bg-gradient-to-r from-yellow-400 to-amber-500 rounded-xl p-6 mb-8 text-white shadow-lg">
        <div class="flex items-center gap-3 mb-2">
            <span class="text-2xl">&#x1F451;</span>
            <h2 class="text-xl font-bold">Enterprise Package</h2>
        </div>
        <p class="text-yellow-100">ร้านค้าของคุณมีสิทธิ์รับชำระเงินเข้าบัญชีตนเองได้โดยตรง</p>
    </div>

    {{-- Quick Stats --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-6">
            <div class="text-sm text-gray-500 dark:text-gray-400">อุปกรณ์ที่ใช้งาน</div>
            <div class="text-3xl font-bold text-gray-900 dark:text-white mt-1">
                {{ $status['current_devices'] ?? 0 }}
            </div>
            <a href="{{ route('seller.sms-gateway.devices') }}" class="text-sm text-indigo-600 dark:text-indigo-400 hover:underline mt-2 inline-block">
                จัดการอุปกรณ์ &rarr;
            </a>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-6">
            <div class="text-sm text-gray-500 dark:text-gray-400">บัญชีธนาคาร</div>
            <div class="text-3xl font-bold text-gray-900 dark:text-white mt-1">
                {{ \App\Models\SmsGatewayBankAccount::where('store_id', $store->id)->where('is_active', true)->count() }}
            </div>
            <a href="{{ route('seller.sms-gateway.bank-accounts') }}" class="text-sm text-indigo-600 dark:text-indigo-400 hover:underline mt-2 inline-block">
                จัดการบัญชี &rarr;
            </a>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-6">
            <div class="text-sm text-gray-500 dark:text-gray-400">สถานะระบบ</div>
            <div class="flex items-center gap-2 mt-1">
                <span class="inline-block w-3 h-3 rounded-full {{ ($status['has_access'] ?? false) ? 'bg-green-500' : 'bg-red-500' }}"></span>
                <span class="text-lg font-semibold text-gray-900 dark:text-white">
                    {{ ($status['has_access'] ?? false) ? 'พร้อมใช้งาน' : 'ยังไม่เปิดใช้' }}
                </span>
            </div>
        </div>
    </div>

    {{-- ขั้นตอนการตั้งค่า --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-6 mb-8">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">ขั้นตอนการตั้งค่า</h3>
        <div class="space-y-4">
            {{-- Step 1: ดาวน์โหลดแอป --}}
            <div class="flex gap-4">
                <div class="flex-shrink-0 w-10 h-10 rounded-full bg-indigo-100 dark:bg-indigo-900/30 flex items-center justify-center">
                    <span class="text-indigo-600 dark:text-indigo-400 font-bold">1</span>
                </div>
                <div class="flex-1">
                    <h4 class="font-medium text-gray-900 dark:text-white">ดาวน์โหลดแอป SMS Checker</h4>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mb-2">ติดตั้งแอปบนมือถือ Android ที่รับ SMS จากธนาคาร</p>
                    <a href="{{ $downloadUrl }}" target="_blank"
                       class="inline-flex items-center gap-2 px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-sm rounded-lg transition">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                        </svg>
                        ดาวน์โหลด APK
                    </a>
                </div>
            </div>

            {{-- Step 2: เพิ่มอุปกรณ์ --}}
            <div class="flex gap-4">
                <div class="flex-shrink-0 w-10 h-10 rounded-full bg-indigo-100 dark:bg-indigo-900/30 flex items-center justify-center">
                    <span class="text-indigo-600 dark:text-indigo-400 font-bold">2</span>
                </div>
                <div class="flex-1">
                    <h4 class="font-medium text-gray-900 dark:text-white">เพิ่มอุปกรณ์ + สแกน QR Code</h4>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mb-2">สร้างอุปกรณ์ใหม่แล้วสแกน QR Code ด้วยแอปเพื่อจับคู่</p>
                    <a href="{{ route('seller.sms-gateway.devices') }}"
                       class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm rounded-lg transition">
                        จัดการอุปกรณ์ &rarr;
                    </a>
                </div>
            </div>

            {{-- Step 3: ตั้งค่าบัญชีธนาคาร --}}
            <div class="flex gap-4">
                <div class="flex-shrink-0 w-10 h-10 rounded-full bg-indigo-100 dark:bg-indigo-900/30 flex items-center justify-center">
                    <span class="text-indigo-600 dark:text-indigo-400 font-bold">3</span>
                </div>
                <div class="flex-1">
                    <h4 class="font-medium text-gray-900 dark:text-white">ตั้งค่าบัญชีธนาคาร</h4>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mb-2">เพิ่มบัญชีธนาคารที่ลูกค้าจะโอนเงินเข้าโดยตรง</p>
                    <a href="{{ route('seller.sms-gateway.bank-accounts') }}"
                       class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm rounded-lg transition">
                        จัดการบัญชีธนาคาร &rarr;
                    </a>
                </div>
            </div>

            {{-- Step 4: พร้อมใช้งาน --}}
            <div class="flex gap-4">
                <div class="flex-shrink-0 w-10 h-10 rounded-full bg-green-100 dark:bg-green-900/30 flex items-center justify-center">
                    <span class="text-green-600 dark:text-green-400 font-bold">&#x2713;</span>
                </div>
                <div class="flex-1">
                    <h4 class="font-medium text-gray-900 dark:text-white">พร้อมรับชำระเงิน!</h4>
                    <p class="text-sm text-gray-600 dark:text-gray-400">เมื่อลูกค้าโอนเงิน แอป SMS Checker จะส่ง SMS ไปยืนยันที่เซิร์ฟเวอร์อัตโนมัติ</p>
                </div>
            </div>
        </div>
    </div>

    {{-- คำอธิบายระบบ --}}
    <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-700 rounded-xl p-6">
        <h3 class="font-semibold text-blue-800 dark:text-blue-300 mb-2">ระบบทำงานอย่างไร?</h3>
        <ul class="text-sm text-blue-700 dark:text-blue-400 space-y-1 list-disc list-inside">
            <li>ลูกค้าสั่งซื้อสินค้าและเลือกโอนเงินเข้าบัญชีร้านคุณ</li>
            <li>ลูกค้าโอนเงินไปยังบัญชีธนาคารที่คุณตั้งค่าไว้</li>
            <li>ธนาคารส่ง SMS แจ้งเตือนมายังมือถือที่ติดตั้งแอป SMS Checker</li>
            <li>แอปส่งข้อมูล SMS ไปยืนยันที่เซิร์ฟเวอร์โดยอัตโนมัติ</li>
            <li>ระบบจับคู่ยอดเงินและยืนยันคำสั่งซื้อให้ทันที</li>
        </ul>
    </div>
</div>
@endsection
