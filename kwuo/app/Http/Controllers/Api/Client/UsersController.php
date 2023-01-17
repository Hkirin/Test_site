<?php

namespace App\Http\Controllers\Api\Client;

use App\Http\Controllers\ApiController;
use App\Http\Resources\Client\UserResource;
use App\Mail\Client\OTPAccountVerification;
use App\Models\OTPVerification;
use App\Sms\SendSms;
use App\Models\Transaction;
use App\ExResource\BvnVerification;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use App\Models\User;
use App\Models\UserDetail;
use App\Models\Lead;
use App\Models\Wallet;
use App\Models\Business;
use Illuminate\Support\Facades\Validator;
use App\Notifications\Client\ResetPasswordNotification;
use Illuminate\Support\Facades\Mail;
use App\Traits\FileUploadTrait;

class UsersController extends ApiController
{
    protected const TOKEN_KEY = "UserToken";
    use FileUploadTrait;
    protected const RULES = [
        "register" => [
            'firstname' => 'required|string|max:191',
            'lastname' => 'required|string|max:191',
            'identity' => 'required|string|max:191',
        ],
        "password" => [
            "oldpassword" => "required|string",
            "password" => "required|string|min:6",
            "confirm_password" => "required|string|same:password|min:6"
        ]
    ];
    public $successStatus = 200;

     /**
     * Login api.
     *
     * @return \Illuminate\Http\Response
     */
    public function login()
    {
        if(Auth::attempt([
            $this->filter_user_identity() => request('identity'),
            'password' => request('password')
            ])){
                $user = Auth::user();

                if($user->ban == 1){
                    Auth::logout();
                    return $this->message("You account has been suspended, Please contact the service provider", 401);
                }
                $success['token'] = $user->createToken(static::TOKEN_KEY)->accessToken;
                $success['redirect_to'] = route('dashboard');
                $newobj = array_merge($user->toArray(), [
                    "wallet" => $user->wallet->wallet,
                    "account_type" => $user->wallet->account_type,
                    "balance" => number_format($user->wallet->balance),
                ]);
                $token_user_details = array_merge($newobj, $success);
                return response()->json($token_user_details, 200);
            }else{
                return response([
                    'error' => "Unauthorized",
                    'message' => "Invalid email, phone, or password",
                ], 401);
            }
    }
    public function filter_user_identity()
    {
        $user_identity = request('identity');
        $field = filter_var($user_identity, FILTER_VALIDATE_EMAIL) ? 'email' : 'phone';
        request()->merge([$field => $user_identity]);
        return $field;
    }
     /**
     * Register api.
     *
     * @return \Illuminate\Http\Response
     */
    public function register()
    {
        if($this->verifyField(request('identity'))->getStatusCode() !== 422){
            try {
               $otp =  $this->generate_otp('registation');
               if($this->filter_user_identity() == 'phone'){
                $sms = new SendSms();
                if(Str::startsWith(request('identity'), '0')){
                    $phone = Str::replaceFirst('0', '234', request('identity'));
                    $sms->send_token_for_user_account_verification($phone, $otp);
                }
                if(Str::startsWith(request('identity'), '234')){
                    $sms->send_token_for_user_account_verification(request('identity'), $otp);
                }
                return response(['message' => "Please check phone message box for otp"],200);
               }
               if($this->filter_user_identity() == 'email'){
                   Mail::to(request('identity'))->send(new OTPAccountVerification($otp));
                   return response(['message' => "Please check email for otp"],200);
               }

            } catch (\Swift_TransportException $e) {
                Log::error("Couldn't send OTP");
                return response(["error" =>"Unable to send OTP"], 422);
            }
        }
        return response(["error" =>'Duplicate data entry'], 422);
    }
    public function verifyField($str = "")
    {
        //Validate if email or phone  number already exist
        $identity = ($str == "") ? request('identity') : $str;
        if($this->filter_user_identity() == 'email'){
            if(User::whereEmail($identity)->count() > 0){
               return response(["error" => "Email has been used already"], 422);
            }
            if(Lead::whereEmail($identity)->count() > 0){
                return response(["error" => "Email has been used already"], 422);
            }
        }
        if($this->filter_user_identity() == 'phone'){
            if(User::wherePhone($identity)->count() > 0){
                return response(["error" =>"Phone Number has been used already"], 422);
            }
            if(Lead::wherePhone($identity)->count() > 0){
                return response(['error' => "Phone Number has been used already"], 422);
            }
        }
        return response(200);
    }

