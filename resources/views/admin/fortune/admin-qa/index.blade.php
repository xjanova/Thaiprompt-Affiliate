@extends('layouts.admin-v3')

@section('title', 'RAG Admin Q&A')

@section('content')
<div class="container mx-auto px-4 py-6 max-w-7xl">
    {{-- Header --}}
    <div class="mb-6">
        <h1 class="text-3xl font-bold text-gray-900 dark:text-white flex items-center gap-3">
            <span>📚</span> RAG Admin Q&A
        </h1>
        <p class="text-gray-600 dark:text-gray-400 mt-2">
            จับคู่ "คำถามลูกค้า ↔ คำตอบแอดมิน" จาก FB Page Inbox → AI เรียนสไตล์ตอบของแอดมินไว้ใช้ตอบลูกค้าเมื่อบริบทคล้ายกัน
        </p>
    </div>

    {{-- Flash messages --}}
    @if (session('success'))
        <div class="mb-4 p-4 bg-green-100 dark:bg-green-900 text-green-800 dark:text-green-200 rounded-lg">
            ✅ {{ session('success') }}
        </div>
    @endif
    @if (session('error'))
        <div class="mb-4 p-4 bg-red-100 dark:bg-red-900 text-red-800 dark:text-red-200 rounded-lg">
            ❌ {{ session('error') }}
        </div>
    @endif

    {{-- Stats --}}
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
        <div class="p-4 bg-white dark:bg-gray-800 rounded-lg shadow">
            <div class="text-xs text-gray-500 dark:text-gray-400">ทั้งหมด</div>
            <div class="text-2xl font-bold text-gray-900 dark:text-white">{{ number_format($stats['total']) }}</div>
        </div>
        <div class="p-4 bg-white dark:bg-gray-800 rounded-lg shadow">
            <div class="text-xs text-gray-500 dark:text-gray-400">เปิดใช้งาน</div>
            <div class="text-2xl font-bold text-green-600 dark:text-green-400">{{ number_format($stats['active']) }}</div>
        </div>
        <div class="p-4 bg-white dark:bg-gray-800 rounded-lg shadow">
            <div class="text-xs text-gray-500 dark:text-gray-400">มี embedding</div>
            <div class="text-2xl font-bold text-blue-600 dark:text-blue-400">{{ number_format($stats['with_embedding']) }}</div>
        </div>
        <div class="p-4 bg-white dark:bg-gray-800 rounded-lg shadow">
            <div class="text-xs text-gray-500 dark:text-gray-400">ถูก retrieve รวม</div>
            <div class="text-2xl font-bold text-purple-600 dark:text-purple-400">{{ number_format($stats['total_hits']) }}</div>
        </div>
    </div>

    {{-- 🏷 Category breakdown — กระจายตามหมวด --}}
    @if (! empty($stats['category_breakdown']))
        <details class="mb-6 p-4 bg-white dark:bg-gray-800 rounded-lg shadow">
            <summary class="cursor-pointer font-semibold text-gray-900 dark:text-white">🏷 กระจายตามหมวด ({{ count($stats['category_breakdown']) }} หมวด)</summary>
            <div class="mt-4 grid grid-cols-2 md:grid-cols-4 gap-3">
                @foreach ($categoryOptions as $catKey => $catLabel)
                    @php($catCount = $stats['category_breakdown'][$catKey] ?? 0)
                    <a href="{{ route('admin.fortune.admin-qa.index', ['category' => $catKey]) }}"
                       class="block p-3 bg-gray-50 dark:bg-gray-700 rounded hover:bg-blue-50 dark:hover:bg-blue-900 transition">
                        <div class="text-xs text-gray-500 dark:text-gray-400">{{ $catLabel }}</div>
                        <div class="text-lg font-bold text-gray-900 dark:text-white">{{ number_format($catCount) }}</div>
                    </a>
                @endforeach
                @if (isset($stats['category_breakdown']['__null__']))
                    <a href="{{ route('admin.fortune.admin-qa.index', ['category' => '__null__']) }}"
                       class="block p-3 bg-yellow-50 dark:bg-yellow-900/30 rounded hover:bg-yellow-100 dark:hover:bg-yellow-900 transition">
                        <div class="text-xs text-yellow-700 dark:text-yellow-300">⚠️ ยังไม่จัดหมวด (legacy)</div>
                        <div class="text-lg font-bold text-yellow-900 dark:text-yellow-100">{{ number_format($stats['category_breakdown']['__null__']) }}</div>
                    </a>
                @endif
            </div>
        </details>
    @endif

    {{-- Settings panel --}}
    <details class="mb-6 p-4 bg-white dark:bg-gray-800 rounded-lg shadow">
        <summary class="cursor-pointer font-semibold text-gray-900 dark:text-white">⚙️ ตั้งค่าระบบ RAG</summary>
        <form action="{{ route('admin.fortune.admin-qa.settings') }}" method="POST" class="mt-4 space-y-4">
            @csrf
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <label class="flex items-center gap-3">
                    <input type="hidden" name="admin_qa_capture_enabled" value="0">
                    <input type="checkbox" name="admin_qa_capture_enabled" value="1"
                           @checked($settings->admin_qa_capture_enabled ?? true)
                           class="rounded text-blue-600">
                    <div>
                        <div class="font-medium text-gray-900 dark:text-white">📥 เปิดการเก็บข้อมูล (Capture)</div>
                        <div class="text-xs text-gray-500 dark:text-gray-400">เก็บคู่ Q&A อัตโนมัติเมื่อแอดมินตอบใน FB Page Inbox</div>
                    </div>
                </label>

                <label class="flex items-center gap-3">
                    <input type="hidden" name="admin_qa_rag_enabled" value="0">
                    <input type="checkbox" name="admin_qa_rag_enabled" value="1"
                           @checked($settings->admin_qa_rag_enabled ?? true)
                           class="rounded text-blue-600">
                    <div>
                        <div class="font-medium text-gray-900 dark:text-white">🤖 เปิดการใช้งาน (RAG inject)</div>
                        <div class="text-xs text-gray-500 dark:text-gray-400">AI ใช้ Q&A คล้ายเป็น few-shot ตอนตอบลูกค้า</div>
                    </div>
                </label>

                <label class="block">
                    <div class="font-medium text-gray-900 dark:text-white mb-1">🎯 Threshold (0.0-1.0)</div>
                    <input type="number" step="0.01" min="0" max="1" name="admin_qa_rag_threshold"
                           value="{{ $settings->admin_qa_rag_threshold ?? 0.78 }}"
                           class="w-full px-3 py-2 border rounded dark:bg-gray-700 dark:text-white dark:border-gray-600">
                    <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">cosine similarity ≥ ค่านี้ ถึงจะถูกหยิบมาใช้ (แนะนำ 0.78)</div>
                </label>

                <label class="block">
                    <div class="font-medium text-gray-900 dark:text-white mb-1">🔢 Top-K examples</div>
                    <input type="number" min="1" max="10" name="admin_qa_rag_top_k"
                           value="{{ $settings->admin_qa_rag_top_k ?? 3 }}"
                           class="w-full px-3 py-2 border rounded dark:bg-gray-700 dark:text-white dark:border-gray-600">
                    <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">จำนวนตัวอย่างที่ inject ใน prompt (แนะนำ 3)</div>
                </label>
            </div>
            <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">💾 บันทึก</button>
        </form>
    </details>

    {{-- Search/Filter --}}
    <form method="GET" class="mb-4 grid grid-cols-1 md:grid-cols-4 gap-3">
        <input type="text" name="search" value="{{ $search }}" placeholder="🔍 ค้น Q หรือ A..."
               class="md:col-span-2 px-3 py-2 border rounded dark:bg-gray-700 dark:text-white dark:border-gray-600">
        <select name="category" class="px-3 py-2 border rounded dark:bg-gray-700 dark:text-white dark:border-gray-600">
            <option value="">— ทุกหมวด —</option>
            @foreach ($categoryOptions as $catKey => $catLabel)
                <option value="{{ $catKey }}" @selected($categoryFilter === $catKey)>{{ $catLabel }}</option>
            @endforeach
            <option value="__null__" @selected($categoryFilter === '__null__')>⚠️ ยังไม่จัดหมวด</option>
        </select>
        <select name="platform" class="px-3 py-2 border rounded dark:bg-gray-700 dark:text-white dark:border-gray-600">
            <option value="">— ทุก platform —</option>
            <option value="facebook" @selected($platform === 'facebook')>Facebook</option>
            <option value="line" @selected($platform === 'line')>LINE</option>
            <option value="manual" @selected($platform === 'manual')>Manual</option>
        </select>
        <select name="active" class="px-3 py-2 border rounded dark:bg-gray-700 dark:text-white dark:border-gray-600">
            <option value="">— สถานะ —</option>
            <option value="1" @selected($activeFilter === '1')>✅ เปิด</option>
            <option value="0" @selected($activeFilter === '0')>⏸ ปิด</option>
        </select>
        <div class="md:col-span-2 flex gap-2">
            <button type="submit" class="flex-1 px-4 py-2 bg-gray-700 dark:bg-gray-600 text-white rounded hover:bg-gray-800">ค้นหา</button>
            <a href="{{ route('admin.fortune.admin-qa.index') }}" class="px-4 py-2 bg-gray-300 dark:bg-gray-700 text-gray-700 dark:text-gray-200 rounded hover:bg-gray-400 dark:hover:bg-gray-600">ล้าง</a>
        </div>
    </form>

    {{-- Table --}}
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-700">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">#</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Q / A</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">หมวด</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">แหล่ง</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Embed</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Hits</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">สถานะ</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">จัดการ</th>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse ($rows as $qa)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30 transition">
                            <td class="px-4 py-4 text-sm text-gray-900 dark:text-gray-100 align-top">{{ $qa->id }}</td>
                            <td class="px-4 py-4 text-sm align-top">
                                <div class="mb-2">
                                    <div class="text-xs text-blue-600 dark:text-blue-400 font-semibold">Q ({{ Str::length($qa->q_text) }} chars)</div>
                                    <div class="text-gray-900 dark:text-gray-100">{{ Str::limit($qa->q_text, 120) }}</div>
                                </div>
                                <div>
                                    <div class="text-xs text-green-600 dark:text-green-400 font-semibold">A ({{ Str::length($qa->a_text) }} chars)</div>
                                    <div class="text-gray-700 dark:text-gray-300">{{ Str::limit($qa->a_text, 150) }}</div>
                                </div>
                            </td>
                            <td class="px-4 py-4 text-sm align-top">
                                @if ($qa->category)
                                    <span class="inline-flex px-2 py-1 text-xs rounded-full
                                        @switch($qa->category)
                                            @case('pre_purchase') bg-blue-100 dark:bg-blue-900 text-blue-800 dark:text-blue-200 @break
                                            @case('birthdate_help') bg-indigo-100 dark:bg-indigo-900 text-indigo-800 dark:text-indigo-200 @break
                                            @case('question_input') bg-violet-100 dark:bg-violet-900 text-violet-800 dark:text-violet-200 @break
                                            @case('pre_payment') bg-yellow-100 dark:bg-yellow-900 text-yellow-800 dark:text-yellow-200 @break
                                            @case('payment_confirm') bg-green-100 dark:bg-green-900 text-green-800 dark:text-green-200 @break
                                            @case('post_reading') bg-purple-100 dark:bg-purple-900 text-purple-800 dark:text-purple-200 @break
                                            @case('celtic_picking') bg-pink-100 dark:bg-pink-900 text-pink-800 dark:text-pink-200 @break
                                            @default bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-200
                                        @endswitch
                                    ">{{ $qa->categoryLabel() }}</span>
                                @else
                                    <span class="inline-flex px-2 py-1 text-xs rounded-full bg-orange-100 dark:bg-orange-900/40 text-orange-800 dark:text-orange-300">⚠️ ไม่ระบุ</span>
                                @endif
                                @if ($qa->reading_type)
                                    <div class="text-xs text-gray-500 mt-1">📦 {{ $qa->reading_type }}</div>
                                @endif
                            </td>
                            <td class="px-4 py-4 text-sm text-gray-700 dark:text-gray-300 align-top">
                                <div>{{ $qa->source_platform }}</div>
                                @if ($qa->source_user_id)
                                    <div class="text-xs text-gray-500 font-mono">{{ Str::limit($qa->source_user_id, 12) }}</div>
                                @endif
                                <div class="text-xs text-gray-500">{{ $qa->created_at?->diffForHumans() }}</div>
                            </td>
                            <td class="px-4 py-4 text-sm align-top">
                                @if ($qa->q_embedding)
                                    <span class="text-green-600 dark:text-green-400">✅ {{ count($qa->q_embedding) }}d</span>
                                @else
                                    <form action="{{ route('admin.fortune.admin-qa.reembed', $qa) }}" method="POST" class="inline">
                                        @csrf
                                        <button type="submit" class="text-orange-600 dark:text-orange-400 hover:underline">⚠️ ว่าง — กด re-embed</button>
                                    </form>
                                @endif
                            </td>
                            <td class="px-4 py-4 text-sm text-gray-900 dark:text-gray-100 align-top">{{ number_format($qa->hit_count) }}</td>
                            <td class="px-4 py-4 text-sm align-top">
                                <form action="{{ route('admin.fortune.admin-qa.toggle', $qa) }}" method="POST" class="inline">
                                    @csrf
                                    @if ($qa->is_active)
                                        <button type="submit" class="px-2 py-1 bg-green-100 dark:bg-green-900 text-green-800 dark:text-green-200 rounded text-xs hover:opacity-80">✅ เปิด</button>
                                    @else
                                        <button type="submit" class="px-2 py-1 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded text-xs hover:opacity-80">⏸ ปิด</button>
                                    @endif
                                </form>
                            </td>
                            <td class="px-4 py-4 text-sm text-right align-top">
                                <a href="{{ route('admin.fortune.admin-qa.edit', $qa) }}" class="text-blue-600 dark:text-blue-400 hover:underline">แก้ไข</a>
                                <form action="{{ route('admin.fortune.admin-qa.destroy', $qa) }}" method="POST" class="inline ml-2" onsubmit="return confirm('ลบ Q&A นี้ใช่ไหม?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-red-600 dark:text-red-400 hover:underline">ลบ</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-4 py-12 text-center text-gray-500 dark:text-gray-400">
                                ยังไม่มีข้อมูล — แอดมินตอบลูกค้าใน FB Page Inbox แล้วระบบจะเก็บอัตโนมัติ
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-4">{{ $rows->links() }}</div>
</div>
@endsection
