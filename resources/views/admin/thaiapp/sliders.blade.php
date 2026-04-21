@extends('layouts.arrow-x-v3')
@section('title', 'Sliders · แอพ')
@section('content')
<div class="container-fluid px-4 py-6">
    <a href="{{ route('admin.thaiapp.hub') }}" class="text-sm text-gray-500 hover:text-blue-500">← Thaiapp-MANAGER</a>
    <div class="flex items-center justify-between mt-1 mb-6">
        <h1 class="text-3xl font-bold">🖼️ Sliders (hero carousel)</h1>
        <button onclick="document.getElementById('add').classList.toggle('hidden')" class="px-4 py-2 bg-blue-600 text-white rounded-lg">+ เพิ่ม slider</button>
    </div>
    @include('admin.thaiapp._flash')

    <div id="add" class="hidden mb-6 p-6 bg-white dark:bg-gray-800 border rounded-xl">
        <form method="POST" action="{{ route('admin.thaiapp.sliders.store') }}" class="grid grid-cols-2 gap-3">
            @csrf
            <input type="number" name="order" placeholder="ลำดับ (0-100)" value="0" class="px-3 py-2 bg-gray-50 dark:bg-gray-900 border rounded-lg">
            <select name="media_type" class="px-3 py-2 bg-gray-50 dark:bg-gray-900 border rounded-lg">
                <option value="image">image</option><option value="video">video</option>
            </select>
            <input type="text" name="title_th" placeholder="Title TH" required class="col-span-2 px-3 py-2 bg-gray-50 dark:bg-gray-900 border rounded-lg">
            <input type="text" name="title_en" placeholder="Title EN" class="col-span-2 px-3 py-2 bg-gray-50 dark:bg-gray-900 border rounded-lg">
            <input type="text" name="subtitle_th" placeholder="Subtitle TH" class="col-span-2 px-3 py-2 bg-gray-50 dark:bg-gray-900 border rounded-lg">
            <input type="text" name="cta_label_th" placeholder="CTA label (เช่น 'ดูเลย')" class="px-3 py-2 bg-gray-50 dark:bg-gray-900 border rounded-lg">
            <input type="text" name="cta_deeplink" placeholder="/taladsod · /shop/1" class="px-3 py-2 bg-gray-50 dark:bg-gray-900 border rounded-lg">
            <input type="text" name="media_url" placeholder="Media URL (https://...)" class="col-span-2 px-3 py-2 bg-gray-50 dark:bg-gray-900 border rounded-lg">
            <button type="submit" class="col-span-2 px-4 py-2 bg-emerald-600 text-white rounded-lg">เพิ่ม</button>
        </form>
    </div>

    <div class="space-y-3">
        @forelse($sliders as $s)
        <form method="POST" action="{{ route('admin.thaiapp.sliders.update', $s->id) }}" class="p-4 bg-white dark:bg-gray-800 border rounded-xl grid grid-cols-12 gap-3 items-center">
            @csrf @method('PUT')
            <input type="number" name="order" value="{{ $s->order }}" class="col-span-1 px-2 py-2 bg-gray-50 dark:bg-gray-900 border rounded-lg text-center">
            <input type="text" name="title_th" value="{{ $s->title_th }}" required class="col-span-3 px-3 py-2 bg-gray-50 dark:bg-gray-900 border rounded-lg">
            <input type="text" name="subtitle_th" value="{{ $s->subtitle_th }}" class="col-span-3 px-3 py-2 bg-gray-50 dark:bg-gray-900 border rounded-lg">
            <input type="text" name="cta_deeplink" value="{{ $s->cta_deeplink }}" placeholder="/route" class="col-span-2 px-3 py-2 bg-gray-50 dark:bg-gray-900 border rounded-lg font-mono text-xs">
            <input type="text" name="media_url" value="{{ $s->media_url }}" class="col-span-2 px-3 py-2 bg-gray-50 dark:bg-gray-900 border rounded-lg text-xs">
            <div class="col-span-1 flex gap-1">
                <button class="px-2 py-2 bg-blue-600 text-white rounded">💾</button>
        </form>
                <form method="POST" action="{{ route('admin.thaiapp.sliders.destroy', $s->id) }}" onsubmit="return confirm('ลบ?')">
                    @csrf @method('DELETE')
                    <button class="px-2 py-2 bg-rose-600 text-white rounded">🗑️</button>
                </form>
            </div>
        @empty
        <div class="p-8 bg-white dark:bg-gray-800 border rounded-xl text-center text-gray-500">ยังไม่มี slider</div>
        @endforelse
    </div>
</div>
@endsection
