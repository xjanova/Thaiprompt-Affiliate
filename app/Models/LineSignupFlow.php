<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LineSignupFlow extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'step_key',
        'step_order',
        'message_text',
        'message_flex',
        'input_type',
        'validation_rules',
        'validation_error_message',
        'next_step_key',
        'conditional_next_steps',
        'quick_reply_options',
        'is_active',
        'is_skippable',
        'require_ai',
        'ai_prompt',
        'ai_context',
    ];

    protected function casts(): array
    {
        return [
            'message_flex' => 'array',
            'validation_rules' => 'array',
            'conditional_next_steps' => 'array',
            'quick_reply_options' => 'array',
            'ai_context' => 'array',
            'is_active' => 'boolean',
            'is_skippable' => 'boolean',
            'require_ai' => 'boolean',
        ];
    }

    /**
     * Get the next step flow
     */
    public function nextStep()
    {
        return $this->hasOne(LineSignupFlow::class, 'step_key', 'next_step_key');
    }

    /**
     * Scope for active flows
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope ordered by step_order
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('step_order');
    }

    /**
     * Get flow by step key
     */
    public static function getByStepKey(string $stepKey): ?self
    {
        return self::where('step_key', $stepKey)
            ->where('is_active', true)
            ->first();
    }

    /**
     * Get first step
     */
    public static function getFirstStep(): ?self
    {
        return self::active()
            ->ordered()
            ->first();
    }

    /**
     * Get next step based on conditions
     */
    public function getNextStepFor(?array $data = null): ?self
    {
        // Check conditional next steps
        if ($this->conditional_next_steps && $data) {
            foreach ($this->conditional_next_steps as $condition) {
                if ($this->evaluateCondition($condition, $data)) {
                    return self::getByStepKey($condition['next_step_key'] ?? $this->next_step_key);
                }
            }
        }

        // Return default next step
        if ($this->next_step_key) {
            return self::getByStepKey($this->next_step_key);
        }

        return null;
    }

    /**
     * Evaluate condition (simple implementation)
     */
    private function evaluateCondition(array $condition, array $data): bool
    {
        $field = $condition['field'] ?? null;
        $operator = $condition['operator'] ?? '==';
        $value = $condition['value'] ?? null;

        if (!$field || !isset($data[$field])) {
            return false;
        }

        $fieldValue = $data[$field];

        return match($operator) {
            '==' => $fieldValue == $value,
            '===' => $fieldValue === $value,
            '!=' => $fieldValue != $value,
            '!==' => $fieldValue !== $value,
            '>' => $fieldValue > $value,
            '>=' => $fieldValue >= $value,
            '<' => $fieldValue < $value,
            '<=' => $fieldValue <= $value,
            'in' => in_array($fieldValue, (array) $value),
            'not_in' => !in_array($fieldValue, (array) $value),
            'contains' => str_contains($fieldValue, $value),
            default => false,
        };
    }

    /**
     * Validate user input
     */
    public function validateInput(string $input): array
    {
        $errors = [];

        // Basic validation based on input_type
        switch ($this->input_type) {
            case 'phone':
                if (!preg_match('/^[0-9]{9,10}$/', $input)) {
                    $errors[] = 'กรุณากรอกเบอร์โทรศัพท์ให้ถูกต้อง (9-10 หลัก)';
                }
                break;

            case 'email':
                if (!filter_var($input, FILTER_VALIDATE_EMAIL)) {
                    $errors[] = 'กรุณากรอกอีเมลให้ถูกต้อง';
                }
                break;

            case 'name':
                if (strlen($input) < 2) {
                    $errors[] = 'กรุณากรอกชื่ออย่างน้อย 2 ตัวอักษร';
                }
                break;
        }

        // Custom validation rules
        if ($this->validation_rules) {
            foreach ($this->validation_rules as $rule => $params) {
                if (!$this->applyValidationRule($rule, $input, $params)) {
                    $errors[] = $this->validation_error_message ?? "ข้อมูลไม่ถูกต้อง";
                }
            }
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
        ];
    }

    /**
     * Apply validation rule
     */
    private function applyValidationRule(string $rule, string $input, $params): bool
    {
        return match($rule) {
            'required' => !empty($input),
            'min_length' => strlen($input) >= $params,
            'max_length' => strlen($input) <= $params,
            'regex' => preg_match($params, $input) === 1,
            default => true,
        };
    }

    /**
     * Get formatted message with variables replaced
     */
    public function getFormattedMessage(array $variables = []): string
    {
        $message = $this->message_text;

        foreach ($variables as $key => $value) {
            $message = str_replace("{{$key}}", $value, $message);
        }

        return $message;
    }

    /**
     * Get Quick Reply structure for LINE
     */
    public function getQuickReplyStructure(): ?array
    {
        if (!$this->quick_reply_options) {
            return null;
        }

        $items = [];
        foreach ($this->quick_reply_options as $option) {
            $items[] = [
                'type' => 'action',
                'action' => [
                    'type' => 'message',
                    'label' => $option['label'] ?? $option,
                    'text' => $option['value'] ?? $option,
                ],
            ];
        }

        return [
            'items' => $items,
        ];
    }
}
