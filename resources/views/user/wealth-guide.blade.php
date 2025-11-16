@extends('layouts.user-arrow-x')

@section('title', 'เส้นทางเศรษฐี - คู่มือสู่ความร่ำรวย')

@section('content')
<div class="min-h-screen bg-gradient-to-br from-amber-50 via-yellow-50 to-orange-50 py-8">
    <div class="container mx-auto px-4 max-w-6xl">

        <!-- E-Book Cover -->
        <div class="relative bg-gradient-to-br from-yellow-400 via-amber-500 to-orange-600 rounded-3xl shadow-2xl overflow-hidden mb-8">
            <div class="absolute inset-0 bg-[url('data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMjAwIiBoZWlnaHQ9IjIwMCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48ZGVmcz48cGF0dGVybiBpZD0iZ3JpZCIgd2lkdGg9IjQwIiBoZWlnaHQ9IjQwIiBwYXR0ZXJuVW5pdHM9InVzZXJTcGFjZU9uVXNlIj48cGF0aCBkPSJNIDQwIDAgTCAwIDAgMCA0MCIgZmlsbD0ibm9uZSIgc3Ryb2tlPSJ3aGl0ZSIgc3Ryb2tlLW9wYWNpdHk9IjAuMSIgc3Ryb2tlLXdpZHRoPSIxIi8+PC9wYXR0ZXJuPjwvZGVmcz48cmVjdCB3aWR0aD0iMTAwJSIgaGVpZ2h0PSIxMDAlIiBmaWxsPSJ1cmwoI2dyaWQpIi8+PC9zdmc+')] opacity-30"></div>
            <div class="relative z-10 text-center py-16 px-8">
                <div class="text-8xl mb-6 animate-bounce">💰</div>
                <h1 class="text-6xl font-bold text-white mb-4 drop-shadow-2xl">เส้นทางเศรษฐี</h1>
                <p class="text-2xl text-yellow-100 mb-6 font-semibold">คู่มือสู่ความร่ำรวย ด้วยระบบ Affiliate ของเรา</p>
                <div class="flex justify-center gap-4 flex-wrap text-white">
                    <div class="bg-white/20 backdrop-blur-sm px-6 py-3 rounded-full border-2 border-white/50">
                        <span class="font-bold">📚 10 บทเรียน</span>
                    </div>
                    <div class="bg-white/20 backdrop-blur-sm px-6 py-3 rounded-full border-2 border-white/50">
                        <span class="font-bold">🎯 จากมือใหม่สู่แม่ทีม</span>
                    </div>
                    <div class="bg-white/20 backdrop-blur-sm px-6 py-3 rounded-full border-2 border-white/50">
                        <span class="font-bold">💎 5 ระดับยศ</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Navigation -->
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-6 mb-8">
            <h2 class="text-2xl font-bold text-gray-800 dark:text-white mb-4 flex items-center gap-2">
                <span>🧭</span> สารบัญด่วน
            </h2>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
                <a href="#chapter-1" class="flex items-center gap-3 p-4 rounded-xl bg-gradient-to-r from-blue-50 to-blue-100 hover:from-blue-100 hover:to-blue-200 transition-all transform hover:scale-105 group">
                    <span class="text-3xl group-hover:scale-110 transition-transform">🚀</span>
                    <div>
                        <div class="font-bold text-blue-800">บทที่ 1</div>
                        <div class="text-sm text-blue-600">เริ่มต้นอย่างไร</div>
                    </div>
                </a>
                <a href="#chapter-2" class="flex items-center gap-3 p-4 rounded-xl bg-gradient-to-r from-purple-50 to-purple-100 hover:from-purple-100 hover:to-purple-200 transition-all transform hover:scale-105 group">
                    <span class="text-3xl group-hover:scale-110 transition-transform">💡</span>
                    <div>
                        <div class="font-bold text-purple-800">บทที่ 2</div>
                        <div class="text-sm text-purple-600">ระบบทำงานอย่างไร</div>
                    </div>
                </a>
                <a href="#chapter-3" class="flex items-center gap-3 p-4 rounded-xl bg-gradient-to-r from-green-50 to-green-100 hover:from-green-100 hover:to-green-200 transition-all transform hover:scale-105 group">
                    <span class="text-3xl group-hover:scale-110 transition-transform">💰</span>
                    <div>
                        <div class="font-bold text-green-800">บทที่ 3</div>
                        <div class="text-sm text-green-600">4 ช่องทางรายได้</div>
                    </div>
                </a>
                <a href="#chapter-4" class="flex items-center gap-3 p-4 rounded-xl bg-gradient-to-r from-yellow-50 to-yellow-100 hover:from-yellow-100 hover:to-yellow-200 transition-all transform hover:scale-105 group">
                    <span class="text-3xl group-hover:scale-110 transition-transform">⭐</span>
                    <div>
                        <div class="font-bold text-yellow-800">บทที่ 4</div>
                        <div class="text-sm text-yellow-600">ระบบยศและอันดับ</div>
                    </div>
                </a>
                <a href="#chapter-5" class="flex items-center gap-3 p-4 rounded-xl bg-gradient-to-r from-pink-50 to-pink-100 hover:from-pink-100 hover:to-pink-200 transition-all transform hover:scale-105 group">
                    <span class="text-3xl group-hover:scale-110 transition-transform">👥</span>
                    <div>
                        <div class="font-bold text-pink-800">บทที่ 5</div>
                        <div class="text-sm text-pink-600">สร้างทีมที่แข็งแกร่ง</div>
                    </div>
                </a>
                <a href="#chapter-6" class="flex items-center gap-3 p-4 rounded-xl bg-gradient-to-r from-indigo-50 to-indigo-100 hover:from-indigo-100 hover:to-indigo-200 transition-all transform hover:scale-105 group">
                    <span class="text-3xl group-hover:scale-110 transition-transform">📈</span>
                    <div>
                        <div class="font-bold text-indigo-800">บทที่ 6</div>
                        <div class="text-sm text-indigo-600">กลยุทธ์ขายที่ได้ผล</div>
                    </div>
                </a>
                <a href="#chapter-7" class="flex items-center gap-3 p-4 rounded-xl bg-gradient-to-r from-red-50 to-red-100 hover:from-red-100 hover:to-red-200 transition-all transform hover:scale-105 group">
                    <span class="text-3xl group-hover:scale-110 transition-transform">💳</span>
                    <div>
                        <div class="font-bold text-red-800">บทที่ 7</div>
                        <div class="text-sm text-red-600">กระเป๋าเงินและถอนเงิน</div>
                    </div>
                </a>
                <a href="#chapter-8" class="flex items-center gap-3 p-4 rounded-xl bg-gradient-to-r from-teal-50 to-teal-100 hover:from-teal-100 hover:to-teal-200 transition-all transform hover:scale-105 group">
                    <span class="text-3xl group-hover:scale-110 transition-transform">👑</span>
                    <div>
                        <div class="font-bold text-teal-800">บทที่ 8</div>
                        <div class="text-sm text-teal-600">เป็นแม่ทีมมืออาชีพ</div>
                    </div>
                </a>
                <a href="#chapter-9" class="flex items-center gap-3 p-4 rounded-xl bg-gradient-to-r from-orange-50 to-orange-100 hover:from-orange-100 hover:to-orange-200 transition-all transform hover:scale-105 group">
                    <span class="text-3xl group-hover:scale-110 transition-transform">🎓</span>
                    <div>
                        <div class="font-bold text-orange-800">บทที่ 9</div>
                        <div class="text-sm text-orange-600">เคล็ดลับและคำแนะนำ</div>
                    </div>
                </a>
                <a href="#chapter-10" class="flex items-center gap-3 p-4 rounded-xl bg-gradient-to-r from-cyan-50 to-cyan-100 hover:from-cyan-100 hover:to-cyan-200 transition-all transform hover:scale-105 group">
                    <span class="text-3xl group-hover:scale-110 transition-transform">❓</span>
                    <div>
                        <div class="font-bold text-cyan-800">บทที่ 10</div>
                        <div class="text-sm text-cyan-600">คำถามที่พบบ่อย</div>
                    </div>
                </a>
            </div>
        </div>

        <!-- Chapter 1: Getting Started -->
        <div id="chapter-1" class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-8 mb-8 scroll-mt-8">
            <div class="flex items-center gap-4 mb-6">
                <div class="text-6xl">🚀</div>
                <div>
                    <div class="text-sm text-blue-600 font-semibold">CHAPTER 1</div>
                    <h2 class="text-4xl font-bold bg-gradient-to-r from-blue-600 to-purple-600 bg-clip-text text-transparent">เริ่มต้นอย่างไร</h2>
                </div>
            </div>

            <div class="prose prose-lg max-w-none">
                <div class="bg-blue-50 border-l-4 border-blue-500 p-6 rounded-r-xl mb-6">
                    <h3 class="text-xl font-bold text-blue-900 mb-3">🎯 ยินดีต้อนรับสู่ระบบ Thaiprompt-Affiliate!</h3>
                    <p class="text-blue-800 leading-relaxed">
                        ระบบของเราคือโอกาสทองที่จะเปลี่ยนความพยายามของคุณให้กลายเป็นรายได้ที่มั่นคง
                        ไม่ว่าคุณจะเป็นมือใหม่หรือมืออาชีพ เรามีเครื่องมือและระบบที่จะช่วยให้คุณประสบความสำเร็จ
                    </p>
                </div>

                <h3 class="text-2xl font-bold text-gray-800 dark:text-white mb-4">📝 ขั้นตอนเริ่มต้น 5 ขั้นตอน</h3>

                <div class="space-y-4">
                    <div class="flex gap-4 p-5 bg-gradient-to-r from-blue-50 to-indigo-50 rounded-xl border-2 border-blue-200">
                        <div class="flex-shrink-0 w-12 h-12 bg-blue-600 text-white rounded-full flex items-center justify-center font-bold text-xl">1</div>
                        <div class="flex-1">
                            <h4 class="font-bold text-lg text-gray-800 dark:text-white mb-2">สมัครสมาชิก</h4>
                            <p class="text-gray-700 dark:text-gray-300">กรอกข้อมูลให้ครบถ้วน ใช้ลิงก์แนะนำจากผู้สปอนเซอร์ของคุณ (ถ้ามี) เพื่อเชื่อมโยงเข้ากับทีม</p>
                        </div>
                    </div>

                    <div class="flex gap-4 p-5 bg-gradient-to-r from-purple-50 to-pink-50 rounded-xl border-2 border-purple-200">
                        <div class="flex-shrink-0 w-12 h-12 bg-purple-600 text-white rounded-full flex items-center justify-center font-bold text-xl">2</div>
                        <div class="flex-1">
                            <h4 class="font-bold text-lg text-gray-800 dark:text-white mb-2">ยืนยันตัวตน (KYC)</h4>
                            <p class="text-gray-700 dark:text-gray-300">อัพโหลดบัตรประชาชนและรูปถ่ายเพื่อยืนยันตัวตน จะช่วยเพิ่มความน่าเชื่อถือและปลดล็อคฟีเจอร์เต็มรูปแบบ</p>
                        </div>
                    </div>

                    <div class="flex gap-4 p-5 bg-gradient-to-r from-green-50 to-emerald-50 rounded-xl border-2 border-green-200">
                        <div class="flex-shrink-0 w-12 h-12 bg-green-600 text-white rounded-full flex items-center justify-center font-bold text-xl">3</div>
                        <div class="flex-1">
                            <h4 class="font-bold text-lg text-gray-800 dark:text-white mb-2">ทำความเข้าใจระบบ</h4>
                            <p class="text-gray-700 dark:text-gray-300">อ่านคู่มือนี้ให้จบ ดูวิดีโอสอนใช้งาน และทดลองใช้เครื่องมือต่างๆ เช่น ตัวคำนวณรายได้</p>
                        </div>
                    </div>

                    <div class="flex gap-4 p-5 bg-gradient-to-r from-yellow-50 to-orange-50 rounded-xl border-2 border-yellow-200">
                        <div class="flex-shrink-0 w-12 h-12 bg-yellow-600 text-white rounded-full flex items-center justify-center font-bold text-xl">4</div>
                        <div class="flex-1">
                            <h4 class="font-bold text-lg text-gray-800 dark:text-white mb-2">เริ่มแชร์และขาย</h4>
                            <p class="text-gray-700 dark:text-gray-300">คัดลอกลิงก์แนะนำของคุณ แชร์ให้เพื่อนและคนรู้จัก เริ่มสร้างยอดขายแรกของคุณ</p>
                        </div>
                    </div>

                    <div class="flex gap-4 p-5 bg-gradient-to-r from-red-50 to-pink-50 rounded-xl border-2 border-red-200">
                        <div class="flex-shrink-0 w-12 h-12 bg-red-600 text-white rounded-full flex items-center justify-center font-bold text-xl">5</div>
                        <div class="flex-1">
                            <h4 class="font-bold text-lg text-gray-800 dark:text-white mb-2">สร้างทีมและเติบโต</h4>
                            <p class="text-gray-700 dark:text-gray-300">เชิญคนอื่นมาร่วมทีม สอนพวกเขาให้ประสบความสำเร็จ และรับค่าคอมจากทั้งทีม</p>
                        </div>
                    </div>
                </div>

                <div class="mt-8 bg-gradient-to-r from-amber-50 to-yellow-50 border-2 border-amber-300 rounded-xl p-6">
                    <h4 class="font-bold text-xl text-amber-900 mb-3 flex items-center gap-2">
                        <span>💡</span> เคล็ดลับสำหรับมือใหม่
                    </h4>
                    <ul class="space-y-2 text-amber-800">
                        <li class="flex items-start gap-2">
                            <span class="text-amber-600 font-bold mt-1">•</span>
                            <span><strong>เริ่มจากวงใกล้:</strong> ขายให้คนรู้จักก่อน เพราะพวกเขาเชื่อใจคุณ</span>
                        </li>
                        <li class="flex items-start gap-2">
                            <span class="text-amber-600 font-bold mt-1">•</span>
                            <span><strong>เรียนรู้ผลิตภัณฑ์:</strong> ทดลองใช้เองก่อนแนะนำผู้อื่น</span>
                        </li>
                        <li class="flex items-start gap-2">
                            <span class="text-amber-600 font-bold mt-1">•</span>
                            <span><strong>ตั้งเป้าหมาย:</strong> กำหนดเป้ารายได้รายเดือนและทำงานให้ถึงเป้า</span>
                        </li>
                        <li class="flex items-start gap-2">
                            <span class="text-amber-600 font-bold mt-1">•</span>
                            <span><strong>สม่ำเสมอคือกุญแจ:</strong> ขายทุกวันดีกว่าขายเยอะวันเดียว</span>
                        </li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- Chapter 2: How the System Works -->
        <div id="chapter-2" class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-8 mb-8 scroll-mt-8">
            <div class="flex items-center gap-4 mb-6">
                <div class="text-6xl">💡</div>
                <div>
                    <div class="text-sm text-purple-600 font-semibold">CHAPTER 2</div>
                    <h2 class="text-4xl font-bold bg-gradient-to-r from-purple-600 to-pink-600 bg-clip-text text-transparent">ระบบทำงานอย่างไร</h2>
                </div>
            </div>

            <div class="prose prose-lg max-w-none">
                <p class="text-xl text-gray-700 dark:text-gray-300 leading-relaxed mb-6">
                    ระบบของเราใช้โมเดล <strong>MLM (Multi-Level Marketing)</strong> แบบผสมผสาน
                    ที่ให้คุณได้รับค่าตอบแทนทั้งจากยอดขายตัวเองและจากทีมงาน
                </p>

                <h3 class="text-2xl font-bold text-gray-800 dark:text-white mb-4">🔄 2 ระบบหลัก</h3>

                <div class="grid md:grid-cols-2 gap-6 mb-8">
                    <div class="bg-gradient-to-br from-blue-50 to-blue-100 p-6 rounded-2xl border-2 border-blue-300">
                        <h4 class="text-2xl font-bold text-blue-900 mb-3 flex items-center gap-2">
                            <span>📊</span> 1. Unilevel (แนวตั้ง)
                        </h4>
                        <p class="text-blue-800 mb-4">คุณได้ค่าคอมจากยอดขายของทีมลูกสาขาลงไปหลายระดับ (ขึ้นอยู่กับแผน)</p>
                        <div class="bg-white dark:bg-gray-800 p-4 rounded-xl">
                            <div class="text-center mb-2">
                                <div class="inline-block bg-blue-600 text-white px-4 py-2 rounded-lg font-bold">คุณ</div>
                            </div>
                            <div class="text-center mb-2">
                                <div class="text-blue-600">↓ Level 1 (30%)</div>
                                <div class="flex justify-center gap-2 mb-2">
                                    <div class="bg-blue-400 text-white px-3 py-1 rounded text-sm">A</div>
                                    <div class="bg-blue-400 text-white px-3 py-1 rounded text-sm">B</div>
                                </div>
                            </div>
                            <div class="text-center">
                                <div class="text-blue-600">↓ Level 2 (20%)</div>
                                <div class="flex justify-center gap-2">
                                    <div class="bg-blue-300 text-white px-2 py-1 rounded text-xs">C</div>
                                    <div class="bg-blue-300 text-white px-2 py-1 rounded text-xs">D</div>
                                    <div class="bg-blue-300 text-white px-2 py-1 rounded text-xs">E</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="bg-gradient-to-br from-purple-50 to-purple-100 p-6 rounded-2xl border-2 border-purple-300">
                        <h4 class="text-2xl font-bold text-purple-900 mb-3 flex items-center gap-2">
                            <span>⚖️</span> 2. Binary (แนวคู่)
                        </h4>
                        <p class="text-purple-800 mb-4">คุณได้โบนัสเมื่อทีมซ้าย-ขวาจับคู่กัน (Pair Matching)</p>
                        <div class="bg-white dark:bg-gray-800 p-4 rounded-xl">
                            <div class="text-center mb-3">
                                <div class="inline-block bg-purple-600 text-white px-4 py-2 rounded-lg font-bold">คุณ</div>
                            </div>
                            <div class="flex justify-center gap-8">
                                <div class="text-center">
                                    <div class="text-purple-600 mb-2">ซ้าย</div>
                                    <div class="bg-purple-400 text-white px-4 py-2 rounded-lg mb-1">3 คน</div>
                                    <div class="text-sm text-purple-700">30,000 PV</div>
                                </div>
                                <div class="text-center">
                                    <div class="text-purple-600 mb-2">ขวา</div>
                                    <div class="bg-purple-400 text-white px-4 py-2 rounded-lg mb-1">5 คน</div>
                                    <div class="text-sm text-purple-700">50,000 PV</div>
                                </div>
                            </div>
                            <div class="mt-4 text-center bg-green-100 p-3 rounded-lg">
                                <div class="text-green-800 font-bold">✅ จับคู่ 3 คู่ = 3,000 บาท</div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="bg-gradient-to-r from-indigo-50 to-purple-50 border-2 border-indigo-300 rounded-xl p-6">
                    <h4 class="font-bold text-xl text-indigo-900 mb-4">📈 ตัวอย่างการคำนวณ</h4>
                    <div class="space-y-3 text-indigo-800">
                        <div class="bg-white dark:bg-gray-800 p-4 rounded-lg">
                            <div class="font-bold mb-2">สมมติ: ลูกทีม Level 1 ขายได้ 10,000 บาท</div>
                            <div class="ml-4">
                                <div>💰 คุณได้ค่าคอม Unilevel = 10,000 × 30% = <strong class="text-green-600">3,000 บาท</strong></div>
                            </div>
                        </div>
                        <div class="bg-white dark:bg-gray-800 p-4 rounded-lg">
                            <div class="font-bold mb-2">สมมติ: ทีมซ้าย-ขวาจับคู่กันได้ 5 คู่</div>
                            <div class="ml-4">
                                <div>💰 คุณได้โบนัส Binary = 5 × 1,000 = <strong class="text-green-600">5,000 บาท</strong></div>
                            </div>
                        </div>
                        <div class="bg-gradient-to-r from-green-400 to-emerald-500 text-white p-4 rounded-lg font-bold text-lg">
                            🎉 รวมรายได้ = 3,000 + 5,000 = <span class="text-2xl">8,000 บาท</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Chapter 3: Income Streams -->
        <div id="chapter-3" class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-8 mb-8 scroll-mt-8">
            <div class="flex items-center gap-4 mb-6">
                <div class="text-6xl">💰</div>
                <div>
                    <div class="text-sm text-green-600 font-semibold">CHAPTER 3</div>
                    <h2 class="text-4xl font-bold bg-gradient-to-r from-green-600 to-emerald-600 bg-clip-text text-transparent">4 ช่องทางรายได้</h2>
                </div>
            </div>

            <div class="prose prose-lg max-w-none">
                <p class="text-xl text-gray-700 dark:text-gray-300 leading-relaxed mb-6">
                    คุณสามารถสร้างรายได้จาก <strong>4 ช่องทางหลัก</strong> ที่ทำงานไปพร้อมกัน
                    ยิ่งคุณพัฒนาทีมมากเท่าไร รายได้ก็จะเพิ่มขึ้นทวีคูณ!
                </p>

                <div class="grid gap-6 mb-8">
                    <!-- Income Stream 1 -->
                    <div class="bg-gradient-to-r from-blue-50 to-cyan-50 border-2 border-blue-300 rounded-2xl p-6">
                        <div class="flex items-start gap-4">
                            <div class="text-5xl">📊</div>
                            <div class="flex-1">
                                <h3 class="text-2xl font-bold text-blue-900 mb-3">1. ค่าคอมมิชชั่นตรง (Direct Commission)</h3>
                                <p class="text-blue-800 mb-4">รับค่าคอมทันทีจากยอดขายของลูกทีมโดยตรง ตามระบบ Unilevel</p>

                                <div class="bg-white dark:bg-gray-800 p-4 rounded-xl mb-4">
                                    <h4 class="font-bold text-blue-900 mb-3">อัตราค่าคอมตามระดับ:</h4>
                                    <div class="space-y-2">
                                        <div class="flex justify-between items-center p-3 bg-blue-50 rounded-lg">
                                            <span class="font-semibold">Level 1 (ลูกทีมตรง)</span>
                                            <span class="font-bold text-blue-600">30%</span>
                                        </div>
                                        <div class="flex justify-between items-center p-3 bg-blue-50 rounded-lg">
                                            <span class="font-semibold">Level 2 (ลูกของลูก)</span>
                                            <span class="font-bold text-blue-600">20%</span>
                                        </div>
                                        <div class="flex justify-between items-center p-3 bg-blue-50 rounded-lg">
                                            <span class="font-semibold">Level 3-5</span>
                                            <span class="font-bold text-blue-600">10-15%</span>
                                        </div>
                                    </div>
                                </div>

                                <div class="bg-green-100 border-l-4 border-green-600 p-4 rounded-r-lg">
                                    <div class="font-bold text-green-900 mb-2">💡 ตัวอย่าง:</div>
                                    <div class="text-green-800">
                                        ลูกทีม Level 1 จำนวน 10 คน ขายคนละ 5,000 บาท/เดือน<br>
                                        <strong>รายได้ของคุณ = 10 × 5,000 × 30% = 15,000 บาท/เดือน</strong>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Income Stream 2 -->
                    <div class="bg-gradient-to-r from-purple-50 to-pink-50 border-2 border-purple-300 rounded-2xl p-6">
                        <div class="flex items-start gap-4">
                            <div class="text-5xl">⚖️</div>
                            <div class="flex-1">
                                <h3 class="text-2xl font-bold text-purple-900 mb-3">2. โบนัสจับคู่ (Binary Matching Bonus)</h3>
                                <p class="text-purple-800 mb-4">รับโบนัสพิเศษเมื่อทีมซ้าย-ขวาของคุณจับคู่กัน</p>

                                <div class="bg-white dark:bg-gray-800 p-4 rounded-xl mb-4">
                                    <h4 class="font-bold text-purple-900 mb-3">วิธีการคำนวณ:</h4>
                                    <div class="space-y-3">
                                        <div class="p-3 bg-purple-50 rounded-lg">
                                            <div class="font-semibold mb-1">💎 ค่าโบนัสต่อคู่</div>
                                            <div class="text-purple-700">1,000 บาท/คู่ (หรือตามแผนของคุณ)</div>
                                        </div>
                                        <div class="p-3 bg-purple-50 rounded-lg">
                                            <div class="font-semibold mb-1">📊 PV ขั้นต่ำ</div>
                                            <div class="text-purple-700">ต้องมี PV อย่างน้อย 10,000 ทั้งสองข้าง</div>
                                        </div>
                                        <div class="p-3 bg-purple-50 rounded-lg">
                                            <div class="font-semibold mb-1">⚡ จำกัดต่อวัน</div>
                                            <div class="text-purple-700">สูงสุด 10 คู่/วัน (หรือตามแผน)</div>
                                        </div>
                                    </div>
                                </div>

                                <div class="bg-green-100 border-l-4 border-green-600 p-4 rounded-r-lg">
                                    <div class="font-bold text-green-900 mb-2">💡 ตัวอย่าง:</div>
                                    <div class="text-green-800">
                                        ทีมซ้าย 50,000 PV | ทีมขวา 30,000 PV = จับคู่ได้ 3 คู่<br>
                                        <strong>รายได้ = 3 × 1,000 = 3,000 บาท/วัน</strong>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Income Stream 3 -->
                    <div class="bg-gradient-to-r from-yellow-50 to-orange-50 border-2 border-yellow-300 rounded-2xl p-6">
                        <div class="flex items-start gap-4">
                            <div class="text-5xl">⭐</div>
                            <div class="flex-1">
                                <h3 class="text-2xl font-bold text-yellow-900 mb-3">3. โบนัสยศ (Rank Bonus)</h3>
                                <p class="text-yellow-800 mb-4">รับโบนัสพิเศษเมื่อขึ้นยศใหม่ และรายได้ประจำทุกเดือน</p>

                                <div class="bg-white dark:bg-gray-800 p-4 rounded-xl mb-4">
                                    <h4 class="font-bold text-yellow-900 mb-3">โบนัสตามยศ:</h4>
                                    <div class="space-y-2">
                                        <div class="flex justify-between items-center p-3 bg-gradient-to-r from-gray-100 to-gray-200 rounded-lg">
                                            <span class="font-semibold">🥉 Bronze</span>
                                            <span class="font-bold text-gray-700 dark:text-gray-300">5,000 บาท (ครั้งเดียว) + 500 บาท/เดือน</span>
                                        </div>
                                        <div class="flex justify-between items-center p-3 bg-gradient-to-r from-gray-300 to-gray-400 rounded-lg">
                                            <span class="font-semibold">🥈 Silver</span>
                                            <span class="font-bold text-gray-800 dark:text-white">15,000 บาท (ครั้งเดียว) + 2,000 บาท/เดือน</span>
                                        </div>
                                        <div class="flex justify-between items-center p-3 bg-gradient-to-r from-yellow-200 to-yellow-400 rounded-lg">
                                            <span class="font-semibold">🥇 Gold</span>
                                            <span class="font-bold text-yellow-900">50,000 บาท (ครั้งเดียว) + 10,000 บาท/เดือน</span>
                                        </div>
                                        <div class="flex justify-between items-center p-3 bg-gradient-to-r from-cyan-200 to-blue-400 rounded-lg">
                                            <span class="font-semibold">💎 Platinum</span>
                                            <span class="font-bold text-blue-900">150,000 บาท (ครั้งเดียว) + 30,000 บาท/เดือน</span>
                                        </div>
                                        <div class="flex justify-between items-center p-3 bg-gradient-to-r from-purple-400 to-pink-500 rounded-lg">
                                            <span class="font-semibold text-white">💎 Diamond</span>
                                            <span class="font-bold text-white">500,000 บาท (ครั้งเดียว) + 100,000 บาท/เดือน</span>
                                        </div>
                                    </div>
                                </div>

                                <div class="bg-green-100 border-l-4 border-green-600 p-4 rounded-r-lg">
                                    <div class="font-bold text-green-900 mb-2">💡 ข้อดี:</div>
                                    <div class="text-green-800">
                                        โบนัสยศช่วยให้คุณมีรายได้ประจำที่มั่นคง แม้ในช่วงที่ยอดขายน้อย
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Income Stream 4 -->
                    <div class="bg-gradient-to-r from-pink-50 to-rose-50 border-2 border-pink-300 rounded-2xl p-6">
                        <div class="flex items-start gap-4">
                            <div class="text-5xl">🎁</div>
                            <div class="flex-1">
                                <h3 class="text-2xl font-bold text-pink-900 mb-3">4. โบนัสสปอนเซอร์ (Sponsorship Bonus)</h3>
                                <p class="text-pink-800 mb-4">รับโบนัสพิเศษเมื่อเชิญคนใหม่เข้ามาซื้อสินค้า</p>

                                <div class="bg-white dark:bg-gray-800 p-4 rounded-xl mb-4">
                                    <h4 class="font-bold text-pink-900 mb-3">อัตราโบนัส:</h4>
                                    <div class="space-y-2">
                                        <div class="p-3 bg-pink-50 rounded-lg">
                                            <div class="font-semibold mb-1">💰 โบนัสต่อคน</div>
                                            <div class="text-pink-700">10-20% ของยอดซื้อครั้งแรก</div>
                                        </div>
                                        <div class="p-3 bg-pink-50 rounded-lg">
                                            <div class="font-semibold mb-1">🔥 โปรโมชั่นพิเศษ</div>
                                            <div class="text-pink-700">บางช่วงอาจมีโบนัสเพิ่ม เช่น เชิญ 5 คน รับเพิ่ม 5,000 บาท</div>
                                        </div>
                                    </div>
                                </div>

                                <div class="bg-green-100 border-l-4 border-green-600 p-4 rounded-r-lg">
                                    <div class="font-bold text-green-900 mb-2">💡 ตัวอย่าง:</div>
                                    <div class="text-green-800">
                                        เชิญเพื่อน 10 คน ซื้อสินค้าคนละ 10,000 บาท<br>
                                        <strong>รายได้ = 10 × 10,000 × 15% = 15,000 บาท</strong>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Total Income Example -->
                <div class="bg-gradient-to-br from-green-400 via-emerald-500 to-teal-600 text-white rounded-2xl p-8 shadow-2xl">
                    <h3 class="text-3xl font-bold mb-6 text-center">🎯 ตัวอย่างรายได้รวม 1 เดือน</h3>
                    <div class="grid md:grid-cols-2 gap-4 mb-6">
                        <div class="bg-white/20 backdrop-blur-sm p-4 rounded-xl">
                            <div class="text-sm mb-1">Direct Commission</div>
                            <div class="text-2xl font-bold">15,000 บาท</div>
                        </div>
                        <div class="bg-white/20 backdrop-blur-sm p-4 rounded-xl">
                            <div class="text-sm mb-1">Binary Bonus (10 คู่/วัน)</div>
                            <div class="text-2xl font-bold">300,000 บาท</div>
                        </div>
                        <div class="bg-white/20 backdrop-blur-sm p-4 rounded-xl">
                            <div class="text-sm mb-1">Rank Bonus (Gold)</div>
                            <div class="text-2xl font-bold">10,000 บาท</div>
                        </div>
                        <div class="bg-white/20 backdrop-blur-sm p-4 rounded-xl">
                            <div class="text-sm mb-1">Sponsorship Bonus</div>
                            <div class="text-2xl font-bold">5,000 บาท</div>
                        </div>
                    </div>
                    <div class="text-center bg-white/30 backdrop-blur-sm p-6 rounded-xl">
                        <div class="text-xl mb-2">💰 รายได้รวมทั้งหมด</div>
                        <div class="text-6xl font-bold">330,000 บาท</div>
                        <div class="text-sm mt-2 opacity-90">*ตัวเลขขึ้นอยู่กับยอดขายและทีมงานของคุณ</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Chapter 4: Rank System -->
        <div id="chapter-4" class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-8 mb-8 scroll-mt-8">
            <div class="flex items-center gap-4 mb-6">
                <div class="text-6xl">⭐</div>
                <div>
                    <div class="text-sm text-yellow-600 font-semibold">CHAPTER 4</div>
                    <h2 class="text-4xl font-bold bg-gradient-to-r from-yellow-600 to-orange-600 bg-clip-text text-transparent">ระบบยศและอันดับ</h2>
                </div>
            </div>

            <div class="prose prose-lg max-w-none">
                <p class="text-xl text-gray-700 dark:text-gray-300 leading-relaxed mb-6">
                    ระบบยศช่วยให้คุณมีเป้าหมายที่ชัดเจน และรับสิทธิพิเศษมากขึ้นเมื่อคุณพัฒนาทีม
                </p>

                <div class="space-y-6">
                    <!-- Bronze -->
                    <div class="bg-gradient-to-r from-gray-100 to-gray-200 border-2 border-gray-400 rounded-2xl p-6">
                        <div class="flex items-start gap-4 mb-4">
                            <div class="text-5xl">🥉</div>
                            <div>
                                <h3 class="text-2xl font-bold text-gray-800 dark:text-white">Bronze (บรอนซ์)</h3>
                                <p class="text-gray-600 dark:text-gray-400">ยศเริ่มต้นสำหรับผู้มุ่งมั่น</p>
                            </div>
                        </div>
                        <div class="grid md:grid-cols-3 gap-4">
                            <div class="bg-white dark:bg-gray-800 p-4 rounded-xl">
                                <div class="font-semibold text-gray-700 dark:text-gray-300 mb-2">📊 เงื่อนไข</div>
                                <ul class="text-sm text-gray-600 dark:text-gray-400 space-y-1">
                                    <li>• ทีมอย่างน้อย 10 คน</li>
                                    <li>• ยอดขายทีม 50,000 บาท/เดือน</li>
                                    <li>• ลูกทีมตรง 3 คน</li>
                                </ul>
                            </div>
                            <div class="bg-white dark:bg-gray-800 p-4 rounded-xl">
                                <div class="font-semibold text-gray-700 dark:text-gray-300 mb-2">💰 รางวัล</div>
                                <ul class="text-sm text-gray-600 dark:text-gray-400 space-y-1">
                                    <li>• โบนัส 5,000 บาท (ครั้งเดียว)</li>
                                    <li>• รายได้ 500 บาท/เดือน</li>
                                    <li>• ค่าคอม +5%</li>
                                </ul>
                            </div>
                            <div class="bg-white dark:bg-gray-800 p-4 rounded-xl">
                                <div class="font-semibold text-gray-700 dark:text-gray-300 mb-2">🎁 สิทธิพิเศษ</div>
                                <ul class="text-sm text-gray-600 dark:text-gray-400 space-y-1">
                                    <li>• กรอบโปรไฟล์พิเศษ</li>
                                    <li>• เข้าร่วมกลุ่ม VIP</li>
                                    <li>• ใบประกาศนียบัตร</li>
                                </ul>
                            </div>
                        </div>
                    </div>

                    <!-- Silver -->
                    <div class="bg-gradient-to-r from-gray-300 to-gray-400 border-2 border-gray-500 rounded-2xl p-6">
                        <div class="flex items-start gap-4 mb-4">
                            <div class="text-5xl">🥈</div>
                            <div>
                                <h3 class="text-2xl font-bold text-gray-900 dark:text-white">Silver (ซิลเวอร์)</h3>
                                <p class="text-gray-700 dark:text-gray-300">ก้าวสู่ระดับกลาง มั่นคงขึ้น</p>
                            </div>
                        </div>
                        <div class="grid md:grid-cols-3 gap-4">
                            <div class="bg-white dark:bg-gray-800 p-4 rounded-xl">
                                <div class="font-semibold text-gray-700 dark:text-gray-300 mb-2">📊 เงื่อนไข</div>
                                <ul class="text-sm text-gray-600 dark:text-gray-400 space-y-1">
                                    <li>• ทีมอย่างน้อย 30 คน</li>
                                    <li>• ยอดขายทีม 150,000 บาท/เดือน</li>
                                    <li>• ลูกทีมตรง 5 คน</li>
                                    <li>• มีลูกทีม Bronze 2 คน</li>
                                </ul>
                            </div>
                            <div class="bg-white dark:bg-gray-800 p-4 rounded-xl">
                                <div class="font-semibold text-gray-700 dark:text-gray-300 mb-2">💰 รางวัล</div>
                                <ul class="text-sm text-gray-600 dark:text-gray-400 space-y-1">
                                    <li>• โบนัส 15,000 บาท (ครั้งเดียว)</li>
                                    <li>• รายได้ 2,000 บาท/เดือน</li>
                                    <li>• ค่าคอม +10%</li>
                                    <li>• Multiplier 1.2x</li>
                                </ul>
                            </div>
                            <div class="bg-white dark:bg-gray-800 p-4 rounded-xl">
                                <div class="font-semibold text-gray-700 dark:text-gray-300 mb-2">🎁 สิทธิพิเศษ</div>
                                <ul class="text-sm text-gray-600 dark:text-gray-400 space-y-1">
                                    <li>• กรอบโปรไฟล์เงิน</li>
                                    <li>• เครื่องมือวิเคราะห์ขั้นสูง</li>
                                    <li>• ส่วนลดสินค้า 10%</li>
                                    <li>• เข้าอบรมฟรี</li>
                                </ul>
                            </div>
                        </div>
                    </div>

                    <!-- Gold -->
                    <div class="bg-gradient-to-r from-yellow-200 via-yellow-400 to-yellow-500 border-2 border-yellow-600 rounded-2xl p-6 shadow-xl">
                        <div class="flex items-start gap-4 mb-4">
                            <div class="text-5xl">🥇</div>
                            <div>
                                <h3 class="text-2xl font-bold text-yellow-900">Gold (ทอง)</h3>
                                <p class="text-yellow-800">ระดับมืออาชีพ รายได้มั่นคง</p>
                            </div>
                        </div>
                        <div class="grid md:grid-cols-3 gap-4">
                            <div class="bg-white dark:bg-gray-800 p-4 rounded-xl">
                                <div class="font-semibold text-gray-700 dark:text-gray-300 mb-2">📊 เงื่อนไข</div>
                                <ul class="text-sm text-gray-600 dark:text-gray-400 space-y-1">
                                    <li>• ทีมอย่างน้อย 100 คน</li>
                                    <li>• ยอดขายทีม 500,000 บาท/เดือน</li>
                                    <li>• ลูกทีมตรง 10 คน</li>
                                    <li>• มีลูกทีม Silver 3 คน</li>
                                </ul>
                            </div>
                            <div class="bg-white dark:bg-gray-800 p-4 rounded-xl">
                                <div class="font-semibold text-gray-700 dark:text-gray-300 mb-2">💰 รางวัล</div>
                                <ul class="text-sm text-gray-600 dark:text-gray-400 space-y-1">
                                    <li>• โบนัส 50,000 บาท (ครั้งเดียว)</li>
                                    <li>• รายได้ 10,000 บาท/เดือน</li>
                                    <li>• ค่าคอม +15%</li>
                                    <li>• Multiplier 1.5x</li>
                                </ul>
                            </div>
                            <div class="bg-white dark:bg-gray-800 p-4 rounded-xl">
                                <div class="font-semibold text-gray-700 dark:text-gray-300 mb-2">🎁 สิทธิพิเศษ</div>
                                <ul class="text-sm text-gray-600 dark:text-gray-400 space-y-1">
                                    <li>• กรอบโปรไฟล์ทอง</li>
                                    <li>• ทริปต่างประเทศ</li>
                                    <li>• ส่วนลดสินค้า 20%</li>
                                    <li>• โค้ชส่วนตัว</li>
                                </ul>
                            </div>
                        </div>
                    </div>

                    <!-- Platinum -->
                    <div class="bg-gradient-to-r from-cyan-200 via-blue-400 to-indigo-500 border-2 border-blue-600 rounded-2xl p-6 shadow-2xl">
                        <div class="flex items-start gap-4 mb-4">
                            <div class="text-5xl">💎</div>
                            <div>
                                <h3 class="text-2xl font-bold text-white drop-shadow-lg">Platinum (แพลทินัม)</h3>
                                <p class="text-blue-100">ผู้นำระดับสูง ทีมแข็งแกร่ง</p>
                            </div>
                        </div>
                        <div class="grid md:grid-cols-3 gap-4">
                            <div class="bg-white dark:bg-gray-800 p-4 rounded-xl">
                                <div class="font-semibold text-gray-700 dark:text-gray-300 mb-2">📊 เงื่อนไข</div>
                                <ul class="text-sm text-gray-600 dark:text-gray-400 space-y-1">
                                    <li>• ทีมอย่างน้อย 300 คน</li>
                                    <li>• ยอดขายทีม 1,500,000 บาท/เดือน</li>
                                    <li>• ลูกทีมตรง 15 คน</li>
                                    <li>• มีลูกทีม Gold 5 คน</li>
                                </ul>
                            </div>
                            <div class="bg-white dark:bg-gray-800 p-4 rounded-xl">
                                <div class="font-semibold text-gray-700 dark:text-gray-300 mb-2">💰 รางวัล</div>
                                <ul class="text-sm text-gray-600 dark:text-gray-400 space-y-1">
                                    <li>• โบนัส 150,000 บาท (ครั้งเดียว)</li>
                                    <li>• รายได้ 30,000 บาท/เดือน</li>
                                    <li>• ค่าคอม +20%</li>
                                    <li>• Multiplier 2.0x</li>
                                </ul>
                            </div>
                            <div class="bg-white dark:bg-gray-800 p-4 rounded-xl">
                                <div class="font-semibold text-gray-700 dark:text-gray-300 mb-2">🎁 สิทธิพิเศษ</div>
                                <ul class="text-sm text-gray-600 dark:text-gray-400 space-y-1">
                                    <li>• กรอบโปรไฟล์แพลทินัม</li>
                                    <li>• ทริปหรูทั่วโลก</li>
                                    <li>• ส่วนลดสินค้า 30%</li>
                                    <li>• เข้าร่วมตัดสินใจบริษัท</li>
                                </ul>
                            </div>
                        </div>
                    </div>

                    <!-- Diamond -->
                    <div class="bg-gradient-to-br from-purple-400 via-pink-500 to-rose-600 border-2 border-pink-700 rounded-2xl p-6 shadow-2xl">
                        <div class="flex items-start gap-4 mb-4">
                            <div class="text-5xl">💎✨</div>
                            <div>
                                <h3 class="text-2xl font-bold text-white drop-shadow-lg">Diamond (เพชร)</h3>
                                <p class="text-pink-100">ยอดของยอด ผู้นำสูงสุด</p>
                            </div>
                        </div>
                        <div class="grid md:grid-cols-3 gap-4">
                            <div class="bg-white dark:bg-gray-800 p-4 rounded-xl">
                                <div class="font-semibold text-gray-700 dark:text-gray-300 mb-2">📊 เงื่อนไข</div>
                                <ul class="text-sm text-gray-600 dark:text-gray-400 space-y-1">
                                    <li>• ทีมอย่างน้อย 1,000 คน</li>
                                    <li>• ยอดขายทีม 5,000,000 บาท/เดือน</li>
                                    <li>• ลูกทีมตรง 25 คน</li>
                                    <li>• มีลูกทีม Platinum 7 คน</li>
                                </ul>
                            </div>
                            <div class="bg-white dark:bg-gray-800 p-4 rounded-xl">
                                <div class="font-semibold text-gray-700 dark:text-gray-300 mb-2">💰 รางวัล</div>
                                <ul class="text-sm text-gray-600 dark:text-gray-400 space-y-1">
                                    <li>• โบนัส 500,000 บาท (ครั้งเดียว)</li>
                                    <li>• รายได้ 100,000 บาท/เดือน</li>
                                    <li>• ค่าคอม +25%</li>
                                    <li>• Multiplier 3.0x</li>
                                </ul>
                            </div>
                            <div class="bg-white dark:bg-gray-800 p-4 rounded-xl">
                                <div class="font-semibold text-gray-700 dark:text-gray-300 mb-2">🎁 สิทธิพิเศษ</div>
                                <ul class="text-sm text-gray-600 dark:text-gray-400 space-y-1">
                                    <li>• กรอบโปรไฟล์เพชร</li>
                                    <li>• รถยนต์หรู</li>
                                    <li>• ส่วนลดสินค้า 40%</li>
                                    <li>• หุ้นบริษัท</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mt-8 bg-gradient-to-r from-orange-50 to-red-50 border-2 border-orange-300 rounded-xl p-6">
                    <h4 class="font-bold text-xl text-orange-900 mb-4 flex items-center gap-2">
                        <span>🎯</span> เคล็ดลับการขึ้นยศเร็ว
                    </h4>
                    <ul class="space-y-2 text-orange-800">
                        <li class="flex items-start gap-2">
                            <span class="text-orange-600 font-bold mt-1">1.</span>
                            <span><strong>สอนลูกทีม:</strong> ช่วยลูกทีมของคุณขึ้นยศ พวกเขาจะช่วยดันคุณขึ้นไปด้วย</span>
                        </li>
                        <li class="flex items-start gap-2">
                            <span class="text-orange-600 font-bold mt-1">2.</span>
                            <span><strong>สร้างทีมกว้าง:</strong> อย่าพึ่งแค่คนเดียว กระจายทีมให้หลากหลาย</span>
                        </li>
                        <li class="flex items-start gap-2">
                            <span class="text-orange-600 font-bold mt-1">3.</span>
                            <span><strong>ทำยอดส่วนตัว:</strong> คุณต้องเป็นแบบอย่างที่ดี ขายด้วยตัวเองด้วย</span>
                        </li>
                        <li class="flex items-start gap-2">
                            <span class="text-orange-600 font-bold mt-1">4.</span>
                            <span><strong>ติดตามทุกวัน:</strong> เช็คความคืบหน้า กระตุ้นทีม และแก้ปัญหาทันที</span>
                        </li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- Chapter 5: Team Building -->
        <div id="chapter-5" class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-8 mb-8 scroll-mt-8">
            <div class="flex items-center gap-4 mb-6">
                <div class="text-6xl">👥</div>
                <div>
                    <div class="text-sm text-pink-600 font-semibold">CHAPTER 5</div>
                    <h2 class="text-4xl font-bold bg-gradient-to-r from-pink-600 to-rose-600 bg-clip-text text-transparent">สร้างทีมที่แข็งแกร่ง</h2>
                </div>
            </div>

            <div class="prose prose-lg max-w-none">
                <p class="text-xl text-gray-700 dark:text-gray-300 leading-relaxed mb-6">
                    ความสำเร็จในระบบ MLM ไม่ได้มาจากคุณคนเดียว แต่มาจาก<strong>ความแข็งแกร่งของทีม</strong>
                    ยิ่งทีมแข็งแกร่ง คุณก็ยิ่งร่ำรวย!
                </p>

                <h3 class="text-2xl font-bold text-gray-800 dark:text-white mb-4">🎯 7 ขั้นตอนสร้างทีมแข็งแกร่ง</h3>

                <div class="space-y-4">
                    <div class="bg-gradient-to-r from-blue-50 to-cyan-50 border-l-4 border-blue-500 p-6 rounded-r-xl">
                        <h4 class="text-xl font-bold text-blue-900 mb-3">1. 🎣 หาคนที่เหมาะสม</h4>
                        <p class="text-blue-800 mb-3">ไม่ใช่ทุกคนที่จะเหมาะกับระบบ ให้มองหา:</p>
                        <ul class="space-y-2 text-blue-700">
                            <li>✅ คนที่อยากหารายได้เสริม มีเวลา มีความมุ่งมั่น</li>
                            <li>✅ คนที่มีเครือข่ายกว้าง ชอบพูดคุย ชอบแนะนำ</li>
                            <li>✅ คนที่เรียนรู้ได้เร็ว พัฒนาตัวเองอยู่เสมอ</li>
                            <li>❌ หลีกเลี่ยงคนที่แค่อยากได้เงินด่วน ไม่อยากทำงาน</li>
                        </ul>
                    </div>

                    <div class="bg-gradient-to-r from-purple-50 to-pink-50 border-l-4 border-purple-500 p-6 rounded-r-xl">
                        <h4 class="text-xl font-bold text-purple-900 mb-3">2. 🎓 อบรมและถ่ายทอด</h4>
                        <p class="text-purple-800 mb-3">ลูกทีมต้องเก่งไม่แพ้คุณ:</p>
                        <ul class="space-y-2 text-purple-700">
                            <li>📚 จัดอบรมประจำสัปดาห์ สอนระบบ เทคนิคขาย</li>
                            <li>📱 สร้างกลุ่มไลน์หรือเฟซบุ๊ค แบ่งปันเคล็ดลับ</li>
                            <li>🎥 บันทึกวิดีโอสอน ให้ลูกทีมดูซ้ำได้</li>
                            <li>📝 ให้สคริปต์การขาย เทมเพลตโพสต์</li>
                        </ul>
                    </div>

                    <div class="bg-gradient-to-r from-green-50 to-emerald-50 border-l-4 border-green-500 p-6 rounded-r-xl">
                        <h4 class="text-xl font-bold text-green-900 mb-3">3. 💪 สนับสนุนและกระตุ้น</h4>
                        <p class="text-green-800 mb-3">อยู่เคียงข้างลูกทีมเสมอ:</p>
                        <ul class="space-y-2 text-green-700">
                            <li>🤝 ช่วยปิดการขายครั้งแรก เป็นกำลังใจ</li>
                            <li>📞 โทรเช็คความคืบหน้าสัปดาห์ละครั้ง</li>
                            <li>🏆 มอบรางวัลให้ลูกทีมที่ทำผลงานดี</li>
                            <li>❤️ ให้คำปรึกษา แก้ปัญหาอย่างจริงใจ</li>
                        </ul>
                    </div>

                    <div class="bg-gradient-to-r from-yellow-50 to-orange-50 border-l-4 border-yellow-500 p-6 rounded-r-xl">
                        <h4 class="text-xl font-bold text-yellow-900 mb-3">4. 📊 ติดตามและวิเคราะห์</h4>
                        <p class="text-yellow-800 mb-3">ใช้ข้อมูลวางแผน:</p>
                        <ul class="space-y-2 text-yellow-700">
                            <li>📈 ดู Dashboard ของทีมทุกวัน รู้ว่าใครทำดี ใครติดปัญหา</li>
                            <li>🎯 ตั้งเป้าหมายรายสัปดาห์ รายเดือน</li>
                            <li>🔍 วิเคราะห์ว่ากลยุทธ์ไหนได้ผล ไหนไม่ได้ผล</li>
                            <li>📉 หาสาเหตุที่ยอดขายลด แก้ไขทันที</li>
                        </ul>
                    </div>

                    <div class="bg-gradient-to-r from-red-50 to-pink-50 border-l-4 border-red-500 p-6 rounded-r-xl">
                        <h4 class="text-xl font-bold text-red-900 mb-3">5. 🌟 สร้างวัฒนธรรมทีม</h4>
                        <p class="text-red-800 mb-3">ทีมที่ดีต้องมีความรู้สึกเป็นครอบครัว:</p>
                        <ul class="space-y-2 text-red-700">
                            <li>🎉 จัดกิจกรรมทีมบิลดิ้ง ปาร์ตี้ฉลองความสำเร็จ</li>
                            <li>🏅 มีระบบยกย่องคนทำดี ให้เกียรติ</li>
                            <li>💬 สร้างบรรยากาศแห่งการช่วยเหลือซึ่งกันและกัน</li>
                            <li>🎯 มีวิสัยทัศน์ร่วม ทุกคนรู้ว่าทีมไปทางไหน</li>
                        </ul>
                    </div>

                    <div class="bg-gradient-to-r from-indigo-50 to-purple-50 border-l-4 border-indigo-500 p-6 rounded-r-xl">
                        <h4 class="text-xl font-bold text-indigo-900 mb-3">6. 🔄 ทำให้ระบบทำงานอัตโนมัติ</h4>
                        <p class="text-indigo-800 mb-3">ลดการพึ่งพาคุณคนเดียว:</p>
                        <ul class="space-y-2 text-indigo-700">
                            <li>🤖 ใช้ระบบอัตโนมัติ เช่น chatbot ตอบคำถามลูกค้า</li>
                            <li>📚 สร้างคลังความรู้ ให้ทีมหาข้อมูลเองได้</li>
                            <li>👥 ฝึกลูกทีมเก่งๆ ให้เป็นผู้นำรุ่นใหม่</li>
                            <li>⚙️ มีระบบรายงานผลอัตโนมัติ</li>
                        </ul>
                    </div>

                    <div class="bg-gradient-to-r from-teal-50 to-cyan-50 border-l-4 border-teal-500 p-6 rounded-r-xl">
                        <h4 class="text-xl font-bold text-teal-900 mb-3">7. 🚀 ขยายทีมอย่างต่อเนื่อง</h4>
                        <p class="text-teal-800 mb-3">อย่าหยุดเติบโต:</p>
                        <ul class="space-y-2 text-teal-700">
                            <li>🌱 หาสมาชิกใหม่ทุกสัปดาห์ อย่าหยุดหา</li>
                            <li>🎓 ส่งเสริมลูกทีมให้หาคนของตัวเอง</li>
                            <li>🌍 ขยายไปพื้นที่ใหม่ จังหวัดใหม่</li>
                            <li>📱 ใช้โซเชียลมีเดีย ทำ content marketing</li>
                        </ul>
                    </div>
                </div>

                <div class="mt-8 bg-gradient-to-br from-pink-100 to-rose-200 border-2 border-pink-400 rounded-xl p-6">
                    <h4 class="font-bold text-2xl text-pink-900 mb-4 flex items-center gap-2">
                        <span>👑</span> กฎทอง 3 ข้อของการสร้างทีม
                    </h4>
                    <div class="space-y-4">
                        <div class="bg-white dark:bg-gray-800 p-5 rounded-xl">
                            <div class="font-bold text-lg text-pink-800 mb-2">1. เป็นแบบอย่างที่ดี</div>
                            <p class="text-gray-700 dark:text-gray-300">คุณต้องทำในสิ่งที่สอน ถ้าคุณไม่ขาย ลูกทีมก็จะไม่ขาย</p>
                        </div>
                        <div class="bg-white dark:bg-gray-800 p-5 rounded-xl">
                            <div class="font-bold text-lg text-pink-800 mb-2">2. สอนให้เก่งกว่าคุณ</div>
                            <p class="text-gray-700 dark:text-gray-300">อย่ากลัวลูกทีมเก่งกว่า ยิ่งเขาเก่ง คุณยิ่งได้ประโยชน์</p>
                        </div>
                        <div class="bg-white dark:bg-gray-800 p-5 rounded-xl">
                            <div class="font-bold text-lg text-pink-800 mb-2">3. ช่วยพวกเขาประสบความสำเร็จ</div>
                            <p class="text-gray-700 dark:text-gray-300">เมื่อทีมสำเร็จ คุณก็จะสำเร็จไปด้วย นี่คือ Win-Win แท้ๆ</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Chapter 6-10 Summary Cards -->
        <div class="grid md:grid-cols-2 gap-6 mb-8">
            <!-- Chapter 6 -->
            <div id="chapter-6" class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-6 scroll-mt-8">
                <div class="flex items-center gap-3 mb-4">
                    <div class="text-4xl">📈</div>
                    <div>
                        <div class="text-xs text-indigo-600 font-semibold">CHAPTER 6</div>
                        <h3 class="text-2xl font-bold text-gray-800 dark:text-white">กลยุทธ์ขายที่ได้ผล</h3>
                    </div>
                </div>
                <div class="space-y-3 text-gray-700 dark:text-gray-300">
                    <div class="bg-indigo-50 p-4 rounded-xl">
                        <div class="font-bold mb-2">🎯 เข้าใจลูกค้า</div>
                        <p class="text-sm">ถามปัญหา ฟังให้ดี แล้วเสนอโซลูชั่น ไม่ใช่ยัดเยียดขาย</p>
                    </div>
                    <div class="bg-indigo-50 p-4 rounded-xl">
                        <div class="font-bold mb-2">📱 ใช้โซเชียลมีเดีย</div>
                        <p class="text-sm">โพสต์ content สม่ำเสมอ เล่าเรื่องราวความสำเร็จ ไม่ใช่แค่โฆษณา</p>
                    </div>
                    <div class="bg-indigo-50 p-4 rounded-xl">
                        <div class="font-bold mb-2">🤝 สร้างความไว้วางใจ</div>
                        <p class="text-sm">รีวิวจากคนจริง ใช้ผลิตภัณฑ์เองก่อน มีหลักฐานการจ่ายเงิน</p>
                    </div>
                    <div class="bg-indigo-50 p-4 rounded-xl">
                        <div class="font-bold mb-2">💬 Follow Up</div>
                        <p class="text-sm">ติดตามลูกค้าที่สนใจ ส่วนใหญ่ปิดการขายที่รอบ 3-5</p>
                    </div>
                </div>
            </div>

            <!-- Chapter 7 -->
            <div id="chapter-7" class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-6 scroll-mt-8">
                <div class="flex items-center gap-3 mb-4">
                    <div class="text-4xl">💳</div>
                    <div>
                        <div class="text-xs text-red-600 font-semibold">CHAPTER 7</div>
                        <h3 class="text-2xl font-bold text-gray-800 dark:text-white">กระเป๋าเงินและถอนเงิน</h3>
                    </div>
                </div>
                <div class="space-y-3 text-gray-700 dark:text-gray-300">
                    <div class="bg-red-50 p-4 rounded-xl">
                        <div class="font-bold mb-2">💰 ยอดคงเหลือ</div>
                        <p class="text-sm">เช็คยอดเงินแบบ realtime มีทั้งเงินหลัก, โบนัส, คอมมิชชั่น</p>
                    </div>
                    <div class="bg-red-50 p-4 rounded-xl">
                        <div class="font-bold mb-2">🏦 ถอนเงิน</div>
                        <p class="text-sm">ขั้นต่ำ 500 บาท โอนภายใน 1-3 วันทำการ ผ่านธนาคารหรือ PromptPay</p>
                    </div>
                    <div class="bg-red-50 p-4 rounded-xl">
                        <div class="font-bold mb-2">📊 ประวัติรายการ</div>
                        <p class="text-sm">ดูรายการรับ-จ่าย รายงานภาษี ดาวน์โหลด PDF ได้</p>
                    </div>
                    <div class="bg-red-50 p-4 rounded-xl">
                        <div class="font-bold mb-2">🔒 ความปลอดภัย</div>
                        <p class="text-sm">ใช้ PIN, 2FA ปกป้องบัญชี ข้อมูลเข้ารหัสทั้งหมด</p>
                    </div>
                </div>
            </div>

            <!-- Chapter 8 -->
            <div id="chapter-8" class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-6 scroll-mt-8">
                <div class="flex items-center gap-3 mb-4">
                    <div class="text-4xl">👑</div>
                    <div>
                        <div class="text-xs text-teal-600 font-semibold">CHAPTER 8</div>
                        <h3 class="text-2xl font-bold text-gray-800 dark:text-white">เป็นแม่ทีมมืออาชีพ</h3>
                    </div>
                </div>
                <div class="space-y-3 text-gray-700 dark:text-gray-300">
                    <div class="bg-teal-50 p-4 rounded-xl">
                        <div class="font-bold mb-2">📚 เรียนรู้ไม่หยุด</div>
                        <p class="text-sm">เข้าอบรม อ่านหนังสือ ติดตามเทรนด์ใหม่ๆ</p>
                    </div>
                    <div class="bg-teal-50 p-4 rounded-xl">
                        <div class="font-bold mb-2">🎤 พัฒนาทักษะพูด</div>
                        <p class="text-sm">นำเสนอได้ชัดเจน สอนได้น่าฟัง สร้างแรงบันดาลใจ</p>
                    </div>
                    <div class="bg-teal-50 p-4 rounded-xl">
                        <div class="font-bold mb-2">💪 มีวินัย</div>
                        <p class="text-sm">ทำงานสม่ำเสมอ ตรงต่อเวลา เป็นตัวอย่าง</p>
                    </div>
                    <div class="bg-teal-50 p-4 rounded-xl">
                        <div class="font-bold mb-2">❤️ ใส่ใจทีม</div>
                        <p class="text-sm">อยู่เคียงข้าง แก้ปัญหา เป็นพี่เลี้ยงที่ดี</p>
                    </div>
                </div>
            </div>

            <!-- Chapter 9 -->
            <div id="chapter-9" class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-6 scroll-mt-8">
                <div class="flex items-center gap-3 mb-4">
                    <div class="text-4xl">🎓</div>
                    <div>
                        <div class="text-xs text-orange-600 font-semibold">CHAPTER 9</div>
                        <h3 class="text-2xl font-bold text-gray-800 dark:text-white">เคล็ดลับและคำแนะนำ</h3>
                    </div>
                </div>
                <div class="space-y-3 text-gray-700 dark:text-gray-300">
                    <div class="bg-orange-50 p-4 rounded-xl">
                        <div class="font-bold mb-2">⏰ เริ่มทำเลย</div>
                        <p class="text-sm">อย่ารอวันที่ "พร้อม" เพราะไม่มีวันนั้น เริ่มตอนนี้</p>
                    </div>
                    <div class="bg-orange-50 p-4 rounded-xl">
                        <div class="font-bold mb-2">🎯 ตั้งเป้าชัดเจน</div>
                        <p class="text-sm">รายได้เท่าไร เมื่อไร ทำอะไรบ้าง เขียนลงกระดาษ</p>
                    </div>
                    <div class="bg-orange-50 p-4 rounded-xl">
                        <div class="font-bold mb-2">🚫 อย่าท้อง่ายๆ</div>
                        <p class="text-sm">ปฏิเสธ 100 คน ถึงจะปิด 10 คน นี่เป็นเรื่องปกติ</p>
                    </div>
                    <div class="bg-orange-50 p-4 rounded-xl">
                        <div class="font-bold mb-2">📊 ใช้เครื่องมือ</div>
                        <p class="text-sm">ตัวคำนวณรายได้ กราฟทีม รายงานต่างๆ ช่วยได้มาก</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Chapter 10: FAQ -->
        <div id="chapter-10" class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-8 mb-8 scroll-mt-8">
            <div class="flex items-center gap-4 mb-6">
                <div class="text-6xl">❓</div>
                <div>
                    <div class="text-sm text-cyan-600 font-semibold">CHAPTER 10</div>
                    <h2 class="text-4xl font-bold bg-gradient-to-r from-cyan-600 to-blue-600 bg-clip-text text-transparent">คำถามที่พบบ่อย (FAQ)</h2>
                </div>
            </div>

            <div class="space-y-4">
                <div class="bg-gradient-to-r from-blue-50 to-blue-100 rounded-xl p-6">
                    <h4 class="font-bold text-lg text-blue-900 mb-2">Q1: ต้องลงทุนเท่าไรถึงจะเริ่มได้?</h4>
                    <p class="text-blue-800">A: สมัครฟรี! แต่ถ้าซื้อสินค้าเพื่อขาย หรือใช้เอง ราคาเริ่มต้น 500 บาท คุณสามารถเริ่มด้วยการแนะนำคนอื่นโดยไม่ต้องลงทุนเลยก็ได้</p>
                </div>

                <div class="bg-gradient-to-r from-purple-50 to-purple-100 rounded-xl p-6">
                    <h4 class="font-bold text-lg text-purple-900 mb-2">Q2: ต้องใช้เวลาทำงานเท่าไร?</h4>
                    <p class="text-purple-800">A: ขึ้นอยู่กับเป้าหมาย ถ้าอยากรายได้ 10,000 บาท อาจใช้ 2-3 ชั่วโมง/วัน แต่ถ้าอยากรายได้ 100,000 บาท อาจต้อง fulltime</p>
                </div>

                <div class="bg-gradient-to-r from-green-50 to-green-100 rounded-xl p-6">
                    <h4 class="font-bold text-lg text-green-900 mb-2">Q3: ถอนเงินได้จริงไหม? นานแค่ไหน?</h4>
                    <p class="text-green-800">A: ถอนได้จริง 100% โอนภายใน 1-3 วันทำการ มีหลักฐานการจ่ายเงินนับพัน-หมื่นรายการ</p>
                </div>

                <div class="bg-gradient-to-r from-yellow-50 to-yellow-100 rounded-xl p-6">
                    <h4 class="font-bold text-lg text-yellow-900 mb-2">Q4: ไม่เคยขายของ จะทำได้ไหม?</h4>
                    <p class="text-yellow-800">A: ได้แน่นอน! เรามีการอบรม สอนทุกอย่างตั้งแต่เริ่มต้น มีทีมสนับสนุน มีสคริปต์ขายให้ใช้ ไม่ต้องกังวล</p>
                </div>

                <div class="bg-gradient-to-r from-pink-50 to-pink-100 rounded-xl p-6">
                    <h4 class="font-bold text-lg text-pink-900 mb-2">Q5: ขายได้จริงไหม? มีคนซื้อไหม?</h4>
                    <p class="text-pink-800">A: ขายได้จริง! สินค้าของเรามีคุณภาพ ราคาดี มีคนซื้อจริง มีรีวิวนับพัน ที่สำคัญคือคุณต้องเรียนรู้วิธีขายที่ถูกต้อง</p>
                </div>

                <div class="bg-gradient-to-r from-red-50 to-red-100 rounded-xl p-6">
                    <h4 class="font-bold text-lg text-red-900 mb-2">Q6: ถ้าไม่มีคนในทีมเลย จะได้เงินไหม?</h4>
                    <p class="text-red-800">A: ได้! คุณสามารถขายเองและรับค่าคอมจากยอดขายตัวเอง แต่ถ้ามีทีม รายได้จะเพิ่มขึ้นทวีคูณ</p>
                </div>

                <div class="bg-gradient-to-r from-indigo-50 to-indigo-100 rounded-xl p-6">
                    <h4 class="font-bold text-lg text-indigo-900 mb-2">Q7: ระบบนี้ถูกกฎหมายไหม?</h4>
                    <p class="text-indigo-800">A: ถูกต้องตามกฎหมาย 100% เป็นระบบ MLM ที่ถูกต้อง มีการขายสินค้าจริง ไม่ใช่ระบบปิรามิดหลอกลวง</p>
                </div>

                <div class="bg-gradient-to-r from-teal-50 to-teal-100 rounded-xl p-6">
                    <h4 class="font-bold text-lg text-teal-900 mb-2">Q8: ถ้าทำแล้วไม่ได้เงิน จะทำยังไง?</h4>
                    <p class="text-teal-800">A: ถามตัวเองว่า: 1) ขายหรือยัง? 2) สอนทีมหรือยัง? 3) ทำสม่ำเสมอหรือเปล่า? ถ้าทำครบแล้วยังไม่ได้ ปรึกษาผู้สปอนเซอร์หรือทีมซัพพอร์ตเรา</p>
                </div>
            </div>
        </div>

        <!-- Call to Action -->
        <div class="bg-gradient-to-br from-purple-600 via-pink-600 to-rose-600 rounded-3xl shadow-2xl p-12 text-center text-white">
            <div class="text-7xl mb-6 animate-bounce">🚀</div>
            <h2 class="text-5xl font-bold mb-4">พร้อมเริ่มต้นแล้วหรือยัง?</h2>
            <p class="text-2xl mb-8 text-pink-100">
                คุณมีทุกอย่างที่ต้องการแล้ว ขาดแค่การเริ่มต้น!
            </p>
            <div class="flex flex-col sm:flex-row gap-4 justify-center">
                <a href="{{ route('user.dashboard') }}" class="px-10 py-5 bg-white dark:bg-gray-800 text-purple-600 rounded-full text-xl font-bold shadow-2xl hover:bg-gray-100 dark:bg-gray-700 transform hover:scale-105 transition-all">
                    📊 ไปที่แดชบอร์ด
                </a>
                <a href="{{ route('user.mlm.income-simulator') }}" class="px-10 py-5 bg-yellow-400 text-purple-900 rounded-full text-xl font-bold shadow-2xl hover:bg-yellow-300 transform hover:scale-105 transition-all">
                    💰 คำนวณรายได้
                </a>
            </div>
            <div class="mt-8 text-pink-100">
                <p class="text-lg">หากมีคำถาม ติดต่อทีมสนับสนุนได้ทันที!</p>
            </div>
        </div>

        <!-- Footer -->
        <div class="text-center mt-12 text-gray-600 dark:text-gray-400">
            <p class="text-sm">© {{ date('Y') }} Thaiprompt-Affiliate - เส้นทางเศรษฐี เวอร์ชั่น 1.0</p>
            <p class="text-xs mt-2">สงวนลิขสิทธิ์ ห้ามทำซ้ำหรือเผยแพร่โดยไม่ได้รับอนุญาต</p>
        </div>

    </div>
</div>

@push('styles')
<style>
    @keyframes pulse-slow {
        0%, 100% { opacity: 1; }
        50% { opacity: 0.8; }
    }
    .animate-pulse-slow {
        animation: pulse-slow 3s ease-in-out infinite;
    }
    .scroll-mt-8 {
        scroll-margin-top: 2rem;
    }
    html {
        scroll-behavior: smooth;
    }
</style>
@endpush
@endsection
