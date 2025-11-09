@php
use App\Models\User;
use App\Models\Affiliate;
use App\Models\Commission;

$settings = $section['settings'] ?? [];
$content = $section['content'] ?? [];
$stats = $content['stats'] ?? [];

// Get realtime data if enabled
if ($settings['use_realtime_data'] ?? false) {
    $realtimeStats = [
        'total_users' => User::count(),
        'total_affiliates' => Affiliate::count(),
        'total_commissions' => Commission::sum('amount'),
        'success_rate' => Commission::where('status', 'paid')->count() > 0
            ? round((Commission::where('status', 'paid')->count() / Commission::count()) * 100, 1)
            : 95,
    ];
}
@endphp

<section class="py-20 bg-white relative overflow-hidden">
    <div class="container mx-auto px-4">
        @if(!empty($content['title']))
        <div class="text-center mb-12">
            <h2 class="text-4xl font-bold text-gray-900 mb-4">{{ $content['title'] }}</h2>
            @if(!empty($content['subtitle']))
            <p class="text-xl text-gray-600">{{ $content['subtitle'] }}</p>
            @endif
        </div>
        @endif

        <div class="grid grid-cols-1 md:grid-cols-{{ $settings['columns'] ?? 4 }} gap-8">
            @foreach($stats as $stat)
            @php
                $value = $stat['value'] ?? 0;
                if (($stat['value_type'] ?? 'static') === 'dynamic' && isset($realtimeStats[$value])) {
                    $value = $realtimeStats[$value];
                }

                $colorClasses = match($stat['color'] ?? 'blue') {
                    'blue' => 'from-blue-500 to-blue-600',
                    'purple' => 'from-purple-500 to-purple-600',
                    'green' => 'from-green-500 to-green-600',
                    'pink' => 'from-pink-500 to-pink-600',
                    'orange' => 'from-orange-500 to-orange-600',
                    default => 'from-gray-500 to-gray-600',
                };
            @endphp

            <div class="text-center p-8 bg-gradient-to-br {{ $colorClasses }} rounded-2xl shadow-lg transform hover:scale-105 transition-all duration-300">
                <div class="text-6xl mb-4">{{ $stat['icon'] ?? '📊' }}</div>
                <div class="text-4xl font-bold text-white mb-2">
                    {{ $stat['prefix'] ?? '' }}{{ number_format($value) }}{{ $stat['suffix'] ?? '' }}
                </div>
                <div class="text-white/90 text-lg font-medium">
                    {{ $stat['label'] ?? '' }}
                </div>
            </div>
            @endforeach
        </div>
    </div>
</section>
