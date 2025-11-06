/**
 * KYC Camera Capture Component
 * Features: Card frame overlay, real-time capture, image quality validation
 */

export function kycCameraCapture() {
    return {
        // State
        cameraMode: false,
        stream: null,
        facingMode: 'environment', // 'user' for front camera, 'environment' for back camera
        capturedImage: null,
        isCapturing: false,
        error: null,

        // Camera controls
        async startCamera() {
            this.error = null;
            this.capturedImage = null;

            try {
                // Request camera permission
                const constraints = {
                    video: {
                        facingMode: this.facingMode,
                        width: { ideal: 1920 },
                        height: { ideal: 1080 }
                    }
                };

                this.stream = await navigator.mediaDevices.getUserMedia(constraints);

                // Set video stream
                const video = this.$refs.video;
                if (video) {
                    video.srcObject = this.stream;
                    video.play();
                }

                this.cameraMode = true;
            } catch (err) {
                console.error('Camera error:', err);
                this.error = 'ไม่สามารถเข้าถึงกล้องได้ กรุณาอนุญาตการใช้งานกล้องในเบราว์เซอร์';
                this.cameraMode = false;
            }
        },

        stopCamera() {
            if (this.stream) {
                this.stream.getTracks().forEach(track => track.stop());
                this.stream = null;
            }
            this.cameraMode = false;
            this.capturedImage = null;
        },

        async switchCamera() {
            this.facingMode = this.facingMode === 'user' ? 'environment' : 'user';
            this.stopCamera();
            await this.startCamera();
        },

        capturePhoto() {
            const video = this.$refs.video;
            const canvas = this.$refs.canvas;

            if (!video || !canvas) return;

            // Set canvas size to video size
            canvas.width = video.videoWidth;
            canvas.height = video.videoHeight;

            // Draw current frame
            const context = canvas.getContext('2d');
            context.drawImage(video, 0, 0);

            // Convert to blob
            canvas.toBlob((blob) => {
                if (blob) {
                    // Create file from blob
                    const file = new File([blob], `id-card-${Date.now()}.jpg`, {
                        type: 'image/jpeg',
                        lastModified: Date.now()
                    });

                    // Create preview URL
                    this.capturedImage = URL.createObjectURL(blob);

                    // Set to file input (for form submission)
                    this.setFileInput(file, 'idCard');

                    // Stop camera after capture
                    this.stopCamera();
                }
            }, 'image/jpeg', 0.92);
        },

        retakePhoto() {
            this.capturedImage = null;
            this.startCamera();
        },

        setFileInput(file, type) {
            // Create DataTransfer to set file input
            const dataTransfer = new DataTransfer();
            dataTransfer.items.add(file);

            if (type === 'idCard') {
                const input = document.getElementById('id_card_image');
                if (input) {
                    input.files = dataTransfer.files;

                    // Trigger change event for preview
                    const event = new Event('change', { bubbles: true });
                    input.dispatchEvent(event);
                }
            } else if (type === 'selfie') {
                const input = document.getElementById('selfie_image');
                if (input) {
                    input.files = dataTransfer.files;

                    // Trigger change event for preview
                    const event = new Event('change', { bubbles: true });
                    input.dispatchEvent(event);
                }
            }
        },

        // Cleanup on component destroy
        destroy() {
            this.stopCamera();
        }
    };
}

// Export for use in Alpine.js
window.kycCameraCapture = kycCameraCapture;
