@extends('layouts.admin-v3')

@section('title', 'Interactive Affiliate Tree')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="bg-gradient-to-r from-emerald-600 to-emerald-800 rounded-lg shadow-lg p-6 mb-6">
        <div class="flex items-center justify-between">
            <div class="flex items-center space-x-4">
                <div class="bg-white bg-opacity-20 p-3 rounded-lg">
                    <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"></path>
                    </svg>
                </div>
                <div>
                    <h1 class="text-3xl font-bold text-white">Interactive Affiliate Tree</h1>
                    <p class="text-emerald-100 mt-1">Visual representation of affiliate network structure</p>
                </div>
            </div>
            <a href="{{ route('admin.affiliates.index') }}" class="bg-white text-emerald-600 px-4 py-2 rounded-lg hover:bg-emerald-50 transition">Back to List</a>
        </div>
    </div>
    <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4 mb-6">
        <p class="text-sm text-yellow-700"><strong>Under Development:</strong> Interactive tree visualization with drag-and-drop, zoom, and search capabilities.</p>
    </div>
    <div class="bg-white rounded-lg shadow p-6">
        <div class="flex justify-between items-center mb-6">
            <div class="flex space-x-3">
                <button class="px-4 py-2 bg-emerald-600 text-white rounded hover:bg-emerald-700 transition">Zoom In</button>
                <button class="px-4 py-2 bg-emerald-600 text-white rounded hover:bg-emerald-700 transition">Zoom Out</button>
                <button class="px-4 py-2 bg-emerald-600 text-white rounded hover:bg-emerald-700 transition">Reset View</button>
            </div>
            <div>
                <input type="text" placeholder="Search affiliate..." class="px-4 py-2 border border-gray-300 rounded-lg">
            </div>
        </div>
        <div class="h-96 bg-gray-50 rounded-lg flex items-center justify-center">
            <div class="text-center">
                <svg class="w-20 h-20 text-gray-400 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"></path>
                </svg>
                <p class="text-gray-600 text-lg">Interactive Tree View Coming Soon</p>
                <p class="text-gray-500 text-sm mt-2">Will feature D3.js visualization with interactive nodes</p>
            </div>
        </div>
    </div>
</div>
@endsection
