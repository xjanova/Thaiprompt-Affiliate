@extends('layouts.admin-v3')

@section('title', 'AI Gen Settings')

@section('content')
<div class="container-fluid">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-1">
                <i class="fas fa-cog text-primary"></i> System Settings
            </h1>
            <p class="text-muted mb-0">ตั้งค่าระบบ AI Gen โดยรวม</p>
        </div>
        <button class="btn btn-primary" onclick="saveAllSettings()">
            <i class="fas fa-save"></i> Save All Changes
        </button>
    </div>

    <!-- Success Alert -->
    <div class="alert alert-success alert-dismissible fade show d-none" id="success-alert">
        <i class="fas fa-check-circle"></i> <span id="success-message">Settings saved successfully!</span>
        <button type="button" class="close" onclick="$('#success-alert').addClass('d-none')">
            <span>&times;</span>
        </button>
    </div>

    <!-- Settings Tabs -->
    <ul class="nav nav-tabs mb-4" role="tablist">
        <li class="nav-item">
            <a class="nav-link active" data-toggle="tab" href="#general">
                <i class="fas fa-sliders-h"></i> General
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link" data-toggle="tab" href="#limits">
                <i class="fas fa-shield-alt"></i> Limits & Security
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link" data-toggle="tab" href="#defaults">
                <i class="fas fa-magic"></i> Defaults
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link" data-toggle="tab" href="#notifications">
                <i class="fas fa-bell"></i> Notifications
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link" data-toggle="tab" href="#advanced">
                <i class="fas fa-code"></i> Advanced
            </a>
        </li>
    </ul>

    <!-- Tab Content -->
    <div class="tab-content">
        <!-- General Settings -->
        <div id="general" class="tab-pane fade show active">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-sliders-h"></i> General Settings
                    </h6>
                </div>
                <div class="card-body">
                    <div class="form-group">
                        <label class="font-weight-bold">System Status</label>
                        <div class="custom-control custom-switch">
                            <input type="checkbox" class="custom-control-input" id="system_enabled" name="system_enabled" checked>
                            <label class="custom-control-label" for="system_enabled">
                                Enable AI Gen System
                            </label>
                        </div>
                        <small class="form-text text-muted">ปิดระบบชั่วคราวสำหรับการบำรุงรักษา</small>
                    </div>

                    <div class="form-group">
                        <label class="font-weight-bold">Maintenance Mode</label>
                        <div class="custom-control custom-switch">
                            <input type="checkbox" class="custom-control-input" id="maintenance_mode" name="maintenance_mode">
                            <label class="custom-control-label" for="maintenance_mode">
                                Maintenance Mode (Admin Only)
                            </label>
                        </div>
                        <small class="form-text text-muted">เมื่อเปิดใช้งาน เฉพาะ Admin เท่านั้นที่สามารถใช้งานได้</small>
                    </div>

                    <div class="form-group">
                        <label class="font-weight-bold">Maintenance Message</label>
                        <textarea class="form-control" id="maintenance_message" name="maintenance_message" rows="3" placeholder="ระบบกำลังปรับปรุง กรุณากลับมาใช้งานภายหลัง...">ระบบกำลังปรับปรุง กรุณากลับมาใช้งานภายหลัง...</textarea>
                    </div>

                    <hr>

                    <div class="form-group">
                        <label class="font-weight-bold">Allow Guest Access</label>
                        <div class="custom-control custom-switch">
                            <input type="checkbox" class="custom-control-input" id="allow_guest" name="allow_guest">
                            <label class="custom-control-label" for="allow_guest">
                                Allow Non-Logged In Users to View Gallery
                            </label>
                        </div>
                        <small class="form-text text-muted">อนุญาตให้ผู้ใช้ที่ไม่ได้ล็อกอินดู Gallery ได้</small>
                    </div>

                    <div class="form-group">
                        <label class="font-weight-bold">Require Email Verification</label>
                        <div class="custom-control custom-switch">
                            <input type="checkbox" class="custom-control-input" id="require_verification" name="require_verification" checked>
                            <label class="custom-control-label" for="require_verification">
                                Require Email Verification Before Use
                            </label>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Limits & Security -->
        <div id="limits" class="tab-pane fade">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-shield-alt"></i> Limits & Security Settings
                    </h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="font-weight-bold">Max Daily Generations (Per User)</label>
                                <input type="number" class="form-control" id="max_daily_generations" name="max_daily_generations" value="100" min="1">
                                <small class="form-text text-muted">จำนวนสูงสุดที่ user สามารถสร้างได้ต่อวัน</small>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="font-weight-bold">Max Concurrent Requests</label>
                                <input type="number" class="form-control" id="max_concurrent" name="max_concurrent" value="3" min="1">
                                <small class="form-text text-muted">จำนวน request พร้อมกันสูงสุดต่อ user</small>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="font-weight-bold">Rate Limit (Per Minute)</label>
                                <input type="number" class="form-control" id="rate_limit" name="rate_limit" value="10" min="1">
                                <small class="form-text text-muted">จำนวน request สูงสุดต่อนาที</small>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="font-weight-bold">Timeout (Seconds)</label>
                                <input type="number" class="form-control" id="request_timeout" name="request_timeout" value="120" min="30">
                                <small class="form-text text-muted">เวลารอ response สูงสุด</small>
                            </div>
                        </div>
                    </div>

                    <hr>

                    <div class="form-group">
                        <label class="font-weight-bold">Content Moderation</label>
                        <div class="custom-control custom-switch">
                            <input type="checkbox" class="custom-control-input" id="content_moderation" name="content_moderation" checked>
                            <label class="custom-control-label" for="content_moderation">
                                Enable AI Content Moderation
                            </label>
                        </div>
                        <small class="form-text text-muted">ตรวจสอบ prompt ก่อนส่งไป API</small>
                    </div>

                    <div class="form-group">
                        <label class="font-weight-bold">Blocked Keywords</label>
                        <textarea class="form-control" id="blocked_keywords" name="blocked_keywords" rows="4" placeholder="One keyword per line..."></textarea>
                        <small class="form-text text-muted">คำที่ห้ามใช้ใน prompt (ทีละคำ)</small>
                    </div>

                    <div class="form-group">
                        <label class="font-weight-bold">Auto-Block After Failed Attempts</label>
                        <input type="number" class="form-control" id="auto_block_threshold" name="auto_block_threshold" value="5" min="1">
                        <small class="form-text text-muted">บล็อก user หลังจากละเมิดกฎกี่ครั้ง</small>
                    </div>
                </div>
            </div>
        </div>

        <!-- Defaults -->
        <div id="defaults" class="tab-pane fade">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-magic"></i> Default Generation Settings
                    </h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="font-weight-bold">Default Provider</label>
                                <select class="form-control" id="default_provider" name="default_provider">
                                    <option value="">Auto (Best Available)</option>
                                </select>
                                <small class="form-text text-muted">Provider เริ่มต้นเมื่อไม่ได้ระบุ</small>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="font-weight-bold">Default Image Size</label>
                                <select class="form-control" id="default_image_size" name="default_image_size">
                                    <option value="square">Square (1:1)</option>
                                    <option value="landscape">Landscape (16:9)</option>
                                    <option value="portrait">Portrait (9:16)</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="font-weight-bold">Default Style</label>
                                <select class="form-control" id="default_style" name="default_style">
                                    <option value="realistic">Realistic</option>
                                    <option value="artistic">Artistic</option>
                                    <option value="anime">Anime</option>
                                    <option value="cartoon">Cartoon</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="font-weight-bold">Default Quality</label>
                                <select class="form-control" id="default_quality" name="default_quality">
                                    <option value="standard">Standard</option>
                                    <option value="hd">HD</option>
                                    <option value="ultra">Ultra HD</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <hr>

                    <div class="form-group">
                        <label class="font-weight-bold">Auto-Save to Gallery</label>
                        <div class="custom-control custom-switch">
                            <input type="checkbox" class="custom-control-input" id="auto_save" name="auto_save" checked>
                            <label class="custom-control-label" for="auto_save">
                                Automatically Save Completed Generations
                            </label>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="font-weight-bold">Public Gallery</label>
                        <div class="custom-control custom-switch">
                            <input type="checkbox" class="custom-control-input" id="public_gallery" name="public_gallery">
                            <label class="custom-control-label" for="public_gallery">
                                Make Gallery Public by Default
                            </label>
                        </div>
                        <small class="form-text text-muted">อนุญาตให้ผู้ใช้คนอื่นดูผลงานได้</small>
                    </div>
                </div>
            </div>
        </div>

        <!-- Notifications -->
        <div id="notifications" class="tab-pane fade">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-bell"></i> Notification Settings
                    </h6>
                </div>
                <div class="card-body">
                    <h6 class="font-weight-bold mb-3">Email Notifications</h6>

                    <div class="form-group">
                        <div class="custom-control custom-switch">
                            <input type="checkbox" class="custom-control-input" id="notify_completion" name="notify_completion" checked>
                            <label class="custom-control-label" for="notify_completion">
                                Notify when generation is completed
                            </label>
                        </div>
                    </div>

                    <div class="form-group">
                        <div class="custom-control custom-switch">
                            <input type="checkbox" class="custom-control-input" id="notify_failure" name="notify_failure" checked>
                            <label class="custom-control-label" for="notify_failure">
                                Notify when generation fails
                            </label>
                        </div>
                    </div>

                    <div class="form-group">
                        <div class="custom-control custom-switch">
                            <input type="checkbox" class="custom-control-input" id="notify_credits_low" name="notify_credits_low" checked>
                            <label class="custom-control-label" for="notify_credits_low">
                                Notify when credits are running low
                            </label>
                        </div>
                    </div>

                    <div class="form-group">
                        <div class="custom-control custom-switch">
                            <input type="checkbox" class="custom-control-input" id="notify_expiring" name="notify_expiring" checked>
                            <label class="custom-control-label" for="notify_expiring">
                                Notify when subscription is expiring soon
                            </label>
                        </div>
                    </div>

                    <hr>

                    <h6 class="font-weight-bold mb-3">Admin Notifications</h6>

                    <div class="form-group">
                        <label class="font-weight-bold">Admin Email</label>
                        <input type="email" class="form-control" id="admin_email" name="admin_email" placeholder="admin@example.com">
                        <small class="form-text text-muted">อีเมลสำหรับรับการแจ้งเตือนของระบบ</small>
                    </div>

                    <div class="form-group">
                        <div class="custom-control custom-switch">
                            <input type="checkbox" class="custom-control-input" id="notify_admin_errors" name="notify_admin_errors" checked>
                            <label class="custom-control-label" for="notify_admin_errors">
                                Notify admin when system errors occur
                            </label>
                        </div>
                    </div>

                    <div class="form-group">
                        <div class="custom-control custom-switch">
                            <input type="checkbox" class="custom-control-input" id="notify_admin_abuse" name="notify_admin_abuse" checked>
                            <label class="custom-control-label" for="notify_admin_abuse">
                                Notify admin when abuse is detected
                            </label>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Advanced -->
        <div id="advanced" class="tab-pane fade">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-code"></i> Advanced Settings
                    </h6>
                </div>
                <div class="card-body">
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle"></i>
                        <strong>Warning:</strong> การเปลี่ยนแปลงการตั้งค่าขั้นสูงอาจส่งผลต่อการทำงานของระบบ
                    </div>

                    <div class="form-group">
                        <label class="font-weight-bold">Storage Driver</label>
                        <select class="form-control" id="storage_driver" name="storage_driver">
                            <option value="local">Local Disk</option>
                            <option value="s3">Amazon S3</option>
                            <option value="gcs">Google Cloud Storage</option>
                            <option value="do">DigitalOcean Spaces</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="font-weight-bold">CDN URL</label>
                        <input type="url" class="form-control" id="cdn_url" name="cdn_url" placeholder="https://cdn.example.com">
                        <small class="form-text text-muted">URL ของ CDN สำหรับ serve ไฟล์</small>
                    </div>

                    <hr>

                    <div class="form-group">
                        <label class="font-weight-bold">Cache Duration (Minutes)</label>
                        <input type="number" class="form-control" id="cache_duration" name="cache_duration" value="60" min="1">
                        <small class="form-text text-muted">ระยะเวลา cache ข้อมูล</small>
                    </div>

                    <div class="form-group">
                        <label class="font-weight-bold">Queue Driver</label>
                        <select class="form-control" id="queue_driver" name="queue_driver">
                            <option value="sync">Sync (No Queue)</option>
                            <option value="redis">Redis</option>
                            <option value="database">Database</option>
                            <option value="sqs">Amazon SQS</option>
                        </select>
                    </div>

                    <hr>

                    <div class="form-group">
                        <label class="font-weight-bold">Debug Mode</label>
                        <div class="custom-control custom-switch">
                            <input type="checkbox" class="custom-control-input" id="debug_mode" name="debug_mode">
                            <label class="custom-control-label" for="debug_mode">
                                Enable Debug Mode (Show Detailed Errors)
                            </label>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="font-weight-bold">Log Level</label>
                        <select class="form-control" id="log_level" name="log_level">
                            <option value="debug">Debug</option>
                            <option value="info" selected>Info</option>
                            <option value="warning">Warning</option>
                            <option value="error">Error</option>
                        </select>
                    </div>

                    <hr>

                    <div class="form-group">
                        <button class="btn btn-warning" onclick="clearCache()">
                            <i class="fas fa-trash"></i> Clear All Cache
                        </button>
                        <button class="btn btn-info ml-2" onclick="testConnection()">
                            <i class="fas fa-plug"></i> Test Provider Connections
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
let settings = {};

