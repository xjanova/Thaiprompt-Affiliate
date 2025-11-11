@extends('layouts.admin')
@section('title', 'Email Log Details')
@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="bg-gradient-to-r from-cyan-600 to-cyan-800 rounded-lg shadow-lg p-6 mb-6">
        <div class="flex items-center justify-between">
            <div><h1 class="text-3xl font-bold text-white">Email Log Details</h1></div>
            <a href="{{ route('admin.email-logs.index') }}" class="bg-white text-cyan-600 px-4 py-2 rounded-lg">Back</a>
        </div>
    </div>
    <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4 mb-6"><p class="text-sm text-yellow-700"><strong>Under Development:</strong> Email log details viewer.</p></div>
    <div class="bg-white rounded-lg shadow p-6">
        <div class="space-y-4">
            <div><p class="text-sm text-gray-500">Recipient</p><p class="font-medium">sample@email.com</p></div>
            <div><p class="text-sm text-gray-500">Subject</p><p class="font-medium">Sample Email Subject</p></div>
            <div><p class="text-sm text-gray-500">Status</p><span class="px-2 py-1 bg-green-100 text-green-800 rounded text-sm">Sent</span></div>
            <div><p class="text-sm text-gray-500">Message</p><div class="mt-2 p-4 bg-gray-50 rounded">Email content will appear here</div></div>
        </div>
    </div>
</div>
@endsection
