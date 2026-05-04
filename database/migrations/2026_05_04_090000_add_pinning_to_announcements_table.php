<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('announcements', 'is_pinned')) {
            Schema::table('announcements', function (Blueprint $table): void {
                $table->boolean('is_pinned')
                    ->default(false)
                    ->after('is_active');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('announcements', 'is_pinned')) {
            Schema::table('announcements', function (Blueprint $table): void {
                $table->dropColumn('is_pinned');
            });
        }
    }
};
