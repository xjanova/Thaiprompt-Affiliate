using SnakeGameServer.Network;

namespace SnakeGameServer.Game;

/// <summary>
/// ผู้เล่นในห้อง — เชื่อม Snake กับ ClientConnection
/// </summary>
public class Player
{
    /// <summary>
    /// Player ID (unique per session)
    /// </summary>
    public string Id { get; }

    /// <summary>
    /// ชื่อผู้เล่น
    /// </summary>
    public string Name { get; set; }

    /// <summary>
    /// Skin ที่เลือก
    /// </summary>
    public string Skin { get; set; }

    /// <summary>
    /// Snake entity
    /// </summary>
    public Snake Snake { get; }

    /// <summary>
    /// WebSocket connection
    /// </summary>
    public ClientConnection? Connection { get; set; }

    /// <summary>
    /// เวลาที่เข้าร่วม
    /// </summary>
    public DateTime JoinedAt { get; }

    /// <summary>
    /// เวลาที่ได้รับ input ล่าสุด
    /// </summary>
    public DateTime LastInputTime { get; set; }

    /// <summary>
    /// จำนวน kills ใน session นี้
    /// </summary>
    public int Kills { get; set; }

    /// <summary>
    /// จำนวน input ใน 1 วินาทีปัจจุบัน
    /// </summary>
    public int InputCountThisSecond { get; set; }

    /// <summary>
    /// วินาทีที่เริ่มนับ input
    /// </summary>
    public DateTime InputCountResetTime { get; set; }

    /// <summary>
    /// เวลาเล่นตั้งแต่เข้าร่วม
    /// </summary>
    public TimeSpan PlayDuration => DateTime.UtcNow - JoinedAt;

    /// <summary>
    /// สาเหตุการตาย
    /// </summary>
    public string? DeathReason { get; set; }

    /// <summary>
    /// ID ของผู้ที่ kill (ถ้ามี)
    /// </summary>
    public string? KilledBy { get; set; }

    public Player(string name, string skin, double x, double z,
                  int initialLength = 5, double speed = 0.30,
                  double boostSpeed = 0.60, double turnSpeed = 0.15,
                  double segmentSpacing = 0.5)
    {
        Id = "p_" + Guid.NewGuid().ToString("N")[..10];
        Name = name;
        Skin = skin;
        Snake = new Snake(x, z, initialLength, speed, boostSpeed, turnSpeed, segmentSpacing);
        JoinedAt = DateTime.UtcNow;
        LastInputTime = DateTime.UtcNow;
        InputCountResetTime = DateTime.UtcNow;
    }
}
