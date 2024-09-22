<?php

namespace App\Http\Controllers;

use App\Models\Area;
use App\Models\Feedback;
use App\Models\Group;
use App\Models\ManagementReport;
use App\Models\Rating;
use App\Models\PilotTrainingActivity;
use App\Models\PilotTraining;
use App\Models\PilotRating;
use App\Models\PilotTrainingReport;
use App\Models\Training;
use App\Models\TrainingActivity;
use App\Models\TrainingExamination;
use App\Models\TrainingReport;
use App\Models\User;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

/**
 * This controller handles the report views and statistics
 */
class ReportController extends Controller
{
    /**
     * Show the training statistics view
     *
     * @return \Illuminate\View\View
     *
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function access()
    {
        $this->authorize('viewAccessReport', ManagementReport::class);

        $availableUsers = User::all();

        // Cherrypick those with access roles
        $users = collect();
        foreach ($availableUsers as $user) {
            if ($user->groups()->count()) {
                $users->push($user);
            }
        }

        $areas = Area::all();

        return view('reports.access', compact('users', 'areas'));
    }

    /**
     * Show the training statistics view
     *
     * @param  int  $filterArea  areaId to filter by
     * @return \Illuminate\View\View
     *
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function trainings($filterArea = false)
    {
        $this->authorize('accessTrainingReports', [ManagementReport::class, $filterArea]);
        // Get stats
        $cardStats = $this->getCardStats($filterArea);
        $totalRequests = $this->getDailyRequestsStats($filterArea);
        [$newRequests, $completedRequests, $closedRequests, $passFailRequests] = $this->getBiAnnualRequestsStats($filterArea);
        $queues = $this->getQueueStats($filterArea);

        // Send it to the view
        ($filterArea) ? $filterName = Area::find($filterArea)->name : $filterName = 'All Areas';
        $areas = Area::all();

        return view('reports.trainings', compact('filterName', 'areas', 'cardStats', 'totalRequests', 'newRequests', 'completedRequests', 'closedRequests', 'passFailRequests', 'queues'));
    }

    /**
     * Show the training activities statistics view
     *
     * @param  int  $filterArea  areaId to filter by
     * @return \Illuminate\View\View
     *
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function activities($filterArea = false)
    {
        $this->authorize('accessTrainingReports', [ManagementReport::class, $filterArea]);

        // Fetch TrainingActivity

        $activities = PilotTrainingActivity::with('pilotTraining', 'pilotTraining.pilotRatings', 'pilotTraining.user', 'user' )->orderByDesc('created_at')->limit(100)->get();
        

        // Fetch TrainingReport and ExaminationReport from last activity to now
        $trainingReports = PilotTrainingReport::where('created_at', '>=', $activities->last()->created_at)->get();

        $entries = $activities->merge($trainingReports);

        // Do the rest
        $statuses = PilotTrainingController::$statuses;

        $areas = Area::all();

        return view('reports.activities', compact('trainingReports', 'statuses', 'areas', 'entries'));
    }

    /**
     * Show the mentors statistics view
     *
     * @return \Illuminate\View\View
     *
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function mentors()
    {
        $this->authorize('viewMentors', ManagementReport::class);

        if (auth()->user()->isAdmin()) {
            $mentors = Group::find(3)->users()->with('trainingReports', 'teaches', 'teaches.reports', 'teaches.user')->get();
        } else {
            $mentors = Group::find(3)->users()->with('trainingReports', 'teaches', 'teaches.reports', 'teaches.user')->whereHas('groups', function (Builder $query) {
                $query->whereIn('area_id', auth()->user()->groups()->pluck('area_id'));
            })->get();
        }

        $mentors = $mentors->sortBy('name')->unique();
        $statuses = TrainingController::$statuses;

        return view('reports.mentors', compact('mentors', 'statuses'));
    }

    /**
     * Show the instructors statistics view
     *
     * @return \Illuminate\View\View
     *
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function instructors()
    {
        $this->authorize('viewInstructors', ManagementReport::class);

        $instructors = collect();

        if (auth()->user()->isAdmin()) {
            $instructors = Group::find(4)->users()
                ->with(['pilotTrainingReports' => function ($query) {
                    $query->where('created_at', '>=', now()->subYear()); // Filter for last 12 months
                }, 'instructs', 'instructs.reports', 'instructs.user'])
                ->withSum('pilotTrainingReports', 'instructor_hours') // Total hours
                ->get();
        }

        $instructors = $instructors->sortBy('name')->unique();
        $statuses = PilotTrainingController::$statuses;

        foreach ($instructors as $instructor) {
            $instructor->last_12_months_hours = $instructor->pilotTrainingReports->sum('instructor_hours'); // Sum for the last 12 months
        }

        return view('reports.instructors', compact('instructors', 'statuses'));
    }

    /**
     * Index received feedback
     *
     * @return \Illuminate\View\View
     */
    public function feedback()
    {
        $feedback = Feedback::all()->sortByDesc('created_at');

        return view('reports.feedback', compact('feedback'));
    }

