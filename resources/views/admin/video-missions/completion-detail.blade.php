@extends('layouts.admin-v3')

@section('title', 'รายละเอียดการทำภารกิจ #' . $completion->id)

@section('content')
<div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    {{-- Header --}}
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
                🔍 รายละเอียดการทำภารกิจ #{{ $completion->id }}
            </h1>
            <p class="text-gray-600 dark:text-gray-400">{{ $completion->mission->display_title ?? 'ภารกิจถูกลบ' }}</p>
        </div>
        <a href="{{ route('admin.video-missions.completions') }}"
           class="px-4 py-2 bg-gray-200 dark:bg-gray-700 hover:bg-gray-300 dark:hover:bg-gray-600 text-gray-800 dark:text-white rounded-xl font-medium transition-all">
            ← กลับ
        </a>
    </div>

    <div class="grid lg:grid-cols-3 gap-6">
        {{-- Main Info --}}
        <div class="lg:col-span-2 space-y-6">
            {{-- Status Banner --}}
            <div class="rounded-2xl p-6 {{ $completion->is_suspicious ? 'bg-red-50 dark:bg-red-900/30 border border-red-200 dark:border-red-800' : ($completion->status === 'verified' ? 'bg-green-50 dark:bg-green-900/30 border border-green-200 dark:border-green-800' : 'bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700') }}">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-4">
                        <span class="text-4xl">{{ $completion->status_icon }}</span>
                        <div>
                            <h2 class="text-lg font-semibold {{ $completion->is_suspicious ? 'text-red-800 dark:text-red-200' : 'text-gray-900 dark:text-white' }}">
                                {{ $completion->status_label }}
                            </h2>
                            <p class="text-sm {{ $completion->is_suspicious ? 'text-red-600 dark:text-red-300' : 'text-gray-600 dark:text-gray-400' }}">
                                @if($completion->is_suspicious)
                                ⚠️ ตรวจพบพฤติกรรมน่าสงสัย (Score: {{ $completion->suspicious_score }})
                                @else
                                พฤติกรรมปกติ
                                @endif
                            </p>
                        </div>
                    </div>
                    @if($completion->status === 'completed')
                    <div class="flex gap-2">
                        <form action="{{ route('admin.video-missions.completion.verify', $completion) }}" method="POST">
                            @csrf
                            <button type="submit"
                                    class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg font-medium transition">
                                ✅ ยืนยัน
                            </button>
                        </form>
                        <button type="button" onclick="showRejectModal()"
                                class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg font-medium transition">
                            ❌ ปฏิเสธ
                        </button>
                    </div>
                    @endif
                </div>
            </div>

            {{-- Watch Stats --}}
            <div class="glass-fusion dark:bg-gray-800/50 rounded-2xl shadow-lg p-6 backdrop-blur-sm border border-white/20 dark:border-gray-700/50">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">📊 สถิติการดู</h2>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                    <div class="text-center p-4 bg-gray-50 dark:bg-gray-700/50 rounded-xl">
                        <p class="text-3xl font-bold text-gray-900 dark:text-white">{{ $completion->watched_seconds ?? 0 }}</p>
                        <p class="text-sm text-gray-600 dark:text-gray-400">วินาที</p>
                    </div>
                    <div class="text-center p-4 bg-gray-50 dark:bg-gray-700/50 rounded-xl">
                        <p class="text-3xl font-bold text-gray-900 dark:text-white">{{ $completion->watch_percentage ?? 0 }}%</p>
                        <p class="text-sm text-gray-600 dark:text-gray-400">ดูไปแล้ว</p>
                    </div>
                    <div class="text-center p-4 bg-gray-50 dark:bg-gray-700/50 rounded-xl">
                        <p class="text-3xl font-bold text-gray-900 dark:text-white">{{ $completion->heartbeat_count ?? 0 }}</p>
                        <p class="text-sm text-gray-600 dark:text-gray-400">Heartbeats</p>
                    </div>
                    <div class="text-center p-4 bg-gray-50 dark:bg-gray-700/50 rounded-xl">
                        <p class="text-3xl font-bold {{ ($completion->tab_switch_count ?? 0) > 3 ? 'text-red-600' : 'text-gray-900 dark:text-white' }}">{{ $completion->tab_switch_count ?? 0 }}</p>
                        <p class="text-sm text-gray-600 dark:text-gray-400">Tab Switches</p>
                    </div>
                </div>
            </div>

            {{-- Anti-Cheat Details --}}
            <div class="glass-fusion dark:bg-gray-800/50 rounded-2xl shadow-lg p-6 backdrop-blur-sm border border-white/20 dark:border-gray-700/50">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">🛡️ Anti-Cheat Details</h2>
                <div class="grid md:grid-cols-2 gap-4">
                    <div class="space-y-3">
                        <div class="flex justify-between items-center py-2 border-b border-gray-200 dark:border-gray-700">
                            <span class="text-gray-600 dark:text-gray-400">Focus Lost Count</span>
                            <span class="font-medium text-gray-900 dark:text-white">{{ $completion->focus_lost_count ?? 0 }}</span>
                        </div>
                        <div class="flex justify-between items-center py-2 border-b border-gray-200 dark:border-gray-700">
                            <span class="text-gray-600 dark:text-gray-400">Seek Count</span>
                            <span class="font-medium text-gray-900 dark:text-white">{{ $completion->seek_count ?? 0 }}</span>
                        </div>
                        <div class="flex justify-between items-center py-2 border-b border-gray-200 dark:border-gray-700">
                            <span class="text-gray-600 dark:text-gray-400">Pause Count</span>
                            <span class="font-medium text-gray-900 dark:text-white">{{ $completion->pause_count ?? 0 }}</span>
                        </div>
                        <div class="flex justify-between items-center py-2 border-b border-gray-200 dark:border-gray-700">
                            <span class="text-gray-600 dark:text-gray-400">Speed Changes</span>
                            <span class="font-medium text-gray-900 dark:text-white">{{ $completion->speed_change_count ?? 0 }}</span>
                        </div>
                    </div>
                    <div class="space-y-3">
                        <div class="flex justify-between items-center py-2 border-b border-gray-200 dark:border-gray-700">
                            <span class="text-gray-600 dark:text-gray-400">Suspicious Score</span>
                            <span class="font-medium {{ ($completion->suspicious_score ?? 0) > 50 ? 'text-red-600' : 'text-gray-900 dark:text-white' }}">{{ $completion->suspicious_score ?? 0 }}</span>
                        </div>
                        <div class="flex justify-between items-center py-2 border-b border-gray-200 dark:border-gray-700">
                            <span class="text-gray-600 dark:text-gray-400">IP Address</span>
                            <span class="font-medium text-gray-900 dark:text-white">{{ $completion->ip_address ?? '-' }}</span>
                        </div>
                        <div class="flex justify-between items-center py-2 border-b border-gray-200 dark:border-gray-700">
                            <span class="text-gray-600 dark:text-gray-400">Device</span>
                            <span class="font-medium text-gray-900 dark:text-white text-sm">{{ Str::limit($completion->device_info ?? '-', 30) }}</span>
                        </div>
                    </div>
                </div>

                @if($completion->watch_segments)
                <div class="mt-4">
                    <h3 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Watch Segments</h3>
                    <pre class="bg-gray-100 dark:bg-gray-900 rounded-lg p-3 text-xs overflow-x-auto">{{ json_encode($completion->watch_segments, JSON_PRETTY_PRINT) }}</pre>
                </div>
                @endif
            </div>

            {{-- Reward Info --}}
            @if($completion->reward_given)
            <div class="glass-fusion dark:bg-gray-800/50 rounded-2xl shadow-lg p-6 backdrop-blur-sm border border-white/20 dark:border-gray-700/50">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">🎁 รางวัลที่ได้รับ</h2>
                <div class="flex flex-wrap gap-4">
                    @if($completion->reward_money > 0)
                    <div class="px-4 py-2 bg-green-100 dark:bg-green-900/50 text-green-800 dark:text-green-200 rounded-lg">
                        💵 ฿{{ number_format($completion->reward_money, 2) }}
                    </div>
                    @endif
                    @if($completion->reward_coins > 0)
                    <div class="px-4 py-2 bg-yellow-100 dark:bg-yellow-900/50 text-yellow-800 dark:text-yellow-200 rounded-lg">
                        🪙 {{ number_format($completion->reward_coins) }} Coins
                    </div>
                    @endif
                    @if($completion->reward_exp > 0)
                    <div class="px-4 py-2 bg-purple-100 dark:bg-purple-900/50 text-purple-800 dark:text-purple-200 rounded-lg">
                        📈 {{ number_format($completion->reward_exp) }} EXP
                    </div>
                    @endif
                </div>
            </div>
            @endif
        </div>

        {{-- Sidebar --}}
        <div class="space-y-6">
            {{-- User Info --}}
            <div class="glass-fusion dark:bg-gray-800/50 rounded-2xl shadow-lg p-6 backdrop-blur-sm border border-white/20 dark:border-gray-700/50">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">👤 ผู้ใช้</h2>
                <div class="flex items-center gap-4 mb-4">
                    <div class="w-16 h-16 bg-gradient-to-br from-pink-500 to-purple-500 rounded-full flex items-center justify-center text-2xl text-white">
                        {{ substr($completion->user->name ?? '?', 0, 1) }}
                    </div>
                    <div>
                        <p class="font-semibold text-gray-900 dark:text-white">{{ $completion->user->name ?? 'Unknown' }}</p>
                        <p class="text-sm text-gray-600 dark:text-gray-400">{{ $completion->user->email ?? '' }}</p>
                    </div>
                </div>
                @if($completion->user)
                <div class="space-y-2 text-sm">
                    <p class="text-gray-600 dark:text-gray-400">
                        Rank: <span class="font-medium text-gray-900 dark:text-white">{{ $completion->user->rank->name ?? 'ไม่มี Rank' }}</span>
                    </p>
                    <p class="text-gray-600 dark:text-gray-400">
                        สมัครเมื่อ: <span class="font-medium text-gray-900 dark:text-white">{{ $completion->user->created_at->format('d/m/Y') }}</span>
                    </p>
                </div>
                @endif
            </div>

            {{-- Mission Info --}}
            <div class="glass-fusion dark:bg-gray-800/50 rounded-2xl shadow-lg p-6 backdrop-blur-sm border border-white/20 dark:border-gray-700/50">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">🎬 ภารกิจ</h2>
                @if($completion->mission)
                <div class="flex items-center gap-3 mb-4">
                    <span class="text-3xl">{{ $completion->mission->icon ?? '🎬' }}</span>
                    <div>
                        <p class="font-medium text-gray-900 dark:text-white">{{ $completion->mission->display_title }}</p>
                        <p class="text-sm text-gray-600 dark:text-gray-400">ต้องดู {{ $completion->mission->required_watch_seconds }} วินาที</p>
                    </div>
                </div>
                <a href="{{ route('admin.video-missions.show', $completion->mission) }}"
                   class="block w-full text-center px-4 py-2 bg-gray-200 dark:bg-gray-700 hover:bg-gray-300 dark:hover:bg-gray-600 text-gray-800 dark:text-white rounded-lg font-medium transition">
                    ดูภารกิจ
                </a>
                @else
                <p class="text-gray-500 dark:text-gray-400">ภารกิจถูกลบไปแล้ว</p>
                @endif
            </div>

            {{-- Timeline --}}
            <div class="glass-fusion dark:bg-gray-800/50 rounded-2xl shadow-lg p-6 backdrop-blur-sm border border-white/20 dark:border-gray-700/50">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">📅 Timeline</h2>
                <div class="space-y-4 text-sm">
                    <div class="flex items-start gap-3">
                        <span class="text-blue-500">🔵</span>
                        <div>
                            <p class="text-gray-900 dark:text-white">เริ่มดู</p>
                            <p class="text-gray-500 dark:text-gray-400">{{ $completion->created_at->format('d/m/Y H:i:s') }}</p>
                        </div>
                    </div>
                    @if($completion->completed_at)
                    <div class="flex items-start gap-3">
                        <span class="text-green-500">🟢</span>
                        <div>
                            <p class="text-gray-900 dark:text-white">ทำเสร็จ</p>
                            <p class="text-gray-500 dark:text-gray-400">{{ \Carbon\Carbon::parse($completion->completed_at)->format('d/m/Y H:i:s') }}</p>
                        </div>
                    </div>
                    @endif
                    @if($completion->verified_at)
                    <div class="flex items-start gap-3">
                        <span class="text-green-500">✅</span>
                        <div>
                            <p class="text-gray-900 dark:text-white">ยืนยันโดย {{ $completion->verifier->name ?? 'System' }}</p>
                            <p class="text-gray-500 dark:text-gray-400">{{ \Carbon\Carbon::parse($completion->verified_at)->format('d/m/Y H:i:s') }}</p>
                        </div>
                    </div>
                    @endif
                    @if($completion->rejected_at)
                    <div class="flex items-start gap-3">
                        <span class="text-red-500">❌</span>
                        <div>
                            <p class="text-gray-900 dark:text-white">ปฏิเสธโดย {{ $completion->rejecter->name ?? 'System' }}</p>
                            <p class="text-gray-500 dark:text-gray-400">{{ \Carbon\Carbon::parse($completion->rejected_at)->format('d/m/Y H:i:s') }}</p>
                            @if($completion->rejection_reason)
                            <p class="text-red-600 dark:text-red-400">เหตุผล: {{ $completion->rejection_reason }}</p>
                            @endif
                        </div>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Reject Modal --}}
