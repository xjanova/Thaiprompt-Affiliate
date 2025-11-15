# Changelog

ประวัติการเปลี่ยนแปลงของโปรเจค Thai Prompt Affiliate Marketing Platform

## [v3.0.0] - 2025-11-15

### 🎨 Major UI Overhaul - Modern UI v3.0
**Phase 1: Hotels Module (21 files)**
- Upgraded all Hotels admin views with Modern UI v3.0
- Replaced emoji icons with FontAwesome
- Added comprehensive dark mode support
- Enhanced responsive design patterns
- Modules: rooms, bookings, packages, facilities, reviews, reports, settings

**Phase 2: MLM System (20 files)**
- Upgraded all MLM admin views with Modern UI v3.0
- Enhanced visual hierarchy and user experience
- Improved data visualization and charts
- Modules: members, plans, commissions, product-pv, reports, genealogy, calculator, settings

### 🐛 Critical Bug Fixes
- **Bug #1**: Fixed missing sponsor_id relationship in MLM member model
- **Bug #2**: Resolved duplicate PV increment in binary service
- **Bug #3**: Implemented carry forward PV expiry tracking

### 📊 Statistics
- **Total Files Upgraded**: 41 files
- **Total Commits**: 13 commits
- **UI Components**: 100+ components enhanced
- **Dark Mode**: Full support across all modules

### 🚀 Breaking Changes
- Major version bump to v3.0.0 due to significant UI changes
- All admin interfaces now use Modern UI v3.0 design system
- FontAwesome icons replace all emoji/SVG icons
- Dark mode is now default-enabled

### 📝 Notes
- Modern UI upgrade project in progress (Phase 3+: E-Commerce, AI Bots, Seller, User modules)
- Current progress: 41/180+ files (23%)
- Future phases scheduled for upcoming releases

## [v2.251.11] - 2025-11-15

### ✨ Features

### 🐛 Bug Fixes
- Merge pull request #1186 from xjanova/claude/analyze-snake-game-project-01Qfo5tFr3MNSpUWcksZmM7o (3655f6eb)
- fix(routes): move game-settings routes inside games group and update menu route name (40a55c51)
### 🔧 Other Changes


## [v2.251.10] - 2025-11-15

### ✨ Features
- feat(mlm): upgrade settings/index to Modern UI v3.0 (Group 6 complete - 4/4) (ebf76910)
- feat(mlm): upgrade genealogy, calculator, placement-examples to Modern UI v3.0 (3/4 Group 6 WIP) (7b845b21)
### 🐛 Bug Fixes

### 🔧 Other Changes
- Merge pull request #1185 from xjanova/claude/analyze-modern-ui-upgrade-01EpRCcTP6FtQLWBnbEAkgYC (259bad88)

## [v2.251.9] - 2025-11-15

### ✨ Features

### 🐛 Bug Fixes
- Merge pull request #1184 from xjanova/claude/fix-blade-section-error-017XBG95xejKy3XPKJiQPvmG (2b812b9a)
- fix: remove extra @endsection directive in my-positions.blade.php (cd0c2b42)
### 🔧 Other Changes


## [v2.251.8] - 2025-11-15

### ✨ Features

### 🐛 Bug Fixes
- Merge pull request #1183 from xjanova/claude/fix-migration-errors-01RSXEfwRs1YFp9KKpmDTqJR (ba77e284)
- fix: add ALTER migration to update game_settings type ENUM (6bbb4c08)
### 🔧 Other Changes


## [v2.251.7] - 2025-11-15

### ✨ Features

### 🐛 Bug Fixes
- Merge pull request #1182 from xjanova/claude/fix-migration-errors-01RSXEfwRs1YFp9KKpmDTqJR (2d7ad0f9)
- fix: add missing snake_game_types table migration (fca80f4c)
### 🔧 Other Changes


## [v2.251.6] - 2025-11-15

### ✨ Features

### 🐛 Bug Fixes
- Merge pull request #1181 from xjanova/claude/analyze-snake-game-project-01Qfo5tFr3MNSpUWcksZmM7o (88b29c32)
- fix(snake-game): standardize event data structure and prevent errors in event listeners (feadb7ad)
- feat(snake-game): add comprehensive error logging to wallet listeners (a42e9e85)
### 🔧 Other Changes


## [v2.251.5] - 2025-11-15

### ✨ Features

### 🐛 Bug Fixes
- Merge pull request #1180 from xjanova/claude/analyze-snake-game-project-01Qfo5tFr3MNSpUWcksZmM7o (98a7e78d)
- fix(snake-game): standardize event data access pattern for consistency (a8dfb87a)
### 🔧 Other Changes


## [v2.251.4] - 2025-11-15

### ✨ Features

### 🐛 Bug Fixes
- Merge pull request #1179 from xjanova/claude/analyze-snake-game-project-01Qfo5tFr3MNSpUWcksZmM7o (db2efc38)
- fix: emit GameEventProcessed event directly instead of returning response (2daf9c95)
### 🔧 Other Changes


## [v2.251.3] - 2025-11-15

### ✨ Features

### 🐛 Bug Fixes
- Merge pull request #1178 from xjanova/claude/fix-admin-routes-01VNexsNrpRskEi2yRWGtJD8 (d8f4d51f)
- fix(routes): move tools.game-settings route outside group to prevent duplicate route error (8f9e2d24)
### 🔧 Other Changes


## [v2.251.2] - 2025-11-15

### ✨ Features

### 🐛 Bug Fixes
- Merge pull request #1177 from xjanova/claude/analyze-snake-game-project-01Qfo5tFr3MNSpUWcksZmM7o (e7a07f83)
- fix(routes): wrap bot_automation include in middleware group to prevent route loading error (9e25f5e0)
### 🔧 Other Changes


## [v2.251.1] - 2025-11-15

### ✨ Features

### 🐛 Bug Fixes
- Merge pull request #1176 from xjanova/claude/analyze-snake-game-project-01Qfo5tFr3MNSpUWcksZmM7o (91ab3c1b)
- fix(routes): standardize admin routes to prevent 404 errors (c89a3e6a)
### 🔧 Other Changes


## [v2.251.0] - 2025-11-15

### ✨ Features
- Merge pull request #1175 from xjanova/claude/analyze-snake-game-project-01Qfo5tFr3MNSpUWcksZmM7o (a7f4cbb5)
- feat(snake-game): add game event processing and wallet listeners (WIP) (59beb82b)
### 🐛 Bug Fixes

### 🔧 Other Changes


## [v2.250.0] - 2025-11-15

### ✨ Features
- feat: implement client-side service status polling for Snake.io (f70fabe6)
- feat: add Snake.io Admin Dashboard with service monitoring (WIP) (0a7f661c)
### 🐛 Bug Fixes

### 🔧 Other Changes
- Merge pull request #1167 from xjanova/claude/snake-game-wallet-018Dqk7vxEKCSzTighZZQmES (420b3de6)

## [v2.249.3] - 2025-11-15

### ✨ Features
- feat(hotels): upgrade Reviews Detail to Modern UI v3.0 (7/36 Hotels Phase 2) (c847ae34)
- feat(hotels): upgrade Reviews Index to Modern UI v3.0 (6/36 Hotels Phase 2) (8793c7e3)
- feat(hotels): upgrade Rooms Availability Calendar to Modern UI v3.0 (5/36 Hotels Phase 2) (18a4b524)
