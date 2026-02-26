@extends('layouts.admin-v3')

@section('title', 'จัดการหมวดหมู่ฟอรั่ม')

@section('content')
<div class="space-y-6">
    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-white">📁 จัดการหมวดหมู่ฟอรั่ม</h1>
            <p class="text-white/60 mt-1">จัดการหมวดหมู่และหมวดหมู่ย่อยสำหรับฟอรั่มชุมชน</p>
        </div>
        <a href="{{ route('admin.forum.categories.create') }}"
           class="inline-flex items-center gap-2 px-4 py-2 bg-gradient-to-r from-blue-500 to-purple-500 text-white rounded-lg hover:from-blue-600 hover:to-purple-600 transition shadow-lg">
            <i class="fas fa-plus"></i>
            <span>สร้างหมวดหมู่ใหม่</span>
        </a>
    </div>

    {{-- Categories List --}}
    <div class="glass-card rounded-xl overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-white/5">
                    <tr>
                        <th class="px-6 py-4 text-left text-xs font-medium text-white/60 uppercase tracking-wider">ลำดับ</th>
                        <th class="px-6 py-4 text-left text-xs font-medium text-white/60 uppercase tracking-wider">หมวดหมู่</th>
                        <th class="px-6 py-4 text-left text-xs font-medium text-white/60 uppercase tracking-wider">หมวดหมู่ย่อย</th>
                        <th class="px-6 py-4 text-left text-xs font-medium text-white/60 uppercase tracking-wider">กระทู้</th>
                        <th class="px-6 py-4 text-left text-xs font-medium text-white/60 uppercase tracking-wider">สถานะ</th>
                        <th class="px-6 py-4 text-right text-xs font-medium text-white/60 uppercase tracking-wider">จัดการ</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-white/10">
                    @forelse($categories as $category)
                        <tr class="hover:bg-white/5 transition">
                            <td class="px-6 py-4 text-white/80">{{ $loop->iteration }}</td>
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-3">
                                    <span class="text-2xl">{{ $category->icon ?? '📁' }}</span>
                                    <div>
                                        <div class="font-medium text-white">{{ $category->name }}</div>
                                        @if($category->description)
                                            <div class="text-sm text-white/60">{{ Str::limit($category->description, 50) }}</div>
                                        @endif
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-white/80">{{ $category->children_count }}</td>
                            <td class="px-6 py-4 text-white/80">{{ $category->threads_count }}</td>
                            <td class="px-6 py-4">
                                @if($category->is_locked)
                                    <span class="inline-flex items-center gap-1 px-2 py-1 bg-red-500/20 text-red-400 rounded text-xs">
                                        <i class="fas fa-lock"></i> ล็อค
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 px-2 py-1 bg-green-500/20 text-green-400 rounded text-xs">
                                        <i class="fas fa-unlock"></i> เปิด
                                    </span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <a href="{{ route('admin.forum.categories.edit', $category) }}"
                                       class="p-2 text-blue-400 hover:bg-blue-500/20 rounded-lg transition"
                                       title="แก้ไข">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <form action="{{ route('admin.forum.categories.delete', $category) }}" method="POST" class="inline"
                                          onsubmit="return confirm('ต้องการลบหมวดหมู่นี้?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="p-2 text-red-400 hover:bg-red-500/20 rounded-lg transition" title="ลบ">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>

                        {{-- Subcategories --}}
                        @foreach($category->children as $child)
                            <tr class="hover:bg-white/5 transition bg-white/[0.02]">
                                <td class="px-6 py-3 text-white/60 pl-12">└</td>
                                <td class="px-6 py-3 pl-12">
                                    <div class="flex items-center gap-3">
                                        <span class="text-xl">{{ $child->icon ?? '📄' }}</span>
                                        <div>
                                            <div class="font-medium text-white/80">{{ $child->name }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-3 text-white/60">-</td>
                                <td class="px-6 py-3 text-white/60">{{ $child->threads_count }}</td>
                                <td class="px-6 py-3">
                                    @if($child->is_locked)
                                        <span class="inline-flex items-center gap-1 px-2 py-1 bg-red-500/20 text-red-400 rounded text-xs">
                                            <i class="fas fa-lock text-[10px]"></i>
                                        </span>
                                    @endif
                                </td>
                                <td class="px-6 py-3 text-right">
                                    <div class="flex items-center justify-end gap-2">
                                        <a href="{{ route('admin.forum.categories.edit', $child) }}"
                                           class="p-1.5 text-blue-400 hover:bg-blue-500/20 rounded transition text-sm">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-12 text-center text-white/60">
                                <i class="fas fa-folder-open text-4xl mb-4 block"></i>
                                <p>ยังไม่มีหมวดหมู่</p>
                                <a href="{{ route('admin.forum.categories.create') }}" class="text-blue-400 hover:underline mt-2 inline-block">
                                    + สร้างหมวดหมู่แรก
                                </a>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<style>
.glass-card {
    background: rgba(255, 255, 255, 0.05);
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255, 255, 255, 0.1);
}
</style>
@endsection
