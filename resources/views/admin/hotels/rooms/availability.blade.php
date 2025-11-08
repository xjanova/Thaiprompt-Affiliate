@extends('layouts.admin')

@section('title', 'ปฏิทินห้องว่าง - ' . $roomType->name)

@section('content')
<div class="container-fluid">
    <div class="mb-4">
        <h1 class="h3 mb-2">ปฏิทินห้องว่าง: {{ $roomType->name }}</h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="{{ route('admin.hotels.index') }}">โรงแรม</a></li>
                <li class="breadcrumb-item"><a href="{{ route('admin.hotels.show', $hotel->id) }}">{{ $hotel->name }}</a></li>
                <li class="breadcrumb-item"><a href="{{ route('admin.hotels.rooms.index', $hotel->id) }}">ห้องพัก</a></li>
                <li class="breadcrumb-item active">ปฏิทิน</li>
            </ol>
        </nav>
    </div>

    <div class="row mb-4">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">ปฏิทินความพร้อม</h5>
                    <div>
                        <button type="button" class="btn btn-sm btn-primary" id="prevMonth">
                            <i class="fas fa-chevron-left"></i> เดือนก่อน
                        </button>
                        <span id="currentMonth" class="mx-3 font-weight-bold"></span>
                        <button type="button" class="btn btn-sm btn-primary" id="nextMonth">
                            เดือนถัดไป <i class="fas fa-chevron-right"></i>
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered text-center" id="calendarTable">
                            <thead class="thead-light">
                                <tr>
                                    <th>อา</th><th>จ</th><th>อ</th><th>พ</th><th>พฤ</th><th>ศ</th><th>ส</th>
                                </tr>
                            </thead>
                            <tbody id="calendarBody"></tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card mb-3">
                <div class="card-header">
                    <h5 class="mb-0">ข้อมูลห้อง</h5>
                </div>
                <div class="card-body">
                    <p><strong>จำนวนห้องทั้งหมด:</strong> {{ $roomType->total_rooms }} ห้อง</p>
                    <p><strong>ราคาพื้นฐาน:</strong> ฿{{ number_format($roomType->base_price) }}</p>
                    @if($roomType->weekend_price)
                        <p><strong>ราคาวันหยุด:</strong> ฿{{ number_format($roomType->weekend_price) }}</p>
                    @endif
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">แก้ไขห้องว่างและราคา</h5>
                </div>
                <div class="card-body">
                    <form id="updateForm">
                        <input type="hidden" id="selectedDate" name="date">

                        <div class="form-group">
                            <label>วันที่เลือก:</label>
                            <div id="displayDate" class="font-weight-bold text-primary">-</div>
                        </div>

                        <div class="form-group">
                            <label for="available_rooms">จำนวนห้องว่าง:</label>
                            <input type="number" class="form-control" id="available_rooms"
                                   name="available_rooms" min="0" max="{{ $roomType->total_rooms }}">
                        </div>

                        <div class="form-group">
                            <label for="price">ราคา (บาท):</label>
                            <input type="number" class="form-control" id="price"
                                   name="price" step="0.01" min="0">
                        </div>

                        <button type="submit" class="btn btn-primary btn-block" id="submitBtn" disabled>
                            <i class="fas fa-save"></i> บันทึก
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
let currentDate = new Date();
let selectedDate = null;

const thaiMonths = ['มกราคม', 'กุมภาพันธ์', 'มีนาคม', 'เมษายน', 'พฤษภาคม', 'มิถุนายน',
                     'กรกฎาคม', 'สิงหาคม', 'กันยายน', 'ตุลาคม', 'พฤศจิกายน', 'ธันวาคม'];