<div id="rejectModal" class="fixed inset-0 bg-black/50 hidden z-50 flex items-center justify-center">
    <div class="bg-white dark:bg-gray-800 rounded-2xl p-6 w-full max-w-md mx-4">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">ปฏิเสธการทำภารกิจ</h3>
        <form action="{{ route('admin.video-missions.completion.reject', $completion) }}" method="POST">
            @csrf
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">เหตุผล <span class="text-red-500">*</span></label>
                <textarea name="reason" rows="3" required
                          class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-pink-500"
                          placeholder="ระบุเหตุผลในการปฏิเสธ..."></textarea>
            </div>
            <div class="flex gap-3">
                <button type="button" onclick="hideRejectModal()"
                        class="flex-1 px-4 py-2 bg-gray-200 dark:bg-gray-700 text-gray-800 dark:text-white rounded-lg font-medium hover:bg-gray-300 dark:hover:bg-gray-600 transition">
                    ยกเลิก
                </button>
                <button type="submit"
                        class="flex-1 px-4 py-2 bg-red-600 text-white rounded-lg font-medium hover:bg-red-700 transition">
                    ปฏิเสธ
                </button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
function showRejectModal() {
    document.getElementById('rejectModal').classList.remove('hidden');
}
function hideRejectModal() {
    document.getElementById('rejectModal').classList.add('hidden');
}
</script>
@endpush
@endsection
