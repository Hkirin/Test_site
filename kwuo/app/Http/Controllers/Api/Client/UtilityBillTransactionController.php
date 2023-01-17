<?php

namespace App\Http\Controllers\Api\Client;

use App\Http\Controllers\Controller;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;
use App\ExResource\UtilityBills;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use App\Models\Transaction;
use App\Models\Wallet;
use App\Http\Resources\Client\TransactionResource;
use Hash;

class UtilityBillTransactionController extends Controller
{
    public function airtime_purchase()
    {
        $user = Auth::user();
        $getAccount = Wallet::where("wallet", request("account"))->where("user_id", $user->id)->first();
        $validate = Validator::make(request()->all() ,[
            "network_provider" => "required|string",
            "amount" => "required|string",
            "pin" => "required|string",
            "phone" => "required|string",
            "account" => "required|string",
        ]);
        if($validate->fails()){
            return response()->json([
                "status" => "error",
                "data" => $validate->errors(),
            ], 422);
        }
        if($getAccount == null){
            return response()->json(["error" => "Unknown account or unauthorized user"], 422);
        }
        $input  = request()->all();
        if( (int)$input['amount']  == 0){
            return response()->json(['message' => "Can't send a zero value"], 422);
        }
        if($user->pin == null){
            return response()->json(["message" => "Please set pin before transaction"], 422);
        }
        if((int)$input['amount'] > $getAccount->balance){
            return response()->json(["error" =>'Insufficient Balance'], 422);
        }
        $pin = $input["pin"];
        $uni_transaction_id = Str::random(12);
        $phone = $input["phone"];
        $truncatedPhoneNumber = "";
        if(Str::contains($phone, '+234')){
            $truncatedPhoneNumber = \str_replace('+234', '0', $phone);
        }
        if(Str::contains($phone, '234')){
            $truncatedPhoneNumber = Str::replaceFirst('234', '0', $phone);
        }
        if(Str::contains($truncatedPhoneNumber, '+')){
            $truncatedPhoneNumber = \str_replace('+', '', $truncatedPhoneNumber);
        }
        $purchase = new UtilityBills();
        $data  = [
            "serviceID" => request("network_provider"),
            "amount" => request("amount"),
            "phone" => ($truncatedPhoneNumber == "") ? $phone : $truncatedPhoneNumber, 
            "request_id" => $uni_transaction_id
        ];
        try {
            if(Hash::check($pin, $user->pin)){
                $airtime_data  = $purchase->initiateProcess($data);
                if($airtime_data->code != "000"){
                    Log::error("Could not purchase airtime " . $airtime_data->content->transactions->status);
                    return response()->json(["error" => "Airtime Purcahse failed "], 422);
                }
                if($airtime_data->content->transactions->status == "delivered"){
                    $networkProvider = [$input["network_provider"], $input["phone"]];
                    $debit = Transaction::create([
                        "amount" => request("amount"),
                        "wallet_id" => $getAccount->id, 
                        "transaction_id" => $airtime_data->content->transactions->transactionId,
                        "transaction_type" => "debit",
                        "transaction_method" => "airtime",
                        "account_type" => $getAccount->account_type,
                        'reference' => Str::random('15'),
                        "previous_balance" => (int) $getAccount->balance,
                        "new_balance" => (int) $getAccount->balance - (int)request('amount'),
                        "status" => "success",
                        "network_provider" => json_encode($networkProvider)
                    ]);
                
                    $getAccount->update(['balance' => $getAccount->balance - $debit->amount]);
                }
                return response()->json([
                    "data" => TransactionResource::make($debit)
                ], 200);
            }else{
                return response()->json(["error" => "Invalid Pin"], 422);
            }
        } catch (\Throwable $th) {
            Log::error("Could'nt Make Airtime purchase" .$th);
        }
        Log::error("Could'nt Make Airtime purchase " );
      return response()->json(["error" => "Something went wrong"], 500);
    }

