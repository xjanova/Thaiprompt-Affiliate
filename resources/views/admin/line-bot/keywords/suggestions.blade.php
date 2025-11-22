@extends('layouts.admin-v3')

@section('title', 'Keyword Suggestions')

@push('styles')
<style>
    .card-3d{transition:transform .3s,box-shadow .3s}
    .card-3d:hover{transform:translateY(-4px);box-shadow:0 15px 35px rgba(0,0,0,.2)}
    .glass-card{background:rgba(255,255,255,.1);backdrop-filter:blur(10px);border:1px solid rgba(255,255,255,.2)}
    .dark .glass-card{background:rgba(17,24,39,.5);border:1px solid rgba(255,255,255,.1)}
    .pulse-dot{animation:pulse 2s infinite}
    @keyframes pulse{0%,100%{opacity:1}50%{opacity:.5}}
</style>
@endpush

@section('content')
<div class="container-fluid px-4 py-8" x-data="suggestions()">
    {{-- Header --}}
    <div class="mb-8 relative overflow-hidden rounded-2xl bg-gradient-to-r from-purple-500 via-pink-500 to-red-500 p-8">
        <div class="relative z-10 flex justify-between items-center">
            <div>
                <h1 class="text-4xl font-bold text-white mb-2 flex items-center gap-3">
                    <span class="text-5xl">💡</span>
                    Keyword Suggestions
                </h1>
                <p class="text-white/90 text-lg">AI วิเคราะห์และเสนอ keywords ใหม่ที่ควรสร้าง</p>
            </div>
            <div class="flex gap-2">
                <button @click="refreshSuggestions()" class="px-6 py-3 bg-white/20 hover:bg-white/30 backdrop-blur-md text-white rounded-xl font-medium transition-all">
                    🔄 Refresh
                </button>
                <a href="{{ route('admin.line-bot.keywords.suggestions.export') }}" class="px-6 py-3 bg-white/20 hover:bg-white/30 backdrop-blur-md text-white rounded-xl font-medium transition-all">
                    📥 Export
                </a>
            </div>
        </div>
        <div class="absolute top-0 right-0 w-64 h-64 bg-white/10 rounded-full -mr-32 -mt-32"></div>
    </div>

    {{-- Recommendations --}}
    @if($recommendations)
    <div class="mb-8">
        <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
            <span>🎯</span>
            Recommendations
        </h2>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            @foreach($recommendations as $rec)
            <div class="glass-card rounded-xl p-5 border-l-4 @if($rec['type']==='urgent') border-red-500 @elseif($rec['type']==='opportunity') border-green-500 @else border-blue-500 @endif">
                <div class="flex items-start gap-3">
                    <span class="text-3xl">{{ $rec['type'] === 'urgent' ? '🚨' : ($rec['type'] === 'opportunity' ? '✨' : 'ℹ️') }}</span>
                    <div>
                        <h3 class="font-bold text-gray-900 dark:text-white mb-1">{{ $rec['message'] }}</h3>
                        <p class="text-sm text-gray-600 dark:text-gray-400">{{ $rec['action'] }}</p>
                    </div>
                </div>
            </div>
            @endforeach
        </div>
    </div>
    @endif

    {{-- Statistics --}}
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-6 mb-8">
        <div class="card-3d bg-white dark:bg-gray-800 rounded-xl shadow-xl p-6 border border-gray-200 dark:border-gray-700">
            <div class="flex items-center justify-between mb-3">
                <div class="w-12 h-12 bg-gradient-to-br from-red-400 to-red-600 rounded-lg flex items-center justify-center text-2xl">❌</div>
                <span class="text-xs font-bold text-gray-500 dark:text-gray-400 uppercase">No Match</span>
            </div>
            <p class="text-3xl font-bold text-red-600 dark:text-red-400">{{ $statistics['total_no_matches'] }}</p>
            <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">ข้อความที่ไม่ match</p>
        </div>

        <div class="card-3d bg-white dark:bg-gray-800 rounded-xl shadow-xl p-6 border border-gray-200 dark:border-gray-700">
            <div class="flex items-center justify-between mb-3">
                <div class="w-12 h-12 bg-gradient-to-br from-orange-400 to-orange-600 rounded-lg flex items-center justify-center text-2xl">🔍</div>
                <span class="text-xs font-bold text-gray-500 dark:text-gray-400 uppercase">Unique</span>
            </div>
            <p class="text-3xl font-bold text-orange-600 dark:text-orange-400">{{ $statistics['unique_no_matches'] }}</p>
            <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">ข้อความไม่ซ้ำ</p>
        </div>

        <div class="card-3d bg-white dark:bg-gray-800 rounded-xl shadow-xl p-6 border border-gray-200 dark:border-gray-700">
            <div class="flex items-center justify-between mb-3">
                <div class="w-12 h-12 bg-gradient-to-br from-green-400 to-green-600 rounded-lg flex items-center justify-center text-2xl">💡</div>
                <span class="text-xs font-bold text-gray-500 dark:text-gray-400 uppercase">Suggestions</span>
            </div>
            <p class="text-3xl font-bold text-green-600 dark:text-green-400">{{ $statistics['suggestions_count'] }}</p>
            <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">คำแนะนำ</p>
        </div>

        <div class="card-3d bg-white dark:bg-gray-800 rounded-xl shadow-xl p-6 border border-gray-200 dark:border-gray-700">
            <div class="flex items-center justify-between mb-3">
                <div class="w-12 h-12 bg-gradient-to-br from-blue-400 to-blue-600 rounded-lg flex items-center justify-center text-2xl">🔑</div>
                <span class="text-xs font-bold text-gray-500 dark:text-gray-400 uppercase">Existing</span>
            </div>
            <p class="text-3xl font-bold text-blue-600 dark:text-blue-400">{{ $statistics['existing_keywords'] }}</p>
            <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">Keywords ปัจจุบัน</p>
        </div>

        <div class="card-3d bg-white dark:bg-gray-800 rounded-xl shadow-xl p-6 border border-gray-200 dark:border-gray-700">
            <div class="flex items-center justify-between mb-3">
                <div class="w-12 h-12 bg-gradient-to-br from-purple-400 to-purple-600 rounded-lg flex items-center justify-center text-2xl">📈</div>
                <span class="text-xs font-bold text-gray-500 dark:text-gray-400 uppercase">Coverage+</span>
            </div>
            <p class="text-3xl font-bold text-purple-600 dark:text-purple-400">{{ $statistics['potential_coverage_increase'] }}%</p>
            <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">เพิ่มขึ้นได้</p>
        </div>
    </div>

    {{-- Filters --}}
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-6 mb-8 border border-gray-200 dark:border-gray-700">
        <form method="GET" action="{{ route('admin.line-bot.keywords.suggestions.index') }}" class="flex gap-4 items-end">
            <div class="flex-1">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Analysis Period</label>
                <select name="days" x-model="filters.days" class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-line-green">
                    <option value="7" @selected($days==7)>7 วัน</option>
                    <option value="14" @selected($days==14)>14 วัน</option>
                    <option value="30" @selected($days==30)>30 วัน</option>
                    <option value="60" @selected($days==60)>60 วัน</option>
                    <option value="90" @selected($days==90)>90 วัน</option>
                </select>
            </div>

            <div class="flex-1">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Min Frequency</label>
                <select name="min_frequency" x-model="filters.minFreq" class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-line-green">
                    <option value="1" @selected($minFrequency==1)>1+ ครั้ง</option>
                    <option value="2" @selected($minFrequency==2)>2+ ครั้ง</option>
                    <option value="3" @selected($minFrequency==3)>3+ ครั้ง</option>
                    <option value="5" @selected($minFrequency==5)>5+ ครั้ง</option>
                    <option value="10" @selected($minFrequency==10)>10+ ครั้ง</option>
                </select>
            </div>

            <button type="submit" class="px-6 py-2.5 bg-gradient-to-r from-line-green to-green-600 hover:from-green-600 hover:to-line-green text-white rounded-lg font-medium transition-all transform hover:scale-105 shadow-lg">
                🔍 Filter
            </button>
        </form>
    </div>

    {{-- Suggestions Table --}}
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl overflow-hidden border border-gray-200 dark:border-gray-700">
        <div class="p-6 border-b border-gray-200 dark:border-gray-700">
            <h2 class="text-xl font-bold text-gray-900 dark:text-white flex items-center gap-2">
                <span class="pulse-dot">💡</span>
                {{ count($suggestions) }} Suggestions Available
            </h2>
        </div>

        @if(count($suggestions) > 0)
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gradient-to-r from-gray-50 to-gray-100 dark:from-gray-700 dark:to-gray-600">
                    <tr>
                        <th class="px-6 py-4 text-left text-xs font-bold text-gray-700 dark:text-gray-300 uppercase">Keyword</th>
                        <th class="px-6 py-4 text-left text-xs font-bold text-gray-700 dark:text-gray-300 uppercase">Trigger Words</th>
                        <th class="px-6 py-4 text-left text-xs font-bold text-gray-700 dark:text-gray-300 uppercase">Freq</th>
                        <th class="px-6 py-4 text-left text-xs font-bold text-gray-700 dark:text-gray-300 uppercase">Confidence</th>
                        <th class="px-6 py-4 text-left text-xs font-bold text-gray-700 dark:text-gray-300 uppercase">Samples</th>
                        <th class="px-6 py-4 text-right text-xs font-bold text-gray-700 dark:text-gray-300 uppercase">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @foreach($suggestions as $sug)
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition">
                        <td class="px-6 py-4 font-bold text-gray-900 dark:text-white">{{ $sug['keyword'] }}</td>
                        <td class="px-6 py-4">
                            <div class="flex flex-wrap gap-1">
                                @foreach($sug['trigger_words'] as $word)
                                <span class="px-2 py-1 bg-blue-100 dark:bg-blue-900 text-blue-800 dark:text-blue-200 rounded text-xs font-medium">{{ $word }}</span>
                                @endforeach
                            </div>
                        </td>
                        <td class="px-6 py-4"><span class="px-3 py-1 bg-green-100 dark:bg-green-900 text-green-800 dark:text-green-200 rounded-full text-sm font-bold">{{ $sug['frequency'] }}</span></td>
                        <td class="px-6 py-4">
                            <div class="flex items-center gap-2">
                                <div class="w-20 bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                                    <div class="bg-gradient-to-r from-blue-400 to-blue-600 h-2 rounded-full" style="width:{{ min($sug['confidence'],100) }}%"></div>
                                </div>
                                <span class="text-xs font-bold">{{ round($sug['confidence'],0) }}%</span>
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            <div class="space-y-1 max-w-xs">
                                @foreach($sug['sample_messages'] as $sample)
                                <p class="text-xs text-gray-600 dark:text-gray-400 truncate" title="{{ $sample }}">"{{ \Str::limit($sample,40) }}"</p>
                                @endforeach
                            </div>
                        </td>
                        <td class="px-6 py-4 text-right">
                            <button @click="approve('{{ $sug['keyword'] }}',{{ json_encode($sug['trigger_words']) }})" class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg text-sm font-medium transition-all transform hover:scale-105">
                                ✅ Approve
                            </button>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @else
        <div class="p-16 text-center">
            <div class="text-6xl mb-4">✅</div>
            <p class="text-xl text-gray-500 dark:text-gray-400 font-medium">ไม่มี suggestions ใหม่</p>
            <p class="text-sm text-gray-400 dark:text-gray-500 mt-2">ระบบ keywords ของคุณมีการครอบคลุมที่ดีแล้ว!</p>
        </div>
        @endif
    </div>
</div>

@push('scripts')
<script>
function suggestions(){
    return{
        filters:{days:'{{ $days }}',minFreq:'{{ $minFrequency }}'},
        approve(keyword,triggers){
            const url='{{ route("admin.line-bot.keywords.create") }}?keyword='+keyword+'&trigger_words='+encodeURIComponent(JSON.stringify(triggers))+'&category=custom&priority=50';
            window.location.href=url;
        },
        refreshSuggestions(){
            fetch('{{ route("admin.line-bot.keywords.suggestions.refresh") }}?days='+this.filters.days+'&min_frequency='+this.filters.minFreq)
                .then(r=>r.json())
                .then(data=>{if(data.success)location.reload()});
        }
    }
}
</script>
@endpush
@endsection
