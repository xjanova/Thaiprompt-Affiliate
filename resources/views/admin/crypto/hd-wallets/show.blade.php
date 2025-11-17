@extends('layouts.admin-v3')

@section('title', 'รายละเอียด Wallet: ' . $wallet->name)

@section('content')
<div class="space-y-6">

    <!-- Header with Back Button -->
    <div class="flex items-center justify-between">
        <div class="flex items-center space-x-4">
            <a href="{{ route('admin.crypto.hd-wallets.index') }}" class="w-10 h-10 glass-fusion dark:bg-gray-800 border border-gray-200 dark:border-gray-700 dark:border-gray-600 rounded-xl flex items-center justify-center text-gray-700 dark:text-gray-300 dark:text-gray-300 hover:shadow-md transition-all">
                <i class="fas fa-arrow-left"></i>
            </a>
            <div>
                <h1 class="text-3xl font-bold text-gray-900 dark:text-white flex items-center">
                    @if($wallet->is_master_wallet)
                        <i class="fas fa-crown text-yellow-500 mr-3"></i>
                    @else
                        <i class="fas fa-wallet text-emerald-600 mr-3"></i>
                    @endif
                    {{ $wallet->name }}
                </h1>
                <p class="text-gray-600 dark:text-gray-400 dark:text-gray-400 mt-1">รายละเอียดและข้อมูลสถิติของกระเป๋า</p>
            </div>
        </div>
        <div class="flex items-center space-x-2">
            <!-- Status Badge -->
            @if($wallet->status === 'active')
                <span class="px-4 py-2 bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200 rounded-xl font-semibold">
                    <i class="fas fa-check-circle mr-1"></i> ใช้งานอยู่
                </span>
            @elseif($wallet->status === 'locked')
                <span class="px-4 py-2 bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200 rounded-xl font-semibold">
                    <i class="fas fa-lock mr-1"></i> ล็อกอยู่
                </span>
            @else
                <span class="px-4 py-2 bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200 rounded-xl font-semibold">
                    <i class="fas fa-ban mr-1"></i> ระงับ
                </span>
            @endif
        </div>
    </div>

    <!-- Wallet Type Banner -->
    @if($wallet->is_master_wallet)
    <div class="bg-gradient-to-r from-yellow-400 via-orange-500 to-red-500 rounded-2xl shadow-2xl p-8 text-white relative overflow-hidden">
        <div class="absolute inset-0 opacity-10">
            <div class="absolute top-0 right-0 w-64 h-64 glass-fusion rounded-full -mr-32 -mt-32" border border-white/20 dark:border-white/10></div>
            <div class="absolute bottom-0 left-0 w-48 h-48 glass-fusion rounded-full -ml-24 -mb-24" border border-white/20 dark:border-white/10></div>
        </div>
        <div class="relative flex items-center justify-between">
            <div>
                <div class="flex items-center space-x-3 mb-3">
                    <i class="fas fa-crown text-5xl"></i>
                    <h2 class="text-3xl font-bold">Master Wallet</h2>
                </div>
                <p class="text-yellow-100 mb-2">กระเป๋าหลักที่ใช้สำหรับสร้าง Child Wallets</p>
                <div class="flex items-center space-x-6 mt-4">
                    <div>
                        <p class="text-yellow-100 text-sm">Total Derived</p>
                        <p class="text-2xl font-bold">{{ $wallet->total_derived_wallets }} wallets</p>
                    </div>
                    <div>
                        <p class="text-yellow-100 text-sm">Derivation Index</p>
                        <p class="text-2xl font-bold">{{ $wallet->derivation_index ?? 0 }}</p>
                    </div>
                </div>
            </div>
            <div class="text-8xl opacity-20">
                <i class="fas fa-sitemap"></i>
            </div>
        </div>
    </div>
    @else
    <div class="bg-gradient-to-r from-emerald-500 via-green-600 to-emerald-700 rounded-2xl shadow-2xl p-8 text-white relative overflow-hidden">
        <div class="absolute inset-0 opacity-10">
            <div class="absolute top-0 right-0 w-64 h-64 glass-fusion rounded-full -mr-32 -mt-32" border border-white/20 dark:border-white/10></div>
            <div class="absolute bottom-0 left-0 w-48 h-48 glass-fusion rounded-full -ml-24 -mb-24" border border-white/20 dark:border-white/10></div>
        </div>
        <div class="relative flex items-center justify-between">
            <div>
                <div class="flex items-center space-x-3 mb-3">
                    <i class="fas fa-wallet text-5xl"></i>
                    <h2 class="text-3xl font-bold">Child Wallet</h2>
                </div>
                <p class="text-emerald-100 mb-2">กระเป๋าลูกที่สร้างจาก Master Wallet</p>
                @if($wallet->masterWallet)
                <div class="mt-4">
                    <p class="text-emerald-100 text-sm mb-1">Parent Master Wallet:</p>
                    <a href="{{ route('admin.crypto.hd-wallets.show', $wallet->masterWallet->id) }}" class="inline-flex items-center px-4 py-2 glass-fusion hover:glass-fusion backdrop-blur-sm rounded-xl transition-all">
                        <i class="fas fa-external-link-alt mr-2"></i>
                        {{ $wallet->masterWallet->name }}
                    </a>
                </div>
                @endif
            </div>
            <div class="text-8xl opacity-20">
                <i class="fas fa-layer-group"></i>
            </div>
        </div>
    </div>
    @endif

    <!-- Owner Information -->
    <div class="glass-fusion dark:bg-gray-800 rounded-2xl shadow-xl p-6" hover:scale-105 transition-transform border border-white/20 dark:border-white/10>
        <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-6 flex items-center">
            <i class="fas fa-user-circle text-emerald-600 mr-2"></i>
            ข้อมูลเจ้าของกระเป๋า
        </h3>
        <div class="grid md:grid-cols-2 gap-6">
            <div class="flex items-center space-x-4 p-4 bg-gray-100/50 dark:bg-gray-800/50/50 dark:bg-gray-800/50 dark:bg-gray-700 rounded-xl">
                <div class="w-16 h-16 bg-gradient-to-br from-emerald-400 to-green-600 rounded-full flex items-center justify-center text-white text-2xl font-bold">
                    {{ strtoupper(substr($wallet->user->name, 0, 1)) }}
                </div>
                <div>
                    <p class="text-sm text-gray-600 dark:text-gray-400 dark:text-gray-400">ชื่อผู้ใช้</p>
                    <p class="text-lg font-bold text-gray-900 dark:text-white">{{ $wallet->user->name }}</p>
                </div>
            </div>
            <div class="flex items-center space-x-4 p-4 bg-gray-100/50 dark:bg-gray-800/50/50 dark:bg-gray-800/50 dark:bg-gray-700 rounded-xl">
                <div class="w-16 h-16 bg-blue-100 dark:bg-blue-900 rounded-full flex items-center justify-center">
                    <i class="fas fa-envelope text-2xl text-blue-600"></i>
                </div>
                <div>
                    <p class="text-sm text-gray-600 dark:text-gray-400 dark:text-gray-400">อีเมล</p>
                    <p class="text-lg font-semibold text-gray-900 dark:text-white">{{ $wallet->user->email }}</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Statistics Grid -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        @foreach($statistics as $key => $value)
            @php
                $icons = [
                    'total_transactions' => ['icon' => 'exchange-alt', 'color' => 'blue'],
                    'total_received' => ['icon' => 'arrow-down', 'color' => 'green'],
                    'total_sent' => ['icon' => 'arrow-up', 'color' => 'red'],
                    'balance_thb' => ['icon' => 'coins', 'color' => 'emerald'],
                    'balance_btc' => ['icon' => 'bitcoin', 'color' => 'orange'],
                    'balance_eth' => ['icon' => 'ethereum', 'color' => 'purple'],
                    'child_wallets' => ['icon' => 'layer-group', 'color' => 'indigo'],
                ];
                $stat = $icons[$key] ?? ['icon' => 'chart-line', 'color' => 'gray'];

                $labels = [
                    'total_transactions' => 'รายการทั้งหมด',
                    'total_received' => 'รับเข้าทั้งหมด',
                    'total_sent' => 'ส่งออกทั้งหมด',
                    'balance_thb' => 'ยอดคงเหลือ THB',
                    'balance_btc' => 'ยอดคงเหลือ BTC',
                    'balance_eth' => 'ยอดคงเหลือ ETH',
                    'child_wallets' => 'Child Wallets',
                ];
                $label = $labels[$key] ?? ucfirst(str_replace('_', ' ', $key));
            @endphp

            <div class="glass-fusion dark:bg-gray-800 rounded-2xl shadow-lg p-6 border-l-4 border-{{ $stat['color'] }}-500" hover:scale-105 transition-transform border border-white/20 dark:border-white/10>
                <div class="flex items-center justify-between mb-4">
                    <div class="w-12 h-12 bg-{{ $stat['color'] }}-100 dark:bg-{{ $stat['color'] }}-900/20 rounded-xl flex items-center justify-center">
                        <i class="fab fa-{{ $stat['icon'] }} text-2xl text-{{ $stat['color'] }}-600"></i>
                    </div>
                </div>
                <p class="text-2xl font-bold text-gray-900 dark:text-white mb-1">
                    @if(is_numeric($value))
                        {{ number_format($value, 2) }}
                    @else
                        {{ $value }}
                    @endif
                </p>
                <p class="text-sm text-gray-600 dark:text-gray-400 dark:text-gray-400">{{ $label }}</p>
            </div>
        @endforeach
    </div>

    <!-- Child Wallets (if master) -->
    @if($wallet->is_master_wallet && $wallet->childWallets->count() > 0)
    <div class="glass-fusion dark:bg-gray-800 rounded-2xl shadow-xl overflow-hidden" border border-white/20 dark:border-white/10>
        <div class="bg-gradient-to-r from-emerald-500 to-green-600 px-6 py-4">
            <h3 class="text-xl font-bold text-white flex items-center">
                <i class="fas fa-sitemap mr-2"></i>
                Child Wallets ({{ $wallet->childWallets->count() }})
            </h3>
        </div>
        <div class="p-6">
            <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-4">
                @foreach($wallet->childWallets as $childWallet)
                <a href="{{ route('admin.crypto.hd-wallets.show', $childWallet->id) }}" class="block p-4 bg-gray-100/50 dark:bg-gray-800/50/50 dark:bg-gray-800/50 dark:bg-gray-700 rounded-xl hover:shadow-lg hover:scale-105 transition-all">
                    <div class="flex items-center justify-between mb-3">
                        <p class="font-semibold text-gray-900 dark:text-white">{{ $childWallet->name }}</p>
                        <i class="fas fa-arrow-right text-emerald-600"></i>
                    </div>
                    <div class="flex items-center justify-between text-sm">
                        <span class="text-gray-600 dark:text-gray-400 dark:text-gray-400">Index: {{ $childWallet->derivation_index }}</span>
                        <span class="px-2 py-1 bg-{{ $childWallet->status === 'active' ? 'green' : 'yellow' }}-100 text-{{ $childWallet->status === 'active' ? 'green' : 'yellow' }}-800 rounded text-xs">
                            {{ $childWallet->status }}
                        </span>
                    </div>
                </a>
                @endforeach
            </div>
        </div>
    </div>
    @endif

    <!-- Crypto Addresses -->
    @if($wallet->cryptoAddresses->count() > 0)
    <div class="glass-fusion dark:bg-gray-800 rounded-2xl shadow-xl overflow-hidden" border border-white/20 dark:border-white/10>
        <div class="bg-gradient-to-r from-blue-500 to-indigo-600 px-6 py-4">
            <h3 class="text-xl font-bold text-white flex items-center">
                <i class="fas fa-link mr-2"></i>
                Crypto Addresses ({{ $wallet->cryptoAddresses->count() }})
            </h3>
        </div>
        <div class="p-6">
            <div class="space-y-4">
                @foreach($wallet->cryptoAddresses as $address)
                <div class="p-5 bg-gradient-to-r from-gray-50 to-gray-100 dark:from-gray-700 dark:to-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 dark:border-gray-600">
                    <div class="flex items-center justify-between mb-3">
                        <div class="flex items-center space-x-3">
                            <div class="w-10 h-10 bg-gradient-to-br from-blue-500 to-indigo-600 rounded-full flex items-center justify-center text-white font-bold">
                                {{ strtoupper(substr($address->currency->symbol ?? 'BTC', 0, 1)) }}
                            </div>
                            <div>
                                <p class="font-bold text-gray-900 dark:text-white">{{ $address->currency->name ?? 'Bitcoin' }}</p>
                                <p class="text-xs text-gray-500 dark:text-gray-400 dark:text-gray-400">{{ $address->currency->symbol ?? 'BTC' }}</p>
                            </div>
                        </div>
                        <button onclick="copyAddress('{{ $address->address }}')" class="px-4 py-2 bg-blue-100 hover:bg-blue-200 dark:bg-blue-900 dark:hover:bg-blue-800 text-blue-700 dark:text-blue-300 rounded-xl transition-all">
                            <i class="fas fa-copy mr-2"></i>คัดลอก
                        </button>
                    </div>
                    <div class="glass-fusion dark:bg-gray-900 rounded-xl p-3 font-mono text-sm break-all" border border-white/20 dark:border-white/10>
                        {{ $address->address }}
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </div>
    @endif

    <!-- Recent Transactions -->
    @if($wallet->cryptoTransactions->count() > 0)
    <div class="glass-fusion dark:bg-gray-800 rounded-2xl shadow-xl overflow-hidden" border border-white/20 dark:border-white/10>
        <div class="bg-gradient-to-r from-purple-500 to-pink-600 px-6 py-4">
            <h3 class="text-xl font-bold text-white flex items-center">
                <i class="fas fa-history mr-2"></i>
                ประวัติธุรกรรมล่าสุด
            </h3>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-100/50 dark:bg-gray-800/50/50 dark:bg-gray-800/50 dark:bg-gray-900">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 dark:text-gray-400 dark:text-gray-400 uppercase">ประเภท</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 dark:text-gray-400 dark:text-gray-400 uppercase">จำนวน</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 dark:text-gray-400 dark:text-gray-400 uppercase">สถานะ</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 dark:text-gray-400 dark:text-gray-400 uppercase">วันที่</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @foreach($wallet->cryptoTransactions->take(10) as $transaction)
                    <tr class="hover:bg-gray-100/50 dark:bg-gray-800/50/50 dark:bg-gray-800/50 dark:hover:bg-gray-700 transition-colors">
                        <td class="px-6 py-4">
                            @if($transaction->type === 'receive')
                                <span class="inline-flex items-center px-3 py-1 bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200 rounded-full text-sm font-semibold">
                                    <i class="fas fa-arrow-down mr-1"></i> รับเข้า
                                </span>
                            @else
                                <span class="inline-flex items-center px-3 py-1 bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200 rounded-full text-sm font-semibold">
                                    <i class="fas fa-arrow-up mr-1"></i> ส่งออก
                                </span>
                            @endif
                        </td>
                        <td class="px-6 py-4">
                            <p class="font-bold text-gray-900 dark:text-white">{{ $transaction->amount }} {{ $transaction->currency }}</p>
                        </td>
                        <td class="px-6 py-4">
                            <span class="px-3 py-1 bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200 rounded-full text-xs font-semibold">
                                {{ $transaction->status }}
                            </span>
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-400 dark:text-gray-400">
                            {{ $transaction->created_at->format('d/m/Y H:i') }}
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif

    <!-- Actions -->
    <div class="glass-fusion dark:bg-gray-800 rounded-2xl shadow-xl p-6" hover:scale-105 transition-transform border border-white/20 dark:border-white/10>
        <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-6 flex items-center">
            <i class="fas fa-tools text-emerald-600 mr-2"></i>
            การจัดการ
        </h3>
        <div class="grid md:grid-cols-3 gap-4">
            @if($wallet->status === 'active')
            <button onclick="lockWallet()" class="flex items-center justify-center px-6 py-4 bg-yellow-100 hover:bg-yellow-200 dark:bg-yellow-900 dark:hover:bg-yellow-800 text-yellow-800 dark:text-yellow-200 rounded-xl font-semibold transition-all">
                <i class="fas fa-lock mr-2"></i>
                ล็อกกระเป๋า
            </button>
            @else
            <button onclick="unlockWallet()" class="flex items-center justify-center px-6 py-4 bg-green-100 hover:bg-green-200 dark:bg-green-900 dark:hover:bg-green-800 text-green-800 dark:text-green-200 rounded-xl font-semibold transition-all">
                <i class="fas fa-unlock mr-2"></i>
                ปลดล็อก
            </button>
            @endif

            @if($wallet->status !== 'suspended')
            <button onclick="suspendWallet()" class="flex items-center justify-center px-6 py-4 bg-red-100 hover:bg-red-200 dark:bg-red-900 dark:hover:bg-red-800 text-red-800 dark:text-red-200 rounded-xl font-semibold transition-all">
                <i class="fas fa-ban mr-2"></i>
                ระงับกระเป๋า
            </button>
            @else
            <button onclick="reactivateWallet()" class="flex items-center justify-center px-6 py-4 bg-blue-100 hover:bg-blue-200 dark:bg-blue-900 dark:hover:bg-blue-800 text-blue-800 dark:text-blue-200 rounded-xl font-semibold transition-all">
                <i class="fas fa-check-circle mr-2"></i>
                เปิดใช้งาน
            </button>
            @endif

            <a href="{{ route('admin.crypto.hd-wallets.index') }}" class="flex items-center justify-center px-6 py-4 bg-gray-100/50 dark:bg-gray-800/50 hover:bg-gray-200 dark:bg-gray-700 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-900 dark:text-white dark:text-gray-200 rounded-xl font-semibold transition-all">
                <i class="fas fa-arrow-left mr-2"></i>
                กลับหน้ารายการ
            </a>
        </div>
    </div>

