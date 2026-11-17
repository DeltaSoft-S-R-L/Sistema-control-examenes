<?php

use Illuminate\Support\Facades\Route;

// Serve the React application for the root URL and client-side routes.
Route::get('/{any?}', function () {
    return view('index');
})->where('any', '^(?!api).*$');
