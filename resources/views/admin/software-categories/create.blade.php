@extends('layouts.admin')
@section('title', 'Create Software Category')
@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="bg-gradient-to-r from-red-600 to-red-800 rounded-lg shadow-lg p-6 mb-6">
        <div class="flex items-center justify-between">
            <div><h1 class="text-3xl font-bold text-white">Create Software Category</h1></div>
            <a href="{{ route('admin.software-categories.index') }}" class="bg-white text-red-600 px-4 py-2 rounded-lg">Back</a>
        </div>
    </div>
    <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4 mb-6"><p class="text-sm text-yellow-700"><strong>Under Development:</strong> Category creation form.</p></div>
    <div class="bg-white rounded-lg shadow p-6">
        <form>
            <div class="grid grid-cols-2 gap-6">
                <div><label class="block text-sm font-medium text-gray-700 mb-2">Category Name</label><input type="text" class="w-full px-4 py-2 border rounded-lg"></div>
                <div><label class="block text-sm font-medium text-gray-700 mb-2">Slug</label><input type="text" class="w-full px-4 py-2 border rounded-lg"></div>
                <div class="col-span-2"><label class="block text-sm font-medium text-gray-700 mb-2">Description</label><textarea rows="3" class="w-full px-4 py-2 border rounded-lg"></textarea></div>
            </div>
            <div class="flex justify-end space-x-3 mt-6">
                <a href="{{ route('admin.software-categories.index') }}" class="px-6 py-2 border rounded-lg">Cancel</a>
                <button type="submit" class="px-6 py-2 bg-red-600 text-white rounded-lg">Create</button>
            </div>
        </form>
    </div>
</div>
@endsection
