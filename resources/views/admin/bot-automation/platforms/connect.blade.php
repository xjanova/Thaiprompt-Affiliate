@extends('layouts.admin')

@section('title', 'Connect Platform')

@section('content')
<div class="container-fluid px-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-gray-800">Connect New Platform</h1>
        <a href="{{ route('admin.bot-automation.platforms.index') }}" class="btn btn-secondary">
            <i class="fas fa-arrow-left mr-2"></i>Back to Platforms
        </a>
    </div>

    <div class="row">
        <div class="col-lg-8">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Select Platform</h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6 mb-4">
                            <div class="card platform-card border h-100" data-platform="facebook" onclick="selectPlatform('facebook')">
                                <div class="card-body text-center">
                                    <i class="fab fa-facebook-messenger fa-4x text-primary mb-3"></i>
                                    <h5>Facebook Messenger</h5>
                                    <p class="text-muted">Connect your Facebook Messenger to automate conversations</p>
                                    <span class="badge badge-info">Popular</span>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6 mb-4">
                            <div class="card platform-card border h-100" data-platform="line" onclick="selectPlatform('line')">
                                <div class="card-body text-center">
                                    <i class="fab fa-line fa-4x text-success mb-3"></i>
                                    <h5>LINE</h5>
                                    <p class="text-muted">Connect your LINE account for bot automation</p>
                                    <span class="badge badge-success">Recommended</span>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6 mb-4">
                            <div class="card platform-card border h-100" data-platform="telegram" onclick="selectPlatform('telegram')">
                                <div class="card-body text-center">
                                    <i class="fab fa-telegram fa-4x text-info mb-3"></i>
                                    <h5>Telegram</h5>
                                    <p class="text-muted">Connect your Telegram bot for automated messaging</p>
                                    <span class="badge badge-info">Easy Setup</span>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6 mb-4">
                            <div class="card platform-card border h-100" data-platform="slack" onclick="selectPlatform('slack')">
                                <div class="card-body text-center">
                                    <i class="fab fa-slack fa-4x text-danger mb-3"></i>
                                    <h5>Slack</h5>
                                    <p class="text-muted">Connect your Slack workspace for team automation</p>
                                    <span class="badge badge-secondary">Enterprise</span>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6 mb-4">
                            <div class="card platform-card border h-100" data-platform="whatsapp" onclick="selectPlatform('whatsapp')">
                                <div class="card-body text-center">
                                    <i class="fab fa-whatsapp fa-4x text-success mb-3"></i>
                                    <h5>WhatsApp Business</h5>
                                    <p class="text-muted">Connect WhatsApp Business API for customer support</p>
                                    <span class="badge badge-warning">Business</span>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6 mb-4">
                            <div class="card platform-card border h-100" data-platform="discord" onclick="selectPlatform('discord')">
                                <div class="card-body text-center">
                                    <i class="fab fa-discord fa-4x text-primary mb-3"></i>
                                    <h5>Discord</h5>
                                    <p class="text-muted">Connect your Discord server for community automation</p>
                                    <span class="badge badge-info">Gaming</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card shadow mb-4 d-none" id="connectionForm">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Connection Details</h6>
                </div>
                <div class="card-body">
                    <form action="{{ route('admin.bot-automation.platforms.store') }}" method="POST">
                        @csrf
                        <input type="hidden" name="platform_type" id="platform_type">

                        <div class="form-group">
                            <label for="platform_name">Platform Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('platform_name') is-invalid @enderror"
                                   id="platform_name" name="platform_name" value="{{ old('platform_name') }}" required>
                            @error('platform_name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label for="api_key">API Key <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('api_key') is-invalid @enderror"
                                   id="api_key" name="api_key" value="{{ old('api_key') }}" required>
                            @error('api_key')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <small class="form-text text-muted">Enter your platform API key</small>
                        </div>

                        <div class="form-group">
                            <label for="api_secret">API Secret</label>
                            <input type="password" class="form-control @error('api_secret') is-invalid @enderror"
                                   id="api_secret" name="api_secret" value="{{ old('api_secret') }}">
                            @error('api_secret')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <small class="form-text text-muted">Enter your platform API secret (if required)</small>
                        </div>

                        <div class="form-group">
                            <label for="webhook_url">Webhook URL</label>
                            <input type="url" class="form-control @error('webhook_url') is-invalid @enderror"
                                   id="webhook_url" name="webhook_url" value="{{ old('webhook_url', url('/webhook/bot')) }}" readonly>
                            @error('webhook_url')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <small class="form-text text-muted">Use this URL in your platform's webhook settings</small>
                        </div>

                        <div class="form-group">
                            <label for="description">Description</label>
                            <textarea class="form-control @error('description') is-invalid @enderror"
                                      id="description" name="description" rows="3">{{ old('description') }}</textarea>
                            @error('description')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="form-group">
                            <div class="custom-control custom-checkbox">
                                <input type="checkbox" class="custom-control-input" id="auto_connect" name="auto_connect" value="1" {{ old('auto_connect') ? 'checked' : '' }}>
                                <label class="custom-control-label" for="auto_connect">Connect automatically after saving</label>
                            </div>
                        </div>

                        <div class="form-group mb-0">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-plug mr-2"></i>Connect Platform
                            </button>
                            <button type="button" class="btn btn-secondary" onclick="cancelConnection()">
                                Cancel
                            </button>
                            <button type="button" class="btn btn-info" onclick="testConnection()">
                                <i class="fas fa-vial mr-2"></i>Test Connection
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Setup Guide</h6>
                </div>
                <div class="card-body">
                    <div id="setupGuide">
                        <p class="text-muted">Select a platform to see setup instructions</p>
                    </div>
                </div>
            </div>

            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Need Help?</h6>
                </div>
                <div class="card-body">
                    <ul class="list-unstyled mb-0">
                        <li class="mb-2">
                            <a href="#" class="text-decoration-none">
                                <i class="fas fa-book mr-2"></i>Documentation
                            </a>
                        </li>
                        <li class="mb-2">
                            <a href="#" class="text-decoration-none">
                                <i class="fas fa-video mr-2"></i>Video Tutorials
                            </a>
                        </li>
                        <li class="mb-2">
                            <a href="#" class="text-decoration-none">
                                <i class="fas fa-question-circle mr-2"></i>FAQ
                            </a>
                        </li>
                        <li class="mb-2">
                            <a href="#" class="text-decoration-none">
                                <i class="fas fa-headset mr-2"></i>Contact Support
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.platform-card {
    cursor: pointer;
    transition: all 0.3s ease;
}

.platform-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15) !important;
}

