<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\{AuthController};

/*
|----------------------------------------------------------------------
| API Routes
|----------------------------------------------------------------------
|
| Aqui é onde você pode registrar rotas da API para sua aplicação.
| As rotas são carregadas pelo RouteServiceProvider e todas elas
| serão atribuídas ao grupo de middleware "api". Faça algo incrível!
|
*/

//Auth
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::middleware(['auth:sanctum'])->group(function () {

});

