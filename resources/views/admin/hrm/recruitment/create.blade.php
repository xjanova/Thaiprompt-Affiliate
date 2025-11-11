@extends('layouts.admin')

@section('title', 'Create Job Posting')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="bg-gradient-to-r from-teal-600 to-teal-800 rounded-lg shadow-lg p-6 mb-6">
        <div class="flex items-center justify-between">
            <div class="flex items-center space-x-4">
                <div class="bg-white bg-opacity-20 p-3 rounded-lg">
                    <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                    </svg>
                </div>
                <div>
                    <h1 class="text-3xl font-bold text-white">Create Job Posting</h1>
                    <p class="text-teal-100 mt-1">Post a new job opening</p>
                </div>
            </div>
            <a href="{{ route('admin.hrm.recruitment.index') }}" class="bg-white text-teal-600 px-4 py-2 rounded-lg hover:bg-teal-50 transition">Back to List</a>
        </div>
    </div>
    <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4 mb-6">
        <p class="text-sm text-yellow-700"><strong>Under Development:</strong> Job posting creation form with requirements.</p>
    </div>
    <div class="bg-white rounded-lg shadow p-6">
        <form>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Position Title</label>
                    <input type="text" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500" placeholder="e.g., Software Engineer">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Department</label>
                    <select class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500">
                        <option>Select Department</option>
                    </select>
                </div>
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Job Description</label>
                    <textarea rows="4" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500"></textarea>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Employment Type</label>
                    <select class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500">
                        <option>Full-time</option><option>Part-time</option><option>Contract</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Salary Range</label>
                    <input type="text" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500" placeholder="e.g., 50,000 - 70,000">
                </div>
            </div>
            <div class="flex justify-end space-x-3 mt-6">
                <a href="{{ route('admin.hrm.recruitment.index') }}" class="px-6 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition">Cancel</a>
                <button type="submit" class="px-6 py-2 bg-teal-600 text-white rounded-lg hover:bg-teal-700 transition">Create Posting</button>
            </div>
        </form>
    </div>
</div>
@endsection
