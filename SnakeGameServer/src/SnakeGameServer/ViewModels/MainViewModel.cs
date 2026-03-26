using System.Collections.ObjectModel;
using System.ComponentModel;
using System.Runtime.CompilerServices;
using System.Windows;
using System.Windows.Input;
using System.Windows.Threading;
using SnakeGameServer.Core;

namespace SnakeGameServer.ViewModels;

/// <summary>
/// ViewModel สำหรับ MainWindow (MVVM Pattern - manual implementation)
/// รองรับระบบ Channel, API Key, และ Client Status
/// </summary>
public class MainViewModel : INotifyPropertyChanged
{
    private readonly GameServer _server;
    private readonly DispatcherTimer _uiTimer;

    // === Server Status ===
    private bool _isRunning;
    private string _statusText = "Stopped";
    private string _statusColor = "#FF5252";

    // === Config: Server ===
    private int _port = 8080;
    private int _maxPlayersPerRoom = 30;
    private int _tickRate = 30;

    // === Config: Channel & Security ===
    private int _maxChannels = 4;
    private string _channelPrefix = "CH";
    private string _apiKey = "";
    private bool _requireApiKey = true;

    // === Config: World ===
    private int _worldSize = 200;
    private int _foodCount = 100;
    private int _foodRespawnThreshold = 80;
    private int _maxFoodSpawnPerTick = 5;

    // === Config: Snake ===
    private double _snakeSpeed = 0.30;
    private double _snakeBoostSpeed = 0.60;
    private double _snakeTurnSpeed = 0.15;
    private int _initialSnakeLength = 5;
    private double _segmentSpacing = 0.5;
    private double _foodCollisionRadius = 0.8;
    private double _snakeCollisionRadius = 0.6;
    private int _scorePerLength = 10;
    private double _boostScoreCost = 2;

    // === Config: Powerup & Death ===
    private int _powerupExpireSeconds = 60;
    private int _maxDroppedFoodOnDeath = 10;
    private int _droppedFoodValue = 2;

    // === Config: Anti-Cheat ===
    private int _maxInputsPerSecond = 60;
    private int _fullSyncInterval = 10;

    // === Config: Database ===
    private string _dbProvider = "SQLite";
    private string _connectionString = "Data Source=snakegame.db";

    // === Statistics ===
    private int _connectedPlayers;
    private int _activeRooms;
    private string _uptime = "00:00:00";
    private int _peakPlayers;
    private int _totalConnections;
    private int _rejectedConnections;
    private double _tickDurationMs;
    private long _tickCount;

    // === Room & Player & Client ===
    private ObservableCollection<RoomInfo> _rooms = new();
    private ObservableCollection<PlayerInfo> _players = new();
    private ObservableCollection<ClientStatusEntry> _clientStatuses = new();
    private ObservableCollection<ChannelEntry> _channels = new();
    private PlayerInfo? _selectedPlayer;

    // === Log ===
    private ObservableCollection<string> _logEntries = new();

    // ==================== Properties ====================

    public bool IsRunning { get => _isRunning; set => SetField(ref _isRunning, value); }
    public string StatusText { get => _statusText; set => SetField(ref _statusText, value); }
    public string StatusColor { get => _statusColor; set => SetField(ref _statusColor, value); }

    // Server
    public int Port { get => _port; set => SetField(ref _port, value); }
    public int MaxPlayersPerRoom { get => _maxPlayersPerRoom; set => SetField(ref _maxPlayersPerRoom, value); }
    public int TickRate { get => _tickRate; set => SetField(ref _tickRate, value); }

    // Channel & Security
    public int MaxChannels { get => _maxChannels; set => SetField(ref _maxChannels, value); }
    public string ChannelPrefix { get => _channelPrefix; set => SetField(ref _channelPrefix, value); }
    public string ApiKey { get => _apiKey; set => SetField(ref _apiKey, value); }
    public bool RequireApiKey { get => _requireApiKey; set => SetField(ref _requireApiKey, value); }

    // World
    public int WorldSize { get => _worldSize; set => SetField(ref _worldSize, value); }
    public int FoodCount { get => _foodCount; set => SetField(ref _foodCount, value); }
    public int FoodRespawnThreshold { get => _foodRespawnThreshold; set => SetField(ref _foodRespawnThreshold, value); }
    public int MaxFoodSpawnPerTick { get => _maxFoodSpawnPerTick; set => SetField(ref _maxFoodSpawnPerTick, value); }

