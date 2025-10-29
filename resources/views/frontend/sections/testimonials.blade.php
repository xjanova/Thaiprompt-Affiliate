<!-- Testimonials Section -->
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
            <!-- Default testimonials grid -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <div class="bg-gray-50 p-6 rounded-xl shadow-md">
                    <div class="flex items-center mb-4">
                        <div class="w-12 h-12 bg-indigo-500 rounded-full flex items-center justify-center text-white font-bold">
                            A
                        </div>
                        <div class="ml-4">
                            <h4 class="font-semibold text-gray-900">คุณ A</h4>
                            <p class="text-sm text-gray-600">Affiliate</p>
                        </div>
                    </div>
                    <p class="text-gray-700">"ระบบใช้งานง่ายมาก ได้เงินจริง แนะนำเลยครับ"</p>
                    <div class="mt-4 text-yellow-400">★★★★★</div>
                </div>

                <div class="bg-gray-50 p-6 rounded-xl shadow-md">
                    <div class="flex items-center mb-4">
                        <div class="w-12 h-12 bg-purple-500 rounded-full flex items-center justify-center text-white font-bold">
                            B
                        </div>
                        <div class="ml-4">
                            <h4 class="font-semibold text-gray-900">คุณ B</h4>
                            <p class="text-sm text-gray-600">Affiliate</p>
                        </div>
                    </div>
                    <p class="text-gray-700">"ระบบเสถียร จ่ายตรงเวลา ทีมซัพพอร์ตดีมาก"</p>
                    <div class="mt-4 text-yellow-400">★★★★★</div>
                </div>

                <div class="bg-gray-50 p-6 rounded-xl shadow-md">
                    <div class="flex items-center mb-4">
                        <div class="w-12 h-12 bg-pink-500 rounded-full flex items-center justify-center text-white font-bold">
                            C
                        </div>
                        <div class="ml-4">
                            <h4 class="font-semibold text-gray-900">คุณ C</h4>
                            <p class="text-sm text-gray-600">Affiliate</p>
                        </div>
                    </div>
                    <p class="text-gray-700">"รายได้ดีกว่าที่คิด ขอบคุณสำหรับระบบที่ดีครับ"</p>
                    <div class="mt-4 text-yellow-400">★★★★★</div>
                </div>
            </div>
        @endif
    </div>
</section>
