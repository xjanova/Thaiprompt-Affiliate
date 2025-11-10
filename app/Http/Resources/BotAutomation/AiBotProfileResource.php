<?php

namespace App\Http\Resources\BotAutomation;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AiBotProfileResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'display_name' => $this->display_name,
            'description' => $this->description,
            'avatar_url' => $this->avatar_url,
            'provider' => [
                'id' => $this->provider->id,
                'name' => $this->provider->name,
            ],
            'model' => [
                'id' => $this->model->id,
                'name' => $this->model->name,
            ],
        ];
    }
}