</div>

@push('scripts')
<script>
function copyAddress(address) {
    navigator.clipboard.writeText(address).then(() => {
        alert('คัดลอก Address สำเร็จ!');
    });
}

function lockWallet() {
    if (confirm('คุณต้องการล็อกกระเป๋านี้หรือไม่?')) {
        fetch('{{ route("admin.crypto.hd-wallets.lock", $wallet) }}', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ minutes: 30, reason: 'Locked by admin' })
        }).then(() => window.location.reload());
    }
}

function unlockWallet() {
    if (confirm('คุณต้องการปลดล็อกกระเป๋านี้หรือไม่?')) {
        fetch('{{ route("admin.crypto.hd-wallets.unlock", $wallet) }}', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            }
        }).then(() => window.location.reload());
    }
}

function suspendWallet() {
    const reason = prompt('กรุณาระบุเหตุผลในการระงับ:');
    if (reason) {
        fetch('{{ route("admin.crypto.hd-wallets.suspend", $wallet) }}', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ reason })
        }).then(() => window.location.reload());
    }
}

function reactivateWallet() {
    if (confirm('คุณต้องการเปิดใช้งานกระเป๋านี้หรือไม่?')) {
        fetch('{{ route("admin.crypto.hd-wallets.reactivate", $wallet) }}', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            }
        }).then(() => window.location.reload());
    }
}
</script>
@endpush
@endsection
