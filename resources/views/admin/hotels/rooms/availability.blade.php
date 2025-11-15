@extends('layouts.admin')

@section('content')
<div class="min-h-screen bg-gradient-to-br from-gray-50 via-blue-50 to-indigo-50 dark:from-gray-900 dark:via-gray-800 dark:to-gray-900 p-6">
    <!-- Gradient Header -->
    <div class="relative overflow-hidden bg-gradient-to-br from-blue-600 via-indigo-600 to-purple-600 rounded-3xl shadow-2xl p-8 mb-8">
        <div class="absolute inset-0 bg-black/10"></div>
        <div class="relative">
            <div class="flex items-center justify-between mb-4">
                <div class="flex items-center">
                    <div class="w-16 h-16 bg-white/20 backdrop-blur-sm rounded-2xl flex items-center justify-center mr-4">
                        <svg class="w-9 h-9 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                        </svg>
                    </div>
                    <div>
                        <h1 class="text-4xl font-bold text-white mb-2" data-translate>ปฏิทินห้องว่าง</h1>
                        <p class="text-blue-100">{{ $roomType->name }}</p>
                        <p class="text-blue-200 text-sm">{{ $hotel->name }}</p>
                    </div>
                </div>

                <!-- Language Switcher -->
                <div class="relative" x-data="{ open: false }">
                    <button @click="open = !open"
                            class="px-4 py-2 bg-white/20 backdrop-blur-sm hover:bg-white/30 rounded-xl transition text-white font-semibold flex items-center">
                        <span class="mr-2">🇹🇭</span>
                        <span data-translate>ภาษา</span>
                        <svg class="w-4 h-4 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                        </svg>
                    </button>
                    <div x-show="open"
                         @click.away="open = false"
                         x-transition
                         class="absolute right-0 mt-2 w-48 bg-white dark:bg-gray-800 rounded-xl shadow-lg py-2 z-50">
                        <a href="#" class="flex items-center px-4 py-2 hover:bg-gray-100 dark:hover:bg-gray-700 transition">
                            <span class="mr-2">🇹🇭</span>
                            <span>ไทย</span>
                        </a>
                        <a href="#" class="flex items-center px-4 py-2 hover:bg-gray-100 dark:hover:bg-gray-700 transition">
                            <span class="mr-2">🇬🇧</span>
                            <span>English</span>
                        </a>
                        <a href="#" class="flex items-center px-4 py-2 hover:bg-gray-100 dark:hover:bg-gray-700 transition">
                            <span class="mr-2">🇨🇳</span>
                            <span>中文</span>
                        </a>
                        <a href="#" class="flex items-center px-4 py-2 hover:bg-gray-100 dark:hover:bg-gray-700 transition">
                            <span class="mr-2">🇯🇵</span>
                            <span>日本語</span>
                        </a>
                    </div>
                </div>
            </div>

            <div class="flex space-x-3">
                <a href="{{ route('admin.hotels.rooms.index', $hotel->id) }}"
                   class="px-6 py-3 bg-white/20 backdrop-blur-sm hover:bg-white/30 rounded-xl transition text-white font-semibold flex items-center">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                    </svg>
                    <span data-translate>กลับ</span>
                </a>
                <a href="{{ route('admin.hotels.rooms.show', [$hotel->id, $roomType->id]) }}"
                   class="px-6 py-3 bg-white/20 backdrop-blur-sm hover:bg-white/30 rounded-xl transition text-white font-semibold flex items-center">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                    </svg>
                    <span data-translate>ดูข้อมูลห้อง</span>
                </a>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Calendar Section (2/3) -->
        <div class="lg:col-span-2">
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
                <div class="p-6 border-b border-gray-200 dark:border-gray-700 flex justify-between items-center">
                    <h2 class="text-2xl font-bold text-gray-900 dark:text-white flex items-center">
                        <svg class="w-6 h-6 mr-2 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                        </svg>
                        <span data-translate>ปฏิทินความพร้อม</span>
                    </h2>
                    <div class="flex items-center space-x-3">
                        <button type="button" id="prevMonth"
                                class="px-4 py-2 bg-blue-600 hover:bg-blue-700 dark:bg-blue-500 dark:hover:bg-blue-600 text-white rounded-lg transition font-semibold">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                            </svg>
                        </button>
                        <span id="currentMonth" class="text-lg font-bold text-gray-900 dark:text-white"></span>
                        <button type="button" id="nextMonth"
                                class="px-4 py-2 bg-blue-600 hover:bg-blue-700 dark:bg-blue-500 dark:hover:bg-blue-600 text-white rounded-lg transition font-semibold">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                            </svg>
                        </button>
                    </div>
                </div>
                <div class="p-6">
                    <div class="overflow-x-auto">
                        <table class="w-full" id="calendarTable">
                            <thead>
                                <tr class="border-b-2 border-gray-200 dark:border-gray-700">
                                    <th class="py-3 px-2 text-center text-sm font-bold text-red-600 dark:text-red-400" data-translate>อา</th>
                                    <th class="py-3 px-2 text-center text-sm font-bold text-gray-700 dark:text-gray-300" data-translate>จ</th>
                                    <th class="py-3 px-2 text-center text-sm font-bold text-gray-700 dark:text-gray-300" data-translate>อ</th>
                                    <th class="py-3 px-2 text-center text-sm font-bold text-gray-700 dark:text-gray-300" data-translate>พ</th>
                                    <th class="py-3 px-2 text-center text-sm font-bold text-gray-700 dark:text-gray-300" data-translate>พฤ</th>
                                    <th class="py-3 px-2 text-center text-sm font-bold text-gray-700 dark:text-gray-300" data-translate>ศ</th>
                                    <th class="py-3 px-2 text-center text-sm font-bold text-blue-600 dark:text-blue-400" data-translate>ส</th>
                                </tr>
                            </thead>
                            <tbody id="calendarBody"></tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Legend -->
            <div class="mt-6 bg-white dark:bg-gray-800 rounded-2xl shadow-xl border border-gray-200 dark:border-gray-700 p-6">
                <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4" data-translate>คำอธิบาย</h3>
                <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
                    <div class="flex items-center">
                        <div class="w-4 h-4 bg-blue-100 dark:bg-blue-900/30 border-2 border-blue-500 rounded mr-2"></div>
                        <span class="text-sm text-gray-700 dark:text-gray-300" data-translate>วันนี้</span>
                    </div>
                    <div class="flex items-center">
                        <div class="w-4 h-4 bg-indigo-100 dark:bg-indigo-900/30 border-2 border-indigo-500 rounded mr-2"></div>
                        <span class="text-sm text-gray-700 dark:text-gray-300" data-translate>วันที่เลือก</span>
                    </div>
                    <div class="flex items-center">
                        <div class="w-4 h-4 bg-green-100 dark:bg-green-900/30 rounded mr-2"></div>
                        <span class="text-sm text-gray-700 dark:text-gray-300" data-translate>มีห้องว่าง</span>
                    </div>
                    <div class="flex items-center">
                        <div class="w-4 h-4 bg-red-100 dark:bg-red-900/30 rounded mr-2"></div>
                        <span class="text-sm text-gray-700 dark:text-gray-300" data-translate>ห้องเต็ม</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Sidebar (1/3) -->
        <div class="lg:col-span-1 space-y-6">
            <!-- Room Info Card -->
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
                <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="text-xl font-bold text-gray-900 dark:text-white flex items-center">
                        <svg class="w-5 h-5 mr-2 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <span data-translate>ข้อมูลห้อง</span>
                    </h3>
                </div>
                <div class="p-6 space-y-4">
                    <div class="flex items-center justify-between">
                        <span class="text-gray-600 dark:text-gray-400" data-translate>จำนวนห้องทั้งหมด</span>
                        <span class="text-gray-900 dark:text-white font-bold">{{ $roomType->total_rooms }} <span data-translate>ห้อง</span></span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-gray-600 dark:text-gray-400" data-translate>ราคาพื้นฐาน</span>
                        <span class="text-blue-600 dark:text-blue-400 font-bold">฿{{ number_format($roomType->base_price) }}</span>
                    </div>
                    @if($roomType->weekend_price)
                    <div class="flex items-center justify-between">
                        <span class="text-gray-600 dark:text-gray-400" data-translate>ราคาวันหยุด</span>
                        <span class="text-blue-600 dark:text-blue-400 font-bold">฿{{ number_format($roomType->weekend_price) }}</span>
                    </div>
                    @endif
                </div>
            </div>

            <!-- Edit Form Card -->
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
                <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="text-xl font-bold text-gray-900 dark:text-white flex items-center">
                        <svg class="w-5 h-5 mr-2 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                        </svg>
                        <span data-translate>แก้ไขห้องว่างและราคา</span>
                    </h3>
                </div>
                <div class="p-6">
                    <form id="updateForm" class="space-y-4">
                        <input type="hidden" id="selectedDate" name="date">

                        <div>
                            <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2" data-translate>วันที่เลือก</label>
                            <div id="displayDate" class="px-4 py-3 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-xl text-blue-900 dark:text-blue-100 font-bold">
                                -
                            </div>
                        </div>

                        <div>
                            <label for="available_rooms" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2" data-translate>จำนวนห้องว่าง</label>
                            <input type="number"
                                   id="available_rooms"
                                   name="available_rooms"
                                   min="0"
                                   max="{{ $roomType->total_rooms }}"
                                   class="w-full px-4 py-3 bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 focus:border-transparent text-gray-900 dark:text-white">
                        </div>

                        <div>
                            <label for="price" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2" data-translate>ราคา (บาท)</label>
                            <input type="number"
                                   id="price"
                                   name="price"
                                   step="0.01"
                                   min="0"
                                   class="w-full px-4 py-3 bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 focus:border-transparent text-gray-900 dark:text-white">
                        </div>

                        <button type="submit"
                                id="submitBtn"
                                disabled
                                class="w-full px-6 py-3 bg-blue-600 hover:bg-blue-700 dark:bg-blue-500 dark:hover:bg-blue-600 text-white rounded-xl transition font-semibold disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"/>
                            </svg>
                            <span data-translate>บันทึก</span>
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.calendar-day {
    padding: 12px;
    cursor: pointer;
    transition: all 0.2s;
    border: 2px solid transparent;
    border-radius: 8px;
}

