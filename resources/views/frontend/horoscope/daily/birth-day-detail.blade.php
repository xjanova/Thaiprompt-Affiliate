{{-- หน้ารายละเอียดดวงตามวันเกิด --}}
@extends('frontend.horoscope.layout')

@section('horoscope-content')
<div x-data="birthDayDetail()" x-init="init()">

    {{-- ==================== Header ==================== --}}
    <section class="relative py-10 md:py-14">
        <div class="container mx-auto px-4">

            {{-- Breadcrumb --}}
            <div class="mb-6">
                <nav class="flex items-center gap-2 text-sm text-purple-300/50">
                    <a href="{{ route('horoscope.home') }}" class="hover:text-purple-200 transition">🏠 หน้าแรก</a>
                    <span>/</span>
                    <a href="{{ route('horoscope.daily.index', ['tab' => 'birthday']) }}" class="hover:text-purple-200 transition">ดวงรายวัน</a>
                    <span>/</span>
                    <span class="text-purple-200/80">{{ $birthDayInfo['name_th'] }}</span>
                </nav>
            </div>

            {{-- Header Card --}}
            <div class="relative bg-white/5 backdrop-blur-lg rounded-3xl p-6 md:p-8 border border-white/10 overflow-hidden">
                {{-- เรืองแสงสีประจำวัน --}}
                <div class="absolute top-0 right-0 w-64 h-64 rounded-bl-full opacity-15"
                     style="background: linear-gradient(135deg, {{ $birthDayInfo['color_hex'] }}, transparent);"></div>
                <div class="absolute bottom-0 left-0 w-40 h-40 rounded-tr-full opacity-10"
                     style="background: {{ $birthDayInfo['color_hex'] }};"></div>

                <div class="relative flex flex-col md:flex-row items-center gap-6">
                    {{-- Emoji + ข้อมูลวัน --}}
                    <div class="flex items-center gap-4">
                        <div class="w-20 h-20 md:w-24 md:h-24 rounded-2xl flex items-center justify-center text-5xl md:text-6xl shadow-2xl shrink-0"
                             style="background: {{ $birthDayInfo['color_hex'] }}30; border: 2px solid {{ $birthDayInfo['color_hex'] }}50; box-shadow: 0 0 40px {{ $birthDayInfo['color_hex'] }}30;">
                            {{ $birthDayInfo['emoji'] }}
                        </div>
                        <div>
                            <h1 class="text-3xl md:text-4xl font-black text-white">{{ $birthDayInfo['name_th'] }}</h1>
                            <p class="text-purple-300/60 text-sm mt-1">ดวงประจำ{{ today()->locale('th')->translatedFormat('l j F') }} {{ today()->year + 543 }}</p>
                            <div class="flex flex-wrap gap-2 mt-2">
                                <span class="px-2.5 py-0.5 rounded-full text-xs text-white/80"
                                      style="background: {{ $birthDayInfo['color_hex'] }}30; border: 1px solid {{ $birthDayInfo['color_hex'] }}50;">
                                    {{ $birthDayInfo['planet_symbol'] }} {{ $birthDayInfo['planet_name'] }}
                                </span>
                                <span class="px-2.5 py-0.5 bg-white/10 rounded-full text-xs text-purple-200/80">
                                    ธาตุ{{ $birthDayInfo['element'] }}
                                </span>
                                <span class="px-2.5 py-0.5 bg-white/10 rounded-full text-xs text-purple-200/80">
                                    สี{{ $birthDayInfo['lucky_color'] }}
                                </span>
                            </div>
                        </div>
                    </div>

                    {{-- คะแนนรวม --}}
                    <div class="flex-1"></div>
                    @if($prediction)
                    <div class="text-center md:text-right shrink-0">
                        <div class="text-purple-300/50 text-xs mb-1">คะแนนรวมวันนี้</div>
                        <div class="text-5xl md:text-6xl font-black"
                             style="background: linear-gradient(135deg, {{ $birthDayInfo['color_hex'] }}, {{ $birthDayInfo['planet_color'] }}); -webkit-background-clip: text; -webkit-text-fill-color: transparent;">
                            {{ number_format($prediction->average_score, 1) }}
                        </div>
                        <div class="text-purple-300/50 text-xs">จาก 5.0</div>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </section>

    {{-- ==================== ข้อมูลโหราศาสตร์ไทย ==================== --}}
    <section class="container mx-auto px-4 mb-8">
        <h2 class="text-xl md:text-2xl font-bold text-white mb-5 flex items-center gap-2">
            <span class="text-2xl">🪐</span> ข้อมูลโหราศาสตร์ไทย
        </h2>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            {{-- ดาวประจำวัน --}}
            <div class="bg-gradient-to-br from-indigo-600/15 to-purple-900/15 backdrop-blur-lg rounded-2xl p-5 border border-indigo-500/20">
                <div class="flex items-center gap-3 mb-3">
                    <div class="w-12 h-12 rounded-xl flex items-center justify-center text-2xl"
                         style="background: {{ $birthDayInfo['planet_color'] }}30; border: 1px solid {{ $birthDayInfo['planet_color'] }}50;">
                        {{ $birthDayInfo['planet_symbol'] }}
                    </div>
                    <div>
                        <div class="text-purple-300/60 text-xs">ดาวประจำวัน</div>
                        <div class="text-white font-bold text-lg">{{ $birthDayInfo['planet_name'] }}</div>
                    </div>
                </div>
                <p class="text-purple-200/60 text-sm">
                    ดาว{{ $birthDayInfo['planet_name'] }}เป็นดาวประจำ{{ $birthDayInfo['name_th'] }}
                    ส่งอิทธิพลต่อชีวิตในทุกด้าน
                </p>
            </div>

            {{-- ดาวมิตร --}}
            <div class="bg-gradient-to-br from-emerald-600/15 to-teal-900/15 backdrop-blur-lg rounded-2xl p-5 border border-emerald-500/20">
                <div class="flex items-center gap-2 mb-3">
                    <span class="text-2xl">🤝</span>
                    <div class="text-purple-300/60 text-xs">ดาวมิตร</div>
                </div>
                <div class="text-white font-bold mb-2">ดาวที่ส่งพลังบวก</div>
                <div class="flex flex-wrap gap-1.5">
                    @foreach($birthDayInfo['friends'] as $friend)
                        <span class="px-2.5 py-1 bg-emerald-500/15 border border-emerald-500/20 rounded-lg text-emerald-300 text-xs font-medium">
                            {{ $friend }}
                        </span>
                    @endforeach
                </div>
            </div>

            {{-- ดาวศัตรู --}}
            <div class="bg-gradient-to-br from-red-600/15 to-rose-900/15 backdrop-blur-lg rounded-2xl p-5 border border-red-500/20">
                <div class="flex items-center gap-2 mb-3">
                    <span class="text-2xl">⚔️</span>
                    <div class="text-purple-300/60 text-xs">ดาวศัตรู</div>
                </div>
                <div class="text-white font-bold mb-2">ดาวที่ต้องระวัง</div>
                <div class="flex flex-wrap gap-1.5">
                    @foreach($birthDayInfo['enemies'] as $enemy)
                        <span class="px-2.5 py-1 bg-red-500/15 border border-red-500/20 rounded-lg text-red-300 text-xs font-medium">
                            {{ $enemy }}
                        </span>
                    @endforeach
                </div>
            </div>
        </div>
    </section>

    @if($prediction)
    {{-- ==================== Score Meters ==================== --}}
    <section class="container mx-auto px-4 mb-8">
        <div class="grid grid-cols-2 sm:grid-cols-5 gap-3">
            @php
                $dimensions = [
                    ['label' => 'ภาพรวม', 'score' => $prediction->overall_score, 'icon' => '⭐', 'color' => $birthDayInfo['color_hex']],
                    ['label' => 'ความรัก', 'score' => $prediction->love_score, 'icon' => '❤️', 'color' => '#ec4899'],
                    ['label' => 'การงาน', 'score' => $prediction->career_score, 'icon' => '💼', 'color' => '#06b6d4'],
                    ['label' => 'การเงิน', 'score' => $prediction->finance_score, 'icon' => '💰', 'color' => '#f59e0b'],
                    ['label' => 'สุขภาพ', 'score' => $prediction->health_score, 'icon' => '💚', 'color' => '#10b981'],
                ];
            @endphp

            @foreach($dimensions as $dim)
                <div class="bg-white/5 backdrop-blur-lg rounded-2xl p-4 border border-white/10 text-center">
                    <div class="text-2xl mb-1">{{ $dim['icon'] }}</div>
                    <div class="text-white text-xs font-bold mb-2">{{ $dim['label'] }}</div>
                    {{-- วงกลมคะแนน --}}
                    <div class="relative w-14 h-14 mx-auto">
                        <svg class="w-14 h-14 transform -rotate-90" viewBox="0 0 56 56">
                            <circle cx="28" cy="28" r="24" fill="none" stroke="rgba(255,255,255,0.1)" stroke-width="4"/>
                            <circle cx="28" cy="28" r="24" fill="none"
                                    stroke="{{ $dim['color'] }}"
                                    stroke-width="4"
                                    stroke-dasharray="{{ 2 * 3.14159 * 24 }}"
                                    stroke-dashoffset="{{ 2 * 3.14159 * 24 * (1 - $dim['score'] / 5) }}"
                                    stroke-linecap="round"
                                    class="transition-all duration-1000"
                                    x-data
                                    x-init="$el.style.strokeDashoffset = '{{ 2 * 3.14159 * 24 }}'; setTimeout(() => $el.style.strokeDashoffset = '{{ 2 * 3.14159 * 24 * (1 - $dim['score'] / 5) }}', 300)"/>
                        </svg>
                        <div class="absolute inset-0 flex items-center justify-center">
                            <span class="text-lg font-black text-white">{{ $dim['score'] }}</span>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </section>

    {{-- ==================== ข้อมูลมงคล ==================== --}}
    <section class="container mx-auto px-4 mb-8">
        @include('frontend.horoscope.partials._lucky-info', ['prediction' => $prediction])
    </section>

    {{-- ==================== คำทำนาย 5 ด้าน ==================== --}}
    <section class="container mx-auto px-4 mb-8">
        <h2 class="text-xl md:text-2xl font-bold text-white mb-6 flex items-center gap-2">
            <span class="text-2xl">🔮</span> คำทำนายวันนี้
        </h2>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            {{-- ภาพรวม --}}
            @if($prediction->overall_prediction_th)
                <div class="md:col-span-2">
                    @include('frontend.horoscope.partials._prediction-card', [
                        'title' => 'ภาพรวม',
                        'icon' => '⭐',
                        'text' => $prediction->overall_prediction_th,
                        'score' => $prediction->overall_score,
                        'color' => 'purple',
                    ])
                </div>
            @endif

            @if($prediction->love_prediction_th)
                @include('frontend.horoscope.partials._prediction-card', [
                    'title' => 'ความรัก',
                    'icon' => '❤️',
                    'text' => $prediction->love_prediction_th,
                    'score' => $prediction->love_score,
                    'color' => 'pink',
                ])
            @endif

            @if($prediction->career_prediction_th)
                @include('frontend.horoscope.partials._prediction-card', [
                    'title' => 'การงาน',
                    'icon' => '💼',
                    'text' => $prediction->career_prediction_th,
                    'score' => $prediction->career_score,
                    'color' => 'cyan',
                ])
            @endif

            @if($prediction->finance_prediction_th)
                @include('frontend.horoscope.partials._prediction-card', [
                    'title' => 'การเงิน',
                    'icon' => '💰',
                    'text' => $prediction->finance_prediction_th,
                    'score' => $prediction->finance_score,
                    'color' => 'amber',
                ])
            @endif

            @if($prediction->health_prediction_th)
                @include('frontend.horoscope.partials._prediction-card', [
                    'title' => 'สุขภาพ',
                    'icon' => '💚',
                    'text' => $prediction->health_prediction_th,
                    'score' => $prediction->health_score,
                    'color' => 'emerald',
                ])
            @endif

            {{-- ⏰ (2026-08-02) ช่วงเวลาของวัน — เช้า/เที่ยง/บ่าย/เย็น/กลางคืน
                 คำนวณจากตำแหน่งดาวจริงรายช่วง (DailyAstroBrief::periods)
                 ⚠️ กล่องแชทแนบลิงก์มาหน้านี้พร้อมข้อความ "และช่วงเวลาของวัน"
                    ถ้าบล็อกนี้หาย = สัญญาแล้วลูกค้าเปิดมาไม่เจอ --}}
            @if($prediction->time_prediction_th)
                @php
                    // แยกทีละบรรทัด แล้วตัดเป็น "ชื่อช่วง : เนื้อหา" ถ้ามีเครื่องหมาย :
                    // (ทำใน @php ไม่ใช่ใน directive — กันปัญหาวงเล็บซ้อนใน Blade)
                    $timeRows = [];
                    foreach (preg_split('/\R/u', $prediction->time_prediction_th) as $line) {
                        $line = trim($line);
                        if ($line === '') {
                            continue;
                        }
                        $pos = mb_strpos($line, ':');
                        if ($pos !== false && $pos <= 40) {
                            $timeRows[] = [
                                'label' => trim(mb_substr($line, 0, $pos)),
                                'text' => trim(mb_substr($line, $pos + 1)),
                            ];
                        } else {
                            $timeRows[] = ['label' => null, 'text' => $line];
                        }
                    }
                @endphp

                <div class="md:col-span-2">
                    <div class="bg-white/5 backdrop-blur-lg rounded-2xl p-5 border border-white/10">
                        <h3 class="text-white font-bold mb-4 flex items-center gap-2">
                            <span class="text-xl">⏰</span> ช่วงเวลาของวัน
                        </h3>

                        <div class="space-y-3">
                            @foreach($timeRows as $row)
                                <div class="flex flex-col sm:flex-row sm:items-start gap-1 sm:gap-3">
                                    @if($row['label'])
                                        <span class="shrink-0 text-indigo-300 font-semibold text-sm sm:w-44">
                                            {{ $row['label'] }}
                                        </span>
                                    @endif
                                    <span class="text-purple-100/80 text-sm leading-relaxed">
                                        {{ $row['text'] }}
                                    </span>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </section>
    @else
    {{-- ยังไม่มีคำทำนาย --}}
    <section class="container mx-auto px-4 mb-8">
        <div class="bg-white/5 backdrop-blur-lg rounded-2xl p-12 border border-white/10 text-center">
            <div class="text-5xl mb-4">🌙</div>
            <h3 class="text-white text-xl font-bold mb-2">ยังไม่มีดวงวันนี้</h3>
            <p class="text-purple-300/60 text-sm">
                ดวง{{ $birthDayInfo['name_th'] }}ประจำวันนี้กำลังถูกสร้างโดย AI กรุณากลับมาตรวจสอบใหม่ในภายหลัง
            </p>
        </div>
    </section>
    @endif

    {{-- ==================== ประวัติ 7 วัน ==================== --}}
    @if($history->isNotEmpty())
    <section class="container mx-auto px-4 mb-8">
        <h2 class="text-xl md:text-2xl font-bold text-white mb-6 flex items-center gap-2">
            <span class="text-2xl">📊</span> ดวงย้อนหลัง 7 วัน
        </h2>

        {{-- กราฟแท่งคะแนน --}}
        <div class="bg-white/5 backdrop-blur-lg rounded-2xl p-5 border border-white/10">
            <div class="flex items-end justify-between gap-2 h-40">
                @foreach($history->reverse() as $day)
                    @php
                        $avg = $day->average_score;
                        $heightPercent = ($avg / 5) * 100;
                        $isToday = $day->target_date->isToday();
                        $barColor = $isToday ? $birthDayInfo['color_hex'] : 'rgba(168, 85, 247, 0.5)';
                    @endphp
                    <div class="flex-1 flex flex-col items-center">
                        <div class="text-xs font-bold {{ $isToday ? 'text-white' : 'text-purple-300/50' }} mb-1">
                            {{ number_format($avg, 1) }}
                        </div>
                        <div class="w-full max-w-[40px] rounded-t-lg transition-all duration-1000"
                             style="height: {{ $heightPercent }}%; background: {{ $barColor }}; {{ $isToday ? 'box-shadow: 0 0 15px ' . $birthDayInfo['color_hex'] . '60;' : '' }}"
                             x-data
                             x-init="$el.style.height = '0%'; setTimeout(() => $el.style.height = '{{ $heightPercent }}%', 300)">
                        </div>
                        <div class="text-[10px] mt-2 {{ $isToday ? 'text-white font-bold' : 'text-purple-300/40' }}">
                            {{ $day->target_date->locale('th')->translatedFormat('D') }}
                        </div>
                        @if($isToday)
                            <div class="text-[9px] text-purple-300/60 mt-0.5">วันนี้</div>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>

        {{-- ตารางรายละเอียด --}}
        <div class="mt-4 bg-white/5 backdrop-blur-lg rounded-2xl p-5 border border-white/10 overflow-x-auto">
            <table class="w-full min-w-[600px]">
                <thead>
                    <tr class="text-purple-300/60 text-xs">
                        <th class="text-left pb-3 pl-2">วันที่</th>
                        <th class="text-center pb-3">⭐</th>
                        <th class="text-center pb-3">❤️</th>
                        <th class="text-center pb-3">💼</th>
                        <th class="text-center pb-3">💰</th>
                        <th class="text-center pb-3">💚</th>
                        <th class="text-center pb-3">เฉลี่ย</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($history as $day)
                        @php
                            $isToday = $day->target_date->isToday();
                        @endphp
                        <tr class="{{ $isToday ? 'bg-white/5' : '' }} border-t border-white/5">
                            <td class="py-3 pl-2 text-sm {{ $isToday ? 'text-white font-bold' : 'text-purple-200/70' }}">
                                {{ $day->target_date->locale('th')->translatedFormat('D j M') }}
                                @if($isToday)
                                    <span class="ml-1 px-1.5 py-0.5 rounded text-[10px] text-white/80"
                                          style="background: {{ $birthDayInfo['color_hex'] }}40;">วันนี้</span>
                                @endif
                            </td>
                            @foreach(['overall_score', 'love_score', 'career_score', 'finance_score', 'health_score'] as $scoreField)
                                @php
                                    $s = $day->{$scoreField};
                                    $starClass = $s >= 4 ? 'text-amber-400' : ($s >= 3 ? 'text-white/70' : 'text-red-400/70');
                                @endphp
                                <td class="py-3 text-center">
                                    <span class="text-sm font-bold {{ $starClass }}">{{ $s }}</span>
                                </td>
                            @endforeach
                            <td class="py-3 text-center">
                                <span class="text-sm font-bold text-purple-300">{{ number_format($day->average_score, 1) }}</span>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </section>
    @endif

    {{-- ==================== แชร์ ==================== --}}
    @if($prediction)
    <section class="container mx-auto px-4 mb-8">
        <div class="bg-white/5 backdrop-blur-lg rounded-2xl p-5 border border-white/10 flex flex-col sm:flex-row items-center justify-between gap-4">
            <div>
                <h3 class="text-white font-bold text-sm">แชร์ดวง{{ $birthDayInfo['name_th'] }}วันนี้</h3>
                <p class="text-purple-300/50 text-xs mt-1">ให้เพื่อนที่เกิด{{ $birthDayInfo['name_th'] }}ได้ดูดวงด้วย</p>
            </div>
            @include('frontend.horoscope.partials._share-buttons', [
                'url' => route('horoscope.daily.birth-day', $birthDayInfo['day']),
                'title' => "ดวง{$birthDayInfo['name_th']}วันนี้ — คะแนน " . ($prediction ? number_format($prediction->average_score, 1) : '?') . "/5",
            ])
        </div>
    </section>
    @endif

    {{-- ==================== Navigation วันอื่นๆ ==================== --}}
    <section class="container mx-auto px-4 pb-16">
        <h2 class="text-lg font-bold text-white mb-4 flex items-center gap-2">
            <span>🔀</span> ดูวันเกิดอื่นๆ
        </h2>
        @php
            $dayNames = ['วันอาทิตย์', 'วันจันทร์', 'วันอังคาร', 'วันพุธ', 'วันพฤหัสบดี', 'วันศุกร์', 'วันเสาร์'];
            $dayEmojis = ['☀️', '🌙', '🔴', '🟢', '🟠', '🔵', '🟣'];
            $dayHexColors = ['#ef4444', '#eab308', '#ec4899', '#22c55e', '#f97316', '#06b6d4', '#7c3aed'];
        @endphp
        <div class="grid grid-cols-4 sm:grid-cols-7 gap-2">
            @for($d = 0; $d <= 6; $d++)
                <a href="{{ route('horoscope.daily.birth-day', $d) }}"
                   class="group text-center p-3 rounded-xl transition-all duration-200
                          {{ $d === $birthDayInfo['day']
                              ? 'bg-white/15 border border-white/30 ring-2'
                              : 'bg-white/5 border border-white/5 hover:bg-white/10 hover:border-white/20' }}"
                   style="{{ $d === $birthDayInfo['day'] ? 'ring-color: ' . $dayHexColors[$d] . '50;' : '' }}">
                    <div class="text-xl md:text-2xl">{{ $dayEmojis[$d] }}</div>
                    <div class="text-white text-[10px] mt-1 leading-tight font-medium">{{ $dayNames[$d] }}</div>
                </a>
            @endfor
        </div>
    </section>

</div>

<script>
function birthDayDetail() {
    return {
        init() {
            // Animation ต่างๆ
        },
    };
}
</script>
@endsection
