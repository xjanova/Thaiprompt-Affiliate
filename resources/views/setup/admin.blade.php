@extends('setup.layout')

@section('title', 'สร้างบัญชีผู้ดูแลระบบ')

@section('content')
<div>
    <h2 class="text-2xl font-bold text-gray-800 mb-6">สร้างบัญชีผู้ดูแลระบบ</h2>

    <p class="text-gray-600 mb-6">
        สร้างบัญชีผู้ดูแลระบบคนแรกเพื่อเข้าสู่ระบบ
    </p>

    <form id="adminForm">
        <div class="space-y-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">ชื่อ-นามสกุล</label>
                <input type="text" name="name" required
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                    placeholder="Admin Name">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">อีเมล</label>
                <input type="email" name="email" required
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                    placeholder="admin@example.com">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">รหัสผ่าน</label>
                <input type="password" name="password" required minlength="8"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                    placeholder="ต้องมีอย่างน้อย 8 ตัวอักษร">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">ยืนยันรหัสผ่าน</label>
                <input type="password" name="password_confirmation" required minlength="8"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                    placeholder="กรอกรหัสผ่านอีกครั้ง">
            </div>
        </div>

        <!-- Alert Messages -->
        <div id="alertContainer" class="mt-6"></div>

        <!-- Progress -->
        <div id="progressContainer" class="mt-6 hidden">
            <div class="bg-blue-50 border-l-4 border-blue-400 p-4 rounded">
                <div class="flex items-center">
                    <i class="fas fa-spinner fa-spin text-blue-600 mr-3"></i>
                    <div class="flex-1">
                        <p class="text-sm font-medium text-blue-800" id="progressText">กำลังติดตั้ง...</p>
                        <div class="mt-2 bg-blue-200 rounded-full h-2 overflow-hidden">
                            <div id="progressBar" class="bg-blue-600 h-full transition-all duration-500" style="width: 0%"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="flex justify-between mt-8">
            <a href="{{ route('setup.database') }}" id="backBtn" class="inline-flex items-center px-6 py-3 bg-gray-300 hover:bg-gray-400 text-gray-800 font-semibold rounded-lg transition duration-300">
                <i class="fas fa-arrow-left mr-2"></i>
                ย้อนกลับ
            </a>
            <button type="submit" id="submitBtn" class="inline-flex items-center px-6 py-3 bg-indigo-600 hover:bg-indigo-700 text-white font-semibold rounded-lg transition duration-300">
                เริ่มการติดตั้ง
                <i class="fas fa-rocket ml-2"></i>
            </button>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('adminForm');
    const submitBtn = document.getElementById('submitBtn');
    const backBtn = document.getElementById('backBtn');
    const alertContainer = document.getElementById('alertContainer');
    const progressContainer = document.getElementById('progressContainer');
    const progressBar = document.getElementById('progressBar');
    const progressText = document.getElementById('progressText');

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

    function updateProgress(percent, text) {
        progressBar.style.width = percent + '%';
        progressText.textContent = text;
    }

    form.addEventListener('submit', async function(e) {
        e.preventDefault();

        // Validate password match
        if (form.password.value !== form.password_confirmation.value) {
            showAlert('รหัสผ่านไม่ตรงกัน กรุณาตรวจสอบอีกครั้ง', 'error');
            return;
        }

        const formData = {
            name: form.name.value,
            email: form.email.value,
            password: form.password.value,
            password_confirmation: form.password_confirmation.value,
        };

        submitBtn.disabled = true;
        backBtn.style.display = 'none';
        progressContainer.classList.remove('hidden');
        alertContainer.innerHTML = '';

        try {
            // Step 1: Creating admin
            updateProgress(33, 'กำลังสร้างฐานข้อมูล...');

            const response = await fetch('{{ route("setup.admin.create") }}', {
                method: 'POST',
                body: JSON.stringify(formData)
            });

            const data = await response.json();

            if (data.success) {
                updateProgress(100, 'ติดตั้งสำเร็จ!');

                setTimeout(() => {
                    window.location.href = '{{ route("setup.complete") }}';
                }, 1000);
            } else {
                throw new Error(data.message);
            }
        } catch (error) {
            progressContainer.classList.add('hidden');
            showAlert('✗ การติดตั้งล้มเหลว: ' + error.message, 'error');
            submitBtn.disabled = false;
            backBtn.style.display = '';
        }
    });
});
</script>
@endpush
