# Changelog

ประวัติการเปลี่ยนแปลงของโปรเจค Thai Prompt Affiliate Marketing Platform

## [v2.109.0] - 2025-11-09

### ✨ Features
- Merge pull request #649 from xjanova/claude/admin-homepage-builder-011CUwjFGcYcikh9prH1zfKj (d52a3ae)
- feat: complete Page Builder v2.104.0 with validation, permissions, and templates (063862b)
### 🐛 Bug Fixes

### 🔧 Other Changes
- chore: resolve merge conflict with main branch (5f287eb)

## [v2.109.0] - 2025-11-09

### ✨ Features - Page Builder Completion
- **Section Templates**: Created 11 missing section templates bringing total to 19
  - hero-gradient: Hero section with animated gradient backgrounds
  - hero-video: Hero with video background (MP4, YouTube, Vimeo support)
  - features-list: Detailed features list with icons and images
  - testimonials: Testimonial cards with ratings and verified badges
  - image-text: Image and text combination sections
  - gallery: Image gallery with lightbox support
  - pricing: Professional pricing tables with plan comparison
  - team: Team member showcase with social links
  - faq: FAQ accordion with multiple layout options
  - contact-form: Advanced contact form with validation
  - custom-html: Custom HTML/CSS/JS section

- **Edit Section Modal**: Full-featured section editor
  - JSON-based content and settings editor
  - Real-time validation
  - Visual feedback and error handling
  - API endpoint for fetching section data

- **Auto-Save System**: Automatic data persistence
  - Auto-save every 30 seconds
  - Save on window blur (tab switch)
  - Visual notifications
  - Smart detection to prevent unnecessary saves

- **Visual Template Gallery**: Beautiful template picker modal
  - Grid layout with icons
  - Category-based icons for each section type
  - Hover effects and visual feedback
  - Quick add functionality

- **Configuration System**: Centralized config file
  - Cache settings
  - Section types and page types definitions
  - Default settings for all section types
  - Validation rules
  - Permission configuration
  - SEO and storage settings
  - Development/debug options

- **Validation Service**: Content structure validation
  - Type-specific validation rules
  - Required fields checking
  - Settings validation
  - Strict mode option

- **Permission Policy**: Role-based access control
  - PageBuilderPolicy with granular permissions
  - View, create, edit, delete, publish permissions
  - Homepage protection
  - Admin override

- **Enhanced Seeder**: Updated PageBuilderSeeder with 22 templates
  - Sample data for all 11 new section types
  - Complete default content and settings
  - Ready-to-use template examples

### 🎨 Improvements
- Enhanced UI/UX for Page Builder editor
- Improved section card design
- Better modal transitions and animations
- Comprehensive documentation in config file

### 📚 Technical Details
- Total section templates: 19
- New files: 16 (11 templates + config + service + policy + seeder updates)
- Updated files: 5 (controller, view, routes, seeder, changelog)
- Lines of code added: ~3,300+
- All PHP files syntax validated

### 🚀 System Status
Page Builder is now **production-ready** with:
✅ Visual drag & drop editor
✅ Real-time responsive preview
✅ Complete section library (19 types)
✅ Advanced edit capabilities
✅ Auto-save functionality
✅ Permission system
✅ Content validation
✅ Configuration management
✅ 22 ready-to-use templates in seeder

### 🐛 Bug Fixes

### 🔧 Other Changes

## [v2.108.0] - 2025-11-09

### ✨ Features
- Merge pull request #648 from xjanova/claude/admin-homepage-builder-011CUwjFGcYcikh9prH1zfKj (2893ce3)
- feat: complete Page Builder system with missing features (6ef5882)
### 🐛 Bug Fixes

### 🔧 Other Changes


## [v2.107.2] - 2025-11-09

### ✨ Features

### 🐛 Bug Fixes
- Merge pull request #647 from xjanova/claude/admin-homepage-builder-011CUwjFGcYcikh9prH1zfKj (b238bbe)
- fix: PageBuilderController update method return JSON for AJAX (4f6616d)
### 🔧 Other Changes


## [v2.107.1] - 2025-11-09

### ✨ Features

### 🐛 Bug Fixes
- fix: improve Page Builder edit page functionality (a55b0e3)
- fix: replace Vite with CDN assets in Page Builder preview (c6676a8)
### 🔧 Other Changes
- Merge pull request #646 from xjanova/claude/admin-homepage-builder-011CUwjFGcYcikh9prH1zfKj (781fd97)

