@extends('layouts.admin')

@section('title', 'RGB Effects - Arrow X')

@section('content')
<div class="container-fluid px-4 py-8">
    <div class="max-w-6xl mx-auto">
        <div class="mb-8">
            <a href="{{ route('admin.arrow-x-theme.index') }}" class="text-purple-600 dark:text-purple-400 hover:underline mb-4 inline-block">
                <i class="fas fa-arrow-left mr-2"></i> กลับ
            </a>
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900 dark:text-white mb-2">RGB Effects</h1>
                    <p class="text-gray-600 dark:text-gray-400">เอฟเฟกต์เรืองแสงสำหรับ Active States</p>
                </div>
                <button onclick="alert('Create RGB Effect form - Coming soon!')" class="px-6 py-3 bg-gradient-to-r from-purple-500 via-pink-500 to-orange-500 text-white rounded-xl">
                    <i class="fas fa-plus mr-2"></i> สร้าง Effect
                </button>
            </div>
        </div>

        @if(session('success'))
        <div class="bg-green-50 dark:bg-green-900/20 border border-green-200 text-green-800 dark:text-green-300 px-4 py-3 rounded-xl mb-6">
            {{ session('success') }}
        </div>
        @endif

        {{-- RGB Effects List --}}
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            @forelse($rgbEffects as $effect)
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6">
                <div class="flex items-start justify-between mb-4">
                    <div>
                        <h3 class="text-lg font-bold text-gray-900 dark:text-white">{{ $effect->effect_name }}</h3>
                        <p class="text-sm text-gray-600 dark:text-gray-400">{{ ucfirst($effect->target_element) }}</p>
                    </div>
                    <span class="px-3 py-1 bg-{{ $effect->is_enabled ? 'green' : 'gray' }}-100 dark:bg-{{ $effect->is_enabled ? 'green' : 'gray' }}-900/30 text-{{ $effect->is_enabled ? 'green' : 'gray' }}-800 dark:text-{{ $effect->is_enabled ? 'green' : 'gray' }}-300 text-xs rounded-full">
                        {{ $effect->is_enabled ? 'Active' : 'Disabled' }}
                    </span>
                </div>

                <div class="space-y-2 text-sm text-gray-600 dark:text-gray-400">
                    <div><strong>Animation:</strong> {{ ucfirst($effect->animation_type) }}</div>
                    <div><strong>Trigger:</strong> {{ ucfirst($effect->trigger_state) }}</div>
                    <div><strong>Duration:</strong> {{ $effect->animation_duration }}ms</div>
                    <div><strong>Intensity:</strong> {{ ucfirst($effect->intensity) }}</div>
                </div>

                <div class="flex gap-2 mt-4">
                    <form action="{{ route('admin.arrow-x-theme.rgb-effects.destroy', $effect) }}" method="POST">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="px-4 py-2 bg-red-100 dark:bg-red-900/30 text-red-800 dark:text-red-300 rounded-lg text-sm" onclick="return confirm('ยืนยันการลบ?')">
                            <i class="fas fa-trash"></i> ลบ
                        </button>
                    </form>
                </div>
            </div>
            @empty
            <div class="col-span-2 text-center py-12 text-gray-500 dark:text-gray-400">
                ไม่มี RGB Effects<br>
                <small class="text-xs">คลิก "สร้าง Effect" เพื่อเพิ่มเอฟเฟกต์ใหม่</small>
            </div>
            @endforelse
        </div>
    </div>
</div>
@endsection
