@extends('layouts.admin')

@section('title', 'Department Details')

@section('content')
<div class="container mx-auto px-4 py-8">
    <!-- Header -->
    <div class="bg-gradient-to-r from-blue-600 to-blue-800 rounded-lg shadow-lg p-6 mb-6">
        <div class="flex items-center justify-between">
            <div class="flex items-center space-x-4">
                <div class="bg-white bg-opacity-20 p-3 rounded-lg">
                    <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                    </svg>
                </div>
                <div>
                    <h1 class="text-3xl font-bold text-white">Department Details</h1>
                    <p class="text-blue-100 mt-1">View department information and staff</p>
                </div>
            </div>
            <div class="flex space-x-3">
                <a href="#" class="bg-white text-blue-600 px-4 py-2 rounded-lg hover:bg-blue-50 transition">
                    Edit
                </a>
                <a href="{{ route('admin.hrm.departments.index') }}" class="bg-blue-500 text-white px-4 py-2 rounded-lg hover:bg-blue-600 transition">
                    Back
                </a>
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
                    <strong class="font-bold">Under Development:</strong> Department details view with staff listing and organizational chart.
                </p>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Department Info -->
        <div class="lg:col-span-2">
            <div class="bg-white rounded-lg shadow p-6 mb-6">
                <h2 class="text-xl font-semibold text-gray-900 mb-4">Department Information</h2>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <p class="text-sm text-gray-500">Department Name</p>
                        <p class="font-medium text-gray-900 mt-1">Sample Department</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500">Department Code</p>
                        <p class="font-medium text-gray-900 mt-1">DEPT</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500">Manager</p>
                        <p class="font-medium text-gray-900 mt-1">Not Assigned</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500">Location</p>
                        <p class="font-medium text-gray-900 mt-1">Main Office</p>
                    </div>
                    <div class="col-span-2">
                        <p class="text-sm text-gray-500">Description</p>
                        <p class="font-medium text-gray-900 mt-1">Department description will appear here</p>
                    </div>
                </div>
            </div>

            <!-- Staff List -->
            <div class="bg-white rounded-lg shadow overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h2 class="text-xl font-semibold text-gray-900">Department Staff</h2>
                </div>
                <div class="p-6">
                    <p class="text-center text-gray-500">No staff members assigned to this department</p>
                </div>
            </div>
        </div>

        <!-- Stats Sidebar -->
        <div class="space-y-6">
            <div class="bg-white rounded-lg shadow p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Statistics</h3>
                <div class="space-y-3">
                    <div class="flex justify-between items-center">
                        <span class="text-gray-600">Total Employees</span>
                        <span class="font-bold text-gray-900">--</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-gray-600">Sub-departments</span>
                        <span class="font-bold text-gray-900">--</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-gray-600">Status</span>
                        <span class="px-2 py-1 bg-green-100 text-green-800 rounded text-sm font-medium">Active</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
