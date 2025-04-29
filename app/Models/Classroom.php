<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;


class Classroom extends Model
{
    use HasFactory;
    protected $fillable = [
        'name',
        'description',
    ];

    public function attendances()
    {
        return $this->hasMany(Attendance::class);
    }
}
