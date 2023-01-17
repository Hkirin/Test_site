<?php

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\UserDetail;
use App\Models\Wallet;
use Illuminate\Support\Facades\Schema;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Schema::disableForeignKeyConstraints();
        User::truncate();
        factory(User::class)->create([
            "firstname" => "Zoie",
            "lastname" => "Lambert",
            "email" => "zoie@example.com",
            "password" => bcrypt('secret'),
            "phone" => "2348000000000",
            "pin" => bcrypt("1234"),
            "avatar" => "avatar.png",
            "is_verified" => 1,
        ]);
        factory(User::class)->create([
            "firstname" => "Michael",
            "lastname" => "Jons",
            "email" => "jons@example.com",
            "password" => bcrypt('secret'),
            "phone" => "234809999999",
            "pin" => bcrypt("1234"),
            "avatar" => "avatar.png",
            "is_verified" => 1,
        ]);
        Wallet::truncate();
        factory(Wallet::class)->create([
            "user_id" => 1,
            "account_type" => "personal",
            "balance" => "800000"
        ]);
        factory(Wallet::class)->create([
            "user_id" => 2,
            "account_type" => "personal",
            "balance" => "500000"
        ]);
        UserDetail::truncate();
        factory(UserDetail::class)->create([
            "user_id" => 1,
            "country" => "Nigeria",
            "state" => "Rivers State",
            "lga" => "Obio Akpor",
            "phone" => "2348000000000",
            "gender" => 'female',
            "bvn" => "12345678911",
        ]);
        factory(UserDetail::class)->create([
            "user_id" => 2,
            "country" => "Nigeria",
            "state" => "Akawa Ibom State",
            "lga" => "Uyo",
            "phone" => "23480999999",
            "gender" => 'male',
            "bvn" => "12728473623",
        ]);
        $users = factory(User::class, 3)->create();
        foreach($users as $user){
            factory(Wallet::class)->create([
                'user_id' => $user->id,
                "account_type" => "personal"
            ]);
        }
        Schema::enableForeignKeyConstraints();
    }
}