    public function list_of_network_service_providers()
    {
        $network_providers =  ["mtn", "airtel", "etisalat", "glo", "smile"];
        return response()->json([ "data" =>$network_providers], 200);
    }
    public function data_bundle($network)
    {
        $data = new UtilityBills();
        $dataBundle  = $data->get_data_bundle($network);
        if($dataBundle->response_description != 000){
            return response()->json(["error" => "Something went wrong"], 500);
        }
        return response()->json([
            "service_name" => $dataBundle->content->ServiceName,
            "serviceId" => $dataBundle->content->serviceID,
            "data" => $dataBundle->content->varations
        ], 200);
    }
    public function purchase_data()
    {
        $user = Auth::user();
        $getAccount = Wallet::where("wallet", request("account"))->where("user_id", $user->id)->first();
        $validate = Validator::make(request()->all(), [
            "network_provider" => "required|string",
            "phone" => "required|string",
            "amount" => "required|string",
            "variation_code" => "required|string",
            "pin" => "required|string",
            "account" => "required|string"
        ]);
        if($validate->fails()){
            return response()->json([
                "error" => $validate->errors()
            ], 422);
        }
        $uni_transaction_id = Str::random(12);
        $input  = request()->all();
        $phone = $input["phone"];
        $pin = request("pin");
        $truncatedPhoneNumber = "";
        if(Str::contains($phone, '+234')){
            $truncatedPhoneNumber = \str_replace('+234', '0', $phone);
        }
        if(Str::contains($phone, '234')){
            $truncatedPhoneNumber = Str::replaceFirst('234', '0', $phone);
        }
        if(Str::contains($truncatedPhoneNumber, '+')){
            $truncatedPhoneNumber = \str_replace('+', '', $truncatedPhoneNumber);
        }

        if($getAccount == null){
            return response()->json(["error" => "Unknown account or unauthorized user"], 422);
        }
        
        if( (int)$input['amount']  == 0){
            return response()->json(['message' => "Can't send a zero value"], 422);
        }
        if($user->pin == null){
            return response()->json(["message" => "Please set pin before transaction"], 422);
        }
        if((int)$input['amount'] > $getAccount->balance){
            return response()->json(["error" =>'Insufficient Balance'], 422);
        }
        $data = [
            "serviceID" => request("network_provider"),
            "billersCode" => "08065981154",
            "request_id" => $uni_transaction_id,
            "variation_code" => request("variation_code"),
            "amount" => request("amount"),
            "phone" => ($truncatedPhoneNumber == "") ? $phone : $truncatedPhoneNumber,
        ];
        $purchase = new UtilityBills();
        try {
            if(Hash::check($pin, $user->pin)){
                $dataBundle  = $purchase->initiateProcess($data);
                if($dataBundle->code != "000"){
                    Log::error("Could not purchase data " . $dataBundle->content->errors);
                    return response()->json(["error" => "Data Purcahse failed "], 422);
                }
                if($dataBundle->content->transactions->status == "delivered"){
                    $networkProvider = [$input["network_provider"], $input["phone"]];
                    $debit = Transaction::create([
                        "amount" => request("amount"),
                        "wallet_id" => $getAccount->id, 
                        "transaction_id" => $dataBundle->content->transactions->transactionId,
                        "transaction_type" => "debit",
                        "transaction_method" => "airtime",
                        "account_type" => $getAccount->account_type,
                        'reference' => Str::random('15'),
                        "previous_balance" => (int) $getAccount->balance,
                        "new_balance" => (int) $getAccount->balance - (int)request('amount'),
                        "status" => "success",
                        "network_provider" => json_encode($networkProvider)
                    ]);
                
                    $getAccount->update(['balance' => $getAccount->balance - $debit->amount]);
                }
                return response()->json([
                    "data" => TransactionResource::make($debit)
                ], 200);
            }
            return response()->json(["error" => "Invalid Pin"], 422);
        } catch (\Throwable $th) {
            Log::error("Could'nt Make Data purchase" .$th);
        }
        Log::error("Could'nt Make Data purchase " );
      return response()->json(["error" => "Something went wrong"], 500);
    }
}
