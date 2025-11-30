using System.ComponentModel;
using System.Runtime.CompilerServices;

namespace ThaipromptAffiliateApp.ViewModels
{
    /// <summary>
    /// Base ViewModel with common properties and methods
    /// ใช้ manual INotifyPropertyChanged implementation แทน source generators
    /// เพื่อหลีกเลี่ยง intermediatexaml bug ใน MAUI
    /// </summary>
    public class BaseViewModel : INotifyPropertyChanged
    {
        private bool _isBusy;
        private bool _isRefreshing;
        private string _title = string.Empty;
        private string _errorMessage = string.Empty;
        private bool _hasError;

        public bool IsBusy
        {
            get => _isBusy;
            set => SetProperty(ref _isBusy, value);
        }

        public bool IsRefreshing
        {
            get => _isRefreshing;
            set => SetProperty(ref _isRefreshing, value);
        }

        public string Title
        {
            get => _title;
            set => SetProperty(ref _title, value);
        }

        public string ErrorMessage
        {
            get => _errorMessage;
            set => SetProperty(ref _errorMessage, value);
        }

        public bool HasError
        {
            get => _hasError;
            set => SetProperty(ref _hasError, value);
        }

        /// <summary>
        /// INotifyPropertyChanged event
        /// </summary>
        public event PropertyChangedEventHandler? PropertyChanged;

        /// <summary>
        /// Set property value and raise PropertyChanged event
        /// </summary>
        protected bool SetProperty<T>(ref T field, T value, [CallerMemberName] string? propertyName = null)
        {
            if (EqualityComparer<T>.Default.Equals(field, value))
                return false;

            field = value;
            OnPropertyChanged(propertyName);
            return true;
        }

        /// <summary>
        /// Raise PropertyChanged event
        /// </summary>
        protected void OnPropertyChanged([CallerMemberName] string? propertyName = null)
        {
            PropertyChanged?.Invoke(this, new PropertyChangedEventArgs(propertyName));
        }

        /// <summary>
        /// Called when page appears - override in derived classes
        /// </summary>
        public virtual void OnAppearing()
        {
        }

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
