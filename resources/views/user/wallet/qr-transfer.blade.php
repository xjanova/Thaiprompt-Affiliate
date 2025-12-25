{{--
    QR Transfer - สแกน QR เพื่อโอนเงิน
    ใช้กล้องสแกน QR Code หรืออัพโหลดรูป QR เพื่อโอนเงินทันที
--}}

@extends('layouts.user-arrow-x')

@section('title', 'สแกนจ่าย QR Code')

@section('content')
<div class="space-y-6 pb-20 lg:pb-6" x-data="qrTransferManager()">
    {{-- Premium Hero Header (Cyan-Blue-Indigo for QR Transfer/Scan) --}}
    <div class="relative overflow-hidden bg-gradient-to-r from-cyan-600 via-blue-600 to-indigo-600 dark:from-cyan-800 dark:via-blue-800 dark:to-indigo-800 rounded-2xl shadow-2xl p-8">
        {{-- Animated Background Orbs --}}
        <div class="absolute inset-0 opacity-10">
            <div class="absolute top-0 right-0 w-96 h-96 bg-white rounded-full blur-3xl animate-pulse"></div>
            <div class="absolute bottom-0 left-0 w-96 h-96 bg-white rounded-full blur-3xl animate-pulse" style="animation-delay: 0.5s"></div>
        </div>

        {{-- Floating Icons --}}
        <div class="absolute inset-0 overflow-hidden pointer-events-none">
            <div class="absolute text-white/10 text-8xl top-10 right-20" style="animation: float 6s ease-in-out infinite">
                <i class="fas fa-camera-retro"></i>
            </div>
        </div>

        {{-- Header Content --}}
        <div class="relative z-10">
            <div class="flex items-center gap-3 mb-4">
                <a href="{{ route('user.wallet.index') }}" class="glass-fusion px-4 py-2 hover:bg-white/25 rounded-lg transition-all">
                    <i class="fas fa-arrow-left mr-2"></i>กลับ
                </a>
                <div class="glass-fusion p-4 rounded-2xl">
                    <i class="fas fa-camera text-4xl text-white drop-shadow-lg"></i>
                </div>
                <div>
                    <h1 class="text-4xl font-bold text-white drop-shadow-lg">สแกนจ่าย</h1>
                    <p class="text-blue-100 text-lg mt-1">สแกน QR Code เพื่อโอนเงินทันที</p>
                </div>
            </div>

            {{-- Balance Display --}}
            <div class="mt-6 glass-fusion rounded-xl p-6">
                <p class="text-blue-100 text-sm mb-1">ยอดเงินคงเหลือ</p>
                <p class="text-5xl font-bold text-white drop-shadow-lg">฿{{ number_format($wallet->balance, 2) }}</p>
            </div>
        </div>
    </div>

    <!-- Scanner Mode Tabs -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-2">
        <div class="flex gap-2">
            <button @click="mode = 'scan'"
                    :class="mode === 'scan' ? 'bg-indigo-600 text-white' : 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300'"
                    class="flex-1 py-3 rounded-lg font-medium transition flex items-center justify-center gap-2">
                <i class="fas fa-camera"></i>
                สแกนกล้อง
            </button>
            <button @click="mode = 'upload'"
                    :class="mode === 'upload' ? 'bg-indigo-600 text-white' : 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300'"
                    class="flex-1 py-3 rounded-lg font-medium transition flex items-center justify-center gap-2">
                <i class="fas fa-image"></i>
                อัพโหลดรูป
            </button>
            <button @click="mode = 'manual'"
                    :class="mode === 'manual' ? 'bg-indigo-600 text-white' : 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300'"
                    class="flex-1 py-3 rounded-lg font-medium transition flex items-center justify-center gap-2">
                <i class="fas fa-keyboard"></i>
                กรอกเอง
            </button>
        </div>
    </div>

    <!-- Scanner Section -->
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl overflow-hidden">
        <!-- Camera Scanner -->
        <div x-show="mode === 'scan'" x-transition>
            <div class="relative">
                <video id="qr-video" class="w-full aspect-square object-cover bg-black"></video>
                <!-- Scan Overlay -->
                <div class="absolute inset-0 flex items-center justify-center pointer-events-none">
                    <div class="w-64 h-64 border-4 border-white/50 rounded-3xl relative">
                        <div class="absolute -top-1 -left-1 w-8 h-8 border-t-4 border-l-4 border-indigo-500 rounded-tl-xl"></div>
                        <div class="absolute -top-1 -right-1 w-8 h-8 border-t-4 border-r-4 border-indigo-500 rounded-tr-xl"></div>
                        <div class="absolute -bottom-1 -left-1 w-8 h-8 border-b-4 border-l-4 border-indigo-500 rounded-bl-xl"></div>
                        <div class="absolute -bottom-1 -right-1 w-8 h-8 border-b-4 border-r-4 border-indigo-500 rounded-br-xl"></div>
                        <div class="absolute inset-x-0 top-1/2 h-0.5 bg-indigo-500 animate-pulse"></div>
                    </div>
                </div>
            </div>
            <div class="p-4 text-center">
                <p class="text-gray-600 dark:text-gray-400">วาง QR Code ในกรอบเพื่อสแกน</p>
                <button @click="startCamera()" x-show="!cameraActive"
                        class="mt-4 px-6 py-3 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl font-medium transition">
                    <i class="fas fa-video mr-2"></i>
                    เปิดกล้อง
                </button>
                <button @click="stopCamera()" x-show="cameraActive"
                        class="mt-4 px-6 py-3 bg-red-600 hover:bg-red-700 text-white rounded-xl font-medium transition">
                    <i class="fas fa-stop mr-2"></i>
                    ปิดกล้อง
                </button>
            </div>
        </div>

        <!-- Upload Image -->
        <div x-show="mode === 'upload'" x-transition class="p-8">
            <div class="text-center">
                <label class="cursor-pointer">
                    <div class="border-4 border-dashed border-gray-300 dark:border-gray-600 rounded-2xl p-12 hover:border-indigo-500 transition">
                        <i class="fas fa-cloud-upload-alt text-6xl text-gray-400 dark:text-gray-500 mb-4"></i>
                        <p class="text-gray-600 dark:text-gray-400 font-medium">คลิกเพื่ออัพโหลดรูป QR Code</p>
                        <p class="text-gray-500 text-sm mt-2">รองรับ JPG, PNG, GIF</p>
                    </div>
                    <input type="file" accept="image/*" @change="handleImageUpload($event)" class="hidden">
                </label>

                <!-- Preview -->
                <div x-show="uploadedImage" class="mt-6">
                    <img :src="uploadedImage" class="max-w-xs mx-auto rounded-xl shadow-lg">
                </div>
            </div>
        </div>

        <!-- Manual Input -->
        <div x-show="mode === 'manual'" x-transition class="p-6">
            <div class="max-w-md mx-auto space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Wallet Address ผู้รับ
                    </label>
                    <input type="text"
                           x-model="manualAddress"
                           @input="lookupWallet()"
                           class="w-full px-4 py-3 border-2 border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                           placeholder="TPW...">
                </div>

                <button @click="validateManualAddress()"
                        :disabled="!manualAddress || manualAddress.length < 10"
                        class="w-full py-3 bg-indigo-600 hover:bg-indigo-700 disabled:bg-gray-400 text-white rounded-xl font-medium transition">
                    <i class="fas fa-search mr-2"></i>
                    ค้นหา
                </button>
            </div>
        </div>
    </div>

    <!-- Recipient Info (After Scan) -->
    <div x-show="recipientData" x-transition
         class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-6">
        <h3 class="font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
            <i class="fas fa-user-check text-green-500"></i>
            ข้อมูลผู้รับเงิน
        </h3>

        <div class="flex items-center gap-4 p-4 bg-gray-50 dark:bg-gray-700 rounded-xl mb-6">
            <div class="w-16 h-16 bg-gradient-to-br from-indigo-500 to-purple-600 rounded-full flex items-center justify-center text-white text-2xl font-bold">
                <span x-text="recipientData?.user_name?.charAt(0) || '?'"></span>
            </div>
            <div class="flex-1">
                <p class="font-bold text-gray-900 dark:text-white text-lg" x-text="recipientData?.user_name"></p>
                <p class="text-gray-500 dark:text-gray-400 text-sm font-mono" x-text="recipientData?.wallet_address"></p>
            </div>
            <div class="text-green-500">
                <i class="fas fa-check-circle text-2xl"></i>
            </div>
        </div>

        <!-- Amount Input -->
        <div class="mb-6">
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                จำนวนเงินที่ต้องการโอน
            </label>
            <div class="relative">
                <span class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-500 text-xl">฿</span>
                <input type="number"
                       x-model="transferAmount"
                       @input="calculateFee()"
                       min="{{ $minTransferAmount }}"
                       step="0.01"
                       class="w-full pl-12 pr-4 py-4 text-2xl border-2 border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                       placeholder="0.00">
            </div>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-2">
                ยอดโอนขั้นต่ำ: ฿{{ number_format($minTransferAmount, 2) }} |
                ยอดคงเหลือ: ฿{{ number_format($wallet->balance, 2) }}
            </p>
        </div>

        <!-- Fee Display -->
        <div class="bg-gray-50 dark:bg-gray-700 rounded-xl p-4 mb-6">
            <div class="flex justify-between items-center mb-2">
                <span class="text-gray-600 dark:text-gray-400">จำนวนเงินโอน</span>
                <span class="font-medium text-gray-900 dark:text-white">฿<span x-text="formatNumber(transferAmount || 0)"></span></span>
            </div>
            <div class="flex justify-between items-center mb-2">
                <span class="text-gray-600 dark:text-gray-400">ค่าธรรมเนียม</span>
                <span class="font-medium" :class="feeAmount > 0 ? 'text-orange-500' : 'text-green-500'">
                    ฿<span x-text="formatNumber(feeAmount)"></span>
                    <template x-if="feeAmount === 0">
                        <span class="text-xs ml-1">(ฟรี!)</span>
                    </template>
                </span>
            </div>
            <div class="border-t border-gray-300 dark:border-gray-600 pt-2 mt-2">
                <div class="flex justify-between items-center">
                    <span class="font-bold text-gray-900 dark:text-white">ยอดหักทั้งหมด</span>
                    <span class="font-bold text-xl text-indigo-600">฿<span x-text="formatNumber(totalDeduction)"></span></span>
                </div>
            </div>
        </div>

        <!-- Balance Warning -->
        <div x-show="!hasEnoughBalance" class="bg-red-50 dark:bg-red-900/30 border border-red-200 dark:border-red-700 rounded-xl p-4 mb-6">
            <p class="text-red-600 dark:text-red-400 flex items-center gap-2">
                <i class="fas fa-exclamation-triangle"></i>
                ยอดเงินไม่เพียงพอ กรุณาเติมเงินก่อนทำรายการ
            </p>
        </div>

        <!-- Description -->
        <div class="mb-6">
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                หมายเหตุ (ไม่บังคับ)
            </label>
            <input type="text"
                   x-model="description"
                   maxlength="255"
                   class="w-full px-4 py-3 border-2 border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                   placeholder="เช่น ค่าอาหาร, โอนคืน">
        </div>

        <!-- PIN Input -->
        <div class="mb-6">
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                PIN กระเป๋าเงิน (6 หลัก)
            </label>
            <input type="password"
                   x-model="pin"
                   maxlength="6"
                   pattern="[0-9]{6}"
                   class="w-full px-4 py-3 text-center text-2xl tracking-widest border-2 border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                   placeholder="● ● ● ● ● ●">
        </div>

        <!-- Submit Button -->
        <button @click="submitTransfer()"
                :disabled="!canSubmit || isSubmitting"
                class="w-full py-4 bg-gradient-to-r from-indigo-600 to-purple-600 hover:from-indigo-700 hover:to-purple-700 disabled:from-gray-400 disabled:to-gray-500 text-white rounded-xl font-bold text-lg transition flex items-center justify-center gap-2">
            <template x-if="isSubmitting">
                <i class="fas fa-spinner fa-spin"></i>
            </template>
            <template x-if="!isSubmitting">
                <i class="fas fa-paper-plane"></i>
            </template>
            <span x-text="isSubmitting ? 'กำลังโอน...' : 'ยืนยันการโอนเงิน'"></span>
        </button>

        <!-- Cancel Button -->
        <button @click="resetForm()"
                class="w-full py-3 mt-3 bg-gray-200 dark:bg-gray-700 hover:bg-gray-300 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 rounded-xl font-medium transition">
            <i class="fas fa-times mr-2"></i>
            ยกเลิก
        </button>
    </div>

    <!-- Success Modal -->
    <div x-show="showSuccess" x-transition
         class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm">
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-2xl p-8 max-w-md w-full mx-4 text-center">
            <div class="w-20 h-20 mx-auto bg-green-100 dark:bg-green-900/30 rounded-full flex items-center justify-center mb-6">
                <i class="fas fa-check text-4xl text-green-500"></i>
            </div>
            <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-2">โอนเงินสำเร็จ!</h2>
            <p class="text-gray-600 dark:text-gray-400 mb-6" x-text="successMessage"></p>

            <div class="bg-gray-50 dark:bg-gray-700 rounded-xl p-4 mb-6 text-left">
                <div class="flex justify-between mb-2">
                    <span class="text-gray-500">เลขอ้างอิง</span>
                    <span class="font-mono text-sm text-indigo-600 dark:text-indigo-400" x-text="successData?.reference"></span>
                </div>
                <div class="flex justify-between mb-2">
                    <span class="text-gray-500">ผู้รับ</span>
                    <span class="font-medium text-gray-900 dark:text-white" x-text="successData?.to_name"></span>
                </div>
                <div class="flex justify-between mb-2">
                    <span class="text-gray-500">จำนวน</span>
                    <span class="font-bold text-green-500">฿<span x-text="formatNumber(successData?.amount)"></span></span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-500">ค่าธรรมเนียม</span>
                    <span class="text-gray-900 dark:text-white">฿<span x-text="formatNumber(successData?.fee)"></span></span>
                </div>
            </div>

            <a href="{{ route('user.wallet.index') }}"
               class="block w-full py-3 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl font-medium transition">
                กลับหน้ากระเป๋าเงิน
            </a>
        </div>
    </div>
