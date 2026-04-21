<a href="{{ $href }}"
   class="block p-5 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl hover:shadow-lg hover:border-blue-400 transition">
    <div class="flex items-start justify-between mb-3">
        <div class="text-4xl">{{ $icon }}</div>
        <span class="text-xs font-medium px-2 py-1 bg-blue-100 dark:bg-blue-900/40 text-blue-700 dark:text-blue-300 rounded-full">
            {{ $stat }}
        </span>
    </div>
    <div class="font-bold text-gray-900 dark:text-white text-lg">{{ $title }}</div>
    <div class="text-sm text-gray-600 dark:text-gray-400 mt-1">{{ $subtitle }}</div>
</a>
