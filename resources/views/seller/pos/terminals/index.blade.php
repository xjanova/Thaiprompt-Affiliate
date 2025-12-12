@extends('layouts.seller')

@section('title', 'จัดการ POS Terminal')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">🖥️ จัดการ POS Terminal</h1>
            <p class="text-gray-500 dark:text-gray-400 mt-1">ลงทะเบียนและจัดการ POS Desktop App</p>
        </div>
        <a href="{{ route('seller.pos.index') }}" class="px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700">
            ← กลับ
        </a>
    </div>

    <!-- Statistics -->
    <div class="grid grid-cols-2 md:grid-cols-5 gap-4">
        <div class="bg-blue-500 rounded-lg p-4 text-white">
            <div class="text-2xl font-bold">{{ $stats['total_api_keys'] }}</div>
            <div class="text-sm opacity-80">API Keys ทั้งหมด</div>
        </div>
        <div class="bg-green-500 rounded-lg p-4 text-white">
            <div class="text-2xl font-bold">{{ $stats['active_api_keys'] }}</div>
            <div class="text-sm opacity-80">API Keys ใช้งานได้</div>
        </div>
        <div class="bg-purple-500 rounded-lg p-4 text-white">
            <div class="text-2xl font-bold">{{ $stats['total_terminals'] }}</div>
            <div class="text-sm opacity-80">Terminals ทั้งหมด</div>
        </div>
        <div class="bg-orange-500 rounded-lg p-4 text-white">
            <div class="text-2xl font-bold">{{ $stats['active_terminals'] }}</div>
            <div class="text-sm opacity-80">Terminals ใช้งาน</div>
        </div>
        <div class="bg-cyan-500 rounded-lg p-4 text-white">
            <div class="text-2xl font-bold">{{ $stats['online_terminals'] }}</div>
            <div class="text-sm opacity-80">Terminals ออนไลน์</div>
        </div>
    </div>

    <!-- Store Info -->
    <div class="bg-blue-50 dark:bg-blue-900/30 border border-blue-200 dark:border-blue-800 rounded-lg p-4">
        <div class="flex items-center gap-3">
            <span class="text-2xl">🏪</span>
            <div>
                <div class="font-bold text-blue-900 dark:text-blue-100">{{ $store->name }}</div>
                <div class="text-sm text-blue-700 dark:text-blue-300">
                    รหัสร้าน: <code class="bg-blue-200 dark:bg-blue-800 px-2 py-0.5 rounded">{{ $store->shop_code ?? $store->id }}</code>
                </div>
            </div>
        </div>
    </div>

    <!-- New API Key Alert -->
    @if(session('new_api_key'))
    <div class="bg-green-50 dark:bg-green-900/30 border border-green-300 dark:border-green-700 rounded-lg p-4">
        <div class="flex items-start gap-3">
            <span class="text-2xl">🔑</span>
            <div class="flex-1">
                <div class="font-bold text-green-800 dark:text-green-200">API Key ใหม่สร้างสำเร็จ!</div>
                <p class="text-sm text-green-700 dark:text-green-300 mt-1">คัดลอก Key นี้ไปใส่ในโปรแกรม POS (จะแสดงครั้งเดียว)</p>
                <div class="mt-2 flex items-center gap-2">
                    <code id="newApiKey" class="bg-green-200 dark:bg-green-800 px-3 py-2 rounded font-mono text-sm select-all">{{ session('new_api_key') }}</code>
                    <button onclick="copyApiKey()" class="px-3 py-2 bg-green-600 text-white rounded hover:bg-green-700 text-sm">
                        📋 คัดลอก
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- Create API Key Section -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
        <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4">➕ สร้าง API Key ใหม่</h3>
        <p class="text-gray-600 dark:text-gray-400 text-sm mb-4">
            สร้าง API Key เพื่อลงทะเบียน POS Terminal ใหม่ นำ Key ไปกรอกในโปรแกรม POS Desktop
        </p>
        <form action="{{ route('seller.pos.api-keys.store') }}" method="POST" class="flex gap-4 items-end">
            @csrf
            <div class="flex-1">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">ชื่อ (ไม่บังคับ)</label>
                <input type="text" name="name" placeholder="เช่น เครื่องหน้าร้าน, แคชเชียร์ 1"
                    class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white">
            </div>
            <button type="submit" class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 font-medium">
                🔑 สร้าง API Key
            </button>
        </form>
    </div>

    <!-- API Keys List -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow">
        <div class="p-4 border-b border-gray-200 dark:border-gray-700">
            <h3 class="text-lg font-bold text-gray-900 dark:text-white">🔑 API Keys ของร้าน</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50 dark:bg-gray-700">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">ชื่อ</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">API Key</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">สถานะ</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Terminals</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">ใช้งานล่าสุด</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">จัดการ</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse($apiKeys as $apiKey)
                    <tr>
                        <td class="px-4 py-3 text-sm text-gray-900 dark:text-white">
                            {{ $apiKey->name ?? 'ไม่ระบุชื่อ' }}
                        </td>
                        <td class="px-4 py-3">
                            <code class="bg-gray-100 dark:bg-gray-700 px-2 py-1 rounded text-xs font-mono">
                                {{ substr($apiKey->key, 0, 12) }}...
                            </code>
                            <button onclick="copyToClipboard('{{ $apiKey->key }}')" class="ml-1 text-blue-600 hover:text-blue-800 text-xs">📋</button>
                        </td>
                        <td class="px-4 py-3">
                            <span class="px-2 py-1 text-xs rounded-full {{ $apiKey->getStatusBadgeClass() }}">
                                {{ $apiKey->getStatusText() }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-400">
                            {{ $apiKey->terminals->count() }} เครื่อง
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400">
                            {{ $apiKey->last_used_at?->diffForHumans() ?? 'ยังไม่เคยใช้' }}
                        </td>
                        <td class="px-4 py-3 text-sm">
                            <div class="flex gap-2">
                                <form action="{{ route('seller.pos.api-keys.toggle-block', $apiKey) }}" method="POST" class="inline">
                                    @csrf
                                    <button type="submit" class="text-{{ $apiKey->is_blocked ? 'green' : 'orange' }}-600 hover:underline text-xs">
                                        {{ $apiKey->is_blocked ? '✅ ปลดบล็อก' : '⛔ บล็อก' }}
                                    </button>
                                </form>
                                <form action="{{ route('seller.pos.api-keys.destroy', $apiKey) }}" method="POST" class="inline"
                                    onsubmit="return confirm('ลบ API Key นี้?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-red-600 hover:underline text-xs">🗑️ ลบ</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">
                            ยังไม่มี API Key - กดปุ่ม "สร้าง API Key" เพื่อเริ่มต้น
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Terminals List -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow">
        <div class="p-4 border-b border-gray-200 dark:border-gray-700">
            <h3 class="text-lg font-bold text-gray-900 dark:text-white">🖥️ POS Terminals ที่ลงทะเบียนแล้ว</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50 dark:bg-gray-700">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Device</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Product Key</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">สถานะ</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">ออนไลน์</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Sync ล่าสุด</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">จัดการ</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse($terminals as $terminal)
                    <tr>
                        <td class="px-4 py-3">
                            <div class="text-sm font-medium text-gray-900 dark:text-white">
                                {{ $terminal->device_name ?? 'Unknown Device' }}
                            </div>
                            <div class="text-xs text-gray-500 dark:text-gray-400">
                                {{ $terminal->device_model }} • {{ $terminal->platform }}
                            </div>
                        </td>
                        <td class="px-4 py-3">
                            <code class="bg-gray-100 dark:bg-gray-700 px-2 py-1 rounded text-xs font-mono">
                                {{ $terminal->product_key }}
                            </code>
                        </td>
                        <td class="px-4 py-3">
                            @if($terminal->status === 'active')
                            <span class="px-2 py-1 text-xs rounded-full bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">
                                ใช้งาน
                            </span>
                            @else
                            <span class="px-2 py-1 text-xs rounded-full bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300">
                                ปิด
                            </span>
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            @if($terminal->is_online)
                            <span class="flex items-center gap-1 text-green-600">
                                <span class="w-2 h-2 bg-green-500 rounded-full animate-pulse"></span> ออนไลน์
                            </span>
                            @else
                            <span class="flex items-center gap-1 text-gray-500">
                                <span class="w-2 h-2 bg-gray-400 rounded-full"></span> ออฟไลน์
                            </span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400">
                            {{ $terminal->last_sync_at?->diffForHumans() ?? 'ยังไม่เคย' }}
                        </td>
                        <td class="px-4 py-3 text-sm">
                            <div class="flex gap-2">
                                <form action="{{ route('seller.pos.terminals.toggle-status', $terminal) }}" method="POST" class="inline">
                                    @csrf
                                    <button type="submit" class="text-{{ $terminal->status === 'active' ? 'orange' : 'green' }}-600 hover:underline text-xs">
                                        {{ $terminal->status === 'active' ? '⏸️ ปิด' : '▶️ เปิด' }}
                                    </button>
                                </form>
                                <form action="{{ route('seller.pos.terminals.destroy', $terminal) }}" method="POST" class="inline"
                                    onsubmit="return confirm('ลบ Terminal นี้?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-red-600 hover:underline text-xs">🗑️ ลบ</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">
                            ยังไม่มี Terminal ลงทะเบียน
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Instructions -->
    <div class="bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-lg p-4">
        <h4 class="font-bold text-yellow-800 dark:text-yellow-200 mb-2">📖 วิธีใช้งาน</h4>
        <ol class="text-sm text-yellow-700 dark:text-yellow-300 space-y-1 list-decimal list-inside">
            <li>กดปุ่ม <strong>"สร้าง API Key"</strong> เพื่อสร้าง Key ใหม่</li>
            <li>คัดลอก <strong>API Key</strong> ที่ได้ไปใส่ในโปรแกรม POS Desktop</li>
            <li>กรอก <strong>รหัสร้าน</strong>: <code class="bg-yellow-200 dark:bg-yellow-800 px-1 rounded">{{ $store->shop_code ?? $store->id }}</code></li>
            <li>กรอก <strong>Server URL</strong>: <code class="bg-yellow-200 dark:bg-yellow-800 px-1 rounded">{{ config('app.url') }}</code></li>
            <li>กด <strong>Activate</strong> ในโปรแกรม POS</li>
        </ol>
    </div>
</div>

<script>
function copyApiKey() {
    const key = document.getElementById('newApiKey').textContent;
    navigator.clipboard.writeText(key);
    alert('คัดลอก API Key แล้ว!');
}

function copyToClipboard(text) {
    navigator.clipboard.writeText(text);
    alert('คัดลอกแล้ว!');
}
</script>
@endsection
