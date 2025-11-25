# Required Fonts for Thaiprompt Ultra App

## Download Open Sans Fonts

This project uses **Open Sans** font family. You need to download and place the following font files in this folder:

### Required Files:
1. `OpenSans-Regular.ttf`
2. `OpenSans-SemiBold.ttf` (or `OpenSans-Semibold.ttf`)
3. `OpenSans-Bold.ttf`

### Download Options:

**Option 1: Google Fonts (Recommended)**
1. Go to: https://fonts.google.com/specimen/Open+Sans
2. Click "Download family"
3. Extract the ZIP file
4. Copy the .ttf files to this folder

**Option 2: Direct Download**
- https://github.com/googlefonts/opensans/tree/main/fonts/ttf

### File Naming:
After downloading, make sure the files are named exactly as:
- `OpenSans-Regular.ttf`
- `OpenSans-Semibold.ttf`
- `OpenSans-Bold.ttf`

## Font Configuration in MauiProgram.cs

The fonts are configured in `MauiProgram.cs`:
```csharp
.ConfigureFonts(fonts =>
{
    fonts.AddFont("OpenSans-Regular.ttf", "OpenSansRegular");
    fonts.AddFont("OpenSans-Semibold.ttf", "OpenSansSemibold");
    fonts.AddFont("OpenSans-Bold.ttf", "OpenSansBold");
});
```

## Alternative: Use System Fonts

If you prefer to use system fonts, you can modify the XAML files to use:
- `FontFamily="Default"` instead of `FontFamily="OpenSansRegular"`
