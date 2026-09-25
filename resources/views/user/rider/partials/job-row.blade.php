{{--
    แถวงาน 1 รายการ (ประวัติงาน / งานล่าสุด)
    ตัวแปร: $row = ['id','job_number','job_type','job_type_text','status','status_text','rider_earnings','cod_amount','created_at','completed_at']
--}}
@php
    $ui = \App\Support\RiderWebUi::class;
    $rowStatus = (string) ($row['status'] ?? '');
    $rowTone = $ui::jobTone($rowStatus);
    $rowDone = in_array($rowStatus, ['completed', 'delivered'], true);
@endphp
<a href="{{ route('user.rider.jobs.show', (int) $row['id']) }}" class="rd-item rd-tone-{{ $rowTone }}">
    <span class="ic"><i class="fas {{ $rowDone ? 'fa-circle-check' : ($rowStatus === 'failed' ? 'fa-triangle-exclamation' : ($rowStatus === 'cancelled' ? 'fa-ban' : $ui::jobIcon($row['job_type'] ?? null))) }}"></i></span>
    <span class="main">
        <span class="ttl" style="display:block;">{{ $row['job_type_text'] ?? 'ส่งของ' }} · #{{ $row['job_number'] }}</span>
        <span class="sub" style="display:block;">{{ $ui::shortDate(($row['completed_at'] ?? null) ?: ($row['created_at'] ?? null)) }} · {{ $row['status_text'] ?? '' }}</span>
    </span>
    <span class="end">
        @if($rowDone)
            <span class="rd-money" style="font-size:16px; color:var(--rd-ok);">+฿{{ $ui::money($row['rider_earnings'] ?? 0) }}</span>
        @else
            <span class="rd-pill rd-tone-{{ $rowTone }}">{{ $row['status_text'] ?? '' }}</span>
        @endif
        @if((float) ($row['cod_amount'] ?? 0) > 0)
            <span class="rd-small rd-muted" style="display:block; margin-top:3px;">COD ฿{{ $ui::money($row['cod_amount']) }}</span>
        @endif
    </span>
</a>
