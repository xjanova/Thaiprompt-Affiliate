{{--
    แถบขั้นตอนแนวนอน (ความคืบหน้างาน / ขั้นตอนสมัคร) — ธีมโนวา
    ตัวแปร: $steps = [['label' => ..., 'state' => done|current|stopped|todo, 'sub' => ?string], ...], $stepsLabel (aria-label)
--}}
@php
    $stepClass = ['done' => 'is-done', 'current' => 'is-now', 'stopped' => 'is-stop'];
@endphp
<ol class="nvm-rd-steps" aria-label="{{ $stepsLabel ?? 'ขั้นตอน' }}">
    @foreach($steps as $step)
        <li class="{{ $stepClass[$step['state'] ?? ''] ?? '' }}" @if(($step['state'] ?? '') === 'current') aria-current="step" @endif>
            {{ $step['label'] }}
            @if(!empty($step['sub']))<small class="nvm-num">{{ $step['sub'] }}</small>@endif
        </li>
    @endforeach
</ol>
