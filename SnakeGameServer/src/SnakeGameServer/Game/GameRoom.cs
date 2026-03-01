using System.Collections.Concurrent;
using SnakeGameServer.Core;
using SnakeGameServer.Network.Protocol;

namespace SnakeGameServer.Game;

/// <summary>
/// สถานะห้อง
/// </summary>
public enum RoomStatus
{
    Waiting,
    Active,
    Finished
}

/// <summary>
/// ห้องเกม — จัดการ state ทั้งหมดของ 1 ห้อง
/// Tick ถูกเรียกจาก GameLoop 30 ครั้ง/วินาที
/// </summary>
public class GameRoom
{
    private readonly ServerConfig _config;
    private readonly Random _rng = new();
    private readonly object _lock = new();

    public string RoomId { get; }
    public string RoomCode { get; }
    public RoomStatus Status { get; set; } = RoomStatus.Waiting;
    public DateTime CreatedAt { get; }

    /// <summary>
    /// ผู้เล่นทั้งหมดในห้อง
    /// </summary>
    public ConcurrentDictionary<string, Player> Players { get; } = new();

    /// <summary>
    /// อาหารทั้งหมดในห้อง
    /// </summary>
    public List<Food> Foods { get; } = new();

    /// <summary>
    /// Powerups ทั้งหมดในห้อง
    /// </summary>
    public List<Powerup> Powerups { get; } = new();

    /// <summary>
    /// Event: ต้องการส่ง message ไปยังผู้เล่นเฉพาะ
    /// </summary>
    public event Action<string, string>? OnSendToPlayer;

    /// <summary>
    /// Event: ผู้เล่นตาย (สำหรับ save stats)
    /// </summary>
    public event Action<Player, string?, int, int>? OnPlayerDied;

    /// <summary>
    /// Event: log
    /// </summary>
    public event Action<string>? OnLog;

    // === Powerup spawn timer ===
    private int _powerupSpawnTicks;
    private int _powerupSpawnInterval;

    public GameRoom(string roomCode, ServerConfig config)
    {
        RoomId = Guid.NewGuid().ToString("N")[..12];
        RoomCode = roomCode;
        _config = config;
        CreatedAt = DateTime.UtcNow;
        _powerupSpawnInterval = config.PowerupExpireSeconds * config.TickRate / 3; // spawn ทุก 20 วินาที

        // Spawn อาหารเริ่มต้น
        SpawnInitialFood();
    }

    /// <summary>
    /// เพิ่มผู้เล่นเข้าห้อง
    /// </summary>
    public Player? AddPlayer(string name, string skin)
    {
        if (Players.Count >= _config.MaxPlayersPerRoom)
            return null;

        // สุ่มตำแหน่งเริ่มต้น
        double x = (_rng.NextDouble() * 2 - 1) * _config.SpawnBoundary;
        double z = (_rng.NextDouble() * 2 - 1) * _config.SpawnBoundary;

        var player = new Player(
            name, skin, x, z,
            _config.InitialSnakeLength,
            _config.SnakeSpeed,
            _config.SnakeBoostSpeed,
            _config.SnakeTurnSpeed,
            _config.SegmentSpacing);

        if (!Players.TryAdd(player.Id, player))
            return null;

        // เปลี่ยนสถานะห้องเป็น Active ถ้ามี >= 2 คน
        if (Players.Count >= 2 && Status == RoomStatus.Waiting)
            Status = RoomStatus.Active;

        // แจ้งผู้เล่นอื่น
        var joinMsg = MessageSerializer.Serialize(new PlayerJoinMessage
        {
            Player = CreatePlayerDto(player)
        });

        foreach (var (_, existingPlayer) in Players)
        {
            if (existingPlayer.Id != player.Id)
                OnSendToPlayer?.Invoke(existingPlayer.Id, joinMsg);
        }

        OnLog?.Invoke($"Player {name} joined room {RoomCode} ({Players.Count}/{_config.MaxPlayersPerRoom})");

        return player;
    }

