@extends('layouts.admin')
@section('title', 'Financial Report')
@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="bg-gradient-to-r from-gray-700 to-gray-900 rounded-lg shadow-lg p-6 mb-6">
        <div class="flex items-center justify-between">
            <div><h1 class="text-3xl font-bold text-white">Financial Report</h1><p class="text-gray-300 mt-1">Comprehensive financial analysis</p></div>
            <button class="bg-white text-gray-700 px-4 py-2 rounded-lg">Export PDF</button>
        </div>
    </div>
    <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4 mb-6"><p class="text-sm text-yellow-700"><strong>Under Development:</strong> Financial reporting with charts and export.</p></div>
    <div class="grid grid-cols-4 gap-6 mb-6">
        <div class="bg-white rounded-lg shadow p-6"><h3 class="text-sm text-gray-500 mb-2">Total Revenue</h3><p class="text-3xl font-bold">฿--</p></div>
        <div class="bg-white rounded-lg shadow p-6"><h3 class="text-sm text-gray-500 mb-2">Total Expenses</h3><p class="text-3xl font-bold">฿--</p></div>
        <div class="bg-white rounded-lg shadow p-6"><h3 class="text-sm text-gray-500 mb-2">Net Profit</h3><p class="text-3xl font-bold text-green-600">฿--</p></div>
        <div class="bg-white rounded-lg shadow p-6"><h3 class="text-sm text-gray-500 mb-2">Profit Margin</h3><p class="text-3xl font-bold">--%</p></div>
    </div>
    <div class="bg-white rounded-lg shadow p-6"><div class="h-64 flex items-center justify-center bg-gray-50 rounded"><p class="text-gray-400">Financial charts will be displayed here</p></div></div>
</div>
@endsection
