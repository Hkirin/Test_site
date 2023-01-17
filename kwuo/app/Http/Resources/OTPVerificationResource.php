<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class OTPVerificationResource extends JsonResource
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
            "otp" => $this->otp,
            "status" => $this->status
        ];
    }
}
