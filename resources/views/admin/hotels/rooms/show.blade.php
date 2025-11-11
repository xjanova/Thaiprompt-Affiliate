@extends('layouts.admin')

@section('title', 'Room Details')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="bg-gradient-to-r from-pink-600 to-pink-800 rounded-lg shadow-lg p-6 mb-6">
        <div class="flex items-center justify-between">
            <div class="flex items-center space-x-4">
                <div class="bg-white bg-opacity-20 p-3 rounded-lg">
                    <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path>
                    </svg>
                </div>
                <div>
                    <h1 class="text-3xl font-bold text-white">Room Details</h1>
                    <p class="text-pink-100 mt-1">View room information and availability</p>
                </div>
            </div>
            <div class="flex space-x-3">
                <a href="#" class="bg-white text-pink-600 px-4 py-2 rounded-lg hover:bg-pink-50 transition">Edit</a>
                <a href="{{ route('admin.hotels.rooms.index') }}" class="bg-pink-500 text-white px-4 py-2 rounded-lg hover:bg-pink-600 transition">Back</a>
            </div>
        </div>
    </div>
    <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4 mb-6">
        <p class="text-sm text-yellow-700"><strong>Under Development:</strong> Room details with availability calendar and pricing management.</p>
    </div>
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 bg-white rounded-lg shadow p-6">
            <h2 class="text-xl font-semibold text-gray-900 mb-4">Room Information</h2>
            <div class="grid grid-cols-2 gap-4">
                <div><p class="text-sm text-gray-500">Room Number</p><p class="font-medium text-gray-900">101</p></div>
                <div><p class="text-sm text-gray-500">Room Type</p><p class="font-medium text-gray-900">Deluxe</p></div>
                <div><p class="text-sm text-gray-500">Capacity</p><p class="font-medium text-gray-900">2 guests</p></div>
                <div><p class="text-sm text-gray-500">Price per Night</p><p class="font-medium text-gray-900">฿2,500</p></div>
            </div>
        </div>
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Status</h3>
            <div class="space-y-3">
                <div class="flex justify-between"><span class="text-gray-600">Availability</span><span class="px-2 py-1 bg-green-100 text-green-800 rounded text-sm font-medium">Available</span></div>
                <div class="flex justify-between"><span class="text-gray-600">Cleanliness</span><span class="text-gray-900 font-medium">Clean</span></div>
            </div>
        </div>
    </div>
</div>
@endsection
