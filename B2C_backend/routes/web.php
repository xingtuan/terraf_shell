<?php

use App\Http\Controllers\MediaController;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Route;

Route::get('/', function (): JsonResponse {
    return response()->json([
        'success' => true,
        'message' => 'Product Community API is running.',
        'data' => [
            'name' => config('app.name'),
            'type' => 'api-only',
            'api_prefix' => '/api',
            'health_check' => '/up',
        ],
    ]);
});

Route::get('/media/files/{disk}/{path}', [MediaController::class, 'show'])
    ->where('path', '.*')
    ->name('media.files.show');
