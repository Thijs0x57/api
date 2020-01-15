<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class LockerResource extends JsonResource
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
            'guid' => $this->guid,
            'is_currently_claimable' => $this->isCurrentlyClaimable(),
        ];
    }
}
