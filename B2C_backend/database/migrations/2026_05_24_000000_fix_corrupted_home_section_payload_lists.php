<?php

use App\Models\HomeSection;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Keys inside a section payload that are expected to be sequential lists.
     * A bug in applyPayloadState caused Filament's UUID-keyed Livewire state to be merged
     * on top of the existing integer-indexed items on every save, doubling the entry count.
     */
    private const LIST_KEYS = [
        'items',
        'metrics',
        'benefits',
        'steps',
        'columns',
        'rows',
        'downloads',
        'cards',
        'topic_options',
        'social_links',
        'legal_links',
        'legend',
    ];

    public function up(): void
    {
        if (! Schema::hasTable('home_sections')) {
            return;
        }

        foreach (HomeSection::all() as $section) {
            $payload = $section->payload;

            if (! is_array($payload)) {
                continue;
            }

            $changed = false;

            foreach (self::LIST_KEYS as $key) {
                if (! isset($payload[$key]) || ! is_array($payload[$key])) {
                    continue;
                }

                if (array_is_list($payload[$key])) {
                    continue; // Already a proper list – nothing to fix.
                }

                // The array has non-sequential keys (a mix of integer and UUID string keys).
                // Extract UUID-keyed entries (newest form data submitted by the admin user).
                // If none exist, fall back to all values re-indexed.
                $uuidValues = array_values(array_filter(
                    $payload[$key],
                    fn ($k) => is_string($k) && preg_match(
                        '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i',
                        (string) $k
                    ),
                    ARRAY_FILTER_USE_KEY
                ));

                $payload[$key] = $uuidValues ?: array_values($payload[$key]);
                $changed = true;
            }

            if ($changed) {
                $section->payload = $payload;
                $section->save();
            }
        }
    }

    public function down(): void
    {
        // Non-reversible data repair – no rollback possible.
    }
};