    /// <summary>
    /// ลบผู้เล่นออกจากห้อง
    /// </summary>
    public void RemovePlayer(string playerId)
    {
        if (!Players.TryRemove(playerId, out var player))
            return;

        player.DeathReason = "left";

        // แจ้งผู้เล่นอื่น
        var leaveMsg = MessageSerializer.Serialize(new PlayerLeaveMessage
        {
            PlayerId = playerId
        });
        BroadcastToAll(leaveMsg);

        // ถ้าห้องว่าง → Finished
        if (Players.IsEmpty)
            Status = RoomStatus.Finished;

        OnLog?.Invoke($"Player {player.Name} left room {RoomCode} ({Players.Count}/{_config.MaxPlayersPerRoom})");
    }

    /// <summary>
    /// Game tick — เรียก 30 ครั้ง/วินาที
    /// </summary>
    public void Tick(long tickCount)
    {
        lock (_lock)
        {
            if (Players.IsEmpty) return;

            // 1. Move all snakes
            MoveSnakes();

            // 2. Check collisions
            CheckFoodCollisions();
            CheckPowerupCollisions();
            CheckSnakeCollisions();
            CheckBoundaryCollisions();

            // 3. Maintain food supply
            MaintainFoodCount();

            // 4. Spawn powerups periodically
            _powerupSpawnTicks++;
            if (_powerupSpawnTicks >= _powerupSpawnInterval)
            {
                SpawnRandomPowerup();
                _powerupSpawnTicks = 0;
            }

            // 5. Remove expired powerups
            RemoveExpiredPowerups();

            // 6. Broadcast state
            BroadcastState(tickCount);
        }
    }

    // ==================== Movement ====================

    private void MoveSnakes()
    {
        double boostCostPerTick = _config.BoostScoreCostPerSecond / _config.TickRate;

        foreach (var (_, player) in Players)
        {
            if (player.Snake.IsAlive)
            {
                player.Snake.Tick(boostCostPerTick);
            }
        }
    }

    // ==================== Collisions ====================

    private void CheckFoodCollisions()
    {
        var collectedFoods = new List<(Food food, Player player)>();

        foreach (var (_, player) in Players)
        {
            if (!player.Snake.IsAlive) continue;
            var head = player.Snake.HeadPosition;

            foreach (var food in Foods)
            {
                if (food.IsCollected) continue;

                // Magnet: ดึงอาหารเข้าหาตัว
                if (player.Snake.HasMagnet)
                {
                    double dist = head.DistanceTo2D(food.Position);
                    if (dist < 5.0 && dist > _config.FoodCollisionRadius)
                    {
                        // ดึง food เข้าหา head
                        food.Position = Vec3.Lerp(food.Position, head, 0.1);
                    }
                }

                if (head.DistanceTo2D(food.Position) < _config.FoodCollisionRadius)
                {
                    food.IsCollected = true;
                    collectedFoods.Add((food, player));
                }
            }
        }

        // Process collected foods
        foreach (var (food, player) in collectedFoods)
        {
            player.Snake.AddScore(food.Value);

            // แจ้งทุกคน
            var msg = MessageSerializer.Serialize(new FoodCollectedMessage
            {
                FoodId = food.Id,
                PlayerId = player.Id
            });
            BroadcastToAll(msg);
        }

        // ลบอาหารที่ถูกเก็บแล้ว
        Foods.RemoveAll(f => f.IsCollected);
    }

    private void CheckPowerupCollisions()
    {
        var collected = new List<(Powerup powerup, Player player)>();

        foreach (var (_, player) in Players)
        {
            if (!player.Snake.IsAlive) continue;
            var head = player.Snake.HeadPosition;

            foreach (var powerup in Powerups)
            {
                if (powerup.IsCollected || powerup.IsExpired) continue;

                if (head.DistanceTo2D(powerup.Position) < _config.FoodCollisionRadius)
                {
                    powerup.IsCollected = true;
                    collected.Add((powerup, player));
                }
            }
        }

        foreach (var (powerup, player) in collected)
        {
            player.Snake.ApplyPowerup(powerup.Type, _config.PowerupExpireSeconds);
        }

        Powerups.RemoveAll(p => p.IsCollected);
    }

