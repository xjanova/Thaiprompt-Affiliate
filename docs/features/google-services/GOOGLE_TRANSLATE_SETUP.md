# Google Translate API Integration

This application now supports Google Cloud Translation API for automatic content translation across multiple languages.

## Features

- Automatic translation of page content
- Support for 9 languages (English, Thai, Chinese, Japanese, Korean, Vietnamese, Spanish, French, German)
- Smart caching to reduce API calls
- Fallback to session-based language switching
- Easy configuration
- Admin settings for managing translation preferences

## Setup Instructions

### Step 1: Install Dependencies

The required package is already added to `composer.json`. Run:

```bash
composer install
```

This will install `google/cloud-translate` package.

### Step 2: Get Google Cloud Credentials

#### Option A: Using API Key (Simpler)

1. Go to [Google Cloud Console](https://console.cloud.google.com/)
2. Create a new project or select an existing one
3. Enable the **Cloud Translation API**
4. Go to **APIs & Services** > **Credentials**
5. Click **Create Credentials** > **API Key**
6. Copy the generated API key

Add to your `.env`:
```env
GOOGLE_TRANSLATE_ENABLED=true
GOOGLE_TRANSLATE_API_KEY=your-api-key-here
```

#### Option B: Using Service Account (Recommended for Production)

1. Go to [Google Cloud Console](https://console.cloud.google.com/)
2. Create a new project or select an existing one
3. Enable the **Cloud Translation API**
4. Go to **IAM & Admin** > **Service Accounts**
5. Click **Create Service Account**
6. Give it a name and grant **Cloud Translation API User** role
7. Click **Create Key** and download the JSON file
8. Save the JSON file to `storage/app/google-credentials.json`

Add to your `.env`:
```env
GOOGLE_TRANSLATE_ENABLED=true
GOOGLE_TRANSLATE_PROJECT_ID=your-project-id
GOOGLE_TRANSLATE_CREDENTIALS=storage/app/google-credentials.json
```

### Step 3: Configure Environment Variables

Add these variables to your `.env` file:

```env
# Google Translate Configuration
GOOGLE_TRANSLATE_ENABLED=true
GOOGLE_TRANSLATE_API_KEY=your-api-key-here
# OR
GOOGLE_TRANSLATE_PROJECT_ID=your-project-id
GOOGLE_TRANSLATE_CREDENTIALS=storage/app/google-credentials.json

# Translation Cache Settings
TRANSLATE_CACHE_ENABLED=true
TRANSLATE_CACHE_TTL=86400

# Source Language (your primary content language)
TRANSLATE_SOURCE_LANGUAGE=th
```

### Step 4: Clear Configuration Cache

```bash
php artisan config:clear
php artisan cache:clear
```

## Usage

### Frontend Usage

#### Using the Advanced Language Switcher

Replace the standard language switcher in your layout:

```blade
<!-- Old -->
@include('components.language-switcher')

<!-- New (with Google Translate) -->
@include('components.language-switcher-advanced')
```

#### Making Content Translatable

Add `data-translate` attribute to elements you want to be translated:

```html
<h1 data-translate>ยินดีต้อนรับ</h1>
<p data-translate>เนื้อหาที่ต้องการแปล</p>
```

### API Endpoints

#### Translate Single Text

```javascript
const response = await fetch('/api/translate', {
    method: 'POST',
    headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content
    },
    body: JSON.stringify({
        text: 'สวัสดี',
        target_lang: 'en',
        source_lang: 'th'
    })
});

const data = await response.json();
console.log(data.translated); // "Hello"
```

#### Translate Multiple Texts (Batch)

```javascript
const response = await fetch('/api/translate/batch', {
    method: 'POST',
    headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content
    },
    body: JSON.stringify({
        texts: ['สวัสดี', 'ขอบคุณ', 'ลาก่อน'],
        target_lang: 'en',
        source_lang: 'th'
    })
});

const data = await response.json();
console.log(data.translations); // ["Hello", "Thank you", "Goodbye"]
```

#### Get Available Languages

```javascript
const response = await fetch('/api/translate/languages');
const data = await response.json();
console.log(data.languages);
```

#### Check Translation Status

```javascript
const response = await fetch('/api/translate/status');
const data = await response.json();
console.log(data.enabled); // true/false
```

### Backend Usage

#### In Controllers or Services

```php
use App\Services\TranslationService;

class MyController extends Controller
{
    protected TranslationService $translator;

    public function __construct(TranslationService $translator)
    {
        $this->translator = $translator;
    }

    public function translate()
    {
        $translated = $this->translator->translate(
            'สวัสดีครับ',
            'en',  // target language
            'th'   // source language (optional)
        );

        // $translated will be "Hello"
    }

    public function translateMultiple()
    {
        $texts = ['สวัสดี', 'ขอบคุณ', 'ลาก่อน'];
        $translated = $this->translator->translateBatch($texts, 'en', 'th');

        // $translated will be ["Hello", "Thank you", "Goodbye"]
    }
}
```

## Supported Languages

| Code | Language | Native Name |
|------|----------|------------|
| en | English | English |
| th | Thai | ไทย |
| zh | Chinese | 中文 |
| ja | Japanese | 日本語 |
| ko | Korean | 한국어 |
| vi | Vietnamese | Tiếng Việt |
| es | Spanish | Español |
| fr | French | Français |
| de | German | Deutsch |

You can add more languages in `config/translate.php`.

## Configuration

All translation settings can be found in `config/translate.php`:

- `google.enabled` - Enable/disable Google Translate
- `google.api_key` - Your API key
- `google.credentials_path` - Path to service account JSON
- `supported_languages` - List of available languages
- `cache.enabled` - Enable translation caching
- `cache.ttl` - Cache time-to-live in seconds
- `source_language` - Your primary content language

## Caching

Translations are automatically cached to reduce API costs and improve performance:

- Default TTL: 24 hours
- Cache prefix: `translate:`
- Cache key format: `translate:{source}:{target}:{text_hash}`

Clear translation cache:

```php
$translator = app(\App\Services\TranslationService::class);
$translator->clearCache();
```

## Troubleshooting

### Translation Not Working

1. Check if API is enabled in `.env`:
   ```bash
   php artisan tinker
   config('translate.google.enabled')
   ```

2. Verify credentials are correct and API is enabled in Google Cloud Console

3. Check logs:
   ```bash
   tail -f storage/logs/laravel.log
   ```

4. Test translation service:
   ```bash
   php artisan tinker
   $translator = app(\App\Services\TranslationService::class);
   $translator->isEnabled(); // Should return true
   $translator->translate('Hello', 'th', 'en');
   ```

### API Quota Exceeded

- Google offers 500,000 characters/month for free
- Enable caching to reduce API calls
- Consider upgrading your Google Cloud plan

### Slow Translation

- Ensure caching is enabled (`TRANSLATE_CACHE_ENABLED=true`)
- Use batch translation for multiple texts
- Consider translating only essential content

## Cost Estimation

Google Cloud Translation pricing (as of 2024):
- First 500,000 characters/month: FREE
- After that: $20 per million characters

With caching enabled, most sites will stay within the free tier.

## Security Notes

1. **Never commit credentials** to version control
2. Add `google-credentials.json` to `.gitignore`
3. Use environment variables for sensitive data
4. Restrict API key to specific domains/IPs in Google Cloud Console
5. Regularly rotate API keys

## Support

For issues or questions:
1. Check the logs: `storage/logs/laravel.log`
2. Verify Google Cloud Console settings
3. Review this documentation
4. Contact support team

---

**Note:** This is an optional feature. The application will continue to work with session-based language switching if Google Translate is not configured.
