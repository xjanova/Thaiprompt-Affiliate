@extends('layouts.admin-v3')

@section('title', 'การวิเคราะห์ NLP - LINE Bot Keywords')

@push('styles')
<style>
.stat-card-pulse {
    animation: statPulse 3s ease-in-out infinite;
}

@keyframes statPulse {
    0%, 100% { transform: scale(1); }
    50% { transform: scale(1.02); }
}
</style>
@endpush

@section('content')
<div class="container mx-auto px-4 py-6" x-data="nlpAnalysis(@js([
    'totalEntities' => $totalEntities,
    'totalIntents' => $totalIntents,
    'totalClusters' => $totalClusters,
    'avgConfidence' => $avgConfidence ?? 0.95
]))" x-init="init()">
    {{-- Header with LINE Green Gradient --}}
    <div class="mb-8 relative overflow-hidden bg-gradient-to-r from-[#06C755] via-green-500 to-emerald-600 rounded-2xl p-8 shadow-2xl">
        <div class="absolute inset-0 opacity-20">
            <div class="absolute inset-0 bg-[radial-gradient(circle_at_20%_50%,rgba(255,255,255,0.3),transparent_50%)]"></div>
        </div>

        <div class="relative z-10">
            <h1 class="text-4xl font-black text-white mb-2 flex items-center gap-3">
                <span class="text-5xl">🧠</span>
                <span>การวิเคราะห์ NLP</span>
            </h1>
            <p class="text-white/90 text-lg">
                วิเคราะห์ entities, intents, และ keyword clusters จากข้อความผู้ใช้
            </p>
        </div>
    </div>

    {{-- Statistics Cards with 3D Effect --}}
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        {{-- Total Entities --}}
        <div class="group perspective-1000">
            <div class="relative transform-gpu transition-all duration-500 group-hover:scale-105 group-hover:rotate-y-2">
                <div class="absolute inset-0 bg-gradient-to-br from-blue-500 to-cyan-600 rounded-2xl blur-xl opacity-50 group-hover:opacity-75 transition-opacity"></div>
                <div class="relative backdrop-blur-xl bg-white/80 dark:bg-gray-800/80 rounded-2xl shadow-2xl p-6 border-2 border-blue-200 dark:border-blue-700">
                    <div class="flex justify-between items-start mb-4">
                        <div>
                            <p class="text-sm font-bold text-blue-700 dark:text-blue-300 mb-2">Entities ทั้งหมด</p>
                            <h3 class="text-4xl font-black text-blue-900 dark:text-blue-100" x-text="stats.totalEntities">
                                {{ number_format($totalEntities) }}
                            </h3>
                        </div>
                        <div class="w-14 h-14 bg-gradient-to-br from-blue-500 to-cyan-600 rounded-xl flex items-center justify-center shadow-lg">
                            <span class="text-3xl">🏷️</span>
                        </div>
                    </div>
                    <div class="h-1 bg-gradient-to-r from-blue-500 to-cyan-600 rounded-full"></div>
                </div>
            </div>
        </div>

        {{-- Total Intents --}}
        <div class="group perspective-1000">
            <div class="relative transform-gpu transition-all duration-500 group-hover:scale-105 group-hover:rotate-y-2">
                <div class="absolute inset-0 bg-gradient-to-br from-green-500 to-emerald-600 rounded-2xl blur-xl opacity-50 group-hover:opacity-75 transition-opacity"></div>
                <div class="relative backdrop-blur-xl bg-white/80 dark:bg-gray-800/80 rounded-2xl shadow-2xl p-6 border-2 border-green-200 dark:border-green-700">
                    <div class="flex justify-between items-start mb-4">
                        <div>
                            <p class="text-sm font-bold text-green-700 dark:text-green-300 mb-2">Intents ทั้งหมด</p>
                            <h3 class="text-4xl font-black text-green-900 dark:text-green-100" x-text="stats.totalIntents">
                                {{ number_format($totalIntents) }}
                            </h3>
                        </div>
                        <div class="w-14 h-14 bg-gradient-to-br from-green-500 to-emerald-600 rounded-xl flex items-center justify-center shadow-lg">
                            <span class="text-3xl">🎯</span>
                        </div>
                    </div>
                    <div class="h-1 bg-gradient-to-r from-green-500 to-emerald-600 rounded-full"></div>
                </div>
            </div>
        </div>

        {{-- Total Clusters --}}
        <div class="group perspective-1000">
            <div class="relative transform-gpu transition-all duration-500 group-hover:scale-105 group-hover:rotate-y-2">
                <div class="absolute inset-0 bg-gradient-to-br from-purple-500 to-pink-600 rounded-2xl blur-xl opacity-50 group-hover:opacity-75 transition-opacity"></div>
                <div class="relative backdrop-blur-xl bg-white/80 dark:bg-gray-800/80 rounded-2xl shadow-2xl p-6 border-2 border-purple-200 dark:border-purple-700">
                    <div class="flex justify-between items-start mb-4">
                        <div>
                            <p class="text-sm font-bold text-purple-700 dark:text-purple-300 mb-2">Clusters ที่ใช้งาน</p>
                            <h3 class="text-4xl font-black text-purple-900 dark:text-purple-100" x-text="stats.totalClusters">
                                {{ number_format($totalClusters) }}
                            </h3>
                        </div>
                        <div class="w-14 h-14 bg-gradient-to-br from-purple-500 to-pink-600 rounded-xl flex items-center justify-center shadow-lg">
                            <span class="text-3xl">🔗</span>
                        </div>
                    </div>
                    <div class="h-1 bg-gradient-to-r from-purple-500 to-pink-600 rounded-full"></div>
                </div>
            </div>
        </div>

        {{-- High Confidence Rate --}}
        <div class="group perspective-1000">
            <div class="relative transform-gpu transition-all duration-500 group-hover:scale-105 group-hover:rotate-y-2">
                <div class="absolute inset-0 bg-gradient-to-br from-orange-500 to-red-600 rounded-2xl blur-xl opacity-50 group-hover:opacity-75 transition-opacity"></div>
                <div class="relative backdrop-blur-xl bg-white/80 dark:bg-gray-800/80 rounded-2xl shadow-2xl p-6 border-2 border-orange-200 dark:border-orange-700">
                    <div class="flex justify-between items-start mb-4">
                        <div>
                            <p class="text-sm font-bold text-orange-700 dark:text-orange-300 mb-2">Confidence เฉลี่ย</p>
                            <h3 class="text-4xl font-black text-orange-900 dark:text-orange-100">
                                <span x-text="Math.round(stats.avgConfidence * 100)">{{ isset($avgConfidence) ? round($avgConfidence * 100) : '95' }}</span>%
                            </h3>
                        </div>
                        <div class="w-14 h-14 bg-gradient-to-br from-orange-500 to-red-600 rounded-xl flex items-center justify-center shadow-lg">
                            <span class="text-3xl">✅</span>
                        </div>
                    </div>
                    <div class="h-1 bg-gradient-to-r from-orange-500 to-red-600 rounded-full"></div>
                </div>
            </div>
        </div>
    </div>

    {{-- Quick Navigation Cards --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
        {{-- Entities Card --}}
        <a href="{{ route('admin.line-bot.keywords.nlp-analysis.entities') }}"
           class="group relative overflow-hidden backdrop-blur-xl bg-gradient-to-br from-blue-50/80 to-cyan-50/80 dark:from-blue-900/30 dark:to-cyan-900/30 rounded-2xl p-6 border-2 border-blue-200 dark:border-blue-700 hover:border-blue-400 dark:hover:border-blue-500 transition-all shadow-xl hover:shadow-2xl hover:scale-105">
            <div class="flex items-start justify-between mb-4">
                <div class="w-16 h-16 bg-gradient-to-br from-blue-500 to-cyan-600 rounded-xl flex items-center justify-center shadow-lg group-hover:scale-110 transition-transform">
                    <span class="text-4xl">📊</span>
                </div>
                <i class="fas fa-arrow-right text-blue-500 dark:text-blue-400 text-xl group-hover:translate-x-2 transition-transform"></i>
            </div>
            <h3 class="text-2xl font-black text-blue-900 dark:text-blue-100 mb-2">Entities</h3>
            <p class="text-sm text-blue-700 dark:text-blue-300 mb-4">ดู entities ที่ดึงมาจากข้อความ</p>
            <p class="text-3xl font-black text-blue-600 dark:text-blue-400">
                {{ number_format($totalEntities) }}
            </p>
        </a>

        {{-- Intents Card --}}
        <a href="{{ route('admin.line-bot.keywords.nlp-analysis.intents') }}"
           class="group relative overflow-hidden backdrop-blur-xl bg-gradient-to-br from-green-50/80 to-emerald-50/80 dark:from-green-900/30 dark:to-emerald-900/30 rounded-2xl p-6 border-2 border-green-200 dark:border-green-700 hover:border-green-400 dark:hover:border-green-500 transition-all shadow-xl hover:shadow-2xl hover:scale-105">
            <div class="flex items-start justify-between mb-4">
                <div class="w-16 h-16 bg-gradient-to-br from-green-500 to-emerald-600 rounded-xl flex items-center justify-center shadow-lg group-hover:scale-110 transition-transform">
                    <span class="text-4xl">🎯</span>
                </div>
                <i class="fas fa-arrow-right text-green-500 dark:text-green-400 text-xl group-hover:translate-x-2 transition-transform"></i>
            </div>
            <h3 class="text-2xl font-black text-green-900 dark:text-green-100 mb-2">Intents</h3>
            <p class="text-sm text-green-700 dark:text-green-300 mb-4">ดู intents และแผนกที่แนะนำ</p>
            <p class="text-3xl font-black text-green-600 dark:text-green-400">
                {{ number_format($totalIntents) }}
            </p>
        </a>

        {{-- Clusters Card --}}
        <a href="{{ route('admin.line-bot.keywords.nlp-analysis.clusters') }}"
           class="group relative overflow-hidden backdrop-blur-xl bg-gradient-to-br from-purple-50/80 to-pink-50/80 dark:from-purple-900/30 dark:to-pink-900/30 rounded-2xl p-6 border-2 border-purple-200 dark:border-purple-700 hover:border-purple-400 dark:hover:border-purple-500 transition-all shadow-xl hover:shadow-2xl hover:scale-105">
            <div class="flex items-start justify-between mb-4">
                <div class="w-16 h-16 bg-gradient-to-br from-purple-500 to-pink-600 rounded-xl flex items-center justify-center shadow-lg group-hover:scale-110 transition-transform">
                    <span class="text-4xl">🔗</span>
                </div>
                <i class="fas fa-arrow-right text-purple-500 dark:text-purple-400 text-xl group-hover:translate-x-2 transition-transform"></i>
            </div>
            <h3 class="text-2xl font-black text-purple-900 dark:text-purple-100 mb-2">Clusters</h3>
            <p class="text-sm text-purple-700 dark:text-purple-300 mb-4">จัดการ keyword clusters</p>
            <p class="text-3xl font-black text-purple-600 dark:text-purple-400">
                {{ number_format($totalClusters) }}
            </p>
        </a>
    </div>

    {{-- Distribution Charts --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
        {{-- Entity Types Distribution --}}
        <div class="backdrop-blur-xl bg-white/80 dark:bg-gray-800/80 rounded-2xl shadow-2xl p-6 border border-white/20 dark:border-gray-700/30">
            <h3 class="text-xl font-black text-gray-900 dark:text-white mb-6 flex items-center gap-3">
                <div class="w-10 h-10 bg-gradient-to-br from-blue-500 to-cyan-600 rounded-xl flex items-center justify-center">
                    <i class="fas fa-chart-pie text-white"></i>
                </div>
                <span>Entity Types ที่พบ</span>
            </h3>

            <div class="space-y-4">
                @forelse($entityDistribution as $entity)
                    <div class="group p-4 bg-gray-50 dark:bg-gray-900/50 rounded-xl hover:bg-gray-100 dark:hover:bg-gray-900 transition-all">
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-sm font-bold text-gray-700 dark:text-gray-300">{{ $entity->entity_type }}</span>
                            <span class="text-lg font-black text-blue-600 dark:text-blue-400">{{ $entity->count }}</span>
                        </div>
                        <div class="relative w-full h-3 bg-gray-200 dark:bg-gray-700 rounded-full overflow-hidden">
                            <div class="absolute inset-y-0 left-0 bg-gradient-to-r from-blue-500 to-cyan-600 rounded-full transition-all duration-1000"
                                 style="width: {{ ($entity->count / $totalEntities * 100) }}%"></div>
                        </div>
                    </div>
                @empty
                    <div class="text-center py-12">
                        <div class="w-20 h-20 bg-gray-100 dark:bg-gray-700 rounded-full flex items-center justify-center mx-auto mb-4">
                            <i class="fas fa-inbox text-3xl text-gray-400"></i>
                        </div>
                        <p class="text-gray-500 dark:text-gray-400 font-medium">ยังไม่มีข้อมูล</p>
                    </div>
                @endforelse
            </div>
        </div>

        {{-- Top Intents --}}
        <div class="backdrop-blur-xl bg-white/80 dark:bg-gray-800/80 rounded-2xl shadow-2xl p-6 border border-white/20 dark:border-gray-700/30">
            <h3 class="text-xl font-black text-gray-900 dark:text-white mb-6 flex items-center gap-3">
                <div class="w-10 h-10 bg-gradient-to-br from-green-500 to-emerald-600 rounded-xl flex items-center justify-center">
                    <i class="fas fa-chart-bar text-white"></i>
                </div>
                <span>Top Intents</span>
            </h3>

            <div class="space-y-4">
                @forelse($topIntents as $intent)
                    <div class="group p-4 bg-gray-50 dark:bg-gray-900/50 rounded-xl hover:bg-gray-100 dark:hover:bg-gray-900 transition-all">
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-sm font-bold text-gray-700 dark:text-gray-300">{{ $intent->primary_intent }}</span>
                            <span class="text-lg font-black text-green-600 dark:text-green-400">{{ $intent->count }}</span>
                        </div>
                        <div class="relative w-full h-3 bg-gray-200 dark:bg-gray-700 rounded-full overflow-hidden">
                            <div class="absolute inset-y-0 left-0 bg-gradient-to-r from-green-500 to-emerald-600 rounded-full transition-all duration-1000"
                                 style="width: {{ ($intent->count / $totalIntents * 100) }}%"></div>
                        </div>
                    </div>
                @empty
                    <div class="text-center py-12">
                        <div class="w-20 h-20 bg-gray-100 dark:bg-gray-700 rounded-full flex items-center justify-center mx-auto mb-4">
                            <i class="fas fa-inbox text-3xl text-gray-400"></i>
                        </div>
                        <p class="text-gray-500 dark:text-gray-400 font-medium">ยังไม่มีข้อมูล</p>
                    </div>
                @endforelse
            </div>
        </div>
    </div>

    {{-- Additional Data Sections --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
        {{-- Top Clusters --}}
        <div class="backdrop-blur-xl bg-white/80 dark:bg-gray-800/80 rounded-2xl shadow-2xl p-6 border border-white/20 dark:border-gray-700/30">
            <h3 class="text-xl font-black text-gray-900 dark:text-white mb-6 flex items-center gap-3">
                <div class="w-10 h-10 bg-gradient-to-br from-purple-500 to-pink-600 rounded-xl flex items-center justify-center">
                    <i class="fas fa-layer-group text-white"></i>
                </div>
                <span>Top Clusters ตามการใช้งาน</span>
            </h3>

            <div class="space-y-3">
                @forelse($topClusters as $cluster)
                    <div class="flex items-center justify-between p-4 bg-gradient-to-r from-purple-50 to-pink-50 dark:from-purple-900/30 dark:to-pink-900/30 rounded-xl border border-purple-200 dark:border-purple-700 hover:shadow-lg transition-all">
                        <div>
                            <p class="text-sm font-bold text-gray-900 dark:text-white">{{ $cluster->display_name }}</p>
                            <p class="text-xs text-purple-600 dark:text-purple-400">{{ $cluster->keyword_count }} keywords</p>
                        </div>
                        <div class="text-right">
                            <p class="text-xl font-black text-purple-600 dark:text-purple-400">
                                {{ $cluster->total_matches }}
                            </p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">การใช้งาน</p>
                        </div>
                    </div>
                @empty
                    <div class="text-center py-8">
                        <p class="text-gray-500 dark:text-gray-400">ยังไม่มี clusters</p>
                    </div>
                @endforelse
            </div>
        </div>

        {{-- High Confidence Entities --}}
        <div class="backdrop-blur-xl bg-white/80 dark:bg-gray-800/80 rounded-2xl shadow-2xl p-6 border border-white/20 dark:border-gray-700/30">
            <h3 class="text-xl font-black text-gray-900 dark:text-white mb-6 flex items-center gap-3">
                <div class="w-10 h-10 bg-gradient-to-br from-orange-500 to-red-600 rounded-xl flex items-center justify-center">
                    <i class="fas fa-star text-white"></i>
                </div>
                <span>High Confidence Entities ล่าสุด</span>
            </h3>

            <div class="space-y-3">
                @forelse($highConfidenceEntities as $entity)
                    <div class="flex items-center justify-between p-4 bg-gradient-to-r from-orange-50 to-red-50 dark:from-orange-900/30 dark:to-red-900/30 rounded-xl border border-orange-200 dark:border-orange-700 hover:shadow-lg transition-all">
                        <div>
                            <p class="text-sm font-bold text-gray-900 dark:text-white">{{ $entity->entity_value }}</p>
                            <p class="text-xs text-orange-600 dark:text-orange-400">{{ $entity->entity_type }}</p>
                        </div>
                        <div class="text-right">
                            <p class="text-xl font-black text-orange-600 dark:text-orange-400">
                                {{ round($entity->confidence * 100) }}%
                            </p>
                        </div>
                    </div>
                @empty
                    <div class="text-center py-8">
                        <p class="text-gray-500 dark:text-gray-400">ยังไม่มีข้อมูล</p>
                    </div>
                @endforelse
            </div>
        </div>
    </div>

    {{-- Urgent Intents Alert --}}
    @if($urgentIntents->count() > 0)
    <div class="backdrop-blur-xl bg-gradient-to-r from-red-50/80 to-orange-50/80 dark:from-red-900/30 dark:to-orange-900/30 rounded-2xl shadow-2xl p-6 border-2 border-red-300 dark:border-red-700">
        <h3 class="text-2xl font-black text-red-900 dark:text-red-100 mb-4 flex items-center gap-3">
            <div class="w-12 h-12 bg-gradient-to-br from-red-500 to-orange-600 rounded-xl flex items-center justify-center animate-pulse">
                <i class="fas fa-exclamation-triangle text-white text-xl"></i>
            </div>
            <span>🚨 Urgent Intents</span>
        </h3>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            @foreach($urgentIntents->take(6) as $intent)
                <div class="flex items-center gap-3 p-4 bg-white/50 dark:bg-gray-800/50 rounded-xl">
                    <div class="flex-shrink-0 w-10 h-10 bg-red-500 text-white rounded-lg flex items-center justify-center font-black">
                        !
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-bold text-red-900 dark:text-red-100 truncate">{{ $intent->primary_intent }}</p>
                        <p class="text-xs text-gray-600 dark:text-gray-400">
                            {{ $intent->suggested_department ?? '-' }} | Confidence: {{ round($intent->primary_confidence * 100) }}%
                        </p>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
    @endif
</div>

@push('scripts')
<script>
/**
 * NLP Analysis Component
 *
 * จัดการการแสดงผล NLP overview และ statistics
 */
function nlpAnalysis(initialStats) {
    return {
        stats: initialStats,

        /**
         * เริ่มต้น component
         */
        init() {
            console.log('NLP Analysis initialized', this.stats);
        }
    };
}
</script>

<style>
.perspective-1000 {
    perspective: 1000px;
}

.rotate-y-2 {
    transform: rotateY(2deg);
}
</style>
@endpush
@endsection
