{{--
    Arrow X Menu Renderer - V3 Menu System

    Modern premium menu theme พร้อม full customization
    - Gradient backgrounds
    - RGB lighting effects
    - Glassmorphism
    - 3D effects
    - Smooth animations
    - Dark/light mode support

    Props:
    - role: Role ของผู้ใช้ (admin, seller, user)
    - themeSettings: การตั้งค่า theme (optional - จะดึงจาก database/config)

    @version 3.0.0
    @since 2025-11-15
--}}

@props([
    'role' => 'admin',
    'themeSettings' => null,
])

@php
    use App\Services\MenuService;
    use App\Models\WindowsUiSetting;
    use App\Models\Setting;

    // ดึง MenuService
    $menuService = app(MenuService::class);
    $menus = $menuService->getMenuForRole($role, auth()->user());

    // ดึงการตั้งค่า theme (จาก database หรือ config)
    $settings = $themeSettings ?? config('menu-themes.themes.arrow-x.settings', []);

    // Logo & Branding
    $menuLogo = WindowsUiSetting::get('millennium_menu_logo');
    $mainLogo = Setting::get('logo');
    $showLogo = $settings['show_logo'] ?? true;
    $logoSize = $settings['logo_size'] ?? 40;
    $appName = Setting::get('app_name', 'TP-Affiliate');
    $showAppName = $settings['show_app_name'] ?? true;

    // Layout
    $menuWidth = $settings['menu_width'] ?? 400;
    $menuMaxHeight = $settings['menu_max_height'] ?? 600;
    $borderRadius = $settings['border_radius'] ?? 16;

    // Colors & Gradient
    $useGradient = $settings['use_gradient'] ?? true;
    $gradientFrom = $settings['gradient_from'] ?? '#9333ea';
    $gradientTo = $settings['gradient_to'] ?? '#db2777';
    $bgColor = $settings['bg_color'] ?? '#9333ea';
    $textColor = $settings['text_color'] ?? '#ffffff';

    // Effects
    $opacity = $settings['opacity'] ?? 95;
    $blurAmount = $settings['blur_amount'] ?? 24;
    $shadowSize = $settings['shadow_size'] ?? 'xl';

    // RGB Effects
    $rgbEnabled = $settings['rgb_enabled'] ?? true;
    $rgbSpeed = $settings['rgb_speed'] ?? 5;
    $rgbBorderWidth = $settings['rgb_border_width'] ?? 2;
    $rgbGlowSize = $settings['rgb_glow_size'] ?? 10;

    // Animation
    $animationStyle = $settings['animation_style'] ?? 'slide';
    $animationDuration = $settings['animation_duration'] ?? 200;

    // Build CSS variables
    $cssVars = [
        '--menu-width' => $menuWidth . 'px',
        '--menu-max-height' => $menuMaxHeight . 'px',
        '--menu-border-radius' => $borderRadius . 'px',
        '--menu-opacity' => $opacity / 100,
        '--menu-blur' => $blurAmount . 'px',
        '--menu-gradient-from' => $gradientFrom,
        '--menu-gradient-to' => $gradientTo,
        '--menu-bg-color' => $bgColor,
        '--menu-text-color' => $textColor,
        '--rgb-speed' => $rgbSpeed . 's',
        '--rgb-border-width' => $rgbBorderWidth . 'px',
        '--rgb-glow-size' => $rgbGlowSize . 'px',
        '--animation-duration' => $animationDuration . 'ms',
    ];

    $cssVarsString = collect($cssVars)->map(fn($value, $key) => "$key: $value")->implode('; ');
@endphp

{{-- Arrow X Menu Container --}}
<div
    class="arrow-x-menu-wrapper"
    style="{{ $cssVarsString }}"
    x-data="{ startMenuOpen: false }"
    @keydown.escape.window="startMenuOpen = false"
