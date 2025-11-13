@props(['type' => 'admin'])

@php
    use App\Models\ClassicXSetting;
    use App\Models\Setting;

    $logo = Setting::get('logo');
    $appName = Setting::get('app_name', 'TP-Affiliate');

    // Classic X Settings
    $sidebarWidth = ClassicXSetting::get('classic_x_sidebar_width', 280);
    $sidebarCollapsedWidth = ClassicXSetting::get('classic_x_sidebar_collapsed_width', 64);
    $sidebarPosition = ClassicXSetting::get('classic_x_sidebar_position', 'left');
    $sidebarCollapsible = ClassicXSetting::get('classic_x_sidebar_collapsible', true);

    // Colors
    $primaryColor = ClassicXSetting::get('classic_x_primary_color', '#2271b1');
    $sidebarBg = ClassicXSetting::get('classic_x_sidebar_bg', '#1d2327');
    $sidebarText = ClassicXSetting::get('classic_x_sidebar_text', '#ffffff');
    $sidebarHoverBg = ClassicXSetting::get('classic_x_sidebar_hover_bg', '#2c3338');
    $sidebarActiveBg = ClassicXSetting::get('classic_x_sidebar_active_bg', '#0073aa');
    $sidebarActiveText = ClassicXSetting::get('classic_x_sidebar_active_text', '#ffffff');
    $submenuBg = ClassicXSetting::get('classic_x_submenu_bg', '#23282d');

    // Typography
    $fontFamily = ClassicXSetting::get('classic_x_font_family', '-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,sans-serif');
    $menuFontSize = ClassicXSetting::get('classic_x_menu_font_size', 14);
    $submenuFontSize = ClassicXSetting::get('classic_x_submenu_font_size', 13);

    // Visual Effects
    $enableShadows = ClassicXSetting::get('classic_x_enable_shadows', true);
    $enableGradients = ClassicXSetting::get('classic_x_enable_gradients', true);
    $enableAnimations = ClassicXSetting::get('classic_x_enable_animations', true);
    $shadowIntensity = ClassicXSetting::get('classic_x_shadow_intensity', 'medium');
    $animationSpeed = ClassicXSetting::get('classic_x_animation_speed', 300);

    // Menu Behavior
    $autoCollapseMobile = ClassicXSetting::get('classic_x_auto_collapse_mobile', true);
    $mobileBreakpoint = ClassicXSetting::get('classic_x_mobile_breakpoint', 768);
    $stickySidebar = ClassicXSetting::get('classic_x_sticky_sidebar', true);
    $submenuIndicator = ClassicXSetting::get('classic_x_submenu_indicator', 'chevron');

    // Advanced
    $enable3dDepth = ClassicXSetting::get('classic_x_enable_3d_depth', true);
    $depthIntensity = ClassicXSetting::get('classic_x_depth_intensity', 'medium');
    $enableBlur = ClassicXSetting::get('classic_x_enable_blur', false);
    $blurAmount = ClassicXSetting::get('classic_x_blur_amount', 10);

    // Logo
    $showLogo = ClassicXSetting::get('classic_x_show_logo', true);
    $logoHeight = ClassicXSetting::get('classic_x_logo_height', 48);
    $logoPadding = ClassicXSetting::get('classic_x_logo_padding', 16);

    // Badges
    $enableBadges = ClassicXSetting::get('classic_x_enable_badges', true);
    $badgeStyle = ClassicXSetting::get('classic_x_badge_style', 'rounded');
    $badgeColor = ClassicXSetting::get('classic_x_badge_color', '#d63638');

    // Shadow styles
    $shadowStyle = match($shadowIntensity) {
        'light' => '0 1px 3px rgba(0,0,0,0.12), 0 1px 2px rgba(0,0,0,0.08)',
        'medium' => '0 4px 6px rgba(0,0,0,0.15), 0 2px 4px rgba(0,0,0,0.12)',
        'strong' => '0 10px 20px rgba(0,0,0,0.25), 0 6px 10px rgba(0,0,0,0.15)',
        default => '0 4px 6px rgba(0,0,0,0.15)',
    };

    // Depth styles
    $depthStyle = match($depthIntensity) {
        'light' => 'translateZ(5px)',
        'medium' => 'translateZ(10px)',
        'strong' => 'translateZ(20px)',
        default => 'translateZ(10px)',
    };

    // Current route for active state
    $currentRoute = request()->route() ? request()->route()->getName() : '';
