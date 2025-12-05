using System.Text.Json;

namespace ThaipromptAffiliateApp.Services;

/// <summary>
/// บริการจัดการการตั้งค่าแอป
/// ใช้ MAUI Preferences สำหรับเก็บค่าลง Storage
/// </summary>
public class SettingsService : ISettingsService
{
    // ============================================
    // CONSTANTS - DEFAULT VALUES
    // ============================================

    /// <summary>
    /// ค่าเริ่มต้นของการตั้งค่าต่างๆ
    /// </summary>
    private static readonly Dictionary<string, object> DefaultSettings = new()
    {
        // การแจ้งเตือน
        { "push_notification", true },
        { "notification_sound", true },
        { "vibration", true },
        { "order_updates", true },
        { "commission_alerts", true },

        // ธีมและการแสดงผล
        { "dark_mode", true },
        { "animations", true },
        { "language", "ไทย" },

        // ความเป็นส่วนตัว
        { "biometric_login", false },
        { "auto_login", true },
        { "analytics", true },

        // ข้อมูลและแคช
        { "wifi_only", false },
        { "search_history", new List<string>() },
        { "max_cache_size_mb", 100 },
    };

    /// <summary>
    /// Prefix สำหรับ key ทั้งหมด
    /// </summary>
    private const string KeyPrefix = "tpaffiliate_";

    // ============================================
    // GET SETTINGS
    // ============================================

    /// <summary>
    /// ดึงค่าตั้งค่า boolean
    /// </summary>
    public bool GetSetting(string key, bool defaultValue)
    {
        try
        {
            return Preferences.Default.Get(GetFullKey(key), defaultValue);
        }
        catch
        {
            return defaultValue;
        }
    }

    /// <summary>
    /// ดึงค่าตั้งค่า string
    /// </summary>
    public string GetSetting(string key, string defaultValue)
    {
        try
        {
            return Preferences.Default.Get(GetFullKey(key), defaultValue);
        }
        catch
        {
            return defaultValue;
        }
    }

    /// <summary>
    /// ดึงค่าตั้งค่า int
    /// </summary>
    public int GetSetting(string key, int defaultValue)
    {
        try
        {
            return Preferences.Default.Get(GetFullKey(key), defaultValue);
        }
        catch
        {
            return defaultValue;
        }
    }

    /// <summary>
    /// ดึงค่าตั้งค่า object (deserialize from JSON)
    /// </summary>
    public T GetSetting<T>(string key, T defaultValue)
    {
        try
        {
            var json = Preferences.Default.Get<string?>(GetFullKey(key), null);
            if (string.IsNullOrEmpty(json))
                return defaultValue;

            var result = JsonSerializer.Deserialize<T>(json);
            return result ?? defaultValue;
        }
        catch
        {
            return defaultValue;
        }
    }

    // ============================================
    // SET SETTINGS
    // ============================================

    /// <summary>
    /// บันทึกค่าตั้งค่า boolean
    /// </summary>
    public void SetSetting(string key, bool value)
    {
        try
        {
            Preferences.Default.Set(GetFullKey(key), value);
        }
        catch (Exception ex)
        {
            System.Diagnostics.Debug.WriteLine($"บันทึกค่า {key} ผิดพลาด: {ex.Message}");
        }
    }

    /// <summary>
    /// บันทึกค่าตั้งค่า string
    /// </summary>
    public void SetSetting(string key, string value)
    {
        try
        {
            Preferences.Default.Set(GetFullKey(key), value);
        }
        catch (Exception ex)
        {
            System.Diagnostics.Debug.WriteLine($"บันทึกค่า {key} ผิดพลาด: {ex.Message}");
        }
    }

    /// <summary>
    /// บันทึกค่าตั้งค่า int
    /// </summary>
    public void SetSetting(string key, int value)
    {
        try
        {
            Preferences.Default.Set(GetFullKey(key), value);
        }
        catch (Exception ex)
        {
            System.Diagnostics.Debug.WriteLine($"บันทึกค่า {key} ผิดพลาด: {ex.Message}");
        }
    }

