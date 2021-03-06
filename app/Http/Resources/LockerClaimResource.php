<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class LockerClaimResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'is_set_up' => $this->isSetUp(),
            'is_active' => $this->isActive(),
            'attempts' => [
                'failed' => (int) $this->failed_attempts,
                'left' => $this->attemptsLeft(),
            ],
            'start_at' => $this->start_at,
            'end_at' => $this->end_at,
            'client' => new ClientResource($this->whenLoaded('client')),
        ];
    }
}
