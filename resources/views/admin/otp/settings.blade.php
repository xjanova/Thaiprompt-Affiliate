@extends('layouts.admin')

@section('title', 'OTP Settings')

@section('content')
<div class="container-fluid">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-1">OTP Settings</h1>
            <p class="text-muted">Configure phone verification with OTP system</p>
        </div>
        <div>
            <button type="button" class="btn btn-info" onclick="showTestModal()">
                <i class="fas fa-paper-plane me-2"></i>Test OTP
            </button>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-circle me-2"></i>{{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <form action="{{ route('admin.otp.settings.update') }}" method="POST">
        @csrf
        @method('PUT')

        <div class="row">
            <!-- General Settings -->
            <div class="col-lg-8">
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-white py-3">
                        <h5 class="mb-0"><i class="fas fa-cog text-primary me-2"></i>General Settings</h5>
                    </div>
                    <div class="card-body">
                        <!-- Enable OTP -->
                        <div class="mb-4">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="enabled" name="enabled" value="1"
                                    {{ old('enabled', $settings->enabled) ? 'checked' : '' }}>
                                <label class="form-check-label fw-bold" for="enabled">
                                    Enable OTP Verification System
                                </label>
                            </div>
                            <small class="text-muted">Enable phone number verification using OTP codes</small>
                        </div>

                        <!-- Provider Selection -->
                        <div class="mb-4">
                            <label class="form-label fw-bold">SMS Provider</label>
                            <select class="form-select @error('provider') is-invalid @enderror" name="provider" id="provider" onchange="toggleProvider()">
                                <option value="twilio" {{ old('provider', $settings->provider) === 'twilio' ? 'selected' : '' }}>Twilio</option>
                                <option value="nexmo" {{ old('provider', $settings->provider) === 'nexmo' ? 'selected' : '' }}>Nexmo/Vonage</option>
                                <option value="custom" {{ old('provider', $settings->provider) === 'custom' ? 'selected' : '' }}>Custom API</option>
                            </select>
                            @error('provider')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Twilio Settings -->
                        <div id="twilio-settings" class="provider-settings" style="display: none;">
                            <h6 class="border-bottom pb-2 mb-3">Twilio Configuration</h6>
                            <div class="mb-3">
                                <label class="form-label">Account SID</label>
                                <input type="text" class="form-control @error('twilio_sid') is-invalid @enderror"
                                    name="twilio_sid" value="{{ old('twilio_sid', $settings->twilio_sid) }}"
                                    placeholder="ACxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx">
                                @error('twilio_sid')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Auth Token</label>
                                <input type="password" class="form-control @error('twilio_token') is-invalid @enderror"
                                    name="twilio_token" value="{{ old('twilio_token', $settings->twilio_token) }}"
                                    placeholder="your_auth_token">
                                @error('twilio_token')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="mb-3">
                                <label class="form-label">From Number</label>
                                <input type="text" class="form-control @error('twilio_from') is-invalid @enderror"
                                    name="twilio_from" value="{{ old('twilio_from', $settings->twilio_from) }}"
                                    placeholder="+1234567890">
                                @error('twilio_from')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="alert alert-info">
                                <small>
                                    <i class="fas fa-info-circle me-2"></i>
                                    Get your Twilio credentials from <a href="https://console.twilio.com" target="_blank">Twilio Console</a>
                                </small>
                            </div>
                        </div>

                        <!-- Nexmo Settings -->
                        <div id="nexmo-settings" class="provider-settings" style="display: none;">
                            <h6 class="border-bottom pb-2 mb-3">Nexmo/Vonage Configuration</h6>
                            <div class="mb-3">
                                <label class="form-label">API Key</label>
                                <input type="text" class="form-control @error('nexmo_key') is-invalid @enderror"
                                    name="nexmo_key" value="{{ old('nexmo_key', $settings->nexmo_key) }}"
                                    placeholder="your_api_key">
                                @error('nexmo_key')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="mb-3">
                                <label class="form-label">API Secret</label>
                                <input type="password" class="form-control @error('nexmo_secret') is-invalid @enderror"
                                    name="nexmo_secret" value="{{ old('nexmo_secret', $settings->nexmo_secret) }}"
                                    placeholder="your_api_secret">
                                @error('nexmo_secret')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="mb-3">
                                <label class="form-label">From Name/Number</label>
                                <input type="text" class="form-control @error('nexmo_from') is-invalid @enderror"
                                    name="nexmo_from" value="{{ old('nexmo_from', $settings->nexmo_from) }}"
                                    placeholder="YourBrand or +1234567890">
                                @error('nexmo_from')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="alert alert-info">
                                <small>
                                    <i class="fas fa-info-circle me-2"></i>
                                    Get your Nexmo credentials from <a href="https://dashboard.nexmo.com" target="_blank">Nexmo Dashboard</a>
                                </small>
                            </div>
                        </div>

                        <!-- Custom API Settings -->
                        <div id="custom-settings" class="provider-settings" style="display: none;">
                            <h6 class="border-bottom pb-2 mb-3">Custom API Configuration</h6>
                            <div class="mb-3">
                                <label class="form-label">API URL</label>
                                <input type="url" class="form-control @error('custom_api_url') is-invalid @enderror"
                                    name="custom_api_url" value="{{ old('custom_api_url', $settings->custom_api_url) }}"
                                    placeholder="https://api.example.com/send-sms">
                                @error('custom_api_url')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="mb-3">
                                <label class="form-label">API Key (Optional)</label>
                                <input type="password" class="form-control @error('custom_api_key') is-invalid @enderror"
                                    name="custom_api_key" value="{{ old('custom_api_key', $settings->custom_api_key) }}"
                                    placeholder="your_api_key">
                                @error('custom_api_key')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Custom Headers (JSON)</label>
                                <textarea class="form-control @error('custom_api_headers') is-invalid @enderror"
                                    name="custom_api_headers" rows="3"
                                    placeholder='{"Authorization": "Bearer token", "Content-Type": "application/json"}'>{{ old('custom_api_headers', $settings->custom_api_headers) }}</textarea>
                                @error('custom_api_headers')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="alert alert-info">
                                <small>
                                    <i class="fas fa-info-circle me-2"></i>
                                    API should accept POST request with JSON: {"phone": "+66812345678", "message": "Your code is: 123456", "code": "123456"}
                                </small>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- OTP Configuration -->
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-white py-3">
                        <h5 class="mb-0"><i class="fas fa-key text-primary me-2"></i>OTP Configuration</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">OTP Length</label>
                                <input type="number" class="form-control @error('otp_length') is-invalid @enderror"
                                    name="otp_length" value="{{ old('otp_length', $settings->otp_length) }}"
                                    min="4" max="8">
                                @error('otp_length')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <small class="text-muted">Number of digits in OTP code (4-8)</small>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label">Expiry Time (minutes)</label>
                                <input type="number" class="form-control @error('otp_expiry_minutes') is-invalid @enderror"
                                    name="otp_expiry_minutes" value="{{ old('otp_expiry_minutes', $settings->otp_expiry_minutes) }}"
                                    min="1" max="60">
                                @error('otp_expiry_minutes')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <small class="text-muted">How long the OTP is valid</small>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label">Max Verification Attempts</label>
                                <input type="number" class="form-control @error('max_attempts') is-invalid @enderror"
                                    name="max_attempts" value="{{ old('max_attempts', $settings->max_attempts) }}"
                                    min="1" max="10">
                                @error('max_attempts')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <small class="text-muted">Failed attempts before OTP expires</small>
                            </div>

                            <div class="col-md-6 mb-3">
                                <div class="form-check form-switch mt-4">
                                    <input class="form-check-input" type="checkbox" id="alphanumeric" name="alphanumeric" value="1"
                                        {{ old('alphanumeric', $settings->alphanumeric) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="alphanumeric">
                                        Use Alphanumeric OTP
                                    </label>
                                </div>
                                <small class="text-muted">Letters and numbers instead of just numbers</small>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Rate Limiting -->
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-white py-3">
                        <h5 class="mb-0"><i class="fas fa-shield-alt text-primary me-2"></i>Rate Limiting</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Max OTP per Phone</label>
                                <input type="number" class="form-control @error('rate_limit_per_phone') is-invalid @enderror"
                                    name="rate_limit_per_phone" value="{{ old('rate_limit_per_phone', $settings->rate_limit_per_phone) }}"
                                    min="1" max="20">
                                @error('rate_limit_per_phone')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <small class="text-muted">Maximum OTP requests allowed</small>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label">Time Window (minutes)</label>
                                <input type="number" class="form-control @error('rate_limit_window') is-invalid @enderror"
                                    name="rate_limit_window" value="{{ old('rate_limit_window', $settings->rate_limit_window) }}"
                                    min="1" max="1440">
                                @error('rate_limit_window')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <small class="text-muted">Time window for rate limiting</small>
                            </div>
                        </div>
                        <div class="alert alert-warning">
                            <small>
                                <i class="fas fa-exclamation-triangle me-2"></i>
                                Example: 3 requests per 60 minutes = A phone number can request OTP maximum 3 times in 1 hour
                            </small>
                        </div>
                    </div>
                </div>

                <!-- Message Template -->
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-white py-3">
                        <h5 class="mb-0"><i class="fas fa-comment-alt text-primary me-2"></i>Message Template</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label">SMS Message Template</label>
                            <textarea class="form-control @error('message_template') is-invalid @enderror"
                                name="message_template" rows="3">{{ old('message_template', $settings->message_template) }}</textarea>
                            @error('message_template')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <small class="text-muted">
                                Available placeholders: <code>{code}</code>, <code>{expiry}</code>
                            </small>
                        </div>
                        <div class="alert alert-info">
                            <strong>Preview:</strong><br>
                            <span id="message-preview">{{ str_replace(['{code}', '{expiry}'], ['123456', $settings->otp_expiry_minutes], old('message_template', $settings->message_template)) }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Info Panel -->
            <div class="col-lg-4">
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-primary text-white py-3">
                        <h5 class="mb-0"><i class="fas fa-info-circle me-2"></i>Information</h5>
                    </div>
                    <div class="card-body">
                        <h6 class="mb-3">OTP System Status</h6>
                        <div class="mb-3">
                            <div class="d-flex justify-content-between align-items-center">
                                <span>System Status:</span>
                                @if($settings->enabled)
                                    <span class="badge bg-success">Active</span>
                                @else
                                    <span class="badge bg-danger">Inactive</span>
                                @endif
                            </div>
                        </div>
                        <div class="mb-3">
                            <div class="d-flex justify-content-between align-items-center">
                                <span>Provider:</span>
                                <span class="badge bg-info">{{ ucfirst($settings->provider) }}</span>
                            </div>
                        </div>
                        <div class="mb-3">
                            <div class="d-flex justify-content-between align-items-center">
                                <span>OTP Length:</span>
                                <span class="badge bg-secondary">{{ $settings->otp_length }} digits</span>
                            </div>
                        </div>
                        <div class="mb-3">
                            <div class="d-flex justify-content-between align-items-center">
                                <span>Expiry Time:</span>
                                <span class="badge bg-secondary">{{ $settings->otp_expiry_minutes }} min</span>
                            </div>
                        </div>

                        <hr>

                        <h6 class="mb-3">Setup Guide</h6>
                        <ol class="small">
                            <li class="mb-2">Choose your SMS provider</li>
                            <li class="mb-2">Enter provider credentials</li>
                            <li class="mb-2">Configure OTP settings</li>
                            <li class="mb-2">Set rate limiting rules</li>
                            <li class="mb-2">Customize message template</li>
                            <li class="mb-2">Test the system</li>
                            <li class="mb-2">Enable OTP verification</li>
                        </ol>

                        <hr>

                        <h6 class="mb-3">Thai Phone Format</h6>
                        <p class="small text-muted mb-0">
                            Supported formats:
                        </p>
                        <ul class="small">
                            <li><code>0812345678</code></li>
                            <li><code>66812345678</code></li>
                            <li><code>+66812345678</code></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <!-- Save Button -->
        <div class="row">
            <div class="col-12">
                <div class="card shadow-sm">
                    <div class="card-body d-flex justify-content-between align-items-center">
                        <p class="mb-0 text-muted">
                            <i class="fas fa-save me-2"></i>
                            Make sure to test the OTP system before enabling it for users
                        </p>
                        <button type="submit" class="btn btn-primary px-4">
                            <i class="fas fa-save me-2"></i>Save Settings
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<!-- Test OTP Modal -->
<div class="modal fade" id="testModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Test OTP Sending</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="test-alert" class="alert d-none"></div>
                <div class="mb-3">
                    <label class="form-label">Phone Number</label>
                    <input type="text" class="form-control" id="test_phone"
                        placeholder="0812345678 or +66812345678">
                    <small class="text-muted">Enter a valid Thai phone number</small>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" onclick="sendTestOtp()">
                    <i class="fas fa-paper-plane me-2"></i>Send Test OTP
                </button>
            </div>
        </div>
    </div>
</div>

<script>
// Toggle provider settings
function toggleProvider() {
    const provider = document.getElementById('provider').value;
    document.querySelectorAll('.provider-settings').forEach(el => el.style.display = 'none');

    if (provider === 'twilio') {
        document.getElementById('twilio-settings').style.display = 'block';
    } else if (provider === 'nexmo') {
        document.getElementById('nexmo-settings').style.display = 'block';
    } else if (provider === 'custom') {
        document.getElementById('custom-settings').style.display = 'block';
    }
}

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    toggleProvider();

    // Update message preview on template change
    const templateInput = document.querySelector('[name="message_template"]');
    const expiryInput = document.querySelector('[name="otp_expiry_minutes"]');

    if (templateInput && expiryInput) {
        function updatePreview() {
            const template = templateInput.value;
            const expiry = expiryInput.value || '5';
            const preview = template.replace('{code}', '123456').replace('{expiry}', expiry);
            document.getElementById('message-preview').textContent = preview;
        }

        templateInput.addEventListener('input', updatePreview);
        expiryInput.addEventListener('input', updatePreview);
    }
});

// Show test modal
function showTestModal() {
    const modal = new bootstrap.Modal(document.getElementById('testModal'));
    modal.show();
}

// Send test OTP
async function sendTestOtp() {
    const phone = document.getElementById('test_phone').value;
    const alertDiv = document.getElementById('test-alert');

    if (!phone) {
        alertDiv.className = 'alert alert-warning';
        alertDiv.textContent = 'กรุณากรอกเบอร์โทรศัพท์';
        alertDiv.classList.remove('d-none');
        return;
    }

    try {
        const response = await fetch('{{ route("admin.otp.test") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({ test_phone: phone })
        });

        const data = await response.json();

        if (data.success) {
            alertDiv.className = 'alert alert-success';
            alertDiv.textContent = data.message;
        } else {
            alertDiv.className = 'alert alert-danger';
            alertDiv.textContent = data.message;
        }
        alertDiv.classList.remove('d-none');
    } catch (error) {
        alertDiv.className = 'alert alert-danger';
        alertDiv.textContent = 'เกิดข้อผิดพลาด กรุณาลองใหม่อีกครั้ง';
        alertDiv.classList.remove('d-none');
    }
}
</script>
@endsection
