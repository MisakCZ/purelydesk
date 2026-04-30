<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tickets', function (Blueprint $table): void {
            if (! Schema::hasColumn('tickets', 'archived_at')) {
                $table->timestamp('archived_at')->nullable()->after('closed_at')->index();
            }

            if (! Schema::hasColumn('tickets', 'archived_by_user_id')) {
                $table->foreignId('archived_by_user_id')
                    ->nullable()
                    ->after('archived_at')
                    ->constrained('users')
                    ->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('tickets', function (Blueprint $table): void {
            if (Schema::hasColumn('tickets', 'archived_by_user_id')) {
                $table->dropConstrainedForeignId('archived_by_user_id');
            }

            if (Schema::hasColumn('tickets', 'archived_at')) {
                $table->dropIndex(['archived_at']);
                $table->dropColumn('archived_at');
            }
        });
    }
};
