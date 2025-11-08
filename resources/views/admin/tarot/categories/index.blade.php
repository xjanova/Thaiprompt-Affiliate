@extends('layouts.admin')

@section('title', 'จัดการหมวดหมู่')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-3xl font-bold text-gray-800">จัดการหมวดหมู่การทำนาย</h1>
        <a href="{{ route('admin.tarot.categories.create') }}" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg">
            <i class="fas fa-plus mr-2"></i> เพิ่มหมวดหมู่
        </a>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        @foreach($categories as $category)
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center justify-between mb-4">
                <div class="text-4xl" style="color: {{ $category->color }}">
                    <i class="fas {{ $category->icon }}"></i>
                </div>
                @if($category->is_active)
                    <span class="bg-green-100 text-green-800 px-2 py-1 text-xs rounded-full">Active</span>
                @else
                    <span class="bg-gray-100 text-gray-800 px-2 py-1 text-xs rounded-full">Inactive</span>
                @endif
            </div>

            <h3 class="text-xl font-bold text-gray-800 mb-2">{{ $category->name_th }}</h3>
            <p class="text-gray-600 text-sm mb-4">{{ $category->description_th }}</p>

            <div class="mb-4">
                @if($category->price > 0)
                    <div class="text-2xl font-bold text-gray-800">฿{{ number_format($category->price) }}</div>
                    @if($category->is_free_first)
                        <div class="text-sm text-green-600">ฟรีครั้งแรกต่อวัน</div>
                    @endif
                @else
                    <div class="text-2xl font-bold text-green-600">ฟรี</div>
                @endif
            </div>

            <div class="flex gap-2">
                <a href="{{ route('admin.tarot.categories.edit', $category->id) }}" class="flex-1 bg-blue-600 hover:bg-blue-700 text-white text-center py-2 rounded text-sm">
                    แก้ไข
                </a>
                <form action="{{ route('admin.tarot.categories.destroy', $category->id) }}" method="POST" class="flex-1">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="w-full bg-red-600 hover:bg-red-700 text-white py-2 rounded text-sm" onclick="return confirm('ต้องการลบ?')">
                        ลบ
                    </button>
                </form>
            </div>
        </div>
        @endforeach
    </div>
</div>
@endsection
