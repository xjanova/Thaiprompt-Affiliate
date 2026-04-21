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
        <div class="p-4 bg-white dark:bg-gray-800 border rounded-xl">
            <form method="POST" action="{{ route('admin.thaiapp.banners.update', $b->id) }}" class="grid grid-cols-5 gap-3 items-center">
                @csrf @method('PUT')
                <input type="text" name="title" value="{{ $b->title }}" class="px-3 py-2 bg-gray-50 dark:bg-gray-900 border rounded-lg" required>
                <input type="text" name="title_en" value="{{ $b->title_en }}" placeholder="EN" class="px-3 py-2 bg-gray-50 dark:bg-gray-900 border rounded-lg">
                <input type="text" name="description" value="{{ $b->description }}" placeholder="desc" class="col-span-2 px-3 py-2 bg-gray-50 dark:bg-gray-900 border rounded-lg">
                <div class="flex gap-1">
                    <button class="flex-1 px-3 py-2 bg-blue-600 text-white rounded-lg">Save</button>
            </form>
                    <form method="POST" action="{{ route('admin.thaiapp.banners.destroy', $b->id) }}" onsubmit="return confirm('ลบ banner?')" class="flex-shrink-0">
                        @csrf @method('DELETE')
                        <button class="px-3 py-2 bg-rose-600 text-white rounded-lg">🗑️</button>
                    </form>
                </div>
            @if($b->image_url)
                <img src="{{ $b->image_url }}" alt="" class="mt-3 h-24 rounded-lg object-cover">
            @endif
        </div>
        @empty
        <div class="p-8 bg-white dark:bg-gray-800 border rounded-xl text-center text-gray-500">ยังไม่มี banner</div>
        @endforelse
    </div>
</div>
@endsection