.calendar-day:hover {
    background: rgba(59, 130, 246, 0.1) !important;
    transform: scale(1.05);
}

.calendar-day.today {
    background: rgba(59, 130, 246, 0.1);
    border-color: rgb(59, 130, 246);
}

.calendar-day.selected {
    background: rgba(99, 102, 241, 0.2);
    border-color: rgb(99, 102, 241);
}

.calendar-day.has-rooms {
    background: rgba(34, 197, 94, 0.1);
}

.calendar-day.no-rooms {
    background: rgba(239, 68, 68, 0.1);
}

.dark .calendar-day {
    border-color: transparent;
}

.dark .calendar-day.today {
    background: rgba(59, 130, 246, 0.2);
    border-color: rgb(59, 130, 246);
}

.dark .calendar-day.selected {
    background: rgba(99, 102, 241, 0.3);
    border-color: rgb(99, 102, 241);
}

.dark .calendar-day.has-rooms {
    background: rgba(34, 197, 94, 0.2);
}

.dark .calendar-day.no-rooms {
    background: rgba(239, 68, 68, 0.2);
}
</style>

<script>
let currentDate = new Date();
let selectedDate = null;

const thaiMonths = ['มกราคม', 'กุมภาพันธ์', 'มีนาคม', 'เมษายน', 'พฤษภาคม', 'มิถุนายน',
                     'กรกฎาคม', 'สิงหาคม', 'กันยายน', 'ตุลาคม', 'พฤศจิกายน', 'ธันวาคม'];

