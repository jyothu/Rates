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
