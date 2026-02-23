{{-- การ์ดวันเกิด — ใช้ใน homepage --}}
{{-- @param $birthDay — array ['day', 'name_th', 'emoji', 'color_hex', 'planet', 'element', 'lucky_color'] --}}
{{-- @param $prediction — HoroscopeDailyPrediction|null --}}
@php
    $prediction = $todayBirthDayPredictions[$birthDay['day']] ?? null;
@endphp

<a href="{{ route('horoscope.daily.birth-day', $birthDay['day']) }}"
   class="group block">
    <div class="relative bg-white/5 backdrop-blur-lg rounded-2xl p-4 border border-white/10 group-hover:border-white/30 transition-all duration-300 group-hover:scale-105 group-hover:-translate-y-1 overflow-hidden">
        {{-- เรืองแสงมุม --}}
        <div class="absolute top-0 left-0 w-16 h-16 rounded-br-full opacity-20"
             style="background: {{ $birthDay['color_hex'] }};"></div>

        {{-- Emoji + ชื่อวัน --}}
        <div class="flex items-center gap-3 mb-2">
            <div class="w-10 h-10 rounded-lg flex items-center justify-center text-xl shadow-md"
                 style="background: {{ $birthDay['color_hex'] }}30; border: 1px solid {{ $birthDay['color_hex'] }}50;">
                {{ $birthDay['emoji'] }}
            </div>
            <div>
                <h4 class="text-white font-bold text-sm">{{ $birthDay['name_th'] }}</h4>
                <p class="text-purple-300/50 text-xs">{{ $birthDay['planet'] }}</p>
            </div>
        </div>

        {{-- ธาตุ + สีมงคล --}}
        <div class="flex gap-2 text-xs mb-2">
            <span class="px-2 py-0.5 bg-white/5 rounded-full text-purple-300/60">
                {{ $birthDay['element'] }}
            </span>
            <span class="px-2 py-0.5 bg-white/5 rounded-full text-purple-300/60">
                {{ $birthDay['lucky_color'] }}
            </span>
        </div>

        {{-- คะแนน (ถ้ามี) --}}
        @if($prediction)
            <div class="flex items-center gap-1 mt-1">
                @for($i = 1; $i <= 5; $i++)
                    <svg class="w-3.5 h-3.5 {{ $i <= $prediction->overall_score ? 'text-amber-400' : 'text-white/10' }}" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                    </svg>
                @endfor
            </div>
        @endif

        {{-- Arrow --}}
        <div class="absolute bottom-3 right-3 opacity-0 group-hover:opacity-100 transition-all duration-300 transform translate-x-2 group-hover:translate-x-0">
            <svg class="w-4 h-4 text-white/60" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
            </svg>
        </div>
    </div>
</a>
