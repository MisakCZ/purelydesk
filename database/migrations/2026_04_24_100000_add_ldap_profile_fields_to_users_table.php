<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            if (! Schema::hasColumn('users', 'username')) {
                $table->string('username')->nullable()->unique()->after('id');
            }

            if (! Schema::hasColumn('users', 'ldap_dn')) {
                $table->text('ldap_dn')->nullable()->after('email');
            }

            if (! Schema::hasColumn('users', 'external_id')) {
                $table->string('external_id')->nullable()->unique()->after('ldap_dn');
            }

            if (! Schema::hasColumn('users', 'display_name')) {
                $table->string('display_name')->nullable()->after('name');
            }

            if (! Schema::hasColumn('users', 'department')) {
                $table->string('department')->nullable()->after('display_name');
            }

            if (! Schema::hasColumn('users', 'auth_source')) {
                $table->string('auth_source', 32)->nullable()->after('preferred_locale');
            }

            if (! Schema::hasColumn('users', 'is_active')) {
                $table->boolean('is_active')->default(true)->after('auth_source');
            }

            if (! Schema::hasColumn('users', 'last_login_at')) {
                $table->timestamp('last_login_at')->nullable()->after('is_active');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            foreach ([
                'last_login_at',
                'is_active',
                'auth_source',
                'department',
                'display_name',
                'external_id',
                'ldap_dn',
                'username',
            ] as $column) {
                if (Schema::hasColumn('users', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
