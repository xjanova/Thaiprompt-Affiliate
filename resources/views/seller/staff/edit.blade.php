@extends('layouts.seller-v4')

@section('title', 'แก้ไขพนักงาน - '.$employee->full_name)

@section('content')
<div style="display:flex; flex-direction:column; gap:18px;">

    <x-seller-kit.header :title="'แก้ไข '.($employee->full_name_th ?: $employee->full_name)" icon="✏️" crumb="ร้านค้า · พนักงาน"
                         :subtitle="'รหัสพนักงาน '.$employee->employee_id">
        <a href="{{ route('seller.staff.show', $employee) }}" class="tp-btn tp-btn-sm">← ข้อมูลพนักงาน</a>
    </x-seller-kit.header>

    @include('seller.staff.partials.nav')

    <x-seller-kit.errors />

    @include('seller.staff.partials.form', [
        'employee' => $employee,
        'action' => route('seller.staff.update', $employee),
        'method' => 'PUT',
        'submitLabel' => '💾 บันทึกการแก้ไข',
    ])
</div>
@endsection
