<footer class="bg-gray-800 text-white mt-12">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
            <!-- About -->
            <div>
                <h3 class="text-lg font-semibold mb-4">{{ config('app.name') }}</h3>
                <p class="text-gray-400 text-sm">
                    ตลาดกลางออนไลน์สำหรับผู้ประกอบการไทย พร้อมระบบ MLM ที่ยุติธรรม
                </p>
            </div>

            <!-- Quick Links -->
            <div>
                <h3 class="text-lg font-semibold mb-4">ลิงก์ด่วน</h3>
                <ul class="space-y-2 text-sm">
                    <li><a href="{{ url('/') }}" class="text-gray-400 hover:text-white">หน้าแรก</a></li>
                    <li><a href="{{ route('products.index') }}" class="text-gray-400 hover:text-white">สินค้าทั้งหมด</a></li>
                    <li><a href="{{ route('vendors.index') }}" class="text-gray-400 hover:text-white">ร้านค้า</a></li>
                    <li><a href="{{ url('/about') }}" class="text-gray-400 hover:text-white">เกี่ยวกับเรา</a></li>
                </ul>
            </div>

            <!-- Support -->
            <div>
                <h3 class="text-lg font-semibold mb-4">ช่วยเหลือ</h3>
                <ul class="space-y-2 text-sm">
                    <li><a href="{{ url('/help') }}" class="text-gray-400 hover:text-white">ศูนย์ช่วยเหลือ</a></li>
                    <li><a href="{{ url('/contact') }}" class="text-gray-400 hover:text-white">ติดต่อเรา</a></li>
                    <li><a href="{{ url('/terms') }}" class="text-gray-400 hover:text-white">เงื่อนไขการใช้งาน</a></li>
                    <li><a href="{{ url('/privacy') }}" class="text-gray-400 hover:text-white">นโยบายความเป็นส่วนตัว</a></li>
                </ul>
            </div>

            <!-- Contact -->
            <div>
                <h3 class="text-lg font-semibold mb-4">ติดต่อเรา</h3>
                <ul class="space-y-2 text-sm text-gray-400">
                    <li>อีเมล: support@thaiprompt.com</li>
                    <li>โทร: 02-xxx-xxxx</li>
                    <li>LINE: @thaiprompt</li>
                </ul>
            </div>
        </div>

        <div class="border-t border-gray-700 mt-8 pt-8 text-center text-sm text-gray-400">
            <p>&copy; {{ date('Y') }} {{ config('app.name') }}. All rights reserved.</p>
        </div>
    </div>
</footer>
