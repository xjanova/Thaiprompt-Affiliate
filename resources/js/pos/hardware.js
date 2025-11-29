/**
 * TP-POS Hardware Integration
 *
 * จัดการการเชื่อมต่อกับอุปกรณ์ต่างๆ
 * - Receipt Printer (ESC/POS)
 * - Barcode Scanner
 * - Cash Drawer
 * - Customer Display
 * - Scale
 *
 * @version 1.0.0
 */

// ============================================
// RECEIPT PRINTER (ESC/POS)
// ============================================

/**
 * ESC/POS Commands
 */
const ESC = '\x1B';
const GS = '\x1D';
const ESCPOS = {
    // Initialize printer
    INIT: ESC + '@',

    // Text formatting
    ALIGN_LEFT: ESC + 'a' + '\x00',
    ALIGN_CENTER: ESC + 'a' + '\x01',
    ALIGN_RIGHT: ESC + 'a' + '\x02',

    BOLD_ON: ESC + 'E' + '\x01',
    BOLD_OFF: ESC + 'E' + '\x00',

    DOUBLE_HEIGHT_ON: ESC + '!' + '\x10',
    DOUBLE_WIDTH_ON: ESC + '!' + '\x20',
    DOUBLE_ON: ESC + '!' + '\x30',
    NORMAL: ESC + '!' + '\x00',

    UNDERLINE_ON: ESC + '-' + '\x01',
    UNDERLINE_OFF: ESC + '-' + '\x00',

    // Font size
    FONT_A: ESC + 'M' + '\x00',
    FONT_B: ESC + 'M' + '\x01',

    // Line spacing
    LINE_SPACING_DEFAULT: ESC + '2',
    LINE_SPACING: (n) => ESC + '3' + String.fromCharCode(n),

    // Paper
    CUT_PARTIAL: GS + 'V' + '\x01',
    CUT_FULL: GS + 'V' + '\x00',
    FEED_LINES: (n) => ESC + 'd' + String.fromCharCode(n),

    // Cash drawer
    OPEN_DRAWER: ESC + 'p' + '\x00' + '\x19' + '\xFA',

    // Barcode
    BARCODE_HEIGHT: (h) => GS + 'h' + String.fromCharCode(h),
    BARCODE_WIDTH: (w) => GS + 'w' + String.fromCharCode(w),
    BARCODE_TEXT_BELOW: GS + 'H' + '\x02',
    BARCODE_CODE128: (data) => GS + 'k' + '\x49' + String.fromCharCode(data.length) + data,

    // QR Code
    QR_SIZE: (n) => GS + '(k' + '\x03\x00' + '\x31' + '\x43' + String.fromCharCode(n),
    QR_ERROR: GS + '(k' + '\x03\x00' + '\x31' + '\x45' + '\x31',
    QR_STORE: (data) => GS + '(k' + String.fromCharCode((data.length + 3) % 256) +
                        String.fromCharCode(Math.floor((data.length + 3) / 256)) +
                        '\x31' + '\x50' + '\x30' + data,
    QR_PRINT: GS + '(k' + '\x03\x00' + '\x31' + '\x51' + '\x30'
};

/**
 * Printer Class
 */
export class ReceiptPrinter {
    constructor(options = {}) {
        this.type = options.type || 'web'; // 'web', 'usb', 'bluetooth', 'network'
        this.width = options.width || 48; // characters per line
        this.encoding = options.encoding || 'TIS-620'; // Thai encoding
        this.device = null;
        this.isConnected = false;
    }

    /**
     * เชื่อมต่อเครื่องพิมพ์
     */
    async connect() {
        switch (this.type) {
            case 'usb':
                return this.connectUSB();
            case 'bluetooth':
                return this.connectBluetooth();
            case 'network':
                return this.connectNetwork();
            default:
                return this.connectWeb();
        }
    }

