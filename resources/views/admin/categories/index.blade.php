@extends('layouts.admin')

@section('title', 'จัดการหมวดหมู่')

@push('styles')
<style>
:root { --primary: #4f46e5; }
.cm-header { background: linear-gradient(135deg, #4f46e5 0%, #4338ca 100%); border-radius: 16px; padding: 32px; color: white; margin-bottom: 24px; }
.cm-card { background: white; border-radius: 12px; padding: 24px; border: 1px solid #e5e7eb; margin-bottom: 16px; transition: all 0.2s; }
.cm-card:hover { box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1); }
.cm-icon { width: 48px; height: 48px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 24px; }
.cm-badge { display: inline-flex; padding: 4px 12px; border-radius: 6px; font-size: 12px; font-weight: 500; }
.cm-btn { padding: 10px 20px; border-radius: 8px; font-size: 14px; font-weight: 500; border: none; cursor: pointer; transition: all 0.2s; }
.cm-btn-primary { background: var(--primary); color: white; }
.cm-btn-success { background: #10b981; color: white; }
</style>
@endpush

@section('content')
<div class="container-fluid py-4">
    <div class="cm-header">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h1 style="font-size: 28px; font-weight: 700; margin-bottom: 8px;">จัดการหมวดหมู่</h1>
                <p style="opacity: 0.9; margin: 0;">จัดระเบียบเนื้อหาการเรียนรู้</p>
            </div>
            <a href="{{ route('admin.categories.create') }}" class="cm-btn cm-btn-success">
                <i class="fas fa-plus"></i> สร้างหมวดหมู่ใหม่
            </a>
        </div>
    </div>

    @foreach($categories as $category)
        <div class="cm-card">
            <div class="row align-items-center">
                <div class="col-auto">
                    <div class="cm-icon" style="background: {{ $category->color ?? '#e5e7eb' }}22;">
                        {{ $category->icon ?? '📚' }}
                    </div>
                </div>
                <div class="col">
                    <h3 style="font-size: 18px; font-weight: 600; margin-bottom: 4px;">{{ $category->name }}</h3>
                    <p style="color: #6b7280; margin: 0; font-size: 14px;">{{ $category->description }}</p>
                </div>
                <div class="col-auto">
                    <span class="cm-badge" style="background: #f3f4f6; color: #374151;">
                        {{ $category->articles_count }} บทความ
                    </span>
                </div>
                <div class="col-auto">
                    @if($category->is_active)
                        <span class="cm-badge" style="background: #d1fae5; color: #065f46;">
                            <i class="fas fa-check-circle"></i> เปิดใช้งาน
                        </span>
                    @else
                        <span class="cm-badge" style="background: #fee2e2; color: #991b1b;">
                            <i class="fas fa-times-circle"></i> ปิดใช้งาน
                        </span>
                    @endif
                </div>
                <div class="col-auto">
                    <div class="d-flex gap-2">
                        <a href="{{ route('admin.categories.edit', $category) }}" class="cm-btn" style="background: #f3f4f6; color: #374151;">
                            <i class="fas fa-edit"></i> แก้ไข
                        </a>
                        @if($category->articles_count == 0)
                            <form method="POST" action="{{ route('admin.categories.destroy', $category) }}"
                                  onsubmit="return confirm('ต้องการลบหมวดหมู่นี้?');" style="display: inline;">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="cm-btn" style="background: #fee2e2; color: #991b1b;">
                                    <i class="fas fa-trash"></i> ลบ
                                </button>
                            </form>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    @endforeach

    @if($categories->count() == 0)
        <div style="text-align: center; padding: 60px; background: white; border-radius: 12px;">
            <div style="font-size: 64px; opacity: 0.3;">📁</div>
            <h3 style="margin-top: 16px;">ยังไม่มีหมวดหมู่</h3>
            <p style="color: #6b7280;">สร้างหมวดหมู่แรกของคุณ</p>
            <a href="{{ route('admin.categories.create') }}" class="cm-btn cm-btn-success">
                <i class="fas fa-plus"></i> สร้างหมวดหมู่ใหม่
            </a>
        </div>
    @endif
</div>
@endsection
