@extends('layouts.admin')

@section('title', 'Deployment Center')

@section('content')
<div class="container-fluid">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-1">
                <i class="fas fa-rocket text-primary"></i> Deployment Center
            </h1>
            <p class="text-muted mb-0">Deploy updates from GitHub repository</p>
        </div>
        <div>
            <button class="btn btn-outline-primary" onclick="checkForUpdates()">
                <i class="fas fa-sync-alt"></i> Check for Updates
            </button>
        </div>
    </div>

    <!-- Alert Messages -->
    <div id="alert-container"></div>

    <!-- Current Git Info -->
    <div class="row mb-4">
        <div class="col-lg-12">
            <div class="card shadow">
                <div class="card-header py-3 d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-code-branch"></i> Current Version
                    </h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3">
                            <div class="mb-3">
                                <small class="text-muted d-block">Branch</small>
                                <span class="font-weight-bold">
                                    <i class="fas fa-code-branch text-primary"></i> {{ $currentBranch }}
                                </span>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="mb-3">
                                <small class="text-muted d-block">Current Commit</small>
                                <code>{{ $currentCommitShort }}</code>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <small class="text-muted d-block">Commit Message</small>
                                <span>{{ $currentCommitMessage ?: 'N/A' }}</span>
                            </div>
                        </div>
                    </div>

                    @if($lastDeployment)
                    <div class="border-top pt-3 mt-3">
                        <small class="text-muted d-block mb-2">Last Successful Deployment</small>
                        <div class="d-flex align-items-center">
                            <span class="badge badge-success mr-2">Success</span>
                            <span class="text-sm">
                                {{ $lastDeployment->completed_at->diffForHumans() }}
                                @if($lastDeployment->startedBy)
                                    by <strong>{{ $lastDeployment->startedBy->name }}</strong>
                                @endif
                                <span class="text-muted">({{ $lastDeployment->duration_human }})</span>
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
            <div class="card shadow">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-tasks"></i> Deployment Actions
                    </h6>
                </div>
                <div class="card-body">
                    @if($isDeploying && $runningDeployment)
                        <!-- Deployment in Progress -->
                        <div class="alert alert-info">
                            <i class="fas fa-spinner fa-spin"></i>
                            <strong>Deployment in Progress</strong>
                            <p class="mb-0">
                                Started {{ $runningDeployment->started_at->diffForHumans() }}
                                @if($runningDeployment->startedBy)
                                    by <strong>{{ $runningDeployment->startedBy->name }}</strong>
                                @endif
                            </p>
                        </div>
                        <div class="d-flex gap-2">
                            <button class="btn btn-primary" onclick="viewDeploymentLogs({{ $runningDeployment->id }})">
                                <i class="fas fa-eye"></i> View Logs
                            </button>
                            <button class="btn btn-danger" onclick="cancelDeployment({{ $runningDeployment->id }})">
                                <i class="fas fa-stop"></i> Cancel Deployment
                            </button>
                        </div>
                    @else
                        <!-- Deploy Button -->
                        <div class="mb-3">
                            <p class="text-muted mb-3">
                                Deploy the latest changes from the <strong>{{ $currentBranch }}</strong> branch.
                                This will run the deployment script and update your application.
                            </p>
                            <div id="update-info" class="alert alert-secondary d-none mb-3">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <strong><i class="fas fa-info-circle"></i> Updates Available</strong>
                                        <div id="update-details" class="mt-2"></div>
                                    </div>
                                </div>
                            </div>
                            <button class="btn btn-success btn-lg" onclick="startDeployment()" id="deploy-btn">
                                <i class="fas fa-rocket"></i> Deploy Now
                            </button>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Deployment Logs (Hidden by default, shown during deployment) -->
    <div class="row mb-4 d-none" id="logs-container">
        <div class="col-lg-12">
            <div class="card shadow">
                <div class="card-header py-3 d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-terminal"></i> Deployment Logs
                    </h6>
                    <button class="btn btn-sm btn-outline-secondary" onclick="clearLogs()">
                        <i class="fas fa-eraser"></i> Clear
                    </button>
                </div>
                <div class="card-body p-0">
                    <div id="deployment-logs" class="bg-dark text-light p-3" style="height: 400px; overflow-y: auto; font-family: monospace; font-size: 13px;">
                        <div class="text-center text-muted py-5">
                            <i class="fas fa-terminal fa-3x mb-3"></i>
                            <p>Waiting for deployment to start...</p>
                        </div>
                    </div>
                </div>
                <div class="card-footer">
                    <div id="deployment-status" class="d-flex justify-content-between align-items-center">
                        <span class="text-muted">Ready</span>
                        <span id="deployment-duration" class="badge badge-secondary">0s</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Deployment History -->
    <div class="row">
        <div class="col-lg-12">
            <div class="card shadow">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-history"></i> Recent Deployments
                    </h6>
                </div>
                <div class="card-body">
                    @if($recentDeployments->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Status</th>
                                        <th>Branch</th>
                                        <th>Commit</th>
                                        <th>Started By</th>
                                        <th>Started At</th>
                                        <th>Duration</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($recentDeployments as $deployment)
                                    <tr>
                                        <td>{!! $deployment->status_badge !!}</td>
                                        <td>
                                            <i class="fas fa-code-branch text-muted"></i>
                                            {{ $deployment->branch }}
                                        </td>
                                        <td>
                                            @if($deployment->commit_hash)
                                                <code>{{ $deployment->short_commit }}</code>
                                                <br>
                                                <small class="text-muted">{{ Str::limit($deployment->commit_message, 50) }}</small>
                                            @else
                                                <span class="text-muted">N/A</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($deployment->startedBy)
                                                <i class="fas fa-user text-muted"></i>
                                                {{ $deployment->startedBy->name }}
                                            @else
                                                <span class="text-muted">System</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($deployment->started_at)
                                                {{ $deployment->started_at->format('M d, Y H:i:s') }}
                                                <br>
                                                <small class="text-muted">{{ $deployment->started_at->diffForHumans() }}</small>
                                            @else
                                                <span class="text-muted">Not started</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($deployment->duration_human)
                                                <span class="badge badge-info">{{ $deployment->duration_human }}</span>
                                            @else
                                                <span class="text-muted">-</span>
                                            @endif
                                        </td>
                                        <td>
                                            <div class="btn-group btn-group-sm">
                                                @if($deployment->output || $deployment->error)
                                                    <button class="btn btn-outline-primary" onclick="viewDeploymentLogs({{ $deployment->id }})" title="View Logs">
                                                        <i class="fas fa-file-alt"></i>
                                                    </button>
                                                @endif
                                                @if($deployment->isCompleted() && $deployment->rollback_available && !$isDeploying)
                                                    <button class="btn btn-outline-warning" onclick="rollbackDeployment({{ $deployment->id }})" title="Rollback">
                                                        <i class="fas fa-undo"></i>
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
                        <div class="text-center text-muted py-5">
                            <i class="fas fa-inbox fa-3x mb-3"></i>
                            <p>No deployment history yet</p>
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
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-terminal"></i> Deployment Logs
                </h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div id="modal-deployment-info" class="mb-3"></div>
                <div id="modal-deployment-logs" class="bg-dark text-light p-3" style="height: 500px; overflow-y: auto; font-family: monospace; font-size: 13px; white-space: pre-wrap;"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
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
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Checking...';
    btn.disabled = true;

    fetch('{{ route('admin.deployment.check-updates') }}')
        .then(response => response.json())
        .then(data => {
            btn.innerHTML = originalHtml;
            btn.disabled = false;

            if (data.error) {
                showAlert('danger', 'Error checking updates: ' + data.error);
                return;
            }

            const updateInfo = document.getElementById('update-info');
            const updateDetails = document.getElementById('update-details');

            if (data.has_updates) {
                let html = `
                    <p class="mb-2">
                        <strong>${data.commits_count}</strong> new commit(s) available on branch <strong>${data.branch}</strong>
                    </p>
                    <p class="mb-2 text-sm">
                        Local: <code>${data.local_commit}</code> → Remote: <code>${data.remote_commit}</code>
                    </p>
                `;

                if (data.commits && data.commits.length > 0) {
                    html += '<div class="mt-2"><strong>New Commits:</strong><ul class="mb-0 mt-1">';
                    data.commits.forEach(commit => {
                        html += `<li><code>${commit.hash}</code> ${commit.message}</li>`;
                    });
                    html += '</ul></div>';
                }

                updateDetails.innerHTML = html;
                updateInfo.classList.remove('d-none', 'alert-secondary');
                updateInfo.classList.add('alert-info');
                showAlert('info', `${data.commits_count} update(s) available! Click "Deploy Now" to update.`);
            } else {
                updateDetails.innerHTML = '<p class="mb-0">✓ Your application is up to date!</p>';
                updateInfo.classList.remove('d-none', 'alert-info');
                updateInfo.classList.add('alert-secondary');
                showAlert('success', 'Your application is up to date!');
            }
        })
        .catch(error => {
            btn.innerHTML = originalHtml;
            btn.disabled = false;
            showAlert('danger', 'Failed to check updates: ' + error.message);
        });
}

