{{--
    Landing Page — ผู้ซื้อ (Buyer)
    หน้าสอนการใช้งานก่อนส่งไปเพิ่มเพื่อน LINE ตลาดสด

    Route: GET /taladsod/start/buyer
    V3: Tailwind CSS + Alpine.js
--}}

@extends('layouts.taladsod')

@section('title', 'ช๊อปปิ้งตลาดสดออนไลน์ ส่งถึงบ้าน')

@section('meta_description', 'ซื้อของสดออนไลน์ ผักสด ผลไม้ เนื้อสัตว์ อาหารทะเล จากตลาดใกล้บ้าน ส่งถึงที่ พร้อม Cashback ทุกออเดอร์')

@section('content')
<div class="min-h-screen bg-gradient-to-b from-green-50 to-white dark:from-gray-900 dark:to-gray-800">

    {{-- Hero Section --}}
    <section class="relative overflow-hidden py-16 lg:py-24">
        {{-- Background Decoration --}}
        <div class="absolute inset-0 opacity-10 dark:opacity-5">
            <div class="absolute top-10 left-10 text-8xl">🥬</div>
            <div class="absolute top-20 right-20 text-7xl">🍎</div>
            <div class="absolute bottom-10 left-1/4 text-6xl">🐟</div>
            <div class="absolute bottom-20 right-10 text-8xl">🥩</div>
        </div>

        <div class="relative z-10 max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
            <div class="inline-flex items-center gap-2 px-4 py-2 rounded-full bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-300 text-sm font-medium mb-6">
                <span class="w-2 h-2 bg-green-500 rounded-full animate-pulse"></span>
                สำหรับผู้ซื้อ
            </div>

            <h1 class="text-4xl md:text-5xl lg:text-6xl font-bold text-gray-900 dark:text-white mb-6 leading-tight">
                ซื้อของสดออนไลน์
                <span class="text-green-600 dark:text-green-400">ส่งถึงบ้าน</span>
            </h1>

            <p class="text-xl text-gray-600 dark:text-gray-300 mb-10 max-w-2xl mx-auto">
                เลือกซื้อของสดคุณภาพจากตลาดใกล้บ้าน สั่งง่ายผ่าน LINE ส่งไวถึงหน้าบ้าน พร้อม Cashback ทุกออเดอร์
            </p>
        </div>
    </section>

    {{-- ขั้นตอนง่าย 3 Steps --}}
    <section class="py-16 bg-white dark:bg-gray-800">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
            <h2 class="text-3xl font-bold text-center text-gray-900 dark:text-white mb-12">
                เริ่มต้นง่ายๆ <span class="text-green-600 dark:text-green-400">3 ขั้นตอน</span>
            </h2>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                {{-- Step 1 --}}
                <div class="relative text-center p-6">
                    <div class="w-16 h-16 bg-green-100 dark:bg-green-900/30 rounded-full flex items-center justify-center mx-auto mb-4">
                        <span class="text-2xl font-bold text-green-600 dark:text-green-400">1</span>
                    </div>
                    <div class="text-4xl mb-4">📱</div>
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-2">เพิ่มเพื่อน LINE</h3>
                    <p class="text-gray-600 dark:text-gray-400">กดปุ่มด้านล่างเพื่อเพิ่มเพื่อน LINE ตลาดสดไทยพร๊อม</p>
                    {{-- Connector --}}
                    <div class="hidden md:block absolute top-8 left-full w-full h-0.5 bg-green-200 dark:bg-green-800 -translate-x-1/2"></div>
                </div>

                {{-- Step 2 --}}
                <div class="relative text-center p-6">
                    <div class="w-16 h-16 bg-green-100 dark:bg-green-900/30 rounded-full flex items-center justify-center mx-auto mb-4">
                        <span class="text-2xl font-bold text-green-600 dark:text-green-400">2</span>
                    </div>
                    <div class="text-4xl mb-4">🛒</div>
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-2">เลือกสินค้า</h3>
                    <p class="text-gray-600 dark:text-gray-400">เปิดเมนูตลาดสด เลือกสินค้าที่ต้องการ ระบุจำนวน แล้วสั่งซื้อ</p>
                    <div class="hidden md:block absolute top-8 left-full w-full h-0.5 bg-green-200 dark:bg-green-800 -translate-x-1/2"></div>
                </div>

                {{-- Step 3 --}}
                <div class="text-center p-6">
                    <div class="w-16 h-16 bg-green-100 dark:bg-green-900/30 rounded-full flex items-center justify-center mx-auto mb-4">
                        <span class="text-2xl font-bold text-green-600 dark:text-green-400">3</span>
                    </div>
                    <div class="text-4xl mb-4">🚚</div>
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-2">รับของถึงบ้าน</h3>
                    <p class="text-gray-600 dark:text-gray-400">ไรเดอร์รับของจากร้านค้าแล้วส่งถึงหน้าบ้านคุณ พร้อม Cashback</p>
                </div>
            </div>
        </div>
    </section>

    {{-- จุดเด่น --}}
    <section class="py-16 bg-green-50 dark:bg-gray-900">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
            <h2 class="text-3xl font-bold text-center text-gray-900 dark:text-white mb-12">
                ทำไมต้องตลาดสด<span class="text-green-600 dark:text-green-400">ไทยพร๊อม</span>?
            </h2>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                <div class="flex items-start gap-4 p-6 bg-white dark:bg-gray-800 rounded-xl shadow-sm">
                    <div class="text-3xl">🥬</div>
                    <div>
                        <h3 class="font-bold text-gray-900 dark:text-white mb-1">ของสดจริง จากตลาด</h3>
                        <p class="text-gray-600 dark:text-gray-400 text-sm">คัดสรรจากผู้ขายในตลาดสด ไม่ผ่านคนกลาง สดใหม่ทุกวัน</p>
                    </div>
                </div>

                <div class="flex items-start gap-4 p-6 bg-white dark:bg-gray-800 rounded-xl shadow-sm">
                    <div class="text-3xl">⚡</div>
                    <div>
                        <h3 class="font-bold text-gray-900 dark:text-white mb-1">ส่งไว ไรเดอร์ใกล้บ้าน</h3>
                        <p class="text-gray-600 dark:text-gray-400 text-sm">ไรเดอร์ประจำพื้นที่ รับของจากตลาดส่งถึงบ้านรวดเร็ว</p>
                    </div>
                </div>

                <div class="flex items-start gap-4 p-6 bg-white dark:bg-gray-800 rounded-xl shadow-sm">
                    <div class="text-3xl">💰</div>
                    <div>
                        <h3 class="font-bold text-gray-900 dark:text-white mb-1">ราคาตลาด ไม่บวกเพิ่ม</h3>
                        <p class="text-gray-600 dark:text-gray-400 text-sm">ราคาเท่าซื้อเองที่ตลาด จ่ายแค่ค่าส่ง</p>
                    </div>
                </div>

                <div class="flex items-start gap-4 p-6 bg-white dark:bg-gray-800 rounded-xl shadow-sm">
                    <div class="text-3xl">🎁</div>
                    <div>
                        <h3 class="font-bold text-gray-900 dark:text-white mb-1">Cashback ทุกออเดอร์</h3>
                        <p class="text-gray-600 dark:text-gray-400 text-sm">ได้เงินคืนทุกครั้งที่สั่งซื้อ ผ่านระบบ MLM สะสมรายได้</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- CTA Section --}}
    <section class="py-20 bg-gradient-to-r from-green-600 to-emerald-700 dark:from-green-800 dark:to-emerald-900">
        <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
            <div class="text-5xl mb-6">🛍️</div>
            <h2 class="text-3xl md:text-4xl font-bold text-white mb-4">พร้อมช๊อปแล้วหรือยัง?</h2>
            <p class="text-lg text-green-100 mb-8">
                กดเพิ่มเพื่อน LINE ตลาดสดไทยพร๊อม เริ่มช๊อปปิ้งได้ทันที!
            </p>
            <a href="{{ config('services.line.fresh_market_add_friend_url', env('LINE_FRESH_MARKET_ADD_FRIEND_URL', '#')) }}"
               target="_blank"
               rel="noopener noreferrer"
               class="inline-flex items-center gap-3 px-10 py-5 bg-[#06C755] hover:bg-[#05b34d] text-white font-bold text-xl rounded-2xl shadow-2xl shadow-black/20 hover:shadow-black/30 transition-all hover:-translate-y-1">
                <svg class="w-8 h-8" viewBox="0 0 24 24" fill="currentColor">
                    <path d="M19.365 9.863c.349 0 .63.285.63.631 0 .345-.281.63-.63.63H17.61v1.125h1.755c.349 0 .63.283.63.63 0 .344-.281.629-.63.629h-2.386c-.345 0-.627-.285-.627-.629V8.108c0-.345.282-.63.63-.63h2.386c.346 0 .627.285.627.63 0 .349-.281.63-.63.63H17.61v1.125h1.755zm-3.855 3.016c0 .27-.174.51-.432.596-.064.021-.133.031-.199.031-.211 0-.391-.09-.51-.25l-2.443-3.317v2.94c0 .344-.279.629-.631.629-.346 0-.626-.285-.626-.629V8.108c0-.27.173-.51.43-.595.06-.023.136-.033.194-.033.195 0 .375.104.495.254l2.462 3.33V8.108c0-.345.282-.63.63-.63.345 0 .63.285.63.63v4.771zm-5.741 0c0 .344-.282.629-.631.629-.345 0-.627-.285-.627-.629V8.108c0-.345.282-.63.63-.63.346 0 .628.285.628.63v4.771zm-2.466.629H4.917c-.345 0-.63-.285-.63-.629V8.108c0-.345.285-.63.63-.63.348 0 .63.285.63.63v4.141h1.756c.348 0 .629.283.629.63 0 .344-.282.629-.629.629M24 10.314C24 4.943 18.615.572 12 .572S0 4.943 0 10.314c0 4.811 4.27 8.842 10.035 9.608.391.082.923.258 1.058.59.12.301.079.766.038 1.08l-.164 1.02c-.045.301-.24 1.186 1.049.645 1.291-.539 6.916-4.078 9.436-6.975C23.176 14.393 24 12.458 24 10.314"/>
                </svg>
                เพิ่มเพื่อน LINE ตลาดสด
            </a>
            <p class="text-green-200 text-sm mt-4">ฟรี! ไม่มีค่าใช้จ่าย</p>
        </div>
    </section>

    {{-- หรือเลือกบทบาทอื่น --}}
    <section class="py-12 bg-gray-50 dark:bg-gray-800">
        <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
            <p class="text-gray-600 dark:text-gray-400 mb-4">สนใจบทบาทอื่น?</p>
            <div class="flex flex-wrap justify-center gap-4">
                <a href="{{ route('taladsod.landing.seller') }}"
                   class="px-6 py-3 bg-white dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl text-gray-700 dark:text-gray-300 hover:border-orange-500 hover:text-orange-600 dark:hover:text-orange-400 transition-all">
                    🏪 เปิดร้านขาย/บริการ
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
