<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void {
        Schema::create('organizations', static function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->timestamps();
        });

        Schema::create('organization_user', static function (Blueprint $t) {
            $t->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $t->foreignId('user_id')->constrained()->cascadeOnDelete();
            $t->string('role')->nullable(); // owner|admin|member (ارزشی افزوده در کنار spatie/permission)
            $t->timestamps();
            $t->unique(['organization_id','user_id']);
        });

        Schema::create('organization_domains', static function (Blueprint $t) {
            $t->id();
            $t->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $t->string('domain')->unique();
            $t->timestamp('verified_at')->nullable();
            $t->timestamps();
        });

        Schema::create('organization_invitations', static function (Blueprint $t) {
            $t->id();
            $t->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $t->string('email');
            $t->string('role')->nullable();
            $t->string('token')->unique();
            $t->foreignId('invited_by')->constrained('users')->cascadeOnDelete();
            $t->string('status')->default('pending'); // pending|accepted|rejected|cancelled|expired
            $t->timestamp('expires_at')->nullable();
            $t->timestamp('accepted_at')->nullable();
            $t->timestamp('rejected_at')->nullable();
            $t->timestamp('cancelled_at')->nullable();
            $t->foreignId('accepted_by')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamps();
            
            $t->index(['email', 'organization_id']);
            $t->index(['status', 'expires_at']);
        });
    }

    public function down(): void {
        Schema::dropIfExists('organization_invitations');
        Schema::dropIfExists('organization_domains');
        Schema::dropIfExists('organization_user');
        Schema::dropIfExists('organizations');
    }
};
