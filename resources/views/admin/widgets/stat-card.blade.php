<div class="bg-white rounded-lg shadow p-6">
    <div class="flex items-center justify-between">
        <div class="flex-1">
            <p class="text-sm text-gray-600 mb-1">{{ $title }}</p>
            <p class="text-2xl font-bold text-gray-900">{{ $value }}</p>

            @if(isset($change))
                <p class="text-sm {{ str_starts_with($change, '+') ? 'text-green-600' : 'text-red-600' }} mt-2">
                    {{ $change }} จากเดือนที่แล้ว
                </p>
            @endif

            @if(isset($badge))
                <span class="inline-block mt-2 px-2 py-1 text-xs font-medium bg-red-100 text-red-800 rounded">
                    {{ $badge }}
                </span>
            @endif
        </div>

        <div class="ml-4 flex-shrink-0">
            @php
                $iconClass = match($color ?? 'blue') {
                    'green' => 'bg-green-100 text-green-600',
                    'blue' => 'bg-blue-100 text-blue-600',
                    'purple' => 'bg-purple-100 text-purple-600',
                    'yellow' => 'bg-yellow-100 text-yellow-600',
                    'red' => 'bg-red-100 text-red-600',
                    default => 'bg-gray-100 text-gray-600'
                };
            @endphp
            <div class="{{ $iconClass }} p-3 rounded-full">
                @if($icon === 'money')
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                @elseif($icon === 'shopping-bag')
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                    </svg>
                @elseif($icon === 'users')
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                    </svg>
                @elseif($icon === 'chart')
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                    </svg>
                @endif
            </div>
        </div>
    </div>
</div>
