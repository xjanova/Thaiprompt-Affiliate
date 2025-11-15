<!DOCTYPE html>
<html lang="th" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', 'Dashboard') - Admin - {{ config('app.name') }}</title>

    {{-- Google Fonts --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&family=Noto+Sans+Thai:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">

    {{-- Font Awesome 6.5.1 --}}
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" integrity="sha512-DTOQO9RWCH3ppGqcWaEA1BIZOC6xxalwEsw9c2QQeAIftl+Vegovlnee1c9QX4TctnWMn13TZye+giMm8e2LwA==" crossorigin="anonymous" referrerpolicy="no-referrer" />

    {{-- Vite Assets --}}
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    {{-- Arrow X Theme Styles --}}
    <x-arrow-x.theme-styles />

    @stack('styles')
</head>
<body class="h-full font-sans overflow-hidden"
      x-data="{
          sidebarOpen: window.innerWidth >= 768,
          profileOpen: false,
          showScrollTop: false
      }"
      x-init="
          // เริ่มต้น theme store
          $store.theme.init();

          // ตรวจสอบ scroll position สำหรับปุ่มกลับด้านบน
          $nextTick(() => {
              const mainContent = document.querySelector('main.overflow-y-auto');
              if (mainContent) {
                  mainContent.addEventListener('scroll', () => {
                      showScrollTop = mainContent.scrollTop > 300;
                  });
              }
          });

          // ปรับ sidebar ตามขนาดหน้าจอ (มือถือซ่อน, desktop แสดง)
          window.addEventListener('resize', () => {
              if (window.innerWidth >= 768) {
                  sidebarOpen = true;
              } else {
                  sidebarOpen = false;
              }
          });
      ">

    {{-- Background Gradient - โหมดสว่าง=สีสัน, โหมดมืด=มืด+ทึบ --}}
    <div class="fixed inset-0 -z-10 transition-colors duration-500"
         :class="$store.theme.isDark
             ? 'bg-gradient-to-br from-gray-900 via-gray-800 to-gray-900'
             : 'bg-gradient-to-br from-purple-500 via-pink-500 to-orange-500'">
    </div>

    {{-- Dark Mode Overlay - เพิ่มความทึบ 95% เมื่อโหมดมืด --}}
    <div x-show="$store.theme.isDark"
         x-transition
         class="fixed inset-0 bg-black/40 -z-10"></div>

    {{-- Animated Background Circles วงกลมเคลื่อนไหวพื้นหลัง --}}
    <div class="fixed inset-0 -z-10 overflow-hidden pointer-events-none">
        <div class="absolute top-1/4 left-1/4 w-96 h-96 rounded-full blur-3xl animate-pulse transition-all duration-500"
             :class="$store.theme.isDark
                 ? 'bg-gradient-to-br from-blue-600 to-cyan-700 opacity-15'
                 : 'bg-gradient-to-br from-cyan-400 to-blue-600 opacity-30'">
        </div>
        <div class="absolute bottom-1/4 right-1/4 w-96 h-96 rounded-full blur-3xl animate-pulse transition-all duration-500"
             style="animation-delay: 1s;"
             :class="$store.theme.isDark
                 ? 'bg-gradient-to-br from-purple-700 to-pink-800 opacity-15'
                 : 'bg-gradient-to-br from-pink-400 to-purple-600 opacity-30'">
        </div>
        <div class="absolute top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2 w-96 h-96 rounded-full blur-3xl animate-pulse transition-all duration-500"
             style="animation-delay: 2s;"
             :class="$store.theme.isDark
                 ? 'bg-gradient-to-br from-orange-800 to-yellow-900 opacity-15'
                 : 'bg-gradient-to-br from-yellow-400 to-orange-600 opacity-30'">
        </div>
    </div>

    <div class="flex h-full">
        {{-- Sidebar Component --}}
        <x-arrow-x.sidebar-v3 />

        {{-- Main Content Area --}}
        <div class="flex-1 flex flex-col overflow-hidden">
            {{-- Top Navbar Component --}}
            <x-arrow-x.navbar-v3 />

            {{-- Page Content --}}
            <main class="flex-1 overflow-y-auto p-4 md:p-6">
                @yield('content')
            </main>
        </div>
    </div>

    {{-- Toast Notifications --}}
    <div class="fixed bottom-4 right-4 z-50 space-y-2"
         x-data="{ notifications: [] }"
         @notify.window="notifications.push($event.detail); setTimeout(() => notifications.shift(), 3000)">
        <template x-for="(notification, index) in notifications" :key="index">
            <div x-show="true"
                 x-transition:enter="transition ease-out duration-300"
                 x-transition:enter-start="translate-x-full"
                 x-transition:enter-end="translate-x-0"
                 x-transition:leave="transition ease-in duration-200"
                 x-transition:leave-start="translate-x-0"
                 x-transition:leave-end="translate-x-full"
                 class="glass-fusion rounded-xl p-4 shadow-2xl border border-white/30 min-w-[300px]"
                 :class="{
                     'border-l-4 border-l-green-500': notification.type === 'success',
                     'border-l-4 border-l-red-500': notification.type === 'error',
                     'border-l-4 border-l-yellow-500': notification.type === 'warning',
                     'border-l-4 border-l-blue-500': notification.type === 'info'
                 }">
                <div class="flex items-center gap-3">
                    <div class="flex-shrink-0">
                        <template x-if="notification.type === 'success'">
                            <i class="fas fa-check-circle text-2xl text-green-400"></i>
                        </template>
                        <template x-if="notification.type === 'error'">
                            <i class="fas fa-exclamation-circle text-2xl text-red-400"></i>
                        </template>
                        <template x-if="notification.type === 'warning'">
                            <i class="fas fa-exclamation-triangle text-2xl text-yellow-400"></i>
                        </template>
                        <template x-if="notification.type === 'info'">
                            <i class="fas fa-info-circle text-2xl text-blue-400"></i>
                        </template>
                    </div>
                    <p class="flex-1 text-white font-medium" x-text="notification.message"></p>
                </div>
            </div>
        </template>
    </div>

    {{-- Theme Customizer - ปรับแต่งธีมแบบละเอียด --}}
    <x-arrow-x.theme-customizer />

    {{-- Scroll to Top Button - ปุ่มกลับขึ้นด้านบน (มุมขวาล่าง) --}}
    <button @click="document.querySelector('main.overflow-y-auto').scrollTo({ top: 0, behavior: 'smooth' })"
            x-show="showScrollTop"
            x-transition:enter="transition ease-out duration-300 transform"
            x-transition:enter-start="translate-y-16 opacity-0 scale-75"
            x-transition:enter-end="translate-y-0 opacity-100 scale-100"
            x-transition:leave="transition ease-in duration-200 transform"
            x-transition:leave-start="translate-y-0 opacity-100 scale-100"
            x-transition:leave-end="translate-y-16 opacity-0 scale-75"
            type="button"
            class="fixed bottom-6 right-6 z-50 p-4 rounded-2xl glass-fusion shadow-2xl border border-white/30 hover:scale-110 transition-all group"
            title="กลับขึ้นด้านบน">
        <i class="fas fa-arrow-up text-white text-xl drop-shadow group-hover:scale-110 group-hover:-translate-y-1 transition-transform"></i>
    </button>

    @stack('scripts')
</body>
</html>