    /**
     * เชื่อมต่อผ่าน WebUSB
     */
    async connectUSB() {
        if (!('usb' in navigator)) {
            throw new Error('WebUSB not supported');
        }

        try {
            this.device = await navigator.usb.requestDevice({
                filters: [
                    { vendorId: 0x04b8 }, // Epson
                    { vendorId: 0x0519 }, // Star Micronics
                    { vendorId: 0x0483 }, // XPrinter
                    { vendorId: 0x0fe6 }, // ICS/Contex
                ]
            });

            await this.device.open();
            await this.device.selectConfiguration(1);
            await this.device.claimInterface(0);

            this.isConnected = true;
            console.log('[Printer] USB connected:', this.device.productName);

            return true;

        } catch (error) {
            console.error('[Printer] USB connection failed:', error);
            throw error;
        }
    }

    /**
     * เชื่อมต่อผ่าน Web Bluetooth
     */
    async connectBluetooth() {
        if (!('bluetooth' in navigator)) {
            throw new Error('Web Bluetooth not supported');
        }

        try {
            this.device = await navigator.bluetooth.requestDevice({
                filters: [
                    { services: ['000018f0-0000-1000-8000-00805f9b34fb'] }, // Serial
                ],
                optionalServices: ['battery_service']
            });

            const server = await this.device.gatt.connect();
            const service = await server.getPrimaryService('000018f0-0000-1000-8000-00805f9b34fb');
            this.characteristic = await service.getCharacteristic('00002af1-0000-1000-8000-00805f9b34fb');

            this.isConnected = true;
            console.log('[Printer] Bluetooth connected:', this.device.name);

            return true;

        } catch (error) {
            console.error('[Printer] Bluetooth connection failed:', error);
            throw error;
        }
    }

    /**
     * เชื่อมต่อผ่าน Web Print (fallback)
     */
    async connectWeb() {
        this.isConnected = true;
        console.log('[Printer] Using Web Print API');
        return true;
    }

    /**
     * เชื่อมต่อผ่าน Network (WebSocket proxy)
     */
    async connectNetwork() {
        // ต้องมี print server ที่รัน WebSocket
        // ยังไม่ implement ในตอนนี้
        this.isConnected = true;
        return true;
    }

    /**
     * ส่งข้อมูลไปเครื่องพิมพ์
     */
    async write(data) {
        if (!this.isConnected) {
            throw new Error('Printer not connected');
        }

        const encoder = new TextEncoder();
        const bytes = encoder.encode(data);

        switch (this.type) {
            case 'usb':
                await this.device.transferOut(1, bytes);
                break;

            case 'bluetooth':
                // แบ่งส่งทีละ chunk
                const chunkSize = 20;
                for (let i = 0; i < bytes.length; i += chunkSize) {
                    const chunk = bytes.slice(i, i + chunkSize);
                    await this.characteristic.writeValue(chunk);
                }
                break;

            default:
                // Web print - เก็บไว้แล้วพิมพ์ทีเดียว
                this.printBuffer = (this.printBuffer || '') + data;
                break;
        }
    }