    /**
     * Return the statistics for the cards (in queue, in training, awaiting exam, completed this year) on top of the page
     *
     * @param  int  $filterArea  areaId to filter by
     * @return mixed
     */
    protected function getCardStats($filterArea)
    {
        $payload = [
            'waiting' => 0,
            'training' => 0,
            'exam' => 0,
            'completed' => 0,
            'closed' => 0,
        ];

        $payload['waiting'] = PilotTraining::where('status', 0)->count();
        $payload['training'] = PilotTraining::whereIn('status', [1, 2])->count();
        $payload['exam'] = PilotTraining::where('status', 3)->count();
        $payload['completed'] = PilotTraining::where('status', -1)->where('closed_at', '>=', date('Y-m-d H:i:s', strtotime('first day of january this year')))->count();
        $payload['closed'] = PilotTraining::where('status', -2)->where('closed_at', '>=', date('Y-m-d H:i:s', strtotime('first day of january this year')))->count();
       

        return $payload;
    }

    /**
     * Return the statistics the total amount of requests per day
     *
     * @param  int  $areaFilter  areaId to filter by
     * @return mixed
     */
    protected function getDailyRequestsStats($areaFilter)
    {
        // Create an arra with all dates last 12 months
        $dates = [];
        foreach (CarbonPeriod::create(Carbon::now()->subYear(1), Carbon::now()) as $date) {
            $dates[$date->format('Y-m-d')] = ['x' => $date->format('Y-m-d'), 'y' => 0];
        }

        $data = PilotTraining::select([
            DB::raw('count(id) as `count`'),
            DB::raw('DATE(created_at) as day'),
        ])->groupBy('day')
            ->where('created_at', '>=', Carbon::now()->subYear(1))
            ->get();

        foreach ($data as $entry) {
            $dates[$entry->day]['y'] = $entry->count;
        }

        // Strip the keys to match requirement of chart.js
        $payload = [];
        foreach ($dates as $loadKey => $load) {
            array_push($payload, $load);
        }

        //        dd($payload);
        return $payload;
    }

