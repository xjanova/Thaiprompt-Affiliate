using TP.POS.Core.Entities;
using TP.POS.Core.Interfaces;
using TP.POS.Infrastructure.Api;
using TP.POS.Infrastructure.Data;

namespace TP.POS.App.Services;

/// <summary>
/// Service สำหรับจัดการสินค้า
/// </summary>
public class ProductService : IProductService
{
    private readonly PosDatabase _database;
    private readonly TpAffiliateApiClient _apiClient;

    public ProductService(PosDatabase database, TpAffiliateApiClient apiClient)
    {
        _database = database;
        _apiClient = apiClient;
    }

    public async Task<List<Product>> GetAllAsync()
    {
        return await _database.GetAllProductsAsync();
    }

    public async Task<Product?> GetByIdAsync(int id)
    {
        return await _database.GetProductByIdAsync(id);
    }

    public async Task<Product?> GetByBarcodeAsync(string barcode)
    {
        return await _database.GetProductByBarcodeAsync(barcode);
    }

    public async Task<Product?> GetBySkuAsync(string sku)
    {
        return await _database.GetProductBySkuAsync(sku);
    }

    public async Task<List<Product>> SearchAsync(string keyword, int? categoryId = null, int limit = 50)
    {
        return await _database.SearchProductsAsync(keyword, categoryId, limit);
    }

    public async Task<List<Product>> GetByCategoryAsync(int categoryId)
    {
        return await _database.SearchProductsAsync(string.Empty, categoryId);
    }

    public async Task<Product> CreateAsync(Product product)
    {
        await _database.SaveProductAsync(product);
        return product;
    }

    public async Task<Product> UpdateAsync(Product product)
    {
        await _database.SaveProductAsync(product);
        return product;
    }

    public async Task<bool> DeleteAsync(int id)
    {
        var product = await GetByIdAsync(id);
        if (product != null)
        {
            product.IsActive = false;
            await _database.SaveProductAsync(product);
            return true;
        }
        return false;
    }

    public async Task<bool> UpdateStockAsync(int productId, int quantity)
    {
        return await _database.UpdateProductStockAsync(productId, quantity);
    }

    public async Task<List<Product>> GetLowStockAsync()
    {
        var products = await GetAllAsync();
        return products.Where(p => p.StockQuantity <= p.ReorderLevel).ToList();
    }

    public async Task<int> SyncFromServerAsync(DateTime? since = null)
    {
        try
        {
            var response = since.HasValue
                ? await _apiClient.GetUpdatedProductsAsync(since.Value)
                : await _apiClient.GetAllProductsAsync();

            if (response?.Success == true && response.Data != null)
            {
                foreach (var product in response.Data)
                {
                    product.IsSynced = true;
                    product.LastSyncedAt = DateTime.UtcNow;
                    await _database.SaveProductAsync(product);
                }
                return response.Data.Count;
            }
        }
        catch (Exception ex)
        {
            System.Diagnostics.Debug.WriteLine($"Sync error: {ex.Message}");
        }
        return 0;
    }

    public async Task<int> ImportFromServerAsync()
    {
        return await SyncFromServerAsync(null);
    }

    public async Task<List<Category>> GetCategoriesAsync()
    {
        return await _database.GetAllCategoriesAsync();
    }
}
