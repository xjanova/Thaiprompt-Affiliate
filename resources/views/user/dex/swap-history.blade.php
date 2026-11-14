{{--
    ประวัติการแลกเปลี่ยน — TPIX DEX (ธีมโนวาเต็ม)
    - $swaps = ประวัติของสมาชิกเอง (แบ่งหน้า 20) · $filters = ตัวกรองจาก query string · $summary = ยอดรวมทั้งหมด
    - ตัวกรองเป็นฟอร์ม GET ธรรมดา · ปุ่มส่งออก CSV ทำจากแถวในหน้านี้ (ฝั่งเบราว์เซอร์) — ป้ายปุ่มบอกชัดว่าส่งออกเฉพาะหน้านี้
--}}
@extends('layouts.user-nova')

@section('title', 'ประวัติการแลกเปลี่ยน - TPIX DEX')

@push('styles')<link rel="stylesheet" href="{{ asset('theme-nova/member/dex-staking.css') }}?v={{ filemtime(public_path('theme-nova/member/dex-staking.css')) }}">@endpush

@php
    $num = function ($n, int $d = 6) {
        $s = number_format((float) $n, $d, '.', ',');

        return str_contains($s, '.') ? rtrim(rtrim($s, '0'), '.') : $s;
    };
    $thDate = fn ($d) => $d ? $d->copy()->locale('th')->translatedFormat('j M Y · H:i') : '-';
    $swapChip = ['completed' => ['ok', 'สำเร็จ'], 'pending' => ['warn', 'รอดำเนินการ'], 'failed' => ['bad', 'ไม่สำเร็จ']];

    $csvRows = $swaps->getCollection()->map(fn ($s) => [
        $s->created_at?->format('Y-m-d H:i:s'),
        $s->tokenIn->symbol ?? '',
        (string) $s->amount_in,
        $s->tokenOut->symbol ?? '',
        (string) $s->amount_out,
        (string) $s->fee,
        $swapChip[$s->status][1] ?? $s->status,
        $s->tx_hash,
    ])->values();
    $hasFilter = $filters['q'] !== '' || $filters['status'] !== '' || $filters['period'] !== '' || $filters['sort'] !== 'date_desc';
@endphp

@section('content')
<div class="nvm-stack">

<section class="nvm-ph" aria-labelledby="nvm-dx-sh-h">
    <div class="nvm-ph__in">
        <div class="nvm-ph__txt">
            <a class="nvm-ph__back" href="{{ route('user.dex.swap') }}"><svg class="nvm-ico"><use href="#nvm-i-arrow"/></svg>แลกเปลี่ยน Token</a>
            <div class="nvm-eyebrow nvm-latin">Swap history</div>
            <h1 id="nvm-dx-sh-h">ประวัติการแลกเปลี่ยน</h1>
            <p class="nvm-ph__lead">ทุกรายการแลกเหรียญของคุณบน TPIX DEX พร้อมเลขธุรกรรม</p>
        </div>
        <img class="nvm-ph__art" src="{{ asset('images/nova/deco/scroll.webp') }}" alt="" width="200" height="200">
    </div>
</section>

<section class="nvm-kpis nvm-kpis--3" aria-label="สรุปการแลกเปลี่ยน">
    <div class="nvm-card nvm-kpi">
        <div class="nvm-kpi__lbl">จำนวนรายการ</div>
        <div class="nvm-kpi__en nvm-latin">Total swaps</div>
        <div class="nvm-kpi__val nvm-num">{{ number_format((int) ($summary->total ?? 0)) }}</div>
    </div>
    <div class="nvm-card nvm-kpi">
        <div class="nvm-kpi__lbl">เดือนนี้</div>
        <div class="nvm-kpi__en nvm-latin">This month</div>
        <div class="nvm-kpi__val nvm-num">{{ number_format((int) ($summary->this_month ?? 0)) }}</div>
    </div>
    <div class="nvm-card nvm-kpi">
        <div class="nvm-kpi__lbl">ค่าธรรมเนียมรวม</div>
        <div class="nvm-kpi__en nvm-latin">Fees paid</div>
        <div class="nvm-kpi__val nvm-num">{{ $num($summary->fees ?? 0) }}</div>
    </div>
</section>

