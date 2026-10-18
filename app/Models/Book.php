<?php

namespace App\Models;

use Database\Factories\BookFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Book extends Model
{
    /** @use HasFactory<BookFactory> */
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'title',
        'author',
        'year',
        'file_path',
        'cover',
        'category',
        'literacy_level',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'year' => 'integer',
        ];
    }

    /**
     * Normalize literacy level string to RENDAH or TINGGI.
     */
    public static function normalizeLevel(?string $level): ?string
    {
        if (! $level) {
            return null;
        }

        $upper = strtoupper(trim($level));
        if (in_array($upper, ['RENDAH', 'DASAR', 'LOW', 'BASIC'])) {
            return 'RENDAH';
        }
        if (in_array($upper, ['TINGGI', 'LANJUTAN', 'HIGH', 'ADVANCED'])) {
            return 'TINGGI';
        }

        return $upper;
    }

    /**
     * Scope query to books matching a given literacy level.
     */
    public function scopeForLiteracyLevel($query, string $level)
    {
        $normalized = self::normalizeLevel($level);
        if ($normalized === 'RENDAH') {
            return $query->whereIn('literacy_level', ['RENDAH', 'DASAR']);
        }
        if ($normalized === 'TINGGI') {
            return $query->whereIn('literacy_level', ['TINGGI', 'LANJUTAN']);
        }

        return $query->where('literacy_level', $level);
    }

    public function readingLogs(): HasMany
    {
        return $this->hasMany(ReadingLog::class);
    }

    public function quizzes(): HasMany
    {
        return $this->hasMany(Quiz::class);
    }

    public function resumes(): HasMany
    {
        return $this->hasMany(BookResume::class);
    }
}
