{{-- การ์ดวันเกิด — ใช้ในหน้าแรกดูดวง (horoscope.home) และหน้าดวงรายวัน (horoscope.daily.index) --}}
{{-- @param $birthDay — array ['day', 'name_th', 'emoji', 'color_hex', 'planet', 'element', 'lucky_color'] --}}
{{-- @param $todayBirthDayPredictions — collection คำทำนายวันนี้ คีย์ด้วยเลขวัน 0-6 --}}
@php
    $prediction = $todayBirthDayPredictions[$birthDay['day']] ?? null;

    // ภาพเทพพาหนะประจำวันเกิด — เจนไว้ล่วงหน้า 7 ใบ ตั้งชื่อตามเลขวัน 0=อาทิตย์ ถึง 6=เสาร์
    $artPath = 'images/horoscope/birth-days/day-'.$birthDay['day'].'.webp';

    // สัตว์พาหนะประจำวันตามคติไทย — ใช้เขียน alt ให้สื่อความหมายจริง ไม่ใช่ชื่อไฟล์
    $mounts = ['ราชสีห์', 'ม้า', 'กระบือ', 'ช้าง', 'กวาง', 'โค', 'เสือ'];
    $mount = $mounts[$birthDay['day']] ?? '';
@endphp

<a href="{{ route('horoscope.daily.birth-day', $birthDay['day']) }}"
   class="group block focus:outline-none focus-visible:ring-2 focus-visible:ring-white/60 focus-visible:ring-offset-2 focus-visible:ring-offset-transparent rounded-2xl"
   aria-label="ดูคำทำนายดวง{{ $birthDay['name_th'] }}วันนี้">
    <article class="relative overflow-hidden rounded-2xl border border-white/10 bg-white/5 backdrop-blur-lg
                    transition-all duration-300 group-hover:-translate-y-1 group-hover:border-white/30
                    group-hover:shadow-2xl">

        {{-- ==================== ภาพประจำวัน ==================== --}}
        <div class="relative aspect-square overflow-hidden">
            <img src="{{ asset($artPath) }}"
                 alt="การ์ด{{ $birthDay['name_th'] }} — {{ $mount }}พาหนะประจำวัน ลายไทยสีทองบนพื้นจักรวาล"
                 width="640" height="640"
                 loading="lazy" decoding="async"
                 class="h-full w-full object-cover transition-transform duration-700 ease-out group-hover:scale-110">

            {{-- ฟิล์มไล่สีทับภาพ — ให้ชื่อวันอ่านออกทุกภาพ ไม่ต้องพึ่งความสว่างของภาพ --}}
            <div class="pointer-events-none absolute inset-0 bg-gradient-to-t from-[#0f0a1e] via-[#0f0a1e]/45 to-transparent"></div>

            {{-- แสงเรืองสีประจำวัน เจิมมุมบนซ้าย --}}
            <div class="pointer-events-none absolute inset-0 opacity-60 mix-blend-screen transition-opacity duration-300 group-hover:opacity-90"
                 style="background: radial-gradient(circle at 15% 10%, {{ $birthDay['color_hex'] }}55, transparent 55%);"></div>

            {{-- คะแนนดวงวันนี้ มุมบนขวา --}}
            @if($prediction)
                <div class="absolute top-2 right-2 flex items-center gap-1 rounded-full bg-black/45 px-2 py-1 backdrop-blur-md
                            ring-1 ring-white/15">
                    <svg class="h-3 w-3 text-amber-400" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true">
                        <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                    </svg>
                    <span class="text-[11px] font-bold leading-none text-white">
                        {{ number_format($prediction->average_score, 1) }}
                    </span>
                </div>
            @endif

            {{-- ชื่อวัน + ดาวประจำวัน วางบนภาพ --}}
            <div class="absolute inset-x-0 bottom-0 p-3">
                <h3 class="text-base md:text-lg font-black leading-tight text-white drop-shadow-[0_2px_8px_rgba(0,0,0,0.9)]">
                    {{ $birthDay['emoji'] }} {{ $birthDay['name_th'] }}
                </h3>
                <p class="mt-0.5 text-[11px] font-medium text-white/70 drop-shadow-[0_1px_6px_rgba(0,0,0,0.9)]">
                    ดาว{{ $birthDay['planet'] }}
                </p>
            </div>
        </div>

        {{-- ==================== ท้ายการ์ด — ธาตุ + สีมงคล ==================== --}}
        <div class="flex items-center justify-between gap-2 px-3 py-2.5">
            <div class="flex flex-wrap gap-1.5">
                <span class="rounded-full bg-white/5 px-2 py-0.5 text-[10px] text-purple-200/70 ring-1 ring-white/10">
                    {{ $birthDay['element'] }}
                </span>
                <span class="rounded-full bg-white/5 px-2 py-0.5 text-[10px] text-purple-200/70 ring-1 ring-white/10">
                    {{ $birthDay['lucky_color'] }}
                </span>
            </div>
            <svg class="h-4 w-4 shrink-0 text-white/30 transition-all duration-300 group-hover:translate-x-0.5 group-hover:text-white/80"
                 fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
            </svg>
        </div>

        {{-- เส้นเรืองสีประจำวันที่ขอบล่าง โผล่ตอน hover --}}
        <div class="pointer-events-none absolute inset-x-0 bottom-0 h-0.5 origin-left scale-x-0 transition-transform duration-300 group-hover:scale-x-100"
             style="background: linear-gradient(90deg, transparent, {{ $birthDay['color_hex'] }}, transparent);"></div>
    </article>
</a>
