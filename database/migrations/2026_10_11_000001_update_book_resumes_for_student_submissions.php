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
        Schema::table('book_resumes', function (Blueprint $table) {
            $table->index('book_id');
            $table->dropUnique(['book_id', 'category']);
            $table->unique(['book_id', 'user_id', 'category']);
            $table->unsignedTinyInteger('score')->nullable()->after('content');
            $table->text('teacher_feedback')->nullable()->after('score');
            $table->foreignId('graded_by')->nullable()->after('teacher_feedback')->constrained('users')->nullOnDelete();
            $table->timestamp('graded_at')->nullable()->after('graded_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('book_resumes', function (Blueprint $table) {
            $table->dropForeign(['graded_by']);
            $table->dropColumn(['score', 'teacher_feedback', 'graded_by', 'graded_at']);
            $table->dropUnique(['book_id', 'user_id', 'category']);
            $table->unique(['book_id', 'category']);
            $table->dropIndex(['book_id']);
        });
    }
};
