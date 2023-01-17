<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Resources\Json\JsonResource;
use App\Traits\FileUploadTrait;

class AdminResources extends JsonResource
{
    use FileUploadTrait;
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        return [
            "firstname" => $this->firstname,
            "lastname" => $this->lastname,
            "phone" => $this->phone,
            "email" => $this->email,
            "username" => $this->username,
            "verified" => ($this->verified == 1 )?? true,
            "avatar" => ($this->avatar == "avatar.png")? "avatar.png" : $this->diskAvatar()->url($this->avatar),
        ];
    }
}
