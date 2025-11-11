@extends('layouts.admin')
@section('title', 'SEO Management')
@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="bg-gradient-to-r from-indigo-600 to-indigo-800 rounded-lg shadow-lg p-6 mb-6">
        <div class="flex items-center justify-between">
            <div><h1 class="text-3xl font-bold text-white">SEO Management</h1><p class="text-indigo-100 mt-1">Search engine optimization tools</p></div>
            <a href="{{ route('admin.seo.settings') }}" class="bg-white text-indigo-600 px-4 py-2 rounded-lg">Settings</a>
        </div>
    </div>
    <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4 mb-6"><p class="text-sm text-yellow-700"><strong>Under Development:</strong> SEO management and optimization tools.</p></div>
    <div class="grid grid-cols-3 gap-6">
        <div class="bg-white rounded-lg shadow p-6"><h3 class="text-lg font-semibold mb-2">Meta Tags</h3><p class="text-sm text-gray-600">Manage page meta tags</p></div>
        <div class="bg-white rounded-lg shadow p-6"><h3 class="text-lg font-semibold mb-2">Sitemaps</h3><p class="text-sm text-gray-600">Generate and manage sitemaps</p></div>
        <div class="bg-white rounded-lg shadow p-6"><h3 class="text-lg font-semibold mb-2">Robots.txt</h3><p class="text-sm text-gray-600">Configure robots.txt</p></div>
    </div>
</div>
@endsection
