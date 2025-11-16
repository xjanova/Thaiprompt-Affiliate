@extends('layouts.user-arrow-x')

@section('title', 'การตั้งค่าอีเมล')

@section('content')
<div class="space-y-6 pb-20 lg:pb-6">
    <!-- Header -->
    <div class="bg-gradient-to-r from-green-600 via-teal-600 to-cyan-600 rounded-2xl shadow-2xl p-8 text-white">
        <div class="flex items-center gap-4">
            <div class="w-16 h-16 bg-white/20 rounded-xl flex items-center justify-center backdrop-blur-sm">
                <span class="text-3xl">📧</span>
            </div>
            <div>
                <h1 class="text-3xl font-bold">การตั้งค่าอีเมล</h1>
                <p class="text-green-100 mt-1">จัดการการรับอีเมลแจ้งเตือนและข่าวสาร</p>
            </div>
        </div>
    </div>

    <form action="{{ route('user.email.preferences.update') }}" method="POST" class="space-y-6">
        @csrf
        @method('PUT')

        <!-- Master Switch -->
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-6">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-4">
                    <div class="w-12 h-12 bg-gradient-to-br from-blue-500 to-indigo-600 rounded-xl flex items-center justify-center">
                        <span class="text-2xl">🔔</span>
                    </div>
                    <div>
                        <h2 class="text-xl font-bold text-gray-800 dark:text-white">การแจ้งเตือนทั้งหมด</h2>
                        <p class="text-sm text-gray-600 dark:text-gray-400">เปิด/ปิดการรับอีเมลทั้งหมด</p>
                    </div>
                </div>
                <label class="relative inline-flex items-center cursor-pointer">
                    <input type="checkbox"
                           name="all_emails"
                           value="1"
                           {{ old('all_emails', $preference->all_emails ?? true) ? 'checked' : '' }}
                           class="sr-only peer"
                           onchange="toggleAllEmails(this)">
                    <div class="w-14 h-8 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[4px] after:left-[4px] after:bg-white dark:bg-gray-800 after:border-gray-300 dark:border-gray-600 after:border after:rounded-full after:h-6 after:w-6 after:transition-all peer-checked:bg-blue-600"></div>
                </label>
            </div>
        </div>

        <!-- Email Categories -->
        <div id="emailCategories" class="space-y-4">
            <!-- Security Alerts -->
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-6">
                <div class="flex items-start gap-4">
                    <div class="w-12 h-12 bg-red-100 dark:bg-red-900/30 rounded-xl flex items-center justify-center flex-shrink-0">
                        <span class="text-2xl">🔐</span>
                    </div>
                    <div class="flex-1">
                        <div class="flex items-start justify-between mb-2">
                            <div>
                                <h3 class="text-lg font-bold text-gray-800 dark:text-white">แจ้งเตือนความปลอดภัย</h3>
                                <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">การเข้าสู่ระบบ, เปลี่ยนรหัสผ่าน, เปลี่ยนการตั้งค่าสำคัญ</p>
                            </div>
                            <label class="relative inline-flex items-center cursor-pointer">
                                <input type="checkbox"
                                       name="security_alerts"
                                       value="1"
                                       {{ old('security_alerts', $preference->security_alerts ?? true) ? 'checked' : '' }}
                                       class="sr-only peer email-toggle">
                                <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-red-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white dark:bg-gray-800 after:border-gray-300 dark:border-gray-600 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-red-600"></div>
                            </label>
                        </div>
                        <div class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg p-3 text-sm">
                            <span class="font-semibold text-red-800 dark:text-red-300">แนะนำให้เปิด:</span>
                            <span class="text-red-700 dark:text-red-400">เพื่อความปลอดภัยของบัญชีคุณ</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Commission Notifications -->
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-6">
                <div class="flex items-start gap-4">
                    <div class="w-12 h-12 bg-green-100 dark:bg-green-900/30 rounded-xl flex items-center justify-center flex-shrink-0">
                        <span class="text-2xl">💰</span>
                    </div>
                    <div class="flex-1">
                        <div class="flex items-start justify-between">
                            <div>
                                <h3 class="text-lg font-bold text-gray-800 dark:text-white">แจ้งเตือนค่าคอมมิชชั่น</h3>
                                <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">การรับค่าคอมมิชชั่น, โบนัส, รายได้</p>
                            </div>
                            <label class="relative inline-flex items-center cursor-pointer">
                                <input type="checkbox"
                                       name="commission_notifications"
                                       value="1"
                                       {{ old('commission_notifications', $preference->commission_notifications ?? true) ? 'checked' : '' }}
                                       class="sr-only peer email-toggle">
                                <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-green-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white dark:bg-gray-800 after:border-gray-300 dark:border-gray-600 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-green-600"></div>
                            </label>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Withdrawal Notifications -->
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-6">
                <div class="flex items-start gap-4">
                    <div class="w-12 h-12 bg-blue-100 dark:bg-blue-900/30 rounded-xl flex items-center justify-center flex-shrink-0">
                        <span class="text-2xl">🏦</span>
                    </div>
                    <div class="flex-1">
                        <div class="flex items-start justify-between">
                            <div>
                                <h3 class="text-lg font-bold text-gray-800 dark:text-white">แจ้งเตือนการถอนเงิน</h3>
                                <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">สถานะการถอนเงิน, ยืนยันการโอน</p>
                            </div>
                            <label class="relative inline-flex items-center cursor-pointer">
                                <input type="checkbox"
                                       name="withdrawal_notifications"
                                       value="1"
                                       {{ old('withdrawal_notifications', $preference->withdrawal_notifications ?? true) ? 'checked' : '' }}
                                       class="sr-only peer email-toggle">
                                <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white dark:bg-gray-800 after:border-gray-300 dark:border-gray-600 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
                            </label>
                        </div>
                    </div>
                </div>
            </div>

            <!-- System Announcements -->
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-6">
                <div class="flex items-start gap-4">
                    <div class="w-12 h-12 bg-purple-100 dark:bg-purple-900/30 rounded-xl flex items-center justify-center flex-shrink-0">
                        <span class="text-2xl">📢</span>
                    </div>
                    <div class="flex-1">
                        <div class="flex items-start justify-between">
                            <div>
                                <h3 class="text-lg font-bold text-gray-800 dark:text-white">ประกาศระบบ</h3>
                                <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">ข่าวสารสำคัญ, การปรับปรุงระบบ, กิจกรรมพิเศษ</p>
                            </div>
                            <label class="relative inline-flex items-center cursor-pointer">
                                <input type="checkbox"
                                       name="system_announcements"
                                       value="1"
                                       {{ old('system_announcements', $preference->system_announcements ?? true) ? 'checked' : '' }}
                                       class="sr-only peer email-toggle">
                                <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-purple-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white dark:bg-gray-800 after:border-gray-300 dark:border-gray-600 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-purple-600"></div>
                            </label>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Weekly Reports -->
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-6">
                <div class="flex items-start gap-4">
                    <div class="w-12 h-12 bg-indigo-100 dark:bg-indigo-900/30 rounded-xl flex items-center justify-center flex-shrink-0">
                        <span class="text-2xl">📊</span>
                    </div>
                    <div class="flex-1">
                        <div class="flex items-start justify-between">
                            <div>
                                <h3 class="text-lg font-bold text-gray-800 dark:text-white">รายงานประจำสัปดาห์</h3>
                                <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">สรุปรายได้, สถิติการขาย, ยอดคอมมิชชั่น</p>
                            </div>
                            <label class="relative inline-flex items-center cursor-pointer">
                                <input type="checkbox"
                                       name="weekly_reports"
                                       value="1"
                                       {{ old('weekly_reports', $preference->weekly_reports ?? true) ? 'checked' : '' }}
                                       class="sr-only peer email-toggle">
                                <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-indigo-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white dark:bg-gray-800 after:border-gray-300 dark:border-gray-600 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-indigo-600"></div>
                            </label>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Marketing Emails -->
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-6">
                <div class="flex items-start gap-4">
                    <div class="w-12 h-12 bg-yellow-100 dark:bg-yellow-900/30 rounded-xl flex items-center justify-center flex-shrink-0">
                        <span class="text-2xl">🎯</span>
                    </div>
                    <div class="flex-1">
                        <div class="flex items-start justify-between">
                            <div>
                                <h3 class="text-lg font-bold text-gray-800 dark:text-white">อีเมลการตลาด</h3>
                                <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">โปรโมชั่น, ข้อเสนอพิเศษ, เคล็ดลับการขาย</p>
                            </div>
                            <label class="relative inline-flex items-center cursor-pointer">
                                <input type="checkbox"
                                       name="marketing_emails"
                                       value="1"
                                       {{ old('marketing_emails', $preference->marketing_emails ?? false) ? 'checked' : '' }}
                                       class="sr-only peer email-toggle">
                                <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-yellow-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white dark:bg-gray-800 after:border-gray-300 dark:border-gray-600 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-yellow-500"></div>
                            </label>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Language Preference -->
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-6">
            <div class="flex items-center gap-4 mb-4">
                <div class="w-12 h-12 bg-gradient-to-br from-pink-500 to-rose-600 rounded-xl flex items-center justify-center">
                    <span class="text-2xl">🌐</span>
                </div>
                <div>
                    <h2 class="text-xl font-bold text-gray-800 dark:text-white">ภาษาที่ต้องการ</h2>
                    <p class="text-sm text-gray-600 dark:text-gray-400">เลือกภาษาสำหรับอีเมลที่ได้รับ</p>
                </div>
            </div>
            <div class="grid md:grid-cols-2 gap-4">
                <label class="relative cursor-pointer">
                    <input type="radio"
                           name="preferred_language"
                           value="th"
                           {{ old('preferred_language', $preference->preferred_language ?? 'th') === 'th' ? 'checked' : '' }}
                           class="peer sr-only">
                    <div class="border-2 border-gray-200 dark:border-gray-700 rounded-xl p-4 hover:border-blue-400 dark:hover:border-blue-500 peer-checked:border-blue-600 dark:peer-checked:border-blue-500 peer-checked:bg-blue-50 dark:peer-checked:bg-blue-900/30 transition-all">
                        <div class="flex items-center gap-3">
                            <span class="text-3xl">🇹🇭</span>
                            <div>
                                <div class="font-bold text-gray-800 dark:text-white">ภาษาไทย</div>
                                <div class="text-sm text-gray-600 dark:text-gray-400">Thai Language</div>
                            </div>
                        </div>
                    </div>
                    <div class="absolute top-2 right-2 w-6 h-6 bg-blue-600 rounded-full hidden peer-checked:flex items-center justify-center">
                        <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path>
                        </svg>
                    </div>
                </label>

                <label class="relative cursor-pointer">
                    <input type="radio"
                           name="preferred_language"
                           value="en"
                           {{ old('preferred_language', $preference->preferred_language ?? 'th') === 'en' ? 'checked' : '' }}
                           class="peer sr-only">
                    <div class="border-2 border-gray-200 dark:border-gray-700 rounded-xl p-4 hover:border-blue-400 dark:hover:border-blue-500 peer-checked:border-blue-600 dark:peer-checked:border-blue-500 peer-checked:bg-blue-50 dark:peer-checked:bg-blue-900/30 transition-all">
                        <div class="flex items-center gap-3">
                            <span class="text-3xl">🇬🇧</span>
                            <div>
                                <div class="font-bold text-gray-800 dark:text-white">English</div>
                                <div class="text-sm text-gray-600 dark:text-gray-400">English Language</div>
                            </div>
                        </div>
                    </div>
                    <div class="absolute top-2 right-2 w-6 h-6 bg-blue-600 rounded-full hidden peer-checked:flex items-center justify-center">
                        <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path>
                        </svg>
                    </div>
                </label>
            </div>
        </div>

        <!-- Action Buttons -->
        <div class="flex flex-col sm:flex-row gap-4">
            <button type="submit"
                    class="flex-1 px-6 py-4 bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 text-white rounded-xl font-bold text-lg transition-all shadow-lg hover:shadow-xl">
                💾 บันทึกการตั้งค่า
            </button>

            <button type="button"
                    onclick="disableAll()"
                    class="px-6 py-4 bg-red-600 hover:bg-red-700 text-white rounded-xl font-bold transition-colors shadow-lg">
                ❌ ปิดทั้งหมด
            </button>

            <button type="button"
                    onclick="enableAll()"
                    class="px-6 py-4 bg-green-600 hover:bg-green-700 text-white rounded-xl font-bold transition-colors shadow-lg">
                ✅ เปิดทั้งหมด
            </button>
        </div>
    </form>

    <!-- Info Box -->
    <div class="bg-gradient-to-br from-blue-50 to-indigo-50 dark:from-blue-900/20 dark:to-indigo-900/20 rounded-2xl shadow-xl p-6 border border-blue-100 dark:border-blue-800">
        <div class="flex gap-3">
            <span class="text-2xl">💡</span>
            <div class="flex-1">
                <h3 class="font-bold text-gray-800 dark:text-white mb-2">เกี่ยวกับการตั้งค่าอีเมล</h3>
                <ul class="text-sm text-gray-600 dark:text-gray-400 space-y-1">
                    <li>• คุณสามารถเปลี่ยนการตั้งค่าได้ทุกเมื่อ</li>
                    <li>• การแจ้งเตือนความปลอดภัยแนะนำให้เปิดไว้เสมอ</li>
                    <li>• อีเมลสำคัญจะถูกส่งไปยังอีเมลที่ลงทะเบียนไว้: <strong class="text-gray-800 dark:text-white">{{ auth()->user()->email }}</strong></li>
                    <li>• การตั้งค่าจะมีผลทันทีหลังบันทึก</li>
                </ul>
            </div>
        </div>
    </div>
