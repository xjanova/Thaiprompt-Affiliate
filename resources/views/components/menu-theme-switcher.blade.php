@php
    $user = auth()->user();
    $currentTheme = $user->menu_theme_preference ?? 'millennium';
@endphp

<div class="menu-theme-switcher" x-data="themeSwitcher()" x-init="init()">
    <button @click="open = true" class="theme-switcher-trigger" title="เปลี่ยนธีมเมนู">
        <i class="fas fa-palette"></i>
        <span class="hidden md:inline ml-2">ธีมเมนู</span>
    </button>

    <!-- Theme Switcher Modal -->
    <div x-show="open"
         x-cloak
         @click.away="open = false"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 z-[9999] flex items-center justify-center bg-black bg-opacity-50 backdrop-blur-sm">

        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-2xl max-w-4xl w-full mx-4 overflow-hidden"
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0 transform scale-95"
             x-transition:enter-end="opacity-100 transform scale-100"
             x-transition:leave="transition ease-in duration-200"
             x-transition:leave-start="opacity-100 transform scale-100"
             x-transition:leave-end="opacity-0 transform scale-95">

            <!-- Header -->
            <div class="bg-gradient-to-r from-purple-600 to-blue-600 p-6 text-white">
                <div class="flex items-center justify-between">
                    <div>
                        <h2 class="text-2xl font-bold flex items-center">
                            <i class="fas fa-palette mr-3"></i>
                            เลือกธีมเมนู
                        </h2>
                        <p class="text-purple-100 mt-1">เลือกสไตล์เมนูที่คุณชอบ</p>
                    </div>
                    <button @click="open = false" class="text-white hover:text-purple-200 transition-colors">
                        <i class="fas fa-times text-2xl"></i>
                    </button>
                </div>
            </div>

            <!-- Content -->
            <div class="p-6">
                <div class="grid md:grid-cols-2 gap-6">
                    <!-- Millennium Theme Card -->
                    <div class="theme-card group cursor-pointer"
                         :class="{ 'selected': currentTheme === 'millennium' }"
                         @click="selectTheme('millennium')">
                        <div class="relative rounded-xl overflow-hidden border-4 transition-all duration-300"
                             :class="currentTheme === 'millennium' ? 'border-purple-500 shadow-xl shadow-purple-500/50' : 'border-gray-200 dark:border-gray-700 hover:border-purple-300'">

                            <!-- Preview Image/Mockup -->
                            <div class="h-48 bg-gradient-to-br from-slate-900 via-purple-900 to-slate-900 flex items-center justify-center p-4">
                                <div class="w-full h-full bg-slate-800/50 backdrop-blur-xl rounded-lg border border-white/10 flex items-center justify-center">
                                    <div class="text-center">
                                        <i class="fas fa-windows text-6xl text-purple-400 mb-3"></i>
                                        <div class="text-white font-semibold">Windows 11 Style</div>
                                    </div>
                                </div>
                            </div>

                            <!-- Selected Badge -->
                            <div x-show="currentTheme === 'millennium'"
                                 class="absolute top-4 right-4 bg-purple-500 text-white px-3 py-1 rounded-full text-sm font-semibold flex items-center">
                                <i class="fas fa-check mr-2"></i> เลือกอยู่
                            </div>
                        </div>

                        <div class="mt-4">
                            <h3 class="text-xl font-bold text-gray-800 dark:text-white flex items-center">
                                <i class="fas fa-rocket text-purple-500 mr-2"></i>
                                Millennium Theme
                            </h3>
                            <p class="text-gray-600 dark:text-gray-400 mt-2">
                                สไตล์ Windows 11 ที่ทันสมัย มีแถบงาน (Taskbar) แบบ Floating พร้อม Blur Effect และ RGB Lighting
                            </p>

                            <div class="mt-4 space-y-2">
                                <div class="flex items-center text-sm text-gray-600 dark:text-gray-400">
                                    <i class="fas fa-check-circle text-green-500 mr-2"></i>
                                    Glass Morphism Design
                                </div>
                                <div class="flex items-center text-sm text-gray-600 dark:text-gray-400">
                                    <i class="fas fa-check-circle text-green-500 mr-2"></i>
                                    RGB Border Animation
                                </div>
                                <div class="flex items-center text-sm text-gray-600 dark:text-gray-400">
                                    <i class="fas fa-check-circle text-green-500 mr-2"></i>
                                    Customizable Start Menu
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Classic X Theme Card -->
                    <div class="theme-card group cursor-pointer"
                         :class="{ 'selected': currentTheme === 'classic_x' }"
                         @click="selectTheme('classic_x')">
                        <div class="relative rounded-xl overflow-hidden border-4 transition-all duration-300"
                             :class="currentTheme === 'classic_x' ? 'border-blue-500 shadow-xl shadow-blue-500/50' : 'border-gray-200 dark:border-gray-700 hover:border-blue-300'">

                            <!-- Preview Image/Mockup -->
                            <div class="h-48 bg-gradient-to-br from-gray-900 via-blue-900 to-gray-900 flex items-center justify-center p-4">
                                <div class="w-full h-full bg-gray-800 rounded-lg border border-gray-700 flex">
                                    <div class="w-1/3 bg-gray-900 p-3 space-y-2">
                                        <div class="h-2 bg-blue-500 rounded w-3/4"></div>
                                        <div class="h-2 bg-gray-700 rounded"></div>
                                        <div class="h-2 bg-gray-700 rounded"></div>
                                        <div class="h-2 bg-gray-700 rounded w-2/3"></div>
                                    </div>
                                    <div class="flex-1 flex items-center justify-center">
                                        <div class="text-center">
                                            <i class="fas fa-border-all text-5xl text-blue-400 mb-2"></i>
                                            <div class="text-white text-sm font-semibold">WordPress Style</div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Selected Badge -->
                            <div x-show="currentTheme === 'classic_x'"
                                 class="absolute top-4 right-4 bg-blue-500 text-white px-3 py-1 rounded-full text-sm font-semibold flex items-center">
                                <i class="fas fa-check mr-2"></i> เลือกอยู่
                            </div>

                            <!-- Premium Badge -->
                            <div class="absolute top-4 left-4 bg-gradient-to-r from-yellow-400 to-orange-500 text-white px-3 py-1 rounded-full text-xs font-bold flex items-center">
                                <i class="fas fa-crown mr-1"></i> PREMIUM
                            </div>
                        </div>

                        <div class="mt-4">
                            <h3 class="text-xl font-bold text-gray-800 dark:text-white flex items-center">
                                <i class="fas fa-gem text-blue-500 mr-2"></i>
                                Classic X Theme
                            </h3>
                            <p class="text-gray-600 dark:text-gray-400 mt-2">
                                สไตล์ WordPress ที่ได้รับการยกระดับ มี Sidebar แบบ Professional พร้อม 3D Depth Effects และ Premium Styling
                            </p>

                            <div class="mt-4 space-y-2">
                                <div class="flex items-center text-sm text-gray-600 dark:text-gray-400">
                                    <i class="fas fa-check-circle text-green-500 mr-2"></i>
                                    WordPress-Inspired Design
                                </div>
                                <div class="flex items-center text-sm text-gray-600 dark:text-gray-400">
                                    <i class="fas fa-check-circle text-green-500 mr-2"></i>
                                    3D Depth & Shadow Effects
                                </div>
                                <div class="flex items-center text-sm text-gray-600 dark:text-gray-400">
                                    <i class="fas fa-check-circle text-green-500 mr-2"></i>
                                    Premium Professional Look
                                </div>
                                <div class="flex items-center text-sm text-gray-600 dark:text-gray-400">
                                    <i class="fas fa-check-circle text-green-500 mr-2"></i>
                                    Fully Customizable Sidebar
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Apply Button -->
                <div class="mt-8 flex items-center justify-between">
                    <div class="text-sm text-gray-500 dark:text-gray-400">
                        <i class="fas fa-info-circle mr-2"></i>
                        การเปลี่ยนธีมจะมีผลทันทีและจะถูกบันทึกในบัญชีของคุณ
                    </div>
                    <div class="flex space-x-3">
                        <button @click="open = false"
                                class="px-6 py-2 bg-gray-200 dark:bg-gray-700 text-gray-800 dark:text-white rounded-lg hover:bg-gray-300 dark:hover:bg-gray-600 transition-colors">
                            ยกเลิก
                        </button>
                        <button @click="applyTheme"
                                class="px-6 py-2 bg-gradient-to-r from-purple-600 to-blue-600 text-white rounded-lg hover:from-purple-700 hover:to-blue-700 transition-all shadow-lg hover:shadow-xl">
                            <i class="fas fa-check mr-2"></i>
                            ใช้ธีมนี้
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    /* Hide elements with x-cloak until Alpine.js loads (prevent FOUC) */
    [x-cloak] {
        display: none !important;
    }

    .theme-switcher-trigger {
        display: flex;
        align-items: center;
        padding: 0.5rem 1rem;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        border: none;
        border-radius: 0.5rem;
        cursor: pointer;
        font-weight: 500;
        transition: all 0.3s;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    }

    .theme-switcher-trigger:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 12px rgba(0, 0, 0, 0.15);
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

