namespace TP.POS.App;

/// <summary>
/// Shell Navigation
/// </summary>
public partial class AppShell : Shell
{
    public AppShell()
    {
        InitializeComponent();

        // ลงทะเบียน Routes
        Routing.RegisterRoute("login", typeof(Views.LoginPage));
        Routing.RegisterRoute("checkout", typeof(Views.CheckoutPage));
        Routing.RegisterRoute("receipt", typeof(Views.ReceiptPage));
        Routing.RegisterRoute("product-detail", typeof(Views.ProductDetailPage));
        Routing.RegisterRoute("customer-select", typeof(Views.CustomerSelectPage));
        Routing.RegisterRoute("stock-adjustment", typeof(Views.StockAdjustmentPage));
    }
}
