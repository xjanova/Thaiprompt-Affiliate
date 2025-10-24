/**
 * NFC Scanner for Chrome Android
 * Implements Web NFC API for scanning products, QR codes, and PromptPay
 */

class NFCScanner {
    constructor() {
        this.isSupported = 'NDEFReader' in window;
        this.reader = null;
        this.isScanning = false;
    }

    /**
     * Check if NFC is supported
     */
    checkSupport() {
        if (!this.isSupported) {
            console.error('Web NFC is not supported on this browser');
            return false;
        }

        if (!('https:' === location.protocol || 'localhost' === location.hostname)) {
            console.error('NFC requires HTTPS');
            return false;
        }

        return true;
    }

    /**
     * Request NFC permission
     */
    async requestPermission() {
        try {
            const permission = await navigator.permissions.query({ name: 'nfc' });
            return permission.state === 'granted' || permission.state === 'prompt';
        } catch (error) {
            console.error('Permission check failed:', error);
            return false;
        }
    }

    /**
     * Start scanning for NFC tags
     */
    async startScanning(onTagRead, onError) {
        if (!this.checkSupport()) {
            if (onError) onError(new Error('NFC not supported'));
            return false;
        }

        try {
            this.reader = new NDEFReader();
            await this.reader.scan();
            this.isScanning = true;

            console.log('NFC scan started...');

            this.reader.addEventListener('reading', ({ message, serialNumber }) => {
                console.log(`NFC tag detected: ${serialNumber}`);
                const tagData = this.parseNDEFMessage(message);

                if (onTagRead) {
                    onTagRead({
                        serialNumber,
                        data: tagData
                    });
                }
            });

            this.reader.addEventListener('readingerror', (error) => {
                console.error('NFC read error:', error);
                if (onError) onError(error);
            });

            return true;
        } catch (error) {
            console.error('Failed to start NFC scan:', error);
            if (onError) onError(error);
            return false;
        }
    }

    /**
     * Stop scanning
     */
    stopScanning() {
        if (this.reader) {
            this.isScanning = false;
            console.log('NFC scan stopped');
        }
    }

    /**
     * Parse NDEF message
     */
    parseNDEFMessage(message) {
        const records = [];

        for (const record of message.records) {
            const recordData = {
                recordType: record.recordType,
                mediaType: record.mediaType,
                data: null
            };

            try {
                const textDecoder = new TextDecoder(record.encoding || 'utf-8');

                if (record.recordType === 'text') {
                    recordData.data = textDecoder.decode(record.data);
                } else if (record.recordType === 'url') {
                    recordData.data = textDecoder.decode(record.data);
                } else if (record.recordType === 'mime') {
                    // Handle JSON data
                    if (record.mediaType === 'application/json') {
                        const jsonText = textDecoder.decode(record.data);
                        recordData.data = JSON.parse(jsonText);
                    } else {
                        recordData.data = record.data;
                    }
                } else {
                    recordData.data = record.data;
                }
            } catch (error) {
                console.error('Failed to parse record:', error);
                recordData.data = record.data;
            }

            records.push(recordData);
        }

        return records;
    }

    /**
     * Write data to NFC tag
     */
    async writeTag(data) {
        if (!this.checkSupport()) {
            throw new Error('NFC not supported');
        }

        try {
            const writer = new NDEFReader();
            const records = [];

            // Support different data types
            if (typeof data === 'string') {
                // URL or text
                if (data.startsWith('http://') || data.startsWith('https://')) {
                    records.push({ recordType: 'url', data });
                } else {
                    records.push({ recordType: 'text', data });
                }
            } else if (typeof data === 'object') {
                // JSON data
                records.push({
                    recordType: 'mime',
                    mediaType: 'application/json',
                    data: JSON.stringify(data)
                });
            }

            await writer.write({ records });
            console.log('Successfully wrote to NFC tag');
            return true;
        } catch (error) {
            console.error('Failed to write NFC tag:', error);
            throw error;
        }
    }

