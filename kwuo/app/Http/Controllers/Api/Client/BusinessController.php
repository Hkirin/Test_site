<?php

namespace App\Http\Controllers\Api\Client;

use App\Http\Controllers\Controller;
use App\Models\Business;
use App\Models\Wallet;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class BusinessController extends Controller
{
    /*
    * Creates a Business account
    *
    */
    public function store(){
        $user = Auth::user();
        $data = request()->validate([
            "business_name" => "required|string",
            "identity" => "required|string|max:191",
            "category_id" => "required|string",
            "flat_no" => "required|string",
            "city" => "required|string",
            "rc_number" => "required|string",
            "cac" => "required|mimes:jpg,jpeg,png,pdf"
        ]);
        try {
                // write a function to verify the RC Number
                $fileNameWithExt = request()->file('cac')->getClientOriginalExtension();
                $fileNameToStore = uniqid().Str::slug(request('business_name')) .".".$fileNameWithExt;
                $filePath = storage_path("app/public/upload/cac");
                $fileUploadPath = request("cac")->move($filePath, $fileNameToStore);
                $rc_number_check = Business::where("rc_number", $data['rc_number'])->count();
                if($rc_number_check > 0){
                    return response()->json(['error' => "Rc Number already exist with a business"], 422);
                }
                if($this->verifyField(request('identity'))->getStatusCode() != 422){
                    $website = Str::contains(request("website"), ["http", "https", "//"]);
                    $business = Business::create(array_merge($data, [
                        "user_id" => $user->id,
                        "slug" => Str::slug(request('business_name')),
                        $this->filter_user_identity() => request("identity"),
                        "website" => ($website == true) ? Str::of(request("website"))->after("//") : request("website"),
                        "street" => request("street"),
                        "postal_code" => request("postal_code"),
                        "cac" => ($fileNameToStore != null)? $fileNameToStore : "document.png",
                        "status" => "2",
                        "reference" => Str::random(8),
                    ]));
                        $wallet = Wallet::create([
                            "business_id" => $business->id,
                            "account_type" => "business",
                            "wallet" => substr(str_shuffle("ABCDEFGHIJKLMNOPQRSTUVWXYZ"), 0, 2).random_int(10000, 99999),
                            "reference" => Str::random(10),
                        ]);
                        $account = array_merge($business->toArray(), $wallet->toArray());
                    return response()->json([
                        "message" => "Business account created successfully",
                        // "business" => $account,
                    ],200);
                }
                return response()->json(["error" => "Email or Phone number already exist"], 422);
        } catch (\Throwable $th) {
            Log::error("Could not create business account". $th);
            return response()->json(["error" => "Unable to create business account "], 422);
        }  
        return response()->json(["error" => "Something went wrong"], 500);
    }

    public function filter_user_identity()
    {
        $user_identity = request('identity');
        $field = filter_var($user_identity, FILTER_VALIDATE_EMAIL) ? 'email' : 'phone';
        request()->merge([$field => $user_identity]);
        return $field;
    }
    public function verifyField($str = "")
    {
        //Validate if email or phone  number already exist
        $identity = ($str == "") ? request('identity') : $str;
        if($this->filter_user_identity() == 'email'){
            if(Business::whereEmail($identity)->count() > 0){
               return response(["error" => "Email has been used already"], 422);
            }
        }
        if($this->filter_user_identity() == 'phone'){
            if(Business::wherePhone($identity)->count() > 0){
                return response(["error" =>"Phone Number has been used already"], 422);
            }
        }
        return response(200);
    }
}
