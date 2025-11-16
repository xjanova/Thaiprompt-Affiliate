<!DOCTYPE html>
<html lang="th" x-data="{ darkMode: false }" :class="{ 'dark': darkMode }">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    @php
        $appName = \App\Models\Setting::get('app_name', 'TP-Affiliate');
        $logo = \App\Models\Setting::get('logo');
        $logoUrl = $logo ? asset($logo) : asset('images/logo.svg');
    @endphp

    <title>@yield('title') - {{ $appName }}</title>

    {{-- Vite Assets --}}
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    {{-- Alpine.js --}}
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.13.5/dist/cdn.min.js"></script>

    <style>
        /**
         * Arrow X Theme - Authentication Styles
         *
         * Glass Fusion Effect สำหรับ Authentication Pages
         */
        .glass-fusion-auth {
            background: linear-gradient(
                135deg,
                rgba(255, 255, 255, 0.25) 0%,
                rgba(255, 255, 255, 0.15) 100%
            );
            backdrop-filter: blur(20px) saturate(180%);
            -webkit-backdrop-filter: blur(20px) saturate(180%);
            border: 1px solid rgba(255, 255, 255, 0.3);
            box-shadow:
                0 8px 32px 0 rgba(31, 38, 135, 0.37),
                inset 0 1px 0 rgba(255, 255, 255, 0.3);
        }

        /* Gradient Mesh Background */
        .gradient-mesh-auth {
            background:
                radial-gradient(ellipse at top left, rgba(139, 92, 246, 0.15) 0%, transparent 50%),
                radial-gradient(ellipse at top right, rgba(236, 72, 153, 0.15) 0%, transparent 50%),
                radial-gradient(ellipse at bottom left, rgba(59, 130, 246, 0.15) 0%, transparent 50%),
                radial-gradient(ellipse at bottom right, rgba(251, 146, 60, 0.15) 0%, transparent 50%),
                linear-gradient(135deg, #667eea 0%, #764ba2 50%, #f093fb 100%);
            background-size: 200% 200%;
            animation: gradientShift 15s ease infinite;
        }

        @keyframes gradientShift {
            0%, 100% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
        }

        /* Logo Glow Effect */
        .logo-glow-auth {
            filter: drop-shadow(0 0 20px rgba(139, 92, 246, 0.5))
                    drop-shadow(0 0 40px rgba(236, 72, 153, 0.3));
            animation: pulseGlow 3s ease-in-out infinite;
        }

        @keyframes pulseGlow {
            0%, 100% {
                filter: drop-shadow(0 0 20px rgba(139, 92, 246, 0.5))
                        drop-shadow(0 0 40px rgba(236, 72, 153, 0.3));
            }
            50% {
                filter: drop-shadow(0 0 30px rgba(139, 92, 246, 0.8))
                        drop-shadow(0 0 60px rgba(236, 72, 153, 0.5));
            }
        }

        /* Floating Particles */
        .particle-auth {
            position: absolute;
            border-radius: 50%;
            background: linear-gradient(135deg, rgba(255, 255, 255, 0.3), rgba(255, 255, 255, 0.1));
            backdrop-filter: blur(10px);
            animation: floatParticle 20s infinite ease-in-out;
        }

        @keyframes floatParticle {
            0%, 100% {
                transform: translate(0, 0) rotate(0deg);
                opacity: 0.3;
            }
            33% {
                transform: translate(100px, -100px) rotate(120deg);
                opacity: 0.6;
            }
            66% {
                transform: translate(-50px, 50px) rotate(240deg);
                opacity: 0.4;
            }
        }

        /* Premium Input with 3D effect */
        .input-3d {
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            box-shadow:
                0 2px 4px rgba(0, 0, 0, 0.05),
                inset 0 1px 0 rgba(255, 255, 255, 0.5);
        }

        .input-3d:focus {
            transform: translateY(-2px);
            box-shadow:
                0 8px 16px rgba(139, 92, 246, 0.2),
                0 4px 8px rgba(236, 72, 153, 0.1),
                inset 0 1px 0 rgba(255, 255, 255, 0.8);
        }

        /* Button with shimmer effect */
        .btn-shimmer {
            position: relative;
            overflow: hidden;
        }

        .btn-shimmer::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(
                90deg,
                transparent,
                rgba(255, 255, 255, 0.4),
                transparent
            );
            transition: left 0.5s ease;
        }

        .btn-shimmer:hover::before {
            left: 100%;
        }

        /* Dark mode specific styles */
        .dark .glass-fusion-auth {
            background: linear-gradient(
                135deg,
                rgba(31, 41, 55, 0.8) 0%,
                rgba(17, 24, 39, 0.7) 100%
            );
            border-color: rgba(75, 85, 99, 0.5);
            box-shadow:
                0 8px 32px 0 rgba(0, 0, 0, 0.5),
                inset 0 1px 0 rgba(255, 255, 255, 0.1);
        }

        .dark .gradient-mesh-auth {
            background:
                radial-gradient(ellipse at top left, rgba(139, 92, 246, 0.08) 0%, transparent 50%),
                radial-gradient(ellipse at top right, rgba(236, 72, 153, 0.08) 0%, transparent 50%),
                radial-gradient(ellipse at bottom left, rgba(59, 130, 246, 0.08) 0%, transparent 50%),
                radial-gradient(ellipse at bottom right, rgba(251, 146, 60, 0.08) 0%, transparent 50%),
                linear-gradient(135deg, #1e1b4b 0%, #312e81 50%, #1e293b 100%);
        }
    </style>

    @stack('styles')
</head>
<body class="gradient-mesh-auth min-h-screen relative overflow-hidden">
    {{-- Floating Particles Background --}}
    <div class="particle-auth" style="width: 80px; height: 80px; top: 10%; left: 10%; animation-delay: 0s;"></div>
    <div class="particle-auth" style="width: 120px; height: 120px; top: 60%; left: 85%; animation-delay: 3s;"></div>
    <div class="particle-auth" style="width: 60px; height: 60px; top: 80%; left: 15%; animation-delay: 6s;"></div>
    <div class="particle-auth" style="width: 100px; height: 100px; top: 30%; left: 80%; animation-delay: 9s;"></div>
    <div class="particle-auth" style="width: 70px; height: 70px; top: 50%; left: 5%; animation-delay: 12s;"></div>

    {{-- Main Content --}}
    <div class="min-h-screen flex items-center justify-center px-4 py-8 relative z-10">
        <div class="w-full max-w-md">
            {{-- Logo & Branding --}}
            <div class="text-center mb-8" x-data="{ show: false }" x-init="setTimeout(() => show = true, 100)" x-show="show" x-transition:enter="transition ease-out duration-500" x-transition:enter-start="opacity-0 -translate-y-4" x-transition:enter-end="opacity-100 translate-y-0">
                <div class="flex justify-center mb-6">
                    <img src="{{ $logoUrl }}"
                         alt="{{ $appName }}"
                         class="h-20 w-auto logo-glow-auth">
                </div>
                <h1 class="text-4xl font-extrabold bg-gradient-to-r from-purple-600 via-pink-600 to-orange-600 bg-clip-text text-transparent dark:from-purple-400 dark:via-pink-400 dark:to-orange-400 mb-2">
                    {{ $appName }}
                </h1>
                <p class="text-gray-600 dark:text-gray-400 font-medium">
                    @yield('subtitle', 'ระบบบริหารจัดการพันธมิตร')
                </p>
            </div>

            {{-- Content Card --}}
            <div class="glass-fusion-auth rounded-3xl p-8 shadow-2xl" x-data="{ show: false }" x-init="setTimeout(() => show = true, 200)" x-show="show" x-transition:enter="transition ease-out duration-500" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100">
                @yield('content')
            </div>

            {{-- Footer Links --}}
            <div class="mt-6 text-center" x-data="{ show: false }" x-init="setTimeout(() => show = true, 300)" x-show="show" x-transition:enter="transition ease-out duration-500" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100">
                @yield('footer-links')

                {{-- Back to Home --}}
                <a href="{{ route('home') }}"
                   class="inline-flex items-center mt-4 px-6 py-3 bg-white/20 dark:bg-gray-800/30 backdrop-blur-md text-white dark:text-gray-200 font-semibold rounded-xl hover:bg-white/30 dark:hover:bg-gray-800/50 transition duration-300 shadow-lg group">
                    <svg class="w-5 h-5 mr-2 group-hover:-translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                    </svg>
                    กลับหน้าแรก
                </a>
            </div>

            {{-- Security Badge --}}
            <div class="mt-8 text-center">
                <p class="text-white/80 dark:text-gray-400 text-sm font-medium flex items-center justify-center">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                    </svg>
                    ระบบปลอดภัยด้วยการเข้ารหัสระดับสูง
                </p>
            </div>
        </div>
    </div>

    @stack('scripts')
</body>
</html>
