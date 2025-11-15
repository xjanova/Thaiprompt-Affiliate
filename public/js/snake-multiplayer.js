/**
 * Snake.io Multiplayer Manager (WebSocket Edition)
 *
 * จัดการการเชื่อมต่อ multiplayer ผ่าน Laravel Reverb WebSocket
 */
class SnakeMultiplayerManager {
    constructor(apiBaseUrl = '/api/games/snake-io', serverConfig = null) {
        // ✅ รองรับการตั้งค่า server แบบ custom (IP + Port)
        if (serverConfig && serverConfig.ip && serverConfig.port) {
            // ใช้ full URL จาก server config
            this.apiBaseUrl = `http://${serverConfig.ip}:${serverConfig.port}/api/games/snake-io`;
            console.log('[Multiplayer] ใช้ custom server:', this.apiBaseUrl);
        } else {
            // ใช้ relative path (สำหรับ localhost หรือ same domain)
            this.apiBaseUrl = apiBaseUrl;
        }

        this.roomId = null;
        this.playerId = null;
        this.roomCode = null;
        this.channel = null; // WebSocket channel
        this.otherPlayers = new Map(); // Map<playerId, playerData>
        this.serverItems = new Map(); // Map<itemId, itemData>

        // Callbacks สำหรับการรับ real-time events
        this.onPlayerJoinedCallback = null;
        this.onPlayerLeftCallback = null;
        this.onPlayerMovedCallback = null;
        this.onPlayerDiedCallback = null;
        this.onItemSpawnedCallback = null;
        this.onItemCollectedCallback = null;

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

            // เชื่อมต่อ WebSocket channel (ใช้ polling แทน)
            this.connectWebSocket();

            return data;
        } catch (error) {
            console.error('[Multiplayer] เข้าร่วมเกมล้มเหลว:', error);
            throw error;
        }
    }

    /**
     * เชื่อมต่อ WebSocket channel (ใช้ Polling แทน WebSocket)
     */
    connectWebSocket() {
        console.log('[Multiplayer] เริ่ม polling สำหรับ room:', this.roomId);

        // ✅ ใช้ Polling แทน WebSocket (ไม่ต้องใช้ Laravel Echo)
        // โพลทุก 2 วินาที (เพื่อป้องกันเกมค้าง)
        this.pollingInterval = setInterval(async () => {
            try {
                await this.pollRoomState();
            } catch (error) {
                console.error('[Multiplayer] Polling error:', error);
            }
        }, 2000); // ✅ 2 วินาที (ปรับลดจาก 1 วินาที เพื่อไม่ให้เกมค้าง)
    }

    /**
     * โพลสถานะห้อง (ดึงข้อมูลผู้เล่นคนอื่น)
     */
    async pollRoomState() {
        if (!this.roomId) return;

        try {
            const response = await fetch(`${this.apiBaseUrl}/room-state/${this.roomId}`, {
                method: 'GET',
                headers: {
                    'Content-Type': 'application/json',
                },
            });

            if (!response.ok) return;

            const data = await response.json();
            if (!data.success) return;

            const roomState = data.room_state;

            // อัปเดตผู้เล่นคนอื่น (ไม่รวมตัวเอง)
            const newPlayerIds = new Set();

            if (roomState.players && Array.isArray(roomState.players)) {
                roomState.players.forEach(playerData => {
                    // ข้ามตัวเอง
                    if (playerData.player_id === this.playerId) {
                        return;
                    }

                    newPlayerIds.add(playerData.player_id);

                    // อัปเดตหรือสร้างผู้เล่นใหม่
                    if (!this.otherPlayers.has(playerData.player_id)) {
                        // ผู้เล่นใหม่เข้ามา
                        console.log('[Multiplayer] ผู้เล่นใหม่เข้าร่วม:', playerData.player_name);
                    }

                    this.otherPlayers.set(playerData.player_id, {
                        id: playerData.player_id,
                        name: playerData.player_name || 'Player',
                        skin: playerData.skin || 'classic',
                        position: playerData.position || { x: 0, y: 0, z: 0 },
                        direction: playerData.direction || { x: 1, y: 0, z: 0 },
                        score: playerData.score || 0,
                        length: playerData.length || 5,
                        is_alive: playerData.is_alive !== false,
                    });
                });
            }

            // ลบผู้เล่นที่ออกไปแล้ว
            for (const [playerId, playerData] of this.otherPlayers) {
                if (!newPlayerIds.has(playerId)) {
                    console.log('[Multiplayer] ผู้เล่นออก:', playerData.name);
                    this.otherPlayers.delete(playerId);
                }
            }

        } catch (error) {
            // Silent fail - ไม่ให้ error ซ้ำเยอะ
        }
    }

    /**
     * ยกเลิกการเชื่อมต่อ WebSocket
     */
    disconnectWebSocket() {
        if (this.pollingInterval) {
            clearInterval(this.pollingInterval);
            this.pollingInterval = null;
            console.log('[Multiplayer] หยุด polling');
        }
    }


    /**
     * ออกจากเกม
     */
    async leaveGame() {
        try {
            this.disconnectWebSocket();

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
     * อัปเดตสถานะผู้เล่น (ส่งไปยัง server)
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

            this.disconnectWebSocket();
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

    /**
     * ตั้งค่า callback สำหรับเหตุการณ์ต่างๆ
     */
    setOnPlayerJoined(callback) {
        this.onPlayerJoinedCallback = callback;
    }

    setOnPlayerLeft(callback) {
        this.onPlayerLeftCallback = callback;
    }

    setOnPlayerMoved(callback) {
        this.onPlayerMovedCallback = callback;
    }

    setOnPlayerDied(callback) {
        this.onPlayerDiedCallback = callback;
    }

    setOnItemSpawned(callback) {
        this.onItemSpawnedCallback = callback;
    }

    setOnItemCollected(callback) {
        this.onItemCollectedCallback = callback;
    }
}

// Export for use
if (typeof module !== 'undefined' && module.exports) {
    module.exports = SnakeMultiplayerManager;
} else {
    window.SnakeMultiplayerManager = SnakeMultiplayerManager;
}
