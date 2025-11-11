@extends('layouts.admin')

@section('title', 'Performance Analytics')

@section('content')
<div class="container mx-auto px-4 py-8">
    <!-- Header -->
    <div class="bg-gradient-to-r from-green-600 to-green-800 rounded-lg shadow-lg p-6 mb-6">
        <div class="flex items-center justify-between">
            <div class="flex items-center space-x-4">
                <div class="bg-white bg-opacity-20 p-3 rounded-lg">
                    <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                    </svg>
                </div>
                <div>
                    <h1 class="text-3xl font-bold text-white">Performance Analytics</h1>
                    <p class="text-green-100 mt-1">Monitor application performance and optimization metrics</p>
                </div>
            </div>
            <a href="{{ route('admin.dashboard') }}" class="bg-white text-green-600 px-4 py-2 rounded-lg hover:bg-green-50 transition">
                Back to Dashboard
            </a>
        </div>
    </div>

    <!-- Performance Metrics -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-sm font-medium text-gray-500 mb-2">Average Response Time</h3>
            <p class="text-3xl font-bold text-gray-900">-- ms</p>
            <p class="text-sm text-green-600 mt-2">Within acceptable range</p>
        </div>
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-sm font-medium text-gray-500 mb-2">Page Load Time</h3>
            <p class="text-3xl font-bold text-gray-900">-- s</p>
            <p class="text-sm text-green-600 mt-2">Good performance</p>
        </div>
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-sm font-medium text-gray-500 mb-2">Query Time</h3>
            <p class="text-3xl font-bold text-gray-900">-- ms</p>
            <p class="text-sm text-yellow-600 mt-2">Needs optimization</p>
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
                    <strong class="font-bold">Under Development:</strong> Comprehensive performance monitoring including response times, query optimization, and resource usage analysis.
                </p>
            </div>
        </div>
    </div>

    <!-- Performance Areas -->
    <div class="bg-white rounded-lg shadow p-6">
        <h2 class="text-xl font-semibold text-gray-900 mb-4">Performance Monitoring Areas</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="border border-gray-200 rounded-lg p-4">
                <h3 class="font-semibold text-gray-900 mb-2">Server Performance</h3>
                <p class="text-sm text-gray-600">CPU usage, memory consumption, disk I/O</p>
            </div>
            <div class="border border-gray-200 rounded-lg p-4">
                <h3 class="font-semibold text-gray-900 mb-2">Database Performance</h3>
                <p class="text-sm text-gray-600">Query execution time, connection pool, indexes</p>
            </div>
            <div class="border border-gray-200 rounded-lg p-4">
                <h3 class="font-semibold text-gray-900 mb-2">API Performance</h3>
                <p class="text-sm text-gray-600">Endpoint response times, throughput, errors</p>
            </div>
            <div class="border border-gray-200 rounded-lg p-4">
                <h3 class="font-semibold text-gray-900 mb-2">Frontend Performance</h3>
                <p class="text-sm text-gray-600">Page load times, asset delivery, rendering</p>
            </div>
        </div>
    </div>
</div>
@endsection
