@php
    $appName = \App\Models\Setting::get('app_name', 'TP-Affiliate Pro');
@endphp

<!-- Slide 1: Title / Cover -->
<div class="slide active">
    <div class="h-full flex items-center justify-center bg-gradient-to-br from-indigo-900/90 via-purple-900/80 to-pink-900/90 p-12 backdrop-blur-xl relative overflow-hidden">
        <!-- Animated background particles -->
        <div class="absolute inset-0 opacity-20">
            <div class="absolute top-20 left-20 w-72 h-72 bg-blue-400 rounded-full mix-blend-screen filter blur-3xl animate-blob"></div>
            <div class="absolute top-40 right-20 w-72 h-72 bg-purple-400 rounded-full mix-blend-screen filter blur-3xl animate-blob animation-delay-2000"></div>
            <div class="absolute bottom-20 left-1/2 w-72 h-72 bg-pink-400 rounded-full mix-blend-screen filter blur-3xl animate-blob animation-delay-4000"></div>
        </div>

        <div class="text-center text-white max-w-5xl relative z-10">
            <!-- Logo -->
            <div class="mb-8 animate-float">
                <img src="{{ asset('images/logo.svg') }}" alt="{{ $appName }}" width="200" height="200" class="w-48 h-48 mx-auto filter drop-shadow-2xl object-contain">
            </div>

            <h1 class="text-7xl md:text-8xl font-black mb-6 leading-tight animate-fade-in-up">
                <span class="text-transparent bg-clip-text bg-gradient-to-r from-yellow-300 via-pink-300 to-purple-300">
                    {{ $appName }}
                </span>
            </h1>

            <div class="bg-white/10 backdrop-blur-md border border-white/20 rounded-3xl p-8 mb-8 shadow-2xl">
                <h2 class="text-4xl md:text-5xl font-bold mb-4">
                    ระบบแอฟฟิลิเอตมืออาชีพ
                </h2>
                <p class="text-2xl text-indigo-200 leading-relaxed">
                    ระบบแอฟฟิลิเอตที่ทันสมัยและพร้อมใช้งาน
                    <br>
                    พัฒนาด้วย <strong class="text-pink-300">Laravel 11</strong> • รองรับ<strong class="text-purple-300">หลายภาษา</strong> • ออกแบบสวยงามด้วย <strong class="text-blue-300">Tailwind CSS</strong>
                </p>
            </div>

            <div class="flex items-center justify-center gap-8 text-lg">
                <div class="flex items-center gap-2 bg-white/10 backdrop-blur-sm px-4 py-2 rounded-full border border-white/20">
                    <div class="w-3 h-3 bg-emerald-400 rounded-full animate-pulse"></div>
                    <span>Production Ready</span>
                </div>
                <div class="flex items-center gap-2 bg-white/10 backdrop-blur-sm px-4 py-2 rounded-full border border-white/20">
                    <div class="w-3 h-3 bg-emerald-400 rounded-full animate-pulse"></div>
                    <span>Enterprise Level</span>
                </div>
                <div class="flex items-center gap-2 bg-white/10 backdrop-blur-sm px-4 py-2 rounded-full border border-white/20">
                    <div class="w-3 h-3 bg-emerald-400 rounded-full animate-pulse"></div>
                    <span>Open Source</span>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Slide 2: Overview / ภาพรวมระบบ -->
<div class="slide">
    <div class="h-full flex items-center justify-center bg-gradient-to-br from-slate-900/90 via-indigo-900/80 to-slate-900/90 p-12 backdrop-blur-xl relative overflow-hidden">
        <!-- Background decoration -->
        <div class="absolute inset-0 opacity-10">
            <div class="absolute inset-0" style="background-image:
                linear-gradient(to right, rgba(99, 102, 241, 0.15) 1px, transparent 1px),
                linear-gradient(to bottom, rgba(99, 102, 241, 0.15) 1px, transparent 1px);
                background-size: 40px 40px;"></div>
        </div>

        <div class="max-w-6xl w-full relative z-10">
            <h2 class="text-5xl md:text-6xl font-black text-white mb-12 text-center animate-fade-in">
                📌 เกี่ยวกับระบบ
            </h2>

            <div class="bg-white/10 backdrop-blur-md border border-white/30 rounded-3xl p-8 mb-8 shadow-2xl">
                <p class="text-2xl text-white/90 leading-relaxed text-center">
                    <strong class="text-transparent bg-clip-text bg-gradient-to-r from-blue-300 to-purple-300">TP-Affiliate Pro</strong>
                    คือระบบจัดการแอฟฟิลิเอตมาร์เก็ตติ้งที่สมบูรณ์แบบ พร้อมใช้งานทันทีหลังติดตั้ง
                    เหมาะสำหรับธุรกิจที่ต้องการระบบแอฟฟิลิเอตที่มีประสิทธิภาพ
                    มีระบบการจัดการที่ครบครัน และรองรับการปรับแต่งได้อย่างยืดหยุ่น
                </p>
            </div>

            <div class="grid grid-cols-2 gap-8">
                <!-- Left Card -->
                <div class="bg-gradient-to-br from-white/15 to-white/5 backdrop-blur-lg border border-white/30 rounded-3xl p-8 transform hover:scale-105 transition-all duration-300 shadow-2xl">
                    <div class="text-6xl mb-6 text-center">🎯</div>
                    <h3 class="text-3xl font-bold text-white mb-6 text-center">เหมาะสำหรับ</h3>
                    <ul class="space-y-4 text-white text-lg">
                        <li class="flex items-start gap-3">
                            <span class="text-emerald-400 text-xl">✅</span>
                            <span>ธุรกิจออนไลน์ที่ต้องการระบบแอฟฟิลิเอต</span>
                        </li>
                        <li class="flex items-start gap-3">
                            <span class="text-emerald-400 text-xl">✅</span>
                            <span>บริษัทที่ต้องการสร้างเครือข่ายการตลาด</span>
                        </li>
                        <li class="flex items-start gap-3">
                            <span class="text-emerald-400 text-xl">✅</span>
                            <span>Startup ที่ต้องการเริ่มต้นอย่างรวดเร็ว</span>
                        </li>
                        <li class="flex items-start gap-3">
                            <span class="text-emerald-400 text-xl">✅</span>
                            <span>นักพัฒนาที่ต้องการ Template ระบบแอฟฟิลิเอต</span>
                        </li>
                    </ul>
                </div>

                <!-- Right Card -->
                <div class="bg-gradient-to-br from-white/15 to-white/5 backdrop-blur-lg border border-white/30 rounded-3xl p-8 transform hover:scale-105 transition-all duration-300 shadow-2xl">
                    <div class="text-6xl mb-6 text-center">💡</div>
                    <h3 class="text-3xl font-bold text-white mb-6 text-center">จุดเด่น</h3>
                    <ul class="space-y-4 text-white text-lg">
                        <li class="flex items-start gap-3">
                            <span class="text-yellow-400 text-xl">🔥</span>
                            <span><strong>ติดตั้งง่าย</strong> - ใช้เวลาไม่เกิน 5 นาที</span>
                        </li>
                        <li class="flex items-start gap-3">
                            <span class="text-yellow-400 text-xl">🎨</span>
                            <span><strong>UI/UX สวยงาม</strong> - ออกแบบโดยมืออาชีพ</span>
                        </li>
                        <li class="flex items-start gap-3">
                            <span class="text-yellow-400 text-xl">🔐</span>
                            <span><strong>ปลอดภัยสูง</strong> - มาตรฐาน Enterprise Level</span>
                        </li>
                        <li class="flex items-start gap-3">
                            <span class="text-yellow-400 text-xl">🌍</span>
                            <span><strong>รองรับหลายภาษา</strong> - ไทย, อังกฤษ (เพิ่มได้ไม่จำกัด)</span>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Slide 3: Key Features - Affiliate System -->
