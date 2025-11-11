@extends('layouts.admin')
@section('title', 'Quotations')
@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="bg-gradient-to-r from-yellow-600 to-yellow-800 rounded-lg shadow-lg p-6 mb-6">
        <div class="flex items-center justify-between">
            <div><h1 class="text-3xl font-bold text-white">Quotations</h1><p class="text-yellow-100 mt-1">Manage customer quotations</p></div>
            <a href="{{ route('admin.quotations.create') }}" class="bg-white text-yellow-600 px-4 py-2 rounded-lg">New Quotation</a>
        </div>
    </div>
    <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4 mb-6"><p class="text-sm text-yellow-700"><strong>Under Development:</strong> Quotation management system.</p></div>
    <div class="bg-white rounded-lg shadow"><table class="min-w-full divide-y divide-gray-200"><thead class="bg-gray-50"><tr><th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Quote #</th><th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Customer</th><th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Amount</th><th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th><th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th></tr></thead><tbody class="bg-white"><tr><td colspan="5" class="px-6 py-8 text-center text-gray-500">No quotations found</td></tr></tbody></table></div>
</div>
@endsection
