<?php

namespace App\Http\Resources\Client;

use App\Models\Wallet;
use App\Models\User;
use Illuminate\Http\Resources\Json\JsonResource;

class TransactionResource extends JsonResource
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
            'amount' => $this->amount,
            "transaction_id" => $this->transaction_id,
            'transactionType' => $this->transaction_type,
            'transactionMethod' => $this->transaction_method,
            'status' => $this->status,
            "wallet" => new WalletResource(Wallet::find($this->wallet_id)),
            "previous" => number_format($this->previous_balance),
            "currentBalance" => number_format($this->new_balance),
            "recipient" => ($this->user_id != null) ? $this->getRecipiant($this->user_id) : null,
            "network_provider" => ($this->network_provider != null || $this->network_provider != "")? json_decode($this->network_provider): ""
        ];
    }
    public function getRecipiant($user)
    {
        if($this->transaction_method == "kwuo_transfer"){
            $user = User::find($user);
            if($user != null){
                return $user;
            }
            return null;
        }
    }
}
