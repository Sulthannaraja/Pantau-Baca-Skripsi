<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Classroom extends Model
{
    protected $fillable = ['name', 'teacher_id', 'code'];

    public function teacher()
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    public function users()
    {
        return $this->belongsToMany(User::class);
    }

    public function assignments()
    {
        return $this->hasMany(Assignment::class);
    }
}
