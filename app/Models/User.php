<?php

namespace App\Models;

use anlutro\LaravelSettings\Facade as Setting;
use App\Exceptions\PolicyMethodMissingException;
use App\Exceptions\PolicyMissingException;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $table = 'users';

    public $timestamps = false;

    protected $casts = [
        'last_login' => 'datetime',
        'last_activity' => 'datetime',
        'last_inactivity_warning' => 'datetime',
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'id', 'email', 'first_name', 'last_name', 'rating', 'rating_short', 'rating_long', 'pilotrating', 'pilotrating_short', 'pilotrating_long', 'region', 'division', 'subdivision', 'atc_active', 'last_login', 'access_token', 'refresh_token', 'token_expires',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'remember_token',
    ];

    /**
     * Relationship of all permissions to this user
     *
     * @return Illuminate\Database\Eloquent\Collection|Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function groups()
    {
        return $this->belongsToMany(Group::class, 'permissions')->withPivot('area_id')->withTimestamps();
    }

    /**
     * Find all users with queried group
     *
     * @param  int  $groupId  the id of the group to check for
     * @return Illuminate\Database\Eloquent\Collection
     */
    public static function allWithGroup($groupId, $IneqSymbol = '=')
    {
        return User::whereHas('groups', function ($query) use ($groupId, $IneqSymbol) {
            $query->where('id', $IneqSymbol, $groupId);
        })->get();
    }

    public function endorsements()
    {
        return $this->hasMany(Endorsement::class);
    }

    public function instructorEndorsements()
    {
        return $this->hasMany(InstructorEndorsement::class);
    }

    public function pilotTrainingReports()
    {
        return $this->hasMany(PilotTrainingReport::class, 'written_by_id');
    }

    public function pilotTrainings()
    {
        return $this->hasMany(PilotTraining::class);
    }

    public function instructs()
    {
        return $this->belongsToMany(PilotTraining::class, 'pilot_training_instructor')->withPivot('expire_at');
    }

    public function exams()
    {
        return $this->hasMany(Exam::class);
    }


    public function isInstructing(User $user)
    {
        return $this->instructs->where('user_id', $user->id)->count() > 0;
    }

    public function ratings()
    {
        return $this->belongsToMany(Rating::class);
    }

    public function pilotRatings()
    {
        return $this->belongsToMany(PilotRating::class);
    }

    public function callsigns()
    {
        return $this->hasMany(Callsign::class);
    }

    public function tasks()
    {
        return $this->hasMany(Task::class, 'assignee_user_id');
    }

    public function getNameAttribute()
    {
        return $this->first_name . ' ' . $this->last_name;
    }

    public function getNotificationEmailAttribute()
    {
        if ($this->setting_workmail_address) {
            return $this->setting_workmail_address;
        }

        return $this->email;
    }

    /**
     * Get the models allowed for the user to be viewed.
     *
     * @return mixed
     *
     * @throws PolicyMethodMissingException
     * @throws PolicyMissingException
     */
    public function viewableModels($class, array $options = [], array $with = [])
    {
        if (policy($class) == null) {
            throw new PolicyMissingException();
        }

        if (! method_exists(policy($class), 'view')) {
            throw new PolicyMethodMissingException('The view method does not exist on the policy.');
        }

        $models = $class::where($options)->with($with)->get();

        foreach ($models as $key => $model) {
            if ($this->cannot('view', $model)) {
                $models->pull($key);
            }
        }

        return $models;
    }

    public function instructingTrainings()
    {
        $trainings = PilotTraining::where('status', '>=', 1)->whereHas('instructors', function ($query) {
            $query->where('user_id', $this->id);
        })->with('pilotRatings', 'reports', 'user')->orderBy('id')->get();

        return $trainings;
    }

    public function getInstructors()
    {
        $instructors = User::whereHas('permissions', function ($query) {
            $query->where('group_id', 4);
        })->get();

        return $instructors;
    }

    public function hasActivePilotTraining(bool $includeWaiting)
    {
        if ($includeWaiting) {
            return count($this->pilotTrainings()->whereIn('status', [0, 1, 2, 3])->get()) > 0;
        } else {
            return count($this->pilotTrainings()->whereIn('status', [1, 2, 3])->get()) > 0;
        }
    }

    /**
     * Return if user is a instructor
     *
     * @return bool
     */
    public function isInstructor()
    {
        return $this->groups->where('id', 4)->isNotEmpty();
    }

    public function isInstructorOrAbove()
    {
        return $this->isInstructor() || $this->isAdmin();
    }

    /**
     * Return if user is a moderator
     *
     * @return bool
     */
    public function isModerator(?Area $area = null)
    {
        if ($area == null) {
            return $this->groups->where('id', 2)->isNotEmpty();
        }

        // Check if user is moderator in the specified area
        foreach ($this->groups->where('id', 2) as $group) {
            if ($group->pivot->area_id == $area->id) {
                return true;
            }
        }

        return false;
    }

    /**
     * Return if user is a moderator or above
     *
     * @return bool
     */
    public function isModeratorOrAbove(?Area $area = null)
    {
        if ($area == null) {
            return $this->groups->where('id', '<=', 2)->isNotEmpty();
        }

        if ($this->isAdmin()) {
            return true;
        }

        // Check if user is moderator or above in the specified area
        foreach ($this->groups->where('id', '<=', 2) as $group) {
            if ($group->pivot->area_id == $area->id) {
                return true;
            }
        }

        return false;
    }

    /**
     * Return if user is an admin
     *
     * @return bool
     */
    public function isAdmin()
    {
        return $this->groups->contains('id', 1);
    }
}
