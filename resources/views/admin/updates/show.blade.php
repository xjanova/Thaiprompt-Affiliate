@extends('layouts.admin')

@section('content')
<div class="container mx-auto px-4 py-8" x-data="updateDetail({{ $update->id }})">
    <!-- Header -->
    <div class="mb-8">
        <a href="{{ route('admin.updates.index') }}" class="text-indigo-600 hover:text-indigo-700 mb-4 inline-block">
            ← กลับไปหน้ารายการอัพเดท
        </a>
        <h1 class="text-3xl font-bold text-gray-800 mb-2">
            Version {{ $update->version }}
            @if($update->version_name)
                <span class="text-gray-500">- {{ $update->version_name }}</span>
            @endif
        </h1>
        <p class="text-gray-600">{{ $update->description }}</p>
    </div>

    <!-- Version Info -->
    <div class="bg-white rounded-lg shadow p-6 mb-8">
        <div class="grid md:grid-cols-3 gap-6">
            <div>
                <p class="text-sm text-gray-600 mb-1">ประเภท</p>
                @php
                    $typeColors = [
                        'major' => 'bg-red-100 text-red-800',
                        'minor' => 'bg-blue-100 text-blue-800',
                        'patch' => 'bg-green-100 text-green-800',
                    ];
                @endphp
                <span class="px-3 py-1 rounded-full text-sm font-medium {{ $typeColors[$update->type] ?? 'bg-gray-100 text-gray-800' }}">
                    {{ ucfirst($update->type) }} Update
                </span>
            </div>

            <div>
                <p class="text-sm text-gray-600 mb-1">วันที่เผยแพร่</p>
                <p class="text-lg font-semibold text-gray-800">{{ $update->released_at->format('d M Y') }}</p>
            </div>

            <div>
                <p class="text-sm text-gray-600 mb-1">สถิติ</p>
                <p class="text-sm text-gray-800">
                    📥 {{ $update->download_count }} ดาวน์โหลด | ⚡ {{ $update->install_count }} ติดตั้ง
                </p>
            </div>
        </div>
    </div>

    <!-- Requirements Check -->
    <div class="bg-white rounded-lg shadow p-6 mb-8">
        <h2 class="text-xl font-bold text-gray-800 mb-4">ความต้องการของระบบ</h2>

        <div class="space-y-3">
            <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                <span class="text-gray-700">PHP Version</span>
                <span class="flex items-center gap-2">
                    <span class="text-gray-600">ต้องการ: {{ $requirements['php_version']['required'] }}</span>
                    <span class="text-gray-600">|</span>
                    <span class="text-gray-600">ปัจจุบัน: {{ $requirements['php_version']['current'] }}</span>
                    <span class="text-2xl">
                        {{ $requirements['php_version']['passed'] ? '✅' : '❌' }}
                    </span>
                </span>
            </div>

            @if($update->requires_migration)
            <div class="flex items-center justify-between p-3 bg-yellow-50 rounded-lg">
                <span class="text-gray-700">Database Migration</span>
                <span class="text-yellow-800 font-medium">⚠️ ต้องการ Migration</span>
            </div>
            @endif

            @if(!$requirements['can_update'])
            <div class="p-4 bg-red-50 border-l-4 border-red-400">
                <p class="text-red-800 font-semibold">❌ ระบบไม่ตรงตามความต้องการในการอัพเดท</p>
                <p class="text-red-700 text-sm mt-1">กรุณาอัพเกรดระบบก่อนทำการติดตั้ง</p>
            </div>
            @else
            <div class="p-4 bg-green-50 border-l-4 border-green-400">
                <p class="text-green-800 font-semibold">✅ ระบบพร้อมสำหรับการอัพเดท</p>
            </div>
            @endif
        </div>
    </div>

    <!-- Changelog -->
    @if($update->changelog)
    <div class="bg-white rounded-lg shadow p-6 mb-8">
        <h2 class="text-xl font-bold text-gray-800 mb-4">📝 Changelog</h2>

        @php
            $changelog = $update->parsed_changelog;
        @endphp

        @if(!empty($changelog))
            <div class="space-y-6">
                @if(isset($changelog['features']) && count($changelog['features']) > 0)
                <div>
                    <h3 class="text-lg font-semibold text-gray-800 mb-2">🎉 New Features</h3>
                    <ul class="space-y-2">
                        @foreach($changelog['features'] as $item)
                        <li class="flex items-start gap-2">
                            <span class="text-green-600 mt-1">•</span>
                            <span class="text-gray-700">{{ $item }}</span>
                        </li>
                        @endforeach
                    </ul>
                </div>
                @endif

                @if(isset($changelog['improvements']) && count($changelog['improvements']) > 0)
                <div>
                    <h3 class="text-lg font-semibold text-gray-800 mb-2">✨ Improvements</h3>
                    <ul class="space-y-2">
                        @foreach($changelog['improvements'] as $item)
                        <li class="flex items-start gap-2">
                            <span class="text-blue-600 mt-1">•</span>
                            <span class="text-gray-700">{{ $item }}</span>
                        </li>
                        @endforeach
                    </ul>
                </div>
                @endif

                @if(isset($changelog['fixes']) && count($changelog['fixes']) > 0)
                <div>
                    <h3 class="text-lg font-semibold text-gray-800 mb-2">🐛 Bug Fixes</h3>
                    <ul class="space-y-2">
                        @foreach($changelog['fixes'] as $item)
                        <li class="flex items-start gap-2">
                            <span class="text-orange-600 mt-1">•</span>
                            <span class="text-gray-700">{{ $item }}</span>
                        </li>
                        @endforeach
                    </ul>
                </div>
                @endif

                @if(isset($changelog['breaking']) && count($changelog['breaking']) > 0)
                <div class="bg-red-50 p-4 rounded-lg">
                    <h3 class="text-lg font-semibold text-red-800 mb-2">⚠️ Breaking Changes</h3>
                    <ul class="space-y-2">
                        @foreach($changelog['breaking'] as $item)
                        <li class="flex items-start gap-2">
                            <span class="text-red-600 mt-1">•</span>
                            <span class="text-red-700">{{ $item }}</span>
                        </li>
                        @endforeach
                    </ul>
                </div>
                @endif

                @if(isset($changelog['security']) && count($changelog['security']) > 0)
                <div class="bg-purple-50 p-4 rounded-lg">
                    <h3 class="text-lg font-semibold text-purple-800 mb-2">🔒 Security</h3>
                    <ul class="space-y-2">
                        @foreach($changelog['security'] as $item)
                        <li class="flex items-start gap-2">
                            <span class="text-purple-600 mt-1">•</span>
                            <span class="text-purple-700">{{ $item }}</span>
                        </li>
                        @endforeach
                    </ul>
                </div>
                @endif
            </div>
        @else
            <div class="prose max-w-none">
                {!! nl2br(e($update->changelog)) !!}
            </div>
        @endif
    </div>
    @endif

    <!-- Update Progress (shown when installing) -->
    <div x-show="installing" class="bg-white rounded-lg shadow p-6 mb-8">
        <h2 class="text-xl font-bold text-gray-800 mb-4">กำลังติดตั้งอัพเดท...</h2>

        <!-- Progress Bar -->
        <div class="mb-4">
            <div class="flex items-center justify-between mb-2">
                <span class="text-sm font-medium text-gray-700" x-text="progressMessage"></span>
                <span class="text-sm font-medium text-gray-700" x-text="progressPercent + '%'"></span>
            </div>
            <div class="w-full bg-gray-200 rounded-full h-4 overflow-hidden">
                <div class="bg-indigo-600 h-4 rounded-full transition-all duration-500" :style="'width: ' + progressPercent + '%'"></div>
            </div>
        </div>

        <!-- Status Message -->
        <div class="flex items-center gap-3 text-gray-700">
            <svg class="animate-spin h-5 w-5 text-indigo-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            <span x-text="statusMessage"></span>
        </div>
    </div>

    <!-- Install Button -->
    @if($requirements['can_update'])
    <div class="bg-white rounded-lg shadow p-6">
        <button @click="installUpdate" :disabled="installing" class="w-full bg-green-600 text-white px-6 py-3 rounded-lg font-semibold hover:bg-green-700 transition disabled:opacity-50 disabled:cursor-not-allowed">
            <span x-show="!installing">⚡ ติดตั้งอัพเดทเวอร์ชัน {{ $update->version }}</span>
            <span x-show="installing">กำลังติดตั้ง...</span>
        </button>

        <p class="text-sm text-gray-600 mt-4 text-center">
            💡 ระบบจะสร้าง backup ข้อมูลอัตโนมัติก่อนทำการอัพเดท
        </p>
    </div>
    @endif
