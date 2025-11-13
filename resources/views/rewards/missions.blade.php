@extends('layouts.app')

@section('title', 'Missions')

@section('content')
<div class="container mx-auto px-4 py-8">
    <!-- Header -->
    <div class="mb-8">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-4xl font-bold text-gray-900 dark:text-white mb-2">
                    🎯 Missions
                </h1>
                <p class="text-gray-600 dark:text-gray-400">
                    Complete missions to earn rewards and unlock achievements
                </p>
            </div>
            <div class="text-right">
                <p class="text-3xl font-bold text-purple-600 dark:text-purple-400">{{ $completedCount }}</p>
                <p class="text-sm text-gray-500 dark:text-gray-400">Completed</p>
            </div>
        </div>
    </div>

    <!-- Daily Missions -->
    <div class="mb-8">
        <div class="flex items-center mb-4">
            <h2 class="text-2xl font-bold text-gray-900 dark:text-white">📅 Daily Missions</h2>
            <span class="ml-3 px-3 py-1 bg-blue-100 dark:bg-blue-900 text-blue-800 dark:text-blue-200 rounded-full text-sm font-medium">
                Resets Daily
            </span>
        </div>

        @if($dailyMissions->isNotEmpty())
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                @foreach($dailyMissions as $mission)
                    @php
                        $progress = $mission->userProgress->first();
                        $currentProgress = $progress->current_progress ?? 0;
                        $percentage = $mission->target > 0 ? min(100, ($currentProgress / $mission->target) * 100) : 0;
                        $isCompleted = $progress && $progress->is_completed;
                        $isClaimed = $progress && $progress->is_claimed;
                    @endphp

                    <div class="bg-white dark:bg-gray-800 rounded-lg p-6 shadow-lg {{ $isCompleted && !$isClaimed ? 'ring-2 ring-green-400' : '' }}">
                        <div class="flex items-start justify-between mb-4">
                            <div class="flex-1">
                                <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-1">
                                    {{ $mission->name }}
                                </h3>
                                <p class="text-sm text-gray-600 dark:text-gray-400">
                                    {{ $mission->description }}
                                </p>
                            </div>
                            <div class="ml-4">
                                @for($i = 1; $i <= $mission->difficulty; $i++)
                                    <span class="text-yellow-400">⭐</span>
                                @endfor
                            </div>
                        </div>

                        <!-- Progress Bar -->
                        <div class="mb-4">
                            <div class="flex items-center justify-between text-sm mb-1">
                                <span class="text-gray-600 dark:text-gray-400">Progress</span>
                                <span class="font-medium text-gray-900 dark:text-white">
                                    {{ $currentProgress }} / {{ $mission->target }}
                                </span>
                            </div>
                            <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-3">
                                <div class="bg-blue-600 h-3 rounded-full transition-all duration-300" 
                                     style="width: {{ $percentage }}%"></div>
                            </div>
                        </div>

                        <!-- Reward -->
                        <div class="flex items-center justify-between">
                            <div class="flex items-center space-x-2">
                                <span class="text-2xl">💰</span>
                                <div>
                                    <p class="text-sm text-gray-500 dark:text-gray-400">Reward</p>
                                    <p class="font-bold text-gray-900 dark:text-white">
                                        ฿{{ number_format($mission->reward_amount) }}
                                        @if($mission->reward_points > 0)
                                            <span class="text-purple-600">+{{ $mission->reward_points }}pts</span>
                                        @endif
                                    </p>
                                </div>
                            </div>

                            @if($isClaimed)
                                <button disabled class="px-4 py-2 bg-gray-300 dark:bg-gray-600 text-gray-600 dark:text-gray-400 rounded-lg cursor-not-allowed">
                                    ✅ Claimed
                                </button>
                            @elseif($isCompleted)
                                <button onclick="claimMission({{ $mission->id }})" 
                                        class="px-4 py-2 bg-green-500 hover:bg-green-600 text-white rounded-lg font-medium transition">
                                    Claim Reward
                                </button>
                            @else
                                <button disabled class="px-4 py-2 bg-gray-200 dark:bg-gray-700 text-gray-400 dark:text-gray-500 rounded-lg cursor-not-allowed">
                                    In Progress
                                </button>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <div class="bg-gray-100 dark:bg-gray-800 rounded-lg p-8 text-center">
                <p class="text-gray-500 dark:text-gray-400">No daily missions available at the moment</p>
            </div>
        @endif
    </div>

    <!-- Weekly Missions -->
    <div class="mb-8">
        <div class="flex items-center mb-4">
            <h2 class="text-2xl font-bold text-gray-900 dark:text-white">🗓️ Weekly Missions</h2>
            <span class="ml-3 px-3 py-1 bg-purple-100 dark:bg-purple-900 text-purple-800 dark:text-purple-200 rounded-full text-sm font-medium">
                Resets Weekly
            </span>
        </div>

        @if($weeklyMissions->isNotEmpty())
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                @foreach($weeklyMissions as $mission)
                    @php
                        $progress = $mission->userProgress->first();
                        $currentProgress = $progress->current_progress ?? 0;
                        $percentage = $mission->target > 0 ? min(100, ($currentProgress / $mission->target) * 100) : 0;
                        $isCompleted = $progress && $progress->is_completed;
                        $isClaimed = $progress && $progress->is_claimed;
                    @endphp

                    <div class="bg-white dark:bg-gray-800 rounded-lg p-6 shadow-lg {{ $isCompleted && !$isClaimed ? 'ring-2 ring-green-400' : '' }}">
                        <div class="flex items-start justify-between mb-4">
                            <div class="flex-1">
                                <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-1">
                                    {{ $mission->name }}
                                </h3>
                                <p class="text-sm text-gray-600 dark:text-gray-400">
                                    {{ $mission->description }}
                                </p>
                            </div>
                            <div class="ml-4">
                                @for($i = 1; $i <= $mission->difficulty; $i++)
                                    <span class="text-yellow-400">⭐</span>
                                @endfor
                            </div>
                        </div>

                        <!-- Progress Bar -->
                        <div class="mb-4">
                            <div class="flex items-center justify-between text-sm mb-1">
                                <span class="text-gray-600 dark:text-gray-400">Progress</span>
                                <span class="font-medium text-gray-900 dark:text-white">
                                    {{ $currentProgress }} / {{ $mission->target }}
                                </span>
                            </div>
                            <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-3">
                                <div class="bg-purple-600 h-3 rounded-full transition-all duration-300" 
                                     style="width: {{ $percentage }}%"></div>
                            </div>
                        </div>

                        <!-- Reward -->
                        <div class="flex items-center justify-between">
                            <div class="flex items-center space-x-2">
                                <span class="text-2xl">💰</span>
                                <div>
                                    <p class="text-sm text-gray-500 dark:text-gray-400">Reward</p>
                                    <p class="font-bold text-gray-900 dark:text-white">
                                        ฿{{ number_format($mission->reward_amount) }}
                                        @if($mission->reward_points > 0)
                                            <span class="text-purple-600">+{{ $mission->reward_points }}pts</span>
                                        @endif
                                    </p>
                                </div>
                            </div>

                            @if($isClaimed)
                                <button disabled class="px-4 py-2 bg-gray-300 dark:bg-gray-600 text-gray-600 dark:text-gray-400 rounded-lg cursor-not-allowed">
                                    ✅ Claimed
                                </button>
                            @elseif($isCompleted)
                                <button onclick="claimMission({{ $mission->id }})" 
                                        class="px-4 py-2 bg-green-500 hover:bg-green-600 text-white rounded-lg font-medium transition">
                                    Claim Reward
                                </button>
                            @else
                                <button disabled class="px-4 py-2 bg-gray-200 dark:bg-gray-700 text-gray-400 dark:text-gray-500 rounded-lg cursor-not-allowed">
                                    In Progress
                                </button>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <div class="bg-gray-100 dark:bg-gray-800 rounded-lg p-8 text-center">
                <p class="text-gray-500 dark:text-gray-400">No weekly missions available at the moment</p>
            </div>
        @endif
    </div>

    <!-- Tips -->
    <div class="bg-blue-50 dark:bg-blue-900 rounded-lg p-6">
        <h3 class="text-lg font-bold text-blue-900 dark:text-blue-100 mb-3">
            💡 Mission Tips
        </h3>
        <ul class="space-y-2 text-blue-800 dark:text-blue-200">
            <li class="flex items-start">
                <span class="mr-2">•</span>
                <span>Complete daily missions every day for consistent rewards</span>
            </li>
            <li class="flex items-start">
                <span class="mr-2">•</span>
                <span>Weekly missions offer bigger rewards but require more effort</span>
            </li>
            <li class="flex items-start">
                <span class="mr-2">•</span>
                <span>Higher difficulty missions (more stars) give better rewards</span>
            </li>
            <li class="flex items-start">
                <span class="mr-2">•</span>
                <span>Don't forget to claim your rewards after completing missions!</span>
            </li>
        </ul>
    </div>
</div>

<script>
function claimMission(missionId) {
    fetch(`/rewards/missions/${missionId}/claim`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Show success notification
            showNotification(`You earned ฿${data.reward.amount}!`, 'success');
            
            // Reload after 1 second
            setTimeout(() => {
                location.reload();
            }, 1000);
        } else {
            showNotification('Error: ' + (data.error || 'Failed to claim reward'), 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('Failed to claim reward. Please try again.', 'error');
    });
}

function showNotification(message, type) {
    const bgColor = type === 'success' ? 'bg-green-500' : 'bg-red-500';
    const notification = document.createElement('div');
    notification.className = `fixed top-20 left-1/2 transform -translate-x-1/2 ${bgColor} text-white px-6 py-3 rounded-lg shadow-lg z-50`;
    notification.textContent = message;
    document.body.appendChild(notification);

    setTimeout(() => {
        notification.remove();
    }, 3000);
}
</script>
@endsection
