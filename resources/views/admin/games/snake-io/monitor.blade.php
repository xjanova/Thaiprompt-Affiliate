@extends('layouts.admin')

@section('title', 'Snake.io Service Monitor')

@section('content')
<div class="container-fluid py-4">
    {{-- Header with Service Control --}}
    <div class="row mb-4">
        <div class="col-12">
            <div class="card bg-gradient-dark dark:bg-gray-800">
                <div class="card-body p-4">
                    <div class="row align-items-center">
                        <div class="col-md-6">
                            <h2 class="text-white mb-0">
                                🐍 Snake.io Service Monitor
                            </h2>
                            <p class="text-white-50 mb-0">
                                Real-time multiplayer service monitoring
                            </p>
                        </div>
                        <div class="col-md-6 text-md-end">
                            {{-- Service Status Badge --}}
                            <span id="service-status-badge" class="badge badge-lg
                                {{ $status['is_online'] ? 'bg-success' : 'bg-secondary' }} me-2">
                                <i class="fas {{ $status['is_online'] ? 'fa-circle' : 'fa-stop-circle' }} me-1"></i>
                                <span id="status-text">{{ $status['is_online'] ? 'ONLINE' : 'OFFLINE' }}</span>
                            </span>

                            {{-- Start/Stop Buttons --}}
                            @if($status['is_online'])
                                <button onclick="stopService()" class="btn btn-warning btn-lg">
                                    <i class="fas fa-stop me-1"></i> Stop Service
                                </button>
                            @else
                                <button onclick="startService()" class="btn btn-success btn-lg">
                                    <i class="fas fa-play me-1"></i> Start Service
                                </button>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Stats Cards --}}
    <div class="row mb-4">
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="card dark:bg-gray-800">
                <div class="card-body">
                    <div class="d-flex">
                        <div class="flex-shrink-0">
                            <div class="avatar avatar-lg bg-gradient-primary rounded-3">
                                <i class="fas fa-users text-white"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h6 class="text-muted mb-1">Online Players</h6>
                            <h3 class="mb-0 dark:text-white" id="total-players">{{ $status['total_players'] }}</h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-3 col-md-6 mb-3">
            <div class="card dark:bg-gray-800">
                <div class="card-body">
                    <div class="d-flex">
                        <div class="flex-shrink-0">
                            <div class="avatar avatar-lg bg-gradient-success rounded-3">
                                <i class="fas fa-door-open text-white"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h6 class="text-muted mb-1">Active Rooms</h6>
                            <h3 class="mb-0 dark:text-white" id="total-rooms">{{ $status['total_rooms'] }}</h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-3 col-md-6 mb-3">
            <div class="card dark:bg-gray-800">
                <div class="card-body">
                    <div class="d-flex">
                        <div class="flex-shrink-0">
                            <div class="avatar avatar-lg bg-gradient-warning rounded-3">
                                <i class="fas fa-exclamation-triangle text-white"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h6 class="text-muted mb-1">Suspicious Activities</h6>
                            <h3 class="mb-0 dark:text-white" id="total-suspicious">{{ count($suspiciousActivities) }}</h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-3 col-md-6 mb-3">
            <div class="card dark:bg-gray-800">
                <div class="card-body">
                    <div class="d-flex">
                        <div class="flex-shrink-0">
                            <div class="avatar avatar-lg bg-gradient-info rounded-3">
                                <i class="fas fa-trophy text-white"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h6 class="text-muted mb-1">Top Score</h6>
                            <h3 class="mb-0 dark:text-white">{{ $topScores->first()->score ?? 0 }}</h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Main Content --}}
    <div class="row">
        {{-- Online Players --}}
        <div class="col-lg-6 mb-4">
            <div class="card dark:bg-gray-800">
                <div class="card-header bg-transparent">
                    <h5 class="mb-0 dark:text-white">
                        <i class="fas fa-users text-primary me-2"></i>
                        Online Players
                    </h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive" style="max-height: 400px; overflow-y: auto;">
                        <table class="table table-sm" id="players-table">
                            <thead>
                                <tr class="dark:text-gray-300">
                                    <th>Username</th>
                                    <th>IP Address</th>
                                    <th>Room</th>
                                    <th>Score</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody id="players-body">
                                @forelse($onlinePlayers as $player)
                                <tr class="dark:text-gray-400">
                                    <td>{{ $player['username'] }}</td>
                                    <td><code>{{ $player['ip_address'] }}</code></td>
                                    <td>{{ $player['room_id'] ?? '-' }}</td>
                                    <td>{{ $player['score'] }}</td>
                                    <td>
                                        <button class="btn btn-sm btn-danger" onclick="kickPlayer({{ $player['user_id'] }})">
                                            <i class="fas fa-times"></i> Kick
                                        </button>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="5" class="text-center text-muted">
                                        No online players
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        {{-- Active Rooms --}}
        <div class="col-lg-6 mb-4">
            <div class="card dark:bg-gray-800">
                <div class="card-header bg-transparent">
                    <h5 class="mb-0 dark:text-white">
                        <i class="fas fa-door-open text-success me-2"></i>
                        Active Rooms
                    </h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive" style="max-height: 400px; overflow-y: auto;">
                        <table class="table table-sm" id="rooms-table">
                            <thead>
                                <tr class="dark:text-gray-300">
                                    <th>Room ID</th>
                                    <th>Name</th>
                                    <th>Players</th>
                                    <th>Created</th>
                                </tr>
                            </thead>
                            <tbody id="rooms-body">
                                @forelse($rooms as $room)
                                <tr class="dark:text-gray-400">
                                    <td><code>{{ Str::limit($room['room_id'], 15) }}</code></td>
                                    <td>{{ $room['name'] }}</td>
                                    <td>
                                        <span class="badge bg-primary">
                                            {{ $room['current_players'] }}/{{ $room['max_players'] }}
                                        </span>
                                    </td>
                                    <td>{{ \Carbon\Carbon::parse($room['created_at'])->diffForHumans() }}</td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="4" class="text-center text-muted">
                                        No active rooms
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        {{-- Top Scores --}}
        <div class="col-lg-6 mb-4">
            <div class="card dark:bg-gray-800">
                <div class="card-header bg-transparent">
                    <h5 class="mb-0 dark:text-white">
                        <i class="fas fa-trophy text-warning me-2"></i>
                        Top Scores (Leaderboard)
                    </h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive" style="max-height: 400px; overflow-y: auto;">
                        <table class="table table-sm">
                            <thead>
                                <tr class="dark:text-gray-300">
                                    <th>#</th>
                                    <th>Player</th>
                                    <th>Score</th>
                                    <th>Length</th>
                                    <th>Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($topScores as $index => $entry)
                                <tr class="dark:text-gray-400">
                                    <td>
                                        @if($index === 0)
                                            <i class="fas fa-crown text-warning"></i>
                                        @else
                                            {{ $index + 1 }}
                                        @endif
                                    </td>
                                    <td>{{ $entry->user->name ?? 'Unknown' }}</td>
                                    <td><strong>{{ number_format($entry->score) }}</strong></td>
                                    <td>{{ $entry->wave_reached }} ข้อ</td>
                                    <td>{{ $entry->created_at->diffForHumans() }}</td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="5" class="text-center text-muted">
                                        No scores yet
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        {{-- Suspicious Activities --}}
        <div class="col-lg-6 mb-4">
            <div class="card dark:bg-gray-800">
                <div class="card-header bg-transparent d-flex justify-content-between align-items-center">
                    <h5 class="mb-0 dark:text-white">
                        <i class="fas fa-exclamation-triangle text-danger me-2"></i>
                        Suspicious Activities
                    </h5>
                    <button class="btn btn-sm btn-outline-danger" onclick="clearSuspicious()">
                        <i class="fas fa-trash"></i> Clear
                    </button>
                </div>
                <div class="card-body">
                    <div class="table-responsive" style="max-height: 400px; overflow-y: auto;">
                        <table class="table table-sm" id="suspicious-table">
                            <thead>
                                <tr class="dark:text-gray-300">
                                    <th>User ID</th>
                                    <th>Type</th>
                                    <th>Details</th>
                                    <th>Time</th>
                                </tr>
                            </thead>
                            <tbody id="suspicious-body">
                                @forelse($suspiciousActivities as $activity)
                                <tr class="dark:text-gray-400">
                                    <td>{{ $activity['user_id'] }}</td>
                                    <td>
                                        <span class="badge bg-warning">{{ $activity['type'] }}</span>
                                    </td>
                                    <td>
                                        <small>{{ json_encode($activity['data']) }}</small>
                                    </td>
                                    <td>{{ \Carbon\Carbon::parse($activity['timestamp'])->diffForHumans() }}</td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="4" class="text-center text-muted">
                                        No suspicious activities
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
/**
 * Auto-refresh ทุก 5 วินาที
 */
