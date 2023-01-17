<?php
    namespace App\ExResource;
    use Illuminate\Support\Facades\Log;
    class UtilityBills{

        //Bills Payment for VTpass API
        public function initiateProcess($data)
        {
            $curl = curl_init();
            curl_setopt_array($curl, array(
                CURLOPT_URL =>env("VTPASS_BASE_URL")."pay",
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_ENCODING => "",
                    CURLOPT_MAXREDIRS => 10,
                    CURLOPT_USERPWD => env("VTPASS_USERNAME").":" .env("VTPASS_PASSWORD"),
                    CURLOPT_TIMEOUT => 30,
                    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                    CURLOPT_CUSTOMREQUEST => "POST",
                    CURLOPT_POSTFIELDS => $data,
                ));
                $response = curl_exec($curl);
                $err = curl_error($curl);
                curl_close($curl);
            if ($err) {
                Log::error('Curl Error: ' . $err);
                return response()->json(['error' => "Server Error"], 500);
            } else {
                return json_decode( $response);
            }
        }
        public function get_data_bundle($network_provider)
        {
            $curl = curl_init();
            curl_setopt_array($curl, array(
                CURLOPT_URL =>env("VTPASS_BASE_URL")."service-variations?serviceID=".$network_provider. "-data",
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => "",
            ));
                $response = curl_exec($curl);
                $err = curl_error($curl);
                curl_close($curl);
            if ($err) {
                Log::error('Curl Error: ' . $err);
                return response()->json(['error' => "Server Error"], 500);
            } else {
                return json_decode( $response);
            }
        }
}