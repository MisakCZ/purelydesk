<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tickets', function (Blueprint $table): void {
            if (! Schema::hasColumn('tickets', 'resolved_at')) {
                $table->timestamp('resolved_at')->nullable()->after('last_activity_at');
            }

            if (! Schema::hasColumn('tickets', 'auto_close_at')) {
                $table->timestamp('auto_close_at')->nullable()->after('resolved_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('tickets', function (Blueprint $table): void {
            $columns = [];

            if (Schema::hasColumn('tickets', 'resolved_at')) {
                $columns[] = 'resolved_at';
            }

            if (Schema::hasColumn('tickets', 'auto_close_at')) {
                $columns[] = 'auto_close_at';
            }

            if ($columns !== []) {
                $table->dropColumn($columns);
            }
        });
    }
};