<section class="nvm-card nvm-box" aria-labelledby="nvm-dx-shl-h" x-data="nvmDxCsv(@js($csvRows), 'tpix-swap-history.csv', ['วันที่', 'จ่าย', 'จำนวนจ่าย', 'รับ', 'จำนวนรับ', 'ค่าธรรมเนียม', 'สถานะ', 'เลขธุรกรรม'])">
    <div class="nvm-sec-h">
        <div><small class="nvm-latin">History</small><h2 id="nvm-dx-shl-h">รายการทั้งหมด</h2></div>
        @if($swaps->isNotEmpty())
            <button type="button" class="nvm-btn nvm-btn--line nvm-btn--sm" @click="download()"><svg class="nvm-ico"><use href="#nvm-i-down"/></svg>ส่งออกหน้านี้ (CSV {{ number_format(count($csvRows)) }} แถว)</button>
        @endif
    </div>

    <form method="GET" action="{{ route('user.dex.swap-history') }}" class="nvm-filter">
        <div class="nvm-field">
            <label class="nvm-label" for="nvm-dx-sh-q">ค้นหาเหรียญ</label>
            <input id="nvm-dx-sh-q" type="search" name="q" maxlength="50" class="nvm-input" placeholder="เช่น TPIX" value="{{ $filters['q'] }}">
        </div>
        <div class="nvm-field">
            <label class="nvm-label" for="nvm-dx-sh-st">สถานะ</label>
            <select id="nvm-dx-sh-st" name="status" class="nvm-input">
                <option value="">ทุกสถานะ</option>
                @foreach(['completed' => 'สำเร็จ', 'pending' => 'รอดำเนินการ', 'failed' => 'ไม่สำเร็จ'] as $value => $label)
                    <option value="{{ $value }}" @selected($filters['status'] === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div class="nvm-field">
            <label class="nvm-label" for="nvm-dx-sh-pd">ช่วงเวลา</label>
            <select id="nvm-dx-sh-pd" name="period" class="nvm-input">
                <option value="">ทุกช่วงเวลา</option>
                @foreach(['today' => 'วันนี้', 'week' => '7 วันที่แล้ว', 'month' => '30 วันที่แล้ว', 'year' => 'ปีนี้'] as $value => $label)
                    <option value="{{ $value }}" @selected($filters['period'] === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div class="nvm-field">
            <label class="nvm-label" for="nvm-dx-sh-so">เรียงตาม</label>
            <select id="nvm-dx-sh-so" name="sort" class="nvm-input">
                @foreach(['date_desc' => 'วันที่: ใหม่-เก่า', 'date_asc' => 'วันที่: เก่า-ใหม่', 'amount_desc' => 'จำนวนจ่าย: มาก-น้อย', 'amount_asc' => 'จำนวนจ่าย: น้อย-มาก'] as $value => $label)
                    <option value="{{ $value }}" @selected($filters['sort'] === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div class="nvm-filter__btns">
            <button type="submit" class="nvm-btn nvm-btn--gold"><svg class="nvm-ico"><use href="#nvm-i-search"/></svg>กรอง</button>
            @if($hasFilter)<a class="nvm-btn nvm-btn--line" href="{{ route('user.dex.swap-history') }}">ล้าง</a>@endif
        </div>
    </form>

    @if($swaps->isEmpty())
        <div class="nvm-empty">
            <img src="{{ asset('images/nova/wallet/empty.webp') }}" alt="" width="86" height="86" loading="lazy">
            @if($hasFilter)
                <b>ไม่พบรายการตามตัวกรอง</b>
                ลองเปลี่ยนเงื่อนไขการค้นหา
            @else
                <b>ยังไม่มีประวัติการแลกเปลี่ยน</b>
                เริ่มแลกเหรียญครั้งแรกได้เลย
                <div><a class="nvm-btn nvm-btn--gold nvm-btn--sm" href="{{ route('user.dex.swap') }}"><svg class="nvm-ico"><use href="#nvm-i-swap"/></svg>แลกเปลี่ยน Token</a></div>
            @endif
        </div>
    @else
        <div class="nvm-table-wrap nvm-gap-t">
            <table class="nvm-table">
                <thead>
                    <tr>
                        <th>วันที่ / เวลา</th>
                        <th>จ่าย</th>
                        <th>รับ</th>
                        <th class="r">ค่าธรรมเนียม</th>
                        <th class="c">สถานะ</th>
                        <th>เลขธุรกรรม</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($swaps as $swap)
                        @php [$tone, $label] = $swapChip[$swap->status] ?? ['', $swap->status]; @endphp
                        <tr>
                            <td class="nvm-num">{{ $thDate($swap->created_at) }}</td>
                            <td class="nvm-num">{{ $num($swap->amount_in) }} {{ $swap->tokenIn->symbol ?? '?' }}</td>
                            <td class="nvm-num">{{ $num($swap->amount_out) }} {{ $swap->tokenOut->symbol ?? '?' }}</td>
                            <td class="r nvm-num">{{ $num($swap->fee) }}</td>
                            <td class="c"><span class="nvm-chip {{ $tone ? 'nvm-chip--'.$tone : '' }}">{{ $label }}</span></td>
                            <td>
                                <span class="nvm-dx-hash">{{ \Illuminate\Support\Str::limit($swap->tx_hash, 14, '…') }}</span>
                                <button type="button" class="nvm-btn nvm-btn--line nvm-btn--sm" @click="nvmCopy(@js($swap->tx_hash), 'คัดลอกเลขธุรกรรมแล้ว')" aria-label="คัดลอกเลขธุรกรรม"><svg class="nvm-ico"><use href="#nvm-i-copy"/></svg></button>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @if($swaps->hasPages())
            <div class="nvm-pager nvm-gap-t">{{ $swaps->links() }}</div>
        @endif
    @endif
</section>

</div>
@endsection

@push('scripts')
@include('user.dex.partials.nova-csv')
@endpush
