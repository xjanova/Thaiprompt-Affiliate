@extends('layouts.admin-v3')

@section('title', 'รายละเอียดอุปกรณ์ POS')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-3xl font-bold text-gray-900">📱 {{ $device->device_name }}</h1>
            <p class="text-gray-500 mt-1">รหัสอุปกรณ์: {{ $device->device_code }}</p>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('admin.pos.devices.index') }}" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300">
                ← กลับ
            </a>
            @if(auth()->user()->isSuperAdmin())
            <a href="{{ route('admin.pos.devices.edit', $device) }}" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                ✏️ แก้ไข
            </a>
            @endif
        </div>
    </div>

    <!-- Device Info -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Status Cards -->
        <div class="bg-white rounded-xl shadow-lg p-6">
            <h3 class="text-lg font-bold text-gray-900 mb-4">สถานะอุปกรณ์</h3>
            <div class="space-y-3">
                <div class="flex items-center justify-between">
                    <span class="text-sm text-gray-500">สถานะการใช้งาน</span>
                    @if($device->is_active)
                        <span class="px-3 py-1 bg-green-100 text-green-700 rounded-full text-sm font-medium">เปิดใช้งาน</span>
                    @else
                        <span class="px-3 py-1 bg-gray-100 text-gray-700 rounded-full text-sm font-medium">ปิดใช้งาน</span>
                    @endif
                </div>
                <div class="flex items-center justify-between">
                    <span class="text-sm text-gray-500">สถานะออนไลน์</span>
                    @if($device->is_online)
                        <span class="px-3 py-1 bg-green-100 text-green-700 rounded-full text-sm font-medium">🟢 Online</span>
                    @else
                        <span class="px-3 py-1 bg-gray-100 text-gray-700 rounded-full text-sm font-medium">⚪ Offline</span>
                    @endif
                </div>
                <div class="flex items-center justify-between">
                    <span class="text-sm text-gray-500">ประเภทอุปกรณ์</span>
                    @if($device->device_type === 'premium')
                        <span class="px-3 py-1 bg-purple-100 text-purple-700 rounded-full text-sm font-medium">💎 Premium</span>
                    @else
                        <span class="px-3 py-1 bg-blue-100 text-blue-700 rounded-full text-sm font-medium">📱 Standard</span>
                    @endif
                </div>
            </div>
        </div>

        <!-- Subscription Info -->
        <div class="bg-white rounded-xl shadow-lg p-6">
            <h3 class="text-lg font-bold text-gray-900 mb-4">ข้อมูลแพคเกจ</h3>
            <div class="space-y-3">
                <div>
                    <span class="text-sm text-gray-500">สถานะแพคเกจ</span>
                    <div class="mt-1">
                        @if($device->subscription_status === 'active')
                            <span class="px-3 py-1 bg-green-100 text-green-700 rounded-full text-sm font-medium">✅ Active</span>
                        @elseif($device->subscription_status === 'trial')
                            <span class="px-3 py-1 bg-blue-100 text-blue-700 rounded-full text-sm font-medium">🆓 Trial</span>
                        @elseif($device->subscription_status === 'expired')
                            <span class="px-3 py-1 bg-red-100 text-red-700 rounded-full text-sm font-medium">⚠️ Expired</span>
                        @else
                            <span class="px-3 py-1 bg-gray-100 text-gray-700 rounded-full text-sm font-medium">🔒 Suspended</span>
                        @endif
                    </div>
                </div>
                <div>
                    <span class="text-sm text-gray-500">วันหมดอายุ</span>
                    <div class="text-sm font-medium text-gray-900 mt-1">
                        {{ $device->subscription_ends_at ? $device->subscription_ends_at->format('d/m/Y H:i') : '-' }}
                    </div>
                </div>
                <div>
                    <span class="text-sm text-gray-500">ค่าบริการรายเดือน</span>
                    <div class="text-lg font-bold text-gray-900 mt-1">฿{{ number_format($device->monthly_fee, 2) }}</div>
                </div>
            </div>
        </div>

        <!-- Store Info -->
        <div class="bg-white rounded-xl shadow-lg p-6">
            <h3 class="text-lg font-bold text-gray-900 mb-4">ข้อมูลร้านค้า</h3>
            <div class="space-y-3">
                <div>
                    <span class="text-sm text-gray-500">ชื่อร้าน</span>
                    <div class="text-sm font-medium text-gray-900 mt-1">{{ $device->store->store_name ?? 'N/A' }}</div>
                </div>
                <div>
                    <span class="text-sm text-gray-500">รหัสร้าน</span>
                    <div class="text-sm font-medium text-gray-900 mt-1">{{ $device->store->store_code ?? '-' }}</div>
                </div>
                @if($device->last_online_at)
                <div>
                    <span class="text-sm text-gray-500">ออนไลน์ล่าสุด</span>
                    <div class="text-sm font-medium text-gray-900 mt-1">{{ $device->last_online_at->format('d/m/Y H:i') }}</div>
                </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Stats -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
        <div class="bg-white rounded-xl shadow-lg p-6">
            <div class="text-3xl mb-2">💰</div>
            <div class="text-sm text-gray-500">ยอดขายวันนี้</div>
            <div class="text-2xl font-bold text-gray-900">฿{{ number_format($stats['today_sales'], 2) }}</div>
            <div class="text-xs text-gray-500 mt-1">{{ number_format($stats['today_transactions']) }} รายการ</div>
        </div>

        <div class="bg-white rounded-xl shadow-lg p-6">
            <div class="text-3xl mb-2">📊</div>
            <div class="text-sm text-gray-500">ยอดขายทั้งหมด</div>
            <div class="text-2xl font-bold text-gray-900">฿{{ number_format($stats['total_sales'], 2) }}</div>
            <div class="text-xs text-gray-500 mt-1">{{ number_format($stats['total_transactions']) }} รายการ</div>
        </div>

        <div class="bg-white rounded-xl shadow-lg p-6">
            <div class="text-3xl mb-2">📱</div>
            <div class="text-sm text-gray-500">Session ที่เปิดอยู่</div>
            <div class="text-2xl font-bold text-gray-900">
                {{ $stats['active_session'] ? '1' : '0' }}
            </div>
            @if($stats['active_session'])
            <div class="text-xs text-gray-500 mt-1">เปิดโดย {{ $stats['active_session']->user->name ?? 'N/A' }}</div>
            @else
            <div class="text-xs text-gray-500 mt-1">ไม่มี session ที่เปิด</div>
            @endif
        </div>

        <div class="bg-white rounded-xl shadow-lg p-6">
            <div class="text-3xl mb-2">🔑</div>
            <div class="text-sm text-gray-500">License Key</div>
            <div class="text-xs font-mono text-gray-900 mt-1 break-all">{{ $device->license_key }}</div>
            @if(auth()->user()->isSuperAdmin())
            <form action="{{ route('admin.pos.devices.regenerate-license', $device) }}" method="POST" class="mt-2">
                @csrf
                <button type="submit" class="text-xs text-blue-600 hover:text-blue-700"
                        onclick="return confirm('ต้องการสร้าง License Key ใหม่?')">
                    🔄 สร้างใหม่
                </button>
            </form>
            @endif
        </div>
    </div>

    <!-- Features -->
    <div class="bg-white rounded-xl shadow-lg p-6">
        <h3 class="text-lg font-bold text-gray-900 mb-4">ฟีเจอร์ที่เปิดใช้งาน</h3>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <div class="flex items-center gap-2">
                @if($device->dual_screen_enabled)
                    <span class="text-green-600">✅</span>
                @else
                    <span class="text-gray-400">❌</span>
                @endif
                <span class="text-sm text-gray-700">Dual Screen (จอคู่)</span>
            </div>
            <div class="flex items-center gap-2">
                @if($device->offline_mode_enabled)
                    <span class="text-green-600">✅</span>
                @else
                    <span class="text-gray-400">❌</span>
                @endif
                <span class="text-sm text-gray-700">Offline Mode</span>
            </div>
            <div class="flex items-center gap-2">
                @if($device->receipt_printer_enabled)
                    <span class="text-green-600">✅</span>
                @else
                    <span class="text-gray-400">❌</span>
                @endif
                <span class="text-sm text-gray-700">Receipt Printer</span>
            </div>
            <div class="flex items-center gap-2">
                @if($device->auto_renew)
                    <span class="text-green-600">✅</span>
                @else
                    <span class="text-gray-400">❌</span>
                @endif
                <span class="text-sm text-gray-700">Auto Renew</span>
            </div>
        </div>
    </div>

    <!-- Admin Actions -->
    @if(auth()->user()->isSuperAdmin())
    <div class="bg-white rounded-xl shadow-lg p-6">
        <h3 class="text-lg font-bold text-gray-900 mb-4">การจัดการ</h3>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <form action="{{ route('admin.pos.devices.toggle-status', $device) }}" method="POST">
                @csrf
                <button type="submit" class="w-full px-4 py-2 {{ $device->is_active ? 'bg-red-600 hover:bg-red-700' : 'bg-green-600 hover:bg-green-700' }} text-white rounded-lg transition-colors">
                    {{ $device->is_active ? '🔴 ปิดใช้งาน' : '🟢 เปิดใช้งาน' }}
                </button>
            </form>

            @if($device->subscription_status !== 'suspended')
            <form action="{{ route('admin.pos.devices.suspend', $device) }}" method="POST"
                  onsubmit="return confirm('ต้องการระงับการใช้งานอุปกรณ์นี้?')">
                @csrf
                <button type="submit" class="w-full px-4 py-2 bg-orange-600 hover:bg-orange-700 text-white rounded-lg transition-colors">
                    🔒 ระงับการใช้งาน
                </button>
            </form>
            @else
            <form action="{{ route('admin.pos.devices.reactivate', $device) }}" method="POST">
                @csrf
                <button type="submit" class="w-full px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg transition-colors">
                    🔓 เปิดใช้งานใหม่
                </button>
            </form>
            @endif

            <form action="{{ route('admin.pos.devices.force-offline', $device) }}" method="POST"
                  onsubmit="return confirm('บังคับให้ออฟไลน์และปิด session ทั้งหมด?')">
                @csrf
                <button type="submit" class="w-full px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg transition-colors">
                    ⚠️ Force Offline
                </button>
            </form>

            <button type="button" onclick="document.getElementById('extendModal').classList.remove('hidden')"
                    class="w-full px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition-colors">
                📅 ขยายอายุแพคเกจ
            </button>
        </div>
    </div>

    <!-- Extend Subscription Modal -->
    <div id="extendModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
        <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
            <div class="mt-3">
                <h3 class="text-lg font-bold text-gray-900 mb-4">ขยายอายุแพคเกจ</h3>
                <form action="{{ route('admin.pos.devices.extend-subscription', $device) }}" method="POST">
                    @csrf
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">จำนวนเดือน</label>
                        <input type="number" name="extension_months" min="1" max="36" value="1" required
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                        <p class="text-xs text-gray-500 mt-1">1-36 เดือน</p>
                    </div>
                    <div class="flex gap-2">
                        <button type="submit" class="flex-1 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                            ยืนยัน
                        </button>
                        <button type="button" onclick="document.getElementById('extendModal').classList.add('hidden')"
                                class="flex-1 px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300">
                            ยกเลิก
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @endif

    <!-- Recent Transactions -->
    @if($device->transactions->count() > 0)
    <div class="bg-white rounded-xl shadow-lg p-6">
        <h3 class="text-lg font-bold text-gray-900 mb-4">รายการขายล่าสุด (10 รายการ)</h3>
        <div class="overflow-x-auto">
            <table class="min-w-full">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500">วันที่</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500">วิธีชำระ</th>
                        <th class="px-4 py-2 text-right text-xs font-medium text-gray-500">จำนวน</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500">สถานะ</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @foreach($device->transactions->take(10) as $transaction)
                    <tr>
                        <td class="px-4 py-2 text-sm text-gray-900">{{ $transaction->transaction_date->format('d/m/Y H:i') }}</td>
                        <td class="px-4 py-2 text-sm">
                            @if($transaction->payment_method === 'cash')
                                <span class="px-2 py-1 bg-green-100 text-green-700 rounded text-xs">💵 เงินสด</span>
                            @elseif($transaction->payment_method === 'card')
                                <span class="px-2 py-1 bg-blue-100 text-blue-700 rounded text-xs">💳 บัตร</span>
                            @else
                                <span class="px-2 py-1 bg-purple-100 text-purple-700 rounded text-xs">📱 QR</span>
                            @endif
                        </td>
                        <td class="px-4 py-2 text-sm text-right font-bold">฿{{ number_format($transaction->total_amount, 2) }}</td>
                        <td class="px-4 py-2 text-sm">
                            @if($transaction->status === 'completed')
                                <span class="px-2 py-1 bg-green-100 text-green-700 rounded text-xs">สำเร็จ</span>
                            @elseif($transaction->status === 'refunded')
                                <span class="px-2 py-1 bg-red-100 text-red-700 rounded text-xs">คืนเงิน</span>
                            @else
                                <span class="px-2 py-1 bg-gray-100 text-gray-700 rounded text-xs">{{ $transaction->status }}</span>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif
</div>

@if(session('success'))
<script>
    alert('{{ session("success") }}');
</script>
@endif
@endsection
