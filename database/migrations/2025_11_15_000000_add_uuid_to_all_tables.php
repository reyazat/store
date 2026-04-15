<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Add UUID to users table
        Schema::table('users', static function (Blueprint $table) {
            $table->uuid('uuid')->after('id')->unique();
        });

        // Add UUID to organizations table
        Schema::table('organizations', static function (Blueprint $table) {
            $table->uuid('uuid')->after('id')->unique();
        });


        // Add UUID to jwt_tokens table
        Schema::table('jwt_tokens', static function (Blueprint $table) {
            $table->uuid('uuid')->after('id')->unique();
        });

        // Add UUID to otps table
        Schema::table('otps', static function (Blueprint $table) {
            $table->uuid('uuid')->after('id')->unique();
        });

        // Add UUID to user_totp_secrets table
        Schema::table('user_totp_secrets', static function (Blueprint $table) {
            $table->uuid('uuid')->after('id')->unique();
        });

        // Add UUID to user_recovery_codes table
        Schema::table('user_recovery_codes', static function (Blueprint $table) {
            $table->uuid('uuid')->after('id')->unique();
        });

        // Add UUID to password_histories table
        Schema::table('password_histories', static function (Blueprint $table) {
            $table->uuid('uuid')->after('id')->unique();
        });

        // Add UUID to verification_tokens table
        Schema::table('verification_tokens', static function (Blueprint $table) {
            $table->uuid('uuid')->after('id')->unique();
        });

        // Add UUID to organization_domains table
        Schema::table('organization_domains', static function (Blueprint $table) {
            $table->uuid('uuid')->after('id')->unique();
        });

        // Add UUID to organization_invitations table
        Schema::table('organization_invitations', static function (Blueprint $table) {
            $table->uuid('uuid')->after('id')->unique();
        });

        // Generate UUIDs for existing records
        $this->generateUuidsForExistingRecords();
    }

    /**
     * Generate UUIDs for existing records
     */
    private function generateUuidsForExistingRecords(): void
    {
        $tables = [
            'users',
            'organizations',
            'jwt_tokens',
            'otps',
            'user_totp_secrets',
            'user_recovery_codes',
            'password_histories',
            'verification_tokens',
            'organization_domains',
            'organization_invitations',
        ];

        foreach ($tables as $table) {
            if (Schema::hasTable($table)) {
                DB::table($table)->whereNull('uuid')->chunkById(100, static function ($records) use ($table) {
                    foreach ($records as $record) {
                        DB::table($table)
                            ->where('id', $record->id)
                            ->update(['uuid' => (string) Str::uuid()]);
                    }
                });
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', static function (Blueprint $table) {
            $table->dropColumn('uuid');
        });

        Schema::table('organizations', static function (Blueprint $table) {
            $table->dropColumn('uuid');
        });


        Schema::table('jwt_tokens', static function (Blueprint $table) {
            $table->dropColumn('uuid');
        });

        Schema::table('otps', static function (Blueprint $table) {
            $table->dropColumn('uuid');
        });

        Schema::table('user_totp_secrets', static function (Blueprint $table) {
            $table->dropColumn('uuid');
        });

        Schema::table('user_recovery_codes', static function (Blueprint $table) {
            $table->dropColumn('uuid');
        });

        Schema::table('password_histories', static function (Blueprint $table) {
            $table->dropColumn('uuid');
        });

        Schema::table('verification_tokens', static function (Blueprint $table) {
            $table->dropColumn('uuid');
        });

        Schema::table('organization_domains', static function (Blueprint $table) {
            $table->dropColumn('uuid');
        });

        Schema::table('organization_invitations', static function (Blueprint $table) {
            $table->dropColumn('uuid');
        });
    }
};