function generateCalendar() {
    const year = currentDate.getFullYear();
    const month = currentDate.getMonth();

    document.getElementById('currentMonth').textContent = `${thaiMonths[month]} ${year + 543}`;

    const firstDay = new Date(year, month, 1).getDay();
    const daysInMonth = new Date(year, month + 1, 0).getDate();

    let html = '<tr>';
    for (let i = 0; i < firstDay; i++) html += '<td></td>';

    for (let day = 1; day <= daysInMonth; day++) {
        if ((firstDay + day - 1) % 7 === 0 && day !== 1) html += '</tr><tr>';

        const date = `${year}-${String(month + 1).padStart(2, '0')}-${String(day).padStart(2, '0')}`;
        const isToday = new Date().toDateString() === new Date(year, month, day).toDateString();

        html += `<td class="calendar-day ${isToday ? 'bg-light' : ''}" data-date="${date}" style="cursor: pointer; padding: 10px;">
                    <div class="font-weight-bold">${day}</div>
                    <div class="availability-info" id="info-${date}"><small class="text-muted">โหลด...</small></div>
                 </td>`;
    }

    html += '</tr>';
    document.getElementById('calendarBody').innerHTML = html;
    loadAvailability();

    document.querySelectorAll('.calendar-day').forEach(cell => {
        cell.addEventListener('click', function() {
            document.querySelectorAll('.calendar-day').forEach(c => c.classList.remove('table-primary'));
            this.classList.add('table-primary');
            selectDate(this.dataset.date);
        });
    });
}

function loadAvailability() {
    const year = currentDate.getFullYear();
    const month = currentDate.getMonth();
    const startDate = `${year}-${String(month + 1).padStart(2, '0')}-01`;
    const endDate = new Date(year, month + 1, 0);
    const endDateStr = `${year}-${String(month + 1).padStart(2, '0')}-${String(endDate.getDate()).padStart(2, '0')}`;

    fetch(`/admin/hotels/{{ $hotel->id }}/rooms/{{ $roomType->id }}/availability?start=${startDate}&end=${endDateStr}`)
        .then(response => response.json())
        .then(data => {
            data.forEach(item => {
                const infoDiv = document.getElementById(`info-${item.date}`);
                if (infoDiv) {
                    infoDiv.innerHTML = `
                        <small class="d-block ${item.available_rooms > 0 ? 'text-success' : 'text-danger'}">
                            ${item.available_rooms} ห้อง
                        </small>
                        <small class="d-block text-primary">฿${Number(item.price).toLocaleString()}</small>
                    `;
                }
            });
        });
}

function selectDate(date) {
    selectedDate = date;
    document.getElementById('selectedDate').value = date;
    document.getElementById('displayDate').textContent = formatDateThai(date);
    document.getElementById('submitBtn').disabled = false;

    fetch(`/admin/hotels/{{ $hotel->id }}/rooms/{{ $roomType->id }}/availability?date=${date}`)
        .then(response => response.json())
        .then(data => {
            if (data.length > 0) {
                document.getElementById('available_rooms').value = data[0].available_rooms;
                document.getElementById('price').value = data[0].price;
            } else {
                document.getElementById('available_rooms').value = {{ $roomType->total_rooms }};
                document.getElementById('price').value = {{ $roomType->base_price }};
            }
        });
}

function formatDateThai(dateStr) {
    const date = new Date(dateStr);
    return `${date.getDate()} ${thaiMonths[date.getMonth()]} ${date.getFullYear() + 543}`;
}

document.getElementById('prevMonth').addEventListener('click', () => {
    currentDate.setMonth(currentDate.getMonth() - 1);
    generateCalendar();
});

document.getElementById('nextMonth').addEventListener('click', () => {
    currentDate.setMonth(currentDate.getMonth() + 1);
    generateCalendar();
});

document.getElementById('updateForm').addEventListener('submit', (e) => {
    e.preventDefault();
    const formData = new FormData(e.target);
    const data = Object.fromEntries(formData.entries());

    fetch(`/admin/hotels/{{ $hotel->id }}/rooms/{{ $roomType->id }}/availability`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        body: JSON.stringify(data)
    })
    .then(response => response.json())
    .then(result => {
        if (result.success) {
            alert('บันทึกสำเร็จ');
            loadAvailability();
        } else {
            alert('เกิดข้อผิดพลาด');
        }
    });
});

generateCalendar();
</script>
@endsection
