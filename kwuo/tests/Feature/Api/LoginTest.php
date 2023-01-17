<?php

namespace Tests\Feature\Api;

use Tests\Feature\ApiTestCase;
use App\Models\User;

class LoginTest extends ApiTestCase
{
  public function setUp(): void
  {
      parent::setUp();
      $this->afterApplicationCreated(function(){
        $this->artisan('passport:install');
      });
  }
    /**
     * @group Api
     **/
  public function test_invalid_user_get_unauthorized()
  {
        $user = factory(User::class)->create(['password' => bcrypt('developer')]);
        $response = $this->postJson('/api/v1/login', [
            'email' => $user->email,
            'password' => 'password', //wrong password
        ]);
        $response->assertUnauthorized()->assertSee('Unauthorized');
  }
    /**
     * @group Api
     **/
  public function test_get_token_if_user_credentials_is_correct()
  {
      $user = factory(User::class)->create(['password' => bcrypt($pass = 'encodedentity')]);
      $response = $this->postJson('/api/v1/login', [
        'identity' => ($user) ? $user->email : $user->phone ,
        'password' => $pass
      ]);
      $this->assertNotNull($response['token']);
      $response->assertStatus(200)->assertJsonStructure(['token']);
  }

}
