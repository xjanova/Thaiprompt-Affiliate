<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

class AcademySetting extends Model
{
    use HasFactory;

    protected $fillable = [
        // Basic Information
        'academy_name',
        'academy_description',
        'academy_email',
        'academy_phone',
        'academy_website',

        // Logo & Branding
        'logo_path',
        'small_logo_path',
        'favicon_path',
        'primary_color',
        'secondary_color',

        // Certificate Template Settings
        'certificate_template_type',
        'certificate_header_html',
        'certificate_footer_html',
        'certificate_background_path',
        'certificate_border_style',

        // Signatures
        'signatures',

        // Certificate Template Files
        'certificate_word_template_path',
        'certificate_pdf_template_path',

        // Email Settings
        'send_certificate_email',
        'certificate_email_subject',
        'certificate_email_body',

        // Course Completion Settings
        'min_completion_percentage',
        'min_quiz_score',
        'require_all_quizzes',

        // Certificate Numbering
        'certificate_number_prefix',
        'certificate_number_length',
        'certificate_number_format',

        // Other Settings
        'enable_student_reviews',
        'enable_course_discussions',
        'enable_course_notes',
        'enable_course_downloads',
        'require_enrollment_approval',
        'enable_course_preview',

        // Instructor Settings
        'allow_instructors_create_courses',
        'require_course_approval',
        'instructor_commission_percentage',

        // System Settings
        'is_active',
        'additional_settings',
    ];

    protected $casts = [
        'signatures' => 'array',
        'additional_settings' => 'array',
        'send_certificate_email' => 'boolean',
        'require_all_quizzes' => 'boolean',
        'enable_student_reviews' => 'boolean',
        'enable_course_discussions' => 'boolean',
        'enable_course_notes' => 'boolean',
        'enable_course_downloads' => 'boolean',
        'require_enrollment_approval' => 'boolean',
        'enable_course_preview' => 'boolean',
        'allow_instructors_create_courses' => 'boolean',
        'require_course_approval' => 'boolean',
        'is_active' => 'boolean',
        'min_completion_percentage' => 'integer',
        'min_quiz_score' => 'integer',
        'instructor_commission_percentage' => 'integer',
        'certificate_number_length' => 'integer',
    ];

    /**
     * Get the singleton instance of Academy Settings
     */
    public static function getInstance()
    {
        return Cache::remember('academy_settings', 3600, function () {
            return self::first() ?? self::create([
                'academy_name' => 'Academy Platform',
                'primary_color' => '#6366f1',
                'secondary_color' => '#06b6d4',
            ]);
        });
    }

    /**
     * Get a specific setting value
     */
    public static function get($key, $default = null)
    {
        $instance = self::getInstance();
        return $instance->$key ?? $default;
    }

    /**
     * Set a specific setting value
     */
    public static function set($key, $value)
    {
        $instance = self::getInstance();
        $instance->update([$key => $value]);
        self::clearCache();
        return $instance;
    }

    /**
     * Clear settings cache
     */
    public static function clearCache()
    {
        Cache::forget('academy_settings');
    }

    /**
     * Get logo URL
     */
    public function getLogoUrlAttribute()
    {
        if (!$this->logo_path) {
            return null;
        }
        return Storage::disk('public')->url($this->logo_path);
    }

    /**
     * Get small logo URL
     */
    public function getSmallLogoUrlAttribute()
    {
        if (!$this->small_logo_path) {
            return $this->logo_url;
        }
        return Storage::disk('public')->url($this->small_logo_path);
    }

    /**
     * Get favicon URL
     */
    public function getFaviconUrlAttribute()
    {
        if (!$this->favicon_path) {
            return null;
        }
        return Storage::disk('public')->url($this->favicon_path);
    }

    /**
     * Get certificate background URL
     */
    public function getCertificateBackgroundUrlAttribute()
    {
        if (!$this->certificate_background_path) {
            return null;
        }
        return Storage::disk('public')->url($this->certificate_background_path);
    }

