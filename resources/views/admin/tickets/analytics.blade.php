@extends('layouts.admin')

@section('title', 'Ticket Analytics')

@section('content')
<div class="container mx-auto px-4 py-8 max-w-7xl">
    <!-- Modern Gradient Header -->
    <div class="bg-gradient-to-br from-blue-600 via-cyan-600 to-sky-600 rounded-2xl shadow-2xl p-8 text-white mb-8">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-3xl font-bold mb-2 flex items-center">
                    <i class="fas fa-chart-line mr-3"></i>
                    Ticket Analytics
                </h2>
                <p class="text-blue-100 text-lg">
                    วิเคราะห์และติดตามประสิทธิภาพของระบบ Ticket
                </p>
            </div>
            <div>
                <a href="{{ route('admin.tickets.index') }}"
                   class="inline-flex items-center px-6 py-3 bg-white/20 hover:bg-white/30 backdrop-blur-sm rounded-xl font-semibold transition-all duration-200 hover:scale-105">
                    <i class="fas fa-arrow-left mr-2"></i>
                    กลับหน้าหลัก
                </a>
            </div>
        </div>
    </div>

    <!-- Date Range Filter -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6 mb-8">
        <form method="GET" action="{{ route('admin.tickets.analytics') }}" class="flex flex-wrap items-end gap-4">
            <div class="flex-1 min-w-[200px]">
                <label for="date_from" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                    <i class="fas fa-calendar mr-1"></i>
                    From Date
                </label>
                <input type="date" name="date_from" id="date_from"
                       value="{{ request('date_from', $dateFrom->format('Y-m-d')) }}"
                       class="w-full px-4 py-3 rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all">
            </div>
            <div class="flex-1 min-w-[200px]">
                <label for="date_to" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                    <i class="fas fa-calendar mr-1"></i>
                    To Date
                </label>
                <input type="date" name="date_to" id="date_to"
                       value="{{ request('date_to', $dateTo->format('Y-m-d')) }}"
                       class="w-full px-4 py-3 rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all">
            </div>
            <div>
                <button type="submit"
                        class="px-6 py-3 rounded-lg bg-gradient-to-r from-blue-600 to-cyan-600 text-white font-semibold hover:from-blue-700 hover:to-cyan-700 transition-all shadow-lg">
                    <i class="fas fa-filter mr-2"></i>
                    Apply Filter
                </button>
            </div>
        </form>
    </div>

    <!-- Statistics Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <!-- Total Tickets Card -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6 border-l-4 border-blue-500 hover:shadow-xl transition-all duration-200 hover:-translate-y-1">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-500 dark:text-gray-400 text-sm font-medium mb-1">Total Tickets</p>
                    <p class="text-3xl font-bold text-gray-800 dark:text-white">{{ number_format($analytics['total_tickets'] ?? 0) }}</p>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">In selected period</p>
                </div>
                <div class="bg-blue-100 dark:bg-blue-900/30 p-4 rounded-lg">
                    <i class="fas fa-ticket-alt text-2xl text-blue-600 dark:text-blue-400"></i>
                </div>
            </div>
        </div>

        <!-- Avg Response Time Card -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6 border-l-4 border-green-500 hover:shadow-xl transition-all duration-200 hover:-translate-y-1">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-500 dark:text-gray-400 text-sm font-medium mb-1">Avg Response Time</p>
                    <p class="text-3xl font-bold text-gray-800 dark:text-white">{{ number_format($analytics['avg_response_time'] ?? 0) }}</p>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Minutes</p>
                </div>
                <div class="bg-green-100 dark:bg-green-900/30 p-4 rounded-lg">
                    <i class="fas fa-hourglass-start text-2xl text-green-600 dark:text-green-400"></i>
                </div>
            </div>
        </div>

        <!-- Avg Resolution Time Card -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6 border-l-4 border-cyan-500 hover:shadow-xl transition-all duration-200 hover:-translate-y-1">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-500 dark:text-gray-400 text-sm font-medium mb-1">Avg Resolution Time</p>
                    <p class="text-3xl font-bold text-gray-800 dark:text-white">{{ number_format($analytics['avg_resolution_time'] ?? 0) }}</p>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Minutes</p>
                </div>
                <div class="bg-cyan-100 dark:bg-cyan-900/30 p-4 rounded-lg">
                    <i class="fas fa-hourglass-end text-2xl text-cyan-600 dark:text-cyan-400"></i>
                </div>
            </div>
        </div>

        <!-- SLA Compliance Card -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6 border-l-4 border-amber-500 hover:shadow-xl transition-all duration-200 hover:-translate-y-1">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-500 dark:text-gray-400 text-sm font-medium mb-1">SLA Compliance</p>
                    <p class="text-3xl font-bold text-gray-800 dark:text-white">{{ number_format($analytics['sla_compliance'] ?? 100) }}%</p>
                    <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2 mt-2">
                        <div class="bg-gradient-to-r from-amber-400 to-orange-500 h-2 rounded-full" style="width: {{ $analytics['sla_compliance'] ?? 100 }}%"></div>
                    </div>
                </div>
                <div class="bg-amber-100 dark:bg-amber-900/30 p-4 rounded-lg">
                    <i class="fas fa-chart-line text-2xl text-amber-600 dark:text-amber-400"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts Row -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
        <!-- Tickets by Status -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg overflow-hidden">
            <div class="bg-gradient-to-r from-blue-500 to-cyan-500 px-6 py-4">
                <h3 class="text-xl font-bold text-white flex items-center">
                    <i class="fas fa-tasks mr-2"></i>
                    Tickets by Status
                </h3>
            </div>
            <div class="p-6">
                @if(isset($analytics['tickets_by_status']) && $analytics['tickets_by_status']->isNotEmpty())
                    <div class="space-y-3">
                        @foreach($analytics['tickets_by_status'] as $status => $count)
                            @php
                                $statusColors = [
                                    'open' => 'bg-blue-500',
                                    'in_progress' => 'bg-yellow-500',
                                    'waiting_customer' => 'bg-purple-500',
                                    'resolved' => 'bg-green-500',
                                    'closed' => 'bg-gray-500'
                                ];
                                $color = $statusColors[$status] ?? 'bg-gray-500';
                                $total = $analytics['total_tickets'] > 0 ? $analytics['total_tickets'] : 1;
                                $percentage = round(($count / $total) * 100, 1);
                            @endphp
                            <div class="flex items-center justify-between p-4 bg-gray-50 dark:bg-gray-700 rounded-lg hover:shadow-md transition-all">
                                <div class="flex items-center flex-1">
                                    <div class="w-3 h-3 {{ $color }} rounded-full mr-3"></div>
                                    <span class="font-semibold text-gray-900 dark:text-white capitalize">{{ str_replace('_', ' ', $status) }}</span>
                                </div>
                                <div class="flex items-center gap-4">
                                    <div class="text-right">
                                        <div class="text-2xl font-bold text-gray-800 dark:text-white">{{ $count }}</div>
                                        <div class="text-xs text-gray-500 dark:text-gray-400">{{ $percentage }}%</div>
                                    </div>
                                    <div class="w-24 bg-gray-200 dark:bg-gray-600 rounded-full h-2">
                                        <div class="{{ $color }} h-2 rounded-full" style="width: {{ $percentage }}%"></div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="flex flex-col items-center justify-center py-12 text-gray-400 dark:text-gray-500">
                        <i class="fas fa-chart-bar text-6xl mb-4"></i>
                        <p class="text-lg">No data available</p>
                    </div>
                @endif
            </div>
        </div>

        <!-- Tickets by Priority -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg overflow-hidden">
            <div class="bg-gradient-to-r from-orange-500 to-red-500 px-6 py-4">
                <h3 class="text-xl font-bold text-white flex items-center">
                    <i class="fas fa-flag mr-2"></i>
                    Tickets by Priority
                </h3>
            </div>
            <div class="p-6">
                @if(isset($analytics['tickets_by_priority']) && $analytics['tickets_by_priority']->isNotEmpty())
                    <div class="space-y-3">
                        @foreach($analytics['tickets_by_priority'] as $priority => $count)
                            @php
                                $priorityColors = [
                                    'critical' => 'bg-red-500',
                                    'high' => 'bg-orange-500',
                                    'medium' => 'bg-yellow-500',
                                    'low' => 'bg-blue-500'
                                ];
                                $color = $priorityColors[$priority] ?? 'bg-gray-500';
                                $total = $analytics['total_tickets'] > 0 ? $analytics['total_tickets'] : 1;
                                $percentage = round(($count / $total) * 100, 1);
                            @endphp
                            <div class="flex items-center justify-between p-4 bg-gray-50 dark:bg-gray-700 rounded-lg hover:shadow-md transition-all">
                                <div class="flex items-center flex-1">
                                    <div class="w-3 h-3 {{ $color }} rounded-full mr-3"></div>
                                    <span class="font-semibold text-gray-900 dark:text-white capitalize">{{ $priority }}</span>
                                </div>
                                <div class="flex items-center gap-4">
                                    <div class="text-right">
                                        <div class="text-2xl font-bold text-gray-800 dark:text-white">{{ $count }}</div>
                                        <div class="text-xs text-gray-500 dark:text-gray-400">{{ $percentage }}%</div>
                                    </div>
                                    <div class="w-24 bg-gray-200 dark:bg-gray-600 rounded-full h-2">
                                        <div class="{{ $color }} h-2 rounded-full" style="width: {{ $percentage }}%"></div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="flex flex-col items-center justify-center py-12 text-gray-400 dark:text-gray-500">
                        <i class="fas fa-chart-pie text-6xl mb-4"></i>
                        <p class="text-lg">No data available</p>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Top Performing Agents -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg overflow-hidden">
        <div class="bg-gradient-to-r from-purple-500 to-pink-500 px-6 py-4">
            <h3 class="text-xl font-bold text-white flex items-center">
                <i class="fas fa-trophy mr-2"></i>
                Top Performing Agents
            </h3>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gradient-to-r from-gray-50 to-gray-100 dark:from-gray-700 dark:to-gray-600">
                    <tr>
                        <th class="px-6 py-4 text-left text-xs font-bold text-gray-700 dark:text-gray-200 uppercase tracking-wider">
                            Rank
                        </th>
                        <th class="px-6 py-4 text-left text-xs font-bold text-gray-700 dark:text-gray-200 uppercase tracking-wider">
                            Agent
                        </th>
                        <th class="px-6 py-4 text-left text-xs font-bold text-gray-700 dark:text-gray-200 uppercase tracking-wider">
                            Resolved Tickets
                        </th>
                        <th class="px-6 py-4 text-left text-xs font-bold text-gray-700 dark:text-gray-200 uppercase tracking-wider">
                            Performance
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse($analytics['top_agents'] ?? [] as $index => $agent)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors duration-150">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center">
                                    @if($index == 0)
                                        <span class="inline-flex items-center justify-center w-10 h-10 rounded-full bg-gradient-to-br from-yellow-400 to-amber-600 text-white font-bold shadow-lg">
                                            <i class="fas fa-crown"></i>
                                        </span>
                                    @elseif($index == 1)
                                        <span class="inline-flex items-center justify-center w-10 h-10 rounded-full bg-gradient-to-br from-gray-400 to-gray-600 text-white font-bold shadow-lg">
                                            #2
                                        </span>
                                    @elseif($index == 2)
                                        <span class="inline-flex items-center justify-center w-10 h-10 rounded-full bg-gradient-to-br from-orange-400 to-orange-600 text-white font-bold shadow-lg">
                                            #3
                                        </span>
                                    @else
                                        <span class="inline-flex items-center justify-center w-10 h-10 rounded-full bg-gradient-to-br from-blue-400 to-blue-600 text-white font-bold">
                                            #{{ $index + 1 }}
                                        </span>
                                    @endif
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center">
                                    <div class="flex-shrink-0 h-10 w-10">
                                        <div class="h-10 w-10 rounded-full bg-gradient-to-br from-purple-400 to-pink-600 flex items-center justify-center text-white font-semibold">
                                            {{ substr($agent->assignedTo->name ?? 'N/A', 0, 1) }}
                                        </div>
                                    </div>
                                    <div class="ml-4">
                                        <div class="text-sm font-semibold text-gray-900 dark:text-white">
                                            {{ $agent->assignedTo->name ?? 'N/A' }}
                                        </div>
                                        <div class="text-xs text-gray-500 dark:text-gray-400">
                                            {{ $agent->assignedTo->email ?? '' }}
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="inline-flex items-center px-4 py-2 rounded-full text-sm font-bold bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300">
                                    <i class="fas fa-check-circle mr-2"></i>
                                    {{ $agent->resolved_count }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                @php
                                    $maxResolved = $analytics['top_agents'][0]->resolved_count ?? 1;
                                    $performance = round(($agent->resolved_count / $maxResolved) * 100);
                                @endphp
                                <div class="flex items-center">
                                    <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2 mr-3" style="min-width: 100px;">
                                        <div class="bg-gradient-to-r from-purple-500 to-pink-600 h-2 rounded-full" style="width: {{ $performance }}%"></div>
                                    </div>
                                    <span class="text-sm font-semibold text-gray-700 dark:text-gray-300">{{ $performance }}%</span>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-6 py-12 text-center">
                                <div class="flex flex-col items-center justify-center text-gray-400 dark:text-gray-500">
                                    <i class="fas fa-users text-6xl mb-4"></i>
                                    <p class="text-lg font-medium">No agent data available</p>
                                    <p class="text-sm mt-2">Agent performance will appear here once tickets are resolved</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
