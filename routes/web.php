<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return ['Laravel' => app()->version()];
});

Route::get('/adduser', function () {
    return view('adduser'); // sesuaikan dengan path relatif terhadap /views/
});

// require __DIR__.'/auth.php';
