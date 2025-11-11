@extends('layouts.admin')
@section('title', 'Create Quotation')
@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="bg-gradient-to-r from-yellow-600 to-yellow-800 rounded-lg shadow-lg p-6 mb-6">
        <div class="flex items-center justify-between">
            <div><h1 class="text-3xl font-bold text-white">Create Quotation</h1></div>
            <a href="{{ route('admin.quotations.index') }}" class="bg-white text-yellow-600 px-4 py-2 rounded-lg">Back</a>
        </div>
    </div>
    <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4 mb-6"><p class="text-sm text-yellow-700"><strong>Under Development:</strong> Quotation creation form.</p></div>
    <div class="bg-white rounded-lg shadow p-6">
        <form>
            <div class="grid grid-cols-2 gap-6">
                <div><label class="block text-sm font-medium text-gray-700 mb-2">Customer</label><input type="text" class="w-full px-4 py-2 border rounded-lg"></div>
                <div><label class="block text-sm font-medium text-gray-700 mb-2">Valid Until</label><input type="date" class="w-full px-4 py-2 border rounded-lg"></div>
            </div>
            <div class="flex justify-end space-x-3 mt-6">
                <a href="{{ route('admin.quotations.index') }}" class="px-6 py-2 border rounded-lg">Cancel</a>
                <button type="submit" class="px-6 py-2 bg-yellow-600 text-white rounded-lg">Create</button>
            </div>
        </form>
    </div>
</div>
@endsection
