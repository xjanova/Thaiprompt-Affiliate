@props([
    'title' => '',
    'description' => '',
    'icon' => null,
    'collapsed' => false,
])

<div x-data="{ open: !{{ $collapsed ? 'true' : 'false' }} }" class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-gray-200 dark:border-slate-700 overflow-hidden transition-all duration-200 hover:shadow-md">
    <!-- Header -->
    <div @click="open = !open" class="flex items-center justify-between p-5 cursor-pointer hover:bg-gray-50 dark:hover:bg-slate-750 transition-colors">
        <div class="flex items-center gap-4 flex-1">
            @if($icon)
                <div class="flex-shrink-0 w-10 h-10 flex items-center justify-center rounded-xl bg-gradient-to-br from-orange-500 to-pink-500 text-white">
                    {!! $icon !!}
                </div>
            @endif
            <div class="flex-1">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">{{ $title }}</h3>
                @if($description)
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">{{ $description }}</p>
                @endif
            </div>
        </div>

        <!-- Toggle Icon -->
        <div class="ml-4">
            <svg class="w-5 h-5 text-gray-400 transition-transform duration-200"
                 :class="{'rotate-180': open}"
                 fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
            </svg>
        </div>
    </div>

    <!-- Content -->
    <div x-show="open"
         x-collapse
         class="border-t border-gray-100 dark:border-slate-700">
        <div class="p-6 space-y-5">
            {{ $slot }}
        </div>
    </div>
</div>
