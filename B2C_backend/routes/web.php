<?php

use App\Http\Controllers\Admin\MediaStorageScanController;
use App\Http\Controllers\Admin\SettingsBackupController;
use App\Http\Controllers\Install\InstallController;
use App\Http\Controllers\MediaController;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

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

Route::middleware('throttle:install')->group(function (): void {
    Route::get('/install', [InstallController::class, 'index'])->name('install.index');
    Route::post('/install', [InstallController::class, 'store'])->name('install.store');
});

Route::get('/media/files/{disk}/{path}', [MediaController::class, 'show'])
    ->where('path', '.*')
    ->name('media.files.show');

Route::get('/admin/locale/{locale}', function (string $locale) {
    abort_unless(in_array($locale, ['en', 'ko', 'zh'], true), 404);

    session(['admin_locale' => $locale]);

    $previous = url()->previous();

    return redirect(Str::contains($previous, '/admin') ? $previous : '/admin');
})->name('admin.locale.switch');

// Temporary debug route — remove after confirming footer save works
Route::get('/api/debug-footer-payload', function () {
    $record = \App\Models\HomeSection::find(5);
    if (! $record) {
        return response()->json(['error' => 'record not found']);
    }
    return response()->json([
        'id'      => $record->id,
        'key'     => $record->key,
        'payload' => $record->payload,
        'updated' => $record->updated_at,
    ]);
});

Route::middleware('auth')->group(function (): void {
    Route::get('/admin/settings/export', [SettingsBackupController::class, 'export'])
        ->name('admin.settings.export');
    Route::get('/admin/settings/handover-summary', [SettingsBackupController::class, 'handoverSummary'])
        ->name('admin.settings.handover-summary');
    Route::get('/admin/media-scan/export', [MediaStorageScanController::class, 'export'])
        ->name('admin.media-scan.export');
});
