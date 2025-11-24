@extends('layouts.admin-v3')

@section('title', 'Import Token จาก CoinMarketCap')

@section('content')
<div class="space-y-6">
    {{-- Header --}}
    <div class="bg-gradient-to-r from-orange-600 via-red-600 to-pink-600 rounded-2xl shadow-2xl p-8 text-white relative overflow-hidden">
        <div class="absolute top-0 right-0 -mt-4 -mr-4 w-40 h-40 glass-fusion opacity-10 rounded-full" border border-white/20 dark:border-white/10></div>
        <div class="absolute bottom-0 left-0 -mb-4 -ml-4 w-32 h-32 glass-fusion opacity-10 rounded-full" border border-white/20 dark:border-white/10></div>
        <div class="relative z-10">
            <div class="flex items-center gap-4 mb-2">
                <div class="text-5xl">🔗</div>
                <div>
                    <h1 class="text-3xl font-bold">Import Token จาก CoinMarketCap</h1>
                    <p class="text-orange-100 mt-1">นำเข้าข้อมูล Token จาก CoinMarketCap API</p>
                </div>
            </div>
        </div>
    </div>

    {{-- Import Form --}}
    <div class="backdrop-blur-xl bg-white/80 dark:bg-gray-800/80 border border-white/20 dark:border-gray-700/30 rounded-2xl shadow-2xl p-6 hover:scale-105 transition-transform">
        <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-6 flex items-center gap-2">
            <span>📥</span>
            <span>Import Token ใหม่</span>
        </h2>

        <form action="{{ route('admin.tokens.import-cmc.store') }}" method="POST" class="space-y-6">
            @csrf

            {{-- CMC ID Input --}}
            <div>
                <label for="cmc_id" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                    CoinMarketCap ID
                </label>
                <div class="relative">
                    <input
                        type="text"
                        id="cmc_id"
                        name="cmc_id"
                        value="{{ old('cmc_id') }}"
                        placeholder="เช่น: 1027 (สำหรับ Ethereum)"
                        class="w-full px-4 py-3 rounded-xl border-2 border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:ring-4 focus:ring-orange-500/20 focus:border-orange-500 transition-all"
                        required
                    >
                    <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                        <span class="text-gray-400 text-sm">CMC</span>
                    </div>
                </div>
                <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                    💡 <strong>วิธีหา CMC ID:</strong> ไปที่ CoinMarketCap → เลือก Token → ดูใน URL (เช่น /currencies/bitcoin/ → ID = 1)
                </p>
                @error('cmc_id')
                    <p class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                @enderror
            </div>

            {{-- Submit Button --}}
            <div class="flex items-center gap-4">
                <button
                    type="submit"
                    class="px-8 py-3 bg-gradient-to-r from-orange-600 to-red-600 hover:from-orange-700 hover:to-red-700 text-white font-semibold rounded-xl shadow-lg hover:shadow-xl transform hover:scale-105 transition duration-300"
                >
                    <span class="flex items-center gap-2">
                        <span>🚀</span>
                        <span>Import Token</span>
                    </span>
                </button>
                <a
                    href="{{ route('admin.tokens.index') }}"
                    class="px-6 py-3 bg-gray-200 dark:bg-gray-700 hover:bg-gray-300 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 font-semibold rounded-xl transition-all"
                >
                    ยกเลิก
                </a>
            </div>
        </form>
    </div>

    {{-- Instructions --}}
    <div class="bg-blue-50 dark:bg-blue-900 dark:bg-opacity-20 border border-blue-200 dark:border-blue-700 rounded-2xl p-6">
        <h3 class="text-lg font-bold text-blue-900 dark:text-blue-300 mb-4 flex items-center gap-2">
            <span>ℹ️</span>
            <span>คำแนะนำการใช้งาน</span>
        </h3>
        <div class="space-y-3 text-sm text-blue-800 dark:text-blue-200">
            <div class="flex items-start gap-3">
                <span class="font-bold">1️⃣</span>
                <p>ค้นหา Token ที่ต้องการบน <a href="https://coinmarketcap.com" target="_blank" class="underline hover:text-blue-600 dark:hover:text-blue-400">CoinMarketCap</a></p>
            </div>
            <div class="flex items-start gap-3">
                <span class="font-bold">2️⃣</span>
                <p>คัดลอก CMC ID จาก URL (ตัวเลขใน URL หรือจากหน้ารายละเอียด)</p>
            </div>
            <div class="flex items-start gap-3">
                <span class="font-bold">3️⃣</span>
                <p>วาง CMC ID ในฟอร์มด้านบนแล้วกด Import</p>
            </div>
            <div class="flex items-start gap-3">
                <span class="font-bold">4️⃣</span>
                <p>ระบบจะดึงข้อมูล Token (ชื่อ, สัญลักษณ์, ราคา, logo) จาก CMC โดยอัตโนมัติ</p>
            </div>
            <div class="flex items-start gap-3">
                <span class="font-bold">5️⃣</span>
                <p>Token ที่ import จะถูกสร้างในฐานะ "draft" - คุณสามารถแก้ไขและ deploy ได้ภายหลัง</p>
            </div>
        </div>
    </div>

    {{-- Recently Imported Tokens --}}
    @if($importedTokens && $importedTokens->count() > 0)
    <div class="backdrop-blur-xl bg-white/80 dark:bg-gray-800/80 border border-white/20 dark:border-gray-700/30 rounded-2xl shadow-2xl p-6 hover:scale-105 transition-transform">
        <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-6 flex items-center gap-2">
            <span>📜</span>
            <span>Tokens ที่ Import ล่าสุด</span>
        </h2>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gradient-to-r from-orange-600 via-red-600 to-pink-600">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-bold text-white uppercase tracking-wider">
                            Token
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-bold text-white uppercase tracking-wider">
                            CMC ID
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-bold text-white uppercase tracking-wider">
                            สถานะ
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-bold text-white uppercase tracking-wider">
                            Import โดย
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-bold text-white uppercase tracking-wider">
                            วันที่
                        </th>
                        <th class="px-6 py-3 text-right text-xs font-bold text-white uppercase tracking-wider">
                            จัดการ
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-gray-900 divide-y divide-gray-200 dark:divide-gray-700">
                    @foreach($importedTokens as $token)
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center gap-3">
                                @if($token->logo)
                                <img src="{{ $token->logo }}" alt="{{ $token->name }}" class="w-8 h-8 rounded-full">
                                @else
                                <div class="w-8 h-8 rounded-full bg-gradient-to-br from-gray-300 to-gray-400 flex items-center justify-center text-white font-bold text-xs">
                                    {{ substr($token->symbol, 0, 2) }}
                                </div>
                                @endif
                                <div>
                                    <div class="text-sm font-semibold text-gray-900 dark:text-white">
                                        {{ $token->name }}
                                    </div>
                                    <div class="text-xs text-gray-500 dark:text-gray-400">
                                        {{ $token->symbol }}
                                    </div>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-orange-100 dark:bg-orange-900 text-orange-800 dark:text-orange-200">
                                #{{ $token->cmc_id }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @php
                                $statusColors = [
                                    'draft' => 'bg-gray-100 dark:bg-gray-700 text-gray-900 dark:text-gray-300',
                                    'deploying' => 'bg-blue-100 dark:bg-blue-900/30 text-blue-800 dark:text-blue-400',
                                    'deployed' => 'bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-400',
                                    'verified' => 'bg-purple-100 dark:bg-purple-900/30 text-purple-800 dark:text-purple-400',
                                    'active' => 'bg-emerald-100 dark:bg-emerald-900/30 text-emerald-800 dark:text-emerald-400',
                                    'paused' => 'bg-yellow-100 dark:bg-yellow-900/30 text-yellow-800 dark:text-yellow-400',
                                    'suspended' => 'bg-red-100 dark:bg-red-900/30 text-red-800 dark:text-red-400',
                                ];
                            @endphp
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $statusColors[$token->status] ?? $statusColors['draft'] }}">
                                {{ ucfirst($token->status) }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600 dark:text-gray-400">
                            {{ $token->creator->name ?? 'System' }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600 dark:text-gray-400">
                            {{ $token->created_at->format('d/m/Y H:i') }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm">
                            <a href="{{ route('admin.tokens.show', $token->id) }}" class="text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300 font-medium">
                                ดูรายละเอียด →
                            </a>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif
</div>
@endsection
