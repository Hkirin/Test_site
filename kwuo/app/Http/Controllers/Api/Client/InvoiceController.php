<?php

namespace App\Http\Controllers\Api\Client;

use App\Models\User;
use App\Models\Invoice;
use App\Models\Business;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Notifications\Client\DebitTransaction;
use App\Notifications\Client\CreditTransaction;
use App\Http\Resources\Client\TransactionResource;

class InvoiceController extends Controller
{
    public function initiate_invoice($reference)
    {
        $user = Auth::user();
        $businessReference = Business::where("reference", $reference)->first();
        $data = request()->validate([
            "recipient" => 'required',
            "amount" => 'required|string',
            "type" => "required|string",
        ]);
        try {
            $recipients =  User::whereIn('id', request('recipient'))->get();
            $paymentidentifier = Str::random(8);
            if($user->reference == $reference){
                foreach($recipients as $recipient){
                    $invoice = Invoice::create(array_merge($data, [
                        "recipient" => $recipient->id,
                        "reference" => $paymentidentifier,
                        "wallet_id" => $user->wallet->id,
                        "message" => request('message'),
                        ]));
                }
                return response()->json([
                    "message" => "Inovice was created successfully",
                    "payment_request" => $invoice
                ], 200);
            }
            if($businessReference->reference == $reference){
                foreach($recipients as $recipient){
                    $invoice = Invoice::create(array_merge($data, [
                        "recipient" => $recipient->id,
                        "reference" => $paymentidentifier,
                        "wallet_id" => $businessReference->wallet->id,
                        "message" => request('message'),
                        ]));
                }
                return response()->json([
                    "message" => "Invoice was created successfully",
                    "invoice" => $invoice
                ], 200);
            }
            
        } catch (\Throwable $th) {
            Log::error("Could not create payment request: " .$th);
            return response()->json(["error" => "Could not create payment request"], 422);
        }
        return response()->json(["error" => "Something went wrong"], 500);
    }
    public function accept_decline_invoice($reference)
    {
        $data = request()->validate(["accept" => "required|string"]);
        $user = Auth::user();
        $payment = Invoice::where("reference", $reference)->where("recipient", $user->id)->first();
        try {
            if($payment->recipient != $user->id){
                return response()->json(["error" => "Unauthorized request"], 422);
            }
            if($payment->status == 1){
                return response()->json(["message" => "You already accepted this payment"]);
            }
            if($payment->status == 2){
                return response()->json(["message" => "You already declined this payment"]);
            }
            if($payment->recipient == $user->id && $payment->status == 0){
                if(request('action') == 1){
                    $payment->update(["status" => 1]);
                    return response()->json(["message" => "Payment request accepted "], 200);
                }
                if(request("action") == 2){
                    $payment->update(["status" => 2]);
                    return response()->json(["message" => "Payment request declined "], 200);
                }
            }
        } catch (\Throwable $th) {
            Log::error("Could not  accept or decline  payment request ". $th);
            return response()->json(["error" => "Could not  accept or decline  payment request"], 422);
        }
        return response()->json(["error" => "Something went wrong  "], 500);
    }
    public function cancel_invoice($reference)
    {
        $user = Auth::user();
        try {
            $payments = Invoice::where("user_id", $user->id)->where("reference", $reference)->get(); 
            foreach($payments as $payment){
                $payment->update(["is_visible" => 1]);
                return response()->json(["message" => "Payment request cancelled "], 200);
            }
        } catch (\Throwable $th) {
           Log::error("Could not cancel Payment request" . $th);
           return response()->json(["error" => "Could not cancel payment request"], 422);
        }
       return response()->json(["error" => "Something went wrong"], 500);
    }
    public function make_invoice_payment($reference)
    {
        $data = request()->validate([
            "amount" => "required|string",
            "transaction_method" => "required|string",
            "status" => "required|string",
            "transaction_type" => "required|string"
        ]);
        $user = Auth::user();
        $payment = Invoice::where("recipient", $user->id)->where("reference", $reference)->first();
        $recipient = User::find($payment->user_id);
        try {
            if($payment != null){
                // Debit Transaction
                if(request('transaction_method') == "kwuo_transfer" && $user->wallet->balance < request('amount')){
                    return response()->json(['error' => "Insufficient wallet balance to make this transaction"], 422);
                }
                $debit_transaction = Transaction::create(array_merge($data, [
                    "reference" => $payment->reference,
                    "transaction_id" => Str::random(10),
                    "transaction_type" => "debit",
                    "transaction_method" => request('transaction_method'),
                    "user_id" => $recipient->id, // represents recipient id in the transactions table
                    "wallet_id" => $user->wallet->id,
                    "account_type" => "personal",
                    "status" => request('status'),
                ]));
                if($debit_transaction->status == "success"){
                    $payment->update(['is_paid' => 1]);
                    $debit_transaction->update([
                        "previous_balance" => $user->wallet->balance,
                        "new_balance" => $user->wallet->balance - $debit_transaction->amount
                    ]);
                    $user->wallet->update([
                        "balance" => $user->wallet->balance - $debit_transaction->amount
                    ]);
                    $_debitdata = [
                        'reciever' => $recipient->firstname. " " .$recipient->lastname,
                        'amount' => number_format($debit_transaction->amount),
                        'previous_balance' => number_format($debit_transaction->previous_balance),
                        'new_balance' => number_format($debit_transaction->new_balance),
                        'message' =>  $debit_transaction->message,
                        'transaction_id' => $debit_transaction->transaction_id,
                    ];
                    $user->notify(new DebitTransaction($recipient, $_debitdata));
                        // Credit Transaction
                        $credit_transaction = Transaction::create(array_merge($data, [
                            "previous_balance" => $recipient->wallet->balance,
                            "new_balance" => $recipient->wallet->balance + request('amount'),
                            "reference" => $payment->reference,
                            "transaction_id" => Str::random(10),
                            "transaction_type" => "credit",
                            "transaction_method" => "payment_request",
                            "user_id" => $user->id,
                            "wallet_id" => $recipient->wallet->id,
                            "account_type" => "personal"
                        ]));
                        $recipient->wallet->update(['balance' => $recipient->wallet->balance + request('amount')]);
                        $_creditdata = [
                            'sender' => $user->firstname. " " .$user->lastname,
                            'amount' => number_format($credit_transaction->amount),
                            'previous_balance' => number_format($credit_transaction->previous_balance),
                            'new_balance' => number_format($credit_transaction->new_balance),
                            'message' =>  $credit_transaction->message,
                            'transaction_id' => $credit_transaction->transaction_id,
                        ];
                        // Send a Message to the Recipient of this transaction
                        $recipient->notify(new CreditTransaction($user, $_creditdata));
                        return response()->json([
                            "message" => "Payment Request made successfully",
                            "transaction" => new TransactionResource($debit_transaction),
                        ]);
                }
                if(request("status") != "success"){
                    return response()->json([
                        "message" => "Payment Request ". request("status"),
                        "transaction" => new TransactionResource($debit_transaction),
                    ]);
                }
            }
        } catch (\Throwable $th) {
            Log::error("Could not make request payment " . $th);
            return response()->json(['error' => "Could not make request payment"], 422);
        }
        return response()->json(['error' => "Something went wrong"], 500);
    }
    public function invoices()
    {
        $user = Auth::user();
        $payment_requests = Invoice::where("recipient", $user->id)->get();
        return response()->json([
            "payment_request" => [
                "recipient" => User::find($payment_requests->user_id),
                "amount" => $payment_requests->amount,
                "status" => $payment_requests->status,
                "is_paid" => $payment_requests->is_paid
            ] 
        ], 200);
    }
    public function invoices_log($reference)
    {
        $user = Auth::user();
        $businessReference = Business::where("reference", $reference)->first();
        try {
            if($user->reference == $reference){
                $invoices = Invoice::where("wallet_id", $user->wallet->id)->get();
                return response()->json($invoices);
            }
            if($businessReference->reference == $reference){
                $invoices = Invoice::where("wallet_id", $businessReference->wallet->id)->get();
                return response()->json($invoices);
            }
            return response()->json(["error" => "Invoice Log not found"], 422);
        } catch (\Throwable $th) {
            Log::error("Invoice Log not found". $th);
            return response()->json(["message" => "Something went wrong"], 500);
        }
    }
}
