<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;


Route::group(['prefix' => 'v1'], function(){
    Route::post('register', 'Api\Client\UsersController@register');
    Route::post('register/lead', 'Api\Client\UsersController@lead_store');
    Route::post('store', 'Api\Client\UsersController@store');
    Route::post('login', 'Api\Client\UsersController@login');
    Route::post('register/verify-identity', 'Api\Client\UsersController@verifyField');
    Route::post("register/resend/otp", 'Api\Client\UsersController@resend_otp');
    Route::post('register/verify/otp', 'Api\Client\UsersController@verify_otp');
    Route::post("password/reset", "Api\Client\UsersController@generate_reset_password_link");
    Route::post("password/reset/update", "Api\Client\UsersController@reset_password");
    
    Route::group(['middleware' => ['auth:api']], function () {
        Route::post('profile/password/update', "Api\Client\UsersController@change_password");
        Route::post('logout', "Api\Client\UsersController@logoutFromDevice");
        Route::post('profile/update', "Api\Client\UsersController@updateProfile");
        Route::get("profile/percent", "Api\Client\UsersController@checkProfileCount");
        Route::get("details", "Api\Client\UsersController@getUserDetails");
        Route::post("profile/update/avatar", "Api\Client\UsersController@uploadAvatar");
        Route::post("profile/update/bvn", "Api\Client\UsersController@update_bvn");


        Route::get('transactions/log', "Api\Client\TransactionsController@transaction_log");
        Route::get('business/{business}/transactions/log', "Api\Client\TransactionsController@business_transaction_log");
        Route::post('transaction', "Api\Client\TransactionsController@add_transaction");
        Route::post('transaction/verify/identity', "Api\Client\TransactionsController@kwuo_identity");
        Route::post('transaction/transfer/', "Api\Client\TransactionsController@kwuo_transfer");
        Route::post('wallet/balance', "Api\Client\TransactionsController@getWalletBalance");
        Route::post('transaction/set-pin', "Api\Client\TransactionsController@setTransactionPin");
        Route::post("transaction/reset-pin", "Api\Client\TransactionsController@resetTransactionPin");
        Route::post('transaction/aritime/purchase', "Api\Client\UtilityBillTransactionController@airtime_purchase");
        Route::get("data/bundle/{network}", "Api\Client\UtilityBillTransactionController@data_bundle");
        Route::post("transaction/data/purchase", "Api\Client\UtilityBillTransactionController@purchase_data");
        Route::get("network/providers", "Api\Client\UtilityBillTransactionController@list_of_network_service_providers");
        Route::get("transaction/frequent", "Api\Client\TransactionsController@frequentlyTransfered");

        Route::post('card/new', "Api\Client\CardDetailsController@add_card");
        Route::get('cards', "Api\Client\CardDetailsController@get_cards");
        Route::get('notifications', "Api\Client\UsersNotificationController@get_notifications");
        Route::post('agent/switch', "Api\Client\UsersController@switch_to_agent")->middleware("completeprofile")->name("agent_switch");
        Route::get("agents", "Api\Client\UsersController@get_agents");

        Route::post("init-invoice/request/{reference}", "Api\Client\InvoiceController@initiate_invoice");
        Route::post("invoice/request/action/{reference}", "Api\Client\InvoiceController@accept_decline_invoice");
        Route::post("invoice/request/cancel/{reference}", "Api\Client\InvoiceController@cancel_invoice");
        Route::post("invoice/request/pay/{reference}", "Api\Client\InvoiceController@make_invoice_payment");
        Route::get("invoice/requests", "Api\Client\InvoiceController@invoices");
        Route::get("invoices/log/{reference}", "Api\Client\InvoiceController@invoices_log");

        Route::post("business/create", "Api\Client\BusinessController@store");
        Route::get("accounts", "Api\Client\UsersController@list_accounts");
        Route::delete("account/delete", "Api\Client\UsersController@delete_account");

        Route::post("/invite", "Api\Client\UsersController@inviteFriends");
       
    });

    // Admin | Staff

    Route::group(["prefix" => "adminaccess"], function(){
        Route::post("login", "Api\Admin\AdminController@login");
    });
});










// Fallback Function for Invalid Routes
Route::fallback(function(){
    return response([
        'error' => 'Page Not Found. If error persists, Please contact service provider'
    ], 404);
});
