<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('incoming_emails')) {
            return;
        }

        Schema::table('incoming_emails', function (Blueprint $table): void {
            if (! Schema::hasColumn('incoming_emails', 'raw_hash')) {
                $table->string('raw_hash')->nullable()->unique()->after('message_id');
            }

            if (! Schema::hasColumn('incoming_emails', 'status')) {
                $table->string('status')->default('pending')->after('sender_email');
            }

            if (! Schema::hasColumn('incoming_emails', 'failure_reason')) {
                $table->text('failure_reason')->nullable()->after('status');
            }

            if (! Schema::hasColumn('incoming_emails', 'processed_at')) {
                $table->timestamp('processed_at')->nullable()->after('failure_reason');
            }

            if (! Schema::hasColumn('incoming_emails', 'failed_at')) {
                $table->timestamp('failed_at')->nullable()->after('processed_at');
            }

            if (! Schema::hasColumn('incoming_emails', 'attachment_notice_sent_at')) {
                $table->timestamp('attachment_notice_sent_at')->nullable()->after('failed_at');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('incoming_emails')) {
            return;
        }

        Schema::table('incoming_emails', function (Blueprint $table): void {
            foreach (['attachment_notice_sent_at', 'failed_at', 'processed_at', 'failure_reason', 'status', 'raw_hash'] as $column) {
                if (Schema::hasColumn('incoming_emails', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