/**
 * สร้างปฏิทิน
 */
function generateCalendar() {
    const year = currentDate.getFullYear();
    const month = currentDate.getMonth();

    document.getElementById('currentMonth').textContent = `${thaiMonths[month]} ${year + 543}`;

    const firstDay = new Date(year, month, 1).getDay();
    const daysInMonth = new Date(year, month + 1, 0).getDate();

    let html = '<tr>';

    // เติมช่องว่างก่อนวันที่ 1
    for (let i = 0; i < firstDay; i++) {
        html += '<td></td>';
    }

    // สร้างวันที่
    for (let day = 1; day <= daysInMonth; day++) {
        if ((firstDay + day - 1) % 7 === 0 && day !== 1) {
            html += '</tr><tr>';
        }

        const date = `${year}-${String(month + 1).padStart(2, '0')}-${String(day).padStart(2, '0')}`;
        const isToday = new Date().toDateString() === new Date(year, month, day).toDateString();

        html += `<td class="calendar-day ${isToday ? 'today' : ''}" data-date="${date}">
                    <div class="text-center">
                        <div class="font-bold text-lg text-gray-900 dark:text-white mb-1">${day}</div>
                        <div class="availability-info text-xs" id="info-${date}">
                            <span class="text-gray-400 dark:text-gray-500">โหลด...</span>
                        </div>
                    </div>
                 </td>`;
    }

    html += '</tr>';
    document.getElementById('calendarBody').innerHTML = html;
    loadAvailability();

    // เพิ่ม event listeners
    document.querySelectorAll('.calendar-day').forEach(cell => {
        cell.addEventListener('click', function() {
            document.querySelectorAll('.calendar-day').forEach(c => c.classList.remove('selected'));
            this.classList.add('selected');
            selectDate(this.dataset.date);
        });
    });
}

