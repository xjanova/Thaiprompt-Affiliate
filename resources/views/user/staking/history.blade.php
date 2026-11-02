{{--
    ประวัติการ Stake (ธีมโนวาเต็ม)
    - $stakes = Stake ทั้งหมดของสมาชิกเอง (แบ่งหน้า 20) · $filters = ตัวกรองจาก query string · $summary = ยอดรวม
    - เดิมโหลดผ่าน jQuery จาก /api/v1/tpix/staking/history (ไม่มี session → 401) → ตอนนี้เรนเดอร์จากเซิร์ฟเวอร์
--}}
@extends('layouts.user-nova')

@section('title', 'ประวัติการ Stake - TPIX Staking')

@push('styles')<link rel="stylesheet" href="{{ asset('theme-nova/member/dex-staking.css') }}?v={{ filemtime(public_path('theme-nova/member/dex-staking.css')) }}">@endpush

@php
    $num = function ($n, int $d = 4) {
        $s = number_format((float) $n, $d, '.', ',');

        return str_contains($s, '.') ? rtrim(rtrim($s, '0'), '.') : $s;
    };
    $thDate = fn ($d) => $d ? $d->copy()->locale('th')->translatedFormat('j M Y · H:i') : '-';
    $stakeChip = ['active' => ['ok', 'กำลัง Stake'], 'unstaked' => ['', 'ถอนแล้ว'], 'slashed' => ['bad', 'ถูกตัด']];
    $poolLabel = fn ($stake) => $stake->pool ? ($stake->pool->name ?: ($stake->pool->token->symbol ?? '-')) : 'พูลที่ถูกลบ';
    $symbolOf = fn ($stake) => $stake->pool?->token?->symbol ?? '';

    $csvRows = $stakes->getCollection()->map(fn ($s) => [
        $s->created_at?->format('Y-m-d H:i:s'),
        $poolLabel($s),
        (string) $s->amount,
        $symbolOf($s),
        (string) $s->rewards_earned,
        $s->unlock_at?->format('Y-m-d H:i:s'),
        $stakeChip[$s->status][1] ?? $s->status,
    ])->values();
    $hasFilter = $filters['status'] !== '' || $filters['period'] !== '' || $filters['sort'] !== 'date_desc';
@endphp

@section('content')
<div class="nvm-stack">

<section class="nvm-ph" aria-labelledby="nvm-dx-shs-h">
    <div class="nvm-ph__in">
        <div class="nvm-ph__txt">
            <a class="nvm-ph__back" href="{{ route('user.staking.index') }}"><svg class="nvm-ico"><use href="#nvm-i-arrow"/></svg>TPIX Staking</a>
            <div class="nvm-eyebrow nvm-latin">Staking history</div>
            <h1 id="nvm-dx-shs-h">ประวัติการ Stake</h1>
            <p class="nvm-ph__lead">ทุกรายการ Stake ของคุณ ทั้งที่ยังเปิดอยู่และที่ถอนแล้ว</p>
        </div>
        <img class="nvm-ph__art" src="{{ asset('images/nova/deco/scroll.webp') }}" alt="" width="200" height="200">
    </div>
</section>

<section class="nvm-kpis nvm-kpis--3" aria-label="สรุปการ Stake">
    <div class="nvm-card nvm-kpi">
        <div class="nvm-kpi__lbl">รายการทั้งหมด</div>
        <div class="nvm-kpi__en nvm-latin">Total stakes</div>
        <div class="nvm-kpi__val nvm-num">{{ number_format((int) ($summary->total ?? 0)) }}</div>
    </div>
    <div class="nvm-card nvm-kpi">
        <div class="nvm-kpi__lbl">กำลัง Stake</div>
        <div class="nvm-kpi__en nvm-latin">Active</div>
        <div class="nvm-kpi__val nvm-num">{{ number_format((int) ($summary->active ?? 0)) }}</div>
    </div>
    <div class="nvm-card nvm-kpi">
        <div class="nvm-kpi__lbl">รางวัลสะสม (รวมทุกเหรียญ)</div>
        <div class="nvm-kpi__en nvm-latin">Rewards earned</div>
        <div class="nvm-kpi__val nvm-num">{{ $num($summary->rewards ?? 0) }}</div>
    </div>
</section>

