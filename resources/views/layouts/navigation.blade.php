@php
// Fetch Header Settings from Database
$headerLayout = \App\Models\Setting::get('header_layout', 'default');
$headerPosition = \App\Models\Setting::get('header_position', 'relative');
$headerTransparent = \App\Models\Setting::get('header_transparent', false);
$headerTransparentOnScroll = \App\Models\Setting::get('header_transparent_on_scroll', false);
$headerStickyScroll = \App\Models\Setting::get('header_sticky_scroll', false);
$headerHideOnScroll = \App\Models\Setting::get('header_hide_on_scroll', false);
$headerShrinkOnScroll = \App\Models\Setting::get('header_shrink_on_scroll', false);
$headerBgColor = \App\Models\Setting::get('header_bg_color', '#ffffff');
$headerBgOpacity = \App\Models\Setting::get('header_bg_opacity', 100);
$headerBlur = \App\Models\Setting::get('header_blur', false);
$headerBlurAmount = \App\Models\Setting::get('header_blur_amount', 10);
$headerShadow = \App\Models\Setting::get('header_shadow', 'lg');
$headerShadowOnScroll = \App\Models\Setting::get('header_shadow_on_scroll', true);
$headerHeight = \App\Models\Setting::get('header_height', 64);
$headerHeightScrolled = \App\Models\Setting::get('header_height_scrolled', 56);
$headerPaddingX = \App\Models\Setting::get('header_padding_x', 16);
$headerTextColor = \App\Models\Setting::get('header_text_color', '#1f2937');
$headerTextColorScroll = \App\Models\Setting::get('header_text_color_scroll', '#1f2937');
$headerHoverColor = \App\Models\Setting::get('header_hover_color', '#4f46e5');
$headerBorderBottom = \App\Models\Setting::get('header_border_bottom', false);
$headerBorderColor = \App\Models\Setting::get('header_border_color', '#e5e7eb');
$headerBorderWidth = \App\Models\Setting::get('header_border_width', 1);
$headerAnimationDuration = \App\Models\Setting::get('header_animation_duration', 300);
$headerAnimationEasing = \App\Models\Setting::get('header_animation_easing', 'ease-in-out');
$headerLogoHeight = \App\Models\Setting::get('header_logo_height', 60);
$headerLogoHeightScrolled = \App\Models\Setting::get('header_logo_height_scrolled', 48);
$headerGradient = \App\Models\Setting::get('header_gradient', false);
$headerGradientFrom = \App\Models\Setting::get('header_gradient_from', '#ffffff');
$headerGradientTo = \App\Models\Setting::get('header_gradient_to', '#f3f4f6');
$headerGradientDirection = \App\Models\Setting::get('header_gradient_direction', 'to-r');

// 3D Effects Settings
$header3dEnabled = \App\Models\Setting::get('header_3d_enabled', true);
$header3dDepth = \App\Models\Setting::get('header_3d_depth', 3);
$header3dPerspective = \App\Models\Setting::get('header_3d_perspective', 1000);
$header3dShadowIntensity = \App\Models\Setting::get('header_3d_shadow_intensity', 'medium');

// Logo Display Settings
$headerShowLogo = \App\Models\Setting::get('header_show_logo', true);
$headerLogoAnimated = \App\Models\Setting::get('header_logo_animated', true);

// Windows UI Compatibility Settings
$headerWindowsUiMode = \App\Models\Setting::get('header_windows_ui_mode', false);
$headerWindowsUiHeight = \App\Models\Setting::get('header_windows_ui_height', 48);

// Helper function to convert hex to rgba
function hexToRgba($hex, $opacity) {
    $hex = ltrim($hex, '#');
    $r = hexdec(substr($hex, 0, 2));
    $g = hexdec(substr($hex, 2, 2));
    $b = hexdec(substr($hex, 4, 2));
    return "rgba($r, $g, $b, " . ($opacity / 100) . ")";
}

// Build shadow classes
$shadowClasses = [
    'none' => '',
    'sm' => 'shadow-sm',
    'md' => 'shadow',
    'lg' => 'shadow-lg',
    'xl' => 'shadow-xl',
    '2xl' => 'shadow-2xl',
];
$currentShadow = $shadowClasses[$headerShadow] ?? 'shadow-lg';

