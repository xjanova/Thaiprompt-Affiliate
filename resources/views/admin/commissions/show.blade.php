@extends('layouts.admin-v3')
@section('title', 'Commission Details')
@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="rounded-lg shadow-lg p-6 mb-6" style="background: linear-gradient(to right, #059669, #065f46);">
        <div class="flex items-center justify-between">
            <div><h1 class="text-3xl font-bold" style="color: #ffffff;">Commission Details</h1></div>
            <a href="{{ route('admin.commissions.index') }}" class="px-4 py-2 rounded-lg" style="background-color: #ffffff; color: #059669;">Back</a>
        </div>
    </div>
    <div class="border-l-4 p-4 mb-6" style="background-color: #fefce8; border-color: #facc15;">
        <p class="text-sm" style="color: #a16207;"><strong>Under Development:</strong> Commission details view.</p>
    </div>
    <div class="rounded-lg shadow p-6" style="background-color: #ffffff;">
        <div class="grid grid-cols-2 gap-4">
            <div><p class="text-sm" style="color: #6b7280;">Affiliate</p><p class="font-medium">Sample Affiliate</p></div>
            <div><p class="text-sm" style="color: #6b7280;">Amount</p><p class="font-medium">฿1,000</p></div>
        </div>
    </div>
</div>
@endsection