<section class="nvm-card nvm-box" aria-labelledby="nvm-dx-shsl-h" x-data="nvmDxCsv(@js($csvRows), 'tpix-staking-history.csv', ['วันที่', 'พูล', 'จำนวน', 'เหรียญ', 'รางวัล', 'ปลดล็อก', 'สถานะ'])">
    <div class="nvm-sec-h">
        <div><small class="nvm-latin">History</small><h2 id="nvm-dx-shsl-h">รายการทั้งหมด</h2></div>
        @if($stakes->isNotEmpty())
            <button type="button" class="nvm-btn nvm-btn--line nvm-btn--sm" @click="download()"><svg class="nvm-ico"><use href="#nvm-i-down"/></svg>ส่งออก CSV</button>
        @endif
    </div>

    <form method="GET" action="{{ route('user.staking.history') }}" class="nvm-filter">
        <div class="nvm-field">
            <label class="nvm-label" for="nvm-dx-hs-st">สถานะ</label>
            <select id="nvm-dx-hs-st" name="status" class="nvm-input">
                <option value="">ทุกสถานะ</option>
                @foreach(['active' => 'กำลัง Stake', 'unstaked' => 'ถอนแล้ว', 'slashed' => 'ถูกตัด'] as $value => $label)
                    <option value="{{ $value }}" @selected($filters['status'] === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div class="nvm-field">
            <label class="nvm-label" for="nvm-dx-hs-pd">ช่วงเวลา</label>
            <select id="nvm-dx-hs-pd" name="period" class="nvm-input">
                <option value="">ทุกช่วงเวลา</option>
                @foreach(['today' => 'วันนี้', 'week' => '7 วันที่แล้ว', 'month' => '30 วันที่แล้ว', 'year' => 'ปีนี้'] as $value => $label)
                    <option value="{{ $value }}" @selected($filters['period'] === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div class="nvm-field">
            <label class="nvm-label" for="nvm-dx-hs-so">เรียงตาม</label>
            <select id="nvm-dx-hs-so" name="sort" class="nvm-input">
                @foreach(['date_desc' => 'วันที่: ใหม่-เก่า', 'date_asc' => 'วันที่: เก่า-ใหม่', 'amount_desc' => 'จำนวน: มาก-น้อย', 'amount_asc' => 'จำนวน: น้อย-มาก'] as $value => $label)
                    <option value="{{ $value }}" @selected($filters['sort'] === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div class="nvm-filter__btns">
            <button type="submit" class="nvm-btn nvm-btn--gold"><svg class="nvm-ico"><use href="#nvm-i-search"/></svg>กรอง</button>
            @if($hasFilter)<a class="nvm-btn nvm-btn--line" href="{{ route('user.staking.history') }}">ล้าง</a>@endif
        </div>
    </form>

    @if($stakes->isEmpty())
        <div class="nvm-empty">
            <img src="{{ asset('images/nova/wallet/empty.webp') }}" alt="" width="86" height="86" loading="lazy">
            @if($hasFilter)
                <b>ไม่พบรายการตามตัวกรอง</b>
                ลองเปลี่ยนเงื่อนไขการค้นหา
            @else
                <b>ยังไม่มีประวัติการ Stake</b>
                เริ่ม Stake ครั้งแรกเพื่อรับรางวัล
                <div><a class="nvm-btn nvm-btn--gold nvm-btn--sm" href="{{ route('user.staking.index') }}"><svg class="nvm-ico"><use href="#nvm-i-plus"/></svg>ดูพูล Stake</a></div>
            @endif
        </div>
    @else
        <div class="nvm-table-wrap nvm-gap-t">
            <table class="nvm-table">
                <thead>
                    <tr>
                        <th>วันที่</th>
                        <th>พูล</th>
                        <th class="r">จำนวน</th>
                        <th class="r">รางวัล</th>
                        <th>ปลดล็อก</th>
                        <th class="c">สถานะ</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($stakes as $stake)
                        @php [$tone, $label] = $stakeChip[$stake->status] ?? ['', $stake->status]; @endphp
                        <tr>
                            <td class="nvm-num">{{ $thDate($stake->created_at) }}</td>
                            <td>
                                @if($stake->pool)
                                    <a class="nvm-link" href="{{ route('user.staking.show', $stake->pool_id) }}">{{ $poolLabel($stake) }}</a>
                                @else
                                    {{ $poolLabel($stake) }}
                                @endif
                            </td>
                            <td class="r nvm-num">{{ $num($stake->amount) }} {{ $symbolOf($stake) }}</td>
                            <td class="r nvm-num">{{ $num($stake->rewards_earned) }}</td>
                            <td class="nvm-num">{{ $stake->unlock_at ? $thDate($stake->unlock_at) : 'ไม่ล็อก' }}</td>
                            <td class="c"><span class="nvm-chip {{ $tone ? 'nvm-chip--'.$tone : '' }}">{{ $label }}</span></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @if($stakes->hasPages())
            <div class="nvm-pager nvm-gap-t">{{ $stakes->links() }}</div>
        @endif
    @endif
</section>

</div>
@endsection

@push('scripts')
@include('user.dex.partials.nova-csv')
@endpush