.platform-card.selected {
    border-color: #4e73df !important;
    border-width: 2px;
}
</style>

<script>
const setupGuides = {
    facebook: `
        <h6>Facebook Messenger Setup</h6>
        <ol>
            <li>Create a Facebook App</li>
            <li>Add Messenger product</li>
            <li>Generate Page Access Token</li>
            <li>Configure webhook</li>
            <li>Subscribe to page events</li>
        </ol>
    `,
    line: `
        <h6>LINE Setup</h6>
        <ol>
            <li>Create a LINE Developers account</li>
            <li>Create a Messaging API channel</li>
            <li>Get Channel Access Token</li>
            <li>Set webhook URL</li>
            <li>Enable webhook</li>
        </ol>
    `,
    telegram: `
        <h6>Telegram Setup</h6>
        <ol>
            <li>Talk to @BotFather on Telegram</li>
            <li>Create a new bot</li>
            <li>Get the Bot Token</li>
            <li>Set webhook URL (optional)</li>
            <li>Start receiving messages</li>
        </ol>
    `,
    slack: `
        <h6>Slack Setup</h6>
        <ol>
            <li>Create a Slack App</li>
            <li>Add Bot Token Scopes</li>
            <li>Install to Workspace</li>
            <li>Get Bot User OAuth Token</li>
            <li>Configure Event Subscriptions</li>
        </ol>
    `,
    whatsapp: `
        <h6>WhatsApp Business Setup</h6>
        <ol>
            <li>Sign up for WhatsApp Business API</li>
            <li>Get API credentials</li>
            <li>Verify your business</li>
            <li>Configure webhook</li>
            <li>Test the connection</li>
        </ol>
    `,
    discord: `
        <h6>Discord Setup</h6>
        <ol>
            <li>Create Discord Application</li>
            <li>Add a Bot to your Application</li>
            <li>Copy the Bot Token</li>
            <li>Invite bot to your server</li>
            <li>Configure permissions</li>
        </ol>
    `
};

function selectPlatform(platform) {
    // Remove selected class from all cards
    document.querySelectorAll('.platform-card').forEach(card => {
        card.classList.remove('selected');
    });

    // Add selected class to clicked card
    event.currentTarget.classList.add('selected');

    // Set platform type
    document.getElementById('platform_type').value = platform;

    // Show connection form
    document.getElementById('connectionForm').classList.remove('d-none');

    // Update setup guide
    document.getElementById('setupGuide').innerHTML = setupGuides[platform] || '<p class="text-muted">No setup guide available</p>';

    // Scroll to form
    document.getElementById('connectionForm').scrollIntoView({ behavior: 'smooth' });
}

function cancelConnection() {
    document.getElementById('connectionForm').classList.add('d-none');
    document.querySelectorAll('.platform-card').forEach(card => {
        card.classList.remove('selected');
    });
    document.getElementById('setupGuide').innerHTML = '<p class="text-muted">Select a platform to see setup instructions</p>';
}

function testConnection() {
    alert('Testing connection... This feature is under development.');
}
</script>
@endsection