<script>
    function themeSwitcher() {
        return {
            open: false,
            currentTheme: '{{ $currentTheme }}',

            init() {
                // Initialize theme
                console.log('Theme Switcher initialized with theme:', this.currentTheme);
            },

            selectTheme(theme) {
                this.currentTheme = theme;
                console.log('Selected theme:', theme);
            },

            async applyTheme() {
                console.log('Applying theme:', this.currentTheme);
                this.open = false; // Close modal first

                // Show loading indicator with smooth fade-in (prevent FOUC)
                const loadingDiv = document.createElement('div');
                loadingDiv.id = 'theme-loading';
                loadingDiv.style.opacity = '0';
                loadingDiv.style.transition = 'opacity 0.3s ease-in-out';
                loadingDiv.innerHTML = `
                    <div style="position: fixed; inset: 0; background: rgba(0,0,0,0.85); backdrop-filter: blur(10px); display: flex; align-items: center; justify-content: center; z-index: 99999;">
                        <div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 40px 50px; border-radius: 20px; text-align: center; box-shadow: 0 25px 50px rgba(0,0,0,0.5);">
                            <div style="position: relative; width: 80px; height: 80px; margin: 0 auto 20px;">
                                <div style="position: absolute; width: 100%; height: 100%; border: 4px solid rgba(255,255,255,0.2); border-radius: 50%;"></div>
                                <div style="position: absolute; width: 100%; height: 100%; border: 4px solid transparent; border-top-color: white; border-radius: 50%; animation: spin 1s linear infinite;"></div>
                            </div>
                            <p style="font-size: 20px; font-weight: 700; color: white; margin: 0; text-shadow: 0 2px 4px rgba(0,0,0,0.2);">กำลังเปลี่ยนธีม...</p>
                            <p style="font-size: 14px; color: rgba(255,255,255,0.8); margin-top: 8px;">โปรดรอสักครู่</p>
                        </div>
                    </div>
                    <style>
                        @keyframes spin {
                            to { transform: rotate(360deg); }
                        }
                    </style>
                `;
                document.body.appendChild(loadingDiv);

                // Fade in smoothly after a tiny delay (prevent flash)
                requestAnimationFrame(() => {
                    setTimeout(() => {
                        loadingDiv.style.opacity = '1';
                    }, 10);
                });

                try {
                    const response = await fetch('{{ route("user.theme.update") }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify({
                            menu_theme_preference: this.currentTheme
                        })
                    });

                    const data = await response.json();
                    console.log('Server response:', data);

                    if (response.ok && data.success) {
                        console.log('Theme updated successfully, reloading...', data);
                        // Smooth transition: Fade out before reload
                        setTimeout(() => {
                            loadingDiv.style.opacity = '0';
                            setTimeout(() => {
                                window.location.reload();
                            }, 300);
                        }, 500);
                    } else {
                        console.error('Failed to update theme:', data);
                        // Fade out gracefully before removing
                        loadingDiv.style.opacity = '0';
                        setTimeout(() => {
                            loadingDiv.remove();
                        }, 300);
                        alert('เกิดข้อผิดพลาดในการเปลี่ยนธีม: ' + (data.message || 'Unknown error'));
                    }
                } catch (error) {
                    console.error('Error updating theme:', error);
                    // Fade out gracefully before removing
                    loadingDiv.style.opacity = '0';
                    setTimeout(() => {
                        loadingDiv.remove();
                    }, 300);
                    alert('เกิดข้อผิดพลาดในการเชื่อมต่อ: ' + error.message);
                }
            }
        }
    }
</script>
