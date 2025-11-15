@extends('layouts.admin')

@section('title', 'ปฏิทินการจอง')

@section('content')
<div class="min-h-screen bg-gradient-to-br from-gray-50 via-indigo-50 to-violet-50 dark:from-gray-900 dark:via-gray-800 dark:to-gray-900 p-6">
    <!-- Language Switcher -->
    <div class="flex justify-end mb-4" x-data="{ open: false }">
        <div class="relative">
            <button @click="open = !open" class="flex items-center space-x-2 px-4 py-2 bg-white dark:bg-gray-800 rounded-xl shadow-lg border border-gray-200 dark:border-gray-700 hover:shadow-xl transition-all">
                <svg class="w-5 h-5 text-gray-600 dark:text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5h12M9 3v2m1.048 9.5A18.022 18.022 0 016.412 9m6.088 9h7M11 21l5-10 5 10M12.751 5C11.783 10.77 8.07 15.61 3 18.129"/>
                </svg>
                <span class="text-sm font-semibold text-gray-700 dark:text-gray-200">ภาษา</span>
            </button>
            <div x-show="open" @click.away="open = false" x-transition class="absolute right-0 mt-2 w-48 bg-white dark:bg-gray-800 rounded-xl shadow-2xl border border-gray-200 dark:border-gray-700 py-2 z-50">
                <a href="#" class="flex items-center px-4 py-2 hover:bg-gray-100 dark:hover:bg-gray-700 transition">
                    <span class="text-2xl mr-3">🇹🇭</span>
                    <span class="text-gray-700 dark:text-gray-200">ไทย</span>
                </a>
                <a href="#" class="flex items-center px-4 py-2 hover:bg-gray-100 dark:hover:bg-gray-700 transition">
                    <span class="text-2xl mr-3">🇬🇧</span>
                    <span class="text-gray-700 dark:text-gray-200">English</span>
                </a>
                <a href="#" class="flex items-center px-4 py-2 hover:bg-gray-100 dark:hover:bg-gray-700 transition">
                    <span class="text-2xl mr-3">🇨🇳</span>
                    <span class="text-gray-700 dark:text-gray-200">中文</span>
                </a>
                <a href="#" class="flex items-center px-4 py-2 hover:bg-gray-100 dark:hover:bg-gray-700 transition">
                    <span class="text-2xl mr-3">🇯🇵</span>
                    <span class="text-gray-700 dark:text-gray-200">日本語</span>
                </a>
            </div>
        </div>
    </div>

    <!-- Gradient Header -->
    <div class="relative overflow-hidden bg-gradient-to-br from-indigo-600 via-violet-600 to-purple-600 rounded-3xl shadow-2xl p-8 mb-8">
        <div class="absolute inset-0 bg-black/10"></div>
        <div class="absolute inset-0 bg-white/5 backdrop-blur-sm"></div>

        <div class="relative flex items-center justify-between">
            <div class="flex items-center">
                <div class="w-16 h-16 bg-white/20 backdrop-blur-sm rounded-2xl flex items-center justify-center mr-4">
                    <svg class="w-9 h-9 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                </div>
                <div>
                    <h1 class="text-4xl font-bold text-white mb-2" data-translate>ปฏิทินการจอง</h1>
                    <p class="text-indigo-100" data-translate>ดูและจัดการการจองในมุมมองปฏิทิน</p>
                </div>
            </div>

            <div class="flex space-x-3">
                <a href="{{ route('admin.hotels.bookings.create') }}"
                   class="relative overflow-hidden group px-6 py-3 bg-white/20 backdrop-blur-sm hover:bg-white/30 rounded-xl transition-all text-white font-semibold flex items-center shadow-lg">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                    <span data-translate>สร้างการจอง</span>
                </a>
                <a href="{{ route('admin.hotels.bookings.index') }}"
                   class="relative overflow-hidden group px-6 py-3 bg-white/20 backdrop-blur-sm hover:bg-white/30 rounded-xl transition-all text-white font-semibold flex items-center shadow-lg">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                    </svg>
                    <span data-translate>กลับ</span>
                </a>
            </div>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
        <div class="bg-gradient-to-br from-blue-500 to-indigo-600 rounded-2xl shadow-xl p-6 text-white transform hover:scale-105 transition-all">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-blue-100 text-sm mb-1" data-translate>การจองวันนี้</p>
                    <p class="text-3xl font-bold">{{ $todayBookings ?? 0 }}</p>
                </div>
                <div class="p-3 bg-white/20 backdrop-blur-sm rounded-xl">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
            </div>
        </div>

        <div class="bg-gradient-to-br from-green-500 to-emerald-600 rounded-2xl shadow-xl p-6 text-white transform hover:scale-105 transition-all">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-green-100 text-sm mb-1" data-translate>เช็คอินวันนี้</p>
                    <p class="text-3xl font-bold">{{ $todayCheckIns ?? 0 }}</p>
                </div>
                <div class="p-3 bg-white/20 backdrop-blur-sm rounded-xl">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"/>
                    </svg>
                </div>
            </div>
        </div>

        <div class="bg-gradient-to-br from-purple-500 to-pink-600 rounded-2xl shadow-xl p-6 text-white transform hover:scale-105 transition-all">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-purple-100 text-sm mb-1" data-translate>เช็คเอาท์วันนี้</p>
                    <p class="text-3xl font-bold">{{ $todayCheckOuts ?? 0 }}</p>
                </div>
                <div class="p-3 bg-white/20 backdrop-blur-sm rounded-xl">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                    </svg>
                </div>
            </div>
        </div>

        <div class="bg-gradient-to-br from-amber-500 to-orange-600 rounded-2xl shadow-xl p-6 text-white transform hover:scale-105 transition-all">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-amber-100 text-sm mb-1" data-translate>อัตราการเข้าพัก</p>
                    <p class="text-3xl font-bold">{{ $occupancyRate ?? 0 }}%</p>
                </div>
                <div class="p-3 bg-white/20 backdrop-blur-sm rounded-xl">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                    </svg>
                </div>
            </div>
        </div>
    </div>

    <!-- Calendar Card -->
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
        <div class="p-6">
            <div id="calendar"></div>
        </div>
    </div>

    <!-- Legend -->
    <div class="mt-6 bg-white dark:bg-gray-800 rounded-2xl shadow-xl border border-gray-200 dark:border-gray-700 p-6">
        <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4 flex items-center">
            <svg class="w-5 h-5 mr-2 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/>
            </svg>
            <span data-translate>สีสถานะการจอง</span>
        </h3>
        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4">
            <div class="flex items-center">
                <div class="w-4 h-4 bg-amber-500 rounded mr-2"></div>
                <span class="text-sm text-gray-700 dark:text-gray-300" data-translate>รอชำระเงิน</span>
            </div>
            <div class="flex items-center">
                <div class="w-4 h-4 bg-green-500 rounded mr-2"></div>
                <span class="text-sm text-gray-700 dark:text-gray-300" data-translate>ยืนยันแล้ว</span>
            </div>
            <div class="flex items-center">
                <div class="w-4 h-4 bg-blue-500 rounded mr-2"></div>
                <span class="text-sm text-gray-700 dark:text-gray-300" data-translate>เช็คอินแล้ว</span>
            </div>
            <div class="flex items-center">
                <div class="w-4 h-4 bg-purple-500 rounded mr-2"></div>
                <span class="text-sm text-gray-700 dark:text-gray-300" data-translate>เช็คเอาท์แล้ว</span>
            </div>
            <div class="flex items-center">
                <div class="w-4 h-4 bg-red-500 rounded mr-2"></div>
                <span class="text-sm text-gray-700 dark:text-gray-300" data-translate>ยกเลิกแล้ว</span>
            </div>
            <div class="flex items-center">
                <div class="w-4 h-4 bg-emerald-500 rounded mr-2"></div>
                <span class="text-sm text-gray-700 dark:text-gray-300" data-translate>เสร็จสิ้น</span>
            </div>
        </div>
    </div>
