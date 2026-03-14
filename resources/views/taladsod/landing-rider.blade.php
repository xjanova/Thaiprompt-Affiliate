{{--
    Landing Page — ไรเดอร์ / ช่างบริการ (Rider / Service Provider)
    หน้าสอนการใช้งานก่อนส่งไปเพิ่มเพื่อน LINE ตลาดสด

    Route: GET /taladsod/start/rider
    V3: Tailwind CSS + Alpine.js
--}}

@extends('layouts.taladsod')

@section('title', 'สมัครเป็นไรเดอร์ & ช่างบริการ - ตลาดสดไทยพร๊อม')

@section('meta_description', 'สมัครเป็นไรเดอร์ส่งของ หรือช่างบริการ (ประปา แอร์ เสริมสวย หมอนวด) สร้างรายได้ทุกวัน เลือกเวลาทำงานเอง')

@section('content')
<div class="min-h-screen bg-gradient-to-b from-blue-50 to-white dark:from-gray-900 dark:to-gray-800">

    {{-- Hero Section --}}
    <section class="relative overflow-hidden py-16 lg:py-24">
        <div class="absolute inset-0 opacity-10 dark:opacity-5">
            <div class="absolute top-10 left-10 text-8xl">🚴</div>
            <div class="absolute top-20 right-20 text-7xl">🔧</div>
            <div class="absolute bottom-10 right-1/4 text-6xl">💆</div>
        </div>

        <div class="relative z-10 max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
            <div class="inline-flex items-center gap-2 px-4 py-2 rounded-full bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300 text-sm font-medium mb-6">
                <span class="w-2 h-2 bg-blue-500 rounded-full animate-pulse"></span>
                สำหรับไรเดอร์ & ช่างบริการ
            </div>

            <h1 class="text-4xl md:text-5xl lg:text-6xl font-bold text-gray-900 dark:text-white mb-6 leading-tight">
                รับงานส่งของ
                <span class="text-blue-600 dark:text-blue-400">&</span>
                งานช่าง
                <br>
                <span class="text-blue-600 dark:text-blue-400">สร้างรายได้ทุกวัน</span>
            </h1>

            <p class="text-xl text-gray-600 dark:text-gray-300 mb-10 max-w-2xl mx-auto">
                เลือกเส้นทางของคุณ — ส่งของหาเงิน หรือใช้ฝีมือช่างรับงานบริการ ทำได้ทั้งสองอย่าง!
            </p>
        </div>
    </section>

    {{-- 2 Tracks Section --}}
    <section class="py-16 bg-white dark:bg-gray-800" x-data="{ activeTrack: 'delivery' }">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
            <h2 class="text-3xl font-bold text-center text-gray-900 dark:text-white mb-8">
                เลือก<span class="text-blue-600 dark:text-blue-400">เส้นทาง</span>ของคุณ
            </h2>

            {{-- Track Tabs --}}
            <div class="flex justify-center gap-4 mb-12">
                <button @click="activeTrack = 'delivery'"
                        :class="activeTrack === 'delivery' ? 'bg-blue-600 text-white shadow-lg shadow-blue-500/30' : 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300'"
                        class="px-8 py-4 rounded-xl font-bold text-lg transition-all">
                    🚴 ไรเดอร์ส่งของ
                </button>
                <button @click="activeTrack = 'service'"
                        :class="activeTrack === 'service' ? 'bg-indigo-600 text-white shadow-lg shadow-indigo-500/30' : 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300'"
                        class="px-8 py-4 rounded-xl font-bold text-lg transition-all">
                    🔧 ช่างบริการ
                </button>
            </div>

            {{-- Track: Delivery Rider --}}
            <div x-show="activeTrack === 'delivery'" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-4" x-transition:enter-end="opacity-100 translate-y-0">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                    <div>
                        <h3 class="text-2xl font-bold text-gray-900 dark:text-white mb-4">ไรเดอร์ส่งของ</h3>
                        <p class="text-gray-600 dark:text-gray-400 mb-6">
                            รับงานส่งของจากตลาดสดถึงบ้านลูกค้า รับงานส่งพัสดุ เอกสาร อาหาร เลือกเวลาทำงานเองได้
                        </p>
                        <ul class="space-y-3">
                            <li class="flex items-start gap-3">
                                <span class="text-green-500 mt-1">✓</span>
                                <span class="text-gray-700 dark:text-gray-300">เลือกรับงานเอง ไม่มีบังคับ</span>
                            </li>
                            <li class="flex items-start gap-3">
                                <span class="text-green-500 mt-1">✓</span>
                                <span class="text-gray-700 dark:text-gray-300">ค่าส่งเริ่มต้น 30-50 บาท/ออเดอร์</span>
                            </li>
                            <li class="flex items-start gap-3">
                                <span class="text-green-500 mt-1">✓</span>
                                <span class="text-gray-700 dark:text-gray-300">โบนัสเมื่อส่งครบตามเป้า</span>
                            </li>
                            <li class="flex items-start gap-3">
                                <span class="text-green-500 mt-1">✓</span>
                                <span class="text-gray-700 dark:text-gray-300">ใช้มอเตอร์ไซค์ หรือรถยนต์ก็ได้</span>
                            </li>
                        </ul>
                    </div>
                    <div class="bg-blue-50 dark:bg-blue-900/20 rounded-2xl p-8">
                        <h4 class="font-bold text-gray-900 dark:text-white mb-4">ขั้นตอนสมัคร</h4>
                        <div class="space-y-4">
                            <div class="flex items-start gap-3">
                                <div class="w-8 h-8 bg-blue-600 text-white rounded-full flex items-center justify-center flex-shrink-0 text-sm font-bold">1</div>
                                <p class="text-gray-700 dark:text-gray-300">เพิ่มเพื่อน LINE ตลาดสดไทยพร๊อม</p>
                            </div>
                            <div class="flex items-start gap-3">
                                <div class="w-8 h-8 bg-blue-600 text-white rounded-full flex items-center justify-center flex-shrink-0 text-sm font-bold">2</div>
                                <p class="text-gray-700 dark:text-gray-300">พิมพ์ "สมัครไรเดอร์" เลือก "ส่งของ"</p>
                            </div>
                            <div class="flex items-start gap-3">
                                <div class="w-8 h-8 bg-blue-600 text-white rounded-full flex items-center justify-center flex-shrink-0 text-sm font-bold">3</div>
                                <p class="text-gray-700 dark:text-gray-300">ยืนยันตัวตน OTP + วางค่าประกันงาน</p>
                            </div>
                            <div class="flex items-start gap-3">
                                <div class="w-8 h-8 bg-blue-600 text-white rounded-full flex items-center justify-center flex-shrink-0 text-sm font-bold">4</div>
                                <p class="text-gray-700 dark:text-gray-300">เริ่มรับงานส่งของได้ทันที!</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Track: Service Provider (ช่างบริการ) --}}
            <div x-show="activeTrack === 'service'" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-4" x-transition:enter-end="opacity-100 translate-y-0">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                    <div>
                        <h3 class="text-2xl font-bold text-gray-900 dark:text-white mb-4">ไรเดอร์เซอร์วิส (ช่างบริการ)</h3>
                        <p class="text-gray-600 dark:text-gray-400 mb-6">
                            ใช้ฝีมือของคุณรับงานบริการ ทั้งงานช่างและงานส่งของ — ทำได้ทั้งสองอย่าง!
                        </p>

                        {{-- ประเภทช่าง --}}
                        <div class="grid grid-cols-2 gap-3 mb-6">
                            <div class="p-3 bg-indigo-50 dark:bg-indigo-900/20 rounded-xl text-center">
                                <div class="text-2xl mb-1">🔧</div>
                                <p class="text-sm font-medium text-gray-700 dark:text-gray-300">ช่างประปา</p>
                            </div>
                            <div class="p-3 bg-indigo-50 dark:bg-indigo-900/20 rounded-xl text-center">
                                <div class="text-2xl mb-1">❄️</div>
                                <p class="text-sm font-medium text-gray-700 dark:text-gray-300">ช่างแอร์</p>
                            </div>
                            <div class="p-3 bg-indigo-50 dark:bg-indigo-900/20 rounded-xl text-center">
                                <div class="text-2xl mb-1">⚡</div>
                                <p class="text-sm font-medium text-gray-700 dark:text-gray-300">ช่างไฟฟ้า</p>
                            </div>
                            <div class="p-3 bg-indigo-50 dark:bg-indigo-900/20 rounded-xl text-center">
                                <div class="text-2xl mb-1">💇</div>
                                <p class="text-sm font-medium text-gray-700 dark:text-gray-300">เสริมสวย</p>
                            </div>
                            <div class="p-3 bg-indigo-50 dark:bg-indigo-900/20 rounded-xl text-center">
                                <div class="text-2xl mb-1">💆</div>
                                <p class="text-sm font-medium text-gray-700 dark:text-gray-300">หมอนวด</p>
                            </div>
                            <div class="p-3 bg-indigo-50 dark:bg-indigo-900/20 rounded-xl text-center">
                                <div class="text-2xl mb-1">🏠</div>
                                <p class="text-sm font-medium text-gray-700 dark:text-gray-300">และอีกมาก...</p>
                            </div>
                        </div>
                    </div>
                    <div class="bg-indigo-50 dark:bg-indigo-900/20 rounded-2xl p-8">
                        <h4 class="font-bold text-gray-900 dark:text-white mb-4">ขั้นตอนสมัคร</h4>
                        <div class="space-y-4">
                            <div class="flex items-start gap-3">
                                <div class="w-8 h-8 bg-indigo-600 text-white rounded-full flex items-center justify-center flex-shrink-0 text-sm font-bold">1</div>
                                <p class="text-gray-700 dark:text-gray-300">เพิ่มเพื่อน LINE ตลาดสดไทยพร๊อม</p>
                            </div>
                            <div class="flex items-start gap-3">
                                <div class="w-8 h-8 bg-indigo-600 text-white rounded-full flex items-center justify-center flex-shrink-0 text-sm font-bold">2</div>
                                <p class="text-gray-700 dark:text-gray-300">พิมพ์ "สมัครไรเดอร์" เลือก "ช่างบริการ" หรือ "ทั้งสองอย่าง"</p>
                            </div>
                            <div class="flex items-start gap-3">
                                <div class="w-8 h-8 bg-indigo-600 text-white rounded-full flex items-center justify-center flex-shrink-0 text-sm font-bold">3</div>
                                <p class="text-gray-700 dark:text-gray-300">เลือกประเภทงานช่าง + ยืนยันตัวตน OTP</p>
                            </div>
                            <div class="flex items-start gap-3">
                                <div class="w-8 h-8 bg-indigo-600 text-white rounded-full flex items-center justify-center flex-shrink-0 text-sm font-bold">4</div>
                                <p class="text-gray-700 dark:text-gray-300">วางค่าประกันงาน → เริ่มรับงานได้ทันที!</p>
                            </div>
                        </div>

                        <div class="mt-6 p-4 bg-indigo-100 dark:bg-indigo-800/30 rounded-xl">
                            <p class="text-sm text-indigo-800 dark:text-indigo-200">
                                <strong>💡 เคล็ดลับ:</strong> เลือก "ทั้งสองอย่าง" เพื่อรับทั้งงานส่งของและงานช่าง เพิ่มรายได้สูงสุด!
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- ข้อดี --}}
    <section class="py-16 bg-blue-50 dark:bg-gray-900">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
            <h2 class="text-3xl font-bold text-center text-gray-900 dark:text-white mb-12">
                ทำไมเลือก<span class="text-blue-600 dark:text-blue-400">ไทยพร๊อม</span>?
            </h2>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                <div class="p-6 bg-white dark:bg-gray-800 rounded-xl shadow-sm">
                    <div class="text-3xl mb-3">⏰</div>
                    <h3 class="font-bold text-gray-900 dark:text-white mb-2">เลือกเวลาเอง</h3>
                    <p class="text-gray-600 dark:text-gray-400 text-sm">ทำเวลาไหนก็ได้ ไม่มีบังคับ เปิด-ปิดรับงานตามใจ</p>
                </div>

                <div class="p-6 bg-white dark:bg-gray-800 rounded-xl shadow-sm">
                    <div class="text-3xl mb-3">💵</div>
                    <h3 class="font-bold text-gray-900 dark:text-white mb-2">รายได้ดี</h3>
                    <p class="text-gray-600 dark:text-gray-400 text-sm">ค่าส่ง + ค่าบริการ + โบนัสเป้า + MLM Cashback</p>
                </div>

                <div class="p-6 bg-white dark:bg-gray-800 rounded-xl shadow-sm">
                    <div class="text-3xl mb-3">📍</div>
                    <h3 class="font-bold text-gray-900 dark:text-white mb-2">งานใกล้บ้าน</h3>
                    <p class="text-gray-600 dark:text-gray-400 text-sm">ระบบจับคู่งานใกล้ตำแหน่งคุณ ไม่ต้องวิ่งไกล</p>
                </div>

                <div class="p-6 bg-white dark:bg-gray-800 rounded-xl shadow-sm">
                    <div class="text-3xl mb-3">🛡️</div>
                    <h3 class="font-bold text-gray-900 dark:text-white mb-2">ค่าประกันคืนได้</h3>
                    <p class="text-gray-600 dark:text-gray-400 text-sm">ค่าประกันงานแรกเข้า ขอคืนได้เมื่อหยุดทำงาน</p>
                </div>

                <div class="p-6 bg-white dark:bg-gray-800 rounded-xl shadow-sm">
                    <div class="text-3xl mb-3">📱</div>
                    <h3 class="font-bold text-gray-900 dark:text-white mb-2">จัดการผ่าน LINE</h3>
                    <p class="text-gray-600 dark:text-gray-400 text-sm">รับงาน อัพเดทสถานะ ดูรายได้ ทุกอย่างผ่าน LINE</p>
                </div>

                <div class="p-6 bg-white dark:bg-gray-800 rounded-xl shadow-sm">
                    <div class="text-3xl mb-3">⭐</div>
                    <h3 class="font-bold text-gray-900 dark:text-white mb-2">สร้างชื่อเสียง</h3>
                    <p class="text-gray-600 dark:text-gray-400 text-sm">สะสมรีวิว เรตติ้ง ลูกค้าประจำ → งานเข้าสม่ำเสมอ</p>
                </div>
            </div>
        </div>
    </section>

    {{-- CTA Section --}}
    <section class="py-20 bg-gradient-to-r from-blue-600 to-indigo-700 dark:from-blue-800 dark:to-indigo-900">
        <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
            <div class="text-5xl mb-6">🚀</div>
            <h2 class="text-3xl md:text-4xl font-bold text-white mb-4">พร้อมเริ่มสร้างรายได้แล้วหรือยัง?</h2>
            <p class="text-lg text-blue-100 mb-8">
                เพิ่มเพื่อน LINE ตลาดสด แล้วพิมพ์ "สมัครไรเดอร์" เริ่มรับงานได้ทันที!
            </p>
            <a href="{{ config('services.line.fresh_market_add_friend_url', env('LINE_FRESH_MARKET_ADD_FRIEND_URL', '#')) }}"
               target="_blank"
               rel="noopener noreferrer"
               class="inline-flex items-center gap-3 px-10 py-5 bg-[#06C755] hover:bg-[#05b34d] text-white font-bold text-xl rounded-2xl shadow-2xl shadow-black/20 hover:shadow-black/30 transition-all hover:-translate-y-1">
                <svg class="w-8 h-8" viewBox="0 0 24 24" fill="currentColor">
                    <path d="M19.365 9.863c.349 0 .63.285.63.631 0 .345-.281.63-.63.63H17.61v1.125h1.755c.349 0 .63.283.63.63 0 .344-.281.629-.63.629h-2.386c-.345 0-.627-.285-.627-.629V8.108c0-.345.282-.63.63-.63h2.386c.346 0 .627.285.627.63 0 .349-.281.63-.63.63H17.61v1.125h1.755zm-3.855 3.016c0 .27-.174.51-.432.596-.064.021-.133.031-.199.031-.211 0-.391-.09-.51-.25l-2.443-3.317v2.94c0 .344-.279.629-.631.629-.346 0-.626-.285-.626-.629V8.108c0-.27.173-.51.43-.595.06-.023.136-.033.194-.033.195 0 .375.104.495.254l2.462 3.33V8.108c0-.345.282-.63.63-.63.345 0 .63.285.63.63v4.771zm-5.741 0c0 .344-.282.629-.631.629-.345 0-.627-.285-.627-.629V8.108c0-.345.282-.63.63-.63.346 0 .628.285.628.63v4.771zm-2.466.629H4.917c-.345 0-.63-.285-.63-.629V8.108c0-.345.285-.63.63-.63.348 0 .63.285.63.63v4.141h1.756c.348 0 .629.283.629.63 0 .344-.282.629-.629.629M24 10.314C24 4.943 18.615.572 12 .572S0 4.943 0 10.314c0 4.811 4.27 8.842 10.035 9.608.391.082.923.258 1.058.59.12.301.079.766.038 1.08l-.164 1.02c-.045.301-.24 1.186 1.049.645 1.291-.539 6.916-4.078 9.436-6.975C23.176 14.393 24 12.458 24 10.314"/>
                </svg>
                สมัครเป็นไรเดอร์/ช่าง
            </a>
            <p class="text-blue-200 text-sm mt-4">มีค่าประกันงานแรกเข้า (ขอคืนได้)</p>
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
                <a href="{{ route('taladsod.landing.seller') }}"
                   class="px-6 py-3 bg-white dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl text-gray-700 dark:text-gray-300 hover:border-orange-500 hover:text-orange-600 dark:hover:text-orange-400 transition-all">
                    🏪 เปิดร้านขาย/บริการ
                </a>
            </div>
        </div>
    </section>
</div>
@endsection
