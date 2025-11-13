@extends('layouts.windows')

@section('title', 'หน้าแรก | Windows Theme')

@section('content')

    <!-- Premium Landing Page Sections -->
    @if($premiumSections['hero'])

        <!-- Hero Section - Premium Design with Logo -->
        <section class="relative min-h-screen flex items-center justify-center overflow-hidden bg-gradient-to-br from-purple-900 via-indigo-900 to-blue-900">
            <!-- Animated Background Elements -->
            <div class="absolute inset-0 overflow-hidden">
                <!-- Gradient Orbs with Glow -->
                <div class="absolute w-96 h-96 bg-gradient-to-r from-purple-500 to-pink-500 opacity-20 rounded-full -top-20 -left-20 animate-pulse blur-3xl"></div>
                <div class="absolute w-80 h-80 bg-gradient-to-r from-blue-500 to-cyan-500 opacity-20 rounded-full top-40 right-20 animate-pulse blur-3xl" style="animation-delay: 1s;"></div>
                <div class="absolute w-64 h-64 bg-gradient-to-r from-pink-500 to-purple-500 opacity-20 rounded-full bottom-20 left-1/3 animate-pulse blur-3xl" style="animation-delay: 2s;"></div>

                <!-- Floating Particles -->
                <div class="absolute top-20 left-10 w-2 h-2 bg-yellow-300 rounded-full animate-ping"></div>
                <div class="absolute top-40 right-20 w-3 h-3 bg-pink-300 rounded-full animate-ping" style="animation-delay: 0.5s;"></div>
                <div class="absolute bottom-40 left-20 w-2 h-2 bg-blue-300 rounded-full animate-ping" style="animation-delay: 1s;"></div>
                <div class="absolute bottom-20 right-40 w-3 h-3 bg-purple-300 rounded-full animate-ping" style="animation-delay: 1.5s;"></div>
            </div>

            <!-- Grid Pattern Overlay -->
            <div class="absolute inset-0 opacity-10" style="background-image: linear-gradient(rgba(255,255,255,.05) 1px, transparent 1px), linear-gradient(90deg, rgba(255,255,255,.05) 1px, transparent 1px); background-size: 50px 50px;"></div>

            <div class="relative z-10 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-24 text-center text-white">
                <!-- Logo Section -->
                <div class="mb-12 animate-fade-in-down">
                    <div class="inline-block p-8 bg-white/10 backdrop-blur-xl rounded-3xl border-2 border-white/20 shadow-2xl transform hover:scale-105 transition-all duration-500">
                        <img src="{{ asset('images/logo.svg') }}" alt="Logo" class="w-32 h-32 mx-auto filter drop-shadow-2xl">
                    </div>
                </div>

                <div class="animate-fade-in-up">
                    <h1 class="text-5xl md:text-7xl font-bold mb-6 leading-tight">
                        <span class="text-transparent bg-clip-text bg-gradient-to-r from-yellow-300 via-pink-300 to-purple-300 animate-pulse">
                            สร้างรายได้ไม่จำกัด
                        </span><br>
                        <span class="text-white drop-shadow-2xl">กับระบบ Affiliate ที่ทรงพลัง</span>
                    </h1>
                    <p class="text-xl md:text-2xl mb-12 text-indigo-200 max-w-3xl mx-auto leading-relaxed">
                        แพลตฟอร์มครบวงจร • มั่นคง • จ่ายจริง<br>
                        <span class="text-yellow-300 font-semibold">พร้อมระบบจัดการที่ทันสมัยที่สุด</span>
                    </p>

                    <!-- Feature Cards Grid -->
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-12 max-w-6xl mx-auto">
                        <!-- Affiliate Card -->
                        <a href="{{ route('user.dashboard') }}" class="group relative bg-gradient-to-br from-purple-600/30 to-pink-600/30 backdrop-blur-xl border border-white/20 rounded-2xl p-6 transform hover:scale-105 hover:-translate-y-2 transition-all duration-300 hover:shadow-2xl hover:shadow-purple-500/50 cursor-pointer">
                            <div class="absolute inset-0 bg-gradient-to-br from-purple-600 to-pink-600 opacity-0 group-hover:opacity-20 rounded-2xl transition-opacity duration-300"></div>
                            <div class="relative z-10">
                                <div class="text-5xl mb-4 group-hover:scale-110 transition-transform">💼</div>
                                <h3 class="text-xl font-bold mb-2 text-white">ระบบ Affiliate</h3>
                                <p class="text-purple-200 text-sm mb-4">หาเงินออนไลน์ รายได้ไม่จำกัด</p>
                                <div class="flex items-center justify-center text-yellow-300 font-semibold group-hover:gap-2 transition-all">
                                    <span>เริ่มเลย</span>
                                    <svg class="w-4 h-4 ml-1 group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                                    </svg>
                                </div>
                            </div>
                        </a>

                        <!-- Software Purchase Card - NEW -->
                        <a href="{{ route('software.products.index') }}" class="group relative bg-gradient-to-br from-blue-600/30 to-cyan-600/30 backdrop-blur-xl border border-white/20 rounded-2xl p-6 transform hover:scale-105 hover:-translate-y-2 transition-all duration-300 hover:shadow-2xl hover:shadow-blue-500/50 cursor-pointer">
                            <div class="absolute inset-0 bg-gradient-to-br from-blue-600 to-cyan-600 opacity-0 group-hover:opacity-20 rounded-2xl transition-opacity duration-300"></div>
                            <div class="absolute -top-2 -right-2 bg-gradient-to-r from-yellow-400 to-orange-500 text-white text-xs font-bold px-3 py-1 rounded-full shadow-lg animate-pulse">
                                ใหม่!
                            </div>
                            <div class="relative z-10">
                                <div class="text-5xl mb-4 group-hover:scale-110 transition-transform">🛒</div>
                                <h3 class="text-xl font-bold mb-2 text-white">ซื้อซอฟแวร์</h3>
                                <p class="text-blue-200 text-sm mb-4">ระบบครบวงจร ราคาคุ้มค่า</p>
                                <div class="flex items-center justify-center text-yellow-300 font-semibold group-hover:gap-2 transition-all">
                                    <span>ดูสินค้า</span>
                                    <svg class="w-4 h-4 ml-1 group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                                    </svg>
                                </div>
                            </div>
                        </a>

                        <!-- Tarot Card -->
                        <a href="{{ route('tarot.index') }}" class="group relative bg-gradient-to-br from-indigo-600/30 to-purple-600/30 backdrop-blur-xl border border-white/20 rounded-2xl p-6 transform hover:scale-105 hover:-translate-y-2 transition-all duration-300 hover:shadow-2xl hover:shadow-indigo-500/50 cursor-pointer">
                            <div class="absolute inset-0 bg-gradient-to-br from-indigo-600 to-purple-600 opacity-0 group-hover:opacity-20 rounded-2xl transition-opacity duration-300"></div>
                            <div class="relative z-10">
                                <div class="text-5xl mb-4 group-hover:scale-110 transition-transform">🔮</div>
                                <h3 class="text-xl font-bold mb-2 text-white">ทาโร่ต์ AI</h3>
                                <p class="text-indigo-200 text-sm mb-4">ทำนายดวงชะตา ฟรีทุกวัน</p>
                                <div class="flex items-center justify-center text-yellow-300 font-semibold group-hover:gap-2 transition-all">
                                    <span>ทำนายเลย</span>
                                    <svg class="w-4 h-4 ml-1 group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                                    </svg>
                                </div>
                            </div>
                        </a>

                        <!-- Platform Info Card -->
                        <a href="{{ route('about.professional') }}" class="group relative bg-gradient-to-br from-pink-600/30 to-orange-600/30 backdrop-blur-xl border border-white/20 rounded-2xl p-6 transform hover:scale-105 hover:-translate-y-2 transition-all duration-300 hover:shadow-2xl hover:shadow-pink-500/50 cursor-pointer">
                            <div class="absolute inset-0 bg-gradient-to-br from-pink-600 to-orange-600 opacity-0 group-hover:opacity-20 rounded-2xl transition-opacity duration-300"></div>
                            <div class="relative z-10">
                                <div class="text-5xl mb-4 group-hover:scale-110 transition-transform">📊</div>
                                <h3 class="text-xl font-bold mb-2 text-white">แพลตฟอร์ม</h3>
                                <p class="text-pink-200 text-sm mb-4">ข้อมูลระบบและฟีเจอร์</p>
                                <div class="flex items-center justify-center text-yellow-300 font-semibold group-hover:gap-2 transition-all">
                                    <span>ดูรายละเอียด</span>
                                    <svg class="w-4 h-4 ml-1 group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                                    </svg>
                                </div>
                            </div>
                        </a>
                    </div>

                    <!-- CTA Buttons -->
                    <div class="flex flex-col sm:flex-row gap-4 justify-center mb-12">
                        @guest
                            <a href="{{ route('register') }}" class="group relative inline-flex items-center justify-center px-10 py-5 bg-gradient-to-r from-yellow-400 via-pink-500 to-purple-600 text-white font-bold text-lg rounded-2xl shadow-2xl hover:shadow-purple-500/50 transition-all duration-300 transform hover:scale-105 overflow-hidden">
                                <div class="absolute inset-0 bg-gradient-to-r from-purple-600 via-pink-500 to-yellow-400 opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>
                                <span class="relative z-10">🚀 เริ่มต้นฟรีวันนี้</span>
                                <svg class="w-5 h-5 ml-2 relative z-10 group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"></path>
                                </svg>
                            </a>
                            <a href="{{ route('login') }}" class="inline-flex items-center justify-center px-10 py-5 bg-white/10 backdrop-blur-sm text-white font-bold text-lg rounded-2xl border-2 border-white/30 hover:bg-white/20 hover:border-white/50 transition-all duration-300 transform hover:scale-105">
                                เข้าสู่ระบบ
                            </a>
                        @else
                            <a href="{{ route(Auth::user()->is_admin ? 'admin.dashboard' : 'user.dashboard') }}" class="inline-flex items-center justify-center px-10 py-5 bg-gradient-to-r from-yellow-400 via-pink-500 to-purple-600 text-white font-bold text-lg rounded-2xl shadow-2xl hover:shadow-purple-500/50 transition-all duration-300 transform hover:scale-105">
                                🎯 เข้าสู่แดชบอร์ด
                            </a>
                        @endguest
                    </div>

                    <!-- 3D Presentation CTA -->
                    <div class="flex justify-center mb-8">
                        <a href="{{ route('presentation') }}" class="group relative inline-flex items-center justify-center px-8 py-4 bg-gradient-to-r from-cyan-500 via-blue-500 to-purple-500 text-white font-bold text-base rounded-xl shadow-lg hover:shadow-cyan-500/50 transition-all duration-300 transform hover:scale-105 overflow-hidden">
                            <div class="absolute inset-0 bg-gradient-to-r from-purple-500 via-blue-500 to-cyan-500 opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>
                            <span class="relative z-10">🎮 สำรวจด้วย 3D Interactive Presentation</span>
                            <svg class="w-5 h-5 ml-2 relative z-10 group-hover:rotate-12 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path>
                            </svg>
                        </a>
                    </div>

                    <!-- Trust Badges -->
                    <div class="flex flex-wrap items-center justify-center gap-8 text-white/90">
                        <div class="flex items-center gap-3 bg-white/10 backdrop-blur-sm px-5 py-3 rounded-full border border-white/20">
                            <svg class="w-6 h-6 text-green-400" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                            </svg>
                            <span class="font-semibold">ปลอดภัย 100%</span>
                        </div>
                        <div class="flex items-center gap-3 bg-white/10 backdrop-blur-sm px-5 py-3 rounded-full border border-white/20">
                            <svg class="w-6 h-6 text-green-400" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                            </svg>
                            <span class="font-semibold">จ่ายรายได้ทุกสัปดาห์</span>
                        </div>
                        <div class="flex items-center gap-3 bg-white/10 backdrop-blur-sm px-5 py-3 rounded-full border border-white/20">
                            <svg class="w-6 h-6 text-green-400" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                            </svg>
                            <span class="font-semibold">ไม่มีค่าใช้จ่ายแอบแฝง</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Scroll Down Arrow -->
            <div class="absolute bottom-8 left-1/2 transform -translate-x-1/2 animate-bounce">
                <div class="bg-white/10 backdrop-blur-sm p-3 rounded-full border border-white/20">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3"></path>
                    </svg>
                </div>
            </div>
        </section>
    @endif

    <!-- Mystical Tarot Reading Section -->
    <section class="relative py-20 overflow-hidden bg-gradient-to-br from-purple-900 via-indigo-900 to-purple-800">
        <!-- Mystical Background Elements -->
        <div class="absolute inset-0 overflow-hidden opacity-20">
            <div class="absolute top-10 left-10 text-6xl animate-pulse" style="animation-delay: 0s;">✨</div>
            <div class="absolute top-20 right-20 text-5xl animate-pulse" style="animation-delay: 1s;">🌙</div>
            <div class="absolute bottom-20 left-20 text-6xl animate-pulse" style="animation-delay: 2s;">⭐</div>
            <div class="absolute bottom-32 right-32 text-4xl animate-pulse" style="animation-delay: 1.5s;">✨</div>
            <div class="absolute top-1/2 left-1/4 text-5xl animate-pulse" style="animation-delay: 0.5s;">🔮</div>
        </div>

        <!-- Animated Gradient Orbs -->
        <div class="absolute top-0 left-1/4 w-96 h-96 bg-purple-500 rounded-full mix-blend-multiply filter blur-3xl opacity-30 animate-blob"></div>
        <div class="absolute top-20 right-1/4 w-96 h-96 bg-indigo-500 rounded-full mix-blend-multiply filter blur-3xl opacity-30 animate-blob animation-delay-2000"></div>
        <div class="absolute bottom-0 left-1/3 w-96 h-96 bg-pink-500 rounded-full mix-blend-multiply filter blur-3xl opacity-30 animate-blob animation-delay-4000"></div>

        <div class="relative z-10 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-12">
                <!-- Badge -->
                <div class="inline-flex items-center gap-2 px-6 py-3 bg-gradient-to-r from-purple-500/30 to-pink-500/30 backdrop-blur-sm rounded-full border border-purple-300/30 mb-6 animate-fade-in-down">
                    <span class="text-2xl">🔮</span>
                    <span class="text-white font-semibold">เปิดเผยดวงชะตา ค้นหาคำตอบ</span>
                </div>

                <!-- Main Heading -->
                <h2 class="text-5xl md:text-7xl font-black mb-6 leading-tight">
                    <span class="text-transparent bg-clip-text bg-gradient-to-r from-yellow-300 via-pink-300 to-purple-300 animate-pulse">
                        ทาโร่ต์ AI
                    </span>
                    <br>
                    <span class="text-white">ทำนายดวงชะตาของคุณ</span>
                </h2>

                <p class="text-xl md:text-2xl text-purple-100 max-w-3xl mx-auto mb-8 leading-relaxed">
                    ระบบทำนายไพ่ทาโร่ต์ที่ทันสมัย พร้อมอนิเมชั่นสวยงาม
                    <br>
                    <span class="text-yellow-300 font-semibold">✨ ทำนายฟรีวันละ 1 ครั้งต่อหมวดหมู่!</span>
                </p>

                <!-- Feature Highlights - Clickable Cards -->
                <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-10 max-w-5xl mx-auto">
                    <a href="{{ route('tarot.index') }}" class="group relative bg-gradient-to-br from-pink-600/20 to-red-600/20 backdrop-blur-sm border border-white/20 rounded-2xl p-8 transform hover:scale-110 hover:-translate-y-3 transition-all duration-300 hover:shadow-2xl hover:shadow-pink-500/50 cursor-pointer">
                        <div class="absolute inset-0 bg-gradient-to-br from-pink-600 to-red-600 opacity-0 group-hover:opacity-20 rounded-2xl transition-opacity duration-300"></div>
                        <div class="relative z-10">
                            <div class="text-5xl mb-4 group-hover:scale-125 group-hover:rotate-12 transition-transform duration-300">💖</div>
                            <h3 class="text-white font-bold text-lg mb-2">ความรัก</h3>
                            <p class="text-purple-200 text-sm mb-3">เปิดเผยเรื่องราวหัวใจ</p>
                            <div class="flex items-center text-yellow-300 text-xs font-semibold">
                                <span>เริ่มทำนาย</span>
                                <svg class="w-3 h-3 ml-1 group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                                </svg>
                            </div>
                        </div>
                    </a>

                    <a href="{{ route('tarot.index') }}" class="group relative bg-gradient-to-br from-blue-600/20 to-indigo-600/20 backdrop-blur-sm border border-white/20 rounded-2xl p-8 transform hover:scale-110 hover:-translate-y-3 transition-all duration-300 hover:shadow-2xl hover:shadow-blue-500/50 cursor-pointer">
                        <div class="absolute inset-0 bg-gradient-to-br from-blue-600 to-indigo-600 opacity-0 group-hover:opacity-20 rounded-2xl transition-opacity duration-300"></div>
                        <div class="relative z-10">
                            <div class="text-5xl mb-4 group-hover:scale-125 group-hover:rotate-12 transition-transform duration-300">💼</div>
                            <h3 class="text-white font-bold text-lg mb-2">การงาน</h3>
                            <p class="text-purple-200 text-sm mb-3">มองหาโอกาสใหม่</p>
                            <div class="flex items-center text-yellow-300 text-xs font-semibold">
                                <span>เริ่มทำนาย</span>
                                <svg class="w-3 h-3 ml-1 group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                                </svg>
                            </div>
                        </div>
                    </a>

                    <a href="{{ route('tarot.index') }}" class="group relative bg-gradient-to-br from-green-600/20 to-emerald-600/20 backdrop-blur-sm border border-white/20 rounded-2xl p-8 transform hover:scale-110 hover:-translate-y-3 transition-all duration-300 hover:shadow-2xl hover:shadow-green-500/50 cursor-pointer">
                        <div class="absolute inset-0 bg-gradient-to-br from-green-600 to-emerald-600 opacity-0 group-hover:opacity-20 rounded-2xl transition-opacity duration-300"></div>
                        <div class="relative z-10">
                            <div class="text-5xl mb-4 group-hover:scale-125 group-hover:rotate-12 transition-transform duration-300">💰</div>
                            <h3 class="text-white font-bold text-lg mb-2">การเงิน</h3>
                            <p class="text-purple-200 text-sm mb-3">วางแผนทางการเงิน</p>
                            <div class="flex items-center text-yellow-300 text-xs font-semibold">
                                <span>เริ่มทำนาย</span>
                                <svg class="w-3 h-3 ml-1 group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                                </svg>
                            </div>
                        </div>
                    </a>

                    <a href="{{ route('tarot.index') }}" class="group relative bg-gradient-to-br from-yellow-600/20 to-orange-600/20 backdrop-blur-sm border border-white/20 rounded-2xl p-8 transform hover:scale-110 hover:-translate-y-3 transition-all duration-300 hover:shadow-2xl hover:shadow-yellow-500/50 cursor-pointer">
                        <div class="absolute inset-0 bg-gradient-to-br from-yellow-600 to-orange-600 opacity-0 group-hover:opacity-20 rounded-2xl transition-opacity duration-300"></div>
                        <div class="relative z-10">
                            <div class="text-5xl mb-4 group-hover:scale-125 group-hover:rotate-12 transition-transform duration-300">🌟</div>
                            <h3 class="text-white font-bold text-lg mb-2">ทั่วไป</h3>
                            <p class="text-purple-200 text-sm mb-3">คำทำนายรายวัน</p>
                            <div class="flex items-center text-yellow-300 text-xs font-semibold">
                                <span>เริ่มทำนาย</span>
                                <svg class="w-3 h-3 ml-1 group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                                </svg>
                            </div>
                        </div>
                    </a>
                </div>

                <!-- Main CTA Button -->
                <div class="relative inline-block mb-6">
                    <div class="absolute -inset-2 bg-gradient-to-r from-yellow-400 via-pink-500 to-purple-600 rounded-full blur-lg opacity-75 group-hover:opacity-100 transition duration-1000 animate-pulse"></div>
                    <a href="{{ route('tarot.index') }}" class="relative inline-flex items-center gap-4 px-12 py-6 bg-gradient-to-r from-yellow-400 via-pink-500 to-purple-600 text-white text-xl md:text-2xl font-bold rounded-full shadow-2xl hover:shadow-purple-500/50 transition-all duration-300 transform hover:scale-110 group">
                        <span class="text-3xl group-hover:rotate-12 transition-transform">🔮</span>
                        <span>เริ่มทำนายตอนนี้</span>
                        <svg class="w-7 h-7 group-hover:translate-x-2 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"></path>
                        </svg>
                    </a>
                </div>

                <!-- Trust Indicators -->
                <div class="flex flex-wrap items-center justify-center gap-6 text-purple-200">
                    <div class="flex items-center gap-2">
                        <svg class="w-5 h-5 text-yellow-300" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"></path>
                        </svg>
                        <span>ฟรีทุกวัน</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <svg class="w-5 h-5 text-yellow-300" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                        </svg>
                        <span>78 ใบทาโร่ต์แท้</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <svg class="w-5 h-5 text-yellow-300" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M11.3 1.046A1 1 0 0112 2v5h4a1 1 0 01.82 1.573l-7 10A1 1 0 018 18v-5H4a1 1 0 01-.82-1.573l7-10a1 1 0 011.12-.38z" clip-rule="evenodd"></path>
                        </svg>
                        <span>AI แปลผลอัตโนมัติ</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <svg class="w-5 h-5 text-yellow-300" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M9 2a1 1 0 000 2h2a1 1 0 100-2H9z"></path>
                            <path fill-rule="evenodd" d="M4 5a2 2 0 012-2 3 3 0 003 3h2a3 3 0 003-3 2 2 0 012 2v11a2 2 0 01-2 2H6a2 2 0 01-2-2V5zm3 4a1 1 0 000 2h.01a1 1 0 100-2H7zm3 0a1 1 0 000 2h3a1 1 0 100-2h-3zm-3 4a1 1 0 100 2h.01a1 1 0 100-2H7zm3 0a1 1 0 100 2h3a1 1 0 100-2h-3z" clip-rule="evenodd"></path>
                        </svg>
                        <span>บันทึกผลได้</span>
                    </div>
                </div>

                <!-- Sample Cards Preview -->
                <div class="mt-12 flex justify-center gap-4 opacity-80">
                    <div class="w-20 h-32 bg-gradient-to-br from-purple-600 to-pink-600 rounded-lg shadow-2xl transform -rotate-12 hover:rotate-0 transition-transform duration-300 flex items-center justify-center text-white text-3xl">
                        🌙
                    </div>
                    <div class="w-20 h-32 bg-gradient-to-br from-indigo-600 to-purple-600 rounded-lg shadow-2xl transform -rotate-6 hover:rotate-0 transition-transform duration-300 flex items-center justify-center text-white text-3xl">
                        ⭐
                    </div>
                    <div class="w-20 h-32 bg-gradient-to-br from-pink-600 to-purple-600 rounded-lg shadow-2xl transform rotate-0 hover:scale-110 transition-transform duration-300 flex items-center justify-center text-white text-4xl border-2 border-yellow-300">
                        🔮
                    </div>
                    <div class="w-20 h-32 bg-gradient-to-br from-purple-600 to-indigo-600 rounded-lg shadow-2xl transform rotate-6 hover:rotate-0 transition-transform duration-300 flex items-center justify-center text-white text-3xl">
                        ✨
                    </div>
                    <div class="w-20 h-32 bg-gradient-to-br from-pink-600 to-indigo-600 rounded-lg shadow-2xl transform rotate-12 hover:rotate-0 transition-transform duration-300 flex items-center justify-center text-white text-3xl">
                        💫
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Platform Overview Banner - Enhanced -->
    <section class="py-20 bg-gradient-to-br from-blue-900 via-indigo-900 to-purple-900 relative overflow-hidden">
        <!-- Animated Background -->
        <div class="absolute inset-0 overflow-hidden">
            <div class="absolute w-96 h-96 bg-gradient-to-r from-blue-500 to-cyan-500 opacity-20 rounded-full -top-20 -left-20 animate-pulse blur-3xl"></div>
            <div class="absolute w-80 h-80 bg-gradient-to-r from-purple-500 to-pink-500 opacity-20 rounded-full top-40 right-20 animate-pulse blur-3xl" style="animation-delay: 1s;"></div>
        </div>

        <!-- Grid Pattern -->
        <div class="absolute inset-0 opacity-5" style="background-image: url('data:image/svg+xml,%3Csvg width=\'60\' height=\'60\' viewBox=\'0 0 60 60\' xmlns=\'http://www.w3.org/2000/svg\'%3E%3Cg fill=\'none\' fill-rule=\'evenodd\'%3E%3Cg fill=\'%23ffffff\' fill-opacity=\'0.4\'%3E%3Cpath d=\'M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z\'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E');"></div>

        <div class="relative z-10 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <!-- Header -->
            <div class="text-center mb-12">
                <div class="inline-block mb-4 animate-fade-in-down">
                    <span class="px-6 py-3 bg-gradient-to-r from-blue-500/30 to-purple-500/30 backdrop-blur-sm rounded-full text-white font-semibold border border-white/30 shadow-lg">
                        🚀 Enterprise Platform
                    </span>
                </div>
                <h2 class="text-4xl md:text-5xl font-bold mb-4 text-white animate-fade-in-up">
                    แพลตฟอร์มระดับมืออาชีพ
                </h2>
                <p class="text-lg md:text-xl text-blue-200 max-w-3xl mx-auto">
                    สร้างด้วยเทคโนโลยีที่ทันสมัยที่สุด มีฟีเจอร์ครบครัน<br class="hidden md:block">
                    <span class="text-yellow-300 font-semibold">113+ Models • 105 Tables • 91 Controllers</span>
                </p>
            </div>

            <div class="grid md:grid-cols-2 gap-8 items-center">
                <!-- Features Grid -->
                <div class="grid grid-cols-2 gap-4">
                    <a href="{{ route('about.professional') }}" class="group bg-gradient-to-br from-purple-600/20 to-pink-600/20 backdrop-blur-xl border border-white/20 rounded-2xl p-6 transform hover:scale-105 hover:-translate-y-2 transition-all duration-300 hover:shadow-2xl hover:shadow-purple-500/50 cursor-pointer">
                        <div class="absolute inset-0 bg-gradient-to-br from-purple-600 to-pink-600 opacity-0 group-hover:opacity-20 rounded-2xl transition-opacity"></div>
                        <div class="relative z-10">
                            <div class="w-12 h-12 bg-white/10 rounded-xl flex items-center justify-center mb-3 text-2xl group-hover:scale-110 transition-transform">
                                🔄
                            </div>
                            <div class="font-bold text-white text-lg mb-1">MLM System</div>
                            <div class="text-sm text-blue-200">Unilevel & Binary</div>
                        </div>
                    </a>

                    <a href="{{ route('about.professional') }}" class="group bg-gradient-to-br from-blue-600/20 to-cyan-600/20 backdrop-blur-xl border border-white/20 rounded-2xl p-6 transform hover:scale-105 hover:-translate-y-2 transition-all duration-300 hover:shadow-2xl hover:shadow-blue-500/50 cursor-pointer">
                        <div class="absolute inset-0 bg-gradient-to-br from-blue-600 to-cyan-600 opacity-0 group-hover:opacity-20 rounded-2xl transition-opacity"></div>
                        <div class="relative z-10">
                            <div class="w-12 h-12 bg-white/10 rounded-xl flex items-center justify-center mb-3 text-2xl group-hover:scale-110 transition-transform">
                                🛍️
                            </div>
                            <div class="font-bold text-white text-lg mb-1">E-Commerce</div>
                            <div class="text-sm text-blue-200">Multi-Vendor</div>
                        </div>
                    </a>

                    <a href="{{ route('about.professional') }}" class="group bg-gradient-to-br from-green-600/20 to-emerald-600/20 backdrop-blur-xl border border-white/20 rounded-2xl p-6 transform hover:scale-105 hover:-translate-y-2 transition-all duration-300 hover:shadow-2xl hover:shadow-green-500/50 cursor-pointer">
                        <div class="absolute inset-0 bg-gradient-to-br from-green-600 to-emerald-600 opacity-0 group-hover:opacity-20 rounded-2xl transition-opacity"></div>
                        <div class="relative z-10">
                            <div class="w-12 h-12 bg-white/10 rounded-xl flex items-center justify-center mb-3 text-2xl group-hover:scale-110 transition-transform">
                                🤖
                            </div>
                            <div class="font-bold text-white text-lg mb-1">AI Integration</div>
                            <div class="text-sm text-blue-200">LINE Bot + RAG</div>
                        </div>
                    </a>

                    <a href="{{ route('about.professional') }}" class="group bg-gradient-to-br from-yellow-600/20 to-orange-600/20 backdrop-blur-xl border border-white/20 rounded-2xl p-6 transform hover:scale-105 hover:-translate-y-2 transition-all duration-300 hover:shadow-2xl hover:shadow-yellow-500/50 cursor-pointer">
                        <div class="absolute inset-0 bg-gradient-to-br from-yellow-600 to-orange-600 opacity-0 group-hover:opacity-20 rounded-2xl transition-opacity"></div>
                        <div class="relative z-10">
                            <div class="w-12 h-12 bg-white/10 rounded-xl flex items-center justify-center mb-3 text-2xl group-hover:scale-110 transition-transform">
                                ✅
                            </div>
                            <div class="font-bold text-white text-lg mb-1">Production Ready</div>
                            <div class="text-sm text-blue-200">v1.159.0</div>
                        </div>
                    </a>
                </div>

                <!-- Stats Panel -->
                <div class="bg-white/10 backdrop-blur-xl rounded-3xl p-8 border-2 border-white/20 shadow-2xl">
                    <h3 class="text-2xl font-bold text-white mb-6 text-center">Platform Statistics</h3>
                    <div class="space-y-4">
                        <div class="group bg-gradient-to-r from-white/5 to-white/10 rounded-xl p-4 hover:from-white/10 hover:to-white/15 transition-all duration-300 transform hover:scale-105">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 bg-purple-500/30 rounded-lg flex items-center justify-center text-xl">
                                        📦
                                    </div>
                                    <span class="font-semibold text-white">Database Models</span>
                                </div>
                                <span class="text-3xl font-bold text-transparent bg-clip-text bg-gradient-to-r from-yellow-300 to-pink-300">113+</span>
                            </div>
                        </div>

                        <div class="group bg-gradient-to-r from-white/5 to-white/10 rounded-xl p-4 hover:from-white/10 hover:to-white/15 transition-all duration-300 transform hover:scale-105">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 bg-blue-500/30 rounded-lg flex items-center justify-center text-xl">
                                        🗃️
                                    </div>
                                    <span class="font-semibold text-white">Database Tables</span>
                                </div>
                                <span class="text-3xl font-bold text-transparent bg-clip-text bg-gradient-to-r from-blue-300 to-cyan-300">105</span>
                            </div>
                        </div>

                        <div class="group bg-gradient-to-r from-white/5 to-white/10 rounded-xl p-4 hover:from-white/10 hover:to-white/15 transition-all duration-300 transform hover:scale-105">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 bg-green-500/30 rounded-lg flex items-center justify-center text-xl">
                                        ⚙️
                                    </div>
                                    <span class="font-semibold text-white">HTTP Controllers</span>
                                </div>
                                <span class="text-3xl font-bold text-transparent bg-clip-text bg-gradient-to-r from-green-300 to-emerald-300">91</span>
                            </div>
                        </div>

                        <div class="group bg-gradient-to-r from-white/5 to-white/10 rounded-xl p-4 hover:from-white/10 hover:to-white/15 transition-all duration-300 transform hover:scale-105">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 bg-pink-500/30 rounded-lg flex items-center justify-center text-xl">
                                        🔄
                                    </div>
                                    <span class="font-semibold text-white">Migrations</span>
                                </div>
                                <span class="text-3xl font-bold text-transparent bg-clip-text bg-gradient-to-r from-pink-300 to-purple-300">136</span>
                            </div>
                        </div>

                        <div class="text-center pt-4">
                            <span class="inline-flex items-center gap-2 px-6 py-3 bg-gradient-to-r from-green-500 to-emerald-500 text-white rounded-full text-sm font-bold shadow-lg animate-pulse">
                                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                                </svg>
                                Production Ready
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- CTA Button -->
            <div class="text-center mt-12">
                <a href="{{ route('about.professional') }}" class="group inline-flex items-center px-8 py-4 bg-gradient-to-r from-white to-blue-50 text-indigo-600 font-bold text-lg rounded-2xl shadow-2xl hover:shadow-white/30 transition-all duration-300 transform hover:scale-105">
                    <span>อ่านรายละเอียดทั้งหมด</span>
                    <svg class="w-5 h-5 ml-2 group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"></path>
                    </svg>
                </a>
            </div>
        </div>
    </section>

    @if($premiumSections['investment'] && $investmentPlans->count() > 0)
    <!-- Investment ROI Hero Section -->
    <section class="py-20 bg-gradient-to-br from-purple-900 via-pink-900 to-red-900 relative overflow-hidden">
        <!-- Animated Background -->
        <div class="absolute inset-0 opacity-20">
            <div class="absolute top-0 left-0 w-96 h-96 bg-yellow-500 rounded-full mix-blend-multiply filter blur-3xl animate-blob"></div>
            <div class="absolute top-0 right-0 w-96 h-96 bg-pink-500 rounded-full mix-blend-multiply filter blur-3xl animate-blob animation-delay-2000"></div>
            <div class="absolute bottom-0 left-1/2 w-96 h-96 bg-purple-500 rounded-full mix-blend-multiply filter blur-3xl animate-blob animation-delay-4000"></div>
        </div>

        <div class="relative z-10 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <!-- Section Header -->
            <div class="text-center mb-16">
                <div class="inline-flex items-center gap-2 px-6 py-3 bg-white/10 backdrop-blur-sm rounded-full border border-white/20 mb-6 animate-pulse">
                    <span class="relative flex h-3 w-3">
                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-green-400 opacity-75"></span>
                        <span class="relative inline-flex rounded-full h-3 w-3 bg-green-500"></span>
                    </span>
                    <span class="text-white font-semibold">🚀 ระบบลงทุนใหม่!</span>
                </div>

                <h2 class="text-5xl md:text-7xl font-black mb-6 leading-tight">
                    <span class="text-transparent bg-clip-text bg-gradient-to-r from-yellow-300 via-pink-300 to-purple-300">
                        ลงทุน ROI
                    </span>
                    <br>
                    <span class="text-white">รับผลตอบแทนเติบโตต่อเนื่อง</span>
                </h2>

                <p class="text-xl md:text-2xl text-pink-100 max-w-4xl mx-auto mb-12 leading-relaxed">
                    ระบบการลงทุนที่โปร่งใส ปลอดภัย จ่าย ROI อัตโนมัติทุกวัน
                    <br>
                    @if($investmentPlans->isNotEmpty())
                    <span class="text-yellow-300 font-semibold">เริ่มต้นเพียง ฿{{ number_format($investmentPlans->min('min_amount'), 0) }}</span>
                    @endif
                </p>

                <!-- Investment Stats -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 max-w-4xl mx-auto mb-12">
                    <div class="bg-white/10 backdrop-blur-sm border border-white/20 rounded-2xl p-6 transform hover:scale-105 transition-all">
                        <div class="text-4xl mb-2">💰</div>
                        <div class="text-3xl font-bold text-white mb-1">฿{{ number_format($stats['total_invested'] / 1000000, 1) }}M</div>
                        <div class="text-pink-200 text-sm">ลงทุนทั้งหมด</div>
                    </div>
                    <div class="bg-white/10 backdrop-blur-sm border border-white/20 rounded-2xl p-6 transform hover:scale-105 transition-all">
                        <div class="text-4xl mb-2">📈</div>
                        <div class="text-3xl font-bold text-yellow-300 mb-1">฿{{ number_format($stats['total_roi_paid'] / 1000000, 1) }}M</div>
                        <div class="text-pink-200 text-sm">ROI ที่จ่ายแล้ว</div>
                    </div>
                    <div class="bg-white/10 backdrop-blur-sm border border-white/20 rounded-2xl p-6 transform hover:scale-105 transition-all">
                        <div class="text-4xl mb-2">👥</div>
                        <div class="text-3xl font-bold text-green-300 mb-1">{{ number_format($stats['active_investors']) }}+</div>
                        <div class="text-pink-200 text-sm">นักลงทุนที่ใช้งาน</div>
                    </div>
                </div>

                <!-- CTA Buttons -->
                <div class="flex flex-col sm:flex-row gap-4 justify-center mb-8">
                    @auth
                        <a href="{{ route('user.investments.plans') }}" class="group relative inline-flex items-center justify-center px-10 py-5 bg-gradient-to-r from-yellow-400 to-orange-500 text-gray-900 text-lg font-bold rounded-xl shadow-2xl hover:shadow-yellow-500/50 transition-all duration-300 transform hover:scale-105">
                            <span class="relative z-10">ดูแผนการลงทุน</span>
                            <svg class="w-6 h-6 ml-2 group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"></path>
                            </svg>
                        </a>
                        <a href="{{ route('user.investments.index') }}" class="inline-flex items-center justify-center px-10 py-5 bg-white/10 backdrop-blur-sm text-white text-lg font-bold rounded-xl border-2 border-white/30 hover:bg-white/20 transition-all duration-300 transform hover:scale-105">
                            Dashboard การลงทุน
                        </a>
                    @else
                        <a href="{{ route('register') }}" class="group relative inline-flex items-center justify-center px-10 py-5 bg-gradient-to-r from-yellow-400 to-orange-500 text-gray-900 text-lg font-bold rounded-xl shadow-2xl hover:shadow-yellow-500/50 transition-all duration-300 transform hover:scale-105">
                            <span class="relative z-10">เริ่มลงทุนวันนี้</span>
                            <svg class="w-6 h-6 ml-2 group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"></path>
                            </svg>
                        </a>
                        <a href="{{ route('login') }}" class="inline-flex items-center justify-center px-10 py-5 bg-white/10 backdrop-blur-sm text-white text-lg font-bold rounded-xl border-2 border-white/30 hover:bg-white/20 transition-all duration-300 transform hover:scale-105">
                            เข้าสู่ระบบ
                        </a>
                    @endauth
                </div>

                <!-- Trust Indicators -->
                <div class="flex flex-wrap items-center justify-center gap-6 text-white/90">
                    <div class="flex items-center gap-2">
                        <svg class="w-5 h-5 text-green-400" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                        </svg>
                        <span class="text-sm">จ่าย ROI อัตโนมัติทุกวัน</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <svg class="w-5 h-5 text-green-400" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                        </svg>
                        <span class="text-sm">ถอนได้ตลอดเวลา</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <svg class="w-5 h-5 text-green-400" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                        </svg>
                        <span class="text-sm">โปร่งใส ตรวจสอบได้</span>
                    </div>
                </div>
            </div>

            <!-- Investment Plans Preview -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 max-w-7xl mx-auto">
                @foreach($investmentPlans->take(4) as $plan)
                <div class="group bg-white/10 backdrop-blur-lg border-2 border-white/20 rounded-2xl p-6 hover:bg-white/20 hover:border-yellow-400/50 transition-all duration-300 transform hover:scale-105 hover:-translate-y-2 {{ $plan->is_featured ? 'ring-2 ring-yellow-400' : '' }}">
                    @if($plan->is_featured)
                    <div class="mb-3">
                        <span class="px-3 py-1 bg-gradient-to-r from-yellow-400 to-orange-500 text-gray-900 text-xs font-bold rounded-full">
                            ⭐ แนะนำ
                        </span>
                    </div>
                    @endif

                    <div class="text-center mb-4">
                        <div class="text-5xl mb-3">{{ $plan->icon ?? '💎' }}</div>
                        <h3 class="text-2xl font-bold text-white mb-2">{{ $plan->display_name }}</h3>
                        <p class="text-pink-200 text-sm">{{ Str::limit($plan->display_description, 50) }}</p>
                    </div>

                    <div class="space-y-3 mb-6">
                        <div class="flex justify-between items-center text-sm">
                            <span class="text-pink-200">อัตรา ROI:</span>
                            <span class="text-yellow-300 font-bold text-lg">{{ $plan->roi_rate }}%</span>
                        </div>
                        <div class="flex justify-between items-center text-sm">
                            <span class="text-pink-200">ระยะเวลา:</span>
                            <span class="text-white font-semibold">{{ $plan->term_days }} วัน</span>
                        </div>
                        <div class="flex justify-between items-center text-sm">
                            <span class="text-pink-200">ขั้นต่ำ:</span>
                            <span class="text-white font-semibold">฿{{ number_format($plan->min_amount, 0) }}</span>
                        </div>
                    </div>

                    <a href="@auth{{ route('user.investments.plans.show', $plan) }}@else{{ route('register') }}@endauth"
                       class="block w-full px-4 py-3 bg-gradient-to-r from-purple-600 to-pink-600 group-hover:from-yellow-400 group-hover:to-orange-500 text-white group-hover:text-gray-900 rounded-lg font-bold text-center transition-all duration-300 shadow-lg">
                        ดูรายละเอียด
                    </a>
                </div>
                @endforeach
            </div>

            <!-- View All Plans Link -->
            @if($investmentPlans->count() > 4)
            <div class="text-center mt-12">
                <a href="@auth{{ route('user.investments.plans') }}@else{{ route('register') }}@endauth"
                   class="inline-flex items-center gap-2 px-8 py-4 bg-white/10 backdrop-blur-sm text-white font-bold rounded-xl border-2 border-white/30 hover:bg-white/20 transition-all duration-300">
                    <span>ดูแผนการลงทุนทั้งหมด</span>
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"></path>
                    </svg>
                </a>
            </div>
            @endif
        </div>
    </section>
    @endif

    <!-- Epic Wiki Hero Section - Knowledge Base Entry Point -->
    <section class="py-20 bg-gradient-to-br from-slate-900 via-purple-900 to-slate-900 relative overflow-hidden">
        <!-- Animated Grid Background -->
        <div class="absolute inset-0 opacity-20">
            <div class="absolute inset-0" style="background-image:
                linear-gradient(to right, rgba(147, 51, 234, 0.1) 1px, transparent 1px),
                linear-gradient(to bottom, rgba(147, 51, 234, 0.1) 1px, transparent 1px);
                background-size: 50px 50px;"></div>
        </div>

        <!-- Floating Orbs -->
        <div class="absolute top-20 left-10 w-72 h-72 bg-purple-500 rounded-full mix-blend-multiply filter blur-xl opacity-20 animate-blob"></div>
        <div class="absolute top-40 right-10 w-72 h-72 bg-pink-500 rounded-full mix-blend-multiply filter blur-xl opacity-20 animate-blob animation-delay-2000"></div>
        <div class="absolute -bottom-8 left-20 w-72 h-72 bg-indigo-500 rounded-full mix-blend-multiply filter blur-xl opacity-20 animate-blob animation-delay-4000"></div>

        <div class="relative z-10 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-12">
                <!-- Badge -->
                <div class="inline-flex items-center gap-2 px-6 py-3 bg-white/10 backdrop-blur-sm rounded-full border border-white/20 mb-8 animate-fade-in-down">
                    <span class="relative flex h-3 w-3">
                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-green-400 opacity-75"></span>
                        <span class="relative inline-flex rounded-full h-3 w-3 bg-green-500"></span>
                    </span>
                    <span class="text-white font-semibold">📚 สารานุกรมความรู้ฉบับสมบูรณ์</span>
                </div>

                <!-- Main Heading -->
                <h2 class="text-5xl md:text-7xl font-black mb-6 leading-tight">
                    <span class="text-transparent bg-clip-text bg-gradient-to-r from-white via-purple-200 to-pink-200">
                        Platform Wiki
                    </span>
                    <br>
                    <span class="text-white">องค์ความรู้ที่คุณต้องรู้</span>
                </h2>

                <p class="text-xl md:text-2xl text-gray-300 max-w-4xl mx-auto mb-12 leading-relaxed">
                    เจาะลึกทุกระบบ อธิบายหลักการ วิเคราะห์เทคโนโลยี พร้อมข้อมูลวิจัยและบริบทสังคมไทย
                    <br>
                    <span class="text-purple-300 font-semibold">เหมือนได้อ่านตำราเรียนเล่มใหญ่ แต่สนุกกว่า</span>
                </p>

                <!-- Features Grid -->
                <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-12 max-w-6xl mx-auto">
                    <div class="group bg-white/5 backdrop-blur-sm border border-white/10 rounded-2xl p-6 hover:bg-white/10 hover:border-white/30 transition-all duration-300 transform hover:scale-105">
                        <div class="text-4xl mb-3 group-hover:scale-110 transition-transform">🔍</div>
                        <h3 class="text-white font-bold mb-2">เจาะลึกทุกระบบ</h3>
                        <p class="text-gray-400 text-sm">อธิบายทุก Feature พร้อมตัวอย่าง Code</p>
                    </div>

                    <div class="group bg-white/5 backdrop-blur-sm border border-white/10 rounded-2xl p-6 hover:bg-white/10 hover:border-white/30 transition-all duration-300 transform hover:scale-105">
                        <div class="text-4xl mb-3 group-hover:scale-110 transition-transform">📊</div>
                        <h3 class="text-white font-bold mb-2">ข้อมูลวิจัย</h3>
                        <p class="text-gray-400 text-sm">สถิติจริง ตัวเลขจริง อ้างอิงได้</p>
                    </div>

                    <div class="group bg-white/5 backdrop-blur-sm border border-white/10 rounded-2xl p-6 hover:bg-white/10 hover:border-white/30 transition-all duration-300 transform hover:scale-105">
                        <div class="text-4xl mb-3 group-hover:scale-110 transition-transform">🇹🇭</div>
                        <h3 class="text-white font-bold mb-2">บริบทไทย</h3>
                        <p class="text-gray-400 text-sm">วิเคราะห์ปัญหาและโอกาสในสังคมไทย</p>
                    </div>

                    <div class="group bg-white/5 backdrop-blur-sm border border-white/10 rounded-2xl p-6 hover:bg-white/10 hover:border-white/30 transition-all duration-300 transform hover:scale-105">
                        <div class="text-4xl mb-3 group-hover:scale-110 transition-transform">📖</div>
                        <h3 class="text-white font-bold mb-2">อ่านง่าย</h3>
                        <p class="text-gray-400 text-sm">Sidebar Navigation แบบ Wikipedia</p>
                    </div>
                </div>

                <!-- Epic CTA Button -->
                <div class="relative inline-block">
                    <div class="absolute -inset-1 bg-gradient-to-r from-purple-600 to-pink-600 rounded-full blur-lg opacity-75 group-hover:opacity-100 transition duration-1000 group-hover:duration-200 animate-pulse"></div>
                    <a href="{{ route('platform.wiki') }}" class="relative inline-flex items-center gap-3 px-12 py-6 bg-gradient-to-r from-purple-600 to-pink-600 text-white text-xl font-bold rounded-full shadow-2xl hover:shadow-purple-500/50 transition-all duration-300 transform hover:scale-105 group">
                        <svg class="w-8 h-8 group-hover:rotate-12 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path>
                        </svg>
                        <span>เปิดอ่าน Platform Wiki</span>
                        <svg class="w-6 h-6 group-hover:translate-x-2 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"></path>
                        </svg>
                    </a>
                </div>

                <!-- Sub Text -->
                <p class="text-gray-400 text-sm mt-6">
                    💡 <strong>เหมาะสำหรับ:</strong> Developer, นักลงทุน, ผู้สนใจเทคโนโลยี และทุกคนที่อยากเข้าใจระบบแบบลึกซึ้ง
                </p>
            </div>

            <!-- Preview Cards - What's Inside -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 max-w-6xl mx-auto">
                <div class="bg-white/5 backdrop-blur-sm border border-white/10 rounded-2xl p-8 hover:bg-white/10 transition-all">
                    <h3 class="text-white text-2xl font-bold mb-4">🔄 MLM System</h3>
                    <ul class="text-gray-300 space-y-2 text-sm">
                        <li>• ทำไมต้องมี 2 Plans?</li>
                        <li>• หลักการคำนวณ Commission</li>
                        <li>• Commission Engine Deep Dive</li>
                        <li>• พร้อม Code Examples</li>
                    </ul>
                </div>

                <div class="bg-white/5 backdrop-blur-sm border border-white/10 rounded-2xl p-8 hover:bg-white/10 transition-all">
                    <h3 class="text-white text-2xl font-bold mb-4">🛒 E-Commerce</h3>
                    <ul class="text-gray-300 space-y-2 text-sm">
                        <li>• ทำไมต้อง Multi-Vendor?</li>
                        <li>• รูปแบบการสร้างรายได้</li>
                        <li>• สถิติ E-Commerce ไทย</li>
                        <li>• 6 ส่วนหลักของระบบ</li>
                    </ul>
                </div>

                <div class="bg-white/5 backdrop-blur-sm border border-white/10 rounded-2xl p-8 hover:bg-white/10 transition-all">
                    <h3 class="text-white text-2xl font-bold mb-4">🧠 Technology</h3>
                    <ul class="text-gray-300 space-y-2 text-sm">
                        <li>• สถาปัตยกรรมระบบ</li>
                        <li>• Database Design Philosophy</li>
                        <li>• Security Architecture</li>
                        <li>• AI Integration Strategy</li>
                    </ul>
                </div>
            </div>
        </div>
    </section>

    <!-- Presentation Slides Section -->
    @include('components.presentation-slides')

    <style>
        @keyframes blob {
            0% { transform: translate(0px, 0px) scale(1); }
            33% { transform: translate(30px, -50px) scale(1.1); }
            66% { transform: translate(-20px, 20px) scale(0.9); }
            100% { transform: translate(0px, 0px) scale(1); }
        }
        .animate-blob {
            animation: blob 7s infinite;
        }
        .animation-delay-2000 {
            animation-delay: 2s;
        }
        .animation-delay-4000 {
            animation-delay: 4s;
        }
        @keyframes fade-in-down {
            0% { opacity: 0; transform: translateY(-20px); }
            100% { opacity: 1; transform: translateY(0); }
        }
        .animate-fade-in-down {
            animation: fade-in-down 1s ease-out;
        }
    </style>

    @if($premiumSections['statistics'])
        <!-- Live Statistics Section - แสดงสถิติแบบ Real-time -->
        <section class="py-20 bg-white relative overflow-hidden">
            <div class="absolute inset-0 bg-gradient-to-br from-indigo-50 to-purple-50 opacity-50"></div>

            <div class="relative z-10 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="text-center mb-16">
                    <h2 class="text-4xl md:text-5xl font-bold text-gray-900 mb-4">
                        ตัวเลขที่พิสูจน์ความสำเร็จ
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
        <section class="relative pt-20 pb-0 overflow-hidden">
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

@keyframes fade-in-down {
    0% {
        opacity: 0;
        transform: translateY(-20px);
    }
    100% {
        opacity: 1;
        transform: translateY(0);
    }
}

.animate-fade-in-down {
    animation: fade-in-down 1s ease-out;
}

@keyframes blob {
    0% {
        transform: translate(0px, 0px) scale(1);
    }
    33% {
        transform: translate(30px, -50px) scale(1.1);
    }
    66% {
        transform: translate(-20px, 20px) scale(0.9);
    }
    100% {
        transform: translate(0px, 0px) scale(1);
    }
}

.animate-blob {
    animation: blob 7s infinite;
}

.animation-delay-2000 {
    animation-delay: 2s;
}

.animation-delay-4000 {
    animation-delay: 4s;
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
