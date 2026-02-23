{{-- ผลลัพธ์เลขศาสตร์ — Shareable Result Page --}}
@extends('frontend.horoscope.layout')

@section('horoscope-content')
<div class="container mx-auto px-4 py-12">
    <div class="max-w-2xl mx-auto">

        {{-- Breadcrumb --}}
        <div class="mb-6">
            <nav class="flex items-center gap-2 text-sm text-purple-300/50">
                <a href="{{ route('horoscope.home') }}" class="hover:text-purple-200 transition">🏠 หน้าแรก</a>
                <span>/</span>
                <a href="{{ route('horoscope.numerology.index') }}" class="hover:text-purple-200 transition">เลขศาสตร์</a>
                <span>/</span>
                <span class="text-purple-200/80">ผลวิเคราะห์</span>
            </nav>
        </div>

        {{-- เลขชะตา --}}
        <div class="bg-gradient-to-br from-emerald-600/15 to-teal-600/15 backdrop-blur-lg rounded-3xl p-8 border border-emerald-500/20 text-center mb-6">
            <div class="text-purple-300/50 text-sm mb-2">
                {{ \App\Models\HoroscopeNumerologyReading::TYPE_ICONS[$reading->reading_type] ?? '🔢' }}
                {{ \App\Models\HoroscopeNumerologyReading::TYPE_LABELS_TH[$reading->reading_type] ?? 'เลขศาสตร์' }}
            </div>
            <div class="text-purple-200/70 text-sm mb-4">{{ $reading->input_value }}</div>

            {{-- ตัวเลขชะตา --}}
            <div class="text-8xl font-black mb-2"
                 style="background: linear-gradient(135deg, #10b981, #14b8a6, #06b6d4); -webkit-background-clip: text; -webkit-text-fill-color: transparent;">
                {{ $reading->numerology_number }}
            </div>
            <div class="text-white font-bold text-xl">ดาว{{ $numberMeaning['name'] }}</div>
            <div class="text-purple-300/60 text-sm mt-1">{{ $numberMeaning['meaning'] }}</div>

            {{-- ธาตุ + สี --}}
            <div class="flex justify-center gap-3 mt-4">
                <span class="px-3 py-1 bg-white/10 rounded-full text-xs text-purple-200/80">
                    ธาตุ{{ $numberMeaning['element'] }}
                </span>
                <span class="px-3 py-1 bg-white/10 rounded-full text-xs text-purple-200/80">
                    สี{{ $numberMeaning['color'] }}
                </span>
            </div>
        </div>

        {{-- การแยกตัวเลข --}}
        @if($breakdown)
        <div class="bg-white/5 backdrop-blur-lg rounded-2xl p-5 border border-white/10 mb-4">
            <h3 class="text-white font-bold text-sm mb-3 flex items-center gap-2">
                <span>📊</span> วิธีคำนวณ
            </h3>
            @if(isset($breakdown['digits']))
                <div class="flex flex-wrap items-center gap-1 text-lg">
                    @foreach($breakdown['digits'] as $i => $d)
                        <span class="w-8 h-8 bg-emerald-500/20 border border-emerald-500/30 rounded-lg flex items-center justify-center text-emerald-300 font-bold text-sm">
                            {{ $d }}
                        </span>
                        @if(!$loop->last)
                            <span class="text-purple-300/30 text-xs">+</span>
                        @endif
                    @endforeach
                    <span class="text-purple-300/30 text-sm mx-2">=</span>
                    <span class="text-white font-bold">{{ $breakdown['sum'] ?? '' }}</span>
                    <span class="text-purple-300/30 text-sm mx-2">→</span>
                    <span class="w-10 h-10 bg-gradient-to-br from-emerald-500 to-teal-500 rounded-xl flex items-center justify-center text-white font-black text-lg shadow-lg">
                        {{ $reading->numerology_number }}
                    </span>
                </div>
            @endif
        </div>
        @endif

        {{-- คำวิเคราะห์ --}}
        @if($reading->ai_analysis_th)
        <div class="bg-white/5 backdrop-blur-lg rounded-2xl p-6 border border-white/10 mb-4">
            <h3 class="text-white font-bold text-lg mb-3 flex items-center gap-2">
                <span>🔮</span> คำวิเคราะห์
            </h3>
            <p class="text-purple-200/80 text-sm leading-relaxed whitespace-pre-line">{{ $reading->ai_analysis_th }}</p>
        </div>
        @endif

        {{-- คำแนะนำ --}}
        @if($reading->ai_advice_th)
        <div class="bg-gradient-to-br from-amber-600/10 to-orange-600/10 backdrop-blur-lg rounded-2xl p-6 border border-amber-500/20 mb-4">
            <h3 class="text-white font-bold text-lg mb-3 flex items-center gap-2">
                <span>💡</span> คำแนะนำ
            </h3>
            <p class="text-purple-200/80 text-sm leading-relaxed whitespace-pre-line">{{ $reading->ai_advice_th }}</p>
        </div>
        @endif

        {{-- เลขมงคล + สีมงคล --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-6">
            @if($reading->lucky_numbers)
            <div class="bg-white/5 backdrop-blur-lg rounded-2xl p-5 border border-white/10">
                <h4 class="text-white font-bold text-sm mb-3 flex items-center gap-2">🎯 เลขมงคล</h4>
                <div class="flex flex-wrap gap-2">
                    @foreach($reading->lucky_numbers as $num)
                        <span class="px-4 py-2 bg-gradient-to-r from-amber-500/20 to-yellow-500/20 border border-amber-500/30 rounded-xl text-amber-300 font-bold text-lg">
                            {{ $num }}
                        </span>
                    @endforeach
                </div>
            </div>
            @endif

            @if($reading->lucky_colors)
            <div class="bg-white/5 backdrop-blur-lg rounded-2xl p-5 border border-white/10">
                <h4 class="text-white font-bold text-sm mb-3 flex items-center gap-2">🎨 สีมงคล</h4>
                <div class="flex flex-wrap gap-2">
                    @foreach($reading->lucky_colors as $color)
                        <span class="px-4 py-2 bg-white/10 border border-white/20 rounded-xl text-white font-medium text-sm">
                            สี{{ $color }}
                        </span>
                    @endforeach
                </div>
            </div>
            @endif
        </div>

        {{-- แชร์ + วิเคราะห์ใหม่ --}}
        <div class="flex flex-col sm:flex-row items-center justify-between gap-4">
            @include('frontend.horoscope.partials._share-buttons', [
                'url' => route('horoscope.numerology.result', $reading->id),
                'title' => "เลขชะตา {$reading->numerology_number} — {$numberMeaning['name']}",
            ])
            <a href="{{ route('horoscope.numerology.index') }}"
               class="px-5 py-2.5 bg-gradient-to-r from-emerald-500 to-teal-500 text-white font-bold rounded-xl shadow-lg hover:scale-105 transition-all text-sm">
                🔄 วิเคราะห์ใหม่
            </a>
        </div>

    </div>
</div>
@endsection
