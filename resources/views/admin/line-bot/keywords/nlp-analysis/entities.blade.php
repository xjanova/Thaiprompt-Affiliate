@extends('layouts.admin-v3')

@section('title', 'Entities')

@section('content')
<div class="container mx-auto px-4 py-8">
    {{-- Page Header --}}
    <div class="mb-8 flex justify-between items-center">
        <div>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">📊 Extracted Entities</h1>
            <p class="text-gray-600 dark:text-gray-400">{{ number_format($entities->total()) }} entities พบทั้งหมด</p>
        </div>
        <a href="{{ route('admin.line-bot.keywords.nlp-analysis.index') }}"
           class="px-4 py-2 bg-gray-600 hover:bg-gray-700 dark:bg-gray-700 dark:hover:bg-gray-600 text-white rounded transition">
            ← กลับ
        </a>
    </div>

    {{-- Filter Form --}}
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6 mb-8">
        <form method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-4">
            {{-- Type Filter --}}
            <div>
                <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">Entity Type</label>
                <select name="type" class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded dark:bg-gray-700 dark:text-white">
                    <option value="">ทั้งหมด</option>
                    @foreach($entityTypes as $type)
                        <option value="{{ $type }}" {{ request('type') === $type ? 'selected' : '' }}>
                            {{ $type }}
                        </option>
                    @endforeach
                </select>
            </div>

            {{-- Source Filter --}}
            <div>
                <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">Source</label>
                <select name="source" class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded dark:bg-gray-700 dark:text-white">
                    <option value="">ทั้งหมด</option>
                    <option value="LEXICON" {{ request('source') === 'LEXICON' ? 'selected' : '' }}>Lexicon</option>
                    <option value="PATTERN" {{ request('source') === 'PATTERN' ? 'selected' : '' }}>Pattern</option>
                    <option value="ML" {{ request('source') === 'ML' ? 'selected' : '' }}>ML</option>
                </select>
            </div>

            {{-- Min Confidence Filter --}}
            <div>
                <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">Min Confidence</label>
                <select name="min_confidence" class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded dark:bg-gray-700 dark:text-white">
                    <option value="">ทั้งหมด</option>
                    <option value="0.5" {{ request('min_confidence') === '0.5' ? 'selected' : '' }}>50%+</option>
                    <option value="0.7" {{ request('min_confidence') === '0.7' ? 'selected' : '' }}>70%+</option>
                    <option value="0.8" {{ request('min_confidence') === '0.8' ? 'selected' : '' }}>80%+</option>
                    <option value="0.9" {{ request('min_confidence') === '0.9' ? 'selected' : '' }}>90%+</option>
                </select>
            </div>

            {{-- Submit Button --}}
            <div class="flex items-end">
                <button type="submit" class="w-full px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded transition">
                    🔍 ค้นหา
                </button>
            </div>
        </form>
    </div>

    {{-- Entities Table --}}
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-700">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Entity Value</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Type</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Source</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Confidence</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Primary</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">วันที่</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">จัดการ</th>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse($entities as $entity)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700 transition">
                            <td class="px-6 py-4 text-sm font-semibold text-gray-900 dark:text-white">
                                {{ $entity->entity_value }}
                            </td>
                            <td class="px-6 py-4 text-sm">
                                <span class="px-3 py-1 bg-blue-100 dark:bg-blue-900 text-blue-900 dark:text-blue-100 rounded-full text-xs font-semibold">
                                    {{ $entity->entity_type }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-400">
                                <span class="px-2 py-1 bg-gray-100 dark:bg-gray-700 rounded text-xs">
                                    {{ $entity->entity_source }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-sm">
                                <div class="flex items-center">
                                    <div class="w-16 bg-gray-200 dark:bg-gray-700 rounded-full h-2 mr-2">
                                        <div class="bg-green-500 h-2 rounded-full"
                                             style="width: {{ $entity->confidence * 100 }}%">
                                        </div>
                                    </div>
                                    <span class="text-xs font-semibold">{{ round($entity->confidence * 100) }}%</span>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-sm">
                                @if($entity->is_primary)
                                    <span class="px-2 py-1 bg-green-100 dark:bg-green-900 text-green-900 dark:text-green-100 rounded text-xs font-semibold">
                                        ✓ Yes
                                    </span>
                                @else
                                    <span class="px-2 py-1 bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-400 rounded text-xs">
                                        No
                                    </span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-400">
                                {{ $entity->created_at->format('d M Y') }}
                            </td>
                            <td class="px-6 py-4 text-right text-sm">
                                <button onclick="deleteEntity({{ $entity->id }})"
                                        class="text-red-600 hover:text-red-900 dark:text-red-400 dark:hover:text-red-300">
                                    ลบ
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-12 text-center text-gray-500 dark:text-gray-400">
                                ไม่พบ entities
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Pagination --}}
    <div class="mt-6">
        {{ $entities->links() }}
    </div>
</div>

<script>
function deleteEntity(entityId) {
    if (confirm('ต้องการลบ entity นี้ใช่หรือไม่?')) {
        fetch(`/admin/line-bot/keywords/nlp-analysis/entities/${entityId}`, {
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