// Build 3D shadow styles based on intensity
$shadow3dStyles = [
    'low' => '0 2px 8px rgba(0, 0, 0, 0.08), 0 4px 16px rgba(0, 0, 0, 0.04)',
    'medium' => '0 4px 16px rgba(0, 0, 0, 0.12), 0 8px 32px rgba(0, 0, 0, 0.08), 0 2px 4px rgba(0, 0, 0, 0.06)',
    'high' => '0 8px 24px rgba(0, 0, 0, 0.16), 0 16px 48px rgba(0, 0, 0, 0.12), 0 4px 8px rgba(0, 0, 0, 0.08)',
];
$current3dShadow = $shadow3dStyles[$header3dShadowIntensity] ?? $shadow3dStyles['medium'];

// Build gradient direction classes
$gradientDirections = [
    'to-r' => 'bg-gradient-to-r',
    'to-l' => 'bg-gradient-to-l',
    'to-b' => 'bg-gradient-to-b',
    'to-t' => 'bg-gradient-to-t',
    'to-br' => 'bg-gradient-to-br',
    'to-bl' => 'bg-gradient-to-bl',
];
$gradientClass = $gradientDirections[$headerGradientDirection] ?? 'bg-gradient-to-r';

$logo = \App\Models\Setting::get('logo');
$appName = \App\Models\Setting::get('app_name', 'TP-Affiliate');

// Logo size settings for navigation header
$logoNavigationWidth = \App\Models\Setting::get('logo_navigation_width', $headerLogoHeight ?? 60);
$logoNavigationHeight = \App\Models\Setting::get('logo_navigation_height', $headerLogoHeight ?? 60);
$logoNavigationScrolledWidth = \App\Models\Setting::get('logo_navigation_scrolled_width', $headerLogoHeightScrolled ?? 48);
$logoNavigationScrolledHeight = \App\Models\Setting::get('logo_navigation_scrolled_height', $headerLogoHeightScrolled ?? 48);

// Get dynamic menu items from database
$menuItems = \App\Models\MenuItem::getForLocation('header');
@endphp

