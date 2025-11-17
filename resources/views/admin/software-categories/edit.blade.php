@extends('layouts.admin-v3')
@section('title', 'Edit Software Category')
@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="bg-gradient-to-r from-red-600 to-red-800 rounded-xl shadow-lg p-6 mb-6">
        <div class="flex items-center justify-between">
            <div><h1 class="text-3xl font-bold text-white">Edit Software Category</h1></div>
            <a href="{{ route('admin.software-categories.index') }}" class="glass-fusion text-red-600 px-4 py-2 rounded-xl">Back</a>
        </div>
    </div>
    <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4 mb-6"><p class="text-sm text-yellow-700"><strong>Under Development:</strong> Edit category functionality.</p></div>
    <div class="glass-fusion rounded-xl shadow p-6" hover:scale-105 transition-transform border border-white/20 dark:border-white/10>
        <form>
            <div class="grid grid-cols-2 gap-6">
                <div><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Category Name</label><input type="text" value="Sample Category" class="w-full px-4 py-2 border rounded-xl"></div>
                <div><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Status</label><select class="w-full px-4 py-2 border rounded-xl"><option>Active</option><option>Inactive</option></select></div>
            </div>
            <div class="flex justify-end space-x-3 mt-6">
                <a href="{{ route('admin.software-categories.index') }}" class="px-6 py-2 border rounded-xl">Cancel</a>
                <button type="submit" class="px-6 py-2 bg-red-600 text-white rounded-xl">Update</button>
            </div>
        </form>
    </div>
</div>
@endsection
