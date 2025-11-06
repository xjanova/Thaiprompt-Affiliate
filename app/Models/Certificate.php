<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Certificate extends Model
{
    use HasFactory;

    protected $fillable = [
        'certificate_number',
        'user_id',
        'article_id',
        'student_name',
        'course_title',
        'completion_percentage',
        'quiz_score',
        'total_hours',
        'issued_at',
        'completed_at',
        'pdf_path',
        'verification_code',
        'is_revoked',
        'revoked_at',
        'revoked_reason',
        'metadata',
    ];

    protected $casts = [
        'completion_percentage' => 'decimal:2',
        'quiz_score' => 'decimal:2',
        'total_hours' => 'integer',
        'issued_at' => 'datetime',
        'completed_at' => 'datetime',
        'is_revoked' => 'boolean',
        'revoked_at' => 'datetime',
        'metadata' => 'array',
    ];

    /**
     * Boot the model
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($certificate) {
            // Generate unique certificate number
            if (empty($certificate->certificate_number)) {
                $certificate->certificate_number = self::generateCertificateNumber();
            }

            // Generate verification code
            if (empty($certificate->verification_code)) {
                $certificate->verification_code = self::generateVerificationCode();
            }

            // Set issued date
            if (empty($certificate->issued_at)) {
                $certificate->issued_at = now();
            }
        });
    }

    /**
     * Get the user who owns the certificate
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the article/course this certificate is for
     */
    public function article()
    {
        return $this->belongsTo(LearningArticle::class, 'article_id');
    }

    /**
     * Generate unique certificate number
     */
    public static function generateCertificateNumber()
    {
        do {
            $year = date('Y');
            $month = date('m');
            $random = strtoupper(Str::random(8));
            $certificateNumber = "CERT-{$year}{$month}-{$random}";
        } while (self::where('certificate_number', $certificateNumber)->exists());

        return $certificateNumber;
    }

    /**
     * Generate verification code
     */
    public static function generateVerificationCode()
    {
        do {
            $code = strtoupper(Str::random(12));
        } while (self::where('verification_code', $code)->exists());

        return $code;
    }

    /**
     * Get verification URL
     */
    public function getVerificationUrlAttribute()
    {
        return route('certificate.verify', $this->verification_code);
    }

    /**
     * Get download URL
     */
    public function getDownloadUrlAttribute()
    {
        return route('certificate.download', $this->id);
    }

    /**
     * Check if certificate is valid (not revoked)
     */
    public function isValid()
    {
        return !$this->is_revoked;
    }

    /**
     * Revoke certificate
     */
    public function revoke($reason = null)
    {
        $this->update([
            'is_revoked' => true,
            'revoked_at' => now(),
            'revoked_reason' => $reason,
        ]);
    }

    /**
     * Get formatted issued date
     */
    public function getFormattedIssuedDateAttribute()
    {
        return $this->issued_at->format('d F Y');
    }

    /**
     * Scope for valid certificates
     */
    public function scopeValid($query)
    {
        return $query->where('is_revoked', false);
    }

    /**
     * Scope for revoked certificates
     */
    public function scopeRevoked($query)
    {
        return $query->where('is_revoked', true);
    }

    /**
     * Scope for user certificates
     */
    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope for course certificates
     */
    public function scopeForCourse($query, $articleId)
    {
        return $query->where('article_id', $articleId);
    }
}
