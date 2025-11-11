@extends('layouts.admin')

@section('title', 'Capacity Analytics')

@section('content')
<div class="container mx-auto px-4 py-8">
    <!-- Header -->
    <div class="bg-gradient-to-r from-cyan-600 to-cyan-800 rounded-lg shadow-lg p-6 mb-6">
        <div class="flex items-center justify-between">
            <div class="flex items-center space-x-4">
                <div class="bg-white bg-opacity-20 p-3 rounded-lg">
                    <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"></path>
                    </svg>
                </div>
                <div>
                    <h1 class="text-3xl font-bold text-white">Capacity Analytics</h1>
                    <p class="text-cyan-100 mt-1">Monitor resource utilization and capacity planning</p>
                </div>
            </div>
            <a href="{{ route('admin.dashboard') }}" class="bg-white text-cyan-600 px-4 py-2 rounded-lg hover:bg-cyan-50 transition">
                Back to Dashboard
            </a>
        </div>
    </div>

    <!-- Resource Usage -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-sm font-medium text-gray-500 mb-2">CPU Usage</h3>
            <p class="text-3xl font-bold text-gray-900">--%</p>
            <div class="mt-2 w-full bg-gray-200 rounded-full h-2">
                <div class="bg-cyan-600 h-2 rounded-full" style="width: 0%"></div>
            </div>
        </div>
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-sm font-medium text-gray-500 mb-2">Memory Usage</h3>
            <p class="text-3xl font-bold text-gray-900">--%</p>
            <div class="mt-2 w-full bg-gray-200 rounded-full h-2">
                <div class="bg-blue-600 h-2 rounded-full" style="width: 0%"></div>
            </div>
        </div>
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-sm font-medium text-gray-500 mb-2">Disk Usage</h3>
            <p class="text-3xl font-bold text-gray-900">--%</p>
            <div class="mt-2 w-full bg-gray-200 rounded-full h-2">
                <div class="bg-purple-600 h-2 rounded-full" style="width: 0%"></div>
            </div>
        </div>
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-sm font-medium text-gray-500 mb-2">Network Usage</h3>
            <p class="text-3xl font-bold text-gray-900">-- Mbps</p>
            <div class="mt-2 w-full bg-gray-200 rounded-full h-2">
                <div class="bg-green-600 h-2 rounded-full" style="width: 0%"></div>
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
                    <strong class="font-bold">Under Development:</strong> Capacity planning analytics with resource monitoring, usage forecasting, and scaling recommendations.
                </p>
            </div>
        </div>
    </div>

    <!-- Capacity Planning -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div class="bg-white rounded-lg shadow p-6">
            <h2 class="text-xl font-semibold text-gray-900 mb-4">Resource Trends</h2>
            <div class="h-64 flex items-center justify-center bg-gray-50 rounded">
                <p class="text-gray-400">Trend chart will be displayed here</p>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow p-6">
            <h2 class="text-xl font-semibold text-gray-900 mb-4">Capacity Forecast</h2>
            <div class="space-y-4">
                <div class="p-4 bg-blue-50 border border-blue-200 rounded-lg">
                    <h3 class="font-semibold text-blue-900 mb-2">Storage Capacity</h3>
                    <p class="text-sm text-blue-700">Estimated to reach 80% in 3 months</p>
                </div>
                <div class="p-4 bg-green-50 border border-green-200 rounded-lg">
                    <h3 class="font-semibold text-green-900 mb-2">CPU Resources</h3>
                    <p class="text-sm text-green-700">Sufficient for next 6 months</p>
                </div>
                <div class="p-4 bg-yellow-50 border border-yellow-200 rounded-lg">
                    <h3 class="font-semibold text-yellow-900 mb-2">Memory Resources</h3>
                    <p class="text-sm text-yellow-700">May need upgrade in 4 months</p>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
