@extends('layouts.seller-v4')

@section('title', 'Trophy ของร้าน')

@php
    $achievedByTrophy = $achievements->keyBy('trophy_id');
    $tierOrder = array_keys(\App\Models\StoreTrophy::TIERS);
    $groups = collect($allTrophies)->sortBy(fn ($list, $tier) => array_search($tier, $tierOrder, true) === false ? 99 : array_search($tier, $tierOrder, true));
    $newly = collect($newlyAwarded ?? []);
@endphp

@section('content')
<div style="display:flex; flex-direction:column; gap:18px;">

    <x-seller-kit.header title="Trophy ของร้าน" icon="🏅" crumb="ร้านค้า · การตลาด · รางวัลร้าน"
                         :subtitle="'ได้รับแล้ว '.number_format($achievements->count()).' จาก '.number_format(collect($allTrophies)->flatten(1)->count()).' รายการ · แต้มสะสม '.number_format((int) ($store->trophy_points ?? 0))">
        <a href="{{ route('seller.achievements.index') }}" class="tp-btn tp-btn-sm">← รางวัลและสถานะ</a>
    </x-seller-kit.header>

    @include('seller.marketing.partials.nav')

    @if($newly->isNotEmpty())
        <div class="tp-card" style="border-left:4px solid var(--tp-ok, #5aa07e); background:linear-gradient(120deg, color-mix(in srgb, var(--tp-ok, #5aa07e) 14%, var(--card-bg)), var(--card-bg) 70%);">
            <div class="tp-section-h">🎉 ยินดีด้วย! ร้านได้รับ Trophy ใหม่ {{ $newly->count() }} รายการ</div>
            <div style="display:flex; flex-wrap:wrap; gap:6px; margin-top:8px;">
                @foreach($newly as $t)
                    <span class="tp-pill tp-pill-gold">{{ $t->name_th ?: $t->name }}</span>
                @endforeach
            </div>
        </div>
    @endif

    @forelse($groups as $tier => $trophies)
        <div>
            <div class="tp-section-h" style="margin-bottom:10px;">{{ \App\Models\StoreTrophy::TIERS[$tier]['name_th'] ?? $tier }} ({{ $trophies->count() }})</div>
            <div style="display:grid; grid-template-columns:repeat(auto-fill,minmax(190px,1fr)); gap:14px;">
                @foreach($trophies as $trophy)
                    @include('seller.achievements.partials.trophy-badge', [
                        'trophy' => $trophy,
                        'achieved' => $achievedByTrophy->has($trophy->id),
                        'achievement' => $achievedByTrophy->get($trophy->id),
                    ])
                @endforeach
            </div>
        </div>
    @empty
        <div class="tp-card">
            <x-seller-kit.empty icon="🏅" title="ยังไม่มี Trophy ในระบบ" text="ผู้ดูแลระบบยังไม่ได้เปิดใช้งาน Trophy" />
        </div>
    @endforelse
</div>
@endsection