</div>

@push('scripts')
<!-- jsQR for scanning QR from camera/image -->
<script src="https://cdn.jsdelivr.net/npm/jsqr@1.4.0/dist/jsQR.min.js"></script>
<script>
    function qrTransferManager() {
        return {
            mode: 'scan',
            cameraActive: false,
            videoStream: null,
            scanInterval: null,
            uploadedImage: null,
            manualAddress: '',

            // Recipient data
            recipientData: null,

            // Transfer form
            transferAmount: null,
            feeAmount: 0,
            totalDeduction: 0,
            hasEnoughBalance: true,
            description: '',
            pin: '',
            isSubmitting: false,

            // Success
            showSuccess: false,
            successMessage: '',
            successData: null,

            // Settings
            balance: {{ $wallet->balance }},
            feeType: '{{ $transferFeeType }}',
            baseFee: {{ $transferFee }},
            minAmount: {{ $minTransferAmount }},

            get canSubmit() {
                return this.recipientData &&
                       this.transferAmount >= this.minAmount &&
                       this.pin.length === 6 &&
                       this.hasEnoughBalance;
            },

            init() {
                // Auto start camera on mobile
                if (this.mode === 'scan') {
                    // Don't auto-start for privacy
                }
            },

            async startCamera() {
                try {
                    const video = document.getElementById('qr-video');
                    this.videoStream = await navigator.mediaDevices.getUserMedia({
                        video: { facingMode: 'environment' }
                    });
                    video.srcObject = this.videoStream;
                    video.play();
                    this.cameraActive = true;

                    // Start scanning
                    const canvas = document.createElement('canvas');
                    const ctx = canvas.getContext('2d');

                    this.scanInterval = setInterval(() => {
                        if (video.readyState === video.HAVE_ENOUGH_DATA) {
                            canvas.width = video.videoWidth;
                            canvas.height = video.videoHeight;
                            ctx.drawImage(video, 0, 0, canvas.width, canvas.height);

                            const imageData = ctx.getImageData(0, 0, canvas.width, canvas.height);
                            const code = jsQR(imageData.data, imageData.width, imageData.height);

                            if (code) {
                                this.handleQrCode(code.data);
                            }
                        }
                    }, 200);
                } catch (err) {
                    console.error('Camera error:', err);
                    this.showNotification('ไม่สามารถเปิดกล้องได้ กรุณาอนุญาตการเข้าถึงกล้อง', 'error');
                }
            },

            stopCamera() {
                if (this.videoStream) {
                    this.videoStream.getTracks().forEach(track => track.stop());
                    this.videoStream = null;
                }
                if (this.scanInterval) {
                    clearInterval(this.scanInterval);
                    this.scanInterval = null;
                }
                this.cameraActive = false;
            },

            handleImageUpload(event) {
                const file = event.target.files[0];
                if (!file) return;

                const reader = new FileReader();
                reader.onload = (e) => {
                    this.uploadedImage = e.target.result;

                    // Decode QR from image
                    const img = new Image();
                    img.onload = () => {
                        const canvas = document.createElement('canvas');
                        const ctx = canvas.getContext('2d');
                        canvas.width = img.width;
                        canvas.height = img.height;
                        ctx.drawImage(img, 0, 0);

                        const imageData = ctx.getImageData(0, 0, canvas.width, canvas.height);
                        const code = jsQR(imageData.data, imageData.width, imageData.height);

                        if (code) {
                            this.handleQrCode(code.data);
                        } else {
                            this.showNotification('ไม่พบ QR Code ในรูปภาพ กรุณาลองใหม่', 'error');
                        }
                    };
                    img.src = e.target.result;
                };
                reader.readAsDataURL(file);
            },

            async handleQrCode(data) {
                this.stopCamera();

                try {
                    // Parse QR data
                    let qrData;
                    try {
                        qrData = JSON.parse(data);
                    } catch {
                        // Maybe it's just a wallet address
                        if (data.startsWith('TPW')) {
                            qrData = { address: data };
                        } else {
                            throw new Error('Invalid QR format');
                        }
                    }

                    if (!qrData.address) {
                        throw new Error('ไม่พบ Wallet Address ใน QR Code');
                    }

                    // Lookup wallet
                    await this.lookupWallet(qrData.address, qrData.amount);

                } catch (err) {
                    console.error('QR parse error:', err);
                    this.showNotification('QR Code ไม่ถูกต้อง กรุณาลองใหม่', 'error');
                }
            },

            async lookupWallet(address = null, presetAmount = null) {
                const walletAddress = address || this.manualAddress;
                if (!walletAddress || walletAddress.length < 10) return;

                try {
                    const response = await fetch('{{ route("user.wallet.qr-transfer.lookup") }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        body: JSON.stringify({ wallet_address: walletAddress })
                    });

                    const result = await response.json();

                    if (result.success) {
                        this.recipientData = result.data;
                        if (presetAmount) {
                            this.transferAmount = presetAmount;
                            this.calculateFee();
                        }
                        this.showNotification('พบผู้รับเงิน: ' + result.data.user_name, 'success');
                    } else {
                        this.showNotification(result.message, 'error');
                    }
                } catch (err) {
                    console.error('Lookup error:', err);
                    this.showNotification('เกิดข้อผิดพลาดในการค้นหา', 'error');
                }
            },

            validateManualAddress() {
                if (this.manualAddress && this.manualAddress.length >= 10) {
                    this.lookupWallet();
                }
            },

            async calculateFee() {
                if (!this.transferAmount || this.transferAmount <= 0) {
                    this.feeAmount = 0;
                    this.totalDeduction = 0;
                    this.hasEnoughBalance = true;
                    return;
                }

                try {
                    const response = await fetch('{{ route("user.wallet.qr-transfer.calculate-fee") }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        body: JSON.stringify({ amount: this.transferAmount })
                    });

                    const result = await response.json();

                    if (result.success) {
                        this.feeAmount = result.data.fee;
                        this.totalDeduction = result.data.total;
                        this.hasEnoughBalance = result.data.has_enough_balance;
                    }
                } catch (err) {
                    console.error('Fee calculation error:', err);
                }
            },

            async submitTransfer() {
                if (!this.canSubmit || this.isSubmitting) return;

                this.isSubmitting = true;

                try {
                    const response = await fetch('{{ route("user.wallet.qr-transfer.process") }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        body: JSON.stringify({
                            wallet_address: this.recipientData.wallet_address,
                            amount: this.transferAmount,
                            pin: this.pin,
                            description: this.description || 'QR Transfer'
                        })
                    });

                    const result = await response.json();

                    if (result.success) {
                        this.successData = result.data;
                        this.successMessage = `โอนเงิน ฿${this.formatNumber(result.data.amount)} ให้ ${result.data.to_name} สำเร็จ`;
                        this.showSuccess = true;
                    } else {
                        this.showNotification(result.message, 'error');
                    }
                } catch (err) {
                    console.error('Transfer error:', err);
                    this.showNotification('เกิดข้อผิดพลาดในการโอนเงิน', 'error');
                } finally {
                    this.isSubmitting = false;
                }
            },

            resetForm() {
                this.recipientData = null;
                this.transferAmount = null;
                this.feeAmount = 0;
                this.totalDeduction = 0;
                this.hasEnoughBalance = true;
                this.description = '';
                this.pin = '';
                this.manualAddress = '';
                this.uploadedImage = null;
            },

            formatNumber(num) {
                return parseFloat(num || 0).toLocaleString('th-TH', {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2
                });
            },

            showNotification(message, type = 'info') {
                const toast = document.createElement('div');
                toast.className = `fixed top-4 left-1/2 -translate-x-1/2 px-6 py-3 rounded-xl shadow-lg text-white z-50 ${
                    type === 'success' ? 'bg-green-600' : type === 'error' ? 'bg-red-600' : 'bg-indigo-600'
                }`;
                toast.textContent = message;
                document.body.appendChild(toast);

                setTimeout(() => {
                    toast.classList.add('opacity-0', 'transition-opacity');
                    setTimeout(() => toast.remove(), 300);
                }, 3000);
            }
        }
    }
</script>
@endpush

@push('styles')
<style>
.glass-fusion {
    background: rgba(255, 255, 255, 0.15);
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255, 255, 255, 0.2);
}
@keyframes float {
    0%, 100% { transform: translateY(0px); }
    50% { transform: translateY(-20px); }
}
</style>
@endpush
@endsection
