<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            Admin: Analytics & Reports
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            <!-- Overview Stats -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                <div class="bg-gradient-to-r from-blue-500 to-blue-600 rounded-xl shadow-xl p-6 text-white">
                    <p class="text-sm opacity-90 mb-1">Total Trades</p>
                    <p class="text-3xl font-bold">{{ number_format($stats['total_trades']) }}</p>
                </div>
                <div class="bg-gradient-to-r from-green-500 to-green-600 rounded-xl shadow-xl p-6 text-white">
                    <p class="text-sm opacity-90 mb-1">Successful Trades</p>
                    <p class="text-3xl font-bold">{{ number_format($stats['successful_trades']) }}</p>
                </div>
                <div class="bg-gradient-to-r from-purple-500 to-purple-600 rounded-xl shadow-xl p-6 text-white">
                    <p class="text-sm opacity-90 mb-1">Total Volume</p>
                    <p class="text-3xl font-bold">{{ number_format($stats['total_volume'], 2) }}</p>
                </div>
                <div class="bg-gradient-to-r from-yellow-500 to-yellow-600 rounded-xl shadow-xl p-6 text-white">
                    <p class="text-sm opacity-90 mb-1">Total Profit</p>
                    <p class="text-3xl font-bold">฿{{ number_format($stats['total_profit'], 2) }}</p>
                </div>
            </div>

            <!-- Revenue by Package -->
            <div class="bg-white dark:bg-gray-800 shadow-xl rounded-lg p-6">
                <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-4">Revenue by Package</h3>
                <div class="space-y-4">
                    @foreach($revenueByPackage as $revenue)
                    <div class="flex items-center justify-between p-4 bg-gray-50 dark:bg-gray-700 rounded-lg">
                        <span class="font-medium">{{ $revenue->name }}</span>
                        <span class="text-xl font-bold text-green-600">฿{{ number_format($revenue->total_revenue, 0) }}</span>
                    </div>
                    @endforeach
                </div>
            </div>

            <!-- Monthly Growth -->
            <div class="bg-white dark:bg-gray-800 shadow-xl rounded-lg p-6">
                <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-4">Monthly Subscription Growth ({{ now()->year }})</h3>
                <div class="grid grid-cols-6 gap-4">
                    @foreach($monthlyGrowth as $month)
                    <div class="text-center p-4 bg-blue-50 dark:bg-blue-900 rounded-lg">
                        <p class="text-xs text-gray-600 dark:text-gray-400 mb-1">Month {{ $month->month }}</p>
                        <p class="text-2xl font-bold">{{ $month->count }}</p>
                    </div>
                    @endforeach
                </div>
            </div>

            <!-- Export Options -->
            <div class="bg-white dark:bg-gray-800 shadow-xl rounded-lg p-6">
                <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-4">Export Reports</h3>
                <div class="flex gap-3">
                    <button class="bg-green-600 hover:bg-green-700 text-white px-6 py-3 rounded-lg font-semibold">
                        📥 Export to Excel
                    </button>
                    <button class="bg-red-600 hover:bg-red-700 text-white px-6 py-3 rounded-lg font-semibold">
                        📄 Export to PDF
                    </button>
                    <button class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-lg font-semibold">
                        📊 Generate Custom Report
                    </button>
                </div>
            </div>

        </div>
    </div>
</x-app-layout>