async function loadSettings() {
    try {
        const response = await fetch('/admin/ai-gen/settings', {
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        });

        const data = await response.json();

        if (data.success) {
            settings = data.data;
            populateSettings();
        }
    } catch (error) {
        console.error('Error loading settings:', error);
        showError('Failed to load settings');
    }
}

async function loadProviders() {
    try {
        const response = await fetch('/admin/ai-gen/providers', {
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        });

        const data = await response.json();

        if (data.success) {
            const select = document.getElementById('default_provider');
            data.data.forEach(provider => {
                const option = document.createElement('option');
                option.value = provider.slug;
                option.textContent = provider.name;
                select.appendChild(option);
            });
        }
    } catch (error) {
        console.error('Error loading providers:', error);
    }
}

function populateSettings() {
    // Populate all form fields with settings values
    Object.keys(settings).forEach(key => {
        const element = document.getElementById(key) || document.querySelector(`[name="${key}"]`);
        if (element) {
            if (element.type === 'checkbox') {
                element.checked = settings[key];
            } else {
                element.value = settings[key];
            }
        }
    });
}

function collectSettings() {
    const collected = {};
    const inputs = document.querySelectorAll('#general input, #general select, #general textarea, ' +
                                              '#limits input, #limits select, #limits textarea, ' +
                                              '#defaults input, #defaults select, #defaults textarea, ' +
                                              '#notifications input, #notifications select, #notifications textarea, ' +
                                              '#advanced input, #advanced select, #advanced textarea');

    inputs.forEach(input => {
        const key = input.id || input.name;
        if (key) {
            if (input.type === 'checkbox') {
                collected[key] = input.checked;
            } else {
                collected[key] = input.value;
            }
        }
    });

    return collected;
}

