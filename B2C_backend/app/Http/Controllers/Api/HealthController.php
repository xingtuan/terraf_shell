<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\EmailSetting;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Throwable;

class HealthController extends Controller
{
    public function index(): JsonResponse
    {
        $checks = [
            'database' => $this->databaseStatus(),
            'storage' => $this->storageStatus(),
            'mail' => $this->mailStatus(),
        ];

        return $this->successResponse([
            'status' => in_array('error', $checks, true) ? 'error' : (in_array('warning', $checks, true) ? 'warning' : 'ok'),
            'checks' => $checks,
        ]);
    }

    public function database(): JsonResponse
    {
        return $this->successResponse(['status' => $this->databaseStatus()]);
    }

    public function storage(): JsonResponse
    {
        return $this->successResponse(['status' => $this->storageStatus()]);
    }

    public function mail(): JsonResponse
    {
        return $this->successResponse(['status' => $this->mailStatus()]);
    }

    private function databaseStatus(): string
    {
        try {
            DB::select('select 1');

            return 'ok';
        } catch (Throwable) {
            return 'error';
        }
    }

    private function storageStatus(): string
    {
        try {
            $disk = (string) config('community.uploads.disk', config('filesystems.default'));
            Storage::disk($disk);

            return 'ok';
        } catch (Throwable) {
            return 'error';
        }
    }

    private function mailStatus(): string
    {
        try {
            if (! Schema::hasTable('email_settings')) {
                return config('mail.default') === 'log' ? 'warning' : 'ok';
            }

            $settings = EmailSetting::query()->oldest('id')->first();

            return $settings?->is_enabled ? 'ok' : 'warning';
        } catch (Throwable) {
            return 'warning';
        }
    }
}
