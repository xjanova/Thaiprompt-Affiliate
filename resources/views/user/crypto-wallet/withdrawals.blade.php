@extends('layouts.user-arrow-x')

@section('title', 'ประวัติการถอนเหรียญ')

@section('content')
<div class="container mx-auto px-4 py-6">
    {{-- Premium Hero Header (Orange-Red for Withdrawal History) --}}
    <div class="relative overflow-hidden bg-gradient-to-r from-orange-600 via-red-600 to-pink-600 dark:from-orange-800 dark:via-red-800 dark:to-pink-800 rounded-2xl shadow-2xl p-8 mb-8">
        {{-- Animated Background Orbs --}}
        <div class="absolute inset-0 opacity-10">
            <div class="absolute top-0 right-0 w-96 h-96 bg-white rounded-full blur-3xl animate-pulse"></div>
            <div class="absolute bottom-0 left-0 w-96 h-96 bg-white rounded-full blur-3xl animate-pulse" style="animation-delay: 0.5s"></div>
        </div>

        {{-- Floating Icon Background --}}
        <div class="absolute inset-0 overflow-hidden pointer-events-none">
            <div class="absolute text-white/10 text-8xl top-10 right-20" style="animation: float 6s ease-in-out infinite">
                <i class="fas fa-file-export"></i>
            </div>
        </div>

        {{-- Content --}}
        <div class="relative z-10">
            <div class="flex items-center justify-between mb-4">
                <div class="flex items-center gap-4">
                    <div class="glass-fusion p-4 rounded-2xl">
                        <i class="fas fa-history text-3xl text-white drop-shadow-lg"></i>
                    </div>
                    <div>
                        <h1 class="text-3xl md:text-4xl font-bold text-white drop-shadow-lg">
                            📜 ประวัติการถอนเหรียญ
                        </h1>
                        <p class="text-orange-100 mt-1">
                            คำขอถอนเหรียญทั้งหมด
                        </p>
                    </div>
                </div>
                <a href="{{ route('user.crypto-wallet.index') }}"
                   class="glass-fusion hover:bg-white/30 rounded-lg px-4 py-2 text-white font-medium transition-all flex items-center gap-2">
                    <i class="fas fa-arrow-left"></i>
                    <span class="hidden md:inline">กลับหน้าหลัก</span>
                </a>
            </div>
        </div>
    </div>

    @if(session('success'))
        <div class="mb-4 p-4 bg-green-100 dark:bg-green-900/30 border border-green-400 dark:border-green-700 text-green-700 dark:text-green-400 rounded-lg">
            {{ session('success') }}
        </div>
    @endif

    <!-- Withdrawals List -->
    <div class="space-y-4">
        @forelse($withdrawals as $withdrawal)
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-md p-6">
                <div class="flex items-start justify-between mb-4">
                    <div>
                        <div class="flex items-center gap-2 mb-2">
                            <h3 class="text-xl font-bold text-gray-800 dark:text-gray-100">
                                {{ number_format($withdrawal->amount, 8) }} {{ $withdrawal->currency->code }}
                            </h3>
                            @if($withdrawal->status === 'pending')
                                <span class="px-3 py-1 bg-yellow-100 dark:bg-yellow-900/30 text-yellow-700 dark:text-yellow-400 rounded-full text-sm font-medium">
                                    ⏳ รอดำเนินการ
                                </span>
                            @elseif($withdrawal->status === 'approved')
                                <span class="px-3 py-1 bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-400 rounded-full text-sm font-medium">
                                    👍 อนุมัติแล้ว
                                </span>
                            @elseif($withdrawal->status === 'completed')
                                <span class="px-3 py-1 bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400 rounded-full text-sm font-medium">
                                    ✓ สำเร็จ
                                </span>
                            @elseif($withdrawal->status === 'rejected')
                                <span class="px-3 py-1 bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-400 rounded-full text-sm font-medium">
                                    ✕ ถูกปฏิเสธ
                                </span>
                            @elseif($withdrawal->status === 'cancelled')
                                <span class="px-3 py-1 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-400 rounded-full text-sm font-medium">
                                    ยกเลิก
                                </span>
                            @else
                                <span class="px-3 py-1 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-400 rounded-full text-sm font-medium">
                                    {{ $withdrawal->status }}
                                </span>
                            @endif
                        </div>
                        <p class="text-sm text-gray-500 dark:text-gray-400">
                            Request ID: {{ $withdrawal->request_id }}
                        </p>
                    </div>
                    <div class="text-right text-sm text-gray-600 dark:text-gray-400">
                        <div>{{ $withdrawal->created_at->format('d/m/Y') }}</div>
                        <div>{{ $withdrawal->created_at->format('H:i') }}</div>
                    </div>
                </div>

                <!-- Details -->
                <div class="grid md:grid-cols-2 gap-4 mb-4 p-4 bg-gray-50 dark:bg-gray-700 rounded-lg">
                    <div>
                        <div class="text-sm text-gray-600 dark:text-gray-400 mb-1">ที่อยู่ปลายทาง</div>
                        <div class="font-mono text-xs text-gray-800 dark:text-gray-100 break-all">
                            {{ $withdrawal->to_address }}
                        </div>
                    </div>
                    <div>
                        <div class="text-sm text-gray-600 dark:text-gray-400 mb-1">เครือข่าย</div>
                        <div class="font-semibold text-gray-800 dark:text-gray-100 uppercase">
                            {{ $withdrawal->network }}
                        </div>
                    </div>
                </div>

                <!-- Amount Breakdown -->
                <div class="space-y-2 mb-4 text-sm">
                    <div class="flex justify-between">
                        <span class="text-gray-600 dark:text-gray-400">จำนวนถอน:</span>
                        <span class="font-semibold text-gray-800 dark:text-gray-100">
                            {{ number_format($withdrawal->amount, 8) }} {{ $withdrawal->currency->code }}
                        </span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600 dark:text-gray-400">ค่าธรรมเนียมเครือข่าย:</span>
                        <span class="text-red-600 dark:text-red-400">
                            - {{ number_format($withdrawal->network_fee, 8) }}
                        </span>
                    </div>
                    @if($withdrawal->platform_fee > 0)
                        <div class="flex justify-between">
                            <span class="text-gray-600 dark:text-gray-400">ค่าธรรมเนียมแพลตฟอร์ม:</span>
                            <span class="text-red-600 dark:text-red-400">
                                - {{ number_format($withdrawal->platform_fee, 8) }}
                            </span>
                        </div>
                    @endif
                    <div class="border-t dark:border-gray-600 pt-2 flex justify-between font-bold">
                        <span class="text-gray-800 dark:text-gray-100">จำนวนที่ได้รับ:</span>
                        <span class="text-green-600 dark:text-green-400">
                            {{ number_format($withdrawal->net_amount, 8) }} {{ $withdrawal->currency->code }}
                        </span>
                    </div>
                </div>

                <!-- Actions -->
                <div class="flex gap-2">
                    @if($withdrawal->isPending())
                        <form action="{{ route('user.crypto-wallet.withdrawal.cancel', $withdrawal->id) }}" method="POST" class="flex-1"
                              onsubmit="return confirm('คุณแน่ใจที่จะยกเลิกคำขอนี้?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit"
                                    class="w-full px-4 py-2 bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-400 rounded-lg font-medium hover:bg-red-200 dark:hover:bg-red-900/50 transition-all">
                                ยกเลิกคำขอ
                            </button>
                        </form>
                    @endif

                    @if($withdrawal->cryptoTransaction && $withdrawal->cryptoTransaction->tx_hash)
                        <a href="{{ $withdrawal->cryptoTransaction->getExplorerUrl() }}" target="_blank"
                           class="flex-1 px-4 py-2 bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-400 rounded-lg font-medium text-center hover:bg-blue-200 dark:hover:bg-blue-900/50 transition-all">
                            🔗 ดูใน Block Explorer
                        </a>
                    @endif
                </div>

                <!-- Rejection Reason -->
                @if($withdrawal->status === 'rejected' && $withdrawal->rejection_reason)
                    <div class="mt-4 p-3 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg">
                        <div class="text-sm font-medium text-red-900 dark:text-red-300 mb-1">เหตุผลที่ถูกปฏิเสธ:</div>
                        <div class="text-sm text-red-800 dark:text-red-400">{{ $withdrawal->rejection_reason }}</div>
                    </div>
                @endif
            </div>
        @empty
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-md p-12 text-center">
                <div class="text-6xl mb-4">📤</div>
                <p class="text-gray-500 dark:text-gray-400 mb-4">ยังไม่มีประวัติการถอน</p>
                <a href="{{ route('user.crypto-wallet.withdraw') }}"
                   class="inline-block bg-gradient-to-r from-amber-500 to-orange-600 text-white px-6 py-3 rounded-lg font-medium hover:from-amber-600 hover:to-orange-700 transition-all">
                    ถอนเหรียญ
                </a>
            </div>
        @endforelse
    </div>

    <!-- Pagination -->
    @if($withdrawals->hasPages())
        <div class="mt-6">
            {{ $withdrawals->links() }}
        </div>
    @endif
</div>
@endsection

@push('styles')
<style>
.glass-fusion {
    background: rgba(255, 255, 255, 0.15);
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255, 255, 255, 0.2);
}
@keyframes float {
    0%, 100% { transform: translateY(0px); }
    50% { transform: translateY(-20px); }
}
</style>
@endpush
