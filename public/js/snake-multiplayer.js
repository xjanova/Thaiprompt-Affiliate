/**
 * Snake.io Multiplayer Manager (WebSocket Edition)
 *
 * จัดการการเชื่อมต่อ multiplayer ผ่าน Laravel Reverb WebSocket
 */
class SnakeMultiplayerManager {
    constructor(apiBaseUrl = '/api/games/snake-io') {
        this.apiBaseUrl = apiBaseUrl;
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

            // เชื่อมต่อ WebSocket channel
            this.connectWebSocket();

            // โหลดสถานะห้องเริ่มต้น (ผู้เล่นและไอเทมที่มีอยู่แล้ว)
            await this.loadInitialRoomState();

            return data;
        } catch (error) {
            console.error('[Multiplayer] เข้าร่วมเกมล้มเหลว:', error);
            throw error;
        }
    }

    /**
     * เชื่อมต่อ WebSocket channel
     */
    connectWebSocket() {
        if (!window.Echo) {
            console.error('[Multiplayer] Laravel Echo ไม่พร้อมใช้งาน');
            return;
        }

        const channelName = `snake-room.${this.roomId}`;
        this.channel = window.Echo.channel(channelName);

        console.log('[Multiplayer] เชื่อมต่อ WebSocket:', channelName);

        // ฟังเหตุการณ์: ผู้เล่นเข้าร่วม
        this.channel.listen('.player.joined', (data) => {
            console.log('[WebSocket] ผู้เล่นเข้าร่วม:', data);
            this.otherPlayers.set(data.player_id, {
                id: data.player_id,
                name: data.player_name,
                skin: data.skin,
                position: data.position,
                direction: { x: 1, y: 0, z: 0 },
                score: 0,
                length: 5,
                is_alive: true,
            });

            if (this.onPlayerJoinedCallback) {
                this.onPlayerJoinedCallback(data);
            }
        });

        // ฟังเหตุการณ์: ผู้เล่นออกจากห้อง
        this.channel.listen('.player.left', (data) => {
            console.log('[WebSocket] ผู้เล่นออก:', data.player_id);
            this.otherPlayers.delete(data.player_id);

            if (this.onPlayerLeftCallback) {
                this.onPlayerLeftCallback(data);
            }
        });

        // ฟังเหตุการณ์: ผู้เล่นเคลื่อนที่
        this.channel.listen('.player.moved', (data) => {
            // อัปเดตข้อมูลผู้เล่น
            if (this.otherPlayers.has(data.player_id)) {
                const player = this.otherPlayers.get(data.player_id);
                player.position = data.position;
                player.direction = data.direction;
                player.score = data.score;
                player.length = data.length;
            }

            if (this.onPlayerMovedCallback) {
                this.onPlayerMovedCallback(data);
            }
        });

        // ฟังเหตุการณ์: ผู้เล่นตาย
        this.channel.listen('.player.died', (data) => {
            console.log('[WebSocket] ผู้เล่นตาย:', data.player_id);

            if (this.otherPlayers.has(data.player_id)) {
                const player = this.otherPlayers.get(data.player_id);
                player.is_alive = false;
            }

            if (this.onPlayerDiedCallback) {
                this.onPlayerDiedCallback(data);
            }
        });

        // ฟังเหตุการณ์: ไอเทมใหม่เกิด
        this.channel.listen('.item.spawned', (data) => {
            console.log('[WebSocket] ไอเทมใหม่:', data);
            this.serverItems.set(data.item_id, {
                id: data.item_id,
                type: data.type,
                position: data.position,
                value: data.value,
            });

            if (this.onItemSpawnedCallback) {
                this.onItemSpawnedCallback(data);
            }
        });

        // ฟังเหตุการณ์: ไอเทมถูกเก็บ
        this.channel.listen('.item.collected', (data) => {
            console.log('[WebSocket] ไอเทมถูกเก็บ:', data.item_id);
            this.serverItems.delete(data.item_id);

            if (this.onItemCollectedCallback) {
                this.onItemCollectedCallback(data);
            }
        });
    }

    /**
     * ยกเลิกการเชื่อมต่อ WebSocket
     */
    disconnectWebSocket() {
        if (this.channel) {
            window.Echo.leave(`snake-room.${this.roomId}`);
            this.channel = null;
            console.log('[Multiplayer] ยกเลิกการเชื่อมต่อ WebSocket');
        }
    }

    /**
     * โหลดสถานะห้องเริ่มต้น
     */
    async loadInitialRoomState() {
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
            console.error('[Multiplayer] โหลดสถานะห้องล้มเหลว:', error);
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
