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
                        ไม่สามารถใช้ลิงก์นี้ได้
                    </h1>
                    <p class="text-gray-600 dark:text-gray-400 text-sm">
                        {{ $error }}
                    </p>
                </div>
            @else
                {{-- Main Card --}}
                <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl overflow-hidden"
                     x-data="inviteLanding()">

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
                        <div class="text-center mb-5">
                            <p class="text-gray-700 dark:text-gray-300 text-sm">
                                <span class="font-semibold text-purple-600 dark:text-purple-400">คุณ{{ $referrerName }}</span>
                                เชิญคุณมาดูดวงออนไลน์
                            </p>
                        </div>

                        {{-- Countdown หมดอายุ --}}
                        @if($expiresAt)
                        <div class="bg-amber-50 dark:bg-amber-900/20 rounded-lg px-4 py-2 mb-5 text-center border border-amber-200 dark:border-amber-800">
                            <p class="text-xs text-amber-700 dark:text-amber-300">
                                <span class="font-medium">ลิงก์หมดอายุใน</span>
                                <span class="font-bold" x-text="countdown">--:--:--</span>
                            </p>
                        </div>
                        @endif

                        @if($lineDeepLink)
                            {{-- ===== ปุ่มหลัก: LINE Deep Link ===== --}}
                            <a href="{{ $lineDeepLink }}"
                               class="block w-full py-4 px-6 bg-[#06C755] hover:bg-[#05B14C] text-white text-center font-bold rounded-xl shadow-lg hover:shadow-xl transition transform hover:-translate-y-0.5 text-lg mb-4">
                                <span class="flex items-center justify-center gap-2">
                                    <svg class="w-6 h-6" viewBox="0 0 24 24" fill="currentColor">
                                        <path d="M19.365 9.863c.349 0 .63.285.63.631 0 .345-.281.63-.63.63H17.61v1.125h1.755c.349 0 .63.283.63.63 0 .344-.281.629-.63.629h-2.386c-.345 0-.627-.285-.627-.629V8.108c0-.345.282-.63.627-.63h2.386c.349 0 .63.285.63.63 0 .349-.281.63-.63.63H17.61v1.125h1.755zm-3.855 3.016c0 .27-.174.51-.432.596-.064.021-.133.031-.199.031-.211 0-.391-.09-.51-.25l-2.443-3.317v2.94c0 .344-.279.629-.631.629-.346 0-.626-.285-.626-.629V8.108c0-.27.173-.51.43-.595.06-.023.136-.033.194-.033.195 0 .375.104.495.254l2.462 3.33V8.108c0-.345.282-.63.63-.63.345 0 .63.285.63.63v4.771zm-5.741 0c0 .344-.282.629-.631.629-.345 0-.627-.285-.627-.629V8.108c0-.345.282-.63.627-.63.349 0 .631.285.631.63v4.771zm-2.466.629H4.917c-.345 0-.63-.285-.63-.629V8.108c0-.345.285-.63.63-.63.348 0 .63.285.63.63v4.141h1.756c.348 0 .629.283.629.63 0 .344-.281.629-.629.629M24 10.314C24 4.943 18.615.572 12 .572S0 4.943 0 10.314c0 4.811 4.27 8.842 10.035 9.608.391.082.923.258 1.058.59.12.301.079.766.038 1.08l-.164 1.02c-.045.301-.24 1.186 1.049.645 1.291-.539 6.916-4.078 9.436-6.975C23.176 14.393 24 12.458 24 10.314"/>
                                    </svg>
                                    เปิด LINE แชทดูดวงเลย
                                </span>
                            </a>

                            {{-- ขั้นตอน (สำหรับ deep link) --}}
                            <div class="space-y-2 mb-5">
                                <div class="flex items-center gap-3">
                                    <div class="w-6 h-6 rounded-full bg-green-100 dark:bg-green-900/50 flex items-center justify-center flex-shrink-0">
                                        <span class="text-xs font-bold text-green-600 dark:text-green-400">1</span>
                                    </div>
                                    <p class="text-sm text-gray-700 dark:text-gray-300">กดปุ่มสีเขียวด้านบน</p>
                                </div>
                                <div class="flex items-center gap-3">
                                    <div class="w-6 h-6 rounded-full bg-green-100 dark:bg-green-900/50 flex items-center justify-center flex-shrink-0">
                                        <span class="text-xs font-bold text-green-600 dark:text-green-400">2</span>
                                    </div>
                                    <p class="text-sm text-gray-700 dark:text-gray-300">กด <span class="font-semibold">"ส่ง"</span> ข้อความในแชท LINE</p>
                                </div>
                                <div class="flex items-center gap-3">
                                    <div class="w-6 h-6 rounded-full bg-green-100 dark:bg-green-900/50 flex items-center justify-center flex-shrink-0">
                                        <span class="text-xs font-bold text-green-600 dark:text-green-400">3</span>
                                    </div>
                                    <p class="text-sm text-gray-700 dark:text-gray-300">เริ่มดูดวงได้เลย!</p>
                                </div>
                            </div>

                            <p class="text-center text-xs text-gray-400 dark:text-gray-500 mb-5">
                                ข้อความจะถูกสร้างให้อัตโนมัติ ไม่ต้องพิมพ์เอง
                            </p>

                            {{-- ===== Fallback: สำหรับ browser ที่เปิด LINE ไม่ได้ ===== --}}
                            <div class="border-t border-gray-200 dark:border-gray-700 pt-5">
                                <button @click="showFallback = !showFallback"
                                        class="w-full text-center text-sm text-gray-500 dark:text-gray-400 hover:text-purple-600 dark:hover:text-purple-400 transition">
                                    <span x-text="showFallback ? '▲ ซ่อน' : '▼ เปิด LINE ไม่ได้? กดที่นี่'"></span>
                                </button>

                                <div x-show="showFallback" x-cloak x-transition class="mt-4 space-y-4">
                                    @if($lineAddFriendUrl)
                                    {{-- ปุ่มเพิ่มเพื่อน LINE ปกติ --}}
                                    <a href="{{ $lineAddFriendUrl }}"
                                       target="_blank"
                                       class="block w-full py-3 px-4 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 text-gray-800 dark:text-white text-center font-medium rounded-lg transition text-sm">
                                        เพิ่มเพื่อน LINE OA ก่อน
                                    </a>
                                    @endif

                                    {{-- รหัสอ้างอิง --}}
                                    <div class="bg-gray-50 dark:bg-gray-700/50 rounded-lg p-4">
                                        <p class="text-xs text-gray-500 dark:text-gray-400 mb-2 text-center">
                                            หลังเพิ่มเพื่อนแล้ว พิมพ์รหัสนี้ส่งไปในแชท:
                                        </p>
                                        <div class="flex items-center gap-2">
                                            <code class="flex-1 bg-white dark:bg-gray-800 px-3 py-2 rounded border border-gray-300 dark:border-gray-600 text-sm text-center font-mono text-purple-600 dark:text-purple-400 select-all">{{ $referralCode }}</code>
                                            <button @click="copyCode()"
                                                    class="px-3 py-2 bg-purple-600 hover:bg-purple-700 text-white rounded text-sm transition flex-shrink-0">
                                                <span x-text="copied ? '✓' : 'คัดลอก'"></span>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>

                        @elseif($lineAddFriendUrl)
                            {{-- Fallback: ไม่มี deep link แต่มี add friend URL --}}
                            <a href="{{ $lineAddFriendUrl }}"
                               class="block w-full py-4 px-6 bg-[#06C755] hover:bg-[#05B14C] text-white text-center font-bold rounded-xl shadow-lg text-lg">
                                <span class="flex items-center justify-center gap-2">
                                    <svg class="w-6 h-6" viewBox="0 0 24 24" fill="currentColor">
                                        <path d="M19.365 9.863c.349 0 .63.285.63.631 0 .345-.281.63-.63.63H17.61v1.125h1.755c.349 0 .63.283.63.63 0 .344-.281.629-.63.629h-2.386c-.345 0-.627-.285-.627-.629V8.108c0-.345.282-.63.627-.63h2.386c.349 0 .63.285.63.63 0 .349-.281.63-.63.63H17.61v1.125h1.755zm-3.855 3.016c0 .27-.174.51-.432.596-.064.021-.133.031-.199.031-.211 0-.391-.09-.51-.25l-2.443-3.317v2.94c0 .344-.279.629-.631.629-.346 0-.626-.285-.626-.629V8.108c0-.27.173-.51.43-.595.06-.023.136-.033.194-.033.195 0 .375.104.495.254l2.462 3.33V8.108c0-.345.282-.63.63-.63.345 0 .63.285.63.63v4.771zm-5.741 0c0 .344-.282.629-.631.629-.345 0-.627-.285-.627-.629V8.108c0-.345.282-.63.627-.63.349 0 .631.285.631.63v4.771zm-2.466.629H4.917c-.345 0-.63-.285-.63-.629V8.108c0-.345.285-.63.63-.63.348 0 .63.285.63.63v4.141h1.756c.348 0 .629.283.629.63 0 .344-.281.629-.629.629M24 10.314C24 4.943 18.615.572 12 .572S0 4.943 0 10.314c0 4.811 4.27 8.842 10.035 9.608.391.082.923.258 1.058.59.12.301.079.766.038 1.08l-.164 1.02c-.045.301-.24 1.186 1.049.645 1.291-.539 6.916-4.078 9.436-6.975C23.176 14.393 24 12.458 24 10.314"/>
                                    </svg>
                                    เพิ่มเพื่อน LINE
                                </span>
                            </a>
                        @else
                            <div class="bg-yellow-50 dark:bg-yellow-900/20 rounded-lg p-4 text-center">
                                <p class="text-sm text-yellow-700 dark:text-yellow-300">
                                    ระบบยังไม่ได้ตั้งค่า LINE OA
                                </p>
                            </div>
                        @endif
                    </div>
                </div>

                {{-- ===== แผนการตลาด: วิธีทำเงินจากระบบ ===== --}}
                @if(isset($commissionInfo) && $commissionInfo['enabled'])
                <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl overflow-hidden mt-4" x-data="{ showPlan: false }">
                    <button @click="showPlan = !showPlan"
                            class="w-full px-6 py-4 flex items-center justify-between bg-gradient-to-r from-amber-500 to-orange-500 text-white hover:from-amber-600 hover:to-orange-600 transition">
                        <div class="flex items-center gap-3">
                            <span class="text-2xl">💰</span>
                            <div class="text-left">
                                <p class="font-bold text-sm">อยากมีรายได้จากดูดวง?</p>
                                <p class="text-amber-100 text-xs">เรียนรู้วิธีสร้างรายได้เบื้องต้น</p>
                            </div>
                        </div>
                        <svg class="w-5 h-5 transition-transform" :class="showPlan && 'rotate-180'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                        </svg>
                    </button>

                    <div x-show="showPlan" x-cloak x-transition class="p-6 space-y-5">

                        {{-- วิธีทำเงิน 3 ขั้นตอน --}}
                        <div>
                            <h3 class="text-sm font-bold text-gray-900 dark:text-white mb-3">วิธีสร้างรายได้ 3 ขั้นตอน</h3>
                            <div class="space-y-3">
                                <div class="flex items-start gap-3">
                                    <div class="w-8 h-8 rounded-full bg-gradient-to-br from-purple-500 to-indigo-500 flex items-center justify-center flex-shrink-0 shadow">
                                        <span class="text-xs font-bold text-white">1</span>
                                    </div>
                                    <div>
                                        <p class="text-sm font-semibold text-gray-800 dark:text-gray-200">สมัครสมาชิกฟรี</p>
                                        <p class="text-xs text-gray-500 dark:text-gray-400">แค่เพิ่มเพื่อน LINE แล้วพิมพ์ "ดูดวง" ระบบจะสร้างบัญชีให้อัตโนมัติ</p>
                                    </div>
                                </div>
                                <div class="flex items-start gap-3">
                                    <div class="w-8 h-8 rounded-full bg-gradient-to-br from-purple-500 to-indigo-500 flex items-center justify-center flex-shrink-0 shadow">
                                        <span class="text-xs font-bold text-white">2</span>
                                    </div>
                                    <div>
                                        <p class="text-sm font-semibold text-gray-800 dark:text-gray-200">แชร์ลิงก์ดูดวงให้เพื่อน</p>
                                        <p class="text-xs text-gray-500 dark:text-gray-400">สร้างลิงก์เชิญส่วนตัวของคุณ แล้วแชร์ให้เพื่อนมาดูดวง</p>
                                    </div>
                                </div>
                                <div class="flex items-start gap-3">
                                    <div class="w-8 h-8 rounded-full bg-gradient-to-br from-purple-500 to-indigo-500 flex items-center justify-center flex-shrink-0 shadow">
                                        <span class="text-xs font-bold text-white">3</span>
                                    </div>
                                    <div>
                                        <p class="text-sm font-semibold text-gray-800 dark:text-gray-200">รับคอมมิชชั่นอัตโนมัติ</p>
                                        <p class="text-xs text-gray-500 dark:text-gray-400">เมื่อเพื่อนจ่ายค่าดูดวง คุณรับคอมมิชชั่นทันที</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- อัตราคอมมิชชั่น --}}
                        <div class="bg-gradient-to-br from-green-50 to-emerald-50 dark:from-green-900/20 dark:to-emerald-900/20 rounded-xl p-4 border border-green-200 dark:border-green-800">
                            <h3 class="text-sm font-bold text-green-800 dark:text-green-300 mb-3 flex items-center gap-2">
                                <span>📊</span> อัตราคอมมิชชั่น
                            </h3>
                            <div class="space-y-2">
                                {{-- Level 1 --}}
                                <div class="flex items-center justify-between bg-white dark:bg-gray-800 rounded-lg px-4 py-3 shadow-sm">
                                    <div class="flex items-center gap-2">
                                        <div class="w-6 h-6 rounded-full bg-green-500 flex items-center justify-center">
                                            <span class="text-[10px] font-bold text-white">L1</span>
                                        </div>
                                        <span class="text-sm text-gray-700 dark:text-gray-300">เพื่อนที่คุณเชิญ</span>
                                    </div>
                                    <span class="text-sm font-bold text-green-600 dark:text-green-400">{{ $commissionInfo['level1_display'] }}/ครั้ง</span>
                                </div>

                                {{-- Level 2 --}}
                                @if($commissionInfo['has_level2'])
                                <div class="flex items-center justify-between bg-white dark:bg-gray-800 rounded-lg px-4 py-3 shadow-sm">
                                    <div class="flex items-center gap-2">
                                        <div class="w-6 h-6 rounded-full bg-blue-500 flex items-center justify-center">
                                            <span class="text-[10px] font-bold text-white">L2</span>
                                        </div>
                                        <span class="text-sm text-gray-700 dark:text-gray-300">เพื่อนของเพื่อน</span>
                                    </div>
                                    <span class="text-sm font-bold text-blue-600 dark:text-blue-400">{{ $commissionInfo['level2_display'] }}/ครั้ง</span>
                                </div>
                                @endif
                            </div>
                        </div>

                        {{-- ตัวอย่างรายได้ --}}
                        <div class="bg-gradient-to-br from-purple-50 to-indigo-50 dark:from-purple-900/20 dark:to-indigo-900/20 rounded-xl p-4 border border-purple-200 dark:border-purple-800">
                            <h3 class="text-sm font-bold text-purple-800 dark:text-purple-300 mb-2 flex items-center gap-2">
                                <span>💡</span> ตัวอย่าง
                            </h3>
                            <p class="text-xs text-purple-700 dark:text-purple-300 leading-relaxed">
                                ถ้าคุณเชิญเพื่อน 10 คน แต่ละคนดูดวงเดือนละ 2 ครั้ง
                                @if($commissionInfo['level1'])
                                    <br>→ รายได้ Level 1: <span class="font-bold">{{ $commissionInfo['level1_display'] }} x 10 x 2 = {{ number_format(($commissionInfo['level1'] ?? 0) * 10 * 2, 0) }} บาท/เดือน</span>
                                @endif
                                @if($commissionInfo['has_level2'] && $commissionInfo['level2'])
                                    <br>ถ้าเพื่อนแต่ละคนเชิญอีก 5 คน (50 คน)
                                    <br>→ รายได้ Level 2: <span class="font-bold">{{ $commissionInfo['level2_display'] }} x 50 x 2 = {{ number_format(($commissionInfo['level2'] ?? 0) * 50 * 2, 0) }} บาท/เดือน</span>
                                @endif
                            </p>
                        </div>

                        {{-- จุดเด่น --}}
                        <div class="grid grid-cols-2 gap-2">
                            <div class="bg-gray-50 dark:bg-gray-700/50 rounded-lg p-3 text-center">
                                <div class="text-xl mb-1">🆓</div>
                                <p class="text-[11px] font-medium text-gray-700 dark:text-gray-300">สมัครฟรี ไม่มีค่าใช้จ่าย</p>
                            </div>
                            <div class="bg-gray-50 dark:bg-gray-700/50 rounded-lg p-3 text-center">
                                <div class="text-xl mb-1">⚡</div>
                                <p class="text-[11px] font-medium text-gray-700 dark:text-gray-300">รับคอมมิชชั่นอัตโนมัติ</p>
                            </div>
                            <div class="bg-gray-50 dark:bg-gray-700/50 rounded-lg p-3 text-center">
                                <div class="text-xl mb-1">📱</div>
                                <p class="text-[11px] font-medium text-gray-700 dark:text-gray-300">ทำได้ผ่านมือถือ</p>
                            </div>
                            <div class="bg-gray-50 dark:bg-gray-700/50 rounded-lg p-3 text-center">
                                <div class="text-xl mb-1">💸</div>
                                <p class="text-[11px] font-medium text-gray-700 dark:text-gray-300">ถอนเงินได้จริง</p>
                            </div>
                        </div>

                        <p class="text-center text-[11px] text-gray-400 dark:text-gray-500">
                            * อัตราคอมมิชชั่นอาจเปลี่ยนแปลงตามนโยบายของระบบ
                        </p>
                    </div>
                </div>
                @endif

                {{-- Footer --}}
                <div class="text-center mt-6">
                    <p class="text-xs text-gray-400 dark:text-gray-500">
                        Powered by Thaiprompt
                    </p>
                </div>
            @endif
        </div>
    </div>

    @if(!$error)
    <script>
    function inviteLanding() {
        return {
            showFallback: false,
            copied: false,
            countdown: '--:--:--',
            expiresAt: '{{ $expiresAt ?? '' }}',

            init() {
                if (this.expiresAt) {
                    this.updateCountdown();
                    setInterval(() => this.updateCountdown(), 1000);
                }
            },

            updateCountdown() {
                const now = new Date();
                const expires = new Date(this.expiresAt);
                const diff = expires - now;

                if (diff <= 0) {
                    this.countdown = 'หมดอายุแล้ว';
                    return;
                }

                const hours = Math.floor(diff / (1000 * 60 * 60));
                const minutes = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));
                const seconds = Math.floor((diff % (1000 * 60)) / 1000);

                this.countdown = `${String(hours).padStart(2, '0')}:${String(minutes).padStart(2, '0')}:${String(seconds).padStart(2, '0')}`;
            },

            copyCode() {
                const code = '{{ $referralCode ?? '' }}';
                navigator.clipboard.writeText(code).then(() => {
                    this.copied = true;
                    setTimeout(() => this.copied = false, 2000);
                });
            }
        }
    }
    </script>
    @endif
</body>
</html>