    public function lead_store()
    {
        $validate_data = Validator::make(request()->all(), static::RULES["register"]);
        if($validate_data->fails()){
            return response()->json($validate_data->errors());
        }
        $input = request()->all();
        if($this->verifyField(request('identity'))->getStatusCode() != 422){
            $user = Lead::create(array_merge($input, [
                $this->filter_user_identity() => request('identity'),
            ]));
            return response()->json($user, 200);
        }
       return response(["error" => 'Duplicate data entry'], 422);
    }
    public function store()
    {
        $validate_data = Validator::make(request()->all(), [
            'password' => 'required|string|min:6',
            'confirm_password' => 'required|string|min:6|same:password'
        ]);
        if($validate_data->fails()){
            return response()->json($validate_data->errors());
        }
        try {
            if(request('email') !== null){
                $lead = Lead::where('email', request('email'))->first();
                if($lead == null){
                    return response()->json('Invalid Identity', 422);
                }
                $new_data = $lead->toArray();
                $user = User::create(array_merge($new_data, [
                    'password' => bcrypt(request('password')),
                    'is_verified' => 1,
                    'email_verified' => now(),
                    'pin' => bcrypt(request('pin')),
                    'avatar' => 'avatar.png',
                    'reference' => Str::random(8)
                ]));
                $data['token'] = $user->createToken(static::TOKEN_KEY)->accessToken;
                $wallet = Wallet::create([
                    "user_id" => $user->id,
                    "wallet" => substr(str_shuffle("ABCDEFGHIJKLMNOPQRSTUVWXYZ"), 0, 2).random_int(10000, 99999),
                    "reference" => Str::random(10),
                ]);
                $newobj = array_merge($user->toArray(), [
                    "wallet" => $user->wallet->wallet,
                    "account_type" => $user->wallet->account_type,
                    "balance" => number_format($user->wallet->balance),
                ]);
                $fullObj = array_merge($newobj, $data);
                $lead->delete();
                return response()->json($fullObj, 200);
            }
            if(request('phone') !== null){
                $lead = Lead::where('phone', request('phone'))->first();
                if($lead == null){
                    return response()->json('Invalid Identity', 422);
                }
                $new_data = $lead->toArray();
                $user = User::create(array_merge($new_data, [
                    'password' => bcrypt(request('password')),
                    'is_verified' => 1,
                    'email_verified' => now(),
                    'pin' => bcrypt(request('pin')),
                    'avatar' => 'avatar.png',
                    'reference' => Str::random(8)
                ]));
                $data['token'] = $user->createToken(static::TOKEN_KEY)->accessToken;
                $wallet = Wallet::create([
                    "user_id" => $user->id,
                    "wallet" => substr(str_shuffle("ABCDEFGHIJKLMNOPQRSTUVWXYZ"), 0, 2).random_int(10000, 99999),
                    "reference" => Str::random(10),
                ]);
                $newobj = array_merge($user->toArray(), [
                    "wallet" => $user->wallet->wallet,
                    "account_type" => $user->wallet->account_type,
                    "balance" => number_format($user->wallet->balance),
                ]);
                $fullObj = array_merge($newobj, $data);
                $lead->delete();
                return response()->json($fullObj, 200);
            }
        } catch (\Throwable $th) {
            Log::error('Could not add new user' .$th);
            return response()->json('An Error occurred', 422);
        }
        return response()->json("Server Error", 500);
    }

