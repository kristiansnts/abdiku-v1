<?php

use App\Http\Controllers\DemoController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->to('/admin');
});

Route::get('/login', function () {
    return redirect()->to('/admin/login');
});

Route::middleware('throttle:3,10')->group(function () {
    Route::get('/demo/start', [DemoController::class, 'show'])->name('demo.show');
    Route::post('/demo/start', [DemoController::class, 'start'])->name('demo.start');
    Route::get('/demo/ready', [DemoController::class, 'ready'])->name('demo.ready');
});

