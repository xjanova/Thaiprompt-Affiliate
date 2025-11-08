@php
    use App\Models\WindowsUiSetting;

    $taskbarSettings = WindowsUiSetting::getTaskbarSettings();
    $startMenuItems = WindowsUiSetting::getStartMenuItems();
    $taskbarApps = WindowsUiSetting::getTaskbarApps();
    $systemTrayIcons = WindowsUiSetting::getSystemTrayIcons();
    $rgbSettings = WindowsUiSetting::getRgbSettings();

    $logo = \App\Models\Setting::get('logo');
    $appName = \App\Models\Setting::get('app_name', 'TP-Affiliate');
    $startButtonText = WindowsUiSetting::get('windows_start_button_text', 'เริ่ม');
    $useLogoForStart = WindowsUiSetting::get('windows_start_button_use_logo', true);

    $position = $taskbarSettings['position'];
    $height = $taskbarSettings['height'];
    $transparency = $taskbarSettings['transparency'];
    $blur = $taskbarSettings['blur'];
@endphp

<div
    x-data="windowsTaskbar()"
    x-init="init()"
    class="fixed left-0 right-0 z-50 {{ $position === 'top' ? 'top-0' : 'bottom-0' }}"
    style="height: {{ $height }}px;">

    <!-- RGB Border Animation -->
    @if($rgbSettings['enabled'])
        <div class="absolute inset-0 rgb-border-animation"></div>
    @endif

    <!-- Taskbar Background -->
    <div class="absolute inset-0 backdrop-blur-{{ $blur ? 'xl' : 'none' }} bg-gray-900/{{ $transparency }} border-{{ $position === 'top' ? 'b' : 't' }} border-white/10"></div>

    <!-- Taskbar Content -->
    <div class="relative h-full max-w-full mx-auto px-2 flex items-center justify-between">

        <!-- Left Side: Start Button -->
        <div class="flex items-center gap-2">
            <!-- Start Button -->
            <button
                @click="toggleStartMenu()"
                :class="{'bg-white/20': startMenuOpen}"
                class="group flex items-center gap-2 px-4 py-2 rounded-lg hover:bg-white/20 transition-all duration-300 transform hover:scale-105 active:scale-95">

                @if($useLogoForStart && $logo)
                    <img src="{{ asset($logo) }}" alt="{{ $appName }}" class="w-8 h-8 object-contain">
                @else
                    <div class="w-8 h-8 bg-gradient-to-br from-blue-500 to-cyan-500 rounded flex items-center justify-center">
                        <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M0 0h11v11H0V0zm13 0h11v11H13V0zM0 13h11v11H0V13zm13 0h11v11H13V13z"/>
                        </svg>
                    </div>
                @endif

                <span class="text-white font-semibold text-sm hidden md:inline-block group-hover:text-blue-300 transition-colors">
                    {{ $startButtonText }}
                </span>

                <!-- Tooltip -->
                <div class="absolute {{ $position === 'top' ? 'top-full mt-2' : 'bottom-full mb-2' }} left-0 px-3 py-1.5 bg-gray-800 text-white text-xs rounded shadow-lg opacity-0 group-hover:opacity-100 transition-opacity pointer-events-none whitespace-nowrap">
                    {{ $startButtonText }}
                </div>
            </button>
        </div>

        <!-- Center: Taskbar Apps -->
        <div class="flex-1 flex items-center justify-center gap-1 px-4 overflow-x-auto taskbar-apps">
            @foreach($taskbarApps as $app)
                <a
                    href="{{ $app['url'] }}"
                    class="group relative flex items-center justify-center w-12 h-12 rounded-lg hover:bg-white/20 transition-all duration-300 transform hover:scale-110 active:scale-95"
                    title="{{ $app['label'] }}">
                    <span class="text-2xl">{{ $app['icon'] }}</span>

                    <!-- App Label Tooltip -->
                    <div class="absolute {{ $position === 'top' ? 'top-full mt-2' : 'bottom-full mb-2' }} left-1/2 transform -translate-x-1/2 px-3 py-1.5 bg-gray-800 text-white text-xs rounded shadow-lg opacity-0 group-hover:opacity-100 transition-opacity pointer-events-none whitespace-nowrap z-10">
                        {{ $app['label'] }}
                    </div>
                </a>
            @endforeach
        </div>

        <!-- Right Side: System Tray -->
        <div class="flex items-center gap-2">
            <x-windows-system-tray :icons="$systemTrayIcons" :position="$position" />
        </div>
    </div>

    <!-- Start Menu -->
    <div
        x-show="startMenuOpen"
        @click.away="startMenuOpen = false"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 transform scale-95 {{ $position === 'top' ? '-translate-y-4' : 'translate-y-4' }}"
        x-transition:enter-end="opacity-100 transform scale-100 translate-y-0"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100 transform scale-100 translate-y-0"
        x-transition:leave-end="opacity-0 transform scale-95 {{ $position === 'top' ? '-translate-y-4' : 'translate-y-4' }}"
        class="absolute left-4 {{ $position === 'top' ? 'top-full mt-2' : 'bottom-full mb-2' }} w-96 max-h-[600px] bg-gray-900/95 backdrop-blur-xl rounded-2xl shadow-2xl border border-white/10 overflow-hidden"
        style="display: none;">
        <x-windows-start-menu :items="$startMenuItems" />
    </div>
</div>

@push('scripts')
<script>
function windowsTaskbar() {
    return {
        startMenuOpen: false,

        init() {
            // Close start menu on Escape key
            document.addEventListener('keydown', (e) => {
                if (e.key === 'Escape' && this.startMenuOpen) {
                    this.startMenuOpen = false;
                }
            });
        },

        toggleStartMenu() {
            this.startMenuOpen = !this.startMenuOpen;
        }
    }
}
</script>
@endpush

@if($rgbSettings['enabled'])
@push('styles')
<style>
    @keyframes rgbBorder {
        0% { background-position: 0% 50%; }
        50% { background-position: 100% 50%; }
        100% { background-position: 0% 50%; }
    }

    .rgb-border-animation {
        background: linear-gradient(
            90deg,
            @foreach($rgbSettings['colors'] as $index => $color)
                {{ $color }}{{ $index < count($rgbSettings['colors']) - 1 ? ',' : '' }}
            @endforeach
        );
        background-size: 200% 200%;
        animation: rgbBorder {{ $rgbSettings['speed'] }}s ease infinite;
        height: 2px;
        @if($position === 'bottom')
            top: 0;
        @else
            bottom: 0;
        @endif

        @if($rgbSettings['glow'])
        filter: blur(1px);
        box-shadow: 0 0 10px currentColor, 0 0 20px currentColor;
        @endif
    }

    /* Scrollbar for taskbar apps */
    .taskbar-apps::-webkit-scrollbar {
        height: 4px;
    }

    .taskbar-apps::-webkit-scrollbar-track {
        background: transparent;
    }

    .taskbar-apps::-webkit-scrollbar-thumb {
        background: rgba(255, 255, 255, 0.3);
        border-radius: 2px;
    }

    .taskbar-apps::-webkit-scrollbar-thumb:hover {
        background: rgba(255, 255, 255, 0.5);
    }
</style>
@endpush
@endif
