<footer class="bg-gray-800 dark:bg-gray-900 text-white">
    <div class="max-w-7xl mx-auto py-12 px-4 sm:px-6 lg:px-8">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-8">
            {{-- โลโก้และคำอธิบาย --}}
            <div class="col-span-1 md:col-span-2">
                <h3 class="text-2xl font-bold mb-4">{{ \App\Models\Setting::get('app_name', 'TP-Affiliate') }}</h3>
                <p class="text-gray-300 mb-4">
                    ระบบ Affiliate Marketing ที่ทันสมัย มืออาชีพ และพร้อมใช้งาน
                    ทั้งบนเว็บไซต์และแอปพลิเคชันมือถือ
                </p>
                {{-- ดาวน์โหลดแอป --}}
                <div class="flex flex-wrap gap-2 mt-4">
                    <span class="text-sm text-gray-400">ดาวน์โหลดแอป:</span>
                    <a href="#" class="text-gray-300 hover:text-white transition text-sm">
                        <span class="inline-flex items-center gap-1">📱 iOS</span>
                    </a>
                    <a href="#" class="text-gray-300 hover:text-white transition text-sm">
                        <span class="inline-flex items-center gap-1">🤖 Android</span>
                    </a>
                </div>
            </div>

            {{-- เมนูหลัก --}}
            <div>
                <h4 class="text-lg font-semibold mb-4">เมนูหลัก</h4>
                <ul class="space-y-2">
                    <li><a href="{{ route('home') }}" class="text-gray-300 hover:text-white transition">หน้าแรก</a></li>
                    <li><a href="{{ route('about') }}" class="text-gray-300 hover:text-white transition">เกี่ยวกับเรา</a></li>
                    <li><a href="{{ route('contact') }}" class="text-gray-300 hover:text-white transition">ติดต่อเรา</a></li>
                    <li><a href="{{ route('forum.index') }}" class="text-gray-300 hover:text-white transition flex items-center gap-1">💬 ฟอรั่มชุมชน</a></li>
                </ul>
            </div>

            {{-- ช่วยเหลือ --}}
            <div>
                <h4 class="text-lg font-semibold mb-4">ช่วยเหลือ</h4>
                <ul class="space-y-2">
                    <li><a href="{{ route('page.show', 'faq') }}" class="text-gray-300 hover:text-white transition">คำถามที่พบบ่อย</a></li>
                    <li><a href="{{ route('forum.index') }}" class="text-gray-300 hover:text-white transition">ฟอรั่มถาม-ตอบ</a></li>
                    <li><a href="{{ route('contact') }}" class="text-gray-300 hover:text-white transition">ติดต่อสนับสนุน</a></li>
                </ul>
            </div>

            {{-- นโยบายและข้อกำหนด --}}
            <div>
                <h4 class="text-lg font-semibold mb-4">📋 นโยบายและข้อกำหนด</h4>
                <ul class="space-y-2">
                    <li>
                        <a href="{{ route('page.show', 'privacy') }}" class="text-gray-300 hover:text-white transition flex items-center gap-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                            </svg>
                            นโยบายความเป็นส่วนตัว
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('page.show', 'terms') }}" class="text-gray-300 hover:text-white transition flex items-center gap-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                            </svg>
                            ข้อกำหนดการใช้งาน
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('page.show', 'cookie-policy') }}" class="text-gray-300 hover:text-white transition flex items-center gap-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            นโยบายคุ๊กกี้
                        </a>
                    </li>
                </ul>
            </div>
        </div>

        {{-- ลิขสิทธิ์ --}}
        <div class="mt-8 border-t border-gray-700 pt-8">
            <div class="flex flex-col md:flex-row justify-between items-center gap-4">
                <p class="text-gray-400 text-sm text-center md:text-left">
                    &copy; {{ date('Y') }} {{ \App\Models\Setting::get('app_name', 'TP-Affiliate') }}. สงวนลิขสิทธิ์.
                </p>
                <div class="flex flex-wrap justify-center gap-4 text-sm">
                    <a href="{{ route('page.show', 'privacy') }}" class="text-gray-400 hover:text-white transition">Privacy</a>
                    <span class="text-gray-600">|</span>
                    <a href="{{ route('page.show', 'terms') }}" class="text-gray-400 hover:text-white transition">Terms</a>
                    <span class="text-gray-600">|</span>
                    <a href="{{ route('page.show', 'cookie-policy') }}" class="text-gray-400 hover:text-white transition">Cookies</a>
                    <span class="text-gray-600">|</span>
                    <span class="text-gray-500 text-xs">v{{ config('version.current') }}</span>
                </div>
            </div>
        </div>
    </div>
</footer>
