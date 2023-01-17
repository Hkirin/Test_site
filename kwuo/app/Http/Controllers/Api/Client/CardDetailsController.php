<?php

namespace App\Http\Controllers\Api\Client;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;
use App\Http\Resources\Client\CardResource;
use Illuminate\Support\Str;
use App\Models\CardDetail;
use Illuminate\Http\Request;

class CardDetailsController extends Controller
{
    public function add_card(){
        $data = request()->validate([
            "card_holder_name" => "required|string",
            "card_no" => "required|string",
            "card_cvv" => "required|string",
            "card_exp_date" => "required|string"
        ]);
        $user = Auth::user();
        try {
            $check_card = CardDetail::where("mask_card", $this->mask_card($data['card_no']))
            ->where("user_id", $user->id)->count();
            if($check_card > 0){
                return response()->json(['message' => "Card already exist "], 422);
            }
            $card_details = CardDetail::create(array_merge($data, [
                "user_id" => $user->id,
                "card_no" => Crypt::encryptString($data["card_no"]),
                "card_cvv" => Crypt::encryptString($data["card_cvv"]),
                "mask_card" => $this->mask_card($data['card_no']),
                "reference" => Str::random(9),
                "type" => request("type"),
                "status" => 1
            ]));
            return response()->json([
                "message" => "Card added successfully",
                "card" => [
                    "cardHolder" => $card_details->card_holder_name,
                    "card_last_digits" => substr(Crypt::decrypt($card_details->card_no, false), -4),
                    "card_first_digits" => substr(Crypt::decrypt($card_details->card_no, false), 0, 4),
                    "card_type" => $card_details->type,
                    "user" => [$user->firstname, $user->lastname]
                ],
                    
            ], 200);
        } catch (\Throwable $th) {
            Log::error("Unable to add Card details: " .$th);
            return response()->json(["error" => "Unable to add card details"], 422);
        }
        return response()->json(["error" => "Something went wrong"], 500);
    }
    public function mask_card($number, $maskingCharacter = 'x') {
        return substr($number, 0, 4) . str_repeat($maskingCharacter, strlen($number) - 8) . substr($number, -4);
    }
    public function get_cards(){
        $user = Auth::user();
        $cards = CardDetail::where("user_id", $user->id)->latest()->get();
        return  CardResource::collection($cards);
    }
}
