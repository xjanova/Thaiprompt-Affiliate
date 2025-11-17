@extends('layouts.admin-v3')

@section('title', 'TPIX Network Status')

@section('content')
<div class="space-y-6">
    {{-- Header --}}
    <div class="bg-gradient-to-r from-emerald-600 via-teal-600 to-cyan-600 rounded-2xl shadow-2xl p-8 text-white relative overflow-hidden">
        <div class="absolute top-0 right-0 -mt-4 -mr-4 w-40 h-40 glass-fusion opacity-10 rounded-full" border border-white/20 dark:border-white/10></div>
        <div class="absolute bottom-0 left-0 -mb-4 -ml-4 w-32 h-32 glass-fusion opacity-10 rounded-full" border border-white/20 dark:border-white/10></div>
        <div class="relative z-10">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold mb-2 flex items-center gap-3">
                        <span class="text-5xl">🌐</span>
                        <span>TPIX Network Status</span>
                    </h1>
                    <p class="text-emerald-100">สถานะและข้อมูลเครือข่าย TPIX Blockchain</p>
                </div>
                <div class="flex items-center gap-4">
                    <div class="text-right">
                        <div class="text-sm text-emerald-200 mb-1">สถานะ</div>
                        <div class="flex items-center gap-2">
                            <span class="w-3 h-3 bg-green-400 rounded-full animate-pulse"></span>
                            <span class="text-2xl font-bold">Online</span>
                        </div>
                    </div>
                    <a href="{{ route('admin.tpix.dashboard') }}" class="px-4 py-2 glass-fusion bg-opacity-20 hover:bg-opacity-30 rounded-xl text-sm font-semibold transition">
                        ← Dashboard
                    </a>
                </div>
            </div>
        </div>
    </div>

    {{-- Network Information --}}
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        {{-- Network Name --}}
        <div class="glass-fusion dark:bg-slate-800 rounded-2xl shadow-xl p-6" hover:scale-105 transition-transform border border-white/20 dark:border-white/10>
            <div class="flex items-center gap-3 mb-4">
                <div class="w-12 h-12 rounded-full style="background: var(--arrow-x-primary-gradient)" flex items-center justify-center text-white text-2xl">
                    🏷️
                </div>
                <div>
                    <div class="text-xs text-gray-500 dark:text-gray-400 dark:text-gray-400">Network Name</div>
                    <div class="text-lg font-bold text-gray-900 dark:text-white">{{ $status['network_name'] }}</div>
                </div>
            </div>
        </div>

        {{-- Chain ID --}}
        <div class="glass-fusion dark:bg-slate-800 rounded-2xl shadow-xl p-6" hover:scale-105 transition-transform border border-white/20 dark:border-white/10>
            <div class="flex items-center gap-3 mb-4">
                <div class="w-12 h-12 rounded-full style="background: var(--arrow-x-accent-gradient)" flex items-center justify-center text-white text-2xl">
                    🔗
                </div>
                <div>
                    <div class="text-xs text-gray-500 dark:text-gray-400 dark:text-gray-400">Chain ID</div>
                    <div class="text-lg font-bold text-gray-900 dark:text-white">{{ $status['chain_id'] }}</div>
                </div>
            </div>
        </div>

        {{-- Current Block --}}
        <div class="glass-fusion dark:bg-slate-800 rounded-2xl shadow-xl p-6" hover:scale-105 transition-transform border border-white/20 dark:border-white/10>
            <div class="flex items-center gap-3 mb-4">
                <div class="w-12 h-12 rounded-full style="background: var(--arrow-x-success-gradient)" flex items-center justify-center text-white text-2xl">
                    📦
                </div>
                <div>
                    <div class="text-xs text-gray-500 dark:text-gray-400 dark:text-gray-400">Current Block</div>
                    <div class="text-lg font-bold text-gray-900 dark:text-white">{{ number_format((float)$status['block_number']) }}</div>
                </div>
            </div>
        </div>

        {{-- Block Time --}}
        <div class="glass-fusion dark:bg-slate-800 rounded-2xl shadow-xl p-6" hover:scale-105 transition-transform border border-white/20 dark:border-white/10>
            <div class="flex items-center gap-3 mb-4">
                <div class="w-12 h-12 rounded-full style="background: var(--arrow-x-warning-gradient)" flex items-center justify-center text-white text-2xl">
                    ⏱️
                </div>
                <div>
                    <div class="text-xs text-gray-500 dark:text-gray-400 dark:text-gray-400">Block Time</div>
                    <div class="text-lg font-bold text-gray-900 dark:text-white">{{ $status['block_time'] }} วินาที</div>
                </div>
            </div>
        </div>
    </div>

    {{-- Gas and Supply Information --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        {{-- Gas Price --}}
        <div class="bg-gradient-to-br from-yellow-400 via-orange-500 to-red-500 rounded-2xl shadow-xl p-6 text-white">
            <div class="flex items-center justify-between mb-4">
                <div class="text-5xl">⛽</div>
                <span class="px-3 py-1 glass-fusion bg-opacity-20 rounded-xl text-xs font-semibold">
                    Real-time
                </span>
            </div>
            <p class="text-white text-opacity-80 text-sm mb-1">Gas Price</p>
            <p class="text-4xl font-bold">{{ number_format((float)$status['gas_price_gwei'], 2) }}</p>
            <p class="text-xs text-white text-opacity-70 mt-2">Gwei ({{ number_format((float)$status['gas_price']) }} Wei)</p>
        </div>

        {{-- Total Supply --}}
        <div class="bg-gradient-to-br from-blue-500 to-indigo-600 rounded-2xl shadow-xl p-6 text-white">
            <div class="flex items-center justify-between mb-4">
                <div class="text-5xl">💎</div>
            </div>
            <p class="text-white text-opacity-80 text-sm mb-1">Total Supply</p>
            <p class="text-4xl font-bold">{{ number_format((float)$status['total_supply']) }}</p>
            <p class="text-xs text-white text-opacity-70 mt-2">TPIX Tokens</p>
        </div>

        {{-- Network Health --}}
        <div class="bg-gradient-to-br from-emerald-500 to-green-600 rounded-2xl shadow-xl p-6 text-white">
            <div class="flex items-center justify-between mb-4">
                <div class="text-5xl">❤️</div>
                <span class="px-3 py-1 glass-fusion bg-opacity-20 rounded-xl text-xs font-semibold">
                    100%
                </span>
            </div>
            <p class="text-white text-opacity-80 text-sm mb-1">Network Health</p>
            <p class="text-4xl font-bold">Excellent</p>
            <p class="text-xs text-white text-opacity-70 mt-2">เครือข่ายทำงานปกติ</p>
        </div>
    </div>

    {{-- Connection Information --}}
    <div class="glass-fusion dark:bg-slate-800 rounded-2xl shadow-xl p-6" hover:scale-105 transition-transform border border-white/20 dark:border-white/10>
        <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-6 flex items-center gap-2">
            <span>🔌</span>
            <span>ข้อมูลการเชื่อมต่อ</span>
        </h2>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            {{-- RPC URL --}}
            <div class="bg-gray-100/50 dark:bg-gray-800/50/50 dark:bg-gray-800/50 dark:bg-slate-700 rounded-xl p-5">
                <div class="flex items-center gap-3 mb-3">
                    <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-cyan-500 to-blue-600 flex items-center justify-center text-white text-xl">
                        🌍
                    </div>
                    <div>
                        <div class="text-xs text-gray-500 dark:text-gray-400 dark:text-gray-400">RPC Endpoint</div>
                        <div class="text-sm font-semibold text-gray-900 dark:text-white">Remote Procedure Call</div>
                    </div>
                </div>
                <div class="glass-fusion dark:bg-slate-800 rounded-xl p-3 border border-gray-200 dark:border-gray-700 dark:border-slate-600" border border-white/20 dark:border-white/10>
                    <code class="text-sm text-gray-900 dark:text-white dark:text-gray-200 break-all">{{ $status['rpc_url'] }}</code>
                </div>
                <button onclick="copyToClipboard('{{ $status['rpc_url'] }}')" class="mt-3 w-full px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold rounded-xl transition">
                    📋 Copy RPC URL
                </button>
            </div>

            {{-- Explorer URL --}}
            <div class="bg-gray-100/50 dark:bg-gray-800/50/50 dark:bg-gray-800/50 dark:bg-slate-700 rounded-xl p-5">
                <div class="flex items-center gap-3 mb-3">
                    <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-purple-500 to-pink-600 flex items-center justify-center text-white text-xl">
                        🔍
                    </div>
                    <div>
                        <div class="text-xs text-gray-500 dark:text-gray-400 dark:text-gray-400">Block Explorer</div>
                        <div class="text-sm font-semibold text-gray-900 dark:text-white">Blockchain Explorer</div>
                    </div>
                </div>
                <div class="glass-fusion dark:bg-slate-800 rounded-xl p-3 border border-gray-200 dark:border-gray-700 dark:border-slate-600" border border-white/20 dark:border-white/10>
                    <code class="text-sm text-gray-900 dark:text-white dark:text-gray-200 break-all">{{ $status['explorer_url'] }}</code>
                </div>
                <a href="{{ $status['explorer_url'] }}" target="_blank" class="mt-3 block w-full px-4 py-2 bg-purple-600 hover:bg-purple-700 text-white text-sm font-semibold rounded-xl transition text-center">
                    🚀 เปิด Explorer
                </a>
            </div>
        </div>
    </div>

    {{-- Network Features --}}
    <div class="glass-fusion dark:bg-slate-800 rounded-2xl shadow-xl p-6" hover:scale-105 transition-transform border border-white/20 dark:border-white/10>
        <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-6 flex items-center gap-2">
            <span>⚙️</span>
            <span>คุณสมบัติเครือข่าย</span>
        </h2>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            <div class="flex items-center gap-3 p-4 bg-green-50 dark:bg-green-900 dark:bg-opacity-20 border border-green-200 dark:border-green-700 rounded-xl">
                <div class="text-2xl">✅</div>
                <div>
                    <div class="text-sm font-bold text-green-900 dark:text-green-300">Smart Contracts</div>
                    <div class="text-xs text-green-700 dark:text-green-400">ใช้งานได้</div>
                </div>
            </div>

            <div class="flex items-center gap-3 p-4 bg-blue-50 dark:bg-blue-900 dark:bg-opacity-20 border border-blue-200 dark:border-blue-700 rounded-xl">
                <div class="text-2xl">✅</div>
                <div>
                    <div class="text-sm font-bold text-blue-900 dark:text-blue-300">ERC-20 Tokens</div>
                    <div class="text-xs text-blue-700 dark:text-blue-400">รองรับ</div>
                </div>
            </div>

            <div class="flex items-center gap-3 p-4 bg-purple-50 dark:bg-purple-900 dark:bg-opacity-20 border border-purple-200 dark:border-purple-700 rounded-xl">
                <div class="text-2xl">✅</div>
                <div>
                    <div class="text-sm font-bold text-purple-900 dark:text-purple-300">NFT Support</div>
                    <div class="text-xs text-purple-700 dark:text-purple-400">รองรับ ERC-721</div>
                </div>
            </div>

            <div class="flex items-center gap-3 p-4 bg-yellow-50 dark:bg-yellow-900 dark:bg-opacity-20 border border-yellow-200 dark:border-yellow-700 rounded-xl">
                <div class="text-2xl">✅</div>
                <div>
                    <div class="text-sm font-bold text-yellow-900 dark:text-yellow-300">Fast Transactions</div>
                    <div class="text-xs text-yellow-700 dark:text-yellow-400">{{ $status['block_time'] }}s block time</div>
                </div>
            </div>
        </div>
    </div>

    {{-- Connection Test --}}
    <div class="bg-gradient-to-r from-gray-100 to-gray-200 dark:from-slate-700 dark:to-slate-800 rounded-2xl shadow-xl p-6">
        <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
            <span>🧪</span>
            <span>ทดสอบการเชื่อมต่อ</span>
        </h2>
        <p class="text-sm text-gray-600 dark:text-gray-400 dark:text-gray-400 mb-4">
            ทดสอบการเชื่อมต่อกับ TPIX Blockchain เพื่อตรวจสอบสถานะการทำงาน
        </p>
        <button
            onclick="testConnection()"
            class="px-6 py-3 bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-700 hover:to-teal-700 text-white font-semibold rounded-xl shadow-lg hover:shadow-xl transform hover:scale-105 transition duration-300"
        >
            <span class="flex items-center gap-2">
                <span>🔄</span>
                <span id="testBtnText">ทดสอบการเชื่อมต่อ</span>
            </span>
        </button>
        <div id="testResult" class="mt-4 hidden"></div>
    </div>
</div>

@push('scripts')
<script>
function copyToClipboard(text) {
    navigator.clipboard.writeText(text).then(() => {
        alert('คัดลอกไปยังคลิปบอร์ดแล้ว!');
    }).catch(err => {
        console.error('Failed to copy:', err);
    });
}

async function testConnection() {
    const btn = document.querySelector('button[onclick="testConnection()"]');
    const btnText = document.getElementById('testBtnText');
    const resultDiv = document.getElementById('testResult');

    // Disable button
    btn.disabled = true;
    btnText.textContent = 'กำลังทดสอบ...';

    try {
        const response = await fetch('{{ route('admin.tpix.check-connection') }}');
        const data = await response.json();

        if (data.success) {
            resultDiv.className = 'mt-4 p-4 bg-green-100 dark:bg-green-900 dark:bg-opacity-30 border border-green-500 rounded-xl';
            resultDiv.innerHTML = `
                <div class="flex items-center gap-3">
                    <div class="text-3xl">✅</div>
                    <div>
                        <div class="font-bold text-green-900 dark:text-green-300">${data.message}</div>
                        <div class="text-sm text-green-700 dark:text-green-400">Block Number: ${data.block_number}</div>
                    </div>
                </div>
            `;
        } else {
            throw new Error(data.message);
        }
    } catch (error) {
        resultDiv.className = 'mt-4 p-4 bg-red-100 dark:bg-red-900 dark:bg-opacity-30 border border-red-500 rounded-xl';
        resultDiv.innerHTML = `
            <div class="flex items-center gap-3">
                <div class="text-3xl">❌</div>
                <div>
                    <div class="font-bold text-red-900 dark:text-red-300">เชื่อมต่อล้มเหลว</div>
                    <div class="text-sm text-red-700 dark:text-red-400">${error.message}</div>
                </div>
            </div>
        `;
    } finally {
        resultDiv.classList.remove('hidden');
        btn.disabled = false;
        btnText.textContent = 'ทดสอบการเชื่อมต่อ';
    }
}
</script>
@endpush
@endsection
