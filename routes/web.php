<?php

Route::get('/', function () {
    return redirect('/user');
});

Route::get('/user', 'extensionController@index');

Route::get('/user/add', 'extensionController@create');

Route::post('/user/add', 'extensionController@store');

Route::post('/user/{id}/delete', 'extensionController@delete');

Route::get('/user/{id}/edit', 'extensionController@edit_view');

Route::post('/user/{id}/edit', 'extensionController@edit');

Route::get('/test', function() {
    /* Check connection with remote sqlsrv server */
    //DB::connection('sqlsrv')->getPdo();
    return 'DONE';
});
