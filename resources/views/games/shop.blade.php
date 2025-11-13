@extends('layouts.app')

@section('title', $game->name . ' - Shop')

@section('content')
<div class="container mx-auto px-4 py-8">
    <!-- Header -->
    <div class="mb-8">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-3xl font-bold text-gray-900 dark:text-white mb-2">
                    {{ $game->name }} Shop
                </h1>
                <p class="text-gray-600 dark:text-gray-400">
                    Purchase premium skins and customize your gameplay
                </p>
            </div>
            <a href="{{ route('games.show', $game->slug) }}" class="px-6 py-3 bg-gray-600 hover:bg-gray-700 text-white rounded-lg transition">
                Back to Game
            </a>
        </div>
    </div>

    <!-- Wallet Balance -->
    <div class="mb-8 bg-gradient-to-r from-purple-600 to-blue-600 rounded-lg p-6 text-white">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm opacity-90 mb-1">Your Wallet Balance</p>
                <p class="text-3xl font-bold">฿{{ number_format($walletBalance, 2) }}</p>
            </div>
            <div class="bg-white bg-opacity-20 rounded-full p-4">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path>
                </svg>
            </div>
        </div>
        <div class="mt-4">
            <a href="{{ route('user.wallet.index') }}" class="text-sm underline hover:no-underline">
                Add funds to wallet →
            </a>
        </div>
    </div>

    <!-- Tabs -->
    <div class="mb-6">
        <div class="border-b border-gray-200 dark:border-gray-700">
            <nav class="-mb-px flex space-x-8">
                <button onclick="showTab('shop')" id="tab-shop" class="tab-button border-b-2 border-blue-500 py-4 px-1 text-sm font-medium text-blue-600 dark:text-blue-400">
                    Available Skins
                </button>
                <button onclick="showTab('owned')" id="tab-owned" class="tab-button border-b-2 border-transparent py-4 px-1 text-sm font-medium text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300">
                    My Collection
                </button>
                <button onclick="showTab('history')" id="tab-history" class="tab-button border-b-2 border-transparent py-4 px-1 text-sm font-medium text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300">
                    Purchase History
                </button>
            </nav>
        </div>
    </div>

    <!-- Available Skins Tab -->
    <div id="content-shop" class="tab-content">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            @foreach($skins as $skin)
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg overflow-hidden transform transition hover:scale-105">
                    <!-- Skin Preview -->
                    <div class="relative h-48 overflow-hidden" style="background: linear-gradient(135deg, {{ $skin->color_primary }}, {{ $skin->color_secondary }});">
                        <div class="absolute inset-0 flex items-center justify-center">
                            @if($skin->preview_image)
                                <img src="{{ $skin->preview_image }}" alt="{{ $skin->name }}" class="w-full h-full object-cover">
                            @else
                                <!-- Pattern Preview -->
                                <div class="w-24 h-24 rounded-full" style="background: {{ $skin->color_primary }}; box-shadow: 0 0 40px {{ $skin->color_secondary }};"></div>
                            @endif
                        </div>

                        <!-- Badges -->
                        <div class="absolute top-2 right-2 flex gap-2">
                            @if($skin->is_premium)
                                <span class="px-2 py-1 bg-yellow-500 text-white text-xs font-bold rounded">PREMIUM</span>
                            @endif
                            @if($skin->is_limited)
                                <span class="px-2 py-1 bg-red-500 text-white text-xs font-bold rounded">LIMITED</span>
                            @endif
                        </div>

                        @if(in_array($skin->id, $ownedSkinIds))
                            <div class="absolute inset-0 bg-black bg-opacity-50 flex items-center justify-center">
                                <div class="text-center">
                                    <svg class="w-16 h-16 text-green-500 mx-auto mb-2" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                    </svg>
                                    <p class="text-white font-bold">OWNED</p>
                                </div>
                            </div>
                        @endif
                    </div>

                    <!-- Skin Info -->
                    <div class="p-6">
                        <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-2">
                            {{ $skin->name }}
                        </h3>
                        <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
                            {{ $skin->description }}
                        </p>

                        <!-- Requirements -->
                        @if($skin->min_level || $skin->min_score)
                            <div class="mb-4 text-xs text-gray-500 dark:text-gray-400">
                                <p>Requirements:</p>
                                @if($skin->min_level)
                                    <p>• Level {{ $skin->min_level }}+</p>
                                @endif
                                @if($skin->min_score)
                                    <p>• Score: {{ number_format($skin->min_score) }}+</p>
                                @endif
                            </div>
                        @endif

                        <!-- Price & Action -->
                        <div class="flex items-center justify-between">
                            <div>
                                @if($skin->price > 0)
                                    <p class="text-2xl font-bold text-gray-900 dark:text-white">
                                        ฿{{ number_format($skin->price, 2) }}
                                    </p>
                                @else
                                    <p class="text-2xl font-bold text-green-600 dark:text-green-400">
                                        FREE
                                    </p>
                                @endif
                            </div>

                            @if(in_array($skin->id, $ownedSkinIds))
                                <button disabled class="px-6 py-2 bg-gray-400 text-white rounded-lg cursor-not-allowed">
                                    Owned
                                </button>
                            @else
                                <button onclick="purchaseSkin({{ $skin->id }}, '{{ $skin->name }}', {{ $skin->price }})"
                                        class="px-6 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition">
                                    Purchase
                                </button>
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        @if($skins->isEmpty())
            <div class="text-center py-12">
                <svg class="w-24 h-24 text-gray-400 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"></path>
                </svg>
                <p class="text-gray-500 dark:text-gray-400">No skins available at the moment</p>
            </div>
        @endif
    </div>

    <!-- My Collection Tab -->
    <div id="content-owned" class="tab-content hidden">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            @foreach($skins->whereIn('id', $ownedSkinIds) as $skin)
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg overflow-hidden">
                    <div class="h-48" style="background: linear-gradient(135deg, {{ $skin->color_primary }}, {{ $skin->color_secondary }});">
                        <div class="h-full flex items-center justify-center">
                            @if($skin->preview_image)
                                <img src="{{ $skin->preview_image }}" alt="{{ $skin->name }}" class="w-full h-full object-cover">
                            @else
                                <div class="w-24 h-24 rounded-full" style="background: {{ $skin->color_primary }}; box-shadow: 0 0 40px {{ $skin->color_secondary }};"></div>
                            @endif
                        </div>
                    </div>
                    <div class="p-6">
                        <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-2">{{ $skin->name }}</h3>
                        <p class="text-sm text-gray-600 dark:text-gray-400">{{ $skin->description }}</p>
                    </div>
                </div>
            @endforeach
        </div>

        @if($skins->whereIn('id', $ownedSkinIds)->isEmpty())
            <div class="text-center py-12">
                <svg class="w-24 h-24 text-gray-400 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zm0 0h12a2 2 0 002-2v-4a2 2 0 00-2-2h-2.343M11 7.343l1.657-1.657a2 2 0 012.828 0l2.829 2.829a2 2 0 010 2.828l-8.486 8.485M7 17h.01"></path>
                </svg>
                <p class="text-gray-500 dark:text-gray-400 mb-4">You don't own any skins yet</p>
                <button onclick="showTab('shop')" class="px-6 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg">
                    Browse Skins
                </button>
            </div>
        @endif
    </div>

    <!-- Purchase History Tab -->
    <div id="content-history" class="tab-content hidden">
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow overflow-hidden">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-900">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Date</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Item</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Type</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Amount</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Status</th>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                    @foreach($transactions as $transaction)
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-300">
                                {{ $transaction->created_at->format('M d, Y H:i') }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-300">
                                {{ $transaction->skin ? $transaction->skin->name : $transaction->description }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                {{ str_replace('_', ' ', ucwords($transaction->type)) }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                <span class="{{ $transaction->amount < 0 ? 'text-red-600' : 'text-green-600' }}">
                                    {{ $transaction->amount < 0 ? '-' : '+' }}฿{{ number_format(abs($transaction->amount), 2) }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-2 py-1 text-xs font-semibold rounded-full
                                    {{ $transaction->status === 'completed' ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' : '' }}
                                    {{ $transaction->status === 'pending' ? 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200' : '' }}
                                    {{ $transaction->status === 'failed' ? 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200' : '' }}">
                                    {{ ucfirst($transaction->status) }}
                                </span>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            @if($transactions->isEmpty())
                <div class="text-center py-12">
                    <svg class="w-24 h-24 text-gray-400 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                    <p class="text-gray-500 dark:text-gray-400">No transaction history yet</p>
                </div>
            @endif
        </div>
    </div>
</div>

<!-- Purchase Confirmation Modal -->
<div id="purchaseModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center">
    <div class="bg-white dark:bg-gray-800 rounded-lg p-8 max-w-md w-full mx-4">
        <h3 class="text-2xl font-bold text-gray-900 dark:text-white mb-4">Confirm Purchase</h3>
        <p class="text-gray-600 dark:text-gray-400 mb-4">
            Are you sure you want to purchase <strong id="modalSkinName"></strong>?
        </p>
        <div class="bg-gray-100 dark:bg-gray-700 rounded-lg p-4 mb-6">
            <div class="flex justify-between mb-2">
                <span class="text-gray-600 dark:text-gray-400">Price:</span>
                <span class="font-bold text-gray-900 dark:text-white">฿<span id="modalSkinPrice"></span></span>
            </div>
            <div class="flex justify-between mb-2">
                <span class="text-gray-600 dark:text-gray-400">Current Balance:</span>
                <span class="font-bold text-gray-900 dark:text-white">฿{{ number_format($walletBalance, 2) }}</span>
            </div>
            <div class="border-t border-gray-300 dark:border-gray-600 my-2"></div>
            <div class="flex justify-between">
                <span class="text-gray-600 dark:text-gray-400">New Balance:</span>
                <span class="font-bold text-gray-900 dark:text-white">฿<span id="modalNewBalance"></span></span>
            </div>
        </div>
        <div class="flex gap-4">
            <button onclick="closeModal()" class="flex-1 px-6 py-3 bg-gray-300 hover:bg-gray-400 text-gray-800 rounded-lg transition">
                Cancel
            </button>
            <button onclick="confirmPurchase()" class="flex-1 px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition">
                Confirm
            </button>
        </div>
    </div>
</div>

<script>
let currentSkinId = null;
let currentSkinName = null;
let currentSkinPrice = null;

function showTab(tab) {
    // Hide all tabs
    document.querySelectorAll('.tab-content').forEach(el => el.classList.add('hidden'));
    document.querySelectorAll('.tab-button').forEach(el => {
        el.classList.remove('border-blue-500', 'text-blue-600', 'dark:text-blue-400');
        el.classList.add('border-transparent', 'text-gray-500', 'dark:text-gray-400');
    });

    // Show selected tab
    document.getElementById('content-' + tab).classList.remove('hidden');
    const button = document.getElementById('tab-' + tab);
    button.classList.add('border-blue-500', 'text-blue-600', 'dark:text-blue-400');
    button.classList.remove('border-transparent', 'text-gray-500', 'dark:text-gray-400');
}

function purchaseSkin(skinId, skinName, skinPrice) {
    currentSkinId = skinId;
    currentSkinName = skinName;
    currentSkinPrice = skinPrice;

    const walletBalance = {{ $walletBalance }};
    const newBalance = walletBalance - skinPrice;

    document.getElementById('modalSkinName').textContent = skinName;
    document.getElementById('modalSkinPrice').textContent = skinPrice.toFixed(2);
    document.getElementById('modalNewBalance').textContent = newBalance.toFixed(2);

    document.getElementById('purchaseModal').classList.remove('hidden');
}

function closeModal() {
    document.getElementById('purchaseModal').classList.add('hidden');
    currentSkinId = null;
    currentSkinName = null;
    currentSkinPrice = null;
}

function confirmPurchase() {
    if (!currentSkinId) return;

    const button = event.target;
    button.disabled = true;
    button.textContent = 'Processing...';

    fetch(`{{ route('games.shop.purchase', ['slug' => $game->slug, 'skinId' => ':skinId']) }}`.replace(':skinId', currentSkinId), {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Purchase successful! 🎉');
            location.reload();
        } else {
            alert('Purchase failed: ' + (data.error || 'Unknown error'));
            button.disabled = false;
            button.textContent = 'Confirm';
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Purchase failed. Please try again.');
        button.disabled = false;
        button.textContent = 'Confirm';
    });
}
</script>
@endsection
