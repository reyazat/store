<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('organizations', function (Blueprint $table) {
            $table->text('description')->nullable()->after('name');
            $table->string('logo')->nullable()->after('description');
            $table->enum('status', ['active', 'inactive', 'suspended'])->default('active')->after('logo');
        });
    }

    public function down(): void
    {
        Schema::table('organizations', function (Blueprint $table) {
            $table->dropColumn(['description', 'logo', 'status']);
        });
    }
};
