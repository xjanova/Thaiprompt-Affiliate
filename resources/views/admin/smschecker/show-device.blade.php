@extends('layouts.admin-v3')

@section('title', 'รายละเอียดอุปกรณ์ - ' . ($device->device_name ?? $device->device_id))

@section('content')
<div class="space-y-6">
    {{-- Header --}}
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">
                📱 {{ $device->device_name ?? $device->device_id }}
            </h1>
            <p class="text-gray-500 dark:text-gray-400 mt-1">
                รายละเอียดและสถิติของอุปกรณ์ SMS Checker
            </p>
        </div>
        <a href="{{ route('admin.smschecker.devices') }}"
           class="px-4 py-2 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-300 dark:hover:bg-gray-600 transition">
            ← กลับ
        </a>
    </div>

    {{-- Flash Message --}}
    @if(session('success'))
    <div class="bg-green-100 dark:bg-green-900 border border-green-400 dark:border-green-700 text-green-700 dark:text-green-300 px-4 py-3 rounded-lg">
        ✅ {{ session('success') }}
    </div>
    @endif

    {{-- สถิติ --}}
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-4">
            <p class="text-sm text-gray-500 dark:text-gray-400">SMS ทั้งหมด</p>
            <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ number_format($stats['total_notifications']) }}</p>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-4">
            <p class="text-sm text-gray-500 dark:text-gray-400">จับคู่สำเร็จ</p>
            <p class="text-2xl font-bold text-green-600 dark:text-green-400">{{ number_format($stats['matched']) }}</p>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-4">
            <p class="text-sm text-gray-500 dark:text-gray-400">รอจับคู่</p>
            <p class="text-2xl font-bold text-yellow-600 dark:text-yellow-400">{{ number_format($stats['pending']) }}</p>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-4">
            <p class="text-sm text-gray-500 dark:text-gray-400">วันนี้</p>
            <p class="text-2xl font-bold text-blue-600 dark:text-blue-400">{{ number_format($stats['today']) }}</p>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        {{-- ข้อมูลอุปกรณ์ --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">ข้อมูลอุปกรณ์</h2>
            <dl class="space-y-3">
                <div class="flex justify-between">
                    <dt class="text-sm text-gray-500 dark:text-gray-400">Device ID</dt>
                    <dd class="text-sm font-mono text-gray-900 dark:text-white">{{ $device->device_id }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-sm text-gray-500 dark:text-gray-400">ชื่ออุปกรณ์</dt>
                    <dd class="text-sm text-gray-900 dark:text-white">{{ $device->device_name ?? '-' }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-sm text-gray-500 dark:text-gray-400">แพลตฟอร์ม</dt>
                    <dd class="text-sm text-gray-900 dark:text-white">{{ $device->platform ?? 'android' }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-sm text-gray-500 dark:text-gray-400">เวอร์ชั่นแอพ</dt>
                    <dd class="text-sm text-gray-900 dark:text-white">{{ $device->app_version ?? '-' }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-sm text-gray-500 dark:text-gray-400">สถานะ</dt>
                    <dd>
                        @if($device->status === 'active')
                            <span class="px-2 py-1 text-xs font-medium bg-green-100 text-green-700 dark:bg-green-900 dark:text-green-300 rounded-full">🟢 Active</span>
                        @elseif($device->status === 'inactive')
                            <span class="px-2 py-1 text-xs font-medium bg-gray-100 text-gray-700 dark:bg-gray-600 dark:text-gray-300 rounded-full">⚪ Inactive</span>
                        @else
                            <span class="px-2 py-1 text-xs font-medium bg-red-100 text-red-700 dark:bg-red-900 dark:text-red-300 rounded-full">🔴 Blocked</span>
                        @endif
                    </dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-sm text-gray-500 dark:text-gray-400">เจ้าของ</dt>
                    <dd class="text-sm text-gray-900 dark:text-white">
                        @if($device->user)
                            {{ $device->user->name ?? 'User #' . $device->user_id }}
                        @else
                            <span class="text-gray-400">-</span>
                        @endif
                    </dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-sm text-gray-500 dark:text-gray-400">IP ล่าสุด</dt>
                    <dd class="text-sm font-mono text-gray-900 dark:text-white">{{ $device->ip_address ?? '-' }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-sm text-gray-500 dark:text-gray-400">Active ล่าสุด</dt>
                    <dd class="text-sm text-gray-900 dark:text-white">{{ $device->last_active_at ? $device->last_active_at->format('d/m/Y H:i:s') : 'ไม่เคย' }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-sm text-gray-500 dark:text-gray-400">สร้างเมื่อ</dt>
                    <dd class="text-sm text-gray-900 dark:text-white">{{ $device->created_at->format('d/m/Y H:i:s') }}</dd>
                </div>

                {{-- FCM Token Status --}}
                <div class="pt-3 mt-3 border-t border-gray-200 dark:border-gray-700">
                    <div class="flex justify-between items-center">
                        <dt class="text-sm text-gray-500 dark:text-gray-400">🔔 FCM Push</dt>
                        <dd>
                            @if($device->fcm_token)
                                <span class="px-2 py-1 text-xs font-medium bg-green-100 text-green-700 dark:bg-green-900 dark:text-green-300 rounded-full">✅ พร้อม</span>
                            @else
                                <span class="px-2 py-1 text-xs font-medium bg-red-100 text-red-700 dark:bg-red-900 dark:text-red-300 rounded-full">❌ ไม่มี token</span>
                            @endif
                        </dd>
                    </div>
                </div>
                @if($device->fcm_token)
                <div class="flex justify-between">
                    <dt class="text-sm text-gray-500 dark:text-gray-400">FCM Token</dt>
                    <dd class="text-xs font-mono text-gray-600 dark:text-gray-400 max-w-[200px] truncate" title="{{ $device->fcm_token }}">
                        {{ Str::limit($device->fcm_token, 30) }}
                    </dd>
                </div>
                @endif
                @if($device->fcm_token_updated_at)
                <div class="flex justify-between">
                    <dt class="text-sm text-gray-500 dark:text-gray-400">Token อัพเดท</dt>
                    <dd class="text-sm text-gray-900 dark:text-white">{{ $device->fcm_token_updated_at->format('d/m/Y H:i:s') }}</dd>
                </div>
                @endif
                @if($device->fcm_token)
                <div class="pt-2 mt-2 border-t border-gray-200 dark:border-gray-700">
                    <form action="{{ route('admin.smschecker.device-clear-fcm', $device) }}" method="POST"
                          onsubmit="return confirm('ลบ FCM Token? แอพจะลงทะเบียน token ใหม่เมื่อเปิดใช้งาน')">
                        @csrf
                        <button type="submit"
                                class="w-full px-3 py-1.5 text-xs bg-red-100 hover:bg-red-200 dark:bg-red-900 dark:hover:bg-red-800 text-red-700 dark:text-red-300 rounded-lg transition">
                            🗑️ ลบ FCM Token (Reset)
                        </button>
                    </form>
                </div>
                @endif
            </dl>
        </div>

        {{-- API Keys (แสดงเฉพาะ admin) --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">🔑 API Keys</h2>
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-500 dark:text-gray-400 mb-1">API Key</label>
                    <div class="flex items-center space-x-2">
                        <input type="text" value="{{ $device->api_key }}" readonly
                               class="flex-1 px-3 py-2 bg-gray-100 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-sm font-mono text-gray-900 dark:text-white">
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-500 dark:text-gray-400 mb-1">Secret Key</label>
                    <div class="flex items-center space-x-2">
                        <input type="text" value="{{ $device->secret_key }}" readonly
                               class="flex-1 px-3 py-2 bg-gray-100 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-sm font-mono text-gray-900 dark:text-white">
                    </div>
                </div>

                <div class="bg-yellow-50 dark:bg-yellow-900/30 rounded-lg p-3">
                    <p class="text-xs text-yellow-700 dark:text-yellow-300">
                        ⚠️ นำ API Key และ Secret Key ไปตั้งค่าในแอพ Android SMS Checker
                        <br>ห้ามเปิดเผย Secret Key ให้บุคคลอื่น
                    </p>
                </div>

                <div class="flex flex-wrap gap-3">
                    <a href="{{ route('admin.smschecker.device-qr', $device) }}"
                       class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition text-sm inline-block">
                        📲 QR Code ตั้งค่า
                    </a>

                    <form method="POST" action="{{ route('admin.smschecker.device-regenerate', $device) }}"
                          onsubmit="return confirm('ต้องการสร้าง API Key ใหม่หรือไม่? Key เก่าจะใช้งานไม่ได้อีก')">
                        @csrf
                        <button type="submit"
                                class="px-4 py-2 bg-yellow-600 text-white rounded-lg hover:bg-yellow-700 transition text-sm">
                            🔄 สร้าง Key ใหม่
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    {{-- การจัดการ --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6">
        <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">⚙️ จัดการอุปกรณ์</h2>
        <div class="flex flex-wrap gap-3">
            @if($device->status !== 'active')
            <form method="POST" action="{{ route('admin.smschecker.device-toggle', $device) }}">
                @csrf
                <input type="hidden" name="status" value="active">
                <button type="submit" class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition text-sm">
                    🟢 เปิดใช้งาน
                </button>
            </form>
            @endif

            @if($device->status !== 'inactive')
            <form method="POST" action="{{ route('admin.smschecker.device-toggle', $device) }}">
                @csrf
                <input type="hidden" name="status" value="inactive">
                <button type="submit" class="px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700 transition text-sm">
                    ⚪ ปิดใช้งาน
                </button>
            </form>
            @endif

            @if($device->status !== 'blocked')
            <form method="POST" action="{{ route('admin.smschecker.device-toggle', $device) }}">
                @csrf
                <input type="hidden" name="status" value="blocked">
                <button type="submit" class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition text-sm">
                    🔴 บล็อก
                </button>
            </form>
            @endif

            <form method="POST" action="{{ route('admin.smschecker.device-destroy', $device) }}"
                  onsubmit="return confirm('ต้องการลบอุปกรณ์นี้หรือไม่? การกระทำนี้ไม่สามารถย้อนกลับได้')">
                @csrf
                @method('DELETE')
                <button type="submit" class="px-4 py-2 bg-red-100 text-red-700 dark:bg-red-900 dark:text-red-300 rounded-lg hover:bg-red-200 dark:hover:bg-red-800 transition text-sm">
                    🗑️ ลบอุปกรณ์
                </button>
            </form>
        </div>
    </div>

    {{-- ประวัติ SMS --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white">📨 ประวัติ SMS Notifications</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-700">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">เวลา</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">ธนาคาร</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">ประเภท</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">จำนวนเงิน</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">ผู้ส่ง/ผู้รับ</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Reference</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">สถานะ</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse($notifications as $notification)
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700 transition">
                        <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-400">
                            {{ $notification->created_at->format('d/m/Y H:i') }}
                        </td>
                        <td class="px-6 py-4 text-sm font-medium text-gray-900 dark:text-white">
                            {{ $notification->bank }}
                        </td>
                        <td class="px-6 py-4">
                            @if($notification->type === 'credit')
                                <span class="px-2 py-1 text-xs font-medium bg-green-100 text-green-700 dark:bg-green-900 dark:text-green-300 rounded-full">💰 เงินเข้า</span>
                            @else
                                <span class="px-2 py-1 text-xs font-medium bg-orange-100 text-orange-700 dark:bg-orange-900 dark:text-orange-300 rounded-full">💸 เงินออก</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-sm font-medium text-right {{ $notification->type === 'credit' ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                            {{ $notification->type === 'credit' ? '+' : '-' }}{{ number_format($notification->amount, 2) }}
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-400">
                            {{ $notification->sender_or_receiver ?? '-' }}
                        </td>
                        <td class="px-6 py-4 text-sm font-mono text-gray-600 dark:text-gray-400">
                            {{ $notification->reference_number ?? '-' }}
                        </td>
                        <td class="px-6 py-4">
                            @switch($notification->status)
                                @case('pending')
                                    <span class="px-2 py-1 text-xs font-medium bg-yellow-100 text-yellow-700 dark:bg-yellow-900 dark:text-yellow-300 rounded-full">⏳ รอจับคู่</span>
                                    @break
                                @case('matched')
                                    <span class="px-2 py-1 text-xs font-medium bg-blue-100 text-blue-700 dark:bg-blue-900 dark:text-blue-300 rounded-full">🔗 จับคู่แล้ว</span>
                                    @break
                                @case('confirmed')
                                    <span class="px-2 py-1 text-xs font-medium bg-green-100 text-green-700 dark:bg-green-900 dark:text-green-300 rounded-full">✅ ยืนยัน</span>
                                    @break
                                @case('rejected')
                                    <span class="px-2 py-1 text-xs font-medium bg-red-100 text-red-700 dark:bg-red-900 dark:text-red-300 rounded-full">❌ ปฏิเสธ</span>
                                    @break
                                @case('expired')
                                    <span class="px-2 py-1 text-xs font-medium bg-gray-100 text-gray-700 dark:bg-gray-600 dark:text-gray-300 rounded-full">⏰ หมดอายุ</span>
                                    @break
                            @endswitch
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="px-6 py-12 text-center text-gray-500 dark:text-gray-400">
                            📭 ยังไม่มี SMS notifications จากอุปกรณ์นี้
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Pagination --}}
    <div class="mt-6">
        {{ $notifications->links() }}
    </div>
</div>
@endsection
