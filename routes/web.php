<?php

/** @var \Laravel\Lumen\Routing\Router $router */

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It is a breeze. Simply tell Lumen the URIs it should respond to
| and give it the Closure to call when that URI is requested.
|
*/

$router->get('/', function () use ($router) {
    return response()->json([
        'name' => config('app.name'),
        'status' => 'running',
    ]);
});

/*
|--------------------------------------------------------------------------
| Gateway Routes (Protected by API Key)
|--------------------------------------------------------------------------
|
| These routes proxy requests to the BKN Web Service API.
| The full path after /api/ is forwarded as-is to the configured base URL.
|
| Examples:
| - /api/apisiasn/1.0/pns/data-utama/{nip}
| - /api/referensi_siasn/1/agama
|
*/

$router->group(['middleware' => 'api_key', 'prefix' => 'api'], function () use ($router) {

    // Catch-all proxy route — forwards full path to SIASN API
    $router->addRoute(['GET', 'POST', 'PUT', 'PATCH', 'DELETE'], '{path:.*}', 'ProxyController@proxy');
});