<nav class="header-navigation {{ $currentShadow }} backdrop-blur-xl bg-white/95 dark:bg-gray-900/95 border-b border-gray-200 dark:border-gray-700 {{ $header3dEnabled ? 'header-3d-effect' : '' }}"
     x-data="navigationMenu()"
     x-init="init()"
     :class="{
         'header-scrolled': scrolled,
         'header-hidden': hidden,
         '{{ $shadowClasses[$headerShadowOnScroll ? 'xl' : 'none'] }}': scrolled && {{ $headerShadowOnScroll ? 'true' : 'false' }}
     }"
     style="
         transition-property: all;
         transition-duration: {{ $headerAnimationDuration }}ms;
         transition-timing-function: {{ $headerAnimationEasing }};
         @if($headerPosition === 'fixed')
             position: fixed;
             top: 0;
             left: 0;
             right: 0;
             z-index: 1000;
         @elseif($headerPosition === 'absolute')
             position: absolute;
             top: 0;
             left: 0;
             right: 0;
             z-index: 1000;
         @else
             position: relative;
         @endif
         @if($headerStickyScroll)
             position: sticky;
             top: 0;
             z-index: 1000;
         @endif
         @if($header3dEnabled)
         box-shadow: {{ $current3dShadow }};
         transform: translateZ({{ $header3dDepth }}px);
         transform-style: preserve-3d;
         perspective: {{ $header3dPerspective }}px;
         @endif
     "
     :style="{
         height: ({{ $headerWindowsUiMode ? 'true' : 'false' }} ? '{{ $headerWindowsUiHeight }}px' : (scrolled && {{ $headerShrinkOnScroll ? 'true' : 'false' }} ? '{{ $headerHeightScrolled }}px' : '{{ $headerHeight }}px')),
         paddingLeft: '{{ $headerPaddingX }}px',
         paddingRight: '{{ $headerPaddingX }}px',
         @if($headerBlur)
         backdropFilter: 'blur({{ $headerBlurAmount }}px) saturate(180%)',
         WebkitBackdropFilter: 'blur({{ $headerBlurAmount }}px) saturate(180%)',
         @endif
         @if($headerTransparent || ($headerTransparentOnScroll && scrolled))
         opacity: '0.98',
         @else
         opacity: '1',
         @endif
         @if($headerHideOnScroll)
         transform: hidden ? 'translateY(-100%)' : 'translateY(0)',
         @endif
     }">

    <div class="max-w-7xl mx-auto">
        <div class="flex justify-between items-center" style="height: 100%;">
            <!-- Logo Section -->
            @if($headerShowLogo)
            <div class="shrink-0 flex items-center w-48 {{ $header3dEnabled ? 'logo-3d-container' : '' }}">
                <a href="{{ route('home') }}" class="flex items-center {{ $headerLogoAnimated ? 'logo-animated' : '' }}">
                    @if($logo)
                        <img src="{{ asset($logo) }}"
                             alt="{{ $appName }}"
                             class="object-contain transition-all {{ $header3dEnabled ? 'logo-3d-effect' : '' }}"
                             :style="{
                                 width: (scrolled && {{ $headerShrinkOnScroll ? 'true' : 'false' }} ? '{{ $logoNavigationScrolledWidth }}px' : '{{ $logoNavigationWidth }}px'),
                                 height: (scrolled && {{ $headerShrinkOnScroll ? 'true' : 'false' }} ? '{{ $logoNavigationScrolledHeight }}px' : '{{ $logoNavigationHeight }}px')
                             }">
                    @else
                        <span class="text-2xl font-bold transition-all {{ $header3dEnabled ? 'logo-3d-effect' : '' }}"
                              :style="{
                                  fontSize: (scrolled && {{ $headerShrinkOnScroll ? 'true' : 'false' }} ? '{{ $headerLogoHeightScrolled / 2.5 }}px' : '{{ $headerLogoHeight / 2.5 }}px')
                              }">
                            {{ $appName }}
                        </span>
                    @endif
                </a>
            </div>
            @else
            <div class="shrink-0 w-48"></div>
            @endif

            <!-- Desktop Navigation Links - Centered -->
            <div class="hidden sm:flex flex-1 justify-center items-center gap-3">
                @if($menuItems && $menuItems->count() > 0)
                    @foreach($menuItems as $menuItem)
                        @if($menuItem->shouldDisplay())
                            <a href="{{ $menuItem->url ?? '#' }}"
                               class="nav-link-premium group relative inline-flex items-center gap-2 px-4 py-2 text-sm font-semibold rounded-lg transition-all duration-300 hover:bg-gradient-to-r hover:from-indigo-50 hover:to-purple-50 dark:hover:from-indigo-900/30 dark:hover:to-purple-900/30"
                               style="color: {{ $headerTextColor }};"
                               @if($menuItem->target === '_blank') target="_blank" rel="noopener noreferrer" @endif>
                                @if($menuItem->icon)<span class="text-lg group-hover:scale-110 transition-transform duration-300">{{ $menuItem->icon }}</span>@endif
                                <span class="group-hover:translate-x-0.5 transition-transform duration-300">{{ $menuItem->title }}</span>
                                <span class="absolute bottom-0 left-0 w-0 h-0.5 bg-gradient-to-r from-indigo-600 to-purple-600 group-hover:w-full transition-all duration-300"></span>
                            </a>
                        @endif
                    @endforeach
                @else
                    {{-- Fallback to hardcoded menu if no database items --}}
                    <a href="{{ route('home') }}"
                       class="nav-link-premium group relative inline-flex items-center gap-2 px-4 py-2 text-sm font-semibold rounded-lg transition-all duration-300 hover:bg-gradient-to-r hover:from-indigo-50 hover:to-purple-50 dark:hover:from-indigo-900/30 dark:hover:to-purple-900/30"
                       style="color: {{ $headerTextColor }};">
                        <span class="text-lg group-hover:scale-110 transition-transform duration-300">🏠</span>
                        <span class="group-hover:translate-x-0.5 transition-transform duration-300">หน้าแรก</span>
                        <span class="absolute bottom-0 left-0 w-0 h-0.5 bg-gradient-to-r from-indigo-600 to-purple-600 group-hover:w-full transition-all duration-300"></span>
                    </a>
                    <a href="{{ route('marketplace.index') }}"
                       class="nav-link-premium group relative inline-flex items-center gap-2 px-4 py-2 text-sm font-semibold rounded-lg transition-all duration-300 hover:bg-gradient-to-r hover:from-blue-50 hover:to-cyan-50 dark:hover:from-blue-900/30 dark:hover:to-cyan-900/30"
                       style="color: {{ $headerTextColor }};">
                        <span class="text-lg group-hover:scale-110 transition-transform duration-300">🤖</span>
                        <span class="group-hover:translate-x-0.5 transition-transform duration-300">ตลาดบอท</span>
                        <span class="absolute bottom-0 left-0 w-0 h-0.5 bg-gradient-to-r from-blue-600 to-cyan-600 group-hover:w-full transition-all duration-300"></span>
                    </a>
                    <a href="{{ route('shop.index') }}"
                       class="nav-link-premium group relative inline-flex items-center gap-2 px-4 py-2 text-sm font-semibold rounded-lg transition-all duration-300 hover:bg-gradient-to-r hover:from-pink-50 hover:to-rose-50 dark:hover:from-pink-900/30 dark:hover:to-rose-900/30"
                       style="color: {{ $headerTextColor }};">
                        <span class="text-lg group-hover:scale-110 transition-transform duration-300">🛍️</span>
                        <span class="group-hover:translate-x-0.5 transition-transform duration-300">ช๊อปปิ้ง</span>
                        <span class="absolute bottom-0 left-0 w-0 h-0.5 bg-gradient-to-r from-pink-600 to-rose-600 group-hover:w-full transition-all duration-300"></span>
                    </a>
                    <a href="{{ route('hotels.index') }}"
                       class="nav-link-premium group relative inline-flex items-center gap-2 px-4 py-2 text-sm font-semibold rounded-lg transition-all duration-300 hover:bg-gradient-to-r hover:from-orange-50 hover:to-amber-50 dark:hover:from-orange-900/30 dark:hover:to-amber-900/30"
                       style="color: {{ $headerTextColor }};">
                        <span class="text-lg group-hover:scale-110 transition-transform duration-300">🏨</span>
                        <span class="group-hover:translate-x-0.5 transition-transform duration-300">จองโรงแรม</span>
                        <span class="absolute bottom-0 left-0 w-0 h-0.5 bg-gradient-to-r from-orange-600 to-amber-600 group-hover:w-full transition-all duration-300"></span>
                    </a>
                    @auth
                        <a href="{{ route('my-rentals.index') }}"
                           class="nav-link-premium group relative inline-flex items-center gap-2 px-4 py-2 text-sm font-semibold rounded-lg transition-all duration-300 hover:bg-gradient-to-r hover:from-green-50 hover:to-emerald-50 dark:hover:from-green-900/30 dark:hover:to-emerald-900/30"
                           style="color: {{ $headerTextColor }};">
                            <span class="text-lg group-hover:scale-110 transition-transform duration-300">💼</span>
                            <span class="group-hover:translate-x-0.5 transition-transform duration-300">การเช่าของฉัน</span>
                            <span class="absolute bottom-0 left-0 w-0 h-0.5 bg-gradient-to-r from-green-600 to-emerald-600 group-hover:w-full transition-all duration-300"></span>
                        </a>
                        @if(Auth::user()->is_hotel_admin && Auth::user()->managed_hotel_id)
                            <a href="{{ route('hotel-admin.dashboard') }}"
                               class="nav-link-premium group relative inline-flex items-center gap-2 px-4 py-2 text-sm font-semibold rounded-lg transition-all duration-300 hover:bg-gradient-to-r hover:from-teal-50 hover:to-cyan-50 dark:hover:from-teal-900/30 dark:hover:to-cyan-900/30"
                               style="color: {{ $headerTextColor }};">
                                <span class="text-lg group-hover:scale-110 transition-transform duration-300">🏨</span>
                                <span class="group-hover:translate-x-0.5 transition-transform duration-300">จัดการโรงแรม</span>
                                <span class="absolute bottom-0 left-0 w-0 h-0.5 bg-gradient-to-r from-teal-600 to-cyan-600 group-hover:w-full transition-all duration-300"></span>
                            </a>
                        @endif
                        @if(Auth::user()->ownedBots()->where('is_rentable', true)->exists())
                            <a href="{{ route('owner-dashboard.index') }}"
                               class="nav-link-premium group relative inline-flex items-center gap-2 px-4 py-2 text-sm font-semibold rounded-lg transition-all duration-300 hover:bg-gradient-to-r hover:from-violet-50 hover:to-purple-50 dark:hover:from-violet-900/30 dark:hover:to-purple-900/30"
                               style="color: {{ $headerTextColor }};">
                                <span class="text-lg group-hover:scale-110 transition-transform duration-300">📊</span>
                                <span class="group-hover:translate-x-0.5 transition-transform duration-300">Dashboard</span>
                                <span class="absolute bottom-0 left-0 w-0 h-0.5 bg-gradient-to-r from-violet-600 to-purple-600 group-hover:w-full transition-all duration-300"></span>
                            </a>
                        @endif
                    @endauth
                    <a href="{{ route('about.professional') }}"
                       class="nav-link-premium group relative inline-flex items-center gap-2 px-4 py-2 text-sm font-semibold rounded-lg transition-all duration-300 hover:bg-gradient-to-r hover:from-slate-50 hover:to-gray-50 dark:hover:from-slate-900/30 dark:hover:to-gray-900/30"
                       style="color: {{ $headerTextColor }};">
                        <span class="text-lg group-hover:scale-110 transition-transform duration-300">ℹ️</span>
                        <span class="group-hover:translate-x-0.5 transition-transform duration-300">เกี่ยวกับเรา</span>
                        <span class="absolute bottom-0 left-0 w-0 h-0.5 bg-gradient-to-r from-slate-600 to-gray-600 group-hover:w-full transition-all duration-300"></span>
                    </a>
                    <a href="{{ route('platform.wiki') }}"
                       class="nav-link-premium group relative inline-flex items-center gap-2 px-4 py-2 text-sm font-semibold rounded-lg transition-all duration-300 hover:bg-gradient-to-r hover:from-purple-50 hover:to-fuchsia-50 dark:hover:from-purple-900/30 dark:hover:to-fuchsia-900/30"
                       style="color: {{ $headerTextColor }};"
                       title="สารานุกรมความรู้ - Platform Wiki">
                        <span class="text-lg group-hover:scale-110 transition-transform duration-300">📚</span>
                        <span class="group-hover:translate-x-0.5 transition-transform duration-300">Platform Wiki</span>
                        <span class="absolute bottom-0 left-0 w-0 h-0.5 bg-gradient-to-r from-purple-600 to-fuchsia-600 group-hover:w-full transition-all duration-300"></span>
                    </a>
                    <a href="{{ route('contact') }}"
                       class="nav-link-premium group relative inline-flex items-center gap-2 px-4 py-2 text-sm font-semibold rounded-lg transition-all duration-300 hover:bg-gradient-to-r hover:from-sky-50 hover:to-blue-50 dark:hover:from-sky-900/30 dark:hover:to-blue-900/30"
                       style="color: {{ $headerTextColor }};">
                        <span class="text-lg group-hover:scale-110 transition-transform duration-300">✉️</span>
                        <span class="group-hover:translate-x-0.5 transition-transform duration-300">ติดต่อเรา</span>
                        <span class="absolute bottom-0 left-0 w-0 h-0.5 bg-gradient-to-r from-sky-600 to-blue-600 group-hover:w-full transition-all duration-300"></span>
                    </a>
                @endif
            </div>

            <!-- Right Side: Language + Auth -->
            <div class="hidden sm:flex sm:items-center space-x-4 w-48 justify-end">
                <!-- Language Switcher -->
                <div class="relative z-50">
                    <x-language-switcher-pro />
                </div>

                <!-- Shopping Cart Icon -->
                @auth
                <div class="relative" x-data="{ cartCount: 0 }" x-init="
                    // Fetch cart count on load
                    fetch('{{ route('cart.count') }}')
                        .then(response => response.json())
                        .then(data => cartCount = data.count)
                        .catch(error => console.error('Error fetching cart count:', error));

                    // Refresh cart count every 30 seconds
                    setInterval(() => {
                        fetch('{{ route('cart.count') }}')
                            .then(response => response.json())
                            .then(data => cartCount = data.count)
                            .catch(error => console.error('Error fetching cart count:', error));
                    }, 30000);
                ">
                    <a href="{{ route('cart.index') }}"
                       class="relative inline-flex items-center p-2 text-sm font-medium rounded-lg hover:bg-gray-100 transition duration-150 ease-in-out"
                       style="color: {{ $headerTextColor }};"
                       onmouseover="this.style.color='{{ $headerHoverColor }}';"
                       onmouseout="this.style.color='{{ $headerTextColor }}';"
                       title="ตะกร้าสินค้า">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/>
                        </svg>
                        <span x-show="cartCount > 0"
                              x-text="cartCount"
                              class="absolute -top-1 -right-1 inline-flex items-center justify-center px-2 py-1 text-xs font-bold leading-none text-white transform bg-red-600 rounded-full min-w-[1.25rem]">
                        </span>
                    </a>
                </div>
                @endauth

                @auth
                    <div class="ml-3 relative" x-data="{ open: false }">
                        <button @click="open = !open"
                                class="flex items-center text-sm font-medium focus:outline-none transition duration-150 ease-in-out"
                                style="color: {{ $headerTextColor }};"
                                onmouseover="this.style.color='{{ $headerHoverColor }}';"
                                onmouseout="this.style.color='{{ $headerTextColor }}';">
                            <span>{{ Auth::user()->name }}</span>
                            <svg class="ml-2 -mr-0.5 h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                            </svg>
                        </button>

                        <div x-show="open"
                             @click.away="open = false"
                             class="origin-top-right absolute right-0 mt-2 w-48 rounded-md shadow-lg py-1 bg-white ring-1 ring-black ring-opacity-5"
                             style="display: none;">
                            <a href="{{ route('admin.dashboard') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">แดชบอร์ด</a>
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit" class="block w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">ออกจากระบบ</button>
                            </form>
                        </div>
                    </div>
                @else
                    <a href="{{ route('login') }}"
                       class="nav-link-premium group relative inline-flex items-center gap-2 px-4 py-2 text-sm font-semibold rounded-lg transition-all duration-300 hover:bg-gradient-to-r hover:from-indigo-50 hover:to-blue-50 dark:hover:from-indigo-900/30 dark:hover:to-blue-900/30"
                       style="color: {{ $headerTextColor }};">
                        <svg class="w-4 h-4 group-hover:scale-110 transition-transform duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"></path>
                        </svg>
                        <span>เข้าสู่ระบบ</span>
                        <span class="absolute bottom-0 left-0 w-0 h-0.5 bg-gradient-to-r from-indigo-600 to-blue-600 group-hover:w-full transition-all duration-300"></span>
                    </a>
                    <a href="{{ route('register') }}"
                       class="nav-link-premium group relative inline-flex items-center gap-2 px-4 py-2 text-sm font-bold rounded-lg transition-all duration-300 hover:bg-gradient-to-r hover:from-violet-50 hover:to-purple-50 dark:hover:from-violet-900/30 dark:hover:to-purple-900/30"
                       style="color: {{ $headerTextColor }};">
                        <svg class="w-4 h-4 group-hover:scale-110 transition-transform duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"></path>
                        </svg>
                        <span>สมัครสมาชิก</span>
                        <span class="absolute bottom-0 left-0 w-0 h-0.5 bg-gradient-to-r from-violet-600 to-purple-600 group-hover:w-full transition-all duration-300"></span>
                    </a>
                @endauth
            </div>

            <!-- Mobile Hamburger -->
            <div class="-mr-2 flex items-center sm:hidden">
                <button @click="open = !open"
                        class="inline-flex items-center justify-center p-2 rounded-md transition duration-150 ease-in-out"
                        style="color: {{ $headerTextColor }};"
                        onmouseover="this.style.color='{{ $headerHoverColor }}';"
                        onmouseout="this.style.color='{{ $headerTextColor }}';">
                    <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                        <path :class="{'hidden': open, 'inline-flex': !open }" class="inline-flex" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        <path :class="{'hidden': !open, 'inline-flex': open }" class="hidden" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <!-- Responsive Mobile Navigation Menu -->
    <div :class="{'block': open, 'hidden': !open}" class="hidden sm:hidden">
        <div class="pt-2 pb-3 space-y-1">
            @if($menuItems && $menuItems->count() > 0)
                @foreach($menuItems as $menuItem)
                    @if($menuItem->shouldDisplay())
                        <a href="{{ $menuItem->url ?? '#' }}"
                           class="block pl-3 pr-4 py-2 border-l-4 border-transparent text-base font-medium transition duration-150 ease-in-out"
                           style="color: {{ $headerTextColor }};"
                           @if($menuItem->target === '_blank') target="_blank" rel="noopener noreferrer" @endif>
                            @if($menuItem->icon){{ $menuItem->icon }} @endif{{ $menuItem->title }}
                        </a>
                    @endif
                @endforeach
            @else
                {{-- Fallback to hardcoded menu if no database items --}}
                <a href="{{ route('home') }}"
                   class="block pl-3 pr-4 py-2 border-l-4 border-transparent text-base font-medium transition duration-150 ease-in-out"
                   style="color: {{ $headerTextColor }};">
                    หน้าแรก
                </a>
                <a href="{{ route('marketplace.index') }}"
                   class="block pl-3 pr-4 py-2 border-l-4 border-transparent text-base font-medium transition duration-150 ease-in-out"
                   style="color: {{ $headerTextColor }};">
                    🤖 ตลาดบอท
                </a>
                <a href="{{ route('shop.index') }}"
                   class="block pl-3 pr-4 py-2 border-l-4 border-transparent text-base font-medium transition duration-150 ease-in-out"
                   style="color: {{ $headerTextColor }};">
                    🛍️ ช๊อปปิ้ง
                </a>
                <a href="{{ route('hotels.index') }}"
                   class="block pl-3 pr-4 py-2 border-l-4 border-transparent text-base font-medium transition duration-150 ease-in-out"
                   style="color: {{ $headerTextColor }};">
                    🏨 จองโรงแรม
                </a>
                @auth
                    <a href="{{ route('my-rentals.index') }}"
                       class="block pl-3 pr-4 py-2 border-l-4 border-transparent text-base font-medium transition duration-150 ease-in-out"
                       style="color: {{ $headerTextColor }};">
                        💼 การเช่าของฉัน
                    </a>
                    @if(Auth::user()->ownedBots()->where('is_rentable', true)->exists())
                        <a href="{{ route('owner-dashboard.index') }}"
                           class="block pl-3 pr-4 py-2 border-l-4 border-transparent text-base font-medium transition duration-150 ease-in-out"
                           style="color: {{ $headerTextColor }};">
                            📊 Dashboard เจ้าของ
                        </a>
                    @endif
                @endauth
                <a href="{{ route('about.professional') }}"
                   class="block pl-3 pr-4 py-2 border-l-4 border-transparent text-base font-medium transition duration-150 ease-in-out"
                   style="color: {{ $headerTextColor }};">
                    เกี่ยวกับเรา
                </a>
                <a href="{{ route('platform.wiki') }}"
                   class="block mx-3 my-2 px-4 py-3 bg-gradient-to-r from-purple-600 to-pink-600 text-white rounded-lg text-base font-bold text-center shadow-lg"
                   style="border: none;">
                    📚 Platform Wiki - สารานุกรมความรู้
                </a>
                <a href="{{ route('contact') }}"
                   class="block pl-3 pr-4 py-2 border-l-4 border-transparent text-base font-medium transition duration-150 ease-in-out"
                   style="color: {{ $headerTextColor }};">
                    ติดต่อเรา
                </a>
            @endif
        </div>

        <!-- Mobile Language Switcher -->
        <div class="pt-4 pb-3 border-t" style="border-color: {{ $headerBorderColor }};">
            <div class="px-4">
                <div class="text-sm mb-2" style="color: {{ $headerTextColor }};">เลือกภาษา</div>
                <x-language-switcher-pro />
            </div>
        </div>

        <!-- Mobile Auth Links -->
        @auth
            <div class="pt-4 pb-3 border-t" style="border-color: {{ $headerBorderColor }};"
                 x-data="{ cartCount: 0 }" x-init="
                    fetch('{{ route('cart.count') }}')
                        .then(response => response.json())
                        .then(data => cartCount = data.count)
                        .catch(error => console.error('Error fetching cart count:', error));
                ">
                <a href="{{ route('cart.index') }}"
                   class="flex items-center justify-between pl-3 pr-4 py-2 border-l-4 border-transparent text-base font-medium"
                   style="color: {{ $headerTextColor }};">
                    <span>🛒 ตะกร้าสินค้า</span>
                    <span x-show="cartCount > 0"
                          x-text="cartCount"
                          class="inline-flex items-center justify-center px-2 py-1 text-xs font-bold leading-none text-white bg-red-600 rounded-full min-w-[1.25rem]">
                    </span>
                </a>
            </div>
        @endauth

        @guest
            <div class="pt-4 pb-3 border-t" style="border-color: {{ $headerBorderColor }};">
                <div class="space-y-1">
                    <a href="{{ route('login') }}"
                       class="block pl-3 pr-4 py-2 border-l-4 border-transparent text-base font-medium"
                       style="color: {{ $headerTextColor }};">
                        เข้าสู่ระบบ
                    </a>
                    <a href="{{ route('register') }}"
                       class="block pl-3 pr-4 py-2 border-l-4 border-indigo-500 text-base font-medium bg-indigo-50"
                       style="color: #4f46e5;">
                        สมัครสมาชิก
                    </a>
                </div>
            </div>
        @endguest
    </div>
