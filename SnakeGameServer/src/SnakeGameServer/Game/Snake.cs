namespace SnakeGameServer.Game;

/// <summary>
/// Snake entity — จัดการ position, segments, movement บน server
/// Server-authoritative: server คำนวณตำแหน่งทั้งหมด
/// </summary>
public class Snake
{
    /// <summary>
    /// รายการ segments (index 0 = head)
    /// </summary>
    public List<Vec3> Segments { get; } = new();

    /// <summary>
    /// ทิศทางปัจจุบัน (normalized)
    /// </summary>
    public Vec3 Direction { get; set; } = Vec3.Right;

    /// <summary>
    /// ทิศทางเป้าหมายจาก client input
    /// </summary>
    public Vec3 TargetDirection { get; set; } = Vec3.Right;

    /// <summary>
    /// ความยาวเป้าหมาย (จำนวน segments)
    /// </summary>
    public int Length { get; set; }

    /// <summary>
    /// คะแนนปัจจุบัน
    /// </summary>
    public int Score { get; set; }

    /// <summary>
    /// ยังมีชีวิตอยู่?
    /// </summary>
    public bool IsAlive { get; set; } = true;

    /// <summary>
    /// กำลังบูสท์?
    /// </summary>
    public bool IsBoosting { get; set; }

    // === Config values (set from ServerConfig) ===
    public double Speed { get; set; } = 0.30;
    public double BoostSpeed { get; set; } = 0.60;
    public double TurnSpeed { get; set; } = 0.15;
    public double SegmentSpacing { get; set; } = 0.5;

    // === Powerups ===
    public DateTime? MagnetExpiry { get; set; }
    public DateTime? SpeedExpiry { get; set; }
    public DateTime? MultiplierExpiry { get; set; }

    // === Boost cost tracking ===
    private double _boostCostAccumulator;

    /// <summary>
    /// มี score multiplier อยู่?
    /// </summary>
    public bool HasMultiplier => MultiplierExpiry.HasValue && MultiplierExpiry > DateTime.UtcNow;

    /// <summary>
    /// มี speed boost powerup อยู่?
    /// </summary>
    public bool HasSpeedBoost => SpeedExpiry.HasValue && SpeedExpiry > DateTime.UtcNow;

    /// <summary>
    /// มี magnet อยู่?
    /// </summary>
    public bool HasMagnet => MagnetExpiry.HasValue && MagnetExpiry > DateTime.UtcNow;

    /// <summary>
    /// Head position (segment แรก)
    /// </summary>
    public Vec3 HeadPosition => Segments.Count > 0 ? Segments[0] : Vec3.Zero;

    /// <summary>
    /// สร้าง snake ที่ตำแหน่งเริ่มต้น
    /// </summary>
    public Snake(double x, double z, int initialLength = 5, double speed = 0.30,
                 double boostSpeed = 0.60, double turnSpeed = 0.15, double segmentSpacing = 0.5)
    {
        Length = initialLength;
        Speed = speed;
        BoostSpeed = boostSpeed;
        TurnSpeed = turnSpeed;
        SegmentSpacing = segmentSpacing;

        // สร้าง segments เรียงจาก head ไปทางซ้าย
        for (int i = 0; i < initialLength; i++)
        {
            Segments.Add(new Vec3(x - i * segmentSpacing, 0.5, z));
        }
    }

    /// <summary>
    /// อัพเดท snake ทุก tick (เรียกจาก game loop)
    /// </summary>
    /// <param name="boostCostPerTick">score ที่ใช้ต่อ tick เมื่อ boost</param>
    public void Tick(double boostCostPerTick)
    {
        if (!IsAlive) return;

        // 1. Smooth turn ไปหาทิศทางเป้าหมาย
        var target = TargetDirection.Normalized();
        Direction = Vec3.Lerp(Direction, target, TurnSpeed).Normalized();

        // 2. คำนวณ speed
        double currentSpeed = Speed;
        if (IsBoosting) currentSpeed = BoostSpeed;
        if (HasSpeedBoost) currentSpeed *= 1.5; // powerup boost

        // 3. คำนวณ head position ใหม่
        var newHead = Segments[0] + Direction * currentSpeed;
        newHead = new Vec3(newHead.X, 0.5, newHead.Z); // lock Y to ground

        // 4. Insert head ใหม่ ลบ tail ถ้ายาวเกิน
        Segments.Insert(0, newHead);
        while (Segments.Count > Length)
        {
            Segments.RemoveAt(Segments.Count - 1);
        }

        // 5. Boost cost
        if (IsBoosting && Score > 0)
        {
            _boostCostAccumulator += boostCostPerTick;
            if (_boostCostAccumulator >= 1.0)
            {
                int cost = (int)_boostCostAccumulator;
                Score = Math.Max(0, Score - cost);
                _boostCostAccumulator -= cost;

                // อัพเดท length ตาม score
                RecalculateLength();
            }
        }

        // 6. Clear expired powerups
        ClearExpiredPowerups();
    }

    /// <summary>
    /// เพิ่ม score และคำนวณ length ใหม่
    /// </summary>
    public void AddScore(int amount)
    {
        if (HasMultiplier) amount *= 2;
        Score += amount;
        RecalculateLength();
    }

    /// <summary>
    /// คำนวณ length จาก score
    /// </summary>
    private void RecalculateLength()
    {
        // ทุก 10 score = +1 length, เริ่มจาก 5
        int newLength = 5 + (Score / 10);
        Length = Math.Max(Length, newLength); // length ไม่ลดลง (ยกเว้น boost)
    }

    /// <summary>
    /// ลบ powerups ที่หมดอายุ
    /// </summary>
    private void ClearExpiredPowerups()
    {
        var now = DateTime.UtcNow;
        if (MagnetExpiry.HasValue && MagnetExpiry < now) MagnetExpiry = null;
        if (SpeedExpiry.HasValue && SpeedExpiry < now) SpeedExpiry = null;
        if (MultiplierExpiry.HasValue && MultiplierExpiry < now) MultiplierExpiry = null;
    }

    /// <summary>
    /// Apply powerup
    /// </summary>
    public void ApplyPowerup(string type, int durationSeconds = 60)
    {
        var expiry = DateTime.UtcNow.AddSeconds(durationSeconds);
        switch (type)
        {
            case "magnet":
                MagnetExpiry = expiry;
                break;
            case "speed":
                SpeedExpiry = expiry;
                break;
            case "multiplier":
                MultiplierExpiry = expiry;
                break;
        }
    }
}