<div class="slide">
    <div class="h-full flex items-center justify-center bg-gradient-to-br from-purple-900/90 via-pink-900/80 to-red-900/90 p-12 backdrop-blur-xl relative overflow-hidden">
        <div class="absolute inset-0 opacity-20">
            <div class="absolute top-10 left-10 w-96 h-96 bg-purple-400 rounded-full mix-blend-screen filter blur-3xl animate-pulse"></div>
            <div class="absolute bottom-10 right-10 w-96 h-96 bg-pink-400 rounded-full mix-blend-screen filter blur-3xl animate-pulse animation-delay-2000"></div>
        </div>

        <div class="max-w-6xl w-full relative z-10">
            <h2 class="text-5xl md:text-6xl font-black text-white mb-12 text-center">
                🎯 ระบบแอฟฟิลิเอต
            </h2>

            <div class="grid grid-cols-2 gap-6">
                <!-- Card 1 -->
                <div class="bg-white/10 backdrop-blur-md border border-white/30 rounded-2xl p-6 hover:bg-white/15 transition-all shadow-xl">
                    <div class="flex items-start gap-4">
                        <div class="text-5xl">👥</div>
                        <div class="flex-1 text-white">
                            <h3 class="text-2xl font-bold mb-3">การจัดการสมาชิก</h3>
                            <ul class="space-y-2 text-white/90">
                                <li>• ระบบสมัครสมาชิกอัตโนมัติ</li>
                                <li>• การยืนยันตัวตนผ่านอีเมล</li>
                                <li>• โปรไฟล์ที่ปรับแต่งได้</li>
                                <li>• ระบบอัปโหลดรูปโปรไฟล์</li>
                            </ul>
                        </div>
                    </div>
                </div>

                <!-- Card 2 -->
                <div class="bg-white/10 backdrop-blur-md border border-white/30 rounded-2xl p-6 hover:bg-white/15 transition-all shadow-xl">
                    <div class="flex items-start gap-4">
                        <div class="text-5xl">💰</div>
                        <div class="flex-1 text-white">
                            <h3 class="text-2xl font-bold mb-3">ระบบคอมมิชชั่น</h3>
                            <ul class="space-y-2 text-white/90">
                                <li>• คำนวณคอมมิชชั่นอัตโนมัติ</li>
                                <li>• รองรับหลายระดับ (Multi-tier)</li>
                                <li>• ตั้งค่าเปอร์เซ็นต์คอมได้</li>
                                <li>• ประวัติคอมมิชชั่นแบบละเอียด</li>
                            </ul>
                        </div>
                    </div>
                </div>

                <!-- Card 3 -->
                <div class="bg-white/10 backdrop-blur-md border border-white/30 rounded-2xl p-6 hover:bg-white/15 transition-all shadow-xl">
                    <div class="flex items-start gap-4">
                        <div class="text-5xl">🔗</div>
                        <div class="flex-1 text-white">
                            <h3 class="text-2xl font-bold mb-3">ระบบลิงก์แอฟฟิลิเอต</h3>
                            <ul class="space-y-2 text-white/90">
                                <li>• สร้างลิงก์อัตโนมัติ</li>
                                <li>• QR Code Generator</li>
                                <li>• ติดตามการคลิก</li>
                                <li>• สถิติการแปลง (Conversion)</li>
                            </ul>
                        </div>
                    </div>
                </div>

                <!-- Card 4 -->
                <div class="bg-white/10 backdrop-blur-md border border-white/30 rounded-2xl p-6 hover:bg-white/15 transition-all shadow-xl">
                    <div class="flex items-start gap-4">
                        <div class="text-5xl">💳</div>
                        <div class="flex-1 text-white">
                            <h3 class="text-2xl font-bold mb-3">ระบบกระเป๋าเงิน</h3>
                            <ul class="space-y-2 text-white/90">
                                <li>• กระเป๋าเงินสำหรับแต่ละผู้ใช้</li>
                                <li>• ถอนเงินผ่านหลายช่องทาง</li>
                                <li>• ประวัติธุรกรรมแบบละเอียด</li>
                                <li>• การอนุมัติอัตโนมัติ/Manual</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>

            <div class="mt-8 bg-gradient-to-r from-yellow-500/20 to-orange-500/20 backdrop-blur-md border border-yellow-300/30 rounded-2xl p-6 text-center shadow-xl">
                <p class="text-2xl text-white font-bold">
                    💡 ระบบแอฟฟิลิเอตที่สมบูรณ์แบบที่สุด พร้อมใช้งานทันที
                </p>
            </div>
        </div>
    </div>
</div>

