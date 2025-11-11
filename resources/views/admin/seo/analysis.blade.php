@extends('layouts.admin')
@section('title', 'SEO Analysis')
@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="bg-gradient-to-r from-indigo-600 to-indigo-800 rounded-lg shadow-lg p-6 mb-6">
        <div class="flex items-center justify-between">
            <div><h1 class="text-3xl font-bold text-white">SEO Analysis</h1><p class="text-indigo-100 mt-1">Analyze SEO performance</p></div>
            <a href="{{ route('admin.seo.index') }}" class="bg-white text-indigo-600 px-4 py-2 rounded-lg">Back</a>
        </div>
    </div>
    <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4 mb-6"><p class="text-sm text-yellow-700"><strong>Under Development:</strong> SEO performance analysis.</p></div>
    <div class="grid grid-cols-4 gap-6">
        <div class="bg-white rounded-lg shadow p-6"><h3 class="text-sm text-gray-500 mb-2">SEO Score</h3><p class="text-3xl font-bold">--/100</p></div>
        <div class="bg-white rounded-lg shadow p-6"><h3 class="text-sm text-gray-500 mb-2">Indexed Pages</h3><p class="text-3xl font-bold">--</p></div>
        <div class="bg-white rounded-lg shadow p-6"><h3 class="text-sm text-gray-500 mb-2">Backlinks</h3><p class="text-3xl font-bold">--</p></div>
        <div class="bg-white rounded-lg shadow p-6"><h3 class="text-sm text-gray-500 mb-2">Keywords</h3><p class="text-3xl font-bold">--</p></div>
    </div>
</div>
@endsection
