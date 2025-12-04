{{--
/**
 * Signup via Web Presentation Slides
 *
 * สไลด์แนะนำการสมัครผ่านเว็บไซต์
 *
 * @version 1.0.0
 */
--}}

<!-- Slide 1: สมัครผ่านเว็บ - ภาพรวม -->
<div class="slide active">
    <div class="h-full flex items-center justify-center bg-gradient-to-br from-blue-900/95 via-indigo-900/90 to-purple-900/95 p-8 md:p-12 backdrop-blur-xl relative overflow-hidden">
        <div class="absolute inset-0 opacity-30">
            <div class="absolute top-1/4 left-1/4 w-96 h-96 bg-blue-400 rounded-full mix-blend-screen filter blur-3xl animate-blob"></div>
            <div class="absolute bottom-1/4 right-1/4 w-96 h-96 bg-indigo-400 rounded-full mix-blend-screen filter blur-3xl animate-blob animation-delay-2000"></div>
        </div>

        <div class="max-w-5xl w-full relative z-10">
            <h2 class="text-4xl md:text-5xl lg:text-6xl font-black text-white mb-6 text-center">
                🌐 สมัครผ่านเว็บไซต์
            </h2>
            <p class="text-center text-xl md:text-2xl text-blue-200 mb-10">
                กรอกข้อมูลครบ พร้อมใช้งานทันที
            </p>

            <div class="bg-white/10 backdrop-blur-md border border-white/30 rounded-3xl p-8 mb-8 shadow-2xl">
                <div class="flex flex-col md:flex-row items-center justify-center gap-8">
                    <!-- Web Icon -->
                    <div class="flex-shrink-0">
                        <div class="w-32 h-32 bg-gradient-to-br from-blue-500 to-indigo-600 rounded-3xl flex items-center justify-center shadow-2xl animate-float">
                            <span class="text-7xl">🌐</span>
                        </div>
                    </div>

                    <!-- Benefits -->
                    <div class="text-center md:text-left">
                        <h3 class="text-2xl md:text-3xl font-bold text-white mb-4">ทำไมต้องสมัครผ่านเว็บ?</h3>
                        <ul class="space-y-3 text-lg text-white/90">
                            <li class="flex items-center gap-3">
                                <span class="text-blue-400 text-xl">✓</span>
                                <span><strong>ครบถ้วน</strong> - กรอกข้อมูลได้ละเอียด</span>
                            </li>
                            <li class="flex items-center gap-3">
                                <span class="text-blue-400 text-xl">✓</span>
                                <span><strong>รองรับทุกอุปกรณ์</strong> - คอม/มือถือ</span>
                            </li>
                            <li class="flex items-center gap-3">
                                <span class="text-blue-400 text-xl">✓</span>
                                <span><strong>เข้า Dashboard ทันที</strong> - ไม่ต้องติดตั้งแอป</span>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- 4 Steps -->
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <div class="bg-gradient-to-br from-blue-500/25 to-indigo-500/15 backdrop-blur-lg border border-blue-300/30 rounded-2xl p-5 text-center transform hover:scale-105 transition-all">
                    <div class="w-12 h-12 bg-gradient-to-br from-blue-500 to-indigo-600 rounded-xl flex items-center justify-center mx-auto mb-3">
                        <span class="text-xl font-black text-white">1</span>
                    </div>
                    <h4 class="text-base font-bold text-white mb-1">เปิดหน้าสมัคร</h4>
                    <p class="text-white/60 text-xs">คลิกปุ่ม "สมัครสมาชิก"</p>
                </div>

                <div class="bg-gradient-to-br from-indigo-500/25 to-purple-500/15 backdrop-blur-lg border border-indigo-300/30 rounded-2xl p-5 text-center transform hover:scale-105 transition-all">
                    <div class="w-12 h-12 bg-gradient-to-br from-indigo-500 to-purple-600 rounded-xl flex items-center justify-center mx-auto mb-3">
                        <span class="text-xl font-black text-white">2</span>
                    </div>
                    <h4 class="text-base font-bold text-white mb-1">กรอกข้อมูล</h4>
                    <p class="text-white/60 text-xs">ชื่อ อีเมล เบอร์โทร</p>
                </div>

                <div class="bg-gradient-to-br from-purple-500/25 to-violet-500/15 backdrop-blur-lg border border-purple-300/30 rounded-2xl p-5 text-center transform hover:scale-105 transition-all">
                    <div class="w-12 h-12 bg-gradient-to-br from-purple-500 to-violet-600 rounded-xl flex items-center justify-center mx-auto mb-3">
                        <span class="text-xl font-black text-white">3</span>
                    </div>
                    <h4 class="text-base font-bold text-white mb-1">ยืนยัน OTP</h4>
                    <p class="text-white/60 text-xs">รับรหัสทาง SMS</p>
                </div>

                <div class="bg-gradient-to-br from-violet-500/25 to-fuchsia-500/15 backdrop-blur-lg border border-violet-300/30 rounded-2xl p-5 text-center transform hover:scale-105 transition-all">
                    <div class="w-12 h-12 bg-gradient-to-br from-violet-500 to-fuchsia-600 rounded-xl flex items-center justify-center mx-auto mb-3">
                        <span class="text-xl font-black text-white">4</span>
                    </div>
                    <h4 class="text-base font-bold text-white mb-1">เข้าใช้งาน</h4>
                    <p class="text-white/60 text-xs">Login เริ่มต้นทันที</p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Slide 2: Registration Form -->
