@extends('layouts.admin-v3')

@section('title', 'Performance Goal Details')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="bg-gradient-to-r from-indigo-600 to-indigo-800 rounded-lg shadow-lg p-6 mb-6">
        <div class="flex items-center justify-between">
            <div class="flex items-center space-x-4">
                <div class="bg-white bg-opacity-20 p-3 rounded-lg">
                    <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                    </svg>
                </div>
                <div>
                    <h1 class="text-3xl font-bold text-white">Performance Goal Details</h1>
                    <p class="text-indigo-100 mt-1">View goal details and progress</p>
                </div>
            </div>
            <div class="flex space-x-3">
                <a href="#" class="bg-white text-indigo-600 px-4 py-2 rounded-lg hover:bg-indigo-50 transition">Edit</a>
                <a href="{{ route('admin.hrm.performance-goals.index') }}" class="bg-indigo-500 text-white px-4 py-2 rounded-lg hover:bg-indigo-600 transition">Back</a>
            </div>
        </div>
    </div>
    <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4 mb-6">
        <p class="text-sm text-yellow-700"><strong>Under Development:</strong> Detailed goal view with progress timeline.</p>
    </div>
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 bg-white rounded-lg shadow p-6">
            <h2 class="text-xl font-semibold text-gray-900 mb-4">Goal Information</h2>
            <div class="space-y-3">
                <div><p class="text-sm text-gray-500">Title</p><p class="font-medium text-gray-900">Sample Performance Goal</p></div>
                <div><p class="text-sm text-gray-500">Employee</p><p class="font-medium text-gray-900">Employee Name</p></div>
                <div><p class="text-sm text-gray-500">Progress</p><div class="w-full bg-gray-200 rounded-full h-2 mt-2"><div class="bg-indigo-600 h-2 rounded-full" style="width: 50%"></div></div><p class="text-sm text-gray-600 mt-1">50% Complete</p></div>
            </div>
        </div>
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Timeline</h3>
            <div class="space-y-3">
                <div><span class="text-gray-600 text-sm">Start Date</span><p class="font-medium">2024-01-01</p></div>
                <div><span class="text-gray-600 text-sm">Target Date</span><p class="font-medium">2024-12-31</p></div>
                <div><span class="text-gray-600 text-sm">Status</span><span class="px-2 py-1 bg-blue-100 text-blue-800 rounded text-sm font-medium">In Progress</span></div>
            </div>
        </div>
    </div>
</div>
@endsection
