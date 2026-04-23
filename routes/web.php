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
| These routes proxy requests to the BKN SIASN API.
| All requests include both APIM and SSO token authentication.
|
| - /api/siasn/{path} → APIM + SSO auth
|
*/

$router->group(['middleware' => 'api_key', 'prefix' => 'api'], function () use ($router) {

    // Proxy route
    $router->addRoute(['GET', 'POST', 'PUT', 'PATCH', 'DELETE'], 'siasn[/{path:.*}]', 'ProxyController@proxy');
});
