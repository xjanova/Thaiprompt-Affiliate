@extends('layouts.admin-v3')

@section('title', 'Mark Attendance')

@section('content')
<div class="container mx-auto px-4 py-8">
    <!-- Header -->
    <div class="bg-gradient-to-r from-green-600 to-green-800 rounded-lg shadow-lg p-6 mb-6">
        <div class="flex items-center justify-between">
            <div class="flex items-center space-x-4">
                <div class="bg-white bg-opacity-20 p-3 rounded-lg">
                    <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
                <div>
                    <h1 class="text-3xl font-bold text-white">Mark Attendance</h1>
                    <p class="text-green-100 mt-1">Quick attendance marking for today</p>
                </div>
            </div>
            <a href="{{ route('admin.hrm.attendance.index') }}" class="bg-white text-green-600 px-4 py-2 rounded-lg hover:bg-green-50 transition">
                Back to List
            </a>
        </div>
    </div>

    <!-- Current Date/Time -->
    <div class="bg-white rounded-lg shadow p-6 mb-6">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-2xl font-bold text-gray-900">Today's Date</h2>
                <p class="text-gray-600 mt-1">{{ date('l, F j, Y') }}</p>
            </div>
            <div class="text-right">
                <h2 class="text-2xl font-bold text-gray-900">Current Time</h2>
                <p class="text-gray-600 mt-1" id="current-time">--:--:--</p>
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
                    <strong class="font-bold">Under Development:</strong> Real-time attendance marking interface with bulk marking capabilities.
                </p>
            </div>
        </div>
    </div>

    <!-- Quick Mark Form -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2">
            <div class="bg-white rounded-lg shadow p-6">
                <h2 class="text-xl font-semibold text-gray-900 mb-4">Employee List</h2>
                <div class="mb-4">
                    <input type="text" placeholder="Search employees..." class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent">
                </div>
                <div class="space-y-2 max-h-96 overflow-y-auto">
                    <div class="flex items-center justify-between p-4 border border-gray-200 rounded-lg">
                        <div class="flex items-center space-x-3">
                            <div class="w-10 h-10 bg-gray-200 rounded-full flex items-center justify-center">
                                <svg class="w-6 h-6 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                </svg>
                            </div>
                            <div>
                                <p class="font-medium text-gray-900">Employee Name</p>
                                <p class="text-sm text-gray-500">Department</p>
                            </div>
                        </div>
                        <div class="flex space-x-2">
                            <button class="px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700 transition text-sm">
                                Present
                            </button>
                            <button class="px-4 py-2 bg-red-600 text-white rounded hover:bg-red-700 transition text-sm">
                                Absent
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Summary -->
        <div class="space-y-6">
            <div class="bg-white rounded-lg shadow p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Today's Summary</h3>
                <div class="space-y-3">
                    <div class="flex justify-between items-center">
                        <span class="text-gray-600">Total Employees</span>
                        <span class="font-bold text-gray-900">--</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-gray-600">Present</span>
                        <span class="font-bold text-green-600">--</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-gray-600">Absent</span>
                        <span class="font-bold text-red-600">--</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-gray-600">Not Marked</span>
                        <span class="font-bold text-gray-600">--</span>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Quick Actions</h3>
                <div class="space-y-2">
                    <button class="w-full px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition">
                        Mark All Present
                    </button>
                    <button class="w-full px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition">
                        Export Report
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function updateTime() {
    const now = new Date();
    const timeString = now.toLocaleTimeString();
    document.getElementById('current-time').textContent = timeString;
}
setInterval(updateTime, 1000);
updateTime();
</script>
@endsection
