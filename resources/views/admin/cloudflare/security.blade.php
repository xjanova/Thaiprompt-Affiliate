@extends('layouts.admin-v3')

@section('title', 'Cloudflare Security Settings')

@section('content')
<div class="container-fluid px-4 py-6" x-data="securityManager()">
    <!-- Header -->
    <div class="relative mb-8 overflow-hidden rounded-2xl bg-gradient-to-br from-green-500 via-emerald-600 to-teal-600 dark:from-green-600 dark:via-emerald-700 dark:to-teal-700 p-8 shadow-2xl">
        <div class="absolute inset-0 bg-[url('data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNjAiIGhlaWdodD0iNjAiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyI+PGRlZnM+PHBhdHRlcm4gaWQ9ImdyaWQiIHdpZHRoPSI2MCIgaGVpZ2h0PSI2MCIgcGF0dGVyblVuaXRzPSJ1c2VyU3BhY2VPblVzZSI+PHBhdGggZD0iTSAxMCAwIEwgMCAwIDAgMTAiIGZpbGw9Im5vbmUiIHN0cm9rZT0id2hpdGUiIHN0cm9rZS1vcGFjaXR5PSIwLjEiIHN0cm9rZS13aWR0aD0iMSIvPjwvcGF0dGVybj48L2RlZnM+PHJlY3Qgd2lkdGg9IjEwMCUiIGhlaWdodD0iMTAwJSIgZmlsbD0idXJsKCNncmlkKSIvPjwvc3ZnPg==')] opacity-30"></div>
        <div class="relative flex items-center justify-between">
            <div>
                <div class="flex items-center gap-3 mb-2">
                    <a href="{{ route('admin.cloudflare.index') }}" class="w-10 h-10 rounded-lg bg-white/20 flex items-center justify-center hover:bg-white/30 transition-colors">
                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                        </svg>
                    </a>
                    <div class="w-14 h-14 rounded-xl backdrop-blur-sm flex items-center justify-center bg-white/20">
                        <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                        </svg>
                    </div>
                    <div>
                        <h1 class="text-3xl font-bold text-white mb-1">Security Settings</h1>
                        <p class="text-green-100">ตั้งค่าความปลอดภัยของ Cloudflare</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @if(!$isConfigured)
    <div class="mb-6 bg-yellow-50 dark:bg-yellow-900/30 border-l-4 border-yellow-500 p-4 rounded-xl">
        <div class="flex items-start">
            <svg class="h-6 w-6 text-yellow-500 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
            </svg>
            <p class="text-yellow-800 dark:text-yellow-200">ยังไม่ได้ตั้งค่า Cloudflare API</p>
        </div>
    </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Security Level -->
        <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-lg border border-gray-100 dark:border-slate-700 p-6">
            <div class="flex items-center mb-6">
                <div class="w-12 h-12 bg-gradient-to-br from-green-500 to-emerald-500 rounded-xl flex items-center justify-center mr-4">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                    </svg>
                </div>
                <div>
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Security Level</h3>
                    <p class="text-sm text-gray-600 dark:text-gray-400">กำหนดระดับความเข้มงวดในการตรวจสอบ</p>
                </div>
            </div>

            <div class="space-y-3">
                @php
                    $levels = [
                        'off' => ['name' => 'Off', 'desc' => 'ไม่มีการตรวจสอบ (ไม่แนะนำ)', 'color' => 'gray'],
                        'essentially_off' => ['name' => 'Essentially Off', 'desc' => 'ตรวจสอบเฉพาะภัยคุกคามร้ายแรง', 'color' => 'gray'],
                        'low' => ['name' => 'Low', 'desc' => 'ตรวจสอบภัยคุกคามทั่วไป', 'color' => 'yellow'],
                        'medium' => ['name' => 'Medium', 'desc' => 'สมดุลระหว่างความปลอดภัยและ UX', 'color' => 'blue'],
                        'high' => ['name' => 'High', 'desc' => 'ตรวจสอบอย่างเข้มงวด', 'color' => 'green'],
                        'under_attack' => ['name' => 'Under Attack', 'desc' => 'ป้องกัน DDoS (แสดง JS Challenge)', 'color' => 'red'],
                    ];
                @endphp

                @foreach($levels as $level => $info)
                <label class="flex items-center p-4 rounded-lg border-2 cursor-pointer transition-all
                    {{ $securityLevel === $level
                        ? 'border-' . $info['color'] . '-500 bg-' . $info['color'] . '-50 dark:bg-' . $info['color'] . '-900/20'
                        : 'border-gray-200 dark:border-slate-600 hover:border-gray-300 dark:hover:border-slate-500' }}">
                    <input type="radio"
                           name="security_level"
                           value="{{ $level }}"
                           x-model="selectedLevel"
                           {{ $securityLevel === $level ? 'checked' : '' }}
                           class="w-4 h-4 text-{{ $info['color'] }}-600 bg-gray-100 border-gray-300 focus:ring-{{ $info['color'] }}-500">
                    <div class="ml-3">
                        <span class="block text-sm font-medium text-gray-900 dark:text-white">{{ $info['name'] }}</span>
                        <span class="block text-xs text-gray-500 dark:text-gray-400">{{ $info['desc'] }}</span>
                    </div>
                    @if($securityLevel === $level)
                    <span class="ml-auto px-2 py-1 text-xs font-medium text-{{ $info['color'] }}-600 bg-{{ $info['color'] }}-100 dark:bg-{{ $info['color'] }}-900/50 rounded-full">
                        ใช้งานอยู่
                    </span>
                    @endif
                </label>
                @endforeach
            </div>

            <button @click="setSecurityLevel()"
                    :disabled="loading || selectedLevel === '{{ $securityLevel }}' || !{{ $isConfigured ? 'true' : 'false' }}"
                    class="w-full mt-6 px-4 py-3 bg-gradient-to-r from-green-500 to-emerald-500 hover:from-green-600 hover:to-emerald-600 text-white rounded-lg font-medium transition-all disabled:opacity-50 disabled:cursor-not-allowed">
                <span x-show="!loading">บันทึก Security Level</span>
                <span x-show="loading" class="flex items-center justify-center">
                    <svg class="animate-spin h-5 w-5 mr-2" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none"/>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"/>
                    </svg>
                    กำลังบันทึก...
                </span>
            </button>
        </div>

        <!-- Under Attack Mode -->
        <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-lg border border-gray-100 dark:border-slate-700 p-6">
            <div class="flex items-center mb-6">
                <div class="w-12 h-12 bg-gradient-to-br from-red-500 to-orange-500 rounded-xl flex items-center justify-center mr-4">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                </div>
                <div>
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Under Attack Mode</h3>
                    <p class="text-sm text-gray-600 dark:text-gray-400">ป้องกัน DDoS อย่างเข้มงวด</p>
                </div>
            </div>

            <div class="bg-{{ $securityLevel === 'under_attack' ? 'red' : 'gray' }}-50 dark:bg-{{ $securityLevel === 'under_attack' ? 'red' : 'gray' }}-900/20 rounded-xl p-6 mb-6 text-center">
                <div class="w-20 h-20 mx-auto mb-4 rounded-full bg-{{ $securityLevel === 'under_attack' ? 'red' : 'gray' }}-100 dark:bg-{{ $securityLevel === 'under_attack' ? 'red' : 'gray' }}-800 flex items-center justify-center">
                    @if($securityLevel === 'under_attack')
                    <svg class="w-10 h-10 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                    @else
                    <svg class="w-10 h-10 text-gray-400 dark:text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                    </svg>
                    @endif
                </div>
                <h4 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">
                    {{ $securityLevel === 'under_attack' ? 'Under Attack Mode เปิดอยู่' : 'Under Attack Mode ปิดอยู่' }}
                </h4>
                <p class="text-sm text-gray-600 dark:text-gray-400">
                    @if($securityLevel === 'under_attack')
                        ผู้เข้าชมทุกคนจะต้องผ่าน JavaScript Challenge ก่อนเข้าถึงเว็บไซต์
                    @else
                        โหมดปกติ - ตรวจสอบตาม Security Level ที่กำหนด
                    @endif
                </p>
            </div>

            @if($securityLevel === 'under_attack')
                <button @click="disableUnderAttack()"
                        :disabled="loading || !{{ $isConfigured ? 'true' : 'false' }}"
                        class="w-full px-4 py-3 bg-gray-500 hover:bg-gray-600 text-white rounded-lg font-medium transition-all disabled:opacity-50 disabled:cursor-not-allowed">
                    ปิด Under Attack Mode
                </button>
            @else
                <button @click="enableUnderAttack()"
                        :disabled="loading || !{{ $isConfigured ? 'true' : 'false' }}"
                        class="w-full px-4 py-3 bg-gradient-to-r from-red-500 to-orange-500 hover:from-red-600 hover:to-orange-600 text-white rounded-lg font-medium transition-all disabled:opacity-50 disabled:cursor-not-allowed">
                    เปิด Under Attack Mode
                </button>
            @endif
        </div>

        <!-- SSL Settings -->
        @if($sslSettings)
        <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-lg border border-gray-100 dark:border-slate-700 p-6">
            <div class="flex items-center mb-6">
                <div class="w-12 h-12 bg-gradient-to-br from-blue-500 to-indigo-500 rounded-xl flex items-center justify-center mr-4">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                    </svg>
                </div>
                <div>
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">SSL/TLS</h3>
                    <p class="text-sm text-gray-600 dark:text-gray-400">การตั้งค่า HTTPS</p>
                </div>
            </div>

            <div class="bg-blue-50 dark:bg-blue-900/20 rounded-xl p-4">
                <div class="flex items-center justify-between">
                    <span class="text-sm text-gray-600 dark:text-gray-400">SSL Mode</span>
                    <span class="px-3 py-1 bg-blue-100 dark:bg-blue-800 text-blue-800 dark:text-blue-200 rounded-full text-sm font-medium uppercase">
                        {{ $sslSettings['value'] ?? 'Unknown' }}
                    </span>
                </div>
            </div>
        </div>
        @endif

        <!-- Firewall Rules -->
        <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-lg border border-gray-100 dark:border-slate-700 p-6">
            <div class="flex items-center justify-between mb-6">
                <div class="flex items-center">
                    <div class="w-12 h-12 bg-gradient-to-br from-purple-500 to-pink-500 rounded-xl flex items-center justify-center mr-4">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 18.657A8 8 0 016.343 7.343S7 9 9 10c0-2 .5-5 2.986-7C14 5 16.09 5.777 17.656 7.343A7.975 7.975 0 0120 13a7.975 7.975 0 01-2.343 5.657z"/>
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Firewall Rules</h3>
                        <p class="text-sm text-gray-600 dark:text-gray-400">{{ count($firewallRules) }} rules</p>
                    </div>
                </div>
            </div>

            @if(count($firewallRules) > 0)
                <div class="space-y-2">
                    @foreach(array_slice($firewallRules, 0, 5) as $rule)
                    <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-slate-700 rounded-lg">
                        <span class="text-sm text-gray-900 dark:text-gray-100">{{ $rule['description'] ?? 'No description' }}</span>
                        <span class="px-2 py-1 text-xs font-medium rounded-full
                            {{ $rule['action'] === 'block' ? 'bg-red-100 text-red-800 dark:bg-red-900/50 dark:text-red-300' :
                               ($rule['action'] === 'challenge' ? 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/50 dark:text-yellow-300' :
                               'bg-green-100 text-green-800 dark:bg-green-900/50 dark:text-green-300') }}">
                            {{ $rule['action'] ?? 'unknown' }}
                        </span>
                    </div>
                    @endforeach
                </div>
                @if(count($firewallRules) > 5)
                <p class="mt-3 text-sm text-gray-500 dark:text-gray-400 text-center">
                    และอีก {{ count($firewallRules) - 5 }} rules
                </p>
                @endif
            @else
                <div class="text-center py-8">
                    <svg class="w-12 h-12 mx-auto text-gray-400 dark:text-gray-600 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 18.657A8 8 0 016.343 7.343S7 9 9 10c0-2 .5-5 2.986-7C14 5 16.09 5.777 17.656 7.343A7.975 7.975 0 0120 13a7.975 7.975 0 01-2.343 5.657z"/>
                    </svg>
                    <p class="text-gray-500 dark:text-gray-400">ไม่มี Firewall Rules</p>
                </div>
            @endif
        </div>
    </div>

    <!-- Result Message -->
    <div x-show="message"
         x-transition
         :class="messageType === 'success' ? 'bg-green-50 dark:bg-green-900/30 border-green-500' : 'bg-red-50 dark:bg-red-900/30 border-red-500'"
         class="fixed bottom-4 right-4 max-w-sm border-l-4 p-4 rounded-xl shadow-lg z-50">
        <div class="flex items-center">
            <template x-if="messageType === 'success'">
                <svg class="w-5 h-5 text-green-500 mr-2" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                </svg>
            </template>
            <template x-if="messageType === 'error'">
                <svg class="w-5 h-5 text-red-500 mr-2" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                </svg>
            </template>
            <span :class="messageType === 'success' ? 'text-green-800 dark:text-green-200' : 'text-red-800 dark:text-red-200'" x-text="message"></span>
        </div>
    </div>
