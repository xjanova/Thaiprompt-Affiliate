@extends('layouts.admin')

@section('title', 'Performance Goals')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="bg-gradient-to-r from-indigo-600 to-indigo-800 rounded-lg shadow-lg p-6 mb-6">
        <div class="flex items-center justify-between">
            <div class="flex items-center space-x-4">
                <div class="bg-white bg-opacity-20 p-3 rounded-lg">
                    <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
                <div>
                    <h1 class="text-3xl font-bold text-white">Performance Goals</h1>
                    <p class="text-indigo-100 mt-1">Track and manage employee goals and objectives</p>
                </div>
            </div>
            <div class="flex space-x-3">
                <a href="{{ route('admin.hrm.performance-goals.create') }}" class="bg-white text-indigo-600 px-4 py-2 rounded-lg hover:bg-indigo-50 transition">New Goal</a>
                <a href="{{ route('admin.dashboard') }}" class="bg-indigo-500 text-white px-4 py-2 rounded-lg hover:bg-indigo-600 transition">Back</a>
            </div>
        </div>
    </div>
    <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4 mb-6">
        <p class="text-sm text-yellow-700"><strong>Under Development:</strong> Goal setting and tracking system with progress monitoring.</p>
    </div>
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Employee</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Goal</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Target Date</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Progress</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <tr>
                    <td colspan="6" class="px-6 py-8 text-center text-gray-500">
                        <p class="text-lg font-medium">No performance goals found</p>
                        <p class="text-sm mt-1">Create your first performance goal</p>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</div>
@endsection
