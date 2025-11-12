@extends('layouts.admin')

@section('title', 'Threat Intelligence Dashboard')

@section('content')
<div class="container mx-auto px-4 py-8">
    <!-- Header -->
    <div class="flex justify-between items-center mb-6">
        <div>
            <h1 class="text-3xl font-bold text-gray-900">🌍 Threat Intelligence Dashboard</h1>
            <p class="text-gray-600 mt-1">รายงานและข้อมูล IP ที่เป็นภัยคุกคามจากทั่วโลก</p>
        </div>
        <div class="flex gap-3">
            <a href="{{ route('admin.security.index') }}" class="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition">
                ← กลับ
            </a>
            <button type="button" id="updateThreatBtn" class="px-6 py-2 bg-green-600 text-white font-semibold rounded-lg hover:bg-green-700 transition flex items-center gap-2">
                <span>🔄</span>
                <span>อัปเดตข้อมูล</span>
            </button>
        </div>
    </div>

    <!-- Progress Bar (Hidden by default) -->
    <div id="updateProgress" class="mb-6 bg-white rounded-lg border border-gray-200 p-6 hidden">
        <div class="flex items-center gap-4 mb-4">
            <div class="flex-shrink-0">
                <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-green-600"></div>
            </div>
            <div class="flex-1">
                <h3 class="text-lg font-semibold text-gray-900">กำลังอัปเดต Threat Intelligence</h3>
                <p id="progressMessage" class="text-sm text-gray-600 mt-1">เริ่มต้นการอัปเดต...</p>
            </div>
            <div class="text-right">
                <p id="progressPercentage" class="text-2xl font-bold text-green-600">0%</p>
            </div>
        </div>
        <div class="w-full bg-gray-200 rounded-full h-3 overflow-hidden">
            <div id="progressBar" class="bg-green-600 h-3 rounded-full transition-all duration-300" style="width: 0%"></div>
        </div>
    </div>

    @if(session('success'))
    <div class="mb-6 bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-lg">
        {{ session('success') }}
    </div>
    @endif

    @if($errors->any())
    <div class="mb-6 bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-lg">
        <ul class="list-disc list-inside">
            @foreach($errors->all() as $error)
            <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
    @endif

    <!-- Statistics Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
        <!-- Total Threats -->
        <div class="bg-gradient-to-br from-red-50 to-red-100 border border-red-200 rounded-lg p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-red-600 text-sm font-medium">Total Threats</p>
                    <p class="text-3xl font-bold text-red-900 mt-2">{{ number_format($stats['total_threats'] ?? 0) }}</p>
                </div>
                <div class="text-4xl">⚠️</div>
            </div>
        </div>

        <!-- High Confidence -->
        <div class="bg-gradient-to-br from-orange-50 to-orange-100 border border-orange-200 rounded-lg p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-orange-600 text-sm font-medium">High Confidence</p>
                    <p class="text-3xl font-bold text-orange-900 mt-2">{{ number_format($stats['high_confidence'] ?? 0) }}</p>
                </div>
                <div class="text-4xl">🎯</div>
            </div>
        </div>

        <!-- Sources -->
        <div class="bg-gradient-to-br from-blue-50 to-blue-100 border border-blue-200 rounded-lg p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-blue-600 text-sm font-medium">Active Sources</p>
                    <p class="text-3xl font-bold text-blue-900 mt-2">{{ count($stats['by_source'] ?? []) }}</p>
                </div>
                <div class="text-4xl">🔍</div>
            </div>
        </div>

        <!-- Last Update -->
        <div class="bg-gradient-to-br from-green-50 to-green-100 border border-green-200 rounded-lg p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-green-600 text-sm font-medium">Last Update</p>
                    <p class="text-sm font-bold text-green-900 mt-2">
                        @if($stats['last_update'] ?? null)
                            {{ \Carbon\Carbon::parse($stats['last_update'])->diffForHumans() }}
                        @else
                            Never
                        @endif
                    </p>
                </div>
                <div class="text-4xl">⏰</div>
            </div>
        </div>
    </div>

    <!-- Charts Row -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
        <!-- Threats by Type -->
        <div class="bg-white rounded-lg border border-gray-200 p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Threats by Type</h3>
            @if($threatsByType->count() > 0)
            <div class="space-y-3">
                @foreach($threatsByType as $threat)
                <div>
                    <div class="flex justify-between items-center mb-1">
                        <span class="text-sm font-medium text-gray-700">
                            @switch($threat->threat_type)
                                @case('proxy') 🔀 Proxy @break
                                @case('vpn') 🔒 VPN @break
                                @case('tor') 🧅 Tor @break
                                @case('spam') 📧 Spam @break
                                @case('abuse') ⚠️ Abuse @break
                                @case('malware') 🦠 Malware @break
                                @case('scanner') 🔍 Scanner @break
                                @default ❓ Other
                            @endswitch
                        </span>
                        <span class="text-sm font-bold text-gray-900">{{ number_format($threat->count) }}</span>
                    </div>
                    <div class="w-full bg-gray-200 rounded-full h-2">
                        <div class="bg-gradient-to-r from-indigo-500 to-purple-600 h-2 rounded-full"
                             style="width: {{ $stats['total_threats'] > 0 ? ($threat->count / $stats['total_threats']) * 100 : 0 }}%"></div>
                    </div>
                </div>
                @endforeach
            </div>
            @else
            <p class="text-gray-500 text-center py-8">No threat data available</p>
            @endif
        </div>

        <!-- Threats by Source -->
        <div class="bg-white rounded-lg border border-gray-200 p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Threats by Source</h3>
            @if($threatsBySource->count() > 0)
            <div class="space-y-3">
                @foreach($threatsBySource as $source)
                <div>
                    <div class="flex justify-between items-center mb-1">
                        <span class="text-sm font-medium text-gray-700 capitalize">
                            @if($source->source === 'firehol')
                                🆓 Firehol
                            @elseif($source->source === 'abuseipdb')
                                🔍 AbuseIPDB
                            @elseif($source->source === 'ipqualityscore')
                                🛡️ IPQualityScore
                            @else
                                {{ $source->source }}
                            @endif
                        </span>
                        <span class="text-sm font-bold text-gray-900">{{ number_format($source->count) }}</span>
                    </div>
                    <div class="w-full bg-gray-200 rounded-full h-2">
                        <div class="bg-gradient-to-r from-blue-500 to-cyan-600 h-2 rounded-full"
                             style="width: {{ $stats['total_threats'] > 0 ? ($source->count / $stats['total_threats']) * 100 : 0 }}%"></div>
                    </div>
                </div>
                @endforeach
            </div>
            @else
            <p class="text-gray-500 text-center py-8">No source data available</p>
            @endif
        </div>
    </div>

    <!-- IP Checker Tool -->
    <div class="bg-gradient-to-r from-purple-50 to-indigo-50 border border-purple-200 rounded-lg p-6 mb-8" x-data="{ checking: false, result: null }">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">🔎 ตรวจสอบ IP Address</h3>
        <form @submit.prevent="
            checking = true;
            fetch('{{ route('admin.security.threat-intelligence.check') }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({ ip_address: $refs.ipInput.value })
            })
            .then(r => r.json())
            .then(data => { result = data; checking = false; })
            .catch(e => { checking = false; alert('Error: ' + e.message); })
        " class="flex gap-3">
            <input type="text" x-ref="ipInput" placeholder="192.168.1.1"
                   class="flex-1 px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500">
            <button type="submit" :disabled="checking"
                    class="px-6 py-2 bg-purple-600 text-white font-semibold rounded-lg hover:bg-purple-700 transition disabled:opacity-50">
                <span x-show="!checking">ตรวจสอบ</span>
                <span x-show="checking">⏳ กำลังตรวจสอบ...</span>
            </button>
        </form>

        <!-- Result -->
        <div x-show="result" x-transition class="mt-4 p-4 bg-white border border-gray-200 rounded-lg">
            <template x-if="result">
                <div>
                    <div class="flex items-center gap-2 mb-3">
                        <span class="text-lg font-bold" x-text="result?.ip"></span>
                        <span x-show="result?.is_threat" class="px-2 py-1 bg-red-100 text-red-800 text-xs font-semibold rounded">⚠️ THREAT</span>
                        <span x-show="!result?.is_threat" class="px-2 py-1 bg-green-100 text-green-800 text-xs font-semibold rounded">✅ SAFE</span>
                    </div>
                    <template x-if="result?.local_threat">
                        <div class="text-sm text-gray-700">
                            <p><strong>Type:</strong> <span x-text="result.local_threat.threat_type"></span></p>
                            <p><strong>Source:</strong> <span x-text="result.local_threat.source"></span></p>
                            <p><strong>Confidence:</strong> <span x-text="result.local_threat.confidence_score"></span>%</p>
                        </div>
                    </template>
                </div>
            </template>
        </div>
    </div>

    <!-- Recent Threats Table -->
    <div class="bg-white rounded-lg border border-gray-200">
        <div class="p-6 border-b border-gray-200">
            <h3 class="text-lg font-semibold text-gray-900">Recent Threats (Top 100)</h3>
        </div>
        @if($recentThreats->count() > 0)
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">IP Address</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Type</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Source</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Confidence</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Last Seen</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Expires</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @foreach($recentThreats as $threat)
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-mono text-gray-900">{{ $threat->ip_address }}</td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="px-2 py-1 text-xs font-semibold rounded bg-{{ $threat->getThreatTypeBadgeColor() }}-100 text-{{ $threat->getThreatTypeBadgeColor() }}-800">
                                {{ $threat->getThreatTypeLabel() }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700 capitalize">{{ $threat->source }}</td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center gap-2">
                                <div class="w-16 bg-gray-200 rounded-full h-2">
                                    <div class="bg-gradient-to-r from-yellow-400 to-red-600 h-2 rounded-full"
                                         style="width: {{ $threat->confidence_score }}%"></div>
                                </div>
                                <span class="text-sm font-semibold text-gray-900">{{ $threat->confidence_score }}%</span>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            {{ $threat->last_seen->diffForHumans() }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            @if($threat->expires_at)
                                {{ $threat->expires_at->diffForHumans() }}
                            @else
                                <span class="text-gray-400">Never</span>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @else
        <div class="p-12 text-center">
            <div class="text-6xl mb-4">🔍</div>
            <p class="text-gray-500 text-lg">No threat data available yet</p>
            <p class="text-gray-400 text-sm mt-2">Click "อัปเดตข้อมูล" to fetch threat intelligence data</p>
        </div>
        @endif
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const updateBtn = document.getElementById('updateThreatBtn');
    const progressDiv = document.getElementById('updateProgress');
    const progressBar = document.getElementById('progressBar');
    const progressMessage = document.getElementById('progressMessage');
    const progressPercentage = document.getElementById('progressPercentage');

    let pollInterval = null;

    // Update button click handler
    updateBtn.addEventListener('click', function() {
        // Disable button
        updateBtn.disabled = true;
        updateBtn.classList.add('opacity-50', 'cursor-not-allowed');

        // Show progress div
        progressDiv.classList.remove('hidden');

        // Reset progress
        updateProgress(0, 'เริ่มต้นการอัปเดต...');

        // Start the update via AJAX
        fetch('{{ route('admin.security.threat-intelligence.update') }}', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                updateProgress(100, data.message);
                setTimeout(() => {
                    // Reload page to show updated stats
                    window.location.reload();
                }, 2000);
            } else {
                showError(data.message || 'เกิดข้อผิดพลาดในการอัปเดต');
            }
        })
        .catch(error => {
            console.error('Update error:', error);
            showError('เกิดข้อผิดพลาดในการเชื่อมต่อ');
        });

        // Start polling for progress
        startPolling();
    });

    function startPolling() {
        if (pollInterval) {
            clearInterval(pollInterval);
        }

        pollInterval = setInterval(() => {
            fetch('{{ route('admin.security.threat-intelligence.progress') }}', {
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.is_running) {
                    updateProgress(data.percentage, data.message);
                } else if (data.percentage === 100) {
                    updateProgress(100, data.message);
                    stopPolling();
                }
            })
            .catch(error => {
                console.error('Polling error:', error);
            });
        }, 1000); // Poll every second
    }

    function stopPolling() {
        if (pollInterval) {
            clearInterval(pollInterval);
            pollInterval = null;
        }
    }

    function updateProgress(percentage, message) {
        progressBar.style.width = percentage + '%';
        progressPercentage.textContent = percentage + '%';
        progressMessage.textContent = message;

        // Change color based on percentage
        if (percentage === 100) {
            progressBar.classList.remove('bg-green-600');
            progressBar.classList.add('bg-blue-600');
            progressPercentage.classList.remove('text-green-600');
            progressPercentage.classList.add('text-blue-600');
        }
    }

    function showError(message) {
        stopPolling();
        progressDiv.classList.add('hidden');
        updateBtn.disabled = false;
        updateBtn.classList.remove('opacity-50', 'cursor-not-allowed');

        // Show error alert
        const errorDiv = document.createElement('div');
        errorDiv.className = 'mb-6 bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-lg';
        errorDiv.textContent = message;

        // Insert after header
        const header = document.querySelector('.container > div:first-child');
        header.insertAdjacentElement('afterend', errorDiv);

        // Remove after 5 seconds
        setTimeout(() => {
            errorDiv.remove();
        }, 5000);
    }
});
</script>
@endpush