let refreshInterval;

function startAutoRefresh() {
    refreshInterval = setInterval(refreshData, 5000);
}

function stopAutoRefresh() {
    if (refreshInterval) {
        clearInterval(refreshInterval);
    }
}

/**
 * ดึงข้อมูลใหม่จาก API
 */
async function refreshData() {
    try {
        const response = await fetch('/api/admin/games/snake-io/status', {
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json',
            }
        });

        const data = await response.json();

        if (data.success) {
            // อัพเดท stats
            document.getElementById('total-players').textContent = data.status.total_players;
            document.getElementById('total-rooms').textContent = data.status.total_rooms;

            // อัพเดท status badge
            updateStatusBadge(data.status.is_online);

            // อัพเดท tables
            updatePlayersTable(data.players);
            updateRoomsTable(data.rooms);
        }
    } catch (error) {
        console.error('Failed to refresh data:', error);
    }
}

/**
 * อัพเดท status badge
 */
function updateStatusBadge(isOnline) {
    const badge = document.getElementById('service-status-badge');
    const statusText = document.getElementById('status-text');

    if (isOnline) {
        badge.className = 'badge badge-lg bg-success me-2';
        badge.querySelector('i').className = 'fas fa-circle me-1';
        statusText.textContent = 'ONLINE';
    } else {
        badge.className = 'badge badge-lg bg-secondary me-2';
        badge.querySelector('i').className = 'fas fa-stop-circle me-1';
        statusText.textContent = 'OFFLINE';
    }
}

