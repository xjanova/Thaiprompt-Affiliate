@extends('setup.layout')

@section('content')
<div class="max-w-4xl mx-auto" x-data="setupWizard()" x-init="init()">
    <!-- Progress Steps -->
    <div class="bg-white rounded-lg shadow-lg p-6 mb-6">
        <div class="mb-4">
            <div class="bg-gray-200 rounded-full h-2 overflow-hidden">
                <div class="progress-bar" :style="`width: ${progress}%`"></div>
            </div>
        </div>

        <div class="grid grid-cols-6 gap-2 relative">
            <template x-for="(step, index) in steps" :key="index">
                <div class="step-item">
                    <div
                        class="step-circle text-sm"
                        :class="{
                            'active': currentStep === index,
                            'completed': index < currentStep,
                            'pending': index > currentStep
                        }"
                    >
                        <span x-show="index < currentStep">✓</span>
                        <span x-show="index >= currentStep" x-text="index + 1"></span>
                    </div>
                    <div class="mt-2 text-xs text-center font-medium text-gray-700" x-text="step.name"></div>
                    <div class="step-line" x-show="index < steps.length - 1" :class="{'completed': index < currentStep}"></div>
                </div>
            </template>
        </div>
    </div>

    <!-- Main Content Area -->
    <div class="bg-white rounded-lg shadow-lg p-8">
        <!-- Step 0: License Verification -->
        <div x-show="currentStep === 0" x-transition>
            <div class="text-center mb-8">
                <h2 class="text-3xl font-bold text-gray-800 mb-4">ยืนยัน License Key</h2>
                <p class="text-lg text-gray-600 mb-6">กรุณากรอก License Key เพื่อยืนยันสิทธิ์การติดตั้ง</p>
            </div>

            <form @submit.prevent="checkLicense" id="license-form" class="mb-6">
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">License Key <span class="text-red-500">*</span></label>
                        <input
                            type="text"
                            x-model="licenseKey"
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent font-mono text-sm"
                            placeholder="XXXX-XXXX-XXXX-XXXX"
                            required
                        >
                        <p class="mt-2 text-sm text-gray-500">กรุณากรอก License Key ที่ได้รับจากการซื้อ</p>
                    </div>

                    <div id="license-check-result"></div>

                    <button
                        type="submit"
                        class="w-full bg-indigo-600 text-white py-4 rounded-lg font-bold text-lg hover:bg-indigo-700 transition duration-300 shadow-lg"
                        :disabled="checkingLicense"
                    >
                        <span x-show="!checkingLicense">ตรวจสอบ License และเริ่มติดตั้ง</span>
                        <span x-show="checkingLicense">กำลังตรวจสอบ...</span>
                    </button>
                </div>
            </form>

            <div class="bg-blue-50 border-l-4 border-blue-400 p-4 mb-6">
                <div class="flex">
                    <div class="text-2xl mr-3">💡</div>
                    <div>
                        <h4 class="font-bold text-blue-800 mb-1">สำหรับนักพัฒนา (Developer Mode)</h4>
                        <p class="text-sm text-blue-700">หาก IP ของคุณอยู่ใน Dev Whitelist จะข้ามการตรวจสอบ License อัตโนมัติ</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Step 1: Welcome -->
        <div x-show="currentStep === 1" x-transition>
            <div class="text-center mb-8">
                <h2 class="text-3xl font-bold text-gray-800 mb-4">ยินดีต้อนรับสู่ TP-Affiliate</h2>
                <p class="text-lg text-gray-600 mb-6">ระบบจะพาคุณผ่านขั้นตอนการติดตั้งอัตโนมัติ ใช้เวลาประมาณ 5-10 นาที</p>
            </div>

            <div class="grid md:grid-cols-2 gap-6 mb-8">
                <div class="bg-indigo-50 rounded-lg p-6">
                    <div class="text-3xl mb-3">⚡</div>
                    <h3 class="font-bold text-gray-800 mb-2">ติดตั้งอัตโนมัติ</h3>
                    <p class="text-sm text-gray-600">ระบบจะตรวจสอบและติดตั้งทุกอย่างให้อัตโนมัติ</p>
                </div>
                <div class="bg-purple-50 rounded-lg p-6">
                    <div class="text-3xl mb-3">🔒</div>
                    <h3 class="font-bold text-gray-800 mb-2">ปลอดภัย</h3>
                    <p class="text-sm text-gray-600">ตรวจสอบ security และ permissions อย่างละเอียด</p>
                </div>
                <div class="bg-green-50 rounded-lg p-6">
                    <div class="text-3xl mb-3">📊</div>
                    <h3 class="font-bold text-gray-800 mb-2">ครบถ้วน</h3>
                    <p class="text-sm text-gray-600">Database, migrations, และ dependencies ทั้งหมด</p>
                </div>
                <div class="bg-pink-50 rounded-lg p-6">
                    <div class="text-3xl mb-3">🎯</div>
                    <h3 class="font-bold text-gray-800 mb-2">พร้อมใช้งาน</h3>
                    <p class="text-sm text-gray-600">ติดตั้งเสร็จพร้อมใช้งานทันที</p>
                </div>
            </div>

            <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4 mb-6">
                <div class="flex">
                    <div class="text-2xl mr-3">⚠️</div>
                    <div>
                        <h4 class="font-bold text-yellow-800 mb-1">ข้อมูลที่ต้องเตรียม:</h4>
                        <ul class="text-sm text-yellow-700 space-y-1">
                            <li>• ข้อมูล MySQL Database (host, port, database name, username, password)</li>
                            <li>• เวลาประมาณ 5-10 นาที สำหรับการติดตั้ง</li>
                            <li>• ตรวจสอบให้แน่ใจว่า PHP 8.1+ และ extensions จำเป็นติดตั้งแล้ว</li>
                        </ul>
                    </div>
                </div>
            </div>

            <button @click="nextStep" class="w-full bg-indigo-600 text-white py-4 rounded-lg font-bold text-lg hover:bg-indigo-700 transition duration-300 shadow-lg">
                เริ่มการติดตั้ง →
            </button>
        </div>

        <!-- Step 2: Requirements Check -->
        <div x-show="currentStep === 2" x-transition>
            <h2 class="text-2xl font-bold text-gray-800 mb-6">ตรวจสอบความพร้อมของระบบ</h2>

            <div id="requirements-container">
                <div class="text-center py-8">
                    <div class="inline-block animate-spin rounded-full h-12 w-12 border-b-2 border-indigo-600"></div>
                    <p class="mt-4 text-gray-600">กำลังตรวจสอบระบบ...</p>
                </div>
            </div>

            <div class="flex gap-4 mt-6" x-show="requirementsChecked">
                <button @click="prevStep" class="px-6 py-3 border border-gray-300 rounded-lg hover:bg-gray-50 transition">
                    ← ย้อนกลับ
                </button>
                <button @click="nextStep" class="flex-1 bg-indigo-600 text-white py-3 rounded-lg font-semibold hover:bg-indigo-700 transition" :disabled="!requirementsPassed">
                    ดำเนินการต่อ →
                </button>
            </div>
        </div>

        <!-- Step 3: Database Configuration -->
        <div x-show="currentStep === 3" x-transition>
            <h2 class="text-2xl font-bold text-gray-800 mb-6">ตั้งค่า Database</h2>

            <form @submit.prevent="testDatabaseConnection" id="database-form">
                <div class="space-y-4">
                    <div class="grid md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Database Host</label>
                            <input type="text" x-model="dbConfig.host" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent" placeholder="127.0.0.1">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Database Port</label>
                            <input type="text" x-model="dbConfig.port" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent" placeholder="3306">
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Database Name</label>
                        <input type="text" x-model="dbConfig.database" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent" placeholder="thaiprompt_affiliate" required>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Database Username</label>
                        <input type="text" x-model="dbConfig.username" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent" required>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Database Password</label>
                        <input type="password" x-model="dbConfig.password" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                    </div>

                    <div id="db-test-result"></div>

                    <button type="submit" class="w-full bg-blue-600 text-white py-3 rounded-lg font-semibold hover:bg-blue-700 transition">
                        ทดสอบการเชื่อมต่อ
                    </button>
                </div>
            </form>

            <div class="flex gap-4 mt-6">
                <button @click="prevStep" class="px-6 py-3 border border-gray-300 rounded-lg hover:bg-gray-50 transition">
                    ← ย้อนกลับ
                </button>
                <button @click="nextStep" class="flex-1 bg-indigo-600 text-white py-3 rounded-lg font-semibold hover:bg-indigo-700 transition" :disabled="!dbConfigured">
                    ดำเนินการต่อ →
                </button>
            </div>
        </div>

        <!-- Step 4: Install Dependencies -->
        <div x-show="currentStep === 4" x-transition>
            <h2 class="text-2xl font-bold text-gray-800 mb-6">ติดตั้ง Dependencies</h2>

            <div id="dependencies-container">
                <div class="text-center py-8">
                    <p class="text-gray-600">พร้อมติดตั้ง Composer dependencies</p>
                </div>
            </div>

            <div class="flex gap-4 mt-6">
                <button @click="prevStep" class="px-6 py-3 border border-gray-300 rounded-lg hover:bg-gray-50 transition" :disabled="installing">
                    ← ย้อนกลับ
                </button>
                <button @click="installDependencies" class="flex-1 bg-indigo-600 text-white py-3 rounded-lg font-semibold hover:bg-indigo-700 transition" :disabled="installing">
                    <span x-show="!installing">เริ่มติดตั้ง Dependencies</span>
                    <span x-show="installing">กำลังติดตั้ง...</span>
                </button>
            </div>
        </div>

        <!-- Step 5: Database Migration -->
        <div x-show="currentStep === 5" x-transition>
            <h2 class="text-2xl font-bold text-gray-800 mb-6">สร้าง Database Tables</h2>

            <div id="migration-container">
                <div class="text-center py-8">
                    <p class="text-gray-600">พร้อมสร้าง database tables</p>
                </div>
            </div>

            <div class="flex gap-4 mt-6">
                <button @click="prevStep" class="px-6 py-3 border border-gray-300 rounded-lg hover:bg-gray-50 transition" :disabled="migrating">
                    ← ย้อนกลับ
                </button>
                <button @click="runMigrations" class="flex-1 bg-indigo-600 text-white py-3 rounded-lg font-semibold hover:bg-indigo-700 transition" :disabled="migrating">
                    <span x-show="!migrating">เริ่มสร้าง Tables</span>
                    <span x-show="migrating">กำลังสร้าง...</span>
                </button>
            </div>
        </div>

        <!-- Step 6: Create Admin -->
        <div x-show="currentStep === 6" x-transition>
            <h2 class="text-2xl font-bold text-gray-800 mb-6">สร้างบัญชี Super Admin</h2>

            <div class="bg-blue-50 border-l-4 border-blue-400 p-4 mb-6">
                <p class="text-sm text-blue-700">
                    <strong>หมายเหตุ:</strong> บัญชีนี้จะเป็น Super Admin ของระบบ และจะสามารถเข้าถึงทุกฟังก์ชันได้
                </p>
            </div>

            <form @submit.prevent="createAdmin" id="admin-form">
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">ชื่อ-นามสกุล</label>
                        <input type="text" x-model="adminData.name" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent" required>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">อีเมล</label>
                        <input type="email" x-model="adminData.email" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent" required>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">รหัสผ่าน (อย่างน้อย 8 ตัวอักษร)</label>
                        <input type="password" x-model="adminData.password" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent" minlength="8" required>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">ยืนยันรหัสผ่าน</label>
                        <input type="password" x-model="adminData.password_confirmation" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent" required>
                    </div>

                    <div id="admin-create-result"></div>

                    <button type="submit" class="w-full bg-indigo-600 text-white py-3 rounded-lg font-semibold hover:bg-indigo-700 transition" :disabled="creatingAdmin">
                        <span x-show="!creatingAdmin">สร้างบัญชี Super Admin</span>
                        <span x-show="creatingAdmin">กำลังสร้าง...</span>
                    </button>
                </div>
            </form>
        </div>

        <!-- Step 6: Complete -->
        <div x-show="currentStep === 5" x-transition>
            <div class="text-center py-8">
                <div class="text-6xl mb-4">🎉</div>
                <h2 class="text-3xl font-bold text-gray-800 mb-4">ติดตั้งสำเร็จ!</h2>
                <p class="text-lg text-gray-600 mb-8">ระบบ TP-Affiliate พร้อมใช้งานแล้ว</p>

                <div class="bg-green-50 rounded-lg p-6 mb-8 text-left">
                    <h3 class="font-bold text-gray-800 mb-4">ขั้นตอนถัดไป:</h3>
                    <ul class="space-y-2 text-gray-700">
                        <li class="flex items-start">
                            <span class="text-green-600 mr-2">✓</span>
                            <span>เข้าสู่ระบบด้วยบัญชี Super Admin ที่สร้างไว้</span>
                        </li>
                        <li class="flex items-start">
                            <span class="text-green-600 mr-2">✓</span>
                            <span>ตั้งค่าระบบใน Admin Dashboard</span>
                        </li>
                        <li class="flex items-start">
                            <span class="text-green-600 mr-2">✓</span>
                            <span>เพิ่มผู้ใช้และ Affiliates</span>
                        </li>
                        <li class="flex items-start">
                            <span class="text-green-600 mr-2">✓</span>
                            <span>ปรับแต่ง theme และ settings</span>
                        </li>
                    </ul>
                </div>

                <a href="{{ route('login') }}" class="inline-block bg-indigo-600 text-white px-8 py-4 rounded-lg font-bold text-lg hover:bg-indigo-700 transition duration-300 shadow-lg">
                    เข้าสู่ระบบ →
                </a>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function setupWizard() {
    return {
        currentStep: 0,
        steps: [
            { name: 'License', key: 'license' },
            { name: 'ยินดีต้อนรับ', key: 'welcome' },
            { name: 'ตรวจสอบระบบ', key: 'requirements' },
            { name: 'Database', key: 'database' },
            { name: 'Dependencies', key: 'dependencies' },
            { name: 'Migration', key: 'migration' },
            { name: 'Admin', key: 'admin' }
        ],
        licenseKey: '',
        licenseVerified: false,
        checkingLicense: false,
        requirementsChecked: false,
        requirementsPassed: false,
        dbConfigured: false,
        installing: false,
        migrating: false,
        creatingAdmin: false,
        dbConfig: {
            host: '127.0.0.1',
            port: '3306',
            database: 'thaiprompt_affiliate',
            username: '',
            password: ''
        },
        adminData: {
            name: '',
            email: '',
            password: '',
            password_confirmation: ''
        },

        get progress() {
            return (this.currentStep / (this.steps.length - 1)) * 100;
        },

        init() {
            // Auto-check requirements when reaching step 2
            this.$watch('currentStep', (value) => {
                if (value === 2 && !this.requirementsChecked) {
                    this.checkRequirements();
                }
            });
        },

        async checkLicense() {
            this.checkingLicense = true;
            const resultDiv = document.getElementById('license-check-result');
            resultDiv.innerHTML = '<div class="text-center py-2"><div class="inline-block animate-spin rounded-full h-6 w-6 border-b-2 border-indigo-600"></div></div>';

            try {
                const response = await fetch('{{ route('setup.check-auth') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({
                        license_key: this.licenseKey
                    })
                });

                const data = await response.json();

                if (data.authorized) {
                    this.licenseVerified = true;

                    let message = '✓ License ยืนยันสำเร็จ!';
                    if (data.bypass) {
                        message += ' (Developer Mode - ข้ามการตรวจสอบ)';
                    }

                    resultDiv.innerHTML = `<div class="bg-green-50 border-l-4 border-green-400 p-4 mt-4"><p class="text-green-700">${message}</p></div>`;

                    // Auto-proceed to next step
                    setTimeout(() => this.nextStep(), 1000);
                } else {
                    resultDiv.innerHTML = `<div class="bg-red-50 border-l-4 border-red-400 p-4 mt-4">
                        <p class="text-red-700 font-semibold">✗ ไม่สามารถยืนยัน License ได้</p>
                        <p class="text-red-600 text-sm mt-2">${data.message || 'กรุณาตรวจสอบ License Key'}</p>
                    </div>`;
                    this.licenseVerified = false;
                }
            } catch (error) {
                resultDiv.innerHTML = '<div class="bg-red-50 border-l-4 border-red-400 p-4 mt-4"><p class="text-red-700">เกิดข้อผิดพลาดในการตรวจสอบ License</p></div>';
                this.licenseVerified = false;
            }

            this.checkingLicense = false;
        },

        nextStep() {
            if (this.currentStep < this.steps.length - 1) {
                this.currentStep++;
            }
        },

        prevStep() {
            if (this.currentStep > 0) {
                this.currentStep--;
            }
        },

        async checkRequirements() {
            try {
                const response = await fetch('{{ route('setup.check-requirements') }}');
                const data = await response.json();

                const container = document.getElementById('requirements-container');
                container.innerHTML = this.renderRequirements(data);

                this.requirementsChecked = true;
                this.requirementsPassed = data.all_passed;
            } catch (error) {
                console.error('Error checking requirements:', error);
            }
        },

        renderRequirements(data) {
            let html = '<div class="space-y-4">';

            // PHP Version
            html += this.renderRequirementItem('PHP Version', data.php_version.current, data.php_version.passed, data.php_version.required);

            // PHP Extensions
            html += '<div class="border-t pt-4 mt-4"><h3 class="font-bold mb-2">PHP Extensions</h3>';
            for (const [name, status] of Object.entries(data.extensions)) {
                html += this.renderRequirementItem(name, status ? 'Installed' : 'Missing', status);
            }
            html += '</div>';

            // Permissions
            html += '<div class="border-t pt-4 mt-4"><h3 class="font-bold mb-2">File Permissions</h3>';
            for (const [path, status] of Object.entries(data.permissions)) {
                html += this.renderRequirementItem(path, status ? 'Writable' : 'Not Writable', status);
            }
            html += '</div>';

            html += '</div>';
            return html;
        },

        renderRequirementItem(name, value, passed, required = null) {
            const icon = passed ? '✓' : '✗';
            const colorClass = passed ? 'text-green-600' : 'text-red-600';
            const bgClass = passed ? 'bg-green-50' : 'bg-red-50';

            return `
                <div class="flex items-center justify-between p-3 ${bgClass} rounded-lg">
                    <div class="flex items-center">
                        <span class="${colorClass} text-xl font-bold mr-3">${icon}</span>
                        <div>
                            <div class="font-medium text-gray-800">${name}</div>
                            ${required ? `<div class="text-sm text-gray-500">Required: ${required}</div>` : ''}
                        </div>
                    </div>
                    <div class="text-sm font-medium ${colorClass}">${value}</div>
                </div>
            `;
        },

        async testDatabaseConnection() {
            const resultDiv = document.getElementById('db-test-result');
            resultDiv.innerHTML = '<div class="text-center py-2"><div class="inline-block animate-spin rounded-full h-6 w-6 border-b-2 border-indigo-600"></div></div>';

            try {
                const response = await fetch('{{ route('setup.test-database') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify(this.dbConfig)
                });

                const data = await response.json();

                if (data.success) {
                    resultDiv.innerHTML = '<div class="bg-green-50 border-l-4 border-green-400 p-4 mt-4"><p class="text-green-700">✓ เชื่อมต่อ Database สำเร็จ!</p></div>';
                    this.dbConfigured = true;
                } else {
                    resultDiv.innerHTML = `<div class="bg-red-50 border-l-4 border-red-400 p-4 mt-4"><p class="text-red-700">✗ ${data.message}</p></div>`;
                    this.dbConfigured = false;
                }
            } catch (error) {
                resultDiv.innerHTML = '<div class="bg-red-50 border-l-4 border-red-400 p-4 mt-4"><p class="text-red-700">เกิดข้อผิดพลาดในการทดสอบการเชื่อมต่อ</p></div>';
                this.dbConfigured = false;
            }
        },

        async installDependencies() {
            this.installing = true;
            const container = document.getElementById('dependencies-container');
            container.innerHTML = '<div class="text-center py-8"><div class="inline-block animate-spin rounded-full h-12 w-12 border-b-2 border-indigo-600"></div><p class="mt-4 text-gray-600">กำลังติดตั้ง Composer dependencies... อาจใช้เวลา 2-3 นาที</p></div>';

            try {
                const response = await fetch('{{ route('setup.install-dependencies') }}', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    }
                });

                const data = await response.json();

                if (data.success) {
                    container.innerHTML = '<div class="bg-green-50 border-l-4 border-green-400 p-4"><p class="text-green-700 font-semibold">✓ ติดตั้ง Dependencies สำเร็จ!</p></div>';
                    setTimeout(() => this.nextStep(), 1000);
                } else {
                    container.innerHTML = `<div class="bg-red-50 border-l-4 border-red-400 p-4"><p class="text-red-700">✗ เกิดข้อผิดพลาด: ${data.message}</p></div>`;
                }
            } catch (error) {
                container.innerHTML = '<div class="bg-red-50 border-l-4 border-red-400 p-4"><p class="text-red-700">เกิดข้อผิดพลาดในการติดตั้ง Dependencies</p></div>';
            }

            this.installing = false;
        },

        async runMigrations() {
            this.migrating = true;
            const container = document.getElementById('migration-container');
            container.innerHTML = '<div class="text-center py-8"><div class="inline-block animate-spin rounded-full h-12 w-12 border-b-2 border-indigo-600"></div><p class="mt-4 text-gray-600">กำลังสร้าง database tables...</p></div>';

            try {
                const response = await fetch('{{ route('setup.run-migrations') }}', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    }
                });

                const data = await response.json();

                if (data.success) {
                    container.innerHTML = '<div class="bg-green-50 border-l-4 border-green-400 p-4"><p class="text-green-700 font-semibold">✓ สร้าง database tables สำเร็จ!</p></div>';
                    setTimeout(() => this.nextStep(), 1000);
                } else {
                    container.innerHTML = `<div class="bg-red-50 border-l-4 border-red-400 p-4"><p class="text-red-700">✗ เกิดข้อผิดพลาด: ${data.message}</p></div>`;
                }
            } catch (error) {
                container.innerHTML = '<div class="bg-red-50 border-l-4 border-red-400 p-4"><p class="text-red-700">เกิดข้อผิดพลาดในการสร้าง database tables</p></div>';
            }

            this.migrating = false;
        },

        async createAdmin() {
            if (this.adminData.password !== this.adminData.password_confirmation) {
                const resultDiv = document.getElementById('admin-create-result');
                resultDiv.innerHTML = '<div class="bg-red-50 border-l-4 border-red-400 p-4 mt-4"><p class="text-red-700">รหัสผ่านไม่ตรงกัน</p></div>';
                return;
            }

            this.creatingAdmin = true;
            const resultDiv = document.getElementById('admin-create-result');
            resultDiv.innerHTML = '<div class="text-center py-2"><div class="inline-block animate-spin rounded-full h-6 w-6 border-b-2 border-indigo-600"></div></div>';

            try {
                const response = await fetch('{{ route('setup.create-admin') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify(this.adminData)
                });

                const data = await response.json();

                if (data.success) {
                    resultDiv.innerHTML = '<div class="bg-green-50 border-l-4 border-green-400 p-4 mt-4"><p class="text-green-700">✓ สร้างบัญชี Super Admin สำเร็จ!</p></div>';
                    setTimeout(() => {
                        window.location.href = '{{ route('login') }}';
                    }, 2000);
                } else {
                    resultDiv.innerHTML = `<div class="bg-red-50 border-l-4 border-red-400 p-4 mt-4"><p class="text-red-700">✗ ${data.message}</p></div>`;
                }
            } catch (error) {
                resultDiv.innerHTML = '<div class="bg-red-50 border-l-4 border-red-400 p-4 mt-4"><p class="text-red-700">เกิดข้อผิดพลาดในการสร้างบัญชี</p></div>';
            }

            this.creatingAdmin = false;
        }
    }
}
</script>
@endpush
