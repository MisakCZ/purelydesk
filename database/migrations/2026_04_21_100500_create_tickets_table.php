<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('tickets', function (Blueprint $table) {
            $table->id();
            $table->string('ticket_number')->nullable()->unique();
            $table->string('subject');
            $table->longText('description')->nullable();
            $table->string('visibility', 20)->default('public');
            $table->foreignId('department_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('requester_id')->constrained('users');
            $table->foreignId('assignee_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('ticket_status_id')->nullable()->constrained('ticket_statuses')->nullOnDelete();
            $table->foreignId('ticket_priority_id')->nullable()->constrained('ticket_priorities')->nullOnDelete();
            $table->foreignId('ticket_category_id')->nullable()->constrained('ticket_categories')->nullOnDelete();
            $table->timestamp('due_at')->nullable();
            $table->timestamp('last_activity_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->timestamps();

            $table->index(['visibility', 'ticket_status_id']);
            $table->index(['department_id', 'ticket_priority_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tickets');
    }
};
