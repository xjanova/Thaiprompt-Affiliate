{{-- resources/views/user/fortune-referral/recruit.blade.php --}}
@extends('layouts.user-arrow-x')

@section('title', 'ชวนเพื่อนดูดวง')

@section('content')
<div class="min-h-screen bg-gradient-to-br from-amber-50 via-orange-50 to-yellow-50 dark:from-gray-900 dark:via-amber-900/10 dark:to-gray-900">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

        {{-- Hero Header --}}
        <div class="relative overflow-hidden rounded-2xl bg-gradient-to-r from-amber-500 via-orange-500 to-yellow-500 p-6 sm:p-8 mb-8 shadow-xl">
            <div class="absolute inset-0 bg-black/10"></div>
            <div class="absolute -top-10 -right-10 w-40 h-40 bg-white/10 rounded-full blur-2xl"></div>
            <div class="absolute -bottom-10 -left-10 w-32 h-32 bg-yellow-300/20 rounded-full blur-2xl"></div>
            <div class="relative z-10 text-center">
                <div class="flex items-center justify-center gap-3 mb-3">
                    <img src="{{ auth()->user()->profile_picture_url ?? 'https://ui-avatars.com/api/?name=U&background=f59e0b&color=fff&size=64' }}"
                         alt="avatar" class="w-16 h-16 rounded-full border-4 border-white/50 shadow-lg">
                </div>
                <h1 class="text-2xl sm:text-3xl font-bold text-white mb-1">ชวนเพื่อนมาดูดวง</h1>
                <p class="text-amber-100 text-sm sm:text-base">
                    แชร์ลิงก์เชิญเพื่อน → เพื่อนดูดวง → คุณรับคอมมิชชั่นทันที!
                </p>
            </div>
        </div>

        {{-- Referral Link --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6 mb-6 border border-amber-100 dark:border-amber-900/50"
             x-data="{ copied: false }">
            <h2 class="text-lg font-bold text-gray-900 dark:text-white mb-3 flex items-center gap-2">
                ลิงก์เชิญเพื่อน
            </h2>
            <div class="flex gap-2">
                <input type="text" readonly value="{{ $referralLink }}" id="referralLinkInput"
                       class="flex-1 px-4 py-3 bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-sm text-gray-900 dark:text-white">
                <button @click="
                    navigator.clipboard.writeText('{{ $referralLink }}').then(() => {
                        copied = true;
                        setTimeout(() => copied = false, 2000);
                    });
                " class="px-5 py-3 rounded-lg font-medium text-sm transition-all whitespace-nowrap"
                   :class="copied ? 'bg-green-500 text-white' : 'bg-amber-500 hover:bg-amber-600 text-white'">
                    <span x-show="!copied">คัดลอก</span>
                    <span x-show="copied" x-cloak>คัดลอกแล้ว!</span>
                </button>
            </div>
            {{-- Share Buttons --}}
            <div class="flex gap-3 mt-4">
                <a href="https://line.me/R/share?text={{ urlencode('มาดูดวงกัน! ' . $referralLink) }}"
                   target="_blank"
                   class="flex items-center gap-2 px-4 py-2 bg-green-500 hover:bg-green-600 text-white rounded-lg text-sm transition">
                    แชร์ LINE
                </a>
                <button @click="
                    navigator.clipboard.writeText('มาดูดวงกัน!\n{{ $referralLink }}').then(() => {
                        copied = true;
                        setTimeout(() => copied = false, 2000);
                    });
                " class="flex items-center gap-2 px-4 py-2 bg-gray-200 dark:bg-gray-700 hover:bg-gray-300 dark:hover:bg-gray-600 text-gray-800 dark:text-white rounded-lg text-sm transition">
                    คัดลอกข้อความ
                </button>
            </div>
        </div>

        {{-- Commission Preview (ตัวอย่างคำนวณจากค่าจริง) --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6 mb-6 border border-purple-100 dark:border-purple-900/50">
            <h2 class="text-lg font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                ตัวอย่างรายได้จากการแนะนำ
            </h2>

            {{-- 📦 (2026-07-15) โหมดค่าแนะนำแยกตามแพคเกจ — ค่าแนะนำต่างกันตามแพคเกจที่เพื่อนซื้อ --}}
            @php $packageRates = $packageRates ?? null; @endphp
            @if($packageRates)
            <div class="bg-gradient-to-br from-purple-50 to-indigo-50 dark:from-purple-900/20 dark:to-indigo-900/20 rounded-xl p-5 mb-4">
                <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
                    ค่าแนะนำขึ้นกับแพคเกจที่เพื่อนเลือก — เพื่อนจ่ายจริงเมื่อไหร่ คุณได้เมื่อนั้น
                </p>

                <div class="space-y-4">
                    @foreach($packageRates as $pkg)
                    <div class="p-4 bg-white dark:bg-gray-800 rounded-lg border border-purple-200 dark:border-purple-800">
                        <div class="flex items-center justify-between mb-3">
                            <p class="font-bold text-gray-900 dark:text-white">{{ $pkg['label'] }}</p>
                            <span class="text-sm font-medium text-purple-600 dark:text-purple-400">
                                {{ number_format($pkg['price'], 0) }} บาท
                            </span>
                        </div>
                        <div class="space-y-2">
                            <div class="flex items-center justify-between text-sm">
                                <span class="text-gray-600 dark:text-gray-400">🤝 สายตรง (เพื่อนที่คุณชวนเอง)</span>
                                <span class="font-bold text-green-600 dark:text-green-400">
                                    +{{ number_format($pkg['l1'], 0) }} บาท
                                </span>
                            </div>
                            <div class="flex items-center justify-between text-sm">
                                <span class="text-gray-600 dark:text-gray-400">👶 ชั้นหลาน (เพื่อนของเพื่อน)</span>
                                @if($pkg['l2_enabled'])
                                <span class="font-bold text-amber-600 dark:text-amber-400">
                                    +{{ number_format($pkg['l2'], 0) }} บาท
                                </span>
                                @else
                                <span class="font-medium text-gray-400 dark:text-gray-500">แพคเกจนี้ไม่มี</span>
                                @endif
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>

                <p class="text-xs text-gray-500 dark:text-gray-400 mt-4">
                    ค่าแนะนำมี 2 ชั้นเท่านั้น · ชวนได้ไม่จำกัดจำนวนคน ไม่มีเพดานรายได้
                </p>
            </div>
            @else
            <div class="bg-gradient-to-br from-purple-50 to-indigo-50 dark:from-purple-900/20 dark:to-indigo-900/20 rounded-xl p-5 mb-4">
                <p class="text-sm text-gray-600 dark:text-gray-400 mb-3">
                    ราคาดูดวง: <span class="font-bold text-purple-600 dark:text-purple-400">{{ number_format($readingPrice, 0) }} บาท</span>
                </p>

                <div class="space-y-3">
                    {{-- Level 1 --}}
                    <div class="flex items-center justify-between p-3 bg-white dark:bg-gray-800 rounded-lg border border-green-200 dark:border-green-800">
                        <div class="flex items-center gap-2">
                            <span class="text-lg">🤝</span>
                            <div>
                                <p class="text-sm font-medium text-gray-900 dark:text-white">Level 1 — เพื่อนดูดวง 1 ครั้ง</p>
                                <p class="text-xs text-gray-500">{{ $level1Type === 'fixed' ? 'จำนวนคงที่' : 'เปอร์เซ็นต์ ' . ($settings->fortune_level1_commission_amount ?? 10) . '%' }}</p>
                            </div>
                        </div>
                        <span class="text-lg font-bold text-green-600 dark:text-green-400">+{{ number_format($level1Amount, 2) }} บาท</span>
                    </div>

                    {{-- Level 2 --}}
                    @if($level2Enabled)
                    <div class="flex items-center justify-between p-3 bg-white dark:bg-gray-800 rounded-lg border border-amber-200 dark:border-amber-800">
                        <div class="flex items-center gap-2">
                            <span class="text-lg">👶</span>
                            <div>
                                <p class="text-sm font-medium text-gray-900 dark:text-white">Level 2 — หลานดูดวง 1 ครั้ง</p>
                                <p class="text-xs text-gray-500">{{ $level2Type === 'fixed' ? 'จำนวนคงที่' : 'เปอร์เซ็นต์ ' . ($settings->fortune_level2_commission_amount ?? 5) . '%' }}</p>
                            </div>
                        </div>
                        <span class="text-lg font-bold text-amber-600 dark:text-amber-400">+{{ number_format($level2Amount, 2) }} บาท</span>
                    </div>
                    @endif
                </div>
            </div>
            @endif

            {{-- Simulation --}}
            @php
                // โหมดแยกแพคเกจ: อ้างอิง Celtic เป็นตัวอย่าง (แพคเกจที่ค่าแนะนำสูงสุด)
                $simL1 = $packageRates ? $packageRates['celtic_cross']['l1'] : $level1Amount;
                $simL2Enabled = $packageRates ? $packageRates['celtic_cross']['l2_enabled'] : $level2Enabled;
                $simL2 = $packageRates ? $packageRates['celtic_cross']['l2'] : $level2Amount;
                $simLabel = $packageRates ? $packageRates['celtic_cross']['label'] : 'ดูดวง';
                $monthlyEstimate = ($simL1 * 10 * 3) + ($simL2Enabled ? $simL2 * 10 * 5 * 3 : 0);
            @endphp
            <div class="bg-gradient-to-br from-green-50 to-emerald-50 dark:from-green-900/20 dark:to-emerald-900/20 rounded-xl p-5">
                <h3 class="text-sm font-bold text-gray-900 dark:text-white mb-3">
                    จำลองรายได้
                    @if($packageRates)
                    <span class="font-normal text-gray-500">(ถ้าเพื่อนเลือก {{ $simLabel }})</span>
                    @endif
                </h3>
                <div class="space-y-2 text-sm">
                    <div class="flex justify-between">
                        <span class="text-gray-600 dark:text-gray-400">ชวน 10 คน × ดูดวง 3 ครั้ง (สายตรง)</span>
                        <span class="font-bold text-green-600 dark:text-green-400">{{ number_format($simL1 * 10 * 3, 0) }} บาท</span>
                    </div>
                    @if($simL2Enabled)
                    <div class="flex justify-between">
                        <span class="text-gray-600 dark:text-gray-400">10 คน × ชวนอีกคนละ 5 หลาน × 3 ครั้ง (ชั้นหลาน)</span>
                        <span class="font-bold text-amber-600 dark:text-amber-400">{{ number_format($simL2 * 10 * 5 * 3, 0) }} บาท</span>
                    </div>
                    @endif
                    <div class="border-t border-green-200 dark:border-green-700 pt-2 flex justify-between">
                        <span class="font-bold text-gray-900 dark:text-white">รวมต่อเดือน (ประมาณ)</span>
                        <span class="text-lg font-bold text-purple-600 dark:text-purple-400">{{ number_format($monthlyEstimate, 0) }} บาท</span>
                    </div>
                </div>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-3">
                    เป็นเพียงตัวอย่างการคำนวณ ไม่ใช่การรับประกันรายได้ — รายได้จริงขึ้นกับจำนวนเพื่อนที่ใช้บริการจริง
                </p>
            </div>
        </div>

        {{-- Marketing Tips --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-6">
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-5 border border-gray-100 dark:border-gray-700">
                <span class="text-2xl mb-2 block">🎯</span>
                <h3 class="font-bold text-gray-900 dark:text-white mb-1">แชร์ในกลุ่ม LINE</h3>
                <p class="text-xs text-gray-500 dark:text-gray-400">ส่งลิงก์เชิญในกลุ่มเพื่อน กลุ่มดูดวง กลุ่มที่สนใจเรื่องโชคชะตา</p>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-5 border border-gray-100 dark:border-gray-700">
                <span class="text-2xl mb-2 block">📱</span>
                <h3 class="font-bold text-gray-900 dark:text-white mb-1">โพสต์ใน Social Media</h3>
                <p class="text-xs text-gray-500 dark:text-gray-400">แชร์ประสบการณ์ดูดวงพร้อมลิงก์เชิญใน Facebook, Instagram, TikTok</p>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-5 border border-gray-100 dark:border-gray-700">
                <span class="text-2xl mb-2 block">💬</span>
                <h3 class="font-bold text-gray-900 dark:text-white mb-1">ส่งให้เพื่อนโดยตรง</h3>
                <p class="text-xs text-gray-500 dark:text-gray-400">ส่งลิงก์ส่วนตัวให้เพื่อนที่สนใจดูดวง พร้อมบอกข้อดี</p>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-5 border border-gray-100 dark:border-gray-700">
                <span class="text-2xl mb-2 block">🏆</span>
                <h3 class="font-bold text-gray-900 dark:text-white mb-1">ชวนเพื่อนชวนต่อ</h3>
                <p class="text-xs text-gray-500 dark:text-gray-400">บอกเพื่อนว่าชวนต่อก็ได้เงิน — คุณได้จากหลานด้วย (Level 2)</p>
            </div>
        </div>

        {{-- CTA --}}
        <div class="text-center">
            <a href="{{ route('user.fortune-referral.commissions') }}"
               class="inline-flex items-center gap-2 px-6 py-3 bg-purple-600 hover:bg-purple-700 text-white rounded-xl font-medium transition shadow-lg">
                ดูคอมมิชชั่นของฉัน
            </a>
        </div>
    </div>
</div>
@endsection