    public function generate_otp($type= "")
    {
        $otp_gen = mt_rand(0001, 9999);
        $otp = OTPVerification::create([
            'otp' => $otp_gen,
            'token' => bcrypt($otp_gen),
            'type' => ($type) ? $type : null,
            'status' => 0,
            'exp_in' => now()->addSeconds(80),
            'reference' => Str::random(60),
        ]);
        return $otp->otp;
    }

    public function resend_otp()
    {
        $identity =  request('identity');
        $otp = $this->generate_otp('registration');
        if($this->filter_user_identity() == 'email'){
            try {
                Mail::to($identity)->send(new OTPAccountVerification($otp));
                return response(['message' => "Check email for OTP"], 200);
            } catch (\Swift_TransportException $e) {
                Log::error("Couldn't send otp verification code");
                return response(["error" =>'Unable to send OTP'], 422);
            }
        }
        if($this->filter_user_identity() == 'phone'){
            try {
                $sms = new SendSms();
                if(Str::startsWith($identity, '0')){
                    $phone = Str::replaceFirst('0', '234', $identity);
                    $sms->send_token_for_user_account_verification($phone, $otp);
                }
                if(Str::startsWith(request('identity'), '234')){
                    $sms->send_token_for_user_account_verification($identity, $otp);
                }
                return response(["message" => 'OTP Verified'], 200);
            } catch (\Swift_TransportException $e) {
                Log::error("Couldn't send otp verification code");
                return response(["error" =>'Unable to send OTP'], 422);
            }
        }
        return response(["error" => 'Unprocessed Entry'], 422);
    }

    public function verify_otp()
    {
        $otp = request('otp');
        $check_otp = OTPVerification::where('otp', $otp)->first();
        if($check_otp == null){
            return response(["error" => "Invalide OTP"], 422);
        }

        if($check_otp->status == 0){
            $check_otp->update(['status' => 1]);
            return response(['message' => 'OTP Verified'], 200);
        }
        return response(['error'=>'Invalide OTP'], 422);
    }

    public function change_password()
    {
        $validate_data = Validator::make(request()->all(), static::RULES["password"]);
        if($validate_data->fails()){
            return response()->json($validate_data->errors());
        }
        $user = Auth::user();
            if (Hash::check(request()->oldpassword, $user->password)) {
                $user->password = Hash::make(request()->password);
                $user->save();
                return response(["message" => "Password Updated"], 200);
            }
    }

