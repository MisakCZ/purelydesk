<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ticket_notification_batches', function (Blueprint $table): void {
            $table->timestamp('action_grace_until')->nullable()->after('send_after');
        });
    }

    public function down(): void
    {
        Schema::table('ticket_notification_batches', function (Blueprint $table): void {
            $table->dropColumn('action_grace_until');
        });
    }
};
