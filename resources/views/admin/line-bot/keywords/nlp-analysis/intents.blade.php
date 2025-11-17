@extends('layouts.admin-v3')

@section('title', 'Intents')

@section('content')
<div class="container mx-auto px-4 py-8">
    {{-- Page Header --}}
    <div class="mb-8 flex justify-between items-center">
        <div>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">🎯 Message Intents</h1>
            <p class="text-gray-600 dark:text-gray-400">{{ number_format($intents->total()) }} intents พบทั้งหมด</p>
        </div>
        <a href="{{ route('admin.line-bot.keywords.nlp-analysis.index') }}"
           class="px-4 py-2 bg-gray-600 hover:bg-gray-700 dark:bg-gray-700 dark:hover:bg-gray-600 text-white rounded transition">
            ← กลับ
        </a>
    </div>

    {{-- Filter Form --}}
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6 mb-8">
        <form method="GET" class="grid grid-cols-1 md:grid-cols-5 gap-4">
            {{-- Intent Type --}}
            <div>
                <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">Intent Type</label>
                <select name="intent" class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded dark:bg-gray-700 dark:text-white">
                    <option value="">ทั้งหมด</option>
                    @foreach($intentTypes as $type)
                        <option value="{{ $type }}" {{ request('intent') === $type ? 'selected' : '' }}>
                            {{ $type }}
                        </option>
                    @endforeach
                </select>
            </div>

            {{-- Department --}}
            <div>
                <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">Department</label>
                <select name="department" class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded dark:bg-gray-700 dark:text-white">
                    <option value="">ทั้งหมด</option>
                    @foreach($departments as $dept)
                        <option value="{{ $dept }}" {{ request('department') === $dept ? 'selected' : '' }}>
                            {{ $dept }}
                        </option>
                    @endforeach
                </select>
            </div>

            {{-- Priority --}}
            <div>
                <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">Priority</label>
                <select name="priority" class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded dark:bg-gray-700 dark:text-white">
                    <option value="">ทั้งหมด</option>
                    <option value="LOW" {{ request('priority') === 'LOW' ? 'selected' : '' }}>ต่ำ</option>
                    <option value="NORMAL" {{ request('priority') === 'NORMAL' ? 'selected' : '' }}>ปกติ</option>
                    <option value="HIGH" {{ request('priority') === 'HIGH' ? 'selected' : '' }}>สูง</option>
                    <option value="URGENT" {{ request('priority') === 'URGENT' ? 'selected' : '' }}>เร่งด่วน</option>
                </select>
            </div>

            {{-- Urgent Only --}}
            <div class="flex items-end">
                <label class="flex items-center text-sm">
                    <input type="checkbox" name="urgent" value="1" {{ request('urgent') ? 'checked' : '' }}
                           class="rounded border-gray-300 dark:border-gray-600 dark:bg-gray-700">
                    <span class="ml-2 text-gray-700 dark:text-gray-300">เฉพาะเร่งด่วน</span>
                </label>
            </div>

            {{-- Submit --}}
            <div class="flex items-end">
                <button type="submit" class="w-full px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded transition">
                    🔍 ค้นหา
                </button>
            </div>
        </form>
    </div>

    {{-- Intents Table --}}
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-700">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Primary Intent</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Department</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Priority</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Confidence</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Secondary</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Created</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">จัดการ</th>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse($intents as $intent)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700 transition">
                            <td class="px-6 py-4 text-sm font-semibold text-gray-900 dark:text-white">
                                {{ $intent->primary_intent }}
                            </td>
                            <td class="px-6 py-4 text-sm">
                                <span class="px-2 py-1 bg-blue-100 dark:bg-blue-900 text-blue-900 dark:text-blue-100 rounded text-xs font-semibold">
                                    {{ $intent->suggested_department ?? '-' }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-sm">
                                @php
                                    $priorityColors = [
                                        'LOW' => 'bg-green-100 dark:bg-green-900 text-green-900 dark:text-green-100',
                                        'NORMAL' => 'bg-yellow-100 dark:bg-yellow-900 text-yellow-900 dark:text-yellow-100',
                                        'HIGH' => 'bg-orange-100 dark:bg-orange-900 text-orange-900 dark:text-orange-100',
                                        'URGENT' => 'bg-red-100 dark:bg-red-900 text-red-900 dark:text-red-100',
                                    ];
                                @endphp
                                <span class="px-2 py-1 rounded text-xs font-semibold {{ $priorityColors[$intent->priority_level] ?? '' }}">
                                    {{ $intent->priority_label }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-sm">
                                <div class="flex items-center">
                                    <div class="w-16 bg-gray-200 dark:bg-gray-700 rounded-full h-2 mr-2">
                                        <div class="bg-blue-500 h-2 rounded-full"
                                             style="width: {{ $intent->primary_confidence * 100 }}%">
                                        </div>
                                    </div>
                                    <span class="text-xs font-semibold">{{ round($intent->primary_confidence * 100) }}%</span>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-sm">
                                @if($intent->hasSecondaryIntents())
                                    <span class="text-xs text-gray-600 dark:text-gray-400">
                                        {{ implode(', ', $intent->secondary_intents) }}
                                    </span>
                                @else
                                    <span class="text-xs text-gray-400">-</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-400">
                                {{ $intent->created_at->format('d M Y H:i') }}
                            </td>
                            <td class="px-6 py-4 text-right text-sm space-x-2">
                                <button onclick="deleteIntent({{ $intent->id }})"
                                        class="text-red-600 hover:text-red-900 dark:text-red-400 dark:hover:text-red-300">
                                    ลบ
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-12 text-center text-gray-500 dark:text-gray-400">
                                ไม่พบ intents
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Pagination --}}
    <div class="mt-6">
        {{ $intents->links() }}
    </div>
</div>

<script>
function deleteIntent(intentId) {
    if (confirm('ต้องการลบ intent นี้ใช่หรือไม่?')) {
        fetch(`/admin/line-bot/keywords/nlp-analysis/intents/${intentId}`, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            },
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            }
        })
        .catch(error => console.error('Error:', error));
    }
}
</script>
@endsection
