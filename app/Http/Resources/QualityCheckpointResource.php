<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class QualityCheckpointResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'checkpoint_type' => $this->checkpoint_type,
            'checkpoint_name' => $this->checkpoint_name,
            'checked_at' => $this->checked_at?->toIso8601String(),

            // Inspector
            'inspector' => [
                'id' => $this->inspector_id,
                'name' => $this->inspector_name,
                'license' => $this->inspector_license,
                'organization' => $this->organization,
            ],

            // Results
            'result' => [
                'overall' => $this->overall_result,
                'score' => $this->pass_score,
                'percentage' => $this->pass_percentage,
                'color' => $this->result_color,
                'passed' => $this->passed(),
            ],

            // Test parameters
            'test_parameters' => $this->test_parameters,

            // Standards
            'standards_applied' => $this->standards_applied,

            // Certification
            'certification' => [
                'issued' => $this->certification_issued,
                'number' => $this->certificate_number,
                'expiry' => $this->certificate_expiry?->format('Y-m-d'),
            ],

            // Documentation
            'test_report_url' => $this->test_report_url,
            'photos' => $this->photos,
            'notes' => $this->notes,

            // Corrective actions
            'corrective_actions' => $this->corrective_actions,
            'retest_required' => $this->retest_required,

            // Blockchain
            'blockchain_hash' => $this->blockchain_hash,

            // Timestamps
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
