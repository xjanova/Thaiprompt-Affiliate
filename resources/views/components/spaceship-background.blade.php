@php
    $enabled = \App\Models\WindowsUiSetting::get('windows_spaceship_theme', true);
    $showStars = \App\Models\WindowsUiSetting::get('windows_spaceship_stars', true);
    $bgImage = \App\Models\WindowsUiSetting::get('windows_spaceship_bg_image', '');
@endphp

@if($enabled)
<div class="fixed inset-0 -z-10 overflow-hidden pointer-events-none">
    <!-- Space Gradient Background -->
    <div class="absolute inset-0 bg-gradient-to-b from-gray-900 via-blue-900 to-purple-900 dark:from-black dark:via-gray-900 dark:to-purple-950"></div>

    <!-- Custom Background Image (if provided) -->
    @if($bgImage)
        <div class="absolute inset-0 opacity-30">
            <img src="{{ $bgImage }}" alt="Space Background" class="w-full h-full object-cover">
        </div>
    @endif

    <!-- Animated Stars -->
    @if($showStars)
        <div class="stars-container absolute inset-0">
            <!-- Small Stars -->
            <div class="stars stars-small"></div>
            <!-- Medium Stars -->
            <div class="stars stars-medium"></div>
            <!-- Large Stars -->
            <div class="stars stars-large"></div>
        </div>

        <!-- Shooting Stars -->
        <div class="shooting-stars absolute inset-0">
            <div class="shooting-star"></div>
            <div class="shooting-star"></div>
            <div class="shooting-star"></div>
        </div>
    @endif

    <!-- Nebula Effect -->
    <div class="absolute inset-0 opacity-20">
        <div class="absolute top-1/4 left-1/4 w-96 h-96 bg-blue-500/30 rounded-full blur-3xl animate-pulse"></div>
        <div class="absolute bottom-1/4 right-1/4 w-96 h-96 bg-purple-500/30 rounded-full blur-3xl animate-pulse" style="animation-delay: 1s;"></div>
        <div class="absolute top-1/2 right-1/3 w-64 h-64 bg-cyan-500/20 rounded-full blur-3xl animate-pulse" style="animation-delay: 2s;"></div>
    </div>
</div>

@push('styles')
<style>
    /* Stars Animation */
    .stars {
        position: absolute;
        width: 100%;
        height: 100%;
        background: transparent;
    }

    .stars-small {
        background-image:
            radial-gradient(1px 1px at 20px 30px, white, rgba(0,0,0,0)),
            radial-gradient(1px 1px at 60px 70px, white, rgba(0,0,0,0)),
            radial-gradient(1px 1px at 50px 50px, white, rgba(0,0,0,0)),
            radial-gradient(1px 1px at 80px 10px, white, rgba(0,0,0,0)),
            radial-gradient(1px 1px at 130px 80px, white, rgba(0,0,0,0)),
            radial-gradient(1px 1px at 170px 30px, white, rgba(0,0,0,0));
        background-repeat: repeat;
        background-size: 200px 150px;
        animation: twinkle 3s ease-in-out infinite;
    }

    .stars-medium {
        background-image:
            radial-gradient(2px 2px at 40px 60px, white, rgba(0,0,0,0)),
            radial-gradient(2px 2px at 110px 90px, white, rgba(0,0,0,0)),
            radial-gradient(2px 2px at 150px 30px, white, rgba(0,0,0,0)),
            radial-gradient(2px 2px at 70px 120px, white, rgba(0,0,0,0));
        background-repeat: repeat;
        background-size: 250px 200px;
        animation: twinkle 4s ease-in-out infinite reverse;
    }

    .stars-large {
        background-image:
            radial-gradient(3px 3px at 90px 40px, white, rgba(0,0,0,0)),
            radial-gradient(3px 3px at 180px 120px, white, rgba(0,0,0,0)),
            radial-gradient(3px 3px at 140px 80px, white, rgba(0,0,0,0));
        background-repeat: repeat;
        background-size: 300px 250px;
        animation: twinkle 5s ease-in-out infinite;
    }

    @keyframes twinkle {
        0%, 100% { opacity: 0.3; }
        50% { opacity: 1; }
    }

    /* Shooting Stars */
    .shooting-star {
        position: absolute;
        top: 50%;
        left: 50%;
        width: 2px;
        height: 2px;
        background: white;
        border-radius: 50%;
        box-shadow: 0 0 6px 2px rgba(255, 255, 255, 0.8);
        animation: shootingStar 3s linear infinite;
    }

    .shooting-star:nth-child(1) {
        top: 20%;
        left: 80%;
        animation-delay: 0s;
    }

    .shooting-star:nth-child(2) {
        top: 60%;
        left: 90%;
        animation-delay: 5s;
    }

    .shooting-star:nth-child(3) {
        top: 40%;
        left: 70%;
        animation-delay: 10s;
    }

    @keyframes shootingStar {
        0% {
            transform: translate(0, 0) rotate(-45deg);
            opacity: 1;
        }
        70% {
            opacity: 1;
        }
        100% {
            transform: translate(-300px, 300px) rotate(-45deg);
            opacity: 0;
        }
    }

    /* Nebula pulse effect */
    @keyframes pulse {
        0%, 100% {
            opacity: 0.15;
            transform: scale(1);
        }
        50% {
            opacity: 0.25;
            transform: scale(1.1);
        }
    }

    /* Ensure content is above background */
    .min-h-screen > *:not(.fixed) {
        position: relative;
        z-index: 1;
    }
</style>
@endpush
@endif
