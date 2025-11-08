@extends('layouts.admin')
@section('title', 'จัดการสิ่งอำนวยความสะดวก')
@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">จัดการสิ่งอำนวยความสะดวก</h1>
        <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#addModal">
            <i class="fas fa-plus"></i> เพิ่มสิ่งอำนวยความสะดวก
        </button>
    </div>

    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr><th width="50">ลำดับ</th><th width="80">ไอคอน</th><th>ชื่อ</th><th>หมวดหมู่</th><th>สถานะ</th><th width="150">การจัดการ</th></tr>
                    </thead>
                    <tbody id="sortable">
                        @forelse($facilities as $facility)
                            <tr data-id="{{ $facility->id }}">
                                <td>
                                    <i class="fas fa-grip-vertical text-muted" style="cursor: move;"></i>
                                    {{ $facility->sort_order }}
                                </td>
                                <td><i class="fas fa-{{ $facility->icon }} fa-2x"></i></td>
                                <td>{{ $facility->name }}</td>
                                <td><span class="badge badge-secondary">{{ $facility->category }}</span></td>
                                <td>
                                    <div class="custom-control custom-switch">
                                        <input type="checkbox" class="custom-control-input" id="status{{ $facility->id }}"
                                               {{ $facility->is_active ? 'checked' : '' }} onchange="toggleStatus({{ $facility->id }})">
                                        <label class="custom-control-label" for="status{{ $facility->id }}"></label>
                                    </div>
                                </td>
                                <td>
                                    <button class="btn btn-sm btn-primary" onclick="editFacility({{ $facility->id }})">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button class="btn btn-sm btn-danger" onclick="deleteFacility({{ $facility->id }})">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="text-center">ยังไม่มีข้อมูล</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Add Modal -->
<div class="modal fade" id="addModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">เพิ่มสิ่งอำนวยความสะดวก</h5>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <form action="{{ route('admin.hotels.facilities.store') }}" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="form-group">
                        <label>ชื่อ <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="name" required>
                    </div>
                    <div class="form-group">
                        <label>ไอคอน (Font Awesome) <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="icon" placeholder="wifi, swimming-pool, parking" required>
                    </div>
                    <div class="form-group">
                        <label>หมวดหมู่</label>
                        <select class="form-control" name="category">
                            <option value="general">ทั่วไป</option>
                            <option value="recreation">สันทนาการ</option>
                            <option value="service">บริการ</option>
                            <option value="food">อาหาร</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">ยกเลิก</button>
                    <button type="submit" class="btn btn-primary">บันทึก</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/ui/1.12.1/jquery-ui.min.js"></script>
<script>
$(function() {
    $("#sortable").sortable({
        handle: '.fa-grip-vertical',
        update: function() {
            const order = $(this).sortable('toArray', {attribute: 'data-id'});
            fetch('{{ route("admin.hotels.facilities.reorder") }}', {
                method: 'POST',
                headers: {'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}'},
                body: JSON.stringify({order})
            });
        }
    });
});

function toggleStatus(id) {
    fetch(`/admin/hotels/facilities/${id}/toggle-status`, {
        method: 'POST',
        headers: {'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}'}
    });
}

function deleteFacility(id) {
    if (confirm('คุณต้องการลบรายการนี้?')) {
        fetch(`/admin/hotels/facilities/${id}`, {
            method: 'DELETE',
            headers: {'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}'}
        }).then(() => location.reload());
    }
}
</script>
@endsection
