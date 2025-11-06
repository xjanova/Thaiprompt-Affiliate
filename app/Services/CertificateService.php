<?php

namespace App\Services;

use App\Models\Certificate;
use App\Models\LearningArticle;
use App\Models\User;
use App\Models\UserArticleProgress;
use App\Models\QuizAttempt;
use Illuminate\Support\Facades\Storage;

class CertificateService
{
    /**
     * Generate certificate for a user completing a course
     */
    public function generateCertificate(User $user, LearningArticle $article)
    {
        // Check if certificate already exists
        $existing = Certificate::where('user_id', $user->id)
            ->where('article_id', $article->id)
            ->where('is_revoked', false)
            ->first();

        if ($existing) {
            return $existing;
        }

        // Get user progress
        $progress = UserArticleProgress::where('user_id', $user->id)
            ->where('article_id', $article->id)
            ->first();

        if (!$progress || $progress->status !== 'completed') {
            throw new \Exception('User has not completed this course');
        }

        // Calculate quiz score if quizzes exist
        $quizScore = $this->calculateAverageQuizScore($user->id, $article->id);

        // Calculate total hours
        $totalHours = $progress->time_spent > 0 ? round($progress->time_spent / 3600, 1) : 0;

        // Create certificate
        $certificate = Certificate::create([
            'user_id' => $user->id,
            'article_id' => $article->id,
            'student_name' => $user->name,
            'course_title' => $article->title,
            'completion_percentage' => $progress->progress_percentage,
            'quiz_score' => $quizScore,
            'total_hours' => $totalHours,
            'completed_at' => $progress->completed_at,
            'metadata' => [
                'category' => $article->category->name ?? null,
                'difficulty' => $article->difficulty,
                'instructor' => $article->creator->name ?? null,
            ],
        ]);

        // Generate PDF
        $this->generatePDF($certificate);

        return $certificate;
    }

    /**
     * Calculate average quiz score for a course
     */
    protected function calculateAverageQuizScore($userId, $articleId)
    {
        $article = LearningArticle::find($articleId);
        $quizIds = $article->quizzes()->pluck('id');

        if ($quizIds->isEmpty()) {
            return null;
        }

        $avgScore = QuizAttempt::whereIn('quiz_id', $quizIds)
            ->where('user_id', $userId)
            ->where('passed', true)
            ->avg('percentage');

        return $avgScore ? round($avgScore, 2) : null;
    }

    /**
     * Generate PDF certificate
     */
    public function generatePDF(Certificate $certificate)
    {
        // Generate HTML for certificate
        $html = $this->generateCertificateHTML($certificate);

        // For now, we'll save the HTML
        // In production, you would use a PDF library like DomPDF or wkhtmltopdf
        $filename = "certificate_{$certificate->certificate_number}.html";
        $path = "certificates/{$filename}";

        Storage::disk('public')->put($path, $html);

        $certificate->update([
            'pdf_path' => $path,
        ]);

        return $path;
    }

    /**
     * Generate certificate HTML
     */
    protected function generateCertificateHTML(Certificate $certificate)
    {
        return view('certificates.template', [
            'certificate' => $certificate,
        ])->render();
    }

    /**
     * Verify certificate by verification code
     */
    public function verifyCertificate($verificationCode)
    {
        return Certificate::where('verification_code', $verificationCode)
            ->with(['user', 'article'])
            ->first();
    }

    /**
     * Revoke certificate
     */
    public function revokeCertificate(Certificate $certificate, $reason = null)
    {
        $certificate->revoke($reason);

        // Optionally delete PDF file
        if ($certificate->pdf_path) {
            Storage::disk('public')->delete($certificate->pdf_path);
        }

        return $certificate;
    }

    /**
     * Get all certificates for a user
     */
    public function getUserCertificates(User $user)
    {
        return Certificate::where('user_id', $user->id)
            ->with('article')
            ->valid()
            ->orderBy('issued_at', 'desc')
            ->get();
    }

    /**
     * Check if user can get certificate for course
     */
    public function canIssueCertificate(User $user, LearningArticle $article)
    {
        $progress = UserArticleProgress::where('user_id', $user->id)
            ->where('article_id', $article->id)
            ->first();

        if (!$progress || $progress->status !== 'completed') {
            return false;
        }

        // Check if all required quizzes are passed
        $requiredQuizzes = $article->requiredQuizzes;

        foreach ($requiredQuizzes as $quiz) {
            $passed = QuizAttempt::where('quiz_id', $quiz->id)
                ->where('user_id', $user->id)
                ->where('passed', true)
                ->exists();

            if (!$passed) {
                return false;
            }
        }

        return true;
    }

    /**
     * Auto-issue certificate on course completion
     */
    public function autoIssueCertificate(UserArticleProgress $progress)
    {
        if ($progress->status !== 'completed') {
            return null;
        }

        $user = $progress->user;
        $article = $progress->article;

        if (!$this->canIssueCertificate($user, $article)) {
            return null;
        }

        try {
            return $this->generateCertificate($user, $article);
        } catch (\Exception $e) {
            \Log::error('Failed to auto-issue certificate: ' . $e->getMessage());
            return null;
        }
    }
}
