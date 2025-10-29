<!-- Custom Section -->
<section class="py-20 bg-white">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        @if($section->title)
            <div class="text-center mb-12">
                <h2 class="text-4xl font-bold text-gray-900 mb-4">{{ $section->title }}</h2>
                @if($section->subtitle)
                    <p class="text-xl text-gray-600">{{ $section->subtitle }}</p>
                @endif
            </div>
        @endif

        @if($section->content)
            <div class="prose prose-lg max-w-none">
                {!! $section->content !!}
            </div>
        @endif
    </div>
</section>
