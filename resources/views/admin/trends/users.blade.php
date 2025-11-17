@extends('layouts.admin-v3')
@section('title', 'User Trends')
@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="bg-gradient-to-r from-purple-600 to-purple-800 rounded-xl shadow-lg p-6 mb-6">
        <div class="flex items-center justify-between">
            <div><h1 class="text-3xl font-bold text-white">User Trends</h1><p class="text-purple-100 mt-1">User growth and engagement trends</p></div>
            <a href="{{ route('admin.dashboard') }}" class="glass-fusion text-purple-600 px-4 py-2 rounded-xl">Back</a>
        </div>
    </div>
    <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4 mb-6"><p class="text-sm text-yellow-700"><strong>Under Development:</strong> User trends analysis.</p></div>
    <div class="grid grid-cols-4 gap-6 mb-6">
        <div class="glass-fusion rounded-xl shadow p-6" hover:scale-105 transition-transform border border-white/20 dark:border-white/10><h3 class="text-sm text-gray-500 dark:text-gray-400 mb-2">Total Users</h3><p class="text-3xl font-bold">--</p></div>
        <div class="glass-fusion rounded-xl shadow p-6" hover:scale-105 transition-transform border border-white/20 dark:border-white/10><h3 class="text-sm text-gray-500 dark:text-gray-400 mb-2">New Users</h3><p class="text-3xl font-bold">--</p></div>
        <div class="glass-fusion rounded-xl shadow p-6" hover:scale-105 transition-transform border border-white/20 dark:border-white/10><h3 class="text-sm text-gray-500 dark:text-gray-400 mb-2">Active</h3><p class="text-3xl font-bold">--</p></div>
        <div class="glass-fusion rounded-xl shadow p-6" hover:scale-105 transition-transform border border-white/20 dark:border-white/10><h3 class="text-sm text-gray-500 dark:text-gray-400 mb-2">Retention</h3><p class="text-3xl font-bold">--%</p></div>
    </div>
    <div class="glass-fusion rounded-xl shadow p-6" hover:scale-105 transition-transform border border-white/20 dark:border-white/10><div class="h-64 flex items-center justify-center bg-gray-100/50 dark:bg-gray-800/50/50 dark:bg-gray-800/50 rounded"><p class="text-gray-400">Chart will be displayed here</p></div></div>
</div>
@endsection
