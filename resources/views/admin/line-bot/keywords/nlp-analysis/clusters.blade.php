@extends('layouts.admin')

@section('title', 'Keyword Clusters')

@section('content')
<div class="container mx-auto px-4 py-8">
    {{-- Page Header --}}
    <div class="mb-8 flex justify-between items-center">
        <div>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">🔗 Keyword Clusters</h1>
            <p class="text-gray-600 dark:text-gray-400">{{ number_format($clusters->total()) }} clusters ทั้งหมด</p>
        </div>
        <div class="space-x-2">
            <button onclick="showCreateModal()"
                    class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded transition">
                + สร้าง Cluster ใหม่
            </button>
            <a href="{{ route('admin.line-bot.keywords.nlp-analysis.index') }}"
               class="px-4 py-2 bg-gray-600 hover:bg-gray-700 dark:bg-gray-700 dark:hover:bg-gray-600 text-white rounded transition">
                ← กลับ
            </a>
        </div>
    </div>

    {{-- Filter Form --}}
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6 mb-8">
        <form method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-4">
            {{-- Category Filter --}}
            <div>
                <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">Category</label>
                <select name="category" class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded dark:bg-gray-700 dark:text-white">
                    <option value="">ทั้งหมด</option>
                    @foreach(['PRODUCT', 'SERVICE', 'ISSUE', 'FEATURE', 'FEEDBACK', 'PROCESS', 'TECHNICAL', 'BUSINESS', 'OTHER'] as $cat)
                        <option value="{{ $cat }}" {{ request('category') === $cat ? 'selected' : '' }}>
                            {{ $cat }}
                        </option>
                    @endforeach
                </select>
            </div>

            {{-- Status Filter --}}
            <div>
                <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">Status</label>
                <select name="status" class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded dark:bg-gray-700 dark:text-white">
                    <option value="">ทั้งหมด</option>
                    <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>ใช้งาน</option>
                    <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>ปิดใช้งาน</option>
                </select>
            </div>

            {{-- Sort --}}
            <div>
                <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">Sort By</label>
                <select name="sort" class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded dark:bg-gray-700 dark:text-white">
                    <option value="">ล่าสุด</option>
                    <option value="popularity" {{ request('sort') === 'popularity' ? 'selected' : '' }}>ความนิยม</option>
                </select>
            </div>

            {{-- Submit --}}
            <div class="flex items-end">
                <button type="submit" class="w-full px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded transition">
                    🔍 ค้นหา
                </button>
            </div>
        </form>
    </div>

    {{-- Clusters Grid --}}
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        @forelse($clusters as $cluster)
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow hover:shadow-lg transition p-6">
                {{-- Cluster Header --}}
                <div class="flex justify-between items-start mb-4">
                    <div>
                        <h3 class="text-lg font-bold text-gray-900 dark:text-white">
                            {{ $cluster->display_name }}
                        </h3>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">{{ $cluster->cluster_name }}</p>
                    </div>
                    @if($cluster->is_active)
                        <span class="px-2 py-1 bg-green-100 dark:bg-green-900 text-green-900 dark:text-green-100 rounded text-xs font-semibold">
                            ✓ Active
                        </span>
                    @else
                        <span class="px-2 py-1 bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-400 rounded text-xs">
                            Inactive
                        </span>
                    @endif
                </div>

                {{-- Description --}}
                @if($cluster->description)
                    <p class="text-sm text-gray-600 dark:text-gray-400 mb-4 line-clamp-2">
                        {{ $cluster->description }}
                    </p>
                @endif

                {{-- Stats --}}
                <div class="grid grid-cols-3 gap-3 mb-4 p-3 bg-gray-50 dark:bg-gray-700 rounded">
                    <div class="text-center">
                        <p class="text-xs text-gray-600 dark:text-gray-400">Keywords</p>
                        <p class="text-lg font-bold text-gray-900 dark:text-white">{{ $cluster->actual_keyword_count }}</p>
                    </div>
                    <div class="text-center">
                        <p class="text-xs text-gray-600 dark:text-gray-400">Matches</p>
                        <p class="text-lg font-bold text-gray-900 dark:text-white">{{ $cluster->total_matches }}</p>
                    </div>
                    <div class="text-center">
                        <p class="text-xs text-gray-600 dark:text-gray-400">Frequency</p>
                        <p class="text-lg font-bold text-gray-900 dark:text-white">{{ $cluster->usage_frequency_percentage }}%</p>
                    </div>
                </div>

                {{-- Category Tag --}}
                <div class="mb-4">
                    <span class="inline-block px-3 py-1 bg-blue-100 dark:bg-blue-900 text-blue-900 dark:text-blue-100 rounded-full text-xs font-semibold">
                        {{ $cluster->cluster_category }}
                    </span>
                </div>

                {{-- Related Intents --}}
                @if($cluster->getRelatedIntents())
                    <div class="mb-4">
                        <p class="text-xs font-semibold text-gray-700 dark:text-gray-300 mb-2">Related Intents:</p>
                        <div class="flex flex-wrap gap-2">
                            @foreach($cluster->getRelatedIntents() as $intent)
                                <span class="text-xs px-2 py-1 bg-green-100 dark:bg-green-900 text-green-900 dark:text-green-100 rounded">
                                    {{ $intent }}
                                </span>
                            @endforeach
                        </div>
                    </div>
                @endif

                {{-- Actions --}}
                <div class="flex gap-2 pt-4 border-t border-gray-200 dark:border-gray-700">
                    <a href="{{ route('admin.line-bot.keywords.nlp-analysis.show-cluster', $cluster) }}"
                       class="flex-1 text-center px-3 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm rounded transition">
                        📋 ดู
                    </a>
                    <button onclick="deleteCluster({{ $cluster->id }})"
                            class="flex-1 text-center px-3 py-2 bg-red-600 hover:bg-red-700 text-white text-sm rounded transition">
                        🗑️ ลบ
                    </button>
                </div>
            </div>
        @empty
            <div class="col-span-full text-center py-12">
                <p class="text-gray-500 dark:text-gray-400 text-lg">ไม่พบ clusters</p>
            </div>
        @endforelse
    </div>

    {{-- Pagination --}}
    <div class="mt-8">
        {{ $clusters->links() }}
    </div>
</div>

<script>
function showCreateModal() {
    alert('ฟีเจอร์นี้จะเร็วๆ นี้');
}

function deleteCluster(clusterId) {
    if (confirm('ต้องการลบ cluster นี้ใช่หรือไม่?')) {
        fetch(`/admin/line-bot/keywords/nlp-analysis/clusters/${clusterId}`, {
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