    public function generate_reset_password_link()
    {
        $validate_data = Validator::make(request()->all(), [
            "identity" => "required|string",
        ]);    
        if($validate_data->fails()){
            return response()->json($validate_data->errors());
        }
            if($this->filter_user_identity() == "email"){
                $user = User::whereEmail(request("identity"))->first();
                $lead = Lead::whereEmail(request('identity'))->first();
                if($user != null){
                    try {
                        $otp = mt_rand(0001, 9999);
                        // check if OTP Exist Delete it and Generate a new one
                        $check_otp = OTPVerification::where("email", request('identity'))->first();
                        if($check_otp != null){
                            $check_otp->delete();
                        }
                        $token = OTPVerification::create([
                            'email' => $user->email,
                            "token" => bcrypt($otp),
                            "otp" => $otp,
                            "type" => "Password Reset",
                            "status" => 0,
                            "reference" => Str::random(15),
                            "exp_in" => now()->addHours(12),
                        ]);
                        $user->notify(new ResetPasswordNotification($otp));
                        return response(['message' => "Please check your email for OTP"], 200);
                    } catch (\Throwable $th) {
                        Log::error("Could not generate OTP " . $th->getMessage());
                        return response(["error" => "Something went wrong"], 422);
                    }
                }
                if($lead !=  null){
                    try {
                        $otp = mt_rand(0001, 9999);
                         // check if OTP Exist Delete it and Generate a new one
                        $check_otp = OTPVerification::where("email", request('identity'))->first();
                        if($check_otp != null){
                            $check_otp->delete();
                        }
                        $token = OTPVerification::create([
                            'email' => $lead->email,
                            "token" => bcrypt($otp),
                            "otp" => $otp,
                            "type" => "Password Reset",
                            "status" => 0,
                            "reference" => Str::random(15),
                            "exp_in" => now()->addHours(12),
                        ]);
                        $lead->notify(new ResetPasswordNotification($otp));
                        return response(['message' => "Please check your email for OTP"], 200);
                    } catch (\Throwable $th) {
                        Log::error("Could not generate OTP " . $th->getMessage());
                        return response(["error" => "Something went wrong"], 422);
                    }
                }
            }
            if($this->filter_user_identity() == "phone"){
                $user = User::wherePhone(request("identity"))->first();
                $lead = Lead::wherePhone(request('identity'))->first();
                if($user != null){
                    try {
                        $otp = mt_rand(0001, 9999);
                         // check if OTP Exist Delete it and Generate a new one
                        $check_otp = OTPVerification::where("phone", request('identity'))->first();
                        if($check_otp != null){
                            $check_otp->delete();
                        }
                        $token = OTPVerification::create([
                            'phone' => $user->phone,
                            "token" => bcrypt($otp),
                            "otp" => $otp,
                            "type" => "Password Reset",
                            "status" => 0,
                            "reference" => Str::random(15),
                            "exp_in" => now()->addHours(12),
                        ]);
                        $sms = new SendSms();
                        if(Str::startsWith(request('identity'), '0')){
                            $phone = Str::replaceFirst('0', '234', request('identity'));
                            $sms->send_token_for_user_account_verification($phone, $otp);
                        }
                        if(Str::startsWith(request('identity'), '234')){
                            $sms->send_token_for_user_account_verification(request('identity'), $otp);
                        }
                        return response(['message' => 'Please check your phone for OTP'], 200);
                    } catch (\Throwable $th) {
                        Log::error("Could not generate OTP " . $th->getMessage());
                        return response(["error" => "Something went wrong"], 422);
                    }
                }
                if($lead != null){
                    try {
                        $otp = mt_rand(0001, 9999);
                         // check if OTP Exist Delete it and Generate a new one
                        $check_otp = OTPVerification::where("phone", request('identity'))->first();
                        if($check_otp != null){
                            $check_otp->delete();
                        }
                        $token = OTPVerification::create([
                            'phone' => $lead->phone,
                            "token" => bcrypt($otp),
                            "otp" => $otp,
                            "type" => "Password Reset",
                            "status" => 0,
                            "reference" => Str::random(15),
                            "exp_in" => now()->addHours(12),
                        ]);
                        $sms = new SendSms();
                        if(Str::startsWith(request('identity'), '0')){
                            $phone = Str::replaceFirst('0', '234', request('identity'));
                            $sms->send_token_for_user_account_verification($phone, $otp);
                        }
                        if(Str::startsWith(request('identity'), '234')){
                            $sms->send_token_for_user_account_verification(request('identity'), $otp);
                        }
                        return response(['message' => 'Please check your phone for OTP'], 200);
                    } catch (\Throwable $th) {
                        Log::error("Could not generate OTP " . $th->getMessage());
                        return response(["error" => "Something went wrong"], 422);
                    }
                }
            }
    }


