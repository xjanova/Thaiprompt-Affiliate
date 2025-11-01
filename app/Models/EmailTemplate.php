<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmailTemplate extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'subject',
        'body_html',
        'body_text',
        'category',
        'variables',
        'is_active',
        'language',
        'description',
    ];

    protected $casts = [
        'variables' => 'array',
        'is_active' => 'boolean',
    ];

    /**
     * Scope a query to only include active templates.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope a query to filter by category.
     */
    public function scopeCategory($query, string $category)
    {
        return $query->where('category', $category);
    }

    /**
     * Scope a query to filter by language.
     */
    public function scopeLanguage($query, string $language)
    {
        return $query->where('language', $language);
    }

    /**
     * Render the template with given data.
     */
    public function render(array $data): array
    {
        $subject = $this->replaceVariables($this->subject, $data);
        $bodyHtml = $this->replaceVariables($this->body_html, $data);
        $bodyText = $this->body_text ? $this->replaceVariables($this->body_text, $data) : null;

        return [
            'subject' => $subject,
            'body_html' => $bodyHtml,
            'body_text' => $bodyText,
        ];
    }

    /**
     * Replace template variables with actual values.
     */
    protected function replaceVariables(string $content, array $data): string
    {
        foreach ($data as $key => $value) {
            // Support both {{variable}} and {variable} syntax
            $content = str_replace([
                "{{" . $key . "}}",
                "{" . $key . "}",
            ], $value, $content);
        }

        return $content;
    }

    /**
     * Get template by name and language.
     */
    public static function getByName(string $name, string $language = 'th'): ?self
    {
        return static::where('name', $name)
            ->where('language', $language)
            ->where('is_active', true)
            ->first();
    }

    /**
     * Validate template variables.
     */
    public function validateVariables(array $data): bool
    {
        if (!$this->variables) {
            return true;
        }

        foreach ($this->variables as $variable) {
            if (!isset($data[$variable])) {
                return false;
            }
        }

        return true;
    }
}
