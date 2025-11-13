/**
 * QR Code & Barcode Generator
 * Alpine.js component for generating QR codes and barcodes
 */

function qrBarcodeGenerator() {
    return {
        // Type selection
        type: 'qrcode',

        // QR Code settings
        qrFormat: 'text',
        qrContent: 'https://thaiprompt.com',
        qrColor: '#000000',
        qrBgColor: '#ffffff',
        qrSize: 256,
        placeholder: 'ใส่ข้อความที่ต้องการ...',

        // QR Code format-specific data
        emailData: {
            email: '',
            subject: ''
        },
        phoneData: {
            number: '',
            message: ''
        },
        wifiData: {
            ssid: '',
            password: '',
            encryption: 'WPA'
        },
        vcardData: {
            name: '',
            phone: '',
            email: '',
            organization: ''
        },

        // Barcode settings
        barcodeFormat: 'CODE128',
        barcodeContent: '123456789',
        barcodeWidth: 2,
        barcodeHeight: 80,
        barcodeShowText: true,

        /**
         * Initialize the generator
         */
        init() {
            this.generateQR();
            this.generateBarcode();
        },

        /**
         * Update placeholder based on QR format
         */
        updatePlaceholder() {
            const placeholders = {
                text: 'ใส่ข้อความที่ต้องการ...',
                url: 'https://example.com',
                email: 'example@email.com',
                phone: '+66812345678',
                sms: '+66812345678',
                wifi: 'MyWiFiNetwork',
                vcard: 'ชื่อ นามสกุล'
            };
            this.placeholder = placeholders[this.qrFormat] || 'ใส่ข้อมูล...';
            this.generateQR();
        },

        /**
         * Get QR content based on format
         */
        getQRContent() {
            switch (this.qrFormat) {
                case 'text':
                case 'url':
                    return this.qrContent;

                case 'email':
                    const subject = this.emailData.subject ? `?subject=${encodeURIComponent(this.emailData.subject)}` : '';
                    return `mailto:${this.emailData.email}${subject}`;

                case 'phone':
                    return `tel:${this.phoneData.number}`;

                case 'sms':
                    const message = this.phoneData.message ? `?body=${encodeURIComponent(this.phoneData.message)}` : '';
                    return `sms:${this.phoneData.number}${message}`;

                case 'wifi':
                    // Format: WIFI:T:WPA;S:mynetwork;P:mypass;;
                    return `WIFI:T:${this.wifiData.encryption};S:${this.wifiData.ssid};P:${this.wifiData.password};;`;

                case 'vcard':
                    // vCard format
                    return `BEGIN:VCARD
VERSION:3.0
FN:${this.vcardData.name}
TEL:${this.vcardData.phone}
EMAIL:${this.vcardData.email}
ORG:${this.vcardData.organization}
END:VCARD`;

                default:
                    return this.qrContent;
            }
        },

        /**
         * Generate QR Code
         */
        generateQR() {
            const content = this.getQRContent();
            if (!content) return;

            const canvas = document.getElementById('qrCanvas');
            if (!canvas) return;

            try {
                QRCode.toCanvas(canvas, content, {
                    width: parseInt(this.qrSize),
                    margin: 2,
                    color: {
                        dark: this.qrColor,
                        light: this.qrBgColor
                    },
                    errorCorrectionLevel: 'M'
                }, (error) => {
                    if (error) {
                        console.error('QR Code generation error:', error);
                        this.showNotification('เกิดข้อผิดพลาดในการสร้าง QR Code', 'error');
                    }
                });
            } catch (error) {
                console.error('QR Code generation error:', error);
            }
        },

        /**
         * Download QR Code as PNG
         */
        downloadQR() {
            const canvas = document.getElementById('qrCanvas');
            if (!canvas) return;

            try {
                const link = document.createElement('a');
                link.download = `qrcode-${Date.now()}.png`;
                link.href = canvas.toDataURL('image/png');
                link.click();
                this.showNotification('ดาวน์โหลด QR Code สำเร็จ!', 'success');
            } catch (error) {
                console.error('Download error:', error);
                this.showNotification('เกิดข้อผิดพลาดในการดาวน์โหลด', 'error');
            }
        },

        /**
         * Download QR Code as SVG
         */
        downloadQRSVG() {
            const content = this.getQRContent();
            if (!content) return;

            try {
                QRCode.toString(content, {
                    type: 'svg',
                    width: parseInt(this.qrSize),
                    margin: 2,
                    color: {
                        dark: this.qrColor,
                        light: this.qrBgColor
                    }
                }, (error, svg) => {
                    if (error) {
                        console.error('SVG generation error:', error);
                        this.showNotification('เกิดข้อผิดพลาดในการสร้าง SVG', 'error');
                        return;
                    }

                    const blob = new Blob([svg], { type: 'image/svg+xml' });
                    const url = URL.createObjectURL(blob);
                    const link = document.createElement('a');
                    link.download = `qrcode-${Date.now()}.svg`;
                    link.href = url;
                    link.click();
                    URL.revokeObjectURL(url);
                    this.showNotification('ดาวน์โหลด SVG สำเร็จ!', 'success');
                });
            } catch (error) {
                console.error('SVG generation error:', error);
                this.showNotification('เกิดข้อผิดพลาดในการสร้าง SVG', 'error');
            }
        },

        /**
         * Copy QR Code to clipboard
         */
        async copyQRToClipboard() {
            const canvas = document.getElementById('qrCanvas');
            if (!canvas) return;

            try {
                canvas.toBlob(async (blob) => {
                    try {
                        await navigator.clipboard.write([
                            new ClipboardItem({ 'image/png': blob })
                        ]);
                        this.showNotification('คัดลอก QR Code สำเร็จ!', 'success');
                    } catch (error) {
                        console.error('Clipboard error:', error);
                        this.showNotification('เบราว์เซอร์ของคุณไม่รองรับการคัดลอกรูปภาพ', 'error');
                    }
                });
            } catch (error) {
                console.error('Clipboard error:', error);
                this.showNotification('เกิดข้อผิดพลาดในการคัดลอก', 'error');
            }
        },

        /**
         * Get barcode placeholder based on format
         */
        getBarcodePlaceholder() {
            const placeholders = {
                CODE128: 'ข้อความหรือตัวเลข',
                EAN13: '13 หลัก (เช่น 1234567890128)',
                EAN8: '8 หลัก (เช่น 12345670)',
                UPC: '12 หลัก (เช่น 123456789012)',
                CODE39: 'ตัวอักษรและตัวเลข',
                ITF14: '14 หลัก',
                MSI: 'ตัวเลข',
                pharmacode: 'ตัวเลข 3-131070'
            };
            return placeholders[this.barcodeFormat] || 'ใส่ข้อมูล...';
        },

        /**
         * Get barcode help text
         */
        getBarcodeHelp() {
            const help = {
                CODE128: 'รองรับตัวอักษร ตัวเลข และสัญลักษณ์พิเศษ',
                EAN13: 'ใช้สำหรับรหัสสินค้า 13 หลัก (หลักสุดท้ายคือ check digit)',
                EAN8: 'ใช้สำหรับรหัสสินค้า 8 หลัก',
                UPC: 'ใช้สำหรับรหัสสินค้า 12 หลัก',
                CODE39: 'รองรับตัวอักษร A-Z, ตัวเลข 0-9 และ - . $ / + % space',
                ITF14: 'ใช้สำหรับกล่องสินค้า 14 หลัก',
                MSI: 'รองรับเฉพาะตัวเลข',
                pharmacode: 'ใช้สำหรับยา รองรับตัวเลข 3-131070'
            };
            return help[this.barcodeFormat] || '';
        },

        /**
         * Generate Barcode
         */
        generateBarcode() {
            if (!this.barcodeContent) return;

            const svg = document.getElementById('barcodeSVG');
            if (!svg) return;

            try {
                JsBarcode(svg, this.barcodeContent, {
                    format: this.barcodeFormat,
                    width: parseFloat(this.barcodeWidth),
                    height: parseInt(this.barcodeHeight),
                    displayValue: this.barcodeShowText,
                    fontSize: 14,
                    margin: 10,
                    background: '#ffffff',
                    lineColor: '#000000'
                });
            } catch (error) {
                console.error('Barcode generation error:', error);
                this.showNotification('ข้อมูลไม่ถูกต้องสำหรับรูปแบบ Barcode นี้', 'error');
            }
        },

        /**
         * Download Barcode as PNG
         */
        downloadBarcode() {
            const svg = document.getElementById('barcodeSVG');
            if (!svg) return;

            try {
                const svgData = new XMLSerializer().serializeToString(svg);
                const canvas = document.createElement('canvas');
                const ctx = canvas.getContext('2d');
                const img = new Image();

                img.onload = () => {
                    canvas.width = img.width;
                    canvas.height = img.height;
                    ctx.fillStyle = '#ffffff';
                    ctx.fillRect(0, 0, canvas.width, canvas.height);
                    ctx.drawImage(img, 0, 0);

                    const link = document.createElement('a');
                    link.download = `barcode-${Date.now()}.png`;
                    link.href = canvas.toDataURL('image/png');
                    link.click();
                    this.showNotification('ดาวน์โหลด Barcode สำเร็จ!', 'success');
                };

                img.src = 'data:image/svg+xml;base64,' + btoa(unescape(encodeURIComponent(svgData)));
            } catch (error) {
                console.error('Download error:', error);
                this.showNotification('เกิดข้อผิดพลาดในการดาวน์โหลด', 'error');
            }
        },

        /**
         * Download Barcode as SVG
         */
        downloadBarcodeSVG() {
            const svg = document.getElementById('barcodeSVG');
            if (!svg) return;

            try {
                const svgData = new XMLSerializer().serializeToString(svg);
                const blob = new Blob([svgData], { type: 'image/svg+xml' });
                const url = URL.createObjectURL(blob);
                const link = document.createElement('a');
                link.download = `barcode-${Date.now()}.svg`;
                link.href = url;
                link.click();
                URL.revokeObjectURL(url);
                this.showNotification('ดาวน์โหลด SVG สำเร็จ!', 'success');
            } catch (error) {
                console.error('Download error:', error);
                this.showNotification('เกิดข้อผิดพลาดในการดาวน์โหลด', 'error');
            }
        },

        /**
         * Show notification
         */
        showNotification(message, type = 'success') {
            // Create notification element
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

            // Animate in
            setTimeout(() => {
                notification.style.opacity = '1';
                notification.style.transform = 'translateX(0)';
            }, 10);

            // Remove after 3 seconds
            setTimeout(() => {
                notification.style.opacity = '0';
                notification.style.transform = 'translateX(100%)';
                setTimeout(() => {
                    document.body.removeChild(notification);
                }, 300);
            }, 3000);
        }
    };
}

// Make the function available globally for Alpine.js
window.qrBarcodeGenerator = qrBarcodeGenerator;
