<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('ticket_watchers', function (Blueprint $table) {
            $table->boolean('is_manual')->default(true)->after('user_id');
            $table->boolean('is_auto_participant')->default(false)->after('is_manual');
        });

        DB::table('tickets')
            ->select(['id', 'requester_id', 'assignee_id'])
            ->orderBy('id')
            ->chunkById(100, function ($tickets): void {
                $now = now();

                foreach ($tickets as $ticket) {
                    $participantIds = collect([$ticket->requester_id, $ticket->assignee_id])
                        ->filter()
                        ->map(fn ($userId) => (int) $userId)
                        ->unique();

                    foreach ($participantIds as $userId) {
                        $updated = DB::table('ticket_watchers')
                            ->where('ticket_id', $ticket->id)
                            ->where('user_id', $userId)
                            ->update([
                                'is_auto_participant' => true,
                                'updated_at' => $now,
                            ]);

                        if ($updated > 0) {
                            continue;
                        }

                        DB::table('ticket_watchers')->insert([
                            'ticket_id' => $ticket->id,
                            'user_id' => $userId,
                            'is_manual' => false,
                            'is_auto_participant' => true,
                            'created_at' => $now,
                            'updated_at' => $now,
                        ]);
                    }
                }
            }, 'id');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ticket_watchers', function (Blueprint $table) {
            $table->dropColumn(['is_manual', 'is_auto_participant']);
        });
    }
};
