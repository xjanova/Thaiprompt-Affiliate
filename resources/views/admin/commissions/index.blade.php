@extends('layouts.admin')
@section('title', 'Commission Management')
@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="bg-gradient-to-r from-green-600 to-green-800 rounded-lg shadow-lg p-6 mb-6">
        <div class="flex items-center justify-between">
            <div class="flex items-center space-x-4">
                <div class="bg-white bg-opacity-20 p-3 rounded-lg">
                    <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                </div>
                <div><h1 class="text-3xl font-bold text-white">Commission Management</h1><p class="text-green-100 mt-1">Manage affiliate commissions and payouts</p></div>
            </div>
            <a href="{{ route('admin.commissions.create') }}" class="bg-white text-green-600 px-4 py-2 rounded-lg hover:bg-green-50 transition">Add Commission</a>
        </div>
    </div>
    <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4 mb-6"><p class="text-sm text-yellow-700"><strong>Under Development:</strong> Commission tracking and payout management system.</p></div>
    <div class="bg-white rounded-lg shadow"><table class="min-w-full divide-y divide-gray-200"><thead class="bg-gray-50"><tr><th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Affiliate</th><th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Amount</th><th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th><th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th></tr></thead><tbody class="bg-white"><tr><td colspan="4" class="px-6 py-8 text-center text-gray-500">No commissions found</td></tr></tbody></table></div>
</div>
@endsection
