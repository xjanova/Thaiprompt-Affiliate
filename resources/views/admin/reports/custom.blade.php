@extends('layouts.admin-v3')
@section('title', 'Custom Report')
@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="bg-gradient-to-r from-gray-700 to-gray-900 rounded-lg shadow-lg p-6 mb-6">
        <div class="flex items-center justify-between">
            <div><h1 class="text-3xl font-bold text-white">Custom Report Builder</h1><p class="text-gray-300 mt-1">Create custom reports with filters</p></div>
            <button class="bg-white text-gray-700 px-4 py-2 rounded-lg">Generate Report</button>
        </div>
    </div>
    <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4 mb-6"><p class="text-sm text-yellow-700"><strong>Under Development:</strong> Custom report builder with flexible filters.</p></div>
    <div class="bg-white rounded-lg shadow p-6 mb-6">
        <h2 class="text-xl font-semibold mb-4">Report Configuration</h2>
        <div class="grid grid-cols-2 gap-6">
            <div><label class="block text-sm font-medium text-gray-700 mb-2">Report Type</label><select class="w-full px-4 py-2 border rounded-lg"><option>Sales Report</option><option>User Report</option><option>Transaction Report</option></select></div>
            <div><label class="block text-sm font-medium text-gray-700 mb-2">Date Range</label><select class="w-full px-4 py-2 border rounded-lg"><option>Last 7 Days</option><option>Last 30 Days</option><option>Last 90 Days</option><option>Custom</option></select></div>
            <div><label class="block text-sm font-medium text-gray-700 mb-2">Group By</label><select class="w-full px-4 py-2 border rounded-lg"><option>Day</option><option>Week</option><option>Month</option></select></div>
            <div><label class="block text-sm font-medium text-gray-700 mb-2">Output Format</label><select class="w-full px-4 py-2 border rounded-lg"><option>PDF</option><option>Excel</option><option>CSV</option></select></div>
        </div>
    </div>
    <div class="bg-white rounded-lg shadow p-6"><div class="h-64 flex items-center justify-center bg-gray-50 rounded"><p class="text-gray-400">Report preview will be displayed here</p></div></div>
</div>
@endsection
