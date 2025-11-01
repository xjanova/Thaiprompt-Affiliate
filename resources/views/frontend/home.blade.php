@extends('layouts.app')

@section('title', 'หน้าแรก')

@section('content')
<!-- Welcome Section -->
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

<!-- Statistics Section -->
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
                <div class="text-3xl md:text-4xl font-bold text-gray-900 mb-2">
                    ฿{{ number_format($stats['total_earnings'], 0) }}
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
                <div class="text-3xl md:text-4xl font-bold text-gray-900 mb-2">
                    {{ number_format($stats['total_users'], 0) }}
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
                <div class="text-3xl md:text-4xl font-bold text-gray-900 mb-2">
                    {{ number_format($stats['success_rate'], 0) }}%
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
                <div class="text-3xl md:text-4xl font-bold text-gray-900 mb-2">
                    {{ number_format($stats['total_commissions'], 0) }}
                </div>
                <p class="text-gray-600 font-medium">คอมมิชชั่นจ่ายแล้ว</p>
                <p class="text-sm text-gray-500 mt-1">เดือนนี้ {{ $stats['this_month_commissions'] }} รายการ</p>
            </div>
        </div>

        <!-- Average Earnings Highlight -->
        <div class="mt-12 bg-gradient-to-r from-indigo-600 to-purple-600 rounded-2xl shadow-2xl p-8 text-white text-center">
            <p class="text-lg mb-2 text-indigo-200">รายได้เฉลี่ยต่อ Affiliate</p>
            <p class="text-5xl md:text-6xl font-bold mb-2">
                ฿{{ number_format($stats['avg_earnings'], 0) }}
            </p>
            <p class="text-indigo-200">คุณก็สามารถทำได้เช่นกัน!</p>
        </div>
    </div>
</section>

<!-- Top Affiliates Leaderboard -->
@if($topAffiliates->count() > 0)
<section class="py-20 bg-gray-50">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-16">
            <h2 class="text-4xl md:text-5xl font-bold text-gray-900 mb-4">
                🏆 Affiliates ยอดนิยม
            </h2>
            <p class="text-xl text-gray-600">ผู้ที่ประสบความสำเร็จกับเรา คุณก็ทำได้!</p>
        </div>

        <div class="max-w-4xl mx-auto space-y-4">
            @foreach($topAffiliates as $index => $affiliate)
            <div class="bg-white p-6 rounded-2xl shadow-lg hover:shadow-2xl transition-all duration-300 border border-gray-100">
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

@push('scripts')
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
