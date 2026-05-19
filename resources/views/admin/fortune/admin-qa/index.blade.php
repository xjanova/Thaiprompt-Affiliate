@extends('layouts.admin')

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
        <button type="submit" class="md:col-span-4 px-4 py-2 bg-gray-700 dark:bg-gray-600 text-white rounded hover:bg-gray-800">ค้นหา</button>
    </form>

    {{-- Table --}}
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-700">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">#</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Q / A</th>
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
                            <td colspan="7" class="px-4 py-12 text-center text-gray-500 dark:text-gray-400">
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
