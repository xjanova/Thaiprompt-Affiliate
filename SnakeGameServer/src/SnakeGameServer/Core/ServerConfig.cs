namespace SnakeGameServer.Core;

/// <summary>
/// การตั้งค่า server ทั้งหมด
/// </summary>
public class ServerConfig
{
    // === Network ===
    public int Port { get; set; } = 8080;

    // === Game ===
    public int TickRate { get; set; } = 30;
    public int MaxPlayersPerRoom { get; set; } = 30;
    public int WorldSize { get; set; } = 200;
    public int InitialFoodCount { get; set; } = 100;
    public int FoodRespawnThreshold { get; set; } = 80;
    public int MaxFoodSpawnPerTick { get; set; } = 5;
    public int PowerupExpireSeconds { get; set; } = 60;
    public int MaxDroppedFoodOnDeath { get; set; } = 10;
    public int DroppedFoodValue { get; set; } = 2;

    // === Snake ===
    public int InitialSnakeLength { get; set; } = 5;
    public double SnakeSpeed { get; set; } = 0.30;          // units/tick
    public double SnakeBoostSpeed { get; set; } = 0.60;     // units/tick
    public double SnakeTurnSpeed { get; set; } = 0.15;      // lerp factor/tick
    public double SegmentSpacing { get; set; } = 0.5;       // ระยะห่างระหว่าง segment
    public double FoodCollisionRadius { get; set; } = 0.8;  // รัศมีเก็บอาหาร
    public double SnakeCollisionRadius { get; set; } = 0.6; // รัศมีชนหนอน
    public int ScorePerLength { get; set; } = 10;           // score ที่ต้องได้เพื่อยาวขึ้น 1
    public double BoostScoreCostPerSecond { get; set; } = 2; // score ที่ใช้ต่อวินาที

    // === Database ===
    public string DbProvider { get; set; } = "sqlite";     // sqlite, mysql, mssql
    public string ConnectionString { get; set; } = "Data Source=snakegame.db";

    // === Anti-Cheat ===
    public int MaxInputsPerSecond { get; set; } = 60;

    // === Broadcast Optimization ===
    public int FullSyncInterval { get; set; } = 10;  // ส่ง full segments ทุกกี่ tick

    /// <summary>
    /// คำนวณ ms ต่อ tick
    /// </summary>
    public double TickIntervalMs => 1000.0 / TickRate;

    /// <summary>
    /// ขอบเขตโลก (ครึ่งหนึ่ง)
    /// </summary>
    public double HalfWorldSize => WorldSize / 2.0;

    /// <summary>
    /// ขอบเขตสำหรับ spawn (เว้นระยะจากขอบ)
    /// </summary>
    public double SpawnBoundary => HalfWorldSize - 20;
}
