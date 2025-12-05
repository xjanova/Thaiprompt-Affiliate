using System.Windows.Input;
using CommunityToolkit.Mvvm.Input;
using ThaipromptAffiliateApp.Helpers;
using ThaipromptAffiliateApp.Services;

namespace ThaipromptAffiliateApp.ViewModels
{
    /// <summary>
    /// ViewModel for login page
    /// ใช้ manual property implementation แทน source generators
    /// </summary>
    public class LoginViewModel : BaseViewModel
    {
        private readonly IApiService _apiService;

        private string _email = string.Empty;
        private string _password = string.Empty;
        private bool _rememberMe = true;
        private bool _showPassword = false;

        public string Email
        {
            get => _email;
            set => SetProperty(ref _email, value);
        }

        public string Password
        {
            get => _password;
            set => SetProperty(ref _password, value);
        }

        public bool RememberMe
        {
            get => _rememberMe;
            set => SetProperty(ref _rememberMe, value);
        }

        public bool ShowPassword
        {
            get => _showPassword;
            set => SetProperty(ref _showPassword, value);
        }

        // Commands
        public ICommand LoginCommand { get; }
        public ICommand TogglePasswordVisibilityCommand { get; }
        public ICommand ForgotPasswordCommand { get; }
        public ICommand RegisterCommand { get; }

        public LoginViewModel(IApiService apiService)
        {
            _apiService = apiService;
            Title = "เข้าสู่ระบบ";

            // Initialize commands
            LoginCommand = new AsyncRelayCommand(LoginAsync);
            TogglePasswordVisibilityCommand = new RelayCommand(TogglePasswordVisibility);
            ForgotPasswordCommand = new AsyncRelayCommand(ForgotPasswordAsync);
            RegisterCommand = new AsyncRelayCommand(RegisterAsync);

#if DEBUG
            // Pre-fill for development
            Email = "demo@thaiprompt.com";
            Password = "password123";
#endif
        }

        /// <summary>
        /// Validate login form
        /// </summary>
        private bool ValidateForm()
        {
            if (string.IsNullOrWhiteSpace(Email))
            {
                ErrorMessage = "กรุณากรอกอีเมล";
                HasError = true;
                return false;
            }

            if (!Constants.IsValidEmail(Email))
            {
                ErrorMessage = "รูปแบบอีเมลไม่ถูกต้อง";
                HasError = true;
                return false;
            }

            if (string.IsNullOrWhiteSpace(Password))
            {
                ErrorMessage = "กรุณากรอกรหัสผ่าน";
                HasError = true;
                return false;
            }

            if (Password.Length < Constants.MinPasswordLength)
            {
                ErrorMessage = $"รหัสผ่านต้องมีอย่างน้อย {Constants.MinPasswordLength} ตัวอักษร";
                HasError = true;
                return false;
            }

            HasError = false;
            ErrorMessage = string.Empty;
            return true;
        }

        /// <summary>
        /// Login command
        /// </summary>
        private async Task LoginAsync()
        {
            if (!ValidateForm())
            {
                return;
            }

            await ExecuteWithBusyAsync(async () =>
            {
                var result = await _apiService.LoginAsync(Email, Password);

                if (result.Success)
                {
                    // Navigate to main app
                    await Shell.Current.GoToAsync("///dashboard");
                }
                else
                {
                    ErrorMessage = result.Message ?? "เข้าสู่ระบบไม่สำเร็จ";
                    HasError = true;
                }
            });
        }

        /// <summary>
        /// Toggle password visibility
        /// </summary>
        private void TogglePasswordVisibility()
        {
            ShowPassword = !ShowPassword;
        }

        /// <summary>
        /// Navigate to forgot password page
        /// (Coming Soon - แสดง Alert แทน)
        /// </summary>
        private async Task ForgotPasswordAsync()
        {
            // TODO: สร้างหน้า ForgotPasswordPage
            await Application.Current!.MainPage!.DisplayAlert(
                "ลืมรหัสผ่าน",
                "กรุณาติดต่อฝ่ายสนับสนุนที่ support@thaiprompt.com\nหรือโทร 02-xxx-xxxx",
                "ตกลง");
        }

        /// <summary>
        /// Navigate to register page
        /// (Coming Soon - แสดง Alert แทน)
        /// </summary>
        private async Task RegisterAsync()
        {
            // TODO: สร้างหน้า RegisterPage
            await Application.Current!.MainPage!.DisplayAlert(
                "สมัครสมาชิก",
                "ฟีเจอร์นี้จะเปิดให้บริการเร็วๆ นี้\nกรุณาติดต่อ support@thaiprompt.com",
                "ตกลง");
        }
    }
}
