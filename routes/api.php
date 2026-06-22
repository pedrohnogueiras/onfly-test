<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::get('/ping', fn () => ['message' => 'pong']);

Route::controller(AuthController::class)->prefix('auth')->group(function () {
    Route::post('/token', 'auth')->name('auth.token');
});

Route::controller(UserController::class)->prefix('users')->group(function () {
    Route::post('/', 'store')->name('users.store');
});

Route::controller(OrderController::class)
    ->prefix('pedido')
    ->middleware('jwt')
    ->group(function () {
        Route::get('/', 'list')->name('pedido.listar');
        Route::get('/{order_reference}', 'index')->name('pedido.buscar');
        Route::post('/', 'store')->name('pedido.cadastro');
        Route::patch('/{order_reference}/status', 'changeStatus')
            ->middleware('admin')
            ->name('pedido.status');
    });
