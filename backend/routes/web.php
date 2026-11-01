<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
| Ruta catch-all para la SPA (React). Laravel sirve index.blade.php para
| cualquier ruta que no empiece con "api", permitiendo que React Router
| maneje la navegación y evitando errores 404 al refrescar el navegador.
*/

Route::get('/{any?}', function () {
    return view('index');
})->where('any', '^(?!api).*$');