// Start deployment
function startDeployment() {
    if (!confirm('Are you sure you want to start deployment? This will update your application to the latest version.')) {
        return;
    }

    const btn = document.getElementById('deploy-btn');
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Starting...';
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
            showAlert('danger', data.error);
            btn.innerHTML = '<i class="fas fa-rocket"></i> Deploy Now';
            btn.disabled = false;
            return;
        }

        if (data.success) {
            showAlert('info', 'Deployment started! Connecting to logs...');
            // Show logs container
            document.getElementById('logs-container').classList.remove('d-none');
            // Start streaming logs
            streamDeploymentLogs(data.deployment_id);
        }
    })
    .catch(error => {
        showAlert('danger', 'Failed to start deployment: ' + error.message);
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
                showAlert('success', 'Deployment completed successfully! Duration: ' + formatDuration(data.duration));
                setTimeout(() => {
                    window.location.reload();
                }, 2000);
            } else {
                showAlert('danger', 'Deployment failed: ' + (data.error || 'Unknown error'));
            }
        }
    };

    deploymentEventSource.onerror = function(error) {
        console.error('EventSource error:', error);
        clearInterval(deploymentDurationInterval);
        deploymentEventSource.close();
        showAlert('danger', 'Lost connection to deployment stream');
    };
}