<div class="slide">
    <div class="h-full flex items-center justify-center bg-gradient-to-br from-indigo-900/95 via-purple-900/90 to-pink-900/95 p-8 md:p-12 backdrop-blur-xl relative overflow-hidden">
        <div class="absolute inset-0 opacity-25">
            <div class="absolute top-1/3 left-1/4 w-80 h-80 bg-indigo-400 rounded-full mix-blend-screen filter blur-3xl animate-blob"></div>
            <div class="absolute bottom-1/3 right-1/4 w-80 h-80 bg-purple-400 rounded-full mix-blend-screen filter blur-3xl animate-blob animation-delay-2000"></div>
        </div>

        <div class="max-w-5xl w-full relative z-10">
            <h2 class="text-4xl md:text-5xl lg:text-6xl font-black text-white mb-6 text-center">
                📋 กรอกข้อมูลสมัครสมาชิก
            </h2>
            <p class="text-center text-xl text-purple-200 mb-10">
                กรอกข้อมูลพื้นฐานเพียงไม่กี่ช่อง
            </p>

            <div class="grid md:grid-cols-2 gap-8">
                <!-- Required Fields -->
                <div class="bg-white/10 backdrop-blur-md border border-white/30 rounded-2xl p-6 shadow-xl">
                    <h3 class="text-xl font-bold text-white mb-6 flex items-center gap-2">
                        <span class="text-red-400 text-2xl">*</span> ข้อมูลที่จำเป็น
                    </h3>
                    <div class="space-y-4">
                        <div class="bg-blue-500/20 rounded-xl p-4 border border-blue-300/30">
                            <p class="text-white/60 text-sm mb-1">ชื่อ-นามสกุล</p>
                            <p class="text-lg text-white">ใช้สำหรับแสดงผลและรับเงิน</p>
                        </div>
                        <div class="bg-blue-500/20 rounded-xl p-4 border border-blue-300/30">
                            <p class="text-white/60 text-sm mb-1">อีเมล</p>
                            <p class="text-lg text-white">สำหรับ Login และรับข่าวสาร</p>
                        </div>
                        <div class="bg-blue-500/20 rounded-xl p-4 border border-blue-300/30">
                            <p class="text-white/60 text-sm mb-1">เบอร์โทรศัพท์</p>
                            <p class="text-lg text-white">สำหรับรับ OTP ยืนยันตัวตน</p>
                        </div>
                        <div class="bg-blue-500/20 rounded-xl p-4 border border-blue-300/30">
                            <p class="text-white/60 text-sm mb-1">รหัสผ่าน</p>
                            <p class="text-lg text-white">อย่างน้อย 8 ตัวอักษร</p>
                        </div>
                    </div>
                </div>

                <!-- Optional Fields -->
                <div class="bg-white/10 backdrop-blur-md border border-white/30 rounded-2xl p-6 shadow-xl">
                    <h3 class="text-xl font-bold text-white mb-6 flex items-center gap-2">
                        <span class="text-gray-400 text-2xl">○</span> ข้อมูลเพิ่มเติม (ถ้ามี)
                    </h3>
                    <div class="space-y-4">
                        <div class="bg-purple-500/20 rounded-xl p-4 border border-purple-300/30">
                            <p class="text-white/60 text-sm mb-1">รหัสผู้แนะนำ</p>
                            <p class="text-lg text-white">ใส่รหัสถ้ามีคนแนะนำมา</p>
                        </div>
                        <div class="bg-purple-500/20 rounded-xl p-4 border border-purple-300/30">
                            <p class="text-white/60 text-sm mb-1">LINE ID</p>
                            <p class="text-lg text-white">เชื่อมต่อกับ LINE Bot ภายหลัง</p>
                        </div>
                        <div class="bg-purple-500/20 rounded-xl p-4 border border-purple-300/30">
                            <p class="text-white/60 text-sm mb-1">รูปโปรไฟล์</p>
                            <p class="text-lg text-white">อัปโหลดได้ในหน้า Profile</p>
                        </div>
                    </div>

                    <div class="mt-6 p-4 bg-emerald-500/20 rounded-xl border border-emerald-300/30">
                        <p class="text-emerald-300 text-sm">
                            💡 <strong>Tip:</strong> สามารถเพิ่มข้อมูลเพิ่มเติมได้ภายหลังในหน้า Profile
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Slide 3: OTP Verification -->
<div class="slide">
    <div class="h-full flex items-center justify-center bg-gradient-to-br from-purple-900/95 via-violet-900/90 to-indigo-900/95 p-8 md:p-12 backdrop-blur-xl relative overflow-hidden">
        <div class="absolute inset-0 opacity-25">
            <div class="absolute top-1/4 left-1/3 w-80 h-80 bg-purple-400 rounded-full mix-blend-screen filter blur-3xl animate-blob"></div>
            <div class="absolute bottom-1/4 right-1/3 w-80 h-80 bg-violet-400 rounded-full mix-blend-screen filter blur-3xl animate-blob animation-delay-2000"></div>
        </div>

        <div class="max-w-4xl w-full relative z-10">
            <h2 class="text-4xl md:text-5xl lg:text-6xl font-black text-white mb-6 text-center">
                🔐 ยืนยันตัวตนด้วย OTP
            </h2>
            <p class="text-center text-xl text-violet-200 mb-10">
                รับรหัส 6 หลักทาง SMS
            </p>

            <div class="bg-white/10 backdrop-blur-md border border-white/30 rounded-3xl p-8 mb-8 shadow-2xl">
                <div class="text-center mb-8">
                    <div class="inline-flex items-center gap-4 px-8 py-4 bg-gradient-to-r from-purple-500/30 to-violet-500/30 rounded-2xl border border-purple-300/30">
                        <span class="text-5xl">📱</span>
                        <div class="text-left">
                            <p class="text-white/60 text-sm">SMS ส่งไปที่</p>
                            <p class="text-2xl font-bold text-white">08X-XXX-XX89</p>
                        </div>
                    </div>
                </div>

                <!-- OTP Input Visual -->
                <div class="flex justify-center gap-3 mb-8">
                    <div class="w-14 h-16 bg-white/20 rounded-xl border-2 border-white/40 flex items-center justify-center">
                        <span class="text-3xl font-bold text-white">•</span>
                    </div>
                    <div class="w-14 h-16 bg-white/20 rounded-xl border-2 border-white/40 flex items-center justify-center">
                        <span class="text-3xl font-bold text-white">•</span>
                    </div>
                    <div class="w-14 h-16 bg-white/20 rounded-xl border-2 border-white/40 flex items-center justify-center">
                        <span class="text-3xl font-bold text-white">•</span>
                    </div>
                    <div class="w-14 h-16 bg-white/20 rounded-xl border-2 border-white/40 flex items-center justify-center">
                        <span class="text-3xl font-bold text-white">•</span>
                    </div>
                    <div class="w-14 h-16 bg-white/20 rounded-xl border-2 border-white/40 flex items-center justify-center">
                        <span class="text-3xl font-bold text-white">•</span>
                    </div>
                    <div class="w-14 h-16 bg-white/20 rounded-xl border-2 border-purple-400 flex items-center justify-center animate-pulse">
                        <span class="text-3xl font-bold text-purple-300">|</span>
                    </div>
                </div>

                <div class="text-center space-y-4">
                    <p class="text-white/70">รหัสหมดอายุใน <span class="text-amber-300 font-bold">5:00</span> นาที</p>
                    <p class="text-white/50 text-sm">ไม่ได้รับ? <span class="text-purple-300 underline cursor-pointer">ส่งใหม่</span></p>
                </div>
            </div>

            <div class="grid md:grid-cols-3 gap-4">
                <div class="bg-emerald-500/20 backdrop-blur-lg border border-emerald-300/30 rounded-2xl p-4 text-center">
                    <div class="text-3xl mb-2">⚡</div>
                    <h4 class="text-base font-bold text-white mb-1">ส่งเร็ว</h4>
                    <p class="text-white/60 text-xs">รับใน 30 วินาที</p>
                </div>
                <div class="bg-blue-500/20 backdrop-blur-lg border border-blue-300/30 rounded-2xl p-4 text-center">
                    <div class="text-3xl mb-2">🔒</div>
                    <h4 class="text-base font-bold text-white mb-1">ปลอดภัย</h4>
                    <p class="text-white/60 text-xs">เข้ารหัส End-to-End</p>
                </div>
                <div class="bg-purple-500/20 backdrop-blur-lg border border-purple-300/30 rounded-2xl p-4 text-center">
                    <div class="text-3xl mb-2">🔄</div>
                    <h4 class="text-base font-bold text-white mb-1">ส่งซ้ำได้</h4>
                    <p class="text-white/60 text-xs">ไม่จำกัดจำนวนครั้ง</p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Slide 4: Success & Dashboard -->