<!-- Slide 4: Admin Dashboard Features -->
<div class="slide">
    <div class="h-full flex items-center justify-center bg-gradient-to-br from-blue-900/90 via-indigo-900/80 to-purple-900/90 p-12 backdrop-blur-xl relative overflow-hidden">
        <div class="max-w-6xl w-full relative z-10">
            <h2 class="text-5xl md:text-6xl font-black text-white mb-12 text-center">
                🎨 ระบบ Admin Dashboard
            </h2>

            <div class="grid grid-cols-2 gap-6">
                <!-- Dashboard -->
                <div class="bg-white/10 backdrop-blur-md border border-white/30 rounded-2xl p-6 hover:bg-white/15 transition-all shadow-xl">
                    <div class="flex items-start gap-4">
                        <div class="text-5xl">📊</div>
                        <div class="flex-1 text-white">
                            <h3 class="text-2xl font-bold mb-3">Dashboard</h3>
                            <ul class="space-y-2 text-white/90">
                                <li>• สถิติแบบ Real-time</li>
                                <li>• กราฟวิเคราะห์ข้อมูล</li>
                                <li>• แจ้งเตือนสำคัญ</li>
                                <li>• Dark/Light Mode</li>
                            </ul>
                        </div>
                    </div>
                </div>

                <!-- User Management -->
                <div class="bg-white/10 backdrop-blur-md border border-white/30 rounded-2xl p-6 hover:bg-white/15 transition-all shadow-xl">
                    <div class="flex items-start gap-4">
                        <div class="text-5xl">👤</div>
                        <div class="flex-1 text-white">
                            <h3 class="text-2xl font-bold mb-3">จัดการผู้ใช้</h3>
                            <ul class="space-y-2 text-white/90">
                                <li>• CRUD ผู้ใช้ทั้งหมด</li>
                                <li>• ระบบสิทธิ์ (RBAC)</li>
                                <li>• ดูประวัติการใช้งาน</li>
                                <li>• ระงับ/ปลดบล็อกบัญชี</li>
                            </ul>
                        </div>
                    </div>
                </div>

                <!-- Settings -->
                <div class="bg-white/10 backdrop-blur-md border border-white/30 rounded-2xl p-6 hover:bg-white/15 transition-all shadow-xl">
                    <div class="flex items-start gap-4">
                        <div class="text-5xl">⚙️</div>
                        <div class="flex-1 text-white">
                            <h3 class="text-2xl font-bold mb-3">การตั้งค่าระบบ</h3>
                            <ul class="space-y-2 text-white/90">
                                <li>• ตั้งค่าทั่วไป</li>
                                <li>• การตั้งค่าอีเมล</li>
                                <li>• ธีมและสี</li>
                                <li>• Payment Gateway</li>
                            </ul>
                        </div>
                    </div>
                </div>

                <!-- Content Management -->
                <div class="bg-white/10 backdrop-blur-md border border-white/30 rounded-2xl p-6 hover:bg-white/15 transition-all shadow-xl">
                    <div class="flex items-start gap-4">
                        <div class="text-5xl">🌍</div>
                        <div class="flex-1 text-white">
                            <h3 class="text-2xl font-bold mb-3">จัดการหน้าเว็บ</h3>
                            <ul class="space-y-2 text-white/90">
                                <li>• Header Builder</li>
                                <li>• Menu Manager</li>
                                <li>• Content Editor</li>
                                <li>• SEO Management</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>

            <div class="mt-8 bg-gradient-to-r from-blue-500/20 to-purple-500/20 backdrop-blur-md border border-blue-300/30 rounded-2xl p-6 text-center shadow-xl">
                <p class="text-2xl text-white font-bold">
                    ✨ Dashboard ที่ครบครัน ใช้งานง่าย และสวยงาม
                </p>
            </div>
        </div>
    </div>
</div>

<!-- Slide 5: Security Features -->
<div class="slide">
    <div class="h-full flex items-center justify-center bg-gradient-to-br from-emerald-900/90 via-teal-900/80 to-cyan-900/90 p-12 backdrop-blur-xl relative overflow-hidden">
        <div class="max-w-6xl w-full relative z-10">
            <h2 class="text-5xl md:text-6xl font-black text-white mb-12 text-center">
                🔐 ความปลอดภัย
            </h2>

            <div class="bg-white/10 backdrop-blur-md border border-white/30 rounded-3xl p-8 mb-8 shadow-2xl">
                <p class="text-2xl text-white/90 leading-relaxed text-center mb-8">
                    ความปลอดภัยระดับ <strong class="text-emerald-300">Enterprise Level</strong>
                    ด้วยมาตรฐานสากลและเทคโนโลยีล่าสุด
                </p>
            </div>

            <div class="grid grid-cols-2 gap-6">
                <div class="bg-gradient-to-br from-white/15 to-white/5 backdrop-blur-lg border border-white/30 rounded-2xl p-6 hover:scale-105 transition-all shadow-xl">
                    <ul class="space-y-4 text-white text-lg">
                        <li class="flex items-start gap-3">
                            <span class="text-emerald-400 text-2xl">✅</span>
                            <div>
                                <strong>CSRF Protection</strong>
                                <p class="text-white/70 text-sm">ป้องกันการโจมตีแบบ Cross-Site Request Forgery</p>
                            </div>
                        </li>
                        <li class="flex items-start gap-3">
                            <span class="text-emerald-400 text-2xl">✅</span>
                            <div>
                                <strong>XSS Prevention</strong>
                                <p class="text-white/70 text-sm">ป้องกัน Cross-Site Scripting</p>
                            </div>
                        </li>
                        <li class="flex items-start gap-3">
                            <span class="text-emerald-400 text-2xl">✅</span>
                            <div>
                                <strong>SQL Injection Protection</strong>
                                <p class="text-white/70 text-sm">ป้องกัน SQL Injection Attack</p>
                            </div>
                        </li>
                        <li class="flex items-start gap-3">
                            <span class="text-emerald-400 text-2xl">✅</span>
                            <div>
                                <strong>Password Hashing (bcrypt)</strong>
                                <p class="text-white/70 text-sm">เข้ารหัสรหัสผ่านอย่างปลอดภัย</p>
                            </div>
                        </li>
                    </ul>
                </div>

                <div class="bg-gradient-to-br from-white/15 to-white/5 backdrop-blur-lg border border-white/30 rounded-2xl p-6 hover:scale-105 transition-all shadow-xl">
                    <ul class="space-y-4 text-white text-lg">
                        <li class="flex items-start gap-3">
                            <span class="text-emerald-400 text-2xl">✅</span>
                            <div>
                                <strong>Rate Limiting</strong>
                                <p class="text-white/70 text-sm">จำกัดจำนวนคำขอเพื่อป้องกัน DDoS</p>
                            </div>
                        </li>
                        <li class="flex items-start gap-3">
                            <span class="text-emerald-400 text-2xl">✅</span>
                            <div>
                                <strong>Two-Factor Authentication (2FA)</strong>
                                <p class="text-white/70 text-sm">ยืนยันตัวตนสองขั้นตอน</p>
                            </div>
                        </li>
                        <li class="flex items-start gap-3">
                            <span class="text-emerald-400 text-2xl">✅</span>
                            <div>
                                <strong>Activity Logging</strong>
                                <p class="text-white/70 text-sm">บันทึกการใช้งานทั้งหมด</p>
                            </div>
                        </li>
                        <li class="flex items-start gap-3">
                            <span class="text-emerald-400 text-2xl">✅</span>
                            <div>
                                <strong>IP Whitelist/Blacklist</strong>
                                <p class="text-white/70 text-sm">จัดการ IP ที่อนุญาต/บล็อก</p>
                            </div>
                        </li>
                    </ul>
                </div>
            </div>

            <div class="mt-8 bg-gradient-to-r from-emerald-500/20 to-teal-500/20 backdrop-blur-md border border-emerald-300/30 rounded-2xl p-6 text-center shadow-xl">
                <p class="text-2xl text-white font-bold">
                    🛡️ มั่นใจในความปลอดภัยระดับสูงสุด
                </p>
            </div>
        </div>
    </div>