    /**
     * Return the new/completed request statistics for 6 months
     *
     * @param  int  $areaFilter  areaId to filter by
     * @return mixed
     */
    protected function getBiAnnualRequestsStats($areaFilter)
    {
        $monthTranslator = [
            (int) Carbon::now()->format('m') => 6,
            (int) Carbon::now()->subMonths(1)->format('m') => 5,
            (int) Carbon::now()->subMonths(2)->format('m') => 4,
            (int) Carbon::now()->subMonths(3)->format('m') => 3,
            (int) Carbon::now()->subMonths(4)->format('m') => 2,
            (int) Carbon::now()->subMonths(5)->format('m') => 1,
            (int) Carbon::now()->subMonths(6)->format('m') => 0,
        ];

        $newRequests = [];
        $completedRequests = [];
        $closedRequests = [];
        $passFailRequests = [];

        
        foreach (PilotRating::all() as $rating) {
            $newRequests[$rating->name] = [0 => 0, 1 => 0, 2 => 0, 3 => 0, 4 => 0, 5 => 0, 6 => 0];
            $completedRequests[$rating->name] = [0 => 0, 1 => 0, 2 => 0, 3 => 0, 4 => 0, 5 => 0, 6 => 0];
            $closedRequests[$rating->name] = [0 => 0, 1 => 0, 2 => 0, 3 => 0, 4 => 0, 5 => 0, 6 => 0];
            $passFailRequests['Passed'] = [0 => 0, 1 => 0, 2 => 0, 3 => 0, 4 => 0, 5 => 0, 6 => 0];
            $passFailRequests['Failed'] = [0 => 0, 1 => 0, 2 => 0, 3 => 0, 4 => 0, 5 => 0, 6 => 0];
            
            // New requests
            DB::enableQueryLog();

            $query = DB::table('pilot_trainings')
                ->select(DB::raw('count(pilot_trainings.id) as `count`'), DB::raw('MONTH(pilot_trainings.created_at) as month'))
                ->join('pilot_rating_pilot_training', 'pilot_trainings.id', '=', 'pilot_rating_pilot_training.pilot_training_id')
                ->join('pilot_ratings', 'pilot_ratings.id', '=', 'pilot_rating_pilot_training.pilot_rating_id')
                ->where('pilot_trainings.created_at', '>=', date('Y-m-d H:i:s', strtotime('-6 months')))
                ->where('pilot_rating_pilot_training.pilot_rating_id', $rating->id)
                ->groupBy('month')
                ->get();

            foreach ($query as $entry) {
                $newRequests[$rating->name][$monthTranslator[$entry->month]] = $entry->count;
            }

            // Completed requests
            $query = DB::table('pilot_trainings')
                ->select(DB::raw('count(pilot_trainings.id) as `count`'), DB::raw('MONTH(pilot_trainings.created_at) as month'))
                ->join('pilot_rating_pilot_training', 'pilot_trainings.id', '=', 'pilot_rating_pilot_training.pilot_training_id')
                ->join('pilot_ratings', 'pilot_ratings.id', '=', 'pilot_rating_pilot_training.pilot_rating_id')
                ->where('status', -1)
                ->where('closed_at', '>=', date('Y-m-d H:i:s', strtotime('-6 months')))
                ->where('pilot_rating_id', $rating->id)
                ->groupBy('month')
                ->get();

            foreach ($query as $entry) {
                $completedRequests[$rating->name][$monthTranslator[$entry->month]] = $entry->count;
            }

            // Closed requests
            $query = DB::table('pilot_trainings')
                ->select(DB::raw('count(pilot_trainings.id) as `count`'), DB::raw('MONTH(pilot_trainings.created_at) as month'))
                ->join('pilot_rating_pilot_training', 'pilot_trainings.id', '=', 'pilot_rating_pilot_training.pilot_training_id')
                ->join('pilot_ratings', 'pilot_ratings.id', '=', 'pilot_rating_pilot_training.pilot_rating_id')
                ->where('status', -2)
                ->where('closed_at', '>=', date('Y-m-d H:i:s', strtotime('-6 months')))
                ->where('pilot_rating_id', $rating->id)
                ->groupBy('month')
                ->get();

            foreach ($query as $entry) {
                $closedRequests[$rating->name][$monthTranslator[$entry->month]] = $entry->count;
            }
        }

        // Passed trainings
        $query = DB::table('training_examinations')
            ->select(DB::raw('count(training_examinations.id) as `count`'), DB::raw('MONTH(training_examinations.examination_date) as month'))
            ->join('trainings', 'trainings.id', '=', 'training_examinations.training_id')
            ->whereExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('rating_training')
                    ->join('ratings', 'ratings.id', 'rating_training.rating_id')
                    ->whereColumn('rating_training.training_id', 'trainings.id')
                    ->where('ratings.vatsim_rating', '>=', 3)
                    ->whereNotNull('ratings.vatsim_rating');
            })
            ->whereIn('trainings.type', [1, 4])
            ->where('result', 'PASSED')
            ->where('examination_date', '>=', date('Y-m-d H:i:s', strtotime('-6 months')))
            ->groupBy('month')
            ->get();

        foreach ($query as $entry) {
            $passFailRequests['Passed'][$monthTranslator[$entry->month]] = $entry->count;
        }

        // Failed trainings
        $query = DB::table('training_examinations')
            ->select(DB::raw('count(training_examinations.id) as `count`'), DB::raw('MONTH(training_examinations.examination_date) as month'))
            ->join('trainings', 'trainings.id', '=', 'training_examinations.training_id')
            ->whereExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('rating_training')
                    ->join('ratings', 'ratings.id', 'rating_training.rating_id')
                    ->whereColumn('rating_training.training_id', 'trainings.id')
                    ->where('ratings.vatsim_rating', '>=', 3)
                    ->whereNotNull('ratings.vatsim_rating');
            })
            ->whereIn('trainings.type', [1, 4])
            ->where('result', 'FAILED')
            ->where('examination_date', '>=', date('Y-m-d H:i:s', strtotime('-6 months')))
            ->groupBy('month')
            ->get();

        foreach ($query as $entry) {
            $passFailRequests['Failed'][$monthTranslator[$entry->month]] = $entry->count;
        }

    

        return [$newRequests, $completedRequests, $closedRequests, $passFailRequests];
    }

    /**
     * Return the new/completed request statistics for 6 months
     *
     * @param  int  $areaFilter  areaId to filter by
     * @return mixed
     */
    protected function getQueueStats($areaFilter)
    {
        $payload = [];
        if ($areaFilter) {
            foreach (Area::find($areaFilter)->ratings as $rating) {
                if ($rating->pivot->queue_length_low && $rating->pivot->queue_length_high) {
                    $payload[$rating->name] = [
                        $rating->pivot->queue_length_low,
                        $rating->pivot->queue_length_high,
                    ];
                }
            }
        } else {
            $divideRating = [];
            foreach (Area::all() as $area) {
                // Loop through the ratings of this area to get queue length
                foreach ($area->ratings as $rating) {
                    // Only calculate if queue length is defined
                    if ($rating->pivot->queue_length_low && $rating->pivot->queue_length_high) {
                        if (isset($payload[$rating->name])) {
                            $payload[$rating->name][0] = $payload[$rating->name][0] + $rating->pivot->queue_length_low;
                            $payload[$rating->name][1] = $payload[$rating->name][1] + $rating->pivot->queue_length_high;
                            $divideRating[$rating->name]++;
                        } else {
                            $payload[$rating->name] = [
                                $rating->pivot->queue_length_low,
                                $rating->pivot->queue_length_high,
                            ];
                            $divideRating[$rating->name] = 1;
                        }
                    }
                }
            }

            // Divide the queue length appropriately to get an average across areas
            foreach ($payload as $queue => $value) {
                $payload[$queue][0] = $value[0] / $divideRating[$queue];
                $payload[$queue][1] = $value[1] / $divideRating[$queue];
            }
        }

        return $payload;
    }
}
