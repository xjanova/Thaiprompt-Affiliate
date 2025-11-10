@extends('layouts.admin')

@section('title', 'จัดการ Emergency Alert Banner')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">จัดการ Emergency Alert Banner</h1>
            <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">ระบบแจ้งเตือนฉุกเฉินแบบลอย สำหรับแจ้งเตือนสำคัญให้ทุกคนเห็น</p>
        </div>
        <a href="{{ route('admin.app-banners.create') }}"
           class="px-4 py-2 bg-gradient-to-r from-red-600 to-orange-600 text-white rounded-lg hover:from-red-700 hover:to-orange-700 shadow-md hover:shadow-lg transition-all">
            <i class="fas fa-plus mr-2"></i> สร้าง Emergency Alert ใหม่
        </a>
    </div>

    <!-- Success Message -->
    @if(session('success'))
        <div class="bg-green-100 dark:bg-green-900 border border-green-400 dark:border-green-700 text-green-700 dark:text-green-200 px-4 py-3 rounded-lg">
            {{ session('success') }}
        </div>
    @endif

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div class="bg-white dark:bg-slate-800 rounded-lg shadow-md p-4">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600 dark:text-gray-400">Total Banners</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $banners->flatten()->count() }}</p>
                </div>
                <div class="p-3 bg-blue-100 dark:bg-blue-900 rounded-full">
                    <i class="fas fa-flag text-blue-600 dark:text-blue-300"></i>
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-slate-800 rounded-lg shadow-md p-4">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600 dark:text-gray-400">Active Banners</p>
                    <p class="text-2xl font-bold text-green-600 dark:text-green-400">{{ $banners->flatten()->where('is_active', true)->count() }}</p>
                </div>
                <div class="p-3 bg-green-100 dark:bg-green-900 rounded-full">
                    <i class="fas fa-check-circle text-green-600 dark:text-green-300"></i>
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-slate-800 rounded-lg shadow-md p-4">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600 dark:text-gray-400">Total Views</p>
                    <p class="text-2xl font-bold text-purple-600 dark:text-purple-400">{{ number_format($banners->flatten()->sum('view_count')) }}</p>
                </div>
                <div class="p-3 bg-purple-100 dark:bg-purple-900 rounded-full">
                    <i class="fas fa-eye text-purple-600 dark:text-purple-300"></i>
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-slate-800 rounded-lg shadow-md p-4">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600 dark:text-gray-400">Total Clicks</p>
                    <p class="text-2xl font-bold text-orange-600 dark:text-orange-400">{{ number_format($banners->flatten()->sum('click_count')) }}</p>
                </div>
                <div class="p-3 bg-orange-100 dark:bg-orange-900 rounded-full">
                    <i class="fas fa-mouse-pointer text-orange-600 dark:text-orange-300"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Banners by Position -->
    @forelse($banners as $position => $positionBanners)
        <div class="bg-white dark:bg-slate-800 rounded-lg shadow-md overflow-hidden">
            <div class="bg-gradient-to-r from-indigo-600 to-purple-600 px-6 py-4">
                <h2 class="text-lg font-bold text-white">
                    <i class="fas fa-map-marker-alt mr-2"></i>
                    ตำแหน่ง: {{ ucfirst($position) }}
                    <span class="ml-2 px-2 py-1 text-xs rounded-full bg-white text-indigo-600">{{ $positionBanners->count() }} banners</span>
                </h2>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-slate-700">
                    <thead class="bg-gray-50 dark:bg-slate-700">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Preview</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Title</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Type</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Display</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Schedule</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Stats</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Status</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-slate-800 divide-y divide-gray-200 dark:divide-slate-700">
                        @foreach($positionBanners as $banner)
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center gap-2">
                                        @if($banner->icon)
                                            <span class="text-2xl">{{ $banner->icon }}</span>
                                        @endif
                                        @if($banner->image_url)
                                            <img src="{{ asset($banner->image_url) }}" alt="{{ $banner->title }}" class="w-12 h-12 object-cover rounded">
                                        @endif
                                    </div>
                                </td>

                                <td class="px-6 py-4">
                                    <div class="text-sm font-medium text-gray-900 dark:text-white">{{ $banner->title }}</div>
                                    @if($banner->description)
                                        <div class="text-xs text-gray-500 dark:text-gray-400">{{ Str::limit($banner->description, 50) }}</div>
                                    @endif
                                </td>

                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-2 py-1 text-xs rounded-full
                                        @if($banner->type === 'info') bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200
                                        @elseif($banner->type === 'warning') bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200
                                        @elseif($banner->type === 'success') bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200
                                        @elseif($banner->type === 'promotion') bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200
                                        @else bg-indigo-100 text-indigo-800 dark:bg-indigo-900 dark:text-indigo-200
                                        @endif">
                                        {{ ucfirst($banner->type) }}
                                    </span>
                                </td>

                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-xs">
                                        <div class="font-medium text-gray-900 dark:text-white">{{ ucfirst(str_replace('_', ' + ', $banner->display_type)) }}</div>
                                        <div class="text-gray-500 dark:text-gray-400">@ {{ $banner->display_position }}</div>
                                        <div class="text-gray-500 dark:text-gray-400">Size: {{ $banner->size }}</div>
                                    </div>
                                </td>

                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-xs">
                                        @if($banner->start_date)
                                            <div class="text-gray-600 dark:text-gray-400">
                                                <i class="fas fa-calendar-alt mr-1"></i>
                                                {{ $banner->start_date->format('d/m/Y H:i') }}
                                            </div>
                                        @endif
                                        @if($banner->end_date)
                                            <div class="text-gray-600 dark:text-gray-400">
                                                <i class="fas fa-calendar-check mr-1"></i>
                                                {{ $banner->end_date->format('d/m/Y H:i') }}
                                            </div>
                                        @else
                                            <div class="text-gray-500 dark:text-gray-400">No end date</div>
                                        @endif
                                    </div>
                                </td>

                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-xs">
                                        <div class="text-gray-600 dark:text-gray-400">
                                            <i class="fas fa-eye mr-1"></i>
                                            {{ number_format($banner->view_count) }} views
                                        </div>
                                        <div class="text-gray-600 dark:text-gray-400">
                                            <i class="fas fa-mouse-pointer mr-1"></i>
                                            {{ number_format($banner->click_count) }} clicks
                                        </div>
                                    </div>
                                </td>

                                <td class="px-6 py-4 whitespace-nowrap">
                                    <button
                                        onclick="toggleBanner({{ $banner->id }}, {{ $banner->is_active ? 'true' : 'false' }})"
                                        class="relative inline-flex h-6 w-11 items-center rounded-full transition-colors focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2
                                        {{ $banner->is_active ? 'bg-green-600' : 'bg-gray-200 dark:bg-gray-700' }}"
                                        id="toggle-{{ $banner->id }}">
                                        <span class="inline-block h-4 w-4 transform rounded-full bg-white transition-transform {{ $banner->is_active ? 'translate-x-6' : 'translate-x-1' }}" id="toggle-button-{{ $banner->id }}"></span>
                                    </button>
                                </td>

                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    <div class="flex items-center justify-end gap-2">
                                        <a href="{{ route('admin.app-banners.edit', $banner) }}"
                                           class="text-indigo-600 hover:text-indigo-900 dark:text-indigo-400 dark:hover:text-indigo-300">
                                            <i class="fas fa-edit"></i>
                                        </a>

                                        <form action="{{ route('admin.app-banners.destroy', $banner) }}" method="POST" class="inline"
                                              onsubmit="return confirm('Are you sure you want to delete this banner?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-red-600 hover:text-red-900 dark:text-red-400 dark:hover:text-red-300">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @empty
        <div class="bg-white dark:bg-slate-800 rounded-lg shadow-md p-12 text-center">
            <i class="fas fa-inbox text-6xl text-gray-300 dark:text-gray-600 mb-4"></i>
            <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">No Emergency Alerts Yet</h3>
            <p class="text-gray-500 dark:text-gray-400 mb-4">Create your first emergency alert banner to notify your users.</p>
            <a href="{{ route('admin.app-banners.create') }}"
               class="inline-block px-6 py-3 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition-colors">
                <i class="fas fa-plus mr-2"></i> Create Emergency Alert
            </a>
        </div>
    @endforelse
</div>

<script>
function toggleBanner(bannerId, currentStatus) {
    const toggleButton = document.getElementById(`toggle-${bannerId}`);
    const toggleIndicator = document.getElementById(`toggle-button-${bannerId}`);

    fetch(`/admin/app-banners/${bannerId}/toggle`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            if (data.is_active) {
                toggleButton.classList.remove('bg-gray-200', 'dark:bg-gray-700');
                toggleButton.classList.add('bg-green-600');
                toggleIndicator.classList.remove('translate-x-1');
                toggleIndicator.classList.add('translate-x-6');
            } else {
                toggleButton.classList.remove('bg-green-600');
                toggleButton.classList.add('bg-gray-200', 'dark:bg-gray-700');
                toggleIndicator.classList.remove('translate-x-6');
                toggleIndicator.classList.add('translate-x-1');
            }
        }
    })
    .catch(error => console.error('Error:', error));
}
</script>
@endsection
