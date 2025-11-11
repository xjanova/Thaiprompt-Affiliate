@extends('layouts.admin')

@section('title', 'Export Analytics')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="bg-gradient-to-r from-gray-700 via-slate-700 to-zinc-700 rounded-xl shadow-2xl p-6 text-white">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-4">
                <div class="w-14 h-14 bg-white/20 rounded-lg flex items-center justify-center backdrop-blur-sm">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                </div>
                <div>
                    <h1 class="text-2xl font-bold">Export Analytics</h1>
                    <p class="text-gray-200 text-sm">Export analytics data and generate reports</p>
                </div>
            </div>
            <a href="{{ route('admin.dashboard') }}" class="px-4 py-2 bg-white text-gray-700 rounded-lg hover:bg-gray-100 transition font-semibold">
                Back
            </a>
        </div>
    </div>

    <!-- Export Configuration -->
    <div class="grid md:grid-cols-2 gap-6">
        <!-- Configuration Panel -->
        <div class="bg-white rounded-lg shadow-lg p-6">
            <h3 class="text-lg font-bold text-gray-800 mb-4">Export Configuration</h3>
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Report Type</label>
                    <select id="reportType" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-gray-500">
                        <option value="all">All Analytics</option>
                        <option value="traffic">Traffic Analytics</option>
                        <option value="performance">Performance Metrics</option>
                        <option value="database">Database Statistics</option>
                        <option value="business">Business Metrics</option>
                        <option value="security">Security Events</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Date Range</label>
                    <select id="dateRange" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-gray-500">
                        <option value="7">Last 7 Days</option>
                        <option value="30" selected>Last 30 Days</option>
                        <option value="90">Last 90 Days</option>
                        <option value="365">This Year</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Format</label>
                    <select id="format" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-gray-500">
                        <option value="csv" selected>CSV</option>
                        <option value="json">JSON</option>
                        <option value="xlsx">Excel (XLSX)</option>
                    </select>
                </div>
                <button onclick="exportData()" class="w-full px-6 py-3 bg-gray-700 text-white rounded-lg hover:bg-gray-800 transition font-semibold flex items-center justify-center">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                    Generate Export
                </button>
            </div>
        </div>

        <!-- Quick Export -->
        <div class="bg-white rounded-lg shadow-lg p-6">
            <h3 class="text-lg font-bold text-gray-800 mb-4">Quick Export</h3>
            <div class="space-y-3">
                <button onclick="quickExport('csv')" class="w-full px-4 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition flex items-center justify-center">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3"></path>
                    </svg>
                    Export Last 30 Days (CSV)
                </button>
                <button onclick="quickExport('json')" class="w-full px-4 py-3 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition flex items-center justify-center">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3"></path>
                    </svg>
                    Export Last 30 Days (JSON)
                </button>
                <button onclick="quickExport('xlsx')" class="w-full px-4 py-3 bg-green-600 text-white rounded-lg hover:bg-green-700 transition flex items-center justify-center">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3"></path>
                    </svg>
                    Export Last 30 Days (Excel)
                </button>
            </div>

            <div class="mt-6 p-4 bg-blue-50 border border-blue-200 rounded-lg">
                <h4 class="font-semibold text-blue-900 mb-2">Export Options</h4>
                <ul class="text-sm text-blue-700 space-y-1">
                    <li>• CSV: Best for spreadsheets and data analysis</li>
                    <li>• JSON: Best for API integration and programming</li>
                    <li>• Excel: Best for formatted reports and presentations</li>
                </ul>
            </div>
        </div>
    </div>

    <!-- Export Status -->
    <div id="exportStatus" class="hidden bg-white rounded-lg shadow-lg p-6">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-gray-700"></div>
                <div>
                    <h3 class="font-bold text-gray-800">Generating Export...</h3>
                    <p class="text-sm text-gray-600">Please wait while we prepare your data</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Available Reports -->
    <div class="bg-white rounded-lg shadow-lg p-6">
        <h3 class="text-lg font-bold text-gray-800 mb-4">Available Export Reports</h3>
        <div class="grid md:grid-cols-3 gap-4">
            <div class="border border-gray-200 rounded-lg p-4 hover:border-gray-400 transition">
                <h4 class="font-semibold text-gray-800 mb-2">Complete Analytics</h4>
                <p class="text-sm text-gray-600 mb-3">All analytics data including traffic, performance, business, and security metrics</p>
                <button onclick="exportReport('all')" class="text-sm text-gray-700 hover:text-gray-900 font-medium">Export →</button>
            </div>
            <div class="border border-gray-200 rounded-lg p-4 hover:border-gray-400 transition">
                <h4 class="font-semibold text-gray-800 mb-2">Performance Report</h4>
                <p class="text-sm text-gray-600 mb-3">System performance metrics, response times, and resource utilization</p>
                <button onclick="exportReport('performance')" class="text-sm text-gray-700 hover:text-gray-900 font-medium">Export →</button>
            </div>
            <div class="border border-gray-200 rounded-lg p-4 hover:border-gray-400 transition">
                <h4 class="font-semibold text-gray-800 mb-2">Business Metrics</h4>
                <p class="text-sm text-gray-600 mb-3">Revenue, users, transactions, and key business indicators</p>
                <button onclick="exportReport('business')" class="text-sm text-gray-700 hover:text-gray-900 font-medium">Export →</button>
            </div>
            <div class="border border-gray-200 rounded-lg p-4 hover:border-gray-400 transition">
                <h4 class="font-semibold text-gray-800 mb-2">Security Events</h4>
                <p class="text-sm text-gray-600 mb-3">Failed logins, blocked IPs, and security incidents</p>
                <button onclick="exportReport('security')" class="text-sm text-gray-700 hover:text-gray-900 font-medium">Export →</button>
            </div>
            <div class="border border-gray-200 rounded-lg p-4 hover:border-gray-400 transition">
                <h4 class="font-semibold text-gray-800 mb-2">Traffic Analysis</h4>
                <p class="text-sm text-gray-600 mb-3">Request patterns, endpoints, and user traffic data</p>
                <button onclick="exportReport('traffic')" class="text-sm text-gray-700 hover:text-gray-900 font-medium">Export →</button>
            </div>
            <div class="border border-gray-200 rounded-lg p-4 hover:border-gray-400 transition">
                <h4 class="font-semibold text-gray-800 mb-2">Database Stats</h4>
                <p class="text-sm text-gray-600 mb-3">Query performance, connections, and database metrics</p>
                <button onclick="exportReport('database')" class="text-sm text-gray-700 hover:text-gray-900 font-medium">Export →</button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
function showExportStatus() {
    document.getElementById('exportStatus').classList.remove('hidden');
    setTimeout(() => {
        document.getElementById('exportStatus').classList.add('hidden');
    }, 3000);
}

function exportData() {
    const reportType = document.getElementById('reportType').value;
    const days = document.getElementById('dateRange').value;
    const format = document.getElementById('format').value;

    showExportStatus();

    // Build export URL
    const url = `{{ route('admin.analytics.export') }}?type=${reportType}&days=${days}&format=${format}`;

    // Trigger download
    window.location.href = url;
}

function quickExport(format) {
    showExportStatus();
    const url = `{{ route('admin.analytics.export') }}?type=all&days=30&format=${format}`;
    window.location.href = url;
}

function exportReport(type) {
    showExportStatus();
    const url = `{{ route('admin.analytics.export') }}?type=${type}&days=30&format=csv`;
    window.location.href = url;
}
</script>
@endpush
@endsection