    public function reset_password()
    {
        $validate_data = Validator::make(request()->all(), [
            "password" => "required|string|min:8",
            "confirm_password" => "required|string|min:8|same:password",
        ]);
        if($validate_data->fails()){
            return response()->json($validate_data->errors());
        }
        $data = request()->all();
        try {
                $otp_input = request("otp");
                $otp = OTPVerification::where("otp", $otp_input)->first();
                if($otp == null){
                    return response(["error" => "Invalid OTP"], 422);
                }

                if($this->filter_user_identity() == "email"){
                    $user = User::whereEmail(request("identity"))->first();
                    $lead = Lead::whereEmail(request('identity'))->first();
                    if($user != null){
                        if($otp->email == $user->email){
                            $user->update(["password" => bcrypt($data["password"])]);
                            $otp->delete();
                            return response()->json(["message" => "Password reset successfully"], 200);
                        }
                        return response()->json(['error' => "Invalid Identity"], 422);
                    }
                    if($lead != null){
                        if($otp->email == $lead->email){
                            $lead->update(["password" => bcrypt($data["password"])]);
                            $otp->delete();
                            $newUser = User::create(array_merge($lead->toArray(), [
                                "is_verified" => 1,
                                "reference" => Str::random(8),
                                "email_verified_at" => now(),
                                "avatar" => "avatar.png",
                            ]));
                            $wallet = Wallet::create([
                                "user_id" => $newUser->id,
                                "wallet" => substr(str_shuffle("ABCDEFGHIJKLMNOPQRSTUVWXYZ"), 0, 2).random_int(10000, 99999),
                                "reference" => Str::random(10),
                            ]);
                            $lead->delete();
                            return response()->json(["message" => "Password reset successfully"], 200);
                        }
                        return response()->json(['error' => "Invalid Identity"], 422);
                    }
                   if($user == null && $lead == null){
                       return response()->json(["error" => "Invalid Identity"], 422);
                   }
                
                }
                if($this->filter_user_identity() == "phone")
                {
                    $user = User::wherePhone(request("identity"))->first();
                    $lead = Lead::wherePhone(request('identity'))->first();
                    if($user != null){
                        if($otp->phone == $user->phone){
                            $user->update(["password" => bcrypt($data["password"])]);
                            $otp->delete();
                            return response()->json(["message" => "Password reset successfully"], 200);
                        }
                        return response()->json(['error' => "Invalid Identity"], 422);
                    }
                    if($lead != null){
                        if($otp->phone == $lead->phone){
                            $lead->update(["password" => bcrypt($data["password"])]);
                            $otp->delete();
                            $newUser = User::create(array_merge($lead->toArray(), [
                                "is_verified" => 1,
                                "reference" => Str::random(8),
                                "email_verified_at" => now(),
                                "avatar" => "avatar.png",
                            ]));
                            $wallet = Wallet::create([
                                "user_id" => $newUser->id,
                                "wallet" => substr(str_shuffle("ABCDEFGHIJKLMNOPQRSTUVWXYZ"), 0, 2).random_int(10000, 99999),
                                "reference" => Str::random(10),
                            ]);
                            $lead->delete();
                            return response()->json(["message" => "Password reset successfully"], 200);
                        }
                        return response()->json(['error' => "Invalid Identity"], 422);
                    }
                   if($user == null && $lead == null){
                       return response()->json(["error" => "Invalid Identity"], 422);
                   }
                
                }
        } catch (\Throwable $th) {
            Log::error("Could'nt update password " . $th->getMessage());
            return response(["error" => "Server Error"], 500);
        }
    }

    public function logoutFromDevice()
    {
        if(Auth::check()){
            $user = Auth::user();
            $user->token()->revoke();
            return response()->json(["message" => "logged out successfully"], 200);
        }
        return response()->json(["error" => "Something went wrong"], 500);
    }

