@extends('layouts.admin')

@section('title', 'Edit Job Posting')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="bg-gradient-to-r from-teal-600 to-teal-800 rounded-lg shadow-lg p-6 mb-6">
        <div class="flex items-center justify-between">
            <div class="flex items-center space-x-4">
                <div class="bg-white bg-opacity-20 p-3 rounded-lg">
                    <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                    </svg>
                </div>
                <div>
                    <h1 class="text-3xl font-bold text-white">Edit Job Posting</h1>
                    <p class="text-teal-100 mt-1">Update job posting details</p>
                </div>
            </div>
            <a href="{{ route('admin.hrm.recruitment.index') }}" class="bg-white text-teal-600 px-4 py-2 rounded-lg hover:bg-teal-50 transition">Back to List</a>
        </div>
    </div>
    <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4 mb-6">
        <p class="text-sm text-yellow-700"><strong>Under Development:</strong> Edit job posting functionality.</p>
    </div>
    <div class="bg-white rounded-lg shadow p-6">
        <form>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Status</label>
                    <select class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500">
                        <option selected>Open</option><option>Closed</option><option>On Hold</option>
                    </select>
                </div>
            </div>
            <div class="flex justify-end space-x-3 mt-6">
                <a href="{{ route('admin.hrm.recruitment.index') }}" class="px-6 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition">Cancel</a>
                <button type="submit" class="px-6 py-2 bg-teal-600 text-white rounded-lg hover:bg-teal-700 transition">Update Posting</button>
            </div>
        </form>
    </div>
</div>
@endsection
