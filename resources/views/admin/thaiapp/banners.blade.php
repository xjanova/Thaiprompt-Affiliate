@extends('layouts.arrow-x-v3')
@section('title', 'Banners · แอพ')
@section('content')
<div class="container-fluid px-4 py-6">
    <a href="{{ route('admin.thaiapp.hub') }}" class="text-sm text-gray-500 hover:text-blue-500">← Thaiapp-MANAGER</a>
    <div class="flex items-center justify-between mt-1 mb-6">
        <h1 class="text-3xl font-bold">🎨 Banners</h1>
        <button onclick="document.getElementById('addBanner').classList.toggle('hidden')" class="px-4 py-2 bg-blue-600 text-white rounded-lg">+ เพิ่ม banner</button>
    </div>
    @include('admin.thaiapp._flash')

    <div id="addBanner" class="hidden mb-6 p-6 bg-white dark:bg-gray-800 border rounded-xl">
        <form method="POST" action="{{ route('admin.thaiapp.banners.store') }}" class="grid grid-cols-2 gap-3">
            @csrf
            <input type="text" name="title" placeholder="ชื่อ (TH)" required class="px-3 py-2 bg-gray-50 dark:bg-gray-900 border rounded-lg">
            <input type="text" name="title_en" placeholder="ชื่อ (EN)" class="px-3 py-2 bg-gray-50 dark:bg-gray-900 border rounded-lg">
            <input type="text" name="description" placeholder="รายละเอียด" class="col-span-2 px-3 py-2 bg-gray-50 dark:bg-gray-900 border rounded-lg">
            <input type="text" name="image_url" placeholder="URL รูป (เริ่มด้วย https://...)" class="col-span-2 px-3 py-2 bg-gray-50 dark:bg-gray-900 border rounded-lg">
            <input type="text" name="background_color" placeholder="#FFF8EC" class="px-3 py-2 bg-gray-50 dark:bg-gray-900 border rounded-lg">
            <button type="submit" class="px-4 py-2 bg-emerald-600 text-white rounded-lg">เพิ่ม</button>
        </form>
    </div>

    <div class="space-y-3">
        @forelse($banners as $b)
        <div class="p-4 bg-white dark:bg-gray-800 border rounded-xl flex gap-4">
            <form method="POST" action="{{ route('admin.thaiapp.banners.update', $b->id) }}" class="flex-1 grid grid-cols-6 gap-3 items-center">
                @csrf @method('PUT')
                <input type="text" name="title" value="{{ $b->title }}" placeholder="TH" class="col-span-2 px-3 py-2 bg-gray-50 dark:bg-gray-900 border rounded-lg" required>
                <input type="text" name="title_en" value="{{ $b->title_en }}" placeholder="EN" class="col-span-2 px-3 py-2 bg-gray-50 dark:bg-gray-900 border rounded-lg">
                <input type="text" name="background_color" value="{{ $b->background_color }}" placeholder="#FFF8EC" class="col-span-1 px-3 py-2 bg-gray-50 dark:bg-gray-900 border rounded-lg font-mono text-xs">
                <button class="col-span-1 px-3 py-2 bg-blue-600 text-white rounded-lg">💾 Save</button>
                <input type="text" name="description" value="{{ $b->description }}" placeholder="รายละเอียด" class="col-span-4 px-3 py-2 bg-gray-50 dark:bg-gray-900 border rounded-lg">
                <input type="text" name="image_url" value="{{ $b->image_url }}" placeholder="URL รูป (https://...)" class="col-span-2 px-3 py-2 bg-gray-50 dark:bg-gray-900 border rounded-lg text-xs">
            </form>
            <div class="flex flex-col items-end gap-2 flex-shrink-0">
                @if($b->image_url)
                    <img src="{{ $b->image_url }}" alt="" class="h-16 w-24 rounded-lg object-cover border">
                @endif
                <form method="POST" action="{{ route('admin.thaiapp.banners.destroy', $b->id) }}" onsubmit="return confirm('ลบ banner นี้?')">
                    @csrf @method('DELETE')
                    <button class="px-3 py-2 bg-rose-600 text-white rounded-lg text-sm">🗑️ ลบ</button>
                </form>
            </div>
        </div>
        @empty
        <div class="p-8 bg-white dark:bg-gray-800 border rounded-xl text-center text-gray-500">ยังไม่มี banner</div>
        @endforelse
    </div>
</div>
@endsection
