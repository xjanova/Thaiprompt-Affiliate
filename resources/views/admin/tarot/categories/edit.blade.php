@extends('layouts.admin-v3')

@section('title', 'แก้ไขหมวดหมู่')

@section('content')
<div class="container mx-auto px-4 py-8 max-w-4xl">
    <h1 class="text-3xl font-bold mb-6">แก้ไขหมวดหมู่</h1>

    <form action="{{ route('admin.tarot.categories.update', $category->id) }}" method="POST" class="glass-fusion rounded-xl shadow p-6">
        @csrf
        @method('PUT')

        <div class="grid grid-cols-2 gap-6 mb-6">
            <div>
                <label class="block text-sm font-medium mb-2">ชื่อภาษาไทย *</label>
                <input type="text" name="name_th" value="{{ $category->name_th }}" required class="w-full rounded-xl border-gray-300 dark:border-gray-600">
            </div>
            <div>
                <label class="block text-sm font-medium mb-2">ชื่อภาษาอังกฤษ *</label>
                <input type="text" name="name_en" value="{{ $category->name_en }}" required class="w-full rounded-xl border-gray-300 dark:border-gray-600">
            </div>
        </div>

        <div class="grid grid-cols-3 gap-6 mb-6">
            <div>
                <label class="block text-sm font-medium mb-2">ไอคอน</label>
                <input type="text" name="icon" value="{{ $category->icon }}" class="w-full rounded-xl border-gray-300 dark:border-gray-600">
            </div>
            <div>
                <label class="block text-sm font-medium mb-2">สี</label>
                <input type="color" name="color" value="{{ $category->color }}" class="w-full h-10 rounded-xl border-gray-300 dark:border-gray-600">
            </div>
            <div>
                <label class="block text-sm font-medium mb-2">ราคา</label>
                <input type="number" name="price" step="0.01" min="0" value="{{ $category->price }}" required class="w-full rounded-xl border-gray-300 dark:border-gray-600">
            </div>
        </div>

        <div class="mb-6">
            <label class="flex items-center">
                <input type="checkbox" name="is_free_first" value="1" {{ $category->is_free_first ? 'checked' : '' }} class="rounded">
                <span class="ml-2 text-sm">ฟรีครั้งแรกต่อวัน</span>
            </label>
        </div>

        <div class="mb-6">
            <label class="flex items-center">
                <input type="checkbox" name="is_active" value="1" {{ $category->is_active ? 'checked' : '' }} class="rounded">
                <span class="ml-2 text-sm">เปิดใช้งาน</span>
            </label>
        </div>

        <div class="flex gap-4">
            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-xl">บันทึก</button>
            <a href="{{ route('admin.tarot.categories.index') }}" class="bg-gray-300 hover:bg-gray-400 px-6 py-2 rounded-xl">ยกเลิก</a>
        </div>
    </form>
</div>
@endsection