    private void CheckSnakeCollisions()
    {
        var alivePlayers = Players.Values.Where(p => p.Snake.IsAlive).ToList();
        var killList = new List<(Player victim, string? killerId)>();

        foreach (var player in alivePlayers)
        {
            var head = player.Snake.HeadPosition;

            // ชน snake อื่น
            foreach (var other in alivePlayers)
            {
                if (player.Id == other.Id) continue;

                // Head vs body segments (skip head ของอีกฝ่าย)
                for (int i = 1; i < other.Snake.Segments.Count; i++)
                {
                    if (head.DistanceTo2D(other.Snake.Segments[i]) < _config.SnakeCollisionRadius)
                    {
                        killList.Add((player, other.Id));
                        goto nextPlayer;
                    }
                }

                // Head vs Head collision
                if (head.DistanceTo2D(other.Snake.HeadPosition) < _config.SnakeCollisionRadius)
                {
                    // ตัวที่เล็กกว่าตาย ถ้าเท่ากันตายทั้งคู่
                    if (player.Snake.Length <= other.Snake.Length)
                    {
                        killList.Add((player, other.Id));
                        goto nextPlayer;
                    }
                }
            }

            // Self-collision (head vs body, skip 4 segments แรก)
            for (int i = 4; i < player.Snake.Segments.Count; i++)
            {
                if (head.DistanceTo2D(player.Snake.Segments[i]) < _config.SnakeCollisionRadius * 0.5)
                {
                    killList.Add((player, null));
                    break;
                }
            }

            nextPlayer:;
        }

        // Process kills
        foreach (var (victim, killerId) in killList)
        {
            if (victim.Snake.IsAlive) // ป้องกัน double-kill
            {
                KillPlayer(victim, killerId, killerId != null ? "collision" : "self");
            }
        }
    }

    private void CheckBoundaryCollisions()
    {
        double boundary = _config.HalfWorldSize;

        foreach (var (_, player) in Players)
        {
            if (!player.Snake.IsAlive) continue;
            var head = player.Snake.HeadPosition;

            if (Math.Abs(head.X) > boundary || Math.Abs(head.Z) > boundary)
            {
                KillPlayer(player, null, "boundary");
            }
        }
    }

    // ==================== Kill & Drop ====================

    private void KillPlayer(Player player, string? killerId, string deathReason)
    {
        player.Snake.IsAlive = false;
        player.DeathReason = deathReason;
        player.KilledBy = killerId;

        // เพิ่ม kill count ให้ killer
        if (killerId != null && Players.TryGetValue(killerId, out var killer))
        {
            killer.Kills++;
        }

        // Drop อาหารจากหนอนที่ตาย
        var droppedFood = new List<Food>();
        int dropCount = Math.Min(player.Snake.Segments.Count, _config.MaxDroppedFoodOnDeath);
        var deathPos = player.Snake.HeadPosition;

        for (int i = 0; i < dropCount; i++)
        {
            var food = new Food(
                new Vec3(
                    deathPos.X + (_rng.NextDouble() * 10 - 5),
                    0.3,
                    deathPos.Z + (_rng.NextDouble() * 10 - 5)),
                _config.DroppedFoodValue);
            Foods.Add(food);
            droppedFood.Add(food);
        }

        // Broadcast player_died
        var diedMsg = MessageSerializer.Serialize(new PlayerDiedMessage
        {
            PlayerId = player.Id,
            KillerId = killerId,
            FinalScore = player.Snake.Score,
            FinalLength = player.Snake.Length,
            DroppedFood = droppedFood.Select(f => new FoodDto
            {
                Id = f.Id,
                Position = f.Position,
                Value = f.Value
            }).ToList()
        });
        BroadcastToAll(diedMsg);

        // Fire event สำหรับ save stats
        OnPlayerDied?.Invoke(player, killerId, player.Snake.Score, player.Snake.Length);

        OnLog?.Invoke($"Player {player.Name} died ({deathReason}) - Score: {player.Snake.Score}");
    }

    // ==================== Food Management ====================

