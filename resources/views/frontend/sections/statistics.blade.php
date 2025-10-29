<!-- Statistics Section -->
<section class="py-20 bg-gray-50">
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
            <!-- Default statistics grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-8 max-w-4xl mx-auto">
                <div class="bg-white p-8 rounded-xl shadow-md text-center">
                    <div class="text-5xl font-bold text-indigo-600 mb-2">{{ $stats['total_users'] ?? 0 }}</div>
                    <div class="text-gray-600">ผู้ใช้ทั้งหมด</div>
                </div>

                <div class="bg-white p-8 rounded-xl shadow-md text-center">
                    <div class="text-5xl font-bold text-purple-600 mb-2">{{ $stats['total_affiliates'] ?? 0 }}</div>
                    <div class="text-gray-600">Affiliates</div>
                </div>
            </div>
        @endif
    </div>
</section>
