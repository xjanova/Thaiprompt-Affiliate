@extends('layouts.admin')

@section('title', 'Ticket System Settings')

@section('content')
<div class="container-fluid px-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-gray-800">Ticket System Settings</h1>
        <a href="{{ route('admin.tickets.index') }}" class="btn btn-secondary">
            <i class="fas fa-arrow-left mr-2"></i>กลับหน้าหลัก
        </a>
    </div>

    <form action="{{ route('admin.tickets.settings.update') }}" method="POST">
        @csrf
        @method('PUT')

        <!-- General Settings -->
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">General Settings</h6>
            </div>
            <div class="card-body">
                <div class="form-group">
                    <label for="default_priority">Default Priority for New Tickets</label>
                    <select name="default_priority" id="default_priority" class="form-control">
                        <option value="low">Low</option>
                        <option value="medium" selected>Medium</option>
                        <option value="high">High</option>
                        <option value="critical">Critical</option>
                    </select>
                </div>

                <div class="form-check mb-3">
                    <input type="checkbox" name="auto_assign_enabled" id="auto_assign_enabled" class="form-check-input" value="1" checked>
                    <label for="auto_assign_enabled" class="form-check-label">
                        Enable auto-assignment rules
                    </label>
                </div>

                <div class="form-check mb-3">
                    <input type="checkbox" name="sla_enabled" id="sla_enabled" class="form-check-input" value="1" checked>
                    <label for="sla_enabled" class="form-check-label">
                        Enable SLA policies
                    </label>
                </div>

                <div class="form-check mb-3">
                    <input type="checkbox" name="allow_user_reopen" id="allow_user_reopen" class="form-check-input" value="1" checked>
                    <label for="allow_user_reopen" class="form-check-label">
                        Allow users to reopen closed tickets
                    </label>
                </div>
            </div>
        </div>

        <!-- Email Notifications -->
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">Email Notifications</h6>
            </div>
            <div class="card-body">
                <div class="form-check mb-3">
                    <input type="checkbox" name="notify_admin_new_ticket" id="notify_admin_new_ticket" class="form-check-input" value="1" checked>
                    <label for="notify_admin_new_ticket" class="form-check-label">
                        Notify admins of new tickets
                    </label>
                </div>

                <div class="form-check mb-3">
                    <input type="checkbox" name="notify_user_reply" id="notify_user_reply" class="form-check-input" value="1" checked>
                    <label for="notify_user_reply" class="form-check-label">
                        Notify users of new replies
                    </label>
                </div>

                <div class="form-check mb-3">
                    <input type="checkbox" name="notify_user_status_change" id="notify_user_status_change" class="form-check-input" value="1" checked>
                    <label for="notify_user_status_change" class="form-check-label">
                        Notify users of status changes
                    </label>
                </div>

                <div class="form-check mb-3">
                    <input type="checkbox" name="notify_sla_breach" id="notify_sla_breach" class="form-check-input" value="1" checked>
                    <label for="notify_sla_breach" class="form-check-label">
                        Notify admins of SLA breaches
                    </label>
                </div>
            </div>
        </div>

        <!-- File Upload Settings -->
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">File Upload Settings</h6>
            </div>
            <div class="card-body">
                <div class="form-group">
                    <label for="max_file_size">Maximum File Size (MB)</label>
                    <input type="number" name="max_file_size" id="max_file_size" class="form-control" value="10" min="1" max="100">
                </div>

                <div class="form-group">
                    <label for="allowed_extensions">Allowed File Extensions (comma-separated)</label>
                    <input type="text" name="allowed_extensions" id="allowed_extensions" class="form-control"
                           value="jpg,jpeg,png,gif,pdf,doc,docx,txt,zip">
                </div>

                <div class="form-check mb-3">
                    <input type="checkbox" name="scan_uploads" id="scan_uploads" class="form-check-input" value="1">
                    <label for="scan_uploads" class="form-check-label">
                        Scan uploaded files for viruses (requires virus scanner)
                    </label>
                </div>
            </div>
        </div>

        <!-- Knowledge Base Settings -->
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">Knowledge Base Settings</h6>
            </div>
            <div class="card-body">
                <div class="form-check mb-3">
                    <input type="checkbox" name="suggest_kb_articles" id="suggest_kb_articles" class="form-check-input" value="1" checked>
                    <label for="suggest_kb_articles" class="form-check-label">
                        Suggest KB articles when users create tickets
                    </label>
                </div>

                <div class="form-check mb-3">
                    <input type="checkbox" name="public_kb_access" id="public_kb_access" class="form-check-input" value="1" checked>
                    <label for="public_kb_access" class="form-check-label">
                        Allow public access to KB articles
                    </label>
                </div>
            </div>
        </div>

        <!-- Working Hours -->
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">Business Hours</h6>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="business_hours_start">Start Time</label>
                            <input type="time" name="business_hours_start" id="business_hours_start" class="form-control" value="09:00">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="business_hours_end">End Time</label>
                            <input type="time" name="business_hours_end" id="business_hours_end" class="form-control" value="18:00">
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label>Working Days</label>
                    <div class="form-check">
                        <input type="checkbox" name="working_days[]" value="1" id="monday" class="form-check-input" checked>
                        <label for="monday" class="form-check-label">Monday</label>
                    </div>
                    <div class="form-check">
                        <input type="checkbox" name="working_days[]" value="2" id="tuesday" class="form-check-input" checked>
                        <label for="tuesday" class="form-check-label">Tuesday</label>
                    </div>
                    <div class="form-check">
                        <input type="checkbox" name="working_days[]" value="3" id="wednesday" class="form-check-input" checked>
                        <label for="wednesday" class="form-check-label">Wednesday</label>
                    </div>
                    <div class="form-check">
                        <input type="checkbox" name="working_days[]" value="4" id="thursday" class="form-check-input" checked>
                        <label for="thursday" class="form-check-label">Thursday</label>
                    </div>
                    <div class="form-check">
                        <input type="checkbox" name="working_days[]" value="5" id="friday" class="form-check-input" checked>
                        <label for="friday" class="form-check-label">Friday</label>
                    </div>
                    <div class="form-check">
                        <input type="checkbox" name="working_days[]" value="6" id="saturday" class="form-check-input">
                        <label for="saturday" class="form-check-label">Saturday</label>
                    </div>
                    <div class="form-check">
                        <input type="checkbox" name="working_days[]" value="0" id="sunday" class="form-check-input">
                        <label for="sunday" class="form-check-label">Sunday</label>
                    </div>
                </div>
            </div>
        </div>

        <div class="form-group">
            <button type="submit" class="btn btn-primary btn-lg">
                <i class="fas fa-save mr-2"></i>Save Settings
            </button>
        </div>
    </form>
</div>
@endsection
