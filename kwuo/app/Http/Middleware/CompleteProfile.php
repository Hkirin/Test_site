<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;
use App\Models\UserDetail;
use App\Models\User;

class CompleteProfile
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        if(request()->url() == route('agent_switch')){
            $check_user = (bool) $this->complete_details();
            if($check_user){
                return $next($request);
            }
            return response()->json(['message' => "Please complete Profile "]);
        }
    }
    public function complete_details(){
        $user = Auth::user();
        $profile = UserDetail::find($user->id);
        $data = $profile;
        $blackList = ['valid_identity', 'bvn'];
        $nulls = [];
        $count = 0;
        foreach($data as $prop => $value){
            if(in_array($prop, $blackList))continue;
            if(!is_null($value)){
                $count  = $count + 1;
            }else{
                $nulls[] = $prop;
            }
        }
        if(count($nulls) > 0){
            return false;
        }
        return true;
    }
}
