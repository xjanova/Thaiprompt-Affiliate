@extends('layouts.admin')

@section('title', 'จัดการ System Tray')

@section('content')
<div class="container-fluid px-4 py-6">
    <div class="mb-6">
        <a href="{{ route('admin.windows-ui.index') }}" class="text-blue-600 hover:text-blue-700 dark:text-blue-400">
            ← กลับไปหน้าหลัก
        </a>
    </div>

    <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-lg p-6">
        <h2 class="text-2xl font-bold mb-4 text-gray-900 dark:text-white">จัดการ System Tray Icons</h2>
        <p class="text-gray-600 dark:text-gray-400">Feature นี้กำลังพัฒนา...</p>
    </div>
</div>
@endsection
