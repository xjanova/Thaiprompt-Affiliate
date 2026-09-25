@extends('layouts.seller-v4')

@section('title', 'เพิ่มพนักงาน')

@section('content')
<div style="display:flex; flex-direction:column; gap:18px;">

    <x-seller-kit.header title="เพิ่มพนักงาน" icon="➕" crumb="ร้านค้า · พนักงาน"
                         subtitle="ระบบสร้างรหัสพนักงานให้อัตโนมัติ · ไม่เลือกแผนก/ตำแหน่ง ระบบจะใช้ “ทั่วไป / พนักงาน” ให้">
        <a href="{{ route('seller.staff.index') }}" class="tp-btn tp-btn-sm">← พนักงานทั้งหมด</a>
    </x-seller-kit.header>

    @include('seller.staff.partials.nav')

    <x-seller-kit.errors />

    @include('seller.staff.partials.form', [
        'employee' => null,
        'action' => route('seller.staff.store'),
        'method' => 'POST',
        'submitLabel' => '＋ เพิ่มพนักงาน',
    ])
</div>
@endsection
