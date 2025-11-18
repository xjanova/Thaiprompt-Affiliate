@extends('layouts.admin')

@section('title', 'จัดการ Features: ' . $tenant->tenant_name)

@section('content')
<div class="container-fluid px-4 py-6" x-data="tenantFeatures()">
    {{-- Header --}}
    <div class="mb-6">
        <div class="flex items-center gap-3 mb-2">
            <a href="{{ route('admin.ai-core.tenants.show', $tenant) }}"
               class="inline-flex items-center justify-center w-10 h-10 rounded-xl bg-gray-200 hover:bg-gray-300 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 transition-all duration-200">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
            </a>
            <div>
                <h1 class="text-3xl font-bold bg-gradient-to-r from-purple-600 to-pink-600 dark:from-purple-400 dark:to-pink-400 bg-clip-text text-transparent">
                    🎯 จัดการ Features
                </h1>
                <p class="text-gray-600 dark:text-gray-400 mt-1">
                    Tenant: <strong>{{ $tenant->tenant_name }}</strong>
                </p>
            </div>
        </div>
    </div>

    {{-- Stats --}}
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
        <div class="bg-gradient-to-br from-blue-500 to-blue-600 rounded-2xl p-6 text-white shadow-xl">
            <p class="text-blue-100 text-sm mb-1">Total Features</p>
            <h3 class="text-3xl font-bold">{{ $features->count() }}</h3>
        </div>
        <div class="bg-gradient-to-br from-green-500 to-green-600 rounded-2xl p-6 text-white shadow-xl">
            <p class="text-green-100 text-sm mb-1">Enabled</p>
            <h3 class="text-3xl font-bold">{{ $enabledFeatures->count() }}</h3>
        </div>
        <div class="bg-gradient-to-br from-yellow-500 to-yellow-600 rounded-2xl p-6 text-white shadow-xl">
            <p class="text-yellow-100 text-sm mb-1">Trial</p>
            <h3 class="text-3xl font-bold">{{ $tenant->featureAccess()->where('is_trial', true)->count() }}</h3>
        </div>
        <div class="bg-gradient-to-br from-purple-500 to-purple-600 rounded-2xl p-6 text-white shadow-xl">
            <p class="text-purple-100 text-sm mb-1">Available</p>
            <h3 class="text-3xl font-bold">{{ $features->count() - $enabledFeatures->count() }}</h3>
        </div>
    </div>

    {{-- Features Grid --}}
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        @foreach($features as $feature)
            @php
                $access = $tenant->featureAccess()->where('feature_id', $feature->id)->first();
                $isEnabled = $access && $access->is_enabled;
            @endphp

            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl overflow-hidden border-2 transition-all duration-200
                {{ $isEnabled ? 'border-green-500 dark:border-green-400' : 'border-gray-200 dark:border-gray-700' }}">
                {{-- Header --}}
                <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                    <div class="flex items-start justify-between mb-3">
                        <div class="flex items-center gap-3">
                            <span class="text-3xl">{{ $feature->icon }}</span>
                            <div>
                                <h3 class="font-bold text-gray-900 dark:text-white">{{ $feature->feature_name }}</h3>
                                <p class="text-xs text-gray-500 dark:text-gray-400">{{ $feature->feature_key }}</p>
                            </div>
                        </div>
                        <span class="inline-flex items-center px-2 py-1 rounded text-xs font-semibold
                            {{ $feature->pricing_tier === 'free' ? 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300' : '' }}
                            {{ $feature->pricing_tier === 'basic' ? 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400' : '' }}
                            {{ $feature->pricing_tier === 'pro' ? 'bg-purple-100 text-purple-800 dark:bg-purple-900/30 dark:text-purple-400' : '' }}
                            {{ $feature->pricing_tier === 'enterprise' ? 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400' : '' }}">
                            {{ strtoupper($feature->pricing_tier) }}
                        </span>
                    </div>
                    @if($feature->description)
                        <p class="text-sm text-gray-600 dark:text-gray-400">{{ Str::limit($feature->description, 100) }}</p>
                    @endif
                </div>

                {{-- Status & Info --}}
                <div class="p-6 space-y-3">
                    @if($access)
                        {{-- Quota Info --}}
                        @if($feature->quota_type !== 'none' && $access->quota_limit)
                            <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
                                <span class="text-sm text-gray-600 dark:text-gray-400">Quota</span>
                                <span class="text-sm font-semibold text-gray-900 dark:text-white">
                                    {{ number_format($access->quota_used) }} / {{ number_format($access->quota_limit) }}
                                </span>
                            </div>
                        @endif

                        {{-- Trial Info --}}
                        @if($access->is_trial && $access->trial_ends_at)
                            <div class="flex items-center justify-between p-3 bg-yellow-50 dark:bg-yellow-900/20 rounded-lg">
                                <span class="text-sm text-yellow-600 dark:text-yellow-400">🎁 Trial</span>
                                <span class="text-xs text-yellow-700 dark:text-yellow-300">
                                    ถึง {{ $access->trial_ends_at->format('d/m/Y') }}
                                </span>
                            </div>
                        @endif

                        {{-- Status --}}
                        <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
                            <span class="text-sm text-gray-600 dark:text-gray-400">สถานะ</span>
                            <span class="inline-flex items-center px-2 py-1 rounded text-xs font-medium
                                {{ $access->status === 'approved' ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400' : '' }}
                                {{ $access->status === 'pending' ? 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400' : '' }}
                                {{ $access->status === 'rejected' ? 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400' : '' }}
                                {{ $access->status === 'suspended' ? 'bg-orange-100 text-orange-800 dark:bg-orange-900/30 dark:text-orange-400' : '' }}">
                                {{ ucfirst($access->status) }}
                            </span>
                        </div>
                    @else
                        <div class="p-4 bg-gray-50 dark:bg-gray-700/50 rounded-lg text-center text-gray-500 dark:text-gray-400 text-sm">
                            ยังไม่ได้เปิดใช้งาน
                        </div>
                    @endif
                </div>

                {{-- Actions --}}
                <div class="p-4 bg-gray-50 dark:bg-gray-900/50 border-t border-gray-200 dark:border-gray-700">
                    @if($isEnabled)
                        <button @click="disableFeature({{ $feature->id }}, '{{ $feature->feature_name }}')"
                                class="w-full px-4 py-2 bg-red-600 hover:bg-red-700 text-white font-medium rounded-lg transition-colors duration-200">
                            ❌ ปิดใช้งาน
                        </button>
                    @else
                        <button @click="showEnableModal({{ $feature->id }}, '{{ $feature->feature_name }}', '{{ $feature->quota_type }}', {{ $feature->default_quota_limit ?? 0 }})"
                                class="w-full px-4 py-2 bg-green-600 hover:bg-green-700 text-white font-medium rounded-lg transition-colors duration-200">
                            ✅ เปิดใช้งาน
                        </button>
                    @endif
                </div>
            </div>
        @endforeach
    </div>

    {{-- Enable Modal --}}
    <div x-show="showModal"
         x-cloak
         class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4"
         @click.self="showModal = false">
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-2xl max-w-md w-full p-6">
            <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-4">เปิดใช้งาน Feature</h3>
            
            <form @submit.prevent="enableFeature()">
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                            Feature: <span x-text="selectedFeature.name"></span>
                        </label>
                    </div>

                    <div x-show="selectedFeature.quotaType !== 'none'">
                        <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                            🎯 Quota Limit
                        </label>
                        <input type="number"
                               x-model="selectedFeature.quotaLimit"
                               min="0"
                               class="w-full px-4 py-2 bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-green-500 text-gray-900 dark:text-white">
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                            📊 Access Level
                        </label>
                        <select x-model="selectedFeature.accessLevel"
                                class="w-full px-4 py-2 bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-green-500 text-gray-900 dark:text-white">
                            <option value="full">Full Access</option>
                            <option value="limited">Limited Access</option>
                            <option value="view_only">View Only</option>
                        </select>
                    </div>

                    <div class="flex items-center gap-3">
                        <input type="checkbox"
                               x-model="selectedFeature.isTrial"
                               class="w-5 h-5 text-green-600 rounded">
                        <label class="text-sm font-medium text-gray-700 dark:text-gray-300">
                            🎁 เปิดเป็น Trial (ทดลอง 30 วัน)
                        </label>
                    </div>
                </div>

                <div class="flex gap-3 mt-6">
                    <button type="submit"
                            class="flex-1 px-6 py-2 bg-green-600 hover:bg-green-700 text-white font-medium rounded-lg">
                        ✅ เปิดใช้งาน
                    </button>
                    <button type="button"
                            @click="showModal = false"
                            class="flex-1 px-6 py-2 bg-gray-200 hover:bg-gray-300 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 font-medium rounded-lg">
                        ยกเลิก
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
function tenantFeatures() {
    return {
        showModal: false,
        selectedFeature: {
            id: null,
            name: '',
            quotaType: 'none',
            quotaLimit: 0,
            accessLevel: 'full',
            isTrial: false
        },

        showEnableModal(featureId, featureName, quotaType, defaultQuota) {
            this.selectedFeature = {
                id: featureId,
                name: featureName,
                quotaType: quotaType,
                quotaLimit: defaultQuota,
                accessLevel: 'full',
                isTrial: false
            };
            this.showModal = true;
        },

        enableFeature() {
            const data = {
                quota_limit: this.selectedFeature.quotaLimit,
                access_level: this.selectedFeature.accessLevel,
                is_trial: this.selectedFeature.isTrial
            };

            fetch(`/admin/ai-core/tenants/{{ $tenant->id }}/features/${this.selectedFeature.id}/enable`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json'
                },
                body: JSON.stringify(data)
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    window.location.reload();
                } else {
                    alert('เกิดข้อผิดพลาด: ' + (data.message || 'กรุณาลองใหม่อีกครั้ง'));
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('เกิดข้อผิดพลาดในการเชื่อมต่อ');
            });
        },

        disableFeature(featureId, featureName) {
            if (confirm(`คุณต้องการปิด Feature "${featureName}" หรือไม่?`)) {
                fetch(`/admin/ai-core/tenants/{{ $tenant->id }}/features/${featureId}/disable`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json'
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        window.location.reload();
                    } else {
                        alert('เกิดข้อผิดพลาด: ' + (data.message || 'กรุณาลองใหม่อีกครั้ง'));
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('เกิดข้อผิดพลาดในการเชื่อมต่อ');
                });
            }
        }
    }
}
</script>
@endpush
@endsection
