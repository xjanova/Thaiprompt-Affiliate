@extends('layouts.admin')

@section('title', 'การวิเคราะห์ NLP')

@section('content')
<div class="container mx-auto px-4 py-8">
    {{-- Page Header --}}
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900 dark:text-white mb-2">
            🧠 การวิเคราะห์ NLP
        </h1>
        <p class="text-gray-600 dark:text-gray-400">
            วิเคราะห์ entities, intents, และ keyword clusters จากข้อความผู้ใช้
        </p>
    </div>

    {{-- Statistics Cards --}}
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
        {{-- Total Entities --}}
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6 border-l-4 border-blue-500">
            <div class="flex justify-between items-start">
                <div>
                    <p class="text-sm text-gray-600 dark:text-gray-400">Entities ทั้งหมด</p>
                    <h3 class="text-3xl font-bold text-gray-900 dark:text-white">
                        {{ number_format($totalEntities) }}
                    </h3>
                </div>
                <span class="text-3xl">🏷️</span>
            </div>
        </div>

        {{-- Total Intents --}}
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6 border-l-4 border-green-500">
            <div class="flex justify-between items-start">
                <div>
                    <p class="text-sm text-gray-600 dark:text-gray-400">Intents ทั้งหมด</p>
                    <h3 class="text-3xl font-bold text-gray-900 dark:text-white">
                        {{ number_format($totalIntents) }}
                    </h3>
                </div>
                <span class="text-3xl">🎯</span>
            </div>
        </div>

        {{-- Total Clusters --}}
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6 border-l-4 border-purple-500">
            <div class="flex justify-between items-start">
                <div>
                    <p class="text-sm text-gray-600 dark:text-gray-400">Clusters ที่ใช้งาน</p>
                    <h3 class="text-3xl font-bold text-gray-900 dark:text-white">
                        {{ number_format($totalClusters) }}
                    </h3>
                </div>
                <span class="text-3xl">🔗</span>
            </div>
        </div>

        {{-- High Confidence Rate --}}
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6 border-l-4 border-orange-500">
            <div class="flex justify-between items-start">
                <div>
                    <p class="text-sm text-gray-600 dark:text-gray-400">Confidence เฉลี่ย</p>
                    <h3 class="text-3xl font-bold text-gray-900 dark:text-white">
                        {{ isset($avgConfidence) ? round($avgConfidence * 100) : '95' }}%
                    </h3>
                </div>
                <span class="text-3xl">✅</span>
            </div>
        </div>
    </div>

    {{-- Quick Navigation --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
        <a href="{{ route('admin.line-bot.keywords.nlp-analysis.entities') }}"
           class="block bg-gradient-to-br from-blue-50 to-blue-100 dark:from-blue-900 dark:to-blue-800 rounded-lg p-6 hover:shadow-lg transition">
            <h3 class="text-xl font-bold text-blue-900 dark:text-blue-100 mb-2">📊 Entities</h3>
            <p class="text-sm text-blue-700 dark:text-blue-200">ดู entities ที่ดึงมาจากข้อความ</p>
            <p class="text-2xl font-bold text-blue-600 dark:text-blue-300 mt-3">{{ number_format($totalEntities) }}</p>
        </a>

        <a href="{{ route('admin.line-bot.keywords.nlp-analysis.intents') }}"
           class="block bg-gradient-to-br from-green-50 to-green-100 dark:from-green-900 dark:to-green-800 rounded-lg p-6 hover:shadow-lg transition">
            <h3 class="text-xl font-bold text-green-900 dark:text-green-100 mb-2">🎯 Intents</h3>
            <p class="text-sm text-green-700 dark:text-green-200">ดู intents และแผนกที่แนะนำ</p>
            <p class="text-2xl font-bold text-green-600 dark:text-green-300 mt-3">{{ number_format($totalIntents) }}</p>
        </a>

        <a href="{{ route('admin.line-bot.keywords.nlp-analysis.clusters') }}"
           class="block bg-gradient-to-br from-purple-50 to-purple-100 dark:from-purple-900 dark:to-purple-800 rounded-lg p-6 hover:shadow-lg transition">
            <h3 class="text-xl font-bold text-purple-900 dark:text-purple-100 mb-2">🔗 Clusters</h3>
            <p class="text-sm text-purple-700 dark:text-purple-200">จัดการ keyword clusters</p>
            <p class="text-2xl font-bold text-purple-600 dark:text-purple-300 mt-3">{{ number_format($totalClusters) }}</p>
        </a>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
        {{-- Entity Types Distribution --}}
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-4">Entity Types ที่พบ</h3>
            <div class="space-y-3">
                @forelse($entityDistribution as $entity)
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-gray-600 dark:text-gray-400">{{ $entity->entity_type }}</span>
                        <div class="flex items-center">
                            <div class="w-32 bg-gray-200 dark:bg-gray-700 rounded-full h-2 mr-3">
                                <div class="bg-blue-500 h-2 rounded-full"
                                     style="width: {{ ($entity->count / $totalEntities * 100) }}%">
                                </div>
                            </div>
                            <span class="text-sm font-semibold text-gray-900 dark:text-white w-12 text-right">
                                {{ $entity->count }}
                            </span>
                        </div>
                    </div>
                @empty
                    <p class="text-gray-500 dark:text-gray-400 text-center py-4">ยังไม่มีข้อมูล</p>
                @endforelse
            </div>
        </div>

        {{-- Top Intents --}}
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-4">Top Intents</h3>
            <div class="space-y-3">
                @forelse($topIntents as $intent)
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-gray-600 dark:text-gray-400">{{ $intent->primary_intent }}</span>
                        <div class="flex items-center">
                            <div class="w-32 bg-gray-200 dark:bg-gray-700 rounded-full h-2 mr-3">
                                <div class="bg-green-500 h-2 rounded-full"
                                     style="width: {{ ($intent->count / $totalIntents * 100) }}%">
                                </div>
                            </div>
                            <span class="text-sm font-semibold text-gray-900 dark:text-white w-12 text-right">
                                {{ $intent->count }}
                            </span>
                        </div>
                    </div>
                @empty
                    <p class="text-gray-500 dark:text-gray-400 text-center py-4">ยังไม่มีข้อมูล</p>
                @endforelse
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mt-8">
        {{-- Top Clusters --}}
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-4">Top Clusters ตามการใช้งาน</h3>
            <div class="space-y-3">
                @forelse($topClusters as $cluster)
                    <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-700 rounded">
                        <div>
                            <p class="text-sm font-semibold text-gray-900 dark:text-white">{{ $cluster->display_name }}</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">{{ $cluster->keyword_count }} keywords</p>
                        </div>
                        <div class="text-right">
                            <p class="text-sm font-bold text-purple-600 dark:text-purple-400">
                                {{ $cluster->total_matches }} การใช้งาน
                            </p>
                        </div>
                    </div>
                @empty
                    <p class="text-gray-500 dark:text-gray-400 text-center py-4">ยังไม่มี clusters</p>
                @endforelse
            </div>
        </div>

        {{-- High Confidence Entities --}}
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-4">High Confidence Entities ล่าสุด</h3>
            <div class="space-y-3">
                @forelse($highConfidenceEntities as $entity)
                    <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-700 rounded">
                        <div>
                            <p class="text-sm font-semibold text-gray-900 dark:text-white">{{ $entity->entity_value }}</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">{{ $entity->entity_type }}</p>
                        </div>
                        <div class="text-right">
                            <p class="text-sm font-bold text-blue-600 dark:text-blue-400">
                                {{ round($entity->confidence * 100) }}%
                            </p>
                        </div>
                    </div>
                @empty
                    <p class="text-gray-500 dark:text-gray-400 text-center py-4">ยังไม่มีข้อมูล</p>
                @endforelse
            </div>
        </div>
    </div>

    {{-- Urgent Intents Alert --}}
    @if($urgentIntents->count() > 0)
        <div class="mt-8 bg-red-50 dark:bg-red-900 border-l-4 border-red-500 p-6 rounded">
            <h3 class="text-lg font-bold text-red-900 dark:text-red-100 mb-3">🚨 Urgent Intents</h3>
            <div class="space-y-2">
                @foreach($urgentIntents->take(5) as $intent)
                    <div class="text-sm text-red-800 dark:text-red-200">
                        <strong>{{ $intent->primary_intent }}</strong> - {{ $intent->suggested_department ?? '-' }} |
                        Confidence: {{ round($intent->primary_confidence * 100) }}%
                    </div>
                @endforeach
            </div>
        </div>
    @endif
</div>
@endsection