</div>

@push('scripts')
<script>
function updateDetail(updateId) {
    return {
        installing: false,
        progressPercent: 0,
        progressMessage: '',
        statusMessage: '',
        pollInterval: null,
        logId: null,

        async installUpdate() {
            if (!confirm('คุณแน่ใจหรือไม่ที่จะติดตั้งอัพเดทนี้?\n\nระบบจะทำการ backup ข้อมูลอัตโนมัติก่อนอัพเดท')) {
                return;
            }

            this.installing = true;
            this.progressPercent = 0;
            this.progressMessage = 'กำลังเริ่มต้น...';
            this.statusMessage = 'กำลังเตรียมการอัพเดท';

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
                    this.logId = data.log.id;
                    this.startPolling();
                } else {
                    alert('เกิดข้อผิดพลาด: ' + data.message);
                    this.installing = false;
                }
            } catch (error) {
                console.error('Install error:', error);
                alert('เกิดข้อผิดพลาดในการติดตั้งอัพเดท');
                this.installing = false;
            }
        },

        startPolling() {
            this.pollInterval = setInterval(() => {
                this.checkProgress();
            }, 1000);
        },

        async checkProgress() {
            if (!this.logId) return;

            try {
                const response = await fetch(`/admin/updates/logs/${this.logId}`);
                const data = await response.json();

                if (data.log) {
                    this.progressPercent = data.log.progress || 0;
                    this.progressMessage = data.log.message || 'กำลังดำเนินการ...';
                    this.statusMessage = this.getStatusMessage(data.log.status);

                    if (data.log.status === 'completed') {
                        clearInterval(this.pollInterval);
                        setTimeout(() => {
                            alert('อัพเดทสำเร็จ! ระบบจะรีเฟรชหน้าเพจ');
                            window.location.href = '{{ route('admin.updates.index') }}';
                        }, 1000);
                    } else if (data.log.status === 'failed') {
                        clearInterval(this.pollInterval);
                        alert('เกิดข้อผิดพลาด: ' + data.log.message);
                        this.installing = false;
                    }
                }
            } catch (error) {
                console.error('Progress check error:', error);
            }
        },

        getStatusMessage(status) {
            const messages = {
                'pending': 'กำลังเตรียมการ...',
                'backing_up': 'กำลังสำรองข้อมูล...',
                'downloading': 'กำลังดาวน์โหลดอัพเดท...',
                'migrating': 'กำลังอัพเดทฐานข้อมูล...',
                'seeding': 'กำลัง seed ข้อมูล...',
                'updating': 'กำลังอัพเดทระบบ...',
                'finalizing': 'กำลังเสร็จสิ้นการอัพเดท...',
                'completed': 'เสร็จสิ้น!',
                'failed': 'เกิดข้อผิดพลาด'
            };

            return messages[status] || 'กำลังดำเนินการ...';
        }
    }
}
</script>
@endpush
@endsection
