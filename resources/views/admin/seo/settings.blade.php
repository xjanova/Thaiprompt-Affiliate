@extends('layouts.admin-v3')
@section('title', 'SEO Settings')
@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="bg-gradient-to-r from-indigo-600 to-indigo-800 rounded-xl shadow-lg p-6 mb-6">
        <div class="flex items-center justify-between">
            <div><h1 class="text-3xl font-bold text-white">SEO Settings</h1></div>
            <a href="{{ route('admin.seo.index') }}" class="glass-fusion text-indigo-600 px-4 py-2 rounded-xl">Back</a>
        </div>
    </div>
    <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4 mb-6"><p class="text-sm text-yellow-700"><strong>Under Development:</strong> SEO configuration settings.</p></div>
    <div class="glass-fusion rounded-xl shadow p-6" hover:scale-105 transition-transform border border-white/20 dark:border-white/10>
        <form>
            <div class="space-y-4">
                <div><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Site Title</label><input type="text" class="w-full px-4 py-2 border rounded-xl"></div>
                <div><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Meta Description</label><textarea rows="3" class="w-full px-4 py-2 border rounded-xl"></textarea></div>
            </div>
            <div class="flex justify-end space-x-3 mt-6">
                <button type="submit" class="px-6 py-2 bg-indigo-600 text-white rounded-xl">Save Settings</button>
            </div>
        </form>
    </div>
</div>
@endsection
