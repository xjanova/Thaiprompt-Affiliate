@extends('layouts.app')

@section('title', 'Daily Rewards')

@section('content')
<div class="container mx-auto px-4 py-8">
    <!-- Header -->
    <div class="mb-8 text-center">
        <h1 class="text-4xl font-bold text-gray-900 dark:text-white mb-2">
            🎁 Daily Rewards
        </h1>
        <p class="text-gray-600 dark:text-gray-400">
            Login every day to earn amazing rewards!
        </p>
    </div>

    <!-- Streak Stats -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
        <div class="bg-gradient-to-br from-orange-500 to-red-600 rounded-lg p-6 text-white shadow-lg">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm opacity-90 mb-1">Current Streak</p>
                    <p class="text-4xl font-bold">{{ $streak->current_streak }} 🔥</p>
                </div>
                <div class="text-5xl">📅</div>
            </div>
        </div>

        <div class="bg-gradient-to-br from-purple-500 to-indigo-600 rounded-lg p-6 text-white shadow-lg">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm opacity-90 mb-1">Longest Streak</p>
                    <p class="text-4xl font-bold">{{ $streak->longest_streak }} 🏆</p>
                </div>
                <div class="text-5xl">👑</div>
            </div>
        </div>

        <div class="bg-gradient-to-br from-green-500 to-teal-600 rounded-lg p-6 text-white shadow-lg">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm opacity-90 mb-1">Total Earned</p>
                    <p class="text-4xl font-bold">฿{{ number_format($streak->total_rewards) }} 💰</p>
                </div>
                <div class="text-5xl">💵</div>
            </div>
        </div>
    </div>

    <!-- Today's Reward -->
    @if($canClaim)
        <div class="mb-8 bg-gradient-to-r from-yellow-400 via-orange-500 to-red-500 rounded-lg p-8 text-white shadow-xl">
            <div class="text-center">
                <h2 class="text-3xl font-bold mb-4">Today's Reward is Ready! 🎉</h2>
                <div class="bg-white bg-opacity-20 rounded-lg p-6 inline-block mb-6">
                    <p class="text-5xl font-bold mb-2">฿{{ number_format($todayReward['amount']) }}</p>
                    <p class="text-lg opacity-90">Day {{ $streak->current_streak + 1 }}</p>
                    @if(isset($todayReward['bonus']))
                        <p class="text-xl font-bold mt-2 animate-pulse">
                            ⭐ {{ $todayReward['bonus_message'] ?? 'Bonus Day!' }} ⭐
                        </p>
                    @endif
                </div>
                <button onclick="claimDailyReward()" id="claimButton" 
                        class="px-8 py-4 bg-white text-orange-600 rounded-lg font-bold text-xl hover:bg-gray-100 transition transform hover:scale-105 shadow-lg">
                    Claim Now! 🎁
                </button>
            </div>
        </div>
    @else
        <div class="mb-8 bg-gray-100 dark:bg-gray-800 rounded-lg p-8 text-center">
            <div class="text-6xl mb-4">✅</div>
            <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-2">Already Claimed Today!</h2>
            <p class="text-gray-600 dark:text-gray-400">Come back tomorrow for your next reward</p>
            @if($streak->last_claim_date)
                <p class="text-sm text-gray-500 dark:text-gray-500 mt-2">
                    Last claimed: {{ $streak->last_claim_date->format('M d, Y') }}
                </p>
            @endif
        </div>
    @endif

    <!-- Upcoming Rewards -->
    <div class="mb-8">
        <h3 class="text-2xl font-bold text-gray-900 dark:text-white mb-4">Upcoming Rewards (Next 7 Days)</h3>
        <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-7 gap-4">
            @foreach($upcomingRewards as $index => $upcoming)
                <div class="bg-white dark:bg-gray-800 rounded-lg p-4 text-center shadow {{ $upcoming['reward']['bonus'] ? 'ring-2 ring-yellow-400' : '' }}">
                    <p class="text-sm text-gray-500 dark:text-gray-400 mb-2">Day {{ $upcoming['day'] }}</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white">฿{{ $upcoming['reward']['amount'] }}</p>
                    @if($upcoming['reward']['bonus'])
                        <p class="text-xs text-yellow-600 dark:text-yellow-400 mt-1">⭐ Bonus</p>
                    @endif
                </div>
            @endforeach
        </div>
    </div>

    <!-- Reward Calendar -->
    <div class="bg-white dark:bg-gray-800 rounded-lg p-6 shadow-lg">
        <h3 class="text-2xl font-bold text-gray-900 dark:text-white mb-4">Recent Claims</h3>
        @if($recentClaims->isNotEmpty())
            <div class="space-y-2">
                @foreach($recentClaims as $claim)
                    <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                        <div class="flex items-center space-x-3">
                            <div class="text-2xl">
                                @if($claim->bonus_applied)
                                    ⭐
                                @else
                                    🎁
                                @endif
                            </div>
                            <div>
                                <p class="font-medium text-gray-900 dark:text-white">
                                    Day {{ $claim->day_number }}
                                    @if($claim->bonus_applied)
                                        <span class="text-yellow-600 dark:text-yellow-400 text-sm">BONUS</span>
                                    @endif
                                </p>
                                <p class="text-sm text-gray-500 dark:text-gray-400">
                                    {{ $claim->claim_date->format('M d, Y') }}
                                </p>
                            </div>
                        </div>
                        <div class="text-right">
                            <p class="font-bold text-green-600 dark:text-green-400">
                                +฿{{ number_format($claim->reward_amount) }}
                            </p>
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <p class="text-center text-gray-500 dark:text-gray-400 py-4">
                No claims yet. Start your streak today!
            </p>
        @endif
    </div>

    <!-- Tips -->
    <div class="mt-8 bg-blue-50 dark:bg-blue-900 rounded-lg p-6">
        <h3 class="text-lg font-bold text-blue-900 dark:text-blue-100 mb-3">
            💡 Tips to Maximize Your Rewards
        </h3>
        <ul class="space-y-2 text-blue-800 dark:text-blue-200">
            <li class="flex items-start">
                <span class="mr-2">•</span>
                <span>Login every day to maintain your streak and earn more rewards</span>
            </li>
            <li class="flex items-start">
                <span class="mr-2">•</span>
                <span>Every 7 days you get a bonus reward!</span>
            </li>
            <li class="flex items-start">
                <span class="mr-2">•</span>
                <span>Reach 30 days for a massive 2x multiplier bonus!</span>
            </li>
            <li class="flex items-start">
                <span class="mr-2">•</span>
                <span>Missing a day will reset your streak, so don't forget to claim!</span>
            </li>
        </ul>
    </div>
