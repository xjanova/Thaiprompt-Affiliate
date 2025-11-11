@extends('layouts.admin')

@section('title', 'Booking Calendar')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="bg-gradient-to-r from-pink-600 to-pink-800 rounded-lg shadow-lg p-6 mb-6">
        <div class="flex items-center justify-between">
            <div class="flex items-center space-x-4">
                <div class="bg-white bg-opacity-20 p-3 rounded-lg">
                    <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                    </svg>
                </div>
                <div>
                    <h1 class="text-3xl font-bold text-white">Booking Calendar</h1>
                    <p class="text-pink-100 mt-1">View and manage bookings in calendar view</p>
                </div>
            </div>
            <div class="flex space-x-3">
                <a href="{{ route('admin.hotels.bookings.create') }}" class="bg-white text-pink-600 px-4 py-2 rounded-lg hover:bg-pink-50 transition">New Booking</a>
                <a href="{{ route('admin.hotels.bookings.index') }}" class="bg-pink-500 text-white px-4 py-2 rounded-lg hover:bg-pink-600 transition">Back</a>
            </div>
        </div>
    </div>
    <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4 mb-6">
        <p class="text-sm text-yellow-700"><strong>Under Development:</strong> Interactive calendar view with drag-and-drop booking management.</p>
    </div>
    <div class="bg-white rounded-lg shadow p-6">
        <div class="flex justify-between items-center mb-6">
            <div class="flex space-x-3">
                <button class="px-4 py-2 bg-pink-600 text-white rounded hover:bg-pink-700 transition">Today</button>
                <button class="px-4 py-2 bg-gray-200 text-gray-700 rounded hover:bg-gray-300 transition">Week</button>
                <button class="px-4 py-2 bg-gray-200 text-gray-700 rounded hover:bg-gray-300 transition">Month</button>
            </div>
            <div class="flex space-x-3">
                <button class="px-4 py-2 bg-gray-200 rounded hover:bg-gray-300">Previous</button>
                <span class="px-4 py-2 font-semibold">November 2024</span>
                <button class="px-4 py-2 bg-gray-200 rounded hover:bg-gray-300">Next</button>
            </div>
        </div>
        <div class="h-96 bg-gray-50 rounded-lg flex items-center justify-center">
            <div class="text-center">
                <svg class="w-20 h-20 text-gray-400 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                </svg>
                <p class="text-gray-600 text-lg">Calendar View Coming Soon</p>
                <p class="text-gray-500 text-sm mt-2">Will feature full calendar with booking management</p>
            </div>
        </div>
    </div>
</div>
@endsection
