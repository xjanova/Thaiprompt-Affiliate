@extends('layouts.app')

@section('title', 'หน้าแรก')

@section('content')

    <!-- Premium Landing Page Sections -->
    @if($premiumSections['hero'])

        <!-- Hero Section - แบบ Premium อลังการ -->
        <section class="relative min-h-screen flex items-center justify-center overflow-hidden bg-gradient-to-br from-indigo-600 via-purple-600 to-pink-600">
            <!-- Animated Background Elements -->
            <div class="absolute inset-0 overflow-hidden">
                <div class="absolute w-96 h-96 bg-white opacity-10 rounded-full -top-20 -left-20 animate-pulse"></div>
                <div class="absolute w-80 h-80 bg-white opacity-10 rounded-full top-40 right-20 animate-pulse" style="animation-delay: 1s;"></div>
                <div class="absolute w-64 h-64 bg-white opacity-10 rounded-full bottom-20 left-1/3 animate-pulse" style="animation-delay: 2s;"></div>
            </div>

            <div class="relative z-10 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-24 text-center text-white">
                <div class="animate-fade-in-up">
                    <h1 class="text-5xl md:text-7xl font-bold mb-6 leading-tight">
                        สร้างรายได้ไม่จำกัด<br>
                        <span class="text-transparent bg-clip-text bg-gradient-to-r from-yellow-300 to-pink-300">
                            กับระบบ Affiliate
                        </span>
                    </h1>
                    <p class="text-xl md:text-2xl mb-8 text-indigo-100 max-w-3xl mx-auto">
                        แพลตฟอร์ม Affiliate Marketing ที่ทันสมัย มั่นคง จ่ายจริง พร้อมระบบจัดการครบครัน
                    </p>

                    <!-- CTA Buttons -->
                    <div class="flex flex-col sm:flex-row gap-4 justify-center mb-12">
                        @guest
                            <a href="{{ route('register') }}" class="group relative inline-flex items-center justify-center px-8 py-4 bg-white text-indigo-600 font-bold rounded-xl shadow-2xl hover:shadow-white/50 transition-all duration-300 transform hover:scale-105">
                                <span class="relative z-10">เริ่มต้นฟรีวันนี้</span>
                                <svg class="w-5 h-5 ml-2 group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"></path>
                                </svg>
                            </a>
                            <a href="{{ route('login') }}" class="inline-flex items-center justify-center px-8 py-4 bg-white/10 backdrop-blur-sm text-white font-bold rounded-xl border-2 border-white/30 hover:bg-white/20 transition-all duration-300 transform hover:scale-105">
                                เข้าสู่ระบบ
                            </a>
                        @else
                            <a href="{{ route(Auth::user()->is_admin ? 'admin.dashboard' : 'user.dashboard') }}" class="inline-flex items-center justify-center px-8 py-4 bg-white text-indigo-600 font-bold rounded-xl shadow-2xl hover:shadow-white/50 transition-all duration-300 transform hover:scale-105">
                                เข้าสู่แดชบอร์ด
                            </a>
                        @endguest
                    </div>

                    <!-- Trust Badges -->
                    <div class="flex flex-wrap items-center justify-center gap-8 text-white/80">
                        <div class="flex items-center gap-2">
                            <svg class="w-6 h-6 text-green-400" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                            </svg>
                            <span>ปลอดภัย 100%</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <svg class="w-6 h-6 text-green-400" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                            </svg>
                            <span>จ่ายรายได้ทุกสัปดาห์</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <svg class="w-6 h-6 text-green-400" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                            </svg>
                            <span>ไม่มีค่าใช้จ่ายแอบแฝง</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Scroll Down Arrow -->
            <div class="absolute bottom-8 left-1/2 transform -translate-x-1/2 animate-bounce">
                <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3"></path>
                </svg>
            </div>
        </section>
    @endif

    @if($premiumSections['statistics'])
        <!-- Live Statistics Section - แสดงสถิติแบบ Real-time -->
        <section class="py-20 bg-white relative overflow-hidden">
            <div class="absolute inset-0 bg-gradient-to-br from-indigo-50 to-purple-50 opacity-50"></div>

            <div class="relative z-10 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="text-center mb-16">
                    <h2 class="text-4xl md:text-5xl font-bold text-gray-900 mb-4">
                        ตัวเลxที่พิสูจน์ความสำเร็จ
                    </h2>
                    <p class="text-xl text-gray-600">ข้อมูลสถิติแบบเรียลไทม์ จากผู้ใช้งานจริง</p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8">
                    <!-- Total Earnings -->
                    <div class="bg-white rounded-2xl shadow-xl p-8 border border-gray-100 transform hover:scale-105 transition duration-300">
                        <div class="flex items-center justify-between mb-4">
                            <div class="w-14 h-14 bg-gradient-to-br from-green-400 to-emerald-600 rounded-xl flex items-center justify-center text-white text-2xl">
                                💰
                            </div>
                            @if($stats['user_growth'] > 0)
                                <span class="px-3 py-1 bg-green-100 text-green-800 rounded-full text-sm font-semibold">
                                    +{{ number_format($stats['user_growth'], 0) }}%
                                </span>
                            @endif
                        </div>
                        <div class="text-3xl md:text-4xl font-bold text-gray-900 mb-2" data-counter="{{ $stats['total_earnings'] }}">
                            ฿0
                        </div>
                        <p class="text-gray-600 font-medium">รายได้ที่จ่ายไปแล้ว</p>
                        <p class="text-sm text-gray-500 mt-1">เดือนนี้ ฿{{ number_format($stats['this_month_earnings'], 0) }}</p>
                    </div>

                    <!-- Total Users -->
                    <div class="bg-white rounded-2xl shadow-xl p-8 border border-gray-100 transform hover:scale-105 transition duration-300">
                        <div class="flex items-center justify-between mb-4">
                            <div class="w-14 h-14 bg-gradient-to-br from-blue-400 to-indigo-600 rounded-xl flex items-center justify-center text-white text-2xl">
                                👥
                            </div>
                        </div>
                        <div class="text-3xl md:text-4xl font-bold text-gray-900 mb-2" data-counter="{{ $stats['total_users'] }}">
                            0
                        </div>
                        <p class="text-gray-600 font-medium">สมาชิกทั้งหมด</p>
                        <p class="text-sm text-gray-500 mt-1">{{ $stats['total_affiliates'] }} Affiliates</p>
                    </div>

                    <!-- Success Rate -->
                    <div class="bg-white rounded-2xl shadow-xl p-8 border border-gray-100 transform hover:scale-105 transition duration-300">
                        <div class="flex items-center justify-between mb-4">
                            <div class="w-14 h-14 bg-gradient-to-br from-purple-400 to-pink-600 rounded-xl flex items-center justify-center text-white text-2xl">
                                📈
                            </div>
                        </div>
                        <div class="text-3xl md:text-4xl font-bold text-gray-900 mb-2" data-counter="{{ $stats['success_rate'] }}">
                            0
                        </div>
                        <p class="text-gray-600 font-medium">อัตราความสำเร็จ</p>
                        <p class="text-sm text-gray-500 mt-1">Affiliates ที่มีรายได้</p>
                    </div>

                    <!-- Total Commissions -->
                    <div class="bg-white rounded-2xl shadow-xl p-8 border border-gray-100 transform hover:scale-105 transition duration-300">
                        <div class="flex items-center justify-between mb-4">
                            <div class="w-14 h-14 bg-gradient-to-br from-yellow-400 to-orange-600 rounded-xl flex items-center justify-center text-white text-2xl">
                                🎯
                            </div>
                        </div>
                        <div class="text-3xl md:text-4xl font-bold text-gray-900 mb-2" data-counter="{{ $stats['total_commissions'] }}">
                            0
                        </div>
                        <p class="text-gray-600 font-medium">คอมมิชชั่นจ่ายแล้ว</p>
                        <p class="text-sm text-gray-500 mt-1">เดือนนี้ {{ $stats['this_month_commissions'] }} รายการ</p>
                    </div>
                </div>

                <!-- Average Earnings Highlight -->
                <div class="mt-12 bg-gradient-to-r from-indigo-600 to-purple-600 rounded-2xl shadow-2xl p-8 text-white text-center">
                    <p class="text-lg mb-2 text-indigo-200">รายได้เฉลี่ยต่อ Affiliate</p>
                    <p class="text-5xl md:text-6xl font-bold mb-2">
                        ฿<span data-counter="{{ $stats['avg_earnings'] }}">0</span>
                    </p>
                    <p class="text-indigo-200">คุณก็สามารถทำได้เช่นกัน!</p>
                </div>
            </div>
        </section>
    @endif

    @if($premiumSections['features'])
        <!-- Features Section - คุณสมบัติเด่น -->
        <section class="py-20 bg-gray-50">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="text-center mb-16">
                    <h2 class="text-4xl md:text-5xl font-bold text-gray-900 mb-4">
                        ทำไมต้องเลือกเรา?
                    </h2>
                    <p class="text-xl text-gray-600">คุณสมบัติที่ทำให้เราแตกต่างจากที่อื่น</p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                    <!-- Feature 1 -->
                    <div class="group bg-white p-8 rounded-2xl shadow-lg hover:shadow-2xl transition-all duration-300 transform hover:-translate-y-2">
                        <div class="w-16 h-16 bg-gradient-to-br from-indigo-500 to-purple-600 rounded-2xl flex items-center justify-center text-white text-3xl mb-6 group-hover:scale-110 transition-transform">
                            🚀
                        </div>
                        <h3 class="text-2xl font-bold text-gray-900 mb-3">เริ่มต้นง่าย ภายใน 2 นาที</h3>
                        <p class="text-gray-600 leading-relaxed">สมัครสมาชิก ยืนยันอีเมล และเริ่มแชร์ลิงก์ได้ทันที ไม่ต้องรอการอนุมัติ</p>
                    </div>

                    <!-- Feature 2 -->
                    <div class="group bg-white p-8 rounded-2xl shadow-lg hover:shadow-2xl transition-all duration-300 transform hover:-translate-y-2">
                        <div class="w-16 h-16 bg-gradient-to-br from-green-500 to-emerald-600 rounded-2xl flex items-center justify-center text-white text-3xl mb-6 group-hover:scale-110 transition-transform">
                            💵
                        </div>
                        <h3 class="text-2xl font-bold text-gray-900 mb-3">จ่ายรายได้รวดเร็ว</h3>
                        <p class="text-gray-600 leading-relaxed">รับเงินทุกสัปดาห์ โอนตรงบัญชีธนาคาร ไม่มีขั้นต่ำ จ่ายจริง ไม่มีกั๊ก</p>
                    </div>

                    <!-- Feature 3 -->
                    <div class="group bg-white p-8 rounded-2xl shadow-lg hover:shadow-2xl transition-all duration-300 transform hover:-translate-y-2">
                        <div class="w-16 h-16 bg-gradient-to-br from-blue-500 to-cyan-600 rounded-2xl flex items-center justify-center text-white text-3xl mb-6 group-hover:scale-110 transition-transform">
                            📊
                        </div>
                        <h3 class="text-2xl font-bold text-gray-900 mb-3">Dashboard แบบ Real-time</h3>
                        <p class="text-gray-600 leading-relaxed">ติดตามสถิติ รายได้ และยอดขายแบบเรียลไทม์ พร้อมกราฟวิเคราะห์ครบครัน</p>
                    </div>

                    <!-- Feature 4 -->
                    <div class="group bg-white p-8 rounded-2xl shadow-lg hover:shadow-2xl transition-all duration-300 transform hover:-translate-y-2">
                        <div class="w-16 h-16 bg-gradient-to-br from-purple-500 to-pink-600 rounded-2xl flex items-center justify-center text-white text-3xl mb-6 group-hover:scale-110 transition-transform">
                            🎯
                        </div>
                        <h3 class="text-2xl font-bold text-gray-900 mb-3">ระบบ Referral หลายระดับ</h3>
                        <p class="text-gray-600 leading-relaxed">รับคอมมิชชั่นจากทีมของคุณได้หลายชั้น สร้างรายได้แบบ Passive Income</p>
                    </div>

                    <!-- Feature 5 -->
                    <div class="group bg-white p-8 rounded-2xl shadow-lg hover:shadow-2xl transition-all duration-300 transform hover:-translate-y-2">
                        <div class="w-16 h-16 bg-gradient-to-br from-yellow-500 to-orange-600 rounded-2xl flex items-center justify-center text-white text-3xl mb-6 group-hover:scale-110 transition-transform">
                            🔐
                        </div>
                        <h3 class="text-2xl font-bold text-gray-900 mb-3">ปลอดภัย มั่นคง เชื่อถือได้</h3>
                        <p class="text-gray-600 leading-relaxed">ระบบรักษาความปลอดภัยระดับธนาคาร SSL Encryption ข้อมูลของคุณปลอดภัย 100%</p>
                    </div>

                    <!-- Feature 6 -->
                    <div class="group bg-white p-8 rounded-2xl shadow-lg hover:shadow-2xl transition-all duration-300 transform hover:-translate-y-2">
                        <div class="w-16 h-16 bg-gradient-to-br from-red-500 to-pink-600 rounded-2xl flex items-center justify-center text-white text-3xl mb-6 group-hover:scale-110 transition-transform">
                            💬
                        </div>
                        <h3 class="text-2xl font-bold text-gray-900 mb-3">ซัพพอร์ตตลอด 24/7</h3>
                        <p class="text-gray-600 leading-relaxed">ทีมงานพร้อมช่วยเหลือคุณทุกเวลา ตอบคำถามรวดเร็ว มีคู่มือการใช้งานครบครัน</p>
                    </div>
                </div>
            </div>
        </section>
    @endif

    @if($premiumSections['leaderboard'] && $topAffiliates->count() > 0)
        <!-- Top Affiliates Leaderboard -->
        <section class="py-20 bg-white">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="text-center mb-16">
                    <h2 class="text-4xl md:text-5xl font-bold text-gray-900 mb-4">
                        🏆 Affiliates ยอดนิยม
                    </h2>
                    <p class="text-xl text-gray-600">ผู้ที่ประสบความสำเร็จกับเรา คุณก็ทำได้!</p>
                </div>

                <div class="max-w-4xl mx-auto space-y-4">
                    @foreach($topAffiliates as $index => $affiliate)
                    <div class="bg-gradient-to-r from-gray-50 to-white p-6 rounded-2xl shadow-lg hover:shadow-2xl transition-all duration-300 border border-gray-100">
                        <div class="flex items-center gap-6">
                            <!-- Rank Badge -->
                            <div class="flex-shrink-0">
                                @if($index === 0)
                                    <div class="w-16 h-16 bg-gradient-to-br from-yellow-400 to-yellow-600 rounded-2xl flex items-center justify-center text-white font-bold text-2xl shadow-xl">
                                        🥇
                                    </div>
                                @elseif($index === 1)
                                    <div class="w-16 h-16 bg-gradient-to-br from-gray-300 to-gray-500 rounded-2xl flex items-center justify-center text-white font-bold text-2xl shadow-xl">
                                        🥈
                                    </div>
                                @elseif($index === 2)
                                    <div class="w-16 h-16 bg-gradient-to-br from-orange-400 to-orange-600 rounded-2xl flex items-center justify-center text-white font-bold text-2xl shadow-xl">
                                        🥉
                                    </div>
                                @else
                                    <div class="w-16 h-16 bg-gradient-to-br from-indigo-500 to-purple-600 rounded-2xl flex items-center justify-center text-white font-bold text-2xl">
                                        {{ $index + 1 }}
                                    </div>
                                @endif
                            </div>

                            <!-- User Info -->
                            <div class="flex-1 min-w-0">
                                <h4 class="text-xl font-bold text-gray-900 truncate">{{ Str::mask($affiliate->user->name, '*', 2, -2) }}</h4>
                                <p class="text-gray-600">สมาชิกตั้งแต่ {{ $affiliate->created_at->format('M Y') }}</p>
                            </div>

                            <!-- Stats -->
                            <div class="text-right">
                                <p class="text-2xl font-bold text-green-600">฿{{ number_format($affiliate->total_earnings, 0) }}</p>
                                <p class="text-sm text-gray-500">{{ $affiliate->total_referrals }} Referrals</p>
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>

                <div class="text-center mt-12">
                    <p class="text-gray-600 text-lg mb-6">พวกเขาทำได้ คุณก็ทำได้เช่นกัน!</p>
                    @guest
                    <a href="{{ route('register') }}" class="inline-flex items-center px-8 py-4 bg-gradient-to-r from-indigo-600 to-purple-600 text-white font-bold rounded-xl shadow-xl hover:shadow-2xl transition-all duration-300 transform hover:scale-105">
                        เริ่มต้นสร้างรายได้วันนี้
                        <svg class="w-5 h-5 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"></path>
                        </svg>
                    </a>
                    @endguest
                </div>
            </div>
        </section>
    @endif

    @if($premiumSections['how_it_works'])
        <!-- How It Works -->
        <section class="py-20 bg-gradient-to-br from-indigo-50 to-purple-50">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="text-center mb-16">
                    <h2 class="text-4xl md:text-5xl font-bold text-gray-900 mb-4">
                        วิธีการเริ่มต้น
                    </h2>
                    <p class="text-xl text-gray-600">ง่ายเพียง 3 ขั้นตอน</p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-12">
                    <!-- Step 1 -->
                    <div class="text-center">
                        <div class="relative inline-block mb-6">
                            <div class="w-24 h-24 bg-gradient-to-br from-indigo-500 to-purple-600 rounded-full flex items-center justify-center text-white text-4xl shadow-2xl">
                                1
                            </div>
                            <div class="absolute -top-2 -right-2 w-8 h-8 bg-green-500 rounded-full flex items-center justify-center text-white">
                                ✓
                            </div>
                        </div>
                        <h3 class="text-2xl font-bold text-gray-900 mb-3">สมัครสมาชิก</h3>
                        <p class="text-gray-600 text-lg">กรอกข้อมูลเพียงไม่กี่ช่อง ยืนยันอีเมล และเริ่มใช้งานได้ทันที</p>
                    </div>

                    <!-- Step 2 -->
                    <div class="text-center">
                        <div class="relative inline-block mb-6">
                            <div class="w-24 h-24 bg-gradient-to-br from-purple-500 to-pink-600 rounded-full flex items-center justify-center text-white text-4xl shadow-2xl">
                                2
                            </div>
                        </div>
                        <h3 class="text-2xl font-bold text-gray-900 mb-3">แชร์ลิงก์</h3>
                        <p class="text-gray-600 text-lg">คัดลอกลิงก์ของคุณ แชร์ไปยัง Social Media, Line, Facebook หรือช่องทางต่างๆ</p>
                    </div>

                    <!-- Step 3 -->
                    <div class="text-center">
                        <div class="relative inline-block mb-6">
                            <div class="w-24 h-24 bg-gradient-to-br from-green-500 to-emerald-600 rounded-full flex items-center justify-center text-white text-4xl shadow-2xl">
                                3
                            </div>
                        </div>
                        <h3 class="text-2xl font-bold text-gray-900 mb-3">รับเงิน</h3>
                        <p class="text-gray-600 text-lg">เมื่อมีคนสมัครผ่านลิงก์คุณ คุณจะได้รับคอมมิชชั่นทันที รับเงินทุกสัปดาห์</p>
                    </div>
                </div>
            </div>
        </section>
    @endif

    @if($premiumSections['faq'])
        <!-- FAQ Section -->
        <section class="py-20 bg-white">
            <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="text-center mb-16">
                    <h2 class="text-4xl md:text-5xl font-bold text-gray-900 mb-4">
                        คำถามที่พบบ่อย
                    </h2>
                    <p class="text-xl text-gray-600">คำตอบสำหรับคำถามที่คุณอาจสงสัย</p>
                </div>

                <div class="space-y-4" x-data="{ openFaq: 0 }">
                    <!-- FAQ 1 -->
                    <div class="bg-gray-50 rounded-xl overflow-hidden border border-gray-200">
                        <button @click="openFaq = openFaq === 1 ? 0 : 1" class="w-full px-6 py-5 text-left flex items-center justify-between hover:bg-gray-100 transition">
                            <span class="text-lg font-semibold text-gray-900">ต้องมีเงินลงทุนหรือไม่?</span>
                            <svg class="w-6 h-6 text-gray-600 transform transition-transform" :class="{ 'rotate-180': openFaq === 1 }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                            </svg>
                        </button>
                        <div x-show="openFaq === 1" x-collapse class="px-6 pb-5 text-gray-600">
                            <p>ไม่ต้องลงทุนเลยแม้แต่บาทเดียว! สมัครฟรี ไม่มีค่าใช้จ่ายแอบแฝง เริ่มต้นได้ทันทีหลังสมัคร</p>
                        </div>
                    </div>

                    <!-- FAQ 2 -->
                    <div class="bg-gray-50 rounded-xl overflow-hidden border border-gray-200">
                        <button @click="openFaq = openFaq === 2 ? 0 : 2" class="w-full px-6 py-5 text-left flex items-center justify-between hover:bg-gray-100 transition">
                            <span class="text-lg font-semibold text-gray-900">ได้รับเงินจริงหรือไม่?</span>
                            <svg class="w-6 h-6 text-gray-600 transform transition-transform" :class="{ 'rotate-180': openFaq === 2 }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                            </svg>
                        </button>
                        <div x-show="openFaq === 2" x-collapse class="px-6 pb-5 text-gray-600">
                            <p>ได้รับเงินจริง 100%! เราจ่ายรายได้ทุกสัปดาห์ โอนตรงบัญชีธนาคารของคุณ มีหลักฐานการจ่ายเงินให้ตรวจสอบได้</p>
                        </div>
                    </div>

                    <!-- FAQ 3 -->
                    <div class="bg-gray-50 rounded-xl overflow-hidden border border-gray-200">
                        <button @click="openFaq = openFaq === 3 ? 0 : 3" class="w-full px-6 py-5 text-left flex items-center justify-between hover:bg-gray-100 transition">
                            <span class="text-lg font-semibold text-gray-900">มีขั้นต่ำในการถอนเงินหรือไม่?</span>
                            <svg class="w-6 h-6 text-gray-600 transform transition-transform" :class="{ 'rotate-180': openFaq === 3 }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                            </svg>
                        </button>
                        <div x-show="openFaq === 3" x-collapse class="px-6 pb-5 text-gray-600">
                            <p>ไม่มีขั้นต่ำ! คุณสามารถถอนเงินได้ทันทีเมื่อมีรายได้ในระบบ โดยจะจ่ายทุกสัปดาห์ตามรอบที่กำหนด</p>
                        </div>
                    </div>

                    <!-- FAQ 4 -->
                    <div class="bg-gray-50 rounded-xl overflow-hidden border border-gray-200">
                        <button @click="openFaq = openFaq === 4 ? 0 : 4" class="w-full px-6 py-5 text-left flex items-center justify-between hover:bg-gray-100 transition">
                            <span class="text-lg font-semibold text-gray-900">รับเปอร์เซ็นต์คอมมิชชั่นเท่าไหร่?</span>
                            <svg class="w-6 h-6 text-gray-600 transform transition-transform" :class="{ 'rotate-180': openFaq === 4 }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                            </svg>
                        </button>
                        <div x-show="openFaq === 4" x-collapse class="px-6 pb-5 text-gray-600">
                            <p>อัตราคอมมิชชั่นขึ้นอยู่กับแพ็กเกจและระดับของคุณ ยิ่งมียอดขายมาก ยิ่งได้เปอร์เซ็นต์สูง และยังมีคอมมิชชั่นจากทีมงานหลายชั้นอีกด้วย</p>
                        </div>
                    </div>

                    <!-- FAQ 5 -->
                    <div class="bg-gray-50 rounded-xl overflow-hidden border border-gray-200">
                        <button @click="openFaq = openFaq === 5 ? 0 : 5" class="w-full px-6 py-5 text-left flex items-center justify-between hover:bg-gray-100 transition">
                            <span class="text-lg font-semibold text-gray-900">ต้องใช้เวลานานแค่ไหนถึงจะเห็นรายได้?</span>
                            <svg class="w-6 h-6 text-gray-600 transform transition-transform" :class="{ 'rotate-180': openFaq === 5 }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                            </svg>
                        </button>
                        <div x-show="openFaq === 5" x-collapse class="px-6 pb-5 text-gray-600">
                            <p>ขึ้นอยู่กับความตั้งใจของคุณ! บางคนเริ่มมีรายได้ในวันแรก บางคนใช้เวลา 1-2 สัปดาห์ สิ่งสำคัญคือการแชร์ลิงก์อย่างสม่ำเสมอและสร้างเครือข่าย</p>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    @endif

    @if($premiumSections['cta'])
        <!-- Final CTA Section -->
        <section class="relative py-20 overflow-hidden">
            <div class="absolute inset-0 bg-gradient-to-r from-indigo-600 via-purple-600 to-pink-600"></div>
            <div class="absolute inset-0 opacity-10">
                <div class="absolute w-96 h-96 bg-white rounded-full -top-20 -left-20 animate-pulse"></div>
                <div class="absolute w-80 h-80 bg-white rounded-full top-40 right-20 animate-pulse" style="animation-delay: 1s;"></div>
                <div class="absolute w-64 h-64 bg-white rounded-full bottom-20 left-1/3 animate-pulse" style="animation-delay: 2s;"></div>
            </div>

            <div class="relative z-10 max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center text-white">
                <h2 class="text-4xl md:text-5xl font-bold mb-6">พร้อมเริ่มต้นสร้างรายได้แล้วหรือยัง?</h2>
                <p class="text-xl md:text-2xl mb-8 text-indigo-100">
                    เข้าร่วมกับผู้ใช้งานหลายพันคนที่กำลังสร้างรายได้กับเราอยู่ตอนนี้
                </p>

                @guest
                    <a href="{{ route('register') }}" class="inline-flex items-center justify-center px-10 py-5 bg-white text-indigo-600 font-bold text-xl rounded-xl shadow-2xl hover:shadow-white/50 transition-all duration-300 transform hover:scale-105">
                        เริ่มต้นฟรีวันนี้
                        <svg class="w-6 h-6 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"></path>
                        </svg>
                    </a>
                    <p class="mt-6 text-indigo-200">ไม่เสียค่าใช้จ่าย • ไม่ต้องใช้บัตรเครดิต • เริ่มได้ทันที</p>
                @else
                    <a href="{{ route(Auth::user()->is_admin ? 'admin.dashboard' : 'user.dashboard') }}" class="inline-flex items-center justify-center px-10 py-5 bg-white text-indigo-600 font-bold text-xl rounded-xl shadow-2xl hover:shadow-white/50 transition-all duration-300 transform hover:scale-105">
                        เข้าสู่แดชบอร์ด
                        <svg class="w-6 h-6 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"></path>
                        </svg>
                    </a>
                @endguest
            </div>
        </section>
    @endif
    <!-- End Premium Landing Page -->

