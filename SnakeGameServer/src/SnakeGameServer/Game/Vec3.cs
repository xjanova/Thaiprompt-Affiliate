using System.Text.Json.Serialization;

namespace SnakeGameServer.Game;

/// <summary>
/// Lightweight 3D vector struct สำหรับ position และ direction
/// </summary>
public struct Vec3
{
    [JsonPropertyName("x")]
    public double X { get; set; }

    [JsonPropertyName("y")]
    public double Y { get; set; }

    [JsonPropertyName("z")]
    public double Z { get; set; }

    public Vec3(double x, double y, double z)
    {
        X = x;
        Y = y;
        Z = z;
    }

    /// <summary>
    /// คำนวณระยะทาง 3 มิติ
    /// </summary>
    public double DistanceTo(Vec3 other)
    {
        var dx = X - other.X;
        var dy = Y - other.Y;
        var dz = Z - other.Z;
        return Math.Sqrt(dx * dx + dy * dy + dz * dz);
    }

    /// <summary>
    /// คำนวณระยะทาง 2 มิติ (X, Z) — ใช้สำหรับ game plane
    /// </summary>
    public double DistanceTo2D(Vec3 other)
    {
        var dx = X - other.X;
        var dz = Z - other.Z;
        return Math.Sqrt(dx * dx + dz * dz);
    }

    /// <summary>
    /// ความยาวของ vector
    /// </summary>
    public double Length() => Math.Sqrt(X * X + Y * Y + Z * Z);

    /// <summary>
    /// ทำให้เป็น unit vector (ความยาว = 1)
    /// </summary>
    public Vec3 Normalized()
    {
        var len = Length();
        if (len < 0.0001) return new Vec3(1, 0, 0);
        return new Vec3(X / len, Y / len, Z / len);
    }

    /// <summary>
    /// Lerp ระหว่าง 2 vectors
    /// </summary>
    public static Vec3 Lerp(Vec3 a, Vec3 b, double t)
    {
        return new Vec3(
            a.X + (b.X - a.X) * t,
            a.Y + (b.Y - a.Y) * t,
            a.Z + (b.Z - a.Z) * t
        );
    }

    public static Vec3 operator +(Vec3 a, Vec3 b) => new(a.X + b.X, a.Y + b.Y, a.Z + b.Z);
    public static Vec3 operator -(Vec3 a, Vec3 b) => new(a.X - b.X, a.Y - b.Y, a.Z - b.Z);
    public static Vec3 operator *(Vec3 v, double s) => new(v.X * s, v.Y * s, v.Z * s);

    public static Vec3 Zero => new(0, 0, 0);
    public static Vec3 Right => new(1, 0, 0);
    public static Vec3 Forward => new(0, 0, 1);

    public override string ToString() => $"({X:F2}, {Y:F2}, {Z:F2})";
}
