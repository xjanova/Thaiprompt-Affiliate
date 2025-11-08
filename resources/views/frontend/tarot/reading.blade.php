@extends('layouts.app')

@section('content')
<div class="min-h-screen bg-gradient-to-br from-purple-900 via-indigo-900 to-blue-900 py-12">
    <div class="container mx-auto px-4">
        <!-- Header -->
        <div class="text-center mb-12">
            <h1 class="text-4xl font-bold text-white mb-4">
                ผลการทำนาย
            </h1>
            <p class="text-purple-200 text-lg">
                {{ $reading->category->name_th }} - {{ $reading->spreadType->name_th }}
            </p>
            @if($reading->question)
            <p class="text-white mt-4 text-xl">
                คำถาม: "{{ $reading->question }}"
            </p>
            @endif
        </div>

        <!-- Cards Display -->
        <div class="mb-12">
            @if($reading->spreadType->card_count == 1)
                <!-- Single Card -->
                <div class="flex justify-center">
                    @foreach($reading->cards as $readingCard)
                    <div class="tarot-card" data-aos="flip-left">
                        <div class="bg-white rounded-xl shadow-2xl p-6 max-w-sm">
                            <img src="{{ $readingCard->card->image_url }}"
                                 alt="{{ $readingCard->card->getName() }}"
                                 class="w-full rounded-lg mb-4 {{ $readingCard->is_reversed ? 'transform rotate-180' : '' }}">
                            <h3 class="text-2xl font-bold text-gray-800 mb-2 text-center">
                                {{ $readingCard->card->getName() }}
                                @if($readingCard->is_reversed)
                                    <span class="text-red-600 text-sm">(กลับหัว)</span>
                                @endif
                            </h3>
                            <p class="text-gray-600 text-center mb-4">{{ $readingCard->position_name }}</p>
                            <div class="bg-purple-50 p-4 rounded-lg">
                                <p class="text-gray-700">
                                    {{ $readingCard->card->getMeaning($readingCard->is_reversed) }}
                                </p>
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>

            @elseif($reading->spreadType->card_count == 3)
                <!-- 3 Cards Layout -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    @foreach($reading->cards as $readingCard)
                    <div class="tarot-card" data-aos="flip-left" data-aos-delay="{{ $loop->index * 200 }}">
                        <div class="bg-white rounded-xl shadow-xl p-6">
                            <img src="{{ $readingCard->card->image_url }}"
                                 alt="{{ $readingCard->card->getName() }}"
                                 class="w-full rounded-lg mb-4 {{ $readingCard->is_reversed ? 'transform rotate-180' : '' }}">
                            <h3 class="text-xl font-bold text-gray-800 mb-2 text-center">
                                {{ $readingCard->card->getName() }}
                            </h3>
                            <p class="text-purple-600 font-semibold text-center mb-3">
                                {{ $readingCard->position_name }}
                                @if($readingCard->is_reversed) (กลับหัว) @endif
                            </p>
                            <div class="bg-purple-50 p-4 rounded-lg">
                                <p class="text-gray-700 text-sm">
                                    {{ $readingCard->card->getMeaning($readingCard->is_reversed) }}
                                </p>
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>

            @else
                <!-- Multiple Cards Grid -->
                <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-4">
                    @foreach($reading->cards as $readingCard)
                    <div class="tarot-card" data-aos="flip-left" data-aos-delay="{{ $loop->index * 100 }}">
                        <div class="bg-white rounded-lg shadow-lg p-3">
                            <img src="{{ $readingCard->card->image_url }}"
                                 alt="{{ $readingCard->card->getName() }}"
                                 class="w-full rounded mb-2 {{ $readingCard->is_reversed ? 'transform rotate-180' : '' }}">
                            <h4 class="text-sm font-bold text-gray-800 text-center mb-1">
                                {{ $readingCard->card->getName() }}
                            </h4>
                            <p class="text-xs text-purple-600 font-semibold text-center">
                                {{ $readingCard->position_name }}
                            </p>
                        </div>
                    </div>
                    @endforeach
                </div>
            @endif
        </div>

        <!-- Overall Interpretation -->
        @if($reading->interpretation)
        <div class="bg-white bg-opacity-10 backdrop-blur-lg rounded-2xl p-8 mb-8">
            <h2 class="text-2xl font-bold text-white mb-4">คำแนะนำรวม</h2>
            <p class="text-purple-100 text-lg leading-relaxed">
                {{ $reading->interpretation }}
            </p>
        </div>
        @endif

        <!-- Actions -->
        <div class="flex flex-wrap gap-4 justify-center">
            @auth
                @if(!$reading->is_saved)
                <button onclick="saveReading()" class="bg-green-600 hover:bg-green-700 text-white px-8 py-3 rounded-lg font-semibold transition-all">
                    <i class="fas fa-save mr-2"></i> บันทึกคำทำนาย
                </button>
                @else
                <div class="bg-green-600 text-white px-8 py-3 rounded-lg font-semibold">
                    <i class="fas fa-check mr-2"></i> บันทึกแล้ว
                </div>
                @endif
            @endauth

            <a href="{{ route('tarot.category', $reading->category->slug) }}" class="bg-purple-600 hover:bg-purple-700 text-white px-8 py-3 rounded-lg font-semibold transition-all">
                <i class="fas fa-redo mr-2"></i> ทำนายใหม่
            </a>

            <a href="{{ route('tarot.index') }}" class="bg-gray-600 hover:bg-gray-700 text-white px-8 py-3 rounded-lg font-semibold transition-all">
                <i class="fas fa-home mr-2"></i> หน้าหลัก
            </a>
        </div>
    </div>
</div>

@push('styles')
<link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
@endpush

@push('scripts')
<script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    AOS.init({
        duration: 1000,
        once: true
    });

    function saveReading() {
        fetch('{{ route('tarot.reading.save', $reading->id) }}', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                Swal.fire('สำเร็จ!', data.message, 'success').then(() => {
                    location.reload();
                });
            } else {
                Swal.fire('เกิดข้อผิดพลาด', data.error, 'error');
            }
        });
    }
</script>
@endpush
@endsection