## [v2.107.0] - 2025-11-09

### ✨ Features
- Merge pull request #645 from xjanova/claude/admin-homepage-builder-011CUwjFGcYcikh9prH1zfKj (d28bbe7)
- feat: consolidate Windows UI management into comprehensive pages (e59bdb4)
### 🐛 Bug Fixes

### 🔧 Other Changes


## [v2.106.0] - 2025-11-09

### ✨ Features
- Merge pull request #644 from xjanova/claude/fix-seller-pos-sales-route-011CUwi3TfcDHpAED5oJyUZq (cd4c825)
- feat: expand admin settings menu with submenu items (d759e87)
### 🐛 Bug Fixes

### 🔧 Other Changes


## [v2.105.0] - 2025-11-09

### ✨ Features
- Merge pull request #643 from xjanova/claude/admin-homepage-builder-011CUwjFGcYcikh9prH1zfKj (def826b)
- feat: integrate Homepage with Page Builder system (429500b)
### 🐛 Bug Fixes

### 🔧 Other Changes


## [v2.104.0] - 2025-11-09

### ✨ Features
- feat: add Page Builder to Theme & UI menu (62a2fde)
### 🐛 Bug Fixes
- fix: add missing content_width settings validation in WindowsUiController (fbdd5b2)
### 🔧 Other Changes
- Merge pull request #642 from xjanova/claude/admin-homepage-builder-011CUwjFGcYcikh9prH1zfKj (0dc66ad)
- chore: resolve merge conflict in millennium-start-menu.blade.php (0ff9e6b)

## [v2.103.1] - 2025-11-09

### ✨ Features

### 🐛 Bug Fixes

### 🔧 Other Changes
- Merge pull request #641 from xjanova/claude/admin-homepage-builder-011CUwjFGcYcikh9prH1zfKj (3e1ad36)
- chore: register PageBuilderSeeder in DatabaseSeeder (949abda)

## [v2.103.0] - 2025-11-09

### ✨ Features
- Merge pull request #640 from xjanova/claude/admin-homepage-builder-011CUwjFGcYcikh9prH1zfKj (dcae7f5)
- feat: add comprehensive Page Builder system with drag & drop (52bda84)
### 🐛 Bug Fixes

### 🔧 Other Changes


## [v2.102.1] - 2025-11-09

### ✨ Features

### 🐛 Bug Fixes
- Merge pull request #639 from xjanova/claude/fix-seller-pos-sales-route-011CUwi3TfcDHpAED5oJyUZq (baf3a73)
- fix: remove admin.users.permissions from menu (requires user parameter) (1435136)
### 🔧 Other Changes


## [v2.102.0] - 2025-11-09

### ✨ Features
- Merge pull request #638 from xjanova/claude/fix-seller-pos-sales-route-011CUwi3TfcDHpAED5oJyUZq (7268553)
- feat: restore 40 missing menu items in admin and seller panels (ced356b)
### 🐛 Bug Fixes

### 🔧 Other Changes


## [v2.101.0] - 2025-11-09

### ✨ Features
- Merge pull request #637 from xjanova/claude/restore-submenu-system-011CUvztH3NScP3Bxk5PJBVk (e6340f5)
- feat: add comprehensive Millennium UI customization system (45d8897)
### 🐛 Bug Fixes

### 🔧 Other Changes


## [v2.100.1] - 2025-11-09

### ✨ Features

### 🐛 Bug Fixes
- Merge pull request #636 from xjanova/claude/fix-seller-pos-sales-route-011CUwi3TfcDHpAED5oJyUZq (0e8fadb)
- fix: add missing seller routes for POS, wallet, reports, and settings (a67cfd5)
### 🔧 Other Changes


## [v2.100.0] - 2025-11-09

### ✨ Features
- Merge pull request #635 from xjanova/claude/restore-submenu-system-011CUvztH3NScP3Bxk5PJBVk (9d43f26)
- feat: improve menu with clear main/submenu distinction (37a16a7)
### 🐛 Bug Fixes

### 🔧 Other Changes


## [v2.99.0] - 2025-11-09

