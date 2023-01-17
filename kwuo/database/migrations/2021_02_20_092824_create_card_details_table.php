<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCardDetailsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('card_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId("user_id")->constrained()->onDelete("cascade");
            $table->string("card_holder_name");
            $table->string("card_no");
            $table->string("card_cvv");
            $table->string("mask_card");
            $table->date("card_exp_date");
            $table->string("reference")->nullable();
            $table->enum('type', ["unknown","master", "visa", "verve"]);
            $table->boolean("status")->default(0);
            $table->string("transaction_id")->nullable();
            $table->double("amount")->nullable();
            $table->boolean("refund")->default(0);
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
        Schema::dropIfExists('card_details');
    }
}
