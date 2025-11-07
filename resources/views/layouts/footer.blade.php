<footer class="bg-gray-800 text-white">
    <div class="max-w-7xl mx-auto py-12 px-4 sm:px-6 lg:px-8">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
            <div class="col-span-1 md:col-span-2">
                <h3 class="text-2xl font-bold mb-4">{{ \App\Models\Setting::get('app_name', 'TP-Affiliate') }}</h3>
                <p class="text-gray-300">
                    ระบบ Affiliate Marketing ที่ทันสมัย มืออาชีพ และพร้อมใช้งาน
                </p>
            </div>

            <div>
                <h4 class="text-lg font-semibold mb-4">เมนูหลัก</h4>
                <ul class="space-y-2">
                    <li><a href="{{ route('home') }}" class="text-gray-300 hover:text-white transition">หน้าแรก</a></li>
                    <li><a href="{{ route('about') }}" class="text-gray-300 hover:text-white transition">เกี่ยวกับเรา</a></li>
                    <li><a href="{{ route('contact') }}" class="text-gray-300 hover:text-white transition">ติดต่อเรา</a></li>
                </ul>
            </div>

            <div>
                <h4 class="text-lg font-semibold mb-4">ช่วยเหลือ</h4>
                <ul class="space-y-2">
                    <li><a href="#" class="text-gray-300 hover:text-white transition">คำถามที่พบบ่อย</a></li>
                    <li><a href="#" class="text-gray-300 hover:text-white transition">เอกสาร</a></li>
                    <li><a href="#" class="text-gray-300 hover:text-white transition">สนับสนุน</a></li>
                </ul>
            </div>
        </div>

        <div class="mt-8 border-t border-gray-700 pt-8">
            <div class="text-center mb-4">
                <div class="flex flex-wrap justify-center gap-4 text-sm">
                    <a href="{{ route('page.show', 'privacy') }}" class="text-gray-400 hover:text-white transition">นโยบายความเป็นส่วนตัว</a>
                    <span class="text-gray-600">|</span>
                    <a href="{{ route('page.show', 'terms') }}" class="text-gray-400 hover:text-white transition">ข้อกำหนดการใช้งาน</a>
                    <span class="text-gray-600">|</span>
                    <a href="{{ route('page.show', 'cookie-policy') }}" class="text-gray-400 hover:text-white transition">นโยบายคุ๊กกี้</a>
                </div>
            </div>
            <p class="text-center text-gray-400 text-sm">
                &copy; {{ date('Y') }} {{ \App\Models\Setting::get('app_name', 'TP-Affiliate') }}. สงวนลิขสิทธิ์.
                <span class="mx-2 text-gray-600">|</span>
                <span class="text-xs">Version {{ config('version.current') }} {{ config('version.name') }}</span>
            </p>
        </div>
    </div>
</footer>
