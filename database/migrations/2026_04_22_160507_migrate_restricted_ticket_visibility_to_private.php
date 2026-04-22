<?php

use App\Models\Ticket;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::table('tickets')
            ->where('visibility', Ticket::LEGACY_VISIBILITY_RESTRICTED)
            ->update([
                'visibility' => Ticket::VISIBILITY_PRIVATE,
            ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('tickets')
            ->where('visibility', Ticket::VISIBILITY_PRIVATE)
            ->update([
                'visibility' => Ticket::LEGACY_VISIBILITY_RESTRICTED,
            ]);
    }
};