/**
 * อัพเดทตารางผู้เล่น
 */
function updatePlayersTable(players) {
    const tbody = document.getElementById('players-body');

    if (players.length === 0) {
        tbody.innerHTML = '<tr><td colspan="5" class="text-center text-muted">No online players</td></tr>';
        return;
    }

    tbody.innerHTML = players.map(player => `
        <tr class="dark:text-gray-400">
            <td>${player.username}</td>
            <td><code>${player.ip_address}</code></td>
            <td>${player.room_id || '-'}</td>
            <td>${player.score}</td>
            <td>
                <button class="btn btn-sm btn-danger" onclick="kickPlayer(${player.user_id})">
                    <i class="fas fa-times"></i> Kick
                </button>
            </td>
        </tr>
    `).join('');
}

/**
 * อัพเดทตารางห้อง
 */
function updateRoomsTable(rooms) {
    const tbody = document.getElementById('rooms-body');

    if (rooms.length === 0) {
        tbody.innerHTML = '<tr><td colspan="4" class="text-center text-muted">No active rooms</td></tr>';
        return;
    }

    tbody.innerHTML = rooms.map(room => `
        <tr class="dark:text-gray-400">
            <td><code>${room.room_id.substring(0, 15)}</code></td>
            <td>${room.name}</td>
            <td>
                <span class="badge bg-primary">
                    ${room.current_players}/${room.max_players}
                </span>
            </td>
            <td>${timeAgo(room.created_at)}</td>
        </tr>
    `).join('');
}

/**
 * Start service
 */
async function startService() {
    if (!confirm('Start multiplayer service?')) return;

    try {
        const response = await fetch('/api/admin/games/snake-io/start', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json',
            }
        });

        const data = await response.json();

        if (data.success) {
            alert(data.message);
            location.reload();
        }
    } catch (error) {
        alert('Failed to start service: ' + error.message);
    }
}

/**
 * Stop service
 */
async function stopService() {
    if (!confirm('Stop multiplayer service? Players will switch to offline mode.')) return;

    try {
        const response = await fetch('/api/admin/games/snake-io/stop', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json',
            }
        });

        const data = await response.json();

        if (data.success) {
            alert(data.message);
            location.reload();
        }
    } catch (error) {
        alert('Failed to stop service: ' + error.message);
    }
}

/**
 * Kick player
 */
async function kickPlayer(userId) {
    if (!confirm(`Kick player ${userId}?`)) return;

    try {
        const response = await fetch(`/api/admin/games/snake-io/kick/${userId}`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json',
            }
        });

        const data = await response.json();

        if (data.success) {
            alert(data.message);
            refreshData();
        }
    } catch (error) {
        alert('Failed to kick player: ' + error.message);
    }
}

/**
 * Helper: time ago
 */
function timeAgo(timestamp) {
    const seconds = Math.floor((new Date() - new Date(timestamp)) / 1000);

    if (seconds < 60) return `${seconds}s ago`;
    if (seconds < 3600) return `${Math.floor(seconds / 60)}m ago`;
    if (seconds < 86400) return `${Math.floor(seconds / 3600)}h ago`;
    return `${Math.floor(seconds / 86400)}d ago`;
}

// Start auto-refresh on page load
document.addEventListener('DOMContentLoaded', () => {
    startAutoRefresh();
});

// Stop auto-refresh when leaving page
window.addEventListener('beforeunload', () => {
    stopAutoRefresh();
});
</script>
@endsection
