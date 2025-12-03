@extends('layouts.app')

@section('content')
{{-- หน้าประวัติการทำนายไพ่ทาโร่ต์ - V3 Premium Design --}}
<div class="min-h-screen bg-gradient-to-br from-slate-950 via-purple-950 to-indigo-950 relative overflow-hidden"
     x-data="{ ready: false }"
     x-init="setTimeout(() => ready = true, 100)">

    {{-- พื้นหลังแบบ Mystical --}}
    <div class="absolute inset-0 overflow-hidden pointer-events-none">
        {{-- ดาวกระพริบ --}}
        <div class="stars-bg absolute inset-0"></div>

        {{-- วงแหวนเวทย์มนต์ --}}
        <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[600px] h-[600px] opacity-10">
            <div class="absolute inset-0 border border-purple-400 rounded-full animate-spin-slow"></div>
            <div class="absolute inset-8 border border-pink-400 rounded-full animate-spin-slow-reverse"></div>
        </div>

        {{-- กลุ่มหมอก Gradient --}}
        <div class="absolute -top-40 -right-40 w-96 h-96 bg-purple-600/20 rounded-full blur-[100px] animate-pulse"></div>
        <div class="absolute -bottom-40 -left-40 w-96 h-96 bg-pink-600/20 rounded-full blur-[100px] animate-pulse"></div>
    </div>

    <div class="container mx-auto px-4 py-12 relative z-10">
        {{-- Header Section --}}
        <div class="text-center mb-12"
             x-show="ready"
             x-transition:enter="transition ease-out duration-500"
             x-transition:enter-start="opacity-0 transform -translate-y-4"
             x-transition:enter-end="opacity-100 transform translate-y-0">

            {{-- Icon --}}
            <div class="inline-block mb-4">
                <div class="relative">
                    <div class="absolute inset-0 bg-gradient-to-br from-purple-500 to-pink-500 rounded-2xl blur-xl opacity-50 animate-pulse"></div>
                    <div class="relative w-20 h-20 bg-gradient-to-br from-purple-600 via-pink-600 to-purple-600 rounded-2xl flex items-center justify-center shadow-2xl shadow-purple-500/30">
                        <span class="text-4xl">📜</span>
                    </div>
                </div>
            </div>

            <h1 class="text-4xl md:text-5xl font-black mb-4">
                <span class="bg-gradient-to-r from-purple-300 via-pink-300 to-cyan-300 bg-clip-text text-transparent">
                    ประวัติการทำนาย
                </span>
            </h1>
            <p class="text-purple-200/80 text-lg">
                ดูผลการทำนายที่ผ่านมาของคุณ
            </p>
        </div>

        @if($readings->count() > 0)
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 max-w-7xl mx-auto">
            @foreach($readings as $index => $reading)
            <div class="group perspective-1000"
                 x-show="ready"
                 x-transition:enter="transition ease-out duration-500"
                 x-transition:enter-start="opacity-0 transform translate-y-8"
                 x-transition:enter-end="opacity-100 transform translate-y-0"
                 style="transition-delay: {{ $index * 100 }}ms">

                <div class="relative transform-gpu transition-all duration-500 group-hover:scale-105 group-hover:rotate-y-2">
                    {{-- Glow Effect --}}
                    <div class="absolute inset-0 bg-gradient-to-br from-purple-500/30 via-pink-500/20 to-cyan-500/30 rounded-2xl blur-xl opacity-0 group-hover:opacity-100 transition-opacity duration-500"></div>

                    {{-- Card Body --}}
                    <div class="relative bg-gradient-to-br from-slate-900/90 via-purple-900/80 to-slate-900/90 backdrop-blur-xl rounded-2xl p-6 border border-white/10 group-hover:border-purple-400/50 shadow-2xl transition-all duration-500">

                        {{-- Header Row --}}
                        <div class="flex justify-between items-start mb-4">
                            <div class="flex-1">
                                <h3 class="text-xl font-bold text-white mb-1 group-hover:text-purple-200 transition-colors">
                                    {{ $reading->category->name_th }}
                                </h3>
                                <p class="text-purple-300/70 text-sm flex items-center gap-2">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                                    </svg>
                                    {{ $reading->spreadType->name_th }}
                                </p>
                            </div>

                            @if($reading->is_saved)
                                <span class="inline-flex items-center px-3 py-1 bg-gradient-to-r from-amber-500 to-yellow-500 text-slate-900 rounded-full text-xs font-bold shadow-lg shadow-amber-500/30">
                                    <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                        <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                                    </svg>
                                    บันทึกแล้ว
                                </span>
                            @endif
                        </div>

                        {{-- Question --}}
                        @if($reading->question)
                        <div class="bg-purple-900/40 rounded-xl p-3 mb-4 border border-purple-500/20">
                            <p class="text-purple-100/90 text-sm italic line-clamp-2">
                                "{{ Str::limit($reading->question, 80) }}"
                            </p>
                        </div>
                        @endif

                        {{-- Meta Info --}}
                        <div class="flex items-center justify-between text-sm mb-5">
                            <span class="text-purple-300/70 flex items-center gap-2">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                </svg>
                                {{ $reading->created_at->format('d/m/Y') }}
                            </span>

                            @if($reading->is_free)
                                <span class="px-3 py-1 bg-emerald-500/20 text-emerald-400 rounded-full text-xs font-medium border border-emerald-500/40">
                                    🎁 ฟรี
                                </span>
                            @else
                                <span class="px-3 py-1 bg-amber-500/20 text-amber-400 rounded-full text-xs font-medium border border-amber-500/40">
                                    ฿{{ number_format($reading->amount_paid) }}
                                </span>
                            @endif
                        </div>

                        {{-- Action Button --}}
                        <a href="{{ route('tarot.reading.show', $reading->id) }}"
                           class="group/btn relative block w-full overflow-hidden bg-gradient-to-r from-purple-600 to-pink-600 text-white text-center py-3 rounded-xl font-semibold shadow-lg shadow-purple-500/30 transform hover:scale-[1.02] transition-all duration-300">
                            {{-- Shine Effect --}}
                            <div class="absolute inset-0 bg-gradient-to-r from-transparent via-white/20 to-transparent -translate-x-full group-hover/btn:translate-x-full transition-transform duration-700"></div>
                            <span class="relative flex items-center justify-center gap-2">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                </svg>
                                ดูผลการทำนาย
                            </span>
                        </a>
                    </div>
                </div>
            </div>
            @endforeach
        </div>

        {{-- Pagination --}}
        <div class="mt-12 flex justify-center">
            {{ $readings->links() }}
        </div>

        @else
        {{-- Empty State --}}
        <div class="max-w-lg mx-auto"
             x-show="ready"
             x-transition:enter="transition ease-out duration-700"
             x-transition:enter-start="opacity-0 transform scale-95"
             x-transition:enter-end="opacity-100 transform scale-100">

            <div class="relative">
                {{-- Glow Effect --}}
                <div class="absolute inset-0 bg-gradient-to-br from-purple-500/20 via-pink-500/10 to-cyan-500/20 rounded-3xl blur-2xl"></div>

                {{-- Card Body --}}
                <div class="relative bg-gradient-to-br from-slate-900/90 via-purple-900/80 to-slate-900/90 backdrop-blur-xl rounded-3xl p-12 border border-white/10 text-center shadow-2xl">
                    <div class="text-7xl mb-6 animate-bounce-slow">🔮</div>
                    <h3 class="text-3xl font-bold text-white mb-4">
                        ยังไม่มีประวัติการทำนาย
                    </h3>
                    <p class="text-purple-200/80 text-lg mb-8">
                        เริ่มต้นการทำนายของคุณได้เลยตอนนี้
                    </p>
                    <a href="{{ route('tarot.index') }}"
                       class="group relative inline-flex items-center gap-3 overflow-hidden px-8 py-4 bg-gradient-to-r from-purple-600 via-pink-600 to-purple-600 text-white rounded-2xl font-bold text-lg shadow-2xl shadow-purple-500/30 transform hover:scale-105 transition-all duration-300">
                        {{-- Shine Effect --}}
                        <div class="absolute inset-0 bg-gradient-to-r from-transparent via-white/30 to-transparent -translate-x-full group-hover:translate-x-full transition-transform duration-1000"></div>
                        <span class="relative">✨</span>
                        <span class="relative">เริ่มทำนาย</span>
                    </a>
                </div>
            </div>
        </div>
        @endif
    </div>