    // Snake
    public double SnakeSpeed { get => _snakeSpeed; set => SetField(ref _snakeSpeed, value); }
    public double SnakeBoostSpeed { get => _snakeBoostSpeed; set => SetField(ref _snakeBoostSpeed, value); }
    public double SnakeTurnSpeed { get => _snakeTurnSpeed; set => SetField(ref _snakeTurnSpeed, value); }
    public int InitialSnakeLength { get => _initialSnakeLength; set => SetField(ref _initialSnakeLength, value); }
    public double SegmentSpacing { get => _segmentSpacing; set => SetField(ref _segmentSpacing, value); }
    public double FoodCollisionRadius { get => _foodCollisionRadius; set => SetField(ref _foodCollisionRadius, value); }
    public double SnakeCollisionRadius { get => _snakeCollisionRadius; set => SetField(ref _snakeCollisionRadius, value); }
    public int ScorePerLength { get => _scorePerLength; set => SetField(ref _scorePerLength, value); }
    public double BoostScoreCost { get => _boostScoreCost; set => SetField(ref _boostScoreCost, value); }

    // Powerup & Death
    public int PowerupExpireSeconds { get => _powerupExpireSeconds; set => SetField(ref _powerupExpireSeconds, value); }
    public int MaxDroppedFoodOnDeath { get => _maxDroppedFoodOnDeath; set => SetField(ref _maxDroppedFoodOnDeath, value); }
    public int DroppedFoodValue { get => _droppedFoodValue; set => SetField(ref _droppedFoodValue, value); }

    // Anti-Cheat
    public int MaxInputsPerSecond { get => _maxInputsPerSecond; set => SetField(ref _maxInputsPerSecond, value); }
    public int FullSyncInterval { get => _fullSyncInterval; set => SetField(ref _fullSyncInterval, value); }

    // Database
    public string DbProvider { get => _dbProvider; set => SetField(ref _dbProvider, value); }
    public string ConnectionString { get => _connectionString; set => SetField(ref _connectionString, value); }

    // Statistics
    public int ConnectedPlayers { get => _connectedPlayers; set => SetField(ref _connectedPlayers, value); }
    public int ActiveRooms { get => _activeRooms; set => SetField(ref _activeRooms, value); }
    public string Uptime { get => _uptime; set => SetField(ref _uptime, value); }
    public int PeakPlayers { get => _peakPlayers; set => SetField(ref _peakPlayers, value); }
    public int TotalConnections { get => _totalConnections; set => SetField(ref _totalConnections, value); }
    public int RejectedConnections { get => _rejectedConnections; set => SetField(ref _rejectedConnections, value); }
    public double TickDurationMs { get => _tickDurationMs; set => SetField(ref _tickDurationMs, value); }
    public long TickCount { get => _tickCount; set => SetField(ref _tickCount, value); }

    // Collections
    public ObservableCollection<RoomInfo> Rooms { get => _rooms; set => SetField(ref _rooms, value); }
    public ObservableCollection<PlayerInfo> Players { get => _players; set => SetField(ref _players, value); }
    public ObservableCollection<ClientStatusEntry> ClientStatuses { get => _clientStatuses; set => SetField(ref _clientStatuses, value); }
    public ObservableCollection<ChannelEntry> Channels { get => _channels; set => SetField(ref _channels, value); }
    public PlayerInfo? SelectedPlayer { get => _selectedPlayer; set { SetField(ref _selectedPlayer, value); KickPlayerCommand?.RaiseCanExecuteChanged(); } }

    public ObservableCollection<string> LogEntries { get => _logEntries; set => SetField(ref _logEntries, value); }

    // ==================== Commands ====================

    public RelayCommand StartCommand { get; }
    public RelayCommand StopCommand { get; }
    public RelayCommand KickPlayerCommand { get; }
    public RelayCommand ClearLogCommand { get; }
    public RelayCommand CopyApiKeyCommand { get; }
    public RelayCommand RegenerateApiKeyCommand { get; }

    public MainViewModel()
    {
        _server = new GameServer();
        _server.OnLog += AddLog;
        _server.OnStateChanged += () => Application.Current?.Dispatcher.Invoke(RefreshUI);

        _server.Loop.OnTickCompleted += (tick, duration) =>
        {
            _tickCount = tick;
            _tickDurationMs = duration;
        };

        // โหลด API Key ที่สุ่มมาให้แสดงใน UI
        _apiKey = _server.Config.ApiKey;

        // Commands
        StartCommand = new RelayCommand(StartServer, () => !IsRunning);
        StopCommand = new RelayCommand(StopServer, () => IsRunning);
        KickPlayerCommand = new RelayCommand(KickPlayer, () => SelectedPlayer != null);

        ClearLogCommand = new RelayCommand(() =>
        {
            Application.Current?.Dispatcher.Invoke(() => LogEntries.Clear());
        });

        CopyApiKeyCommand = new RelayCommand(() =>
        {
            try
            {
                Clipboard.SetText(ApiKey);
                AddLog($"API Key copied to clipboard!");
            }
            catch { }
        });

        RegenerateApiKeyCommand = new RelayCommand(() =>
        {
            _server.RegenerateApiKey();
            ApiKey = _server.Config.ApiKey;
            AddLog($"API Key regenerated: {ApiKey}");
        });

        // UI refresh timer (1 Hz)
        _uiTimer = new DispatcherTimer
        {
            Interval = TimeSpan.FromSeconds(1)
        };
        _uiTimer.Tick += (_, _) => RefreshUI();
        _uiTimer.Start();
    }

