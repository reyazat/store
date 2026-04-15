<?php

use Illuminate\Support\Facades\Route;

// Vue SPA - All routes handled by Vue Router (except API routes)
Route::get('/{any?}', function () {
    return view('app');
})->where('any', '^(?!api).*');
