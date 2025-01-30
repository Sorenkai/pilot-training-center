<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Area extends Model
{
    use HasFactory;

    public $timestamps = false;

    public function trainings()
    {
        return $this->hasMany(Training::class);
    }

    public function endorsements()
    {
        return $this->belongsToMany(Endorsement::class);
    }

    public function permissions()
    {
        return $this->belongsToMany(Group::class, 'permissions')->withPivot('area_id')->withTimestamps();
    }

    public function mentors()
    {
        return $this->belongsToMany(User::class, 'permissions')->withPivot('group_id')->withTimestamps()->where('group_id', 3);
    }

    public function positions()
    {
        return $this->hasMany(Position::class);
    }
}