</nav>

@push('scripts')
<script>
function navigationMenu() {
    return {
        open: false,
        scrolled: false,
        hidden: false,
        lastScrollTop: 0,
        scrollThreshold: 50,

        init() {
            @if($headerHideOnScroll || $headerShrinkOnScroll || $headerStickyScroll || $headerTransparentOnScroll)
            // Listen to scroll events
            window.addEventListener('scroll', this.handleScroll.bind(this));
            @endif
        },

        handleScroll() {
            const currentScrollTop = window.pageYOffset || document.documentElement.scrollTop;

            // Detect if scrolled past threshold
            this.scrolled = currentScrollTop > this.scrollThreshold;

            @if($headerHideOnScroll)
            // Hide/show header based on scroll direction
            if (currentScrollTop > this.lastScrollTop && currentScrollTop > this.scrollThreshold) {
                // Scrolling down
                this.hidden = true;
            } else {
                // Scrolling up
                this.hidden = false;
            }
            @endif

            this.lastScrollTop = currentScrollTop <= 0 ? 0 : currentScrollTop;
        }
    }
}
</script>
@endpush

<style>
/* Premium Navigation Link Styles */
.nav-link-premium {
    /* Base styles are defined in Tailwind classes */
}

/* 3D Header Effects */
.header-3d-effect {
    position: relative;
}

