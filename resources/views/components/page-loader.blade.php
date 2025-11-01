@php
    // Get loader settings from database
    $loaderEnabled = \App\Models\Setting::get('page_loader_enabled', true);
    $loaderType = \App\Models\Setting::get('page_loader_type', 'spinner'); // spinner, dots, pulse, progress
    $loaderColor = \App\Models\Setting::get('page_loader_color', '#6366f1'); // indigo-500
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

    @elseif($loaderType === 'dots')
        <!-- Dots Loader -->
        <div class="flex space-x-2">
            <div class="w-4 h-4 rounded-full animate-bounce"
                 style="background-color: {{ $loaderColor }}; animation-delay: 0s"></div>
            <div class="w-4 h-4 rounded-full animate-bounce"
                 style="background-color: {{ $loaderColor }}; animation-delay: 0.2s"></div>
            <div class="w-4 h-4 rounded-full animate-bounce"
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
        <!-- Progress Bar Loader -->
        <div class="w-64">
            <div class="h-2 bg-gray-200 dark:bg-gray-700 rounded-full overflow-hidden">
                <div id="progress-bar" class="h-full rounded-full transition-all duration-300"
                     style="background-color: {{ $loaderColor }}; width: 0%"></div>
            </div>
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
    /* Custom animation for smoother bounce */
    @keyframes bounce {
        0%, 80%, 100% {
            transform: scale(0);
        }
        40% {
            transform: scale(1);
        }
    }

    #page-loader > div > div:nth-child(1) {
        animation: bounce 1.4s infinite ease-in-out both;
    }

    #page-loader > div > div:nth-child(2) {
        animation: bounce 1.4s infinite ease-in-out both;
        animation-delay: 0.2s;
    }

    #page-loader > div > div:nth-child(3) {
        animation: bounce 1.4s infinite ease-in-out both;
        animation-delay: 0.4s;
    }
</style>
@endif
