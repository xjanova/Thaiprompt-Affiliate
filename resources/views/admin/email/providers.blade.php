@extends('layouts.admin')

@section('title', 'Email Providers')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">🔌 Email Providers</h1>
            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">จัดการบริการส่งอีเมล และตั้งค่า Failover</p>
        </div>
        <a href="{{ route('admin.email.providers.create') }}" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
            + เพิ่ม Provider
        </a>
    </div>

    <!-- Info Box -->
    <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-4">
        <div class="flex">
            <div class="flex-shrink-0">
                <span class="text-2xl">💡</span>
            </div>
            <div class="ml-3">
                <h3 class="text-sm font-medium text-blue-800 dark:text-blue-200">
                    ระบบ Auto Failover
                </h3>
                <div class="mt-2 text-sm text-blue-700 dark:text-blue-300">
                    <p>ระบบจะเลือก Provider ตาม Priority (สูงสุดก่อน) และสลับอัตโนมัติเมื่อ Provider ล้มเหลวหรือถึง Limit</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Providers Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        @forelse($providers as $provider)
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6 border-2 {{ $provider->is_default ? 'border-blue-500' : 'border-gray-200 dark:border-gray-700' }}">
                <!-- Header -->
                <div class="flex items-start justify-between mb-4">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                            {{ $provider->display_name }}
                            @if($provider->is_default)
                                <span class="ml-2 text-xs bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200 px-2 py-1 rounded-full">
                                    ⭐ Default
                                </span>
                            @endif
                        </h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400">
                            {{ $provider->type === 'api' ? 'API' : 'SMTP' }} • Priority: {{ $provider->priority }}
                        </p>
                    </div>
                    <div>
                        @if($provider->is_active)
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">
                                ✅ ใช้งาน
                            </span>
                        @else
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300">
                                ⭕ ปิด
                            </span>
                        @endif
                    </div>
                </div>

                <!-- Stats -->
                <div class="space-y-3 mb-4">
                    @if($provider->daily_limit)
                        <div>
                            <div class="flex items-center justify-between text-sm mb-1">
                                <span class="text-gray-600 dark:text-gray-400">วันนี้</span>
                                <span class="text-gray-900 dark:text-white font-medium">
                                    {{ $provider->sent_today }} / {{ $provider->daily_limit }}
                                </span>
                            </div>
                            <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                                @php
                                    $percentage = $provider->daily_limit > 0 ? ($provider->sent_today / $provider->daily_limit) * 100 : 0;
                                @endphp
                                <div class="bg-blue-600 h-2 rounded-full" style="width: {{ min($percentage, 100) }}%"></div>
                            </div>
                        </div>
                    @endif

                    @if($provider->hourly_limit)
                        <div>
                            <div class="flex items-center justify-between text-sm mb-1">
                                <span class="text-gray-600 dark:text-gray-400">ชั่วโมงนี้</span>
                                <span class="text-gray-900 dark:text-white font-medium">
                                    {{ $provider->sent_this_hour }} / {{ $provider->hourly_limit }}
                                </span>
                            </div>
                            <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                                @php
                                    $percentage = $provider->hourly_limit > 0 ? ($provider->sent_this_hour / $provider->hourly_limit) * 100 : 0;
                                @endphp
                                <div class="bg-green-600 h-2 rounded-full" style="width: {{ min($percentage, 100) }}%"></div>
                            </div>
                        </div>
                    @endif
                </div>

                <!-- Health Status -->
                @if($provider->health_check && isset($provider->health_check['healthy']))
                    <div class="mb-4 p-3 rounded-lg {{ $provider->health_check['healthy'] ? 'bg-green-50 dark:bg-green-900/20' : 'bg-red-50 dark:bg-red-900/20' }}">
                        <div class="flex items-center">
                            <span class="text-lg mr-2">{{ $provider->health_check['healthy'] ? '✅' : '❌' }}</span>
                            <span class="text-sm {{ $provider->health_check['healthy'] ? 'text-green-800 dark:text-green-200' : 'text-red-800 dark:text-red-200' }}">
                                {{ $provider->health_check['healthy'] ? 'สุขภาพดี' : 'มีปัญหา' }}
                            </span>
                        </div>
                        @if(isset($provider->health_check['message']))
                            <p class="text-xs text-gray-600 dark:text-gray-400 mt-1">{{ $provider->health_check['message'] }}</p>
                        @endif
                    </div>
                @endif

                <!-- Actions -->
                <div class="flex items-center space-x-2">
                    <button onclick="testProvider({{ $provider->id }})" class="flex-1 px-3 py-2 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded hover:bg-gray-200 dark:hover:bg-gray-600 text-sm">
                        🧪 ทดสอบ
                    </button>
                    <a href="{{ route('admin.email.providers.edit', $provider) }}" class="flex-1 px-3 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 text-sm text-center">
                        แก้ไข
                    </a>
                    @if(!$provider->is_default)
                        <form action="{{ route('admin.email.providers.set-default', $provider) }}" method="POST" class="flex-1">
                            @csrf
                            <button type="submit" class="w-full px-3 py-2 bg-green-600 text-white rounded hover:bg-green-700 text-sm">
                                ⭐ ตั้งเป็น Default
                            </button>
                        </form>
                    @endif
                </div>
            </div>
        @empty
            <div class="col-span-3 text-center py-12">
                <span class="text-6xl">📭</span>
                <p class="mt-4 text-gray-500 dark:text-gray-400">ยังไม่มี Email Provider</p>
                <a href="{{ route('admin.email.providers.create') }}" class="mt-4 inline-block px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                    เพิ่ม Provider แรก
                </a>
            </div>
        @endforelse
    </div>
</div>

<script>
function testProvider(providerId) {
    if (!confirm('ทดสอบการเชื่อมต่อ Provider นี้?')) return;

    const button = event.target;
    button.disabled = true;
    button.innerHTML = '⏳ กำลังทดสอบ...';

    fetch(`/admin/email/providers/${providerId}/test`, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        }
    })
    .then(response => response.json())
    .then(data => {
        button.disabled = false;
        button.innerHTML = '🧪 ทดสอบ';

        if (data.healthy) {
            alert('✅ เชื่อมต่อสำเร็จ!\n' + (data.message || ''));
        } else {
            alert('❌ เชื่อมต่อล้มเหลว!\n' + (data.error || data.message || ''));
        }
        location.reload();
    })
    .catch(error => {
        button.disabled = false;
        button.innerHTML = '🧪 ทดสอบ';
        alert('❌ เกิดข้อผิดพลาด: ' + error.message);
    });
}
</script>
@endsection
