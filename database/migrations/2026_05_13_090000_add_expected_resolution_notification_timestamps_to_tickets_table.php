<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tickets', function (Blueprint $table): void {
            $table->timestamp('expected_resolution_due_soon_notified_at')->nullable()->after('expected_resolution_source');
            $table->timestamp('expected_resolution_overdue_notified_at')->nullable()->after('expected_resolution_due_soon_notified_at');
        });
    }

    public function down(): void
    {
        Schema::table('tickets', function (Blueprint $table): void {
            $table->dropColumn([
                'expected_resolution_due_soon_notified_at',
                'expected_resolution_overdue_notified_at',
            ]);
        });
    }
};
