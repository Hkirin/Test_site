<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use App\Traits\FileUploadTrait;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use App\Http\Resources\Admin\AdminResources;

class AdminController extends Controller
{
    protected const TOKEN_KEY = "AdminToken";
    use FileUploadTrait;
    
    public function login()
    {
        if(auth("admin")->attempt(["username" => request("username"), "password" => request("password")])){
            $admin = auth("admin")->user(); 
            if($admin->ban == 1){
                Auth::logout();
                return response()->json([
                    "message" => "Your account has been suspended please contact the service providers",
                    "redirect_to" => route("login"),
                ], 401);
            }
            $sucess["token"] = $admin->createToken(static::TOKEN_KEY)->accessToken;
            $success["redirect_to"] = route("home");
            return response()->json([
                "token" => $sucess,
                "data" => AdminResources::make($admin)
            ]);
        }else{
            return response()->json([
                "error" => "Unauthorised",
                "message" => "Invalide Username or password"
            ], 401);
        }
    }

    public function check_reset_password_identity()
    {
        $validate = Validator::make(request()->all(), [
            "email" => "required|string",
        ]);
        if($validate->fails()){
            return response()->json(["error" => $validate->errors()]);
        }
    }


}
