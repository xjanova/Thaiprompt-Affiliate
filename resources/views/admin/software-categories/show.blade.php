@extends('layouts.admin-v3')
@section('title', 'Software Category Details')
@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="bg-gradient-to-r from-red-600 to-red-800 rounded-xl shadow-lg p-6 mb-6">
        <div class="flex items-center justify-between">
            <div><h1 class="text-3xl font-bold text-white">Software Category Details</h1></div>
            <div class="flex space-x-3">
                <a href="#" class="glass-fusion text-red-600 px-4 py-2 rounded-xl">Edit</a>
                <a href="{{ route('admin.software-categories.index') }}" class="bg-red-500 text-white px-4 py-2 rounded-xl">Back</a>
            </div>
        </div>
    </div>
    <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4 mb-6"><p class="text-sm text-yellow-700"><strong>Under Development:</strong> Category details view.</p></div>
    <div class="grid grid-cols-3 gap-6">
        <div class="col-span-2 glass-fusion rounded-xl shadow p-6" hover:scale-105 transition-transform border border-white/20 dark:border-white/10>
            <h2 class="text-xl font-semibold mb-4">Category Information</h2>
            <div class="space-y-3">
                <div><p class="text-sm text-gray-500 dark:text-gray-400">Category Name</p><p class="font-medium">Sample Category</p></div>
                <div><p class="text-sm text-gray-500 dark:text-gray-400">Slug</p><p class="font-medium">sample-category</p></div>
                <div><p class="text-sm text-gray-500 dark:text-gray-400">Description</p><p class="font-medium">Category description</p></div>
            </div>
        </div>
        <div class="glass-fusion rounded-xl shadow p-6" hover:scale-105 transition-transform border border-white/20 dark:border-white/10>
            <h3 class="text-lg font-semibold mb-4">Statistics</h3>
            <div class="space-y-3">
                <div class="flex justify-between"><span class="text-gray-600 dark:text-gray-400">Products</span><span class="font-bold">--</span></div>
                <div class="flex justify-between"><span class="text-gray-600 dark:text-gray-400">Status</span><span class="px-2 py-1 bg-green-100 text-green-800 rounded text-sm">Active</span></div>
            </div>
        </div>
    </div>
</div>
@endsection
