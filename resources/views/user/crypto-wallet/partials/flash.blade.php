{{--
    แจ้งผลการทำรายการ (flash + ข้อผิดพลาดจากการตรวจฟอร์ม) แบบการ์ด nvm-alert เหมือนหน้า KYC
    $errorTitle = หัวข้อเมื่อฟอร์มไม่ผ่าน (ไม่ส่งมา = ไม่แสดงกล่องข้อผิดพลาดของฟอร์ม)
--}}
@php
    $cwFlashes = [
        'success' => ['ok', 'check', 'สำเร็จ'],
        'error' => ['bad', 'info', 'ทำรายการไม่สำเร็จ'],
        'warning' => ['', 'info', 'โปรดทราบ'],
        'info' => ['info', 'info', 'แจ้งให้ทราบ'],
    ];
@endphp
@foreach($cwFlashes as $cwKey => [$cwTone, $cwIcon, $cwHeading])
    @if(session($cwKey))
        <section class="nvm-card nvm-alert {{ $cwTone ? 'nvm-alert--'.$cwTone : '' }}" role="{{ $cwTone === 'bad' ? 'alert' : 'status' }}">
            <span class="nvm-alert__ic"><svg class="nvm-ico"><use href="#nvm-i-{{ $cwIcon }}"/></svg></span>
            <div class="nvm-alert__txt"><b>{{ $cwHeading }}</b><p>{{ session($cwKey) }}</p></div>
        </section>
    @endif
@endforeach
@if(! empty($errorTitle) && $errors->any())
    <section class="nvm-card nvm-alert nvm-alert--bad" role="alert">
        <span class="nvm-alert__ic"><svg class="nvm-ico"><use href="#nvm-i-info"/></svg></span>
        <div class="nvm-alert__txt">
            <b>{{ $errorTitle }}</b>
            @foreach($errors->all() as $cwError)<p>{{ $cwError }}</p>@endforeach
        </div>
    </section>
@endif