    /**
     * พิมพ์ใบเสร็จ
     */
    async printReceipt(receipt) {
        await this.write(ESCPOS.INIT);

        // Header - ชื่อร้าน
        await this.write(ESCPOS.ALIGN_CENTER);
        await this.write(ESCPOS.DOUBLE_ON);
        await this.write(receipt.storeName + '\n');
        await this.write(ESCPOS.NORMAL);

        // ที่อยู่ร้าน
        if (receipt.storeAddress) {
            await this.write(receipt.storeAddress + '\n');
        }
        if (receipt.storePhone) {
            await this.write('โทร: ' + receipt.storePhone + '\n');
        }

        // เส้นคั่น
        await this.write(this.line('-'));

        // รายละเอียดรายการ
        await this.write(ESCPOS.ALIGN_LEFT);
        await this.write('เลขที่: ' + receipt.transactionNumber + '\n');
        await this.write('วันที่: ' + receipt.date + '\n');
        if (receipt.cashier) {
            await this.write('พนักงาน: ' + receipt.cashier + '\n');
        }
        if (receipt.customerName) {
            await this.write('ลูกค้า: ' + receipt.customerName + '\n');
        }

        await this.write(this.line('-'));

        // รายการสินค้า
        for (const item of receipt.items) {
            const name = this.truncate(item.name, 24);
            const qty = String(item.quantity).padStart(3);
            const price = this.formatMoney(item.price).padStart(10);
            const total = this.formatMoney(item.quantity * item.price).padStart(10);

            await this.write(name + '\n');
            await this.write(`  ${qty} x ${price} = ${total}\n`);

            if (item.discount > 0) {
                await this.write(`     ส่วนลด: -${this.formatMoney(item.discount)}\n`);
            }
        }

        await this.write(this.line('-'));

        // สรุปยอด
        await this.write(this.formatRow('ยอดรวม', receipt.subtotal));

        if (receipt.discount > 0) {
            await this.write(this.formatRow('ส่วนลด', -receipt.discount));
        }

        if (receipt.tax > 0) {
            await this.write(this.formatRow('ภาษี ' + receipt.taxRate + '%', receipt.tax));
        }

        if (receipt.serviceCharge > 0) {
            await this.write(this.formatRow('ค่าบริการ', receipt.serviceCharge));
        }

        await this.write(this.line('='));

        // ยอดสุทธิ
        await this.write(ESCPOS.BOLD_ON);
        await this.write(ESCPOS.DOUBLE_ON);
        await this.write(this.formatRow('รวมสุทธิ', receipt.total));
        await this.write(ESCPOS.NORMAL);

        await this.write(this.line('-'));

        // การชำระเงิน
        await this.write(this.formatRow('ชำระโดย', receipt.paymentMethod));

        if (receipt.paymentMethod === 'เงินสด') {
            await this.write(this.formatRow('รับเงิน', receipt.cashReceived));
            await this.write(this.formatRow('เงินทอน', receipt.change));
        }

        await this.write(this.line('-'));

        // Footer
        await this.write(ESCPOS.ALIGN_CENTER);
        await this.write('\n' + (receipt.footer || 'ขอบคุณที่ใช้บริการ') + '\n');

        // QR Code (ถ้ามี)
        if (receipt.qrCode) {
            await this.printQRCode(receipt.qrCode);
        }

        // ตัดกระดาษ
        await this.write(ESCPOS.FEED_LINES(4));
        await this.write(ESCPOS.CUT_PARTIAL);

        // Execute print (for web print)
        if (this.type === 'web') {
            await this.executePrint();
        }
    }

    /**
     * พิมพ์ QR Code
     */
    async printQRCode(data, size = 6) {
        await this.write(ESCPOS.ALIGN_CENTER);
        await this.write(ESCPOS.QR_SIZE(size));
        await this.write(ESCPOS.QR_ERROR);
        await this.write(ESCPOS.QR_STORE(data));
        await this.write(ESCPOS.QR_PRINT);
        await this.write('\n');
    }

    /**
     * พิมพ์ Barcode
     */
    async printBarcode(data, height = 80) {
        await this.write(ESCPOS.ALIGN_CENTER);
        await this.write(ESCPOS.BARCODE_HEIGHT(height));
        await this.write(ESCPOS.BARCODE_WIDTH(2));
        await this.write(ESCPOS.BARCODE_TEXT_BELOW);
        await this.write(ESCPOS.BARCODE_CODE128(data));
        await this.write('\n');
    }

    /**
     * เปิดลิ้นชักเก็บเงิน
     */
    async openCashDrawer() {
        await this.write(ESCPOS.OPEN_DRAWER);
    }

