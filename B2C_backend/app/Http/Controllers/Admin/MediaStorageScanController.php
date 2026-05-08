<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MediaFile;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class MediaStorageScanController extends Controller
{
    public function export(): StreamedResponse
    {
        abort_unless(auth()->user()?->isAdmin(), 403);

        $payload = [
            'generated_at' => now()->toISOString(),
            'by_disk' => MediaFile::query()
                ->selectRaw('COALESCE(disk, ?) as disk, COUNT(*) as count, COALESCE(SUM(size), 0) as bytes', [(string) config('community.uploads.disk')])
                ->groupBy('disk')
                ->orderBy('disk')
                ->get()
                ->map(fn ($row): array => [
                    'disk' => (string) $row->disk,
                    'count' => (int) $row->count,
                    'bytes' => (int) $row->bytes,
                ]),
            'missing_files' => $this->missingFiles(),
            'migration' => [
                'local_to_azure' => 'dry-run only in this delivery build',
                'azure_to_local' => 'dry-run only in this delivery build',
            ],
        ];

        return response()->streamDownload(
            fn () => print json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
            'terraf-oxp-media-scan-'.now()->format('Ymd-His').'.json',
            ['Content-Type' => 'application/json']
        );
    }

    /**
     * @return array<int, array{disk: string, path: string}>
     */
    private function missingFiles(): array
    {
        return MediaFile::query()
            ->orderBy('id')
            ->limit(200)
            ->get()
            ->filter(function (MediaFile $mediaFile): bool {
                try {
                    return ! Storage::disk($mediaFile->disk ?: (string) config('community.uploads.disk'))->exists($mediaFile->path);
                } catch (Throwable) {
                    return true;
                }
            })
            ->map(fn (MediaFile $mediaFile): array => [
                'disk' => $mediaFile->disk ?: (string) config('community.uploads.disk'),
                'path' => $mediaFile->path,
            ])
            ->values()
            ->all();
    }
}
