<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ticket_attachments', function (Blueprint $table): void {
            if (! Schema::hasColumn('ticket_attachments', 'uploader_id')) {
                $table->foreignId('uploader_id')
                    ->nullable()
                    ->after('user_id')
                    ->constrained('users')
                    ->nullOnDelete();
            }
        });

        if (Schema::hasColumn('ticket_attachments', 'user_id') && Schema::hasColumn('ticket_attachments', 'uploader_id')) {
            DB::table('ticket_attachments')
                ->whereNull('uploader_id')
                ->whereNotNull('user_id')
                ->update(['uploader_id' => DB::raw('user_id')]);
        }
    }

    public function down(): void
    {
        Schema::table('ticket_attachments', function (Blueprint $table): void {
            if (Schema::hasColumn('ticket_attachments', 'uploader_id')) {
                $table->dropConstrainedForeignId('uploader_id');
            }
        });
    }
};
