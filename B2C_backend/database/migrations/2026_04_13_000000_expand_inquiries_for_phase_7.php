<?php

use App\Enums\B2BLeadType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('inquiries', function (Blueprint $table) {
            $table->string('reference', 40)->nullable()->after('id')->unique();
            $table->string('lead_type', 80)->default(B2BLeadType::BusinessContact->value)->after('source_page')->index();
            $table->string('organization_type', 80)->nullable()->after('company_name');
            $table->string('region', 120)->nullable()->after('country');
            $table->string('company_website', 2048)->nullable()->after('region');
            $table->string('job_title', 120)->nullable()->after('company_website');
            $table->text('internal_notes')->nullable()->after('status');
            $table->foreignId('reviewed_by')->nullable()->after('internal_notes')->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable()->after('reviewed_by');
            $table->json('metadata')->nullable()->after('reviewed_at');
            $table->index(['lead_type', 'status', 'created_at'], 'inquiries_lead_type_status_created_at_index');
        });

        Schema::create('partnership_inquiries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lead_id')->unique()->constrained('inquiries')->cascadeOnDelete();
            $table->string('collaboration_type', 80)->index();
            $table->text('collaboration_goal');
            $table->string('project_stage', 120)->nullable();
            $table->string('timeline', 120)->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });

        Schema::create('sample_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lead_id')->unique()->constrained('inquiries')->cascadeOnDelete();
            $table->string('material_interest', 150);
            $table->string('quantity_estimate', 120)->nullable();
            $table->string('shipping_country', 120)->nullable();
            $table->string('shipping_region', 120)->nullable();
            $table->string('shipping_address', 500)->nullable();
            $table->text('intended_use');
            $table->json('metadata')->nullable();
            $table->timestamps();
        });

        $this->backfillLegacyInquiries();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sample_requests');
        Schema::dropIfExists('partnership_inquiries');

        Schema::table('inquiries', function (Blueprint $table) {
            $table->dropIndex('inquiries_lead_type_status_created_at_index');
            $table->dropConstrainedForeignId('reviewed_by');
            $table->dropUnique(['reference']);
            $table->dropColumn([
                'reference',
                'lead_type',
                'organization_type',
                'region',
                'company_website',
                'job_title',
                'internal_notes',
                'reviewed_at',
                'metadata',
            ]);
        });
    }

    private function backfillLegacyInquiries(): void
    {
        DB::table('inquiries')
            ->select(['id', 'inquiry_type'])
            ->orderBy('id')
            ->chunkById(200, function (Collection $rows): void {
                foreach ($rows as $row) {
                    DB::table('inquiries')
                        ->where('id', $row->id)
                        ->update([
                            'reference' => sprintf('INQ-%06d', $row->id),
                            'lead_type' => $this->inferLeadType((string) $row->inquiry_type),
                        ]);
                }
            });
    }

    private function inferLeadType(string $inquiryType): string
    {
        $normalized = strtolower(trim($inquiryType));

        return match (true) {
            str_contains($normalized, 'sample') => B2BLeadType::SampleRequest->value,
            str_contains($normalized, 'university') => B2BLeadType::UniversityCollaboration->value,
            str_contains($normalized, 'product') => B2BLeadType::ProductDevelopmentCollaboration->value,
            str_contains($normalized, 'partnership') => B2BLeadType::PartnershipInquiry->value,
            default => B2BLeadType::BusinessContact->value,
        };
    }
};