</div>

<!-- Slide 6: Multi-Language Support -->
<div class="slide">
    <div class="h-full flex items-center justify-center bg-gradient-to-br from-orange-900/90 via-red-900/80 to-pink-900/90 p-12 backdrop-blur-xl relative overflow-hidden">
        <div class="max-w-6xl w-full relative z-10">
            <h2 class="text-5xl md:text-6xl font-black text-white mb-12 text-center">
                🌍 ระบบหลายภาษา
            </h2>

            <div class="bg-white/10 backdrop-blur-md border border-white/30 rounded-3xl p-8 mb-8 shadow-2xl">
                <div class="text-center">
                    <div class="flex items-center justify-center gap-8 mb-8 flex-wrap">
                        <div class="bg-white/10 backdrop-blur-sm border border-white/20 rounded-2xl px-8 py-4 flex items-center gap-3">
                            <span class="text-4xl">🇹🇭</span>
                            <div class="text-left">
                                <p class="text-white font-bold text-xl">ภาษาไทย</p>
                                <p class="text-white/70">Built-in</p>
                            </div>
                        </div>
                        <div class="bg-white/10 backdrop-blur-sm border border-white/20 rounded-2xl px-8 py-4 flex items-center gap-3">
                            <span class="text-4xl">🇬🇧</span>
                            <div class="text-left">
                                <p class="text-white font-bold text-xl">English</p>
                                <p class="text-white/70">Built-in</p>
                            </div>
                        </div>
                        <div class="bg-white/10 backdrop-blur-sm border border-white/20 rounded-2xl px-8 py-4 flex items-center gap-3">
                            <span class="text-4xl">➕</span>
                            <div class="text-left">
                                <p class="text-white font-bold text-xl">เพิ่มได้ไม่จำกัด</p>
                                <p class="text-white/70">Unlimited</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-6">
                <div class="bg-gradient-to-br from-white/15 to-white/5 backdrop-blur-lg border border-white/30 rounded-2xl p-8 shadow-xl">
                    <h3 class="text-3xl font-bold text-white mb-6 flex items-center gap-3">
                        <span class="text-4xl">✨</span>
                        <span>คุณสมบัติ</span>
                    </h3>
                    <ul class="space-y-4 text-white text-lg">
                        <li class="flex items-start gap-3">
                            <span class="text-yellow-400">🔄</span>
                            <span>สลับภาษาได้ทันที</span>
                        </li>
                        <li class="flex items-start gap-3">
                            <span class="text-yellow-400">📝</span>
                            <span>แปลผ่าน Google Translate API</span>
                        </li>
                        <li class="flex items-start gap-3">
                            <span class="text-yellow-400">🎯</span>
                            <span>ตรวจจับภาษาอัตโนมัติ</span>
                        </li>
                        <li class="flex items-start gap-3">
                            <span class="text-yellow-400">💾</span>
                            <span>จัดการคำแปลผ่าน Admin</span>
                        </li>
                    </ul>
                </div>

                <div class="bg-gradient-to-br from-white/15 to-white/5 backdrop-blur-lg border border-white/30 rounded-2xl p-8 shadow-xl">
                    <h3 class="text-3xl font-bold text-white mb-6 flex items-center gap-3">
                        <span class="text-4xl">🚀</span>
                        <span>ประโยชน์</span>
                    </h3>
                    <ul class="space-y-4 text-white text-lg">
                        <li class="flex items-start gap-3">
                            <span class="text-green-400">📈</span>
                            <span>เข้าถึงตลาดต่างประเทศ</span>
                        </li>
                        <li class="flex items-start gap-3">
                            <span class="text-green-400">👥</span>
                            <span>รองรับผู้ใช้หลากหลาย</span>
                        </li>
                        <li class="flex items-start gap-3">
                            <span class="text-green-400">💼</span>
                            <span>เพิ่มโอกาสทางธุรกิจ</span>
                        </li>
                        <li class="flex items-start gap-3">
                            <span class="text-green-400">🌟</span>
                            <span>มาตรฐาน International</span>
                        </li>
                    </ul>
                </div>
            </div>

            <div class="mt-8 bg-gradient-to-r from-orange-500/20 to-pink-500/20 backdrop-blur-md border border-orange-300/30 rounded-2xl p-6 text-center shadow-xl">
                <p class="text-2xl text-white font-bold">
                    🌐 ทำธุรกิจได้ทั่วโลก ไร้ขีดจำกัด
                </p>
            </div>
        </div>
    </div>
</div>

