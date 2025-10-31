@extends('layouts.admin')

@section('title', 'แก้ไข Header, Template & Menu')

@section('content')
<div class="mb-6">
    <h1 class="text-3xl font-bold text-gray-900">📐 Header & Page Management</h1>
    <p class="mt-1 text-sm text-gray-600">จัดการ Header, เลือกเทมเพลตหน้าแรก และสร้างเมนู</p>
</div>

@include('admin.header-editor._tabs-wrapper')
@endsection
