using CommunityToolkit.Mvvm.ComponentModel;
using CommunityToolkit.Mvvm.Input;
using ThaipromptAffiliate.Helpers;
using ThaipromptAffiliate.Services;

namespace ThaipromptAffiliate.ViewModels
{
    /// <summary>
    /// ViewModel for login page
    /// </summary>
    public partial class LoginViewModel : BaseViewModel
    {
        private readonly IApiService _apiService;

        [ObservableProperty]
        private string _email = string.Empty;

        [ObservableProperty]
        private string _password = string.Empty;

        [ObservableProperty]
        private bool _rememberMe = true;

        [ObservableProperty]
        private bool _showPassword = false;

        public LoginViewModel(IApiService apiService)
        {
            _apiService = apiService;
            Title = "เข้าสู่ระบบ";

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
        [RelayCommand]
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
        [RelayCommand]
        private void TogglePasswordVisibility()
        {
            ShowPassword = !ShowPassword;
        }

        /// <summary>
        /// Navigate to forgot password page
        /// </summary>
        [RelayCommand]
        private async Task ForgotPasswordAsync()
        {
            await NavigateToAsync("forgotpassword");
        }

        /// <summary>
        /// Navigate to register page
        /// </summary>
        [RelayCommand]
        private async Task RegisterAsync()
        {
            await NavigateToAsync("register");
        }
    }
}
