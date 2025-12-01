@extends('layouts.admin-v3')

@section('title', 'Cloudflare Page Rules')

@section('content')
<div class="container-fluid px-4 py-6">
    <!-- Header -->
    <div class="relative mb-8 overflow-hidden rounded-2xl bg-gradient-to-br from-cyan-500 via-blue-600 to-indigo-600 dark:from-cyan-600 dark:via-blue-700 dark:to-indigo-700 p-8 shadow-2xl">
        <div class="absolute inset-0 bg-[url('data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNjAiIGhlaWdodD0iNjAiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyI+PGRlZnM+PHBhdHRlcm4gaWQ9ImdyaWQiIHdpZHRoPSI2MCIgaGVpZ2h0PSI2MCIgcGF0dGVyblVuaXRzPSJ1c2VyU3BhY2VPblVzZSI+PHBhdGggZD0iTSAxMCAwIEwgMCAwIDAgMTAiIGZpbGw9Im5vbmUiIHN0cm9rZT0id2hpdGUiIHN0cm9rZS1vcGFjaXR5PSIwLjEiIHN0cm9rZS13aWR0aD0iMSIvPjwvcGF0dGVybj48L2RlZnM+PHJlY3Qgd2lkdGg9IjEwMCUiIGhlaWdodD0iMTAwJSIgZmlsbD0idXJsKCNncmlkKSIvPjwvc3ZnPg==')] opacity-30"></div>
        <div class="relative flex items-center">
            <a href="{{ route('admin.cloudflare.index') }}" class="w-10 h-10 rounded-lg bg-white/20 flex items-center justify-center hover:bg-white/30 transition-colors mr-3">
                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
            </a>
            <div class="w-14 h-14 rounded-xl backdrop-blur-sm flex items-center justify-center bg-white/20 mr-4">
                <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
            </div>
            <div>
                <h1 class="text-3xl font-bold text-white mb-1">Page Rules</h1>
                <p class="text-cyan-100">กฎการทำงานเฉพาะ URL (ดูอย่างเดียว)</p>
            </div>
        </div>
    </div>

    @if(!$isConfigured)
    <div class="mb-6 bg-yellow-50 dark:bg-yellow-900/30 border-l-4 border-yellow-500 p-4 rounded-xl">
        <p class="text-yellow-800 dark:text-yellow-200">ยังไม่ได้ตั้งค่า Cloudflare API</p>
    </div>
    @endif

    <!-- Page Rules Table -->
    <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-lg border border-gray-100 dark:border-slate-700 overflow-hidden">
        <div class="p-4 border-b border-gray-100 dark:border-slate-700">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Page Rules ({{ count($pageRules) }})</h3>
        </div>

        @if(count($pageRules) > 0)
        <div class="divide-y divide-gray-100 dark:divide-slate-700">
            @foreach($pageRules as $rule)
            <div class="p-4 hover:bg-gray-50 dark:hover:bg-slate-700/50 transition-colors">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-sm font-medium text-gray-900 dark:text-white">{{ $rule['targets'][0]['constraint']['value'] ?? '-' }}</span>
                    <span class="px-2 py-1 text-xs font-medium rounded-full {{ ($rule['status'] ?? '') === 'active' ? 'bg-green-100 text-green-800 dark:bg-green-900/50 dark:text-green-300' : 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300' }}">
                        {{ $rule['status'] ?? 'unknown' }}
                    </span>
                </div>
                <div class="flex flex-wrap gap-2">
                    @foreach($rule['actions'] ?? [] as $action)
                    <span class="px-2 py-1 text-xs bg-blue-100 dark:bg-blue-900/50 text-blue-800 dark:text-blue-300 rounded">
                        {{ $action['id'] }}: {{ is_array($action['value'] ?? '') ? json_encode($action['value']) : ($action['value'] ?? 'on') }}
                    </span>
                    @endforeach
                </div>
            </div>
            @endforeach
        </div>
        @else
        <div class="p-12 text-center">
            <svg class="w-12 h-12 mx-auto text-gray-400 dark:text-gray-600 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
            </svg>
            <p class="text-gray-500 dark:text-gray-400">ไม่มี Page Rules</p>
        </div>
        @endif
    </div>
</div>
@endsection