    /**
     * Get signatures with full URLs
     */
    public function getSignaturesWithUrlsAttribute()
    {
        if (!$this->signatures) {
            return [];
        }

        return collect($this->signatures)->map(function ($signature) {
            if (isset($signature['signature_path'])) {
                $signature['signature_url'] = Storage::disk('public')->url($signature['signature_path']);
            }
            return $signature;
        })->toArray();
    }

    /**
     * Get certificate template URL (Word)
     */
    public function getCertificateWordTemplateUrlAttribute()
    {
        if (!$this->certificate_word_template_path) {
            return null;
        }
        return Storage::disk('public')->url($this->certificate_word_template_path);
    }

    /**
     * Get certificate template URL (PDF)
     */
    public function getCertificatePdfTemplateUrlAttribute()
    {
        if (!$this->certificate_pdf_template_path) {
            return null;
        }
        return Storage::disk('public')->url($this->certificate_pdf_template_path);
    }

    /**
     * Add a signature
     */
    public function addSignature($name, $title, $signaturePath, $position = 'center')
    {
        $signatures = $this->signatures ?? [];
        $signatures[] = [
            'name' => $name,
            'title' => $title,
            'signature_path' => $signaturePath,
            'position' => $position,
            'added_at' => now()->toDateTimeString(),
        ];
        $this->update(['signatures' => $signatures]);
        self::clearCache();
    }

    /**
     * Remove a signature by index
     */
    public function removeSignature($index)
    {
        $signatures = $this->signatures ?? [];
        if (isset($signatures[$index])) {
            // Delete the signature file
            if (isset($signatures[$index]['signature_path'])) {
                Storage::disk('public')->delete($signatures[$index]['signature_path']);
            }
            unset($signatures[$index]);
            $this->update(['signatures' => array_values($signatures)]);
            self::clearCache();
        }
    }

    /**
     * Upload logo
     */
    public function uploadLogo($file, $type = 'main')
    {
        $fieldMap = [
            'main' => 'logo_path',
            'small' => 'small_logo_path',
            'favicon' => 'favicon_path',
        ];

        $field = $fieldMap[$type] ?? 'logo_path';

        // Delete old file
        if ($this->$field) {
            Storage::disk('public')->delete($this->$field);
        }

        // Store new file
        $path = $file->store('academy/logos', 'public');
        $this->update([$field => $path]);
        self::clearCache();

        return $path;
    }

    /**
     * Upload certificate background
     */
    public function uploadCertificateBackground($file)
    {
        // Delete old file
        if ($this->certificate_background_path) {
            Storage::disk('public')->delete($this->certificate_background_path);
        }

        // Store new file
        $path = $file->store('academy/certificates/backgrounds', 'public');
        $this->update(['certificate_background_path' => $path]);
        self::clearCache();

        return $path;
    }

    /**
     * Upload certificate template
     */
    public function uploadCertificateTemplate($file, $type = 'word')
    {
        $fieldMap = [
            'word' => 'certificate_word_template_path',
            'pdf' => 'certificate_pdf_template_path',
        ];

        $field = $fieldMap[$type] ?? 'certificate_word_template_path';

        // Delete old file
        if ($this->$field) {
            Storage::disk('public')->delete($this->$field);
        }

        // Store new file
        $path = $file->store('academy/certificates/templates', 'public');
        $this->update([$field => $path]);
        self::clearCache();

        return $path;
    }

    /**
     * Get default certificate email subject
     */
    public function getDefaultCertificateEmailSubject()
    {
        return $this->certificate_email_subject ?? 'ยินดีด้วย! คุณได้รับใบประกาศนียบัตร';
    }

    /**
     * Get default certificate email body
     */
    public function getDefaultCertificateEmailBody()
    {
        return $this->certificate_email_body ?? 'ยินดีด้วยที่เรียนจบคอร์ส {course_title} ดาวน์โหลดใบประกาศนียบัตรของคุณได้ที่ด้านล่าง';
    }
}
