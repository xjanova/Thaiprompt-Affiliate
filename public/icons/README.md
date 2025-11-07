# Icons Directory

This directory contains all icons used in the application.

## 📁 Structure

- **system/** - System icons (dashboard, settings, users, etc.)
- **theme/** - Theme-related icons (palette, colors, etc.)
- **custom/** - Custom icons uploaded by admin
- **social/** - Social media icons (Facebook, LINE, Twitter, etc.)
- **flags/** - Country flags

## 📤 How to Add Icons

### Method 1: Via Admin Panel (Recommended)
1. Go to `/admin/icons`
2. Select category
3. Click "Upload Icon"
4. Choose your file (SVG, PNG, JPG, WebP)
5. Upload!

### Method 2: Manual Upload
Simply place your icon files in the appropriate category folder.

## ✅ Supported Formats

- **SVG** (Recommended) - Scalable Vector Graphics
- **PNG** - Portable Network Graphics
- **JPG/JPEG** - Joint Photographic Experts Group
- **WebP** - Web Picture format

**Max file size**: 2MB

## 📖 Usage

```blade
<!-- Using Blade Component -->
<x-icon name="dashboard" category="system" size="md" />

<!-- Using Helper -->
<img src="{{ IconHelper::url('dashboard', 'system') }}" />

<!-- Inline SVG -->
{!! IconHelper::inline('dashboard', 'system') !!}
```

## 📚 Documentation

For detailed documentation, see: `ICON_SYSTEM_GUIDE.md`

## 🔗 Recommended Icon Sources

- [Heroicons](https://heroicons.com/)
- [Feather Icons](https://feathericons.com/)
- [Font Awesome](https://fontawesome.com/)
- [Material Icons](https://fonts.google.com/icons)
- [Flaticon](https://www.flaticon.com/)

---

**Version**: 2.0.0
**Last Updated**: 2025-11-07
