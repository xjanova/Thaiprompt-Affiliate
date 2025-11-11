@extends('layouts.user')

@section('title', 'รายละเอียดธุรกรรม Crypto')

@section('content')
<div class="max-w-4xl mx-auto space-y-6 pb-20 lg:pb-6">
    <!-- Header -->
    <div class="bg-gradient-to-r from-cyan-600 via-blue-600 to-indigo-600 rounded-2xl shadow-2xl p-8 text-white">
        <div class="flex items-center gap-4">
            <div class="w-16 h-16 bg-white/20 rounded-xl flex items-center justify-center backdrop-blur-sm">
                <span class="text-3xl">🔍</span>
            </div>
            <div>
                <h1 class="text-3xl font-bold">รายละเอียดธุรกรรม</h1>
                <p class="text-cyan-100 mt-1">ข้อมูลครบถ้วนของธุรกรรม Crypto</p>
            </div>
        </div>
    </div>

    @if(isset($transaction))
        <!-- Transaction Status Banner -->
        <div class="bg-white rounded-2xl shadow-xl p-6">
            <div class="text-center">
                <!-- Status Icon -->
                <div class="w-24 h-24 mx-auto mb-4
                    @if($transaction->status === 'completed') bg-gradient-to-br from-green-400 to-emerald-600
                    @elseif($transaction->status === 'pending') bg-gradient-to-br from-yellow-400 to-orange-600
                    @elseif($transaction->status === 'failed') bg-gradient-to-br from-red-400 to-rose-600
                    @else bg-gradient-to-br from-gray-400 to-slate-600
                    @endif
                    rounded-full flex items-center justify-center shadow-2xl">
                    <span class="text-5xl">
                        @if($transaction->status === 'completed') ✅
                        @elseif($transaction->status === 'pending') ⏳
                        @elseif($transaction->status === 'failed') ❌
                        @else 📋
                        @endif
                    </span>
                </div>

                <!-- Status Text -->
                <h2 class="text-2xl font-bold text-gray-800 mb-2">
                    @if($transaction->status === 'completed') ธุรกรรมสำเร็จ
                    @elseif($transaction->status === 'pending') กำลังดำเนินการ
                    @elseif($transaction->status === 'failed') ธุรกรรมล้มเหลว
                    @else ธุรกรรม
                    @endif
                </h2>
                <p class="text-gray-600">{{ $transaction->created_at->format('d/m/Y H:i:s') }}</p>
            </div>
        </div>

        <!-- Transaction Details -->
        <div class="bg-white rounded-2xl shadow-xl p-6">
            <h3 class="text-xl font-bold text-gray-800 mb-6 flex items-center gap-2">
                <span>📋</span> ข้อมูลธุรกรรม
            </h3>

            <div class="space-y-4">
                <!-- Transaction ID -->
                <div class="flex justify-between items-center py-3 border-b border-gray-200">
                    <span class="text-gray-600">รหัสธุรกรรม:</span>
                    <code class="px-3 py-1 bg-gray-100 rounded font-mono text-sm">
                        {{ $transaction->transaction_hash ?? $transaction->id }}
                    </code>
                </div>

                <!-- Type -->
                <div class="flex justify-between items-center py-3 border-b border-gray-200">
                    <span class="text-gray-600">ประเภท:</span>
                    <span class="font-bold
                        @if($transaction->type === 'deposit') text-green-600
                        @elseif($transaction->type === 'withdraw') text-red-600
                        @else text-blue-600
                        @endif">
                        @if($transaction->type === 'deposit') 💰 ฝาก
                        @elseif($transaction->type === 'withdraw') 📤 ถอน
                        @elseif($transaction->type === 'transfer') 🔄 โอน
                        @elseif($transaction->type === 'exchange') 💱 แลกเปลี่ยน
                        @else {{ ucfirst($transaction->type) }}
                        @endif
                    </span>
                </div>

                <!-- Currency -->
                <div class="flex justify-between items-center py-3 border-b border-gray-200">
                    <span class="text-gray-600">สกุลเงิน:</span>
                    <span class="font-bold text-gray-800">{{ strtoupper($transaction->currency ?? 'N/A') }}</span>
                </div>

                <!-- Amount -->
                <div class="flex justify-between items-center py-3 border-b border-gray-200">
                    <span class="text-gray-600">จำนวน:</span>
                    <span class="text-2xl font-bold text-gray-800">
                        {{ number_format($transaction->amount ?? 0, 8) }} {{ strtoupper($transaction->currency ?? '') }}
                    </span>
                </div>

                <!-- Fee -->
                @if(isset($transaction->fee))
                    <div class="flex justify-between items-center py-3 border-b border-gray-200">
                        <span class="text-gray-600">ค่าธรรมเนียม:</span>
                        <span class="font-bold text-orange-600">
                            {{ number_format($transaction->fee, 8) }} {{ strtoupper($transaction->currency ?? '') }}
                        </span>
                    </div>
                @endif

                <!-- Net Amount -->
                @if(isset($transaction->net_amount))
                    <div class="flex justify-between items-center py-3 border-b border-gray-200">
                        <span class="text-gray-600">จำนวนสุทธิ:</span>
                        <span class="text-xl font-bold text-green-600">
                            {{ number_format($transaction->net_amount, 8) }} {{ strtoupper($transaction->currency ?? '') }}
                        </span>
                    </div>
                @endif

                <!-- From/To Address -->
                @if(isset($transaction->from_address))
                    <div class="flex justify-between items-start py-3 border-b border-gray-200">
                        <span class="text-gray-600">จาก:</span>
                        <code class="px-3 py-1 bg-gray-100 rounded font-mono text-xs break-all max-w-md text-right">
                            {{ $transaction->from_address }}
                        </code>
                    </div>
                @endif

                @if(isset($transaction->to_address))
                    <div class="flex justify-between items-start py-3 border-b border-gray-200">
                        <span class="text-gray-600">ถึง:</span>
                        <code class="px-3 py-1 bg-gray-100 rounded font-mono text-xs break-all max-w-md text-right">
                            {{ $transaction->to_address }}
                        </code>
                    </div>
                @endif

                <!-- Status -->
                <div class="flex justify-between items-center py-3 border-b border-gray-200">
                    <span class="text-gray-600">สถานะ:</span>
                    <span class="px-4 py-2 rounded-full font-bold
                        @if($transaction->status === 'completed') bg-green-100 text-green-800
                        @elseif($transaction->status === 'pending') bg-yellow-100 text-yellow-800
                        @elseif($transaction->status === 'failed') bg-red-100 text-red-800
                        @else bg-gray-100 text-gray-800
                        @endif">
                        @if($transaction->status === 'completed') ✅ สำเร็จ
                        @elseif($transaction->status === 'pending') ⏳ รอดำเนินการ
                        @elseif($transaction->status === 'failed') ❌ ล้มเหลว
                        @else {{ $transaction->status }}
                        @endif
                    </span>
                </div>

                <!-- Confirmations -->
                @if(isset($transaction->confirmations))
                    <div class="flex justify-between items-center py-3 border-b border-gray-200">
                        <span class="text-gray-600">Confirmations:</span>
                        <span class="font-bold text-gray-800">{{ $transaction->confirmations }} / {{ $transaction->required_confirmations ?? 6 }}</span>
                    </div>
                @endif

                <!-- Created At -->
                <div class="flex justify-between items-center py-3">
                    <span class="text-gray-600">วันที่ทำรายการ:</span>
                    <div class="text-right">
                        <div class="font-bold text-gray-800">{{ $transaction->created_at->format('d/m/Y') }}</div>
                        <div class="text-sm text-gray-500">{{ $transaction->created_at->format('H:i:s') }}</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Notes -->
        @if(isset($transaction->notes) && $transaction->notes)
            <div class="bg-gradient-to-br from-blue-50 to-indigo-50 rounded-2xl shadow-xl p-6 border border-blue-200">
                <h3 class="text-lg font-bold text-gray-800 mb-3 flex items-center gap-2">
                    <span>📝</span> หมายเหตุ
                </h3>
                <p class="text-gray-700">{{ $transaction->notes }}</p>
            </div>
        @endif

        <!-- Blockchain Explorer Link -->
        @if(isset($transaction->transaction_hash))
            <div class="bg-white rounded-2xl shadow-xl p-6">
                <h3 class="text-lg font-bold text-gray-800 mb-4 flex items-center gap-2">
                    <span>🔗</span> Blockchain Explorer
                </h3>
                <a href="https://etherscan.io/tx/{{ $transaction->transaction_hash }}"
                   target="_blank"
                   class="block px-6 py-4 bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 text-white rounded-xl font-bold text-center transition-all shadow-lg hover:shadow-xl">
                    🔍 ดูบน Blockchain Explorer →
                </a>
                <p class="text-xs text-gray-500 text-center mt-2">ตรวจสอบรายละเอียดบน Blockchain</p>
            </div>
        @endif

        <!-- Actions -->
        <div class="flex gap-4">
            <a href="{{ route('user.crypto-wallet.transactions') }}"
               class="flex-1 px-6 py-4 bg-gray-200 hover:bg-gray-300 text-gray-700 rounded-xl font-bold text-center transition-colors">
                ← กลับไปรายการธุรกรรม
            </a>

            @if($transaction->status === 'pending')
                <button onclick="refreshTransaction()"
                        class="flex-1 px-6 py-4 bg-blue-600 hover:bg-blue-700 text-white rounded-xl font-bold transition-colors">
                    🔄 รีเฟรชสถานะ
                </button>
            @endif
        </div>
    @else
        <!-- No Transaction Found -->
        <div class="bg-white rounded-2xl shadow-xl p-12 text-center">
            <span class="text-6xl mb-4 block">❌</span>
            <h2 class="text-2xl font-bold text-gray-800 mb-2">ไม่พบข้อมูลธุรกรรม</h2>
            <p class="text-gray-600 mb-6">ไม่สามารถโหลดข้อมูลธุรกรรมได้</p>
            <a href="{{ route('user.crypto-wallet.transactions') }}"
               class="inline-block px-8 py-3 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-bold transition-colors">
                ← กลับไปรายการธุรกรรม
            </a>
        </div>
    @endif
</div>

@push('scripts')
<script>
function refreshTransaction() {
    window.location.reload();
}
</script>
@endpush
@endsection
