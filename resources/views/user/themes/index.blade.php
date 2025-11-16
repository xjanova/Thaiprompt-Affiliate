@extends('layouts.user-arrow-x')

@section('title', __('Theme Settings'))

@section('content')
@php
    $user = auth()->user();
    $currentTheme = $user->menu_theme_preference ?? 'millennium';
@endphp

<div class="container mx-auto px-4 py-6" x-data="{ currentTheme: '{{ $currentTheme }}' }">
    <!-- Header -->
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900 dark:text-white">{{ __('Theme Settings') }}</h1>
        <p class="text-gray-600 dark:text-gray-400 mt-2">{{ __('Customize your menu theme preference') }}</p>
    </div>

    <!-- Theme Selection Cards -->
    <div class="grid md:grid-cols-2 gap-8 mb-8">
        <!-- Millennium Theme Card -->
        <div class="theme-card group cursor-pointer"
             :class="{ 'selected': currentTheme === 'millennium' }"
             @click="currentTheme = 'millennium'">
            <div class="relative rounded-xl overflow-hidden border-4 transition-all duration-300"
                 :class="currentTheme === 'millennium' ? 'border-purple-500 shadow-2xl shadow-purple-500/30' : 'border-gray-200 dark:border-gray-700 hover:border-purple-300'">

                <!-- Preview Image/Mockup -->
                <div class="h-64 bg-gradient-to-br from-slate-900 via-purple-900 to-slate-900 flex items-center justify-center p-6">
                    <div class="w-full h-full bg-slate-800/50 backdrop-blur-xl rounded-lg border border-white/10 flex items-center justify-center">
                        <div class="text-center">
                            <i class="fas fa-windows text-8xl text-purple-400 mb-4"></i>
                            <div class="text-white text-xl font-semibold">Windows 11 Style</div>
                        </div>
                    </div>
                </div>

                <!-- Selected Badge -->
                <div x-show="currentTheme === 'millennium'"
                     x-transition
                     class="absolute top-4 right-4 bg-purple-500 text-white px-4 py-2 rounded-full text-sm font-semibold flex items-center shadow-lg">
                    <i class="fas fa-check mr-2"></i> เลือกอยู่
                </div>
            </div>

            <div class="mt-6 bg-white dark:bg-gray-800 rounded-lg p-6 border border-gray-200 dark:border-gray-700">
                <h3 class="text-2xl font-bold text-gray-800 dark:text-white flex items-center mb-3">
                    <i class="fas fa-rocket text-purple-500 mr-3"></i>
                    Millennium Theme
                </h3>
                <p class="text-gray-600 dark:text-gray-400 mb-4">
                    สไตล์ Windows 11 ที่ทันสมัย มีแถบงาน (Taskbar) แบบ Floating พร้อม Blur Effect และ RGB Lighting
                </p>

                <div class="space-y-3">
                    <div class="flex items-center text-sm text-gray-600 dark:text-gray-400">
                        <i class="fas fa-check-circle text-green-500 mr-3 text-base"></i>
                        Glass Morphism Design
                    </div>
                    <div class="flex items-center text-sm text-gray-600 dark:text-gray-400">
                        <i class="fas fa-check-circle text-green-500 mr-3 text-base"></i>
                        RGB Border Animation
                    </div>
                    <div class="flex items-center text-sm text-gray-600 dark:text-gray-400">
                        <i class="fas fa-check-circle text-green-500 mr-3 text-base"></i>
                        Customizable Start Menu
                    </div>
                    <div class="flex items-center text-sm text-gray-600 dark:text-gray-400">
                        <i class="fas fa-check-circle text-green-500 mr-3 text-base"></i>
                        Modern & Sleek Interface
                    </div>
                </div>
            </div>
        </div>

        <!-- Classic X Theme Card -->
        <div class="theme-card group cursor-pointer"
             :class="{ 'selected': currentTheme === 'classic_x' }"
             @click="currentTheme = 'classic_x'">
            <div class="relative rounded-xl overflow-hidden border-4 transition-all duration-300"
                 :class="currentTheme === 'classic_x' ? 'border-blue-500 shadow-2xl shadow-blue-500/30' : 'border-gray-200 dark:border-gray-700 hover:border-blue-300'">

                <!-- Preview Image/Mockup -->
                <div class="h-64 bg-gradient-to-br from-gray-900 via-blue-900 to-gray-900 flex items-center justify-center p-6">
                    <div class="w-full h-full bg-gray-800 rounded-lg border border-gray-700 flex overflow-hidden">
                        <div class="w-1/3 bg-gray-900 p-4 space-y-3">
                            <div class="h-3 bg-blue-500 rounded w-3/4"></div>
                            <div class="h-3 bg-gray-700 rounded"></div>
                            <div class="h-3 bg-gray-700 rounded"></div>
                            <div class="h-3 bg-gray-700 rounded w-2/3"></div>
                        </div>
                        <div class="flex-1 flex items-center justify-center">
                            <div class="text-center">
                                <i class="fas fa-border-all text-6xl text-blue-400 mb-3"></i>
                                <div class="text-white text-lg font-semibold">WordPress Style</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Selected Badge -->
                <div x-show="currentTheme === 'classic_x'"
                     x-transition
                     class="absolute top-4 right-4 bg-blue-500 text-white px-4 py-2 rounded-full text-sm font-semibold flex items-center shadow-lg">
                    <i class="fas fa-check mr-2"></i> เลือกอยู่
                </div>

                <!-- Premium Badge -->
                <div class="absolute top-4 left-4 bg-gradient-to-r from-yellow-400 to-orange-500 text-white px-3 py-1.5 rounded-full text-xs font-bold flex items-center shadow-lg">
                    <i class="fas fa-crown mr-1"></i> PREMIUM
                </div>
            </div>

            <div class="mt-6 bg-white dark:bg-gray-800 rounded-lg p-6 border border-gray-200 dark:border-gray-700">
                <h3 class="text-2xl font-bold text-gray-800 dark:text-white flex items-center mb-3">
                    <i class="fas fa-gem text-blue-500 mr-3"></i>
                    Classic X Theme
                </h3>
                <p class="text-gray-600 dark:text-gray-400 mb-4">
                    สไตล์ WordPress ที่ได้รับการยกระดับ มี Sidebar แบบ Professional พร้อม 3D Depth Effects และ Premium Styling
                </p>

                <div class="space-y-3">
                    <div class="flex items-center text-sm text-gray-600 dark:text-gray-400">
                        <i class="fas fa-check-circle text-green-500 mr-3 text-base"></i>
                        WordPress-Inspired Design
                    </div>
                    <div class="flex items-center text-sm text-gray-600 dark:text-gray-400">
                        <i class="fas fa-check-circle text-green-500 mr-3 text-base"></i>
                        3D Depth & Shadow Effects
                    </div>
                    <div class="flex items-center text-sm text-gray-600 dark:text-gray-400">
                        <i class="fas fa-check-circle text-green-500 mr-3 text-base"></i>
                        Premium Professional Look
                    </div>
                    <div class="flex items-center text-sm text-gray-600 dark:text-gray-400">
                        <i class="fas fa-check-circle text-green-500 mr-3 text-base"></i>
                        Fully Customizable Sidebar
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Apply Button -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg p-6 border border-gray-200 dark:border-gray-700">
        <div class="flex items-center justify-between">
            <div class="flex items-start">
                <i class="fas fa-info-circle text-blue-500 text-xl mr-3 mt-1"></i>
                <div>
                    <p class="text-gray-700 dark:text-gray-300 font-medium">
                        การเปลี่ยนธีมจะมีผลทันทีและจะถูกบันทึกในบัญชีของคุณ
                    </p>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                        เลือกธีมที่คุณชอบด้านบน แล้วกดปุ่ม "ใช้ธีมนี้" เพื่อบันทึก
                    </p>
                </div>
            </div>
            <button @click="applyTheme"
                    class="px-8 py-3 bg-gradient-to-r from-purple-600 to-blue-600 text-white rounded-lg hover:from-purple-700 hover:to-blue-700 transition-all shadow-lg hover:shadow-xl font-semibold flex items-center">
                <i class="fas fa-check mr-2"></i>
                ใช้ธีมนี้
            </button>
        </div>
    </div>

    <!-- Theme Info Section -->
    <div class="mt-8 bg-gradient-to-r from-blue-50 to-purple-50 dark:from-gray-800 dark:to-gray-900 border border-blue-200 dark:border-gray-700 rounded-lg p-6">
        <div class="flex items-start gap-4">
            <div class="flex-shrink-0">
                <div class="w-12 h-12 bg-blue-500 rounded-full flex items-center justify-center">
                    <i class="fas fa-palette text-white text-xl"></i>
                </div>
            </div>
            <div class="flex-1">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-3">{{ __('About Menu Themes') }}</h3>
                <ul class="space-y-2 text-sm text-gray-700 dark:text-gray-300">
                    <li class="flex items-start">
                        <i class="fas fa-angle-right text-blue-500 mr-2 mt-1"></i>
                        <span>{{ __('Menu themes control the appearance and layout of your navigation menu') }}</span>
                    </li>
                    <li class="flex items-start">
                        <i class="fas fa-angle-right text-blue-500 mr-2 mt-1"></i>
                        <span>{{ __('Choose between modern Windows 11 style or classic WordPress style') }}</span>
                    </li>
                    <li class="flex items-start">
                        <i class="fas fa-angle-right text-blue-500 mr-2 mt-1"></i>
                        <span>{{ __('Your theme preference is saved automatically and syncs across all devices') }}</span>
                    </li>
                    <li class="flex items-start">
                        <i class="fas fa-angle-right text-blue-500 mr-2 mt-1"></i>
                        <span>{{ __('You can change your theme anytime from this page') }}</span>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    function applyTheme() {
        const theme = this.currentTheme;

        // Show loading state
        const button = event.target.closest('button');
        const originalContent = button.innerHTML;
        button.disabled = true;
        button.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>กำลังบันทึก...';

        fetch('{{ route("user.theme.update") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({
                menu_theme_preference: theme
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Show success message
                button.innerHTML = '<i class="fas fa-check mr-2"></i>สำเร็จ!';
                button.classList.remove('from-purple-600', 'to-blue-600');
                button.classList.add('from-green-600', 'to-green-500');

                // Reload page after short delay
                setTimeout(() => {
                    window.location.reload();
                }, 500);
            } else {
                throw new Error(data.message || 'Failed to update theme');
            }
        })
        .catch(error => {
            console.error('Error updating theme:', error);
            button.disabled = false;
            button.innerHTML = originalContent;
            alert('เกิดข้อผิดพลาดในการเปลี่ยนธีม กรุณาลองใหม่อีกครั้ง');
        });
    }
</script>
@endpush

<style>
    .theme-card {
        transition: transform 0.3s ease;
    }

    .theme-card:hover {
        transform: translateY(-4px);
    }

    .theme-card.selected {
        animation: pulse 2s infinite;
    }

    @keyframes pulse {
        0%, 100% {
            opacity: 1;
        }
        50% {
            opacity: 0.95;
        }
    }
</style>
@endsection
