using SQLite;
using TP.POS.Core.Entities;

namespace TP.POS.Infrastructure.Data;

/// <summary>
/// SQLite Database สำหรับ POS
/// ใช้สำหรับเก็บข้อมูล Offline
/// </summary>
public class PosDatabase
{
    private SQLiteAsyncConnection? _database;
    private readonly string _databasePath;

    /// <summary>
    /// สร้าง instance ใหม่
    /// </summary>
    public PosDatabase(string databasePath)
    {
        _databasePath = databasePath;
    }

    /// <summary>
    /// เริ่มต้น Database
    /// สร้างตารางทั้งหมดถ้ายังไม่มี
    /// </summary>
    public async Task InitializeAsync()
    {
        if (_database != null)
            return;

        _database = new SQLiteAsyncConnection(_databasePath, SQLiteOpenFlags.ReadWrite | SQLiteOpenFlags.Create | SQLiteOpenFlags.SharedCache);

        // สร้างตารางทั้งหมด
        await _database.CreateTableAsync<Product>();
        await _database.CreateTableAsync<Category>();
        await _database.CreateTableAsync<Customer>();
        await _database.CreateTableAsync<Transaction>();
        await _database.CreateTableAsync<TransactionItem>();
        await _database.CreateTableAsync<CartItem>();
        await _database.CreateTableAsync<StockMovement>();
        await _database.CreateTableAsync<AppSettings>();
        await _database.CreateTableAsync<SyncLog>();
    }

    /// <summary>
    /// ดึง Database connection
    /// </summary>
    public SQLiteAsyncConnection Database
    {
        get
        {
            if (_database == null)
                throw new InvalidOperationException("Database ยังไม่ได้ initialize กรุณาเรียก InitializeAsync() ก่อน");
            return _database;
        }
    }

    #region Products

    /// <summary>
    /// ดึงสินค้าทั้งหมด
    /// </summary>
    public async Task<List<Product>> GetAllProductsAsync()
    {
        return await Database.Table<Product>()
            .Where(p => p.IsActive)
            .OrderBy(p => p.Name)
            .ToListAsync();
    }

    /// <summary>
    /// ดึงสินค้าตาม ID
    /// </summary>
    public async Task<Product?> GetProductByIdAsync(int id)
    {
        return await Database.Table<Product>()
            .FirstOrDefaultAsync(p => p.Id == id);
    }

    /// <summary>
    /// ดึงสินค้าตามบาร์โค้ด
    /// </summary>
    public async Task<Product?> GetProductByBarcodeAsync(string barcode)
    {
        return await Database.Table<Product>()
            .FirstOrDefaultAsync(p => p.Barcode == barcode && p.IsActive);
    }

    /// <summary>
    /// ดึงสินค้าตาม SKU
    /// </summary>
    public async Task<Product?> GetProductBySkuAsync(string sku)
    {
        return await Database.Table<Product>()
            .FirstOrDefaultAsync(p => p.Sku == sku && p.IsActive);
    }

    /// <summary>
    /// ค้นหาสินค้า
    /// </summary>
    public async Task<List<Product>> SearchProductsAsync(string keyword, int? categoryId = null, int limit = 50)
    {
        var query = Database.Table<Product>().Where(p => p.IsActive);

        // กรองตามหมวดหมู่
        if (categoryId.HasValue)
        {
            query = query.Where(p => p.CategoryId == categoryId.Value);
        }

        var products = await query.ToListAsync();

        // ค้นหาด้วย keyword
        if (!string.IsNullOrWhiteSpace(keyword))
        {
            keyword = keyword.ToLower();
            products = products
                .Where(p =>
                    p.Name.ToLower().Contains(keyword) ||
                    p.Sku.ToLower().Contains(keyword) ||
                    (p.Barcode?.ToLower().Contains(keyword) ?? false))
                .ToList();
        }

        return products.Take(limit).ToList();
    }

    /// <summary>
    /// บันทึกสินค้า
    /// </summary>
    public async Task<int> SaveProductAsync(Product product)
    {
        product.UpdatedAt = DateTime.UtcNow;

        if (product.Id != 0)
        {
            return await Database.UpdateAsync(product);
        }
        else
        {
            product.CreatedAt = DateTime.UtcNow;
            return await Database.InsertAsync(product);
        }
    }

    /// <summary>
    /// บันทึกสินค้าหลายรายการ
    /// </summary>
    public async Task<int> SaveProductsAsync(List<Product> products)
    {
        foreach (var product in products)
        {
            product.UpdatedAt = DateTime.UtcNow;
            if (product.CreatedAt == default)
                product.CreatedAt = DateTime.UtcNow;
        }
        return await Database.InsertAllAsync(products);
    }

