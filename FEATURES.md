# New Features Documentation

## Overview

This document describes the new features added to the Thaiprompt Affiliate Marketplace system.

## 1. Version Update System

### Description
Automatic version checking system that compares the current installation with the latest version on GitHub and notifies super admin when updates are available.

### Features
- **Automatic version checking** from GitHub releases
- **Update notifications** for super admin
- **Update history** tracking
- **Manual update process** with step-by-step instructions
- **Critical update flagging** for security patches
- **Changelog display** with breaking changes

### Usage

#### For Super Admin:
1. Navigate to **Admin > Version Management**
2. Click "Check for Updates" to manually check
3. System automatically checks every 24 hours
4. View update details, changelog, and release notes
5. Follow the instructions to update the system

#### API Endpoints:
- `GET /api/v1/version` - Get current version info
- `GET /api/v1/version/check` - Check for updates
- `POST /admin/version/update/start` - Start update process
- `POST /admin/version/update/complete` - Mark update as completed

### Technical Details
- GitHub API integration
- Semantic versioning support
- Update logs for audit trail
- Rollback capability

---

## 2. NFC Payment System

### Description
Web NFC API integration for contactless card payments, allowing users to pay using NFC cards through supported devices.

### Features
- **Web NFC API** support for modern browsers
- **Card registration** and management
- **User linking** - Link NFC cards to user wallets
- **Payment processing** via NFC tap
- **Balance checking** without payment
- **Transaction logging** for all NFC operations
- **Multi-device support** - Web and future mobile app ready

### Usage

#### For Customers:
1. Navigate to **NFC Payment** page
2. Tap NFC-enabled device with card
3. Confirm payment amount
4. Complete transaction

#### For Super Admin:
1. Navigate to **Admin > NFC Cards**
2. **Register new cards** by scanning or entering card UID
3. **Link cards to users** and their wallets
4. **View transaction history**
5. **Activate/deactivate** cards as needed

#### For POS Terminals:
- Use `/nfc/payment` endpoint
- Scan customer's NFC card
- Process payment automatically

### Supported Card Types
- Standard (basic NFC cards)
- Premium (enhanced features)
- VIP (priority support)

### Technical Details
- Web NFC API (Chrome/Android support)
- Unique card UID identification
- Encrypted transaction processing
- Real-time balance updates

### API Endpoints:
- `POST /api/v1/nfc/process` - Process payment
- `POST /api/v1/nfc/check-balance` - Check card balance
- `POST /api/v1/nfc/verify` - Verify card validity
- `POST /api/v1/nfc/card-info` - Get card information

### Browser Support
- Chrome 89+ (Android)
- Chrome 114+ (Desktop with flag)
- Edge 89+ (Android)

---

## 3. Shop Verification System

### Description
Comprehensive KYC (Know Your Customer) system for vendors to verify their shop authenticity with multiple verification levels.

### Features
- **Document upload** system for verification
- **Multi-level badges** (Bronze, Silver, Gold, Platinum)
- **Document types supported:**
  - Business registration
  - Tax certificate
  - Business license
  - ID card (front/back)
  - Selfie with ID
  - Bank statements
  - Bank book photos
- **Admin review** interface
- **Rejection with feedback**
- **Verification badges** displayed on shop profiles

### Verification Badges

#### 🥉 Bronze - Basic Verification
- Requirements: ID card verification
- Benefits: Basic trust badge

#### 🥈 Silver - Full Verification
- Requirements: ID + Business registration + Tax certificate
- Benefits: Enhanced visibility in listings

#### 🥇 Gold - Premium Verification
- Requirements: Silver requirements + Bank verification
- Benefits: Featured placement, higher trust

#### 💎 Platinum - Ultimate Verification
- Requirements: Gold requirements + Business license
- Benefits: Top placement, maximum trust, exclusive features

### Usage

#### For Vendors:
1. Navigate to **Vendor > Verification**
2. Fill out verification form
3. Upload required documents
4. Submit for review
5. Wait for admin approval
6. Receive verification badge

#### For Admin:
1. Navigate to **Admin > Verification**
2. Review pending verifications
3. Check all submitted documents
4. Approve/Reject with feedback
5. Assign appropriate badge level

### Technical Details
- Secure document storage
- Automatic badge calculation based on documents
- Verification status tracking
- Email notifications for status changes

---

## 4. MLM Network Tree Visualization

### Description
Modern, interactive organizational tree visualization using D3.js for displaying MLM network structure.

### Features
- **Interactive tree diagram** with zoom and pan
- **Real-time data** display
- **Member details on hover** (tooltip)
- **Click to view full details**
- **Add members directly** from tree
- **Visual indicators:**
  - 🟢 Green border: KYC verified
  - 🔴 Red border: Not maintaining minimum sales
  - ⚫ Gray border: Normal status
- **Avatar display:**
  - Default avatar for new users
  - LINE profile picture after KYC
  - Custom uploaded avatar support
