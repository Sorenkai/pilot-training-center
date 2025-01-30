<?php

namespace App\Http\Controllers;

use App\Models\Area;
use App\Models\Feedback;
use App\Models\Group;
use App\Models\ManagementReport;
use App\Models\PilotRating;
use App\Models\PilotTraining;
use App\Models\PilotTrainingActivity;
use App\Models\PilotTrainingReport;
use App\Models\Training;
use App\Models\TrainingActivity;
use App\Models\TrainingReport;
use App\Models\User;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
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
        [$newRequests, $completedRequests, $closedRequests, $passFailRequests, $allExamResults] = $this->getBiAnnualRequestsStats($filterArea);
        // $queues = $this->getQueueStats($filterArea);

        // Send it to the view
        ($filterArea) ? $filterName = Area::find($filterArea)->name : $filterName = 'All Areas';
        $areas = Area::all();

        return view('reports.trainings', compact('filterName', 'areas', 'cardStats', 'totalRequests', 'newRequests', 'completedRequests', 'closedRequests', 'passFailRequests', 'allExamResults'));
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

        $activities = PilotTrainingActivity::with('pilotTraining', 'pilotTraining.pilotRatings', 'pilotTraining.user', 'user')->orderByDesc('created_at')->limit(100)->get();

        // Fetch TrainingReport and ExaminationReport from last activity to now
        $trainingReports = PilotTrainingReport::where('created_at', '>=', $activities->last()->created_at)->get();

        $entries = $activities->merge($trainingReports);

        // Do the rest
        $statuses = PilotTrainingController::$statuses;

        $areas = Area::all();

        return view('reports.activities', compact('trainingReports', 'statuses', 'areas', 'entries'));
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

        $examMonthTranslator = [
            (int) Carbon::now()->format('m') => 11,
            (int) Carbon::now()->subMonths(1)->format('m') => 10,
            (int) Carbon::now()->subMonths(2)->format('m') => 9,
            (int) Carbon::now()->subMonths(3)->format('m') => 8,
            (int) Carbon::now()->subMonths(4)->format('m') => 7,
            (int) Carbon::now()->subMonths(5)->format('m') => 6,
            (int) Carbon::now()->subMonths(6)->format('m') => 5,
            (int) Carbon::now()->subMonths(7)->format('m') => 4,
            (int) Carbon::now()->subMonths(8)->format('m') => 3,
            (int) Carbon::now()->subMonths(9)->format('m') => 2,
            (int) Carbon::now()->subMonths(10)->format('m') => 1,
            (int) Carbon::now()->subMonths(11)->format('m') => 0,
        ];

        $newRequests = [];
        $completedRequests = [];
        $closedRequests = [];
        $passFailRequests = [];
        $allExamResults = [];

        foreach (PilotRating::all() as $rating) {
            $newRequests[$rating->name] = [0 => 0, 1 => 0, 2 => 0, 3 => 0, 4 => 0, 5 => 0, 6 => 0];
            $completedRequests[$rating->name] = [0 => 0, 1 => 0, 2 => 0, 3 => 0, 4 => 0, 5 => 0, 6 => 0];
            $closedRequests[$rating->name] = [0 => 0, 1 => 0, 2 => 0, 3 => 0, 4 => 0, 5 => 0, 6 => 0];
            $passFailRequests['Passed'] = [0 => 0, 1 => 0, 2 => 0, 3 => 0, 4 => 0, 5 => 0, 6 => 0, 7 => 0, 8 => 0, 9 => 0, 10 => 0, 11 => 0];
            $passFailRequests['Partially Passed'] = [0 => 0, 1 => 0, 2 => 0, 3 => 0, 4 => 0, 5 => 0, 6 => 0, 7 => 0, 8 => 0, 9 => 0, 10 => 0, 11 => 0, 12 => 0];
            $passFailRequests['Failed'] = [0 => 0, 1 => 0, 2 => 0, 3 => 0, 4 => 0, 5 => 0, 6 => 0, 7 => 0, 8 => 0, 9 => 0, 10 => 0, 11 => 0];

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
                ->select(DB::raw('count(pilot_trainings.id) as `count`'), DB::raw('MONTH(pilot_trainings.closed_at) as month'))
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
                ->select(DB::raw('count(pilot_trainings.id) as `count`'), DB::raw('MONTH(pilot_trainings.closed_at) as month'))
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
        $query = DB::table('exams')
            ->select(DB::raw('count(exams.id) as `count`'), DB::raw('MONTH(exams.created_at) as month'))
            ->where('type', 'PRACTICAL')
            ->join('pilot_trainings', 'pilot_trainings.id', '=', 'exams.pilot_training_id')
            ->whereExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('pilot_rating_pilot_training')
                    ->join('pilot_ratings', 'pilot_ratings.id', 'pilot_rating_pilot_training.pilot_rating_id')
                    ->whereColumn('pilot_rating_pilot_training.pilot_training_id', 'pilot_trainings.id');
            })
            ->where('result', 'PASS')
            ->where('exams.created_at', '>=', date('Y-m-d H:i:s', strtotime('-11 months')))
            ->groupBy('month')
            ->get();

        foreach ($query as $entry) {
            $passFailRequests['Passed'][$examMonthTranslator[$entry->month]] = $entry->count;
        }

        // Partially passed trainings
        $query = DB::table('exams')
            ->select(DB::raw('count(exams.id) as `count`'), DB::raw('MONTH(exams.created_at) as month'))
            ->where('type', 'PRACTICAL')
            ->join('pilot_trainings', 'pilot_trainings.id', '=', 'exams.pilot_training_id')
            ->whereExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('pilot_rating_pilot_training')
                    ->join('pilot_ratings', 'pilot_ratings.id', 'pilot_rating_pilot_training.pilot_rating_id')
                    ->whereColumn('pilot_rating_pilot_training.pilot_training_id', 'pilot_trainings.id');
            })
            ->where('result', 'PARTIAL PASS')
            ->where('exams.created_at', '>=', date('Y-m-d H:i:s', strtotime('-11 months')))
            ->groupBy('month')
            ->get();

        foreach ($query as $entry) {
            $passFailRequests['Partially Passed'][$examMonthTranslator[$entry->month]] = $entry->count;
        }

        // Failed trainings
        $query = DB::table('exams')
            ->select(DB::raw('count(exams.id) as `count`'), DB::raw('MONTH(exams.created_at) as month'))
            ->where('type', 'PRACTICAL')
            ->join('pilot_trainings', 'pilot_trainings.id', '=', 'exams.pilot_training_id')
            ->whereExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('pilot_rating_pilot_training')
                    ->join('pilot_ratings', 'pilot_ratings.id', 'pilot_rating_pilot_training.pilot_rating_id')
                    ->whereColumn('pilot_rating_pilot_training.pilot_training_id', 'pilot_trainings.id');
            })
            ->where('result', 'FAIL')
            ->where('exams.created_at', '>=', date('Y-m-d H:i:s', strtotime('-11 months')))
            ->groupBy('month')
            ->get();

        foreach ($query as $entry) {
            $passFailRequests['Failed'][$examMonthTranslator[$entry->month]] = $entry->count;
        }

        // Get all exams that are passed
        $query = DB::table('exams')
            ->select(DB::raw('count(exams.id) as `count`'))
            ->where('type', 'PRACTICAL')
            ->where('result', 'PASS')
            ->get();

        foreach ($query as $entry) {
            $allExamResults['Passed'] = $entry->count;
        }

        $query = DB::table('exams')
            ->select(DB::raw('count(exams.id) as `count`'))
            ->where('type', 'PRACTICAL')
            ->where('result', 'PARTIAL PASS')
            ->get();

        foreach ($query as $entry) {
            $allExamResults['Partially Passed'] = $entry->count;
        }

        $query = DB::table('exams')
            ->select(DB::raw('count(exams.id) as `count`'))
            ->where('type', 'PRACTICAL')
            ->where('result', 'FAIL')
            ->get();

        foreach ($query as $entry) {
            $allExamResults['Failed'] = $entry->count;
        }

        return [$newRequests, $completedRequests, $closedRequests, $passFailRequests, $allExamResults];
    }
}
