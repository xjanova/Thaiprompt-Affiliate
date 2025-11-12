@extends('layouts.admin')

@section('title', 'Deployment Center')

@push('styles')
<style>
    /* Modern Deployment Center Styles */
    .deployment-header {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        border-radius: 15px;
        padding: 2rem;
        color: white;
        margin-bottom: 2rem;
        box-shadow: 0 10px 30px rgba(102, 126, 234, 0.3);
        position: relative;
        overflow: hidden;
    }

    .deployment-header::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%23ffffff' fill-opacity='0.05'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");
    }

    .deployment-header .header-content {
        position: relative;
        z-index: 1;
    }

    .deployment-header h1 {
        font-size: 2.5rem;
        font-weight: 700;
        margin-bottom: 0.5rem;
        text-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }

    .deployment-header .subtitle {
        font-size: 1.1rem;
        opacity: 0.95;
        font-weight: 300;
    }

    .deployment-card {
        border: none;
        border-radius: 15px;
        box-shadow: 0 5px 20px rgba(0,0,0,0.08);
        transition: all 0.3s ease;
        overflow: hidden;
    }

    .deployment-card:hover {
        box-shadow: 0 8px 30px rgba(0,0,0,0.12);
        transform: translateY(-2px);
    }

    .card-header-custom {
        background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
        border: none;
        padding: 1.25rem 1.5rem;
        font-weight: 600;
        color: #2d3748;
    }

    .card-header-custom i {
        margin-right: 0.5rem;
    }

    .git-info-badge {
        display: inline-flex;
        align-items: center;
        padding: 0.5rem 1rem;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        border-radius: 50px;
        font-weight: 600;
        box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
        transition: all 0.3s ease;
    }

    .git-info-badge:hover {
        transform: scale(1.05);
        box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4);
    }

    .commit-code {
        background: linear-gradient(135deg, #2d3748 0%, #1a202c 100%);
        color: #68d391;
        padding: 0.4rem 0.8rem;
        border-radius: 8px;
        font-family: 'Monaco', 'Menlo', 'Consolas', monospace;
        font-size: 0.9rem;
        font-weight: 600;
        box-shadow: 0 2px 10px rgba(0,0,0,0.2);
    }

    .status-badge-modern {
        padding: 0.5rem 1rem;
        border-radius: 50px;
        font-weight: 600;
        font-size: 0.85rem;
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    }

    .status-badge-modern.success {
        background: linear-gradient(135deg, #48bb78 0%, #38a169 100%);
        color: white;
    }

    .status-badge-modern.info {
        background: linear-gradient(135deg, #4299e1 0%, #3182ce 100%);
        color: white;
    }

    .status-badge-modern.warning {
        background: linear-gradient(135deg, #ed8936 0%, #dd6b20 100%);
        color: white;
    }

    .status-badge-modern.danger {
        background: linear-gradient(135deg, #f56565 0%, #e53e3e 100%);
        color: white;
    }

    .btn-gradient-primary {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        border: none;
        color: white;
        padding: 0.75rem 2rem;
        border-radius: 50px;
        font-weight: 600;
        box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
        transition: all 0.3s ease;
        position: relative;
        overflow: hidden;
    }

    .btn-gradient-primary::before {
        content: '';
        position: absolute;
        top: 0;
        left: -100%;
        width: 100%;
        height: 100%;
        background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
        transition: left 0.5s ease;
    }

    .btn-gradient-primary:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(102, 126, 234, 0.5);
        color: white;
    }

    .btn-gradient-primary:hover::before {
        left: 100%;
    }

    .btn-gradient-success {
        background: linear-gradient(135deg, #48bb78 0%, #38a169 100%);
        border: none;
        color: white;
        padding: 0.75rem 2.5rem;
        border-radius: 50px;
        font-weight: 600;
        font-size: 1.1rem;
        box-shadow: 0 4px 15px rgba(72, 187, 120, 0.4);
        transition: all 0.3s ease;
    }

    .btn-gradient-success:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(72, 187, 120, 0.5);
        color: white;
    }

    .btn-gradient-danger {
        background: linear-gradient(135deg, #f56565 0%, #e53e3e 100%);
        border: none;
        color: white;
        padding: 0.6rem 1.5rem;
        border-radius: 50px;
        font-weight: 600;
        box-shadow: 0 4px 15px rgba(245, 101, 101, 0.4);
        transition: all 0.3s ease;
    }

    .btn-gradient-danger:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(245, 101, 101, 0.5);
        color: white;
    }

    .terminal-container {
        background: #1a1d24;
        border-radius: 15px;
        overflow: hidden;
        box-shadow: 0 10px 40px rgba(0,0,0,0.3);
    }

    .terminal-header {
        background: #2d3139;
        padding: 0.75rem 1rem;
        display: flex;
        align-items: center;
        justify-content: space-between;
        border-bottom: 1px solid #3d4149;
    }

    .terminal-buttons {
        display: flex;
        gap: 0.5rem;
    }

    .terminal-button {
        width: 12px;
        height: 12px;
        border-radius: 50%;
        transition: all 0.2s ease;
    }

    .terminal-button.close { background: #ff5f56; }
    .terminal-button.minimize { background: #ffbd2e; }
    .terminal-button.maximize { background: #27c93f; }

    .terminal-button:hover {
        transform: scale(1.2);
        box-shadow: 0 0 10px currentColor;
    }

    .terminal-title {
        color: #8b8e96;
        font-size: 0.85rem;
        font-weight: 600;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .terminal-body {
        background: #1a1d24;
        color: #68d391;
        padding: 1.5rem;
        height: 450px;
        overflow-y: auto;
        font-family: 'Monaco', 'Menlo', 'Consolas', monospace;
        font-size: 13px;
        line-height: 1.6;
    }

    .terminal-body::-webkit-scrollbar {
        width: 8px;
    }

    .terminal-body::-webkit-scrollbar-track {
        background: #0d0f12;
    }

    .terminal-body::-webkit-scrollbar-thumb {
        background: #3d4149;
        border-radius: 4px;
    }

    .terminal-body::-webkit-scrollbar-thumb:hover {
        background: #4d5159;
    }

    .log-line {
        margin-bottom: 0.25rem;
        animation: slideIn 0.3s ease;
    }

    @keyframes slideIn {
        from {
            opacity: 0;
            transform: translateX(-10px);
        }
        to {
            opacity: 1;
            transform: translateX(0);
        }
    }

    .terminal-prompt::before {
        content: '❯ ';
        color: #667eea;
        font-weight: bold;
    }

    .update-notification {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        border: none;
        border-radius: 15px;
        padding: 1.5rem;
        color: white;
        box-shadow: 0 5px 20px rgba(102, 126, 234, 0.3);
        animation: pulse 2s infinite;
    }

    @keyframes pulse {
        0%, 100% { transform: scale(1); }
        50% { transform: scale(1.02); }
    }

    .deployment-progress {
        background: linear-gradient(135deg, #4299e1 0%, #3182ce 100%);
        border: none;
        border-radius: 15px;
        padding: 1.5rem;
        color: white;
        box-shadow: 0 5px 20px rgba(66, 153, 225, 0.3);
    }

    .spinner-modern {
        display: inline-block;
        width: 20px;
        height: 20px;
        border: 3px solid rgba(255,255,255,0.3);
        border-radius: 50%;
        border-top-color: white;
        animation: spin 0.8s linear infinite;
    }

    @keyframes spin {
        to { transform: rotate(360deg); }
    }

    .deployment-history-table {
        border-collapse: separate;
        border-spacing: 0 0.5rem;
    }

    .deployment-history-table tbody tr {
        background: white;
        box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        transition: all 0.3s ease;
    }

    .deployment-history-table tbody tr:hover {
        box-shadow: 0 4px 20px rgba(0,0,0,0.1);
        transform: translateX(5px);
    }

    .deployment-history-table tbody td {
        padding: 1rem;
        vertical-align: middle;
        border: none;
    }

    .deployment-history-table tbody tr td:first-child {
        border-radius: 10px 0 0 10px;
    }

    .deployment-history-table tbody tr td:last-child {
        border-radius: 0 10px 10px 0;
    }

    .action-btn-group {
        display: flex;
        gap: 0.5rem;
    }

    .action-btn {
        padding: 0.5rem 1rem;
        border-radius: 8px;
        border: none;
        font-weight: 600;
        transition: all 0.3s ease;
        cursor: pointer;
    }

    .action-btn-primary {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        box-shadow: 0 2px 10px rgba(102, 126, 234, 0.3);
    }

    .action-btn-primary:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
    }

    .action-btn-warning {
        background: linear-gradient(135deg, #ed8936 0%, #dd6b20 100%);
        color: white;
        box-shadow: 0 2px 10px rgba(237, 137, 54, 0.3);
    }

    .action-btn-warning:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 15px rgba(237, 137, 54, 0.4);
    }

    .empty-state {
        text-align: center;
        padding: 4rem 2rem;
        color: #a0aec0;
    }

    .empty-state i {
        font-size: 5rem;
        margin-bottom: 1.5rem;
        opacity: 0.3;
    }

    .commit-list {
        background: rgba(255,255,255,0.1);
        border-radius: 10px;
        padding: 1rem;
        margin-top: 1rem;
    }

    .commit-list ul {
        list-style: none;
        padding: 0;
        margin: 0;
    }

    .commit-list li {
        padding: 0.5rem 0;
        border-bottom: 1px solid rgba(255,255,255,0.1);
    }

    .commit-list li:last-child {
        border-bottom: none;
    }

    .commit-list code {
        background: rgba(0,0,0,0.2);
        padding: 0.2rem 0.5rem;
        border-radius: 4px;
        margin-right: 0.5rem;
    }

    .duration-badge {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 0.4rem 0.8rem;
        border-radius: 50px;
        font-weight: 600;
        font-size: 0.85rem;
        box-shadow: 0 2px 10px rgba(102, 126, 234, 0.3);
    }

    .info-label {
        font-size: 0.75rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        color: #a0aec0;
        font-weight: 600;
        margin-bottom: 0.5rem;
    }

    .info-value {
        font-size: 1.1rem;
        font-weight: 600;
        color: #2d3748;
    }

    .git-branch-icon {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 32px;
        height: 32px;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        border-radius: 8px;
        color: white;
        margin-right: 0.75rem;
    }

    .alert-modern {
        border: none;
        border-radius: 15px;
        padding: 1.25rem 1.5rem;
        box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        animation: slideInDown 0.5s ease;
    }

    @keyframes slideInDown {
        from {
            opacity: 0;
            transform: translateY(-20px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .fade-in {
        animation: fadeIn 0.5s ease;
    }

    @keyframes fadeIn {
        from { opacity: 0; }
        to { opacity: 1; }
    }
</style>
@endpush

@section('content')
<div class="container-fluid">
    <!-- Modern Header -->
    <div class="deployment-header">
        <div class="header-content">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1 class="mb-2">
                        <i class="fas fa-rocket"></i> Deployment Center
                    </h1>
                    <p class="subtitle mb-0">
                        <i class="fas fa-shield-alt"></i> Deploy updates from GitHub with confidence
                    </p>
                </div>
                <div>
                    <button class="btn btn-light btn-lg" onclick="checkForUpdates()" style="border-radius: 50px; padding: 0.75rem 2rem;">
                        <i class="fas fa-sync-alt"></i> Check for Updates
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Alert Container -->
    <div id="alert-container"></div>

    <!-- Current Version Info -->
    <div class="row mb-4">
        <div class="col-lg-12">
            <div class="deployment-card">
                <div class="card-header-custom">
                    <i class="fas fa-code-branch"></i> Current Version Information
                </div>
                <div class="card-body p-4">
                    <div class="row align-items-center">
                        <div class="col-md-4">
                            <div class="d-flex align-items-center mb-3">
                                <div class="git-branch-icon">
                                    <i class="fas fa-code-branch"></i>
                                </div>
                                <div>
                                    <div class="info-label">Current Branch</div>
                                    <span class="git-info-badge">{{ $currentBranch }}</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <div class="info-label">Current Commit</div>
                                <span class="commit-code">{{ $currentCommitShort }}</span>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <div class="info-label">Commit Message</div>
                                <div class="info-value">{{ $currentCommitMessage ?: 'N/A' }}</div>
                            </div>
                        </div>
                    </div>

                    @if($lastDeployment)
                    <div class="border-top pt-3 mt-3">
                        <div class="info-label mb-2">
                            <i class="fas fa-history"></i> Last Successful Deployment
                        </div>
                        <div class="d-flex align-items-center flex-wrap gap-3">
                            <span class="status-badge-modern success">
                                <i class="fas fa-check-circle"></i> Success
                            </span>
                            <span>
                                <i class="far fa-clock"></i> {{ $lastDeployment->completed_at->diffForHumans() }}
                            </span>
                            @if($lastDeployment->startedBy)
                                <span>
                                    <i class="fas fa-user"></i> by <strong>{{ $lastDeployment->startedBy->name }}</strong>
                                </span>
                            @endif
                            <span class="duration-badge">
                                <i class="fas fa-stopwatch"></i> {{ $lastDeployment->duration_human }}
                            </span>
                        </div>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Deployment Actions -->
    <div class="row mb-4">
        <div class="col-lg-12">
            <div class="deployment-card">
                <div class="card-header-custom">
                    <i class="fas fa-tasks"></i> Deployment Actions
                </div>
                <div class="card-body p-4">
                    @if($isDeploying && $runningDeployment)
                        <!-- Deployment in Progress -->
                        <div class="deployment-progress">
                            <div class="d-flex align-items-center mb-3">
                                <div class="spinner-modern mr-3"></div>
                                <div>
                                    <h5 class="mb-1">
                                        <i class="fas fa-rocket"></i> Deployment in Progress
                                    </h5>
                                    <p class="mb-0 opacity-75">
                                        Started {{ $runningDeployment->started_at->diffForHumans() }}
                                        @if($runningDeployment->startedBy)
                                            by <strong>{{ $runningDeployment->startedBy->name }}</strong>
                                        @endif
                                    </p>
                                </div>
                            </div>
                            <div class="action-btn-group">
                                <button class="action-btn action-btn-primary" onclick="viewDeploymentLogs({{ $runningDeployment->id }})">
                                    <i class="fas fa-eye"></i> View Live Logs
                                </button>
                                <button class="action-btn action-btn-warning" onclick="cancelDeployment({{ $runningDeployment->id }})">
                                    <i class="fas fa-stop-circle"></i> Cancel Deployment
                                </button>
                            </div>
                        </div>
                    @else
                        <!-- Deploy Section -->
                        <div class="text-center py-3">
                            <p class="text-muted mb-4" style="font-size: 1.05rem;">
                                <i class="fas fa-info-circle"></i> Deploy the latest changes from the <strong>{{ $currentBranch }}</strong> branch.
                                <br>The deployment script will update your application automatically.
                            </p>

                            <!-- Update Info (Hidden by default) -->
                            <div id="update-info" class="update-notification d-none mb-4 text-left">
                                <h5 class="mb-3">
                                    <i class="fas fa-bell"></i> Updates Available!
                                </h5>
                                <div id="update-details"></div>
                            </div>

                            <button class="btn btn-gradient-success btn-lg" onclick="startDeployment()" id="deploy-btn">
                                <i class="fas fa-rocket"></i> Deploy Now
                            </button>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Deployment Logs Terminal -->
    <div class="row mb-4 d-none fade-in" id="logs-container">
        <div class="col-lg-12">
            <div class="deployment-card">
                <div class="terminal-container">
                    <div class="terminal-header">
                        <div class="terminal-buttons">
                            <div class="terminal-button close"></div>
                            <div class="terminal-button minimize"></div>
                            <div class="terminal-button maximize"></div>
                        </div>
                        <div class="terminal-title">
                            <i class="fas fa-terminal"></i>
                            <span>deployment.log</span>
                        </div>
                        <div>
                            <button class="btn btn-sm text-light" onclick="clearLogs()" style="opacity: 0.7;">
                                <i class="fas fa-eraser"></i> Clear
                            </button>
                        </div>
                    </div>
                    <div class="terminal-body" id="deployment-logs">
                        <div class="text-center text-muted py-5">
                            <i class="fas fa-terminal fa-3x mb-3" style="opacity: 0.3;"></i>
                            <p>Waiting for deployment to start...</p>
                        </div>
                    </div>
                    <div class="terminal-header" style="border-top: 1px solid #3d4149;">
                        <div id="deployment-status" class="d-flex justify-content-between align-items-center w-100">
                            <span class="text-light">
                                <i class="fas fa-circle text-success"></i> Ready
                            </span>
                            <span id="deployment-duration" class="duration-badge">
                                <i class="fas fa-clock"></i> 0s
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Deployment History -->
    <div class="row">
        <div class="col-lg-12">
            <div class="deployment-card">
                <div class="card-header-custom">
                    <i class="fas fa-history"></i> Deployment History
                </div>
                <div class="card-body">
                    @if($recentDeployments->count() > 0)
                        <div class="table-responsive">
                            <table class="table deployment-history-table">
                                <thead>
                                    <tr style="color: #718096;">
                                        <th><i class="fas fa-flag"></i> Status</th>
                                        <th><i class="fas fa-code-branch"></i> Branch</th>
                                        <th><i class="fas fa-code-commit"></i> Commit</th>
                                        <th><i class="fas fa-user"></i> Started By</th>
                                        <th><i class="far fa-calendar"></i> Started At</th>
                                        <th><i class="fas fa-stopwatch"></i> Duration</th>
                                        <th><i class="fas fa-cog"></i> Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($recentDeployments as $deployment)
                                    <tr>
                                        <td>
                                            @php
                                                $statusClass = match($deployment->status) {
                                                    'completed' => 'success',
                                                    'running' => 'info',
                                                    'failed' => 'danger',
                                                    'cancelled' => 'warning',
                                                    default => 'secondary'
                                                };
                                                $statusIcon = match($deployment->status) {
                                                    'completed' => 'check-circle',
                                                    'running' => 'spinner fa-spin',
                                                    'failed' => 'times-circle',
                                                    'cancelled' => 'ban',
                                                    default => 'circle'
                                                };
                                            @endphp
                                            <span class="status-badge-modern {{ $statusClass }}">
                                                <i class="fas fa-{{ $statusIcon }}"></i> {{ ucfirst($deployment->status) }}
                                            </span>
                                        </td>
                                        <td>
                                            <span class="git-info-badge" style="font-size: 0.85rem; padding: 0.4rem 0.8rem;">
                                                <i class="fas fa-code-branch"></i> {{ $deployment->branch }}
                                            </span>
                                        </td>
                                        <td>
                                            @if($deployment->commit_hash)
                                                <span class="commit-code" style="font-size: 0.8rem;">{{ $deployment->short_commit }}</span>
                                                <br>
                                                <small class="text-muted">{{ Str::limit($deployment->commit_message, 40) }}</small>
                                            @else
                                                <span class="text-muted">N/A</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($deployment->startedBy)
                                                <div class="d-flex align-items-center">
                                                    <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center mr-2" style="width: 32px; height: 32px; font-weight: 600;">
                                                        {{ strtoupper(substr($deployment->startedBy->name, 0, 1)) }}
                                                    </div>
                                                    <span>{{ $deployment->startedBy->name }}</span>
                                                </div>
                                            @else
                                                <span class="text-muted">System</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($deployment->started_at)
                                                <div>{{ $deployment->started_at->format('M d, Y') }}</div>
                                                <small class="text-muted">{{ $deployment->started_at->format('H:i:s') }}</small>
                                            @else
                                                <span class="text-muted">Not started</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($deployment->duration_human)
                                                <span class="duration-badge">
                                                    <i class="fas fa-stopwatch"></i> {{ $deployment->duration_human }}
                                                </span>
                                            @else
                                                <span class="text-muted">-</span>
                                            @endif
                                        </td>
                                        <td>
                                            <div class="action-btn-group">
                                                @if($deployment->output || $deployment->error)
                                                    <button class="action-btn action-btn-primary btn-sm" onclick="viewDeploymentLogs({{ $deployment->id }})" title="View Logs">
                                                        <i class="fas fa-file-alt"></i>
                                                    </button>
                                                @endif
                                                @if($deployment->isCompleted() && $deployment->rollback_available && !$isDeploying)
                                                    <button class="action-btn action-btn-warning btn-sm" onclick="rollbackDeployment({{ $deployment->id }})" title="Rollback">
                                                        <i class="fas fa-undo-alt"></i>
                                                    </button>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="empty-state">
                            <i class="fas fa-inbox"></i>
                            <h5>No Deployment History</h5>
                            <p>Deploy your first update to see it here</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Logs Modal -->
<div class="modal fade" id="logsModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-xl" role="document">
        <div class="modal-content" style="border-radius: 15px; border: none; overflow: hidden;">
            <div class="modal-header" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border: none;">
                <h5 class="modal-title">
                    <i class="fas fa-terminal"></i> Deployment Logs
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div id="modal-deployment-info" class="mb-3"></div>
                <div class="terminal-container">
                    <div class="terminal-header">
                        <div class="terminal-buttons">
                            <div class="terminal-button close"></div>
                            <div class="terminal-button minimize"></div>
                            <div class="terminal-button maximize"></div>
                        </div>
                        <div class="terminal-title">
                            <i class="fas fa-file-alt"></i> deployment-log.txt
                        </div>
                        <div></div>
                    </div>
                    <div class="terminal-body" id="modal-deployment-logs" style="height: 500px; white-space: pre-wrap;"></div>
                </div>
            </div>
            <div class="modal-footer" style="border-top: none;">
                <button type="button" class="btn btn-gradient-primary" data-dismiss="modal">
                    <i class="fas fa-times"></i> Close
                </button>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
let deploymentEventSource = null;
let deploymentStartTime = null;
let deploymentDurationInterval = null;

// Check for updates
function checkForUpdates() {
    const btn = event.target.closest('button');
    const originalHtml = btn.innerHTML;
    btn.innerHTML = '<div class="spinner-modern" style="border-color: rgba(102, 126, 234, 0.3); border-top-color: #667eea;"></div> Checking...';
    btn.disabled = true;

    fetch('{{ route('admin.deployment.check-updates') }}')
        .then(response => response.json())
        .then(data => {
            btn.innerHTML = originalHtml;
            btn.disabled = false;

            if (data.error) {
                showAlert('danger', '<i class="fas fa-exclamation-circle"></i> Error checking updates: ' + data.error);
                return;
            }

            const updateInfo = document.getElementById('update-info');
            const updateDetails = document.getElementById('update-details');

            if (data.has_updates) {
                let html = `
                    <p class="mb-3" style="font-size: 1.05rem;">
                        <strong>${data.commits_count}</strong> new commit(s) available on branch <strong>${data.branch}</strong>
                    </p>
                    <div class="d-flex align-items-center gap-3 mb-3">
                        <span class="commit-code">${data.local_commit}</span>
                        <i class="fas fa-arrow-right"></i>
                        <span class="commit-code">${data.remote_commit}</span>
                    </div>
                `;

                if (data.commits && data.commits.length > 0) {
                    html += '<div class="commit-list"><strong><i class="fas fa-list"></i> New Commits:</strong><ul class="mt-2">';
                    data.commits.forEach(commit => {
                        html += `<li><code>${commit.hash}</code> ${commit.message}</li>`;
                    });
                    html += '</ul></div>';
                }

                updateDetails.innerHTML = html;
                updateInfo.classList.remove('d-none');
                showAlert('info', `<i class="fas fa-bell"></i> ${data.commits_count} update(s) available! Click "Deploy Now" to update.`);
            } else {
                updateInfo.classList.add('d-none');
                showAlert('success', '<i class="fas fa-check-circle"></i> Your application is up to date!');
            }
        })
        .catch(error => {
            btn.innerHTML = originalHtml;
            btn.disabled = false;
            showAlert('danger', '<i class="fas fa-exclamation-circle"></i> Failed to check updates: ' + error.message);
        });
}

// Start deployment
function startDeployment() {
    if (!confirm('🚀 Are you sure you want to start deployment?\n\nThis will update your application to the latest version.')) {
        return;
    }

    const btn = document.getElementById('deploy-btn');
    btn.innerHTML = '<div class="spinner-modern"></div> Starting Deployment...';
    btn.disabled = true;

    fetch('{{ route('admin.deployment.start') }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        body: JSON.stringify({
            branch: '{{ $currentBranch }}'
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.error) {
            showAlert('danger', '<i class="fas fa-exclamation-circle"></i> ' + data.error);
            btn.innerHTML = '<i class="fas fa-rocket"></i> Deploy Now';
            btn.disabled = false;
            return;
        }

        if (data.success) {
            showAlert('info', '<i class="fas fa-rocket"></i> Deployment started! Connecting to live logs...');
            const logsContainer = document.getElementById('logs-container');
            logsContainer.classList.remove('d-none');
            logsContainer.scrollIntoView({ behavior: 'smooth' });
            streamDeploymentLogs(data.deployment_id);
        }
    })
    .catch(error => {
        showAlert('danger', '<i class="fas fa-exclamation-circle"></i> Failed to start deployment: ' + error.message);
        btn.innerHTML = '<i class="fas fa-rocket"></i> Deploy Now';
        btn.disabled = false;
    });
}

// Stream deployment logs via SSE
function streamDeploymentLogs(deploymentId) {
    const logsContainer = document.getElementById('deployment-logs');
    logsContainer.innerHTML = '';

    deploymentStartTime = Date.now();
    updateDeploymentDuration();
    deploymentDurationInterval = setInterval(updateDeploymentDuration, 1000);

    const url = '{{ route('admin.deployment.stream', ':id') }}'.replace(':id', deploymentId);
    deploymentEventSource = new EventSource(url);

    deploymentEventSource.onmessage = function(event) {
        const data = JSON.parse(event.data);

        if (data.type === 'log') {
            appendLog(data.message);
        } else if (data.type === 'complete') {
            clearInterval(deploymentDurationInterval);
            deploymentEventSource.close();

            if (data.success) {
                showAlert('success', '<i class="fas fa-check-circle"></i> Deployment completed successfully! Duration: ' + formatDuration(data.duration));
                setTimeout(() => {
                    window.location.reload();
                }, 2000);
            } else {
                showAlert('danger', '<i class="fas fa-times-circle"></i> Deployment failed: ' + (data.error || 'Unknown error'));
            }
        }
    };

    deploymentEventSource.onerror = function(error) {
        console.error('EventSource error:', error);
        clearInterval(deploymentDurationInterval);
        deploymentEventSource.close();
        showAlert('danger', '<i class="fas fa-exclamation-triangle"></i> Lost connection to deployment stream');
    };
}

// Append log message
function appendLog(message) {
    const logsContainer = document.getElementById('deployment-logs');
    const line = document.createElement('div');
    line.className = 'log-line terminal-prompt';
    line.textContent = message;
    logsContainer.appendChild(line);
    logsContainer.scrollTop = logsContainer.scrollHeight;
}

// Clear logs
function clearLogs() {
    document.getElementById('deployment-logs').innerHTML = '';
}

// Update deployment duration
function updateDeploymentDuration() {
    if (!deploymentStartTime) return;
    const duration = Math.floor((Date.now() - deploymentStartTime) / 1000);
    document.getElementById('deployment-duration').innerHTML = '<i class="fas fa-clock"></i> ' + formatDuration(duration);
}

// Format duration
function formatDuration(seconds) {
    if (seconds < 60) {
        return seconds + 's';
    }
    const minutes = Math.floor(seconds / 60);
    const secs = seconds % 60;
    return minutes + 'm ' + secs + 's';
}

// View deployment logs (from history)
function viewDeploymentLogs(deploymentId) {
    fetch('{{ route('admin.deployment.status', ':id') }}'.replace(':id', deploymentId))
        .then(response => response.json())
        .then(data => {
            const deployment = data.deployment;
            const statusClass = deployment.status === 'completed' ? 'success' : deployment.status === 'failed' ? 'danger' : 'info';
            const infoHtml = `
                <div class="alert alert-${statusClass} alert-modern">
                    <div class="row">
                        <div class="col-md-3"><strong>Status:</strong> ${deployment.status}</div>
                        <div class="col-md-3"><strong>Branch:</strong> ${deployment.branch}</div>
                        ${deployment.commit_hash ? '<div class="col-md-3"><strong>Commit:</strong> <code>' + deployment.commit_hash + '</code></div>' : ''}
                        ${deployment.duration ? '<div class="col-md-3"><strong>Duration:</strong> ' + deployment.duration + '</div>' : ''}
                    </div>
                </div>
            `;
            document.getElementById('modal-deployment-info').innerHTML = infoHtml;

            const logs = deployment.error || deployment.output || 'No logs available';
            document.getElementById('modal-deployment-logs').textContent = logs;

            $('#logsModal').modal('show');
        })
        .catch(error => {
            showAlert('danger', '<i class="fas fa-exclamation-circle"></i> Failed to load deployment logs: ' + error.message);
        });
}

// Rollback deployment
function rollbackDeployment(deploymentId) {
    if (!confirm('⚠️ Are you sure you want to rollback to this deployment?\n\nThis will revert your application to the previous version.')) {
        return;
    }

    fetch('{{ route('admin.deployment.rollback', ':id') }}'.replace(':id', deploymentId), {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.error) {
            showAlert('danger', '<i class="fas fa-exclamation-circle"></i> ' + data.error);
            return;
        }

        if (data.success) {
            showAlert('info', '<i class="fas fa-undo-alt"></i> Rollback started! Connecting to logs...');
            document.getElementById('logs-container').classList.remove('d-none');
            streamDeploymentLogs(data.deployment_id);
        }
    })
    .catch(error => {
        showAlert('danger', '<i class="fas fa-exclamation-circle"></i> Failed to start rollback: ' + error.message);
    });
}

// Cancel deployment
function cancelDeployment(deploymentId) {
    if (!confirm('⚠️ Are you sure you want to cancel this deployment?')) {
        return;
    }

    fetch('{{ route('admin.deployment.cancel', ':id') }}'.replace(':id', deploymentId), {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showAlert('warning', '<i class="fas fa-ban"></i> Deployment cancelled');
            setTimeout(() => window.location.reload(), 1000);
        } else {
            showAlert('danger', '<i class="fas fa-exclamation-circle"></i> ' + (data.error || 'Failed to cancel deployment'));
        }
    })
    .catch(error => {
        showAlert('danger', '<i class="fas fa-exclamation-circle"></i> Failed to cancel deployment: ' + error.message);
    });
}

// Show alert
function showAlert(type, message) {
    const alertHtml = `
        <div class="alert alert-${type} alert-modern alert-dismissible fade show" role="alert">
            ${message}
            <button type="button" class="close" data-dismiss="alert">
                <span>&times;</span>
            </button>
        </div>
    `;
    const container = document.getElementById('alert-container');
    container.innerHTML = alertHtml;

    // Auto dismiss after 5 seconds
    setTimeout(() => {
        const alert = container.querySelector('.alert');
        if (alert) {
            $(alert).alert('close');
        }
    }, 5000);
}

// Clean up on page unload
window.addEventListener('beforeunload', function() {
    if (deploymentEventSource) {
        deploymentEventSource.close();
    }
    if (deploymentDurationInterval) {
        clearInterval(deploymentDurationInterval);
    }
});
</script>
@endpush
