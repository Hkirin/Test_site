<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTransactionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->string('transaction_id')->unique();
            $table->string('reference')->nullable();
            $table->enum('account_type', ['personal', 'business']);
            $table->foreignId('wallet_id')->constrained()->onDelete('cascade');
            $table->unsignedBigInteger('user_id')->nullable();
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade'); //represents recipient id in the transactions table
            $table->enum('status', [ 'unknown','processing','pending', 'failed', 'cancelled', 'revert', 'success']);
            $table->enum('transaction_method', ["unknown", "kwuo_transfer", "card", "bank", "airtime", "data_purchase", "ussd", "payment_request"])->nullable()->comment("Bank Transfer, USSD, Kwuo Transfer, Debit Card, Airtime"); //Bank Transfer, Direct Bank Deposit, Kwuo Transfer, Debit Card
            $table->enum('transaction_type', ['debit', 'credit', 'topup']);
            $table->text('message')->nullable();
            $table->double('amount');
            $table->double('previous_balance')->default(0);
            $table->double('new_balance')->default(0);
            $table->double('transfer_fee')->default(0);
            $table->json("network_provider")->nullable();
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
        Schema::dropIfExists('transactions');
    }
}