    /**
     * Execute Web Print
     */
    async executePrint() {
        if (!this.printBuffer) return;

        // สร้าง iframe สำหรับพิมพ์
        const iframe = document.createElement('iframe');
        iframe.style.display = 'none';
        document.body.appendChild(iframe);

        const doc = iframe.contentDocument || iframe.contentWindow.document;
        doc.write(`
            <html>
            <head>
                <style>
                    @media print {
                        @page { margin: 0; size: 80mm auto; }
                        body {
                            font-family: 'Courier New', monospace;
                            font-size: 12px;
                            width: 80mm;
                            margin: 0;
                            padding: 5mm;
                        }
                        pre { margin: 0; white-space: pre-wrap; }
                    }
                </style>
            </head>
            <body>
                <pre>${this.escapeHtml(this.printBuffer)}</pre>
            </body>
            </html>
        `);
        doc.close();

        // รอให้โหลดเสร็จแล้วพิมพ์
        iframe.contentWindow.focus();
        iframe.contentWindow.print();

        // ลบ iframe หลังพิมพ์
        setTimeout(() => {
            document.body.removeChild(iframe);
        }, 1000);

        this.printBuffer = '';
    }

    // ============================================
    // HELPER FUNCTIONS
    // ============================================

    line(char = '-') {
        return char.repeat(this.width) + '\n';
    }

    truncate(text, maxLength) {
        if (!text) return '';
        return text.length > maxLength ? text.substring(0, maxLength - 2) + '..' : text;
    }

    formatMoney(amount) {
        return new Intl.NumberFormat('th-TH', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        }).format(amount);
    }

    formatRow(label, value) {
        const money = typeof value === 'number' ? this.formatMoney(value) : value;
        const spaces = this.width - label.length - money.length;
        return label + ' '.repeat(Math.max(1, spaces)) + money + '\n';
    }

    escapeHtml(text) {
        return text
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;');
    }

    /**
     * ตัดการเชื่อมต่อ
     */
    async disconnect() {
        if (this.device) {
            switch (this.type) {
                case 'usb':
                    await this.device.close();
                    break;
                case 'bluetooth':
                    await this.device.gatt.disconnect();
                    break;
            }
        }

        this.device = null;
        this.isConnected = false;
    }
}

// ============================================
// BARCODE SCANNER
// ============================================

/**
 * Barcode Scanner Class
 */
export class BarcodeScanner {
    constructor(options = {}) {
        this.onScan = options.onScan || (() => {});
        this.buffer = '';
        this.timeout = null;
        this.minLength = options.minLength || 3;
        this.maxDelay = options.maxDelay || 50; // ms between keystrokes
        this.enabled = true;
    }

    /**
     * เริ่ม listener สำหรับ keyboard barcode scanner
     */
    start() {
        this.keyHandler = (e) => this.handleKeypress(e);
        document.addEventListener('keydown', this.keyHandler);
        console.log('[Scanner] Keyboard scanner started');
    }

    /**
     * หยุด listener
     */
    stop() {
        if (this.keyHandler) {
            document.removeEventListener('keydown', this.keyHandler);
        }
        console.log('[Scanner] Keyboard scanner stopped');
    }

    /**
     * จัดการ keypress
     */
    handleKeypress(e) {
        if (!this.enabled) return;

        // ข้าม modifier keys
        if (e.ctrlKey || e.altKey || e.metaKey) return;

        clearTimeout(this.timeout);

        if (e.key === 'Enter') {
            if (this.buffer.length >= this.minLength) {
                this.onScan(this.buffer);
            }
            this.buffer = '';
            return;
        }

        // เฉพาะ printable characters
        if (e.key.length === 1) {
            this.buffer += e.key;
        }

        // Reset หลังจากไม่มี input
        this.timeout = setTimeout(() => {
            this.buffer = '';
        }, this.maxDelay);
    }

    /**
     * เปิด/ปิด scanner
     */
    toggle(enabled) {
        this.enabled = enabled;
    }
}

/**
 * Camera Barcode Scanner
 */
export class CameraBarcodeScanner {
    constructor(options = {}) {
        this.onScan = options.onScan || (() => {});
        this.videoElement = options.videoElement;
        this.stream = null;
        this.scanning = false;
    }

