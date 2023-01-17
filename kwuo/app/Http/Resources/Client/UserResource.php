<?php

namespace App\Http\Resources\Client;

use App\Models\User;
use App\Models\UserDetail;
use App\Models\Wallet;
use App\Traits\FileUploadTrait;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
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
        $wallet = Wallet::where("user_id", $this->id)->first();
        return [
            'id' => $this->id,
            "firstname" => $this->firstname,
            "lastname" => $this->lastname,
            "email" => $this->email,
            "phone" => $this->phone,
            "agent" => ($this->is_agent == 1)?? true,
            "verified" => ($this->is_verified == 1)??true,
            "ban" => ($this->ban == 1)?? true,
            "avatar" => ($this->avatar == "avatar.png" || $this->avatar == null)? "avatar.png" :$this->diskAvatar()->url($this->avatar),
            "user_details" => $this->getUserDetails(),
            'wallet' => WalletResource::make($wallet),
        ];
    }

    public function getUserDetails()
    {
        $user = UserDetail::where("user_id", $this->id)->first();
        if($user != null){
            return[
                "state" => $user->state,
                "city" => $user->city,
                "address" => $user->address,
                "gender" => $user->gender,
                "dob" => $user->dob,
                "lga" => $user->lga,
                "country" => $user->country,
            ];
            return null;
        }
    }
}
