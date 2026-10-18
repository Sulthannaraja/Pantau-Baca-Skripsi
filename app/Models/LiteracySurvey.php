<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LiteracySurvey extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'responses',
        'total_score',
        'level',
    ];

    protected $casts = [
        'responses' => 'array',
        'total_score' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