<div class="slide">
    <div class="h-full flex items-center justify-center bg-gradient-to-br from-blue-900/95 via-indigo-900/90 to-purple-900/95 p-8 md:p-12 backdrop-blur-xl relative overflow-hidden">
        <div class="absolute inset-0 opacity-30">
            <div class="absolute top-1/4 left-1/4 w-96 h-96 bg-blue-400 rounded-full mix-blend-screen filter blur-3xl animate-blob"></div>
            <div class="absolute bottom-1/4 right-1/4 w-96 h-96 bg-indigo-400 rounded-full mix-blend-screen filter blur-3xl animate-blob animation-delay-2000"></div>
        </div>

        <div class="max-w-5xl w-full relative z-10">
            <div class="text-center mb-10">
                <div class="text-8xl mb-6 animate-bounce">🎊</div>
                <h2 class="text-4xl md:text-5xl lg:text-6xl font-black text-white mb-4">
                    ยินดีต้อนรับ!
                </h2>
                <p class="text-xl md:text-2xl text-blue-200">
                    พร้อมเริ่มต้นสร้างรายได้กับเราแล้ว
                </p>
            </div>

            <div class="grid md:grid-cols-2 gap-6 mb-8">
                <div class="bg-white/10 backdrop-blur-md border border-white/30 rounded-2xl p-6 shadow-xl">
                    <h3 class="text-xl font-bold text-white mb-4 flex items-center gap-2">
                        <span class="text-2xl">📊</span> Dashboard Features
                    </h3>
                    <ul class="space-y-3 text-white/90">
                        <li class="flex items-center gap-3">
                            <span class="text-blue-400 text-xl">✓</span>
                            <span>ภาพรวมยอดขาย & คอมมิชชัน</span>
                        </li>
                        <li class="flex items-center gap-3">
                            <span class="text-blue-400 text-xl">✓</span>
                            <span>ดูทีม Downline ทั้งหมด</span>
                        </li>
                        <li class="flex items-center gap-3">
                            <span class="text-blue-400 text-xl">✓</span>
                            <span>Wallet & ถอนเงิน</span>
                        </li>
                        <li class="flex items-center gap-3">
                            <span class="text-blue-400 text-xl">✓</span>
                            <span>Marketing Tools & สื่อโฆษณา</span>
                        </li>
                    </ul>
                </div>

                <div class="bg-white/10 backdrop-blur-md border border-white/30 rounded-2xl p-6 shadow-xl">
                    <h3 class="text-xl font-bold text-white mb-4 flex items-center gap-2">
                        <span class="text-2xl">🚀</span> สิ่งที่ควรทำต่อ
                    </h3>
                    <ul class="space-y-3 text-white/90">
                        <li class="flex items-center gap-3">
                            <span class="text-emerald-400 text-xl">1.</span>
                            <span>เติมข้อมูล Profile ให้ครบ</span>
                        </li>
                        <li class="flex items-center gap-3">
                            <span class="text-emerald-400 text-xl">2.</span>
                            <span>เชื่อมต่อ LINE Bot</span>
                        </li>
                        <li class="flex items-center gap-3">
                            <span class="text-emerald-400 text-xl">3.</span>
                            <span>เข้าร่วมกลุ่ม Community</span>
                        </li>
                        <li class="flex items-center gap-3">
                            <span class="text-emerald-400 text-xl">4.</span>
                            <span>แชร์ลิงก์ Affiliate เริ่มหารายได้</span>
                        </li>
                    </ul>
                </div>
            </div>

            <div class="bg-gradient-to-r from-blue-600/30 via-indigo-600/30 to-purple-600/30 backdrop-blur-md border border-blue-300/40 rounded-2xl p-8 text-center shadow-xl">
                <h3 class="text-2xl md:text-3xl font-black text-white mb-4">
                    💰 เริ่มสร้างรายได้ทันที!
                </h3>
                <p class="text-lg text-white/80 mb-4">
                    คัดลอกลิงก์ Affiliate แชร์ให้เพื่อน รับคอมมิชชันทุกยอดขาย
                </p>
                <div class="flex flex-wrap justify-center gap-4">
                    <div class="bg-white/10 rounded-xl px-6 py-3 border border-white/20">
                        <div class="text-2xl font-black text-transparent bg-clip-text bg-gradient-to-r from-blue-300 to-indigo-300">23.5%</div>
                        <div class="text-white/60 text-xs">Unilevel Commission</div>
                    </div>
                    <div class="bg-white/10 rounded-xl px-6 py-3 border border-white/20">
                        <div class="text-2xl font-black text-transparent bg-clip-text bg-gradient-to-r from-amber-300 to-orange-300">10 ชั้น</div>
                        <div class="text-white/60 text-xs">Depth Level</div>
                    </div>
                    <div class="bg-white/10 rounded-xl px-6 py-3 border border-white/20">
                        <div class="text-2xl font-black text-transparent bg-clip-text bg-gradient-to-r from-purple-300 to-fuchsia-300">8 Ranks</div>
                        <div class="text-white/60 text-xs">ระดับความสำเร็จ</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
