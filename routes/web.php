<?php

Route::get('/', function () {
    return view('main');
});

Route::post('/', function() {
    dd(555);
    return redirect('/');
});

/*
Route::get('/user', 'extensionController@index');

Route::get('/user/add', 'extensionController@create');

Route::post('/user/add', 'extensionController@store');

Route::post('/user/{id}/delete', 'extensionController@delete');

Route::get('/user/{id}/edit', 'extensionController@edit_view');

Route::post('/user/{id}/edit', 'extensionController@edit');

Route::get('/user/log', 'extensionController@log');
*/