### ✨ Features
- feat: rebuild menu with working submenu system and animations (8ad797f)
### 🐛 Bug Fixes
- fix: remove admin.users.permissions (requires user parameter) (df0ef9c)
### 🔧 Other Changes
- Merge pull request #634 from xjanova/claude/restore-submenu-system-011CUvztH3NScP3Bxk5PJBVk (6e55462)

## [v2.98.6] - 2025-11-09

### ✨ Features

### 🐛 Bug Fixes
- Merge pull request #633 from xjanova/claude/restore-submenu-system-011CUvztH3NScP3Bxk5PJBVk (329a9ff)
- fix: remove learning-center.index from user menu (admin-only route) (c131112)
### 🔧 Other Changes


## [v2.98.5] - 2025-11-09

### ✨ Features

### 🐛 Bug Fixes
- Merge pull request #632 from xjanova/claude/restore-submenu-system-011CUvztH3NScP3Bxk5PJBVk (25191bc)
- fix: remove undefined routes from menu (d4689ef)
### 🔧 Other Changes


## [v2.98.4] - 2025-11-09

### ✨ Features

### 🐛 Bug Fixes

### 🔧 Other Changes
- Merge pull request #631 from xjanova/claude/restore-submenu-system-011CUvztH3NScP3Bxk5PJBVk (bd5b8e0)
- refactor: use millennium-start-menu component in taskbar (3992123)

## [v2.98.3] - 2025-11-09

### ✨ Features

### 🐛 Bug Fixes
- fix: make millenniumMenu function global for Alpine.js access (e4a08ec)
### 🔧 Other Changes
- Merge pull request #630 from xjanova/claude/restore-submenu-system-011CUvztH3NScP3Bxk5PJBVk (eedcce9)
- refactor: redesign menu with simple 3D design (d13809b)

## [v2.98.2] - 2025-11-08

### ✨ Features

### 🐛 Bug Fixes

### 🔧 Other Changes
- Merge pull request #629 from xjanova/claude/restore-submenu-system-011CUvztH3NScP3Bxk5PJBVk (d300932)
- refactor: rebuild entire menu system with working submenu functionality (894d655)

## [v2.98.1] - 2025-11-08

### ✨ Features

### 🐛 Bug Fixes
- Merge pull request #628 from xjanova/claude/fix-cart-syntax-errors-011CUw269SAcXGAztBniFZFq (6b89cc2)
- fix: correct syntax error in cart quantity increment button (df96616)
### 🔧 Other Changes


## [v2.98.0] - 2025-11-08

### ✨ Features
- Merge pull request #627 from xjanova/claude/update-wiki-dark-mode-011CUvbQvf6S3Uqar4A6vgWW (8962427)
- feat: Complete platform-wiki enhancements with animated SVG diagrams and improved UX (405bc36)
### 🐛 Bug Fixes

### 🔧 Other Changes


## [v2.97.0] - 2025-11-08

### ✨ Features
- Merge pull request #626 from xjanova/claude/restore-submenu-system-011CUvztH3NScP3Bxk5PJBVk (4f86e54)
- feat: expand user & seller menus and reduce menu size (b02a98e)
### 🐛 Bug Fixes

### 🔧 Other Changes


## [v2.96.0] - 2025-11-08

### ✨ Features
- Merge pull request #625 from xjanova/claude/restore-submenu-system-011CUvztH3NScP3Bxk5PJBVk (f0c6c90)
- feat: add complete admin menu system with all modules (0c1652f)
### 🐛 Bug Fixes

### 🔧 Other Changes


## [v2.95.1] - 2025-11-08

### ✨ Features

### 🐛 Bug Fixes
- fix: add comprehensive dark mode support to shop cart page (bc7c3c8)
- fix: add dark mode support to main shop pages (b73b5c6)
### 🔧 Other Changes
- Merge pull request #624 from xjanova/claude/fix-dark-mode-shop-011CUvzdfHQUaKjJzgo9gfYo (a4dfc10)

## [v2.95.0] - 2025-11-08

### ✨ Features
- Merge pull request #623 from xjanova/claude/create-ebook-wealth-guide-011CUvyvLWuQTdS6o6HrZWp5 (4d55a22)
- feat: add comprehensive Wealth Guide E-book (เส้นทางเศรษฐี) (13b3db8)
### 🐛 Bug Fixes

### 🔧 Other Changes

