<?php

namespace App\Http\Controllers\Api\Client;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Auth;

class UsersNotificationController extends Controller
{
    public function get_notifications()
    {
        $user = Auth::user();
        return response()->json($user->notifications);
    }
}
