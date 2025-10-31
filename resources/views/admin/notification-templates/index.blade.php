@extends('layouts.admin')

@section('title', 'จัดการเทมเพลตการแจ้งเตือน')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">📋 จัดการเทมเพลตการแจ้งเตือน</h1>
            <p class="text-sm text-gray-600 mt-1">สร้างและจัดการเทมเพลตสำหรับการแจ้งเตือน</p>
        </div>
        <a href="{{ route('admin.notification-templates.create') }}"
           class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 shadow-md hover:shadow-lg transition-all">
            + สร้างเทมเพลตใหม่
        </a>
    </div>

    @if(session('success'))
        <div class="bg-green-50 border border-green-200 text-green-800 rounded-lg p-4">
            {{ session('success') }}
        </div>
    @endif

    <!-- Templates Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        @forelse($templates as $template)
            <div class="bg-white rounded-xl shadow-md hover:shadow-lg transition-all overflow-hidden border-2 {{ $template->is_active ? 'border-transparent' : 'border-gray-300' }}">
                <!-- Header -->
                <div class="p-4 {{ $template->is_active ? 'bg-gradient-to-r from-indigo-600 to-purple-600' : 'bg-gray-400' }} text-white">
                    <div class="flex items-start justify-between">
                        <div class="flex-1">
                            <div class="flex items-center gap-2 mb-1">
                                <span class="text-2xl">{{ $template->icon ?? '📬' }}</span>
                                <h3 class="font-bold text-lg">{{ $template->name }}</h3>
                            </div>
                            <p class="text-xs opacity-90">{{ $template->type_label }}</p>
                        </div>
                        <div class="flex flex-col items-end gap-1">
                            @if(!$template->is_active)
                                <span class="px-2 py-1 bg-white/20 rounded text-xs">ปิดใช้งาน</span>
                            @endif
                            <span class="text-xs opacity-75">ใช้งาน {{ $template->usage_count }} ครั้ง</span>
                        </div>
                    </div>
                </div>

                <!-- Content -->
                <div class="p-4">
                    <h4 class="font-semibold text-gray-900 mb-2">{{ $template->title }}</h4>
                    <p class="text-sm text-gray-600 mb-3 line-clamp-2">{{ $template->message }}</p>

                    @if($template->description)
                        <p class="text-xs text-gray-500 italic mb-3">{{ $template->description }}</p>
                    @endif

                    <div class="flex flex-wrap gap-2 mb-4">
                        <span class="px-2 py-1 bg-{{ $template->color ?? 'gray' }}-100 text-{{ $template->color ?? 'gray' }}-700 rounded-full text-xs">
                            {{ $template->priority_label }}
                        </span>
                        @if($template->is_important)
                            <span class="px-2 py-1 bg-red-100 text-red-700 rounded-full text-xs">⭐ สำคัญ</span>
                        @endif
                        @if($template->show_immediately)
                            <span class="px-2 py-1 bg-yellow-100 text-yellow-700 rounded-full text-xs">⚡ แสดงทันที</span>
                        @endif
                    </div>

                    <!-- Actions -->
                    <div class="flex items-center gap-2 pt-3 border-t border-gray-200">
                        <a href="{{ route('admin.notification-templates.edit', $template->id) }}"
                           class="flex-1 px-3 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition text-center text-sm">
                            แก้ไข
                        </a>

                        <form action="{{ route('admin.notification-templates.toggle', $template->id) }}" method="POST" class="inline">
                            @csrf
                            <button type="submit"
                                    class="px-3 py-2 {{ $template->is_active ? 'bg-gray-600 hover:bg-gray-700' : 'bg-green-600 hover:bg-green-700' }} text-white rounded-lg transition text-sm">
                                {{ $template->is_active ? '🚫' : '✓' }}
                            </button>
                        </form>

                        <form action="{{ route('admin.notification-templates.destroy', $template->id) }}" method="POST" class="inline"
                              onsubmit="return confirm('ต้องการลบเทมเพลตนี้ใช่หรือไม่?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit"
                                    class="px-3 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition text-sm">
                                🗑️
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-span-full">
                <div class="bg-white rounded-xl shadow-md p-12 text-center">
                    <div class="text-6xl mb-4">📋</div>
                    <h3 class="text-xl font-bold text-gray-800 mb-2">ยังไม่มีเทมเพลต</h3>
                    <p class="text-gray-600 mb-6">สร้างเทมเพลตเพื่อใช้งานซ้ำได้อย่างสะดวก</p>
                    <a href="{{ route('admin.notification-templates.create') }}"
                       class="inline-block px-6 py-3 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition">
                        + สร้างเทมเพลตแรก
                    </a>
                </div>
            </div>
        @endforelse
    </div>

    <!-- Pagination -->
    @if($templates->hasPages())
        <div class="mt-6">
            {{ $templates->links() }}
        </div>
    @endif
</div>
@endsection
