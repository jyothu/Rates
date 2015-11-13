<?php

Route::get(
    '/',
    ['as' => 'landing', 'uses' => 'HomeController@showLanding']
);

Route::get(
    '/rates',
    ['as' => 'rates', 'uses' => 'HomeController@showRates']
);

Route::get(
    '/services',
    ['as' => 'services', 'uses' => 'ServiceController@index']
);

Route::get(
    '/services/create',
    ['as' => 'services.create', 'uses' => 'ServiceController@create']
);

Route::get(
    '/services/{id}',
    ['as' => 'services.show', 'uses' => 'ServiceController@show']
);


Route::get(
    '/services/{id}/edit',
    ['as' => 'services.edit', 'uses' => 'ServiceController@edit']
);


Route::post(
    '/services', ['as' => 'services.store', 'uses' => 'ServiceController@store']
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

    Route::group(['prefix' => 'fastbuild/v1', 'before' => 'authv1'], function () {

        Route::post(
            '{uri}',
            ['uses' => 'App\Controllers\FastBuildController@callFunction']
        );

    });
});
