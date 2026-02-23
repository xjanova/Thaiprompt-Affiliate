{{-- แสดงข้อมูลมงคล — เลขมงคล สีมงคล ทิศมงคล --}}
{{-- @param $prediction — HoroscopeDailyPrediction --}}

<div class="grid grid-cols-3 gap-3">
    {{-- เลขมงคล --}}
    @if($prediction->lucky_number)
    <div class="bg-white/5 backdrop-blur-lg rounded-xl p-3 border border-white/10 text-center">
        <div class="text-amber-400 text-xs mb-1">🔢 เลขมงคล</div>
        <div class="text-2xl font-black text-white">
            {{ $prediction->lucky_number }}
        </div>
    </div>
    @endif

    {{-- สีมงคล --}}
    @if($prediction->lucky_color_th)
    <div class="bg-white/5 backdrop-blur-lg rounded-xl p-3 border border-white/10 text-center">
        <div class="text-pink-400 text-xs mb-1">🎨 สีมงคล</div>
        <div class="text-lg font-bold text-white">
            {{ $prediction->lucky_color_th }}
        </div>
    </div>
    @endif

    {{-- ทิศมงคล --}}
    @if($prediction->lucky_direction_th)
    <div class="bg-white/5 backdrop-blur-lg rounded-xl p-3 border border-white/10 text-center">
        <div class="text-cyan-400 text-xs mb-1">🧭 ทิศมงคล</div>
        <div class="text-lg font-bold text-white">
            {{ $prediction->lucky_direction_th }}
        </div>
    </div>
    @endif
</div>