    // ==================== Commands ====================

    private async void StartServer()
    {
        _server.UpdateConfig(c =>
        {
            // Server
            c.Port = Port;
            c.MaxPlayersPerRoom = MaxPlayersPerRoom;
            c.TickRate = TickRate;

            // Channel & Security
            c.MaxChannels = MaxChannels;
            c.ChannelPrefix = ChannelPrefix;
            c.RequireApiKey = RequireApiKey;
            // API Key มีค่าอยู่แล้วจาก constructor

            // World
            c.WorldSize = WorldSize;
            c.InitialFoodCount = FoodCount;
            c.FoodRespawnThreshold = FoodRespawnThreshold;
            c.MaxFoodSpawnPerTick = MaxFoodSpawnPerTick;

            // Snake
            c.SnakeSpeed = SnakeSpeed;
            c.SnakeBoostSpeed = SnakeBoostSpeed;
            c.SnakeTurnSpeed = SnakeTurnSpeed;
            c.InitialSnakeLength = InitialSnakeLength;
            c.SegmentSpacing = SegmentSpacing;
            c.FoodCollisionRadius = FoodCollisionRadius;
            c.SnakeCollisionRadius = SnakeCollisionRadius;
            c.ScorePerLength = ScorePerLength;
            c.BoostScoreCostPerSecond = BoostScoreCost;

            // Powerup & Death
            c.PowerupExpireSeconds = PowerupExpireSeconds;
            c.MaxDroppedFoodOnDeath = MaxDroppedFoodOnDeath;
            c.DroppedFoodValue = DroppedFoodValue;

            // Anti-Cheat
            c.MaxInputsPerSecond = MaxInputsPerSecond;
            c.FullSyncInterval = FullSyncInterval;

            // Database
            c.DbProvider = DbProvider.ToLower();
            c.ConnectionString = ConnectionString;
        });

        try
        {
            await _server.StartAsync();
        }
        catch (Exception ex)
        {
            AddLog($"ERROR: {ex.Message}");
            MessageBox.Show($"Failed to start server:\n{ex.Message}", "Error",
                MessageBoxButton.OK, MessageBoxImage.Error);
        }
    }

    private async void StopServer()
    {
        try
        {
            await _server.StopAsync();
        }
        catch (Exception ex)
        {
            AddLog($"ERROR stopping: {ex.Message}");
        }
    }

    private async void KickPlayer()
    {
        if (SelectedPlayer == null) return;

        try
        {
            await _server.KickPlayerAsync(SelectedPlayer.PlayerId);
            AddLog($"Kicked player: {SelectedPlayer.Name}");
        }
        catch (Exception ex)
        {
            AddLog($"Kick failed: {ex.Message}");
        }
    }

    // ==================== UI Refresh ====================

