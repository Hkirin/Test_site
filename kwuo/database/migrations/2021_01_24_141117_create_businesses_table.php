<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateBusinessesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('businesses', function (Blueprint $table) {
            $table->id();
            $table->string('business_name')->nullable();
            $table->string('slug')->nullable();
            $table->foreignId('category_id')->nullable()->constrained()->OnDelete("set null");
            $table->string('logo')->nullable();
            $table->string('reference')->unique()->nullable();
            $table->string('business_type')->nullable();// Starter or Existing
            $table->string('address')->nullable();
            $table->tinyInteger('status')->default(0); //Active = 1, Processing = 2, Ban = 3;
            $table->string('website')->nullable();
            $table->string('phone')->nullable();
            $table->string('rc_number')->nullable();
            $table->string('cac')->nullable();
            $table->string('email')->unique()->nullable();
            $table->foreignId('user_id')->constrained()->onDelete("cascade");
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
        Schema::dropIfExists('businesses');
    }
}
