@extends('layouts.admin-v3')

@section('title', 'Entities - NLP Analysis')

@section('content')
<div class="container mx-auto px-4 py-6" x-data="entitiesManager()" x-init="init()">
    {{-- Header --}}
    <div class="mb-8 flex justify-between items-center">
        <div>
            <a href="{{ route('admin.line-bot.keywords.nlp-analysis.index') }}"
               class="inline-flex items-center gap-2 text-[#06C755] hover:text-emerald-600 dark:text-green-400 dark:hover:text-green-300 font-semibold mb-4 transition-colors">
                <i class="fas fa-arrow-left"></i>
                <span>กลับ</span>
            </a>

            <h1 class="text-4xl font-black text-gray-900 dark:text-white flex items-center gap-3">
                <span class="text-5xl">📊</span>
                <span>Extracted Entities</span>
            </h1>
            <p class="text-gray-600 dark:text-gray-400 mt-2">
                {{ number_format($entities->total()) }} entities พบทั้งหมด
            </p>
        </div>
    </div>

    {{-- Filter Form --}}
    <div class="backdrop-blur-xl bg-white/80 dark:bg-gray-800/80 rounded-2xl shadow-2xl p-6 mb-8 border border-white/20 dark:border-gray-700/30">
        <form method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-4">
            {{-- Type Filter --}}
            <div>
                <label class="block text-sm font-bold text-gray-900 dark:text-white mb-2">
                    <i class="fas fa-tag mr-2 text-[#06C755]"></i>Entity Type
                </label>
                <select name="type"
                        class="w-full px-4 py-3 border-2 border-gray-200 dark:border-gray-700 rounded-xl bg-white/50 dark:bg-gray-900/50 backdrop-blur-sm text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-[#06C755] focus:border-transparent transition-all">
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
                <label class="block text-sm font-bold text-gray-900 dark:text-white mb-2">
                    <i class="fas fa-database mr-2 text-blue-500"></i>Source
                </label>
                <select name="source"
                        class="w-full px-4 py-3 border-2 border-gray-200 dark:border-gray-700 rounded-xl bg-white/50 dark:bg-gray-900/50 backdrop-blur-sm text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all">
                    <option value="">ทั้งหมด</option>
                    <option value="LEXICON" {{ request('source') === 'LEXICON' ? 'selected' : '' }}>Lexicon</option>
                    <option value="PATTERN" {{ request('source') === 'PATTERN' ? 'selected' : '' }}>Pattern</option>
                    <option value="ML" {{ request('source') === 'ML' ? 'selected' : '' }}>ML</option>
                </select>
            </div>

            {{-- Min Confidence Filter --}}
            <div>
                <label class="block text-sm font-bold text-gray-900 dark:text-white mb-2">
                    <i class="fas fa-chart-line mr-2 text-green-500"></i>Min Confidence
                </label>
                <select name="min_confidence"
                        class="w-full px-4 py-3 border-2 border-gray-200 dark:border-gray-700 rounded-xl bg-white/50 dark:bg-gray-900/50 backdrop-blur-sm text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent transition-all">
                    <option value="">ทั้งหมด</option>
                    <option value="0.5" {{ request('min_confidence') === '0.5' ? 'selected' : '' }}>50%+</option>
                    <option value="0.7" {{ request('min_confidence') === '0.7' ? 'selected' : '' }}>70%+</option>
                    <option value="0.8" {{ request('min_confidence') === '0.8' ? 'selected' : '' }}>80%+</option>
                    <option value="0.9" {{ request('min_confidence') === '0.9' ? 'selected' : '' }}>90%+</option>
                </select>
            </div>

            {{-- Submit Button --}}
            <div class="flex items-end">
                <button type="submit"
                        class="w-full px-6 py-3 bg-gradient-to-r from-[#06C755] to-emerald-600 text-white rounded-xl font-bold shadow-lg hover:shadow-2xl hover:scale-105 transition-all">
                    <i class="fas fa-search mr-2"></i>ค้นหา
                </button>
            </div>
        </form>
    </div>

    {{-- Entities Table --}}
    <div class="backdrop-blur-xl bg-white/80 dark:bg-gray-800/80 rounded-2xl shadow-2xl overflow-hidden border border-white/20 dark:border-gray-700/30">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gradient-to-r from-gray-50 to-gray-100 dark:from-gray-900 dark:to-gray-800">
                    <tr>
                        <th class="px-6 py-4 text-left text-xs font-black text-gray-700 dark:text-gray-300 uppercase tracking-wider">
                            Entity Value
                        </th>
                        <th class="px-6 py-4 text-left text-xs font-black text-gray-700 dark:text-gray-300 uppercase tracking-wider">
                            Type
                        </th>
                        <th class="px-6 py-4 text-left text-xs font-black text-gray-700 dark:text-gray-300 uppercase tracking-wider">
                            Source
                        </th>
                        <th class="px-6 py-4 text-left text-xs font-black text-gray-700 dark:text-gray-300 uppercase tracking-wider">
                            Confidence
                        </th>
                        <th class="px-6 py-4 text-left text-xs font-black text-gray-700 dark:text-gray-300 uppercase tracking-wider">
                            Primary
                        </th>
                        <th class="px-6 py-4 text-left text-xs font-black text-gray-700 dark:text-gray-300 uppercase tracking-wider">
                            วันที่
                        </th>
                        <th class="px-6 py-4 text-right text-xs font-black text-gray-700 dark:text-gray-300 uppercase tracking-wider">
                            จัดการ
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-white/50 dark:bg-gray-800/50 backdrop-blur-sm divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse($entities as $entity)
                        <tr class="hover:bg-white/80 dark:hover:bg-gray-700/50 transition-all group">
                            <td class="px-6 py-4 text-sm font-bold text-gray-900 dark:text-white">
                                <div class="flex items-center gap-2">
                                    <div class="w-2 h-2 bg-blue-500 rounded-full"></div>
                                    {{ $entity->entity_value }}
                                </div>
                            </td>
                            <td class="px-6 py-4 text-sm">
                                <span class="inline-flex items-center gap-2 px-4 py-2 bg-blue-100 dark:bg-blue-900/30 text-blue-800 dark:text-blue-200 rounded-full text-xs font-bold">
                                    <i class="fas fa-tag"></i>
                                    {{ $entity->entity_type }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-sm">
                                <span class="px-3 py-1 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-lg text-xs font-semibold">
                                    {{ $entity->entity_source }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-sm">
                                <div class="flex items-center gap-3">
                                    <div class="flex-1 h-3 bg-gray-200 dark:bg-gray-700 rounded-full overflow-hidden max-w-[80px]">
                                        <div class="h-full bg-gradient-to-r from-green-400 to-emerald-600 rounded-full transition-all duration-500"
                                             style="width: {{ $entity->confidence * 100 }}%"></div>
                                    </div>
                                    <span class="text-sm font-black text-gray-900 dark:text-white min-w-[45px]">
                                        {{ round($entity->confidence * 100) }}%
                                    </span>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-sm">
                                @if($entity->is_primary)
                                    <span class="inline-flex items-center gap-2 px-3 py-1 bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-200 rounded-full text-xs font-bold">
                                        <i class="fas fa-check-circle"></i>
                                        Yes
                                    </span>
                                @else
                                    <span class="px-3 py-1 bg-gray-100 dark:bg-gray-700 text-gray-500 dark:text-gray-400 rounded-full text-xs">
                                        No
                                    </span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-400">
                                <div class="flex items-center gap-2">
                                    <i class="fas fa-calendar-alt text-gray-400"></i>
                                    {{ $entity->created_at->format('d M Y') }}
                                </div>
                            </td>
                            <td class="px-6 py-4 text-right text-sm">
                                <button @click="deleteEntity({{ $entity->id }})"
                                        class="inline-flex items-center gap-2 px-4 py-2 bg-red-500/80 hover:bg-red-600 text-white rounded-lg font-semibold transition-all hover:scale-105 opacity-0 group-hover:opacity-100">
                                    <i class="fas fa-trash"></i>
                                    ลบ
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-16 text-center">
                                <div class="flex flex-col items-center gap-4">
                                    <div class="w-24 h-24 bg-gray-100 dark:bg-gray-700 rounded-full flex items-center justify-center">
                                        <i class="fas fa-inbox text-4xl text-gray-400"></i>
                                    </div>
                                    <p class="text-gray-500 dark:text-gray-400 text-lg font-medium">
                                        ไม่พบ entities
                                    </p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        @if($entities->hasPages())
            <div class="bg-white/50 dark:bg-gray-800/50 backdrop-blur-sm px-6 py-4 border-t border-gray-200 dark:border-gray-700">
                {{ $entities->links() }}
            </div>
        @endif
    </div>
</div>

@push('scripts')
<script>
/**
 * Entities Manager Component
 *
 * จัดการการแสดงผลและลบ entities
 */
function entitiesManager() {
    return {
        /**
         * เริ่มต้น component
         */
        init() {
            console.log('Entities Manager initialized');
        },

        /**
         * ลบ entity
         *
         * @param {number} entityId
         */
        deleteEntity(entityId) {
            if (!confirm('ต้องการลบ entity นี้ใช่หรือไม่?')) return;

            fetch(`/admin/line-bot/keywords/nlp-analysis/entities/${entityId}`, {
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
