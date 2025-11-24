@extends('layouts.admin-v3')

@section('title', 'TPIX Token Management')

@section('content')
<div class="space-y-6">
    {{-- Header --}}
    <div class="flex justify-between items-center">
        <h1 class="text-3xl font-bold text-gray-900 dark:text-white flex items-center gap-3">
            <i class="fas fa-coins" style="color: var(--arrow-x-warning)"></i>
            TPIX Token Management
        </h1>
        <a href="{{ route('admin.tokens.import-cmc') }}"
           class="px-6 py-3 text-white rounded-xl shadow-lg hover:shadow-xl transition-all"
           style="background: linear-gradient(to right, var(--arrow-x-info), var(--arrow-x-primary-end))">
            <i class="fas fa-download"></i> Import from CMC
        </a>
    </div>

    {{-- Statistics Cards --}}
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
        <div class="rounded-xl shadow-lg p-6 text-white" style="background: var(--arrow-x-primary-gradient)">
            <h6 class="text-sm opacity-80 mb-2">Total Tokens</h6>
            <h2 class="text-3xl font-bold">{{ $stats['total'] ?? 0 }}</h2>
        </div>
        <div class="rounded-xl shadow-lg p-6 text-white" style="background: linear-gradient(to bottom right, var(--arrow-x-success), var(--arrow-x-info))">
            <h6 class="text-sm opacity-80 mb-2">Active Tokens</h6>
            <h2 class="text-3xl font-bold">{{ $stats['active'] ?? 0 }}</h2>
        </div>
        <div class="rounded-xl shadow-lg p-6 text-white" style="background: linear-gradient(to bottom right, var(--arrow-x-warning), var(--arrow-x-accent))">
            <h6 class="text-sm opacity-80 mb-2">Pending Approval</h6>
            <h2 class="text-3xl font-bold">{{ $stats['pending'] ?? 0 }}</h2>
        </div>
        <div class="rounded-xl shadow-lg p-6 text-white" style="background: linear-gradient(to bottom right, var(--arrow-x-info), var(--arrow-x-primary-end))">
            <h6 class="text-sm opacity-80 mb-2">Total Market Cap</h6>
            <h2 class="text-3xl font-bold">{{ number_format($stats['total_market_cap'] ?? 0, 2) }} TPIX</h2>
        </div>
    </div>

    {{-- Filters --}}
    <div class="backdrop-blur-xl bg-white/80 dark:bg-gray-800/80 border border-white/20 dark:border-gray-700/30 rounded-2xl shadow-2xl p-6">
        <form method="GET" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4">
            {{-- Search Input --}}
            <div>
                <input type="text"
                       name="search"
                       class="w-full px-4 py-3 rounded-xl
                              bg-white dark:bg-gray-900
                              border-2 border-gray-200 dark:border-gray-700
                              text-gray-900 dark:text-gray-100
                              placeholder:text-gray-400 dark:placeholder:text-gray-500
                              focus:border-purple-500 dark:focus:border-purple-400
                              focus:ring-4 focus:ring-purple-500/20
                              transition-all"
                       placeholder="Search tokens..."
                       value="{{ request('search') }}">
            </div>

            {{-- Status Select --}}
            <div>
                <select name="status"
                        class="w-full px-4 py-3 rounded-xl
                               bg-white dark:bg-gray-900
                               border-2 border-gray-200 dark:border-gray-700
                               text-gray-900 dark:text-gray-100
                               focus:border-purple-500 dark:focus:border-purple-400
                               focus:ring-4 focus:ring-purple-500/20
                               transition-all">
                    <option value="">All Status</option>
                    <option value="draft" {{ request('status') == 'draft' ? 'selected' : '' }}>Draft</option>
                    <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Pending</option>
                    <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>Active</option>
                    <option value="rejected" {{ request('status') == 'rejected' ? 'selected' : '' }}>Rejected</option>
                </select>
            </div>

            {{-- Category Select --}}
            <div>
                <select name="category"
                        class="w-full px-4 py-3 rounded-xl
                               bg-white dark:bg-gray-900
                               border-2 border-gray-200 dark:border-gray-700
                               text-gray-900 dark:text-gray-100
                               focus:border-purple-500 dark:focus:border-purple-400
                               focus:ring-4 focus:ring-purple-500/20
                               transition-all">
                    <option value="">All Categories</option>
                    <option value="defi" {{ request('category') == 'defi' ? 'selected' : '' }}>DeFi</option>
                    <option value="gamefi" {{ request('category') == 'gamefi' ? 'selected' : '' }}>GameFi</option>
                    <option value="meme" {{ request('category') == 'meme' ? 'selected' : '' }}>Meme</option>
                    <option value="utility" {{ request('category') == 'utility' ? 'selected' : '' }}>Utility</option>
                </select>
            </div>

            {{-- Sort Select --}}
            <div>
                <select name="sort"
                        class="w-full px-4 py-3 rounded-xl
                               bg-white dark:bg-gray-900
                               border-2 border-gray-200 dark:border-gray-700
                               text-gray-900 dark:text-gray-100
                               focus:border-purple-500 dark:focus:border-purple-400
                               focus:ring-4 focus:ring-purple-500/20
                               transition-all">
                    <option value="created_at" {{ request('sort') == 'created_at' ? 'selected' : '' }}>Newest First</option>
                    <option value="market_cap" {{ request('sort') == 'market_cap' ? 'selected' : '' }}>Market Cap</option>
                    <option value="holders" {{ request('sort') == 'holders' ? 'selected' : '' }}>Holders</option>
                    <option value="volume" {{ request('sort') == 'volume' ? 'selected' : '' }}>Volume</option>
                </select>
            </div>

            {{-- Filter Button --}}
            <div>
                <button type="submit"
                        class="w-full px-6 py-3 rounded-xl
                               bg-gradient-to-r from-purple-600 via-pink-600 to-orange-600
                               hover:from-purple-700 hover:via-pink-700 hover:to-orange-700
                               text-white font-semibold
                               shadow-lg hover:shadow-xl
                               transform hover:scale-105
                               transition-all duration-300">
                    <i class="fas fa-filter mr-2"></i> Filter
                </button>
            </div>
        </form>
    </div>

    {{-- Tokens Table --}}
    <div class="backdrop-blur-xl bg-white/80 dark:bg-gray-800/80 border border-white/20 dark:border-gray-700/30 rounded-2xl shadow-2xl overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gradient-to-r from-purple-600 via-pink-600 to-orange-600">
                    <tr>
                        <th class="px-6 py-4 text-left text-xs font-bold text-white uppercase tracking-wider">
                            Token
                        </th>
                        <th class="px-6 py-4 text-left text-xs font-bold text-white uppercase tracking-wider">
                            Creator
                        </th>
                        <th class="px-6 py-4 text-left text-xs font-bold text-white uppercase tracking-wider">
                            Status
                        </th>
                        <th class="px-6 py-4 text-left text-xs font-bold text-white uppercase tracking-wider">
                            Market Cap
                        </th>
                        <th class="px-6 py-4 text-left text-xs font-bold text-white uppercase tracking-wider">
                            Holders
                        </th>
                        <th class="px-6 py-4 text-left text-xs font-bold text-white uppercase tracking-wider">
                            24h Volume
                        </th>
                        <th class="px-6 py-4 text-right text-xs font-bold text-white uppercase tracking-wider">
                            Actions
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-gray-900 divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse($tokens as $token)
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center gap-3">
                                @if($token->logo)
                                    <img src="{{ asset('storage/' . $token->logo) }}"
                                         class="w-10 h-10 rounded-full ring-2 ring-purple-500/30"
                                         alt="{{ $token->name }}">
                                @else
                                    <div class="w-10 h-10 rounded-full
                                                bg-gradient-to-br from-purple-500 to-pink-500
                                                flex items-center justify-center
                                                text-white font-bold text-sm
                                                ring-2 ring-purple-500/30">
                                        {{ substr($token->symbol, 0, 1) }}
                                    </div>
                                @endif
                                <div>
                                    <div class="text-sm font-bold text-gray-900 dark:text-white">
                                        {{ $token->name }}
                                    </div>
                                    <div class="text-xs text-gray-500 dark:text-gray-400">
                                        {{ $token->symbol }}
                                    </div>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            <div class="text-sm text-gray-900 dark:text-white">
                                {{ $token->creator->name }}
                            </div>
                            <div class="text-xs text-gray-500 dark:text-gray-400">
                                {{ $token->creator->email }}
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-xs font-bold
                                         {{ $token->status == 'active' ? 'bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-400' : '' }}
                                         {{ $token->status == 'pending' ? 'bg-yellow-100 dark:bg-yellow-900/30 text-yellow-800 dark:text-yellow-400' : '' }}
                                         {{ $token->status == 'rejected' ? 'bg-red-100 dark:bg-red-900/30 text-red-800 dark:text-red-400' : '' }}
                                         {{ $token->status == 'draft' ? 'bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-300' : '' }}">
                                {{ ucfirst($token->status) }}
                            </span>
                            @if($token->is_verified)
                                <i class="fas fa-check-circle text-green-600 dark:text-green-400 ml-1"
                                   title="Verified"></i>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">
                            {{ number_format($token->market_cap ?? 0, 2) }} TPIX
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                            {{ number_format($token->holders_count ?? 0) }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                            {{ number_format($token->volume_24h ?? 0, 2) }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm">
                            <div class="flex items-center justify-end gap-2">
                                <a href="{{ route('admin.tokens.show', $token->id) }}"
                                   class="px-3 py-2 rounded-lg
                                          bg-blue-500 hover:bg-blue-600
                                          dark:bg-blue-600 dark:hover:bg-blue-700
                                          text-white
                                          shadow-md hover:shadow-lg
                                          transform hover:scale-105
                                          transition-all"
                                   title="ดูรายละเอียด">
                                    <i class="fas fa-eye"></i>
                                </a>
                                @if($token->status == 'pending')
                                <form action="{{ route('admin.tokens.approve', $token->id) }}"
                                      method="POST"
                                      class="inline-block">
                                    @csrf
                                    <button type="submit"
                                            class="px-3 py-2 rounded-lg
                                                   bg-green-500 hover:bg-green-600
                                                   dark:bg-green-600 dark:hover:bg-green-700
                                                   text-white
                                                   shadow-md hover:shadow-lg
                                                   transform hover:scale-105
                                                   transition-all"
                                            onclick="return confirm('Approve this token?')"
                                            title="อนุมัติ Token">
                                        <i class="fas fa-check"></i>
                                    </button>
                                </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="px-6 py-12 text-center">
                            <div class="flex flex-col items-center justify-center gap-3">
                                <div class="text-6xl">🔍</div>
                                <div class="text-lg font-medium text-gray-500 dark:text-gray-400">
                                    No tokens found
                                </div>
                                <div class="text-sm text-gray-400 dark:text-gray-500">
                                    Try adjusting your search or filters
                                </div>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        <div class="px-6 py-4 bg-gray-50 dark:bg-gray-900 border-t border-gray-200 dark:border-gray-700">
            {{ $tokens->links() }}
        </div>
    </div>
</div>
@endsection