    public function updateProfile()
    {
        $data = Validator::make(request()->all(), [
            "firstname" => "required|string",
            "lastname" => "required|string",
            "gender" => "required|string",
            "address" => "required|string",
            "dob" => "required|date_format:Y-m-d"
        ]);
        if($data->fails()){
            return response()->json(["error" => $data->errors()], 422);
        }
        $input = $data->valid();
        $user = Auth::user();
        if(request()->has("identity")){
            if($this->verifyField(request("identity"))->getStatusCode() == 422){
                return response()->json(["error" => "Duplicate Error"], 422);
            }
        }
        if(request()->has("email") || request()->has("phone")){
            $userEmail = User::whereEmail(request("email"))->count();
            if($userEmail > 0){
                return response()->json(["error" => "Duplicate Data"], 422);
            }
            $userPhone = User::wherePhone(request("phone"))->count();
            if($userPhone > 0){
                return response()->json(["error" => "Duplicate Data"], 422);
            }
        }
        $user->update($input);
        $userDetails = UserDetail::where("user_id", $user->id)->first();
        if($userDetails == null){
            UserDetail::create([
                "user_id" => $user->id,
                "city" => request("city"),
                "state" => request("state"), 
                "dob" => request("dob"),
                "gender" => request("gender"),
                "lga" => request("lga"),
                "address" => request("address")
            ]);
        }
        $userDetails->update([
            "city" => request("city"),
            "state" => request("state"), 
            "dob" => request("dob"),
            "gender" => request("gender"),
            "lga" => request("lga"),
            "address" => request("address")
        ]);
        return response()->json([
            "message" => "Profile updated successfully",
            "user" => UserResource::make($user)
        ], 200);
    }
    public function getUserDetails()
    {
        $user = Auth::user();
        return response()->json(["user" => UserResource::make($user)]);
    }
    public function update_bvn()
    {
        $user = Auth::user();
        $validate = Validator::make(request()->all(), [
            "bvn" => 'required|string',
            "bvn_phone" => "required|string"
        ]);
        if($validate->fails()){
            return response()->json(["error" => $validate->errors()], 422);
        }
        $bvn = new BvnVerification();
        $bvn_data =  $bvn->verifyBvn(request("bvn"));
        if($bvn_data->status == false){
            return response()->json(["error" => "Unable to resolve BVN or BVN does not exist"], 422);
        }
        try {
            if($bvn_data->status == true){
                if(Str::lower($user->firstname) != Str::lower($bvn_data->data->first_name)){
                    return response()->json(["error" =>"Bvn Firstname Does not match"], 422);
                }
                if(Str::lower($user->lastname) != Str::lower($bvn_data->data->last_name)){
                    return response()->json(["error" =>"Bvn Lastname Does not match"], 422);
                }
                if(request("bvn_phone") != $bvn_data->data->mobile){
                     return response()->json(["error" =>"Bvn Phone number does not match"], 422);
                }
                if($user->user_detail->dob != $bvn_data->data->formatted_dob){
                     return response()->json(["error" =>"Bvn Date of birth does not match"], 422);
                 }
                 $user->user_detail->update(["bvn" => $bvn_data->data->bvn, "phone" => request("bvn_phone")]);
                 return response()->json(["message" => "BVN updated successfully"], 200);
            }
        } catch (\Throwable $th) {
           Log::error("could not update bvn ". $th);
           return response()->json(["error" => "Something went wrong"], 500);
        }
        return response()->json(["error" => "Server Error"], 500);
    }
    public function uploadAvatar()
    {
        $user = Auth::user();
        $validate_image = Validator::make(request()->all(), [
            "avatar" => "mimes:jpeg,jpg,png|max:20143"
        ]);
      if($validate_image->fails()){
          return response()->json(["error" => $validate_image->errors()], 422);
      }
        $fileNameWithExt = request()->file("avatar")->getClientOriginalExtension();
        $fileNameToStore = uniqid().".".$fileNameWithExt;
        $this->diskAvatar()->putFileAs("/", request()->file("avatar"), $fileNameToStore);
        $user->avatar = $fileNameToStore;
        $user->update();
        return response()->json([
            "message" => "Profile image uploaded successfully",
            "data" => $this->diskAvatar()->url($fileNameToStore)
        ]);
    }
    public function switch_to_agent()
    {
        $user = Auth::user();
        try {
            $transactions = Transaction::where("user_id", $user->id)->latest()->count();
            if($transactions < 5){
                return response()->json(['message' => "You need to make at least Five (5) Transactions before you can become an agent "]);
            }
                if($user->is_agent == 0){
                    $user->update(["is_agent" => 1]);
                    return response()->json(["message" => "Your now Visible as an agent "]);
                }
                if($user->is_agent == 1){
                    $user->update(["is_agent" => 0]);
                    return response()->json(["message" => "Your visiblity is now turned off"]);
                }
        } catch (\Throwable $th) {
           Log::error("Could not switch to agent ". $th);
           return response()->json(["error" => "Could not switch agent on "]);
        }
    }

