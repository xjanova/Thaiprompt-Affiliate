{{--
    พูลสภาพคล่อง — TPIX DEX (ธีมโนวาเต็ม)
    - $pools = พูลที่เปิดใช้งาน (เรียงตาม Volume 24h) · $myPoolIds = พูลที่สมาชิกมีตำแหน่งอยู่ · $stats = ยอดรวม
    - ค้นหา/เรียง/เฉพาะพูลของฉัน ทำฝั่งเบราว์เซอร์ด้วย Alpine (การ์ดเรนเดอร์จากเซิร์ฟเวอร์ ไม่มี JS ก็เห็นครบ)
--}}
@extends('layouts.user-nova')

@section('title', 'พูลสภาพคล่อง - TPIX DEX')

@push('styles')<link rel="stylesheet" href="{{ asset('theme-nova/member/dex-staking.css') }}?v={{ filemtime(public_path('theme-nova/member/dex-staking.css')) }}">@endpush

@php
    $num = function ($n, int $d = 4) {
        $s = number_format((float) $n, $d, '.', ',');

        return str_contains($s, '.') ? rtrim(rtrim($s, '0'), '.') : $s;
    };
    $abbr = fn (?string $symbol) => mb_strtoupper(mb_substr((string) $symbol, 0, 3));

    $rows = $pools->map(fn ($pool) => [
        'id' => $pool->id,
        'q' => mb_strtolower($pool->tokenA->symbol.' '.$pool->tokenB->symbol.' '.$pool->tokenA->name.' '.$pool->tokenB->name),
        'tvl' => (float) $pool->getTVL(),
        'volume' => (float) $pool->volume_24h,
        'apy' => (float) $pool->getAPY(),
        'created' => $pool->created_at?->timestamp ?? 0,
        'mine' => in_array($pool->id, $myPoolIds, true),
    ])->values();
@endphp

@section('content')
<div class="nvm-stack" x-data="nvmDxPools(@js($rows))">

<section class="nvm-ph" aria-labelledby="nvm-dx-pl-h">
    <div class="nvm-ph__in">
        <div class="nvm-ph__txt">
            <div class="nvm-eyebrow nvm-latin">TPIX DEX · Liquidity</div>
            <h1 id="nvm-dx-pl-h">พูลสภาพคล่อง</h1>
            <p class="nvm-ph__lead">ฝากเหรียญเป็นคู่เข้าพูล แล้วรับส่วนแบ่งค่าธรรมเนียมจากทุกการแลกเปลี่ยนในพูลนั้น</p>
            <div class="nvm-hero__actions">
                <a class="nvm-btn nvm-btn--gold" href="{{ route('user.dex.add-liquidity') }}"><svg class="nvm-ico"><use href="#nvm-i-plus"/></svg>เพิ่มสภาพคล่อง</a>
                <a class="nvm-btn nvm-btn--ghost" href="{{ route('user.dex.my-positions') }}"><svg class="nvm-ico"><use href="#nvm-i-wallet"/></svg>ตำแหน่งของฉัน</a>
            </div>
        </div>
        <img class="nvm-ph__art" src="{{ asset('images/nova/deco/coins.webp') }}" alt="" width="200" height="200">
    </div>
</section>

<section class="nvm-kpis" aria-label="สรุปพูลทั้งหมด">
    @foreach([
        ['มูลค่าในพูลรวม', 'Total value locked', $num($stats['tvl'], 2).' TPIX'],
        ['ปริมาณแลก 24 ชม.', 'Volume 24h', $num($stats['volume_24h'], 2)],
        ['จำนวนพูล', 'Active pools', number_format($stats['pools'])],
        ['ค่าธรรมเนียมสะสม', 'Fees collected', $num($stats['fees'], 2)],
    ] as [$label, $en, $value])
        <div class="nvm-card nvm-kpi">
            <div class="nvm-kpi__lbl">{{ $label }}</div>
            <div class="nvm-kpi__en nvm-latin">{{ $en }}</div>
            <div class="nvm-kpi__val nvm-num">{{ $value }}</div>
        </div>
    @endforeach
</section>

