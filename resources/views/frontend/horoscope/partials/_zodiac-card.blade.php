{{-- การ์ดราศี — ใช้ใน homepage grid --}}
{{-- @param $zodiac — HoroscopeZodiacSign model --}}
{{-- @param $prediction — HoroscopeDailyPrediction|null --}}
@php
    $prediction = $todayPredictions[$zodiac->id] ?? null;
    $averageScore = $prediction ? $prediction->average_score : null;
@endphp

<a href="{{ route('horoscope.daily.zodiac', $zodiac->slug) }}"
   class="group block perspective-1000">
    <div class="relative transform-gpu transition-all duration-500 group-hover:scale-105 group-hover:-translate-y-2">
        {{-- Glow Effect --}}
        <div class="absolute inset-0 rounded-2xl blur-xl opacity-0 group-hover:opacity-60 transition-opacity duration-500"
             style="background: linear-gradient(135deg, {{ $zodiac->color_hex ?? '#9333ea' }}40, {{ $zodiac->gradient_to ?? '#ec4899' }}40);"></div>

        {{-- Card Body --}}
        <div class="relative bg-white/5 backdrop-blur-lg rounded-2xl p-5 border border-white/10 group-hover:border-white/30 transition-all duration-300 card-hover-glow overflow-hidden">
            {{-- เรืองแสงมุมบน --}}
            <div class="absolute top-0 right-0 w-20 h-20 rounded-bl-full opacity-20"
                 style="background: linear-gradient(135deg, {{ $zodiac->color_hex ?? '#9333ea' }}, transparent);"></div>

            {{-- Emoji + ชื่อราศี --}}
            <div class="flex items-center gap-3 mb-3">
                <div class="w-12 h-12 rounded-xl flex items-center justify-center text-2xl shadow-lg shrink-0"
                     style="background: linear-gradient(135deg, {{ $zodiac->color_hex ?? '#9333ea' }}, {{ $zodiac->gradient_to ?? '#ec4899' }});">
                    {{ $zodiac->symbol_emoji }}
                </div>
                <div>
                    <h3 class="text-white font-bold text-lg leading-tight">{{ $zodiac->name_th }}</h3>
                    <p class="text-purple-300/60 text-xs">{{ $zodiac->name_en }}</p>
                </div>
            </div>

            {{-- ช่วงวันที่ --}}
            <p class="text-purple-300/50 text-xs mb-3">
                {{ $zodiac->date_range_start }} — {{ $zodiac->date_range_end }}
            </p>

            {{-- คะแนนวันนี้ (ถ้ามี) --}}
            @if($prediction)
                <div class="space-y-1.5">
                    @include('frontend.horoscope.partials._score-meter', ['label' => 'ภาพรวม', 'score' => $prediction->overall_score, 'color' => 'purple'])
                    @include('frontend.horoscope.partials._score-meter', ['label' => 'ความรัก', 'score' => $prediction->love_score, 'color' => 'pink'])
                    @include('frontend.horoscope.partials._score-meter', ['label' => 'การงาน', 'score' => $prediction->career_score, 'color' => 'cyan'])
                    @include('frontend.horoscope.partials._score-meter', ['label' => 'การเงิน', 'score' => $prediction->finance_score, 'color' => 'amber'])
                </div>

                {{-- เลขมงคล --}}
                @if($prediction->lucky_number)
                    <div class="mt-3 flex items-center gap-2">
                        <span class="text-xs text-purple-300/50">เลขมงคล:</span>
                        <span class="px-2 py-0.5 bg-amber-500/20 text-amber-300 text-xs font-bold rounded-full">
                            {{ $prediction->lucky_number }}
                        </span>
                    </div>
                @endif
            @else
                {{-- ยังไม่มีดวงวันนี้ --}}
                <div class="py-3 text-center">
                    <p class="text-purple-300/40 text-sm">รอดวงวันนี้...</p>
                    <p class="text-purple-300/30 text-xs mt-1">คลิกดูรายละเอียดราศี</p>
                </div>
            @endif

            {{-- ปุ่มดูเพิ่ม (Hover) --}}
            <div class="mt-3 opacity-0 group-hover:opacity-100 transition-all duration-300 transform translate-y-2 group-hover:translate-y-0">
                <div class="w-full py-2 text-center text-white/80 text-sm font-medium rounded-lg"
                     style="background: linear-gradient(135deg, {{ $zodiac->color_hex ?? '#9333ea' }}80, {{ $zodiac->gradient_to ?? '#ec4899' }}80);">
                    ดูรายละเอียด →
                </div>
            </div>
        </div>
    </div>
</a>
