<?php

namespace App\Http\Controllers\Api\Client;

use App\Http\Controllers\Controller;
use App\Http\Resources\Client\TransactionResource;
use App\Http\Resources\Client\UserResource;
use App\Http\Resources\Client\WalletResource;
use App\Notifications\Client\CreditTransaction;
use App\Notifications\Client\DebitTransaction;
use Illuminate\Support\Str;
use App\Models\Business;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Wallet;
use Exception;
use Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class TransactionsController extends Controller
{

    public function transaction_log(){
        $user = Auth::user();
        $transactions =  Transaction::where('wallet_id', $user->wallet->id)->latest()->get();
        return TransactionResource::collection($transactions);
    }

    public function business_transaction_log($business){
        try {
            $business = Auth::user()->getBusiness($business);
            if($business == null) {
                return response(['error' => "Business Account Not Found"], 404);
            }
            $transactions = Transaction::where('wallet_id', $business->wallet->id)->latest()->get();
            return TransactionResource::collection($transactions);
        } catch (\Throwable $e) {
            return response(['error' => "Server Error"], 500);
        }
    }

    public function filter_transaction_log_by_date($_date){
        $param = ['d', 'w', 'm', 'y'];
        $transactions = Transaction::where('')->get();

    }
    public function add_transaction()
    {
        $userWallet =  User::find(Auth::user()->id)->wallet;
        $data = request()->validate([
            "transaction_id" => "required|string",
            "amount" => 'required|string',
            "transaction_type" => "required|string",
            "transaction_method" => "required|string",
        ]);
            try{
                // Credit Transaction
                if($data['transaction_type'] == 'credit'){
                    $transaction = Transaction::create(array_merge($data, [
                        'wallet_id' => $userWallet->id,
                        'previous_balance' => $userWallet->balance,
                        'reference' => Str::random('15'),
                        'status' => request('status'),
                        'message' => request('message'),
                        "user_id" => Auth::user()->id
                    ]));

                    if($transaction->status == 'success'){
                        $wallet = Wallet::find($userWallet->id);
                        $wallet->update(['balance' => $wallet->balance + $transaction->amount]);
                        $trans = Transaction::find($transaction->id);
                        $trans->update(['new_balance' => $userWallet->balance + $trans->amount]);
                        return response(['message' => "Account credited with ". number_format($transaction->amount)], 200);
                    }
                    if($transaction->status != 'success'){
                        $trans = Transaction::find($transaction->id);
                        $trans->update(["new_balance" => $userWallet->balance]);
                        return response(['error' => "Transaction " .$transaction->status], 422);
                    }
                }
                // Debit Transaction
                if($data['transaction_type'] == 'debit'){
                    if($data['amount'] > $userWallet->balance){
                        return response(['error', "Insuficent Fund"], 422);
                    }
                    $transaction = Transaction::create(array_merge($data, [
                        'wallet_id' => $userWallet->id,
                        'previous_balance' => $userWallet->balance,
                        'reference' => Str::random('15'),
                        'status' => request('status'),
                        'message' => request('message'),
                        "user_id" => Auth::user()->id,
                    ]));
                if($transaction->status == 'success'){
                        $wallet = Wallet::find($userWallet->id);
                        $trans = Transaction::find($transaction->id);
                        $trans->update(['new_balance' => $userWallet->balance - $trans->amount]);
                        $wallet->update(['balance' => $wallet->balance - $data['amount']]);
                        return response(['message' => "Account Debited with ". number_format($transaction->amount)], 200);
                }
                if($transaction->status != 'success'){
                        $trans = Transaction::find($transaction->id);
                        $trans->update(["new_balance" => $userWallet->balance]);
                        return response(['error' => "Transaction " .$transaction->status], 422);
                    }
                }
            }catch(Exception $e){
                Log::error("Could not add Transaction: " . $e->getMessage());
                return response(['error' => 'Server Error '], 500);
            }
    }

    public function kwuo_transfer()
    {
        $data = request()->validate([
            "identity" => "required",
            "amount" => "required|string",
            "pin" => "required|string",
        ]);
        $user = Auth::user();
            if($data['amount']  > $user->wallet->balance ){
                return response(['message' => "Insufficent Funds"], 422);
            }
            if( (int)$data['amount']  == 0){
                return response(['message' => "Can't send a zero value"], 422);
            }
            if($user->pin == null){
                return response()->json(["message" => "Please set pin before transaction"], 422);
            }
            try {
                $pin = request('pin');
                if(Hash::check($pin, $user->pin)){
                        // Credit The Recipient Account
                    $recipients = User::whereIn('id', request('identity'))->get();
                    foreach($recipients as  $recipient){
                        $credit_recipient  = Transaction::create(array_merge($data, [
                            'transaction_id' => Str::random('15'),
                            'transaction_type' => 'credit',
                            'transaction_method' => 'kwuo_transfer',
                            'message' => request('message'),
                            'reference' => Str::random('15'),
                            "wallet_id" =>$recipient->wallet->id,
                            "previous_balance" => $recipient->wallet->balance,
                            "new_balance" =>  $recipient->wallet->balance + $data['amount'],
                            "status" => "success",
                            "user_id" => $user->id,
                        ]));
                        if($credit_recipient->status == "success"){
                            $wallet = Wallet::find($recipient->wallet->id);
                            $wallet->update(['balance' => $wallet->balance + $credit_recipient->amount]);
                            $_data = [
                                'sender' => $user->firstname. " " .$user->lastname,
                                'amount' => number_format($credit_recipient->amount),
                                'previous_balance' => number_format($credit_recipient->previous_balance),
                                'new_balance' => number_format($credit_recipient->new_balance),
                                'message' =>  $credit_recipient->message,
                                'transaction_id' => $credit_recipient->transaction_id,
                            ];
                            // Send a Message to the Recipient of this transaction
                            $recipient->notify(new CreditTransaction($user, $_data));
                        }
                   
                    // Debit The Creditor Account
                        $_debit = Transaction::create([
                            "amount" => request('amount'),
                            "wallet_id" => $user->wallet->id, 
                            "transaction_id" => Str::random(15),
                            "transaction_type" => "debit",
                            "transaction_method" => "kwuo_transfer",
                            "message" => request('message'),
                            'reference' => Str::random('15'),
                            "previous_balance" => $user->wallet->balance,
                            "new_balance" => (int) $user->wallet->balance - (int)request('amount'),
                            "status" => "success",
                            "user_id" => $recipient->id
                        ]);
                            if($_debit->status == 'success'){
                                $wallet = Wallet::find($user->wallet->id);
                                $wallet->update(['balance' => $wallet->balance - $_debit->amount]);
                                // Send Message to the User
                                $_debitdata = [
                                    'reciever' => $recipient->firstname. " " .$recipient->lastname,
                                    'amount' => number_format($_debit->amount),
                                    'previous_balance' => number_format($_debit->previous_balance),
                                    'new_balance' => number_format($_debit->new_balance),
                                    'message' =>  $_debit->message,
                                    'transaction_id' => $_debit->transaction_id,
                                ];
                                $user->notify(new DebitTransaction($recipient, $_debitdata));
                            }
                        }
                    return response([
                        'message' => 'You sent '. number_format($credit_recipient->amount),
                        "transaction" => new TransactionResource($_debit)
                        ], 200);
                }
                return response(['error' => 'Invalid Pin'], 422);
            } catch (\Throwable $th) {
                Log::error("Couldn't Send Transaction" .$th);
                return response(['message' => 'Something went wrong'], 422);
            }

    }

    public function kwuo_identity($str = "")
    {
        $identity =  ($str == "") ? request('identity') : $str;
        $authUser = Auth::user();
        // E-Mail Validate
        if(\filter_var($identity, FILTER_VALIDATE_EMAIL)){
            $email = request()->merge(["email" => $identity]);
            if($authUser->email ==  request('identity')){
                return response()->json(["message" => "Can't add this contact for transaction"], 422);
            }
                if($user = User::whereEmail($identity)->first()){
                    return new UserResource($user);
                }
            return response(['message' => "Invalid Identity"], 406);
        }

        // Phone Validate
        if(Str::length($identity) == 11 || Str::length($identity) == 13){
            $phone = request()->merge(['phone' => $identity]);
            if($authUser->phone == request('identity')){
                return response()->json(["message" => "Can't add this contact for transaction"], 422);
            }
                if($user = User::wherePhone($identity)->first()){
                    return new UserResource($user);
                }
            return response(['message' => 'Invalid Phone'], 406);
        }

        // WalletID Validate
         if(Str::length($identity) == 7){
            $wallet = request()->merge(['wallet' => $identity]);
                if($authUser->wallet->wallet == request('identity')){
                    return response()->json(["message" => "Can't add this contact for transaction"], 422);
                }
                if($wallet_details = Wallet::whereWallet($identity)->first()){
                    $user = User::find($wallet_details->user_id);
                    return new UserResource($user);
                }
            return response(['message' => "Invalid Wallet Id"], 406);
         }
         return response()->json(['error' => "Something went Wrong"], 500);

    }

    public function getWalletBalance()
    {
        $user = Auth::user();
        $wallet =  request('wallet');
        $wallet_balance = Wallet::where('wallet', $wallet)->first();
        if($user->id != null && $user->id  == $wallet_balance->user_id){
            return new WalletResource($wallet_balance, 200);
        }
        $businesses = $user->business;
        foreach($businesses as $business){
            if($business->id != null && $business->id == $wallet_balance->business_id){
                return new WalletResource($wallet_balance, 200);
            }
        }
        return response(['error' => 'Unknown Wallet ID'], 422);
    }

    public function setTransactionPin()
    {
        $data = request()->validate([
            'pin' => 'required|string|min:4|max:4',
            'confirm_pin' => 'required|string|same:pin|min:4|max:4'
        ]);
        try {
            $user = Auth::user();
            $user->update(['pin' => bcrypt($data['pin'])]);
            return response()->json($user, 200);
        } catch (\Throwable $th) {
           Log::error('Error: Unable to Update Pin' .$th);
           return response()->json(["error" => "Unable to update pin"], 422);
        }
        return response()->json(["error" => "Something went wrong try again"], 500);
    }
    public function resetTransactionPin()
    {
        $data = request()->validate([
            'old_pin' => 'required|string|min:4|max:4',
            'pin' => 'required|string|min:4|max:4',
            'confirm_pin' => 'required|string|same:pin|min:4|max:4'
        ]);
        try {
            $user = Auth::user();
            if(Hash::check(request('old_pin'), $user->pin)){
                $user->update(["pin" => bcrypt($data['pin'])]);
                return response()->json("Pin updateded successfully");
            }
            return response()->json("Pin does not match", 422);
        } catch (\Throwable $th) {
            Log::error('Error: Unable to Update Pin' .$th);
            return response()->json(["error" => "Unable to update pin"], 422);
        }
        return response()->json(["error" => "Something went wrong try again"], 500);
    }
    public function frequentlyTransfered()
    {
        $user = Auth::user();
        $transactions = Transaction::whereWalletId($user->wallet->id)
            ->whereTransactionType("debit")->pluck("user_id");
        $countArray = [];
        foreach($transactions as $transaction){
               array_push($countArray, $transaction);
        }
       $newCount = array_count_values($countArray);
       $frequsers = [];
        foreach($newCount as $key => $value){
           if($value >= 3){
               array_push($frequsers, $key);
            }
        }  
           $freq = User::whereIn("id", $frequsers)->get();
           return response()->json(["users" => UserResource::collection($freq)]);
    }
}
