@extends('layouts.admin')

@section('title', 'Employee Attendance Report')

@section('content')
<div class="container-fluid px-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-gray-800">Employee Attendance Report</h1>
        <button class="btn btn-success" onclick="exportReport()">
            <i class="fas fa-file-excel mr-2"></i>Export Report
        </button>
    </div>

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Filter Options</h6>
        </div>
        <div class="card-body">
            <form method="GET">
                <div class="row">
                    <div class="col-md-3">
                        <div class="form-group">
                            <label for="employee">Employee</label>
                            <select class="form-control" id="employee" name="employee_id">
                                <option value="">All Employees</option>
                                @foreach($employees ?? [] as $employee)
                                <option value="{{ $employee->id }}">{{ $employee->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label for="date_from">From Date</label>
                            <input type="date" class="form-control" id="date_from" name="date_from">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label for="date_to">To Date</label>
                            <input type="date" class="form-control" id="date_to" name="date_to">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>&nbsp;</label>
                            <button type="submit" class="btn btn-primary btn-block">
                                <i class="fas fa-search mr-2"></i>Generate Report
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Attendance Records</h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th>Employee</th>
                            <th>Date</th>
                            <th>Check In</th>
                            <th>Check Out</th>
                            <th>Hours Worked</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($attendanceRecords ?? [] as $record)
                        <tr>
                            <td>{{ $record->employee_name ?? 'N/A' }}</td>
                            <td>{{ isset($record->date) ? date('M d, Y', strtotime($record->date)) : 'N/A' }}</td>
                            <td>{{ $record->check_in ?? 'N/A' }}</td>
                            <td>{{ $record->check_out ?? 'N/A' }}</td>
                            <td>{{ $record->hours_worked ?? '0' }}h</td>
                            <td>
                                <span class="badge badge-{{ ($record->status ?? 'present') == 'present' ? 'success' : 'danger' }}">
                                    {{ ucfirst($record->status ?? 'Present') }}
                                </span>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted">No attendance records found</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
function exportReport() {
    console.log('Exporting attendance report');
}
</script>
@endsection
