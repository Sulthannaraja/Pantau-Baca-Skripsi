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
        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'sqlite') {
            if (! Schema::hasColumn('books', 'literacy_level')) {
                Schema::table('books', function (Blueprint $table) {
                    $table->string('literacy_level', 20)->default('RENDAH');
                });
            }

            return;
        }

        // MySQL / MariaDB
        DB::statement("ALTER TABLE books MODIFY COLUMN literacy_level ENUM('RENDAH', 'TINGGI', 'DASAR', 'LANJUTAN') NOT NULL DEFAULT 'RENDAH'");
        DB::statement("UPDATE books SET literacy_level = 'RENDAH' WHERE literacy_level = 'DASAR'");
        DB::statement("UPDATE books SET literacy_level = 'TINGGI' WHERE literacy_level = 'LANJUTAN'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'sqlite') {
            return;
        }

        DB::statement("UPDATE books SET literacy_level = 'DASAR' WHERE literacy_level = 'RENDAH'");
        DB::statement("UPDATE books SET literacy_level = 'LANJUTAN' WHERE literacy_level = 'TINGGI'");
        DB::statement("ALTER TABLE books MODIFY COLUMN literacy_level ENUM('DASAR', 'LANJUTAN') NOT NULL DEFAULT 'DASAR'");
    }
};
