@extends('layouts.seller-v4')

@section('title', 'รางวัลและสถานะร้าน')

@php
    $displayState = $achievements->mapWithKeys(fn ($a) => [$a->id => (bool) $a->is_displayed])->all();
@endphp

@section('content')
<div x-data="trophyDisplay(@js($displayState))" style="display:flex; flex-direction:column; gap:18px;">

    <x-seller-kit.header title="รางวัลและสถานะร้าน" icon="🏆" crumb="ร้านค้า · การตลาด"
                         subtitle="สะสม Trophy จากยอดขายและรีวิว เพื่อโชว์บนหน้าร้านและปลดล็อกสถานะร้าน Premium">
        <a href="{{ route('seller.achievements.trophies') }}" class="tp-btn tp-btn-sm">🏅 Trophy ทั้งหมด</a>
        <a href="{{ route('seller.achievements.premium-status') }}" class="tp-btn tp-btn-sm">💎 สถานะ Premium</a>
    </x-seller-kit.header>

    @include('seller.marketing.partials.nav')

    <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(180px,1fr)); gap:14px;">
        <x-seller-kit.stat label="Trophy ที่ได้รับ" :value="number_format((int) $stats['total_trophies'])" icon="🏆" tone="gold" />
        <x-seller-kit.stat label="แต้ม Trophy" :value="number_format((int) $stats['trophy_points'])" icon="✨" tone="violet" />
        <x-seller-kit.stat label="สถานะร้าน" :value="$stats['is_premium'] ? 'Premium 💎' : 'ร้านทั่วไป'" icon="🏪" :tone="$stats['is_premium'] ? 'ok' : 'muted'" />
        <x-seller-kit.stat label="คะแนน AI ของร้าน" :value="number_format((float) $stats['ai_score'], 1)" icon="🤖" tone="info" />
    </div>

    @include('seller.achievements.partials.premium-progress', ['premiumReport' => $premiumReport, 'showCriteria' => false])

    <div>
        <div class="tp-section-h" style="margin-bottom:10px;">🏅 Trophy ที่ได้รับ ({{ number_format($achievements->count()) }})</div>
        @if($achievements->count() > 0)
            <div style="display:grid; grid-template-columns:repeat(auto-fill,minmax(190px,1fr)); gap:14px;">
                @foreach($achievements as $achievement)
                    @if($achievement->trophy)
                        <div style="display:flex; flex-direction:column; gap:8px;">
                            @include('seller.achievements.partials.trophy-badge', ['trophy' => $achievement->trophy, 'achieved' => true, 'achievement' => $achievement])
                            <label style="display:flex; align-items:center; justify-content:center; gap:8px; font-size:12px; font-weight:700; cursor:pointer;">
                                <input type="checkbox" :checked="shown[{{ (int) $achievement->id }}]"
                                       @change="toggle({{ (int) $achievement->id }}, @js(route('seller.achievements.trophies.toggle-display', $achievement)), $event)"
                                       style="width:16px; height:16px; accent-color:var(--accent1);">
                                โชว์บนหน้าร้าน
                            </label>
                        </div>
                    @endif
                @endforeach
            </div>
        @else
            <div class="tp-card">
                <x-seller-kit.empty icon="🏅" title="ยังไม่มี Trophy" text="ขายให้ได้ รีวิวดี ๆ และเพิ่มสินค้าครบ ระบบจะมอบ Trophy ให้อัตโนมัติ — กด “Trophy ทั้งหมด” เพื่อตรวจสิทธิ์ล่าสุด" />
            </div>
        @endif
    </div>

    @if($availableTrophies->count() > 0)
        <div>
            <div class="tp-section-h" style="margin-bottom:10px;">🎯 Trophy ที่ยังรอปลดล็อก</div>
            <div style="display:grid; grid-template-columns:repeat(auto-fill,minmax(190px,1fr)); gap:14px;">
                @foreach($availableTrophies->take(8) as $trophy)
                    @include('seller.achievements.partials.trophy-badge', ['trophy' => $trophy, 'achieved' => false, 'achievement' => null])
                @endforeach
            </div>
        </div>
    @endif
</div>
@endsection

@push('scripts')
<script>
    // สลับการโชว์ Trophy บนหน้าร้าน (JSON seller.achievements.trophies.toggle-display)
    function trophyDisplay(initial) {
        return {
            shown: initial,
            busy: {},
            async toggle(id, url, ev) {
                if (this.busy[id]) { ev.target.checked = !!this.shown[id]; return; }
                this.busy[id] = true;
                try {
                    const res = await fetch(url, { method: 'POST', headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content } });
                    const data = await res.json().catch(() => ({}));
                    if (res.ok && data.success) {
                        this.shown[id] = !!data.is_displayed;
                        window.showNotification(data.message || 'บันทึกแล้ว', 'success');
                    } else {
                        window.showNotification(data.message || 'บันทึกไม่สำเร็จ', 'error');
                    }
                } catch (e) {
                    window.showNotification('เชื่อมต่อเซิร์ฟเวอร์ไม่ได้ กรุณาลองใหม่', 'error');
                } finally {
                    ev.target.checked = !!this.shown[id];
                    this.busy[id] = false;
                }
            },
        };
    }
</script>
@endpush
