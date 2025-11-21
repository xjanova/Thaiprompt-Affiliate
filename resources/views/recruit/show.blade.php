<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    @php
        $appName = \App\Models\Setting::get('app_name', 'TP-Affiliate');
        $displayName = $customization->getDisplayName();
        $memberCode = $mlmMember->member_code;
    @endphp

    <title>{{ $displayName }} แนะนำสมาชิก - {{ $appName }}</title>

    <!-- SEO Meta Tags -->
    <meta name="description" content="ร่วมเป็นส่วนหนึ่งกับทีมของ {{ $displayName }} - สร้างรายได้แบบไม่จำกัด">
    <meta property="og:title" content="{{ $displayName }} แนะนำสมาชิก">
    <meta property="og:description" content="ร่วมเป็นส่วนหนึ่งกับทีมของ {{ $displayName }} - สร้างรายได้แบบไม่จำกัด">
    <meta property="og:image" content="{{ $customization->getDisplayImage() }}">

    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>

    <!-- Alpine.js -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.13.5/dist/cdn.min.js"></script>

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <!-- QRCode.js -->
    <script src="https://cdn.jsdelivr.net/npm/qrcodejs@1.0.0/qrcode.min.js"></script>

    <style>
        @import url('https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;500;600;700&display=swap');

        * {
            font-family: 'Kanit', sans-serif;
        }

        /* Glassmorphism */
        .glass {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.3);
        }

        .glass-dark {
            background: rgba(17, 24, 39, 0.95);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        /* Gradient Background */
        .gradient-bg {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 50%, #f093fb 100%);
            background-size: 400% 400%;
            animation: gradientShift 15s ease infinite;
        }

        @keyframes gradientShift {
            0%, 100% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
        }

        /* Floating Animation */
        @keyframes float {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-20px); }
        }

        .float {
            animation: float 6s ease-in-out infinite;
        }

        /* Pulse Animation */
        @keyframes pulse {
            0%, 100% { transform: scale(1); opacity: 1; }
            50% { transform: scale(1.05); opacity: 0.8; }
        }

        .pulse-slow {
            animation: pulse 3s ease-in-out infinite;
        }

        /* 3D Card Effect */
        .card-3d {
            transform-style: preserve-3d;
            transition: transform 0.6s;
        }

        .card-3d:hover {
            transform: rotateY(5deg) rotateX(5deg);
        }

        /* Shine Effect */
        .shine {
            position: relative;
            overflow: hidden;
        }

        .shine::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: linear-gradient(
                45deg,
                transparent 30%,
                rgba(255, 255, 255, 0.3) 50%,
                transparent 70%
            );
            transform: rotate(45deg);
            animation: shine 3s ease-in-out infinite;
        }

        @keyframes shine {
            0%, 100% { transform: translateX(-100%) translateY(-100%) rotate(45deg); }
            50% { transform: translateX(100%) translateY(100%) rotate(45deg); }
        }

        /* Custom Scrollbar */
        ::-webkit-scrollbar {
            width: 8px;
        }

        ::-webkit-scrollbar-track {
            background: rgba(255, 255, 255, 0.1);
        }

        ::-webkit-scrollbar-thumb {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 10px;
        }
    </style>
