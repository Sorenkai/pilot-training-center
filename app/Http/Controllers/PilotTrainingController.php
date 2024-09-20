<?php

namespace App\Http\Controllers;

use anlutro\LaravelSettings\Facade as Setting;
use App;
use App\Helpers\TrainingStatus;
use App\Models\Callsign;
use App\Models\PilotRating;
use App\Models\PilotTraining;
use App\Models\PilotTrainingReport;
use App\Models\User;
use App\Notifications\PilotTrainingClosedNotification;
use App\Notifications\PilotTrainingCreatedNotification;
use App\Notifications\PilotTrainingInstructorNotification;
use App\Notifications\PilotTrainingPreStatusNotification;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PilotTrainingController extends Controller
{
    use HasFactory;

    /**
     * A list of possible statuses
     */
    public static $statuses = [
        -4 => ['text' => 'Closed by system', 'color' => 'danger', 'icon' => 'fa fa-ban', 'assignableByStaff' => false],
        -3 => ['text' => 'Closed by student', 'color' => 'danger', 'icon' => 'fa fa-ban', 'assignableByStaff' => false],
        -2 => ['text' => 'Closed by staff', 'color' => 'danger', 'icon' => 'fas fa-ban', 'assignableByStaff' => true],
        -1 => ['text' => 'Completed', 'color' => 'success', 'icon' => 'fas fa-check', 'assignableByStaff' => true],
        0 => ['text' => 'In queue', 'color' => 'warning', 'icon' => 'fas fa-hourglass', 'assignableByStaff' => true],
        1 => ['text' => 'Pre-training', 'color' => 'info', 'icon' => 'fas fa-book-open', 'assignableByStaff' => true],
        2 => ['text' => 'Active training', 'color' => 'success', 'icon' => 'fas fa-book-open', 'assignableByStaff' => true],
        3 => ['text' => 'Awaiting exam', 'color' => 'warning', 'icon' => 'fas fa-graduation-cap', 'assignableByStaff' => true],
    ];

    /**
     * A list of possible experiences
     */
    public static $experiences = [
        1 => ['text' => 'New to VATSIM'],
        2 => ['text' => 'Experienced on VATSIM'],
        3 => ['text' => 'Real world pilot'],
    ];

    public function index()
    {
        $this->authorize('viewActiveRequests', PilotTraining::class);
        $openTrainings = Auth::user()->viewableModels(\App\Models\PilotTraining::class, [['status', '>=', 0]], ['user', 'user.groups', 'pilotRatings', 'instructors'])->sort(function ($a, $b) {
            if ($a->status == $b->status) {
                return $a->created_at->timestamp - $b->created_at->timestamp;
            }

            return $b->status - $a->status;
        });

        $statuses = PilotTrainingController::$statuses;

        return view('pilot.training.index', compact('openTrainings', 'statuses'));
    }

    public function history()
    {
        $this->authorize('viewHistoricRequests', PilotTraining::class);

        $closedTrainings = Auth::user()->viewableModels(\App\Models\PilotTraining::class, [['status', '<', 0]], ['reports', 'pilotRatings', 'activities', 'instructors', 'user', 'user.groups', 'user.groups'])->sortByDesc('closed_at');

        $statuses = PilotTrainingController::$statuses;

        return view('pilot.training.history', compact('closedTrainings', 'statuses'));
    }

    public function apply()
    {
        $this->authorize('apply', PilotTraining::class);
        $user = Auth::user();
        $userPilotRating = $user->pilotrating;
        $payload = [];

        // Fetch user's ATC hours
        $vatsimStats = [];
        $client = new \GuzzleHttp\Client();
        if (App::environment('production')) {
            $res = $client->request('GET', 'https://api.vatsim.net/api/ratings/' . $user->id . '/rating_times/');
        } else {
            $res = $client->request('GET', 'https://api.vatsim.net/api/ratings/819096/rating_times/');
        }

        if ($res->getStatusCode() == 200) {
            $vatsimStats = json_decode($res->getBody(), true);
        } else {
            return redirect()->back()->withErrors('We were unable to load the application for you due to missing data from VATSIM. Please try again later.');
        }

        // Get available trainings (PPL, IR, CMEL, ATPL)
        $pilotRatings = PilotRating::whereNotIn('vatsim_rating', [0, 31, 63])->get();

        if ($userPilotRating < 15) {

            foreach ($pilotRatings as $pilotRating) {
                if ($pilotRating->vatsim_rating > $userPilotRating) {
                    $payload[] = [
                        'id' => $pilotRating->id,
                        'name' => $pilotRating->name,
                    ];
                    break;
                }
            }
        }

        return view('pilot.training.apply', [
            'payload' => $payload,
            'pilot_hours' => $vatsimStats,
            'motivation_required' => ($userPilotRating <= 2) ? 1 : 0,
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validateUpdateDetails();
        $this->authorize('store', [PilotTraining::class, $data]);

        if (isset($data['user_id']) && User::find($data['user_id']) == null) {
            return response(['message' => 'The given CID cannot be found in the application database. Please check the user has logged in before.'], 400);
        }

        if (isset($data['training_level'])) {
            $ratings = PilotRating::find(explode('+', $data['training_level']));
        }

        $pilot_training = PilotTraining::create([
            'user_id' => isset($data['user_id']) ? $data['user_id'] : \Auth::id(),
            'created_by' => \Auth::id(),
            'experience' => isset($data['experience']) ? $data['experience'] : null,
            'comment' => isset($data['comment']) ? $data['comment'] : null,
            'english_only_training' => array_key_exists('englishOnly', $data) ? true : false,
        ]);

        if ($ratings->count() > 1) {
            $pilot_training->pilotRatings()->saveMany($ratings);
        } else {
            $pilot_training->pilotRatings()->save($ratings->first());
        }

        // Create and assign callsign to pilot training
        $this->assignCallsign($pilot_training);

        if ($request->expectsJson()) {
            return $pilot_training;
        }

        ActivityLogController::info('TRAINING', 'Created pilot training request ' . $pilot_training->id . ' for CID ' . $pilot_training->user->id . ' - Rating: ' . $ratings->pluck('name'));

        $pilot_training->user->notify(new PilotTrainingCreatedNotification($pilot_training));

        if ($request->expectsJson()) {
            return $pilot_training;
        }

        return redirect()->intended(route('dashboard'));
    }

    public function assignCallsign(PilotTraining $pilotTraining)
    {
        $baseNumber = 000;

        // level = rating id - 1 cause ratings start at P0
        $level = $pilotTraining->pilotRatings()->first()->id - 1;
        $callsignPrefix = Setting::get('ptdCallsign');

        $lastCallsign = DB::table('callsigns')
            ->where('callsign', 'LIKE', "{$callsignPrefix}{$level}%")
            ->orderBy('callsign', 'desc')
            ->first();

        if ($lastCallsign) {
            // Extract the number part from the last callsign and increment it
            $lastNumber = intval(substr($lastCallsign->callsign, strlen("{$callsignPrefix}{$level}")));
            $nextNumber = $lastNumber + 1;
        } else {
            // Start at the base number + 1 if no callsign exists for this level
            $nextNumber = $baseNumber + 1;
        }

        $newCallsign = $callsignPrefix . $level . str_pad($nextNumber, 3, '0', STR_PAD_LEFT);

        $callsign = Callsign::create([
            'callsign' => $newCallsign,
            'training_level' => $level,
            'pilot_training_id' => $pilotTraining->id,
            'user_id' => $pilotTraining->user_id,
        ]);

        $pilotTraining->callsign_id = $callsign->id;
        $pilotTraining->save();

        return $callsign;
    }

    public function create(Request $request, $prefillUserId = null)
    {
        $this->authorize('create', PilotTraining::class);

        $students = User::all();
        $pilotRatings = PilotRating::all();

        return view('pilot.training.create', compact('students', 'pilotRatings', 'prefillUserId'));
    }

    public function show(PilotTraining $training)
    {
        $this->authorize('view', $training);

        $reports = PilotTrainingReport::where('pilot_training_id', $training->id)->with('lesson')->get();

        $instructors = \Auth::user()->allWithGroup(4);
        $statuses = PilotTrainingController::$statuses;
        $experiences = PilotTrainingController::$experiences;
        $activities = $training->activities->sortByDesc('created_at');

        return view('pilot.training.show', compact('training', 'instructors', 'statuses', 'experiences', 'activities', 'reports'));
    }

    public function edit(PilotTraining $training)
    {
        $this->authorize('edit', [PilotTraining::class, $training]);

        $pilotRatings = PilotRating::all();

        return view('pilot.training.edit', compact('training', 'pilotRatings'));
    }

    public function updateDetails(PilotTraining $training)
    {
        $this->authorize('update', $training);
        $oldStatus = $training->fresh()->status;

        $attributes = $this->validateUpdateDetails();
        if (array_key_exists('status', $attributes)) {

            // Dont allow to reopen a training if student already has a training
            if ($attributes['status'] >= 0 && $oldStatus < 0 && $training->user->hasActivePilotTraining(true)) {
                if ($training->user->hasActivePilotTrainings(true)) {
                    return redirect($training->path())->withErrors('Training can not be reopened. The student already has an active training request.');
                }
            }

            $training->updateStatus($attributes['status']);

            if ($attributes['status'] != $oldStatus) {
                if ($attributes['status'] == -2 || $attributes['status'] == -4) {
                    PilotTrainingActivityController::create($training->id, 'STATUS', $attributes['status'], $oldStatus, Auth::user()->id, $attributes['closed_reason']);
                } else {
                    PilotTrainingActivityController::create($training->id, 'STATUS', $attributes['status'], $oldStatus, Auth::user()->id);
                }
            }
        }

        $notifyOfNewInstructor = false;
        if (array_key_exists('instructors', $attributes)) {
            foreach ((array) $attributes['instructors'] as $instructor) {
                if (! $training->instructors->contains($instructor) && User::find($instructor) != null && User::find($instructor)->isInstructor()) {
                    $training->instructors()->attach($instructor, ['expire_at' => now()->addMonths(12)]);
                    $notifyOfNewInstructor = true;

                    PilotTrainingActivityController::create($training->id, 'INSTRUCTOR', $instructor, null, Auth::user()->id);
                }
            }

            foreach ($training->instructors as $instructor) {
                if (! in_array($instructor->id, (array) $attributes['instructors'])) {
                    $training->instructors()->detach($instructor);
                    PilotTrainingActivityController::create($training->id, 'INSTRUCTOR', null, $instructor->id, Auth::user()->id);

                }
            }
            if ($notifyOfNewInstructor) {
                $training->user->notify(new PilotTrainingInstructorNotification($training));
            }
            unset($attributes['instructors']);
        } else {
            foreach ($training->instructors as $instructor) {
                PilotTrainingActivityController::create($training->id, 'INSTRUCTOR', null, $instructor->id, Auth::user()->id);

            }

            $training->instructors()->detach();
        }

        if (isset($attributes['paused_at'])) {
            if (! isset($training->paused_at)) {
                $attributes['paused_at'] = Carbon::now();
                PilotTrainingActivityController::create($training->id, 'PAUSE', 1, null, Auth::user()->id);
            } else {
                $attributes['paused_at'] = $training->paused_at;
            }
        } else {
            if (isset($training->paused_at)) {
                $training->paused_length = $training->paused_length + Carbon::create($training->paused_at)->diffInSeconds(Carbon::now());
                $training->update(['paused_length' => $training->paused_length]);
                PilotTrainingActivityController::create($training->id, 'PAUSE', 0, null, Auth::user()->id);
            }
            $attributes['paused_at'] = null;
        }

        if ((int) $training->status != $oldStatus) {
            if ((int) $training->status < TrainingStatus::IN_QUEUE->value) {
                $attributes['paused_at'] = null;
                if (isset($training->paused_at)) {
                    PilotTrainingActivityController::create($training->id, 'PAUSE', 0, null, Auth::user()->id);
                }
            }
        }

        // Update the training
        $training->update($attributes);

        ActivityLogController::warning('TRAINING', 'Updated pilot training details ' . $training->id .
        ' - Old Status: ' . PilotTrainingController::$statuses[$oldStatus]['text'] .
        ' - New Status: ' . PilotTrainingController::$statuses[$training->status]['text'] .
        ' - Instructor: ' . $training->instructors->pluck('name'));

        if ((int) $training->status != $oldStatus) {
            if ((int) $training->status < TrainingStatus::IN_QUEUE->value) {
                $training->instructors()->detach();

                $training->user->notify(new PilotTrainingClosedNotification($training, (int) $training->status, $training->closed_reason));

                return redirect($training->path())->withSuccess('Training successfully closed. E-mail confirmation of pre-training sent to the student.');
            }

            if ((int) $training->status == TrainingStatus::PRE_TRAINING->value) {
                $training->user->notify(new PilotTrainingPreStatusNotification($training));

                return redirect($training->path())->withSuccess('Training successfully updated. E-mail confirmation of pre-training sent to the student.');
            }
        }

        if ($notifyOfNewInstructor) {
            return redirect($training->path())->withSuccess('Training successfully updated. E-mail notification of instructor assigned sent to the student');
        }

        return redirect($training->path())->withSuccess('Training successfully updated');
    }

    public function updateRequest(PilotTraining $training)
    {
        $this->authorize('update', $training);
        $attributes = $this->validateUpdateEdit();

        $preChangeRatings = $training->pilotRatings;

        $training->pilotRatings()->detach();
        if (isset($attributes['pilotRatings'])) {
            $pilotRatings = PilotRating::find($attributes['pilotRatings']);
        } else {
            return redirect()->back()->withErrors('One or more ratings need to be selected to update training request.');
        }

        if ($pilotRatings->count() > 1) {
            $training->pilotRatings()->saveMany($pilotRatings);
        } else {
            $training->pilotRatings()->save($pilotRatings->first());
        }

        $training->english_only_training = array_key_exists('englishOnly', $attributes) ? true : false;

        $training->save();

        $this->assignCallsign($training);

        ActivityLogController::warning('TRAINING', 'Updated pilot training request ' . $training->id .
        ' - Old Rating: ' . $preChangeRatings->pluck('name') .
        ' - New Rating: ' . $pilotRatings->pluck('name') .
        ' - English only: ' . ($training->english_only_training ? 'true' : 'false'));

        return redirect($training->path())->withSuccess('Pilot training successfully updated');

    }

    protected function validateUpdateDetails()
    {
        return request()->validate([
            'experience' => 'sometimes|required|integer|min:1|max:3',
            'englishOnly' => 'nullable',
            'user_id' => 'sometimes|required|integer',
            'comment' => 'nullable',
            'training_level' => 'sometimes|required',
            'status' => 'sometimes|required|integer',
            'instructors' => 'sometimes',
            'closed_reason' => 'sometimes|max:65',
        ]);
    }

    protected function validateUpdateEdit()
    {
        return request()->validate([
            'pilotRatings' => 'sometimes|required',
            'englishOnly' => 'nullable',
        ]);
    }
}
