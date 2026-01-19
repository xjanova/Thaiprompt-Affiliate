<!DOCTYPE html>
<html lang="th" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sidebar Footer</title>

    {{-- Vite Assets --}}
    @vite(['resources/css/app.css'])

    {{-- Arrow X Theme Styles --}}
    <x-arrow-x.theme-styles />

    <style>
        body {
            background: transparent !important;
            overflow: hidden;
        }

        [x-cloak] {
            display: none !important;
        }
    </style>
</head>
<body class="h-full bg-transparent p-4">
    <div class="relative flex items-center gap-3 h-full">
        {{-- Ultra Premium 3D Logo with Multiple Layers --}}
        <div class="relative w-14 h-14 flex-shrink-0 group">
            {{-- Outer Glow Ring (Rotating) --}}
            <div class="absolute inset-0 rounded-2xl bg-gradient-to-r from-cyan-400 via-purple-500 to-pink-500 opacity-50 blur-md animate-spin-slow"></div>

            {{-- Middle Shine Layer --}}
            <div class="absolute inset-0.5 rounded-2xl bg-gradient-to-br from-white/30 to-transparent opacity-40"></div>

            {{-- Main Logo Container (3D Rotating) --}}
            <div class="relative w-full h-full bg-gradient-to-br from-cyan-400 via-blue-500 to-purple-600 rounded-2xl flex items-center justify-center shadow-2xl animate-rotate-y-3d group-hover:shadow-cyan-500/50 transition-all duration-300">
                {{-- Inner Glow --}}
                <div class="absolute inset-2 bg-gradient-to-br from-white/20 to-transparent rounded-xl"></div>

                {{-- Footer Logo (จาก Theme Setting) หรือ Rocket Icon (Fallback) --}}
                @if($themeSetting && $themeSetting->footer_logo_path)
                    <img src="{{ asset('storage/' . $themeSetting->footer_logo_path) }}"
                         alt="Footer Logo"
                         class="w-10 h-10 object-contain drop-shadow-2xl relative z-10 {{ $animationClass }}">
                @else
                    <i class="fas fa-rocket text-white text-2xl drop-shadow-2xl relative z-10 {{ $animationClass }}"></i>
                @endif

                {{-- Shine Effect --}}
                <div class="absolute inset-0 bg-gradient-to-tr from-transparent via-white/30 to-transparent rounded-2xl opacity-0 group-hover:opacity-100 transition-opacity duration-300 shine-effect"></div>
            </div>

            {{-- Corner Sparkles (Absolute positioned) --}}
            <div class="absolute -top-1 -right-1 w-2 h-2 bg-yellow-300 rounded-full animate-ping opacity-75"></div>
            <div class="absolute -bottom-1 -left-1 w-1.5 h-1.5 bg-cyan-300 rounded-full animate-pulse delay-300"></div>
        </div>

        @if(!$isCompact)
            {{-- Version + License Info (Full Mode) --}}
            <div class="flex-1 min-w-0">
                {{-- App Name with Gradient Text --}}
                <div class="font-black text-base tracking-wider mb-1 bg-gradient-to-r from-white via-cyan-200 to-purple-200 bg-clip-text text-transparent drop-shadow-2xl">
                    TP-AFFILIATE
                </div>

                {{-- Version Badge (Premium Chip) --}}
                <div class="inline-flex items-center gap-1.5 px-2 py-0.5 bg-gradient-to-r from-blue-500/30 to-purple-500/30 rounded-full border border-blue-400/50 backdrop-blur-sm mb-1.5">
                    <i class="fas fa-code-branch text-[10px] text-blue-300 drop-shadow"></i>
                    <span class="text-[10px] font-bold font-mono text-white drop-shadow tracking-wide">v{{ $version }}</span>
                    <div class="w-1 h-1 bg-green-400 rounded-full animate-pulse shadow-lg shadow-green-400/50"></div>
                </div>

                {{-- License Badge (Premium with Animation) --}}
                <div class="inline-flex items-center gap-1.5 px-2 py-0.5 bg-gradient-to-r from-{{ $licenseStatus['status_color'] }}-500/30 to-green-500/30 rounded-full border border-{{ $licenseStatus['status_color'] }}-400/50 backdrop-blur-sm animate-glow-pulse">
                    <i class="fas fa-shield-check text-[10px] text-{{ $licenseStatus['status_color'] }}-300 drop-shadow-lg animate-pulse"></i>
                    <span class="text-[10px] font-bold text-white drop-shadow tracking-wide">{{ $licenseStatus['status_text'] }}</span>
                    <i class="fas fa-check-circle text-[8px] text-green-300"></i>
                </div>

                {{-- Premium Edition Label (Subtle) --}}
                <div class="text-[9px] text-white/60 font-medium tracking-widest mt-1 drop-shadow">
                    <i class="fas fa-star text-yellow-300 text-[8px] mr-0.5 animate-pulse"></i>
                    {{ strtoupper($licenseStatus['type']) }} EDITION
                </div>
            </div>
        @else
            {{-- License Icon Only (Compact Mode) --}}
            <div class="flex flex-col items-center gap-1.5 py-1"
                 title="Licensed - v{{ $version }}">
                {{-- Shield Icon with Badge --}}
                <div class="relative">
                    <i class="fas fa-shield-check text-{{ $licenseStatus['status_color'] }}-300 text-2xl drop-shadow-lg animate-pulse"></i>
                    <div class="absolute -top-1 -right-1 w-2 h-2 bg-green-400 rounded-full animate-pulse shadow-lg shadow-green-400/50"></div>
                </div>
                {{-- Version Text - Larger & More Visible --}}
                <span class="text-[10px] font-bold text-white drop-shadow-lg bg-gradient-to-r from-{{ $licenseStatus['status_color'] }}-400 to-cyan-400 bg-clip-text text-transparent">
                    v{{ $version }}
                </span>
            </div>
        @endif
    </div>

    {{-- Bottom Shine Line --}}
    <div class="absolute bottom-0 left-0 right-0 h-px bg-gradient-to-r from-transparent via-cyan-400/50 to-transparent"></div>

    {{-- Font Awesome (ถ้าไม่มีใน parent) --}}
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    {{-- Custom Animations --}}
    <style>
        @keyframes rotate-y-3d {
            0%, 100% { transform: rotateY(0deg); }
            50% { transform: rotateY(180deg); }
        }

        @keyframes spin-slow {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }

        @keyframes float {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-10px); }
        }

        @keyframes swing {
            0%, 100% { transform: rotate(-5deg); }
            50% { transform: rotate(5deg); }
        }

        @keyframes glow-pulse {
            0%, 100% { box-shadow: 0 0 5px rgba(16, 185, 129, 0.3); }
            50% { box-shadow: 0 0 20px rgba(16, 185, 129, 0.6); }
        }

        .animate-rotate-y-3d {
            animation: rotate-y-3d 10s ease-in-out infinite;
        }

        .animate-spin-slow {
            animation: spin-slow 8s linear infinite;
        }

        .animate-float {
            animation: float 3s ease-in-out infinite;
        }

        .animate-swing {
            animation: swing 2s ease-in-out infinite;
        }

        .animate-glow-pulse {
            animation: glow-pulse 2s ease-in-out infinite;
        }

        .shine-effect {
            background: linear-gradient(
                45deg,
                transparent 30%,
                rgba(255, 255, 255, 0.3) 50%,
                transparent 70%
            );
            background-size: 200% 200%;
            animation: shine 3s infinite;
        }

        @keyframes shine {
            0% { background-position: -200% -200%; }
            100% { background-position: 200% 200%; }
        }
    </style>
</body>
</html>