.header-3d-effect::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: linear-gradient(
        to bottom,
        rgba(255, 255, 255, 0.8) 0%,
        rgba(255, 255, 255, 0.4) 50%,
        rgba(255, 255, 255, 0.1) 100%
    );
    pointer-events: none;
    z-index: 1;
    border-radius: inherit;
}

.dark .header-3d-effect::before {
    background: linear-gradient(
        to bottom,
        rgba(255, 255, 255, 0.05) 0%,
        rgba(255, 255, 255, 0.02) 50%,
        rgba(0, 0, 0, 0.1) 100%
    );
}

.header-3d-effect::after {
    content: '';
    position: absolute;
    top: -2px;
    left: 0;
    right: 0;
    height: 2px;
    background: linear-gradient(
        to right,
        transparent 0%,
        rgba(139, 92, 246, 0.5) 20%,
        rgba(59, 130, 246, 0.5) 40%,
        rgba(16, 185, 129, 0.5) 60%,
        rgba(245, 158, 11, 0.5) 80%,
        transparent 100%
    );
    pointer-events: none;
    z-index: 2;
    animation: shimmer 3s infinite linear;
}

@keyframes shimmer {
    0% {
        background-position: -200% center;
    }
    100% {
        background-position: 200% center;
    }
}

/* Logo 3D Effects */
.logo-3d-container {
    perspective: 1000px;
}

