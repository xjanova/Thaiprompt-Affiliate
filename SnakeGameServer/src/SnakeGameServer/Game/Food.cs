namespace SnakeGameServer.Game;

/// <summary>
/// อาหารในเกม
/// </summary>
public class Food
{
    public string Id { get; }
    public Vec3 Position { get; set; }
    public int Value { get; set; } = 1;
    public bool IsCollected { get; set; }

    public Food()
    {
        Id = Guid.NewGuid().ToString("N")[..8];
    }

    public Food(Vec3 position, int value = 1) : this()
    {
        Position = position;
        Value = value;
    }
}

/// <summary>
/// Powerup ในเกม (magnet, speed, multiplier)
/// </summary>
public class Powerup
{
    public string Id { get; }
    public string Type { get; set; } = "speed";
    public Vec3 Position { get; set; }
    public DateTime SpawnedAt { get; set; }
    public DateTime ExpiresAt { get; set; }
    public bool IsCollected { get; set; }

    public bool IsExpired => DateTime.UtcNow > ExpiresAt;

    public static readonly string[] Types = { "magnet", "speed", "multiplier" };

    public Powerup()
    {
        Id = Guid.NewGuid().ToString("N")[..8];
        SpawnedAt = DateTime.UtcNow;
    }

    public Powerup(string type, Vec3 position, int expireSeconds = 60) : this()
    {
        Type = type;
        Position = position;
        ExpiresAt = SpawnedAt.AddSeconds(expireSeconds);
    }
}