    /// <summary>
    /// บันทึกค่าตั้งค่า object (serialize to JSON)
    /// </summary>
    public void SetSetting<T>(string key, T value)
    {
        try
        {
            var json = JsonSerializer.Serialize(value);
            Preferences.Default.Set(GetFullKey(key), json);
        }
        catch (Exception ex)
        {
            System.Diagnostics.Debug.WriteLine($"บันทึกค่า {key} ผิดพลาด: {ex.Message}");
        }
    }

    // ============================================
    // OTHER OPERATIONS
    // ============================================

    /// <summary>
    /// ลบค่าตั้งค่า
    /// </summary>
    public void RemoveSetting(string key)
    {
        try
        {
            Preferences.Default.Remove(GetFullKey(key));
        }
        catch (Exception ex)
        {
            System.Diagnostics.Debug.WriteLine($"ลบค่า {key} ผิดพลาด: {ex.Message}");
        }
    }

    /// <summary>
    /// ตรวจสอบว่ามี key อยู่หรือไม่
    /// </summary>
    public bool HasSetting(string key)
    {
        return Preferences.Default.ContainsKey(GetFullKey(key));
    }

    /// <summary>
    /// ล้างค่าตั้งค่าทั้งหมด
    /// </summary>
    public async Task ClearAllSettingsAsync()
    {
        try
        {
            // ล้าง Preferences ทั้งหมด
            Preferences.Default.Clear();

            System.Diagnostics.Debug.WriteLine("ล้างค่าตั้งค่าทั้งหมดแล้ว");
            await Task.CompletedTask;
        }
        catch (Exception ex)
        {
            System.Diagnostics.Debug.WriteLine($"ล้างค่าตั้งค่าผิดพลาด: {ex.Message}");
        }
    }

    /// <summary>
    /// รีเซ็ตค่าตั้งค่าเป็นค่าเริ่มต้น
    /// </summary>
    public async Task ResetAllSettingsAsync()
    {
        try
        {
            // ลบค่าที่มีอยู่ก่อน
            await ClearAllSettingsAsync();

            // ตั้งค่า default ใหม่ทั้งหมด
            foreach (var setting in DefaultSettings)
            {
                switch (setting.Value)
                {
                    case bool boolValue:
                        SetSetting(setting.Key, boolValue);
                        break;
                    case string stringValue:
                        SetSetting(setting.Key, stringValue);
                        break;
                    case int intValue:
                        SetSetting(setting.Key, intValue);
                        break;
                    default:
                        SetSetting(setting.Key, setting.Value);
                        break;
                }
            }

            System.Diagnostics.Debug.WriteLine("รีเซ็ตค่าตั้งค่าเป็นค่าเริ่มต้นแล้ว");
        }
        catch (Exception ex)
        {
            System.Diagnostics.Debug.WriteLine($"รีเซ็ตค่าตั้งค่าผิดพลาด: {ex.Message}");
        }
    }

    // ============================================
    // CACHE MANAGEMENT
    // ============================================

    /// <summary>
    /// ดึงขนาดแคชทั้งหมด
    /// </summary>
    public async Task<long> GetCacheSizeAsync()
    {
        try
        {
            long totalSize = 0;

            // Cache directory
            var cacheDir = FileSystem.Current.CacheDirectory;
            if (Directory.Exists(cacheDir))
            {
                totalSize += await GetDirectorySizeAsync(cacheDir);
            }

            // App data directory (บางส่วน)
            var appDataDir = FileSystem.Current.AppDataDirectory;
            var tempDir = Path.Combine(appDataDir, "temp");
            if (Directory.Exists(tempDir))
            {
                totalSize += await GetDirectorySizeAsync(tempDir);
            }

            return totalSize;
        }
        catch (Exception ex)
        {
            System.Diagnostics.Debug.WriteLine($"คำนวณขนาดแคชผิดพลาด: {ex.Message}");
            return 0;
        }
    }

    /// <summary>
    /// คำนวณขนาดของ directory
    /// </summary>
    private static async Task<long> GetDirectorySizeAsync(string path)
    {
        return await Task.Run(() =>
        {
            try
            {
                var dirInfo = new DirectoryInfo(path);
                return dirInfo.EnumerateFiles("*", SearchOption.AllDirectories)
                    .Sum(file => file.Length);
            }
            catch
            {
                return 0;
            }
        });
    }

