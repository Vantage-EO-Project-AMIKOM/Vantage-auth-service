<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('users', 'role')) {
            return;
        }

        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE users MODIFY role ENUM('admin','user','customer','organizer') NOT NULL DEFAULT 'user'");
        }

        DB::table('users')
            ->whereIn('role', ['customer', 'organizer'])
            ->update(['role' => 'user']);

        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE users MODIFY role ENUM('admin','user') NOT NULL DEFAULT 'user'");
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('users', 'role') && DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE users MODIFY role ENUM('admin','customer','organizer') NOT NULL DEFAULT 'customer'");
        }
    }
};