    /// <summary>
    /// อัพเดทสต็อก
    /// </summary>
    public async Task<bool> UpdateProductStockAsync(int productId, int quantity)
    {
        var product = await GetProductByIdAsync(productId);
        if (product == null) return false;

        product.StockQuantity += quantity;
        product.UpdatedAt = DateTime.UtcNow;
        await Database.UpdateAsync(product);
        return true;
    }

    #endregion

    #region Categories

    /// <summary>
    /// ดึงหมวดหมู่ทั้งหมด
    /// </summary>
    public async Task<List<Category>> GetAllCategoriesAsync()
    {
        return await Database.Table<Category>()
            .Where(c => c.IsActive)
            .OrderBy(c => c.SortOrder)
            .ThenBy(c => c.Name)
            .ToListAsync();
    }

    /// <summary>
    /// บันทึกหมวดหมู่
    /// </summary>
    public async Task<int> SaveCategoryAsync(Category category)
    {
        category.UpdatedAt = DateTime.UtcNow;
        if (category.Id != 0)
            return await Database.UpdateAsync(category);
        else
        {
            category.CreatedAt = DateTime.UtcNow;
            return await Database.InsertAsync(category);
        }
    }

    #endregion

    #region Customers

    /// <summary>
    /// ดึงลูกค้าทั้งหมด
    /// </summary>
    public async Task<List<Customer>> GetAllCustomersAsync()
    {
        return await Database.Table<Customer>()
            .Where(c => c.IsActive)
            .OrderBy(c => c.FirstName)
            .ToListAsync();
    }

    /// <summary>
    /// ค้นหาลูกค้า
    /// </summary>
    public async Task<List<Customer>> SearchCustomersAsync(string keyword, int limit = 20)
    {
        var customers = await Database.Table<Customer>()
            .Where(c => c.IsActive)
            .ToListAsync();

        if (!string.IsNullOrWhiteSpace(keyword))
        {
            keyword = keyword.ToLower();
            customers = customers
                .Where(c =>
                    c.FirstName.ToLower().Contains(keyword) ||
                    (c.LastName?.ToLower().Contains(keyword) ?? false) ||
                    (c.Phone?.Contains(keyword) ?? false) ||
                    (c.CustomerCode?.ToLower().Contains(keyword) ?? false))
                .ToList();
        }

        return customers.Take(limit).ToList();
    }

    /// <summary>
    /// บันทึกลูกค้า
    /// </summary>
    public async Task<int> SaveCustomerAsync(Customer customer)
    {
        customer.UpdatedAt = DateTime.UtcNow;
        if (customer.Id != 0)
            return await Database.UpdateAsync(customer);
        else
        {
            customer.CreatedAt = DateTime.UtcNow;
            return await Database.InsertAsync(customer);
        }
    }

    #endregion

    #region Transactions

    /// <summary>
    /// บันทึกรายการขาย
    /// </summary>
    public async Task<int> SaveTransactionAsync(Transaction transaction)
    {
        transaction.UpdatedAt = DateTime.UtcNow;
        if (transaction.Id != 0)
            return await Database.UpdateAsync(transaction);
        else
        {
            transaction.CreatedAt = DateTime.UtcNow;
            return await Database.InsertAsync(transaction);
        }
    }

    /// <summary>
    /// บันทึกรายการสินค้าในใบเสร็จ
    /// </summary>
    public async Task<int> SaveTransactionItemsAsync(List<TransactionItem> items)
    {
        foreach (var item in items)
        {
            item.UpdatedAt = DateTime.UtcNow;
            if (item.CreatedAt == default)
                item.CreatedAt = DateTime.UtcNow;
        }
        return await Database.InsertAllAsync(items);
    }

    /// <summary>
    /// ดึงรายการขายตาม ID
    /// </summary>
    public async Task<Transaction?> GetTransactionByIdAsync(int id)
    {
        return await Database.Table<Transaction>()
            .FirstOrDefaultAsync(t => t.Id == id);
    }

    /// <summary>
    /// ดึงรายการขายวันนี้
    /// </summary>
    public async Task<List<Transaction>> GetTodayTransactionsAsync()
    {
        var today = DateTime.Today;
        return await Database.Table<Transaction>()
            .Where(t => t.TransactionDate >= today)
            .OrderByDescending(t => t.TransactionDate)
            .ToListAsync();
    }

    /// <summary>
    /// ดึงรายการขายที่ยังไม่ sync
    /// </summary>
    public async Task<List<Transaction>> GetUnsyncedTransactionsAsync()
    {
        return await Database.Table<Transaction>()
            .Where(t => !t.IsSynced)
            .ToListAsync();
    }

    /// <summary>
    /// ดึงรายการสินค้าของ Transaction
    /// </summary>
    public async Task<List<TransactionItem>> GetTransactionItemsAsync(int transactionId)
    {
        return await Database.Table<TransactionItem>()
            .Where(i => i.TransactionId == transactionId)
            .ToListAsync();
    }

    #endregion