<section class="nvm-card nvm-box" aria-labelledby="nvm-dx-list-h">
    <div class="nvm-sec-h"><div><small class="nvm-latin">Pools</small><h2 id="nvm-dx-list-h">พูลที่เปิดอยู่</h2></div></div>

    @if($pools->isEmpty())
        <div class="nvm-empty">
            <img src="{{ asset('images/nova/deco/coins.webp') }}" alt="" width="86" height="86" loading="lazy">
            <b>ยังไม่มีพูลสภาพคล่อง</b>
            เริ่มเป็นผู้ให้สภาพคล่องรายแรก แล้วรับส่วนแบ่งค่าธรรมเนียม
            <div><a class="nvm-btn nvm-btn--gold nvm-btn--sm" href="{{ route('user.dex.add-liquidity') }}"><svg class="nvm-ico"><use href="#nvm-i-plus"/></svg>เพิ่มสภาพคล่อง</a></div>
        </div>
    @else
        <div class="nvm-filter">
            <div class="nvm-field">
                <label class="nvm-label" for="nvm-dx-q">ค้นหาพูล</label>
                <input id="nvm-dx-q" type="search" class="nvm-input" placeholder="เช่น TPIX" x-model.debounce.200ms="q">
            </div>
            <div class="nvm-field">
                <label class="nvm-label" for="nvm-dx-sort">เรียงตาม</label>
                <select id="nvm-dx-sort" class="nvm-input" x-model="sort">
                    <option value="volume">ปริมาณแลก 24 ชม. สูงสุด</option>
                    <option value="tvl">มูลค่าในพูลสูงสุด</option>
                    <option value="apy">APY สูงสุด</option>
                    <option value="newest">พูลใหม่ล่าสุด</option>
                </select>
            </div>
            <label class="nvm-check"><input type="checkbox" x-model="mineOnly">เฉพาะพูลที่ฉันมีส่วน</label>
        </div>

        <div class="nvm-dx-pools nvm-gap-t">
            @foreach($pools as $pool)
                @php $isMine = in_array($pool->id, $myPoolIds, true); @endphp
                <article class="nvm-card nvm-dx-pool" x-show="visible({{ $pool->id }})" :style="'--o:' + rank({{ $pool->id }})" x-data="{ open: false }">
                    <div class="nvm-dx-pool__head">
                        <span class="nvm-dx-pair" aria-hidden="true">
                            <span class="nvm-dx-coin">{{ $abbr($pool->tokenA->symbol) }}</span>
                            <span class="nvm-dx-coin nvm-dx-coin--b">{{ $abbr($pool->tokenB->symbol) }}</span>
                        </span>
                        <div>
                            <b>{{ $pool->tokenA->symbol }} / {{ $pool->tokenB->symbol }}</b>
                            <small>ค่าธรรมเนียม <span class="nvm-num">{{ $num($pool->fee_percentage, 3) }}%</span></small>
                        </div>
                    </div>
                    <div class="nvm-pills">
                        @if($isMine)<span class="nvm-chip nvm-chip--gold"><svg class="nvm-ico"><use href="#nvm-i-check"/></svg>คุณมีส่วนในพูลนี้</span>@endif
                        <span class="nvm-chip nvm-chip--ok">APY <span class="nvm-num">{{ $num($pool->getAPY(), 2) }}%</span></span>
                    </div>
                    <div class="nvm-minis">
                        <div class="nvm-mini"><small>มูลค่าในพูล</small><b class="nvm-num">{{ $num($pool->getTVL(), 2) }}</b></div>
                        <div class="nvm-mini"><small>ปริมาณ 24 ชม.</small><b class="nvm-num">{{ $num($pool->volume_24h, 2) }}</b></div>
                    </div>
                    <div x-show="open" x-cloak>
                        <dl class="nvm-kv">
                            <dt>สำรอง {{ $pool->tokenA->symbol }}</dt><dd class="nvm-num">{{ $num($pool->reserve_a) }}</dd>
                            <dt>สำรอง {{ $pool->tokenB->symbol }}</dt><dd class="nvm-num">{{ $num($pool->reserve_b) }}</dd>
                            <dt>ราคา</dt><dd class="nvm-num">1 {{ $pool->tokenA->symbol }} = {{ $num($pool->getCurrentPrice()) }} {{ $pool->tokenB->symbol }}</dd>
                            <dt>ปริมาณ 7 วัน</dt><dd class="nvm-num">{{ $num($pool->volume_7d, 2) }}</dd>
                            <dt>ค่าธรรมเนียมสะสม</dt><dd class="nvm-num">{{ $num($pool->fees_collected) }}</dd>
                            <dt>จำนวนรายการ</dt><dd class="nvm-num">{{ number_format((int) $pool->tx_count) }}</dd>
                        </dl>
                    </div>
                    <div class="nvm-dx-pool__acts">
                        <button type="button" class="nvm-btn nvm-btn--line nvm-btn--sm" @click="open = ! open" :aria-expanded="open.toString()"><svg class="nvm-ico"><use href="#nvm-i-info"/></svg><span x-text="open ? 'ซ่อนรายละเอียด' : 'รายละเอียด'">รายละเอียด</span></button>
                        <a class="nvm-btn nvm-btn--gold nvm-btn--sm" href="{{ route('user.dex.add-liquidity', ['pool' => $pool->id]) }}"><svg class="nvm-ico"><use href="#nvm-i-plus"/></svg>เพิ่ม</a>
                    </div>
                </article>
            @endforeach
        </div>
        <div class="nvm-empty" x-show="shown() === 0" x-cloak>
            <b>ไม่พบพูลที่ตรงกับเงื่อนไข</b>
            ลองเปลี่ยนคำค้นหาหรือปิดตัวกรอง "เฉพาะพูลที่ฉันมีส่วน"
        </div>
    @endif
</section>

<section class="nvm-card nvm-alert nvm-alert--info" role="note">
    <span class="nvm-alert__ic"><svg class="nvm-ico"><use href="#nvm-i-info"/></svg></span>
    <div class="nvm-alert__txt">
        <b>ความเสี่ยงของการให้สภาพคล่อง</b>
        <p>เมื่อราคาเหรียญในคู่เปลี่ยน มูลค่าที่ถอนได้อาจน้อยกว่าการถือเหรียญไว้เฉยๆ (Impermanent Loss) ศึกษาก่อนฝากทุกครั้ง</p>
    </div>
</section>

</div>
@endsection

@push('scripts')
<script>
// ตัวกรอง/เรียงพูลฝั่งเบราว์เซอร์ — ใช้ CSS order จึงไม่ต้องสร้างการ์ดใหม่
function nvmDxPools(rows) {
    return {
        rows: rows,
        q: '',
        sort: 'volume',
        mineOnly: false,
        row(id) { return this.rows.find(r => r.id === id) || {}; },
        visible(id) {
            const r = this.row(id);
            if (this.mineOnly && ! r.mine) return false;
            const q = this.q.trim().toLowerCase();
            return ! q || (r.q || '').includes(q);
        },
        sorted() {
            const key = { volume: 'volume', tvl: 'tvl', apy: 'apy', newest: 'created' }[this.sort] || 'volume';
            return [...this.rows].sort((a, b) => b[key] - a[key]);
        },
        rank(id) { return this.sorted().findIndex(r => r.id === id); },
        shown() { return this.rows.filter(r => this.visible(r.id)).length; }
    };
}
</script>
@endpush
