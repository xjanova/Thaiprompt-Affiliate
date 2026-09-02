@extends('layouts.user-v4')

@section('title', 'คู่มือสร้างเหรียญของคุณเอง - TPIX')

@section('content')
<div x-data="{ activeTab: 'overview' }" class="min-h-screen bg-gradient-to-br from-gray-50 to-gray-100 dark:from-gray-900 dark:to-gray-800">

    {{-- Hero Header --}}
    <div class="relative overflow-hidden bg-gradient-to-r from-green-600 via-emerald-600 to-teal-600">
        <div class="absolute inset-0 bg-black/20"></div>
        <div class="absolute inset-0 bg-[url('data:image/svg+xml,%3Csvg width=\"60\" height=\"60\" viewBox=\"0 0 60 60\" xmlns=\"http://www.w3.org/2000/svg\"%3E%3Cg fill=\"none\" fill-rule=\"evenodd\"%3E%3Cg fill=\"%23ffffff\" fill-opacity=\"0.05\"%3E%3Cpath d=\"M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z\"/%3E%3C/g%3E%3C/g%3E%3C/svg%3E')]"></div>

        <div class="relative max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-16">
            <div class="text-center">
                <div class="inline-flex items-center gap-2 px-4 py-2 bg-white/10 backdrop-blur rounded-full text-white/80 text-sm mb-6">
                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M10.394 2.08a1 1 0 00-.788 0l-7 3a1 1 0 000 1.84L5.25 8.051a.999.999 0 01.356-.257l4-1.714a1 1 0 11.788 1.838L7.667 9.088l1.94.831a1 1 0 00.787 0l7-3a1 1 0 000-1.838l-7-3z"/>
                    </svg>
                    คู่มือสำหรับผู้เริ่มต้น
                </div>

                <h1 class="text-4xl md:text-5xl font-bold text-white mb-4">
                    🪙 สร้างเหรียญของคุณเอง
                </h1>
                <p class="text-xl text-white/80 max-w-3xl mx-auto mb-8">
                    เรียนรู้ขั้นตอนการสร้าง Token บน TPIX Blockchain ตั้งแต่เริ่มต้นจนพร้อมซื้อขาย
                    ง่าย รวดเร็ว ไม่ต้องเขียนโค้ด!
                </p>

                <div class="flex flex-wrap justify-center gap-4">
                    <a href="{{ route('user.tokens.create') }}"
                       class="px-8 py-4 bg-white text-green-600 font-bold rounded-xl hover:bg-gray-100 transition shadow-lg flex items-center gap-2">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                        </svg>
                        เริ่มสร้างเหรียญเลย!
                    </a>
                    <a href="#steps"
                       class="px-8 py-4 bg-white/10 backdrop-blur text-white font-semibold rounded-xl hover:bg-white/20 transition border border-white/20">
                        📖 อ่านคู่มือก่อน
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-12">

        {{-- Quick Stats --}}
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-12">
            <div class="tp-card rounded-2xl p-6 text-center border border-gray-200 dark:border-gray-700">
                <div class="text-3xl mb-2">⏱️</div>
                <div class="text-2xl font-bold text-gray-900 dark:text-white">5 นาที</div>
                <div class="text-sm text-gray-600 dark:text-gray-400">เวลาสร้างโดยเฉลี่ย</div>
            </div>
            <div class="tp-card rounded-2xl p-6 text-center border border-gray-200 dark:border-gray-700">
                <div class="text-3xl mb-2">📝</div>
                <div class="text-2xl font-bold text-gray-900 dark:text-white">4 ขั้นตอน</div>
                <div class="text-sm text-gray-600 dark:text-gray-400">ง่าย ๆ ไม่ซับซ้อน</div>
            </div>
            <div class="tp-card rounded-2xl p-6 text-center border border-gray-200 dark:border-gray-700">
                <div class="text-3xl mb-2">💰</div>
                <div class="text-2xl font-bold text-gray-900 dark:text-white">100 TPIX</div>
                <div class="text-sm text-gray-600 dark:text-gray-400">ค่าสร้างเริ่มต้น</div>
            </div>
            <div class="tp-card rounded-2xl p-6 text-center border border-gray-200 dark:border-gray-700">
                <div class="text-3xl mb-2">🚀</div>
                <div class="text-2xl font-bold text-gray-900 dark:text-white">0 โค้ด</div>
                <div class="text-sm text-gray-600 dark:text-gray-400">ไม่ต้องเขียนโค้ดเอง</div>
            </div>
        </div>

        {{-- Navigation Tabs --}}
        <div class="mb-8">
            <div class="tp-card flex flex-wrap gap-2 p-2 rounded-2xl">
                <button @click="activeTab = 'overview'"
                        :class="activeTab === 'overview' ? 'bg-gradient-to-r from-green-500 to-emerald-500 text-white shadow-lg' : 'text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700'"
                        class="flex-1 px-6 py-3 rounded-xl font-semibold transition-all">
                    📋 ภาพรวม
                </button>
                <button @click="activeTab = 'steps'"
                        :class="activeTab === 'steps' ? 'bg-gradient-to-r from-green-500 to-emerald-500 text-white shadow-lg' : 'text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700'"
                        class="flex-1 px-6 py-3 rounded-xl font-semibold transition-all">
                    📝 ขั้นตอนการสร้าง
                </button>
                <button @click="activeTab = 'features'"
                        :class="activeTab === 'features' ? 'bg-gradient-to-r from-green-500 to-emerald-500 text-white shadow-lg' : 'text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700'"
                        class="flex-1 px-6 py-3 rounded-xl font-semibold transition-all">
                    ⚙️ คุณสมบัติ Token
                </button>
                <button @click="activeTab = 'faq'"
                        :class="activeTab === 'faq' ? 'bg-gradient-to-r from-green-500 to-emerald-500 text-white shadow-lg' : 'text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700'"
                        class="flex-1 px-6 py-3 rounded-xl font-semibold transition-all">
                    ❓ คำถามที่พบบ่อย
                </button>
            </div>
        </div>

        {{-- Tab: Overview --}}
        <div x-show="activeTab === 'overview'" x-transition class="space-y-8">
            {{-- What is Token --}}
            <div class="tp-card rounded-3xl p-8 border border-gray-200 dark:border-gray-700">
                <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-6 flex items-center gap-3">
                    <span class="w-10 h-10 bg-gradient-to-br from-blue-500 to-purple-500 rounded-xl flex items-center justify-center text-white">🤔</span>
                    Token คืออะไร?
                </h2>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                    <div>
                        <p class="text-gray-600 dark:text-gray-400 leading-relaxed mb-4">
                            <strong>Token</strong> คือสินทรัพย์ดิจิทัลที่สร้างขึ้นบน Blockchain
                            สามารถใช้เป็นเหรียญสำหรับโปรเจกต์ของคุณ เช่น:
                        </p>
                        <ul class="space-y-3">
                            <li class="flex items-start gap-3 text-gray-600 dark:text-gray-400">
                                <span class="w-6 h-6 bg-green-100 dark:bg-green-900/30 rounded-full flex items-center justify-center text-green-600 dark:text-green-400 flex-shrink-0 mt-0.5">✓</span>
                                <span><strong>ระบบสมาชิก</strong> - ใช้ Token เป็นรางวัลหรือสิทธิพิเศษ</span>
                            </li>
                            <li class="flex items-start gap-3 text-gray-600 dark:text-gray-400">
                                <span class="w-6 h-6 bg-green-100 dark:bg-green-900/30 rounded-full flex items-center justify-center text-green-600 dark:text-green-400 flex-shrink-0 mt-0.5">✓</span>
                                <span><strong>เกม</strong> - เป็นเงินในเกมหรือ NFT</span>
                            </li>
                            <li class="flex items-start gap-3 text-gray-600 dark:text-gray-400">
                                <span class="w-6 h-6 bg-green-100 dark:bg-green-900/30 rounded-full flex items-center justify-center text-green-600 dark:text-green-400 flex-shrink-0 mt-0.5">✓</span>
                                <span><strong>ธุรกิจ</strong> - ระดมทุนหรือแลกเปลี่ยนสินค้า</span>
                            </li>
                            <li class="flex items-start gap-3 text-gray-600 dark:text-gray-400">
                                <span class="w-6 h-6 bg-green-100 dark:bg-green-900/30 rounded-full flex items-center justify-center text-green-600 dark:text-green-400 flex-shrink-0 mt-0.5">✓</span>
                                <span><strong>ชุมชน</strong> - ให้สิทธิ์โหวตหรือบริหารงาน (DAO)</span>
                            </li>
                        </ul>
                    </div>
                    <div class="bg-gradient-to-br from-green-50 to-emerald-50 dark:from-green-900/20 dark:to-emerald-900/20 rounded-2xl p-6 border border-green-200 dark:border-green-800">
                        <h4 class="font-bold text-green-900 dark:text-green-100 mb-4">✨ ทำไมต้องสร้างบน TPIX?</h4>
                        <ul class="space-y-2 text-sm text-green-800 dark:text-green-200">
                            <li class="flex items-center gap-2">
                                <span class="text-green-500">⚡</span>
                                <span>ค่าธรรมเนียมต่ำมาก (ใกล้ 0)</span>
                            </li>
                            <li class="flex items-center gap-2">
                                <span class="text-green-500">🚀</span>
                                <span>ทำธุรกรรมเร็ว (2 วินาที)</span>
                            </li>
                            <li class="flex items-center gap-2">
                                <span class="text-green-500">🛡️</span>
                                <span>ปลอดภัยด้วย IBFT Consensus</span>
                            </li>
                            <li class="flex items-center gap-2">
                                <span class="text-green-500">🔧</span>
                                <span>ใช้งานง่าย ไม่ต้องเขียนโค้ด</span>
                            </li>
                            <li class="flex items-center gap-2">
                                <span class="text-green-500">💱</span>
                                <span>ซื้อขายได้ทันทีบน TPIX DEX</span>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>

            {{-- Process Overview --}}
            <div class="tp-card rounded-3xl p-8 border border-gray-200 dark:border-gray-700">
                <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-6 flex items-center gap-3">
                    <span class="w-10 h-10 bg-gradient-to-br from-purple-500 to-pink-500 rounded-xl flex items-center justify-center text-white">🛤️</span>
                    ขั้นตอนการสร้าง Token
                </h2>

                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    @foreach([
                        ['step' => 1, 'title' => 'กรอกข้อมูล', 'desc' => 'ชื่อ, Symbol, Supply', 'icon' => '📝', 'color' => 'blue'],
                        ['step' => 2, 'title' => 'เลือกคุณสมบัติ', 'desc' => 'Mint, Burn, Pause', 'icon' => '⚙️', 'color' => 'purple'],
                        ['step' => 3, 'title' => 'ตั้งราคา', 'desc' => 'Tokenomics & Links', 'icon' => '💰', 'color' => 'yellow'],
                        ['step' => 4, 'title' => 'ตรวจสอบ & สร้าง', 'desc' => 'Review & Deploy', 'icon' => '🚀', 'color' => 'green'],
                    ] as $step)
                    <div class="relative">
                        @if($step['step'] < 4)
                        <div class="hidden md:block absolute top-8 left-1/2 w-full h-1 bg-gray-200 dark:bg-gray-700"></div>
                        @endif
                        <div class="relative bg-{{ $step['color'] }}-50 dark:bg-{{ $step['color'] }}-900/20 rounded-2xl p-6 border-2 border-{{ $step['color'] }}-200 dark:border-{{ $step['color'] }}-800 text-center">
                            <div class="inline-flex w-16 h-16 bg-gradient-to-br from-{{ $step['color'] }}-500 to-{{ $step['color'] }}-600 rounded-full items-center justify-center text-2xl text-white shadow-lg mb-4">
                                {{ $step['icon'] }}
                            </div>
                            <div class="text-xs font-bold text-{{ $step['color'] }}-600 dark:text-{{ $step['color'] }}-400 mb-1">
                                ขั้นตอนที่ {{ $step['step'] }}
                            </div>
                            <h4 class="font-bold text-gray-900 dark:text-white mb-1">{{ $step['title'] }}</h4>
                            <p class="text-xs text-gray-600 dark:text-gray-400">{{ $step['desc'] }}</p>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>

        {{-- Tab: Steps --}}
        <div x-show="activeTab === 'steps'" x-transition id="steps" class="space-y-6">
            {{-- Step 1 --}}
            <div class="tp-card rounded-3xl overflow-hidden border border-gray-200 dark:border-gray-700">
                <div class="p-6 bg-gradient-to-r from-blue-500 to-cyan-500">
                    <div class="flex items-center gap-4">
                        <div class="w-16 h-16 bg-white/20 backdrop-blur rounded-2xl flex items-center justify-center text-3xl text-white">
                            📝
                        </div>
                        <div class="text-white">
                            <div class="text-sm font-medium text-white/80">ขั้นตอนที่ 1</div>
                            <h3 class="text-2xl font-bold">กรอกข้อมูลพื้นฐาน</h3>
                        </div>
                    </div>
                </div>
                <div class="p-8">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                        <div>
                            <h4 class="font-bold text-gray-900 dark:text-white mb-4">📌 ข้อมูลที่ต้องกรอก</h4>
                            <div class="space-y-4">
                                <div class="p-4 bg-gray-50 dark:bg-gray-900/50 rounded-xl">
                                    <div class="font-semibold text-gray-900 dark:text-white mb-1">ชื่อ Token</div>
                                    <p class="text-sm text-gray-600 dark:text-gray-400">ชื่อเต็มของเหรียญ เช่น "My Awesome Token"</p>
                                    <div class="mt-2 text-xs text-blue-600 dark:text-blue-400">
                                        💡 ควรเป็นชื่อที่จำง่ายและสื่อความหมาย
                                    </div>
                                </div>
                                <div class="p-4 bg-gray-50 dark:bg-gray-900/50 rounded-xl">
                                    <div class="font-semibold text-gray-900 dark:text-white mb-1">สัญลักษณ์ (Symbol)</div>
                                    <p class="text-sm text-gray-600 dark:text-gray-400">ตัวย่อของเหรียญ 2-20 ตัวอักษร เช่น "MAT"</p>
                                    <div class="mt-2 text-xs text-blue-600 dark:text-blue-400">
                                        💡 ควรเป็นตัวพิมพ์ใหญ่ สั้นและจำง่าย
                                    </div>
                                </div>
                                <div class="p-4 bg-gray-50 dark:bg-gray-900/50 rounded-xl">
                                    <div class="font-semibold text-gray-900 dark:text-white mb-1">Total Supply</div>
                                    <p class="text-sm text-gray-600 dark:text-gray-400">จำนวน Token ทั้งหมดที่จะสร้าง</p>
                                    <div class="mt-2 text-xs text-blue-600 dark:text-blue-400">
                                        💡 คิดให้ดีก่อนตั้ง เปลี่ยนภายหลังไม่ได้ (ยกเว้นเปิด Mintable)
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div>
                            <h4 class="font-bold text-gray-900 dark:text-white mb-4">📊 ตัวอย่าง</h4>
                            <div class="bg-gradient-to-br from-blue-50 to-cyan-50 dark:from-blue-900/20 dark:to-cyan-900/20 rounded-2xl p-6 border border-blue-200 dark:border-blue-800">
                                <div class="flex items-center gap-4 mb-4">
                                    <div class="w-12 h-12 bg-gradient-to-br from-orange-400 to-pink-500 rounded-full flex items-center justify-center text-white font-bold">
                                        CF
                                    </div>
                                    <div>
                                        <div class="font-bold text-gray-900 dark:text-white">CoffeeCoin</div>
                                        <div class="text-sm text-gray-600 dark:text-gray-400">$COFFEE</div>
                                    </div>
                                </div>
                                <div class="space-y-2 text-sm">
                                    <div class="flex justify-between">
                                        <span class="text-gray-600 dark:text-gray-400">Total Supply:</span>
                                        <span class="font-semibold text-gray-900 dark:text-white">10,000,000</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-gray-600 dark:text-gray-400">Decimals:</span>
                                        <span class="font-semibold text-gray-900 dark:text-white">18</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-gray-600 dark:text-gray-400">หมวดหมู่:</span>
                                        <span class="font-semibold text-gray-900 dark:text-white">🔧 Utility</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Step 2 --}}
            <div class="tp-card rounded-3xl overflow-hidden border border-gray-200 dark:border-gray-700">
                <div class="p-6 bg-gradient-to-r from-purple-500 to-pink-500">
                    <div class="flex items-center gap-4">
                        <div class="w-16 h-16 bg-white/20 backdrop-blur rounded-2xl flex items-center justify-center text-3xl text-white">
                            ⚙️
                        </div>
                        <div class="text-white">
                            <div class="text-sm font-medium text-white/80">ขั้นตอนที่ 2</div>
                            <h3 class="text-2xl font-bold">เลือกคุณสมบัติ Token</h3>
                        </div>
                    </div>
                </div>
                <div class="p-8">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="p-6 rounded-2xl border-2 border-green-200 dark:border-green-800 bg-green-50 dark:bg-green-900/20">
                            <div class="flex items-center gap-3 mb-3">
                                <span class="text-2xl">🟢</span>
                                <h4 class="font-bold text-green-900 dark:text-green-100">Mintable</h4>
                            </div>
                            <p class="text-sm text-green-800 dark:text-green-200 mb-3">
                                สามารถสร้าง Token เพิ่มได้หลังจาก Deploy
                            </p>
                            <div class="text-xs text-green-700 dark:text-green-300 bg-green-100 dark:bg-green-900/30 rounded-lg p-3">
                                <strong>เหมาะกับ:</strong> ระบบรางวัล, เกม, Reward Points
                            </div>
                        </div>

                        <div class="p-6 rounded-2xl border-2 border-red-200 dark:border-red-800 bg-red-50 dark:bg-red-900/20">
                            <div class="flex items-center gap-3 mb-3">
                                <span class="text-2xl">🔥</span>
                                <h4 class="font-bold text-red-900 dark:text-red-100">Burnable</h4>
                            </div>
                            <p class="text-sm text-red-800 dark:text-red-200 mb-3">
                                สามารถทำลาย Token เพื่อลด Supply ได้
                            </p>
                            <div class="text-xs text-red-700 dark:text-red-300 bg-red-100 dark:bg-red-900/30 rounded-lg p-3">
                                <strong>เหมาะกับ:</strong> Deflationary Token, Buyback & Burn
                            </div>
                        </div>

                        <div class="p-6 rounded-2xl border-2 border-yellow-200 dark:border-yellow-800 bg-yellow-50 dark:bg-yellow-900/20">
                            <div class="flex items-center gap-3 mb-3">
                                <span class="text-2xl">⏸️</span>
                                <h4 class="font-bold text-yellow-900 dark:text-yellow-100">Pausable</h4>
                            </div>
                            <p class="text-sm text-yellow-800 dark:text-yellow-200 mb-3">
                                หยุดการโอน Token ทั้งหมดชั่วคราวได้
                            </p>
                            <div class="text-xs text-yellow-700 dark:text-yellow-300 bg-yellow-100 dark:bg-yellow-900/30 rounded-lg p-3">
                                <strong>เหมาะกับ:</strong> กรณีฉุกเฉิน, Security Incident
                            </div>
                        </div>

                        <div class="p-6 rounded-2xl border-2 border-blue-200 dark:border-blue-800 bg-blue-50 dark:bg-blue-900/20">
                            <div class="flex items-center gap-3 mb-3">
                                <span class="text-2xl">❄️</span>
                                <h4 class="font-bold text-blue-900 dark:text-blue-100">Freezable</h4>
                            </div>
                            <p class="text-sm text-blue-800 dark:text-blue-200 mb-3">
                                Freeze Address เฉพาะได้ (ไม่ให้โอนออก)
                            </p>
                            <div class="text-xs text-blue-700 dark:text-blue-300 bg-blue-100 dark:bg-blue-900/30 rounded-lg p-3">
                                <strong>เหมาะกับ:</strong> ป้องกัน Hack, Compliance
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Step 3 --}}
            <div class="tp-card rounded-3xl overflow-hidden border border-gray-200 dark:border-gray-700">
                <div class="p-6 bg-gradient-to-r from-yellow-500 to-orange-500">
                    <div class="flex items-center gap-4">
                        <div class="w-16 h-16 bg-white/20 backdrop-blur rounded-2xl flex items-center justify-center text-3xl text-white">
                            💰
                        </div>
                        <div class="text-white">
                            <div class="text-sm font-medium text-white/80">ขั้นตอนที่ 3</div>
                            <h3 class="text-2xl font-bold">Tokenomics & ข้อมูลเพิ่มเติม</h3>
                        </div>
                    </div>
                </div>
                <div class="p-8">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                        <div>
                            <h4 class="font-bold text-gray-900 dark:text-white mb-4">💵 การตั้งราคา</h4>
                            <div class="p-4 bg-yellow-50 dark:bg-yellow-900/20 rounded-xl border border-yellow-200 dark:border-yellow-800 mb-4">
                                <div class="font-semibold text-yellow-900 dark:text-yellow-100 mb-2">ราคาเริ่มต้น (TPIX)</div>
                                <p class="text-sm text-yellow-800 dark:text-yellow-200">
                                    ตั้งราคาที่ต้องการขาย Token ของคุณ<br>
                                    เช่น 1 Token = 0.01 TPIX
                                </p>
                            </div>
                            <div class="text-sm text-gray-600 dark:text-gray-400">
                                <strong>💡 Tips:</strong>
                                <ul class="list-disc list-inside mt-2 space-y-1">
                                    <li>ราคาต่ำ = มีคนสนใจมากขึ้น</li>
                                    <li>ราคาสูง = ดูมีมูลค่า</li>
                                    <li>ควรมี Whitepaper อธิบายโปรเจกต์</li>
                                </ul>
                            </div>
                        </div>
                        <div>
                            <h4 class="font-bold text-gray-900 dark:text-white mb-4">🔗 Social Links</h4>
                            <div class="space-y-3">
                                <div class="flex items-center gap-3 p-3 bg-gray-50 dark:bg-gray-900/50 rounded-xl">
                                    <span class="text-xl">🌐</span>
                                    <div>
                                        <div class="font-semibold text-gray-900 dark:text-white text-sm">Website</div>
                                        <div class="text-xs text-gray-500">หน้าเว็บโปรเจกต์</div>
                                    </div>
                                </div>
                                <div class="flex items-center gap-3 p-3 bg-gray-50 dark:bg-gray-900/50 rounded-xl">
                                    <span class="text-xl">📄</span>
                                    <div>
                                        <div class="font-semibold text-gray-900 dark:text-white text-sm">Whitepaper</div>
                                        <div class="text-xs text-gray-500">เอกสารอธิบายโปรเจกต์</div>
                                    </div>
                                </div>
                                <div class="flex items-center gap-3 p-3 bg-gray-50 dark:bg-gray-900/50 rounded-xl">
                                    <span class="text-xl">🐦</span>
                                    <div>
                                        <div class="font-semibold text-gray-900 dark:text-white text-sm">Twitter</div>
                                        <div class="text-xs text-gray-500">อัพเดทข่าวสาร</div>
                                    </div>
                                </div>
                                <div class="flex items-center gap-3 p-3 bg-gray-50 dark:bg-gray-900/50 rounded-xl">
                                    <span class="text-xl">✈️</span>
                                    <div>
                                        <div class="font-semibold text-gray-900 dark:text-white text-sm">Telegram</div>
                                        <div class="text-xs text-gray-500">Community Chat</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Step 4 --}}
            <div class="tp-card rounded-3xl overflow-hidden border border-gray-200 dark:border-gray-700">
                <div class="p-6 bg-gradient-to-r from-green-500 to-emerald-500">
                    <div class="flex items-center gap-4">
                        <div class="w-16 h-16 bg-white/20 backdrop-blur rounded-2xl flex items-center justify-center text-3xl text-white">
                            🚀
                        </div>
                        <div class="text-white">
                            <div class="text-sm font-medium text-white/80">ขั้นตอนที่ 4</div>
                            <h3 class="text-2xl font-bold">ตรวจสอบและสร้าง Token</h3>
                        </div>
                    </div>
                </div>
                <div class="p-8">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                        <div>
                            <h4 class="font-bold text-gray-900 dark:text-white mb-4">✅ ตรวจสอบข้อมูล</h4>
                            <ul class="space-y-3">
                                <li class="flex items-center gap-3 text-gray-600 dark:text-gray-400">
                                    <span class="w-6 h-6 bg-green-100 dark:bg-green-900/30 rounded-full flex items-center justify-center text-green-600">✓</span>
                                    <span>ชื่อและ Symbol ถูกต้อง</span>
                                </li>
                                <li class="flex items-center gap-3 text-gray-600 dark:text-gray-400">
                                    <span class="w-6 h-6 bg-green-100 dark:bg-green-900/30 rounded-full flex items-center justify-center text-green-600">✓</span>
                                    <span>Total Supply เหมาะสม</span>
                                </li>
                                <li class="flex items-center gap-3 text-gray-600 dark:text-gray-400">
                                    <span class="w-6 h-6 bg-green-100 dark:bg-green-900/30 rounded-full flex items-center justify-center text-green-600">✓</span>
                                    <span>คุณสมบัติที่เลือกตรงความต้องการ</span>
                                </li>
                                <li class="flex items-center gap-3 text-gray-600 dark:text-gray-400">
                                    <span class="w-6 h-6 bg-green-100 dark:bg-green-900/30 rounded-full flex items-center justify-center text-green-600">✓</span>
                                    <span>ราคาเริ่มต้นสมเหตุสมผล</span>
                                </li>
                            </ul>
                        </div>
                        <div>
                            <h4 class="font-bold text-gray-900 dark:text-white mb-4">📋 หลังจากสร้างแล้ว</h4>
                            <div class="space-y-3">
                                <div class="p-4 bg-blue-50 dark:bg-blue-900/20 rounded-xl border border-blue-200 dark:border-blue-800">
                                    <div class="font-semibold text-blue-900 dark:text-blue-100 mb-1">1. รอการอนุมัติ</div>
                                    <p class="text-sm text-blue-800 dark:text-blue-200">ทีมงานจะตรวจสอบ Token ของคุณ</p>
                                </div>
                                <div class="p-4 bg-purple-50 dark:bg-purple-900/20 rounded-xl border border-purple-200 dark:border-purple-800">
                                    <div class="font-semibold text-purple-900 dark:text-purple-100 mb-1">2. Deploy ลง Blockchain</div>
                                    <p class="text-sm text-purple-800 dark:text-purple-200">Smart Contract จะถูกสร้างอัตโนมัติ</p>
                                </div>
                                <div class="p-4 bg-green-50 dark:bg-green-900/20 rounded-xl border border-green-200 dark:border-green-800">
                                    <div class="font-semibold text-green-900 dark:text-green-100 mb-1">3. พร้อมใช้งาน!</div>
                                    <p class="text-sm text-green-800 dark:text-green-200">เริ่มแจก/ขาย Token ได้เลย</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- CTA --}}
            <div class="bg-gradient-to-r from-green-500 to-emerald-500 rounded-3xl p-8 text-center text-white">
                <h3 class="text-2xl font-bold mb-4">พร้อมสร้างเหรียญของคุณแล้วหรือยัง?</h3>
                <p class="text-white/80 mb-6">ขั้นตอนง่าย ๆ ใช้เวลาไม่ถึง 5 นาที!</p>
                <a href="{{ route('user.tokens.create') }}"
                   class="inline-flex items-center gap-2 px-8 py-4 bg-white text-green-600 font-bold rounded-xl hover:bg-gray-100 transition shadow-lg">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                    เริ่มสร้าง Token เลย!
                </a>
            </div>
        </div>

        {{-- Tab: Features --}}
        <div x-show="activeTab === 'features'" x-transition class="space-y-8">
            <div class="tp-card rounded-3xl p-8 border border-gray-200 dark:border-gray-700">
                <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-6">⚙️ คุณสมบัติ Token ที่เลือกได้</h2>

                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-gray-200 dark:border-gray-700">
                                <th class="text-left py-4 px-4 font-semibold text-gray-900 dark:text-white">คุณสมบัติ</th>
                                <th class="text-left py-4 px-4 font-semibold text-gray-900 dark:text-white">คำอธิบาย</th>
                                <th class="text-left py-4 px-4 font-semibold text-gray-900 dark:text-white">เหมาะกับ</th>
                                <th class="text-center py-4 px-4 font-semibold text-gray-900 dark:text-white">แนะนำ</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-900/50">
                                <td class="py-4 px-4">
                                    <span class="inline-flex items-center gap-2">
                                        <span class="px-2 py-1 bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-300 rounded font-mono text-xs">Mintable</span>
                                    </span>
                                </td>
                                <td class="py-4 px-4 text-gray-600 dark:text-gray-400">สร้าง Token เพิ่มได้</td>
                                <td class="py-4 px-4 text-gray-600 dark:text-gray-400">ระบบรางวัล, Gaming</td>
                                <td class="py-4 px-4 text-center">⚠️</td>
                            </tr>
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-900/50">
                                <td class="py-4 px-4">
                                    <span class="inline-flex items-center gap-2">
                                        <span class="px-2 py-1 bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-300 rounded font-mono text-xs">Burnable</span>
                                    </span>
                                </td>
                                <td class="py-4 px-4 text-gray-600 dark:text-gray-400">ทำลาย Token ได้</td>
                                <td class="py-4 px-4 text-gray-600 dark:text-gray-400">Deflationary Token</td>
                                <td class="py-4 px-4 text-center">✅</td>
                            </tr>
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-900/50">
                                <td class="py-4 px-4">
                                    <span class="inline-flex items-center gap-2">
                                        <span class="px-2 py-1 bg-yellow-100 dark:bg-yellow-900/30 text-yellow-700 dark:text-yellow-300 rounded font-mono text-xs">Pausable</span>
                                    </span>
                                </td>
                                <td class="py-4 px-4 text-gray-600 dark:text-gray-400">หยุดการโอนทั้งหมด</td>
                                <td class="py-4 px-4 text-gray-600 dark:text-gray-400">กรณีฉุกเฉิน</td>
                                <td class="py-4 px-4 text-center">⚠️</td>
                            </tr>
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-900/50">
                                <td class="py-4 px-4">
                                    <span class="inline-flex items-center gap-2">
                                        <span class="px-2 py-1 bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300 rounded font-mono text-xs">Freezable</span>
                                    </span>
                                </td>
                                <td class="py-4 px-4 text-gray-600 dark:text-gray-400">Freeze Address เฉพาะ</td>
                                <td class="py-4 px-4 text-gray-600 dark:text-gray-400">Security, Compliance</td>
                                <td class="py-4 px-4 text-center">⚠️</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="mt-6 p-4 bg-yellow-50 dark:bg-yellow-900/20 rounded-xl border border-yellow-200 dark:border-yellow-800">
                    <p class="text-sm text-yellow-800 dark:text-yellow-200">
                        <strong>⚠️ หมายเหตุ:</strong> คุณสมบัติที่มี ⚠️ ควรใช้อย่างระมัดระวัง เพราะอาจส่งผลต่อความน่าเชื่อถือของ Token
                    </p>
                </div>
            </div>
        </div>

        {{-- Tab: FAQ --}}
        <div x-show="activeTab === 'faq'" x-transition class="space-y-4">
            <div class="tp-card rounded-3xl p-8 border border-gray-200 dark:border-gray-700">
                <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-6">❓ คำถามที่พบบ่อย</h2>

                <div class="space-y-4" x-data="{ openFaq: null }">
                    @foreach([
                        ['q' => 'ค่าใช้จ่ายในการสร้าง Token คือเท่าไหร่?', 'a' => 'ค่าสร้าง Token เริ่มต้นที่ 100 TPIX ขึ้นอยู่กับคุณสมบัติที่เลือก เช่น Mintable, Burnable จะมีค่าใช้จ่ายเพิ่มเติม ดูรายละเอียดในหน้าสร้าง Token'],
                        ['q' => 'ใช้เวลานานเท่าไหร่กว่า Token จะพร้อมใช้?', 'a' => 'หลังจากส่งข้อมูลแล้ว ทีมงานจะตรวจสอบภายใน 24-48 ชั่วโมง เมื่ออนุมัติแล้ว Token จะถูก Deploy ลง Blockchain ทันที'],
                        ['q' => 'สามารถแก้ไขข้อมูล Token หลังสร้างได้ไหม?', 'a' => 'ข้อมูลบางส่วนแก้ไขได้ เช่น Logo, Website, Social Links แต่ข้อมูลหลักอย่าง Total Supply, Symbol ไม่สามารถแก้ไขได้'],
                        ['q' => 'Token ของฉันจะซื้อขายได้ที่ไหน?', 'a' => 'เมื่อ Token พร้อมใช้งาน คุณสามารถสร้าง Liquidity Pool บน TPIX DEX เพื่อให้ผู้อื่นซื้อขายได้ทันที'],
                        ['q' => 'ฉันต้องเขียนโค้ดเองไหม?', 'a' => 'ไม่ต้อง! ระบบจะสร้าง Smart Contract ให้อัตโนมัติตามข้อมูลที่คุณกรอก คุณแค่กรอกฟอร์มและคลิกสร้าง'],
                        ['q' => 'มีการรับประกันความปลอดภัยไหม?', 'a' => 'Smart Contract ที่สร้างผ่านระบบเราใช้โค้ดมาตรฐาน ERC-20 ที่ผ่านการตรวจสอบแล้ว แต่คุณควรทดสอบบน Testnet ก่อนเสมอ']
                    ] as $index => $faq)
                    <div class="border border-gray-200 dark:border-gray-700 rounded-2xl overflow-hidden">
                        <button @click="openFaq = openFaq === {{ $index }} ? null : {{ $index }}"
                                class="w-full p-6 text-left flex items-center justify-between hover:bg-gray-50 dark:hover:bg-gray-900/50 transition">
                            <span class="font-semibold text-gray-900 dark:text-white">{{ $faq['q'] }}</span>
                            <svg class="w-5 h-5 text-gray-500 transition-transform" :class="openFaq === {{ $index }} ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                            </svg>
                        </button>
                        <div x-show="openFaq === {{ $index }}" x-collapse class="px-6 pb-6">
                            <p class="text-gray-600 dark:text-gray-400">{{ $faq['a'] }}</p>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>

            {{-- Need Help --}}
            <div class="bg-gradient-to-r from-blue-500 to-purple-500 rounded-3xl p-8 text-white text-center">
                <h3 class="text-xl font-bold mb-3">ยังมีคำถาม?</h3>
                <p class="text-white/80 mb-6">ทีมงานพร้อมช่วยเหลือคุณตลอด 24 ชั่วโมง</p>
                <div class="flex flex-wrap justify-center gap-4">
                    <a href="#" class="px-6 py-3 bg-white/20 backdrop-blur rounded-xl hover:bg-white/30 transition flex items-center gap-2">
                        <span>✈️</span> Telegram
                    </a>
                    <a href="#" class="px-6 py-3 bg-white/20 backdrop-blur rounded-xl hover:bg-white/30 transition flex items-center gap-2">
                        <span>💬</span> Discord
                    </a>
                    <a href="#" class="px-6 py-3 bg-white/20 backdrop-blur rounded-xl hover:bg-white/30 transition flex items-center gap-2">
                        <span>📧</span> Email
                    </a>
                </div>
            </div>
        </div>

    </div>
</div>
@endsection
