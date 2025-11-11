@extends('layouts.admin')
@section('title', 'Sales Trends')
@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="bg-gradient-to-r from-blue-600 to-blue-800 rounded-lg shadow-lg p-6 mb-6">
        <div class="flex items-center justify-between">
            <div><h1 class="text-3xl font-bold text-white">Sales Trends</h1><p class="text-blue-100 mt-1">Analyze sales patterns and trends</p></div>
            <a href="{{ route('admin.dashboard') }}" class="bg-white text-blue-600 px-4 py-2 rounded-lg">Back</a>
        </div>
    </div>
    <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4 mb-6"><p class="text-sm text-yellow-700"><strong>Under Development:</strong> Sales trends analysis with charts.</p></div>
    <div class="grid grid-cols-4 gap-6 mb-6">
        <div class="bg-white rounded-lg shadow p-6"><h3 class="text-sm text-gray-500 mb-2">Total Sales</h3><p class="text-3xl font-bold">฿--</p></div>
        <div class="bg-white rounded-lg shadow p-6"><h3 class="text-sm text-gray-500 mb-2">Growth</h3><p class="text-3xl font-bold">--%</p></div>
        <div class="bg-white rounded-lg shadow p-6"><h3 class="text-sm text-gray-500 mb-2">Orders</h3><p class="text-3xl font-bold">--</p></div>
        <div class="bg-white rounded-lg shadow p-6"><h3 class="text-sm text-gray-500 mb-2">Avg Order</h3><p class="text-3xl font-bold">฿--</p></div>
    </div>
    <div class="bg-white rounded-lg shadow p-6"><div class="h-64 flex items-center justify-center bg-gray-50 rounded"><p class="text-gray-400">Chart will be displayed here</p></div></div>
</div>
@endsection
