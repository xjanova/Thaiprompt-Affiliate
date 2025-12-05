namespace ThaipromptAffiliateApp.Services;

/// <summary>
/// Interface สำหรับจัดการการตั้งค่าแอป
/// รองรับการเก็บค่า boolean, string, int และ object
/// </summary>
public interface ISettingsService
{
    /// <summary>
    /// ดึงค่าตั้งค่า boolean
    /// </summary>
    /// <param name="key">ชื่อ key</param>
    /// <param name="defaultValue">ค่า default ถ้าไม่พบ</param>
    /// <returns>ค่าที่เก็บไว้ หรือ defaultValue</returns>
    bool GetSetting(string key, bool defaultValue);

    /// <summary>
    /// ดึงค่าตั้งค่า string
    /// </summary>
    /// <param name="key">ชื่อ key</param>
    /// <param name="defaultValue">ค่า default ถ้าไม่พบ</param>
    /// <returns>ค่าที่เก็บไว้ หรือ defaultValue</returns>
    string GetSetting(string key, string defaultValue);

    /// <summary>
    /// ดึงค่าตั้งค่า int
    /// </summary>
    /// <param name="key">ชื่อ key</param>
    /// <param name="defaultValue">ค่า default ถ้าไม่พบ</param>
    /// <returns>ค่าที่เก็บไว้ หรือ defaultValue</returns>
    int GetSetting(string key, int defaultValue);

    /// <summary>
    /// ดึงค่าตั้งค่า object (deserialize from JSON)
    /// </summary>
    /// <typeparam name="T">ประเภทของ object</typeparam>
    /// <param name="key">ชื่อ key</param>
    /// <param name="defaultValue">ค่า default ถ้าไม่พบ</param>
    /// <returns>ค่าที่เก็บไว้ หรือ defaultValue</returns>
    T GetSetting<T>(string key, T defaultValue);

    /// <summary>
    /// บันทึกค่าตั้งค่า boolean
    /// </summary>
    /// <param name="key">ชื่อ key</param>
    /// <param name="value">ค่าที่ต้องการเก็บ</param>
    void SetSetting(string key, bool value);

    /// <summary>
    /// บันทึกค่าตั้งค่า string
    /// </summary>
    /// <param name="key">ชื่อ key</param>
    /// <param name="value">ค่าที่ต้องการเก็บ</param>
    void SetSetting(string key, string value);

    /// <summary>
    /// บันทึกค่าตั้งค่า int
    /// </summary>
    /// <param name="key">ชื่อ key</param>
    /// <param name="value">ค่าที่ต้องการเก็บ</param>
    void SetSetting(string key, int value);

    /// <summary>
    /// บันทึกค่าตั้งค่า object (serialize to JSON)
    /// </summary>
    /// <typeparam name="T">ประเภทของ object</typeparam>
    /// <param name="key">ชื่อ key</param>
    /// <param name="value">ค่าที่ต้องการเก็บ</param>
    void SetSetting<T>(string key, T value);

    /// <summary>
    /// ลบค่าตั้งค่า
    /// </summary>
    /// <param name="key">ชื่อ key</param>
    void RemoveSetting(string key);

    /// <summary>
    /// ตรวจสอบว่ามี key อยู่หรือไม่
    /// </summary>
    /// <param name="key">ชื่อ key</param>
    /// <returns>true ถ้ามี key</returns>
    bool HasSetting(string key);

    /// <summary>
    /// ล้างค่าตั้งค่าทั้งหมด
    /// </summary>
    Task ClearAllSettingsAsync();

    /// <summary>
    /// รีเซ็ตค่าตั้งค่าเป็นค่าเริ่มต้น
    /// </summary>
    Task ResetAllSettingsAsync();

    /// <summary>
    /// ดึงขนาดแคชทั้งหมด
    /// </summary>
    /// <returns>ขนาดแคชเป็น bytes</returns>
    Task<long> GetCacheSizeAsync();

    /// <summary>
    /// ล้างแคชทั้งหมด
    /// </summary>
    Task ClearCacheAsync();

    /// <summary>
    /// Export การตั้งค่าทั้งหมดเป็น JSON
    /// </summary>
    /// <returns>JSON string ของการตั้งค่า</returns>
    string ExportSettings();

    /// <summary>
    /// Import การตั้งค่าจาก JSON
    /// </summary>
    /// <param name="json">JSON string ของการตั้งค่า</param>
    /// <returns>true ถ้า import สำเร็จ</returns>
    bool ImportSettings(string json);
}
