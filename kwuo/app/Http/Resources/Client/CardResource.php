<?php

namespace App\Http\Resources\Client;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Crypt;
use App\Models\User;

class CardResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        // return parent::toArray($request);
        return [
            "card_holder_name" => $this->card_holder_name,
            "card_last_digits" => substr(Crypt::decrypt($this->card_no, false), -4),
            "card_first_digits" => substr(Crypt::decrypt($this->card_no, false), 0, 4),
            "card_type" => $this->type,
            "user" => User::find($this->user_id),
        ];
    }
}