    /**
     * Scan product by NFC
     */
    async scanProduct(onProductFound, onError) {
        return this.startScanning((tag) => {
            const records = tag.data;
            let productData = null;

            for (const record of records) {
                if (record.recordType === 'url' && record.data.includes('/products/')) {
                    // Extract product slug from URL
                    const matches = record.data.match(/\/products\/([^\/\?]+)/);
                    if (matches) {
                        productData = { slug: matches[1], url: record.data };
                    }
                } else if (record.recordType === 'mime' && record.mediaType === 'application/json') {
                    // Product data in JSON format
                    productData = record.data;
                }
            }

            if (productData && onProductFound) {
                onProductFound(productData);
            } else if (onError) {
                onError(new Error('No product data found in NFC tag'));
            }
        }, onError);
    }

    /**
     * Scan PromptPay QR code via NFC
     */
    async scanPromptPay(onQRCodeFound, onError) {
        return this.startScanning((tag) => {
            const records = tag.data;
            let qrData = null;

            for (const record of records) {
                if (record.recordType === 'text' || record.recordType === 'url') {
                    // PromptPay QR code data
                    if (record.data.includes('promptpay') || record.data.length === 33) {
                        qrData = record.data;
                        break;
                    }
                }
            }

            if (qrData && onQRCodeFound) {
                onQRCodeFound(qrData);
            } else if (onError) {
                onError(new Error('No PromptPay data found in NFC tag'));
            }
        }, onError);
    }
}

// Export for use in Laravel Blade templates
window.NFCScanner = NFCScanner;

// Example usage functions
window.nfcExamples = {
    /**
     * Scan product example
     */
    scanProduct: async () => {
        const scanner = new NFCScanner();

        if (!scanner.checkSupport()) {
            alert('NFC ไม่รองรับบนอุปกรณ์นี้\nโปรดใช้ Chrome บน Android');
            return;
        }

        const hasPermission = await scanner.requestPermission();
        if (!hasPermission) {
            alert('กรุณาอนุญาตการใช้งาน NFC');
            return;
        }

        console.log('Ready to scan... Please tap your NFC tag');
        alert('พร้อมสแกน! กรุณาแตะแท็ก NFC ของคุณ');

        scanner.scanProduct(
            (product) => {
                console.log('Product found:', product);
                alert(`พบสินค้า: ${product.slug || product.name}`);

                // Redirect to product page
                if (product.slug) {
                    window.location.href = `/products/${product.slug}`;
                } else if (product.url) {
                    window.location.href = product.url;
                }
            },
            (error) => {
                console.error('Scan error:', error);
                alert(`เกิดข้อผิดพลาด: ${error.message}`);
            }
        );
    },

    /**
     * Scan PromptPay example
     */
    scanPromptPay: async () => {
        const scanner = new NFCScanner();

        if (!scanner.checkSupport()) {
            alert('NFC ไม่รองรับบนอุปกรณ์นี้');
            return;
        }

        alert('พร้อมสแกน PromptPay QR! กรุณาแตะแท็ก NFC');

        scanner.scanPromptPay(
            (qrData) => {
                console.log('PromptPay QR found:', qrData);
                alert(`พบ QR Code: ${qrData}`);
                // Process payment
            },
            (error) => {
                console.error('Scan error:', error);
                alert(`เกิดข้อผิดพลาด: ${error.message}`);
            }
        );
    },

    /**
     * Write product URL to NFC tag
     */
    writeProductTag: async (productSlug) => {
        const scanner = new NFCScanner();

        if (!scanner.checkSupport()) {
            alert('NFC ไม่รองรับบนอุปกรณ์นี้');
            return;
        }

        try {
            const productUrl = `${window.location.origin}/products/${productSlug}`;
            alert('พร้อมเขียนข้อมูล! กรุณาแตะแท็ก NFC');

            await scanner.writeTag(productUrl);
            alert('เขียนข้อมูลสำเร็จ!');
        } catch (error) {
            console.error('Write error:', error);
            alert(`เกิดข้อผิดพลาด: ${error.message}`);
        }
    }
};

export default NFCScanner;
