@extends('layouts.app')

@section('title', $game->name . ' - Achievements')

@section('content')
<div class="container mx-auto px-4 py-8">
    <!-- Header -->
    <div class="mb-8">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-3xl font-bold text-gray-900 dark:text-white mb-2">
                    {{ $game->name }} Achievements
                </h1>
                <p class="text-gray-600 dark:text-gray-400">
                    Complete challenges and earn rewards
                </p>
            </div>
            <a href="{{ route('games.show', $game->slug) }}" class="px-6 py-3 bg-gray-600 hover:bg-gray-700 text-white rounded-lg transition">
                Back to Game
            </a>
        </div>
    </div>

    @auth
        <!-- Progress Summary -->
        <div class="mb-8 bg-gradient-to-r from-purple-600 to-blue-600 rounded-lg p-6 text-white">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div class="text-center">
                    <p class="text-4xl font-bold">{{ count($unlockedAchievementIds) }}</p>
                    <p class="text-sm opacity-90">Achievements Unlocked</p>
                </div>
                <div class="text-center">
                    <p class="text-4xl font-bold">{{ count($achievements) }}</p>
                    <p class="text-sm opacity-90">Total Achievements</p>
                </div>
                <div class="text-center">
                    <p class="text-4xl font-bold">{{ count($achievements) > 0 ? round((count($unlockedAchievementIds) / count($achievements)) * 100) : 0 }}%</p>
                    <p class="text-sm opacity-90">Completion Rate</p>
                </div>
            </div>
        </div>
    @endauth

    @guest
        <div class="mb-8 bg-blue-50 dark:bg-blue-900 border border-blue-200 dark:border-blue-700 rounded-lg p-6">
            <div class="flex items-center">
                <svg class="w-6 h-6 text-blue-600 dark:text-blue-400 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                <div>
                    <p class="text-blue-900 dark:text-blue-100 font-medium">Login to track your achievements</p>
                    <p class="text-blue-700 dark:text-blue-300 text-sm">Create an account to save your progress and unlock achievements!</p>
                </div>
                <a href="{{ route('login') }}" class="ml-auto px-6 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition">
                    Login
                </a>
            </div>
        </div>
    @endguest

    <!-- Achievements Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        @foreach($achievements as $achievement)
            @php
                $isUnlocked = in_array($achievement->id, $unlockedAchievementIds);
                $typeColors = [
                    'score' => 'bg-yellow-500',
                    'wave' => 'bg-blue-500',
                    'kills' => 'bg-red-500',
                    'boss' => 'bg-purple-500',
                    'time' => 'bg-green-500',
                ];
                $typeIcons = [
                    'score' => '⭐',
                    'wave' => '🌊',
                    'kills' => '💀',
                    'boss' => '👑',
                    'time' => '⏱️',
                ];
                $badgeColor = $typeColors[$achievement->type] ?? 'bg-gray-500';
                $icon = $typeIcons[$achievement->type] ?? '🏆';
            @endphp

            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg overflow-hidden transform transition hover:scale-105 {{ $isUnlocked ? 'ring-2 ring-yellow-400' : 'opacity-75' }}">
                <!-- Achievement Header -->
                <div class="relative {{ $badgeColor }} p-6 text-white">
                    <div class="flex items-start justify-between">
                        <div class="text-5xl">
                            @if($achievement->icon)
                                {{ $achievement->icon }}
                            @else
                                {{ $icon }}
                            @endif
                        </div>
                        <div class="text-right">
                            <span class="inline-block px-3 py-1 bg-white bg-opacity-20 rounded-full text-xs font-bold uppercase">
                                {{ $achievement->type }}
                            </span>
                        </div>
                    </div>

                    @if($isUnlocked)
                        <div class="absolute top-2 left-2">
                            <div class="bg-yellow-400 text-yellow-900 rounded-full p-2">
                                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                </svg>
                            </div>
                        </div>
                    @else
                        <div class="absolute top-2 left-2">
                            <div class="bg-black bg-opacity-30 rounded-full p-2">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                                </svg>
                            </div>
                        </div>
                    @endif
                </div>

                <!-- Achievement Details -->
                <div class="p-6">
                    <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-2">
                        {{ $achievement->name }}
                    </h3>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
                        {{ $achievement->description }}
                    </p>

                    <!-- Requirement -->
                    <div class="flex items-center justify-between mb-4">
                        <span class="text-sm text-gray-500 dark:text-gray-400">Requirement:</span>
                        <span class="text-sm font-bold text-gray-900 dark:text-white">
                            @switch($achievement->type)
                                @case('score')
                                    Score: {{ number_format($achievement->requirement) }}
                                    @break
                                @case('wave')
                                    Wave: {{ $achievement->requirement }}
                                    @break
                                @case('kills')
                                    Kills: {{ $achievement->requirement }}
                                    @break
                                @case('boss')
                                    Bosses: {{ $achievement->requirement }}
                                    @break
                                @case('time')
                                    Time: {{ gmdate('H:i:s', $achievement->requirement) }}
                                    @break
                                @default
                                    {{ $achievement->requirement }}
                            @endswitch
                        </span>
                    </div>

                    <!-- Reward -->
                    @if($achievement->reward_points > 0)
                        <div class="flex items-center justify-between pt-4 border-t border-gray-200 dark:border-gray-700">
                            <span class="text-sm text-gray-500 dark:text-gray-400">Reward:</span>
                            <span class="text-sm font-bold text-yellow-600 dark:text-yellow-400">
                                +{{ $achievement->reward_points }} Points
                            </span>
                        </div>
                    @endif

                    <!-- Status Badge -->
                    <div class="mt-4">
                        @if($isUnlocked)
                            <div class="bg-green-100 dark:bg-green-900 text-green-800 dark:text-green-200 rounded-lg px-4 py-2 text-center font-medium">
                                ✓ Unlocked
                            </div>
                        @else
                            <div class="bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-400 rounded-lg px-4 py-2 text-center font-medium">
                                🔒 Locked
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    @if($achievements->isEmpty())
        <div class="text-center py-12">
            <svg class="w-24 h-24 text-gray-400 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"></path>
            </svg>
            <p class="text-gray-500 dark:text-gray-400 text-lg mb-4">No achievements available yet</p>
            <p class="text-gray-400 dark:text-gray-500 text-sm">Check back later for new challenges!</p>
        </div>
    @endif

    <!-- Tips Section -->
    @if($achievements->isNotEmpty())
        <div class="mt-12 bg-blue-50 dark:bg-blue-900 rounded-lg p-6">
            <h3 class="text-lg font-bold text-blue-900 dark:text-blue-100 mb-3">
                💡 Tips for Unlocking Achievements
            </h3>
            <ul class="space-y-2 text-blue-800 dark:text-blue-200">
                <li class="flex items-start">
                    <span class="mr-2">•</span>
                    <span>Play consistently to improve your skills and reach higher scores</span>
                </li>
                <li class="flex items-start">
                    <span class="mr-2">•</span>
                    <span>Try different strategies and ship loadouts for better performance</span>
                </li>
                <li class="flex items-start">
                    <span class="mr-2">•</span>
                    <span>Focus on one achievement type at a time for faster progress</span>
                </li>
                <li class="flex items-start">
                    <span class="mr-2">•</span>
                    <span>Achievements are automatically unlocked when you meet the requirements</span>
                </li>
            </ul>
        </div>
    @endif
</div>
@endsection