async function saveAllSettings() {
    const settingsData = collectSettings();

    try {
        const response = await fetch('/admin/ai-gen/settings', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify(settingsData)
        });

        const result = await response.json();

        if (result.success) {
            showSuccess('Settings saved successfully!');
        } else {
            showError(result.error || 'Failed to save settings');
        }
    } catch (error) {
        console.error('Error:', error);
        showError('Failed to save settings');
    }
}

async function clearCache() {
    if (!confirm('Are you sure you want to clear all cache?')) {
        return;
    }

    try {
        const response = await fetch('/admin/ai-gen/settings/clear-cache', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            }
        });

        const result = await response.json();

        if (result.success) {
            showSuccess('Cache cleared successfully!');
        } else {
            showError(result.error || 'Failed to clear cache');
        }
    } catch (error) {
        console.error('Error:', error);
        showError('Failed to clear cache');
    }
}

async function testConnection() {
    showSuccess('Testing connections... Please wait.');

    try {
        const response = await fetch('/admin/ai-gen/settings/test-connections', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            }
        });

        const result = await response.json();

        if (result.success) {
            const results = result.data;
            let message = 'Connection Test Results:\n\n';
            results.forEach(r => {
                message += `${r.provider}: ${r.status}\n`;
            });
            alert(message);
        } else {
            showError(result.error || 'Failed to test connections');
        }
    } catch (error) {
        console.error('Error:', error);
        showError('Failed to test connections');
    }
}

function showSuccess(message) {
    document.getElementById('success-message').textContent = message;
    document.getElementById('success-alert').classList.remove('d-none');

    setTimeout(() => {
        document.getElementById('success-alert').classList.add('d-none');
    }, 5000);
}

function showError(message) {
    alert('Error: ' + message);
}

// Load data on page load
document.addEventListener('DOMContentLoaded', () => {
    loadProviders();
    loadSettings();
});

// Save on Ctrl+S
document.addEventListener('keydown', (e) => {
    if ((e.ctrlKey || e.metaKey) && e.key === 's') {
        e.preventDefault();
        saveAllSettings();
    }
});
</script>
@endpush