    private void SpawnInitialFood()
    {
        double halfWorld = _config.HalfWorldSize - 10;
        for (int i = 0; i < _config.InitialFoodCount; i++)
        {
            Foods.Add(new Food(
                new Vec3(
                    (_rng.NextDouble() * 2 - 1) * halfWorld,
                    0.3,
                    (_rng.NextDouble() * 2 - 1) * halfWorld),
                1));
        }
    }

    private void MaintainFoodCount()
    {
        if (Foods.Count >= _config.FoodRespawnThreshold) return;

        int spawnCount = Math.Min(
            _config.InitialFoodCount - Foods.Count,
            _config.MaxFoodSpawnPerTick);

        if (spawnCount <= 0) return;

        double halfWorld = _config.HalfWorldSize - 10;
        var newFoods = new List<FoodDto>();

        for (int i = 0; i < spawnCount; i++)
        {
            var food = new Food(
                new Vec3(
                    (_rng.NextDouble() * 2 - 1) * halfWorld,
                    0.3,
                    (_rng.NextDouble() * 2 - 1) * halfWorld),
                1);
            Foods.Add(food);
            newFoods.Add(new FoodDto { Id = food.Id, Position = food.Position, Value = food.Value });
        }

        // แจ้ง food spawn
        var msg = MessageSerializer.Serialize(new FoodSpawnMessage { Foods = newFoods });
        BroadcastToAll(msg);
    }

    private void SpawnRandomPowerup()
    {
        if (Powerups.Count >= 5) return; // จำกัด powerup ในห้อง

        double halfWorld = _config.HalfWorldSize - 20;
        var type = Powerup.Types[_rng.Next(Powerup.Types.Length)];

        var powerup = new Powerup(type,
            new Vec3(
                (_rng.NextDouble() * 2 - 1) * halfWorld,
                0.3,
                (_rng.NextDouble() * 2 - 1) * halfWorld),
            _config.PowerupExpireSeconds);

        Powerups.Add(powerup);
    }

    private void RemoveExpiredPowerups()
    {
        Powerups.RemoveAll(p => p.IsExpired);
    }

    // ==================== Broadcasting ====================

    private void BroadcastState(long tickCount)
    {
        bool sendFullSegments = (tickCount % _config.FullSyncInterval == 0);

        var playerStates = Players.Values
            .Where(p => p.Snake.IsAlive)
            .Select(p => new PlayerStateDto
            {
                Id = p.Id,
                Name = p.Name,
                Skin = p.Skin,
                Position = p.Snake.HeadPosition,
                Direction = p.Snake.Direction,
                Score = p.Snake.Score,
                Length = p.Snake.Length,
                IsAlive = true,
                IsBoosting = p.Snake.IsBoosting,
                Segments = sendFullSegments ? new List<Vec3>(p.Snake.Segments) : null
            })
            .ToList();

        var stateJson = MessageSerializer.Serialize(new StateMessage
        {
            Players = playerStates,
            Tick = tickCount
        });

        BroadcastToAll(stateJson);
    }

    private void BroadcastToAll(string json)
    {
        foreach (var (_, player) in Players)
        {
            OnSendToPlayer?.Invoke(player.Id, json);
        }
    }

    // ==================== Helpers ====================

    /// <summary>
    /// สร้าง WelcomeMessage สำหรับผู้เล่นใหม่
    /// </summary>
    public WelcomeMessage CreateWelcomeMessage(Player player)
    {
        return new WelcomeMessage
        {
            PlayerId = player.Id,
            RoomId = RoomId,
            WorldSize = _config.WorldSize,
            TickRate = _config.TickRate,
            FoodList = Foods.Select(f => new FoodDto
            {
                Id = f.Id,
                Position = f.Position,
                Value = f.Value
            }).ToList(),
            PlayerList = Players.Values
                .Where(p => p.Snake.IsAlive)
                .Select(p => CreatePlayerDto(p))
                .ToList()
        };
    }

    private static PlayerDto CreatePlayerDto(Player p)
    {
        return new PlayerDto
        {
            Id = p.Id,
            Name = p.Name,
            Skin = p.Skin,
            Position = p.Snake.HeadPosition,
            Direction = p.Snake.Direction,
            Score = p.Snake.Score,
            Length = p.Snake.Length,
            IsAlive = p.Snake.IsAlive
        };
    }
}
