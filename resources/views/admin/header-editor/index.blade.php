@extends('layouts.admin')

@section('title', 'แก้ไข Header, Template & Menu')

@push('styles')
<style>
    .color-input-wrapper {
        position: relative;
    }
    .color-input-wrapper input[type="color"] {
        position: absolute;
        opacity: 0;
        width: 100%;
        height: 100%;
        cursor: pointer;
    }
    .color-preview {
        width: 48px;
        height: 48px;
        border-radius: 8px;
        border: 2px solid #e5e7eb;
        cursor: pointer;
        transition: all 0.2s;
    }
    .dark .color-preview {
        border-color: #475569;
    }
    .color-preview:hover {
        transform: scale(1.1);
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    }
    .preview-header {
        transition: all 0.3s ease;
    }
    .setting-card {
        background: white;
        border-radius: 12px;
        padding: 1.5rem;
        margin-bottom: 1.5rem;
        box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        border: 1px solid #e5e7eb;
    }
    .dark .setting-card {
        background: #1e293b;
        border-color: #334155;
        box-shadow: 0 1px 3px rgba(0,0,0,0.5);
    }
    .setting-card:hover {
        box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        border-color: #3b82f6;
    }
    .dark .setting-card:hover {
        box-shadow: 0 4px 12px rgba(0,0,0,0.3);
        border-color: #6366f1;
    }
</style>
@endpush

@section('content')
<div class="mb-6">
    <h1 class="text-3xl font-bold text-gray-900 dark:text-white">📐 Header & Page Management</h1>
    <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">จัดการ Header, เลือกเทมเพลตหน้าแรก และสร้างเมนู</p>
</div>

@include('admin.header-editor._tabs-wrapper')
@endsection
