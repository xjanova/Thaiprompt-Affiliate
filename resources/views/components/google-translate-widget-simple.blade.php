{{-- Simple Google Translate Widget for Testing --}}
@php
    use App\Models\LanguageSetting;
    $languages = LanguageSetting::getEnabled();
    $defaultLang = LanguageSetting::getDefault();
    $defaultCode = $defaultLang ? $defaultLang->code : 'th';

    // Get enabled language codes
    $enabledCodes = $languages->pluck('code')->map(function($code) {
        return match($code) {
            'zh' => 'zh-CN',
            default => $code
        };
    })->join(',');
@endphp

{{-- Visible Google Translate Widget for Testing --}}
<div id="google_translate_element"></div>

<script type="text/javascript">
function googleTranslateElementInit() {
    new google.translate.TranslateElement({
        pageLanguage: '{{ $defaultCode }}',
        includedLanguages: '{{ $enabledCodes }}',
        layout: google.translate.TranslateElement.InlineLayout.HORIZONTAL
    }, 'google_translate_element');
}
</script>

<script type="text/javascript" src="//translate.google.com/translate_a/element.js?cb=googleTranslateElementInit"></script>

<style>
/* Hide Google banner */
.goog-te-banner-frame.skiptranslate {
    display: none !important;
}

body {
    top: 0 !important;
}

/* Style the widget */
#google_translate_element {
    padding: 10px;
}

.goog-te-gadget {
    font-family: inherit !important;
}

.goog-te-gadget-simple {
    background-color: #fff;
    border: 1px solid #d1d5db;
    border-radius: 0.5rem;
    padding: 0.5rem 1rem;
    font-size: 0.875rem;
    color: #374151;
}

.goog-te-gadget-simple:hover {
    background-color: #f9fafb;
}
</style>