- **Rank display** with colored badges
- **Level-based layout**
- **Expandable/collapsible** branches

### Usage

#### For Users:
1. Navigate to **MLM > Network Tree**
2. View your downline structure
3. Hover over members for quick info
4. Click members for detailed view
5. Use zoom controls to navigate large trees

#### For Admin:
1. View any user's tree
2. Add members to downline
3. Monitor network health
4. Track inactive members (red border)

### Interactive Features
- **Zoom:** Scroll wheel or pinch
- **Pan:** Click and drag
- **Center:** Double-click background
- **Details:** Click on member avatar
- **Add Member:** Click "+" button (admin)

### Technical Details
- D3.js hierarchy layout
- SVG rendering for performance
- Lazy loading for large trees
- Real-time sales tracking
- Configurable depth limits

### API Endpoints:
- `GET /mlm/tree-data/{userId?}` - Get tree structure
- `GET /mlm/tree/node/{user}` - Get member details
- `POST /mlm/tree/add-member` - Add new member

---

## 5. Profile Image Management

### Description
Enhanced profile image system with LINE integration and custom upload support.

### Features
- **Default avatars** for new users
- **LINE profile sync** after KYC verification
- **Custom upload** capability
- **Automatic source tracking**
- **Image optimization**

### Usage

#### For Users:
1. Navigate to **Profile Settings**
2. Choose image source:
   - Use LINE profile picture (after KYC)
   - Upload custom image
3. Save changes

### Technical Details
- Image storage in public disk
- Multiple source support
- Automatic resizing
- CDN-ready

---

## Installation & Setup

### 1. Install Dependencies

bash
# Install PHP dependencies
composer install

# Install NPM dependencies
npm install

# Build assets
npm run build


### 2. Run Migrations

bash
php artisan migrate


### 3. Seed Initial Data

bash
php artisan db:seed


### 4. Set Permissions

bash
chmod -R 755 storage
chmod -R 755 bootstrap/cache


### 5. Configure Environment

Add to `.env`:

env
# NFC Configuration
NFC_ENABLED=true
NFC_TIMEOUT=30

# Version Update
GITHUB_REPO=xjanova/Thaiprompt-Affiliate
VERSION_CHECK_INTERVAL=24

# Verification
VERIFICATION_DOCUMENT_DISK=public
MAX_DOCUMENT_SIZE=5120


---

## Configuration

### NFC Settings
- Card types: standard, premium, vip
- Transaction timeout: 30 seconds
- Auto-balance check: enabled

### Verification Settings
- Max document size: 5MB
- Allowed formats: PDF, JPG, PNG
- Review turnaround: 48 hours

### MLM Tree Settings
- Max depth: 5 levels
- Node size: 120x80px
- Animation duration: 750ms

---

## Security Considerations

### NFC Security
- Card UID encryption
- Transaction signing
- IP address logging
- Rate limiting

### Document Security
- Encrypted storage
- Access control
- Audit logging
- Auto-deletion after verification

### API Security
- Token authentication
- CORS protection
- Input validation
- SQL injection prevention

---

## Browser Compatibility

### NFC Support
- ✅ Chrome 89+ (Android)
- ✅ Chrome 114+ (Desktop with flag)
- ✅ Edge 89+ (Android)
- ❌ Safari (not supported)
- ❌ Firefox (not supported)

### Tree Visualization
- ✅ All modern browsers
- ✅ IE11+ (with polyfills)
- ✅ Mobile browsers

---

## Troubleshooting

### NFC Issues
1. **"NFC not supported"**: Use Chrome on Android or enable flag on Desktop
2. **Card not detected**: Check NFC is enabled on device
3. **Payment failed**: Verify card is linked and has sufficient balance

### Verification Issues
1. **Upload failed**: Check file size and format
2. **Status stuck**: Contact admin for review
3. **Badge not showing**: Clear cache and refresh

### Tree Issues
1. **Tree not loading**: Check API permissions
2. **Performance slow**: Reduce depth limit
3. **Member not showing**: Verify downline relationships

---

## API Documentation

### Authentication
All protected endpoints require Bearer token:

http
Authorization: Bearer {token}


### Rate Limiting
- Public endpoints: 60 requests/minute
- Authenticated: 120 requests/minute
- Admin: Unlimited

### Response Format
json
{
    "success": true,
    "data": {},
    "message": "Success message"
}


---

## Support

For issues or questions:
- Email: support@thaiprompt.com
- GitHub Issues: https://github.com/xjanova/Thaiprompt-Affiliate/issues
- Documentation: https://docs.thaiprompt.com

---

## Changelog

### Version 1.1.0 (Current)
- ✨ Added NFC payment system
- ✨ Added shop verification with badges
- ✨ Added version update checker
- ✨ Added MLM tree visualization
- ✨ Added profile image management
- 🐛 Bug fixes and improvements

### Version 1.0.0
- 🎉 Initial release
- Multi-vendor marketplace
- MLM system
- Wallet system
- POS integration
