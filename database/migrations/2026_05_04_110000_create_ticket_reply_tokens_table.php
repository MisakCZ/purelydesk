<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ticket_reply_tokens', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('ticket_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('token', 96)->unique();
            $table->timestamp('last_used_at')->nullable();
            $table->timestamps();

            $table->unique(['ticket_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ticket_reply_tokens');
    }
};
