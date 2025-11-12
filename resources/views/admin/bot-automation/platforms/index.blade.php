@extends('layouts.admin')

@section('title', 'Bot Platforms')

@section('content')
<div class="container-fluid px-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-gray-800">Bot Platforms</h1>
        <a href="{{ route('admin.bot-automation.platforms.connect') }}" class="btn btn-primary">
            <i class="fas fa-plug mr-2"></i>Connect New Platform
        </a>
    </div>

    <div class="row mb-4">
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Total Platforms</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $totalPlatforms ?? '0' }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-layer-group fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-success shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Connected</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $connectedPlatforms ?? '0' }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-check-circle fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-warning shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Pending</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $pendingPlatforms ?? '0' }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-clock fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-info shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Total Messages</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ number_format($totalMessages ?? 0) }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-comments fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Connected Platforms</h6>
        </div>
        <div class="card-body">
            <div class="row">
                @forelse($platforms ?? [] as $platform)
                <div class="col-md-4 mb-4">
                    <div class="card h-100 border-left-{{ $platform->status == 'connected' ? 'success' : 'secondary' }}">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start mb-3">
                                <div>
                                    <h5 class="card-title mb-1">
                                        <i class="fab fa-{{ strtolower($platform->name ?? '') }} mr-2"></i>
                                        {{ $platform->name ?? 'N/A' }}
                                    </h5>
                                    <span class="badge badge-{{ $platform->status == 'connected' ? 'success' : 'secondary' }}">
                                        {{ ucfirst($platform->status ?? 'N/A') }}
                                    </span>
                                </div>
                                <div class="dropdown">
                                    <button class="btn btn-sm btn-light dropdown-toggle" type="button" id="dropdownMenu{{ $platform->id }}" data-toggle="dropdown">
                                        <i class="fas fa-ellipsis-v"></i>
                                    </button>
                                    <div class="dropdown-menu dropdown-menu-right">
                                        <a class="dropdown-item" href="#" onclick="editPlatform({{ $platform->id }})">
                                            <i class="fas fa-edit mr-2"></i>Edit
                                        </a>
                                        <a class="dropdown-item" href="#" onclick="viewSettings({{ $platform->id }})">
                                            <i class="fas fa-cog mr-2"></i>Settings
                                        </a>
                                        <div class="dropdown-divider"></div>
                                        @if($platform->status == 'connected')
                                        <a class="dropdown-item text-warning" href="#" onclick="disconnectPlatform({{ $platform->id }})">
                                            <i class="fas fa-unlink mr-2"></i>Disconnect
                                        </a>
                                        @else
                                        <a class="dropdown-item text-success" href="#" onclick="connectPlatform({{ $platform->id }})">
                                            <i class="fas fa-plug mr-2"></i>Connect
                                        </a>
                                        @endif
                                        <div class="dropdown-divider"></div>
                                        <a class="dropdown-item text-danger" href="#" onclick="deletePlatform({{ $platform->id }})">
                                            <i class="fas fa-trash mr-2"></i>Delete
                                        </a>
                                    </div>
                                </div>
                            </div>

                            <p class="card-text text-muted small">{{ $platform->description ?? 'No description available' }}</p>

                            <div class="mt-3">
                                <div class="row text-center">
                                    <div class="col-4">
                                        <small class="text-muted d-block">Bots</small>
                                        <strong>{{ $platform->bots_count ?? '0' }}</strong>
                                    </div>
                                    <div class="col-4">
                                        <small class="text-muted d-block">Messages</small>
                                        <strong>{{ number_format($platform->messages_count ?? 0) }}</strong>
                                    </div>
                                    <div class="col-4">
                                        <small class="text-muted d-block">Users</small>
                                        <strong>{{ $platform->users_count ?? '0' }}</strong>
                                    </div>
                                </div>
                            </div>

                            @if(isset($platform->last_sync))
                            <div class="mt-3 pt-3 border-top">
                                <small class="text-muted">
                                    <i class="fas fa-sync-alt mr-1"></i>
                                    Last synced: {{ $platform->last_sync->diffForHumans() }}
                                </small>
                            </div>
                            @endif
                        </div>
                    </div>
                </div>
                @empty
                <div class="col-12 text-center text-muted py-5">
                    <i class="fas fa-layer-group fa-3x mb-3"></i>
                    <p>No platforms connected yet. Connect your first platform to get started!</p>
                    <a href="{{ route('admin.bot-automation.platforms.connect') }}" class="btn btn-primary">
                        <i class="fas fa-plug mr-2"></i>Connect Platform
                    </a>
                </div>
                @endforelse
            </div>
        </div>
    </div>

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Available Platforms</h6>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-3 mb-3">
                    <div class="card text-center">
                        <div class="card-body">
                            <i class="fab fa-facebook-messenger fa-3x text-primary mb-2"></i>
                            <h6>Facebook Messenger</h6>
                            <small class="text-muted">Chat platform</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 mb-3">
                    <div class="card text-center">
                        <div class="card-body">
                            <i class="fab fa-line fa-3x text-success mb-2"></i>
                            <h6>LINE</h6>
                            <small class="text-muted">Messaging app</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 mb-3">
                    <div class="card text-center">
                        <div class="card-body">
                            <i class="fab fa-telegram fa-3x text-info mb-2"></i>
                            <h6>Telegram</h6>
                            <small class="text-muted">Messaging app</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 mb-3">
                    <div class="card text-center">
                        <div class="card-body">
                            <i class="fab fa-slack fa-3x text-danger mb-2"></i>
                            <h6>Slack</h6>
                            <small class="text-muted">Team communication</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function editPlatform(id) {
    console.log('Editing platform:', id);
}

function viewSettings(id) {
    console.log('Viewing settings for platform:', id);
}

function connectPlatform(id) {
    if (confirm('Connect this platform?')) {
        console.log('Connecting platform:', id);
    }
}

function disconnectPlatform(id) {
    if (confirm('Are you sure you want to disconnect this platform?')) {
        console.log('Disconnecting platform:', id);
    }
}

function deletePlatform(id) {
    if (confirm('Are you sure you want to delete this platform? This action cannot be undone.')) {
        console.log('Deleting platform:', id);
    }
}
</script>
@endsection
