<?php
    namespace App\Sms;
    class SendSms{
        private $api_key = "TLEA8bm9cSbuT9zQTNJSzdUEvXnoxclCOSaFpKwxiohor6WYkoSYf7apTuxQSa";

        public function send_token_for_user_account_verification($phone, $otp){
            $curl = curl_init();
            $data = [
                'api_key' => $this->api_key,
                "message_type" => "NUMERIC",
               "to" => $phone,
               "from" => "N-Alert",
               "channel" => "dnd",
               "pin_attempts" => 1,
               "pin_time_to_live" =>  2,
               "pin_length" => 4,
               "pin_placeholder" => $otp,
               "message_text" => "Your Kwuo Wallet confirmation code is " . $otp ." Valid for 10 minutes, one-time use only.",
               "pin_type" => "NUMERIC"
            ];
            $post_data = json_encode($data);
            curl_setopt_array($curl, array(
                CURLOPT_URL => "https://termii.com/api/sms/otp/send",
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => "",
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => "POST",
                CURLOPT_POSTFIELDS => $post_data,
                CURLOPT_HTTPHEADER => array(
                  "Content-Type: application/json"
                ),
              ));
              $response = curl_exec($curl);
              curl_close($curl);
              return $response;
        }

        public function send_invitation_to_contact($phone, $referral_code, $name){
          $curl = curl_init();
          $data = [
            'api_key' => $this->api_key,
            "type" => "plain",
            "to" => $phone,
            "from" => "Kwuo",
            "channel" => "generic",
            "sms" => $name." has invited you to use the Kwuo app to pay & collect instant payment from anyone anywhere. Kwuo simply means pay " . "Click here to download the app: https://xxxxxxxxxxxxxxxxxx
            and use the referral code: " .$referral_code. " to claim your bonus." 
          ];
          $post_data = json_encode($data);
          curl_setopt_array($curl, array(
              CURLOPT_URL => "https://termii.com/api/sms/send",
              CURLOPT_RETURNTRANSFER => true,
              CURLOPT_ENCODING => "",
              CURLOPT_MAXREDIRS => 10,
              CURLOPT_TIMEOUT => 0,
              CURLOPT_FOLLOWLOCATION => true,
              CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
              CURLOPT_CUSTOMREQUEST => "POST",
              CURLOPT_POSTFIELDS => $post_data,
              CURLOPT_HTTPHEADER => array(
                "Content-Type: application/json"
              ),
            ));
            $response = curl_exec($curl);
            curl_close($curl);
            return $response;
      }
    }
