using Microsoft.EntityFrameworkCore;
using SnakeGameServer.Data.Entities;

namespace SnakeGameServer.Data;

/// <summary>
/// EF Core Database Context — รองรับ SQLite, MySQL, MSSQL
/// </summary>
public class AppDbContext : DbContext
{
    public DbSet<PlayerStatsEntity> PlayerStats => Set<PlayerStatsEntity>();
    public DbSet<GameHistoryEntity> GameHistory => Set<GameHistoryEntity>();
    public DbSet<LeaderboardEntity> Leaderboard => Set<LeaderboardEntity>();
    public DbSet<ServerConfigEntity> ServerConfig => Set<ServerConfigEntity>();

    private readonly string _provider;
    private readonly string _connectionString;

    public AppDbContext(string provider = "sqlite", string connectionString = "Data Source=snakegame.db")
    {
        _provider = provider.ToLower();
        _connectionString = connectionString;
    }

    protected override void OnConfiguring(DbContextOptionsBuilder options)
    {
        switch (_provider)
        {
            case "mysql":
                options.UseMySql(_connectionString, ServerVersion.AutoDetect(_connectionString));
                break;
            case "mssql":
                options.UseSqlServer(_connectionString);
                break;
            default: // sqlite
                options.UseSqlite(_connectionString);
                break;
        }
    }

    protected override void OnModelCreating(ModelBuilder modelBuilder)
    {
        // PlayerStats — unique per player name
        modelBuilder.Entity<PlayerStatsEntity>(entity =>
        {
            entity.HasIndex(e => e.PlayerName).IsUnique();
        });

        // Leaderboard — index on score descending
        modelBuilder.Entity<LeaderboardEntity>(entity =>
        {
            entity.HasIndex(e => e.Score);
        });

        // GameHistory — index on played_at
        modelBuilder.Entity<GameHistoryEntity>(entity =>
        {
            entity.HasIndex(e => e.PlayedAt);
        });

        // ServerConfig — unique key
        modelBuilder.Entity<ServerConfigEntity>(entity =>
        {
            entity.HasIndex(e => e.Key).IsUnique();
        });
    }
}
