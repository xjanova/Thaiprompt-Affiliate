@extends('layouts.admin')

@section('title', 'Training Course Details')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="bg-gradient-to-r from-orange-600 to-orange-800 rounded-lg shadow-lg p-6 mb-6">
        <div class="flex items-center justify-between">
            <div class="flex items-center space-x-4">
                <div class="bg-white bg-opacity-20 p-3 rounded-lg">
                    <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                    </svg>
                </div>
                <div>
                    <h1 class="text-3xl font-bold text-white">Training Course Details</h1>
                    <p class="text-orange-100 mt-1">View course details and participants</p>
                </div>
            </div>
            <div class="flex space-x-3">
                <a href="#" class="bg-white text-orange-600 px-4 py-2 rounded-lg hover:bg-orange-50 transition">Edit</a>
                <a href="{{ route('admin.hrm.training-courses.index') }}" class="bg-orange-500 text-white px-4 py-2 rounded-lg hover:bg-orange-600 transition">Back</a>
            </div>
        </div>
    </div>
    <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4 mb-6">
        <p class="text-sm text-yellow-700"><strong>Under Development:</strong> Course details with participant list and progress tracking.</p>
    </div>
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 bg-white rounded-lg shadow p-6">
            <h2 class="text-xl font-semibold text-gray-900 mb-4">Course Information</h2>
            <div class="space-y-3">
                <div><p class="text-sm text-gray-500">Course Name</p><p class="font-medium text-gray-900">Sample Training Course</p></div>
                <div><p class="text-sm text-gray-500">Category</p><p class="font-medium text-gray-900">Technical Skills</p></div>
                <div><p class="text-sm text-gray-500">Duration</p><p class="font-medium text-gray-900">8 hours</p></div>
            </div>
        </div>
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Enrollment</h3>
            <div class="space-y-3">
                <div class="flex justify-between"><span class="text-gray-600">Enrolled</span><span class="font-bold text-gray-900">--</span></div>
                <div class="flex justify-between"><span class="text-gray-600">Completed</span><span class="font-bold text-green-600">--</span></div>
                <div class="flex justify-between"><span class="text-gray-600">Status</span><span class="px-2 py-1 bg-green-100 text-green-800 rounded text-sm font-medium">Active</span></div>
            </div>
        </div>
    </div>
</div>
@endsection
