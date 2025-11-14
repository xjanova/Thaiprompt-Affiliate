/**
 * Snake.io Multiplayer Manager
 *
 * จัดการการเชื่อมต่อ multiplayer และ sync state กับ server
 */
class SnakeMultiplayerManager {
    constructor(apiBaseUrl = '/api/games/snake-io') {
        this.apiBaseUrl = apiBaseUrl;
        this.roomId = null;
        this.playerId = null;
        this.roomCode = null;
        this.syncInterval = null;
        this.itemSpawnInterval = null;
        this.otherPlayers = new Map(); // Map<playerId, playerData>
        this.serverItems = new Map(); // Map<itemId, itemData>

        // Sync settings
        this.SYNC_RATE = 200; // มิลลิวินาที (5 ครั้ง/วินาที)
        this.ITEM_CHECK_RATE = 1000; // ตรวจสอบไอเทมทุก 1 วินาที

        // CSRF Token
        this.csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
    }

    /**
     * เข้าร่วมเกม
     */
    async joinGame(playerName, skinSlug = 'classic') {
        try {
            const response = await fetch(`${this.apiBaseUrl}/join`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': this.csrfToken,
                },
                body: JSON.stringify({
                    player_name: playerName,
                    skin_slug: skinSlug,
                }),
            });

            const data = await response.json();

            if (!data.success) {
                throw new Error(data.message || 'เข้าร่วมเกมไม่สำเร็จ');
            }

            this.roomId = data.room_id;
            this.playerId = data.player_id;
            this.roomCode = data.room_code;

            console.log('[Multiplayer] เข้าร่วมห้อง:', this.roomCode, 'Player ID:', this.playerId);

            // เริ่ม sync
            this.startSync();

