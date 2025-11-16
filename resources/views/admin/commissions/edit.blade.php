@extends('layouts.admin-v3')
@section('title', 'Edit Commission')
@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="rounded-lg shadow-lg p-6 mb-6" style="background: linear-gradient(135deg, #16a34a 0%, #166534 100%)">
        <div class="flex items-center justify-between">
            <div><h1 class="text-3xl font-bold text-white">Edit Commission</h1></div>
            <a href="{{ route('admin.commissions.index') }}" class="bg-white px-4 py-2 rounded-lg" style="color: #16a34a">Back</a>
        </div>
    </div>
    <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4 mb-6"><p class="text-sm text-yellow-700"><strong>Under Development:</strong> Edit commission functionality.</p></div>
    <div class="bg-white dark:bg-white/15 backdrop-blur-xl rounded-lg shadow p-6">
        <form>
            <div class="grid grid-cols-2 gap-6">
                <div><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Status</label><select class="w-full px-4 py-2 border rounded-lg bg-white dark:bg-white/10 text-gray-900 dark:text-white"><option>Pending</option><option>Paid</option></select></div>
            </div>
            <div class="flex justify-end space-x-3 mt-6">
                <a href="{{ route('admin.commissions.index') }}" class="px-6 py-2 border rounded-lg bg-white dark:bg-slate-700 text-gray-700 dark:text-gray-300">Cancel</a>
                <button type="submit" class="px-6 py-2 text-white rounded-lg" style="background: linear-gradient(135deg, #16a34a 0%, #166534 100%)">Update</button>
            </div>
        </form>
    </div>
</div>
@endsection
