{{-- แถบคะแนน 1-5 ดาว --}}
{{-- @param string $label -- ชื่อด้าน เช่น 'ภาพรวม' --}}
{{-- @param int $score -- คะแนน 1-5 --}}
{{-- @param string $color -- สี: purple, pink, cyan, amber, emerald --}}
@php
    $colorMap = [
        'purple' => ['bg' => 'bg-purple-500', 'glow' => 'shadow-purple-500/30'],
        'pink' => ['bg' => 'bg-pink-500', 'glow' => 'shadow-pink-500/30'],
        'cyan' => ['bg' => 'bg-cyan-500', 'glow' => 'shadow-cyan-500/30'],
        'amber' => ['bg' => 'bg-amber-500', 'glow' => 'shadow-amber-500/30'],
        'emerald' => ['bg' => 'bg-emerald-500', 'glow' => 'shadow-emerald-500/30'],
    ];
    $c = $colorMap[$color] ?? $colorMap['purple'];
    $widthPercent = ($score / 5) * 100;
@endphp

<div class="flex items-center gap-2 text-xs">
    <span class="text-purple-300/60 w-12 text-right shrink-0">{{ $label }}</span>
    <div class="flex-1 h-1.5 bg-white/5 rounded-full overflow-hidden">
        <div class="h-full rounded-full score-bar {{ $c['bg'] }} {{ $c['glow'] }}"
             style="width: {{ $widthPercent }}%; box-shadow: 0 0 8px currentColor;"
             x-data
             x-init="$el.style.width = '0%'; setTimeout(() => $el.style.width = '{{ $widthPercent }}%', 300)">
        </div>
    </div>
    <span class="text-white/80 font-bold w-4 text-center">{{ $score }}</span>
</div>