<!-- Slide 7: Installation & Update System -->
<div class="slide">
    <div class="h-full flex items-center justify-center bg-gradient-to-br from-violet-900/90 via-purple-900/80 to-fuchsia-900/90 p-12 backdrop-blur-xl relative overflow-hidden">
        <div class="max-w-6xl w-full relative z-10">
            <h2 class="text-5xl md:text-6xl font-black text-white mb-12 text-center">
                🔄 ระบบติดตั้งและอัปเดต
            </h2>

            <div class="grid grid-cols-2 gap-8 mb-8">
                <!-- Installation -->
                <div class="bg-gradient-to-br from-white/15 to-white/5 backdrop-blur-lg border border-white/30 rounded-3xl p-8 shadow-2xl">
                    <div class="text-center mb-6">
                        <div class="text-6xl mb-4">📦</div>
                        <h3 class="text-3xl font-bold text-white">การติดตั้ง</h3>
                    </div>

                    <ul class="space-y-4 text-white text-lg">
                        <li class="flex items-start gap-3">
                            <span class="text-green-400 text-2xl">✓</span>
                            <div>
                                <strong>Script อัตโนมัติ</strong>
                                <p class="text-white/70 text-sm">ติดตั้งได้ใน 5 นาที</p>
                            </div>
                        </li>
                        <li class="flex items-start gap-3">
                            <span class="text-green-400 text-2xl">✓</span>
                            <div>
                                <strong>Setup Wizard</strong>
                                <p class="text-white/70 text-sm">ตั้งค่าผ่าน UI สวยงาม</p>
                            </div>
                        </li>
                        <li class="flex items-start gap-3">
                            <span class="text-green-400 text-2xl">✓</span>
                            <div>
                                <strong>ตรวจสอบระบบ</strong>
                                <p class="text-white/70 text-sm">เช็ค PHP, Database อัตโนมัติ</p>
                            </div>
                        </li>
                        <li class="flex items-start gap-3">
                            <span class="text-green-400 text-2xl">✓</span>
                            <div>
                                <strong>Demo Data</strong>
                                <p class="text-white/70 text-sm">ติดตั้งข้อมูลตัวอย่างได้</p>
                            </div>
                        </li>
                    </ul>
                </div>

                <!-- Update System -->
                <div class="bg-gradient-to-br from-white/15 to-white/5 backdrop-blur-lg border border-white/30 rounded-3xl p-8 shadow-2xl">
                    <div class="text-center mb-6">
                        <div class="text-6xl mb-4">🔄</div>
                        <h3 class="text-3xl font-bold text-white">การอัปเดต</h3>
                    </div>

                    <ul class="space-y-4 text-white text-lg">
                        <li class="flex items-start gap-3">
                            <span class="text-blue-400 text-2xl">✓</span>
                            <div>
                                <strong>ตรวจสอบเวอร์ชั่นอัตโนมัติ</strong>
                                <p class="text-white/70 text-sm">แจ้งเตือนเมื่อมีเวอร์ชั่นใหม่</p>
                            </div>
                        </li>
                        <li class="flex items-start gap-3">
                            <span class="text-blue-400 text-2xl">✓</span>
                            <div>
                                <strong>One-Click Update</strong>
                                <p class="text-white/70 text-sm">อัปเดตด้วยคลิกเดียว</p>
                            </div>
                        </li>
                        <li class="flex items-start gap-3">
                            <span class="text-blue-400 text-2xl">✓</span>
                            <div>
                                <strong>Auto Backup</strong>
                                <p class="text-white/70 text-sm">สำรองข้อมูลก่อนอัปเดต</p>
                            </div>
                        </li>
                        <li class="flex items-start gap-3">
                            <span class="text-blue-400 text-2xl">✓</span>
                            <div>
                                <strong>Rollback Support</strong>
                                <p class="text-white/70 text-sm">ย้อนกลับได้ถ้ามีปัญหา</p>
                            </div>
                        </li>
                    </ul>
                </div>
            </div>

            <div class="bg-white/10 backdrop-blur-md border border-white/30 rounded-2xl p-8 shadow-xl">
                <h3 class="text-2xl font-bold text-white mb-4 text-center">📋 ความต้องการของระบบ</h3>
                <div class="grid grid-cols-4 gap-4 text-center">
                    <div class="bg-white/5 rounded-xl p-4">
                        <p class="text-white/70 text-sm mb-1">PHP</p>
                        <p class="text-white font-bold text-lg">8.1+</p>
                    </div>
                    <div class="bg-white/5 rounded-xl p-4">
                        <p class="text-white/70 text-sm mb-1">MySQL</p>
                        <p class="text-white font-bold text-lg">5.7+</p>
                    </div>
                    <div class="bg-white/5 rounded-xl p-4">
                        <p class="text-white/70 text-sm mb-1">RAM</p>
                        <p class="text-white font-bold text-lg">1 GB+</p>
                    </div>
                    <div class="bg-white/5 rounded-xl p-4">
                        <p class="text-white/70 text-sm mb-1">Disk</p>
                        <p class="text-white font-bold text-lg">500 MB+</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Slide 8: Technology Stack -->
