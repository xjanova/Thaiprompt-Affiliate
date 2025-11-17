@extends('layouts.admin-v3')

@section('title', 'Ticket Ratings')

@section('content')
<div class="container mx-auto px-4 py-8 max-w-7xl">
    <!-- Modern Gradient Header -->
    <div class="bg-gradient-to-br from-yellow-500 via-amber-500 to-orange-500 rounded-2xl shadow-2xl p-8 text-white mb-8">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-3xl font-bold mb-2 flex items-center">
                    <i class="fas fa-star mr-3"></i>
                    Ticket Ratings & Feedback
                </h2>
                <p class="text-yellow-100 text-lg">
                    ติดตามความพึงพอใจและ Feedback จากผู้ใช้งาน
                </p>
            </div>
            <div>
                <a href="{{ route('admin.tickets.index') }}"
                   class="inline-flex items-center px-6 py-3 glass-fusion hover:glass-fusion backdrop-blur-sm rounded-xl font-semibold transition-all duration-200 hover:scale-105">
                    <i class="fas fa-arrow-left mr-2"></i>
                    กลับหน้าหลัก
                </a>
            </div>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <!-- Average Rating Card -->
        <div class="glass-fusion dark:bg-gray-800 rounded-xl shadow-lg p-6 border-l-4 border-yellow-500 hover:shadow-xl transition-all duration-200 hover:-translate-y-1" hover:scale-105 transition-transform border border-white/20 dark:border-white/10>
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-500 dark:text-gray-400 dark:text-gray-400 text-sm font-medium mb-1">Average Rating</p>
                    <div class="flex items-center">
                        <p class="text-3xl font-bold text-gray-900 dark:text-white dark:text-white mr-2">{{ number_format($stats['average_rating'] ?? 0, 1) }}</p>
                        <span class="text-yellow-500 text-2xl">
                            @for($i = 1; $i <= 5; $i++)
                                <i class="fas fa-star{{ $i <= round($stats['average_rating'] ?? 0) ? '' : ' text-gray-300' }}"></i>
                            @endfor
                        </span>
                    </div>
                    <p class="text-xs text-gray-500 dark:text-gray-400 dark:text-gray-400 mt-1">Out of 5.0</p>
                </div>
                <div class="bg-yellow-100 dark:bg-yellow-900/30 p-4 rounded-xl">
                    <i class="fas fa-star text-2xl text-yellow-600 dark:text-yellow-400"></i>
                </div>
            </div>
        </div>

        <!-- Total Ratings Card -->
        <div class="glass-fusion dark:bg-gray-800 rounded-xl shadow-lg p-6 border-l-4 border-cyan-500 hover:shadow-xl transition-all duration-200 hover:-translate-y-1" hover:scale-105 transition-transform border border-white/20 dark:border-white/10>
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-500 dark:text-gray-400 dark:text-gray-400 text-sm font-medium mb-1">Total Ratings</p>
                    <p class="text-3xl font-bold text-gray-900 dark:text-white dark:text-white">{{ number_format($stats['total_ratings'] ?? 0) }}</p>
                    <p class="text-xs text-gray-500 dark:text-gray-400 dark:text-gray-400 mt-1">Total feedback received</p>
                </div>
                <div class="bg-cyan-100 dark:bg-cyan-900/30 p-4 rounded-xl">
                    <i class="fas fa-comments text-2xl text-cyan-600 dark:text-cyan-400"></i>
                </div>
            </div>
        </div>

        <!-- Response Speed Card -->
        <div class="glass-fusion dark:bg-gray-800 rounded-xl shadow-lg p-6 border-l-4 border-green-500 hover:shadow-xl transition-all duration-200 hover:-translate-y-1" hover:scale-105 transition-transform border border-white/20 dark:border-white/10>
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-500 dark:text-gray-400 dark:text-gray-400 text-sm font-medium mb-1">Response Speed</p>
                    <p class="text-3xl font-bold text-gray-900 dark:text-white dark:text-white">{{ number_format($stats['average_response_speed'] ?? 0, 1) }}</p>
                    <div class="w-full bg-gray-200 dark:bg-gray-700 dark:bg-gray-700 rounded-full h-2 mt-2">
                        <div class="bg-gradient-to-r from-green-400 to-emerald-500 h-2 rounded-full" style="width: {{ (($stats['average_response_speed'] ?? 0) / 5) * 100 }}%"></div>
                    </div>
                </div>
                <div class="bg-green-100 dark:bg-green-900/30 p-4 rounded-xl">
                    <i class="fas fa-tachometer-alt text-2xl text-green-600 dark:text-green-400"></i>
                </div>
            </div>
        </div>

        <!-- Solution Quality Card -->
        <div class="glass-fusion dark:bg-gray-800 rounded-xl shadow-lg p-6 border-l-4 border-blue-500 hover:shadow-xl transition-all duration-200 hover:-translate-y-1" hover:scale-105 transition-transform border border-white/20 dark:border-white/10>
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-500 dark:text-gray-400 dark:text-gray-400 text-sm font-medium mb-1">Solution Quality</p>
                    <p class="text-3xl font-bold text-gray-900 dark:text-white dark:text-white">{{ number_format($stats['average_solution_quality'] ?? 0, 1) }}</p>
                    <div class="w-full bg-gray-200 dark:bg-gray-700 dark:bg-gray-700 rounded-full h-2 mt-2">
                        <div class="bg-gradient-to-r from-blue-400 to-indigo-500 h-2 rounded-full" style="width: {{ (($stats['average_solution_quality'] ?? 0) / 5) * 100 }}%"></div>
                    </div>
                </div>
                <div class="bg-blue-100 dark:bg-blue-900/30 p-4 rounded-xl">
                    <i class="fas fa-check-circle text-2xl text-blue-600 dark:text-blue-400"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Ratings Table -->
    <div class="glass-fusion dark:bg-gray-800 rounded-xl shadow-lg overflow-hidden" border border-white/20 dark:border-white/10>
        <div class="bg-gradient-to-r from-yellow-500 to-amber-500 px-6 py-4">
            <h3 class="text-xl font-bold text-white flex items-center">
                <i class="fas fa-list mr-2"></i>
                Recent Ratings
            </h3>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gradient-to-r from-gray-50 to-gray-100 dark:from-gray-700 dark:to-gray-600">
                    <tr>
                        <th class="px-6 py-4 text-left text-xs font-bold text-gray-700 dark:text-gray-300 dark:text-gray-200 uppercase tracking-wider">
                            Ticket
                        </th>
                        <th class="px-6 py-4 text-left text-xs font-bold text-gray-700 dark:text-gray-300 dark:text-gray-200 uppercase tracking-wider">
                            User
                        </th>
                        <th class="px-6 py-4 text-left text-xs font-bold text-gray-700 dark:text-gray-300 dark:text-gray-200 uppercase tracking-wider">
                            Overall Rating
                        </th>
                        <th class="px-6 py-4 text-left text-xs font-bold text-gray-700 dark:text-gray-300 dark:text-gray-200 uppercase tracking-wider">
                            Response Speed
                        </th>
                        <th class="px-6 py-4 text-left text-xs font-bold text-gray-700 dark:text-gray-300 dark:text-gray-200 uppercase tracking-wider">
                            Solution Quality
                        </th>
                        <th class="px-6 py-4 text-left text-xs font-bold text-gray-700 dark:text-gray-300 dark:text-gray-200 uppercase tracking-wider">
                            Staff Friendliness
                        </th>
                        <th class="px-6 py-4 text-left text-xs font-bold text-gray-700 dark:text-gray-300 dark:text-gray-200 uppercase tracking-wider">
                            Feedback
                        </th>
                        <th class="px-6 py-4 text-left text-xs font-bold text-gray-700 dark:text-gray-300 dark:text-gray-200 uppercase tracking-wider">
                            Date
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse($ratings as $rating)
                        <tr class="hover:bg-gray-100/50 dark:bg-gray-800/50/50 dark:bg-gray-800/50 dark:hover:bg-gray-700/50 transition-colors duration-150">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <a href="{{ route('admin.tickets.show', $rating->ticket_id) }}"
                                   class="inline-flex items-center text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300 font-semibold">
                                    <i class="fas fa-ticket-alt mr-2"></i>
                                    {{ $rating->ticket->ticket_number ?? 'N/A' }}
                                </a>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center">
                                    <div class="h-8 w-8 rounded-full bg-gradient-to-br from-purple-400 to-pink-600 flex items-center justify-center text-white font-semibold mr-3">
                                        {{ substr($rating->user->name ?? 'N', 0, 1) }}
                                    </div>
                                    <div class="text-sm font-medium text-gray-900 dark:text-white">
                                        {{ $rating->user->name ?? 'N/A' }}
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center">
                                    <div class="text-yellow-500 text-lg mr-2">
                                        @for($i = 1; $i <= 5; $i++)
                                            <i class="fas fa-star{{ $i <= $rating->rating ? '' : ' text-gray-300 dark:text-gray-600 dark:text-gray-400' }}"></i>
                                        @endfor
                                    </div>
                                    <span class="text-sm font-semibold text-gray-700 dark:text-gray-300 dark:text-gray-300">{{ $rating->rating }}/5</span>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300">
                                    {{ $rating->response_speed ?? '-' }}/5
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300">
                                    {{ $rating->solution_quality ?? '-' }}/5
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-purple-100 text-purple-800 dark:bg-purple-900/30 dark:text-purple-300">
                                    {{ $rating->staff_friendliness ?? '-' }}/5
                                </span>
                            </td>
                            <td class="px-6 py-4">
                                @if($rating->feedback)
                                    <div class="text-sm text-gray-700 dark:text-gray-300 dark:text-gray-300">
                                        {{ Str::limit($rating->feedback, 50) }}
                                    </div>
                                @else
                                    <span class="text-sm text-gray-400 dark:text-gray-500 dark:text-gray-400 italic">No feedback</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400 dark:text-gray-400">
                                <div class="flex items-center">
                                    <i class="fas fa-calendar mr-2 text-gray-400"></i>
                                    <div>
                                        <div>{{ $rating->created_at->format('d M Y') }}</div>
                                        <div class="text-xs">{{ $rating->created_at->format('H:i') }}</div>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-6 py-12 text-center">
                                <div class="flex flex-col items-center justify-center text-gray-400 dark:text-gray-500 dark:text-gray-400">
                                    <i class="fas fa-star text-6xl mb-4"></i>
                                    <p class="text-lg font-medium">No ratings yet</p>
                                    <p class="text-sm mt-2">Ratings will appear here once customers provide feedback</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        @if($ratings->hasPages())
            <div class="px-6 py-4 bg-gray-100/50 dark:bg-gray-800/50/50 dark:bg-gray-800/50 dark:bg-gray-900 border-t border-gray-200 dark:border-gray-700 dark:border-gray-700">
                {{ $ratings->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
