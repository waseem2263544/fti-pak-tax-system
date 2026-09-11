<?php

use Illuminate\Support\Facades\Route;

// Chrome Extension API (no CSRF, token-based auth)
Route::post('ext/login', [\App\Http\Controllers\CredentialApiController::class, 'login']);
Route::get('ext/clients', [\App\Http\Controllers\CredentialApiController::class, 'searchClients']);
Route::get('ext/credentials/{client}', [\App\Http\Controllers\CredentialApiController::class, 'getCredentials']);

// WHT data for the wht-psid Claude skill (read-only, shared-token auth)
Route::get('wht/agents', [\App\Http\Controllers\Api\WhtPsidApiController::class, 'agents']);
Route::get('wht/psid', [\App\Http\Controllers\Api\WhtPsidApiController::class, 'psid']);
Route::get('wht/psid/file', [\App\Http\Controllers\Api\WhtPsidApiController::class, 'file']);
Route::get('wht/status', [\App\Http\Controllers\Api\WhtPsidApiController::class, 'status']);
