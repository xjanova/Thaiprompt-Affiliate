@extends('layouts.admin')

@section('title', 'รายละเอียดเกม')

@section('content')
<div class="container mx-auto px-4 py-6">
    <div class="mb-6 flex justify-between items-center">
        <a href="{{ route('admin.games.index') }}" class="text-indigo-600 dark:text-indigo-400 hover:text-indigo-900 dark:hover:text-indigo-300">
            ← กลับไปรายการเกม
        </a>
        <div class="flex gap-2">
            <a href="{{ route('admin.games.edit', $game) }}" class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-lg font-semibold">
                ✏️ แก้ไข
            </a>
            <form action="{{ route('admin.games.destroy', $game) }}" method="POST" class="inline" onsubmit="return confirm('คุณแน่ใจที่จะลบเกมนี้?')">
                @csrf
                @method('DELETE')
                <button type="submit" class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-lg font-semibold">
                    🗑️ ลบ
                </button>
            </form>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Main Info Card -->
        <div class="lg:col-span-2">
            <div class="bg-white dark:bg-slate-800 rounded-lg shadow-md overflow-hidden">
                @if($game->image)
                    <div class="w-full h-64 bg-gray-200 dark:bg-slate-700">
                        <img src="{{ asset($game->image) }}" alt="{{ $game->title }}" class="w-full h-full object-cover">
                    </div>
                @endif

                <div class="p-6">
                    <div class="flex items-center justify-between mb-4">
                        <div class="flex items-center gap-4">
                            <span class="text-6xl">{{ $game->icon }}</span>
                            <div>
                                <h1 class="text-3xl font-bold text-gray-900 dark:text-white">{{ $game->title }}</h1>
                                @if($game->title_en)
                                    <p class="text-lg text-gray-600 dark:text-gray-400">{{ $game->title_en }}</p>
                                @endif
                            </div>
                        </div>
                        <span class="px-4 py-2 rounded-full text-sm font-semibold {{ $game->is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                            {{ $game->is_active ? '✓ เปิดใช้งาน' : '✗ ปิดใช้งาน' }}
                        </span>
                    </div>

                    <div class="space-y-4">
                        <!-- Description Thai -->
                        <div>
                            <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">คำอธิบาย (ไทย)</h3>
                            <p class="text-gray-600 dark:text-gray-400 whitespace-pre-line">{{ $game->description ?: '-' }}</p>
                        </div>

                        <!-- Description English -->
                        @if($game->description_en)
                            <div>
                                <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">Description (English)</h3>
                                <p class="text-gray-600 dark:text-gray-400 whitespace-pre-line">{{ $game->description_en }}</p>
                            </div>
                        @endif

                        <!-- URL -->
                        <div>
                            <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">URL</h3>
                            <a href="{{ $game->url }}" target="_blank" class="text-indigo-600 hover:text-indigo-900 dark:text-indigo-400 break-all">
                                {{ $game->url }} ↗
                            </a>
                        </div>

                        <!-- Preview Link -->
                        <div class="pt-4">
                            <a href="{{ $game->url }}" target="_blank" class="inline-flex items-center gap-2 bg-gradient-to-r from-indigo-600 to-purple-600 hover:from-indigo-700 hover:to-purple-700 text-white px-6 py-3 rounded-lg font-semibold shadow-lg transition">
                                🎮 เล่นเกมนี้
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Side Info Cards -->
        <div class="space-y-6">
            <!-- Colors Card -->
            <div class="bg-white dark:bg-slate-800 rounded-lg shadow-md p-6">
                <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4">🎨 สี</h2>
                <div class="space-y-3">
                    <div>
                        <p class="text-sm text-gray-600 dark:text-gray-400 mb-1">สีหลัก</p>
                        <div class="flex items-center gap-2">
                            <div class="w-12 h-12 rounded-lg shadow" style="background: {{ $game->primary_color }};"></div>
                            <code class="text-sm">{{ $game->primary_color }}</code>
                        </div>
                    </div>
                    <div>
                        <p class="text-sm text-gray-600 dark:text-gray-400 mb-1">สีรอง</p>
                        <div class="flex items-center gap-2">
                            <div class="w-12 h-12 rounded-lg shadow" style="background: {{ $game->secondary_color }};"></div>
                            <code class="text-sm">{{ $game->secondary_color }}</code>
                        </div>
                    </div>
                    <div>
                        <p class="text-sm text-gray-600 dark:text-gray-400 mb-1">สี Glow</p>
                        <code class="text-sm break-all">{{ $game->glow_color }}</code>
                    </div>
                </div>
            </div>

            <!-- Settings Card -->
            <div class="bg-white dark:bg-slate-800 rounded-lg shadow-md p-6">
                <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4">⚙️ การตั้งค่า</h2>
                <div class="space-y-3">
                    <div class="flex justify-between">
                        <span class="text-gray-600 dark:text-gray-400">รูปแบบการ์ด</span>
                        <span class="font-semibold text-gray-900 dark:text-white capitalize">{{ $game->card_style }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600 dark:text-gray-400">ลำดับแสดงผล</span>
                        <span class="font-semibold text-gray-900 dark:text-white">{{ $game->order }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600 dark:text-gray-400">สถานะ</span>
                        <span class="font-semibold {{ $game->is_active ? 'text-green-600' : 'text-red-600' }}">
                            {{ $game->is_active ? 'เปิดใช้งาน' : 'ปิดใช้งาน' }}
                        </span>
                    </div>
                </div>
            </div>

            <!-- Metadata Card -->
            <div class="bg-white dark:bg-slate-800 rounded-lg shadow-md p-6">
                <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4">📊 ข้อมูลระบบ</h2>
                <div class="space-y-2 text-sm">
                    <div class="flex justify-between">
                        <span class="text-gray-600 dark:text-gray-400">ID</span>
                        <span class="font-mono text-gray-900 dark:text-white">{{ $game->id }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600 dark:text-gray-400">สร้างเมื่อ</span>
                        <span class="text-gray-900 dark:text-white">{{ $game->created_at->format('d/m/Y H:i') }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600 dark:text-gray-400">แก้ไขล่าสุด</span>
                        <span class="text-gray-900 dark:text-white">{{ $game->updated_at->format('d/m/Y H:i') }}</span>
                    </div>
                </div>
            </div>

            <!-- Preview Card -->
            <div class="bg-white dark:bg-slate-800 rounded-lg shadow-md p-6">
                <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4">👁️ ตัวอย่างการ์ด</h2>
                <div class="relative">
                    <div class="aspect-[3/4] rounded-lg overflow-hidden" style="background: rgba(10, 10, 30, 0.85); border: 2px solid {{ $game->primary_color }};">
                        @if($game->image)
                            <img src="{{ asset($game->image) }}" alt="{{ $game->title }}" class="w-full h-full object-cover opacity-20">
                        @endif
                        <div class="absolute inset-0 flex flex-col items-center justify-center text-center p-4">
                            <div class="text-5xl mb-3" style="color: {{ $game->primary_color }};">{{ $game->icon }}</div>
                            <div class="text-lg font-bold mb-2" style="color: {{ $game->primary_color }}; text-shadow: 0 0 10px {{ $game->glow_color }};">
                                {{ $game->title }}
                            </div>
                            <div class="text-sm text-gray-300 text-center whitespace-pre-line">
                                {{ Str::limit($game->description, 60) }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
