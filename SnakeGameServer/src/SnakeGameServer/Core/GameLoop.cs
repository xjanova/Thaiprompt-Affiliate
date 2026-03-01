using System.Diagnostics;
using SnakeGameServer.Game;

namespace SnakeGameServer.Core;

/// <summary>
/// Game Loop — Fixed-timestep 30 tick/sec
/// รันบน dedicated thread แยกจาก UI
/// </summary>
public class GameLoop
{
    private readonly RoomManager _rooms;
    private volatile bool _running;
    private Thread? _thread;
    private long _tickCount;
    private readonly double _targetTickMs;

    /// <summary>
    /// Event: log
    /// </summary>
    public event Action<string>? OnLog;

    /// <summary>
    /// Event: tick สำเร็จ (สำหรับ monitoring)
    /// </summary>
    public event Action<long, double>? OnTickCompleted;

    /// <summary>
    /// จำนวน tick ที่ผ่านไป
    /// </summary>
    public long TickCount => _tickCount;

    /// <summary>
    /// กำลังรันอยู่?
    /// </summary>
    public bool IsRunning => _running;

    /// <summary>
    /// เวลาประมวลผล tick ล่าสุด (ms)
    /// </summary>
    public double LastTickDurationMs { get; private set; }

    public GameLoop(RoomManager rooms, int tickRate = 30)
    {
        _rooms = rooms;
        _targetTickMs = 1000.0 / tickRate;
    }

    /// <summary>
    /// เริ่ม game loop
    /// </summary>
    public void Start()
    {
        if (_running) return;

        _running = true;
        _tickCount = 0;
        _thread = new Thread(Loop)
        {
            IsBackground = true,
            Name = "GameLoop",
            Priority = ThreadPriority.AboveNormal
        };
        _thread.Start();
        OnLog?.Invoke($"Game loop started ({1000.0 / _targetTickMs:F0} tick/sec, {_targetTickMs:F1}ms/tick)");
    }

    /// <summary>
    /// หยุด game loop
    /// </summary>
    public void Stop()
    {
        _running = false;
        _thread?.Join(2000); // รอ 2 วินาที
        _thread = null;
        OnLog?.Invoke("Game loop stopped");
    }

    /// <summary>
    /// Main loop — fixed-timestep pattern
    /// </summary>
    private void Loop()
    {
        var stopwatch = Stopwatch.StartNew();
        double accumulator = 0;
        long previousTicks = stopwatch.ElapsedTicks;
        double ticksPerMs = Stopwatch.Frequency / 1000.0;

        // Cleanup timer (ทุก 30 วินาที)
        int cleanupInterval = (int)(30000 / _targetTickMs);
        int ticksSinceCleanup = 0;

        while (_running)
        {
            long currentTicks = stopwatch.ElapsedTicks;
            double elapsedMs = (currentTicks - previousTicks) / ticksPerMs;
            previousTicks = currentTicks;

            accumulator += elapsedMs;

            // ป้องกัน spiral of death (ถ้า lag มากกว่า 5 ticks)
            if (accumulator > _targetTickMs * 5)
            {
                accumulator = _targetTickMs;
            }

            while (accumulator >= _targetTickMs)
            {
                var tickStart = stopwatch.ElapsedTicks;

                Tick();

                var tickEnd = stopwatch.ElapsedTicks;
                LastTickDurationMs = (tickEnd - tickStart) / ticksPerMs;
                OnTickCompleted?.Invoke(_tickCount, LastTickDurationMs);

                accumulator -= _targetTickMs;
                _tickCount++;
                ticksSinceCleanup++;
            }

            // Cleanup empty rooms ทุก 30 วินาที
            if (ticksSinceCleanup >= cleanupInterval)
            {
                ticksSinceCleanup = 0;
                _rooms.CleanupEmptyRooms();
            }

            // Sleep เพื่อลด CPU usage
            double sleepMs = _targetTickMs - accumulator;
            if (sleepMs > 2)
            {
                Thread.Sleep((int)(sleepMs - 1));
            }
            else if (sleepMs > 0)
            {
                Thread.SpinWait(100);
            }
        }
    }

    /// <summary>
    /// ประมวลผล 1 tick
    /// </summary>
    private void Tick()
    {
        foreach (var room in _rooms.GetActiveRooms())
        {
            room.Tick(_tickCount);
        }
    }
}
