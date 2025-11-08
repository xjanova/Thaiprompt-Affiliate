using CommunityToolkit.Mvvm.ComponentModel;
using CommunityToolkit.Mvvm.Input;

namespace ThaipromptAffiliate.ViewModels
{
    /// <summary>
    /// Base ViewModel with common properties and methods
    /// </summary>
    public partial class BaseViewModel : ObservableObject
    {
        [ObservableProperty]
        private bool _isBusy;

        [ObservableProperty]
        private bool _isRefreshing;

        [ObservableProperty]
        private string _title = string.Empty;

        [ObservableProperty]
        private string _errorMessage = string.Empty;

        [ObservableProperty]
        private bool _hasError;

        /// <summary>
        /// Execute action with busy indicator
        /// </summary>
        protected async Task ExecuteWithBusyAsync(Func<Task> action)
        {
            if (IsBusy) return;

            try
            {
                IsBusy = true;
                HasError = false;
                ErrorMessage = string.Empty;

                await action();
            }
            catch (Exception ex)
            {
                await HandleErrorAsync(ex);
            }
            finally
            {
                IsBusy = false;
            }
        }

        /// <summary>
        /// Execute action with refresh indicator
        /// </summary>
        protected async Task ExecuteWithRefreshAsync(Func<Task> action)
        {
            if (IsRefreshing) return;

            try
            {
                IsRefreshing = true;
                HasError = false;
                ErrorMessage = string.Empty;

                await action();
            }
            catch (Exception ex)
            {
                await HandleErrorAsync(ex);
            }
            finally
            {
                IsRefreshing = false;
            }
        }

        /// <summary>
        /// Handle errors and show user-friendly messages
        /// </summary>
        protected virtual async Task HandleErrorAsync(Exception exception)
        {
            HasError = true;

            ErrorMessage = exception switch
            {
                UnauthorizedAccessException => "กรุณาเข้าสู่ระบบใหม่อีกครั้ง",
                HttpRequestException => "ไม่สามารถเชื่อมต่อกับเซิร์ฟเวอร์ได้",
                TaskCanceledException => "การเชื่อมต่อหมดเวลา",
                _ => "เกิดข้อผิดพลาด กรุณาลองใหม่อีกครั้ง"
            };

            Console.WriteLine($"Error: {exception.Message}");
            Console.WriteLine($"StackTrace: {exception.StackTrace}");

            // Show alert to user
            if (Application.Current?.MainPage != null)
            {
                await Application.Current.MainPage.DisplayAlert(
                    "ข้อผิดพลาด",
                    ErrorMessage,
                    "ตกลง");
            }
        }

        /// <summary>
        /// Show success message
        /// </summary>
        protected async Task ShowSuccessAsync(string title, string message)
        {
            if (Application.Current?.MainPage != null)
            {
                await Application.Current.MainPage.DisplayAlert(
                    title,
                    message,
                    "ตกลง");
            }
        }

        /// <summary>
        /// Show confirmation dialog
        /// </summary>
        protected async Task<bool> ShowConfirmationAsync(string title, string message)
        {
            if (Application.Current?.MainPage != null)
            {
                return await Application.Current.MainPage.DisplayAlert(
                    title,
                    message,
                    "ยืนยัน",
                    "ยกเลิก");
            }

            return false;
        }

        /// <summary>
        /// Navigate to page
        /// </summary>
        protected async Task NavigateToAsync(string route)
        {
            await Shell.Current.GoToAsync(route);
        }

        /// <summary>
        /// Navigate back
        /// </summary>
        protected async Task NavigateBackAsync()
        {
            await Shell.Current.GoToAsync("..");
        }
    }
}
