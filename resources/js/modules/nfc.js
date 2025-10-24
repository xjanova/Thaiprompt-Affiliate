/**
 * NFC Payment Module
 * Handles Web NFC API for card scanning and payment processing
 */

class NFCPayment {
    constructor() {
        this.reader = null;
        this.isScanning = false;
        this.onCardDetected = null;
        this.onError = null;
    }

    /**
     * Check if Web NFC is supported
     */
    static isSupported() {
        return 'NDEFReader' in window;
    }

    /**
     * Request NFC permission and initialize reader
     */
    async initialize() {
        if (!NFCPayment.isSupported()) {
            throw new Error('Web NFC is not supported on this device or browser');
        }

        try {
            this.reader = new NDEFReader();
            return true;
        } catch (error) {
            console.error('Failed to initialize NFC reader:', error);
            throw error;
        }
    }

    /**
     * Start scanning for NFC cards
     */
    async startScan(onCardDetected, onError) {
        if (!this.reader) {
            await this.initialize();
        }

        if (this.isScanning) {
            console.warn('Already scanning for NFC cards');
            return;
        }

        this.onCardDetected = onCardDetected;
        this.onError = onError;

        try {
            await this.reader.scan();
            this.isScanning = true;

            this.reader.addEventListener('reading', ({ message, serialNumber }) => {
                console.log('NFC Card detected:', serialNumber);

                // Convert serial number to readable format
                const cardUid = this.formatSerialNumber(serialNumber);

                // Call the callback with card data
                if (this.onCardDetected) {
                    this.onCardDetected(cardUid, message);
                }
            });

            this.reader.addEventListener('readingerror', () => {
                console.error('NFC reading error');
                if (this.onError) {
                    this.onError('Failed to read NFC card');
                }
            });

            console.log('NFC scanning started');
        } catch (error) {
            console.error('Error starting NFC scan:', error);
            this.isScanning = false;
            if (this.onError) {
                this.onError(error.message);
            }
            throw error;
        }
    }

    /**
     * Stop scanning for NFC cards
     */
    stopScan() {
        this.isScanning = false;
        console.log('NFC scanning stopped');
    }

    /**
     * Format serial number to readable string
     */
    formatSerialNumber(serialNumber) {
        return serialNumber.split(':').map(byte => byte.toUpperCase()).join('');
    }

    /**
     * Write data to NFC card
     */
    async writeCard(data) {
        if (!this.reader) {
            await this.initialize();
        }

        try {
            const encoder = new TextEncoder();
            const records = [
                {
                    recordType: 'text',
                    data: encoder.encode(JSON.stringify(data))
                }
            ];

            await this.reader.write({
                records: records
            });

            console.log('Data written to NFC card successfully');
            return true;
        } catch (error) {
            console.error('Error writing to NFC card:', error);
            throw error;
        }
    }

    /**
     * Process payment using NFC card
     */
    async processPayment(cardUid, amount, terminalId = null) {
        try {
            const response = await fetch('/api/v1/nfc/process', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    card_uid: cardUid,
                    amount: amount,
                    terminal_id: terminalId
                })
            });

            const result = await response.json();

            if (!result.success) {
                throw new Error(result.message || 'Payment failed');
            }

            return result;
        } catch (error) {
            console.error('Payment processing error:', error);
            throw error;
        }
    }

    /**
     * Check card balance
     */
    async checkBalance(cardUid) {
        try {
            const response = await fetch('/api/v1/nfc/check-balance', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    card_uid: cardUid
                })
            });

            const result = await response.json();

            if (!result.success) {
                throw new Error(result.message || 'Failed to check balance');
            }

            return result;
        } catch (error) {
            console.error('Balance check error:', error);
            throw error;
        }
    }

    /**
     * Verify card
     */
    async verifyCard(cardUid) {
        try {
            const response = await fetch('/api/v1/nfc/verify', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    card_uid: cardUid
                })
            });

            const result = await response.json();

            if (!result.success) {
                throw new Error(result.message || 'Card verification failed');
            }

            return result;
        } catch (error) {
            console.error('Card verification error:', error);
            throw error;
        }
    }
}

export default NFCPayment;
