{{-- หน้ารายละเอียดดวงราศี --}}
@extends('frontend.horoscope.layout')

@section('horoscope-content')
<div x-data="zodiacDetail()" x-init="init()">

    {{-- ==================== Header ==================== --}}
    <section class="relative py-10 md:py-14">
        <div class="container mx-auto px-4">

            {{-- Breadcrumb --}}
            <div class="mb-6">
                <nav class="flex items-center gap-2 text-sm text-purple-300/50">
                    <a href="{{ route('horoscope.home') }}" class="hover:text-purple-200 transition">🏠 หน้าแรก</a>
                    <span>/</span>
                    <a href="{{ route('horoscope.daily.index') }}" class="hover:text-purple-200 transition">ดวงรายวัน</a>
                    <span>/</span>
                    <span class="text-purple-200/80">{{ $zodiac->name_th }}</span>
                </nav>
            </div>

            {{-- Header Card --}}
            <div class="relative bg-white/5 backdrop-blur-lg rounded-3xl p-6 md:p-8 border border-white/10 overflow-hidden">
                {{-- เรืองแสงพื้นหลัง --}}
                <div class="absolute top-0 right-0 w-64 h-64 rounded-bl-full opacity-10"
                     style="background: linear-gradient(135deg, {{ $zodiac->color_hex ?? '#9333ea' }}, transparent);"></div>
                <div class="absolute bottom-0 left-0 w-48 h-48 rounded-tr-full opacity-10"
                     style="background: linear-gradient(135deg, transparent, {{ $zodiac->color_hex ?? '#9333ea' }});"></div>

                <div class="relative flex flex-col md:flex-row items-center gap-6">
                    {{-- Emoji + ข้อมูลราศี --}}
                    <div class="flex items-center gap-4">
                        <div class="w-20 h-20 md:w-24 md:h-24 rounded-2xl flex items-center justify-center text-5xl md:text-6xl shadow-2xl shrink-0"
                             style="background: linear-gradient(135deg, {{ $zodiac->color_hex ?? '#9333ea' }}, {{ $zodiac->gradient_to ?? '#ec4899' }}); box-shadow: 0 0 40px {{ $zodiac->color_hex ?? '#9333ea' }}40;">
                            {{ $zodiac->symbol_emoji }}
                        </div>
                        <div>
                            <h1 class="text-3xl md:text-4xl font-black text-white">ราศี{{ $zodiac->name_th }}</h1>
                            <p class="text-purple-300/60 text-sm mt-1">{{ $zodiac->name_en }} • {{ $zodiac->date_range_start }} — {{ $zodiac->date_range_end }}</p>
                            <div class="flex flex-wrap gap-2 mt-2">
                                <span class="px-2.5 py-0.5 bg-white/10 rounded-full text-xs text-purple-200/80">
                                    {{ $zodiac->element }}
                                </span>
                                <span class="px-2.5 py-0.5 bg-white/10 rounded-full text-xs text-purple-200/80">
                                    {{ $zodiac->ruling_planet }}
                                </span>
                            </div>
                        </div>
                    </div>

                    {{-- คะแนนรวมวันนี้ --}}
                    <div class="flex-1"></div>
                    @if($prediction)
                    <div class="text-center md:text-right shrink-0">
                        <div class="text-purple-300/50 text-xs mb-1">คะแนนรวมวันนี้</div>
                        <div class="text-5xl md:text-6xl font-black"
                             style="background: linear-gradient(135deg, {{ $zodiac->color_hex ?? '#9333ea' }}, {{ $zodiac->gradient_to ?? '#ec4899' }}); -webkit-background-clip: text; -webkit-text-fill-color: transparent;">
                            {{ number_format($prediction->average_score, 1) }}
                        </div>
                        <div class="text-purple-300/50 text-xs">จาก 5.0</div>
                    </div>
                    @endif
                </div>

                {{-- วันที่ --}}
                <div class="mt-4 text-center md:text-left">
                    <span class="inline-flex items-center gap-2 px-4 py-1.5 bg-white/5 rounded-full text-sm text-purple-200/70 border border-white/10">
                        📅 {{ today()->locale('th')->translatedFormat('l j F') }} {{ today()->year + 543 }}
                    </span>
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
                    ['label' => 'ภาพรวม', 'score' => $prediction->overall_score, 'icon' => '⭐', 'color' => 'purple'],
                    ['label' => 'ความรัก', 'score' => $prediction->love_score, 'icon' => '❤️', 'color' => 'pink'],
                    ['label' => 'การงาน', 'score' => $prediction->career_score, 'icon' => '💼', 'color' => 'cyan'],
                    ['label' => 'การเงิน', 'score' => $prediction->finance_score, 'icon' => '💰', 'color' => 'amber'],
                    ['label' => 'สุขภาพ', 'score' => $prediction->health_score, 'icon' => '💚', 'color' => 'emerald'],
                ];
            @endphp

            @foreach($dimensions as $dim)
                <div class="bg-white/5 backdrop-blur-lg rounded-2xl p-4 border border-white/10 text-center">
                    <div class="text-2xl mb-1">{{ $dim['icon'] }}</div>
                    <div class="text-white text-xs font-bold mb-2">{{ $dim['label'] }}</div>
                    {{-- แถบคะแนนแนวตั้ง --}}
                    <div class="flex items-end justify-center gap-0.5 h-8">
                        @for($i = 1; $i <= 5; $i++)
                            @php
                                $barColors = [
                                    'purple' => $i <= $dim['score'] ? 'bg-purple-500' : 'bg-white/10',
                                    'pink' => $i <= $dim['score'] ? 'bg-pink-500' : 'bg-white/10',
                                    'cyan' => $i <= $dim['score'] ? 'bg-cyan-500' : 'bg-white/10',
                                    'amber' => $i <= $dim['score'] ? 'bg-amber-500' : 'bg-white/10',
                                    'emerald' => $i <= $dim['score'] ? 'bg-emerald-500' : 'bg-white/10',
                                ];
                                $barColor = $barColors[$dim['color']] ?? 'bg-purple-500';
                            @endphp
                            <div class="w-2 rounded-t {{ $barColor }} transition-all duration-700"
                                 style="height: {{ $i * 20 }}%;"
                                 x-data
                                 x-init="$el.style.height = '0%'; setTimeout(() => $el.style.height = '{{ $i * 20 }}%', {{ 200 + $i * 100 }})">
                            </div>
                        @endfor
                    </div>
                    <div class="text-2xl font-black text-white mt-2">{{ $dim['score'] }}</div>
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
            {{-- ภาพรวม (กว้างเต็ม) --}}
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

            {{-- ความรัก --}}
            @if($prediction->love_prediction_th)
                @include('frontend.horoscope.partials._prediction-card', [
                    'title' => 'ความรัก',
                    'icon' => '❤️',
                    'text' => $prediction->love_prediction_th,
                    'score' => $prediction->love_score,
                    'color' => 'pink',
                ])
            @endif

            {{-- การงาน --}}
            @if($prediction->career_prediction_th)
                @include('frontend.horoscope.partials._prediction-card', [
                    'title' => 'การงาน',
                    'icon' => '💼',
                    'text' => $prediction->career_prediction_th,
                    'score' => $prediction->career_score,
                    'color' => 'cyan',
                ])
            @endif

            {{-- การเงิน --}}
            @if($prediction->finance_prediction_th)
                @include('frontend.horoscope.partials._prediction-card', [
                    'title' => 'การเงิน',
                    'icon' => '💰',
                    'text' => $prediction->finance_prediction_th,
                    'score' => $prediction->finance_score,
                    'color' => 'amber',
                ])
            @endif

            {{-- สุขภาพ --}}
            @if($prediction->health_prediction_th)
                @include('frontend.horoscope.partials._prediction-card', [
                    'title' => 'สุขภาพ',
                    'icon' => '💚',
                    'text' => $prediction->health_prediction_th,
                    'score' => $prediction->health_score,
                    'color' => 'emerald',
                ])
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
                ดวงราศี{{ $zodiac->name_th }}ประจำวันนี้กำลังถูกสร้างโดย AI กรุณากลับมาตรวจสอบใหม่ในภายหลัง
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

        <div class="bg-white/5 backdrop-blur-lg rounded-2xl p-5 border border-white/10 overflow-x-auto">
            <table class="w-full min-w-[600px]">
                <thead>
                    <tr class="text-purple-300/60 text-xs">
                        <th class="text-left pb-3 pl-2">วันที่</th>
                        <th class="text-center pb-3">ภาพรวม</th>
                        <th class="text-center pb-3">ความรัก</th>
                        <th class="text-center pb-3">การงาน</th>
                        <th class="text-center pb-3">การเงิน</th>
                        <th class="text-center pb-3">สุขภาพ</th>
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
                                    <span class="ml-1 px-1.5 py-0.5 bg-purple-500/30 rounded text-[10px] text-purple-300">วันนี้</span>
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
                                <span class="text-sm font-bold text-purple-300">
                                    {{ number_format($day->average_score, 1) }}
                                </span>
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
                <h3 class="text-white font-bold text-sm">แชร์ดวงราศี{{ $zodiac->name_th }}วันนี้</h3>
                <p class="text-purple-300/50 text-xs mt-1">ให้เพื่อนได้ดูดวงด้วย</p>
            </div>
            @include('frontend.horoscope.partials._share-buttons', [
                'url' => route('horoscope.daily.zodiac', $zodiac->slug),
                'title' => "ดวงราศี{$zodiac->name_th}วันนี้ — คะแนน " . ($prediction ? number_format($prediction->average_score, 1) : '?') . "/5",
            ])
        </div>
    </section>
    @endif

    {{-- ==================== Navigation ราศีอื่นๆ ==================== --}}
    <section class="container mx-auto px-4 pb-16">
        <h2 class="text-lg font-bold text-white mb-4 flex items-center gap-2">
            <span>🔀</span> ดูราศีอื่นๆ
        </h2>
        <div class="grid grid-cols-4 sm:grid-cols-6 lg:grid-cols-12 gap-2">
            @foreach($allZodiacs as $z)
                <a href="{{ route('horoscope.daily.zodiac', $z->slug) }}"
                   class="group relative text-center p-2 rounded-xl transition-all duration-200
                          {{ $z->id === $zodiac->id
                              ? 'bg-white/15 border border-white/30 ring-2 ring-purple-500/30'
                              : 'bg-white/5 border border-white/5 hover:bg-white/10 hover:border-white/20' }}">
                    <div class="text-xl md:text-2xl">{{ $z->symbol_emoji }}</div>
                    <div class="text-white text-[10px] mt-0.5 leading-tight font-medium">{{ $z->name_th }}</div>
                </a>
            @endforeach
        </div>
    </section>

</div>

<script>
function zodiacDetail() {
    return {
        init() {
            // Animate เมื่อ scroll เข้ามา
        },
    };
}
</script>
@endsection
