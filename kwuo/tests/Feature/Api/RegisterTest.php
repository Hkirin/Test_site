<?php

namespace Tests\Feature\Api;

use App\Models\OTPVerification;
use App\Models\User;
use Tests\Feature\ApiTestCase;

class RegisterTest extends ApiTestCase
{
    public function setUp(): void
    {
        parent::setUp();
        $this->afterApplicationCreated(function(){
          $this->artisan('passport:install');
        });
    }

    public function test_if_user_identity_is_already_used()
    {
        //User Unique Identity could be Email or Phone number
        $user = factory(User::class)->create(['phone' => null]);
        $response = $this->postJson('/api/v1/register/verify-identity', [
            'identity' => ($user) ? $user->email : $user->phone
        ]);
        $response->assertStatus(422);
    }

    public function test_if_a_user_can_register_using_email_or_phone_number()
    {
        $response = $this->postJson('/api/v1/store', [
            'firstname' => "Maccladone",
            'lastname' => "Steve .o ",
            'identity' => "maccladone@example.net", // Email or Phone number (String)
            'password' => 'pacifier',
            'confirm_password' => 'pacifier',
            'pin' => '2468'
        ]);
        $response->assertStatus(200);
    }

    public function test_if_user_otp_is_generated_and_send()
    {
        $response = $this->postJson("/api/v1/register", [
            'identity' => 'zeddicus@example.net'
        ]);
       $response->assertStatus(200);
    }

    public function test_if_otp_is_valid()
    {
        $otp = factory(OTPVerification::class)->create();
        $response = $this->postJson('/api/v1/register/verify/otp', [
            'otp' => $otp->otp
        ]);
        ($otp->status != 1)? $response->assertStatus(200) : $response->assertStatus(422);
    }

    public function test_resend_otp_for_registration()
    {
        $response = $this->postJson('/api/v1/register/resend/otp', [
            'identity' => 'zeddicuse@example.net'
        ]);
        $response->assertStatus(200);
    }
}
