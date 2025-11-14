@extends('layouts.admin')

@section('title', 'ประวัติการทำงานของบอท')

@section('content')
<div class="container mx-auto px-4 py-6">
    {{-- Header พร้อม gradient background --}}
    <div class="relative mb-8 overflow-hidden rounded-2xl bg-gradient-to-br from-violet-500 via-purple-600 to-fuchsia-700 dark:from-violet-900 dark:via-purple-900 dark:to-fuchsia-950 p-8 shadow-2xl">
        {{-- SVG Pattern Background --}}
        <div class="absolute inset-0 bg-[url('data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNjAiIGhlaWdodD0iNjAiIHZpZXdCb3g9IjAgMCA2MCA2MCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48ZyBmaWxsPSJub25lIiBmaWxsLXJ1bGU9ImV2ZW5vZGQiPjxnIGZpbGw9IiNmZmYiIGZpbGwtb3BhY2l0eT0iMC4xIj48cGF0aCBkPSJNMzYgMzBoLTZ2LTZoNnYtNmg2djZoNnY2aC02djZoLTZ2LTZ6Ii8+PC9nPjwvZz48L3N2Zz4=')] opacity-30"></div>

        <div class="relative flex items-center justify-between">
            <div class="flex items-center gap-4">
                <div class="w-16 h-16 rounded-2xl bg-white/20 backdrop-blur-sm flex items-center justify-center border-2 border-white/30 animate-pulse">
                    <svg class="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
                    </svg>
                </div>
                <div>
                    <h1 data-translate class="text-4xl font-bold text-white mb-2">ประวัติการทำงานของบอท</h1>
                    <p data-translate class="text-violet-100 text-lg">ดูรายละเอียดการทำงานและผลลัพธ์ของบอททั้งหมด</p>
                </div>
            </div>

            {{-- Language Switcher --}}
            <div class="flex items-center gap-3">
                <div class="relative inline-block" x-data="{ open: false }">
                    <button @click="open = !open" class="px-4 py-2 bg-white/20 backdrop-blur-sm text-white rounded-xl hover:bg-white/30 transition-all duration-200 border border-white/30 flex items-center gap-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5h12M9 3v2m1.048 9.5A18.022 18.022 0 016.412 9m6.088 9h7M11 21l5-10 5 10M12.751 5C11.783 10.77 8.07 15.61 3 18.129"/>
                        </svg>
                        <span data-translate>ภาษา</span>
                    </button>

                    <div x-show="open" @click.away="open = false" x-transition
                         class="absolute right-0 mt-2 w-48 bg-white dark:bg-slate-800 rounded-xl shadow-2xl border border-gray-200 dark:border-slate-700 overflow-hidden z-50">
                        <a href="/lang/th" class="block px-4 py-3 hover:bg-gray-50 dark:hover:bg-slate-700 transition-colors">
                            <span class="mr-2">🇹🇭</span> <span data-translate>ไทย</span>
                        </a>
                        <a href="/lang/en" class="block px-4 py-3 hover:bg-gray-50 dark:hover:bg-slate-700 transition-colors">
                            <span class="mr-2">🇬🇧</span> English
                        </a>
                        <a href="/lang/zh" class="block px-4 py-3 hover:bg-gray-50 dark:hover:bg-slate-700 transition-colors">
                            <span class="mr-2">🇨🇳</span> 中文
                        </a>
                        <a href="/lang/ja" class="block px-4 py-3 hover:bg-gray-50 dark:hover:bg-slate-700 transition-colors">
                            <span class="mr-2">🇯🇵</span> 日本語
                        </a>
                    </div>
                </div>

                <a href="{{ route('admin.bot-automation.analytics.index') }}"
                   class="px-6 py-3 bg-white/20 backdrop-blur-sm text-white rounded-xl font-semibold hover:bg-white/30 transition-all duration-300 border-2 border-white/30 flex items-center gap-2 shadow-lg hover:shadow-xl">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                    </svg>
                    <span data-translate>กลับไปแดชบอร์ด</span>
                </a>
            </div>
        </div>
    </div>

    {{-- Execution History Card --}}
    <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-xl border border-gray-200 dark:border-slate-700 overflow-hidden">
        <div class="border-b border-gray-200 dark:border-slate-700 bg-gradient-to-r from-violet-50 to-purple-50 dark:from-slate-800 dark:to-slate-800 px-6 py-4">
            <h2 class="text-xl font-bold text-gray-800 dark:text-white flex items-center gap-2">
                <svg class="w-6 h-6 text-violet-600 dark:text-violet-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                ประวัติการทำงาน
            </h2>
        </div>

        <div class="p-6">
            {{-- Filters --}}
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                <div>
                    <label for="filterStatus" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                        กรองตามสถานะ
                    </label>
                    <select id="filterStatus" class="w-full px-4 py-3 rounded-xl border-2 border-gray-200 dark:border-slate-600 bg-white dark:bg-slate-700 text-gray-900 dark:text-gray-100 focus:border-violet-500 dark:focus:border-violet-400 focus:ring-2 focus:ring-violet-200 dark:focus:ring-violet-900/50 transition-all duration-200">
                        <option value="">สถานะทั้งหมด</option>
                        <option value="success">สำเร็จ</option>
                        <option value="failed">ล้มเหลว</option>
                        <option value="pending">รอดำเนินการ</option>
                        <option value="running">กำลังทำงาน</option>
                    </select>
                </div>
                <div>
                    <label for="filterBot" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                        กรองตามบอท
                    </label>
                    <select id="filterBot" class="w-full px-4 py-3 rounded-xl border-2 border-gray-200 dark:border-slate-600 bg-white dark:bg-slate-700 text-gray-900 dark:text-gray-100 focus:border-violet-500 dark:focus:border-violet-400 focus:ring-2 focus:ring-violet-200 dark:focus:ring-violet-900/50 transition-all duration-200">
                        <option value="">บอททั้งหมด</option>
                        @foreach($bots ?? [] as $bot)
                        <option value="{{ $bot->id }}">{{ $bot->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="filterDate" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                        กรองตามวันที่
                    </label>
                    <input type="date" id="filterDate" class="w-full px-4 py-3 rounded-xl border-2 border-gray-200 dark:border-slate-600 bg-white dark:bg-slate-700 text-gray-900 dark:text-gray-100 focus:border-violet-500 dark:focus:border-violet-400 focus:ring-2 focus:ring-violet-200 dark:focus:ring-violet-900/50 transition-all duration-200">
                </div>
            </div>

            {{-- Table --}}
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-slate-700" id="executionsTable">
                    <thead class="bg-gray-50 dark:bg-slate-900">
                        <tr>
                            <th scope="col" class="px-6 py-4 text-left text-xs font-bold text-gray-600 dark:text-gray-300 uppercase tracking-wider">ID</th>
                            <th scope="col" class="px-6 py-4 text-left text-xs font-bold text-gray-600 dark:text-gray-300 uppercase tracking-wider">ชื่อบอท</th>
                            <th scope="col" class="px-6 py-4 text-left text-xs font-bold text-gray-600 dark:text-gray-300 uppercase tracking-wider">สถานะ</th>
                            <th scope="col" class="px-6 py-4 text-left text-xs font-bold text-gray-600 dark:text-gray-300 uppercase tracking-wider">เริ่มเมื่อ</th>
                            <th scope="col" class="px-6 py-4 text-left text-xs font-bold text-gray-600 dark:text-gray-300 uppercase tracking-wider">เสร็จเมื่อ</th>
                            <th scope="col" class="px-6 py-4 text-left text-xs font-bold text-gray-600 dark:text-gray-300 uppercase tracking-wider">ระยะเวลา</th>
                            <th scope="col" class="px-6 py-4 text-left text-xs font-bold text-gray-600 dark:text-gray-300 uppercase tracking-wider">ผลลัพธ์</th>
                            <th scope="col" class="px-6 py-4 text-left text-xs font-bold text-gray-600 dark:text-gray-300 uppercase tracking-wider">จัดการ</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-slate-800 divide-y divide-gray-200 dark:divide-slate-700">
                        @forelse($executions ?? [] as $execution)
                        <tr class="hover:bg-gray-50 dark:hover:bg-slate-700/50 transition-colors duration-200">
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-gray-100">{{ $execution->id }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700 dark:text-gray-300">{{ $execution->bot_name ?? 'N/A' }}</td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                @if($execution->status == 'success')
                                    <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-xs font-semibold bg-emerald-100 dark:bg-emerald-900/30 text-emerald-800 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800">
                                        <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                        </svg>
                                        สำเร็จ
                                    </span>
                                @elseif($execution->status == 'failed')
                                    <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-xs font-semibold bg-red-100 dark:bg-red-900/30 text-red-800 dark:text-red-300 border border-red-200 dark:border-red-800">
                                        <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                                        </svg>
                                        ล้มเหลว
                                    </span>
                                @elseif($execution->status == 'running')
                                    <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-xs font-semibold bg-blue-100 dark:bg-blue-900/30 text-blue-800 dark:text-blue-300 border border-blue-200 dark:border-blue-800">
                                        <svg class="w-3 h-3 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                                        </svg>
                                        กำลังทำงาน
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-xs font-semibold bg-gray-100 dark:bg-gray-800 text-gray-800 dark:text-gray-300 border border-gray-200 dark:border-gray-700">
                                        {{ ucfirst($execution->status) }}
                                    </span>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700 dark:text-gray-300">{{ $execution->started_at ?? 'N/A' }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700 dark:text-gray-300">{{ $execution->completed_at ?? 'N/A' }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700 dark:text-gray-300">{{ $execution->duration ?? '0' }} วินาที</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700 dark:text-gray-300">{{ $execution->result ?? 'N/A' }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm">
                                <button onclick="viewDetails({{ $execution->id }})" class="inline-flex items-center gap-2 px-4 py-2 bg-gradient-to-r from-violet-500 to-purple-600 hover:from-violet-600 hover:to-purple-700 text-white rounded-lg font-medium transition-all duration-200 shadow-md hover:shadow-lg">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                    </svg>
                                    ดู
                                </button>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="8" class="px-6 py-12 text-center text-gray-500 dark:text-gray-400">
                                <div class="flex flex-col items-center justify-center">
                                    <svg class="w-16 h-16 text-gray-400 dark:text-gray-600 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                    </svg>
                                    <p class="text-lg font-medium">ไม่พบบันทึกการทำงาน</p>
                                </div>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- Pagination --}}
            @if(isset($executions) && method_exists($executions, 'links'))
            <div class="mt-6">
                {{ $executions->links() }}
            </div>
            @endif
        </div>
    </div>
</div>
@endsection
