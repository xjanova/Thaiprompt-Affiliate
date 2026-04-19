@extends('layouts.admin-v3')

@section('title', 'จัดการหมวดหมู่การทำนาย')

@section('content')
<div class="container mx-auto px-4 py-8">
    {{-- Header --}}
    <div class="flex justify-between items-center mb-8">
        <div>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white mb-2">
                📂 จัดการหมวดหมู่การทำนาย
            </h1>
            <p class="text-gray-600 dark:text-gray-400">
                จัดการหมวดหมู่ที่ใช้ในการทำนาย เช่น ความรัก, การเงิน, สุขภาพ
            </p>
        </div>
        <a href="{{ route('admin.fortune.categories.create') }}" 
           class="px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition inline-flex items-center gap-2">
            <span>+</span>
            <span>เพิ่มหมวดหมู่ใหม่</span>
        </a>
    </div>

    {{-- Categories Grid --}}
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        @forelse($categories as $category)
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg hover:shadow-xl transition overflow-hidden">
                {{-- Category Header --}}
                <div class="p-6" style="background: linear-gradient(135deg, {{ $category->color }}22 0%, {{ $category->color }}44 100%);">
                    <div class="flex items-center justify-between mb-3">
                        <span class="text-4xl">{{ $category->icon }}</span>
                        <span class="px-3 py-1 text-xs font-semibold rounded-full {{ $category->is_active ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' : 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300' }}">
                            {{ $category->is_active ? 'เปิดใช้งาน' : 'ปิดใช้งาน' }}
                        </span>
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-2">
                        {{ $category->name }}
                    </h3>
                    <p class="text-sm text-gray-600 dark:text-gray-400">
                        {{ Str::limit($category->description, 80) }}
                    </p>
                </div>

                {{-- Category Stats --}}
                <div class="px-6 py-4 bg-gray-50 dark:bg-gray-700/50 border-t border-gray-200 dark:border-gray-600">
                    <div class="text-sm text-gray-600 dark:text-gray-400 mb-3">
                        <strong>Slug:</strong> <code class="text-xs bg-gray-200 dark:bg-gray-600 px-2 py-1 rounded">{{ $category->slug }}</code>
                    </div>
                    <div class="text-sm text-gray-600 dark:text-gray-400 mb-3">
                        <strong>ลำดับ:</strong> {{ $category->order }}
                    </div>

                    {{-- Actions --}}
                    <div class="flex gap-2 mt-4">
                        <a href="{{ route('admin.fortune.categories.edit', $category) }}" 
                           class="flex-1 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-center rounded-lg transition text-sm">
                            แก้ไข
                        </a>
                        <form action="{{ route('admin.fortune.categories.destroy', $category) }}" 
                              method="POST" 
                              onsubmit="return confirm('แน่ใจหรือไม่ที่จะลบหมวดหมู่นี้?');"
                              class="flex-1">
                            @csrf
                            @method('DELETE')
                            <button type="submit" 
                                    class="w-full px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg transition text-sm">
                                ลบ
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-span-full">
                <div class="bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-700 rounded-xl p-8 text-center">
                    <p class="text-yellow-800 dark:text-yellow-200 text-lg">
                        ⚠️ ยังไม่มีหมวดหมู่การทำนาย
                    </p>
                    <p class="text-yellow-600 dark:text-yellow-400 mt-2">
                        กด "เพิ่มหมวดหมู่ใหม่" เพื่อสร้างหมวดหมู่แรก
                    </p>
                </div>
            </div>
        @endforelse
    </div>

    {{-- Pagination --}}
    @if($categories->hasPages())
        <div class="mt-8">
            {{ $categories->links() }}
        </div>
    @endif
</div>
@endsection
