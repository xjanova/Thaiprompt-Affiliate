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
$headerTextColor = \App\Models\Setting::get('header_text_color', '#374151');
$headerTextColorScroll = \App\Models\Setting::get('header_text_color_scroll', '#374151');
$headerHoverColor = \App\Models\Setting::get('header_hover_color', '#111827');
$headerBorderBottom = \App\Models\Setting::get('header_border_bottom', false);
$headerBorderColor = \App\Models\Setting::get('header_border_color', '#e5e7eb');
$headerBorderWidth = \App\Models\Setting::get('header_border_width', 1);
$headerAnimationDuration = \App\Models\Setting::get('header_animation_duration', 300);
$headerAnimationEasing = \App\Models\Setting::get('header_animation_easing', 'ease-in-out');
$headerLogoHeight = \App\Models\Setting::get('header_logo_height', 40);
$headerLogoHeightScrolled = \App\Models\Setting::get('header_logo_height_scrolled', 32);
$headerGradient = \App\Models\Setting::get('header_gradient', false);
$headerGradientFrom = \App\Models\Setting::get('header_gradient_from', '#ffffff');
$headerGradientTo = \App\Models\Setting::get('header_gradient_to', '#f3f4f6');
$headerGradientDirection = \App\Models\Setting::get('header_gradient_direction', 'to-r');

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
@endphp

<nav class="header-navigation {{ $currentShadow }}"
     x-data="navigationMenu()"
     x-init="init()"
     :class="{
         'header-scrolled': scrolled,
         'header-hidden': hidden,
         '{{ $shadowClasses[$headerShadowOnScroll ? 'lg' : 'none'] }}': scrolled && {{ $headerShadowOnScroll ? 'true' : 'false' }}
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
         backgroundColor: {{ $headerGradient ? 'null' : "'".hexToRgba($headerBgColor, $headerBgOpacity)."'" }},
         @if($headerGradient)
         backgroundImage: 'linear-gradient({{ $headerGradientDirection }}, {{ $headerGradientFrom }}, {{ $headerGradientTo }})',
         @endif
         color: '{{ $headerTextColor }}',
         @if($headerBlur)
         backdropFilter: 'blur({{ $headerBlurAmount }}px)',
         WebkitBackdropFilter: 'blur({{ $headerBlurAmount }}px)',
         @endif
         @if($headerBorderBottom)
         borderBottom: '{{ $headerBorderWidth }}px solid {{ $headerBorderColor }}',
         @endif
         @if($headerTransparent || ($headerTransparentOnScroll && scrolled))
         opacity: '0.95',
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
            <div class="shrink-0 flex items-center">
                <a href="{{ route('home') }}" class="flex items-center">
                    @if($logo)
                        <img src="{{ asset($logo) }}"
                             alt="{{ $appName }}"
                             class="object-contain transition-all"
                             :style="{
                                 height: (scrolled && {{ $headerShrinkOnScroll ? 'true' : 'false' }} ? '{{ $headerLogoHeightScrolled }}px' : '{{ $headerLogoHeight }}px')
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

            <!-- Desktop Navigation Links -->
            <div class="hidden space-x-8 sm:-my-px sm:ml-10 sm:flex">
                <a href="{{ route('home') }}"
                   class="inline-flex items-center px-1 pt-1 border-b-2 border-transparent text-sm font-medium leading-5 transition duration-150 ease-in-out"
                   style="color: {{ $headerTextColor }};"
                   onmouseover="this.style.color='{{ $headerHoverColor }}';"
                   onmouseout="this.style.color='{{ $headerTextColor }}';">
                    หน้าแรก
                </a>
                <a href="{{ route('about') }}"
                   class="inline-flex items-center px-1 pt-1 border-b-2 border-transparent text-sm font-medium leading-5 transition duration-150 ease-in-out"
                   style="color: {{ $headerTextColor }};"
                   onmouseover="this.style.color='{{ $headerHoverColor }}';"
                   onmouseout="this.style.color='{{ $headerTextColor }}';">
                    เกี่ยวกับเรา
                </a>
                <a href="{{ route('contact') }}"
                   class="inline-flex items-center px-1 pt-1 border-b-2 border-transparent text-sm font-medium leading-5 transition duration-150 ease-in-out"
                   style="color: {{ $headerTextColor }};"
                   onmouseover="this.style.color='{{ $headerHoverColor }}';"
                   onmouseout="this.style.color='{{ $headerTextColor }}';">
                    ติดต่อเรา
                </a>
            </div>

            <!-- Right Side: Language + Auth -->
            <div class="hidden sm:flex sm:items-center sm:ml-6 space-x-4">
                <!-- Language Switcher -->
                <div class="relative z-50">
                    <x-language-switcher-pro />
                </div>

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
                       class="text-sm font-medium transition"
                       style="color: {{ $headerTextColor }};"
                       onmouseover="this.style.color='{{ $headerHoverColor }}';"
                       onmouseout="this.style.color='{{ $headerTextColor }}';">
                        เข้าสู่ระบบ
                    </a>
                    <a href="{{ route('register') }}"
                       class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 focus:bg-indigo-700 active:bg-indigo-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                        สมัครสมาชิก
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
            <a href="{{ route('home') }}"
               class="block pl-3 pr-4 py-2 border-l-4 border-transparent text-base font-medium transition duration-150 ease-in-out"
               style="color: {{ $headerTextColor }};">
                หน้าแรก
            </a>
            <a href="{{ route('about') }}"
               class="block pl-3 pr-4 py-2 border-l-4 border-transparent text-base font-medium transition duration-150 ease-in-out"
               style="color: {{ $headerTextColor }};">
                เกี่ยวกับเรา
            </a>
            <a href="{{ route('contact') }}"
               class="block pl-3 pr-4 py-2 border-l-4 border-transparent text-base font-medium transition duration-150 ease-in-out"
               style="color: {{ $headerTextColor }};">
                ติดต่อเรา
            </a>
        </div>

        <!-- Mobile Language Switcher -->
        <div class="pt-4 pb-3 border-t" style="border-color: {{ $headerBorderColor }};">
            <div class="px-4">
                <div class="text-sm mb-2" style="color: {{ $headerTextColor }};">เลือกภาษา</div>
                <x-language-switcher-pro />
            </div>
        </div>

        <!-- Mobile Auth Links -->
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
