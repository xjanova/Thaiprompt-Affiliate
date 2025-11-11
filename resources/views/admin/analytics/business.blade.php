@extends('layouts.admin')

@section('title', 'Business Analytics')

@section('content')
<div class="container mx-auto px-4 py-8">
    <!-- Header -->
    <div class="bg-gradient-to-r from-pink-600 to-pink-800 rounded-lg shadow-lg p-6 mb-6">
        <div class="flex items-center justify-between">
            <div class="flex items-center space-x-4">
                <div class="bg-white bg-opacity-20 p-3 rounded-lg">
                    <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                    </svg>
                </div>
                <div>
                    <h1 class="text-3xl font-bold text-white">Business Analytics</h1>
                    <p class="text-pink-100 mt-1">Key business metrics and performance indicators</p>
                </div>
            </div>
            <a href="{{ route('admin.dashboard') }}" class="bg-white text-pink-600 px-4 py-2 rounded-lg hover:bg-pink-50 transition">
                Back to Dashboard
            </a>
        </div>
    </div>

    <!-- KPIs -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-sm font-medium text-gray-500 mb-2">Total Revenue</h3>
            <p class="text-3xl font-bold text-gray-900">฿--</p>
            <p class="text-sm text-green-600 mt-2">+--% growth</p>
        </div>
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-sm font-medium text-gray-500 mb-2">Active Users</h3>
            <p class="text-3xl font-bold text-gray-900">--</p>
            <p class="text-sm text-green-600 mt-2">+--% growth</p>
        </div>
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-sm font-medium text-gray-500 mb-2">Conversion Rate</h3>
            <p class="text-3xl font-bold text-gray-900">--%</p>
            <p class="text-sm text-gray-600 mt-2">Industry average</p>
        </div>
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-sm font-medium text-gray-500 mb-2">Customer LTV</h3>
            <p class="text-3xl font-bold text-gray-900">฿--</p>
            <p class="text-sm text-green-600 mt-2">Increasing</p>
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
                    <strong class="font-bold">Under Development:</strong> Comprehensive business analytics with revenue tracking, customer metrics, and growth analysis.
                </p>
            </div>
        </div>
    </div>

    <!-- Business Insights -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div class="bg-white rounded-lg shadow p-6">
            <h2 class="text-xl font-semibold text-gray-900 mb-4">Revenue Breakdown</h2>
            <div class="h-64 flex items-center justify-center bg-gray-50 rounded">
                <p class="text-gray-400">Chart will be displayed here</p>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow p-6">
            <h2 class="text-xl font-semibold text-gray-900 mb-4">User Growth Trend</h2>
            <div class="h-64 flex items-center justify-center bg-gray-50 rounded">
                <p class="text-gray-400">Chart will be displayed here</p>
            </div>
        </div>
    </div>
</div>
@endsection