    #region Cart

    /// <summary>
    /// ดึงรายการในตะกร้า
    /// </summary>
    public async Task<List<CartItem>> GetCartItemsAsync(string? sessionToken = null)
    {
        var query = Database.Table<CartItem>();
        if (!string.IsNullOrEmpty(sessionToken))
            query = query.Where(c => c.SessionToken == sessionToken);

        return await query.ToListAsync();
    }

    /// <summary>
    /// บันทึกรายการในตะกร้า
    /// </summary>
    public async Task<int> SaveCartItemAsync(CartItem item)
    {
        item.UpdatedAt = DateTime.UtcNow;
        if (item.Id != 0)
            return await Database.UpdateAsync(item);
        else
        {
            item.CreatedAt = DateTime.UtcNow;
            return await Database.InsertAsync(item);
        }
    }

    /// <summary>
    /// ลบรายการในตะกร้า
    /// </summary>
    public async Task<int> DeleteCartItemAsync(int id)
    {
        return await Database.DeleteAsync<CartItem>(id);
    }

    /// <summary>
    /// ล้างตะกร้า
    /// </summary>
    public async Task<int> ClearCartAsync(string? sessionToken = null)
    {
        if (string.IsNullOrEmpty(sessionToken))
            return await Database.DeleteAllAsync<CartItem>();
        else
            return await Database.ExecuteAsync(
                "DELETE FROM cart_items WHERE SessionToken = ?",
                sessionToken);
    }

    #endregion

    #region Stock Movements

    /// <summary>
    /// บันทึกการเคลื่อนไหวสต็อก
    /// </summary>
    public async Task<int> SaveStockMovementAsync(StockMovement movement)
    {
        movement.UpdatedAt = DateTime.UtcNow;
        if (movement.Id != 0)
            return await Database.UpdateAsync(movement);
        else
        {
            movement.CreatedAt = DateTime.UtcNow;
            return await Database.InsertAsync(movement);
        }
    }

    /// <summary>
    /// ดึงการเคลื่อนไหวสต็อกที่ยังไม่ sync
    /// </summary>
    public async Task<List<StockMovement>> GetUnsyncedStockMovementsAsync()
    {
        return await Database.Table<StockMovement>()
            .Where(s => !s.IsSynced)
            .ToListAsync();
    }

    #endregion

    #region Settings

    /// <summary>
    /// ดึงการตั้งค่า
    /// </summary>
    public async Task<string?> GetSettingAsync(string key)
    {
        var setting = await Database.Table<AppSettings>()
            .FirstOrDefaultAsync(s => s.Key == key);
        return setting?.Value;
    }

    /// <summary>
    /// บันทึกการตั้งค่า
    /// </summary>
    public async Task SetSettingAsync(string key, string value)
    {
        var setting = await Database.Table<AppSettings>()
            .FirstOrDefaultAsync(s => s.Key == key);

        if (setting != null)
        {
            setting.Value = value;
            setting.UpdatedAt = DateTime.UtcNow;
            await Database.UpdateAsync(setting);
        }
        else
        {
            await Database.InsertAsync(new AppSettings
            {
                Key = key,
                Value = value,
                CreatedAt = DateTime.UtcNow,
                UpdatedAt = DateTime.UtcNow
            });
        }
    }

    #endregion

    /// <summary>
    /// ล้างข้อมูลทั้งหมด (ใช้ตอน reset)
    /// </summary>
    public async Task ClearAllDataAsync()
    {
        await Database.DeleteAllAsync<Product>();
        await Database.DeleteAllAsync<Category>();
        await Database.DeleteAllAsync<Customer>();
        await Database.DeleteAllAsync<Transaction>();
        await Database.DeleteAllAsync<TransactionItem>();
        await Database.DeleteAllAsync<CartItem>();
        await Database.DeleteAllAsync<StockMovement>();
        await Database.DeleteAllAsync<SyncLog>();
    }
}

/// <summary>
/// การตั้งค่าแอพ
/// </summary>
[Table("app_settings")]
public class AppSettings
{
    [PrimaryKey, AutoIncrement]
    public int Id { get; set; }

    [MaxLength(100)]
    [Indexed(Unique = true)]
    public string Key { get; set; } = string.Empty;

    public string? Value { get; set; }

    public DateTime CreatedAt { get; set; }
    public DateTime UpdatedAt { get; set; }
}

/// <summary>
/// Log การ Sync
/// </summary>
[Table("sync_logs")]
public class SyncLog
{
    [PrimaryKey, AutoIncrement]
    public int Id { get; set; }

    [MaxLength(50)]
    public string SyncType { get; set; } = string.Empty;

    public bool Success { get; set; }

    public int RecordsProcessed { get; set; }

    public string? ErrorMessage { get; set; }

    public DateTime SyncedAt { get; set; } = DateTime.UtcNow;
}
