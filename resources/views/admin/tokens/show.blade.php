@extends('layouts.admin-v3')

@section('title', $token->name . ' - Token Details')

@section('content')
<div class="space-y-6">
    {{-- Header --}}
    <div class="backdrop-blur-xl bg-white/80 dark:bg-gray-800/80 border border-white/20 dark:border-gray-700/30 rounded-2xl shadow-2xl p-6">
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
            <div class="flex items-center gap-4">
                <a href="{{ route('admin.tokens.index') }}"
                   class="px-4 py-2 rounded-xl
                          bg-gray-200 hover:bg-gray-300
                          dark:bg-gray-700 dark:hover:bg-gray-600
                          text-gray-700 dark:text-gray-300
                          shadow-md hover:shadow-lg
                          transform hover:scale-105
                          transition-all">
                    <i class="fas fa-arrow-left mr-2"></i> Back
                </a>
                @if($token->logo)
                    <img src="{{ asset('storage/' . $token->logo) }}"
                         class="w-16 h-16 rounded-full ring-4 ring-purple-500/30"
                         alt="{{ $token->name }}">
                @endif
                <div>
                    <h1 class="text-3xl font-bold text-gray-900 dark:text-white">
                        {{ $token->name }}
                    </h1>
                    <span class="text-lg text-gray-500 dark:text-gray-400">
                        {{ $token->symbol }}
                    </span>
                </div>
            </div>
            <div class="flex items-center gap-3">
                <span class="inline-flex items-center gap-2 px-4 py-2 rounded-xl text-sm font-bold
                             {{ $token->status == 'active' ? 'bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-400' : '' }}
                             {{ $token->status == 'pending' ? 'bg-yellow-100 dark:bg-yellow-900/30 text-yellow-800 dark:text-yellow-400' : '' }}
                             {{ $token->status == 'rejected' ? 'bg-red-100 dark:bg-red-900/30 text-red-800 dark:text-red-400' : '' }}
                             {{ $token->status == 'draft' ? 'bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-300' : '' }}">
                    {{ ucfirst($token->status) }}
                </span>
                @if($token->is_verified)
                    <i class="fas fa-check-circle text-green-600 dark:text-green-400 text-2xl"
                       title="Verified"></i>
                @endif
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Left Column (2/3) --}}
        <div class="lg:col-span-2 space-y-6">
            {{-- Token Information --}}
            <div class="backdrop-blur-xl bg-white/80 dark:bg-gray-800/80 border border-white/20 dark:border-gray-700/30 rounded-2xl shadow-2xl overflow-hidden">
                <div class="bg-gradient-to-r from-blue-600 via-purple-600 to-pink-600 px-6 py-4">
                    <h2 class="text-xl font-bold text-white flex items-center gap-2">
                        <i class="fas fa-info-circle"></i>
                        Token Information
                    </h2>
                </div>
                <div class="p-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                Contract Address
                            </label>
                            <div class="mt-2 flex items-center gap-2">
                                <code class="px-3 py-2 bg-gray-100 dark:bg-gray-900 text-gray-900 dark:text-gray-100 rounded-lg text-sm flex-1 truncate">
                                    {{ $token->contract_address ?? 'Not deployed' }}
                                </code>
                                @if($token->contract_address)
                                    <button class="px-3 py-2 rounded-lg
                                                   bg-blue-500 hover:bg-blue-600
                                                   dark:bg-blue-600 dark:hover:bg-blue-700
                                                   text-white
                                                   shadow-md hover:shadow-lg
                                                   transform hover:scale-105
                                                   transition-all"
                                            onclick="copyToClipboard('{{ $token->contract_address }}')">
                                        <i class="fas fa-copy"></i>
                                    </button>
                                @endif
                            </div>
                        </div>
                        <div>
                            <label class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                Total Supply
                            </label>
                            <div class="mt-2 text-lg font-bold text-gray-900 dark:text-white">
                                {{ number_format($token->total_supply, 2) }} {{ $token->symbol }}
                            </div>
                        </div>
                        <div>
                            <label class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                Decimals
                            </label>
                            <div class="mt-2 text-lg font-medium text-gray-900 dark:text-white">
                                {{ $token->decimals }}
                            </div>
                        </div>
                        <div>
                            <label class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                Current Price
                            </label>
                            <div class="mt-2 text-lg font-bold text-gray-900 dark:text-white">
                                {{ number_format($token->current_price_tpix ?? 0, 8) }} TPIX
                            </div>
                        </div>
                        <div>
                            <label class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                Market Cap
                            </label>
                            <div class="mt-2 text-lg font-bold text-gray-900 dark:text-white">
                                {{ number_format($token->market_cap ?? 0, 2) }} TPIX
                            </div>
                        </div>
                        <div>
                            <label class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                Holders
                            </label>
                            <div class="mt-2 text-lg font-bold text-gray-900 dark:text-white">
                                {{ number_format($token->holders_count ?? 0) }}
                            </div>
                        </div>
                        <div class="md:col-span-2">
                            <label class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                Description
                            </label>
                            <div class="mt-2 text-gray-700 dark:text-gray-300">
                                {{ $token->description }}
                            </div>
                        </div>
                    </div>

                    {{-- Features --}}
                    <div class="mt-6 pt-6 border-t border-gray-200 dark:border-gray-700">
                        <label class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            Features
                        </label>
                        <div class="mt-3 flex flex-wrap gap-2">
                            @if($token->is_mintable)
                                <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-xs font-bold bg-blue-100 dark:bg-blue-900/30 text-blue-800 dark:text-blue-400">
                                    <i class="fas fa-plus-circle"></i> Mintable
                                </span>
                            @endif
                            @if($token->is_burnable)
                                <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-xs font-bold bg-red-100 dark:bg-red-900/30 text-red-800 dark:text-red-400">
                                    <i class="fas fa-fire"></i> Burnable
                                </span>
                            @endif
                            @if($token->is_pausable)
                                <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-xs font-bold bg-yellow-100 dark:bg-yellow-900/30 text-yellow-800 dark:text-yellow-400">
                                    <i class="fas fa-pause-circle"></i> Pausable
                                </span>
                            @endif
                            @if($token->is_freezable)
                                <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-xs font-bold bg-purple-100 dark:bg-purple-900/30 text-purple-800 dark:text-purple-400">
                                    <i class="fas fa-snowflake"></i> Freezable
                                </span>
                            @endif
                        </div>
                    </div>

                    {{-- Social Links --}}
                    @if($token->website || $token->twitter_handle || $token->telegram_group)
                    <div class="mt-6 pt-6 border-t border-gray-200 dark:border-gray-700">
                        <label class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            Social Links
                        </label>
                        <div class="mt-3 flex flex-wrap gap-2">
                            @if($token->website)
                                <a href="{{ $token->website }}" target="_blank"
                                   class="px-4 py-2 rounded-lg bg-blue-500 hover:bg-blue-600 dark:bg-blue-600 dark:hover:bg-blue-700 text-white shadow-md hover:shadow-lg transform hover:scale-105 transition-all">
                                    <i class="fas fa-globe mr-2"></i> Website
                                </a>
                            @endif
                            @if($token->twitter_handle)
                                <a href="https://twitter.com/{{ $token->twitter_handle }}" target="_blank"
                                   class="px-4 py-2 rounded-lg bg-sky-500 hover:bg-sky-600 dark:bg-sky-600 dark:hover:bg-sky-700 text-white shadow-md hover:shadow-lg transform hover:scale-105 transition-all">
                                    <i class="fab fa-twitter mr-2"></i> Twitter
                                </a>
                            @endif
                            @if($token->telegram_group)
                                <a href="{{ $token->telegram_group }}" target="_blank"
                                   class="px-4 py-2 rounded-lg bg-blue-500 hover:bg-blue-600 dark:bg-blue-600 dark:hover:bg-blue-700 text-white shadow-md hover:shadow-lg transform hover:scale-105 transition-all">
                                    <i class="fab fa-telegram mr-2"></i> Telegram
                                </a>
                            @endif
                        </div>
                    </div>
                    @endif
                </div>
            </div>

            {{-- Coin Control Actions --}}
            @if($token->status == 'active')
            <div class="backdrop-blur-xl bg-white/80 dark:bg-gray-800/80 border border-white/20 dark:border-gray-700/30 rounded-2xl shadow-2xl overflow-hidden">
                <div class="bg-gradient-to-r from-red-600 via-pink-600 to-orange-600 px-6 py-4">
                    <h2 class="text-xl font-bold text-white flex items-center gap-2">
                        <i class="fas fa-shield-alt"></i>
                        Coin Control Actions
                    </h2>
                </div>
                <div class="p-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        @if($token->is_mintable)
                        <button class="px-6 py-4 rounded-xl bg-green-500 hover:bg-green-600 dark:bg-green-600 dark:hover:bg-green-700 text-white font-semibold shadow-lg hover:shadow-xl transform hover:scale-105 transition-all"
                                data-bs-toggle="modal" data-bs-target="#mintModal">
                            <i class="fas fa-plus-circle mr-2"></i> Mint Tokens
                        </button>
                        @endif
                        @if($token->is_burnable)
                        <button class="px-6 py-4 rounded-xl bg-red-500 hover:bg-red-600 dark:bg-red-600 dark:hover:bg-red-700 text-white font-semibold shadow-lg hover:shadow-xl transform hover:scale-105 transition-all"
                                data-bs-toggle="modal" data-bs-target="#burnModal">
                            <i class="fas fa-fire mr-2"></i> Burn Tokens
                        </button>
                        @endif
                        @if($token->is_freezable)
                        <button class="px-6 py-4 rounded-xl bg-yellow-500 hover:bg-yellow-600 dark:bg-yellow-600 dark:hover:bg-yellow-700 text-white font-semibold shadow-lg hover:shadow-xl transform hover:scale-105 transition-all"
                                data-bs-toggle="modal" data-bs-target="#freezeModal">
                            <i class="fas fa-snowflake mr-2"></i> Freeze Address
                        </button>
                        @endif
                        @if($token->is_pausable)
                        <button class="px-6 py-4 rounded-xl bg-blue-500 hover:bg-blue-600 dark:bg-blue-600 dark:hover:bg-blue-700 text-white font-semibold shadow-lg hover:shadow-xl transform hover:scale-105 transition-all"
                                data-bs-toggle="modal" data-bs-target="#pauseModal">
                            <i class="fas fa-pause-circle mr-2"></i> Pause Contract
                        </button>
                        @endif
                    </div>
                </div>
            </div>
            @endif

            {{-- Recent Transactions --}}
            <div class="backdrop-blur-xl bg-white/80 dark:bg-gray-800/80 border border-white/20 dark:border-gray-700/30 rounded-2xl shadow-2xl overflow-hidden">
                <div class="bg-gradient-to-r from-green-600 via-teal-600 to-cyan-600 px-6 py-4">
                    <h2 class="text-xl font-bold text-white flex items-center gap-2">
                        <i class="fas fa-exchange-alt"></i>
                        Recent Transactions
                    </h2>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-900">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider">
                                    TX Hash
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider">
                                    From
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider">
                                    To
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider">
                                    Amount
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider">
                                    Time
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white dark:bg-gray-900 divide-y divide-gray-200 dark:divide-gray-700">
                            @forelse($token->transfers()->latest()->limit(10)->get() as $transfer)
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <code class="text-xs text-gray-900 dark:text-gray-100">
                                        {{ substr($transfer->tx_hash, 0, 10) }}...
                                    </code>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-xs text-gray-600 dark:text-gray-400">
                                    {{ substr($transfer->from_address, 0, 10) }}...
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-xs text-gray-600 dark:text-gray-400">
                                    {{ substr($transfer->to_address, 0, 10) }}...
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">
                                    {{ number_format($transfer->amount, 4) }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-xs text-gray-500 dark:text-gray-400">
                                    {{ $transfer->created_at->diffForHumans() }}
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="5" class="px-6 py-12 text-center">
                                    <div class="flex flex-col items-center gap-3">
                                        <div class="text-4xl">📊</div>
                                        <div class="text-lg font-medium text-gray-500 dark:text-gray-400">
                                            No transactions yet
                                        </div>
                                    </div>
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- Right Column (1/3) --}}
        <div class="space-y-6">
            {{-- Creator Info --}}
            <div class="backdrop-blur-xl bg-white/80 dark:bg-gray-800/80 border border-white/20 dark:border-gray-700/30 rounded-2xl shadow-2xl overflow-hidden">
                <div class="bg-gradient-to-r from-indigo-600 via-purple-600 to-pink-600 px-6 py-4">
                    <h2 class="text-xl font-bold text-white flex items-center gap-2">
                        <i class="fas fa-user"></i>
                        Creator
                    </h2>
                </div>
                <div class="p-6">
                    <div class="flex items-center gap-4 mb-4">
                        <img src="https://ui-avatars.com/api/?name={{ urlencode($token->creator->name) }}"
                             class="w-16 h-16 rounded-full ring-4 ring-purple-500/30"
                             alt="{{ $token->creator->name }}">
                        <div>
                            <div class="font-bold text-gray-900 dark:text-white">
                                {{ $token->creator->name }}
                            </div>
                            <div class="text-sm text-gray-500 dark:text-gray-400">
                                {{ $token->creator->email }}
                            </div>
                        </div>
                    </div>
                    <a href="{{ route('admin.users.show', $token->creator_id) }}"
                       class="block w-full px-4 py-2 rounded-xl text-center
                              bg-blue-500 hover:bg-blue-600
                              dark:bg-blue-600 dark:hover:bg-blue-700
                              text-white font-semibold
                              shadow-md hover:shadow-lg
                              transform hover:scale-105
                              transition-all">
                        View Profile
                    </a>
                </div>
            </div>

            {{-- Admin Actions --}}
            <div class="backdrop-blur-xl bg-white/80 dark:bg-gray-800/80 border border-white/20 dark:border-gray-700/30 rounded-2xl shadow-2xl overflow-hidden">
                <div class="bg-gradient-to-r from-orange-600 via-red-600 to-pink-600 px-6 py-4">
                    <h2 class="text-xl font-bold text-white flex items-center gap-2">
                        <i class="fas fa-tools"></i>
                        Admin Actions
                    </h2>
                </div>
                <div class="p-6 space-y-3">
                    @if($token->status == 'pending')
                    <form action="{{ route('admin.tokens.approve', $token->id) }}" method="POST">
                        @csrf
                        <button type="submit"
                                class="w-full px-4 py-3 rounded-xl bg-green-500 hover:bg-green-600 dark:bg-green-600 dark:hover:bg-green-700 text-white font-semibold shadow-lg hover:shadow-xl transform hover:scale-105 transition-all"
                                onclick="return confirm('Approve this token?')">
                            <i class="fas fa-check mr-2"></i> Approve Token
                        </button>
                    </form>
                    <button class="w-full px-4 py-3 rounded-xl bg-red-500 hover:bg-red-600 dark:bg-red-600 dark:hover:bg-red-700 text-white font-semibold shadow-lg hover:shadow-xl transform hover:scale-105 transition-all"
                            data-bs-toggle="modal" data-bs-target="#rejectModal">
                        <i class="fas fa-times mr-2"></i> Reject Token
                    </button>
                    @endif

                    @if(!$token->is_verified && $token->status == 'active')
                    <form action="{{ route('admin.tokens.verify', $token->id) }}" method="POST">
                        @csrf
                        <button type="submit"
                                class="w-full px-4 py-3 rounded-xl bg-blue-500 hover:bg-blue-600 dark:bg-blue-600 dark:hover:bg-blue-700 text-white font-semibold shadow-lg hover:shadow-xl transform hover:scale-105 transition-all">
                            <i class="fas fa-check-circle mr-2"></i> Verify Token
                        </button>
                    </form>
                    @endif

                    @if(!$token->is_featured)
                    <form action="{{ route('admin.tokens.feature', $token->id) }}" method="POST">
                        @csrf
                        <button type="submit"
                                class="w-full px-4 py-3 rounded-xl bg-yellow-500 hover:bg-yellow-600 dark:bg-yellow-600 dark:hover:bg-yellow-700 text-white font-semibold shadow-lg hover:shadow-xl transform hover:scale-105 transition-all">
                            <i class="fas fa-star mr-2"></i> Feature Token
                        </button>
                    </form>
                    @else
                    <form action="{{ route('admin.tokens.unfeature', $token->id) }}" method="POST">
                        @csrf
                        <button type="submit"
                                class="w-full px-4 py-3 rounded-xl bg-gray-200 hover:bg-gray-300 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 font-semibold shadow-lg hover:shadow-xl transform hover:scale-105 transition-all">
                            <i class="far fa-star mr-2"></i> Unfeature Token
                        </button>
                    </form>
                    @endif

                    <a href="{{ route('admin.tokens.edit', $token->id) }}"
                       class="block w-full px-4 py-3 rounded-xl text-center bg-purple-500 hover:bg-purple-600 dark:bg-purple-600 dark:hover:bg-purple-700 text-white font-semibold shadow-lg hover:shadow-xl transform hover:scale-105 transition-all">
                        <i class="fas fa-edit mr-2"></i> Edit Token
                    </a>

                    @if($token->cmc_id)
                    <form action="{{ route('admin.tokens.sync-cmc', $token->id) }}" method="POST">
                        @csrf
                        <button type="submit"
                                class="w-full px-4 py-3 rounded-xl bg-cyan-500 hover:bg-cyan-600 dark:bg-cyan-600 dark:hover:bg-cyan-700 text-white font-semibold shadow-lg hover:shadow-xl transform hover:scale-105 transition-all">
                            <i class="fas fa-sync mr-2"></i> Sync with CMC
                        </button>
                    </form>
                    @else
                    <button class="w-full px-4 py-3 rounded-xl bg-cyan-500 hover:bg-cyan-600 dark:bg-cyan-600 dark:hover:bg-cyan-700 text-white font-semibold shadow-lg hover:shadow-xl transform hover:scale-105 transition-all"
                            data-bs-toggle="modal" data-bs-target="#linkCMCModal">
                        <i class="fas fa-link mr-2"></i> Link to CMC
                    </button>
                    @endif
                </div>
            </div>

            {{-- Statistics --}}
            <div class="backdrop-blur-xl bg-white/80 dark:bg-gray-800/80 border border-white/20 dark:border-gray-700/30 rounded-2xl shadow-2xl overflow-hidden">
                <div class="bg-gradient-to-r from-teal-600 via-green-600 to-emerald-600 px-6 py-4">
                    <h2 class="text-xl font-bold text-white flex items-center gap-2">
                        <i class="fas fa-chart-line"></i>
                        Statistics
                    </h2>
                </div>
                <div class="p-6 space-y-4">
                    <div>
                        <div class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            24h Volume
                        </div>
                        <div class="mt-1 text-lg font-bold text-gray-900 dark:text-white">
                            {{ number_format($token->volume_24h ?? 0, 2) }} TPIX
                        </div>
                    </div>
                    <div>
                        <div class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            24h Transactions
                        </div>
                        <div class="mt-1 text-lg font-bold text-gray-900 dark:text-white">
                            {{ number_format($token->tx_count_24h ?? 0) }}
                        </div>
                    </div>
                    <div>
                        <div class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            Total Minted
                        </div>
                        <div class="mt-1 text-lg font-bold text-gray-900 dark:text-white">
                            {{ number_format($token->total_minted ?? 0, 2) }}
                        </div>
                    </div>
                    <div>
                        <div class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            Total Burned
                        </div>
                        <div class="mt-1 text-lg font-bold text-gray-900 dark:text-white">
                            {{ number_format($token->total_burned ?? 0, 2) }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
/**
 * คัดลอกข้อความไปยัง clipboard
 *
 * @param {string} text - ข้อความที่ต้องการคัดลอก
 */
function copyToClipboard(text) {
    navigator.clipboard.writeText(text).then(() => {
        // แสดงข้อความสำเร็จ
        alert('Copied to clipboard!');
    }).catch(err => {
        console.error('Failed to copy:', err);
    });
}
</script>
@endsection