</div>

<!-- FullCalendar CSS -->
<link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/index.global.min.css" rel="stylesheet">

<!-- FullCalendar JS -->
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/index.global.min.js"></script>

<script>
/**
 * สร้างและกำหนดค่า FullCalendar
 */
document.addEventListener('DOMContentLoaded', function() {
    const calendarEl = document.getElementById('calendar');

    // แมปสีตามสถานะการจอง
    const statusColors = {
        'pending': '#f59e0b',      // amber
        'confirmed': '#10b981',    // green
        'checked_in': '#3b82f6',   // blue
        'checked_out': '#a855f7',  // purple
        'cancelled': '#ef4444',    // red
        'completed': '#059669'     // emerald
    };

    // ข้อมูลการจองจาก backend (ตัวอย่าง)
    const bookings = @json($bookings ?? []);

    // แปลงข้อมูลการจองเป็น events สำหรับ FullCalendar
    const events = bookings.map(booking => ({
        id: booking.id,
        title: `${booking.guest_name} - ${booking.hotel_name}`,
        start: booking.check_in_date,
        end: booking.check_out_date,
        backgroundColor: statusColors[booking.status] || '#6b7280',
        borderColor: statusColors[booking.status] || '#6b7280',
        extendedProps: {
            bookingNumber: booking.booking_number,
            hotel: booking.hotel_name,
            roomType: booking.room_type_name,
            guest: booking.guest_name,
            status: booking.status,
            totalAmount: booking.total_amount
        }
    }));

    const calendar = new FullCalendar.Calendar(calendarEl, {
        initialView: 'dayGridMonth',
        headerToolbar: {
            left: 'prev,next today',
            center: 'title',
            right: 'dayGridMonth,timeGridWeek,timeGridDay,listMonth'
        },
        buttonText: {
            today: 'วันนี้',
            month: 'เดือน',
            week: 'สัปดาห์',
            day: 'วัน',
            list: 'รายการ'
        },
        locale: 'th',
        firstDay: 1, // เริ่มที่วันจันทร์
        height: 'auto',
        events: events,
        eventClick: function(info) {
            const event = info.event;
            const props = event.extendedProps;

            // แสดง modal หรือ redirect ไปหน้ารายละเอียด
            if (confirm(`การจอง: ${props.bookingNumber}\n\nโรงแรม: ${props.hotel}\nผู้เข้าพัก: ${props.guest}\n\nต้องการดูรายละเอียด?`)) {
                window.location.href = `/admin/hotels/bookings/${event.id}`;
            }
        },
        eventDidMount: function(info) {
            // เพิ่ม tooltip
            info.el.title = `${info.event.extendedProps.bookingNumber}\n${info.event.extendedProps.hotel}\n${info.event.extendedProps.guest}`;
        },
        // สไตล์ custom
        dayMaxEvents: 3,
        moreLinkText: function(num) {
            return `+${num} เพิ่มเติม`;
        },
        views: {
            dayGridMonth: {
                dayMaxEvents: 3
            }
        },
        // Dark mode support
        themeSystem: 'standard'
    });

    calendar.render();

    // ปรับสไตล์ FullCalendar ให้เข้ากับ dark mode
    const updateCalendarTheme = () => {
        const isDark = document.documentElement.classList.contains('dark');
        const calendarElement = document.querySelector('.fc');

        if (isDark) {
            calendarElement.style.backgroundColor = '#1f2937';
            calendarElement.style.color = '#f3f4f6';
        } else {
            calendarElement.style.backgroundColor = '#ffffff';
            calendarElement.style.color = '#111827';
        }
    };

    // เรียกครั้งแรก
    updateCalendarTheme();

    // ติดตาม dark mode changes
    const observer = new MutationObserver(updateCalendarTheme);
    observer.observe(document.documentElement, { attributes: true, attributeFilter: ['class'] });
});
</script>

