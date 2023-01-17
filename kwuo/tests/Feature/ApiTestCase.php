<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Passport\Passport;
use Tests\TestCase;

class ApiTestCase extends TestCase
{
    use RefreshDatabase;
    /**
     * A basic feature test example.
     *
     * @return void
     */
    public function api_url($str = "")
    {
        return str_replace('//', '/', "/api/v1/{$str}");
    }
    public function admin_url($str = "")
    {
        return str_replace('//', '/', "/api/v1/adminaccess/{$str}");
    }
    public function authenticate_user($scopes = [], $props = [])
    {
        $user = factory(User::class)->create($props);
        Passport::actingAs($user, $scopes, 'api');
        return $user;
    }
}
