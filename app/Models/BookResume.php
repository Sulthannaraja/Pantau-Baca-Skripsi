<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BookResume extends Model
{
    use HasFactory;

    protected $fillable = [
        'book_id',
        'user_id',
        'category',
        'tujuan_penyajian',
        'kedalaman_materi',
        'bahasa',
        'penyajian_materi',
        'contoh',
        'content',
        'score',
        'teacher_feedback',
        'graded_by',
        'graded_at',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'score' => 'integer',
            'graded_at' => 'datetime',
        ];
    }

    public function book(): BelongsTo
    {
        return $this->belongsTo(Book::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function grader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'graded_by');
    }
}
