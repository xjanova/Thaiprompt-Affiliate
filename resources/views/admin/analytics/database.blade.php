@extends('layouts.admin')

@section('title', 'Database Analytics')

@section('content')
<div class="container mx-auto px-4 py-8">
    <!-- Header -->
    <div class="bg-gradient-to-r from-indigo-600 to-indigo-800 rounded-lg shadow-lg p-6 mb-6">
        <div class="flex items-center justify-between">
            <div class="flex items-center space-x-4">
                <div class="bg-white bg-opacity-20 p-3 rounded-lg">
                    <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4m0 5c0 2.21-3.582 4-8 4s-8-1.79-8-4"></path>
                    </svg>
                </div>
                <div>
                    <h1 class="text-3xl font-bold text-white">Database Analytics</h1>
                    <p class="text-indigo-100 mt-1">Monitor database performance and query optimization</p>
                </div>
            </div>
            <a href="{{ route('admin.dashboard') }}" class="bg-white text-indigo-600 px-4 py-2 rounded-lg hover:bg-indigo-50 transition">
                Back to Dashboard
            </a>
        </div>
    </div>

    <!-- Database Stats -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-sm font-medium text-gray-500 mb-2">Total Queries</h3>
            <p class="text-3xl font-bold text-gray-900">--</p>
        </div>
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-sm font-medium text-gray-500 mb-2">Slow Queries</h3>
            <p class="text-3xl font-bold text-gray-900">--</p>
        </div>
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-sm font-medium text-gray-500 mb-2">Database Size</h3>
            <p class="text-3xl font-bold text-gray-900">-- GB</p>
        </div>
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-sm font-medium text-gray-500 mb-2">Active Connections</h3>
            <p class="text-3xl font-bold text-gray-900">--</p>
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
                    <strong class="font-bold">Under Development:</strong> Database analytics including query performance, table statistics, and optimization recommendations.
                </p>
            </div>
        </div>
    </div>

    <!-- Database Tables -->
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200">
            <h2 class="text-xl font-semibold text-gray-900">Table Statistics</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Table Name</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Rows</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Size</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Last Updated</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <tr>
                        <td colspan="4" class="px-6 py-4 text-center text-gray-500">
                            No data available - Feature under development
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