    /// <summary>
    /// ล้างแคชทั้งหมด
    /// </summary>
    public async Task ClearCacheAsync()
    {
        try
        {
            // ล้าง cache directory
            var cacheDir = FileSystem.Current.CacheDirectory;
            if (Directory.Exists(cacheDir))
            {
                await Task.Run(() =>
                {
                    foreach (var file in Directory.GetFiles(cacheDir, "*", SearchOption.AllDirectories))
                    {
                        try
                        {
                            File.Delete(file);
                        }
                        catch { }
                    }

                    foreach (var dir in Directory.GetDirectories(cacheDir))
                    {
                        try
                        {
                            Directory.Delete(dir, true);
                        }
                        catch { }
                    }
                });
            }

            // ล้าง temp directory
            var appDataDir = FileSystem.Current.AppDataDirectory;
            var tempDir = Path.Combine(appDataDir, "temp");
            if (Directory.Exists(tempDir))
            {
                await Task.Run(() =>
                {
                    try
                    {
                        Directory.Delete(tempDir, true);
                    }
                    catch { }
                });
            }

            System.Diagnostics.Debug.WriteLine("ล้างแคชทั้งหมดแล้ว");
        }
        catch (Exception ex)
        {
            System.Diagnostics.Debug.WriteLine($"ล้างแคชผิดพลาด: {ex.Message}");
            throw;
        }
    }

    // ============================================
    // IMPORT / EXPORT
    // ============================================

    /// <summary>
    /// Export การตั้งค่าทั้งหมดเป็น JSON
    /// </summary>
    public string ExportSettings()
    {
        try
        {
            var settings = new Dictionary<string, object>();

            foreach (var key in DefaultSettings.Keys)
            {
                if (DefaultSettings[key] is bool)
                {
                    settings[key] = GetSetting(key, (bool)DefaultSettings[key]);
                }
                else if (DefaultSettings[key] is string)
                {
                    settings[key] = GetSetting(key, (string)DefaultSettings[key]);
                }
                else if (DefaultSettings[key] is int)
                {
                    settings[key] = GetSetting(key, (int)DefaultSettings[key]);
                }
                else
                {
                    settings[key] = GetSetting(key, DefaultSettings[key]);
                }
            }

            return JsonSerializer.Serialize(settings, new JsonSerializerOptions
            {
                WriteIndented = true
            });
        }
        catch (Exception ex)
        {
            System.Diagnostics.Debug.WriteLine($"Export การตั้งค่าผิดพลาด: {ex.Message}");
            return "{}";
        }
    }

    /// <summary>
    /// Import การตั้งค่าจาก JSON
    /// </summary>
    public bool ImportSettings(string json)
    {
        try
        {
            var settings = JsonSerializer.Deserialize<Dictionary<string, JsonElement>>(json);
            if (settings == null) return false;

            foreach (var setting in settings)
            {
                switch (setting.Value.ValueKind)
                {
                    case JsonValueKind.True:
                    case JsonValueKind.False:
                        SetSetting(setting.Key, setting.Value.GetBoolean());
                        break;
                    case JsonValueKind.String:
                        SetSetting(setting.Key, setting.Value.GetString() ?? "");
                        break;
                    case JsonValueKind.Number:
                        SetSetting(setting.Key, setting.Value.GetInt32());
                        break;
                    default:
                        SetSetting(setting.Key, setting.Value.ToString());
                        break;
                }
            }

            System.Diagnostics.Debug.WriteLine("Import การตั้งค่าสำเร็จ");
            return true;
        }
        catch (Exception ex)
        {
            System.Diagnostics.Debug.WriteLine($"Import การตั้งค่าผิดพลาด: {ex.Message}");
            return false;
        }
    }

    // ============================================
    // HELPER METHODS
    // ============================================

    /// <summary>
    /// สร้าง full key พร้อม prefix
    /// </summary>
    private static string GetFullKey(string key)
    {
        return $"{KeyPrefix}{key}";
    }
}
