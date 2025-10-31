@extends('layouts.admin')

@section('title', 'รายละเอียดเทมเพลต')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between">
        <div class="flex items-center gap-4">
            <a href="{{ route('admin.notification-templates.index') }}" class="text-gray-600 hover:text-gray-900">
                ← กลับ
            </a>
            <div>
                <h1 class="text-2xl font-bold text-gray-900">{{ $template->name }}</h1>
                <p class="text-sm text-gray-600 mt-1">{{ $template->description ?? 'ไม่มีคำอธิบาย' }}</p>
            </div>
        </div>
        <div class="flex gap-3">
            <a href="{{ route('admin.notification-templates.edit', $template->id) }}"
               class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition">
                แก้ไข
            </a>
            <form action="{{ route('admin.notification-templates.destroy', $template->id) }}" method="POST"
                  onsubmit="return confirm('ต้องการลบเทมเพลตนี้ใช่หรือไม่?')">
                @csrf
                @method('DELETE')
                <button type="submit"
                        class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition">
                    ลบ
                </button>
            </form>
        </div>
    </div>

    <!-- Preview Card -->
    <div class="bg-white rounded-xl shadow-lg overflow-hidden">
        <div class="p-6 {{ $template->is_active ? 'bg-gradient-to-r from-indigo-600 to-purple-600' : 'bg-gray-400' }} text-white">
            <div class="flex items-start justify-between">
                <div class="flex items-center gap-3">
                    <span class="text-4xl">{{ $template->icon ?? '📬' }}</span>
                    <div>
                        <h2 class="text-2xl font-bold">{{ $template->title }}</h2>
                        <p class="text-sm opacity-90 mt-1">{{ $template->type_label }}</p>
                    </div>
                </div>
                <div class="flex flex-col items-end gap-2">
                    @if($template->is_active)
                        <span class="px-3 py-1 bg-green-500 rounded-full text-sm">✓ ใช้งาน</span>
                    @else
                        <span class="px-3 py-1 bg-white/20 rounded-full text-sm">ปิดใช้งาน</span>
                    @endif
                    <span class="text-sm opacity-75">ใช้งาน {{ $template->usage_count }} ครั้ง</span>
                </div>
            </div>
        </div>

        <div class="p-6">
            <div class="prose max-w-none">
                <p class="text-gray-700 text-lg leading-relaxed">{{ $template->message }}</p>
            </div>

            @if($template->action_url)
                <div class="mt-4">
                    <a href="#" class="inline-block px-6 py-3 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition">
                        {{ $template->action_text ?? 'ดำเนินการ' }}
                    </a>
                </div>
            @endif
        </div>
    </div>

    <!-- Details Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <!-- Template Information -->
        <div class="bg-white rounded-xl shadow-md p-6">
            <h3 class="text-lg font-bold text-gray-900 mb-4">ข้อมูลเทมเพลต</h3>
            <dl class="space-y-3">
                <div>
                    <dt class="text-sm font-medium text-gray-500">ประเภท</dt>
                    <dd class="text-sm text-gray-900 mt-1">{{ $template->type_label }}</dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-gray-500">ระดับความสำคัญ</dt>
                    <dd class="text-sm text-gray-900 mt-1">
                        <span class="px-2 py-1 bg-{{ $template->color ?? 'gray' }}-100 text-{{ $template->color ?? 'gray' }}-700 rounded-full">
                            {{ $template->priority_label }}
                        </span>
                    </dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-gray-500">สี</dt>
                    <dd class="text-sm text-gray-900 mt-1">
                        <span class="inline-flex items-center gap-2">
                            <span class="w-4 h-4 rounded-full bg-{{ $template->color ?? 'gray' }}-500"></span>
                            {{ ucfirst($template->color ?? 'ไม่ระบุ') }}
                        </span>
                    </dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-gray-500">Slug</dt>
                    <dd class="text-sm text-gray-900 mt-1 font-mono bg-gray-100 px-2 py-1 rounded">{{ $template->slug }}</dd>
                </div>
            </dl>
        </div>

        <!-- Settings & Options -->
        <div class="bg-white rounded-xl shadow-md p-6">
            <h3 class="text-lg font-bold text-gray-900 mb-4">การตั้งค่า</h3>
            <div class="space-y-3">
                <div class="flex items-center justify-between">
                    <span class="text-sm text-gray-700">สำคัญ</span>
                    @if($template->is_important)
                        <span class="px-2 py-1 bg-red-100 text-red-700 rounded text-xs">⭐ สำคัญ</span>
                    @else
                        <span class="text-gray-400 text-sm">-</span>
                    @endif
                </div>
                <div class="flex items-center justify-between">
                    <span class="text-sm text-gray-700">แสดงทันที</span>
                    @if($template->show_immediately)
                        <span class="px-2 py-1 bg-yellow-100 text-yellow-700 rounded text-xs">⚡ ทันที</span>
                    @else
                        <span class="text-gray-400 text-sm">-</span>
                    @endif
                </div>
                <div class="flex items-center justify-between">
                    <span class="text-sm text-gray-700">สถานะ</span>
                    @if($template->is_active)
                        <span class="px-2 py-1 bg-green-100 text-green-700 rounded text-xs">✓ ใช้งาน</span>
                    @else
                        <span class="px-2 py-1 bg-gray-100 text-gray-700 rounded text-xs">🚫 ปิดใช้งาน</span>
                    @endif
                </div>
                <div class="flex items-center justify-between">
                    <span class="text-sm text-gray-700">URL ดำเนินการ</span>
                    @if($template->action_url)
                        <code class="text-xs bg-gray-100 px-2 py-1 rounded">{{ $template->action_url }}</code>
                    @else
                        <span class="text-gray-400 text-sm">-</span>
                    @endif
                </div>
                <div class="flex items-center justify-between">
                    <span class="text-sm text-gray-700">ข้อความปุ่ม</span>
                    @if($template->action_text)
                        <span class="text-sm text-gray-900">{{ $template->action_text }}</span>
                    @else
                        <span class="text-gray-400 text-sm">-</span>
                    @endif
                </div>
            </div>
        </div>

        <!-- Statistics -->
        <div class="bg-white rounded-xl shadow-md p-6">
            <h3 class="text-lg font-bold text-gray-900 mb-4">สถิติ</h3>
            <div class="space-y-4">
                <div class="bg-blue-50 rounded-lg p-4">
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-blue-700">จำนวนครั้งที่ใช้</span>
                        <span class="text-2xl font-bold text-blue-700">{{ $template->usage_count }}</span>
                    </div>
                </div>
                <div class="text-sm text-gray-600 space-y-1">
                    <p>🕐 สร้างเมื่อ: {{ $template->created_at->format('d/m/Y H:i') }}</p>
                    <p>📝 แก้ไขล่าสุด: {{ $template->updated_at->format('d/m/Y H:i') }}</p>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="bg-white rounded-xl shadow-md p-6">
            <h3 class="text-lg font-bold text-gray-900 mb-4">การดำเนินการ</h3>
            <div class="space-y-3">
                <a href="{{ route('admin.notifications.create') }}?template={{ $template->id }}"
                   class="block w-full px-4 py-3 bg-indigo-600 text-white text-center rounded-lg hover:bg-indigo-700 transition">
                    📨 ใช้เทมเพลตนี้ส่งการแจ้งเตือน
                </a>
                <form action="{{ route('admin.notification-templates.toggle', $template->id) }}" method="POST">
                    @csrf
                    <button type="submit"
                            class="block w-full px-4 py-3 {{ $template->is_active ? 'bg-gray-600 hover:bg-gray-700' : 'bg-green-600 hover:bg-green-700' }} text-white text-center rounded-lg transition">
                        {{ $template->is_active ? '🚫 ปิดใช้งาน' : '✓ เปิดใช้งาน' }}
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
