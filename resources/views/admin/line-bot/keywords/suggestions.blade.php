@extends('layouts.admin')

@section('title', 'Keyword Suggestions')

@section('content')
<div class="container-fluid px-4 py-8">
    {{-- Page Header --}}
    <div class="flex justify-between items-center mb-8">
        <div>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white mb-2">
                💡 Keyword Suggestions
            </h1>
            <p class="text-gray-600 dark:text-gray-400">
                AI วิเคราะห์ข้อความและเสนอ keywords ใหม่ที่ควรสร้าง
            </p>
        </div>
        <div class="flex gap-2">
            <button onclick="refreshSuggestions()"
                class="px-4 py-2 bg-blue-600 hover:bg-blue-700 dark:bg-blue-500 dark:hover:bg-blue-600 text-white rounded-lg transition">
                🔄 Refresh
            </button>
            <a href="{{ route('admin.line-bot.keywords.suggestions.export') }}"
                class="px-4 py-2 bg-green-600 hover:bg-green-700 dark:bg-green-500 dark:hover:bg-green-600 text-white rounded-lg transition">
                📥 Export JSON
            </a>
        </div>
    </div>

    {{-- Recommendations Cards --}}
    @if($recommendations)
    <div class="mb-8">
        <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4">🎯 Recommendations</h2>
        <div class="space-y-3">
            @foreach($recommendations as $rec)
            <div class="bg-gradient-to-r
                @if($rec['type'] === 'urgent') from-red-50 to-red-100 dark:from-red-900 dark:to-red-800 border-l-4 border-red-600
                @elseif($rec['type'] === 'opportunity') from-green-50 to-green-100 dark:from-green-900 dark:to-green-800 border-l-4 border-green-600
                @else from-blue-50 to-blue-100 dark:from-blue-900 dark:to-blue-800 border-l-4 border-blue-600
                @endif
                p-4 rounded-lg">
                <h3 class="font-semibold text-gray-900 dark:text-white">{{ $rec['message'] }}</h3>
                <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">{{ $rec['action'] }}</p>
            </div>
            @endforeach
        </div>
    </div>
    @endif

    {{-- Statistics Cards --}}
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4 mb-8">
        {{-- Total No Matches --}}
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-600 dark:text-gray-400 text-sm">No-match Messages</p>
                    <p class="text-3xl font-bold text-red-600 dark:text-red-400 mt-2">
                        {{ $statistics['total_no_matches'] }}
                    </p>
                </div>
                <div class="w-12 h-12 bg-red-100 dark:bg-red-900 rounded-lg flex items-center justify-center text-2xl">
                    ❌
                </div>
            </div>
        </div>

        {{-- Unique No Matches --}}
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-600 dark:text-gray-400 text-sm">Unique Messages</p>
                    <p class="text-3xl font-bold text-orange-600 dark:text-orange-400 mt-2">
                        {{ $statistics['unique_no_matches'] }}
                    </p>
                </div>
                <div class="w-12 h-12 bg-orange-100 dark:bg-orange-900 rounded-lg flex items-center justify-center text-2xl">
                    🔍
                </div>
            </div>
        </div>

        {{-- Suggestions Count --}}
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-600 dark:text-gray-400 text-sm">Suggestions</p>
                    <p class="text-3xl font-bold text-green-600 dark:text-green-400 mt-2">
                        {{ $statistics['suggestions_count'] }}
                    </p>
                </div>
                <div class="w-12 h-12 bg-green-100 dark:bg-green-900 rounded-lg flex items-center justify-center text-2xl">
                    💡
                </div>
            </div>
        </div>

        {{-- Existing Keywords --}}
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-600 dark:text-gray-400 text-sm">Existing Keywords</p>
                    <p class="text-3xl font-bold text-blue-600 dark:text-blue-400 mt-2">
                        {{ $statistics['existing_keywords'] }}
                    </p>
                </div>
                <div class="w-12 h-12 bg-blue-100 dark:bg-blue-900 rounded-lg flex items-center justify-center text-2xl">
                    🔑
                </div>
            </div>
        </div>

        {{-- Coverage Increase Potential --}}
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-600 dark:text-gray-400 text-sm">Coverage Increase</p>
                    <p class="text-3xl font-bold text-purple-600 dark:text-purple-400 mt-2">
                        {{ $statistics['potential_coverage_increase'] }}%
                    </p>
                </div>
                <div class="w-12 h-12 bg-purple-100 dark:bg-purple-900 rounded-lg flex items-center justify-center text-2xl">
                    📈
                </div>
            </div>
        </div>
    </div>

    {{-- Filters --}}
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6 mb-8">
        <form method="GET" action="{{ route('admin.line-bot.keywords.suggestions.index') }}" class="flex gap-4 items-end">
            <div class="flex-1">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    Analysis Period (Days)
                </label>
                <select name="days" class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg
                    bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                    <option value="7" @selected($days == 7)>Last 7 Days</option>
                    <option value="14" @selected($days == 14)>Last 14 Days</option>
                    <option value="30" @selected($days == 30)>Last 30 Days</option>
                    <option value="60" @selected($days == 60)>Last 60 Days</option>
                    <option value="90" @selected($days == 90)>Last 90 Days</option>
                </select>
            </div>

            <div class="flex-1">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    Minimum Frequency
                </label>
                <select name="min_frequency" class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg
                    bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                    <option value="1" @selected($minFrequency == 1)>1+ occurrences</option>
                    <option value="2" @selected($minFrequency == 2)>2+ occurrences</option>
                    <option value="3" @selected($minFrequency == 3)>3+ occurrences</option>
                    <option value="5" @selected($minFrequency == 5)>5+ occurrences</option>
                    <option value="10" @selected($minFrequency == 10)>10+ occurrences</option>
                </select>
            </div>

            <button type="submit" class="px-6 py-2 bg-blue-600 hover:bg-blue-700 dark:bg-blue-500
                dark:hover:bg-blue-600 text-white rounded-lg transition">
                🔍 Filter
            </button>
        </form>
    </div>

    {{-- Suggestions Table --}}
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow overflow-hidden">
        <div class="p-6 border-b border-gray-200 dark:border-gray-700">
            <h2 class="text-xl font-bold text-gray-900 dark:text-white">
                {{ count($suggestions) }} Suggestions Available
            </h2>
        </div>

        @if(count($suggestions) > 0)
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50 dark:bg-gray-700">
                    <tr>
                        <th class="px-6 py-4 text-left text-sm font-semibold text-gray-900 dark:text-white">
                            Keyword
                        </th>
                        <th class="px-6 py-4 text-left text-sm font-semibold text-gray-900 dark:text-white">
                            Trigger Words
                        </th>
                        <th class="px-6 py-4 text-left text-sm font-semibold text-gray-900 dark:text-white">
                            Frequency
                        </th>
                        <th class="px-6 py-4 text-left text-sm font-semibold text-gray-900 dark:text-white">
                            Confidence
                        </th>
                        <th class="px-6 py-4 text-left text-sm font-semibold text-gray-900 dark:text-white">
                            Sample Messages
                        </th>
                        <th class="px-6 py-4 text-right text-sm font-semibold text-gray-900 dark:text-white">
                            Actions
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @foreach($suggestions as $suggestion)
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700 transition">
                        <td class="px-6 py-4 text-sm font-semibold text-gray-900 dark:text-white">
                            {{ $suggestion['keyword'] }}
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-400">
                            @foreach($suggestion['trigger_words'] as $word)
                                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium
                                    bg-blue-100 dark:bg-blue-900 text-blue-800 dark:text-blue-200 mr-1">
                                    {{ $word }}
                                </span>
                            @endforeach
                        </td>
                        <td class="px-6 py-4 text-sm">
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-bold
                                bg-green-100 dark:bg-green-900 text-green-800 dark:text-green-200">
                                {{ $suggestion['frequency'] }}
                            </span>
                        </td>
                        <td class="px-6 py-4 text-sm">
                            <div class="flex items-center gap-2">
                                <div class="w-20 bg-gray-200 dark:bg-gray-600 rounded-full h-2">
                                    <div class="bg-gradient-to-r from-blue-400 to-blue-600 h-2 rounded-full"
                                        style="width: {{ min($suggestion['confidence'], 100) }}%"></div>
                                </div>
                                <span class="text-xs font-bold">{{ round($suggestion['confidence'], 0) }}%</span>
                            </div>
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-400">
                            <div class="space-y-1 max-w-xs">
                                @foreach($suggestion['sample_messages'] as $sample)
                                    <p class="text-xs truncate" title="{{ $sample }}">
                                        "{{ \Str::limit($sample, 40) }}"
                                    </p>
                                @endforeach
                            </div>
                        </td>
                        <td class="px-6 py-4 text-right text-sm">
                            <button onclick="approveSuggestion('{{ $suggestion['keyword'] }}', {{ json_encode($suggestion['trigger_words']) }}, this)"
                                class="px-3 py-2 bg-green-600 hover:bg-green-700 text-white rounded transition text-xs font-medium">
                                ✅ Approve
                            </button>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @else
        <div class="p-12 text-center text-gray-500 dark:text-gray-400">
            <p class="text-lg">✅ ไม่มี suggestions ใหม่</p>
            <p class="text-sm mt-2">ระบบ keywords ของคุณมีการครอบคลุมที่ดี!</p>
        </div>
        @endif
    </div>
</div>

<script>
    function approveSuggestion(keyword, triggerWords, button) {
        // สำหรับ MVP ให้ redirect ไปที่สร้าง keyword ด้วยค่าที่เสนอ
        const triggerWordsJson = JSON.stringify(triggerWords);
        const url = `{{ route('admin.line-bot.keywords.create') }}?` +
                   `keyword=${keyword}` +
                   `&trigger_words=${encodeURIComponent(triggerWordsJson)}` +
                   `&category=custom` +
                   `&priority=50`;

        window.location.href = url;
    }

    function refreshSuggestions() {
        const days = document.querySelector('select[name="days"]').value;
        const minFrequency = document.querySelector('select[name="min_frequency"]').value;

        fetch('{{ route("admin.line-bot.keywords.suggestions.refresh") }}' +
              `?days=${days}&min_frequency=${minFrequency}`)
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                }
            });
    }
</script>
@endsection