<style>
/**
 * Custom FullCalendar สไตล์สำหรับ Modern UI v3.0
 */
.fc {
    font-family: inherit;
}

.fc .fc-button-primary {
    background-color: #6366f1 !important;
    border-color: #6366f1 !important;
    font-weight: 600;
    padding: 0.5rem 1rem;
    border-radius: 0.75rem;
    transition: all 0.2s;
}

.fc .fc-button-primary:hover {
    background-color: #4f46e5 !important;
    border-color: #4f46e5 !important;
    transform: scale(1.05);
}

.fc .fc-button-primary:disabled {
    background-color: #9ca3af !important;
    border-color: #9ca3af !important;
}

.fc .fc-button-active {
    background-color: #4f46e5 !important;
    border-color: #4f46e5 !important;
}

.fc-theme-standard td,
.fc-theme-standard th {
    border-color: #e5e7eb;
}

.dark .fc-theme-standard td,
.dark .fc-theme-standard th {
    border-color: #374151;
}

.fc .fc-col-header-cell {
    background: linear-gradient(to right, #6366f1, #8b5cf6);
    color: white;
    font-weight: 700;
    padding: 1rem 0.5rem;
}

.fc .fc-daygrid-day-number {
    color: #374151;
    font-weight: 600;
    padding: 0.5rem;
}

.dark .fc .fc-daygrid-day-number {
    color: #d1d5db;
}

.fc .fc-day-today {
    background: rgba(99, 102, 241, 0.1) !important;
}

.dark .fc .fc-day-today {
    background: rgba(99, 102, 241, 0.2) !important;
}

.fc .fc-event {
    cursor: pointer;
    border-radius: 0.5rem;
    padding: 0.25rem 0.5rem;
    font-weight: 600;
    font-size: 0.875rem;
    margin-bottom: 2px;
}

.fc .fc-event:hover {
    opacity: 0.9;
    transform: scale(1.02);
}

.fc .fc-toolbar-title {
    font-size: 1.5rem;
    font-weight: 700;
    color: #1f2937;
}

.dark .fc .fc-toolbar-title {
    color: #f3f4f6;
}
</style>
@endsection