@endphp

<style>
    :root {
        --classic-x-sidebar-width: {{ $sidebarWidth }}px;
        --classic-x-sidebar-collapsed-width: {{ $sidebarCollapsedWidth }}px;
        --classic-x-primary-color: {{ $primaryColor }};
        --classic-x-sidebar-bg: {{ $sidebarBg }};
        --classic-x-sidebar-text: {{ $sidebarText }};
        --classic-x-sidebar-hover-bg: {{ $sidebarHoverBg }};
        --classic-x-sidebar-active-bg: {{ $sidebarActiveBg }};
        --classic-x-sidebar-active-text: {{ $sidebarActiveText }};
        --classic-x-submenu-bg: {{ $submenuBg }};
        --classic-x-animation-speed: {{ $animationSpeed }}ms;
        --classic-x-badge-color: {{ $badgeColor }};
    }

    .classic-x-sidebar {
        font-family: {{ $fontFamily }};
        width: var(--classic-x-sidebar-width);
        background: var(--classic-x-sidebar-bg);
        color: var(--classic-x-sidebar-text);
        height: 100vh;
        position: fixed;
        {{ $sidebarPosition }}: 0;
        top: 0;
        z-index: 1000;
        overflow-x: hidden;
        display: flex;
        flex-direction: column;
        @if($enableShadows)
        box-shadow: {{ $shadowStyle }};
        @endif
        @if($enableAnimations)
        transition: all var(--classic-x-animation-speed) cubic-bezier(0.4, 0, 0.2, 1);
        @endif
        @if($enableBlur)
        backdrop-filter: blur({{ $blurAmount }}px);
        @endif
        @if($enable3dDepth)
        transform-style: preserve-3d;
        perspective: 1000px;
        @endif
    }

    .classic-x-sidebar-scrollable {
        flex: 1;
        overflow-y: auto;
        overflow-x: hidden;
    }

    .classic-x-sidebar.collapsed {
        width: var(--classic-x-sidebar-collapsed-width);
    }

    .classic-x-sidebar::-webkit-scrollbar {
        width: 8px;
    }

    .classic-x-sidebar::-webkit-scrollbar-track {
        background: rgba(0, 0, 0, 0.1);
    }

    .classic-x-sidebar::-webkit-scrollbar-thumb {
        background: rgba(255, 255, 255, 0.2);
        border-radius: 4px;
    }

    .classic-x-sidebar::-webkit-scrollbar-thumb:hover {
        background: rgba(255, 255, 255, 0.3);
    }

    .classic-x-logo-container {
        padding: {{ $logoPadding }}px;
        border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        @if($enableAnimations)
        transition: all var(--classic-x-animation-speed);
        @endif
    }

    .classic-x-logo {
        max-height: {{ $logoHeight }}px;
        width: auto;
    }

    .classic-x-menu {
        list-style: none;
        padding: 0;
        margin: 0;
    }

    .classic-x-menu-item {
        position: relative;
        @if($enable3dDepth)
        transform-style: preserve-3d;
        @endif
    }

    .classic-x-menu-link {
        display: flex;
        align-items: center;
        padding: 12px 20px;
        color: var(--classic-x-sidebar-text);
        text-decoration: none;
        font-size: {{ $menuFontSize }}px;
        font-weight: 400;
        @if($enableAnimations)
        transition: all var(--classic-x-animation-speed);
        @endif
        position: relative;
        overflow: hidden;
    }

    .classic-x-menu-link:hover {
        background: var(--classic-x-sidebar-hover-bg);
        @if($enable3dDepth)
        transform: {{ $depthStyle }};
        @endif
        @if($enableGradients)
        background: linear-gradient(90deg, var(--classic-x-sidebar-hover-bg) 0%, rgba(255,255,255,0.05) 100%);
        @endif
    }

    .classic-x-menu-link.active {
        background: var(--classic-x-sidebar-active-bg);
        color: var(--classic-x-sidebar-active-text);
        font-weight: 600;
        @if($enableGradients)
        background: linear-gradient(135deg, var(--classic-x-sidebar-active-bg) 0%, var(--classic-x-primary-color) 100%);
        @endif
        @if($enableShadows)
        box-shadow: inset 4px 0 0 0 var(--classic-x-primary-color);
        @endif
    }

    .classic-x-menu-icon {
        width: 24px;
        height: 24px;
        margin-right: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 18px;
        flex-shrink: 0;
    }

    .classic-x-menu-text {
        flex: 1;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .classic-x-submenu-indicator {
        width: 20px;
        height: 20px;
        display: flex;
        align-items: center;
        justify-content: center;
        @if($enableAnimations)
        transition: transform var(--classic-x-animation-speed);
        @endif
    }

    .classic-x-submenu-indicator.open {
        transform: rotate(180deg);
    }

    .classic-x-submenu {
        background: var(--classic-x-submenu-bg);
        list-style: none;
        padding: 0;
        margin: 0;
        max-height: 0;
        overflow: hidden;
        @if($enableAnimations)
        transition: max-height var(--classic-x-animation-speed) ease-in-out;
        @endif
    }

    .classic-x-submenu.open {
        max-height: 1000px;
    }

    .classic-x-submenu-link {
        display: flex;
        align-items: center;
        padding: 10px 20px 10px 56px;
        color: rgba(255, 255, 255, 0.7);
        text-decoration: none;
        font-size: {{ $submenuFontSize }}px;
        @if($enableAnimations)
        transition: all var(--classic-x-animation-speed);
        @endif
    }

    .classic-x-submenu-link:hover {
        color: #fff;
        background: rgba(255, 255, 255, 0.05);
        padding-left: 60px;
    }

    .classic-x-submenu-link.active {
        color: var(--classic-x-primary-color);
        font-weight: 500;
    }

    .classic-x-badge {
        padding: 2px 8px;
        border-radius: @if($badgeStyle === 'pill') 12px @elseif($badgeStyle === 'square') 2px @else 4px @endif;
        background: var(--classic-x-badge-color);
        color: #fff;
        font-size: 11px;
        font-weight: 600;
        margin-left: 8px;
    }

    .classic-x-collapse-btn {
        position: absolute;
        top: 16px;
        {{ $sidebarPosition === 'left' ? 'right' : 'left' }}: -12px;
        width: 24px;
        height: 24px;
        background: var(--classic-x-primary-color);
        border: 2px solid var(--classic-x-sidebar-bg);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        color: #fff;
        font-size: 12px;
        @if($enableShadows)
        box-shadow: 0 2px 8px rgba(0,0,0,0.2);
        @endif
        @if($enableAnimations)
        transition: all var(--classic-x-animation-speed);
        @endif
    }

    .classic-x-collapse-btn:hover {
        transform: scale(1.1);
    }

    .classic-x-sidebar.collapsed .classic-x-menu-text,
    .classic-x-sidebar.collapsed .classic-x-submenu-indicator,
    .classic-x-sidebar.collapsed .classic-x-badge {
        display: none;
    }

    .classic-x-sidebar.collapsed .classic-x-logo-container {
        text-align: center;
    }

    .classic-x-sidebar.collapsed .classic-x-menu-icon {
        margin-right: 0;
    }

    .classic-x-sidebar.collapsed .classic-x-submenu {
        display: none;
    }

    /* Content margin based on sidebar position */
    @if($sidebarPosition === 'left')
    .classic-x-content {
        margin-left: var(--classic-x-sidebar-width);
    }
    .classic-x-content.sidebar-collapsed {
        margin-left: var(--classic-x-sidebar-collapsed-width);
    }
    @else
    .classic-x-content {
        margin-right: var(--classic-x-sidebar-width);
    }
    .classic-x-content.sidebar-collapsed {
        margin-right: var(--classic-x-sidebar-collapsed-width);
    }
    @endif

    @if($autoCollapseMobile)
    @media (max-width: {{ $mobileBreakpoint }}px) {
        .classic-x-sidebar {
            transform: translateX({{ $sidebarPosition === 'left' ? '-100%' : '100%' }});
        }
        .classic-x-sidebar.mobile-open {
            transform: translateX(0);
        }
        .classic-x-content {
            margin-left: 0 !important;
            margin-right: 0 !important;
        }
    }
    @endif
</style>

<div class="classic-x-sidebar"
     id="classicXSidebar"
     x-data="{
         collapsed: localStorage.getItem('classic-x-sidebar-collapsed') === 'true',
         openSubmenus: JSON.parse(localStorage.getItem('classic-x-open-submenus') || '[]'),
         init() {
             if (this.collapsed) {
                 this.$el.classList.add('collapsed');
             }
         },
         toggleSidebar() {
             this.collapsed = !this.collapsed;
             localStorage.setItem('classic-x-sidebar-collapsed', this.collapsed);
             if (this.collapsed) {
                 this.$el.classList.add('collapsed');
                 document.getElementById('classicXContent')?.classList.add('sidebar-collapsed');
             } else {
                 this.$el.classList.remove('collapsed');
                 document.getElementById('classicXContent')?.classList.remove('sidebar-collapsed');
             }
         }
     }"
     :class="{ 'collapsed': collapsed }">

    @if($sidebarCollapsible)
    <button class="classic-x-collapse-btn" @click="toggleSidebar()" title="Toggle Sidebar">
        <i class="fas" :class="collapsed ? 'fa-chevron-{{ $sidebarPosition === 'left' ? 'right' : 'left' }}' : 'fa-chevron-{{ $sidebarPosition === 'left' ? 'left' : 'right' }}'"></i>
    </button>
    @endif

    @if($showLogo)
    <div class="classic-x-logo-container">
        @if($logo)
            <img src="{{ asset($logo) }}" alt="{{ $appName }}" class="classic-x-logo" x-show="!collapsed || {{ $showLogo ? 'true' : 'false' }}">
        @else
            <h2 class="text-xl font-bold" x-show="!collapsed">{{ $appName }}</h2>
        @endif
    </div>
    @endif

    <!-- Scrollable Menu Area -->
    <div class="classic-x-sidebar-scrollable">
        <x-classic-x-menu type="{{ $type }}" />
    </div>

    <!-- Classic X Menu Footer -->
    @php
        $appName = \App\Models\Setting::get('app_name', 'TP-Affiliate');
        // Get version from package.json
        $packageJson = json_decode(file_get_contents(base_path('package.json')), true);
        $appVersion = $packageJson['version'] ?? '2.169.1';
        $copyrightText = \App\Models\Setting::get('copyright_text', '© 2025 All Rights Reserved');
    @endphp
    <div class="classic-x-menu-footer" style="
        padding: 16px 20px;
        border-top: 1px solid rgba(255, 255, 255, 0.1);
        font-size: 11px;
        color: rgba(255, 255, 255, 0.5);
        line-height: 1.6;
    ">
        <div style="text-align: center;">
            <div style="font-weight: 600; color: rgba(255, 255, 255, 0.7); margin-bottom: 4px;">
                {{ $appName }}
            </div>
            <div style="margin-bottom: 4px;">
                Version {{ $appVersion }}
            </div>
            <div style="font-size: 10px;">
                {{ $copyrightText }}
            </div>
        </div>
    </div>
</div>