</head>
<body class="gradient-bg min-h-screen">

    <div x-data="recruitPage()" x-init="init()" class="min-h-screen py-8 px-4 md:px-8">

        <!-- Lead Lock Banner (ถ้ามี) -->
        @if($leadLock && $leadLock->isActive())
        <div class="max-w-4xl mx-auto mb-6">
            <div class="glass rounded-2xl shadow-2xl p-4 border-l-4 border-green-500">
                <div class="flex items-center gap-3">
                    <div class="w-12 h-12 bg-green-500 rounded-full flex items-center justify-center text-white text-xl">
                        <i class="fas fa-lock"></i>
                    </div>
                    <div class="flex-1">
                        <h3 class="font-bold text-gray-900">🎯 คุณถูกล็อคไว้แล้ว!</h3>
                        <p class="text-sm text-gray-600">
                            เมื่อคุณสมัคร แม่ทีม <strong>{{ $displayName }}</strong> จะได้รับเครดิต
                            <span class="text-green-600 font-semibold">(เหลือเวลา: {{ $leadLock->getTimeRemaining() }})</span>
                        </p>
                    </div>
                </div>
            </div>
        </div>
        @endif

        <!-- Main Container -->
        <div class="max-w-6xl mx-auto">

            <!-- Header Section: Team Leader Info -->
            <div class="glass rounded-3xl shadow-2xl p-8 mb-8 card-3d">
                <div class="flex flex-col md:flex-row items-center gap-6">

                    <!-- Team Leader Avatar -->
                    <div class="relative">
                        <div class="w-32 h-32 md:w-40 md:h-40 rounded-full overflow-hidden border-4 border-white shadow-2xl float">
                            <img src="{{ $customization->getDisplayImage() }}"
                                 alt="{{ $displayName }}"
                                 class="w-full h-full object-cover">
                        </div>
                        <div class="absolute -bottom-2 -right-2 bg-green-500 text-white rounded-full w-12 h-12 flex items-center justify-center shadow-lg pulse-slow">
                            <i class="fas fa-check text-xl"></i>
                        </div>
                    </div>

                    <!-- Team Leader Details -->
                    <div class="flex-1 text-center md:text-left">
                        <h1 class="text-3xl md:text-4xl font-bold text-gray-900 mb-2">
                            {{ $displayName }}
                        </h1>
                        <p class="text-lg text-gray-600 mb-3">
                            <i class="fas fa-id-card mr-2 text-purple-600"></i>
                            รหัสสมาชิก: <strong class="text-purple-600">{{ $memberCode }}</strong>
                        </p>

                        @if($customization->custom_phone)
                        <p class="text-gray-600 mb-2">
                            <i class="fas fa-phone mr-2 text-blue-600"></i>
                            <a href="tel:{{ $customization->custom_phone }}" class="hover:text-blue-600 transition">
                                {{ $customization->custom_phone }}
                            </a>
                        </p>
                        @endif

                        @if($customization->custom_address)
                        <p class="text-gray-600">
                            <i class="fas fa-map-marker-alt mr-2 text-red-600"></i>
                            {{ $customization->custom_address }}
                        </p>
                        @endif
                    </div>

                    <!-- Stats (if enabled) -->
                    @if($template->show_statistics)
                    <div class="grid grid-cols-2 gap-4 text-center">
                        <div class="glass rounded-xl p-4">
                            <div class="text-3xl font-bold text-green-600">{{ $customization->total_conversions }}</div>
                            <div class="text-xs text-gray-600">สมาชิกในทีม</div>
                        </div>
                        <div class="glass rounded-xl p-4">
                            <div class="text-3xl font-bold text-blue-600">
                                {{ number_format($customization->conversion_rate, 1) }}%
                            </div>
                            <div class="text-xs text-gray-600">อัตราความสำเร็จ</div>
                        </div>
                    </div>
                    @endif
                </div>
            </div>

            <!-- Welcome Message -->
            <div class="glass rounded-3xl shadow-2xl p-8 mb-8">
                <div class="text-center mb-6">
                    <div class="inline-block bg-gradient-to-r from-purple-600 to-pink-600 text-white px-6 py-2 rounded-full text-sm font-semibold mb-4">
                        <i class="fas fa-star mr-2"></i>ข้อความจากแม่ทีม
                    </div>
                </div>

                <div class="prose prose-lg max-w-none text-center">
                    <p class="text-xl text-gray-800 leading-relaxed whitespace-pre-line">
                        {{ $customization->custom_pitch ?? $template->welcome_message }}
                    </p>
                </div>
            </div>

            <!-- Benefits Section -->
            @if($template->benefits && count($template->benefits) > 0)
            <div class="glass rounded-3xl shadow-2xl p-8 mb-8">
                <h2 class="text-3xl font-bold text-center text-gray-900 mb-8">
                    <i class="fas fa-gift text-yellow-500 mr-3"></i>
                    ประโยชน์ที่คุณจะได้รับ
                </h2>

                <div class="grid md:grid-cols-2 gap-4">
                    @foreach($template->benefits as $benefit)
                    <div class="flex items-start gap-3 p-4 rounded-xl hover:bg-white/50 transition">
                        <div class="w-8 h-8 bg-gradient-to-br from-green-400 to-green-600 rounded-full flex items-center justify-center flex-shrink-0">
                            <i class="fas fa-check text-white"></i>
                        </div>
                        <p class="text-gray-700 leading-relaxed">{{ $benefit }}</p>
                    </div>
                    @endforeach
                </div>
            </div>
            @endif

            <!-- Instructions / Steps -->
            @if($template->instructions && count($template->instructions) > 0)
            <div class="glass rounded-3xl shadow-2xl p-8 mb-8">
                <h2 class="text-3xl font-bold text-center text-gray-900 mb-8">
                    <i class="fas fa-list-ol text-blue-500 mr-3"></i>
                    ขั้นตอนการสมัคร
                </h2>

                <div class="grid md:grid-cols-2 gap-6">
                    @foreach($template->instructions as $instruction)
                    <div class="relative">
                        <div class="flex items-start gap-4 p-6 rounded-2xl bg-white/50 hover:bg-white/70 transition card-3d">
                            <!-- Step Number -->
                            <div class="w-16 h-16 bg-gradient-to-br from-purple-500 to-pink-500 rounded-full flex items-center justify-center flex-shrink-0 text-white text-2xl font-bold shadow-lg">
                                {{ $instruction['icon'] ?? $instruction['step'] }}
                            </div>

                            <!-- Step Content -->
                            <div class="flex-1">
                                <h3 class="font-bold text-lg text-gray-900 mb-2">
                                    {{ $instruction['title'] }}
                                </h3>
                                <p class="text-gray-600 text-sm leading-relaxed">
                                    {{ $instruction['description'] }}
                                </p>
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
            @endif

            <!-- LINE OA Section (if required) -->
            @if($template->require_line_friend && $lineSettings)
            <div class="glass rounded-3xl shadow-2xl p-8 mb-8">
                <div class="text-center mb-6">
                    <div class="w-20 h-20 bg-green-500 rounded-full flex items-center justify-center mx-auto mb-4 pulse-slow">
                        <i class="fab fa-line text-white text-4xl"></i>
                    </div>
                    <h2 class="text-3xl font-bold text-gray-900 mb-2">
                        เพิ่มเพื่อน LINE Official Account
                    </h2>
                    <p class="text-gray-600">
                        กรุณาเพิ่มเพื่อนก่อนสมัครสมาชิก เพื่อรับข้อมูลและการสนับสนุน
                    </p>
                </div>

                <div class="flex flex-col md:flex-row items-center justify-center gap-8">
                    <!-- QR Code -->
                    @if($lineSettings->messaging_channel_id)
                    <div class="glass rounded-2xl p-6 shine">
                        <img src="https://qr-official.line.me/gs/M_{{ $lineSettings->messaging_channel_id }}_GW.png"
                             alt="LINE QR Code"
                             class="w-48 h-48 md:w-56 md:h-56">
                    </div>
                    @endif

                    <!-- Add Friend Button -->
                    <div class="text-center">
                        <button @click="addLineFriend()"
                                class="px-8 py-4 bg-gradient-to-r from-green-500 to-green-600 text-white rounded-2xl font-bold text-lg hover:from-green-600 hover:to-green-700 transform hover:scale-105 transition-all duration-300 shadow-2xl">
                            <i class="fab fa-line mr-3 text-2xl"></i>
                            เพิ่มเพื่อน LINE
                        </button>
                        <p class="text-sm text-gray-500 mt-3">
                            <i class="fas fa-info-circle mr-1"></i>
                            บนมือถือจะเปิดแอพ LINE โดยอัตโนมัติ
                        </p>
                    </div>
                </div>
            </div>
            @endif

            <!-- CTA Buttons -->
            <div class="glass rounded-3xl shadow-2xl p-8 mb-8">
                <div class="text-center">
                    <h2 class="text-3xl font-bold text-gray-900 mb-6">
                        <i class="fas fa-rocket text-red-500 mr-3"></i>
                        พร้อมเริ่มต้นแล้วใช่ไหม?
                    </h2>

                    <div class="flex flex-col md:flex-row gap-4 justify-center max-w-2xl mx-auto">
                        <!-- LINE Register (Recommended) -->
                        <a href="{{ route('line.login') }}?ref={{ $memberCode }}"
                           @click="trackClick('register_line')"
                           class="flex-1 px-8 py-5 bg-gradient-to-r from-green-500 to-green-600 text-white rounded-2xl font-bold text-lg hover:from-green-600 hover:to-green-700 transform hover:scale-105 transition-all duration-300 shadow-2xl shine">
                            <div class="flex items-center justify-center gap-3">
                                <i class="fab fa-line text-3xl"></i>
                                <div class="text-left">
                                    <div class="text-sm opacity-90">แนะนำ!</div>
                                    <div>สมัครด้วย LINE</div>
                                </div>
                            </div>
                        </a>

                        <!-- Normal Register -->
                        <a href="{{ $registerUrl }}"
                           @click="trackClick('register_normal')"
                           class="flex-1 px-8 py-5 bg-gradient-to-r from-purple-500 to-pink-500 text-white rounded-2xl font-bold text-lg hover:from-purple-600 hover:to-pink-600 transform hover:scale-105 transition-all duration-300 shadow-2xl">
                            <div class="flex items-center justify-center gap-3">
                                <i class="fas fa-user-plus text-2xl"></i>
                                <div>สมัครแบบธรรมดา</div>
                            </div>
                        </a>
                    </div>

                    <p class="text-sm text-gray-500 mt-6">
                        <i class="fas fa-shield-alt text-green-600 mr-1"></i>
                        ฟรี 100% ไม่มีค่าใช้จ่าย · ปลอดภัย · เริ่มต้นได้ทันที
                    </p>
                </div>
            </div>

            <!-- Footer Info -->
            <div class="text-center text-white/80 text-sm">
                <p>© {{ date('Y') }} {{ $appName }} - สร้างโอกาสไม่จำกัด</p>
                <p class="mt-2">
                    Powered by <strong>{{ $appName }}</strong> MLM System
                </p>
            </div>

        </div>
    </div>

    <script>
        function recruitPage() {
            return {
                sessionId: '{{ session()->getId() }}',
                lineChannelId: '{{ $lineSettings->messaging_channel_id ?? '' }}',
                timeOnPage: 0,
                scrolledToBottom: false,

                init() {
                    // Track time on page
                    setInterval(() => {
                        this.timeOnPage++;
                    }, 1000);

                    // Track scroll
                    window.addEventListener('scroll', () => {
                        const scrolled = window.scrollY + window.innerHeight;
                        const total = document.documentElement.scrollHeight;

                        if (scrolled >= total - 100 && !this.scrolledToBottom) {
                            this.scrolledToBottom = true;
                            this.trackBehavior();
                        }
                    });

                    // Track before unload
                    window.addEventListener('beforeunload', () => {
                        this.trackBehavior();
                    });
                },

                addLineFriend() {
                    const isMobile = /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent);

                    if (isMobile) {
                        window.location.href = 'https://line.me/R/ti/p/@' + this.lineChannelId;
                    } else {
                        alert('กรุณาใช้โทรศัพท์สแกน QR Code เพื่อเพิ่มเพื่อน หรือค้นหา @' + this.lineChannelId + ' ในแอพ LINE');
                    }
                },

                trackClick(type) {
                    this.trackBehavior(type === 'register_line' || type === 'register_normal');
                },

                trackBehavior(clickedRegister = false) {
                    fetch('{{ route("recruit.track-behavior") }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                        },
                        body: JSON.stringify({
                            session_id: this.sessionId,
                            scrolled_to_bottom: this.scrolledToBottom,
                            clicked_register_button: clickedRegister,
                            time_spent: this.timeOnPage
                        })
                    }).catch(err => console.log('Track failed:', err));
                }
            }
        }
    </script>

</body>
</html>