            return data;
        } catch (error) {
            console.error('[Multiplayer] เข้าร่วมเกมล้มเหลว:', error);
            throw error;
        }
    }

    /**
     * ออกจากเกม
     */
    async leaveGame() {
        try {
            this.stopSync();

            if (!this.playerId) return;

            await fetch(`${this.apiBaseUrl}/leave`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': this.csrfToken,
                },
                body: JSON.stringify({
                    player_id: this.playerId,
                }),
            });

            console.log('[Multiplayer] ออกจากห้อง');
        } catch (error) {
            console.error('[Multiplayer] ออกจากเกมล้มเหลว:', error);
        }
    }

    /**
     * อัปเดตสถานะผู้เล่น
     */
    async updatePlayerState(position, direction, score, length) {
        if (!this.playerId) return;

        try {
            await fetch(`${this.apiBaseUrl}/update-state`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': this.csrfToken,
                },
                body: JSON.stringify({
                    player_id: this.playerId,
                    position: {
                        x: position.x,
                        y: position.y,
                        z: position.z,
                    },
                    direction: {
                        x: direction.x,
                        y: direction.y,
                        z: direction.z,
                    },
                    score: score,
                    length: length,
                }),
            });
        } catch (error) {
            console.error('[Multiplayer] อัปเดตสถานะล้มเหลว:', error);
        }
    }

    /**
     * แจ้งว่าผู้เล่นตาย
     */
    async playerDied() {
        if (!this.playerId) return;

        try {
            const response = await fetch(`${this.apiBaseUrl}/player-died`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': this.csrfToken,
                },
                body: JSON.stringify({
                    player_id: this.playerId,
                }),
            });

            const data = await response.json();
            console.log('[Multiplayer] ผู้เล่นตาย - คะแนนสุดท้าย:', data.final_score);

            this.stopSync();
            return data;
        } catch (error) {
            console.error('[Multiplayer] แจ้งการตายล้มเหลว:', error);
        }
    }

    /**
     * เก็บไอเทม
     */
    async collectItem(itemId) {
        if (!this.playerId) return null;

        try {
            const response = await fetch(`${this.apiBaseUrl}/collect-item`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': this.csrfToken,
                },
                body: JSON.stringify({
                    player_id: this.playerId,
                    item_id: itemId,
                }),
            });

            const data = await response.json();

            if (data.success) {
                console.log('[Multiplayer] เก็บไอเทม:', data.item.type);
                return data.item;
            }

            return null;
        } catch (error) {
            console.error('[Multiplayer] เก็บไอเทมล้มเหลว:', error);
            return null;
        }
    }

    /**
     * บันทึกคะแนน (ต้อง auth + wallet)
     */
    async saveScore(score, length) {
        if (!this.playerId) return;

        try {
            const response = await fetch(`${this.apiBaseUrl}/save-score`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': this.csrfToken,
                },
                body: JSON.stringify({
                    player_id: this.playerId,
                    score: score,
                    length: length,
                }),
            });

            const data = await response.json();
            return data;
        } catch (error) {
            console.error('[Multiplayer] บันทึกคะแนนล้มเหลว:', error);
            throw error;
        }
    }

    /**
     * ตรวจสอบ wallet
     */
    async checkWallet() {
        try {
            const response = await fetch(`${this.apiBaseUrl}/check-wallet`, {
                method: 'GET',
                headers: {
                    'Content-Type': 'application/json',
                },
            });

            const data = await response.json();
            return data;
        } catch (error) {
            console.error('[Multiplayer] ตรวจสอบ wallet ล้มเหลว:', error);
            return { success: false, authenticated: false };
        }
    }

    /**
     * เริ่ม sync กับ server
     */
    startSync() {
        // Sync สถานะห้องเป็นระยะ
        this.syncInterval = setInterval(async () => {
            await this.syncRoomState();
        }, this.SYNC_RATE);

        // ตรวจสอบไอเทมใหม่
        this.itemSpawnInterval = setInterval(async () => {
            await this.syncRoomState();
        }, this.ITEM_CHECK_RATE);

        console.log('[Multiplayer] เริ่ม sync (ทุก', this.SYNC_RATE, 'ms)');
    }

    /**
     * หยุด sync
     */
    stopSync() {
        if (this.syncInterval) {
            clearInterval(this.syncInterval);
            this.syncInterval = null;
        }

        if (this.itemSpawnInterval) {
            clearInterval(this.itemSpawnInterval);
            this.itemSpawnInterval = null;
        }

        console.log('[Multiplayer] หยุด sync');
    }

    /**
     * Sync สถานะห้องจาก server
     */
    async syncRoomState() {
        if (!this.roomId) return;

        try {
            const response = await fetch(`${this.apiBaseUrl}/room-state/${this.roomId}`, {
                method: 'GET',
                headers: {
                    'Content-Type': 'application/json',
                },
            });

            const data = await response.json();

            if (!data.success) return;

            const roomState = data.room_state;

            // อัปเดตผู้เล่นคนอื่น
            this.updateOtherPlayers(roomState.players);

            // อัปเดตไอเทม
            this.updateItems(roomState.items);

        } catch (error) {
            console.error('[Multiplayer] Sync room state ล้มเหลว:', error);
        }
    }

    /**
     * อัปเดตข้อมูลผู้เล่นคนอื่น
     */
    updateOtherPlayers(players) {
        // ล้างผู้เล่นเก่า
        const currentPlayerIds = new Set();

        players.forEach(playerData => {
            // ข้ามตัวเอง
            if (playerData.id === this.playerId) return;

            currentPlayerIds.add(playerData.id);
            this.otherPlayers.set(playerData.id, playerData);
        });

        // ลบผู้เล่นที่ออกไปแล้ว
        for (const [playerId] of this.otherPlayers) {
            if (!currentPlayerIds.has(playerId)) {
                this.otherPlayers.delete(playerId);
            }
        }
    }

    /**
     * อัปเดตข้อมูลไอเทม
     */
    updateItems(items) {
        // ล้างไอเทมเก่า
        const currentItemIds = new Set();

        items.forEach(itemData => {
            currentItemIds.add(itemData.id);
            this.serverItems.set(itemData.id, itemData);
        });

        // ลบไอเทมที่หมดอายุ
        for (const [itemId] of this.serverItems) {
            if (!currentItemIds.has(itemId)) {
                this.serverItems.delete(itemId);
            }
        }
    }

    /**
     * ดึงผู้เล่นคนอื่นทั้งหมด
     */
    getOtherPlayers() {
        return Array.from(this.otherPlayers.values());
    }

    /**
     * ดึงไอเทมทั้งหมด
     */
    getServerItems() {
        return Array.from(this.serverItems.values());
    }
}

// Export for use
if (typeof module !== 'undefined' && module.exports) {
    module.exports = SnakeMultiplayerManager;
} else {
    window.SnakeMultiplayerManager = SnakeMultiplayerManager;
}
