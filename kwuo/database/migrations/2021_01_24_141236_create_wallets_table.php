<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateWalletsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('wallets', function (Blueprint $table) {
            $table->id();
            $table->string('wallet', 7)->unique();
            $table->foreignId("user_id")->nullable()->constrained()->onDelete("cascade");
            $table->foreignId("business_id")->nullable()->constrained()->onDelete("cascade");
            $table->enum('account_type', ['personal', 'business']);
            $table->string('reference')->nullable();
            $table->double('balance')->default(0);
            $table->tinyInteger('status')->default(0);
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
        Schema::dropIfExists('wallets');
    }
}
