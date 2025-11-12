@extends('layouts.app')

@section('title', 'Trading Bot Dashboard')

@section('content')
<div class="container mx-auto px-4 py-8">
    <!-- Header -->
    <div class="flex justify-between items-center mb-8">
        <h1 class="text-3xl font-bold text-gray-900 dark:text-white">🤖 Trading Bot Dashboard</h1>
        @if($subscription)
        <a href="{{ route('trading-bot.create') }}" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-6 rounded-lg transition-colors">
            + สร้างบอทใหม่
        </a>
        @endif
    </div>

    <!-- Subscription Status -->
    @if($subscription)
    <div class="bg-gradient-to-r from-blue-500 to-purple-600 rounded-lg p-6 mb-8 text-white">
        <div class="flex justify-between items-center">
            <div>
                <h2 class="text-2xl font-bold mb-2">{{ $subscription->package->name }}</h2>
                <p class="opacity-90">สถานะ: {{ $subscription->status === 'active' ? 'ใช้งานอยู่' : 'หมดอายุ' }}</p>
                @if($subscription->expires_at)
                <p class="opacity-90">หมดอายุ: {{ $subscription->expires_at->format('d/m/Y') }}</p>
                @endif
            </div>
            <div class="text-right">
                <div class="text-3xl font-bold">{{ $bots->count() }}/{{ $subscription->package->max_bots == 999 ? '∞' : $subscription->package->max_bots }}</div>
                <p class="opacity-90">บอทที่ใช้งาน</p>
            </div>
        </div>
    </div>
    @else
    <div class="bg-yellow-50 dark:bg-yellow-900/20 border-l-4 border-yellow-400 p-6 mb-8">
        <div class="flex">
            <div class="flex-shrink-0">
                <svg class="h-5 w-5 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                </svg>
            </div>
            <div class="ml-3">
                <p class="text-sm text-yellow-700 dark:text-yellow-400">
                    คุณยังไม่ได้สมัครแพคเกจ กรุณาสมัครแพคเกจเพื่อเริ่มใช้งานบอทเทรด
                    <a href="{{ route('trading-bot.marketplace') }}" class="font-bold underline">คลิกที่นี่เพื่อสมัคร</a>
                </p>
            </div>
        </div>
    </div>
    @endif

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
        <div class="bg-white dark:bg-gray-800 rounded-lg p-6 shadow-lg">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-500 dark:text-gray-400 text-sm">บอททั้งหมด</p>
                    <p class="text-3xl font-bold text-gray-900 dark:text-white">{{ $stats['total_bots'] }}</p>
                </div>
                <div class="text-4xl">🤖</div>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-lg p-6 shadow-lg">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-500 dark:text-gray-400 text-sm">กำลังทำงาน</p>
                    <p class="text-3xl font-bold text-green-600">{{ $stats['running_bots'] }}</p>
                </div>
                <div class="text-4xl">✅</div>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-lg p-6 shadow-lg">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-500 dark:text-gray-400 text-sm">กำไร/ขาดทุน</p>
                    <p class="text-3xl font-bold {{ $stats['total_profit'] >= 0 ? 'text-green-600' : 'text-red-600' }}">
                        {{ $stats['total_profit'] >= 0 ? '+' : '' }}฿{{ number_format($stats['total_profit'], 2) }}
                    </p>
                </div>
                <div class="text-4xl">💰</div>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-lg p-6 shadow-lg">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-500 dark:text-gray-400 text-sm">จำนวนเทรด</p>
                    <p class="text-3xl font-bold text-gray-900 dark:text-white">{{ $stats['total_trades'] }}</p>
                </div>
                <div class="text-4xl">📊</div>
            </div>
        </div>
    </div>

    <!-- Bots List -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg overflow-hidden mb-8">
        <div class="p-6 border-b border-gray-200 dark:border-gray-700">
            <h2 class="text-xl font-bold text-gray-900 dark:text-white">บอทของคุณ</h2>
        </div>

        @if($bots->count() > 0)
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50 dark:bg-gray-900">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">ชื่อบอท</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Trading Pair</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Exchange</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">กลยุทธ์</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">สถานะ</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">กำไร/ขาดทุน</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">จัดการ</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @foreach($bots as $bot)
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="font-medium text-gray-900 dark:text-white">{{ $bot->name }}</div>
                            @if($bot->dry_run)
                            <span class="text-xs text-blue-600">Paper Trading</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700 dark:text-gray-300">
                            {{ $bot->trading_pair }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700 dark:text-gray-300">
                            {{ $bot->account->exchange->name ?? 'N/A' }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700 dark:text-gray-300">
                            {{ $bot->strategy->name ?? 'N/A' }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @if($bot->status === 'running')
                            <span class="px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">กำลังทำงาน</span>
                            @elseif($bot->status === 'stopped')
                            <span class="px-2 py-1 text-xs font-semibold rounded-full bg-gray-100 text-gray-800">หยุด</span>
                            @elseif($bot->status === 'error')
                            <span class="px-2 py-1 text-xs font-semibold rounded-full bg-red-100 text-red-800">ข้อผิดพลาด</span>
                            @else
                            <span class="px-2 py-1 text-xs font-semibold rounded-full bg-yellow-100 text-yellow-800">{{ $bot->status }}</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="font-medium {{ $bot->total_profit_loss >= 0 ? 'text-green-600' : 'text-red-600' }}">
                                {{ $bot->total_profit_loss >= 0 ? '+' : '' }}฿{{ number_format($bot->total_profit_loss, 2) }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm">
                            <div class="flex justify-end space-x-2">
                                <a href="{{ route('trading-bot.show', $bot) }}" class="text-blue-600 hover:text-blue-900">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                    </svg>
                                </a>
                                @if($bot->status === 'stopped')
                                <form action="{{ route('trading-bot.start', $bot) }}" method="POST" class="inline">
                                    @csrf
                                    <button type="submit" class="text-green-600 hover:text-green-900">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"></path>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                        </svg>
                                    </button>
                                </form>
                                @elseif($bot->status === 'running')
                                <form action="{{ route('trading-bot.stop', $bot) }}" method="POST" class="inline">
                                    @csrf
                                    <button type="submit" class="text-red-600 hover:text-red-900">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 10a1 1 0 011-1h4a1 1 0 011 1v4a1 1 0 01-1 1h-4a1 1 0 01-1-1v-4z"></path>
                                        </svg>
                                    </button>
                                </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @else
        <div class="p-12 text-center">
            <div class="text-6xl mb-4">🤖</div>
            <p class="text-gray-500 dark:text-gray-400 mb-4">คุณยังไม่มีบอทเทรด</p>
            @if($subscription)
            <a href="{{ route('trading-bot.create') }}" class="inline-block bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-6 rounded-lg transition-colors">
                สร้างบอทแรกของคุณ
            </a>
            @endif
        </div>
        @endif
    </div>

    <!-- Quick Actions -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <a href="{{ route('trading-bot.accounts') }}" class="bg-white dark:bg-gray-800 rounded-lg p-6 shadow-lg hover:shadow-xl transition-shadow">
            <div class="flex items-center mb-4">
                <div class="text-3xl mr-3">🔗</div>
                <h3 class="text-lg font-bold text-gray-900 dark:text-white">Trading Accounts</h3>
            </div>
            <p class="text-gray-600 dark:text-gray-300 text-sm">เชื่อมต่อบัญชี Exchange ของคุณ</p>
        </a>

        <a href="{{ route('trading-bot.strategies') }}" class="bg-white dark:bg-gray-800 rounded-lg p-6 shadow-lg hover:shadow-xl transition-shadow">
            <div class="flex items-center mb-4">
                <div class="text-3xl mr-3">📈</div>
                <h3 class="text-lg font-bold text-gray-900 dark:text-white">Strategies</h3>
            </div>
            <p class="text-gray-600 dark:text-gray-300 text-sm">จัดการกลยุทธ์การเทรด</p>
        </a>

        <a href="{{ route('trading-bot.marketplace') }}" class="bg-white dark:bg-gray-800 rounded-lg p-6 shadow-lg hover:shadow-xl transition-shadow">
            <div class="flex items-center mb-4">
                <div class="text-3xl mr-3">🛒</div>
                <h3 class="text-lg font-bold text-gray-900 dark:text-white">Marketplace</h3>
            </div>
            <p class="text-gray-600 dark:text-gray-300 text-sm">ดูแพคเกจและกลยุทธ์ใหม่</p>
        </a>
    </div>
</div>
@endsection