    /**
     * เริ่มสแกนด้วยกล้อง
     */
    async start() {
        if (!('mediaDevices' in navigator)) {
            throw new Error('Camera not supported');
        }

        try {
            this.stream = await navigator.mediaDevices.getUserMedia({
                video: {
                    facingMode: 'environment',
                    width: { ideal: 1280 },
                    height: { ideal: 720 }
                }
            });

            if (this.videoElement) {
                this.videoElement.srcObject = this.stream;
                await this.videoElement.play();
            }

            this.scanning = true;
            this.startDecoding();

            console.log('[Scanner] Camera started');

        } catch (error) {
            console.error('[Scanner] Camera error:', error);
            throw error;
        }
    }

    /**
     * หยุดสแกน
     */
    stop() {
        this.scanning = false;

        if (this.stream) {
            this.stream.getTracks().forEach(track => track.stop());
            this.stream = null;
        }

        if (this.videoElement) {
            this.videoElement.srcObject = null;
        }

        console.log('[Scanner] Camera stopped');
    }

    /**
     * เริ่ม decode frames
     */
    startDecoding() {
        if (!this.scanning) return;

        // ใช้ ZXing library (ต้อง include ใน page)
        if (typeof ZXing !== 'undefined' && this.videoElement) {
            const codeReader = new ZXing.BrowserMultiFormatReader();

            codeReader.decodeFromVideoElement(this.videoElement, (result, error) => {
                if (result) {
                    this.onScan(result.getText());

                    // หยุดชั่วคราวเพื่อไม่ให้ scan ซ้ำ
                    this.scanning = false;
                    setTimeout(() => {
                        this.scanning = true;
                    }, 2000);
                }
            });
        }
    }
}

// ============================================
// CASH DRAWER
// ============================================

/**
 * Cash Drawer Class
 */
export class CashDrawer {
    constructor(printer) {
        this.printer = printer;
    }

    async open() {
        if (this.printer && this.printer.isConnected) {
            await this.printer.openCashDrawer();
            console.log('[CashDrawer] Opened');
        } else {
            console.warn('[CashDrawer] No printer connected');
        }
    }
}

// ============================================
// CUSTOMER DISPLAY
// ============================================

/**
 * Customer Display Class
 */
export class CustomerDisplay {
    constructor(options = {}) {
        this.type = options.type || 'web'; // 'web', 'serial', 'usb'
        this.device = null;
        this.isConnected = false;
        this.width = options.width || 20;
        this.lines = options.lines || 2;
    }

    async connect() {
        if (this.type === 'web') {
            // ใช้ popup window หรือ second screen
            this.displayWindow = window.open(
                '/pos/customer-display',
                'customer-display',
                'width=800,height=600'
            );
            this.isConnected = true;
            return true;
        }

        // USB VFD display
        if (this.type === 'usb' && 'usb' in navigator) {
            try {
                this.device = await navigator.usb.requestDevice({
                    filters: []
                });
                await this.device.open();
                this.isConnected = true;
                return true;
            } catch (error) {
                console.error('[Display] USB connection failed:', error);
                throw error;
            }
        }

        return false;
    }

    async showText(line1, line2 = '') {
        if (this.type === 'web' && this.displayWindow) {
            this.displayWindow.postMessage({
                type: 'text',
                line1: line1,
                line2: line2
            }, '*');
        }

        if (this.type === 'usb' && this.device) {
            // ส่ง ESC/POS commands ไป VFD
            const encoder = new TextEncoder();
            const text = line1.padEnd(this.width) + line2.padEnd(this.width);
            await this.device.transferOut(1, encoder.encode('\x0C' + text));
        }
    }

    async showTotal(items, total) {
        if (this.type === 'web' && this.displayWindow) {
            this.displayWindow.postMessage({
                type: 'cart',
                items: items,
                total: total
            }, '*');
        } else {
            const totalText = '฿' + total.toFixed(2);
            await this.showText('รวม', totalText.padStart(this.width - 4));
        }
    }

    async showThankYou() {
        if (this.type === 'web' && this.displayWindow) {
            this.displayWindow.postMessage({
                type: 'thankYou'
            }, '*');
        } else {
            await this.showText('ขอบคุณที่ใช้บริการ', '');
        }
    }