<div class="slide">
    <div class="h-full flex items-center justify-center bg-gradient-to-br from-gray-900/90 via-slate-900/80 to-zinc-900/90 p-12 backdrop-blur-xl relative overflow-hidden">
        <div class="max-w-6xl w-full relative z-10">
            <h2 class="text-5xl md:text-6xl font-black text-white mb-12 text-center">
                🚀 เทคโนโลยีที่ใช้
            </h2>

            <div class="grid grid-cols-2 gap-8 mb-8">
                <!-- Backend -->
                <div class="bg-gradient-to-br from-red-500/10 to-orange-500/10 backdrop-blur-lg border border-red-300/30 rounded-3xl p-8 shadow-2xl">
                    <h3 class="text-3xl font-bold text-white mb-6 flex items-center gap-3">
                        <span class="text-4xl">⚙️</span>
                        <span>Backend</span>
                    </h3>
                    <ul class="space-y-4 text-white text-lg">
                        <li class="flex items-center gap-3 bg-white/5 rounded-lg p-3">
                            <span class="text-3xl">🔴</span>
                            <div>
                                <strong>Laravel 11</strong>
                                <p class="text-white/70 text-sm">PHP Framework ที่ทันสมัยที่สุด</p>
                            </div>
                        </li>
                        <li class="flex items-center gap-3 bg-white/5 rounded-lg p-3">
                            <span class="text-3xl">🐬</span>
                            <div>
                                <strong>MySQL / SQLite</strong>
                                <p class="text-white/70 text-sm">Database ที่เสถียรและรวดเร็ว</p>
                            </div>
                        </li>
                        <li class="flex items-center gap-3 bg-white/5 rounded-lg p-3">
                            <span class="text-3xl">🔐</span>
                            <div>
                                <strong>Laravel Sanctum</strong>
                                <p class="text-white/70 text-sm">API Authentication</p>
                            </div>
                        </li>
                    </ul>
                </div>

                <!-- Frontend -->
                <div class="bg-gradient-to-br from-blue-500/10 to-cyan-500/10 backdrop-blur-lg border border-blue-300/30 rounded-3xl p-8 shadow-2xl">
                    <h3 class="text-3xl font-bold text-white mb-6 flex items-center gap-3">
                        <span class="text-4xl">🎨</span>
                        <span>Frontend</span>
                    </h3>
                    <ul class="space-y-4 text-white text-lg">
                        <li class="flex items-center gap-3 bg-white/5 rounded-lg p-3">
                            <span class="text-3xl">💨</span>
                            <div>
                                <strong>Tailwind CSS</strong>
                                <p class="text-white/70 text-sm">Utility-first CSS Framework</p>
                            </div>
                        </li>
                        <li class="flex items-center gap-3 bg-white/5 rounded-lg p-3">
                            <span class="text-3xl">⛰️</span>
                            <div>
                                <strong>Alpine.js</strong>
                                <p class="text-white/70 text-sm">Lightweight JavaScript Framework</p>
                            </div>
                        </li>
                        <li class="flex items-center gap-3 bg-white/5 rounded-lg p-3">
                            <span class="text-3xl">📊</span>
                            <div>
                                <strong>Chart.js</strong>
                                <p class="text-white/70 text-sm">Data Visualization</p>
                            </div>
                        </li>
                    </ul>
                </div>
            </div>

            <!-- DevOps -->
            <div class="bg-gradient-to-r from-purple-500/10 to-pink-500/10 backdrop-blur-lg border border-purple-300/30 rounded-3xl p-8 shadow-2xl">
                <h3 class="text-3xl font-bold text-white mb-6 text-center flex items-center justify-center gap-3">
                    <span class="text-4xl">🐳</span>
                    <span>DevOps & Deployment</span>
                </h3>
                <div class="grid grid-cols-3 gap-4">
                    <div class="bg-white/5 rounded-lg p-4 text-center">
                        <span class="text-4xl">🐳</span>
                        <p class="text-white font-bold mt-2">Docker</p>
                        <p class="text-white/70 text-sm">Containerization</p>
                    </div>
                    <div class="bg-white/5 rounded-lg p-4 text-center">
                        <span class="text-4xl">🔧</span>
                        <p class="text-white font-bold mt-2">Nginx</p>
                        <p class="text-white/70 text-sm">Web Server</p>
                    </div>
                    <div class="bg-white/5 rounded-lg p-4 text-center">
                        <span class="text-4xl">⚡</span>
                        <p class="text-white font-bold mt-2">Redis</p>
                        <p class="text-white/70 text-sm">Caching</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Slide 9: License & Pricing -->
<div class="slide">
    <div class="h-full flex items-center justify-center bg-gradient-to-br from-yellow-900/90 via-amber-900/80 to-orange-900/90 p-12 backdrop-blur-xl relative overflow-hidden">
        <div class="max-w-6xl w-full relative z-10">
            <h2 class="text-5xl md:text-6xl font-black text-white mb-12 text-center">
                🔑 License และการเปิดใช้งาน
            </h2>

            <div class="bg-white/10 backdrop-blur-md border border-white/30 rounded-3xl p-8 mb-8 shadow-2xl">
                <h3 class="text-3xl font-bold text-white mb-6 text-center">📋 ประเภท License</h3>

                <div class="grid grid-cols-3 gap-6">
                    <!-- Single Site -->
                    <div class="bg-gradient-to-br from-gray-500/20 to-gray-600/20 backdrop-blur-sm border border-gray-300/30 rounded-2xl p-6 hover:scale-105 transition-all">
                        <div class="text-center">
                            <div class="text-4xl mb-3">🥉</div>
                            <h4 class="text-2xl font-bold text-white mb-4">Single Site</h4>
                            <div class="text-4xl font-black text-yellow-300 mb-4">฿9,900</div>
                            <ul class="space-y-2 text-white/90 text-sm text-left">
                                <li>• 1 โดเมน</li>
                                <li>• Support 6 เดือน</li>
                                <li>• Updates 1 ปี</li>
                            </ul>
                        </div>
                    </div>

                    <!-- Multi Site -->
                    <div class="bg-gradient-to-br from-blue-500/20 to-indigo-600/20 backdrop-blur-sm border border-blue-300/30 rounded-2xl p-6 hover:scale-105 transition-all shadow-xl">
                        <div class="text-center">
                            <div class="text-4xl mb-3">🥈</div>
                            <h4 class="text-2xl font-bold text-white mb-4">Multi Site</h4>
                            <div class="text-4xl font-black text-blue-300 mb-4">฿19,900</div>
                            <ul class="space-y-2 text-white/90 text-sm text-left">
                                <li>• 5 โดเมน</li>
                                <li>• Support 1 ปี</li>
                                <li>• Updates 1 ปี</li>
                            </ul>
                        </div>
                    </div>

                    <!-- Unlimited -->
                    <div class="bg-gradient-to-br from-yellow-500/20 to-orange-600/20 backdrop-blur-sm border border-yellow-300/30 rounded-2xl p-6 hover:scale-105 transition-all shadow-xl">
                        <div class="text-center">
                            <div class="text-4xl mb-3">🥇</div>
                            <h4 class="text-2xl font-bold text-white mb-4">Unlimited</h4>
                            <div class="text-4xl font-black text-orange-300 mb-4">฿39,900</div>
                            <ul class="space-y-2 text-white/90 text-sm text-left">
                                <li>• ไม่จำกัดโดเมน</li>
                                <li>• Support ตลอดชีพ</li>
                                <li>• Updates ตลอดชีพ</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>

            <!-- License Features -->
            <div class="grid grid-cols-2 gap-6">
                <div class="bg-white/10 backdrop-blur-md border border-white/30 rounded-2xl p-6 shadow-xl">
                    <h4 class="text-2xl font-bold text-white mb-4 flex items-center gap-2">
                        <span>🔐</span>
                        <span>ระบบตรวจสอบ License</span>
                    </h4>
                    <ul class="space-y-3 text-white/90">
                        <li>• ✅ License Key Validation</li>
                        <li>• ✅ Domain Binding</li>
                        <li>• ✅ IP Address Tracking</li>
                        <li>• ✅ Remote Verification</li>
                    </ul>
                </div>

                <div class="bg-white/10 backdrop-blur-md border border-white/30 rounded-2xl p-6 shadow-xl">
                    <h4 class="text-2xl font-bold text-white mb-4 flex items-center gap-2">
                        <span>✨</span>
                        <span>การเปิดใช้งาน</span>
                    </h4>
                    <ul class="space-y-3 text-white/90">
                        <li>• 💻 ผ่าน Command Line</li>
                        <li>• 🎨 ผ่าน Admin Dashboard</li>
                        <li>• ⚙️ ผ่านไฟล์ .env</li>
                        <li>• 🚀 ง่าย รวดเร็ว ปลอดภัย</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Slide 10: Getting Started -->
