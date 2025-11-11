@extends('layouts.admin')

@section('title', 'Affiliate Tree - Single View')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="bg-gradient-to-r from-emerald-600 to-emerald-800 rounded-lg shadow-lg p-6 mb-6">
        <div class="flex items-center justify-between">
            <div class="flex items-center space-x-4">
                <div class="bg-white bg-opacity-20 p-3 rounded-lg">
                    <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                    </svg>
                </div>
                <div>
                    <h1 class="text-3xl font-bold text-white">Affiliate Tree - Single View</h1>
                    <p class="text-emerald-100 mt-1">View individual affiliate downline structure</p>
                </div>
            </div>
            <a href="{{ route('admin.affiliates.index') }}" class="bg-white text-emerald-600 px-4 py-2 rounded-lg hover:bg-emerald-50 transition">Back to List</a>
        </div>
    </div>
    <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4 mb-6">
        <p class="text-sm text-yellow-700"><strong>Under Development:</strong> Single affiliate tree view with downline statistics and earnings breakdown.</p>
    </div>
    <div class="grid grid-cols-1 lg:grid-cols-4 gap-6 mb-6">
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-sm font-medium text-gray-500 mb-2">Total Downline</h3>
            <p class="text-3xl font-bold text-gray-900">--</p>
        </div>
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-sm font-medium text-gray-500 mb-2">Direct Referrals</h3>
            <p class="text-3xl font-bold text-gray-900">--</p>
        </div>
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-sm font-medium text-gray-500 mb-2">Tree Depth</h3>
            <p class="text-3xl font-bold text-gray-900">-- levels</p>
        </div>
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-sm font-medium text-gray-500 mb-2">Total Earnings</h3>
            <p class="text-3xl font-bold text-gray-900">฿--</p>
        </div>
    </div>
    <div class="bg-white rounded-lg shadow p-6">
        <h2 class="text-xl font-semibold text-gray-900 mb-4">Downline Structure</h2>
        <div class="text-center py-12">
            <svg class="w-16 h-16 text-gray-400 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path>
            </svg>
            <p class="text-gray-600 text-lg">Select an affiliate to view their downline</p>
            <p class="text-gray-500 text-sm mt-2">Tree structure and statistics will be displayed here</p>
        </div>
    </div>
</div>
@endsection
