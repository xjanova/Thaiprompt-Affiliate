@extends('layouts.admin-v3')
@section('title', 'Create Software Category')
@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="bg-gradient-to-r from-red-600 to-red-800 rounded-xl shadow-lg p-6 mb-6">
        <div class="flex items-center justify-between">
            <div><h1 class="text-3xl font-bold text-white">Create Software Category</h1></div>
            <a href="{{ route('admin.software-categories.index') }}" class="glass-fusion text-red-600 px-4 py-2 rounded-xl">Back</a>
        </div>
    </div>
    <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4 mb-6"><p class="text-sm text-yellow-700"><strong>Under Development:</strong> Category creation form.</p></div>
    <div class="glass-fusion rounded-xl shadow p-6" hover:scale-105 transition-transform border border-white/20 dark:border-white/10>
        <form>
            <div class="grid grid-cols-2 gap-6">
                <div><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Category Name</label><input type="text" class="w-full px-4 py-2 border rounded-xl"></div>
                <div><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Slug</label><input type="text" class="w-full px-4 py-2 border rounded-xl"></div>
                <div class="col-span-2"><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Description</label><textarea rows="3" class="w-full px-4 py-2 border rounded-xl"></textarea></div>
            </div>
            <div class="flex justify-end space-x-3 mt-6">
                <a href="{{ route('admin.software-categories.index') }}" class="px-6 py-2 border rounded-xl">Cancel</a>
                <button type="submit" class="px-6 py-2 bg-red-600 text-white rounded-xl">Create</button>
            </div>
        </form>
    </div>
</div>
@endsection
