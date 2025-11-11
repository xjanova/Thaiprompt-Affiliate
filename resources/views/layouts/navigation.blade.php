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

// Import Taskbar Settings for Menu Buttons
use App\Models\WindowsUiSetting;
$taskbarBgColor = WindowsUiSetting::get('windows_taskbar_bg_color', '#1e293b');
$taskbarTextColor = WindowsUiSetting::get('windows_taskbar_text_color', '#ffffff');
$taskbarHoverBgColor = WindowsUiSetting::get('windows_taskbar_hover_bg_color', '#334155');
$taskbarUseGradient = WindowsUiSetting::get('windows_taskbar_use_gradient', false);
$taskbarGradientFrom = WindowsUiSetting::get('windows_taskbar_gradient_from', '#1e293b');
$taskbarGradientTo = WindowsUiSetting::get('windows_taskbar_gradient_to', '#0f172a');
$millenniumRgbEnabled = WindowsUiSetting::get('millennium_rgb_enabled', true);
$millenniumRgbColors = WindowsUiSetting::get('millennium_rgb_colors', ['#FF0080', '#00F0FF', '#7F00FF', '#FFD700']);

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

<nav class="header-navigation {{ $currentShadow }} backdrop-blur-xl bg-white/95 dark:bg-gray-900/95 border-b border-gray-200 dark:border-gray-700"
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
     "
     :style="{
         height: (scrolled && {{ $headerShrinkOnScroll ? 'true' : 'false' }} ? '{{ $headerHeightScrolled }}px' : '{{ $headerHeight }}px'),
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
            <div class="shrink-0 flex items-center w-48">
                <a href="{{ route('home') }}" class="flex items-center">
                    @if($logo)
                        <img src="{{ asset($logo) }}"
                             alt="{{ $appName }}"
                             class="object-contain transition-all"
                             :style="{
                                 width: (scrolled && {{ $headerShrinkOnScroll ? 'true' : 'false' }} ? '{{ $logoNavigationScrolledWidth }}px' : '{{ $logoNavigationWidth }}px'),
                                 height: (scrolled && {{ $headerShrinkOnScroll ? 'true' : 'false' }} ? '{{ $logoNavigationScrolledHeight }}px' : '{{ $logoNavigationHeight }}px')
                             }">
                    @else
                        <span class="text-2xl font-bold transition-all"
                              :style="{
                                  fontSize: (scrolled && {{ $headerShrinkOnScroll ? 'true' : 'false' }} ? '{{ $headerLogoHeightScrolled / 2.5 }}px' : '{{ $headerLogoHeight / 2.5 }}px')
                              }">
                            {{ $appName }}
                        </span>
                    @endif
                </a>
            </div>

            <!-- Desktop Navigation Links - Centered -->
            <div class="hidden sm:flex flex-1 justify-center items-center gap-3">
                @if($menuItems && $menuItems->count() > 0)
                    @foreach($menuItems as $menuItem)
                        @if($menuItem->shouldDisplay())
                            <a href="{{ $menuItem->url ?? '#' }}"
                               class="nav-link-taskbar group relative inline-flex items-center gap-2 px-4 py-2.5 text-sm font-semibold transition-all duration-300 transform hover:-translate-y-1 hover:shadow-xl border-2 backdrop-blur-sm"
                               style="color: {{ $taskbarTextColor }};
                                      @if($taskbarUseGradient)
                                      background: linear-gradient(to right, {{ $taskbarGradientFrom }}, {{ $taskbarGradientTo }});
                                      @else
                                      background-color: {{ $taskbarBgColor }};
                                      @endif
                                      border-color: rgba(255, 255, 255, 0.1);
                                      box-shadow:
                                          0 1px 2px rgba(255, 255, 255, 0.1) inset,
                                          0 -1px 2px rgba(0, 0, 0, 0.1) inset,
                                          0 2px 8px rgba(0, 0, 0, 0.15),
                                          0 4px 16px rgba(0, 0, 0, 0.1);"
                               @if($menuItem->target === '_blank') target="_blank" rel="noopener noreferrer" @endif
                               onmouseover="this.style.borderColor='rgba(255, 255, 255, 0.3)'; this.style.backgroundColor='{{ $taskbarHoverBgColor }}';"
                               onmouseout="this.style.borderColor='rgba(255, 255, 255, 0.1)'; @if($taskbarUseGradient) this.style.background='linear-gradient(to right, {{ $taskbarGradientFrom }}, {{ $taskbarGradientTo }})'; @else this.style.backgroundColor='{{ $taskbarBgColor }}'; @endif">
                                @if($menuItem->icon)<span class="text-lg group-hover:scale-110 transition-transform duration-300 drop-shadow-sm">{{ $menuItem->icon }}</span>@endif
                                <span class="group-hover:translate-x-0.5 transition-transform duration-300">{{ $menuItem->title }}</span>
                                @if($millenniumRgbEnabled)
                                <!-- RGB Border Effect on Hover -->
                                <span class="absolute bottom-0 left-0 w-0 h-0.5 group-hover:w-full transition-all duration-300 nav-rgb-border"></span>
                                @endif
                                <!-- Inner Highlight -->
                                <div class="absolute inset-x-0 top-0 h-1/2 opacity-20 pointer-events-none" style="background: linear-gradient(to bottom, rgba(255, 255, 255, 0.2), transparent);"></div>
                            </a>
                        @endif
                    @endforeach
                @else
                    {{-- Fallback to hardcoded menu if no database items --}}
                    <a href="{{ route('home') }}"
                       class="nav-link-taskbar group relative inline-flex items-center gap-2 px-4 py-2.5 text-sm font-semibold transition-all duration-300 transform hover:-translate-y-1 hover:shadow-xl border-2 backdrop-blur-sm"
                       style="color: {{ $taskbarTextColor }};
                              @if($taskbarUseGradient)
                              background: linear-gradient(to right, {{ $taskbarGradientFrom }}, {{ $taskbarGradientTo }});
                              @else
                              background-color: {{ $taskbarBgColor }};
                              @endif
                              border-color: rgba(255, 255, 255, 0.1);
                              box-shadow:
                                  0 1px 2px rgba(255, 255, 255, 0.1) inset,
                                  0 -1px 2px rgba(0, 0, 0, 0.1) inset,
                                  0 2px 8px rgba(0, 0, 0, 0.15),
                                  0 4px 16px rgba(0, 0, 0, 0.1);"
                       onmouseover="this.style.borderColor='rgba(255, 255, 255, 0.3)'; this.style.backgroundColor='{{ $taskbarHoverBgColor }}';"
                       onmouseout="this.style.borderColor='rgba(255, 255, 255, 0.1)'; @if($taskbarUseGradient) this.style.background='linear-gradient(to right, {{ $taskbarGradientFrom }}, {{ $taskbarGradientTo }})'; @else this.style.backgroundColor='{{ $taskbarBgColor }}'; @endif">
                        <span class="text-lg group-hover:scale-110 transition-transform duration-300 drop-shadow-sm">🏠</span>
                        <span class="group-hover:translate-x-0.5 transition-transform duration-300">หน้าแรก</span>
                        @if($millenniumRgbEnabled)
                        <span class="absolute bottom-0 left-0 w-0 h-0.5 group-hover:w-full transition-all duration-300 nav-rgb-border"></span>
                        @endif
                        <div class="absolute inset-x-0 top-0 h-1/2 opacity-20 pointer-events-none" style="background: linear-gradient(to bottom, rgba(255, 255, 255, 0.2), transparent);"></div>
                    </a>
                    <a href="{{ route('marketplace.index') }}"
                       class="nav-link-taskbar group relative inline-flex items-center gap-2 px-4 py-2.5 text-sm font-semibold transition-all duration-300 transform hover:-translate-y-1 hover:shadow-xl border-2 backdrop-blur-sm">
                        <span class="text-lg group-hover:scale-110 transition-transform duration-300">🤖</span>
                        <span class="group-hover:translate-x-0.5 transition-transform duration-300">ตลาดบอท</span>
                        <span class="absolute bottom-0 left-0 w-0 h-0.5 bg-gradient-to-r from-blue-600 to-cyan-600 group-hover:w-full transition-all duration-300"></span>
                    </a>
                    <a href="{{ route('shop.index') }}"
                       class="nav-link-taskbar group relative inline-flex items-center gap-2 px-4 py-2.5 text-sm font-semibold transition-all duration-300 transform hover:-translate-y-1 hover:shadow-xl border-2 backdrop-blur-sm">
                        <span class="text-lg group-hover:scale-110 transition-transform duration-300">🛍️</span>
                        <span class="group-hover:translate-x-0.5 transition-transform duration-300">ช๊อปปิ้ง</span>
                        <span class="absolute bottom-0 left-0 w-0 h-0.5 bg-gradient-to-r from-pink-600 to-rose-600 group-hover:w-full transition-all duration-300"></span>
                    </a>
                    <a href="{{ route('hotels.index') }}"
                       class="nav-link-taskbar group relative inline-flex items-center gap-2 px-4 py-2.5 text-sm font-semibold transition-all duration-300 transform hover:-translate-y-1 hover:shadow-xl border-2 backdrop-blur-sm">
                        <span class="text-lg group-hover:scale-110 transition-transform duration-300">🏨</span>
                        <span class="group-hover:translate-x-0.5 transition-transform duration-300">จองโรงแรม</span>
                        <span class="absolute bottom-0 left-0 w-0 h-0.5 bg-gradient-to-r from-orange-600 to-amber-600 group-hover:w-full transition-all duration-300"></span>
                    </a>
                    @auth
                        <a href="{{ route('my-rentals.index') }}"
                           class="nav-link-taskbar group relative inline-flex items-center gap-2 px-4 py-2.5 text-sm font-semibold transition-all duration-300 transform hover:-translate-y-1 hover:shadow-xl border-2 backdrop-blur-sm">
                            <span class="text-lg group-hover:scale-110 transition-transform duration-300">💼</span>
                            <span class="group-hover:translate-x-0.5 transition-transform duration-300">การเช่าของฉัน</span>
                            <span class="absolute bottom-0 left-0 w-0 h-0.5 bg-gradient-to-r from-emerald-600 to-teal-600 group-hover:w-full transition-all duration-300"></span>
                        </a>
                        @if(Auth::user()->is_hotel_admin && Auth::user()->managed_hotel_id)
                            <a href="{{ route('hotel-admin.dashboard') }}"
                               class="nav-link-taskbar group relative inline-flex items-center gap-2 px-4 py-2.5 text-sm font-semibold transition-all duration-300 transform hover:-translate-y-1 hover:shadow-xl border-2 backdrop-blur-sm">
                                <span class="text-lg group-hover:scale-110 transition-transform duration-300">🏨</span>
                                <span class="group-hover:translate-x-0.5 transition-transform duration-300">จัดการโรงแรม</span>
                                <span class="absolute bottom-0 left-0 w-0 h-0.5 bg-gradient-to-r from-orange-600 to-amber-600 group-hover:w-full transition-all duration-300"></span>
                            </a>
                        @endif
                        @if(Auth::user()->ownedBots()->where('is_rentable', true)->exists())
                            <a href="{{ route('owner-dashboard.index') }}"
                               class="nav-link-taskbar group relative inline-flex items-center gap-2 px-4 py-2.5 text-sm font-semibold transition-all duration-300 transform hover:-translate-y-1 hover:shadow-xl border-2 backdrop-blur-sm">
                                <span class="text-lg group-hover:scale-110 transition-transform duration-300">📊</span>
                                <span class="group-hover:translate-x-0.5 transition-transform duration-300">Dashboard</span>
                                <span class="absolute bottom-0 left-0 w-0 h-0.5 bg-gradient-to-r from-amber-600 to-orange-600 group-hover:w-full transition-all duration-300"></span>
                            </a>
                        @endif
                    @endauth
                    <a href="{{ route('about.professional') }}"
                       class="nav-link-taskbar group relative inline-flex items-center gap-2 px-4 py-2.5 text-sm font-semibold transition-all duration-300 transform hover:-translate-y-1 hover:shadow-xl border-2 backdrop-blur-sm">
                        <span class="text-lg group-hover:scale-110 transition-transform duration-300">ℹ️</span>
                        <span class="group-hover:translate-x-0.5 transition-transform duration-300">เกี่ยวกับเรา</span>
                        <span class="absolute bottom-0 left-0 w-0 h-0.5 bg-gradient-to-r from-slate-600 to-gray-600 group-hover:w-full transition-all duration-300"></span>
                    </a>
                    <a href="{{ route('platform.wiki') }}"
                       class="nav-link-special group relative inline-flex items-center gap-2 px-5 py-3 text-sm font-bold hover:scale-105 hover:-translate-y-1 transform transition-all duration-300 overflow-hidden backdrop-blur-sm"
                       title="สารานุกรมความรู้ - Platform Wiki">
                        <span class="text-lg group-hover:rotate-12 transition-transform duration-300 drop-shadow-lg">📚</span>
                        <span class="group-hover:translate-x-0.5 transition-transform duration-300">Platform Wiki</span>
                        <svg class="w-4 h-4 group-hover:translate-x-1 transition-transform duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                        </svg>
                        @if($millenniumRgbEnabled)
                        <span class="absolute bottom-0 left-0 w-0 h-0.5 group-hover:w-full transition-all duration-300 nav-rgb-border"></span>
                        @endif
                        <div class="absolute inset-x-0 top-0 h-1/2 opacity-30 pointer-events-none" style="background: linear-gradient(to bottom, rgba(255, 255, 255, 0.4), transparent);"></div>
                    </a>
                    <a href="{{ route('contact') }}"
                       class="nav-link-taskbar group relative inline-flex items-center gap-2 px-4 py-2.5 text-sm font-semibold transition-all duration-300 transform hover:-translate-y-1 hover:shadow-xl border-2 backdrop-blur-sm">
                        <span class="text-lg group-hover:scale-110 transition-transform duration-300">✉️</span>
                        <span class="group-hover:translate-x-0.5 transition-transform duration-300">ติดต่อเรา</span>
                        <span class="absolute bottom-0 left-0 w-0 h-0.5 bg-gradient-to-r from-blue-600 to-indigo-600 group-hover:w-full transition-all duration-300"></span>
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
                       class="nav-link-taskbar group relative inline-flex items-center gap-2 px-4 py-2.5 text-sm font-semibold transition-all duration-300 transform hover:-translate-y-1 hover:shadow-xl border-2 backdrop-blur-sm">
                        <svg class="w-4 h-4 group-hover:scale-110 transition-transform duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"></path>
                        </svg>
                        <span>เข้าสู่ระบบ</span>
                        @if($millenniumRgbEnabled)
                        <span class="absolute bottom-0 left-0 w-0 h-0.5 group-hover:w-full transition-all duration-300 nav-rgb-border"></span>
                        @endif
                        <div class="absolute inset-x-0 top-0 h-1/2 opacity-20 pointer-events-none" style="background: linear-gradient(to bottom, rgba(255, 255, 255, 0.2), transparent);"></div>
                    </a>
                    <a href="{{ route('register') }}"
                       class="nav-link-special group relative inline-flex items-center gap-2 px-5 py-3 font-bold text-sm hover:scale-105 hover:-translate-y-1 transform transition-all duration-300 overflow-hidden backdrop-blur-sm">
                        <svg class="w-4 h-4 group-hover:rotate-12 transition-transform duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"></path>
                        </svg>
                        <span>สมัครสมาชิก</span>
                        <svg class="w-4 h-4 group-hover:translate-x-1 transition-transform duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"></path>
                        </svg>
                        @if($millenniumRgbEnabled)
                        <span class="absolute bottom-0 left-0 w-0 h-0.5 group-hover:w-full transition-all duration-300 nav-rgb-border"></span>
                        @endif
                        <div class="absolute inset-x-0 top-0 h-1/2 opacity-30 pointer-events-none" style="background: linear-gradient(to bottom, rgba(255, 255, 255, 0.4), transparent);"></div>
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
/* Navigation Taskbar-Style Buttons */
.nav-link-taskbar {
    color: {{ $taskbarTextColor }} !important;
    @if($taskbarUseGradient)
    background: linear-gradient(to right, {{ $taskbarGradientFrom }}, {{ $taskbarGradientTo }}) !important;
    @else
    background-color: {{ $taskbarBgColor }} !important;
    @endif
    border-color: rgba(255, 255, 255, 0.1) !important;
    box-shadow:
        0 1px 2px rgba(255, 255, 255, 0.1) inset,
        0 -1px 2px rgba(0, 0, 0, 0.1) inset,
        0 2px 8px rgba(0, 0, 0, 0.15),
        0 4px 16px rgba(0, 0, 0, 0.1) !important;
}