<div class="slide">
    <div class="h-full flex items-center justify-center bg-gradient-to-br from-green-900/90 via-emerald-900/80 to-teal-900/90 p-12 backdrop-blur-xl relative overflow-hidden">
        <div class="max-w-6xl w-full relative z-10">
            <h2 class="text-5xl md:text-6xl font-black text-white mb-12 text-center">
                🎯 การเริ่มต้นใช้งาน
            </h2>

            <div class="bg-white/10 backdrop-blur-md border border-white/30 rounded-3xl p-8 mb-8 shadow-2xl">
                <h3 class="text-3xl font-bold text-white mb-6 text-center">🚀 ติดตั้งด้วย Script อัตโนมัติ</h3>

                <div class="bg-black/30 backdrop-blur-sm rounded-2xl p-6 mb-6 font-mono text-green-300 overflow-x-auto">
                    <pre class="text-sm">
# 1. Clone repository
git clone https://github.com/xjanova/Thaiprompt-Affiliate.git
cd Thaiprompt-Affiliate

# 2. รัน script ติดตั้งอัตโนมัติ
chmod +x install.sh
./install.sh

# 3. ตั้งค่า License Key
php artisan license:activate YOUR-LICENSE-KEY

# 4. เริ่มใช้งาน
php artisan serve
                    </pre>
                </div>

                <div class="text-center">
                    <p class="text-2xl text-white/90">
                        ✨ <strong class="text-green-300">ใช้เวลาไม่เกิน 5 นาที</strong> ✨
                    </p>
                </div>
            </div>

            <div class="grid grid-cols-3 gap-6">
                <div class="bg-gradient-to-br from-white/15 to-white/5 backdrop-blur-lg border border-white/30 rounded-2xl p-6 text-center shadow-xl">
                    <div class="text-5xl mb-4">1️⃣</div>
                    <h4 class="text-xl font-bold text-white mb-2">Setup Wizard</h4>
                    <p class="text-white/80 text-sm">ตั้งค่าผ่าน UI สวยงาม</p>
                </div>

                <div class="bg-gradient-to-br from-white/15 to-white/5 backdrop-blur-lg border border-white/30 rounded-2xl p-6 text-center shadow-xl">
                    <div class="text-5xl mb-4">2️⃣</div>
                    <h4 class="text-xl font-bold text-white mb-2">Configure</h4>
                    <p class="text-white/80 text-sm">ปรับแต่งตามต้องการ</p>
                </div>

                <div class="bg-gradient-to-br from-white/15 to-white/5 backdrop-blur-lg border border-white/30 rounded-2xl p-6 text-center shadow-xl">
                    <div class="text-5xl mb-4">3️⃣</div>
                    <h4 class="text-xl font-bold text-white mb-2">Launch</h4>
                    <p class="text-white/80 text-sm">เริ่มใช้งานทันที!</p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Slide 11: Documentation & Support -->
<div class="slide">
    <div class="h-full flex items-center justify-center bg-gradient-to-br from-blue-900/90 via-cyan-900/80 to-teal-900/90 p-12 backdrop-blur-xl relative overflow-hidden">
        <div class="max-w-6xl w-full relative z-10">
            <h2 class="text-5xl md:text-6xl font-black text-white mb-12 text-center">
                📚 เอกสารและการสนับสนุน
            </h2>

            <div class="grid grid-cols-2 gap-8 mb-8">
                <!-- Documentation -->
                <div class="bg-gradient-to-br from-white/15 to-white/5 backdrop-blur-lg border border-white/30 rounded-3xl p-8 shadow-2xl">
                    <h3 class="text-3xl font-bold text-white mb-6 flex items-center gap-3">
                        <span class="text-4xl">📖</span>
                        <span>เอกสารประกอบ</span>
                    </h3>
                    <ul class="space-y-3 text-white text-lg">
                        <li class="flex items-center gap-3 bg-white/5 rounded-lg p-3">
                            <span class="text-2xl">📘</span>
                            <span>GETTING-STARTED.md</span>
                        </li>
                        <li class="flex items-center gap-3 bg-white/5 rounded-lg p-3">
                            <span class="text-2xl">📦</span>
                            <span>INSTALLATION-GUIDE.md</span>
                        </li>
                        <li class="flex items-center gap-3 bg-white/5 rounded-lg p-3">
                            <span class="text-2xl">🏭</span>
                            <span>PRODUCTION-INSTALL.md</span>
                        </li>
                        <li class="flex items-center gap-3 bg-white/5 rounded-lg p-3">
                            <span class="text-2xl">💻</span>
                            <span>DEVELOPMENT.md</span>
                        </li>
                        <li class="flex items-center gap-3 bg-white/5 rounded-lg p-3">
                            <span class="text-2xl">📋</span>
                            <span>CHANGELOG.md</span>
                        </li>
                    </ul>
                </div>

                <!-- Support -->
                <div class="bg-gradient-to-br from-white/15 to-white/5 backdrop-blur-lg border border-white/30 rounded-3xl p-8 shadow-2xl">
                    <h3 class="text-3xl font-bold text-white mb-6 flex items-center gap-3">
                        <span class="text-4xl">💬</span>
                        <span>การสนับสนุน</span>
                    </h3>
                    <ul class="space-y-3 text-white text-lg">
                        <li class="flex items-center gap-3 bg-white/5 rounded-lg p-3">
                            <span class="text-2xl">📧</span>
                            <span>Email Support</span>
                        </li>
                        <li class="flex items-center gap-3 bg-white/5 rounded-lg p-3">
                            <span class="text-2xl">💬</span>
                            <span>Live Chat</span>
                        </li>
                        <li class="flex items-center gap-3 bg-white/5 rounded-lg p-3">
                            <span class="text-2xl">🐛</span>
                            <span>GitHub Issues</span>
                        </li>
                        <li class="flex items-center gap-3 bg-white/5 rounded-lg p-3">
                            <span class="text-2xl">📚</span>
                            <span>Knowledge Base</span>
                        </li>
                        <li class="flex items-center gap-3 bg-white/5 rounded-lg p-3">
                            <span class="text-2xl">🎥</span>
                            <span>Video Tutorials</span>
                        </li>
                    </ul>
                </div>
            </div>

            <div class="bg-gradient-to-r from-cyan-500/20 to-blue-500/20 backdrop-blur-md border border-cyan-300/30 rounded-3xl p-8 text-center shadow-2xl">
                <h3 class="text-3xl font-bold text-white mb-4">📞 ติดต่อเรา</h3>
                <div class="grid grid-cols-3 gap-6 text-white">
                    <div>
                        <p class="text-sm text-white/70 mb-1">Website</p>
                        <p class="font-bold">thaiprompt.com</p>
                    </div>
                    <div>
                        <p class="text-sm text-white/70 mb-1">Email</p>
                        <p class="font-bold">support@thaiprompt.com</p>
                    </div>
                    <div>
                        <p class="text-sm text-white/70 mb-1">GitHub</p>
                        <p class="font-bold">@xjanova</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Slide 12: Thank You -->
