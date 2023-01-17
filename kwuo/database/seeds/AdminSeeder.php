<?php

use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use App\Models\Admin;

class AdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Admin::truncate();
        factory(Admin::class)->create([
            "firstname" => "Steve",
            "lastname" => "Brooks",
            "username" => "steve",
            "phone" => "+23480374873",
            "email" => "bsteve@example.com",
            "password" => bcrypt("annoyingsecret"),
            "reference" => Str::random(10),
            "type" => "_admin",
        ]);
        factory(Admin::class)->create([
            "firstname" => "Daniel",
            "lastname" => "Stones",
            "username" => "stons",
            "phone" => "+2347044873",
            "email" => "dstones@example.com",
            "password" => bcrypt("annoyingsecret"),
            "reference" => Str::random(10),
            "type" => "_staff",
        ]);
    }
}
