@extends('layouts.seller-v4')

@section('title', 'สถิติคะแนนร้าน')

@php
    $months = collect($monthlyStats ?? []);
    $countMax = max(1, (int) $months->max('count'));
    $thMonths = ['Jan' => 'ม.ค.', 'Feb' => 'ก.พ.', 'Mar' => 'มี.ค.', 'Apr' => 'เม.ย.', 'May' => 'พ.ค.', 'Jun' => 'มิ.ย.', 'Jul' => 'ก.ค.', 'Aug' => 'ส.ค.', 'Sep' => 'ก.ย.', 'Oct' => 'ต.ค.', 'Nov' => 'พ.ย.', 'Dec' => 'ธ.ค.'];
    $thLabel = function ($label) use ($thMonths) {
        [$m, $y] = array_pad(explode(' ', (string) $label), 2, '');

        return ($thMonths[$m] ?? $m).' '.$y;
    };
@endphp

@section('content')
<div style="display:flex; flex-direction:column; gap:18px;">

    <x-seller-kit.header title="สถิติคะแนนร้าน" icon="📈" crumb="ร้านค้า · การตลาด · คะแนนร้าน"
                         subtitle="แนวโน้มจำนวนรีวิวและคะแนนเฉลี่ย 6 เดือนล่าสุด">
        <a href="{{ route('seller.store-rating.index') }}" class="tp-btn tp-btn-sm">← สรุปคะแนน</a>
    </x-seller-kit.header>

    @include('seller.marketing.partials.nav')

    @include('seller.store-rating.partials.summary', ['stats' => $stats])

    <div class="tp-card">
        <div class="tp-section-h">📊 รีวิวรายเดือน</div>
        <div class="tp-bars" style="height:200px; margin-top:16px;">
            @foreach($months as $m)
                <div class="col" title="{{ $thLabel($m['month']) }} · {{ $m['count'] }} รีวิว · เฉลี่ย {{ $m['average'] }}">
                    <div class="tp-num" style="font-size:11px; font-weight:800; color:var(--deep1);">{{ $m['count'] > 0 ? number_format((float) $m['average'], 1).'★' : '' }}</div>
                    <div class="stack"><div class="bar a" style="height:{{ max(3, (int) $m['count'] / $countMax * 100) }}%; {{ (int) $m['count'] > 0 ? '' : 'opacity:.25;' }}"></div></div>
                    <div class="lbl">{{ $thLabel($m['month']) }}</div>
                </div>
            @endforeach
        </div>
        <div style="font-size:11.5px; color:var(--ink2); margin-top:8px;">ความสูงแท่ง = จำนวนรีวิว · ตัวเลขด้านบน = คะแนนเฉลี่ยของเดือน</div>
    </div>
</div>
@endsection
