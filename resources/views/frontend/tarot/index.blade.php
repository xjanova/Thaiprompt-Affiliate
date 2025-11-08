@extends('layouts.app')

@section('content')
<div class="min-h-screen bg-gradient-to-br from-purple-900 via-indigo-900 to-blue-900">
    <!-- Hero Section -->
    <div class="relative overflow-hidden">
        <div class="absolute inset-0 bg-black opacity-30"></div>
        <div class="absolute inset-0 bg-gradient-to-b from-transparent to-purple-900"></div>

        <div class="relative container mx-auto px-4 py-20">
            <div class="text-center">
                <h1 class="text-5xl md:text-6xl font-bold text-white mb-6" data-aos="fade-down">
                    🔮 หมอดูไพ่ทาโร่ต์
                </h1>
                <p class="text-xl md:text-2xl text-purple-200 mb-8" data-aos="fade-up" data-aos-delay="100">
                    ค้นพบคำตอบที่คุณกำลังมองหา ด้วยไพ่ทาโร่ต์แห่งความลึกลับ
                </p>
                <div class="flex justify-center gap-4" data-aos="fade-up" data-aos-delay="200">
                    <a href="#categories" class="bg-purple-600 hover:bg-purple-700 text-white px-8 py-4 rounded-lg text-lg font-semibold transition-all transform hover:scale-105 shadow-lg">
                        เริ่มทำนาย
                    </a>
                </div>
            </div>
        </div>

        <!-- Decorative Elements -->
        <div class="absolute top-10 left-10 w-20 h-20 bg-purple-500 rounded-full opacity-20 animate-pulse"></div>
        <div class="absolute bottom-10 right-10 w-32 h-32 bg-blue-500 rounded-full opacity-20 animate-pulse" style="animation-delay: 1s;"></div>
    </div>

    <!-- Categories Section -->
    <div id="categories" class="container mx-auto px-4 py-16">
        <h2 class="text-4xl font-bold text-center text-white mb-12" data-aos="fade-up">
            เลือกหมวดหมู่ที่คุณต้องการทำนาย
        </h2>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
            @foreach($categories as $category)
            <div class="bg-white bg-opacity-10 backdrop-blur-lg rounded-2xl p-8 hover:bg-opacity-20 transition-all transform hover:scale-105 cursor-pointer border border-white border-opacity-20"
                 data-aos="fade-up"
                 data-aos-delay="{{ $loop->index * 100 }}"
                 onclick="window.location='{{ route('tarot.category', $category->slug) }}'">

                <!-- Icon -->
                <div class="text-6xl mb-4 text-center" style="color: {{ $category->color }}">
                    @if($category->icon)
                        <i class="fas {{ $category->icon }}"></i>
                    @else
                        <i class="fas fa-star"></i>
                    @endif
                </div>

                <!-- Title -->
                <h3 class="text-2xl font-bold text-white text-center mb-3">
                    {{ $category->name_th }}
                </h3>

                <!-- Description -->
                <p class="text-purple-200 text-center mb-4">
                    {{ $category->description_th }}
                </p>

                <!-- Price Badge -->
                <div class="text-center">
                    @if($category->price > 0)
                        <span class="inline-block bg-yellow-500 text-yellow-900 px-4 py-2 rounded-full font-bold">
                            {{ number_format($category->price, 0) }} บาท
                        </span>
                        @if($category->is_free_first)
                            <p class="text-sm text-green-300 mt-2">ฟรีครั้งแรกต่อวัน</p>
                        @endif
                    @else
                        <span class="inline-block bg-green-500 text-white px-4 py-2 rounded-full font-bold">
                            ฟรี
                        </span>
                    @endif
                </div>
            </div>
            @endforeach
        </div>
    </div>

    <!-- Features Section -->
    <div class="container mx-auto px-4 py-16">
        <h2 class="text-4xl font-bold text-center text-white mb-12">
            ทำไมต้องเลือกเรา
        </h2>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
            <div class="text-center" data-aos="fade-up">
                <div class="text-5xl mb-4">🎴</div>
                <h3 class="text-xl font-bold text-white mb-2">ไพ่ทาโร่ต์ครบ 78 ใบ</h3>
                <p class="text-purple-200">ใช้ไพ่ทาโร่ต์แท้ ครบทั้ง Major และ Minor Arcana</p>
            </div>

            <div class="text-center" data-aos="fade-up" data-aos-delay="100">
                <div class="text-5xl mb-4">✨</div>
                <h3 class="text-xl font-bold text-white mb-2">อนิเมชั่นสวยงาม</h3>
                <p class="text-purple-200">ประสบการณ์การทำนายที่น่าตื่นเต้น</p>
            </div>

            <div class="text-center" data-aos="fade-up" data-aos-delay="200">
                <div class="text-5xl mb-4">💾</div>
                <h3 class="text-xl font-bold text-white mb-2">บันทึกคำทำนาย</h3>
                <p class="text-purple-200">เก็บประวัติการทำนายไว้ดูย้อนหลัง</p>
            </div>
        </div>
    </div>
</div>

@push('styles')
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
@endpush

@push('scripts')
<script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
<script>
    AOS.init({
        duration: 800,
        once: true
    });
</script>
@endpush
@endsection
