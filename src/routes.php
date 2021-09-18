<?php

use Illuminate\Support\Facades\Route;
use Niush\LaravelNanoTo\Http\Controllers\OrderController;

Route::get('/order/success/{id}', [OrderController::class, 'success'])->name('nano-to-success');
Route::get('/order/cancel/{id}', [OrderController::class, 'cancel'])->name('nano-to-cancel');

// Webhook to update the order status
Route::post('/order/webhook/{id}', [OrderController::class, 'webhook'])->name('nano-to-webhook');
