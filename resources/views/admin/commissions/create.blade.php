@extends('layouts.admin-v3')
@section('title', 'Create Commission')
@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="rounded-lg shadow-lg p-6 mb-6" style="background: linear-gradient(135deg, #10b981 0%, #059669 100%)">
        <div class="flex items-center justify-between">
            <div><h1 class="text-3xl font-bold text-white">Create Commission</h1></div>
            <a href="{{ route('admin.commissions.index') }}" class="bg-white px-4 py-2 rounded-lg" style="color: var(--arrow-x-success)">Back</a>
        </div>
    </div>
    <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4 mb-6"><p class="text-sm text-yellow-700"><strong>Under Development:</strong> Commission creation form.</p></div>
    <div class="bg-white rounded-lg shadow p-6">
        <form>
            <div class="grid grid-cols-2 gap-6">
                <div><label class="block text-sm font-medium text-gray-700 mb-2">Affiliate</label><select class="w-full px-4 py-2 border rounded-lg"><option>Select Affiliate</option></select></div>
                <div><label class="block text-sm font-medium text-gray-700 mb-2">Amount</label><input type="number" class="w-full px-4 py-2 border rounded-lg"></div>
            </div>
            <div class="flex justify-end space-x-3 mt-6">
                <a href="{{ route('admin.commissions.index') }}" class="px-6 py-2 border rounded-lg">Cancel</a>
                <button type="submit" class="px-6 py-2 text-white rounded-lg" style="background-color: var(--arrow-x-success)">Create</button>
            </div>
        </form>
    </div>
</div>
@endsection
