<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @php
        $appName = \App\Models\Setting::get('app_name', 'TP-Affiliate');
        $logo = \App\Models\Setting::get('logo');
        $logoAuthWidth = \App\Models\Setting::get('logo_auth_width', 72);
        $logoAuthHeight = \App\Models\Setting::get('logo_auth_height', 72);
    @endphp
    <title>สมัครสมาชิก - {{ $appName }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    @if(config('turnstile.enabled') && config('turnstile.points.register'))
    <script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
    @endif
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

        @keyframes fadeIn {
            from {
                opacity: 0;
            }
            to {
                opacity: 1;
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

        .fade-transition {
            animation: fadeIn 0.5s ease-in-out;
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
    <div class="min-h-screen flex items-center justify-center px-4 py-6 md:py-12">
        <div class="max-w-6xl w-full">
            <!-- Mobile Stats Banner - Show on mobile only -->
            <div class="lg:hidden mb-6 space-y-4 fade-in-up">
                <!-- Compact Stats -->
                <div class="glass-effect rounded-xl shadow-lg p-4">
                    <div class="grid grid-cols-3 gap-3">
                        <div class="text-center">
                            <div class="text-green-600 text-xs font-semibold mb-1">
                                <i class="fas fa-users"></i> สมาชิก
                            </div>
                            <div class="text-xl font-bold text-gray-800" id="memberCountMobile">0</div>
                        </div>
                        <div class="text-center">
                            <div class="text-blue-600 text-xs font-semibold mb-1">
                                <i class="fas fa-money-bill-wave"></i> รายได้
                            </div>
                            <div class="text-xl font-bold text-gray-800">฿<span id="totalEarningsMobile">0</span></div>
                        </div>
                        <div class="text-center">
                            <div class="text-purple-600 text-xs font-semibold mb-1">
                                <i class="fas fa-fire"></i> วันนี้
                            </div>
                            <div class="text-xl font-bold text-gray-800" id="todaySignupsMobile">0</div>
                        </div>
                    </div>
                    <div class="mt-3 pt-3 border-t border-gray-200">
                        <div class="flex items-center justify-center text-xs text-gray-600">
                            <div class="w-2 h-2 bg-green-500 rounded-full mr-2 pulse-animate"></div>
                            <span class="font-medium">อัพเดทแบบเรียลไทม์</span>
                        </div>
                    </div>
                </div>

                <!-- Mobile Testimonial -->
                <div class="glass-effect rounded-xl shadow-lg p-4 fade-transition" id="mobileTestimonial">
                    <div class="flex items-center mb-2">
                        <img src="" alt="User" class="w-10 h-10 rounded-full mr-3" id="mobileTestimonialAvatar">
                        <div>
                            <p class="font-bold text-gray-800 text-sm" id="mobileTestimonialName"></p>
                            <div class="flex text-yellow-400 text-xs">
                                <i class="fas fa-star"></i>
                                <i class="fas fa-star"></i>
                                <i class="fas fa-star"></i>
                                <i class="fas fa-star"></i>
                                <i class="fas fa-star"></i>
                            </div>
                        </div>
                    </div>
                    <p class="text-gray-600 italic text-sm" id="mobileTestimonialText"></p>
                </div>
            </div>

            <div class="grid lg:grid-cols-2 gap-6 lg:gap-8">
                <!-- Left Side - Registration Form -->
                <div class="glass-effect rounded-2xl shadow-2xl p-6 md:p-8 fade-in-up">
                    <div class="text-center mb-6 md:mb-8">
                        @if($logo)
                            <div class="mb-4 flex justify-center">
                                <img src="{{ asset($logo) }}" alt="{{ $appName }} Logo" style="width: {{ $logoAuthWidth }}px; height: {{ $logoAuthHeight }}px;" class="object-contain">
                            </div>
                        @else
                            <h1 class="text-3xl md:text-5xl font-bold bg-gradient-to-r from-purple-600 to-pink-600 bg-clip-text text-transparent mb-2">{{ $appName }}</h1>
                        @endif
                        <p class="text-gray-600 text-base md:text-lg font-semibold mb-2">สมัครสมาชิก</p>
                        <p class="text-gray-500 text-sm md:text-base">เริ่มต้นสร้างรายได้วันนี้</p>
                        <div class="mt-3 md:mt-4 inline-flex items-center px-3 md:px-4 py-2 bg-gradient-to-r from-green-400 to-blue-500 text-white rounded-full text-xs md:text-sm font-semibold pulse-animate">
                            <i class="fas fa-check-circle mr-2"></i>
                            ฟรี! ไม่มีค่าใช้จ่าย
                        </div>

                        @if(!empty($defaultSponsorName) && empty($referralCode))
                            <div class="mt-4 bg-gradient-to-r from-indigo-50 to-purple-50 border border-indigo-200 rounded-xl p-3 md:p-4">
                                <div class="flex items-center justify-center text-indigo-700">
                                    <i class="fas fa-link text-xl md:text-2xl mr-2 md:mr-3"></i>
                                    <div class="text-left">
                                        <p class="text-xs font-medium text-indigo-600">คุณกำลังต่อสายงานกับ</p>
                                        <p class="text-sm md:text-base font-bold">{{ $defaultSponsorName }}</p>
                                    </div>
                                </div>
                            </div>
                        @endif
                    </div>

                    @if ($errors->any())
                        <div class="mb-4 bg-red-50 border-l-4 border-red-500 text-red-700 px-4 py-3 rounded-lg shadow-md">
                            <div class="flex items-center mb-2">
                                <i class="fas fa-exclamation-triangle mr-2"></i>
                                <strong>พบข้อผิดพลาด:</strong>
                            </div>
                            <ul class="list-disc list-inside space-y-1 text-sm">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    @php
                        $lineSettings = \App\Models\LineOaSetting::getActive();
                        $lineRequired = $lineSettings && $lineSettings->require_line_registration;
                        $showLineRegister = $lineSettings && $lineSettings->login_channel_id && $lineSettings->channel_secret;
                    @endphp

                    @if($lineRequired && $showLineRegister)
                        <!-- LINE Required Registration Mode -->
                        <div class="text-center space-y-6">
                            <div class="p-4 md:p-6 bg-gradient-to-r from-green-50 to-emerald-50 border-2 border-green-200 rounded-xl">
                                <div class="mb-4">
                                    <div class="w-16 h-16 md:w-20 md:h-20 bg-green-500 rounded-full flex items-center justify-center mx-auto mb-4">
                                        <svg class="w-10 h-10 md:w-12 md:h-12 text-white" viewBox="0 0 24 24" fill="currentColor">
                                            <path d="M19.365 9.863c.349 0 .63.285.63.631 0 .345-.281.63-.63.63H17.61v1.125h1.755c.349 0 .63.283.63.63 0 .344-.281.629-.63.629h-2.386c-.345 0-.627-.285-.627-.629V8.108c0-.345.282-.63.63-.63h2.386c.346 0 .627.285.627.63 0 .349-.281.63-.63.63H17.61v1.125h1.755zm-3.855 3.016c0 .27-.174.51-.432.596-.064.021-.133.031-.199.031-.211 0-.391-.09-.51-.25l-2.443-3.317v2.94c0 .344-.279.629-.631.629-.346 0-.626-.285-.626-.629V8.108c0-.27.173-.51.43-.595.06-.023.136-.033.194-.033.195 0 .375.104.495.254l2.462 3.33V8.108c0-.345.282-.63.63-.63.345 0 .63.285.63.63v4.771zm-5.741 0c0 .344-.282.629-.631.629-.345 0-.627-.285-.627-.629V8.108c0-.345.282-.63.63-.63.346 0 .628.285.628.63v4.771zm-2.466.629H4.917c-.345 0-.63-.285-.63-.629V8.108c0-.345.285-.63.63-.63.348 0 .63.285.63.63v4.141h1.756c.348 0 .629.283.629.63 0 .344-.282.629-.629.629M24 10.314C24 4.943 18.615.572 12 .572S0 4.943 0 10.314c0 4.811 4.27 8.842 10.035 9.608.391.082.923.258 1.058.59.12.301.079.766.038 1.08l-.164 1.02c-.045.301-.24 1.186 1.049.645 1.291-.539 6.916-4.078 9.436-6.975C23.176 14.393 24 12.458 24 10.314"/>
                                        </svg>
                                    </div>
                                    <h3 class="text-lg md:text-xl font-bold text-gray-800 mb-2">ต้องการ LINE เพื่อสมัครสมาชิก</h3>
                                    <p class="text-sm md:text-base text-gray-600">กรุณาทำตามขั้นตอนด้านล่างเพื่อสมัครสมาชิก</p>
                                </div>

                                <div class="bg-white rounded-lg p-4 md:p-6 space-y-4">
                                    <div class="flex items-start text-left space-x-3">
                                        <div class="flex-shrink-0 w-8 h-8 bg-green-500 text-white rounded-full flex items-center justify-center font-bold text-sm">1</div>
                                        <div class="flex-1">
                                            <p class="font-semibold text-gray-800 text-sm md:text-base">เพิ่มเพื่อน LINE Official Account</p>
                                            <p class="text-xs md:text-sm text-gray-600 mt-1">สแกน QR Code หรือกดปุ่มเพิ่มเพื่อนด้านล่าง</p>
                                        </div>
                                    </div>

                                    <!-- QR Code and Add Friend Button -->
                                    @if($lineSettings->messaging_channel_id)
                                    <div class="flex flex-col items-center space-y-4 py-4">
                                        <!-- QR Code -->
                                        <div class="bg-white p-4 rounded-xl border-2 border-gray-200 shadow-md">
                                            <img src="https://qr-official.line.me/gs/M_{{ $lineSettings->messaging_channel_id }}_GW.png"
                                                 alt="LINE OA QR Code"
                                                 class="w-48 h-48 md:w-56 md:h-56">
                                        </div>

                                        <!-- Add Friend Button -->
                                        <a href="#"
                                           onclick="addLineFriend(event)"
                                           class="w-full max-w-xs px-6 py-4 bg-gradient-to-r from-green-500 to-emerald-600 text-white rounded-xl font-bold text-base md:text-lg hover:from-green-600 hover:to-emerald-700 transform hover:scale-105 transition-all duration-300 shadow-lg hover:shadow-xl flex items-center justify-center">
                                            <i class="fas fa-user-plus mr-2"></i>
                                            <span id="addFriendText">เพิ่มเพื่อน LINE</span>
                                        </a>
                                    </div>
                                    @endif

                                    <div class="flex items-start text-left space-x-3">
                                        <div class="flex-shrink-0 w-8 h-8 bg-green-500 text-white rounded-full flex items-center justify-center font-bold text-sm">2</div>
                                        <div class="flex-1">
                                            <p class="font-semibold text-gray-800 text-sm md:text-base">กดปุ่มสมัครด้วย LINE</p>
                                            <p class="text-xs md:text-sm text-gray-600 mt-1">หลังจากเพิ่มเพื่อนแล้ว กดปุ่มด้านล่างเพื่อสมัครสมาชิก</p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- LINE Register Button -->
                            <a href="{{ route('line.login') }}{{ request('ref') ? '?ref=' . request('ref') : '' }}"
                               class="block w-full px-6 py-4 border-2 border-green-500 rounded-xl text-green-700 bg-white hover:bg-green-50 font-bold text-base md:text-lg shadow-md hover:shadow-lg transition duration-300 group transform hover:scale-105">
                                <svg class="w-6 h-6 md:w-7 md:h-7 inline-block mr-3" viewBox="0 0 24 24" fill="#06C755">
                                    <path d="M19.365 9.863c.349 0 .63.285.63.631 0 .345-.281.63-.63.63H17.61v1.125h1.755c.349 0 .63.283.63.63 0 .344-.281.629-.63.629h-2.386c-.345 0-.627-.285-.627-.629V8.108c0-.345.282-.63.63-.63h2.386c.346 0 .627.285.627.63 0 .349-.281.63-.63.63H17.61v1.125h1.755zm-3.855 3.016c0 .27-.174.51-.432.596-.064.021-.133.031-.199.031-.211 0-.391-.09-.51-.25l-2.443-3.317v2.94c0 .344-.279.629-.631.629-.346 0-.626-.285-.626-.629V8.108c0-.27.173-.51.43-.595.06-.023.136-.033.194-.033.195 0 .375.104.495.254l2.462 3.33V8.108c0-.345.282-.63.63-.63.345 0 .63.285.63.63v4.771zm-5.741 0c0 .344-.282.629-.631.629-.345 0-.627-.285-.627-.629V8.108c0-.345.282-.63.63-.63.346 0 .628.285.628.63v4.771zm-2.466.629H4.917c-.345 0-.63-.285-.63-.629V8.108c0-.345.285-.63.63-.63.348 0 .63.285.63.63v4.141h1.756c.348 0 .629.283.629.63 0 .344-.282.629-.629.629M24 10.314C24 4.943 18.615.572 12 .572S0 4.943 0 10.314c0 4.811 4.27 8.842 10.035 9.608.391.082.923.258 1.058.59.12.301.079.766.038 1.08l-.164 1.02c-.045.301-.24 1.186 1.049.645 1.291-.539 6.916-4.078 9.436-6.975C23.176 14.393 24 12.458 24 10.314"/>
                                </svg>
                                <span class="group-hover:text-green-800 transition">สมัครสมาชิกด้วย LINE</span>
                            </a>

                            <div class="text-xs md:text-sm text-gray-500 italic">
                                <i class="fas fa-info-circle mr-1"></i>
                                ต้องเพิ่มเพื่อน LINE Official Account ก่อนจึงจะสมัครสมาชิกได้
                            </div>
                        </div>

                        <script>
                        function addLineFriend(event) {
                            event.preventDefault();
                            const lineId = '{{ $lineSettings->messaging_channel_id }}';

                            // Detect if mobile
                            const isMobile = /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent);

                            if (isMobile) {
                                // On mobile: Open LINE app to add friend
                                window.location.href = 'https://line.me/R/ti/p/@' + lineId;
                            } else {
                                // On desktop: Show message to scan QR code
                                alert('กรุณาใช้โทรศัพท์สแกน QR Code เพื่อเพิ่มเพื่อน หรือค้นหา @' + lineId + ' ในแอพ LINE');
                            }
                        }
                        </script>
                    @else
                        <!-- Normal Registration Form -->
                        <form method="POST" action="{{ route('register') }}">
                        @csrf

                        @if (!empty($referralCode))
                            <div class="mb-4 bg-gradient-to-r from-green-50 to-blue-50 border-l-4 border-green-500 text-green-700 px-3 md:px-4 py-3 rounded-lg shadow-md">
                                <div class="flex items-center">
                                    <i class="fas fa-user-check text-xl md:text-2xl mr-3"></i>
                                    <div>
                                        <p class="font-semibold text-sm md:text-base">คุณถูกแนะนำโดย</p>
                                        <p class="text-xs md:text-sm">รหัส: <strong class="text-base md:text-lg">{{ $referralCode }}</strong></p>
                                    </div>
                                </div>
                            </div>
                        @endif

                        <div class="space-y-3 md:space-y-4">
                            <div>
                                <label for="name" class="block text-xs md:text-sm font-semibold text-gray-700 mb-2">
                                    <i class="fas fa-user mr-1 text-purple-500"></i> ชื่อ-นามสกุล
                                </label>
                                <input type="text" name="name" id="name" value="{{ old('name') }}"
                                       class="w-full px-3 md:px-4 py-2 md:py-3 border-2 border-gray-200 rounded-xl input-glow focus:ring-0 focus:border-purple-500 transition-all text-sm md:text-base @error('name') border-red-500 @enderror"
                                       placeholder="กรอกชื่อ-นามสกุลของคุณ"
                                       required autofocus>
                                @error('name')
                                    <p class="mt-1 text-xs md:text-sm text-red-600"><i class="fas fa-exclamation-circle mr-1"></i>{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label for="email" class="block text-xs md:text-sm font-semibold text-gray-700 mb-2">
                                    <i class="fas fa-envelope mr-1 text-purple-500"></i> อีเมล
                                </label>
                                <input type="email" name="email" id="email" value="{{ old('email') }}"
                                       class="w-full px-3 md:px-4 py-2 md:py-3 border-2 border-gray-200 rounded-xl input-glow focus:ring-0 focus:border-purple-500 transition-all text-sm md:text-base @error('email') border-red-500 @enderror"
                                       placeholder="your@email.com"
                                       required>
                                @error('email')
                                    <p class="mt-1 text-xs md:text-sm text-red-600"><i class="fas fa-exclamation-circle mr-1"></i>{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label for="password" class="block text-xs md:text-sm font-semibold text-gray-700 mb-2">
                                    <i class="fas fa-lock mr-1 text-purple-500"></i> รหัสผ่าน
                                </label>
                                <input type="password" name="password" id="password"
                                       class="w-full px-3 md:px-4 py-2 md:py-3 border-2 border-gray-200 rounded-xl input-glow focus:ring-0 focus:border-purple-500 transition-all text-sm md:text-base @error('password') border-red-500 @enderror"
                                       placeholder="สร้างรหัสผ่านที่ปลอดภัย"
                                       required>
                                @error('password')
                                    <p class="mt-1 text-xs md:text-sm text-red-600"><i class="fas fa-exclamation-circle mr-1"></i>{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label for="password_confirmation" class="block text-xs md:text-sm font-semibold text-gray-700 mb-2">
                                    <i class="fas fa-lock mr-1 text-purple-500"></i> ยืนยันรหัสผ่าน
                                </label>
                                <input type="password" name="password_confirmation" id="password_confirmation"
                                       class="w-full px-3 md:px-4 py-2 md:py-3 border-2 border-gray-200 rounded-xl input-glow focus:ring-0 focus:border-purple-500 transition-all text-sm md:text-base"
                                       placeholder="กรอกรหัสผ่านอีกครั้ง"
                                       required>
                            </div>

                            <div>
                                <label for="referral_code" class="block text-xs md:text-sm font-semibold text-gray-700 mb-2">
                                    <i class="fas fa-gift mr-1 text-purple-500"></i> รหัสแนะนำ (ถ้ามี)
                                </label>
                                <input type="text" name="referral_code" id="referral_code" value="{{ old('referral_code', $referralCode ?? '') }}"
                                       class="w-full px-3 md:px-4 py-2 md:py-3 border-2 border-gray-200 rounded-xl input-glow focus:ring-0 focus:border-purple-500 transition-all text-sm md:text-base @error('referral_code') border-red-500 @enderror"
                                       placeholder="กรอกรหัสแนะนำจากผู้แนะนำ">
                                @error('referral_code')
                                    <p class="mt-1 text-xs md:text-sm text-red-600"><i class="fas fa-exclamation-circle mr-1"></i>{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                        @if(config('turnstile.enabled') && config('turnstile.points.register'))
                        <div class="mt-4 flex justify-center">
                            <div class="cf-turnstile"
                                 data-sitekey="{{ config('turnstile.site_key') }}"
                                 data-theme="{{ config('turnstile.theme') }}"
                                 data-size="{{ config('turnstile.size') }}">
                            </div>
                        </div>
                        @endif

                            <button type="submit" class="mt-6 w-full bg-gradient-to-r from-purple-600 to-pink-600 text-white py-3 md:py-4 rounded-xl font-bold text-base md:text-lg hover:from-purple-700 hover:to-pink-700 transform hover:scale-105 transition-all duration-300 shadow-lg hover:shadow-2xl">
                                <i class="fas fa-rocket mr-2"></i> สมัครสมาชิกเลย!
                            </button>
                        </form>

                        @if($showLineRegister)
                        <div class="mt-4 md:mt-6">
                            <div class="relative flex items-center justify-center">
                                <div class="border-t border-gray-300 w-full"></div>
                                <span class="glass-effect px-3 md:px-4 text-gray-500 text-xs md:text-sm font-medium absolute">หรือ</span>
                            </div>

                            <a href="{{ route('line.login') }}{{ request('ref') ? '?ref=' . request('ref') : '' }}"
                               class="mt-4 md:mt-6 w-full flex items-center justify-center px-4 md:px-6 py-3 md:py-4 border-2 border-gray-300 rounded-xl text-gray-700 bg-white hover:bg-gray-50 font-bold text-base md:text-lg shadow-md hover:shadow-lg transition duration-300 group transform hover:scale-105">
                                <svg class="w-5 h-5 md:w-6 md:h-6 mr-2 md:mr-3" viewBox="0 0 24 24" fill="#06C755">
                                    <path d="M19.365 9.863c.349 0 .63.285.63.631 0 .345-.281.63-.63.63H17.61v1.125h1.755c.349 0 .63.283.63.63 0 .344-.281.629-.63.629h-2.386c-.345 0-.627-.285-.627-.629V8.108c0-.345.282-.63.63-.63h2.386c.346 0 .627.285.627.63 0 .349-.281.63-.63.63H17.61v1.125h1.755zm-3.855 3.016c0 .27-.174.51-.432.596-.064.021-.133.031-.199.031-.211 0-.391-.09-.51-.25l-2.443-3.317v2.94c0 .344-.279.629-.631.629-.346 0-.626-.285-.626-.629V8.108c0-.27.173-.51.43-.595.06-.023.136-.033.194-.033.195 0 .375.104.495.254l2.462 3.33V8.108c0-.345.282-.63.63-.63.345 0 .63.285.63.63v4.771zm-5.741 0c0 .344-.282.629-.631.629-.345 0-.627-.285-.627-.629V8.108c0-.345.282-.63.63-.63.346 0 .628.285.628.63v4.771zm-2.466.629H4.917c-.345 0-.63-.285-.63-.629V8.108c0-.345.285-.63.63-.63.348 0 .63.285.63.63v4.141h1.756c.348 0 .629.283.629.63 0 .344-.282.629-.629.629M24 10.314C24 4.943 18.615.572 12 .572S0 4.943 0 10.314c0 4.811 4.27 8.842 10.035 9.608.391.082.923.258 1.058.59.12.301.079.766.038 1.08l-.164 1.02c-.045.301-.24 1.186 1.049.645 1.291-.539 6.916-4.078 9.436-6.975C23.176 14.393 24 12.458 24 10.314"/>
                                </svg>
                                <span class="group-hover:text-gray-900 transition">สมัครด้วย LINE</span>
                            </a>
                        </div>
                        @endif
                    @endif

                    <div class="mt-4 md:mt-6 text-center space-y-2 md:space-y-3">
                        <a href="{{ route('login') }}" class="block text-purple-600 hover:text-purple-800 text-xs md:text-sm font-semibold transition-colors">
                            <i class="fas fa-sign-in-alt mr-1"></i> มีบัญชีแล้ว? เข้าสู่ระบบ
                        </a>
                        <a href="{{ route('home') }}" class="block text-gray-600 hover:text-gray-800 text-xs md:text-sm transition-colors">
                            <i class="fas fa-arrow-left mr-1"></i> กลับหน้าแรก
                        </a>
                    </div>
                </div>

                <!-- Right Side - Features & Stats (Desktop only) -->
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

                    <!-- Testimonial (Desktop) -->
                    <div class="stat-card rounded-2xl shadow-2xl p-8 fade-transition" id="desktopTestimonial">
                        <div class="flex items-center mb-4">
                            <img src="" alt="User" class="w-12 h-12 rounded-full mr-3" id="desktopTestimonialAvatar">
                            <div>
                                <p class="font-bold text-gray-800" id="desktopTestimonialName"></p>
                                <div class="flex text-yellow-400">
                                    <i class="fas fa-star"></i>
                                    <i class="fas fa-star"></i>
                                    <i class="fas fa-star"></i>
                                    <i class="fas fa-star"></i>
                                    <i class="fas fa-star"></i>
                                </div>
                            </div>
                        </div>
                        <p class="text-gray-600 italic" id="desktopTestimonialText"></p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // 20 Testimonials
        const testimonials = [
            { name: "สมชาย ใจดี", text: "ระบบใช้งานง่าย รายได้ดี ถอนเงินรวดเร็ว แนะนำเลยครับ!" },
            { name: "วิภา สุขใจ", text: "รายได้เสริมที่ดีมาก ทำง่าย ได้เงินจริง แนะนำให้เพื่อนๆ หลายคนแล้ว" },
            { name: "ประยุทธ ทองดี", text: "ถอนเงินไว มาก ไม่ถึง 24 ชม. เงินเข้าบัญชีแล้ว สุดยอด!" },
            { name: "นิภา จันทร์สว่าง", text: "เริ่มต้นง่าย ไม่ซับซ้อน คอมมิชชั่นดี ชอบมากค่ะ" },
            { name: "อนุชา ศรีสุข", text: "ระบบโปร่งใส ดูสถิติได้ชัดเจน มั่นใจในการใช้งาน" },
            { name: "ปิยะ วงศ์สวัสดิ์", text: "ได้รายได้เพิ่มทุกวัน แค่แนะนำเพื่อนก็มีรายได้แล้ว" },
            { name: "รัตนา มั่งคั่ง", text: "ทีมงานซัพพอร์ตดีมาก ตอบเร็ว แก้ปัญหาได้ทันที" },
            { name: "สุรชัย ร่ำรวย", text: "ใช้งานมา 3 เดือน ได้เงินแล้วหลายหมื่น ขอบคุณครับ" },
            { name: "มณี ทองคำ", text: "สมัครง่าย ใช้งานไม่ยุ่งยาก เหมาะกับคนที่ไม่เก่งคอมพิวเตอร์" },
            { name: "ชัยวัฒน์ เจริญสุข", text: "คอมมิชชั่นสูงกว่าที่อื่นจริงๆ คุ้มค่ามาก" },
            { name: "พิมพ์ใจ สดใส", text: "ระบบดูแลลูกทีมได้ดี มีรายได้แบบ passive income" },
            { name: "เกรียงไกร วิชัยดิษฐ", text: "เว็บไซต์ใช้งานง่าย สะดวก ดูข้อมูลได้ครบถ้วน" },
            { name: "ศิริพร ปานกลาง", text: "ได้รายได้จากที่บ้านค่ะ ไม่ต้องเดินทาง ดีมาก" },
            { name: "บุญช่วย มีสุข", text: "เริ่มจากศูนย์ ตอนนี้มีลูกทีมเยอะ รายได้งาม" },
            { name: "แววดาว สุวรรณ", text: "ระบบปลอดภัย ข้อมูลส่วนตัวไม่รั่วไหล มั่นใจค่ะ" },
            { name: "ธนพล เศรษฐี", text: "ได้เงินจริง จ่ายจริง ไม่มีโกง แนะนำเลยครับ" },
            { name: "สุกัญญา ใจบุญ", text: "ช่วงโควิดทำให้มีรายได้เสริม ขอบคุณมากค่ะ" },
            { name: "วีระ พงศ์สวัสดิ์", text: "แนะนำเพื่อนไปแล้ว 50 คน ทุกคนพอใจกันหมด" },
            { name: "จิราพร สมหวัง", text: "ระบบเข้าใจง่าย มีวิดีโอสอนใช้งาน ดีมากค่ะ" },
            { name: "สมพงษ์ เจริญพร", text: "รายได้ดีกว่าที่คิด ขอบคุณที่มีระบบดีๆ แบบนี้" }
        ];

        let lastTestimonialIndex = -1;

        // Get random testimonial (not repeat the same one)
        function getRandomTestimonial() {
            let randomIndex;
            do {
                randomIndex = Math.floor(Math.random() * testimonials.length);
            } while (randomIndex === lastTestimonialIndex && testimonials.length > 1);

            lastTestimonialIndex = randomIndex;
            return testimonials[randomIndex];
        }

        // Update testimonial display
        function updateTestimonial() {
            const testimonial = getRandomTestimonial();
            const initial = testimonial.name.charAt(0);
            const avatarUrl = `https://ui-avatars.com/api/?name=${initial}&background=${Math.random() > 0.5 ? '667eea' : 'f093fb'}&color=fff&size=50`;

            // Update desktop
            const desktopContainer = document.getElementById('desktopTestimonial');
            if (desktopContainer) {
                document.getElementById('desktopTestimonialName').textContent = testimonial.name;
                document.getElementById('desktopTestimonialText').textContent = `"${testimonial.text}"`;
                document.getElementById('desktopTestimonialAvatar').src = avatarUrl;

                // Add fade transition
                desktopContainer.style.opacity = '0';
                setTimeout(() => {
                    desktopContainer.style.opacity = '1';
                }, 100);
            }

            // Update mobile
            const mobileContainer = document.getElementById('mobileTestimonial');
            if (mobileContainer) {
                document.getElementById('mobileTestimonialName').textContent = testimonial.name;
                document.getElementById('mobileTestimonialText').textContent = `"${testimonial.text}"`;
                document.getElementById('mobileTestimonialAvatar').src = avatarUrl;

                // Add fade transition
                mobileContainer.style.opacity = '0';
                setTimeout(() => {
                    mobileContainer.style.opacity = '1';
                }, 100);
            }
        }

        // Animated counter function
        function animateCounter(element, start, end, duration, decimals = 0) {
            if (!element) return;

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

        // Format number for display
        function formatNumber(num, decimals = 0) {
            if (decimals > 0) {
                return num.toFixed(decimals).replace(/\B(?=(\d{3})+(?!\d))/g, ",");
            }
            return Math.floor(num).toLocaleString('en-US');
        }

        // Initialize fake stats with realistic numbers
        document.addEventListener('DOMContentLoaded', function() {
            // Base numbers (will increase over time)
            const baseMembers = 8547;
            const baseEarnings = 2847653.50;
            const baseTodaySignups = 127;

            // Animate initial counters - Desktop
            animateCounter(document.getElementById('memberCount'), 0, baseMembers, 2000);
            animateCounter(document.getElementById('totalEarnings'), 0, baseEarnings, 2000, 2);
            animateCounter(document.getElementById('todaySignups'), 0, baseTodaySignups, 2000);

            // Animate initial counters - Mobile
            animateCounter(document.getElementById('memberCountMobile'), 0, baseMembers, 2000);
            animateCounter(document.getElementById('totalEarningsMobile'), 0, baseEarnings, 2000, 2);
            animateCounter(document.getElementById('todaySignupsMobile'), 0, baseTodaySignups, 2000);

            // Set initial increments
            const memberIncEl = document.getElementById('memberIncrement');
            const earningsIncEl = document.getElementById('earningsIncrement');
            if (memberIncEl) memberIncEl.textContent = '3';
            if (earningsIncEl) earningsIncEl.textContent = '1,250';

            // Initialize first testimonial
            updateTestimonial();

            // Rotate testimonials every 5 seconds
            setInterval(updateTestimonial, 5000);

            // Simulate live updates
            setInterval(() => {
                // Random increments
                const memberInc = Math.floor(Math.random() * 3) + 1; // 1-3
                const earningsInc = (Math.random() * 2000 + 500).toFixed(2); // 500-2500
                const todayInc = Math.floor(Math.random() * 2) + 1; // 1-2

                // Update increments display
                if (memberIncEl) memberIncEl.textContent = memberInc;
                if (earningsIncEl) earningsIncEl.textContent = parseFloat(earningsInc).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});

                // Desktop counters
                const memberEl = document.getElementById('memberCount');
                const earningsEl = document.getElementById('totalEarnings');
                const todayEl = document.getElementById('todaySignups');

                if (memberEl) {
                    const currentMembers = parseInt(memberEl.textContent.replace(/,/g, ''));
                    animateCounter(memberEl, currentMembers, currentMembers + memberInc, 800);
                }

                if (earningsEl) {
                    const currentEarnings = parseFloat(earningsEl.textContent.replace(/,/g, ''));
                    animateCounter(earningsEl, currentEarnings, currentEarnings + parseFloat(earningsInc), 800, 2);
                }

                if (todayEl) {
                    const currentToday = parseInt(todayEl.textContent.replace(/,/g, ''));
                    animateCounter(todayEl, currentToday, currentToday + todayInc, 800);
                }

                // Mobile counters
                const memberElMobile = document.getElementById('memberCountMobile');
                const earningsElMobile = document.getElementById('totalEarningsMobile');
                const todayElMobile = document.getElementById('todaySignupsMobile');

                if (memberElMobile) {
                    const currentMembers = parseInt(memberElMobile.textContent.replace(/,/g, ''));
                    animateCounter(memberElMobile, currentMembers, currentMembers + memberInc, 800);
                }

                if (earningsElMobile) {
                    const currentEarnings = parseFloat(earningsElMobile.textContent.replace(/,/g, ''));
                    animateCounter(earningsElMobile, currentEarnings, currentEarnings + parseFloat(earningsInc), 800, 2);
                }

                if (todayElMobile) {
                    const currentToday = parseInt(todayElMobile.textContent.replace(/,/g, ''));
                    animateCounter(todayElMobile, currentToday, currentToday + todayInc, 800);
                }

            }, 5000); // Update every 5 seconds
        });
    </script>
</body>
</html>