.nav-link-taskbar:hover {
    background-color: {{ $taskbarHoverBgColor }} !important;
    border-color: rgba(255, 255, 255, 0.3) !important;
    box-shadow:
        0 1px 2px rgba(255, 255, 255, 0.15) inset,
        0 -1px 2px rgba(0, 0, 0, 0.15) inset,
        0 4px 12px rgba(0, 0, 0, 0.2),
        0 8px 24px rgba(0, 0, 0, 0.15) !important;
}

/* RGB Border Animation for Navigation Links */
@if($millenniumRgbEnabled)
@keyframes navRgbBorder {
    0%, 100% {
        background: linear-gradient(90deg,
            {{ $millenniumRgbColors[0] ?? '#FF0080' }} 0%,
            {{ $millenniumRgbColors[1] ?? '#00F0FF' }} 25%,
            {{ $millenniumRgbColors[2] ?? '#7F00FF' }} 50%,
            {{ $millenniumRgbColors[3] ?? '#FFD700' }} 75%,
            {{ $millenniumRgbColors[0] ?? '#FF0080' }} 100%
        );
        background-size: 200% 100%;
        background-position: 0% 50%;
    }
    50% {
        background-position: 100% 50%;
    }
}

.nav-rgb-border {
    background: linear-gradient(90deg,
        {{ $millenniumRgbColors[0] ?? '#FF0080' }} 0%,
        {{ $millenniumRgbColors[1] ?? '#00F0FF' }} 25%,
        {{ $millenniumRgbColors[2] ?? '#7F00FF' }} 50%,
        {{ $millenniumRgbColors[3] ?? '#FFD700' }} 75%,
        {{ $millenniumRgbColors[0] ?? '#FF0080' }} 100%
    );
    background-size: 200% 100%;
    animation: navRgbBorder 5s linear infinite;
    filter: blur(1px);
    box-shadow: 0 0 10px currentColor, 0 0 20px currentColor;
}
@endif

/* Special Buttons - Taskbar Style with Enhanced Effects */
.nav-link-special {
    color: {{ $taskbarTextColor }} !important;
    @if($taskbarUseGradient)
    background: linear-gradient(to right, {{ $taskbarGradientFrom }}, {{ $taskbarGradientTo }}) !important;
    @else
    background-color: {{ $taskbarBgColor }} !important;
    @endif
    border: 2px solid rgba(255, 255, 255, 0.2) !important;
    box-shadow:
        0 1px 3px rgba(255, 255, 255, 0.2) inset,
        0 -1px 2px rgba(0, 0, 0, 0.2) inset,
        0 8px 24px rgba(0, 0, 0, 0.3),
        0 16px 48px rgba(0, 0, 0, 0.2) !important;
}

.nav-link-special:hover {
    background-color: {{ $taskbarHoverBgColor }} !important;
    border-color: rgba(255, 255, 255, 0.4) !important;
    box-shadow:
        0 1px 4px rgba(255, 255, 255, 0.3) inset,
        0 -1px 3px rgba(0, 0, 0, 0.3) inset,
        0 12px 32px rgba(0, 0, 0, 0.4),
        0 24px 64px rgba(0, 0, 0, 0.3) !important;
}
</style>
