@extends('layouts.admin')
@section('title', 'Commission Details')
@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="bg-gradient-to-r from-green-600 to-green-800 rounded-lg shadow-lg p-6 mb-6">
        <div class="flex items-center justify-between">
            <div><h1 class="text-3xl font-bold text-white">Commission Details</h1></div>
            <a href="{{ route('admin.commissions.index') }}" class="bg-white text-green-600 px-4 py-2 rounded-lg">Back</a>
        </div>
    </div>
    <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4 mb-6"><p class="text-sm text-yellow-700"><strong>Under Development:</strong> Commission details view.</p></div>
    <div class="bg-white rounded-lg shadow p-6">
        <div class="grid grid-cols-2 gap-4">
            <div><p class="text-sm text-gray-500">Affiliate</p><p class="font-medium">Sample Affiliate</p></div>
            <div><p class="text-sm text-gray-500">Amount</p><p class="font-medium">฿1,000</p></div>
        </div>
    </div>
</div>
@endsection
