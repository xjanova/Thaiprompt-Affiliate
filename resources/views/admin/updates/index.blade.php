@extends('layouts.admin')

@section('content')
<div class="container mx-auto px-4 py-8" x-data="updateManager()">
    <!-- Header -->
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-800 mb-2">จัดการอัพเดทระบบ</h1>
        <p class="text-gray-600">ตรวจสอบและติดตั้งอัพเดทสำหรับ TP-Affiliate</p>
    </div>

    <!-- Stats Cards -->
    <div class="grid md:grid-cols-4 gap-6 mb-8">
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600 mb-1">เวอร์ชันปัจจุบัน</p>
                    <p class="text-2xl font-bold text-indigo-600">{{ $stats['current_version'] }}</p>
                </div>
                <div class="text-4xl">📦</div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600 mb-1">อัพเดทที่มี</p>
                    <p class="text-2xl font-bold text-blue-600">{{ $stats['total_updates'] }}</p>
                </div>
                <div class="text-4xl">🔄</div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600 mb-1">รอติดตั้ง</p>
                    <p class="text-2xl font-bold text-orange-600">{{ $stats['pending_updates'] }}</p>
                </div>
                <div class="text-4xl">⏳</div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600 mb-1">อัพเดทล่าสุด</p>
                    <p class="text-sm font-medium text-green-600">
                        {{ $stats['last_update'] ? $stats['last_update']->created_at->diffForHumans() : 'ไม่มี' }}
                    </p>
                </div>
                <div class="text-4xl">✅</div>
            </div>
        </div>
    </div>

    <!-- Check for Updates Button -->
    <div class="bg-white rounded-lg shadow p-6 mb-8">
        <div class="flex items-center justify-between">
            <div>
                <h3 class="text-lg font-bold text-gray-800 mb-1">ตรวจสอบอัพเดทใหม่</h3>
                <p class="text-sm text-gray-600">ตรวจสอบว่ามีเวอร์ชันใหม่จาก GitHub หรือไม่</p>
            </div>
            <button @click="checkUpdates" :disabled="checking" class="bg-indigo-600 text-white px-6 py-3 rounded-lg font-semibold hover:bg-indigo-700 transition disabled:opacity-50">
                <span x-show="!checking">🔍 ตรวจสอบอัพเดท</span>
                <span x-show="checking">
                    <svg class="inline animate-spin h-5 w-5 mr-2" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    กำลังตรวจสอบ...
                </span>
            </button>
        </div>
        <div x-show="checkMessage" class="mt-4 p-4 rounded-lg" :class="checkSuccess ? 'bg-green-50 text-green-700' : 'bg-red-50 text-red-700'" x-text="checkMessage"></div>
    </div>

    <!-- Available Updates -->
    @if($availableUpdates->count() > 0)
    <div class="bg-white rounded-lg shadow mb-8">
        <div class="p-6 border-b border-gray-200">
            <h2 class="text-xl font-bold text-gray-800">อัพเดทที่พร้อมติดตั้ง</h2>
        </div>

        <div class="divide-y divide-gray-200">
            @foreach($availableUpdates as $update)
            <div class="p-6 hover:bg-gray-50 transition">
                <div class="flex items-start justify-between">
                    <div class="flex-1">
                        <div class="flex items-center gap-3 mb-2">
                            <h3 class="text-lg font-bold text-gray-800">
                                Version {{ $update->version }}
                                @if($update->version_name)
                                    <span class="text-gray-500 font-normal">- {{ $update->version_name }}</span>
                                @endif
                            </h3>

                            <!-- Type Badge -->
                            @php
                                $typeColors = [
                                    'major' => 'bg-red-100 text-red-800',
                                    'minor' => 'bg-blue-100 text-blue-800',
                                    'patch' => 'bg-green-100 text-green-800',
                                ];
                            @endphp
                            <span class="px-3 py-1 rounded-full text-xs font-medium {{ $typeColors[$update->type] ?? 'bg-gray-100 text-gray-800' }}">
                                {{ ucfirst($update->type) }}
                            </span>

                            @if($update->is_pre_release)
                                <span class="px-3 py-1 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                    Pre-release
                                </span>
                            @endif
                        </div>

                        <p class="text-gray-600 mb-3">{{ $update->description }}</p>

                        <div class="flex items-center gap-6 text-sm text-gray-500">
                            <span>📅 {{ $update->released_at->format('d M Y') }}</span>
                            <span>📥 {{ $update->download_count }} ดาวน์โหลด</span>
                            <span>⚡ {{ $update->install_count }} ติดตั้ง</span>
                        </div>

                        @if($update->breaking_changes)
                            <div class="mt-3 bg-red-50 border-l-4 border-red-400 p-3">
                                <p class="text-sm font-semibold text-red-800 mb-1">⚠️ Breaking Changes:</p>
                                <ul class="text-sm text-red-700 list-disc list-inside">
                                    @foreach($update->breaking_changes as $change)
                                        <li>{{ $change }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif
                    </div>

                    <div class="ml-6 flex flex-col gap-2">
                        <a href="{{ route('admin.updates.show', $update->id) }}" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition text-center">
                            ดูรายละเอียด
                        </a>
                        @if($update->meetsRequirements())
                            <button @click="installUpdate({{ $update->id }})" class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition">
                                ติดตั้ง
                            </button>
                        @else
                            <button disabled class="px-4 py-2 bg-gray-300 text-gray-600 rounded-lg cursor-not-allowed">
                                ไม่รองรับ
                            </button>
                        @endif
                    </div>
                </div>
            </div>
            @endforeach
        </div>

        <div class="p-6 border-t border-gray-200">
            {{ $availableUpdates->links() }}
        </div>
    </div>
    @else
    <div class="bg-white rounded-lg shadow p-12 text-center">
        <div class="text-6xl mb-4">✅</div>
        <h3 class="text-xl font-bold text-gray-800 mb-2">ระบบอัพเดทเป็นเวอร์ชันล่าสุดแล้ว</h3>
        <p class="text-gray-600">ไม่มีอัพเดทใหม่ในขณะนี้</p>
    </div>
    @endif

    <!-- Update History -->
    @if($updateHistory->count() > 0)
    <div class="bg-white rounded-lg shadow mt-8">
        <div class="p-6 border-b border-gray-200">
            <h2 class="text-xl font-bold text-gray-800">ประวัติการอัพเดท</h2>
        </div>

        <div class="divide-y divide-gray-200">
            @foreach($updateHistory as $log)
            <div class="p-6 hover:bg-gray-50 transition">
                <div class="flex items-center justify-between">
                    <div class="flex-1">
                        <div class="flex items-center gap-3 mb-1">
                            <h4 class="font-bold text-gray-800">
                                {{ $log->from_version }} → {{ $log->to_version }}
                            </h4>

                            <!-- Status Badge -->
                            @php
                                $statusColors = [
                                    'completed' => 'bg-green-100 text-green-800',
                                    'failed' => 'bg-red-100 text-red-800',
                                    'rolling_back' => 'bg-yellow-100 text-yellow-800',
                                    'rolled_back' => 'bg-orange-100 text-orange-800',
                                ];
                            @endphp
                            <span class="px-3 py-1 rounded-full text-xs font-medium {{ $statusColors[$log->status] ?? 'bg-gray-100 text-gray-800' }}">
                                {{ ucfirst($log->status) }}
                            </span>
                        </div>

                        <p class="text-sm text-gray-600">{{ $log->message }}</p>

                        <div class="flex items-center gap-4 text-xs text-gray-500 mt-2">
                            <span>📅 {{ $log->created_at->format('d M Y H:i') }}</span>
                            @if($log->initiator)
                                <span>👤 {{ $log->initiator->name }}</span>
                            @endif
                            @if($log->duration)
                                <span>⏱️ {{ $log->duration }}s</span>
                            @endif
                        </div>
                    </div>

                    <div class="ml-6">
                        <a href="{{ route('admin.updates.logs.show', $log->id) }}" class="text-blue-600 hover:text-blue-700 text-sm font-medium">
                            ดูรายละเอียด →
                        </a>
                    </div>
                </div>
            </div>
            @endforeach
        </div>

        <div class="p-6 border-t border-gray-200">
            <a href="{{ route('admin.updates.logs') }}" class="text-indigo-600 hover:text-indigo-700 font-medium">
                ดูประวัติทั้งหมด →
            </a>
        </div>
    </div>
    @endif
</div>

@push('scripts')
<script>
function updateManager() {
    return {
        checking: false,
        checkSuccess: false,
        checkMessage: '',
        installing: false,

        async checkUpdates() {
            this.checking = true;
            this.checkMessage = '';

            try {
                const response = await fetch('{{ route('admin.updates.check') }}');
                const data = await response.json();

                this.checkSuccess = data.success;
                this.checkMessage = data.message;

                if (data.success && data.count > 0) {
                    // Reload page to show new updates
                    setTimeout(() => window.location.reload(), 2000);
                }
            } catch (error) {
                this.checkSuccess = false;
                this.checkMessage = 'เกิดข้อผิดพลาดในการตรวจสอบอัพเดท';
            }

            this.checking = false;
        },

        async installUpdate(updateId) {
            if (!confirm('คุณแน่ใจหรือไม่ที่จะติดตั้งอัพเดทนี้?\n\nระบบจะทำการ backup ข้อมูลอัตโนมัติก่อนอัพเดท')) {
                return;
            }

            this.installing = true;

            try {
                const response = await fetch(`/admin/updates/${updateId}/install`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    }
                });

                const data = await response.json();

                if (data.success) {
                    alert('อัพเดทสำเร็จ! ระบบจะรีเฟรชหน้าเพจ');
                    window.location.reload();
                } else {
                    alert('เกิดข้อผิดพลาด: ' + data.message);
                }
            } catch (error) {
                alert('เกิดข้อผิดพลาดในการติดตั้งอัพเดท');
            }

            this.installing = false;
        }
    }
}
</script>
@endpush
@endsection
