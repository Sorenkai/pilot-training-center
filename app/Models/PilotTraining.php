<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PilotTraining extends Model
{
    protected $guarded = [];

    protected $table = 'pilot_trainings';

    protected $casts = [
        'started_at' => 'datetime',
        'closed_at' => 'datetime',
    ];

    public function path()
    {
        return route('pilot.training.show', ['training' => $this->id]);
    }

    public function updateStatus(int $newStatus, bool $expiredInterest = false)
    {
        $oldStatus = $this->fresh()->status;

        if ($newStatus != $oldStatus) {
            if ($newStatus == 0) {
                $this->update(['started_at' => null, 'closed_at' => null]);
            }

            if ($newStatus >= 1 || $newStatus == -1) {
                if ($oldStatus < 0) {
                    $this->update(['closed_at' => null]);
                }

                if (! isset($this->started_at)) {
                    $this->update(['started_at' => now()]);
                }
            }

            if ($newStatus < 0) {
                $this->update(['closed_at' => now()]);

                if (isset($this->paused_at)) {
                    $this->paused_length = $this->paused_length + Carbon::create($this->paused_at)->diffInSeconds(Carbon::now());
                    $this->update(['paused_at' => null, 'paused_length' => $this->paused_length]);
                }
            }
            $this->update(['status' => $newStatus]);
        }
    }

    public function getInlineInstructors()
    {
        return $this->instructors->pluck('name')->implode(' & ');
    }
    
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function pilotRatings()
    {
        return $this->belongsToMany(PilotRating::class, 'pilot_rating_pilot_training', 'pilot_training_id', 'pilot_rating_id');
    }

    public function instructors()
    {
        return $this->belongsToMany(User::class, 'pilot_training_instructor')->withPivot('expire_at');
    }
    
    public function activities()
    {
        return $this->hasMany(PilotTrainingActivity::class);
    }
}
