using System.Threading.Channels;
using Microsoft.EntityFrameworkCore;
using SnakeGameServer.Data.Entities;

namespace SnakeGameServer.Data;

/// <summary>
/// Async background writer — เขียน DB โดยไม่ block game loop
/// ใช้ Channel เป็น queue
/// </summary>
public class DatabaseWriter : IDisposable
{
    private readonly Channel<Func<AppDbContext, Task>> _channel;
    private readonly string _dbProvider;
    private readonly string _connectionString;
    private readonly CancellationTokenSource _cts = new();
    private Task? _processTask;

    /// <summary>
    /// Event: log
    /// </summary>
    public event Action<string>? OnLog;

    /// <summary>
    /// จำนวน items ใน queue
    /// </summary>
    public int QueueCount { get; private set; }

    public DatabaseWriter(string dbProvider, string connectionString)
    {
        _dbProvider = dbProvider;
        _connectionString = connectionString;
        _channel = Channel.CreateBounded<Func<AppDbContext, Task>>(1000);
    }

    /// <summary>
    /// เริ่ม background writer
    /// </summary>
    public void Start()
    {
        // สร้าง/migrate database
        try
        {
            using var db = CreateContext();
            db.Database.EnsureCreated();
            OnLog?.Invoke($"Database initialized ({_dbProvider}): {_connectionString}");
        }
        catch (Exception ex)
        {
            OnLog?.Invoke($"Database init error: {ex.Message}");
        }

        _processTask = Task.Run(ProcessLoop);
        OnLog?.Invoke("Database writer started");
    }

    /// <summary>
    /// บันทึกประวัติเกม
    /// </summary>
    public void SaveGameHistory(string roomCode, string playerName, int score, int length,
                                 int duration, int kills, string deathReason)
    {
        Enqueue(async db =>
        {
            db.GameHistory.Add(new GameHistoryEntity
            {
                RoomCode = roomCode,
                PlayerName = playerName,
                Score = score,
                Length = length,
                Duration = duration,
                Kills = kills,
                DeathReason = deathReason,
                PlayedAt = DateTime.UtcNow
            });
            await db.SaveChangesAsync();
        });
    }

    /// <summary>
    /// อัพเดทสถิติผู้เล่น
    /// </summary>
    public void UpdatePlayerStats(string playerName, int score, int length, int kills,
                                   int duration)
    {
        Enqueue(async db =>
        {
            var stats = await db.PlayerStats
                .FirstOrDefaultAsync(s => s.PlayerName == playerName);

            if (stats == null)
            {
                stats = new PlayerStatsEntity { PlayerName = playerName };
                db.PlayerStats.Add(stats);
            }

            stats.TotalGames++;
            stats.TotalScore += score;
            stats.TotalKills += kills;
            stats.TotalDeaths++;
            stats.TotalTimePlayed += duration;
            if (score > stats.HighestScore) stats.HighestScore = score;
            if (length > stats.HighestLength) stats.HighestLength = length;
            stats.UpdatedAt = DateTime.UtcNow;

            await db.SaveChangesAsync();
        });
    }

    /// <summary>
    /// บันทึก leaderboard (ถ้า score เข้า top 100)
    /// </summary>
    public void TryAddToLeaderboard(string playerName, int score, int length, int kills,
                                      int duration)
    {
        Enqueue(async db =>
        {
            // เช็คว่า score เข้า top 100 ไหม
            var minScore = db.Leaderboard
                .OrderByDescending(l => l.Score)
                .Skip(99)
                .Select(l => l.Score)
                .FirstOrDefault();

            if (score > minScore || db.Leaderboard.Count() < 100)
            {
                db.Leaderboard.Add(new LeaderboardEntity
                {
                    PlayerName = playerName,
                    Score = score,
                    Length = length,
                    Kills = kills,
                    Duration = duration,
                    AchievedAt = DateTime.UtcNow
                });

                // ลบรายการที่เกิน 100
                var excess = db.Leaderboard
                    .OrderByDescending(l => l.Score)
                    .Skip(100)
                    .ToList();

                if (excess.Any())
                    db.Leaderboard.RemoveRange(excess);

                await db.SaveChangesAsync();
            }
        });
    }

    /// <summary>
    /// ดึง top leaderboard
    /// </summary>
    public async Task<List<LeaderboardEntity>> GetLeaderboardAsync(int top = 50)
    {
        using var db = CreateContext();
        return await db.Leaderboard
            .OrderByDescending(l => l.Score)
            .Take(top)
            .ToListAsync();
    }

    /// <summary>
    /// ดึงสถิติผู้เล่น
    /// </summary>
    public async Task<PlayerStatsEntity?> GetPlayerStatsAsync(string playerName)
    {
        using var db = CreateContext();
        return await db.PlayerStats
            .FirstOrDefaultAsync(s => s.PlayerName == playerName);
    }

    // === Internal ===

    private void Enqueue(Func<AppDbContext, Task> action)
    {
        if (!_channel.Writer.TryWrite(action))
        {
            OnLog?.Invoke("Database write queue full! Dropping write.");
        }
    }

    private async Task ProcessLoop()
    {
        await foreach (var action in _channel.Reader.ReadAllAsync(_cts.Token))
        {
            try
            {
                using var db = CreateContext();
                await action(db);
                QueueCount = _channel.Reader.Count;
            }
            catch (OperationCanceledException)
            {
                break;
            }
            catch (Exception ex)
            {
                OnLog?.Invoke($"Database write error: {ex.Message}");
            }
        }
    }

    private AppDbContext CreateContext()
    {
        return new AppDbContext(_dbProvider, _connectionString);
    }

    public void Dispose()
    {
        _cts.Cancel();
        _channel.Writer.Complete();
        _processTask?.Wait(3000);
        _cts.Dispose();
    }
}
