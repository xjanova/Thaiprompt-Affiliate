@extends('layouts.admin')

@section('title', 'Traffic Analytics')

@section('content')
<div class="container mx-auto px-4 py-8">
    <!-- Header -->
    <div class="bg-gradient-to-r from-orange-600 to-orange-800 rounded-lg shadow-lg p-6 mb-6">
        <div class="flex items-center justify-between">
            <div class="flex items-center space-x-4">
                <div class="bg-white bg-opacity-20 p-3 rounded-lg">
                    <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
                <div>
                    <h1 class="text-3xl font-bold text-white">Traffic Analytics</h1>
                    <p class="text-orange-100 mt-1">Analyze visitor traffic and user behavior patterns</p>
                </div>
            </div>
            <a href="{{ route('admin.dashboard') }}" class="bg-white text-orange-600 px-4 py-2 rounded-lg hover:bg-orange-50 transition">
                Back to Dashboard
            </a>
        </div>
    </div>

    <!-- Traffic Stats -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-sm font-medium text-gray-500 mb-2">Total Visitors</h3>
            <p class="text-3xl font-bold text-gray-900">--</p>
            <p class="text-sm text-green-600 mt-2">+--% from last month</p>
        </div>
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-sm font-medium text-gray-500 mb-2">Page Views</h3>
            <p class="text-3xl font-bold text-gray-900">--</p>
            <p class="text-sm text-green-600 mt-2">+--% from last month</p>
        </div>
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-sm font-medium text-gray-500 mb-2">Bounce Rate</h3>
            <p class="text-3xl font-bold text-gray-900">--%</p>
            <p class="text-sm text-red-600 mt-2">Needs improvement</p>
        </div>
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-sm font-medium text-gray-500 mb-2">Avg. Session Duration</h3>
            <p class="text-3xl font-bold text-gray-900">-- min</p>
            <p class="text-sm text-gray-600 mt-2">Good engagement</p>
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
                    <strong class="font-bold">Under Development:</strong> Traffic analytics with visitor tracking, page views, referral sources, and geographic distribution.
                </p>
            </div>
        </div>
    </div>

    <!-- Traffic Sources -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div class="bg-white rounded-lg shadow p-6">
            <h2 class="text-xl font-semibold text-gray-900 mb-4">Traffic Sources</h2>
            <div class="space-y-3">
                <div class="flex justify-between items-center p-3 bg-gray-50 rounded">
                    <span class="font-medium text-gray-900">Direct</span>
                    <span class="text-sm text-gray-500">--%</span>
                </div>
                <div class="flex justify-between items-center p-3 bg-gray-50 rounded">
                    <span class="font-medium text-gray-900">Organic Search</span>
                    <span class="text-sm text-gray-500">--%</span>
                </div>
                <div class="flex justify-between items-center p-3 bg-gray-50 rounded">
                    <span class="font-medium text-gray-900">Social Media</span>
                    <span class="text-sm text-gray-500">--%</span>
                </div>
                <div class="flex justify-between items-center p-3 bg-gray-50 rounded">
                    <span class="font-medium text-gray-900">Referral</span>
                    <span class="text-sm text-gray-500">--%</span>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow p-6">
            <h2 class="text-xl font-semibold text-gray-900 mb-4">Top Pages</h2>
            <div class="space-y-3">
                <div class="flex justify-between items-center p-3 bg-gray-50 rounded">
                    <span class="font-medium text-gray-900">Home Page</span>
                    <span class="text-sm text-gray-500">-- views</span>
                </div>
                <div class="flex justify-between items-center p-3 bg-gray-50 rounded">
                    <span class="font-medium text-gray-900">Products</span>
                    <span class="text-sm text-gray-500">-- views</span>
                </div>
                <div class="flex justify-between items-center p-3 bg-gray-50 rounded">
                    <span class="font-medium text-gray-900">About</span>
                    <span class="text-sm text-gray-500">-- views</span>
                </div>
                <div class="flex justify-between items-center p-3 bg-gray-50 rounded">
                    <span class="font-medium text-gray-900">Contact</span>
                    <span class="text-sm text-gray-500">-- views</span>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
