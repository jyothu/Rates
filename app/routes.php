<?php

Route::get(
    '/',
    ['as' => 'landing', 'uses' => 'HomeController@showLanding']
);

Route::get(
    '/rates',
    ['as' => 'rates', 'uses' => 'HomeController@showRates']
);

Route::post(
    '/service/options',
    ['uses' => 'RateController@getOptions']
);

Route::post(
    '/service/rates',
    ['uses' => 'RateController@getRates']
);

Route::group(['prefix' => 'api'], function () {
    Route::group(['prefix' => 'v1', 'before' => 'authv1'], function () {

        Route::post(
            '{uri}',
            ['uses' => 'App\Controllers\ApiController@callFunction']
        );

    });
});
