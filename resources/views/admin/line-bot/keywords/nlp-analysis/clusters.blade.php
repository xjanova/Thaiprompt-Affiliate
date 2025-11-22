@extends('layouts.admin-v3')

@section('title', 'Keyword Clusters - NLP Analysis')

@push('styles')
<style>
.word-cloud-item {
    animation: wordFloat 3s ease-in-out infinite;
}

.word-cloud-item:nth-child(2n) {
    animation-delay: 0.5s;
}

.word-cloud-item:nth-child(3n) {
    animation-delay: 1s;
}

@keyframes wordFloat {
    0%, 100% { transform: translateY(0px); }
    50% { transform: translateY(-10px); }
}

.cluster-card {
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.cluster-card:hover {
    transform: translateY(-4px) scale(1.02);
}
</style>
@endpush

@section('content')
<div class="container mx-auto px-4 py-6" x-data="clustersManager()" x-init="init()">
    {{-- Header --}}
    <div class="mb-8 flex justify-between items-center">
        <div>
            <a href="{{ route('admin.line-bot.keywords.nlp-analysis.index') }}"
               class="inline-flex items-center gap-2 text-[#06C755] hover:text-emerald-600 dark:text-green-400 dark:hover:text-green-300 font-semibold mb-4 transition-colors">
                <i class="fas fa-arrow-left"></i>
                <span>กลับ</span>
            </a>

            <h1 class="text-4xl font-black text-gray-900 dark:text-white flex items-center gap-3">
                <span class="text-5xl">🔗</span>
                <span>Keyword Clusters</span>
            </h1>
            <p class="text-gray-600 dark:text-gray-400 mt-2">
                {{ number_format($clusters->total()) }} clusters ที่ใช้งานอยู่
            </p>
        </div>
    </div>

    {{-- Filter Form --}}
    <div class="backdrop-blur-xl bg-white/80 dark:bg-gray-800/80 rounded-2xl shadow-2xl p-6 mb-8 border border-white/20 dark:border-gray-700/30">
        <form method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-4">
            {{-- Category Filter --}}
            <div>
                <label class="block text-sm font-bold text-gray-900 dark:text-white mb-2">
                    <i class="fas fa-tag mr-2 text-[#06C755]"></i>Category
                </label>
                <select name="category"
                        class="w-full px-4 py-3 border-2 border-gray-200 dark:border-gray-700 rounded-xl bg-white/50 dark:bg-gray-900/50 backdrop-blur-sm text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-[#06C755] focus:border-transparent transition-all">
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
                <label class="block text-sm font-bold text-gray-900 dark:text-white mb-2">
                    <i class="fas fa-toggle-on mr-2 text-blue-500"></i>Status
                </label>
                <select name="status"
                        class="w-full px-4 py-3 border-2 border-gray-200 dark:border-gray-700 rounded-xl bg-white/50 dark:bg-gray-900/50 backdrop-blur-sm text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all">
                    <option value="">ทั้งหมด</option>
                    <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>ใช้งาน</option>
                    <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>ปิดใช้งาน</option>
                </select>
            </div>

            {{-- Sort --}}
            <div>
                <label class="block text-sm font-bold text-gray-900 dark:text-white mb-2">
                    <i class="fas fa-sort mr-2 text-purple-500"></i>Sort By
                </label>
                <select name="sort"
                        class="w-full px-4 py-3 border-2 border-gray-200 dark:border-gray-700 rounded-xl bg-white/50 dark:bg-gray-900/50 backdrop-blur-sm text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-all">
                    <option value="">ล่าสุด</option>
                    <option value="popularity" {{ request('sort') === 'popularity' ? 'selected' : '' }}>ความนิยม</option>
                </select>
            </div>

            {{-- Submit --}}
            <div class="flex items-end">
                <button type="submit"
                        class="w-full px-6 py-3 bg-gradient-to-r from-[#06C755] to-emerald-600 text-white rounded-xl font-bold shadow-lg hover:shadow-2xl hover:scale-105 transition-all">
                    <i class="fas fa-search mr-2"></i>ค้นหา
                </button>
            </div>
        </form>
    </div>

    {{-- Clusters Grid with Word Clouds --}}
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
        @forelse($clusters as $cluster)
            <div class="cluster-card backdrop-blur-xl bg-gradient-to-br from-{{ ['blue', 'purple', 'green', 'orange', 'pink', 'cyan'][($loop->index) % 6] }}-50/80 to-{{ ['cyan', 'pink', 'emerald', 'red', 'purple', 'blue'][($loop->index) % 6] }}-50/80 dark:from-{{ ['blue', 'purple', 'green', 'orange', 'pink', 'cyan'][($loop->index) % 6] }}-900/30 dark:to-{{ ['cyan', 'pink', 'emerald', 'red', 'purple', 'blue'][($loop->index) % 6] }}-900/30 rounded-2xl shadow-2xl p-6 border-2 border-{{ ['blue', 'purple', 'green', 'orange', 'pink', 'cyan'][($loop->index) % 6] }}-200 dark:border-{{ ['blue', 'purple', 'green', 'orange', 'pink', 'cyan'][($loop->index) % 6] }}-700">
                {{-- Cluster Header --}}
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-xl font-black text-gray-900 dark:text-white">
                        {{ $cluster->display_name }}
                    </h3>
                    <div class="w-12 h-12 bg-gradient-to-br from-{{ ['blue', 'purple', 'green', 'orange', 'pink', 'cyan'][($loop->index) % 6] }}-500 to-{{ ['cyan', 'pink', 'emerald', 'red', 'purple', 'blue'][($loop->index) % 6] }}-600 rounded-xl flex items-center justify-center shadow-lg">
                        <span class="text-2xl">{{ ['🔵', '🟣', '🟢', '🟠', '🔴', '🔷'][($loop->index) % 6] }}</span>
                    </div>
                </div>

                {{-- Description --}}
                @if($cluster->description)
                    <p class="text-sm text-gray-700 dark:text-gray-300 mb-4 line-clamp-2">
                        {{ $cluster->description }}
                    </p>
                @endif

                {{-- Stats --}}
                <div class="grid grid-cols-3 gap-3 mb-4">
                    <div class="bg-white/50 dark:bg-gray-900/50 rounded-xl p-3 backdrop-blur-sm text-center">
                        <p class="text-xs font-bold text-gray-600 dark:text-gray-400">Keywords</p>
                        <p class="text-2xl font-black text-gray-900 dark:text-white">{{ $cluster->actual_keyword_count }}</p>
                    </div>
                    <div class="bg-white/50 dark:bg-gray-900/50 rounded-xl p-3 backdrop-blur-sm text-center">
                        <p class="text-xs font-bold text-gray-600 dark:text-gray-400">Matches</p>
                        <p class="text-2xl font-black text-{{ ['blue', 'purple', 'green', 'orange', 'pink', 'cyan'][($loop->index) % 6] }}-600 dark:text-{{ ['blue', 'purple', 'green', 'orange', 'pink', 'cyan'][($loop->index) % 6] }}-400">
                            {{ number_format($cluster->total_matches) }}
                        </p>
                    </div>
                    <div class="bg-white/50 dark:bg-gray-900/50 rounded-xl p-3 backdrop-blur-sm text-center">
                        <p class="text-xs font-bold text-gray-600 dark:text-gray-400">Freq</p>
                        <p class="text-2xl font-black text-gray-900 dark:text-white">{{ round($cluster->usage_frequency_percentage) }}%</p>
                    </div>
                </div>

                {{-- Word Cloud Preview (simplified) --}}
                <div class="mb-4 p-4 bg-white/30 dark:bg-gray-900/30 rounded-xl backdrop-blur-sm min-h-[120px] flex flex-wrap gap-2 justify-center items-center">
                    {{-- Show category and related intents as tags --}}
                    <span class="word-cloud-item px-3 py-2 bg-{{ ['blue', 'purple', 'green', 'orange', 'pink', 'cyan'][($loop->index) % 6] }}-100 dark:bg-{{ ['blue', 'purple', 'green', 'orange', 'pink', 'cyan'][($loop->index) % 6] }}-900/50 text-{{ ['blue', 'purple', 'green', 'orange', 'pink', 'cyan'][($loop->index) % 6] }}-800 dark:text-{{ ['blue', 'purple', 'green', 'orange', 'pink', 'cyan'][($loop->index) % 6] }}-200 rounded-lg font-bold text-lg">
                        {{ $cluster->cluster_category }}
                    </span>
                    @if($cluster->getRelatedIntents())
                        @foreach(collect($cluster->getRelatedIntents())->take(5) as $intent)
                            <span class="word-cloud-item px-2 py-1 bg-green-100 dark:bg-green-900/50 text-green-800 dark:text-green-200 rounded-lg text-sm font-semibold">
                                {{ $intent }}
                            </span>
                        @endforeach
                    @endif
                </div>

                {{-- Status Badge --}}
                <div class="mb-4">
                    @if($cluster->is_active)
                        <span class="inline-flex items-center gap-2 px-4 py-2 bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-200 rounded-full text-xs font-bold">
                            <i class="fas fa-check-circle"></i>
                            Active
                        </span>
                    @else
                        <span class="inline-flex items-center gap-2 px-4 py-2 bg-gray-100 dark:bg-gray-700/50 text-gray-600 dark:text-gray-400 rounded-full text-xs font-bold">
                            <i class="fas fa-times-circle"></i>
                            Inactive
                        </span>
                    @endif
                </div>

                {{-- Actions --}}
                <div class="flex gap-2">
                    <a href="{{ route('admin.line-bot.keywords.nlp-analysis.show-cluster', $cluster) }}"
                       class="flex-1 px-4 py-3 bg-gradient-to-r from-{{ ['blue', 'purple', 'green', 'orange', 'pink', 'cyan'][($loop->index) % 6] }}-500 to-{{ ['cyan', 'pink', 'emerald', 'red', 'purple', 'blue'][($loop->index) % 6] }}-600 text-white text-center rounded-xl font-bold shadow-lg hover:shadow-2xl hover:scale-105 transition-all">
                        <i class="fas fa-eye mr-2"></i>ดู
                    </a>
                    <button @click="deleteCluster({{ $cluster->id }})"
                            class="px-4 py-3 bg-red-500/80 hover:bg-red-600 text-white rounded-xl font-bold shadow-lg hover:shadow-2xl hover:scale-105 transition-all">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            </div>
        @empty
            <div class="col-span-full backdrop-blur-xl bg-white/80 dark:bg-gray-800/80 rounded-2xl shadow-2xl p-16 text-center border border-white/20 dark:border-gray-700/30">
                <div class="w-32 h-32 bg-gray-100 dark:bg-gray-700 rounded-full flex items-center justify-center mx-auto mb-6">
                    <i class="fas fa-layer-group text-6xl text-gray-400"></i>
                </div>
                <h3 class="text-2xl font-black text-gray-900 dark:text-white mb-2">
                    ยังไม่มี Clusters
                </h3>
                <p class="text-gray-600 dark:text-gray-400">
                    ระบบจะสร้าง clusters อัตโนมัติเมื่อมีข้อมูลเพียงพอ
                </p>
            </div>
        @endforelse
    </div>

    {{-- Pagination --}}
    @if($clusters->hasPages())
        <div class="mt-8">
            {{ $clusters->links() }}
        </div>
    @endif
</div>

@push('scripts')
<script>
/**
 * Clusters Manager Component
 *
 * จัดการการแสดงผล clusters และ visualizations
 */
function clustersManager() {
    return {
        /**
         * เริ่มต้น component
         */
        init() {
            console.log('Clusters Manager initialized');
        },

        /**
         * ลบ cluster
         *
         * @param {number} clusterId
         */
        deleteCluster(clusterId) {
            if (!confirm('ต้องการลบ cluster นี้ใช่หรือไม่?')) return;

            fetch(`/admin/line-bot/keywords/nlp-analysis/clusters/${clusterId}`, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Content-Type': 'application/json'
                },
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('ลบสำเร็จ');
                    location.reload();
                } else {
                    alert('ข้อผิดพลาด: ' + (data.message || 'Unknown error'));
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('เกิดข้อผิดพลาด');
            });
        }
    };
}
</script>
@endpush
@endsection
