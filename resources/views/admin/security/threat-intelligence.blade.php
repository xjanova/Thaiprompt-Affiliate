@extends('layouts.admin')

@section('title', 'Threat Intelligence Dashboard')

@section('content')
<div class="container mx-auto px-4 py-8">
    <!-- Header -->
    <div class="flex justify-between items-center mb-6">
        <div>
            <h1 class="text-3xl font-bold text-gray-900">🛡️ Threat Intelligence Dashboard</h1>
            <p class="text-gray-600 mt-1">รายงานและข้อมูล IP ที่เป็นภัยคุกคามจากทั่วโลก</p>
        </div>
        <div class="flex gap-3">
            <a href="{{ route('admin.security.index') }}" class="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
                <span>กลับ</span>
            </a>
            <button type="button" id="updateThreatBtn" class="px-6 py-2 bg-gradient-to-r from-green-600 to-emerald-600 text-white font-semibold rounded-lg hover:from-green-700 hover:to-emerald-700 transition shadow-lg flex items-center gap-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                </svg>
                <span>อัปเดตข้อมูล</span>
            </button>
        </div>
    </div>

    <!-- Progress Bar (Hidden by default) -->
    <div id="updateProgress" class="mb-6 bg-white rounded-lg border border-gray-200 shadow-sm p-6 hidden">
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
            <div id="progressBar" class="bg-gradient-to-r from-green-500 to-emerald-500 h-3 rounded-full transition-all duration-300" style="width: 0%"></div>
        </div>
    </div>

    @if(session('success'))
    <div class="mb-6 bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-lg flex items-center gap-2">
        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
        </svg>
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
        <div class="bg-gradient-to-br from-red-50 to-red-100 border border-red-200 rounded-xl p-6 shadow-sm hover:shadow-md transition">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-red-600 text-sm font-medium uppercase tracking-wide">Total Threats</p>
                    <p class="text-3xl font-bold text-red-900 mt-2">{{ number_format($stats['total_threats'] ?? 0) }}</p>
                </div>
                <div class="bg-red-200 rounded-full p-3">
                    <svg class="w-8 h-8 text-red-600" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                    </svg>
                </div>
            </div>
        </div>

        <!-- High Confidence -->
        <div class="bg-gradient-to-br from-orange-50 to-orange-100 border border-orange-200 rounded-xl p-6 shadow-sm hover:shadow-md transition">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-orange-600 text-sm font-medium uppercase tracking-wide">High Confidence</p>
                    <p class="text-3xl font-bold text-orange-900 mt-2">{{ number_format($stats['high_confidence'] ?? 0) }}</p>
                </div>
                <div class="bg-orange-200 rounded-full p-3">
                    <svg class="w-8 h-8 text-orange-600" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                    </svg>
                </div>
            </div>
        </div>

        <!-- Active Sources -->
        <div class="bg-gradient-to-br from-blue-50 to-blue-100 border border-blue-200 rounded-xl p-6 shadow-sm hover:shadow-md transition">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-blue-600 text-sm font-medium uppercase tracking-wide">Active Sources</p>
                    <p class="text-3xl font-bold text-blue-900 mt-2">{{ count($stats['by_source'] ?? []) }}</p>
                </div>
                <div class="bg-blue-200 rounded-full p-3">
                    <svg class="w-8 h-8 text-blue-600" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M2 11a1 1 0 011-1h2a1 1 0 011 1v5a1 1 0 01-1 1H3a1 1 0 01-1-1v-5zM8 7a1 1 0 011-1h2a1 1 0 011 1v9a1 1 0 01-1 1H9a1 1 0 01-1-1V7zM14 4a1 1 0 011-1h2a1 1 0 011 1v12a1 1 0 01-1 1h-2a1 1 0 01-1-1V4z"/>
                    </svg>
                </div>
            </div>
        </div>

        <!-- Last Update -->
        <div class="bg-gradient-to-br from-green-50 to-green-100 border border-green-200 rounded-xl p-6 shadow-sm hover:shadow-md transition">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-green-600 text-sm font-medium uppercase tracking-wide">Last Update</p>
                    <p class="text-sm font-bold text-green-900 mt-2">
                        @if($stats['last_update'] ?? null)
                            {{ \Carbon\Carbon::parse($stats['last_update'])->diffForHumans() }}
                        @else
                            Never
                        @endif
                    </p>
                </div>
                <div class="bg-green-200 rounded-full p-3">
                    <svg class="w-8 h-8 text-green-600" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd"/>
                    </svg>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts Row -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
        <!-- Threats by Type -->
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center gap-2">
                <svg class="w-5 h-5 text-indigo-600" fill="currentColor" viewBox="0 0 20 20">
                    <path d="M2 10a8 8 0 018-8v8h8a8 8 0 11-16 0z"/>
                    <path d="M12 2.252A8.014 8.014 0 0117.748 8H12V2.252z"/>
                </svg>
                Threats by Type
            </h3>
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
                        <div class="bg-gradient-to-r from-indigo-500 to-purple-600 h-2 rounded-full transition-all"
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
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center gap-2">
                <svg class="w-5 h-5 text-blue-600" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M3 3a1 1 0 000 2v8a2 2 0 002 2h2.586l-1.293 1.293a1 1 0 101.414 1.414L10 15.414l2.293 2.293a1 1 0 001.414-1.414L12.414 15H15a2 2 0 002-2V5a1 1 0 100-2H3zm11 4a1 1 0 10-2 0v4a1 1 0 102 0V7zm-3 1a1 1 0 10-2 0v3a1 1 0 102 0V8zM8 9a1 1 0 00-2 0v2a1 1 0 102 0V9z" clip-rule="evenodd"/>
                </svg>
                Threats by Source
            </h3>
            @if($threatsBySource->count() > 0)
            <div class="space-y-3">
                @foreach($threatsBySource as $source)
                <div>
                    <div class="flex justify-between items-center mb-1">
                        <span class="text-sm font-medium text-gray-700 capitalize">
                            @if($source->source === 'firehol')
                                🔥 Firehol
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
                        <div class="bg-gradient-to-r from-blue-500 to-cyan-600 h-2 rounded-full transition-all"
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
    <div class="bg-gradient-to-r from-purple-50 to-indigo-50 border border-purple-200 rounded-xl shadow-sm p-6 mb-8" x-data="{ checking: false, result: null }">
        <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center gap-2">
            <svg class="w-5 h-5 text-purple-600" fill="currentColor" viewBox="0 0 20 20">
                <path d="M9 9a2 2 0 114 0 2 2 0 01-4 0z"/>
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-13a4 4 0 00-3.446 6.032l-2.261 2.26a1 1 0 101.414 1.415l2.261-2.261A4 4 0 1011 5z" clip-rule="evenodd"/>
            </svg>
            ตรวจสอบ IP Address
        </h3>
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
                   class="flex-1 px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent">
            <button type="submit" :disabled="checking"
                    class="px-6 py-2 bg-purple-600 text-white font-semibold rounded-lg hover:bg-purple-700 transition disabled:opacity-50 shadow">
                <span x-show="!checking">ตรวจสอบ</span>
                <span x-show="checking">⏳ กำลังตรวจสอบ...</span>
            </button>
        </form>

        <!-- Result -->
        <div x-show="result" x-transition class="mt-4 p-4 bg-white border border-gray-200 rounded-lg shadow-sm">
            <template x-if="result">
                <div>
                    <div class="flex items-center gap-2 mb-3">
                        <span class="text-lg font-bold font-mono" x-text="result?.ip"></span>
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

    <!-- Filters Section -->
    <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6 mb-6" x-data="{ showFilters: {{ request()->hasAny(['ip_address', 'threat_type', 'source', 'confidence_min', 'confidence_max', 'date_from', 'date_to']) ? 'true' : 'false' }} }">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-semibold text-gray-900 flex items-center gap-2">
                <svg class="w-5 h-5 text-gray-600" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M3 3a1 1 0 011-1h12a1 1 0 011 1v3a1 1 0 01-.293.707L12 11.414V15a1 1 0 01-.293.707l-2 2A1 1 0 018 17v-5.586L3.293 6.707A1 1 0 013 6V3z" clip-rule="evenodd"/>
                </svg>
                ตัวกรองข้อมูล
            </h3>
            <button @click="showFilters = !showFilters" class="text-sm text-indigo-600 hover:text-indigo-800 font-medium flex items-center gap-1">
                <span x-text="showFilters ? 'ซ่อน' : 'แสดง'"></span>
                <svg class="w-4 h-4 transition-transform" :class="{'rotate-180': showFilters}" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd"/>
                </svg>
            </button>
        </div>

        <form method="GET" action="{{ route('admin.security.threat-intelligence') }}" x-show="showFilters" x-transition class="space-y-4">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                <!-- IP Address -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">IP Address</label>
                    <input type="text" name="ip_address" value="{{ request('ip_address') }}"
                           placeholder="Search IP..."
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent text-sm">
                </div>

                <!-- Threat Type -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Threat Type</label>
                    <select name="threat_type" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent text-sm">
                        <option value="">-- ทั้งหมด --</option>
                        @foreach($threatTypes as $type)
                        <option value="{{ $type }}" {{ request('threat_type') == $type ? 'selected' : '' }}>
                            {{ ucfirst($type) }}
                        </option>
                        @endforeach
                    </select>
                </div>

                <!-- Source -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Source</label>
                    <select name="source" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent text-sm">
                        <option value="">-- ทั้งหมด --</option>
                        @foreach($sources as $source)
                        <option value="{{ $source }}" {{ request('source') == $source ? 'selected' : '' }}>
                            {{ ucfirst($source) }}
                        </option>
                        @endforeach
                    </select>
                </div>

                <!-- Per Page -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">แสดงต่อหน้า</label>
                    <select name="per_page" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent text-sm">
                        <option value="10" {{ request('per_page') == 10 ? 'selected' : '' }}>10</option>
                        <option value="20" {{ request('per_page', 20) == 20 ? 'selected' : '' }}>20</option>
                        <option value="50" {{ request('per_page') == 50 ? 'selected' : '' }}>50</option>
                        <option value="100" {{ request('per_page') == 100 ? 'selected' : '' }}>100</option>
                    </select>
                </div>

                <!-- Confidence Min -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Confidence (Min)</label>
                    <input type="number" name="confidence_min" value="{{ request('confidence_min') }}"
                           min="0" max="100" placeholder="0"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent text-sm">
                </div>

                <!-- Confidence Max -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Confidence (Max)</label>
                    <input type="number" name="confidence_max" value="{{ request('confidence_max') }}"
                           min="0" max="100" placeholder="100"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent text-sm">
                </div>

                <!-- Date From -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Date From</label>
                    <input type="date" name="date_from" value="{{ request('date_from') }}"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent text-sm">
                </div>

                <!-- Date To -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Date To</label>
                    <input type="date" name="date_to" value="{{ request('date_to') }}"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent text-sm">
                </div>
            </div>

            <!-- Filter Actions -->
            <div class="flex gap-3 pt-2">
                <button type="submit" class="px-6 py-2 bg-indigo-600 text-white font-semibold rounded-lg hover:bg-indigo-700 transition shadow flex items-center gap-2">
                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z" clip-rule="evenodd"/>
                    </svg>
                    ค้นหา
                </button>
                <a href="{{ route('admin.security.threat-intelligence') }}" class="px-6 py-2 bg-gray-200 text-gray-700 font-semibold rounded-lg hover:bg-gray-300 transition flex items-center gap-2">
                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M4 2a1 1 0 011 1v2.101a7.002 7.002 0 0111.601 2.566 1 1 0 11-1.885.666A5.002 5.002 0 005.999 7H9a1 1 0 010 2H4a1 1 0 01-1-1V3a1 1 0 011-1zm.008 9.057a1 1 0 011.276.61A5.002 5.002 0 0014.001 13H11a1 1 0 110-2h5a1 1 0 011 1v5a1 1 0 11-2 0v-2.101a7.002 7.002 0 01-11.601-2.566 1 1 0 01.61-1.276z" clip-rule="evenodd"/>
                    </svg>
                    ล้างฟิลเตอร์
                </a>
            </div>
        </form>
    </div>

    <!-- Recent Threats Table -->
    <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
        <div class="p-6 border-b border-gray-200 flex items-center justify-between">
            <div>
                <h3 class="text-lg font-semibold text-gray-900 flex items-center gap-2">
                    <svg class="w-5 h-5 text-red-600" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M5 2a1 1 0 011 1v1h1a1 1 0 010 2H6v1a1 1 0 01-2 0V6H3a1 1 0 010-2h1V3a1 1 0 011-1zm0 10a1 1 0 011 1v1h1a1 1 0 110 2H6v1a1 1 0 11-2 0v-1H3a1 1 0 110-2h1v-1a1 1 0 011-1zM12 2a1 1 0 01.967.744L14.146 7.2 17.5 9.134a1 1 0 010 1.732l-3.354 1.935-1.18 4.455a1 1 0 01-1.933 0L9.854 12.8 6.5 10.866a1 1 0 010-1.732l3.354-1.935 1.18-4.455A1 1 0 0112 2z" clip-rule="evenodd"/>
                    </svg>
                    Recent Threats
                </h3>
                <p class="text-sm text-gray-500 mt-1">แสดง {{ $recentThreats->count() }} จาก {{ number_format($recentThreats->total()) }} รายการ</p>
            </div>
        </div>

        @if($recentThreats->count() > 0)
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            <a href="{{ request()->fullUrlWithQuery(['sort_by' => 'ip_address', 'sort_order' => request('sort_order') === 'asc' ? 'desc' : 'asc']) }}" class="flex items-center gap-1 hover:text-gray-700">
                                IP Address
                                @if(request('sort_by') === 'ip_address')
                                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                        @if(request('sort_order') === 'asc')
                                            <path fill-rule="evenodd" d="M14.707 12.707a1 1 0 01-1.414 0L10 9.414l-3.293 3.293a1 1 0 01-1.414-1.414l4-4a1 1 0 011.414 0l4 4a1 1 0 010 1.414z" clip-rule="evenodd"/>
                                        @else
                                            <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd"/>
                                        @endif
                                    </svg>
                                @endif
                            </a>
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            <a href="{{ request()->fullUrlWithQuery(['sort_by' => 'threat_type', 'sort_order' => request('sort_order') === 'asc' ? 'desc' : 'asc']) }}" class="flex items-center gap-1 hover:text-gray-700">
                                Type
                                @if(request('sort_by') === 'threat_type')
                                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                        @if(request('sort_order') === 'asc')
                                            <path fill-rule="evenodd" d="M14.707 12.707a1 1 0 01-1.414 0L10 9.414l-3.293 3.293a1 1 0 01-1.414-1.414l4-4a1 1 0 011.414 0l4 4a1 1 0 010 1.414z" clip-rule="evenodd"/>
                                        @else
                                            <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd"/>
                                        @endif
                                    </svg>
                                @endif
                            </a>
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            <a href="{{ request()->fullUrlWithQuery(['sort_by' => 'source', 'sort_order' => request('sort_order') === 'asc' ? 'desc' : 'asc']) }}" class="flex items-center gap-1 hover:text-gray-700">
                                Source
                                @if(request('sort_by') === 'source')
                                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                        @if(request('sort_order') === 'asc')
                                            <path fill-rule="evenodd" d="M14.707 12.707a1 1 0 01-1.414 0L10 9.414l-3.293 3.293a1 1 0 01-1.414-1.414l4-4a1 1 0 011.414 0l4 4a1 1 0 010 1.414z" clip-rule="evenodd"/>
                                        @else
                                            <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd"/>
                                        @endif
                                    </svg>
                                @endif
                            </a>
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            <a href="{{ request()->fullUrlWithQuery(['sort_by' => 'confidence_score', 'sort_order' => request('sort_order') === 'asc' ? 'desc' : 'asc']) }}" class="flex items-center gap-1 hover:text-gray-700">
                                Confidence
                                @if(request('sort_by') === 'confidence_score')
                                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                        @if(request('sort_order') === 'asc')
                                            <path fill-rule="evenodd" d="M14.707 12.707a1 1 0 01-1.414 0L10 9.414l-3.293 3.293a1 1 0 01-1.414-1.414l4-4a1 1 0 011.414 0l4 4a1 1 0 010 1.414z" clip-rule="evenodd"/>
                                        @else
                                            <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd"/>
                                        @endif
                                    </svg>
                                @endif
                            </a>
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            <a href="{{ request()->fullUrlWithQuery(['sort_by' => 'last_seen', 'sort_order' => request('sort_order') === 'asc' ? 'desc' : 'asc']) }}" class="flex items-center gap-1 hover:text-gray-700">
                                Last Seen
                                @if(request('sort_by') === 'last_seen' || !request('sort_by'))
                                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                        @if(request('sort_order') === 'asc')
                                            <path fill-rule="evenodd" d="M14.707 12.707a1 1 0 01-1.414 0L10 9.414l-3.293 3.293a1 1 0 01-1.414-1.414l4-4a1 1 0 011.414 0l4 4a1 1 0 010 1.414z" clip-rule="evenodd"/>
                                        @else
                                            <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd"/>
                                        @endif
                                    </svg>
                                @endif
                            </a>
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            <a href="{{ request()->fullUrlWithQuery(['sort_by' => 'expires_at', 'sort_order' => request('sort_order') === 'asc' ? 'desc' : 'asc']) }}" class="flex items-center gap-1 hover:text-gray-700">
                                Expires
                                @if(request('sort_by') === 'expires_at')
                                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                        @if(request('sort_order') === 'asc')
                                            <path fill-rule="evenodd" d="M14.707 12.707a1 1 0 01-1.414 0L10 9.414l-3.293 3.293a1 1 0 01-1.414-1.414l4-4a1 1 0 011.414 0l4 4a1 1 0 010 1.414z" clip-rule="evenodd"/>
                                        @else
                                            <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd"/>
                                        @endif
                                    </svg>
                                @endif
                            </a>
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @foreach($recentThreats as $threat)
                    <tr class="hover:bg-gray-50 transition">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="text-sm font-mono font-medium text-gray-900">{{ $threat->ip_address }}</span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="px-2 py-1 text-xs font-semibold rounded-full bg-{{ $threat->getThreatTypeBadgeColor() }}-100 text-{{ $threat->getThreatTypeBadgeColor() }}-800">
                                {{ $threat->getThreatTypeLabel() }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="text-sm text-gray-700 capitalize font-medium">{{ $threat->source }}</span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center gap-2">
                                <div class="w-16 bg-gray-200 rounded-full h-2">
                                    <div class="h-2 rounded-full transition-all {{ $threat->confidence_score >= 70 ? 'bg-gradient-to-r from-red-500 to-red-600' : ($threat->confidence_score >= 50 ? 'bg-gradient-to-r from-yellow-500 to-orange-500' : 'bg-gradient-to-r from-green-500 to-blue-500') }}"
                                         style="width: {{ $threat->confidence_score }}%"></div>
                                </div>
                                <span class="text-sm font-semibold text-gray-900">{{ $threat->confidence_score }}%</span>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            <div class="flex items-center gap-1">
                                <svg class="w-4 h-4 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd"/>
                                </svg>
                                {{ $threat->last_seen->diffForHumans() }}
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            @if($threat->expires_at)
                                <div class="flex items-center gap-1">
                                    <svg class="w-4 h-4 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd"/>
                                    </svg>
                                    {{ $threat->expires_at->diffForHumans() }}
                                </div>
                            @else
                                <span class="text-gray-400 italic">Never</span>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div class="bg-gray-50 px-6 py-4 border-t border-gray-200">
            {{ $recentThreats->links() }}
        </div>
        @else
        <div class="p-12 text-center">
            <div class="text-6xl mb-4">🔍</div>
            <p class="text-gray-500 text-lg font-medium">No threat data available yet</p>
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
            progressBar.classList.remove('bg-gradient-to-r', 'from-green-500', 'to-emerald-500');
            progressBar.classList.add('bg-gradient-to-r', 'from-blue-500', 'to-indigo-500');
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
