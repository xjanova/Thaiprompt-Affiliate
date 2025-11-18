@extends('layouts.admin-v3')
@section('title', 'Activity Report')
@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="bg-gradient-to-r from-gray-700 to-gray-900 rounded-lg shadow-lg p-6 mb-6">
        <div class="flex items-center justify-between">
            <div><h1 class="text-3xl font-bold text-white">Activity Report</h1><p class="text-gray-300 mt-1">User and system activity analysis</p></div>
            <button class="bg-white text-gray-700 px-4 py-2 rounded-lg">Export PDF</button>
        </div>
    </div>
    <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4 mb-6"><p class="text-sm text-yellow-700"><strong>Under Development:</strong> Activity tracking and reporting.</p></div>
    <div class="grid grid-cols-4 gap-6 mb-6">
        <div class="bg-white rounded-lg shadow p-6"><h3 class="text-sm text-gray-500 mb-2">Total Users</h3><p class="text-3xl font-bold">--</p></div>
        <div class="bg-white rounded-lg shadow p-6"><h3 class="text-sm text-gray-500 mb-2">Active Sessions</h3><p class="text-3xl font-bold">--</p></div>
        <div class="bg-white rounded-lg shadow p-6"><h3 class="text-sm text-gray-500 mb-2">Page Views</h3><p class="text-3xl font-bold">--</p></div>
        <div class="bg-white rounded-lg shadow p-6"><h3 class="text-sm text-gray-500 mb-2">Actions</h3><p class="text-3xl font-bold">--</p></div>
    </div>
    <div class="bg-white rounded-lg shadow p-6"><div class="h-64 flex items-center justify-center bg-gray-50 rounded"><p class="text-gray-400">Activity charts will be displayed here</p></div></div>
</div>
@endsection