</div>

<script>
function claimDailyReward() {
    const button = document.getElementById('claimButton');
    button.disabled = true;
    button.textContent = 'Claiming...';

    fetch('{{ route('rewards.daily.claim') }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Show success animation
            button.textContent = '✅ Claimed!';
            button.classList.add('bg-green-500');

            // Create confetti effect
            showConfetti();

            // Show reward notification
            showRewardNotification(data.reward);

            // Reload after 2 seconds
            setTimeout(() => {
                location.reload();
            }, 2000);
        } else {
            alert('Error: ' + (data.error || 'Failed to claim reward'));
            button.disabled = false;
            button.textContent = 'Claim Now! 🎁';
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Failed to claim reward. Please try again.');
        button.disabled = false;
        button.textContent = 'Claim Now! 🎁';
    });
}

function showRewardNotification(reward) {
    const notification = document.createElement('div');
    notification.className = 'fixed top-20 left-1/2 transform -translate-x-1/2 bg-green-500 text-white px-6 py-4 rounded-lg shadow-lg z-50 animate-bounce';
    notification.innerHTML = `
        <div class="text-center">
            <p class="text-2xl font-bold">You earned!</p>
            <p class="text-3xl font-bold">฿${reward.amount}</p>
        </div>
    `;
    document.body.appendChild(notification);

    setTimeout(() => {
        notification.remove();
    }, 3000);
}

function showConfetti() {
    // Simple confetti effect
    for (let i = 0; i < 50; i++) {
        createConfetti();
    }
}

function createConfetti() {
    const confetti = document.createElement('div');
    confetti.style.position = 'fixed';
    confetti.style.width = '10px';
    confetti.style.height = '10px';
    confetti.style.backgroundColor = ['#ff0', '#f0f', '#0ff', '#f00', '#0f0'][Math.floor(Math.random() * 5)];
    confetti.style.left = Math.random() * 100 + '%';
    confetti.style.top = '-10px';
    confetti.style.opacity = '1';
    confetti.style.zIndex = '9999';
    document.body.appendChild(confetti);

    let pos = 0;
    const fall = setInterval(() => {
        if (pos >= window.innerHeight) {
            clearInterval(fall);
            confetti.remove();
        } else {
            pos += 5;
            confetti.style.top = pos + 'px';
            confetti.style.opacity = (1 - pos / window.innerHeight).toString();
        }
    }, 20);
}
</script>
@endsection
