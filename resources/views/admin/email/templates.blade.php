@extends('layouts.admin')

@section('title', 'Email Templates')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">📋 Email Templates</h1>
            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">จัดการ Template อีเมลสำหรับส่งอัตโนมัติ</p>
        </div>
        <a href="{{ route('admin.email.templates.create') }}" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
            + สร้าง Template
        </a>
    </div>

    <!-- Templates Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        @forelse($templates as $template)
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6">
                <!-- Header -->
                <div class="flex items-start justify-between mb-4">
                    <div class="flex-1">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                            {{ $template->name }}
                        </h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                            {{ $template->category }} • {{ strtoupper($template->language) }}
                        </p>
                    </div>
                    <div>
                        @if($template->is_active)
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">
                                ✅ ใช้งาน
                            </span>
                        @else
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300">
                                ⭕ ปิด
                            </span>
                        @endif
                    </div>
                </div>

                <!-- Subject Preview -->
                <div class="mb-4">
                    <p class="text-sm font-medium text-gray-700 dark:text-gray-300">เรื่อง:</p>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">{{ Str::limit($template->subject, 60) }}</p>
                </div>

                <!-- Variables -->
                @if($template->variables && count($template->variables) > 0)
                    <div class="mb-4">
                        <p class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Variables:</p>
                        <div class="flex flex-wrap gap-1">
                            @foreach($template->variables as $variable)
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-mono bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200">
                                    {!! '{{' . $variable . '}}' !!}
                                </span>
                            @endforeach
                        </div>
                    </div>
                @endif

                <!-- Description -->
                @if($template->description)
                    <div class="mb-4">
                        <p class="text-sm text-gray-600 dark:text-gray-400">
                            {{ Str::limit($template->description, 100) }}
                        </p>
                    </div>
                @endif

                <!-- Actions -->
                <div class="flex items-center space-x-2 pt-4 border-t border-gray-200 dark:border-gray-700">
                    <a href="{{ route('admin.email.templates.edit', $template) }}" class="flex-1 px-3 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 text-sm text-center">
                        แก้ไข
                    </a>
                    <button onclick="previewTemplate({{ $template->id }})" class="flex-1 px-3 py-2 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded hover:bg-gray-200 dark:hover:bg-gray-600 text-sm">
                        👁️ ดูตัวอย่าง
                    </button>
                </div>
            </div>
        @empty
            <div class="col-span-3 text-center py-12">
                <span class="text-6xl">📭</span>
                <p class="mt-4 text-gray-500 dark:text-gray-400">ยังไม่มี Email Template</p>
                <a href="{{ route('admin.email.templates.create') }}" class="mt-4 inline-block px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                    สร้าง Template แรก
                </a>
            </div>
        @endforelse
    </div>
</div>

<script>
function previewTemplate(templateId) {
    // TODO: Implement template preview modal
    alert('Template Preview - Coming Soon!');
}
</script>
@endsection