    async clear() {
        if (this.type === 'web' && this.displayWindow) {
            this.displayWindow.postMessage({
                type: 'clear'
            }, '*');
        }

        if (this.type === 'usb' && this.device) {
            const encoder = new TextEncoder();
            await this.device.transferOut(1, encoder.encode('\x0C'));
        }
    }

    disconnect() {
        if (this.displayWindow) {
            this.displayWindow.close();
        }

        if (this.device) {
            this.device.close();
        }

        this.isConnected = false;
    }
}

// ============================================
// SCALE
// ============================================

/**
 * Scale Class
 */
export class Scale {
    constructor(options = {}) {
        this.type = options.type || 'serial';
        this.device = null;
        this.isConnected = false;
        this.onWeight = options.onWeight || (() => {});
    }

    async connect() {
        if (!('serial' in navigator)) {
            console.warn('[Scale] Web Serial not supported');
            return false;
        }

        try {
            this.port = await navigator.serial.requestPort();
            await this.port.open({
                baudRate: 9600,
                dataBits: 8,
                stopBits: 1,
                parity: 'none'
            });

            this.isConnected = true;
            this.startReading();

            console.log('[Scale] Connected');
            return true;

        } catch (error) {
            console.error('[Scale] Connection failed:', error);
            throw error;
        }
    }

    async startReading() {
        const reader = this.port.readable.getReader();
        const decoder = new TextDecoder();
        let buffer = '';

        while (this.isConnected) {
            try {
                const { value, done } = await reader.read();

                if (done) break;

                buffer += decoder.decode(value);

                // ค้นหา weight data (format depends on scale)
                const match = buffer.match(/(\d+\.?\d*)\s*(kg|g)/i);

                if (match) {
                    const weight = parseFloat(match[1]);
                    const unit = match[2].toLowerCase();
                    this.onWeight(weight, unit);
                    buffer = '';
                }

            } catch (error) {
                if (error.name === 'NetworkError') break;
                console.error('[Scale] Read error:', error);
            }
        }

        reader.releaseLock();
    }

    async requestWeight() {
        if (!this.isConnected) return null;

        const writer = this.port.writable.getWriter();
        const encoder = new TextEncoder();

        // ส่งคำสั่งขอน้ำหนัก (depends on scale protocol)
        await writer.write(encoder.encode('W\r\n'));
        writer.releaseLock();
    }

    disconnect() {
        this.isConnected = false;

        if (this.port) {
            this.port.close();
        }
    }
}

// ============================================
// HARDWARE MANAGER
// ============================================

/**
 * Hardware Manager - จัดการอุปกรณ์ทั้งหมด
 */
export class HardwareManager {
    constructor() {
        this.printer = null;
        this.scanner = null;
        this.cameraScanner = null;
        this.cashDrawer = null;
        this.customerDisplay = null;
        this.scale = null;
    }

    async initPrinter(options = {}) {
        this.printer = new ReceiptPrinter(options);
        await this.printer.connect();
        this.cashDrawer = new CashDrawer(this.printer);
        return this.printer;
    }

    initScanner(onScan) {
        this.scanner = new BarcodeScanner({ onScan });
        this.scanner.start();
        return this.scanner;
    }

    async initCameraScanner(videoElement, onScan) {
        this.cameraScanner = new CameraBarcodeScanner({
            videoElement,
            onScan
        });
        await this.cameraScanner.start();
        return this.cameraScanner;
    }

    async initCustomerDisplay(options = {}) {
        this.customerDisplay = new CustomerDisplay(options);
        await this.customerDisplay.connect();
        return this.customerDisplay;
    }

    async initScale(onWeight) {
        this.scale = new Scale({ onWeight });
        await this.scale.connect();
        return this.scale;
    }

    disconnectAll() {
        if (this.printer) this.printer.disconnect();
        if (this.scanner) this.scanner.stop();
        if (this.cameraScanner) this.cameraScanner.stop();
        if (this.customerDisplay) this.customerDisplay.disconnect();
        if (this.scale) this.scale.disconnect();
    }
}

// Export default instance
export const hardware = new HardwareManager();

export default hardware;
