<!-- CTA Section -->
<section class="py-20 bg-gradient-to-r from-indigo-600 to-purple-600 text-white">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
        @if($section->title)
            <h2 class="text-4xl font-bold mb-6">{{ $section->title }}</h2>
        @endif

        @if($section->subtitle)
            <p class="text-xl mb-8 text-indigo-100">{{ $section->subtitle }}</p>
        @endif

        @if($section->content)
            <div class="prose prose-lg max-w-none text-white">
                {!! $section->content !!}
            </div>
        @else
            @guest
                <a href="{{ route('register') }}" class="inline-flex items-center px-8 py-4 bg-white text-indigo-600 font-semibold rounded-lg shadow-lg hover:bg-gray-100 transition duration-300">
                    สมัครเลย ฟรี!
                </a>
            @endguest
        @endif
    </div>
</section>
