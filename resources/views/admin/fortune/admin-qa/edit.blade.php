@extends('layouts.admin-v3')

@section('title', 'แก้ไข Admin Q&A #' . $qa->id)

@section('content')
<div class="container mx-auto px-4 py-6 max-w-4xl">
    <h1 class="text-2xl font-bold text-gray-900 dark:text-white mb-6">
        📝 แก้ไข Admin Q&A #{{ $qa->id }}
    </h1>

    @if ($errors->any())
        <div class="mb-4 p-4 bg-red-100 dark:bg-red-900 text-red-800 dark:text-red-200 rounded-lg">
            <ul class="list-disc list-inside">
                @foreach ($errors->all() as $err)
                    <li>{{ $err }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('admin.fortune.admin-qa.update', $qa) }}" method="POST" class="space-y-6 bg-white dark:bg-gray-800 rounded-lg shadow p-6">
        @csrf
        @method('PUT')

        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                📝 คำถามลูกค้า (Q)
                <span class="text-xs text-gray-500">— ถ้าแก้ ระบบจะ re-embed อัตโนมัติ</span>
            </label>
            <textarea name="q_text" rows="3" required
                      class="w-full px-3 py-2 border rounded dark:bg-gray-700 dark:text-white dark:border-gray-600">{{ old('q_text', $qa->q_text) }}</textarea>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                💬 คำตอบแอดมิน (A)
            </label>
            <textarea name="a_text" rows="6" required
                      class="w-full px-3 py-2 border rounded dark:bg-gray-700 dark:text-white dark:border-gray-600">{{ old('a_text', $qa->a_text) }}</textarea>
        </div>

        {{-- 🏷 หมวด (category) — override classifier ได้ --}}
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                🏷 หมวด (category)
                <span class="text-xs text-gray-500">— ระบบจัดให้อัตโนมัติจาก state ตอน capture แอดมินแก้ได้ถ้าผิด</span>
            </label>
            <select name="category" class="w-full px-3 py-2 border rounded dark:bg-gray-700 dark:text-white dark:border-gray-600">
                <option value="">— ไม่ระบุ (legacy / ทั่วไป) —</option>
                @foreach ($categoryOptions as $catKey => $catLabel)
                    <option value="{{ $catKey }}" @selected(old('category', $qa->category) === $catKey)>{{ $catLabel }}</option>
                @endforeach
            </select>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <label class="flex items-center gap-2">
                <input type="hidden" name="is_active" value="0">
                <input type="checkbox" name="is_active" value="1"
                       @checked(old('is_active', $qa->is_active))
                       class="rounded text-blue-600">
                <span class="text-sm text-gray-700 dark:text-gray-300">✅ เปิดใช้งาน (allow retrieve)</span>
            </label>

            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                    🎯 Threshold override (เว้นว่าง = ใช้ค่า global)
                </label>
                <input type="number" step="0.01" min="0" max="1" name="similarity_threshold"
                       value="{{ old('similarity_threshold', $qa->similarity_threshold) }}"
                       placeholder="ใช้ค่า global"
                       class="w-full px-3 py-2 border rounded dark:bg-gray-700 dark:text-white dark:border-gray-600">
            </div>
        </div>

        <div class="text-xs text-gray-500 dark:text-gray-400 border-t pt-4 space-y-1">
            <div>Platform: <span class="font-mono">{{ $qa->source_platform }}</span></div>
            <div>Customer: <span class="font-mono">{{ $qa->source_user_id ?? '-' }}</span></div>
            @if ($qa->page_id)
                <div>Page ID: <span class="font-mono">{{ $qa->page_id }}</span></div>
            @endif
            @if ($qa->reading_id)
                <div>Reading: <span class="font-mono">#{{ $qa->reading_id }}</span> ({{ $qa->reading_type ?? '?' }})</div>
            @endif
            <div>Embedding: {{ $qa->q_embedding ? count($qa->q_embedding) . 'd' : 'ว่าง' }}</div>
            <div>Hits: {{ $qa->hit_count }} (last: {{ $qa->last_hit_at?->diffForHumans() ?? 'never' }})</div>
            <div>Created: {{ $qa->created_at }}</div>
            @if (! empty($qa->context_json['prev_turns']))
                <details class="mt-2">
                    <summary class="cursor-pointer text-blue-600 dark:text-blue-400">📜 บริบทย้อนหลัง ({{ count($qa->context_json['prev_turns']) }} turn)</summary>
                    <div class="mt-2 space-y-1 p-2 bg-gray-50 dark:bg-gray-900 rounded text-xs">
                        @foreach ($qa->context_json['prev_turns'] as $turn)
                            <div>
                                <span class="font-semibold {{ $turn['role'] === 'user' ? 'text-blue-600' : 'text-green-600' }}">{{ $turn['role'] }}:</span>
                                <span class="text-gray-700 dark:text-gray-300">{{ $turn['content'] }}</span>
                            </div>
                        @endforeach
                    </div>
                </details>
            @endif
        </div>

        <div class="flex gap-3">
            <button type="submit" class="px-6 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">💾 บันทึก</button>
            <a href="{{ route('admin.fortune.admin-qa.index') }}" class="px-6 py-2 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded hover:bg-gray-300">ยกเลิก</a>
        </div>
    </form>
</div>
@endsection
