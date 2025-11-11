@extends('layouts.admin')

@section('title', 'Recruitment Management')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="bg-gradient-to-r from-teal-600 to-teal-800 rounded-lg shadow-lg p-6 mb-6">
        <div class="flex items-center justify-between">
            <div class="flex items-center space-x-4">
                <div class="bg-white bg-opacity-20 p-3 rounded-lg">
                    <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                    </svg>
                </div>
                <div>
                    <h1 class="text-3xl font-bold text-white">Recruitment Management</h1>
                    <p class="text-teal-100 mt-1">Manage job postings and candidate applications</p>
                </div>
            </div>
            <div class="flex space-x-3">
                <a href="{{ route('admin.hrm.recruitment.create') }}" class="bg-white text-teal-600 px-4 py-2 rounded-lg hover:bg-teal-50 transition">New Position</a>
                <a href="{{ route('admin.dashboard') }}" class="bg-teal-500 text-white px-4 py-2 rounded-lg hover:bg-teal-600 transition">Back</a>
            </div>
        </div>
    </div>
    <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4 mb-6">
        <p class="text-sm text-yellow-700"><strong>Under Development:</strong> Recruitment management system with applicant tracking.</p>
    </div>
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Position</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Department</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Applications</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Posted Date</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <tr>
                    <td colspan="6" class="px-6 py-8 text-center text-gray-500">
                        <p class="text-lg font-medium">No job postings found</p>
                        <p class="text-sm mt-1">Create your first job posting</p>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</div>
@endsection
