<!-- Hero Section -->
<section class="relative bg-gradient-to-r from-indigo-600 to-purple-600 text-white">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-24">
        <div class="text-center">
            @if($section->title)
                <h1 class="text-5xl font-bold mb-6 animate-fade-in">
                    {{ $section->title }}
                </h1>
            @endif

            @if($section->subtitle)
                <p class="text-xl mb-8 text-indigo-100">
                    {{ $section->subtitle }}
                </p>
            @endif

            @if($section->content)
                <div class="flex gap-4 justify-center">
                    {!! $section->content !!}
                </div>
            @else
                <div class="flex gap-4 justify-center">
                    @guest
                        <a href="{{ route('register') }}" class="inline-flex items-center px-8 py-4 bg-white text-indigo-600 font-semibold rounded-lg shadow-lg hover:bg-gray-100 transition duration-300">
                            เริ่มต้นใช้งาน
                        </a>
                        <a href="{{ route('login') }}" class="inline-flex items-center px-8 py-4 bg-indigo-500 text-white font-semibold rounded-lg shadow-lg hover:bg-indigo-700 transition duration-300">
                            เข้าสู่ระบบ
                        </a>
                    @else
                        <a href="{{ route('admin.dashboard') }}" class="inline-flex items-center px-8 py-4 bg-white text-indigo-600 font-semibold rounded-lg shadow-lg hover:bg-gray-100 transition duration-300">
                            เข้าสู่แดชบอร์ด
                        </a>
                    @endguest
                </div>
            @endif
        </div>
    </div>
</section>
