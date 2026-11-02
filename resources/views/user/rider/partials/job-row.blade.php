{{--
    แถวงาน 1 รายการ (ประวัติงาน / งานล่าสุด) — ธีมโนวา (วางใน <div class="nvm-txs nvm-tks">)
    ตัวแปร: $row = ['id','job_number','job_type','job_type_text','status','status_text','rider_earnings','cod_amount','created_at','completed_at']
--}}
@php
    $ui = \App\Support\RiderWebUi::class;
    $rowStatus = (string) ($row['status'] ?? '');
    $rowDone = in_array($rowStatus, ['completed', 'delivered'], true);
    $rowIcon = $rowDone ? 'check' : ($rowStatus === 'failed' ? 'info' : ($rowStatus === 'cancelled' ? 'x' : $ui::novaJobIcon($row['job_type'] ?? null)));
@endphp
<a class="nvm-tx" href="{{ route('user.rider.jobs.show', (int) $row['id']) }}">
    <span class="nvm-tx__ic {{ $rowDone ? 'is-in' : ($rowStatus === 'failed' ? 'is-due' : '') }}"><svg class="nvm-ico"><use href="#nvm-i-{{ $rowIcon }}"/></svg></span>
    <div class="nvm-tx__t">
        <b>{{ $row['job_type_text'] ?? 'ส่งของ' }} · <span class="nvm-num">#{{ $row['job_number'] }}</span></b>
        <small class="nvm-num">{{ $ui::shortDate(($row['completed_at'] ?? null) ?: ($row['created_at'] ?? null)) }} · {{ $row['status_text'] ?? '' }}</small>
    </div>
    <div class="nvm-tx__r">
        @if($rowDone)
            <span class="nvm-tx__amt nvm-num is-in">+฿{{ $ui::money($row['rider_earnings'] ?? 0) }}</span>
        @else
            <span class="nvm-chip {{ $ui::novaChip($rowStatus) }}">{{ $row['status_text'] ?? '' }}</span>
        @endif
        @if((float) ($row['cod_amount'] ?? 0) > 0)
            <time class="nvm-num">COD ฿{{ $ui::money($row['cod_amount']) }}</time>
        @endif
    </div>
</a>
