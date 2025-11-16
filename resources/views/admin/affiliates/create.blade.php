@extends('layouts.admin-v3')

@section('title', 'Create Affiliate')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="rounded-lg shadow-lg p-6 mb-6" style="background: var(--arrow-x-primary-gradient)">
        <div class="flex items-center justify-between">
            <div class="flex items-center space-x-4">
                <div class="bg-white bg-opacity-20 p-3 rounded-lg">
                    <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                    </svg>
                </div>
                <div>
                    <h1 class="text-3xl font-bold text-white">Create New Affiliate</h1>
                    <p class="text-white/80 mt-1">Add a new affiliate member to the system</p>
                </div>
            </div>
            <a href="{{ route('admin.affiliates.index') }}" class="px-4 py-2 rounded-lg transition" style="background-color: white; color: var(--arrow-x-primary-start)" onmouseover="this.style.backgroundColor='color-mix(in srgb, var(--arrow-x-primary-start) 10%, white)'" onmouseout="this.style.backgroundColor='white'">Back to List</a>
        </div>
    </div>
    <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4 mb-6">
        <p class="text-sm text-yellow-700"><strong>Under Development:</strong> Affiliate creation form with sponsor assignment and commission settings.</p>
    </div>
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
        <form>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">First Name</label>
                    <input type="text" class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white rounded-lg focus:outline-none focus:ring-2 transition-all duration-200" style="--tw-ring-color: var(--arrow-x-primary-start)" onfocus="this.style.borderColor='var(--arrow-x-primary-start)'" onblur="this.style.borderColor=''">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Last Name</label>
                    <input type="text" class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white rounded-lg focus:outline-none focus:ring-2 transition-all duration-200" style="--tw-ring-color: var(--arrow-x-primary-start)" onfocus="this.style.borderColor='var(--arrow-x-primary-start)'" onblur="this.style.borderColor=''">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Email</label>
                    <input type="email" class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white rounded-lg focus:outline-none focus:ring-2 transition-all duration-200" style="--tw-ring-color: var(--arrow-x-primary-start)" onfocus="this.style.borderColor='var(--arrow-x-primary-start)'" onblur="this.style.borderColor=''">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Phone</label>
                    <input type="tel" class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white rounded-lg focus:outline-none focus:ring-2 transition-all duration-200" style="--tw-ring-color: var(--arrow-x-primary-start)" onfocus="this.style.borderColor='var(--arrow-x-primary-start)'" onblur="this.style.borderColor=''">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Sponsor</label>
                    <select class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white rounded-lg focus:outline-none focus:ring-2 transition-all duration-200" style="--tw-ring-color: var(--arrow-x-primary-start)" onfocus="this.style.borderColor='var(--arrow-x-primary-start)'" onblur="this.style.borderColor=''">
                        <option>Select Sponsor</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Commission Level</label>
                    <select class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white rounded-lg focus:outline-none focus:ring-2 transition-all duration-200" style="--tw-ring-color: var(--arrow-x-primary-start)" onfocus="this.style.borderColor='var(--arrow-x-primary-start)'" onblur="this.style.borderColor=''">
                        <option>Bronze - 5%</option><option>Silver - 10%</option><option>Gold - 15%</option>
                    </select>
                </div>
            </div>
            <div class="flex justify-end space-x-3 mt-6">
                <a href="{{ route('admin.affiliates.index') }}" class="px-6 py-2 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600 transition">Cancel</a>
                <button type="submit" class="px-6 py-2 text-white rounded-lg transition shadow-md hover:shadow-lg transform hover:-translate-y-0.5" style="background: var(--arrow-x-primary-gradient)">Create Affiliate</button>
            </div>
        </form>
    </div>
</div>
@endsection
