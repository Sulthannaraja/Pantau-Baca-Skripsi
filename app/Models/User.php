<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'api_token',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'api_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function readingLogs(): HasMany
    {
        return $this->hasMany(ReadingLog::class);
    }

    public function classrooms()
    {
        return $this->belongsToMany(Classroom::class);
    }

    public function surveys(): HasMany
    {
        return $this->hasMany(LiteracySurvey::class);
    }

    public function isSiswa(): bool
    {
        return $this->role === 'SISWA';
    }

    public function isGuru(): bool
    {
        return $this->role === 'GURU';
    }

    public function isAdmin(): bool
    {
        return $this->role === 'ADMIN';
    }
}
