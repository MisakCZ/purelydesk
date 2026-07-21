<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ticket_notification_batches', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('ticket_id')->constrained()->cascadeOnDelete();
            $table->foreignId('recipient_id')->constrained('users')->cascadeOnDelete();
            $table->timestamp('first_event_at');
            $table->timestamp('last_event_at');
            $table->timestamp('send_after')->index();
            $table->string('status', 20)->index();
            // MariaDB/MySQL allow multiple NULL values while keeping one active row unique.
            $table->boolean('active_marker')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->text('last_error')->nullable();
            $table->timestamps();

            $table->unique(['ticket_id', 'recipient_id', 'active_marker'], 'ticket_notification_batches_active_unique');
            $table->index(['status', 'send_after'], 'ticket_notification_batches_ready_index');
        });

        Schema::create('ticket_notification_batch_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('batch_id')->constrained('ticket_notification_batches')->cascadeOnDelete();
            $table->string('event')->index();
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('ticket_activity_id')->nullable()->constrained('ticket_activities')->nullOnDelete();
            $table->json('context')->nullable();
            $table->timestamp('created_at')->index();

            $table->index(['batch_id', 'created_at', 'id'], 'ticket_notification_batch_items_order_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ticket_notification_batch_items');
        Schema::dropIfExists('ticket_notification_batches');
    }
};
