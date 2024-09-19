<?php

namespace App\Http\Controllers;

use anlutro\LaravelSettings\Facade as Setting;
use App;
use App\Models\PilotTrainingReport;
use App\Models\TrainingInterest;
use App\Models\User;
use App\Models\Vote;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

/**
 * Controller for the dashboard
 */
class DashboardController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        $user = Auth::user();

        $report = PilotTrainingReport::whereIn('pilot_training_id', $user->trainings->pluck('id'))->orderBy('created_at')->get()->last();

        $subdivision = $user->subdivision;
        if (empty($subdivision)) {
            $subdivision = 'No subdivision';
        }

        $data = [
            'rating' => $user->rating_long,
            'rating_short' => $user->rating_short,
            'pilotrating' => $user->pilotrating_long,
            'pilotrating_short' => $user->pilotrating_short,
            'division' => $user->division,
            'subdivision' => $subdivision,
            'report' => $report,
        ];

        $trainings = $user->pilotTrainings;
        $statuses = PilotTrainingController::$statuses;
        $types = TrainingController::$types;

        //$dueInterestRequest = TrainingInterest::whereIn('training_id', $user->trainings->pluck('id'))->where('expired', false)->get()->first();

        // If the user belongs to our subdivision, doesn't have any training requests, has S2+ rating and is marked as inactive -> show notice
        $allowedSubDivisions = explode(',', Setting::get('trainingSubDivisions'));
        $atcInactiveMessage = ((in_array($user->subdivision, $allowedSubDivisions) && $allowedSubDivisions != null) && (! $user->hasActiveTrainings(true) && $user->rating > 1 && ! $user->isAtcActive()) && ! $user->hasRecentlyCompletedTraining());
        $completedTrainingMessage = $user->hasRecentlyCompletedTraining();

        $workmailRenewal = (isset($user->setting_workmail_expire)) ? (Carbon::parse($user->setting_workmail_expire)->diffInDays(Carbon::now(), false) > -7) : false;

        // Check if there's an active vote running to advertise
        $activeVote = Vote::where('closed', 0)->first();

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

        $pilotHours = $vatsimStats['pilot'];

        $studentTrainings = \Auth::user()->instructingTrainings();

        $cronJobError = (($user->isAdmin() && App::environment('production')) && (\Carbon\Carbon::parse(Setting::get('_lastCronRun', '2000-01-01')) <= \Carbon\Carbon::now()->subMinutes(5)));

        $oudatedVersionWarning = $user->isAdmin() && Setting::get('_updateAvailable');

        return view('dashboard', compact('data', 'trainings', 'statuses', 'types', 'atcInactiveMessage', 'completedTrainingMessage', 'activeVote', 'pilotHours', 'workmailRenewal', 'studentTrainings', 'cronJobError', 'oudatedVersionWarning'));
    }

    /**
     * Show the training apply view
     *
     * @return \Illuminate\View\View
     */
    public function apply()
    {
        return view('trainingapply');
    }

    /**
     * Show member endorsements view
     *
     * @return \Illuminate\View\View
     */
    public function endorsements()
    {
        $members = User::has('ratings')->get()->sortBy('name');

        return view('endorsements', compact('members'));
    }
}
