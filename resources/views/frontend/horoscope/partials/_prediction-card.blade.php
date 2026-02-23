{{-- การ์ดแสดงคำทำนายแต่ละด้าน --}}
{{-- @param string $title -- ชื่อด้าน เช่น 'ความรัก' --}}
{{-- @param string $icon -- ไอคอน เช่น '❤️' --}}
{{-- @param string $text -- คำทำนาย --}}
{{-- @param int $score -- คะแนน 1-5 --}}
{{-- @param string $color -- สี gradient: pink, cyan, amber, emerald, purple --}}

@php
    $gradients = [
        'purple' => 'from-purple-600/20 to-purple-900/20 border-purple-500/20',
        'pink' => 'from-pink-600/20 to-pink-900/20 border-pink-500/20',
        'cyan' => 'from-cyan-600/20 to-cyan-900/20 border-cyan-500/20',
        'amber' => 'from-amber-600/20 to-amber-900/20 border-amber-500/20',
        'emerald' => 'from-emerald-600/20 to-emerald-900/20 border-emerald-500/20',
    ];
    $g = $gradients[$color] ?? $gradients['purple'];
@endphp

<div class="bg-gradient-to-br {{ $g }} backdrop-blur-lg rounded-2xl p-5 border transition-all duration-300 hover:scale-[1.02]">
    {{-- Header --}}
    <div class="flex items-center justify-between mb-3">
        <div class="flex items-center gap-2">
            <span class="text-2xl">{{ $icon }}</span>
            <h4 class="text-white font-bold">{{ $title }}</h4>
        </div>

        {{-- คะแนนดาว --}}
        <div class="flex items-center gap-0.5">
            @for($i = 1; $i <= 5; $i++)
                <svg class="w-4 h-4 {{ $i <= $score ? 'text-amber-400' : 'text-white/10' }}" fill="currentColor" viewBox="0 0 20 20">
                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                </svg>
            @endfor
        </div>
    </div>

    {{-- คำทำนาย --}}
    <p class="text-purple-200/80 text-sm leading-relaxed">
        {{ $text }}
    </p>
</div>
