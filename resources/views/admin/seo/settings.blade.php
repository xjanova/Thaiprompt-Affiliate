@extends('layouts.admin')
@section('title', 'SEO Settings')
@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="bg-gradient-to-r from-indigo-600 to-indigo-800 rounded-lg shadow-lg p-6 mb-6">
        <div class="flex items-center justify-between">
            <div><h1 class="text-3xl font-bold text-white">SEO Settings</h1></div>
            <a href="{{ route('admin.seo.index') }}" class="bg-white text-indigo-600 px-4 py-2 rounded-lg">Back</a>
        </div>
    </div>
    <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4 mb-6"><p class="text-sm text-yellow-700"><strong>Under Development:</strong> SEO configuration settings.</p></div>
    <div class="bg-white rounded-lg shadow p-6">
        <form>
            <div class="space-y-4">
                <div><label class="block text-sm font-medium text-gray-700 mb-2">Site Title</label><input type="text" class="w-full px-4 py-2 border rounded-lg"></div>
                <div><label class="block text-sm font-medium text-gray-700 mb-2">Meta Description</label><textarea rows="3" class="w-full px-4 py-2 border rounded-lg"></textarea></div>
            </div>
            <div class="flex justify-end space-x-3 mt-6">
                <button type="submit" class="px-6 py-2 bg-indigo-600 text-white rounded-lg">Save Settings</button>
            </div>
        </form>
    </div>
</div>
@endsection
