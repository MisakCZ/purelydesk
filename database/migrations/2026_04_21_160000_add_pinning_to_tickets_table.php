<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->boolean('is_pinned')->default(false)->after('closed_at');
            $table->timestamp('pinned_at')->nullable()->after('is_pinned');

            $table->index(['is_pinned', 'pinned_at']);
        });
    }

    public function down(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->dropIndex(['is_pinned', 'pinned_at']);
            $table->dropColumn(['is_pinned', 'pinned_at']);
        });
    }
};
