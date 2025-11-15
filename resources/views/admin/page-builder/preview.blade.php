<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Preview - {{ $page->name }}</title>

    <!-- Tailwind CSS CDN with Dark Mode Support -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
        }
    </script>

    <!-- Alpine.js -->
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>

    <style>
        /* Custom animations */
        @keyframes float {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-20px); }
        }
        .float-animation {
            animation: float 3s ease-in-out infinite;
        }
    </style>
</head>
<body class="bg-white dark:bg-gray-900 h-full" x-data="{ darkMode: false }" :class="{ 'dark': darkMode }">
    <!-- Dark Mode Toggle (Top Right Corner) -->
    <div class="fixed top-4 right-4 z-50">
        <button @click="darkMode = !darkMode"
                class="p-3 bg-white dark:bg-gray-800 rounded-xl shadow-lg border border-gray-200 dark:border-gray-700 hover:shadow-xl transition-all">
            <svg x-show="!darkMode" class="w-5 h-5 text-gray-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/>
            </svg>
            <svg x-show="darkMode" class="w-5 h-5 text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/>
            </svg>
        </button>
    </div>

    <!-- Page Content -->
    @foreach($renderData['sections'] as $section)
        @include('page-builder.sections.' . str_replace('_', '-', $section['type']), ['section' => $section])
    @endforeach

    <!-- Empty State -->
    @if(empty($renderData['sections']))
    <div class="min-h-screen flex items-center justify-center bg-gradient-to-br from-gray-50 via-blue-50 to-indigo-50 dark:from-gray-900 dark:via-gray-800 dark:to-gray-900 p-8">
        <div class="max-w-2xl w-full">
            <!-- Main Card -->
            <div class="relative overflow-hidden bg-white dark:bg-gray-800 rounded-3xl shadow-2xl border border-gray-200 dark:border-gray-700 p-12">
                <!-- Background Pattern -->
                <div class="absolute inset-0 opacity-5">
                    <div class="absolute inset-0 bg-gradient-to-br from-blue-500 via-indigo-500 to-purple-500"></div>
                </div>

                <div class="relative text-center">
                    <!-- Animated Icon -->
                    <div class="inline-flex items-center justify-center mb-8">
                        <div class="relative">
                            <!-- Outer Ring -->
                            <div class="absolute inset-0 bg-gradient-to-br from-blue-500 via-indigo-600 to-purple-600 rounded-3xl blur-2xl opacity-30 float-animation"></div>

                            <!-- Icon Container -->
                            <div class="relative w-32 h-32 bg-gradient-to-br from-blue-600 via-indigo-600 to-purple-600 rounded-3xl flex items-center justify-center shadow-2xl">
                                <svg class="w-20 h-20 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 5a1 1 0 011-1h14a1 1 0 011 1v2a1 1 0 01-1 1H5a1 1 0 01-1-1V5zM4 13a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H5a1 1 0 01-1-1v-6zM16 13a1 1 0 011-1h2a1 1 0 011 1v6a1 1 0 01-1 1h-2a1 1 0 01-1-1v-6z"></path>
                                </svg>
                            </div>
                        </div>
                    </div>

                    <!-- Title -->
                    <h3 class="text-4xl font-bold text-gray-900 dark:text-white mb-4" data-translate>
                        ยังไม่มีเนื้อหา
                    </h3>

                    <!-- Subtitle -->
                    <p class="text-lg text-gray-600 dark:text-gray-400 mb-8 max-w-md mx-auto" data-translate>
                        เริ่มสร้างหน้าเว็บของคุณโดยการเพิ่มส่วนต่างๆ จากตัวแก้ไข
                    </p>

                    <!-- Feature List -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-10">
                        <!-- Feature 1 -->
                        <div class="p-6 bg-gradient-to-br from-blue-50 to-indigo-50 dark:from-blue-900/20 dark:to-indigo-900/20 rounded-2xl border-2 border-blue-200 dark:border-blue-800">
                            <div class="w-12 h-12 bg-gradient-to-br from-blue-500 to-indigo-600 rounded-xl flex items-center justify-center mb-4 mx-auto">
                                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zm0 0h12a2 2 0 002-2v-4a2 2 0 00-2-2h-2.343M11 7.343l1.657-1.657a2 2 0 012.828 0l2.829 2.829a2 2 0 010 2.828l-8.486 8.485M7 17h.01"/>
                                </svg>
                            </div>
                            <h4 class="text-sm font-bold text-gray-900 dark:text-gray-100 mb-2" data-translate>ปรับแต่งง่าย</h4>
                            <p class="text-xs text-gray-600 dark:text-gray-400" data-translate>ลากวางส่วนต่างๆ ได้อย่างง่ายดาย</p>
                        </div>

                        <!-- Feature 2 -->
                        <div class="p-6 bg-gradient-to-br from-purple-50 to-pink-50 dark:from-purple-900/20 dark:to-pink-900/20 rounded-2xl border-2 border-purple-200 dark:border-purple-800">
                            <div class="w-12 h-12 bg-gradient-to-br from-purple-500 to-pink-600 rounded-xl flex items-center justify-center mb-4 mx-auto">
                                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                                </svg>
                            </div>
                            <h4 class="text-sm font-bold text-gray-900 dark:text-gray-100 mb-2" data-translate>Responsive</h4>
                            <p class="text-xs text-gray-600 dark:text-gray-400" data-translate>ใช้งานได้ทุกอุปกรณ์</p>
                        </div>

                        <!-- Feature 3 -->
                        <div class="p-6 bg-gradient-to-br from-green-50 to-emerald-50 dark:from-green-900/20 dark:to-emerald-900/20 rounded-2xl border-2 border-green-200 dark:border-green-800">
                            <div class="w-12 h-12 bg-gradient-to-br from-green-500 to-emerald-600 rounded-xl flex items-center justify-center mb-4 mx-auto">
                                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                                </svg>
                            </div>
                            <h4 class="text-sm font-bold text-gray-900 dark:text-gray-100 mb-2" data-translate>รวดเร็ว</h4>
                            <p class="text-xs text-gray-600 dark:text-gray-400" data-translate>โหลดเร็ว ประสิทธิภาพสูง</p>
                        </div>
                    </div>

                    <!-- Call to Action -->
                    <div class="inline-flex items-center px-8 py-4 bg-gradient-to-r from-blue-600 via-indigo-600 to-purple-600 text-white font-bold rounded-2xl shadow-2xl hover:shadow-3xl transition-all transform hover:scale-105">
                        <svg class="w-6 h-6 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                        </svg>
                        <span data-translate>เริ่มสร้างหน้าเว็บ</span>
                    </div>

                    <!-- Help Text -->
                    <p class="mt-8 text-sm text-gray-500 dark:text-gray-500" data-translate>
                        💡 คำแนะนำ: กลับไปที่หน้าแก้ไขเพื่อเพิ่มส่วนต่างๆ
                    </p>
                </div>
            </div>

            <!-- Info Cards Below -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-6">
                <!-- Info Card 1 -->
                <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg border border-gray-200 dark:border-gray-700 p-6">
                    <div class="flex items-start">
                        <div class="w-10 h-10 bg-gradient-to-br from-orange-500 to-red-600 rounded-lg flex items-center justify-center mr-4 flex-shrink-0">
                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                            </svg>
                        </div>
                        <div>
                            <h5 class="text-sm font-bold text-gray-900 dark:text-gray-100 mb-1" data-translate>แสดงตัวอย่างแบบเรียลไทม์</h5>
                            <p class="text-xs text-gray-600 dark:text-gray-400" data-translate>เห็นการเปลี่ยนแปลงทันทีที่แก้ไข</p>
                        </div>
                    </div>
                </div>

                <!-- Info Card 2 -->
                <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg border border-gray-200 dark:border-gray-700 p-6">
                    <div class="flex items-start">
                        <div class="w-10 h-10 bg-gradient-to-br from-teal-500 to-cyan-600 rounded-lg flex items-center justify-center mr-4 flex-shrink-0">
                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                            </svg>
                        </div>
                        <div>
                            <h5 class="text-sm font-bold text-gray-900 dark:text-gray-100 mb-1" data-translate>ปลอดภัย</h5>
                            <p class="text-xs text-gray-600 dark:text-gray-400" data-translate>ข้อมูลของคุณมีความปลอดภัย</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif
</body>
</html>
