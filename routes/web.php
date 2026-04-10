<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return 'Homepage';
});

Route::get('/map', function () {
    return view('map');
});