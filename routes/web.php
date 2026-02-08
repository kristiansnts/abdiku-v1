<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->to('/admin');
});

Route::get('/login', function () {
    return redirect()->to('/admin/login');
});
