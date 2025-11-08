@extends('layouts.app')

@section('content')
<div class="min-h-screen bg-gradient-to-br from-purple-900 via-indigo-900 to-blue-900 py-12">
    <div class="container mx-auto px-4">
        <h1 class="text-4xl font-bold text-white mb-8">ประวัติการทำนาย</h1>

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
                    @if($reading->is_saved)
                        <span class="bg-green-500 text-white px-2 py-1 rounded text-xs">
                            <i class="fas fa-star"></i>
                        </span>
                    @endif
                </div>

                @if($reading->question)
                <p class="text-purple-100 mb-4 text-sm italic">
                    "{{ Str::limit($reading->question, 80) }}"
                </p>
                @endif

                <div class="flex items-center justify-between text-purple-200 text-sm mb-4">
                    <span>
                        <i class="far fa-calendar mr-1"></i>
                        {{ $reading->created_at->format('d/m/Y') }}
                    </span>
                    <span>
                        @if($reading->is_free)
                            <span class="bg-green-600 px-2 py-1 rounded text-xs text-white">ฟรี</span>
                        @else
                            <span class="bg-yellow-600 px-2 py-1 rounded text-xs text-white">
                                {{ number_format($reading->amount_paid) }} บาท
                            </span>
                        @endif
                    </span>
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
            <div class="text-6xl mb-4">🔮</div>
            <h3 class="text-2xl font-bold text-white mb-4">ยังไม่มีประวัติการทำนาย</h3>
            <p class="text-purple-200 mb-6">เริ่มต้นการทำนายของคุณได้เลยตอนนี้</p>
            <a href="{{ route('tarot.index') }}" class="inline-block bg-purple-600 hover:bg-purple-700 text-white px-8 py-3 rounded-lg font-semibold transition-all">
                เริ่มทำนาย
            </a>
        </div>
        @endif
    </div>
</div>
@endsection
