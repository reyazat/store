<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', static function (Blueprint $t) {
            if (!Schema::hasColumn('users', 'phone')) $t->string('phone')->nullable()->unique()->after('email');
            if (!Schema::hasColumn('users', 'email_verified_at')) $t->timestamp('email_verified_at')->nullable()->after('email');
            if (!Schema::hasColumn('users', 'phone_verified_at')) $t->timestamp('phone_verified_at')->nullable()->after('phone');
            if (!Schema::hasColumn('users', 'must_change_password')) $t->boolean('must_change_password')->default(false)->after('password');
            if (!Schema::hasColumn('users', 'password_changed_at')) $t->timestamp('password_changed_at')->nullable()->after('must_change_password');
            if (!Schema::hasColumn('users', 'password_expires_at')) $t->timestamp('password_expires_at')->nullable()->after('password_changed_at');
            if (!Schema::hasColumn('users', 'status')) $t->string('status')->default('active')->after('password_expires_at'); // active|disabled
            if (!Schema::hasColumn('users', 'locked_until')) $t->timestamp('locked_until')->nullable()->after('status');
            if (!Schema::hasColumn('users', 'failed_login_attempts')) $t->unsignedSmallInteger('failed_login_attempts')->default(0)->after('locked_until');
            if (!Schema::hasColumn('users', 'last_login_at')) $t->timestamp('last_login_at')->nullable()->after('failed_login_attempts');
            if (!Schema::hasColumn('users', 'last_login_ip')) $t->string('last_login_ip')->nullable()->after('last_login_at');
            if (!Schema::hasColumn('users', 'mfa_enabled')) $t->boolean('mfa_enabled')->default(false)->after('last_login_ip');

            $t->index(['status', 'locked_until']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', static function (Blueprint $t) {
            $t->dropIndex(['status', 'locked_until']);
            $t->dropColumn([
                'phone','email_verified_at','phone_verified_at',
                'must_change_password','password_changed_at','password_expires_at',
                'status','locked_until','failed_login_attempts','last_login_at','last_login_ip',
                'mfa_enabled'
            ]);
        });
    }
};
