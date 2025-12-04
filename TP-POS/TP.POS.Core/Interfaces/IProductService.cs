using TP.POS.Core.Entities;

namespace TP.POS.Core.Interfaces;

/// <summary>
/// Service สำหรับจัดการสินค้า
/// </summary>
public interface IProductService
{
    /// <summary>
    /// ดึงสินค้าทั้งหมด
    /// </summary>
    Task<List<Product>> GetAllAsync();

    /// <summary>
    /// ดึงสินค้าตาม ID
    /// </summary>
    Task<Product?> GetByIdAsync(int id);

    /// <summary>
    /// ดึงสินค้าตามบาร์โค้ด
    /// </summary>
    Task<Product?> GetByBarcodeAsync(string barcode);

    /// <summary>
    /// ดึงสินค้าตาม SKU
    /// </summary>
    Task<Product?> GetBySkuAsync(string sku);

    /// <summary>
    /// ค้นหาสินค้า
    /// </summary>
    Task<List<Product>> SearchAsync(string keyword, int? categoryId = null, int limit = 50);

    /// <summary>
    /// ดึงสินค้าตามหมวดหมู่
    /// </summary>
    Task<List<Product>> GetByCategoryAsync(int categoryId);

    /// <summary>
    /// ดึงหมวดหมู่ทั้งหมด
    /// </summary>
    Task<List<Category>> GetCategoriesAsync();

    /// <summary>
    /// เพิ่มสินค้าใหม่
    /// </summary>
    Task<Product> CreateAsync(Product product);

    /// <summary>
    /// อัพเดทสินค้า
    /// </summary>
    Task<Product> UpdateAsync(Product product);

    /// <summary>
    /// ลบสินค้า
    /// </summary>
    Task<bool> DeleteAsync(int id);

    /// <summary>
    /// อัพเดทสต็อก
    /// </summary>
    Task<bool> UpdateStockAsync(int productId, int quantity);

    /// <summary>
    /// ดึงสินค้าที่สต็อกต่ำ
    /// </summary>
    Task<List<Product>> GetLowStockAsync();

    /// <summary>
    /// Sync สินค้าจาก Server
    /// </summary>
    Task<int> SyncFromServerAsync(DateTime? since = null);

    /// <summary>
    /// นำเข้าสินค้าจาก Server (ครั้งแรก)
    /// </summary>
    Task<int> ImportFromServerAsync();
}
