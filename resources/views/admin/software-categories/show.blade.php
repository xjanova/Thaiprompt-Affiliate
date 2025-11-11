@extends('layouts.admin')
@section('title', 'Software Category Details')
@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="bg-gradient-to-r from-red-600 to-red-800 rounded-lg shadow-lg p-6 mb-6">
        <div class="flex items-center justify-between">
            <div><h1 class="text-3xl font-bold text-white">Software Category Details</h1></div>
            <div class="flex space-x-3">
                <a href="#" class="bg-white text-red-600 px-4 py-2 rounded-lg">Edit</a>
                <a href="{{ route('admin.software-categories.index') }}" class="bg-red-500 text-white px-4 py-2 rounded-lg">Back</a>
            </div>
        </div>
    </div>
    <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4 mb-6"><p class="text-sm text-yellow-700"><strong>Under Development:</strong> Category details view.</p></div>
    <div class="grid grid-cols-3 gap-6">
        <div class="col-span-2 bg-white rounded-lg shadow p-6">
            <h2 class="text-xl font-semibold mb-4">Category Information</h2>
            <div class="space-y-3">
                <div><p class="text-sm text-gray-500">Category Name</p><p class="font-medium">Sample Category</p></div>
                <div><p class="text-sm text-gray-500">Slug</p><p class="font-medium">sample-category</p></div>
                <div><p class="text-sm text-gray-500">Description</p><p class="font-medium">Category description</p></div>
            </div>
        </div>
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-semibold mb-4">Statistics</h3>
            <div class="space-y-3">
                <div class="flex justify-between"><span class="text-gray-600">Products</span><span class="font-bold">--</span></div>
                <div class="flex justify-between"><span class="text-gray-600">Status</span><span class="px-2 py-1 bg-green-100 text-green-800 rounded text-sm">Active</span></div>
            </div>
        </div>
    </div>
</div>
@endsection
