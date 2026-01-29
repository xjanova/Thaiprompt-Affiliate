@extends('layouts.admin')

@section('title', 'เทมเพลตตอบกลับคำทำนาย')

@section('content')
<div class="container mx-auto px-4 py-8">
    {{-- Header --}}
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-8 gap-4">
        <div>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">
                📝 เทมเพลตตอบกลับคำทำนาย
            </h1>
            <p class="text-gray-600 dark:text-gray-400 mt-1">
                จัดการเทมเพลตข้อความที่ใช้ตอบกลับผ่าน Facebook Messenger
            </p>
        </div>
        <a href="{{ route('admin.fortune.response-templates.create') }}"
           class="px-6 py-3 bg-gradient-to-r from-purple-600 to-indigo-600 hover:from-purple-700 hover:to-indigo-700 text-white rounded-lg shadow-lg transition font-medium">
            + สร้างเทมเพลตใหม่
        </a>
    </div>

    {{-- Stats --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-8">
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-5">
            <div class="text-sm text-gray-500 dark:text-gray-400">เทมเพลตทั้งหมด</div>
            <div class="text-3xl font-bold text-gray-900 dark:text-white mt-1">{{ $stats['total'] }}</div>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-5">
            <div class="text-sm text-gray-500 dark:text-gray-400">เปิดใช้งาน</div>
            <div class="text-3xl font-bold text-green-600 dark:text-green-400 mt-1">{{ $stats['active'] }}</div>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-5">
            <div class="text-sm text-gray-500 dark:text-gray-400">จำนวนประเภท</div>
            <div class="text-3xl font-bold text-indigo-600 dark:text-indigo-400 mt-1">{{ $stats['types'] }}</div>
        </div>
    </div>

    {{-- Alert --}}
    @if(session('success'))
        <div class="bg-green-100 dark:bg-green-900 text-green-800 dark:text-green-200 px-6 py-4 rounded-lg mb-6">
            {{ session('success') }}
        </div>
    @endif

    {{-- เทมเพลตจัดกลุ่มตามประเภท --}}
    @forelse($groupedTemplates as $type => $typeTemplates)
        <div class="mb-8">
            <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                <span>{{ $typeTemplates->first()->getTypeIcon() }}</span>
                <span>{{ $typeTemplates->first()->getTypeLabel() }}</span>
                <span class="text-sm font-normal text-gray-500 dark:text-gray-400">({{ $typeTemplates->count() }})</span>
            </h2>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                @foreach($typeTemplates as $template)
                    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg overflow-hidden border-l-4 {{ $template->is_default ? 'border-indigo-500' : 'border-gray-300 dark:border-gray-600' }}">
                        <div class="p-5">
                            {{-- Header --}}
                            <div class="flex items-start justify-between mb-3">
                                <div>
                                    <h3 class="font-bold text-gray-900 dark:text-white text-lg">
                                        {{ $template->name }}
                                    </h3>
                                    <div class="flex items-center gap-2 mt-1">
                                        <code class="text-xs bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 px-2 py-0.5 rounded">{{ $template->slug }}</code>
                                        @if($template->is_default)
                                            <span class="px-2 py-0.5 text-xs font-semibold rounded-full bg-indigo-100 text-indigo-800 dark:bg-indigo-900 dark:text-indigo-200">
                                                เริ่มต้น
                                            </span>
                                        @endif
                                        @if($template->is_active)
                                            <span class="px-2 py-0.5 text-xs font-semibold rounded-full bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">
                                                เปิดใช้
                                            </span>
                                        @else
                                            <span class="px-2 py-0.5 text-xs font-semibold rounded-full bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200">
                                                ปิดใช้
                                            </span>
                                        @endif
                                    </div>
                                </div>
                            </div>

                            {{-- รูปภาพ --}}
                            @if($template->hasHeaderImage() || $template->hasFooterImage())
                                <div class="flex gap-2 mb-3">
                                    @if($template->hasHeaderImage())
                                        <span class="text-xs bg-blue-50 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300 px-2 py-1 rounded">
                                            🖼️ รูปส่วนหัว
                                        </span>
                                    @endif
                                    @if($template->hasFooterImage())
                                        <span class="text-xs bg-amber-50 dark:bg-amber-900/30 text-amber-700 dark:text-amber-300 px-2 py-1 rounded">
                                            🖼️ รูปส่วนท้าย (QR/บัญชี)
                                        </span>
                                    @endif
                                </div>
                            @endif

                            {{-- ตัวอย่างเนื้อหา --}}
                            <div class="bg-gray-50 dark:bg-gray-900 rounded-lg p-3 mb-3 max-h-40 overflow-y-auto">
                                <pre class="text-xs text-gray-700 dark:text-gray-300 whitespace-pre-wrap font-mono">{{ Str::limit($template->body, 300) }}</pre>
                            </div>

                            {{-- Placeholders --}}
                            @if(!empty($template->available_placeholders))
                                <div class="flex flex-wrap gap-1 mb-3">
                                    @foreach($template->available_placeholders as $placeholder)
                                        <code class="text-xs bg-purple-50 dark:bg-purple-900/30 text-purple-700 dark:text-purple-300 px-1.5 py-0.5 rounded">{{ $placeholder }}</code>
                                    @endforeach
                                </div>
                            @endif

                            {{-- Actions --}}
                            <div class="flex items-center gap-2 pt-3 border-t border-gray-200 dark:border-gray-700">
                                <a href="{{ route('admin.fortune.response-templates.edit', $template) }}"
                                   class="px-3 py-1.5 text-sm bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition">
                                    แก้ไข
                                </a>
                                @if(!$template->is_default)
                                    <form method="POST" action="{{ route('admin.fortune.response-templates.set-default', $template) }}">
                                        @csrf
                                        <button type="submit" class="px-3 py-1.5 text-sm bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg transition">
                                            ตั้งเป็นเริ่มต้น
                                        </button>
                                    </form>
                                @endif
                                <form method="POST" action="{{ route('admin.fortune.response-templates.destroy', $template) }}"
                                      onsubmit="return confirm('ยืนยันลบเทมเพลต &quot;{{ $template->name }}&quot;?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="px-3 py-1.5 text-sm bg-red-600 hover:bg-red-700 text-white rounded-lg transition">
                                        ลบ
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @empty
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-12 text-center">
            <div class="text-6xl mb-4">📝</div>
            <p class="text-gray-500 dark:text-gray-400 text-lg">ยังไม่มีเทมเพลต</p>
            <a href="{{ route('admin.fortune.response-templates.create') }}"
               class="inline-block mt-4 px-6 py-3 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg transition">
                สร้างเทมเพลตแรก
            </a>
        </div>
    @endforelse
</div>
@endsection
