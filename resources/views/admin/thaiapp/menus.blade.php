@extends('layouts.arrow-x-v3')
@section('title', 'Menus · แอพ')
@section('content')
<div class="container-fluid px-4 py-6">
    <a href="{{ route('admin.thaiapp.hub') }}" class="text-sm text-gray-500 hover:text-blue-500">← Thaiapp-MANAGER</a>
    <div class="flex items-center justify-between mt-1 mb-6">
        <h1 class="text-3xl font-bold">🧩 Menus (home tiles)</h1>
        <button onclick="document.getElementById('add').classList.toggle('hidden')" class="px-4 py-2 bg-blue-600 text-white rounded-lg">+ เพิ่ม menu</button>
    </div>
    @include('admin.thaiapp._flash')

    <div id="add" class="hidden mb-6 p-6 bg-white dark:bg-gray-800 border rounded-xl">
        <form method="POST" action="{{ route('admin.thaiapp.menus.store') }}" class="grid grid-cols-6 gap-3">
            @csrf
            <select name="slot" required class="px-3 py-2 bg-gray-50 dark:bg-gray-900 border rounded-lg">
                <option value="home_grid">home_grid</option>
                <option value="home_quick">home_quick</option>
                <option value="drawer">drawer</option>
                <option value="taladsod">taladsod</option>
            </select>
            <input type="number" name="order" value="0" placeholder="order" class="px-3 py-2 bg-gray-50 dark:bg-gray-900 border rounded-lg">
            <input type="text" name="icon" placeholder="🥬 or icon name" class="px-3 py-2 bg-gray-50 dark:bg-gray-900 border rounded-lg">
            <input type="text" name="label_th" placeholder="ผัก-ผลไม้" required class="col-span-2 px-3 py-2 bg-gray-50 dark:bg-gray-900 border rounded-lg">
            <input type="text" name="label_en" placeholder="Produce" class="px-3 py-2 bg-gray-50 dark:bg-gray-900 border rounded-lg">
            <input type="text" name="action" placeholder="/taladsod/listings?category=1" required class="col-span-4 px-3 py-2 bg-gray-50 dark:bg-gray-900 border rounded-lg font-mono text-sm">
            <select name="role" class="px-3 py-2 bg-gray-50 dark:bg-gray-900 border rounded-lg">
                <option value="">any</option><option value="guest">guest</option><option value="authed">authed</option>
            </select>
            <button type="submit" class="px-4 py-2 bg-emerald-600 text-white rounded-lg">เพิ่ม</button>
        </form>
    </div>

    <div class="space-y-2">
        @forelse($menus as $m)
        <form method="POST" action="{{ route('admin.thaiapp.menus.update', $m->id) }}" class="p-3 bg-white dark:bg-gray-800 border rounded-xl grid grid-cols-12 gap-2 items-center text-sm">
            @csrf @method('PUT')
            <input type="text" name="slot" value="{{ $m->slot }}" class="col-span-2 px-2 py-2 bg-gray-50 dark:bg-gray-900 border rounded-lg font-mono text-xs">
            <input type="number" name="order" value="{{ $m->order }}" class="col-span-1 px-2 py-2 bg-gray-50 dark:bg-gray-900 border rounded-lg text-center">
            <input type="text" name="icon" value="{{ $m->icon }}" class="col-span-1 px-2 py-2 bg-gray-50 dark:bg-gray-900 border rounded-lg">
            <input type="text" name="label_th" value="{{ $m->label_th }}" required class="col-span-2 px-2 py-2 bg-gray-50 dark:bg-gray-900 border rounded-lg">
            <input type="text" name="action" value="{{ $m->action }}" required class="col-span-3 px-2 py-2 bg-gray-50 dark:bg-gray-900 border rounded-lg font-mono text-xs">
            <label class="col-span-1 flex items-center gap-1">
                <input type="hidden" name="enabled" value="0">
                <input type="checkbox" name="enabled" value="1" @checked($m->enabled)>
                on
            </label>
            <div class="col-span-2 flex gap-1">
                <button class="flex-1 px-2 py-2 bg-blue-600 text-white rounded">💾 Save</button>
        </form>
                <form method="POST" action="{{ route('admin.thaiapp.menus.destroy', $m->id) }}" onsubmit="return confirm('ลบ?')">
                    @csrf @method('DELETE')
                    <button class="px-2 py-2 bg-rose-600 text-white rounded">🗑️</button>
                </form>
            </div>
        @empty
        <div class="p-8 bg-white dark:bg-gray-800 border rounded-xl text-center text-gray-500">ยังไม่มี menu</div>
        @endforelse
    </div>
</div>
@endsection
