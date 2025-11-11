@extends('layouts.admin')
@section('title', 'Quotation Details')
@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="bg-gradient-to-r from-yellow-600 to-yellow-800 rounded-lg shadow-lg p-6 mb-6">
        <div class="flex items-center justify-between">
            <div><h1 class="text-3xl font-bold text-white">Quotation Details</h1></div>
            <div class="flex space-x-3">
                <a href="#" class="bg-white text-yellow-600 px-4 py-2 rounded-lg">Edit</a>
                <a href="{{ route('admin.quotations.index') }}" class="bg-yellow-500 text-white px-4 py-2 rounded-lg">Back</a>
            </div>
        </div>
    </div>
    <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4 mb-6"><p class="text-sm text-yellow-700"><strong>Under Development:</strong> Quotation details view.</p></div>
    <div class="bg-white rounded-lg shadow p-6">
        <div class="grid grid-cols-2 gap-4">
            <div><p class="text-sm text-gray-500">Quote Number</p><p class="font-medium">Q-2024-001</p></div>
            <div><p class="text-sm text-gray-500">Customer</p><p class="font-medium">Sample Customer</p></div>
            <div><p class="text-sm text-gray-500">Amount</p><p class="font-medium">฿10,000</p></div>
            <div><p class="text-sm text-gray-500">Status</p><span class="px-2 py-1 bg-green-100 text-green-800 rounded text-sm">Sent</span></div>
        </div>
    </div>
</div>
@endsection
