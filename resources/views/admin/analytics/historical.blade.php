@extends('layouts.admin')

@section('title', 'Historical Analytics')

@section('content')
<div class="container mx-auto px-4 py-8">
    <!-- Header -->
    <div class="bg-gradient-to-r from-purple-600 to-purple-800 rounded-lg shadow-lg p-6 mb-6">
        <div class="flex items-center justify-between">
            <div class="flex items-center space-x-4">
                <div class="bg-white bg-opacity-20 p-3 rounded-lg">
                    <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                    </svg>
                </div>
                <div>
                    <h1 class="text-3xl font-bold text-white">Historical Analytics</h1>
                    <p class="text-purple-100 mt-1">Analyze historical data and trends over time</p>
                </div>
            </div>
            <a href="{{ route('admin.dashboard') }}" class="bg-white text-purple-600 px-4 py-2 rounded-lg hover:bg-purple-50 transition">
                Back to Dashboard
            </a>
        </div>
    </div>

    <!-- Date Range Filter -->
    <div class="bg-white rounded-lg shadow p-6 mb-6">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Start Date</label>
                <input type="date" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">End Date</label>
                <input type="date" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Metric Type</label>
                <select class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                    <option>All Metrics</option>
                    <option>User Activity</option>
                    <option>Revenue</option>
                    <option>Performance</option>
                </select>
            </div>
            <div class="flex items-end">
                <button class="w-full bg-purple-600 text-white px-4 py-2 rounded-lg hover:bg-purple-700 transition">
                    Apply Filters
                </button>
            </div>
        </div>
    </div>

    <!-- Development Notice -->
    <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4 mb-6">
        <div class="flex">
            <div class="flex-shrink-0">
                <svg class="h-5 w-5 text-yellow-400" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                </svg>
            </div>
            <div class="ml-3">
                <p class="text-sm text-yellow-700">
                    <strong class="font-bold">Under Development:</strong> Historical analytics with customizable date ranges, trend analysis, and comparative reporting.
                </p>
            </div>
        </div>
    </div>

    <!-- Placeholder Charts -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">User Growth Over Time</h3>
            <div class="h-64 flex items-center justify-center bg-gray-50 rounded">
                <p class="text-gray-400">Chart will be displayed here</p>
            </div>
        </div>
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Revenue Trends</h3>
            <div class="h-64 flex items-center justify-center bg-gray-50 rounded">
                <p class="text-gray-400">Chart will be displayed here</p>
            </div>
        </div>
    </div>
</div>
@endsection