<div class="slide">
    <div class="h-full flex items-center justify-center bg-gradient-to-br from-indigo-900/90 via-purple-900/80 to-pink-900/90 p-12 backdrop-blur-xl relative overflow-hidden">
        <div class="absolute inset-0 opacity-20">
            <div class="absolute top-1/4 left-1/4 w-96 h-96 bg-blue-400 rounded-full mix-blend-screen filter blur-3xl animate-blob"></div>
            <div class="absolute top-1/3 right-1/4 w-96 h-96 bg-purple-400 rounded-full mix-blend-screen filter blur-3xl animate-blob animation-delay-2000"></div>
            <div class="absolute bottom-1/4 left-1/2 w-96 h-96 bg-pink-400 rounded-full mix-blend-screen filter blur-3xl animate-blob animation-delay-4000"></div>
        </div>

        <div class="text-center text-white max-w-5xl relative z-10">
            <div class="mb-8 animate-float">
                <img src="{{ asset('images/logo.svg') }}" alt="{{ $appName }}" width="200" height="200" class="w-48 h-48 mx-auto filter drop-shadow-2xl object-contain">
            </div>

            <h1 class="text-7xl md:text-8xl font-black mb-8 leading-tight">
                <span class="text-transparent bg-clip-text bg-gradient-to-r from-yellow-300 via-pink-300 to-purple-300">
                    Thank You!
                </span>
            </h1>

            <div class="bg-white/10 backdrop-blur-md border border-white/30 rounded-3xl p-10 mb-8 shadow-2xl">
                <h2 class="text-4xl md:text-5xl font-bold mb-6">
                    พร้อมเริ่มต้นแล้วหรือยัง?
                </h2>
                <p class="text-2xl text-white/90 mb-8 leading-relaxed">
                    เริ่มต้นสร้างระบบแอฟฟิลิเอตมืออาชีพของคุณวันนี้
                    <br>
                    ด้วย <strong class="text-pink-300">TP-Affiliate Pro</strong>
                </p>

                <div class="flex items-center justify-center gap-6">
                    <a href="https://thaiprompt.com" class="bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 text-white font-bold text-xl px-10 py-5 rounded-2xl shadow-2xl transform hover:scale-105 transition-all">
                        🌐 เยี่ยมชมเว็บไซต์
                    </a>
                    <a href="mailto:support@thaiprompt.com" class="bg-gradient-to-r from-purple-600 to-pink-600 hover:from-purple-700 hover:to-pink-700 text-white font-bold text-xl px-10 py-5 rounded-2xl shadow-2xl transform hover:scale-105 transition-all">
                        📧 ติดต่อเรา
                    </a>
                </div>
            </div>

            <div class="flex items-center justify-center gap-8 text-lg">
                <div class="flex items-center gap-2 bg-white/10 backdrop-blur-sm px-6 py-3 rounded-full border border-white/20">
                    <span class="text-2xl">❤️</span>
                    <span>Made in Thailand</span>
                </div>
                <div class="flex items-center gap-2 bg-white/10 backdrop-blur-sm px-6 py-3 rounded-full border border-white/20">
                    <span class="text-2xl">🚀</span>
                    <span>Version {{ config('app.version', '1.0.0') }}</span>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
@keyframes blob {
    0%, 100% { transform: translate(0, 0) scale(1); }
    25% { transform: translate(20px, -50px) scale(1.1); }
    50% { transform: translate(-20px, 20px) scale(0.9); }
    75% { transform: translate(20px, 20px) scale(1.05); }
}

.animate-blob {
    animation: blob 15s ease-in-out infinite;
}

.animation-delay-2000 {
    animation-delay: 2s;
}

.animation-delay-4000 {
    animation-delay: 4s;
}

@keyframes float {
    0%, 100% { transform: translateY(0px); }
    50% { transform: translateY(-20px); }
}

.animate-float {
    animation: float 6s ease-in-out infinite;
}

@keyframes fade-in-up {
    0% { opacity: 0; transform: translateY(30px); }
    100% { opacity: 1; transform: translateY(0); }
}

.animate-fade-in-up {
    animation: fade-in-up 1s ease-out;
}

.animate-fade-in {
    animation: fade-in-up 0.8s ease-out;
}
</style>
