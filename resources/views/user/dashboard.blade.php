@extends('layouts.user')

@section('title', 'แดชบอร์ด')

@section('content')
<div class="space-y-6 pb-20 lg:pb-6">
    <!-- Welcome Header with Premium Gradient & Avatar -->
    <div class="bg-gradient-to-r from-indigo-600 via-purple-600 to-pink-600 rounded-2xl shadow-2xl p-8 text-white relative overflow-hidden">
        <div class="absolute top-0 right-0 -mt-4 -mr-4 w-40 h-40 bg-white opacity-10 rounded-full"></div>
        <div class="absolute bottom-0 left-0 -mb-4 -ml-4 w-32 h-32 bg-white opacity-10 rounded-full"></div>
        <div class="absolute top-1/2 right-1/4 w-24 h-24 bg-white opacity-5 rounded-full"></div>
        <div class="relative z-10">
            <div class="flex flex-col md:flex-row items-start md:items-center gap-6">
                <!-- Profile Avatar with Rank Frame -->
                <div class="relative">
                    @php
                        $rankColor = 'gray';
                        $rankStars = 0;
                        if ($user->currentRank) {
                            $rankLevel = $user->currentRank->level ?? 0;
                            if ($rankLevel >= 7) {
                                $rankColor = 'purple';
                                $rankStars = 5;
                            } elseif ($rankLevel >= 5) {
                                $rankColor = 'yellow';
                                $rankStars = 4;
                            } elseif ($rankLevel >= 3) {
                                $rankColor = 'blue';
                                $rankStars = 3;
                            } elseif ($rankLevel >= 2) {
                                $rankColor = 'green';
                                $rankStars = 2;
                            } elseif ($rankLevel >= 1) {
                                $rankColor = 'gray';
                                $rankStars = 1;
                            }
                        }

                        $frameColors = [
                            'gray' => 'from-gray-400 to-gray-600',
                            'green' => 'from-green-400 to-green-600',
                            'blue' => 'from-blue-400 to-blue-600',
                            'purple' => 'from-purple-400 to-purple-600',
                            'yellow' => 'from-yellow-300 to-yellow-500',
                        ];
                    @endphp

                    <div class="relative w-28 h-28 md:w-32 md:h-32">
                        <!-- Rank Frame -->
                        <div class="absolute inset-0 bg-gradient-to-br {{ $frameColors[$rankColor] ?? 'from-gray-400 to-gray-600' }} rounded-full p-1.5 shadow-2xl animate-pulse-slow">
                            <div class="w-full h-full bg-white rounded-full p-1">
                                @if($user->line_picture_url || $user->profile_picture)
                                    <img src="{{ $user->line_picture_url ?? asset($user->profile_picture) }}"
                                         alt="{{ $user->name }}"
                                         class="w-full h-full object-cover rounded-full">
                                @else
                                    <div class="w-full h-full rounded-full bg-gradient-to-br from-indigo-500 to-purple-600 flex items-center justify-center text-white text-4xl font-bold">
                                        {{ strtoupper(substr($user->name, 0, 1)) }}
                                    </div>
                                @endif
                            </div>
                        </div>

                        <!-- Stars -->
                        @if($rankStars > 0)
                            <div class="absolute -bottom-2 left-1/2 transform -translate-x-1/2 flex gap-0.5">
                                @for ($i = 0; $i < $rankStars; $i++)
                                    <div class="text-yellow-300 text-sm drop-shadow-lg">⭐</div>
                                @endfor
                            </div>
                        @endif

                        <!-- KYC Badge -->
                        <div class="absolute -top-1 -right-1">
                            @if($user->kyc_status === 'approved')
                                <div class="w-8 h-8 bg-green-500 rounded-full flex items-center justify-center border-2 border-white shadow-lg" title="ยืนยันตัวตนแล้ว">
                                    <i class="fas fa-check text-white text-xs"></i>
                                </div>
                            @elseif($user->kyc_status === 'pending')
                                <div class="w-8 h-8 bg-yellow-500 rounded-full flex items-center justify-center border-2 border-white shadow-lg animate-pulse" title="รอการตรวจสอบ">
                                    <i class="fas fa-clock text-white text-xs"></i>
                                </div>
                            @elseif($user->kyc_status === 'rejected')
                                <div class="w-8 h-8 bg-red-500 rounded-full flex items-center justify-center border-2 border-white shadow-lg" title="ไม่ผ่านการตรวจสอบ">
                                    <i class="fas fa-times text-white text-xs"></i>
                                </div>
                            @else
                                <a href="{{ route('user.kyc.create') }}" class="w-8 h-8 bg-gray-500 rounded-full flex items-center justify-center border-2 border-white shadow-lg hover:bg-gray-600 transition" title="ยังไม่ได้ยืนยันตัวตน - คลิกเพื่อยืนยัน">
                                    <i class="fas fa-exclamation text-white text-xs"></i>
                                </a>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Welcome Text -->
                <div class="flex-1">
                    <div class="flex items-center gap-3 mb-2">
                        <h1 class="text-3xl md:text-4xl font-bold">👋 สวัสดี, {{ $user->name }}!</h1>
                        @if($user->currentRank)
                            <span class="px-3 py-1 bg-white bg-opacity-20 rounded-lg text-sm font-semibold">
                                {{ $user->currentRank->name }}
                            </span>
                        @endif
                    </div>
                    <p class="text-indigo-100 text-lg">ยินดีต้อนรับสู่ระบบ Premium Affiliate Dashboard - {{ now()->format('d/m/Y') }}</p>
                    @if($affiliate)
                        <div class="mt-4 inline-flex items-center px-4 py-2 bg-white bg-opacity-20 rounded-xl">
                            <span class="text-sm font-semibold">รหัสแนะนำ: {{ $affiliate->referral_code }}</span>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- KYC Status Widget -->
    @if($user->kyc_status !== 'approved')
        <div class="bg-gradient-to-r
            @if($user->kyc_status === 'pending') from-yellow-500 to-orange-500
            @elseif($user->kyc_status === 'rejected') from-red-500 to-pink-500
            @else from-gray-500 to-gray-600 @endif
            rounded-xl shadow-lg p-6 text-white">
            <div class="flex items-start gap-4">
                <div class="text-5xl">
                    @if($user->kyc_status === 'pending')
                        ⏳
                    @elseif($user->kyc_status === 'rejected')
                        ❌
                    @else
                        🪪
                    @endif
                </div>
                <div class="flex-1">
                    <h3 class="text-xl font-bold mb-2">
                        @if($user->kyc_status === 'pending')
                            การยืนยันตัวตนของคุณกำลังรอการตรวจสอบ
                        @elseif($user->kyc_status === 'rejected')
                            การยืนยันตัวตนไม่ผ่านการตรวจสอบ
                        @else
                            ยืนยันตัวตนเพื่อเพิ่มความน่าเชื่อถือ
                        @endif
                    </h3>
                    <p class="text-white text-opacity-90 text-sm mb-4">
                        @if($user->kyc_status === 'pending')
                            เอกสารของคุณกำลังอยู่ระหว่างการตรวจสอบโดยแอดมิน กรุณารอสักครู่
                        @elseif($user->kyc_status === 'rejected')
                            เอกสารของคุณไม่ผ่านการตรวจสอบ กรุณาส่งเอกสารใหม่อีกครั้ง
                        @else
                            ยืนยันตัวตนด้วยบัตรประชาชนเพื่อเพิ่มความปลอดภัยและความน่าเชื่อถือของบัญชี
                        @endif
                    </p>
                    <a href="{{ $user->kyc_status === 'not_submitted' ? route('user.kyc.create') : route('user.kyc.index') }}"
                       class="inline-flex items-center px-4 py-2 bg-white text-gray-900 rounded-lg font-semibold hover:bg-gray-100 transition">
                        <i class="fas fa-arrow-right mr-2"></i>
                        @if($user->kyc_status === 'pending')
                            ดูสถานะ
                        @elseif($user->kyc_status === 'rejected')
                            ส่งเอกสารใหม่
                        @else
                            เริ่มยืนยันตัวตน
                        @endif
                    </a>
                </div>
            </div>
        </div>
    @endif

    <!-- Rank & Retention Widgets -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Rank Widget -->
        @include('components.rank-widget')

        <!-- Retention Widget -->
        @include('components.life-power-widget')
    </div>

    <!-- Premium Stats Cards with Growth Indicators -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <!-- Total Lifetime Earnings -->
        <a href="{{ route('user.commissions') }}" class="relative overflow-hidden bg-gradient-to-br from-green-500 to-emerald-600 rounded-2xl shadow-xl p-6 text-white transform hover:scale-105 transition duration-300 block cursor-pointer">
            <div class="absolute top-0 right-0 -mt-4 -mr-4">
                <div class="w-24 h-24 bg-white opacity-10 rounded-full"></div>
            </div>
            <div class="relative z-10">
                <div class="flex items-center justify-between mb-4">
                    <div class="text-5xl">💎</div>
                    @if($earningsGrowth != 0)
                        <span class="px-2 py-1 bg-white bg-opacity-20 rounded-lg text-xs font-semibold">
                            {{ $earningsGrowth > 0 ? '↑' : '↓' }} {{ number_format(abs($earningsGrowth), 1) }}%
                        </span>
                    @endif
                </div>
                <p class="text-white text-opacity-80 text-sm mb-1">รายได้ตลอดชีพ</p>
                <p class="text-3xl md:text-4xl font-bold">฿{{ number_format($lifetimeEarnings, 0) }}</p>
                <p class="text-xs text-white text-opacity-70 mt-2">เทียบเดือนที่แล้ว · คลิกเพื่อดูรายละเอียด</p>
            </div>
        </a>

        <!-- Approved Earnings -->
        <a href="{{ route('user.commissions', ['status' => 'approved']) }}" class="relative overflow-hidden bg-gradient-to-br from-blue-500 to-blue-600 rounded-2xl shadow-xl p-6 text-white transform hover:scale-105 transition duration-300 block cursor-pointer">
            <div class="absolute top-0 right-0 -mt-4 -mr-4">
                <div class="w-24 h-24 bg-white opacity-10 rounded-full"></div>
            </div>
            <div class="relative z-10">
                <div class="flex items-center justify-between mb-4">
                    <div class="text-5xl">💰</div>
                    <span class="px-2 py-1 bg-white bg-opacity-20 rounded-lg text-xs font-semibold">
                        อนุมัติแล้ว
                    </span>
                </div>
                <p class="text-white text-opacity-80 text-sm mb-1">รายได้ที่อนุมัติ</p>
                <p class="text-3xl md:text-4xl font-bold">฿{{ number_format($totalEarnings, 0) }}</p>
                <p class="text-xs text-white text-opacity-70 mt-2">พร้อมถอนได้ · คลิกเพื่อดูรายละเอียด</p>
            </div>
        </a>

        <!-- Pending Earnings -->
        <a href="{{ route('user.commissions', ['status' => 'pending']) }}" class="relative overflow-hidden bg-gradient-to-br from-yellow-500 to-orange-600 rounded-2xl shadow-xl p-6 text-white transform hover:scale-105 transition duration-300 block cursor-pointer">
            <div class="absolute top-0 right-0 -mt-4 -mr-4">
                <div class="w-24 h-24 bg-white opacity-10 rounded-full"></div>
            </div>
            <div class="relative z-10">
                <div class="flex items-center justify-between mb-4">
                    <div class="text-5xl">⏳</div>
                    <span class="px-2 py-1 bg-white bg-opacity-20 rounded-lg text-xs font-semibold">
                        รอตรวจสอบ
                    </span>
                </div>
                <p class="text-white text-opacity-80 text-sm mb-1">รอการอนุมัติ</p>
                <p class="text-3xl md:text-4xl font-bold">฿{{ number_format($pendingEarnings, 0) }}</p>
                <p class="text-xs text-white text-opacity-70 mt-2">กำลังตรวจสอบ · คลิกเพื่อดูรายละเอียด</p>
            </div>
        </a>

        <!-- Total Referrals -->
        <a href="{{ route('user.referrals') }}" class="relative overflow-hidden bg-gradient-to-br from-purple-500 to-pink-600 rounded-2xl shadow-xl p-6 text-white transform hover:scale-105 transition duration-300 block cursor-pointer">
            <div class="absolute top-0 right-0 -mt-4 -mr-4">
                <div class="w-24 h-24 bg-white opacity-10 rounded-full"></div>
            </div>
            <div class="relative z-10">
                <div class="flex items-center justify-between mb-4">
                    <div class="text-5xl">👥</div>
                    <span class="px-2 py-1 bg-white bg-opacity-20 rounded-lg text-xs font-semibold">
                        {{ $activeReferrals }} ใช้งาน
                    </span>
                </div>
                <p class="text-white text-opacity-80 text-sm mb-1">ผู้แนะนำทั้งหมด</p>
                <p class="text-3xl md:text-4xl font-bold">{{ number_format($totalReferrals) }}</p>
                <p class="text-xs text-white text-opacity-70 mt-2">{{ $maxLevel }} ระดับ · คลิกเพื่อดูรายละเอียด</p>
            </div>
        </a>
    </div>

    <!-- Performance Metrics -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <a href="{{ route('user.commissions') }}" class="bg-white rounded-xl shadow-md p-4 hover:shadow-lg transition cursor-pointer block">
            <div class="flex items-center gap-3">
                <div class="text-3xl">📊</div>
                <div>
                    <p class="text-xs text-gray-500">ค่าเฉลี่ยคอมมิชชั่น</p>
                    <p class="text-xl font-bold text-gray-800">฿{{ number_format($avgCommission, 2) }}</p>
                </div>
            </div>
        </a>

        <a href="{{ route('user.commissions') }}" class="bg-white rounded-xl shadow-md p-4 hover:shadow-lg transition cursor-pointer block">
            <div class="flex items-center gap-3">
                <div class="text-3xl">📈</div>
                <div>
                    <p class="text-xs text-gray-500">คอมมิชชั่นเดือนนี้</p>
                    <p class="text-xl font-bold text-gray-800">{{ number_format($thisMonthCommissions) }}</p>
                </div>
            </div>
        </a>

        <a href="{{ route('user.referrals') }}" class="bg-white rounded-xl shadow-md p-4 hover:shadow-lg transition cursor-pointer block">
            <div class="flex items-center gap-3">
                <div class="text-3xl">🎯</div>
                <div>
                    <p class="text-xs text-gray-500">Conversion Rate</p>
                    <p class="text-xl font-bold text-gray-800">{{ number_format($conversionRate, 1) }}%</p>
                </div>
            </div>
        </a>

        <a href="{{ route('user.commissions', ['status' => 'paid']) }}" class="bg-white rounded-xl shadow-md p-4 hover:shadow-lg transition cursor-pointer block">
            <div class="flex items-center gap-3">
                <div class="text-3xl">💸</div>
                <div>
                    <p class="text-xs text-gray-500">จ่ายแล้วทั้งหมด</p>
                    <p class="text-xl font-bold text-gray-800">฿{{ number_format($paidEarnings, 0) }}</p>
                </div>
            </div>
        </a>
    </div>

    <!-- Charts Section -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Revenue Trend Chart -->
        <div class="lg:col-span-2 bg-white rounded-2xl shadow-xl p-6">
            <div class="flex items-center justify-between mb-6">
                <div>
                    <h3 class="text-xl font-bold text-gray-900">รายได้รายเดือน</h3>
                    <p class="text-sm text-gray-500">12 เดือนย้อนหลัง</p>
                </div>
                <div class="flex gap-2">
                    <span class="px-3 py-1 bg-green-100 text-green-800 rounded-lg text-xs font-semibold">
                        ฿{{ number_format($monthlyRevenue->sum('total'), 0) }}
                    </span>
                </div>
            </div>
            <canvas id="revenueChart" height="80"></canvas>
        </div>

        <!-- Commission Status Donut Chart -->
        <div class="bg-white rounded-2xl shadow-xl p-6">
            <h3 class="text-xl font-bold text-gray-900 mb-6">สถานะคอมมิชชั่น</h3>
            <canvas id="statusChart"></canvas>
            <div class="mt-6 space-y-3">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <div class="w-3 h-3 bg-yellow-500 rounded-full"></div>
                        <span class="text-sm text-gray-600">รอดำเนินการ</span>
                    </div>
                    <span class="font-semibold text-gray-900">{{ $commissionStatus['pending'] }}</span>
                </div>
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <div class="w-3 h-3 bg-green-500 rounded-full"></div>
                        <span class="text-sm text-gray-600">อนุมัติแล้ว</span>
                    </div>
                    <span class="font-semibold text-gray-900">{{ $commissionStatus['approved'] }}</span>
                </div>
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <div class="w-3 h-3 bg-blue-500 rounded-full"></div>
                        <span class="text-sm text-gray-600">จ่ายแล้ว</span>
                    </div>
                    <span class="font-semibold text-gray-900">{{ $commissionStatus['paid'] }}</span>
                </div>
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <div class="w-3 h-3 bg-red-500 rounded-full"></div>
                        <span class="text-sm text-gray-600">ปฏิเสธ</span>
                    </div>
                    <span class="font-semibold text-gray-900">{{ $commissionStatus['rejected'] }}</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Commission Types & Daily Activity -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Commission Types Bar Chart -->
        <div class="bg-white rounded-2xl shadow-xl p-6">
            <h3 class="text-xl font-bold text-gray-900 mb-6">ประเภทคอมมิชชั่น</h3>
            <canvas id="typesChart"></canvas>
        </div>

        <!-- Daily Activity Line Chart -->
        <div class="bg-white rounded-2xl shadow-xl p-6">
            <h3 class="text-xl font-bold text-gray-900 mb-6">กิจกรรมรายวัน (30 วัน)</h3>
            <canvas id="dailyChart"></canvas>
        </div>
    </div>

    <!-- Referral Link & QR Code -->
    @if($affiliate)
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Referral Link -->
        <div class="lg:col-span-2 bg-white rounded-2xl shadow-xl p-6">
            <h2 class="text-xl font-bold text-gray-800 mb-4">🔗 ลิงก์แนะนำของคุณ</h2>
            <div class="flex flex-col sm:flex-row gap-3 mb-4">
                <input type="text"
                       value="{{ url('/register?ref=' . $affiliate->referral_code) }}"
                       readonly
                       id="referralLink"
                       class="flex-1 px-4 py-3 border border-gray-300 rounded-lg bg-gray-50 text-sm">
                <button onclick="copyReferralLink()"
                        class="px-6 py-3 bg-gradient-to-r from-indigo-600 to-purple-600 text-white font-semibold rounded-lg hover:opacity-90 transition whitespace-nowrap">
                    📋 คัดลอก
                </button>
            </div>
            <p class="text-sm text-gray-600">แชร์ลิงก์นี้กับเพื่อนของคุณเพื่อรับคอมมิชชั่น</p>

            <!-- Social Share Buttons -->
            <div class="mt-4 flex flex-wrap gap-3">
                <a href="https://www.facebook.com/sharer/sharer.php?u={{ urlencode(url('/register?ref=' . $affiliate->referral_code)) }}"
                   target="_blank"
                   class="px-4 py-2 bg-blue-600 text-white rounded-lg text-sm font-semibold hover:bg-blue-700 transition">
                    📘 แชร์ Facebook
                </a>
                <a href="https://twitter.com/intent/tweet?url={{ urlencode(url('/register?ref=' . $affiliate->referral_code)) }}&text=มาร่วมกับเราสิ!"
                   target="_blank"
                   class="px-4 py-2 bg-sky-500 text-white rounded-lg text-sm font-semibold hover:bg-sky-600 transition">
                    🐦 แชร์ Twitter
                </a>
                <a href="https://line.me/R/msg/text/?{{ urlencode('มาร่วมกับเราสิ! ' . url('/register?ref=' . $affiliate->referral_code)) }}"
                   target="_blank"
                   class="px-4 py-2 bg-green-500 text-white rounded-lg text-sm font-semibold hover:bg-green-600 transition">
                    💬 แชร์ LINE
                </a>
            </div>
        </div>

        <!-- QR Code -->
        <div class="bg-white rounded-2xl shadow-xl p-6 text-center">
            <h3 class="text-xl font-bold text-gray-900 mb-4">📱 QR Code</h3>
            <div class="flex justify-center mb-4">
                <div id="qrcode" class="inline-block"></div>
            </div>
            <p class="text-sm text-gray-600">สแกน QR Code เพื่อแชร์</p>
        </div>
    </div>
    @endif

    <!-- Top Referrers & Recent Activity -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Top Referrers -->
        <div class="bg-white rounded-2xl shadow-xl p-6">
            <div class="flex items-center justify-between mb-6">
                <h3 class="text-xl font-bold text-gray-900">🏆 Top Referrers ของคุณ</h3>
                <a href="{{ route('user.referrals') }}" class="text-sm text-indigo-600 hover:text-indigo-700 font-semibold">
                    ดูทั้งหมด →
                </a>
            </div>
            <div class="space-y-4">
                @forelse($topReferrers as $index => $referrer)
                    <div class="flex items-center gap-4 p-4 bg-gradient-to-r from-gray-50 to-white rounded-xl hover:shadow-md transition border border-gray-100">
                        <div class="flex-shrink-0">
                            @if($index === 0)
                                <div class="w-12 h-12 bg-gradient-to-br from-yellow-400 to-yellow-600 rounded-full flex items-center justify-center text-white font-bold text-lg shadow-lg">
                                    🥇
                                </div>
                            @elseif($index === 1)
                                <div class="w-12 h-12 bg-gradient-to-br from-gray-300 to-gray-500 rounded-full flex items-center justify-center text-white font-bold text-lg shadow-lg">
                                    🥈
                                </div>
                            @elseif($index === 2)
                                <div class="w-12 h-12 bg-gradient-to-br from-orange-400 to-orange-600 rounded-full flex items-center justify-center text-white font-bold text-lg shadow-lg">
                                    🥉
                                </div>
                            @else
                                <div class="w-12 h-12 bg-gradient-to-br from-indigo-500 to-purple-600 rounded-full flex items-center justify-center text-white font-bold text-lg">
                                    {{ $index + 1 }}
                                </div>
                            @endif
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="font-semibold text-gray-900 truncate">{{ $referrer->user->name }}</p>
                            <p class="text-sm text-gray-500 truncate">{{ $referrer->user->email }}</p>
                        </div>
                        <div class="text-right">
                            <p class="font-bold text-lg text-indigo-600">{{ $referrer->children_count }}</p>
                            <p class="text-xs text-gray-500">refs</p>
                        </div>
                    </div>
                @empty
                    <div class="text-center text-gray-500 py-8">
                        <span class="text-4xl mb-2 block">📊</span>
                        <p>ยังไม่มีข้อมูล</p>
                    </div>
                @endforelse
            </div>
        </div>

        <!-- Recent Activity Feed -->
        <div class="bg-white rounded-2xl shadow-xl p-6">
            <div class="flex items-center justify-between mb-6">
                <h3 class="text-xl font-bold text-gray-900">🔔 กิจกรรมล่าสุด</h3>
                <a href="{{ route('user.commissions') }}" class="text-sm text-indigo-600 hover:text-indigo-700 font-semibold">
                    ดูทั้งหมด →
                </a>
            </div>
            <div class="space-y-4 max-h-96 overflow-y-auto">
                @forelse($recentActivity as $activity)
                    <div class="flex items-start gap-3 p-3 hover:bg-gray-50 rounded-lg transition">
                        <div class="w-10 h-10 bg-gradient-to-br from-indigo-500 to-purple-600 rounded-full flex items-center justify-center text-white font-bold flex-shrink-0">
                            {{ substr($user->name, 0, 1) }}
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-semibold text-gray-900">
                                คุณได้รับคอมมิชชั่น
                            </p>
                            <p class="text-xs text-gray-500">
                                จำนวน <span class="font-semibold text-green-600">฿{{ number_format($activity->amount, 2) }}</span>
                            </p>
                            <p class="text-xs text-gray-400 mt-1">
                                {{ $activity->created_at->diffForHumans() }}
                            </p>
                        </div>
                        <span class="px-2 py-1 rounded-full text-xs font-semibold flex-shrink-0
                            {{ $activity->status === 'pending' ? 'bg-yellow-100 text-yellow-800' : '' }}
                            {{ $activity->status === 'approved' ? 'bg-green-100 text-green-800' : '' }}
                            {{ $activity->status === 'paid' ? 'bg-blue-100 text-blue-800' : '' }}
                            {{ $activity->status === 'rejected' ? 'bg-red-100 text-red-800' : '' }}">
                            @if($activity->status === 'approved') อนุมัติ
                            @elseif($activity->status === 'pending') รอ
                            @elseif($activity->status === 'paid') จ่ายแล้ว
                            @else ปฏิเสธ
                            @endif
                        </span>
                    </div>
                @empty
                    <div class="text-center text-gray-500 py-8">
                        <span class="text-4xl mb-2 block">📭</span>
                        <p>ยังไม่มีกิจกรรม</p>
                    </div>
                @endforelse
            </div>
        </div>
    </div>

    <!-- Quick Actions Premium -->
    <div class="bg-gradient-to-r from-indigo-500 to-purple-600 rounded-2xl shadow-xl p-6 text-white">
        <h3 class="text-xl font-bold mb-4">⚡ การกระทำด่วน</h3>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <a href="{{ route('user.referrals') }}" class="bg-white bg-opacity-20 hover:bg-opacity-30 rounded-xl p-4 text-center transition transform hover:scale-105">
                <div class="text-3xl mb-2">👥</div>
                <p class="text-sm font-semibold">ดูผู้แนะนำ</p>
            </a>
            <a href="{{ route('user.commissions') }}" class="bg-white bg-opacity-20 hover:bg-opacity-30 rounded-xl p-4 text-center transition transform hover:scale-105">
                <div class="text-3xl mb-2">💰</div>
                <p class="text-sm font-semibold">ประวัติคอมมิชชั่น</p>
            </a>
            <a href="{{ route('user.profile') }}" class="bg-white bg-opacity-20 hover:bg-opacity-30 rounded-xl p-4 text-center transition transform hover:scale-105">
                <div class="text-3xl mb-2">👤</div>
                <p class="text-sm font-semibold">แก้ไขโปรไฟล์</p>
            </a>
            <a href="#" onclick="window.print(); return false;" class="bg-white bg-opacity-20 hover:bg-opacity-30 rounded-xl p-4 text-center transition transform hover:scale-105">
                <div class="text-3xl mb-2">📊</div>
                <p class="text-sm font-semibold">พิมพ์รายงาน</p>
            </a>
        </div>
    </div>