/**
 * โหลดข้อมูลความพร้อม
 */
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
                const cell = document.querySelector(`[data-date="${item.date}"]`);

                if (infoDiv && cell) {
                    const hasRooms = item.available_rooms > 0;

                    // อัพเดทคลาส
                    cell.classList.add(hasRooms ? 'has-rooms' : 'no-rooms');

                    // อัพเดทข้อมูล
                    infoDiv.innerHTML = `
                        <div class="font-semibold ${hasRooms ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400'}">
                            ${item.available_rooms} ห้อง
                        </div>
                        <div class="text-blue-600 dark:text-blue-400 font-medium">
                            ฿${Number(item.price).toLocaleString()}
                        </div>
                    `;
                }
            });
        })
        .catch(error => {
            console.error('Error loading availability:', error);
        });
}

/**
 * เลือกวันที่
 */
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
        })
        .catch(error => {
            console.error('Error loading date data:', error);
        });
}

/**
 * แปลงวันที่เป็นภาษาไทย
 */
function formatDateThai(dateStr) {
    const date = new Date(dateStr);
    return `${date.getDate()} ${thaiMonths[date.getMonth()]} ${date.getFullYear() + 543}`;
}

// Event: เดือนก่อนหน้า
document.getElementById('prevMonth').addEventListener('click', () => {
    currentDate.setMonth(currentDate.getMonth() - 1);
    generateCalendar();
});

// Event: เดือนถัดไป
document.getElementById('nextMonth').addEventListener('click', () => {
    currentDate.setMonth(currentDate.getMonth() + 1);
    generateCalendar();
});

// Event: บันทึกข้อมูล
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
            alert('เกิดข้อผิดพลาด: ' + (result.message || 'กรุณาลองใหม่อีกครั้ง'));
        }
    })
    .catch(error => {
        console.error('Error saving data:', error);
        alert('เกิดข้อผิดพลาดในการบันทึก');
    });
});

// สร้างปฏิทินเมื่อโหลดหน้า
generateCalendar();
</script>
@endsection
