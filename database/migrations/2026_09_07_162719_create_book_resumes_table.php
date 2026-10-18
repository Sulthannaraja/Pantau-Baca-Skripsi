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
        Schema::create('book_resumes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('book_id')->constrained('books')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->enum('category', ['DASAR', 'LANJUTAN'])->default('DASAR');
            $table->string('tujuan_penyajian')->nullable();
            $table->string('kedalaman_materi')->nullable();
            $table->string('bahasa')->nullable();
            $table->string('penyajian_materi')->nullable();
            $table->string('contoh')->nullable();
            $table->longText('content');
            $table->timestamps();

            $table->unique(['book_id', 'category']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('book_resumes');
    }
};
