<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateOTPVerificationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('o_t_p_verifications', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('otp');
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('type')->nullable(); //User Registration Verification, Transaction Verificaton
            $table->boolean('status')->default(0); //Used = 1; Unused = 0;
            $table->time('exp_in'); //Expiration time for the generated Otp
            $table->string('reference', 60); //Reference for Tracking the otp
            $table->string('token')->nullable();
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
        Schema::dropIfExists('o_t_p_verifications');
    }
}
