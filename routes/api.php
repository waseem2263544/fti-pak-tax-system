<?php

use Illuminate\Support\Facades\Route;

// Chrome Extension API (no CSRF, token-based auth)
Route::post('ext/login', [\App\Http\Controllers\CredentialApiController::class, 'login']);
Route::get('ext/clients', [\App\Http\Controllers\CredentialApiController::class, 'searchClients']);
Route::get('ext/credentials/{client}', [\App\Http\Controllers\CredentialApiController::class, 'getCredentials']);
Route::get('ext/wht/psid-request/{token}', [\App\Http\Controllers\CredentialApiController::class, 'psidRequest']);
Route::post('ext/wht/psid-request/{token}', [\App\Http\Controllers\CredentialApiController::class, 'completePsidRequest']);
Route::get('ext/wht/psid-request/{token}/file', [\App\Http\Controllers\CredentialApiController::class, 'psidRequestFile']);

// WHT data for the wht-psid Claude skill (read-only, shared-token auth)
Route::get('wht/agents', [\App\Http\Controllers\Api\WhtPsidApiController::class, 'agents']);
Route::get('wht/psid', [\App\Http\Controllers\Api\WhtPsidApiController::class, 'psid']);
Route::get('wht/psid/file', [\App\Http\Controllers\Api\WhtPsidApiController::class, 'file']);
Route::get('wht/status', [\App\Http\Controllers\Api\WhtPsidApiController::class, 'status']);

// MCP connector for the claude.ai app. The secret is part of the path; an unset
// secret makes the endpoint 404 rather than merely unauthenticated.
Route::post('mcp/{secret}', [\App\Http\Controllers\Mcp\WhtMcpController::class, 'handle'])
    ->middleware('throttle:120,1')
    ->where('secret', '[A-Za-z0-9_-]{32,128}');