    public function get_agents()
    {
        $agents = User::where("is_agent", 1)->get(["firstname", "lastname", "phone", "email"]);
        if($agents->count() == 0){
            return response()->json(["message" => "There are no agents available for now "]);
        }
        return response()->json($agents);
    }

    public function list_accounts()
    {
        $user = Auth::user();
        $businesses = Business::where("user_id", $user->id)->pluck("id", "business_name");
        $businessWallets = Wallet::whereIn("business_id", $businesses)->get();
        $newObj = array_merge($businessWallets->toArray(), ["user" => $user->wallet, "fullname" => $user->firstname. " " .$user->lastname]);
       return response()->json($newObj);
    }
    public function delete_account()
    {
        if(Auth::check()){
            try {
                $user = Auth::user();
                $user->business()->delete();
                $user->wallet()->delete();
                $user->user_detail()->delete();
                $user->delete();
                return response()->json(["message" => "account deleted successfully"], 200);
            } catch (\Throwable $th) {
               Log::error(["error" => "Could not delete account"], 422);
               return response()->json(["error" => "Could not delete account"], 422);
            }
            return response()->json(["error" => "Something went wrong"], 500);
        }
        return response()->json(["error" => "Unauthorized"], 500);
    }

    public function checkProfileCount()
    {
        $user = Auth::user();
        $count = 0; 
        $user_data_blacklist = ["reference", "id", "is_agent"];
        foreach($user->toArray() as $key => $user_d){
            if(in_array($key, $user_data_blacklist)) continue;
            if($user_d != null){
                $count = $count + 1;
            }
        }
        $userCount = $count;
        $profile = UserDetail::find($user->id);
        if($profile == null){
            return response()->json([
                "complete" => 14,
                "count" => $userCount
            ], 200);
        }
        $black_list = ["user_id", "id", "created_at", "updated_at", "country"];
        foreach($profile->toArray() as $prop => $value){
            if(in_array($prop, $black_list)) continue;
            if(!is_null($value)){
                $count = $count + 1;
            }
        }
        return response()->json([
            "complete" => 14,
            "count" => $count
            ]);
    }

    public function inviteFriends()
    {
        $user = Auth::user();
        $name = $user->firstname . " " .$user->lastname;
        $validate = Validator::make(request()->all(), [
            "phone" => "required|array",
        ]);
        if($validate->fails()){
            return response()->json([
                "error" => $validate->errors()
            ], 422);
        }
        $input =  request()->all();
        foreach ($input["phone"] as $invite) {
            $sms = new SendSms();
         
            if(Str::startsWith($invite, '0')){
                $phone = Str::replaceFirst('0', '234', $invite);
                    $sms->send_invitation_to_contact($phone, $user->reference, $name);
                }
            if(Str::startsWith($invite, '234')){
                $sms->send_invitation_to_contact($phone, $user->reference, $name );
            }
            if(Str::startsWith($invite, '+')){
                $phone = Str::replaceFirst('+', '', $invite);
                $sms->send_invitation_to_contact($phone, $user->reference, $name );
            }
        }
        return response()->json([
            "message" => "Invitaition sent successfully"
        ], 200);
    }
}