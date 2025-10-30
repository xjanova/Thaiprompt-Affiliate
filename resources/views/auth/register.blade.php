<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @php
        $appName = \App\Models\Setting::get('app_name', 'TP-Affiliate');
    @endphp
    <title>สมัครสมาชิก - {{ $appName }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes pulse {
            0%, 100% {
                opacity: 1;
            }
            50% {
                opacity: 0.7;
            }
        }

        @keyframes gradientShift {
            0% {
                background-position: 0% 50%;
            }
            50% {
                background-position: 100% 50%;
            }
            100% {
                background-position: 0% 50%;
            }
        }

        .fade-in-up {
            animation: fadeInUp 0.8s ease-out;
        }

        .pulse-animate {
            animation: pulse 2s ease-in-out infinite;
        }

        .gradient-animate {
            background: linear-gradient(-45deg, #667eea, #764ba2, #f093fb, #4facfe);
            background-size: 400% 400%;
            animation: gradientShift 15s ease infinite;
        }

        .stat-card {
            backdrop-filter: blur(10px);
            background: rgba(255, 255, 255, 0.95);
        }

        .glass-effect {
            backdrop-filter: blur(16px) saturate(180%);
            background-color: rgba(255, 255, 255, 0.95);
            border: 1px solid rgba(209, 213, 219, 0.3);
        }

        .input-glow:focus {
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
    </style>
</head>
<body class="gradient-animate min-h-screen">
    <div class="min-h-screen flex items-center justify-center px-4 py-12">
        <div class="max-w-6xl w-full">
            <div class="grid lg:grid-cols-2 gap-8">
                <!-- Left Side - Registration Form -->
                <div class="glass-effect rounded-2xl shadow-2xl p-8 fade-in-up">
                    <div class="text-center mb-8">
                        <h1 class="text-5xl font-bold bg-gradient-to-r from-purple-600 to-pink-600 bg-clip-text text-transparent mb-2">{{ $appName }}</h1>
                        <p class="text-gray-600 text-lg">เริ่มต้นสร้างรายได้วันนี้</p>
                        <div class="mt-4 inline-flex items-center px-4 py-2 bg-gradient-to-r from-green-400 to-blue-500 text-white rounded-full text-sm font-semibold pulse-animate">
                            <i class="fas fa-check-circle mr-2"></i>
                            ฟรี! ไม่มีค่าใช้จ่าย
                        </div>
                    </div>

                    @if ($errors->any())
                        <div class="mb-4 bg-red-50 border-l-4 border-red-500 text-red-700 px-4 py-3 rounded-lg shadow-md">
                            <div class="flex items-center mb-2">
                                <i class="fas fa-exclamation-triangle mr-2"></i>
                                <strong>พบข้อผิดพลาด:</strong>
                            </div>
                            <ul class="list-disc list-inside space-y-1">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form method="POST" action="{{ route('register') }}">
                        @csrf

                        @if (!empty($referralCode))
                            <div class="mb-4 bg-gradient-to-r from-green-50 to-blue-50 border-l-4 border-green-500 text-green-700 px-4 py-3 rounded-lg shadow-md">
                                <div class="flex items-center">
                                    <i class="fas fa-user-check text-2xl mr-3"></i>
                                    <div>
                                        <p class="font-semibold">คุณถูกแนะนำโดย</p>
                                        <p class="text-sm">รหัส: <strong class="text-lg">{{ $referralCode }}</strong></p>
                                    </div>
                                </div>
                            </div>
                        @endif

                        <div class="space-y-4">
                            <div>
                                <label for="name" class="block text-sm font-semibold text-gray-700 mb-2">
                                    <i class="fas fa-user mr-1 text-purple-500"></i> ชื่อ-นามสกุล
                                </label>
                                <input type="text" name="name" id="name" value="{{ old('name') }}"
                                       class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl input-glow focus:ring-0 focus:border-purple-500 transition-all @error('name') border-red-500 @enderror"
                                       placeholder="กรอกชื่อ-นามสกุลของคุณ"
                                       required autofocus>
                                @error('name')
                                    <p class="mt-1 text-sm text-red-600"><i class="fas fa-exclamation-circle mr-1"></i>{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label for="email" class="block text-sm font-semibold text-gray-700 mb-2">
                                    <i class="fas fa-envelope mr-1 text-purple-500"></i> อีเมล
                                </label>
                                <input type="email" name="email" id="email" value="{{ old('email') }}"
                                       class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl input-glow focus:ring-0 focus:border-purple-500 transition-all @error('email') border-red-500 @enderror"
                                       placeholder="your@email.com"
                                       required>
                                @error('email')
                                    <p class="mt-1 text-sm text-red-600"><i class="fas fa-exclamation-circle mr-1"></i>{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label for="password" class="block text-sm font-semibold text-gray-700 mb-2">
                                    <i class="fas fa-lock mr-1 text-purple-500"></i> รหัสผ่าน
                                </label>
                                <input type="password" name="password" id="password"
                                       class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl input-glow focus:ring-0 focus:border-purple-500 transition-all @error('password') border-red-500 @enderror"
                                       placeholder="สร้างรหัสผ่านที่ปลอดภัย"
                                       required>
                                @error('password')
                                    <p class="mt-1 text-sm text-red-600"><i class="fas fa-exclamation-circle mr-1"></i>{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label for="password_confirmation" class="block text-sm font-semibold text-gray-700 mb-2">
                                    <i class="fas fa-lock mr-1 text-purple-500"></i> ยืนยันรหัสผ่าน
                                </label>
                                <input type="password" name="password_confirmation" id="password_confirmation"
                                       class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl input-glow focus:ring-0 focus:border-purple-500 transition-all"
                                       placeholder="กรอกรหัสผ่านอีกครั้ง"
                                       required>
                            </div>

                            <div>
                                <label for="referral_code" class="block text-sm font-semibold text-gray-700 mb-2">
                                    <i class="fas fa-gift mr-1 text-purple-500"></i> รหัสแนะนำ (ถ้ามี)
                                </label>
                                <input type="text" name="referral_code" id="referral_code" value="{{ old('referral_code', $referralCode ?? '') }}"
                                       class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl input-glow focus:ring-0 focus:border-purple-500 transition-all @error('referral_code') border-red-500 @enderror"
                                       placeholder="กรอกรหัสแนะนำจากผู้แนะนำ">
                                @error('referral_code')
                                    <p class="mt-1 text-sm text-red-600"><i class="fas fa-exclamation-circle mr-1"></i>{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                        <button type="submit" class="mt-6 w-full bg-gradient-to-r from-purple-600 to-pink-600 text-white py-4 rounded-xl font-bold text-lg hover:from-purple-700 hover:to-pink-700 transform hover:scale-105 transition-all duration-300 shadow-lg hover:shadow-2xl">
                            <i class="fas fa-rocket mr-2"></i> สมัครสมาชิกเลย!
                        </button>
                    </form>

                    <div class="mt-6 text-center space-y-3">
                        <a href="{{ route('login') }}" class="block text-purple-600 hover:text-purple-800 text-sm font-semibold transition-colors">
                            <i class="fas fa-sign-in-alt mr-1"></i> มีบัญชีแล้ว? เข้าสู่ระบบ
                        </a>
                        <a href="{{ route('home') }}" class="block text-gray-600 hover:text-gray-800 text-sm transition-colors">
                            <i class="fas fa-arrow-left mr-1"></i> กลับหน้าแรก
                        </a>
                    </div>
                </div>

                <!-- Right Side - Features & Stats -->
                <div class="hidden lg:block space-y-6 fade-in-up" style="animation-delay: 0.2s;">
                    <!-- Live Stats -->
                    <div class="stat-card rounded-2xl shadow-2xl p-8">
                        <h3 class="text-2xl font-bold text-gray-800 mb-6 flex items-center">
                            <i class="fas fa-chart-line text-green-500 mr-3"></i>
                            สถิติสด (Live Stats)
                        </h3>

                        <div class="space-y-6">
                            <div class="flex items-center justify-between p-4 bg-gradient-to-r from-green-50 to-green-100 rounded-xl">
                                <div class="flex items-center">
                                    <div class="bg-green-500 text-white rounded-full w-12 h-12 flex items-center justify-center mr-4">
                                        <i class="fas fa-users text-xl"></i>
                                    </div>
                                    <div>
                                        <p class="text-sm text-gray-600 font-medium">สมาชิกทั้งหมด</p>
                                        <p class="text-3xl font-bold text-gray-800" id="memberCount">0</p>
                                    </div>
                                </div>
                                <div class="text-green-600 text-sm font-semibold pulse-animate">
                                    <i class="fas fa-arrow-up mr-1"></i>+<span id="memberIncrement">0</span>
                                </div>
                            </div>

                            <div class="flex items-center justify-between p-4 bg-gradient-to-r from-blue-50 to-blue-100 rounded-xl">
                                <div class="flex items-center">
                                    <div class="bg-blue-500 text-white rounded-full w-12 h-12 flex items-center justify-center mr-4">
                                        <i class="fas fa-money-bill-wave text-xl"></i>
                                    </div>
                                    <div>
                                        <p class="text-sm text-gray-600 font-medium">รายได้สะสม</p>
                                        <p class="text-3xl font-bold text-gray-800">฿<span id="totalEarnings">0</span></p>
                                    </div>
                                </div>
                                <div class="text-blue-600 text-sm font-semibold pulse-animate">
                                    <i class="fas fa-arrow-up mr-1"></i>+฿<span id="earningsIncrement">0</span>
                                </div>
                            </div>

                            <div class="flex items-center justify-between p-4 bg-gradient-to-r from-purple-50 to-purple-100 rounded-xl">
                                <div class="flex items-center">
                                    <div class="bg-purple-500 text-white rounded-full w-12 h-12 flex items-center justify-center mr-4">
                                        <i class="fas fa-user-plus text-xl"></i>
                                    </div>
                                    <div>
                                        <p class="text-sm text-gray-600 font-medium">กำลังสมัครวันนี้</p>
                                        <p class="text-3xl font-bold text-gray-800" id="todaySignups">0</p>
                                    </div>
                                </div>
                                <div class="text-purple-600 text-sm font-semibold">
                                    <i class="fas fa-fire mr-1"></i>Hot!
                                </div>
                            </div>
                        </div>

                        <div class="mt-6 pt-6 border-t border-gray-200">
                            <div class="flex items-center text-sm text-gray-600">
                                <div class="w-2 h-2 bg-green-500 rounded-full mr-2 pulse-animate"></div>
                                <span class="font-medium">อัพเดทแบบเรียลไทม์</span>
                            </div>
                        </div>
                    </div>

                    <!-- Benefits -->
                    <div class="stat-card rounded-2xl shadow-2xl p-8">
                        <h3 class="text-2xl font-bold text-gray-800 mb-6 flex items-center">
                            <i class="fas fa-star text-yellow-500 mr-3"></i>
                            ทำไมต้องเลือกเรา?
                        </h3>

                        <div class="space-y-4">
                            <div class="flex items-start">
                                <div class="bg-purple-100 text-purple-600 rounded-lg w-10 h-10 flex items-center justify-center mr-3 flex-shrink-0">
                                    <i class="fas fa-percentage"></i>
                                </div>
                                <div>
                                    <p class="font-semibold text-gray-800">คอมมิชชั่นสูง</p>
                                    <p class="text-sm text-gray-600">รับค่าคอมมิชชั่นสูงสุดในตลาด</p>
                                </div>
                            </div>

                            <div class="flex items-start">
                                <div class="bg-green-100 text-green-600 rounded-lg w-10 h-10 flex items-center justify-center mr-3 flex-shrink-0">
                                    <i class="fas fa-clock"></i>
                                </div>
                                <div>
                                    <p class="font-semibold text-gray-800">ถอนเงินรวดเร็ว</p>
                                    <p class="text-sm text-gray-600">ระบบถอนเงินอัตโนมัติ ภายใน 24 ชม.</p>
                                </div>
                            </div>

                            <div class="flex items-start">
                                <div class="bg-blue-100 text-blue-600 rounded-lg w-10 h-10 flex items-center justify-center mr-3 flex-shrink-0">
                                    <i class="fas fa-headset"></i>
                                </div>
                                <div>
                                    <p class="font-semibold text-gray-800">ซัพพอร์ต 24/7</p>
                                    <p class="text-sm text-gray-600">ทีมงานพร้อมช่วยเหลือตลอด 24 ชั่วโมง</p>
                                </div>
                            </div>

                            <div class="flex items-start">
                                <div class="bg-pink-100 text-pink-600 rounded-lg w-10 h-10 flex items-center justify-center mr-3 flex-shrink-0">
                                    <i class="fas fa-shield-alt"></i>
                                </div>
                                <div>
                                    <p class="font-semibold text-gray-800">ปลอดภัย 100%</p>
                                    <p class="text-sm text-gray-600">ระบบรักษาความปลอดภัยระดับสูง</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Testimonial -->
                    <div class="stat-card rounded-2xl shadow-2xl p-8">
                        <div class="flex items-center mb-4">
                            <img src="https://ui-avatars.com/api/?name=S&background=667eea&color=fff&size=50" alt="User" class="w-12 h-12 rounded-full mr-3">
                            <div>
                                <p class="font-bold text-gray-800">สมชาย ใจดี</p>
                                <div class="flex text-yellow-400">
                                    <i class="fas fa-star"></i>
                                    <i class="fas fa-star"></i>
                                    <i class="fas fa-star"></i>
                                    <i class="fas fa-star"></i>
                                    <i class="fas fa-star"></i>
                                </div>
                            </div>
                        </div>
                        <p class="text-gray-600 italic">"ระบบใช้งานง่าย รายได้ดี ถอนเงินรวดเร็ว แนะนำเลยครับ!"</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Animated counter function
        function animateCounter(element, start, end, duration, decimals = 0) {
            const range = end - start;
            const increment = range / (duration / 16);
            let current = start;

            const timer = setInterval(() => {
                current += increment;
                if ((increment > 0 && current >= end) || (increment < 0 && current <= end)) {
                    current = end;
                    clearInterval(timer);
                }
                element.textContent = decimals > 0
                    ? current.toFixed(decimals).replace(/\B(?=(\d{3})+(?!\d))/g, ",")
                    : Math.floor(current).toLocaleString('en-US');
            }, 16);
        }

        // Initialize fake stats with realistic numbers
        document.addEventListener('DOMContentLoaded', function() {
            // Base numbers (will increase over time)
            const baseMembers = 8547;
            const baseEarnings = 2847653.50;
            const baseTodaySignups = 127;

            // Animate initial counters
            animateCounter(document.getElementById('memberCount'), 0, baseMembers, 2000);
            animateCounter(document.getElementById('totalEarnings'), 0, baseEarnings, 2000, 2);
            animateCounter(document.getElementById('todaySignups'), 0, baseTodaySignups, 2000);

            // Set initial increments
            document.getElementById('memberIncrement').textContent = '3';
            document.getElementById('earningsIncrement').textContent = '1,250';

            // Simulate live updates
            setInterval(() => {
                const memberEl = document.getElementById('memberCount');
                const earningsEl = document.getElementById('totalEarnings');
                const todayEl = document.getElementById('todaySignups');
                const memberIncEl = document.getElementById('memberIncrement');
                const earningsIncEl = document.getElementById('earningsIncrement');

                // Random increments
                const memberInc = Math.floor(Math.random() * 3) + 1; // 1-3
                const earningsInc = (Math.random() * 2000 + 500).toFixed(2); // 500-2500
                const todayInc = Math.floor(Math.random() * 2) + 1; // 1-2

                // Update increments display
                memberIncEl.textContent = memberInc;
                earningsIncEl.textContent = parseFloat(earningsInc).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});

                // Update main counts
                const currentMembers = parseInt(memberEl.textContent.replace(/,/g, ''));
                const currentEarnings = parseFloat(earningsEl.textContent.replace(/,/g, ''));
                const currentToday = parseInt(todayEl.textContent.replace(/,/g, ''));

                animateCounter(memberEl, currentMembers, currentMembers + memberInc, 800);
                animateCounter(earningsEl, currentEarnings, currentEarnings + parseFloat(earningsInc), 800, 2);
                animateCounter(todayEl, currentToday, currentToday + todayInc, 800);

            }, 5000); // Update every 5 seconds
        });
    </script>
</body>
</html>
