{{-- Google Translate Widget Component (Like WordPress Plugins) --}}
@php
    use App\Models\LanguageSetting;
    use App\Models\Setting;

    $languages = LanguageSetting::getEnabled();
    $currentLang = app()->getLocale();
    $defaultLang = LanguageSetting::getDefault();
    $defaultCode = $defaultLang ? $defaultLang->code : 'th';

    // Get enabled language codes for widget
    $enabledCodes = $languages->pluck('code')->map(function($code) {
        // Map to Google Translate language codes
        return match($code) {
            'zh' => 'zh-CN',
            'ar' => 'ar',
            'hi' => 'hi',
            'pt' => 'pt',
            default => $code
        };
    })->join(',');
@endphp

<div id="google-translate-widget" class="google-translate-widget">
    {{-- Hidden div for Google Translate Widget --}}
    <div id="google_translate_element" style="display: none;"></div>
</div>

{{-- Google Translate Widget Script --}}
<script type="text/javascript">
// Global function required by Google Translate
function googleTranslateElementInit() {
    if (typeof google !== 'undefined' && google.translate) {
        new google.translate.TranslateElement({
            pageLanguage: '{{ $defaultCode }}',
            includedLanguages: '{{ $enabledCodes }}',
            layout: google.translate.TranslateElement.InlineLayout.SIMPLE,
            autoDisplay: false,
            multilanguagePage: true
        }, 'google_translate_element');

        console.log('Google Translate Widget initialized');
    }
}

// Load Google Translate Widget Script
(function() {
    const script = document.createElement('script');
    script.type = 'text/javascript';
    script.src = '//translate.google.com/translate_a/element.js?cb=googleTranslateElementInit';
    script.async = true;
    document.head.appendChild(script);
})();

// Helper function to trigger Google Translate
window.changeGoogleTranslateLanguage = function(langCode) {
    // Map language codes to Google Translate format
    const langMap = {
        'zh': 'zh-CN',
        'ar': 'ar',
        'hi': 'hi',
        'pt': 'pt'
    };

    const googleLangCode = langMap[langCode] || langCode;

    // Wait for Google Translate to be ready
    let attempts = 0;
    const maxAttempts = 20;

    const interval = setInterval(function() {
        attempts++;

        // Find the Google Translate select element
        const selectElement = document.querySelector('.goog-te-combo');

        if (selectElement) {
            // Change the value and trigger change event
            selectElement.value = googleLangCode;
            selectElement.dispatchEvent(new Event('change'));
            clearInterval(interval);
            console.log('Language changed to:', googleLangCode);
        } else if (attempts >= maxAttempts) {
            clearInterval(interval);
            console.error('Google Translate widget not found after', maxAttempts, 'attempts');

            // Fallback to session-based language change
            window.location.href = '/lang/' + langCode;
        }
    }, 200); // Check every 200ms
};

// Get current Google Translate language
window.getCurrentGoogleTranslateLanguage = function() {
    const selectElement = document.querySelector('.goog-te-combo');
    if (selectElement && selectElement.value) {
        return selectElement.value;
    }
    return '{{ $defaultCode }}';
};
</script>

{{-- Hide Google Translate Banner and Customize Styling --}}
<style>
/* Hide the Google Translate banner */
.goog-te-banner-frame.skiptranslate {
    display: none !important;
}

body {
    top: 0 !important;
}

/* Hide the default Google Translate widget (we'll use our custom switcher) */
#google_translate_element {
    display: none !important;
}

.goog-te-gadget {
    display: none !important;
}

/* Remove Google Translate branding from footer */
.goog-logo-link,
.goog-te-gadget span {
    display: none !important;
}

/* Ensure translated content maintains styling */
.translated-ltr,
.translated-rtl {
    font-family: inherit !important;
}

/* Fix any layout issues caused by Google Translate */
#goog-gt-tt,
.goog-text-highlight {
    display: none !important;
}

/* Prevent notification bar */
.goog-te-banner-frame {
    display: none !important;
}

iframe.goog-te-banner-frame {
    display: none !important;
}

/* Hide top frame */
.goog-te-banner-frame.skiptranslate {
    display: none !important;
}

body > .skiptranslate {
    display: none !important;
}

body {
    position: static !important;
    top: 0 !important;
}
</style>

{{-- Language detection and auto-switch on page load --}}
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Get current locale from session
    const currentLocale = '{{ $currentLang }}';
    const defaultLocale = '{{ $defaultCode }}';

    // If current locale is different from default, trigger translation
    if (currentLocale !== defaultLocale) {
        // Wait a bit for Google Translate to initialize
        setTimeout(function() {
            window.changeGoogleTranslateLanguage(currentLocale);
        }, 1000);
    }
});
</script>
