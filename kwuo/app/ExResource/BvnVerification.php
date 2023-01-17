<?php
    namespace App\ExResource;

    class BvnVerification{

        public function verifyBvn($bvn){
            $curl = curl_init();
            curl_setopt_array($curl, array(
                CURLOPT_URL => "https://api.paystack.co/bank/resolve_bvn/$bvn",
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => "",
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 30,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => "GET",
                CURLOPT_HTTPHEADER => array(
                "Authorization: Bearer ". env("PAYSTACK_LIVE_SECRET_KEY"),
                "Cache-Control: no-cache",
                ),
            
            ));
            $response = curl_exec($curl);
            $err = curl_error($curl);
            curl_close($curl);

            if ($err) {
                Log::error('Curl Error: ' . $err);
                return response()->json(['error' => "Server Error"], 500);
            } else {
                return json_decode($response);
            }
        }
    }