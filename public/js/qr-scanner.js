/**
 * QR Code Scanner
 * Using ZXing library for QR/Barcode scanning
 */

function qrScanner() {
    return {
        scanMode: 'camera',
        cameraActive: false,
        isLoading: false,
        isDragging: false,
        scannedResult: null,
        resultType: 'text',
        uploadPreview: null,
        cameras: [],
        currentCameraIndex: 0,
        stream: null,
        codeReader: null,

        /**
         * Initialize
         */
        init() {
            this.codeReader = new ZXing.BrowserMultiFormatReader();
            this.detectCameras();
        },

        /**
         * Detect available cameras
         */
        async detectCameras() {
            try {
                const devices = await navigator.mediaDevices.enumerateDevices();
                this.cameras = devices.filter(device => device.kind === 'videoinput');
                console.log('Detected cameras:', this.cameras.length);
            } catch (error) {
                console.error('Error detecting cameras:', error);
            }
        },

        /**
         * Start camera
         */
        async startCamera() {
            this.isLoading = true;
            this.scannedResult = null;

            try {
                const deviceId = this.cameras[this.currentCameraIndex]?.deviceId;
                const video = document.getElementById('cameraVideo');
                const canvas = document.getElementById('scannerCanvas');

                // Start video stream
                const constraints = {
                    video: deviceId ? { deviceId: { exact: deviceId } } : { facingMode: 'environment' }
                };

                this.stream = await navigator.mediaDevices.getUserMedia(constraints);
                video.srcObject = this.stream;

                await video.play();
                this.cameraActive = true;
                this.isLoading = false;

                // Start scanning loop
                this.scanFromCamera(video, canvas);

            } catch (error) {
                console.error('Error starting camera:', error);
                this.isLoading = false;
                this.showNotification('ไม่สามารถเปิดกล้องได้ กรุณาตรวจสอบสิทธิ์การใช้งาน', 'error');
            }
        },

        /**
         * Stop camera
         */
        stopCamera() {
            if (this.stream) {
                this.stream.getTracks().forEach(track => track.stop());
                this.stream = null;
            }
            this.cameraActive = false;

            const video = document.getElementById('cameraVideo');
            if (video) {
                video.srcObject = null;
            }
        },

        /**
         * Switch camera
         */
        async switchCamera() {
            this.stopCamera();
            this.currentCameraIndex = (this.currentCameraIndex + 1) % this.cameras.length;
            await this.startCamera();
        },

        /**
         * Scan from camera feed
         */
        async scanFromCamera(video, canvas) {
            if (!this.cameraActive) return;

            try {
                const result = await this.codeReader.decodeFromVideoElement(video);

                if (result) {
                    this.handleScanResult(result.text);
                    this.stopCamera();
                    return;
                }
            } catch (error) {
                // No QR code found, continue scanning
                if (error.name !== 'NotFoundException') {
                    console.error('Scan error:', error);
                }
            }

            // Continue scanning
            if (this.cameraActive) {
                requestAnimationFrame(() => this.scanFromCamera(video, canvas));
            }
        },

        /**
         * Handle file select
         */
        async handleFileSelect(event) {
            const file = event.target.files[0];
            if (!file) return;

            this.processImageFile(file);
        },

        /**
         * Handle file drop
         */
        async handleDrop(event) {
            this.isDragging = false;
            const file = event.dataTransfer.files[0];

            if (!file || !file.type.startsWith('image/')) {
                this.showNotification('กรุณาเลือกไฟล์รูปภาพ', 'error');
                return;
            }

            this.processImageFile(file);
        },

        /**
         * Process image file
         */
        async processImageFile(file) {
            try {
                // Show preview
                const reader = new FileReader();
                reader.onload = (e) => {
                    this.uploadPreview = e.target.result;
                };
                reader.readAsDataURL(file);

                // Scan QR code from image
                const result = await this.codeReader.decodeFromImageUrl(URL.createObjectURL(file));

                if (result) {
                    this.handleScanResult(result.text);
                } else {
                    this.showNotification('ไม่พบ QR Code หรือ Barcode ในรูปภาพ', 'error');
                }
            } catch (error) {
                console.error('Error scanning image:', error);
                this.showNotification('ไม่สามารถสแกนรูปภาพได้', 'error');
            }
        },

        /**
         * Clear upload
         */
        clearUpload() {
            this.uploadPreview = null;
            this.scannedResult = null;
            const fileInput = this.$refs.fileInput;
            if (fileInput) {
                fileInput.value = '';
            }
        },

        /**
         * Handle scan result
         */
        handleScanResult(content) {
            this.scannedResult = content;
            this.resultType = this.detectContentType(content);
            this.showNotification('สแกนสำเร็จ!', 'success');

            // Send to server for logging (optional)
            this.sendDecodeRequest(content);
        },

        /**
         * Detect content type
         */
        detectContentType(content) {
            if (!content) return 'text';

            if (content.match(/^https?:\/\//i)) {
                return 'url';
            } else if (content.match(/^mailto:/i)) {
                return 'email';
            } else if (content.match(/^tel:/i)) {
                return 'phone';
            } else if (content.match(/^WIFI:/i)) {
                return 'wifi';
            } else if (content.match(/^BEGIN:VCARD/i)) {
                return 'vcard';
            } else if (content.match(/^sms:/i)) {
                return 'sms';
            }

            return 'text';
        },

        /**
         * Send decode request to server
         */
        async sendDecodeRequest(content) {
            try {
                await fetch('/qr-barcode/decode', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({ content })
                });
            } catch (error) {
                console.error('Error sending decode request:', error);
            }
        },

        /**
         * Copy to clipboard
         */
        async copyToClipboard(text) {
            try {
                await navigator.clipboard.writeText(text);
                this.showNotification('คัดลอกสำเร็จ!', 'success');
            } catch (error) {
                console.error('Copy error:', error);
                this.showNotification('ไม่สามารถคัดลอกได้', 'error');
            }
        },

        /**
         * Open link
         */
        openLink(url) {
            if (this.resultType === 'url') {
                window.open(url, '_blank');
            }
        },

        /**
         * Reset scanner
         */
        resetScanner() {
            this.scannedResult = null;
            this.uploadPreview = null;
            this.clearUpload();

            if (this.scanMode === 'camera') {
                this.startCamera();
            }
        },

        /**
         * Show notification
         */
        showNotification(message, type = 'success') {
            const notification = document.createElement('div');
            notification.className = `fixed top-4 right-4 px-6 py-4 rounded-xl shadow-2xl z-50 transform transition-all duration-300 ${
                type === 'success'
                    ? 'bg-gradient-to-r from-green-500 to-emerald-500'
                    : 'bg-gradient-to-r from-red-500 to-pink-500'
            } text-white font-semibold`;
            notification.innerHTML = `
                <div class="flex items-center space-x-3">
                    <i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-circle'} text-xl"></i>
                    <span>${message}</span>
                </div>
            `;

            document.body.appendChild(notification);

            setTimeout(() => {
                notification.style.opacity = '1';
                notification.style.transform = 'translateX(0)';
            }, 10);

            setTimeout(() => {
                notification.style.opacity = '0';
                notification.style.transform = 'translateX(100%)';
                setTimeout(() => {
                    document.body.removeChild(notification);
                }, 300);
            }, 3000);
        },

        /**
         * Cleanup on destroy
         */
        destroy() {
            this.stopCamera();
            if (this.codeReader) {
                this.codeReader.reset();
            }
        }
    };
}

// Make available globally
window.qrScanner = qrScanner;