</div>

@push('scripts')
<!-- QR Code Library -->
<script src="https://cdn.jsdelivr.net/npm/qrcodejs@1.0.0/qrcode.min.js"></script>

<script>
// Copy Referral Link Function
function copyReferralLink() {
    const input = document.getElementById('referralLink');
    input.select();
    input.setSelectionRange(0, 99999);

    navigator.clipboard.writeText(input.value).then(() => {
        // Create toast notification
        const toast = document.createElement('div');
        toast.className = 'fixed top-4 right-4 bg-green-500 text-white px-6 py-3 rounded-lg shadow-lg z-50 animate-fade-in';
        toast.innerHTML = '✅ คัดลอกลิงก์สำเร็จ!';
        document.body.appendChild(toast);

        setTimeout(() => {
            toast.remove();
        }, 3000);
    }).catch(err => {
        console.error('Failed to copy:', err);
        alert('เกิดข้อผิดพลาดในการคัดลอก');
    });
}

// Generate QR Code
@if($affiliate)
const qrcode = new QRCode(document.getElementById("qrcode"), {
    text: "{{ url('/register?ref=' . $affiliate->referral_code) }}",
    width: 180,
    height: 180,
    colorDark : "#4F46E5",
    colorLight : "#ffffff",
    correctLevel : QRCode.CorrectLevel.H
});
@endif

