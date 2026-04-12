<?php

use App\Enums\AccountStatus;
use App\Enums\UserRole;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('account_status')->default(AccountStatus::Active->value)->after('role')->index();
            $table->timestamp('restricted_at')->nullable()->after('banned_at');
            $table->text('restriction_reason')->nullable()->after('ban_reason');
        });

        Schema::table('profiles', function (Blueprint $table) {
            $table->string('school_or_company')->nullable()->after('bio');
            $table->string('region')->nullable()->after('location');
            $table->string('portfolio_url')->nullable()->after('website');
            $table->boolean('open_to_collab')->default(false)->after('portfolio_url');
        });

        DB::table('users')
            ->where('role', 'user')
            ->update(['role' => UserRole::Creator->value]);

        DB::table('users')
            ->where('is_banned', true)
            ->update(['account_status' => AccountStatus::Banned->value]);

        foreach (DB::table('profiles')->select('id', 'location', 'website')->get() as $profile) {
            DB::table('profiles')
                ->where('id', $profile->id)
                ->update([
                    'region' => $profile->location,
                    'portfolio_url' => $profile->website,
                ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('profiles', function (Blueprint $table) {
            $table->dropColumn([
                'school_or_company',
                'region',
                'portfolio_url',
                'open_to_collab',
            ]);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'account_status',
                'restricted_at',
                'restriction_reason',
            ]);
        });
    }
};
