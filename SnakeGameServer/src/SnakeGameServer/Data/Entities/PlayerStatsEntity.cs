using System.ComponentModel.DataAnnotations;
using System.ComponentModel.DataAnnotations.Schema;

namespace SnakeGameServer.Data.Entities;

/// <summary>
/// สถิติผู้เล่น (สะสมตลอด)
/// </summary>
[Table("player_stats")]
public class PlayerStatsEntity
{
    [Key]
    public int Id { get; set; }

    [Required, MaxLength(50)]
    public string PlayerName { get; set; } = "";

    public int TotalGames { get; set; }
    public long TotalScore { get; set; }
    public int HighestScore { get; set; }
    public int HighestLength { get; set; }
    public int TotalKills { get; set; }
    public int TotalDeaths { get; set; }
    public int TotalTimePlayed { get; set; } // วินาที

    public DateTime CreatedAt { get; set; } = DateTime.UtcNow;
    public DateTime UpdatedAt { get; set; } = DateTime.UtcNow;
}

/// <summary>
/// ประวัติเกม (บันทึกทุกครั้งที่ตายหรือออก)
/// </summary>
[Table("game_history")]
public class GameHistoryEntity
{
    [Key]
    public int Id { get; set; }

    [Required, MaxLength(10)]
    public string RoomCode { get; set; } = "";

    [Required, MaxLength(50)]
    public string PlayerName { get; set; } = "";

    public int Score { get; set; }
    public int Length { get; set; }
    public int Duration { get; set; } // วินาที
    public int Kills { get; set; }

    [MaxLength(20)]
    public string DeathReason { get; set; } = ""; // collision, boundary, self, left

    public DateTime PlayedAt { get; set; } = DateTime.UtcNow;
}

/// <summary>
/// Leaderboard (top scores)
/// </summary>
[Table("leaderboard")]
public class LeaderboardEntity
{
    [Key]
    public int Id { get; set; }

    [Required, MaxLength(50)]
    public string PlayerName { get; set; } = "";

    public int Score { get; set; }
    public int Length { get; set; }
    public int Kills { get; set; }
    public int Duration { get; set; } // วินาที

    public DateTime AchievedAt { get; set; } = DateTime.UtcNow;
}

/// <summary>
/// การตั้งค่า server (key-value)
/// </summary>
[Table("server_config")]
public class ServerConfigEntity
{
    [Key]
    public int Id { get; set; }

    [Required, MaxLength(100)]
    public string Key { get; set; } = "";

    [MaxLength(500)]
    public string Value { get; set; } = "";

    public DateTime UpdatedAt { get; set; } = DateTime.UtcNow;
}
