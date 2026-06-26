{{--
    QR Transfer - สแกน QR เพื่อโอนเงิน (Theme V4)
    ใช้กล้องสแกน QR Code หรืออัพโหลดรูป QR เพื่อโอนเงินทันที
    *** Alpine component qrTransferManager() + jsQR + fetch endpoints คงเดิมทั้งหมด ***
--}}
@extends('layouts.user-v4')

@section('title', 'สแกนจ่าย QR Code')

@section('content')
<div style="display:flex; flex-direction:column; gap:18px;" x-data="qrTransferManager()">

    {{-- ── Hero + ยอดเงิน ─────────────────────────────────────── --}}
    <div class="tp-card" style="padding:0; overflow:hidden;">
        <div style="padding:24px; background:linear-gradient(120deg, color-mix(in srgb, var(--accent1) 16%, transparent), transparent 72%);">
            <div style="display:flex; flex-wrap:wrap; align-items:center; gap:14px;">
                <a href="{{ route('user.wallet.index') }}" class="tp-icon-btn" title="กลับ"><i class="fas fa-arrow-left"></i></a>
                <span class="tp-tile" style="width:52px; height:52px; border-radius:16px; font-size:24px;"><i class="fas fa-camera" style="color:#fff;"></i></span>
                <div style="flex:1; min-width:200px;">
                    <h1 style="font-size:clamp(20px,4vw,26px); font-weight:800; margin:0;">สแกนจ่าย</h1>
                    <div style="font-size:12.5px; color:var(--ink2); margin-top:3px;">สแกน QR Code เพื่อโอนเงินทันที</div>
                </div>
            </div>
            <div style="margin-top:18px; padding:18px 22px; border-radius:18px; box-shadow:var(--inset);">
                <div style="font-size:12.5px; color:var(--ink2);">ยอดเงินคงเหลือ</div>
                <div class="tp-num" style="font-size:clamp(30px,6vw,44px); font-weight:800; line-height:1.1; margin-top:4px; color:var(--deep1);">฿{{ number_format($wallet->balance, 2) }}</div>
            </div>
        </div>
    </div>

    {{-- ── โหมดสแกน (แท็บ) ────────────────────────────────────── --}}
    <div class="tp-card" style="padding:7px; display:flex; gap:7px;">
        @php
            $modes = [['scan','fa-camera','สแกนกล้อง'], ['upload','fa-image','อัพโหลดรูป'], ['manual','fa-keyboard','กรอกเอง']];
        @endphp
        @foreach($modes as [$mval, $mic, $mlabel])
            <button @click="mode = '{{ $mval }}'" type="button"
                    :style="mode === '{{ $mval }}' ? 'background:linear-gradient(135deg,var(--accent1),var(--accent2));color:#fff;box-shadow:var(--raise);' : 'color:var(--ink2);'"
                    style="flex:1; border:0; cursor:pointer; font-family:inherit; font-weight:600; font-size:12.5px; padding:11px 6px; border-radius:12px; display:flex; align-items:center; justify-content:center; gap:7px; background:transparent; transition:all .18s ease;">
                <i class="fas {{ $mic }}"></i> {{ $mlabel }}
            </button>
        @endforeach
    </div>

    {{-- ── พื้นที่สแกน ────────────────────────────────────────── --}}
    <div class="tp-card" style="padding:0; overflow:hidden;">
        {{-- กล้อง --}}
        <div x-show="mode === 'scan'" x-transition>
            <div style="position:relative;">
                <video id="qr-video" style="width:100%; aspect-ratio:1/1; object-fit:cover; background:#000; display:block;"></video>
                <div style="position:absolute; inset:0; display:flex; align-items:center; justify-content:center; pointer-events:none;">
                    <div style="width:256px; height:256px; border:4px solid rgba(255,255,255,.5); border-radius:24px; position:relative;">
                        <div style="position:absolute; inset-inline:0; top:50%; height:2px; background:var(--accent1);" class="animate-pulse"></div>
                    </div>
                </div>
            </div>
            <div style="padding:16px; text-align:center;">
                <div style="font-size:12.5px; color:var(--ink2);">วาง QR Code ในกรอบเพื่อสแกน</div>
                <button @click="startCamera()" x-show="!cameraActive" type="button" class="tp-btn tp-btn-primary" style="margin-top:14px;"><i class="fas fa-video"></i> เปิดกล้อง</button>
                <button @click="stopCamera()" x-show="cameraActive" type="button" class="tp-btn" style="margin-top:14px; color:#d9534f;"><i class="fas fa-stop"></i> ปิดกล้อง</button>
            </div>
        </div>

        {{-- อัพโหลดรูป --}}
        <div x-show="mode === 'upload'" x-transition style="padding:28px;">
            <div style="text-align:center;">
                <label style="cursor:pointer; display:block;">
                    <div style="border:3px dashed color-mix(in srgb, var(--ink2) 35%, transparent); border-radius:18px; padding:44px 20px;">
                        <i class="fas fa-cloud-arrow-up" style="font-size:46px; color:var(--ink2);"></i>
                        <div style="font-size:13.5px; font-weight:600; margin-top:12px;">คลิกเพื่ออัพโหลดรูป QR Code</div>
                        <div style="font-size:11.5px; color:var(--ink2); margin-top:4px;">รองรับ JPG, PNG, GIF</div>
                    </div>
                    <input type="file" accept="image/*" @change="handleImageUpload($event)" style="display:none;">
                </label>
                <div x-show="uploadedImage" x-cloak style="margin-top:18px;">
                    <img :src="uploadedImage" style="max-width:280px; margin:0 auto; border-radius:14px; box-shadow:var(--raise);">
                </div>
            </div>
        </div>

        {{-- กรอกเอง --}}
        <div x-show="mode === 'manual'" x-transition style="padding:22px;">
            <div style="max-width:440px; margin:0 auto; display:flex; flex-direction:column; gap:14px;">
                <div>
                    <label style="display:block; font-size:11.5px; font-weight:600; color:var(--ink2); margin-bottom:6px;">Wallet Address ผู้รับ</label>
                    <input type="text" x-model="manualAddress" @input="lookupWallet()" class="tp-input" placeholder="TPW...">
                </div>
                <button @click="validateManualAddress()" :disabled="!manualAddress || manualAddress.length < 10" type="button" class="tp-btn tp-btn-primary" style="width:100%; justify-content:center;"><i class="fas fa-magnifying-glass"></i> ค้นหา</button>
            </div>
        </div>
    </div>

    {{-- ── ข้อมูลผู้รับ + ฟอร์มโอน ───────────────────────────── --}}
    <div x-show="recipientData" x-transition x-cloak class="tp-card" style="padding:20px;">
        <div class="tp-section-h" style="margin-bottom:14px;">✅ ข้อมูลผู้รับเงิน</div>

        <div style="display:flex; align-items:center; gap:14px; padding:14px; border-radius:14px; box-shadow:var(--inset-sm); margin-bottom:18px;">
            <span class="tp-tile" style="width:54px; height:54px; border-radius:50%; font-size:22px;"><span x-text="recipientData?.user_name?.charAt(0) || '?'"></span></span>
            <div style="flex:1; min-width:0;">
                <div style="font-size:16px; font-weight:700;" x-text="recipientData?.user_name"></div>
                <div class="tp-num" style="font-size:12px; color:var(--ink2);" x-text="recipientData?.wallet_address"></div>
            </div>
            <i class="fas fa-circle-check" style="color:#5aa07e; font-size:22px;"></i>
        </div>

        {{-- จำนวนเงิน --}}
        <label style="display:block; font-size:11.5px; font-weight:600; color:var(--ink2); margin-bottom:6px;">จำนวนเงินที่ต้องการโอน</label>
        <div style="position:relative; margin-bottom:6px;">
            <span style="position:absolute; left:15px; top:50%; transform:translateY(-50%); color:var(--ink2); font-size:20px;">฿</span>
            <input type="number" x-model="transferAmount" @input="calculateFee()" min="{{ $minTransferAmount }}" step="0.01" class="tp-input tp-num" style="padding-left:38px; font-size:22px;" placeholder="0.00">
        </div>
        <div style="font-size:11.5px; color:var(--ink2); margin-bottom:16px;">ยอดโอนขั้นต่ำ: ฿{{ number_format($minTransferAmount, 2) }} | ยอดคงเหลือ: ฿{{ number_format($wallet->balance, 2) }}</div>

        {{-- ค่าธรรมเนียม --}}
        <div style="padding:14px; border-radius:14px; box-shadow:var(--inset-sm); margin-bottom:16px; display:flex; flex-direction:column; gap:8px;">
            <div style="display:flex; justify-content:space-between; font-size:13px;"><span style="color:var(--ink2);">จำนวนเงินโอน</span><span class="tp-num" style="font-weight:600;">฿<span x-text="formatNumber(transferAmount || 0)"></span></span></div>
            <div style="display:flex; justify-content:space-between; font-size:13px;">
                <span style="color:var(--ink2);">ค่าธรรมเนียม</span>
                <span class="tp-num" style="font-weight:600;" :style="feeAmount > 0 ? 'color:#e0a52e' : 'color:#5aa07e'">฿<span x-text="formatNumber(feeAmount)"></span><template x-if="feeAmount === 0"><span style="font-size:11px; margin-left:4px;">(ฟรี!)</span></template></span>
            </div>
            <div style="border-top:1px solid color-mix(in srgb, var(--ink2) 16%, transparent); padding-top:8px; display:flex; justify-content:space-between; align-items:center;">
                <span style="font-weight:700;">ยอดหักทั้งหมด</span>
                <span class="tp-num" style="font-weight:800; font-size:18px; color:var(--deep1);">฿<span x-text="formatNumber(totalDeduction)"></span></span>
            </div>
        </div>

        {{-- เตือนยอดไม่พอ --}}
        <div x-show="!hasEnoughBalance" x-cloak style="padding:12px 14px; border-radius:12px; box-shadow:var(--inset-sm); margin-bottom:16px; color:#d9534f; font-size:12.5px;">
            <i class="fas fa-triangle-exclamation"></i> ยอดเงินไม่เพียงพอ กรุณาเติมเงินก่อนทำรายการ
        </div>

        {{-- หมายเหตุ --}}
        <label style="display:block; font-size:11.5px; font-weight:600; color:var(--ink2); margin-bottom:6px;">หมายเหตุ (ไม่บังคับ)</label>
        <input type="text" x-model="description" maxlength="255" class="tp-input" style="margin-bottom:16px;" placeholder="เช่น ค่าอาหาร, โอนคืน">

        {{-- PIN --}}
        <label style="display:block; font-size:11.5px; font-weight:600; color:var(--ink2); margin-bottom:6px;">PIN กระเป๋าเงิน (6 หลัก)</label>
        <input type="password" x-model="pin" maxlength="6" pattern="[0-9]{6}" class="tp-input tp-num" style="margin-bottom:18px; text-align:center; font-size:22px; letter-spacing:8px;" placeholder="● ● ● ● ● ●">

        {{-- ปุ่ม --}}
        <button @click="submitTransfer()" :disabled="!canSubmit || isSubmitting" type="button" class="tp-btn tp-btn-primary" style="width:100%; justify-content:center; height:48px; font-size:15px;">
            <template x-if="isSubmitting"><i class="fas fa-spinner fa-spin"></i></template>
            <template x-if="!isSubmitting"><i class="fas fa-paper-plane"></i></template>
            <span x-text="isSubmitting ? 'กำลังโอน...' : 'ยืนยันการโอนเงิน'"></span>
        </button>
        <button @click="resetForm()" type="button" class="tp-btn" style="width:100%; justify-content:center; margin-top:10px;"><i class="fas fa-xmark"></i> ยกเลิก</button>
    </div>

    {{-- ── Success Modal ─────────────────────────────────────── --}}
    <div x-show="showSuccess" x-cloak x-transition style="position:fixed; inset:0; z-index:120; display:flex; align-items:center; justify-content:center; background:rgba(0,0,0,.5); -webkit-backdrop-filter:blur(3px); backdrop-filter:blur(3px); padding:16px;">
        <div class="tp-card" style="max-width:420px; width:100%; padding:28px; text-align:center;">
            <span class="tp-tile" style="width:72px; height:72px; border-radius:50%; font-size:32px; margin:0 auto; background:#5aa07e;"><i class="fas fa-check" style="color:#fff;"></i></span>
            <div style="font-size:22px; font-weight:800; margin-top:16px;">โอนเงินสำเร็จ!</div>
            <div style="font-size:13px; color:var(--ink2); margin-top:6px;" x-text="successMessage"></div>
            <div style="padding:14px; border-radius:14px; box-shadow:var(--inset-sm); margin-top:18px; text-align:left; display:flex; flex-direction:column; gap:8px;">
                <div style="display:flex; justify-content:space-between; font-size:12.5px;"><span style="color:var(--ink2);">เลขอ้างอิง</span><span class="tp-num" style="color:var(--deep1);" x-text="successData?.reference"></span></div>
                <div style="display:flex; justify-content:space-between; font-size:12.5px;"><span style="color:var(--ink2);">ผู้รับ</span><span style="font-weight:600;" x-text="successData?.to_name"></span></div>
                <div style="display:flex; justify-content:space-between; font-size:12.5px;"><span style="color:var(--ink2);">จำนวน</span><span class="tp-num" style="font-weight:700; color:#5aa07e;">฿<span x-text="formatNumber(successData?.amount)"></span></span></div>
                <div style="display:flex; justify-content:space-between; font-size:12.5px;"><span style="color:var(--ink2);">ค่าธรรมเนียม</span><span class="tp-num">฿<span x-text="formatNumber(successData?.fee)"></span></span></div>
            </div>
            <a href="{{ route('user.wallet.index') }}" class="tp-btn tp-btn-primary" style="width:100%; justify-content:center; margin-top:18px;">กลับหน้ากระเป๋าเงิน</a>
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
                if (window.showNotification) { window.showNotification(message, type); return; }
                const toast = document.createElement('div');
                toast.style.cssText = 'position:fixed;top:16px;left:50%;transform:translateX(-50%);padding:12px 22px;border-radius:12px;color:#fff;z-index:130;box-shadow:0 8px 24px rgba(0,0,0,.2);' +
                    (type === 'success' ? 'background:#5aa07e' : type === 'error' ? 'background:#d9534f' : 'background:#5689b8');
                toast.textContent = message;
                document.body.appendChild(toast);
                setTimeout(() => { toast.style.opacity = '0'; toast.style.transition = 'opacity .3s'; setTimeout(() => toast.remove(), 300); }, 3000);
            }
        }
    }
</script>
@endpush
@endsection