</div>

@push('scripts')
<script>
function securityManager() {
    return {
        loading: false,
        selectedLevel: '{{ $securityLevel }}',
        message: '',
        messageType: 'success',

        showMessage(msg, type = 'success') {
            this.message = msg;
            this.messageType = type;
            setTimeout(() => {
                this.message = '';
            }, 5000);
        },

        async setSecurityLevel() {
            this.loading = true;
            try {
                const response = await fetch('{{ route("admin.cloudflare.set-security-level") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({ level: this.selectedLevel })
                });
                const data = await response.json();

                if (data.success) {
                    this.showMessage(data.message || 'บันทึกสำเร็จ', 'success');
                    setTimeout(() => location.reload(), 1500);
                } else {
                    this.showMessage(data.message || 'เกิดข้อผิดพลาด', 'error');
                }
            } catch (error) {
                this.showMessage('เกิดข้อผิดพลาดในการเชื่อมต่อ', 'error');
            }
            this.loading = false;
        },

        async enableUnderAttack() {
            if (!confirm('คุณต้องการเปิด Under Attack Mode ใช่หรือไม่?\n\nผู้ใช้ทุกคนจะต้องรอ JavaScript Challenge ก่อนเข้าถึงเว็บไซต์')) return;

            this.loading = true;
            try {
                const response = await fetch('{{ route("admin.cloudflare.enable-under-attack") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    }
                });
                const data = await response.json();

                if (data.success) {
                    this.showMessage(data.message || 'เปิด Under Attack Mode สำเร็จ', 'success');
                    setTimeout(() => location.reload(), 1500);
                } else {
                    this.showMessage(data.message || 'เกิดข้อผิดพลาด', 'error');
                }
            } catch (error) {
                this.showMessage('เกิดข้อผิดพลาดในการเชื่อมต่อ', 'error');
            }
            this.loading = false;
        },

        async disableUnderAttack() {
            this.loading = true;
            try {
                const response = await fetch('{{ route("admin.cloudflare.disable-under-attack") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    }
                });
                const data = await response.json();

                if (data.success) {
                    this.showMessage(data.message || 'ปิด Under Attack Mode สำเร็จ', 'success');
                    setTimeout(() => location.reload(), 1500);
                } else {
                    this.showMessage(data.message || 'เกิดข้อผิดพลาด', 'error');
                }
            } catch (error) {
                this.showMessage('เกิดข้อผิดพลาดในการเชื่อมต่อ', 'error');
            }
            this.loading = false;
        }
    }
}
</script>
@endpush
@endsection
