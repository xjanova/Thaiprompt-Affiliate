@extends('layouts.admin-v3')

@section('title', 'จัดการเกม')

@section('content')
<div class="container mx-auto px-4 py-6">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-3xl font-bold text-gray-900 dark:text-white">จัดการเกม 3D Gallery</h1>
        <a href="{{ route('admin.games.create') }}" class="bg-indigo-600 hover:bg-indigo-700 text-white px-6 py-3 rounded-lg font-semibold shadow-lg transition">
            ➕ เพิ่มเกมใหม่
        </a>
    </div>

    @if(session('success'))
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded-lg mb-4">
            {{ session('success') }}
        </div>
    @endif

    <div class="bg-white dark:bg-slate-800 rounded-lg shadow-md overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50 dark:bg-slate-700">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                            ลำดับ
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                            ไอคอน
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                            รูปภาพ
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                            ชื่อเกม
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                            URL
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                            สี
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                            สถานะ
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                            จัดการ
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-slate-800 divide-y divide-gray-200 dark:divide-slate-700">
                    @forelse($games as $game)
                        <tr class="hover:bg-gray-50 dark:hover:bg-slate-700 transition">
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                {{ $game->order }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-4xl">
                                {{ $game->icon }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                @if($game->image)
                                    <img src="{{ asset($game->image) }}" alt="{{ $game->title }}" class="w-16 h-16 object-cover rounded-lg">
                                @else
                                    <span class="text-gray-400 dark:text-gray-500">ไม่มีรูป</span>
                                @endif
                            </td>
                            <td class="px-6 py-4">
                                <div class="text-sm font-medium text-gray-900 dark:text-white">
                                    {{ $game->title }}
                                </div>
                                @if($game->title_en)
                                    <div class="text-sm text-gray-500 dark:text-gray-400">
                                        {{ $game->title_en }}
                                    </div>
                                @endif
                            </td>
                            <td class="px-6 py-4">
                                <a href="{{ $game->url }}" target="_blank" class="text-indigo-600 hover:text-indigo-900 dark:text-indigo-400 text-sm">
                                    {{ Str::limit($game->url, 30) }}
                                </a>
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex gap-2">
                                    <div class="w-8 h-8 rounded" style="background: {{ $game->primary_color }};" title="{{ $game->primary_color }}"></div>
                                    <div class="w-8 h-8 rounded" style="background: {{ $game->secondary_color }};" title="{{ $game->secondary_color }}"></div>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <form action="{{ route('admin.games.toggle-active', $game) }}" method="POST" class="inline">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit" class="px-3 py-1 rounded-full text-xs font-semibold {{ $game->is_active ? 'bg-green-100 text-green-800 hover:bg-green-200' : 'bg-red-100 text-red-800 hover:bg-red-200' }}">
                                        {{ $game->is_active ? '✓ เปิดใช้งาน' : '✗ ปิดใช้งาน' }}
                                    </button>
                                </form>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                <div class="flex gap-2">
                                    <a href="{{ route('admin.games.show', $game) }}" class="text-gray-600 hover:text-gray-900 dark:text-gray-400">
                                        👁️ ดู
                                    </a>
                                    <a href="{{ route('admin.games.edit', $game) }}" class="text-indigo-600 hover:text-indigo-900 dark:text-indigo-400">
                                        ✏️ แก้ไข
                                    </a>
                                    <form action="{{ route('admin.games.destroy', $game) }}" method="POST" class="inline" onsubmit="return confirm('คุณแน่ใจที่จะลบเกมนี้?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-red-600 hover:text-red-900 dark:text-red-400">
                                            🗑️ ลบ
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-6 py-8 text-center text-gray-500 dark:text-gray-400">
                                <div class="flex flex-col items-center">
                                    <span class="text-6xl mb-4">🎮</span>
                                    <p class="text-lg">ยังไม่มีเกมในระบบ</p>
                                    <a href="{{ route('admin.games.create') }}" class="mt-4 text-indigo-600 hover:text-indigo-900">
                                        เพิ่มเกมแรกของคุณ →
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
