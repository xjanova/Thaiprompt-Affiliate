@extends('setup.layout')

@section('title', 'ตั้งค่าฐานข้อมูล')

@section('content')
<div>
    <h2 class="text-2xl font-bold text-gray-800 mb-6">ตั้งค่าฐานข้อมูล</h2>

    <p class="text-gray-600 mb-6">
        กรุณากรอกข้อมูลการเชื่อมต่อฐานข้อมูล MySQL
    </p>

    <form id="databaseForm">
        <div class="space-y-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Database Host</label>
                <input type="text" name="db_host" value="127.0.0.1" required
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                <p class="text-xs text-gray-500 mt-1">ปกติจะเป็น 127.0.0.1 หรือ localhost</p>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Database Port</label>
                <input type="number" name="db_port" value="3306" required
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                <p class="text-xs text-gray-500 mt-1">ปกติจะเป็น 3306</p>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Database Name</label>
                <input type="text" name="db_name" value="thaiprompt_marketplace" required
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                <p class="text-xs text-gray-500 mt-1">ชื่อฐานข้อมูลที่ต้องการใช้</p>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Database Username</label>
                <input type="text" name="db_username" value="root" required
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Database Password</label>
                <input type="password" name="db_password"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                <p class="text-xs text-gray-500 mt-1">ไม่บังคับ - ปล่อยว่างได้ถ้าไม่มีรหัสผ่าน</p>
            </div>
        </div>

        <!-- Alert Messages -->
        <div id="alertContainer" class="mt-6"></div>

        <div class="flex justify-between mt-8">
            <a href="{{ route('setup.requirements') }}" class="inline-flex items-center px-6 py-3 bg-gray-300 hover:bg-gray-400 text-gray-800 font-semibold rounded-lg transition duration-300">
                <i class="fas fa-arrow-left mr-2"></i>
                ย้อนกลับ
            </a>
            <div class="space-x-3">
                <button type="button" id="testBtn" class="inline-flex items-center px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-lg transition duration-300">
                    <i class="fas fa-flask mr-2"></i>
                    ทดสอบการเชื่อมต่อ
                </button>
                <button type="submit" id="saveBtn" disabled class="inline-flex items-center px-6 py-3 bg-indigo-600 hover:bg-indigo-700 text-white font-semibold rounded-lg transition duration-300 disabled:opacity-50 disabled:cursor-not-allowed">
                    บันทึกและถัดไป
                    <i class="fas fa-arrow-right ml-2"></i>
                </button>
            </div>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('databaseForm');
    const testBtn = document.getElementById('testBtn');
    const saveBtn = document.getElementById('saveBtn');
    const alertContainer = document.getElementById('alertContainer');
    let connectionTested = false;

    function showAlert(message, type = 'info') {
        const bgColor = type === 'success' ? 'bg-green-50 border-green-400 text-green-800' :
                       type === 'error' ? 'bg-red-50 border-red-400 text-red-800' :
                       'bg-blue-50 border-blue-400 text-blue-800';

        const icon = type === 'success' ? 'fa-check-circle' :
                    type === 'error' ? 'fa-exclamation-circle' :
                    'fa-info-circle';

        alertContainer.innerHTML = `
            <div class="border-l-4 ${bgColor} p-4 rounded">
                <div class="flex">
                    <i class="fas ${icon} mt-0.5"></i>
                    <div class="ml-3">
                        <p class="text-sm">${message}</p>
                    </div>
                </div>
            </div>
        `;
    }

    testBtn.addEventListener('click', async function() {
        const formData = {
            db_host: form.db_host.value,
            db_port: form.db_port.value,
            db_name: form.db_name.value,
            db_username: form.db_username.value,
            db_password: form.db_password.value,
        };

        testBtn.disabled = true;
        testBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>กำลังทดสอบ...';

        try {
            const response = await fetch('{{ route("setup.database.test") }}', {
                method: 'POST',
                body: JSON.stringify(formData)
            });

            const data = await response.json();

            if (data.success) {
                showAlert('✓ การเชื่อมต่อฐานข้อมูลสำเร็จ!', 'success');
                connectionTested = true;
                saveBtn.disabled = false;
            } else {
                showAlert('✗ การเชื่อมต่อล้มเหลว: ' + data.message, 'error');
                connectionTested = false;
                saveBtn.disabled = true;
            }
        } catch (error) {
            showAlert('✗ เกิดข้อผิดพลาด: ' + error.message, 'error');
            connectionTested = false;
            saveBtn.disabled = true;
        } finally {
            testBtn.disabled = false;
            testBtn.innerHTML = '<i class="fas fa-flask mr-2"></i>ทดสอบการเชื่อมต่อ';
        }
    });

    form.addEventListener('submit', async function(e) {
        e.preventDefault();

        if (!connectionTested) {
            showAlert('กรุณาทดสอบการเชื่อมต่อก่อน', 'error');
            return;
        }

        const formData = {
            db_host: form.db_host.value,
            db_port: form.db_port.value,
            db_name: form.db_name.value,
            db_username: form.db_username.value,
            db_password: form.db_password.value,
        };

        saveBtn.disabled = true;
        saveBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>กำลังบันทึก...';

        try {
            const response = await fetch('{{ route("setup.database.save") }}', {
                method: 'POST',
                body: JSON.stringify(formData)
            });

            const data = await response.json();

            if (data.success) {
                showAlert('✓ บันทึกการตั้งค่าสำเร็จ! กำลังเปลี่ยนหน้า...', 'success');
                setTimeout(() => {
                    window.location.href = '{{ route("setup.admin") }}';
                }, 1500);
            } else {
                showAlert('✗ บันทึกล้มเหลว: ' + data.message, 'error');
                saveBtn.disabled = false;
                saveBtn.innerHTML = 'บันทึกและถัดไป <i class="fas fa-arrow-right ml-2"></i>';
            }
        } catch (error) {
            showAlert('✗ เกิดข้อผิดพลาด: ' + error.message, 'error');
            saveBtn.disabled = false;
            saveBtn.innerHTML = 'บันทึกและถัดไป <i class="fas fa-arrow-right ml-2"></i>';
        }
    });

    // Reset tested flag when form changes
    form.querySelectorAll('input').forEach(input => {
        input.addEventListener('input', function() {
            if (connectionTested) {
                connectionTested = false;
                saveBtn.disabled = true;
                showAlert('การตั้งค่าเปลี่ยนแปลง กรุณาทดสอบการเชื่อมต่อใหม่อีกครั้ง', 'info');
            }
        });
    });
});
</script>
@endpush
