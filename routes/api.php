<?php

use Illuminate\Support\Facades\Route;

// Chrome Extension API (no CSRF, token-based auth)
Route::post('ext/login', [\App\Http\Controllers\CredentialApiController::class, 'login']);
Route::get('ext/clients', [\App\Http\Controllers\CredentialApiController::class, 'searchClients']);
Route::get('ext/credentials/{client}', [\App\Http\Controllers\CredentialApiController::class, 'getCredentials']);
