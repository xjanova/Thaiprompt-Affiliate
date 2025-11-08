@extends('layouts.app')

@section('content')
<div class="min-h-screen bg-gradient-to-br from-purple-900 via-indigo-900 to-blue-900 py-12">
    <div class="container mx-auto px-4">
        <h1 class="text-4xl font-bold text-white mb-8">
            <i class="fas fa-star text-yellow-400 mr-2"></i>
            คำทำนายที่บันทึกไว้
        </h1>

        @if($readings->count() > 0)
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            @foreach($readings as $reading)
            <div class="bg-white bg-opacity-10 backdrop-blur-lg rounded-xl p-6 hover:bg-opacity-20 transition-all border border-white border-opacity-20">
                <div class="flex justify-between items-start mb-4">
                    <div>
                        <h3 class="text-xl font-bold text-white mb-1">
                            {{ $reading->category->name_th }}
                        </h3>
                        <p class="text-purple-200 text-sm">
                            {{ $reading->spreadType->name_th }}
                        </p>
                    </div>
                    <span class="bg-yellow-500 text-yellow-900 px-2 py-1 rounded text-xs">
                        <i class="fas fa-star"></i>
                    </span>
                </div>

                @if($reading->question)
                <p class="text-purple-100 mb-4 text-sm italic">
                    "{{ Str::limit($reading->question, 80) }}"
                </p>
                @endif

                <div class="text-purple-200 text-sm mb-4">
                    <i class="far fa-calendar mr-1"></i>
                    {{ $reading->created_at->format('d/m/Y H:i') }}
                </div>

                <a href="{{ route('tarot.reading.show', $reading->id) }}"
                   class="block w-full bg-purple-600 hover:bg-purple-700 text-white text-center py-2 rounded-lg font-semibold transition-all">
                    ดูผลการทำนาย
                </a>
            </div>
            @endforeach
        </div>

        <!-- Pagination -->
        <div class="mt-8">
            {{ $readings->links() }}
        </div>
        @else
        <div class="bg-white bg-opacity-10 backdrop-blur-lg rounded-xl p-12 text-center">
            <div class="text-6xl mb-4">⭐</div>
            <h3 class="text-2xl font-bold text-white mb-4">ยังไม่มีคำทำนายที่บันทึกไว้</h3>
            <p class="text-purple-200 mb-6">บันทึกคำทำนายที่คุณชอบเพื่อดูย้อนหลังได้</p>
            <a href="{{ route('tarot.history') }}" class="inline-block bg-purple-600 hover:bg-purple-700 text-white px-8 py-3 rounded-lg font-semibold transition-all">
                ดูประวัติทั้งหมด
            </a>
        </div>
        @endif
    </div>
</div>
@endsection
