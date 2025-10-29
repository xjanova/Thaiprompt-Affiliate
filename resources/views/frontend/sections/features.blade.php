<!-- Features Section -->
<section class="py-20 bg-white">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-16">
            @if($section->title)
                <h2 class="text-4xl font-bold text-gray-900 mb-4">{{ $section->title }}</h2>
            @endif

            @if($section->subtitle)
                <p class="text-xl text-gray-600">{{ $section->subtitle }}</p>
            @endif
        </div>

        @if($section->content)
            <div class="prose prose-lg max-w-none">
                {!! $section->content !!}
            </div>
        @else
            <!-- Default features grid -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <div class="p-6 bg-gray-50 rounded-xl shadow-md hover:shadow-xl transition">
                    <div class="text-4xl mb-4">🎯</div>
                    <h3 class="text-xl font-semibold mb-2">ติดตั้งง่าย</h3>
                    <p class="text-gray-600">ติดตั้งได้ภายใน 2 นาที ไม่ต้องแก้ไขโค้ด</p>
                </div>

                <div class="p-6 bg-gray-50 rounded-xl shadow-md hover:shadow-xl transition">
                    <div class="text-4xl mb-4">🎨</div>
                    <h3 class="text-xl font-semibold mb-2">UI/UX สวยงาม</h3>
                    <p class="text-gray-600">ออกแบบมาอย่างมืออาชีพ รองรับทุกอุปกรณ์</p>
                </div>

                <div class="p-6 bg-gray-50 rounded-xl shadow-md hover:shadow-xl transition">
                    <div class="text-4xl mb-4">👑</div>
                    <h3 class="text-xl font-semibold mb-2">ระบบจัดการ</h3>
                    <p class="text-gray-600">จัดการทุกอย่างได้ง่ายจาก Dashboard</p>
                </div>

                <div class="p-6 bg-gray-50 rounded-xl shadow-md hover:shadow-xl transition">
                    <div class="text-4xl mb-4">📊</div>
                    <h3 class="text-xl font-semibold mb-2">รายงานแบบเรียลไทม์</h3>
                    <p class="text-gray-600">ดูสถิติและรายงานแบบเรียลไทม์</p>
                </div>

                <div class="p-6 bg-gray-50 rounded-xl shadow-md hover:shadow-xl transition">
                    <div class="text-4xl mb-4">🔐</div>
                    <h3 class="text-xl font-semibold mb-2">ปลอดภัย</h3>
                    <p class="text-gray-600">ระบบรักษาความปลอดภัยระดับสูง</p>
                </div>

                <div class="p-6 bg-gray-50 rounded-xl shadow-md hover:shadow-xl transition">
                    <div class="text-4xl mb-4">⚡</div>
                    <h3 class="text-xl font-semibold mb-2">รวดเร็ว</h3>
                    <p class="text-gray-600">โหลดเร็ว ประมวลผลเร็ว ใช้งานลื่นไหล</p>
                </div>
            </div>
        @endif
    </div>
</section>
