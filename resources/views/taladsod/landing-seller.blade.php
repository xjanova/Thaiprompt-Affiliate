{{--
    Landing Page — ผู้ขาย/บริการ (Seller/Service)
    หน้าสอนการใช้งานก่อนส่งไปเพิ่มเพื่อน LINE ตลาดสด

    Route: GET /taladsod/start/seller
    V3: Tailwind CSS + Alpine.js
--}}

@extends('layouts.taladsod')

@section('title', 'เปิดร้านออนไลน์ฟรี - ตลาดสดไทยพร๊อม')

@section('meta_description', 'เปิดร้านออนไลน์ฟรี ขายได้ทันที จัดการทุกอย่างผ่าน LINE ไม่ต้องสร้างเว็บ ไม่มีค่าแรกเข้า พร้อม MLM Cashback')

@section('content')
<div class="min-h-screen bg-gradient-to-b from-orange-50 to-white dark:from-gray-900 dark:to-gray-800">

    {{-- Hero Section --}}
    <section class="relative overflow-hidden py-16 lg:py-24">
        <div class="absolute inset-0 opacity-10 dark:opacity-5">
            <div class="absolute top-10 right-10 text-8xl">🏪</div>
            <div class="absolute bottom-10 left-20 text-7xl">💼</div>
        </div>

        <div class="relative z-10 max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
            <div class="inline-flex items-center gap-2 px-4 py-2 rounded-full bg-orange-100 dark:bg-orange-900/30 text-orange-700 dark:text-orange-300 text-sm font-medium mb-6">
                <span class="w-2 h-2 bg-orange-500 rounded-full animate-pulse"></span>
                สำหรับผู้ขาย / ผู้ให้บริการ
            </div>

            <h1 class="text-4xl md:text-5xl lg:text-6xl font-bold text-gray-900 dark:text-white mb-6 leading-tight">
                เปิดร้านออนไลน์<span class="text-orange-600 dark:text-orange-400">ฟรี</span>
                <br>ขายได้ทันที
            </h1>

            <p class="text-xl text-gray-600 dark:text-gray-300 mb-10 max-w-2xl mx-auto">
                ไม่ต้องสร้างเว็บ ไม่ต้องมีทุน จัดการทุกอย่างผ่าน LINE แค่ถ่ายรูปสินค้า ก็เริ่มขายได้เลย
            </p>
        </div>
    </section>

    {{-- ขั้นตอน 4 Steps --}}
    <section class="py-16 bg-white dark:bg-gray-800">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
            <h2 class="text-3xl font-bold text-center text-gray-900 dark:text-white mb-12">
                เริ่มขายใน <span class="text-orange-600 dark:text-orange-400">4 ขั้นตอน</span>
            </h2>

            <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                {{-- Step 1 --}}
                <div class="text-center p-6 relative">
                    <div class="w-14 h-14 bg-orange-100 dark:bg-orange-900/30 rounded-full flex items-center justify-center mx-auto mb-4">
                        <span class="text-xl font-bold text-orange-600 dark:text-orange-400">1</span>
                    </div>
                    <div class="text-3xl mb-3">📱</div>
                    <h3 class="font-bold text-gray-900 dark:text-white mb-2">เพิ่มเพื่อน LINE</h3>
                    <p class="text-gray-600 dark:text-gray-400 text-sm">กดเพิ่มเพื่อน LINE ตลาดสด ระบบสร้างร้านให้อัตโนมัติ</p>
                </div>

                {{-- Step 2 --}}
                <div class="text-center p-6 relative">
                    <div class="w-14 h-14 bg-orange-100 dark:bg-orange-900/30 rounded-full flex items-center justify-center mx-auto mb-4">
                        <span class="text-xl font-bold text-orange-600 dark:text-orange-400">2</span>
                    </div>
                    <div class="text-3xl mb-3">🔐</div>
                    <h3 class="font-bold text-gray-900 dark:text-white mb-2">ยืนยัน OTP</h3>
                    <p class="text-gray-600 dark:text-gray-400 text-sm">พิมพ์เบอร์โทร → รับ OTP ทาง SMS → ยืนยันตัวตน</p>
                </div>

                {{-- Step 3 --}}
                <div class="text-center p-6 relative">
                    <div class="w-14 h-14 bg-orange-100 dark:bg-orange-900/30 rounded-full flex items-center justify-center mx-auto mb-4">
                        <span class="text-xl font-bold text-orange-600 dark:text-orange-400">3</span>
                    </div>
                    <div class="text-3xl mb-3">📸</div>
                    <h3 class="font-bold text-gray-900 dark:text-white mb-2">ถ่ายรูปสินค้า</h3>
                    <p class="text-gray-600 dark:text-gray-400 text-sm">ถ่ายรูปสินค้าส่งผ่าน LINE ตั้งราคา AI ช่วยเขียนคำอธิบาย</p>
                </div>

                {{-- Step 4 --}}
                <div class="text-center p-6">
                    <div class="w-14 h-14 bg-orange-100 dark:bg-orange-900/30 rounded-full flex items-center justify-center mx-auto mb-4">
                        <span class="text-xl font-bold text-orange-600 dark:text-orange-400">4</span>
                    </div>
                    <div class="text-3xl mb-3">🎉</div>
                    <h3 class="font-bold text-gray-900 dark:text-white mb-2">เริ่มขายเลย!</h3>
                    <p class="text-gray-600 dark:text-gray-400 text-sm">สินค้าขึ้นหน้าร้านทันที ลูกค้าสั่งซื้อ ได้เงิน!</p>
                </div>
            </div>
        </div>
    </section>

    {{-- จุดเด่น --}}
    <section class="py-16 bg-orange-50 dark:bg-gray-900">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
            <h2 class="text-3xl font-bold text-center text-gray-900 dark:text-white mb-12">
                ข้อดีของการเปิดร้านกับ<span class="text-orange-600 dark:text-orange-400">ไทยพร๊อม</span>
            </h2>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                <div class="p-6 bg-white dark:bg-gray-800 rounded-xl shadow-sm">
                    <div class="text-3xl mb-3">🆓</div>
                    <h3 class="font-bold text-gray-900 dark:text-white mb-2">ฟรี! ไม่มีค่าแรกเข้า</h3>
                    <p class="text-gray-600 dark:text-gray-400 text-sm">เปิดร้านฟรี ไม่มีค่าสมัคร ไม่มีค่ารายเดือน</p>
                </div>

                <div class="p-6 bg-white dark:bg-gray-800 rounded-xl shadow-sm">
                    <div class="text-3xl mb-3">💬</div>
                    <h3 class="font-bold text-gray-900 dark:text-white mb-2">จัดการผ่าน LINE</h3>
                    <p class="text-gray-600 dark:text-gray-400 text-sm">ลงขาย จัดการออเดอร์ ดูรายได้ ทำทุกอย่างผ่าน LINE ง่ายมาก</p>
                </div>

                <div class="p-6 bg-white dark:bg-gray-800 rounded-xl shadow-sm">
                    <div class="text-3xl mb-3">🤖</div>
                    <h3 class="font-bold text-gray-900 dark:text-white mb-2">AI ช่วยเขียนโฆษณา</h3>
                    <p class="text-gray-600 dark:text-gray-400 text-sm">ถ่ายรูป AI เขียนคำอธิบายสินค้าให้อัตโนมัติ ไม่ต้องพิมพ์เอง</p>
                </div>

                <div class="p-6 bg-white dark:bg-gray-800 rounded-xl shadow-sm">
                    <div class="text-3xl mb-3">🚚</div>
                    <h3 class="font-bold text-gray-900 dark:text-white mb-2">มีไรเดอร์ส่งให้</h3>
                    <p class="text-gray-600 dark:text-gray-400 text-sm">ไม่ต้องส่งเอง ไรเดอร์ในระบบมารับของส่งให้ลูกค้า</p>
                </div>

                <div class="p-6 bg-white dark:bg-gray-800 rounded-xl shadow-sm">
                    <div class="text-3xl mb-3">💰</div>
                    <h3 class="font-bold text-gray-900 dark:text-white mb-2">MLM Cashback</h3>
                    <p class="text-gray-600 dark:text-gray-400 text-sm">ชวนเพื่อนมาขาย ได้คอมมิชชั่นจากยอดขายทั้งทีม</p>
                </div>

                <div class="p-6 bg-white dark:bg-gray-800 rounded-xl shadow-sm">
                    <div class="text-3xl mb-3">📊</div>
                    <h3 class="font-bold text-gray-900 dark:text-white mb-2">แดชบอร์ดครบ</h3>
                    <p class="text-gray-600 dark:text-gray-400 text-sm">ดูยอดขาย ออเดอร์ รีวิว สถิติร้าน ทั้งบน LINE และเว็บ</p>
                </div>
            </div>
        </div>
    </section>

    {{-- CTA Section --}}
    <section class="py-20 bg-gradient-to-r from-orange-500 to-amber-600 dark:from-orange-700 dark:to-amber-800">
        <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
            <div class="text-5xl mb-6">🏪</div>
            <h2 class="text-3xl md:text-4xl font-bold text-white mb-4">พร้อมเปิดร้านแล้วหรือยัง?</h2>
            <p class="text-lg text-orange-100 mb-8">
                เพิ่มเพื่อน LINE ตลาดสด ระบบสร้างร้านให้อัตโนมัติ เริ่มขายได้ทันที!
            </p>
            <a href="{{ config('services.line.fresh_market_add_friend_url', env('LINE_FRESH_MARKET_ADD_FRIEND_URL', '#')) }}"
               target="_blank"
               rel="noopener noreferrer"
               class="inline-flex items-center gap-3 px-10 py-5 bg-[#06C755] hover:bg-[#05b34d] text-white font-bold text-xl rounded-2xl shadow-2xl shadow-black/20 hover:shadow-black/30 transition-all hover:-translate-y-1">
                <svg class="w-8 h-8" viewBox="0 0 24 24" fill="currentColor">
                    <path d="M19.365 9.863c.349 0 .63.285.63.631 0 .345-.281.63-.63.63H17.61v1.125h1.755c.349 0 .63.283.63.63 0 .344-.281.629-.63.629h-2.386c-.345 0-.627-.285-.627-.629V8.108c0-.345.282-.63.63-.63h2.386c.346 0 .627.285.627.63 0 .349-.281.63-.63.63H17.61v1.125h1.755zm-3.855 3.016c0 .27-.174.51-.432.596-.064.021-.133.031-.199.031-.211 0-.391-.09-.51-.25l-2.443-3.317v2.94c0 .344-.279.629-.631.629-.346 0-.626-.285-.626-.629V8.108c0-.27.173-.51.43-.595.06-.023.136-.033.194-.033.195 0 .375.104.495.254l2.462 3.33V8.108c0-.345.282-.63.63-.63.345 0 .63.285.63.63v4.771zm-5.741 0c0 .344-.282.629-.631.629-.345 0-.627-.285-.627-.629V8.108c0-.345.282-.63.63-.63.346 0 .628.285.628.63v4.771zm-2.466.629H4.917c-.345 0-.63-.285-.63-.629V8.108c0-.345.285-.63.63-.63.348 0 .63.285.63.63v4.141h1.756c.348 0 .629.283.629.63 0 .344-.282.629-.629.629M24 10.314C24 4.943 18.615.572 12 .572S0 4.943 0 10.314c0 4.811 4.27 8.842 10.035 9.608.391.082.923.258 1.058.59.12.301.079.766.038 1.08l-.164 1.02c-.045.301-.24 1.186 1.049.645 1.291-.539 6.916-4.078 9.436-6.975C23.176 14.393 24 12.458 24 10.314"/>
                </svg>
                เพิ่มเพื่อน LINE เปิดร้านเลย
            </a>
            <p class="text-orange-200 text-sm mt-4">ฟรี! ไม่มีค่าใช้จ่าย</p>
        </div>
    </section>

    {{-- หรือเลือกบทบาทอื่น --}}
    <section class="py-12 bg-gray-50 dark:bg-gray-800">
        <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
            <p class="text-gray-600 dark:text-gray-400 mb-4">สนใจบทบาทอื่น?</p>
            <div class="flex flex-wrap justify-center gap-4">
                <a href="{{ route('taladsod.landing.buyer') }}"
                   class="px-6 py-3 bg-white dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl text-gray-700 dark:text-gray-300 hover:border-green-500 hover:text-green-600 dark:hover:text-green-400 transition-all">
                    🛍️ ซื้อของตลาดสด
                </a>
                <a href="{{ route('taladsod.landing.rider') }}"
                   class="px-6 py-3 bg-white dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl text-gray-700 dark:text-gray-300 hover:border-blue-500 hover:text-blue-600 dark:hover:text-blue-400 transition-all">
                    🚴 สมัครเป็นไรเดอร์/ช่าง
                </a>
            </div>
        </div>
    </section>
</div>
@endsection