// Get Chart Colors based on theme
const colors = window.getChartColors();
const borderColor = window.isDarkMode() ? '#1e293b' : '#fff';

// Revenue Area Chart
const revenueCtx = document.getElementById('revenueChart');
if (revenueCtx) {
    new Chart(revenueCtx, {
        type: 'line',
        data: {
            labels: {!! json_encode($monthlyRevenue->pluck('month')) !!},
            datasets: [{
                label: 'รายได้ (฿)',
                data: {!! json_encode($monthlyRevenue->pluck('total')) !!},
                borderColor: 'rgb(99, 102, 241)',
                backgroundColor: 'rgba(99, 102, 241, 0.1)',
                fill: true,
                tension: 0.4,
                borderWidth: 3,
                pointRadius: 4,
                pointBackgroundColor: 'rgb(99, 102, 241)',
                pointBorderColor: '#fff',
                pointBorderWidth: 2,
                pointHoverRadius: 6
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    backgroundColor: colors.tooltipBg,
                    padding: 12,
                    titleColor: '#fff',
                    bodyColor: '#fff',
                    borderColor: 'rgba(99, 102, 241, 0.5)',
                    borderWidth: 1,
                    callbacks: {
                        label: function(context) {
                            return 'รายได้: ฿' + context.parsed.y.toLocaleString();
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: {
                        color: colors.gridColor
                    },
                    ticks: {
                        color: colors.textColor,
                        callback: function(value) {
                            return '฿' + value.toLocaleString();
                        }
                    }
                },
                x: {
                    grid: {
                        display: false
                    },
                    ticks: {
                        color: colors.textColor
                    }
                }
            }
        }
    });
}

// Status Donut Chart
const statusCtx = document.getElementById('statusChart');
if (statusCtx) {
    new Chart(statusCtx, {
        type: 'doughnut',
        data: {
            labels: ['รอดำเนินการ', 'อนุมัติแล้ว', 'จ่ายแล้ว', 'ปฏิเสธ'],
            datasets: [{
                data: [
                    {{ $commissionStatus['pending'] }},
                    {{ $commissionStatus['approved'] }},
                    {{ $commissionStatus['paid'] }},
                    {{ $commissionStatus['rejected'] }}
                ],
                backgroundColor: [
                    'rgba(234, 179, 8, 0.8)',
                    'rgba(34, 197, 94, 0.8)',
                    'rgba(59, 130, 246, 0.8)',
                    'rgba(239, 68, 68, 0.8)'
                ],
                borderWidth: 2,
                borderColor: borderColor
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    backgroundColor: colors.tooltipBg,
                    padding: 12,
                    titleColor: '#fff',
                    bodyColor: '#fff'
                }
            },
            cutout: '70%'
        }
    });
}

// Commission Types Bar Chart
const typesCtx = document.getElementById('typesChart');
if (typesCtx) {
    new Chart(typesCtx, {
        type: 'bar',
        data: {
            labels: {!! json_encode($commissionTypes->pluck('type')) !!},
            datasets: [{
                label: 'จำนวนเงิน (฿)',
                data: {!! json_encode($commissionTypes->pluck('total')) !!},
                backgroundColor: [
                    'rgba(99, 102, 241, 0.8)',
                    'rgba(168, 85, 247, 0.8)',
                    'rgba(236, 72, 153, 0.8)'
                ],
                borderRadius: 8,
                borderSkipped: false
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    backgroundColor: colors.tooltipBg,
                    titleColor: '#fff',
                    bodyColor: '#fff',
                    callbacks: {
                        label: function(context) {
                            return 'จำนวน: ฿' + context.parsed.y.toLocaleString();
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: {
                        color: colors.gridColor
                    },
                    ticks: {
                        color: colors.textColor,
                        callback: function(value) {
                            return '฿' + value.toLocaleString();
                        }
                    }
                },
                x: {
                    grid: {
                        display: false
                    },
                    ticks: {
                        color: colors.textColor
                    }
                }
            }
        }
    });
}

// Daily Activity Chart
const dailyCtx = document.getElementById('dailyChart');
if (dailyCtx) {
    new Chart(dailyCtx, {
        type: 'line',
        data: {
            labels: {!! json_encode($dailyCommissions->pluck('date')) !!},
            datasets: [{
                label: 'จำนวนคอมมิชชั่น',
                data: {!! json_encode($dailyCommissions->pluck('count')) !!},
                borderColor: 'rgb(168, 85, 247)',
                backgroundColor: 'rgba(168, 85, 247, 0.1)',
                fill: true,
                tension: 0.4,
                borderWidth: 2,
                pointRadius: 3,
                pointBackgroundColor: 'rgb(168, 85, 247)',
                pointBorderColor: '#fff',
                pointBorderWidth: 2
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    backgroundColor: colors.tooltipBg,
                    padding: 12,
                    titleColor: '#fff',
                    bodyColor: '#fff'
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: {
                        color: colors.gridColor
                    },
                    ticks: {
                        color: colors.textColor,
                        stepSize: 1
                    }
                },
                x: {
                    grid: {
                        display: false
                    },
                    ticks: {
                        color: colors.textColor,
                        maxTicksLimit: 10
                    }
                }
            }
        }
    });
}
</script>

<style>
@keyframes fade-in {
    from {
        opacity: 0;
        transform: translateY(-10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.animate-fade-in {
    animation: fade-in 0.3s ease-out;
}

/* Dashboard Enhancement Animations */
@keyframes float {
    0%, 100% { transform: translateY(0px); }
    50% { transform: translateY(-10px); }
}

@keyframes rotate {
    from { transform: rotate(0deg); }
    to { transform: rotate(360deg); }
}

@keyframes slideInUp {
    from {
        opacity: 0;
        transform: translateY(30px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

/* Add animation delay classes */
.animate-delay-100 { animation-delay: 0.1s; }
.animate-delay-200 { animation-delay: 0.2s; }
.animate-delay-300 { animation-delay: 0.3s; }
.animate-delay-400 { animation-delay: 0.4s; }

/* Smooth page load animations */
.space-y-6 > * {
    animation: slideInUp 0.5s ease-out backwards;
}

.space-y-6 > *:nth-child(1) { animation-delay: 0s; }
.space-y-6 > *:nth-child(2) { animation-delay: 0.1s; }
.space-y-6 > *:nth-child(3) { animation-delay: 0.2s; }
.space-y-6 > *:nth-child(4) { animation-delay: 0.3s; }
.space-y-6 > *:nth-child(5) { animation-delay: 0.4s; }
.space-y-6 > *:nth-child(6) { animation-delay: 0.5s; }

/* Floating decorative circles */
.bg-gradient-to-r .absolute.rounded-full {
    animation: float 3s ease-in-out infinite;
}

.bg-gradient-to-r .absolute.rounded-full:nth-child(2) {
    animation-delay: 0.5s;
}

.bg-gradient-to-r .absolute.rounded-full:nth-child(3) {
    animation-delay: 1s;
}

/* Enhanced card hover effects */
.bg-white.rounded-2xl.shadow-xl {
    transition: all 0.3s ease;
}

.bg-white.rounded-2xl.shadow-xl:hover {
    transform: translateY(-5px);
    box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
}

/* Performance metric cards pulse on hover */
.bg-white.rounded-xl.shadow-md:hover .text-3xl {
    animation: pulse 1s ease-in-out infinite;
}
</style>
@endpush
@endsection
