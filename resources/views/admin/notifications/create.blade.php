@extends('layouts.admin')

@section('title', 'ส่งการแจ้งเตือนใหม่')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex items-center gap-4">
        <a href="{{ route('admin.notifications.index') }}" class="text-gray-600 hover:text-gray-900">
            ← กลับ
        </a>
        <div>
            <h1 class="text-2xl font-bold text-gray-900">ส่งการแจ้งเตือนใหม่</h1>
            <p class="text-sm text-gray-600 mt-1">ส่งการแจ้งเตือนให้กับสมาชิกรายบุคคลหรือทั้งหมด</p>
        </div>
    </div>

    <!-- Templates Section -->
    <div class="bg-white rounded-lg shadow-md p-6" x-data="{ selectedTemplate: null }">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">📋 เทมเพลตสำเร็จรูป</h3>
        <p class="text-sm text-gray-600 mb-4">เลือกเทมเพลตเพื่อกรอกข้อมูลอัตโนมัติ</p>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <!-- Template 1: System Update -->
            <button type="button" @click="useTemplate('system_update')"
                    class="p-4 border-2 border-gray-300 hover:border-indigo-500 rounded-lg text-left transition">
                <div class="text-3xl mb-2">⚙️</div>
                <h4 class="font-semibold text-gray-900">อัปเดตระบบ</h4>
                <p class="text-sm text-gray-600 mt-1">แจ้งเตือนการอัปเดตระบบ</p>
            </button>

            <!-- Template 2: Promotion -->
            <button type="button" @click="useTemplate('promotion')"
                    class="p-4 border-2 border-gray-300 hover:border-indigo-500 rounded-lg text-left transition">
                <div class="text-3xl mb-2">🎉</div>
                <h4 class="font-semibold text-gray-900">โปรโมชั่น</h4>
                <p class="text-sm text-gray-600 mt-1">ประกาศโปรโมชั่นพิเศษ</p>
            </button>

            <!-- Template 3: Maintenance -->
            <button type="button" @click="useTemplate('maintenance')"
                    class="p-4 border-2 border-gray-300 hover:border-indigo-500 rounded-lg text-left transition">
                <div class="text-3xl mb-2">🔧</div>
                <h4 class="font-semibold text-gray-900">ปรับปรุงระบบ</h4>
                <p class="text-sm text-gray-600 mt-1">แจ้งปิดปรับปรุงระบบ</p>
            </button>

            <!-- Template 4: Welcome -->
            <button type="button" @click="useTemplate('welcome')"
                    class="p-4 border-2 border-gray-300 hover:border-indigo-500 rounded-lg text-left transition">
                <div class="text-3xl mb-2">👋</div>
                <h4 class="font-semibold text-gray-900">ต้อนรับสมาชิกใหม่</h4>
                <p class="text-sm text-gray-600 mt-1">ข้อความต้อนรับ</p>
            </button>

            <!-- Template 5: Payment Success -->
            <button type="button" @click="useTemplate('payment_success')"
                    class="p-4 border-2 border-gray-300 hover:border-indigo-500 rounded-lg text-left transition">
                <div class="text-3xl mb-2">💰</div>
                <h4 class="font-semibold text-gray-900">การเงินสำเร็จ</h4>
                <p class="text-sm text-gray-600 mt-1">แจ้งเตือนธุรกรรมสำเร็จ</p>
            </button>

            <!-- Template 6: Important Alert -->
            <button type="button" @click="useTemplate('important_alert')"
                    class="p-4 border-2 border-gray-300 hover:border-indigo-500 rounded-lg text-left transition">
                <div class="text-3xl mb-2">⚠️</div>
                <h4 class="font-semibold text-gray-900">แจ้งเตือนสำคัญ</h4>
                <p class="text-sm text-gray-600 mt-1">ข้อความสำคัญเร่งด่วน</p>
            </button>
        </div>
    </div>

    <!-- Form -->
    <form action="{{ route('admin.notifications.store') }}" method="POST" class="bg-white rounded-lg shadow-md p-6" id="notificationForm">
        @csrf

        <div class="space-y-6">
            <!-- Recipient Type -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    ผู้รับ <span class="text-red-500">*</span>
                </label>
                <div class="space-y-2">
                    <label class="flex items-center">
                        <input type="radio" name="recipient_type" value="all" checked
                               class="w-4 h-4 text-indigo-600 border-gray-300 focus:ring-indigo-500"
                               onchange="toggleUserSelect()">
                        <span class="ml-2 text-sm text-gray-700">สมาชิกทั้งหมด (ประกาศ)</span>
                    </label>
                    <label class="flex items-center">
                        <input type="radio" name="recipient_type" value="single"
                               class="w-4 h-4 text-indigo-600 border-gray-300 focus:ring-indigo-500"
                               onchange="toggleUserSelect()">
                        <span class="ml-2 text-sm text-gray-700">สมาชิกรายบุคคล</span>
                    </label>
                    <label class="flex items-center">
                        <input type="radio" name="recipient_type" value="multiple"
                               class="w-4 h-4 text-indigo-600 border-gray-300 focus:ring-indigo-500"
                               onchange="toggleUserSelect()">
                        <span class="ml-2 text-sm text-gray-700">หลายคน</span>
                    </label>
                </div>
            </div>

            <!-- User Selection -->
            <div id="userSelectDiv" style="display: none;">
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    เลือกสมาชิก <span class="text-red-500">*</span>
                </label>
                <select name="user_ids[]" id="userSelect" multiple
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 @error('user_ids') border-red-500 @enderror"
                        size="10">
                    @foreach($users as $user)
                        <option value="{{ $user->id }}">{{ $user->name }} ({{ $user->email }})</option>
                    @endforeach
                </select>
                <p class="mt-1 text-xs text-gray-500">กด Ctrl/Cmd + คลิก เพื่อเลือกหลายคน</p>
                @error('user_ids')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- Type -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    ประเภท <span class="text-red-500">*</span>
                </label>
                <select name="type" required
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 @error('type') border-red-500 @enderror"
                        onchange="updateIcon()">
                    <option value="system">ระบบ</option>
                    <option value="announcement" selected>ประกาศ</option>
                    <option value="alert">แจ้งเตือน</option>
                    <option value="wallet">กระเป๋าเงิน</option>
                    <option value="commission">คอมมิชชั่น</option>
                </select>
                @error('type')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- Title -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    หัวข้อ <span class="text-red-500">*</span>
                </label>
                <input type="text" name="title" value="{{ old('title') }}" required
                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 @error('title') border-red-500 @enderror"
                       placeholder="เช่น ประกาศสำคัญ">
                @error('title')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- Message -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    ข้อความ <span class="text-red-500">*</span>
                </label>
                <textarea name="message" rows="5" required
                          class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 @error('message') border-red-500 @enderror"
                          placeholder="รายละเอียดการแจ้งเตือน">{{ old('message') }}</textarea>
                @error('message')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Action URL -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        URL ปุ่มดำเนินการ
                    </label>
                    <input type="text" name="action_url" value="{{ old('action_url') }}"
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 @error('action_url') border-red-500 @enderror"
                           placeholder="https://example.com">
                    <p class="mt-1 text-xs text-gray-500">URL ที่จะเปิดเมื่อคลิกปุ่ม (ถ้ามี)</p>
                    @error('action_url')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Action Text -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        ข้อความปุ่ม
                    </label>
                    <input type="text" name="action_text" value="{{ old('action_text') }}"
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 @error('action_text') border-red-500 @enderror"
                           placeholder="เช่น ดูเพิ่มเติม">
                    @error('action_text')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <!-- Priority -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    ระดับความสำคัญ <span class="text-red-500">*</span>
                </label>
                <select name="priority" required
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 @error('priority') border-red-500 @enderror">
                    <option value="low">ต่ำ</option>
                    <option value="normal" selected>ปกติ</option>
                    <option value="high">สูง</option>
                    <option value="urgent">เร่งด่วน</option>
                </select>
                @error('priority')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Icon -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        ไอคอน
                    </label>
                    <input type="text" name="icon" id="iconInput" value="{{ old('icon') }}"
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500"
                           placeholder="🔔">
                    <p class="mt-1 text-xs text-gray-500">ใส่ emoji เช่น 🔔 📢 ⚠️ 💰</p>
                </div>

                <!-- Color -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        สี
                    </label>
                    <select name="color"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500">
                        <option value="gray">เทา</option>
                        <option value="blue" selected>น้ำเงิน</option>
                        <option value="green">เขียว</option>
                        <option value="yellow">เหลือง</option>
                        <option value="orange">ส้ม</option>
                        <option value="red">แดง</option>
                        <option value="purple">ม่วง</option>
                    </select>
                </div>
            </div>

            <!-- Options -->
            <div class="space-y-3">
                <div class="flex items-center">
                    <input type="checkbox" name="is_important" value="1" id="is_important"
                           {{ old('is_important') ? 'checked' : '' }}
                           class="w-4 h-4 text-indigo-600 border-gray-300 rounded focus:ring-indigo-500">
                    <label for="is_important" class="ml-2 text-sm text-gray-700">
                        ทำเครื่องหมายเป็นสำคัญ
                    </label>
                </div>

                <div class="flex items-center">
                    <input type="checkbox" name="show_immediately" value="1" id="show_immediately"
                           {{ old('show_immediately') ? 'checked' : '' }}
                           class="w-4 h-4 text-indigo-600 border-gray-300 rounded focus:ring-indigo-500">
                    <label for="show_immediately" class="ml-2 text-sm text-gray-700">
                        <strong>แสดงป๊อบอัพทันที</strong> (ถ้าสมาชิกอยู่ในระบบจะเห็นทันที)
                    </label>
                </div>
            </div>

            <!-- Actions -->
            <div class="flex justify-end gap-4 pt-6 border-t border-gray-200">
                <a href="{{ route('admin.notifications.index') }}"
                   class="px-6 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50">
                    ยกเลิก
                </a>
                <button type="submit"
                        class="px-6 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700">
                    ส่งการแจ้งเตือน
                </button>
            </div>
        </div>
    </form>