</div>

@if(session('success'))
    <div class="fixed bottom-4 right-4 bg-green-500 text-white px-6 py-3 rounded-lg shadow-lg z-50 animate-bounce">
        ✅ {{ session('success') }}
    </div>
@endif

@if(session('error'))
    <div class="fixed bottom-4 right-4 bg-red-500 text-white px-6 py-3 rounded-lg shadow-lg z-50 animate-bounce">
        ❌ {{ session('error') }}
    </div>
@endif

@push('scripts')
<script>
function toggleAllEmails(checkbox) {
    const emailToggles = document.querySelectorAll('.email-toggle');
    const emailCategories = document.getElementById('emailCategories');

    if (checkbox.checked) {
        emailCategories.classList.remove('opacity-50', 'pointer-events-none');
    } else {
        emailCategories.classList.add('opacity-50', 'pointer-events-none');
        emailToggles.forEach(toggle => {
            toggle.checked = false;
        });
    }
}

async function disableAll() {
    if (!confirm('คุณแน่ใจหรือไม่ที่จะปิดการรับอีเมลทั้งหมด?')) {
        return;
    }

    try {
        const response = await fetch('{{ route("user.email.preferences.disable-all") }}', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json',
            }
        });

        if (response.ok) {
            location.reload();
        } else {
            alert('เกิดข้อผิดพลาด');
        }
    } catch (error) {
        alert('เกิดข้อผิดพลาด');
    }
}

async function enableAll() {
    try {
        const response = await fetch('{{ route("user.email.preferences.enable-all") }}', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json',
            }
        });

        if (response.ok) {
            location.reload();
        } else {
            alert('เกิดข้อผิดพลาด');
        }
    } catch (error) {
        alert('เกิดข้อผิดพลาด');
    }
}

// Auto-hide alerts after 5 seconds
setTimeout(() => {
    document.querySelectorAll('.animate-bounce').forEach(el => {
        el.style.transition = 'opacity 0.5s';
        el.style.opacity = '0';
        setTimeout(() => el.remove(), 500);
    });
}, 5000);

// Initialize state on load
document.addEventListener('DOMContentLoaded', function() {
    const allEmailsCheckbox = document.querySelector('input[name="all_emails"]');
    if (allEmailsCheckbox && !allEmailsCheckbox.checked) {
        toggleAllEmails(allEmailsCheckbox);
    }
});
</script>
@endpush
@endsection
