<?php

use Illuminate\Support\Facades\Route;
use Webkul\Razorpay\Http\Controllers\OAuthController;

Route::group(['middleware' => ['web', 'admin'], 'prefix' => config('app.admin_url')], function () {
    Route::controller(OAuthController::class)
        ->prefix('razorpay/oauth')
        ->group(function () {
            Route::get('authorize', 'authorize')->name('admin.razorpay.oauth.authorize');

            Route::get('callback', 'callback')->name('admin.razorpay.oauth.callback');

            Route::delete('disconnect', 'disconnect')->name('admin.razorpay.oauth.disconnect');
        });
});
