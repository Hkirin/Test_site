<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAdminsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('admins', function (Blueprint $table) {
            $table->id();
            $table->string("firstname");
            $table->string("lastname");
            $table->string("username", 10)->unique();
            $table->string("email", 77)->unique();
            $table->string("phone")->nullable();
            $table->string("password");
            $table->string("reference")->nullable();
            $table->boolean("ban")->default(0);
            $table->enum("type", ["unknown", "_staff", "_admin"]);
            $table->string("avatar")->default("avatar.png");
            $table->boolean("verified")->default(0);
            $table->rememberToken();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('admins');
        Schema::table('admins', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });
    }
}