</div>

<script>
function toggleUserSelect() {
    const recipientType = document.querySelector('input[name="recipient_type"]:checked').value;
    const userSelectDiv = document.getElementById('userSelectDiv');
    const userSelect = document.getElementById('userSelect');

    if (recipientType === 'all') {
        userSelectDiv.style.display = 'none';
        userSelect.removeAttribute('required');
    } else {
        userSelectDiv.style.display = 'block';
        userSelect.setAttribute('required', 'required');
    }

    // Set multiple attribute based on selection
    if (recipientType === 'single') {
        userSelect.removeAttribute('multiple');
        userSelect.size = 10;
    } else if (recipientType === 'multiple') {
        userSelect.setAttribute('multiple', 'multiple');
        userSelect.size = 10;
    }
}

function updateIcon() {
    const type = document.querySelector('select[name="type"]').value;
    const iconInput = document.getElementById('iconInput');

    const icons = {
        'system': '⚙️',
        'announcement': '📢',
        'alert': '⚠️',
        'wallet': '👛',
        'commission': '💰'
    };

    if (icons[type] && !iconInput.value) {
        iconInput.value = icons[type];
    }
}

function useTemplate(templateName) {
    const templates = {
        'system_update': {
            type: 'system',
            title: '🎉 อัปเดตระบบใหม่',
            message: 'เราได้อัปเดตระบบเพื่อให้คุณได้รับประสบการณ์ที่ดีขึ้น ตรวจสอบฟีเจอร์ใหม่ได้เลยตอนนี้!',
            priority: 'normal',
            icon: '⚙️',
            color: 'blue',
            is_important: false,
            show_immediately: true
        },
        'promotion': {
            type: 'announcement',
            title: '🎁 โปรโมชั่นพิเศษ!',
            message: 'รับส่วนลดพิเศษสูงสุด 50%! โปรโมชั่นจำกัดเวลา อย่าพลาดโอกาสดีๆ แบบนี้',
            action_text: 'ดูโปรโมชั่น',
            priority: 'high',
            icon: '🎉',
            color: 'green',
            is_important: true,
            show_immediately: true
        },
        'maintenance': {
            type: 'alert',
            title: '🔧 ปิดปรับปรุงระบบชั่วคราว',
            message: 'ระบบจะปิดปรับปรุงในวันที่ XX/XX/XXXX เวลา XX:XX - XX:XX น. เพื่อพัฒนาประสิทธิภาพให้ดียิ่งขึ้น ขออภัยในความไม่สะดวก',
            priority: 'urgent',
            icon: '🔧',
            color: 'orange',
            is_important: true,
            show_immediately: true
        },
        'welcome': {
            type: 'system',
            title: '👋 ยินดีต้อนรับสู่ระบบ!',
            message: 'ขอบคุณที่เข้าร่วมกับเรา! เรายินดีที่จะช่วยเหลือคุณในทุกขั้นตอน หากมีคำถามสามารถติดต่อเราได้ทันที',
            action_text: 'เริ่มต้นใช้งาน',
            priority: 'normal',
            icon: '👋',
            color: 'purple',
            is_important: false,
            show_immediately: true
        },
        'payment_success': {
            type: 'wallet',
            title: '💰 ธุรกรรมสำเร็จ',
            message: 'ธุรกรรมของคุณได้รับการดำเนินการเรียบร้อยแล้ว สามารถตรวจสอบรายละเอียดได้ในกระเป๋าเงินของคุณ',
            action_url: '/user/wallet/transactions',
            action_text: 'ดูธุรกรรม',
            priority: 'high',
            icon: '💰',
            color: 'green',
            is_important: true,
            show_immediately: false
        },
        'important_alert': {
            type: 'alert',
            title: '⚠️ แจ้งเตือนสำคัญ',
            message: 'กรุณาอ่านข้อความนี้โดยด่วน! มีเรื่องสำคัญที่ต้องการแจ้งให้คุณทราบ',
            priority: 'urgent',
            icon: '⚠️',
            color: 'red',
            is_important: true,
            show_immediately: true
        }
    };

    const template = templates[templateName];
    if (!template) return;

    // Fill form fields
    document.querySelector('select[name="type"]').value = template.type;
    document.querySelector('input[name="title"]').value = template.title;
    document.querySelector('textarea[name="message"]').value = template.message;
    document.querySelector('select[name="priority"]').value = template.priority;
    document.getElementById('iconInput').value = template.icon;
    document.querySelector('select[name="color"]').value = template.color;
    document.getElementById('is_important').checked = template.is_important;
    document.getElementById('show_immediately').checked = template.show_immediately;

    if (template.action_url) {
        document.querySelector('input[name="action_url"]').value = template.action_url;
    }
    if (template.action_text) {
        document.querySelector('input[name="action_text"]').value = template.action_text;
    }

    // Scroll to form
    document.getElementById('notificationForm').scrollIntoView({ behavior: 'smooth', block: 'start' });
}

// Initialize
document.addEventListener('DOMContentLoaded', function() {
    toggleUserSelect();
    updateIcon();
});
</script>
@endsection
