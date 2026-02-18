<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>เชิญดูดวงออนไลน์ | Thaiprompt</title>
    <meta name="description" content="มาดูดวงออนไลน์ผ่าน LINE ได้แล้ววันนี้ คำทำนายแม่นยำโดย AI">

    @php
        $favicon = \App\Models\Setting::get('favicon');
    @endphp
    @if($favicon)
        <link rel="icon" type="image/x-icon" href="{{ asset($favicon) }}">
    @endif

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    <script>
        tailwind.config = {
            darkMode: 'class',
        }
        // ตรวจ dark mode
        if (localStorage.getItem('theme') === 'dark' || (!localStorage.getItem('theme') && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            document.documentElement.classList.add('dark');
        }
    </script>
</head>
<body class="min-h-screen bg-gradient-to-br from-purple-50 via-white to-indigo-50 dark:from-gray-900 dark:via-gray-800 dark:to-purple-900">
    <div class="min-h-screen flex items-center justify-center px-4 py-12">
        <div class="w-full max-w-md">

            @if($error)
                {{-- Error State --}}
                <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-8 text-center">
                    <div class="text-5xl mb-4">😔</div>
                    <h1 class="text-xl font-bold text-gray-900 dark:text-white mb-2">
                        ไม่พบลิงก์
                    </h1>
                    <p class="text-gray-600 dark:text-gray-400 text-sm">
                        {{ $error }}
                    </p>
                </div>
            @else
                {{-- Main Card --}}
                <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl overflow-hidden">
                    {{-- Header --}}
                    <div class="bg-gradient-to-r from-purple-600 to-indigo-600 px-6 py-8 text-center">
                        <div class="text-5xl mb-3">🔮</div>
                        <h1 class="text-xl font-bold text-white mb-1">
                            ดูดวงออนไลน์ผ่าน LINE
                        </h1>
                        <p class="text-purple-200 text-sm">
                            คำทำนายแม่นยำ โดย AI โหราศาสตร์
                        </p>
                    </div>

                    {{-- Body --}}
                    <div class="p-6">
                        {{-- ข้อความเชิญ --}}
                        <div class="text-center mb-6">
                            <p class="text-gray-700 dark:text-gray-300 text-sm">
                                <span class="font-semibold text-purple-600 dark:text-purple-400">คุณ{{ $referrerName }}</span>
                                เชิญคุณมาดูดวงออนไลน์
                            </p>
                        </div>

                        {{-- ขั้นตอน --}}
                        <div class="space-y-3 mb-6">
                            <div class="flex items-start gap-3">
                                <div class="w-7 h-7 rounded-full bg-purple-100 dark:bg-purple-900/50 flex items-center justify-center flex-shrink-0 mt-0.5">
                                    <span class="text-sm font-bold text-purple-600 dark:text-purple-400">1</span>
                                </div>
                                <div>
                                    <p class="text-sm font-medium text-gray-900 dark:text-white">เพิ่มเพื่อน LINE</p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">กดปุ่มด้านล่างเพื่อแอดเพื่อน</p>
                                </div>
                            </div>
                            <div class="flex items-start gap-3">
                                <div class="w-7 h-7 rounded-full bg-purple-100 dark:bg-purple-900/50 flex items-center justify-center flex-shrink-0 mt-0.5">
                                    <span class="text-sm font-bold text-purple-600 dark:text-purple-400">2</span>
                                </div>
                                <div>
                                    <p class="text-sm font-medium text-gray-900 dark:text-white">พิมพ์ "ดูดวง"</p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">พิมพ์คำถามที่อยากรู้</p>
                                </div>
                            </div>
                            <div class="flex items-start gap-3">
                                <div class="w-7 h-7 rounded-full bg-purple-100 dark:bg-purple-900/50 flex items-center justify-center flex-shrink-0 mt-0.5">
                                    <span class="text-sm font-bold text-purple-600 dark:text-purple-400">3</span>
                                </div>
                                <div>
                                    <p class="text-sm font-medium text-gray-900 dark:text-white">รับคำทำนาย</p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">ดูดวงฟรี! หรืออัปเกรดดูละเอียด</p>
                                </div>
                            </div>
                        </div>

                        @if($lineAddFriendUrl)
                            {{-- ปุ่มเพิ่มเพื่อน LINE --}}
                            <a href="{{ $lineAddFriendUrl }}"
                               class="block w-full py-4 px-6 bg-[#06C755] hover:bg-[#05B14C] text-white text-center font-bold rounded-xl shadow-lg hover:shadow-xl transition transform hover:-translate-y-0.5 text-lg">
                                <span class="flex items-center justify-center gap-2">
                                    <svg class="w-6 h-6" viewBox="0 0 24 24" fill="currentColor">
                                        <path d="M19.365 9.863c.349 0 .63.285.63.631 0 .345-.281.63-.63.63H17.61v1.125h1.755c.349 0 .63.283.63.63 0 .344-.281.629-.63.629h-2.386c-.345 0-.627-.285-.627-.629V8.108c0-.345.282-.63.627-.63h2.386c.349 0 .63.285.63.63 0 .349-.281.63-.63.63H17.61v1.125h1.755zm-3.855 3.016c0 .27-.174.51-.432.596-.064.021-.133.031-.199.031-.211 0-.391-.09-.51-.25l-2.443-3.317v2.94c0 .344-.279.629-.631.629-.346 0-.626-.285-.626-.629V8.108c0-.27.173-.51.43-.595.06-.023.136-.033.194-.033.195 0 .375.104.495.254l2.462 3.33V8.108c0-.345.282-.63.63-.63.345 0 .63.285.63.63v4.771zm-5.741 0c0 .344-.282.629-.631.629-.345 0-.627-.285-.627-.629V8.108c0-.345.282-.63.627-.63.349 0 .631.285.631.63v4.771zm-2.466.629H4.917c-.345 0-.63-.285-.63-.629V8.108c0-.345.285-.63.63-.63.348 0 .63.285.63.63v4.141h1.756c.348 0 .629.283.629.63 0 .344-.281.629-.629.629M24 10.314C24 4.943 18.615.572 12 .572S0 4.943 0 10.314c0 4.811 4.27 8.842 10.035 9.608.391.082.923.258 1.058.59.12.301.079.766.038 1.08l-.164 1.02c-.045.301-.24 1.186 1.049.645 1.291-.539 6.916-4.078 9.436-6.975C23.176 14.393 24 12.458 24 10.314"/>
                                    </svg>
                                    เพิ่มเพื่อน LINE
                                </span>
                            </a>

                            <p class="text-center text-xs text-gray-400 dark:text-gray-500 mt-3">
                                ฟรี! ไม่มีค่าใช้จ่ายในการเพิ่มเพื่อน
                            </p>
                        @else
                            <div class="bg-yellow-50 dark:bg-yellow-900/20 rounded-lg p-4 text-center">
                                <p class="text-sm text-yellow-700 dark:text-yellow-300">
                                    ⚠️ ระบบยังไม่ได้ตั้งค่า LINE OA
                                </p>
                            </div>
                        @endif
                    </div>
                </div>

                {{-- Footer --}}
                <div class="text-center mt-6">
                    <p class="text-xs text-gray-400 dark:text-gray-500">
                        Powered by Thaiprompt
                    </p>
                </div>
            @endif
        </div>
    </div>
</body>
</html>