    private void RefreshUI()
    {
        IsRunning = _server.IsRunning;
        StatusText = IsRunning ? "Running" : "Stopped";
        StatusColor = IsRunning ? "#4CAF50" : "#FF5252";

        ConnectedPlayers = _server.Rooms.TotalPlayers;
        ActiveRooms = _server.Rooms.ActiveRooms;
        PeakPlayers = _server.PeakPlayers;
        TotalConnections = _server.TotalConnections;
        RejectedConnections = _server.RejectedConnections;
        TickCount = _server.Loop.TickCount;
        TickDurationMs = _server.Loop.LastTickDurationMs;
        ApiKey = _server.Config.ApiKey;

        if (IsRunning)
        {
            Uptime = _server.Uptime.ToString(@"hh\:mm\:ss");
        }

        // Update channels
        Channels.Clear();
        foreach (var ch in _server.Config.GetChannelNames())
        {
            Channels.Add(new ChannelEntry
            {
                Name = ch,
                PlayerCount = _server.Rooms.GetChannelPlayerCount(ch),
                RoomCount = _server.Rooms.GetChannelRoomCount(ch)
            });
        }

        // Update rooms (เพิ่ม channel info)
        Rooms.Clear();
        foreach (var room in _server.Rooms.GetAllRooms())
        {
            Rooms.Add(new RoomInfo
            {
                RoomCode = room.RoomCode,
                Channel = room.Channel,
                PlayerCount = room.Players.Count,
                MaxPlayers = _server.Config.MaxPlayersPerRoom,
                Status = room.Status.ToString()
            });
        }

        // Update players
        Players.Clear();
        foreach (var room in _server.Rooms.GetActiveRooms())
        {
            foreach (var player in room.Players.Values)
            {
                Players.Add(new PlayerInfo
                {
                    PlayerId = player.Id,
                    Name = player.Name,
                    Skin = player.Skin,
                    Channel = room.Channel,
                    RoomCode = room.RoomCode,
                    Score = player.Snake.Score,
                    Length = player.Snake.Length,
                    IsAlive = player.Snake.IsAlive,
                    Kills = player.Kills,
                    ConnectedTime = player.PlayDuration.ToString(@"mm\:ss"),
                    Endpoint = player.Connection?.RemoteEndpoint ?? "N/A"
                });
            }
        }

        // Update client statuses
        ClientStatuses.Clear();
        foreach (var cs in _server.GetClientStatuses())
        {
            ClientStatuses.Add(new ClientStatusEntry
            {
                ClientId = cs.ClientId,
                IpAddress = cs.IpAddress,
                Status = cs.Status,
                IsOnline = cs.IsOnline,
                IsAuthenticated = cs.IsAuthenticated,
                PlayerName = cs.PlayerName ?? "-",
                Channel = cs.Channel ?? "-",
                RoomCode = cs.RoomCode ?? "-",
                ConnectedDuration = cs.ConnectionDuration.ToString(@"mm\:ss"),
                OnlineIndicator = cs.IsOnline ? "Online" : "Offline"
            });
        }

        // Notify command state
        StartCommand.RaiseCanExecuteChanged();
        StopCommand.RaiseCanExecuteChanged();
        KickPlayerCommand.RaiseCanExecuteChanged();
    }

    private void AddLog(string message)
    {
        Application.Current?.Dispatcher.Invoke(() =>
        {
            LogEntries.Add(message);

            // จำกัด log entries ไม่เกิน 500
            while (LogEntries.Count > 500)
            {
                LogEntries.RemoveAt(0);
            }
        });
    }

    // ==================== INotifyPropertyChanged ====================

    public event PropertyChangedEventHandler? PropertyChanged;

    protected void OnPropertyChanged([CallerMemberName] string? propertyName = null)
    {
        PropertyChanged?.Invoke(this, new PropertyChangedEventArgs(propertyName));
    }

    protected bool SetField<T>(ref T field, T value, [CallerMemberName] string? propertyName = null)
    {
        if (EqualityComparer<T>.Default.Equals(field, value)) return false;
        field = value;
        OnPropertyChanged(propertyName);
        return true;
    }
}

// ==================== Simple RelayCommand ====================

public class RelayCommand : ICommand
{
    private readonly Action _execute;
    private readonly Func<bool>? _canExecute;

    public RelayCommand(Action execute, Func<bool>? canExecute = null)
    {
        _execute = execute ?? throw new ArgumentNullException(nameof(execute));
        _canExecute = canExecute;
    }

    public event EventHandler? CanExecuteChanged;

    public bool CanExecute(object? parameter) => _canExecute?.Invoke() ?? true;
    public void Execute(object? parameter) => _execute();
    public void RaiseCanExecuteChanged() => CanExecuteChanged?.Invoke(this, EventArgs.Empty);
}

// ==================== Data Models for UI ====================

public class RoomInfo
{
    public string RoomCode { get; set; } = "";
    public string Channel { get; set; } = "";
    public int PlayerCount { get; set; }
    public int MaxPlayers { get; set; }
    public string Status { get; set; } = "";
    public string Display => $"[{Channel}] {RoomCode} ({PlayerCount}/{MaxPlayers}) - {Status}";
}

public class PlayerInfo
{
    public string PlayerId { get; set; } = "";
    public string Name { get; set; } = "";
    public string Skin { get; set; } = "";
    public string Channel { get; set; } = "";
    public string RoomCode { get; set; } = "";
    public int Score { get; set; }
    public int Length { get; set; }
    public bool IsAlive { get; set; }
    public int Kills { get; set; }
    public string ConnectedTime { get; set; } = "";
    public string Endpoint { get; set; } = "";
}

public class ChannelEntry
{
    public string Name { get; set; } = "";
    public int PlayerCount { get; set; }
    public int RoomCount { get; set; }
    public string Display => $"{Name} ({PlayerCount} players, {RoomCount} rooms)";
}

public class ClientStatusEntry
{
    public string ClientId { get; set; } = "";
    public string IpAddress { get; set; } = "";
    public string Status { get; set; } = "";
    public bool IsOnline { get; set; }
    public bool IsAuthenticated { get; set; }
    public string PlayerName { get; set; } = "-";
    public string Channel { get; set; } = "-";
    public string RoomCode { get; set; } = "-";
    public string ConnectedDuration { get; set; } = "";
    public string OnlineIndicator { get; set; } = "";
}
