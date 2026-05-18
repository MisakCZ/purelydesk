<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ticket_read_states', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('ticket_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('last_read_activity_id')->nullable()->constrained('ticket_activities')->nullOnDelete();
            $table->timestamp('last_read_at')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'ticket_id']);
            $table->index('ticket_id');
            $table->index('last_read_activity_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ticket_read_states');
    }
};