@push('scripts')
<script>
// Animated Counter
document.addEventListener('DOMContentLoaded', function() {
    const counters = document.querySelectorAll('[data-counter]');

    const animateCounter = (el) => {
        const target = parseFloat(el.getAttribute('data-counter'));
        const duration = 2000; // 2 seconds
        const steps = 60;
        const increment = target / steps;
        const delay = duration / steps;
        let current = 0;

        const isPercentage = el.textContent.includes('%');
        const isCurrency = el.parentElement.textContent.includes('฿');

        const timer = setInterval(() => {
            current += increment;
            if (current >= target) {
                current = target;
                clearInterval(timer);
            }

            if (isCurrency) {
                el.textContent = current.toLocaleString('th-TH', { maximumFractionDigits: 0 });
            } else if (isPercentage) {
                el.textContent = current.toFixed(0);
            } else {
                el.textContent = Math.floor(current).toLocaleString('th-TH');
            }
        }, delay);
    };

    // Intersection Observer for triggering animations when visible
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                animateCounter(entry.target);
                observer.unobserve(entry.target);
            }
        });
    }, { threshold: 0.5 });

    counters.forEach(counter => {
        observer.observe(counter);
    });
});

// Smooth scroll for anchor links
document.querySelectorAll('a[href^="#"]').forEach(anchor => {
    anchor.addEventListener('click', function (e) {
        e.preventDefault();
        const target = document.querySelector(this.getAttribute('href'));
        if (target) {
            target.scrollIntoView({
                behavior: 'smooth',
                block: 'start'
            });
        }
    });
});
</script>

<style>
@keyframes fade-in-up {
    from {
        opacity: 0;
        transform: translateY(30px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.animate-fade-in-up {
    animation: fade-in-up 1s ease-out;
}

@keyframes pulse {
    0%, 100% {
        opacity: 0.1;
        transform: scale(1);
    }
    50% {
        opacity: 0.2;
        transform: scale(1.1);
    }
}
</style>
@endpush
@endsection