// Append log message
function appendLog(message) {
    const logsContainer = document.getElementById('deployment-logs');
    const line = document.createElement('div');
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
    document.getElementById('deployment-duration').textContent = formatDuration(duration);
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
            const infoHtml = `
                <div class="alert alert-${deployment.status === 'completed' ? 'success' : deployment.status === 'failed' ? 'danger' : 'info'}">
                    <strong>Status:</strong> ${deployment.status}<br>
                    <strong>Branch:</strong> ${deployment.branch}<br>
                    ${deployment.commit_hash ? '<strong>Commit:</strong> <code>' + deployment.commit_hash + '</code><br>' : ''}
                    ${deployment.started_at ? '<strong>Started:</strong> ' + deployment.started_at + '<br>' : ''}
                    ${deployment.duration ? '<strong>Duration:</strong> ' + deployment.duration : ''}
                </div>
            `;
            document.getElementById('modal-deployment-info').innerHTML = infoHtml;

            const logs = deployment.error || deployment.output || 'No logs available';
            document.getElementById('modal-deployment-logs').textContent = logs;

            $('#logsModal').modal('show');
        })
        .catch(error => {
            showAlert('danger', 'Failed to load deployment logs: ' + error.message);
        });
}

// Rollback deployment
function rollbackDeployment(deploymentId) {
    if (!confirm('Are you sure you want to rollback to this deployment? This will revert your application to the previous version.')) {
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
            showAlert('danger', data.error);
            return;
        }

        if (data.success) {
            showAlert('info', 'Rollback started! Connecting to logs...');
            document.getElementById('logs-container').classList.remove('d-none');
            streamDeploymentLogs(data.deployment_id);
        }
    })
    .catch(error => {
        showAlert('danger', 'Failed to start rollback: ' + error.message);
    });
}

// Cancel deployment
function cancelDeployment(deploymentId) {
    if (!confirm('Are you sure you want to cancel this deployment?')) {
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
            showAlert('warning', 'Deployment cancelled');
            setTimeout(() => window.location.reload(), 1000);
        } else {
            showAlert('danger', data.error || 'Failed to cancel deployment');
        }
    })
    .catch(error => {
        showAlert('danger', 'Failed to cancel deployment: ' + error.message);
    });
}

// Show alert
function showAlert(type, message) {
    const alertHtml = `
        <div class="alert alert-${type} alert-dismissible fade show" role="alert">
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
