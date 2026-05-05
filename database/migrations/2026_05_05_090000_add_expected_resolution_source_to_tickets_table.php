<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tickets', function (Blueprint $table): void {
            if (! Schema::hasColumn('tickets', 'expected_resolution_source')) {
                $table->string('expected_resolution_source', 20)->nullable()->after('expected_resolution_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('tickets', function (Blueprint $table): void {
            if (Schema::hasColumn('tickets', 'expected_resolution_source')) {
                $table->dropColumn('expected_resolution_source');
            }
        });
    }
};
