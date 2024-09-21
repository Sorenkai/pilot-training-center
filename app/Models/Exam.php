<?php

namespace App\Models;

use App\Models\User;
use App\Models\PilotRating;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Exam extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function pilotRating()
    {
        return $this->belongsTo(PilotRating::class);
    }
}