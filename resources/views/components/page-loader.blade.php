@php
    // Get loader settings from database
    $loaderEnabled = \App\Models\Setting::get('page_loader_enabled', true);
    $loaderType = \App\Models\Setting::get('page_loader_type', 'spinner');
    $loaderColor = \App\Models\Setting::get('page_loader_color', '#6366f1');
    $loaderColorSecondary = \App\Models\Setting::get('page_loader_color_secondary', '#8b5cf6');
    $loaderGif = \App\Models\Setting::get('page_loader_gif');
@endphp

@if($loaderEnabled)
<div id="page-loader" class="fixed inset-0 bg-white dark:bg-gray-900 z-[9999] flex items-center justify-center transition-opacity duration-500">
    @if($loaderType === 'spinner')
        <!-- Spinner Loader -->
        <div class="relative">
            <div class="w-20 h-20 border-4 border-gray-200 dark:border-gray-700 border-t-4 rounded-full animate-spin"
                 style="border-top-color: {{ $loaderColor }}">
            </div>
            @php
                $logo = \App\Models\Setting::get('logo');
            @endphp
            @if($logo)
                <img src="{{ asset($logo) }}" alt="Logo" class="absolute inset-0 m-auto w-10 h-10 object-contain">
            @endif
        </div>

    @elseif($loaderType === 'gradient_spinner')
        <!-- Gradient Spinner Loader -->
        <div class="relative w-20 h-20">
            <div class="absolute inset-0 rounded-full animate-spin"
                 style="background: conic-gradient(from 0deg, {{ $loaderColor }}, {{ $loaderColorSecondary }}, {{ $loaderColor }});
                        -webkit-mask: radial-gradient(farthest-side, transparent calc(100% - 4px), #000 calc(100% - 4px));
                        mask: radial-gradient(farthest-side, transparent calc(100% - 4px), #000 calc(100% - 4px));">
            </div>
        </div>

    @elseif($loaderType === 'dots')
        <!-- Dots Loader -->
        <div class="flex space-x-2">
            <div class="w-4 h-4 rounded-full dot-bounce"
                 style="background-color: {{ $loaderColor }}; animation-delay: 0s"></div>
            <div class="w-4 h-4 rounded-full dot-bounce"
                 style="background-color: {{ $loaderColor }}; animation-delay: 0.2s"></div>
            <div class="w-4 h-4 rounded-full dot-bounce"
                 style="background-color: {{ $loaderColor }}; animation-delay: 0.4s"></div>
        </div>

    @elseif($loaderType === 'pulse')
        <!-- Pulse Loader -->
        <div class="relative">
            <div class="w-20 h-20 rounded-full animate-ping absolute"
                 style="background-color: {{ $loaderColor }}; opacity: 0.3"></div>
            <div class="w-20 h-20 rounded-full animate-pulse"
                 style="background-color: {{ $loaderColor }}"></div>
        </div>

    @elseif($loaderType === 'progress')
        <!-- Progress Bar Loader with Gradient -->
        <div class="w-64">
            <div class="h-3 bg-gray-200 dark:bg-gray-700 rounded-full overflow-hidden">
                <div id="progress-bar" class="h-full rounded-full transition-all duration-300"
                     style="background: linear-gradient(90deg, {{ $loaderColor }}, {{ $loaderColorSecondary }}); width: 0%"></div>
            </div>
        </div>

    @elseif($loaderType === 'wave')
        <!-- Wave Loader -->
        <div class="flex items-end space-x-1">
            <div class="w-2 h-8 rounded-full wave-bar"
                 style="background: linear-gradient(180deg, {{ $loaderColor }}, {{ $loaderColorSecondary }}); animation-delay: 0s"></div>
            <div class="w-2 h-12 rounded-full wave-bar"
                 style="background: linear-gradient(180deg, {{ $loaderColor }}, {{ $loaderColorSecondary }}); animation-delay: 0.1s"></div>
            <div class="w-2 h-16 rounded-full wave-bar"
                 style="background: linear-gradient(180deg, {{ $loaderColor }}, {{ $loaderColorSecondary }}); animation-delay: 0.2s"></div>
            <div class="w-2 h-12 rounded-full wave-bar"
                 style="background: linear-gradient(180deg, {{ $loaderColor }}, {{ $loaderColorSecondary }}); animation-delay: 0.3s"></div>
            <div class="w-2 h-8 rounded-full wave-bar"
                 style="background: linear-gradient(180deg, {{ $loaderColor }}, {{ $loaderColorSecondary }}); animation-delay: 0.4s"></div>
        </div>

    @elseif($loaderType === 'bouncing_balls')
        <!-- Bouncing Balls Loader -->
        <div class="flex space-x-2">
            <div class="w-5 h-5 rounded-full animate-bounce"
                 style="background-color: {{ $loaderColor }}"></div>
            <div class="w-5 h-5 rounded-full animate-bounce"
                 style="background-color: {{ $loaderColor }}; animation-delay: 0.1s; opacity: 0.8"></div>
            <div class="w-5 h-5 rounded-full animate-bounce"
                 style="background-color: {{ $loaderColor }}; animation-delay: 0.2s; opacity: 0.6"></div>
        </div>

    @elseif($loaderType === 'custom_gif' && $loaderGif)
        <!-- Custom GIF Loader -->
        <div class="flex items-center justify-center">
            <img src="{{ asset($loaderGif) }}" alt="Loading..." class="max-w-xs max-h-64 object-contain">
        </div>
    @endif
</div>

<script>
    // Page Loader Script
    (function() {
        const loader = document.getElementById('page-loader');
        const progressBar = document.getElementById('progress-bar');

        // Simulate progress for progress bar loader
        if (progressBar) {
            let progress = 0;
            const interval = setInterval(() => {
                progress += Math.random() * 30;
                if (progress > 90) progress = 90;
                progressBar.style.width = progress + '%';
            }, 100);

            window.addEventListener('load', () => {
                clearInterval(interval);
                progressBar.style.width = '100%';
            });
        }

        // Hide loader when page is fully loaded
        window.addEventListener('load', () => {
            setTimeout(() => {
                if (loader) {
                    loader.style.opacity = '0';
                    setTimeout(() => {
                        loader.style.display = 'none';
                    }, 500);
                }
            }, 300);
        });

        // Fallback: Hide loader after 5 seconds
        setTimeout(() => {
            if (loader && loader.style.opacity !== '0') {
                loader.style.opacity = '0';
                setTimeout(() => {
                    loader.style.display = 'none';
                }, 500);
            }
        }, 5000);
    })();
</script>

<style>
    /* Custom animations */
    @keyframes dot-bounce {
        0%, 80%, 100% {
            transform: scale(0);
        }
        40% {
            transform: scale(1);
        }
    }

    .dot-bounce {
        animation: dot-bounce 1.4s infinite ease-in-out both;
    }

    @keyframes wave-animation {
        0%, 100% {
            transform: scaleY(0.5);
        }
        50% {
            transform: scaleY(1);
        }
    }

    .wave-bar {
        animation: wave-animation 1.2s infinite ease-in-out;
        transform-origin: bottom;
    }

    /* Ensure gradient spinner works in all browsers */
    @supports not (mask: radial-gradient(farthest-side, transparent calc(100% - 4px), #000 calc(100% - 4px))) {
        .gradient-spinner-fallback {
            border: 4px solid transparent;
            border-image: conic-gradient(from 0deg, var(--loader-color, #6366f1), var(--loader-color-secondary, #8b5cf6), var(--loader-color, #6366f1)) 1;
            border-radius: 50%;
        }
    }
</style>
@endif