</div>

@push('styles')
<style>
    /* ดาวบนพื้นหลัง */
    .stars-bg {
        background-image:
            radial-gradient(2px 2px at 20px 30px, white, transparent),
            radial-gradient(2px 2px at 40px 70px, rgba(255,255,255,0.8), transparent),
            radial-gradient(1px 1px at 90px 40px, white, transparent),
            radial-gradient(2px 2px at 160px 120px, rgba(255,255,255,0.6), transparent),
            radial-gradient(1px 1px at 230px 80px, white, transparent);
        background-size: 350px 200px;
        animation: stars-twinkle 8s ease-in-out infinite;
    }

    @keyframes stars-twinkle {
        0%, 100% { opacity: 0.4; }
        50% { opacity: 1; }
    }

    /* หมุนช้าๆ */
    @keyframes spin-slow {
        from { transform: translate(-50%, -50%) rotate(0deg); }
        to { transform: translate(-50%, -50%) rotate(360deg); }
    }
    .animate-spin-slow {
        animation: spin-slow 40s linear infinite;
    }
    .animate-spin-slow-reverse {
        animation: spin-slow 35s linear infinite reverse;
    }

    /* Bounce ช้า */
    @keyframes bounce-slow {
        0%, 100% { transform: translateY(0); }
        50% { transform: translateY(-10px); }
    }
    .animate-bounce-slow {
        animation: bounce-slow 3s ease-in-out infinite;
    }

    /* Perspective */
    .perspective-1000 { perspective: 1000px; }
    .rotate-y-2 { transform: rotateY(2deg); }

    /* Line Clamp */
    .line-clamp-2 {
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }
</style>
@endpush
@endsection