.logo-3d-effect {
    transform-style: preserve-3d;
    filter: drop-shadow(0 4px 8px rgba(0, 0, 0, 0.15))
            drop-shadow(0 2px 4px rgba(0, 0, 0, 0.1));
    transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
}

.logo-animated:hover .logo-3d-effect {
    transform: translateY(-2px) rotateY(5deg) scale(1.05);
    filter: drop-shadow(0 8px 16px rgba(0, 0, 0, 0.2))
            drop-shadow(0 4px 8px rgba(0, 0, 0, 0.15))
            drop-shadow(0 0 20px rgba(139, 92, 246, 0.3));
}

.logo-animated {
    transition: all 0.3s ease;
}

/* Enhanced 3D Navigation Links */
@if($header3dEnabled)
.nav-link-premium {
    position: relative;
    transform-style: preserve-3d;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.nav-link-premium::before {
    content: '';
    position: absolute;
    inset: 0;
    border-radius: inherit;
    background: inherit;
    filter: blur(8px);
    opacity: 0;
    transition: opacity 0.3s ease;
    z-index: -1;
}

.nav-link-premium:hover::before {
    opacity: 0.5;
}

.nav-link-premium:hover {
    transform: translateY(-1px) translateZ(4px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1),
                0 2px 6px rgba(0, 0, 0, 0.06);
}

.nav-link-premium:active {
    transform: translateY(0) translateZ(2px);
}
@endif

/* Windows UI Mode Adjustments */
@if($headerWindowsUiMode)
.header-navigation {
    backdrop-filter: blur(30px) saturate(150%);
    -webkit-backdrop-filter: blur(30px) saturate(150%);
    background: rgba(255, 255, 255, 0.85) !important;
    border-bottom: 1px solid rgba(0, 0, 0, 0.08);
}

.dark .header-navigation {
    background: rgba(30, 30, 30, 0.85) !important;
    border-bottom: 1px solid rgba(255, 255, 255, 0.08);
}

.header-navigation * {
    font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
}
@endif

/* Responsive Adjustments */
@media (max-width: 640px) {
    .header-3d-effect {
        transform: none !important;
        perspective: none !important;
    }

    .logo-3d-effect {
        filter: drop-shadow(0 2px 4px rgba(0, 0, 0, 0.1));
    }
}

/* Smooth scroll behavior for header transitions */
.header-scrolled {
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.header-hidden {
    transform: translateY(-100%) !important;
}

/* Loading shimmer animation for logo */
@keyframes logo-pulse {
    0%, 100% {
        opacity: 1;
        transform: scale(1);
    }
    50% {
        opacity: 0.9;
        transform: scale(1.02);
    }
}

.logo-animated .logo-3d-effect {
    animation: logo-pulse 4s ease-in-out infinite;
}

.logo-animated:hover .logo-3d-effect {
    animation: none;
}
</style>