>
    {{-- Start Button --}}
    <button
        @click="startMenuOpen = !startMenuOpen"
        class="arrow-x-start-button fixed bottom-4 left-1/2 -translate-x-1/2 z-50
               px-6 py-3 rounded-full
               bg-gradient-to-r from-primary-500 to-primary-600
               hover:from-primary-600 hover:to-primary-700
               text-white font-semibold shadow-lg hover:shadow-xl
               transition-all duration-300 transform hover:scale-105"
    >
        <div class="flex items-center gap-2">
            @if($showLogo && ($menuLogo || $mainLogo))
                <img
                    src="{{ $menuLogo ? asset('storage/' . $menuLogo) : Storage::url($mainLogo) }}"
                    alt="Logo"
                    class="w-6 h-6 rounded"
                />
            @else
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                </svg>
            @endif
            @if($showAppName)
                <span>{{ $appName }}</span>
            @endif
        </div>
    </button>

    {{-- Overlay --}}
    <div
        x-show="startMenuOpen"
        @click="startMenuOpen = false"
        x-transition:enter="transition-opacity ease-out duration-300"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition-opacity ease-in duration-200"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        class="fixed inset-0 bg-black/60 backdrop-blur-sm z-[60]"
        style="display: none;"
    ></div>

    {{-- Menu Panel --}}
    <div
        x-show="startMenuOpen"
        @click.away="startMenuOpen = false"
        x-transition:enter="transition ease-out duration-{{ $animationDuration }}"
        x-transition:enter-start="opacity-0 {{ $animationStyle === 'slide' ? 'transform translate-y-4' : 'transform scale-95' }}"
        x-transition:enter-end="opacity-100 transform translate-y-0 scale-100"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        class="arrow-x-menu-panel fixed bottom-20 left-1/2 -translate-x-1/2 z-[70]
               rounded-{{ $borderRadius / 4 }}
               {{ $useGradient ? 'bg-gradient-to-br from-[$var(--menu-gradient-from)] to-[$var(--menu-gradient-to)]' : 'bg-[$var(--menu-bg-color)]' }}
               shadow-{{ $shadowSize }}
               backdrop-blur-{{ $blurAmount / 4 }}
               border border-white/20"
        style="width: var(--menu-width);
               max-height: var(--menu-max-height);
               opacity: var(--menu-opacity);
               display: none;
               @if($rgbEnabled)
               position: relative;
               @endif"
    >
        {{-- RGB Border Effect --}}
        @if($rgbEnabled)
        <div class="arrow-x-rgb-border absolute inset-0 rounded-{{ $borderRadius / 4 }} pointer-events-none"
             style="
                 background: conic-gradient(
                     from 0deg,
                     #FF0080 0deg,
                     #00F0FF 90deg,
                     #7F00FF 180deg,
                     #FFD700 270deg,
                     #FF0080 360deg
                 );
                 padding: var(--rgb-border-width);
                 -webkit-mask: linear-gradient(#fff 0 0) content-box, linear-gradient(#fff 0 0);
                 -webkit-mask-composite: xor;
                 mask-composite: exclude;
                 animation: rgbRotate var(--rgb-speed) linear infinite;
                 filter: blur(var(--rgb-glow-size));
             ">
        </div>

        <style>
            @keyframes rgbRotate {
                0% { transform: rotate(0deg); }
                100% { transform: rotate(360deg); }
            }
        </style>
        @endif

        {{-- Menu Content --}}
        <div class="arrow-x-menu-content relative z-10 p-6 overflow-y-auto"
             style="max-height: calc(var(--menu-max-height) - 3rem);">

            {{-- Header --}}
            <div class="arrow-x-menu-header mb-6 flex items-center gap-4">
                @if($showLogo)
                    @if($menuLogo)
                        <img src="{{ asset('storage/' . $menuLogo) }}"
                             alt="Logo"
                             class="rounded-lg"
                             style="width: {{ $logoSize }}px; height: {{ $logoSize }}px;">
                    @elseif($mainLogo)
                        <img src="{{ Storage::url($mainLogo) }}"
                             alt="Logo"
                             class="rounded-lg"
                             style="width: {{ $logoSize }}px; height: {{ $logoSize }}px;">
                    @else
                        <div class="flex items-center justify-center rounded-lg bg-white/10 font-bold text-white"
                             style="width: {{ $logoSize }}px; height: {{ $logoSize }}px; font-size: {{ $logoSize * 0.5 }}px;">
                            {{ substr($appName, 0, 1) }}
                        </div>
                    @endif
                @endif

                @if($showAppName)
                <div>
                    <h2 class="text-xl font-bold text-white">{{ $appName }}</h2>
                    <p class="text-sm text-white/70">{{ ucfirst($role) }} Dashboard</p>
                </div>
                @endif
            </div>

            {{-- Base Menu Component --}}
            <div class="arrow-x-menu-items text-white">
                <x-menu.base-menu
                    :role="$role"
                    :menu-items="$menus"
                    :search-enabled="true"
                />
            </div>
        </div>
    </div>
</div>

{{-- Arrow X Specific Styles --}}
@push('styles')
<style>
    /* Arrow X Menu Custom Styles */
    .arrow-x-menu-wrapper .menu-item-button,
    .arrow-x-menu-wrapper .menu-item-link {
        @apply bg-white/5 hover:bg-white/10 text-white;
    }

    .arrow-x-menu-wrapper .menu-item-active {
        @apply bg-white/20 border-l-4 border-white/50;
    }

    .arrow-x-menu-wrapper .submenu-item {
        @apply text-white/80 hover:text-white hover:bg-white/5;
    }

    .arrow-x-menu-wrapper .submenu-item-active {
        @apply text-white bg-white/10 border-l-2 border-white;
    }

    /* RGB Hover Effect for Menu Items */
    @if($rgbEnabled && ($settings['menu_item_hover_rgb'] ?? true))
    .arrow-x-menu-wrapper .menu-item-button:hover,
    .arrow-x-menu-wrapper .menu-item-link:hover {
        position: relative;
        background: linear-gradient(135deg, rgba(255,255,255,0.1), rgba(255,255,255,0.05));
        box-shadow: 0 0 20px rgba(147, 51, 234, 0.5), 0 0 40px rgba(219, 39, 119, 0.3);
    }
    @endif

    /* Glassmorphism Effect */
    .arrow-x-menu-panel {
        backdrop-filter: blur(var(--menu-blur));
        background-color: rgba(0, 0, 0, 0.4);
    }

    /* Smooth Scrollbar */
    .arrow-x-menu-content::-webkit-scrollbar {
        width: 6px;
    }

    .arrow-x-menu-content::-webkit-scrollbar-track {
        background: rgba(255, 255, 255, 0.1);
        border-radius: 3px;
    }

    .arrow-x-menu-content::-webkit-scrollbar-thumb {
        background: rgba(255, 255, 255, 0.3);
        border-radius: 3px;
    }

    .arrow-x-menu-content::-webkit-scrollbar-thumb:hover {
        background: rgba(255, 255, 255, 0.5);
    }
</style>
@endpush
