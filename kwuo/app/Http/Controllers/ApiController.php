<?php

namespace App\Http\Controllers;

class ApiController extends Controller
{
    public function message($message = "No Message", $status = 200)
    {
        return response()->json(compact('message'), $status);
    }
    public function fallback()
    {
        return response()->json(['message' => "Invalid URL"]);
    }
}
