<?php

namespace App\Http\Controllers;

use App\Facades\DivisionApi;
use App\Helpers\TrainingStatus;
use App\Models\Area;
use App\Models\InstructorEndorsement;
use App\Models\Endorsement;
use App\Models\Position;
use App\Models\Rating;
use App\Models\PilotRating;
use App\Models\User;
use App\Notifications\EndorsementCreatedNotification;
use App\Notifications\EndorsementModifiedNotification;
use App\Notifications\EndorsementRevokedNotification;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EndorsementController extends Controller
{
    /**
     * Display a listing of the Solo endorsement
     *
     * @return \Illuminate\Http\Response
     */
    public function indexSolos()
    {
        $endorsements = Endorsement::where('type', 'SOLO')->with('positions', 'user')
            ->where(function ($q) {
                $q->orWhere(function ($q2) {
                    $q2->where('expired', false)
                        ->where('revoked', false);
                })
                    ->orWhere(function ($q2) {
                        $q2->where(function ($q3) {
                            $q3->where('valid_to', '>=', Carbon::now()->subDays(14));
                        })
                            ->where(function ($q3) {
                                $q3->where('expired', true)
                                    ->orWhere('revoked', true);
                            });
                    });
            })
            ->get();

        // Sort endorsements
        $endorsements = $endorsements->sortByDesc('valid_to');

        return view('endorsements.solos', compact('endorsements'));
    }

    /**
     * Display a listing of the users with examiner endorsements
     *
     * @return \Illuminate\Http\Response
     */
    public function indexExaminers()
    {
        $endorsements = Endorsement::where('type', 'EXAMINER')->where('revoked', false)->get();
        $areas = Area::all();

        return view('endorsements.examiners', compact('endorsements', 'areas'));
    }

    /**
     * Display a listing of the users with visiting endorsements
     *
     * @return \Illuminate\Http\Response
     */
    public function indexVisitors()
    {
        $endorsements = Endorsement::where('type', 'VISITING')->where('revoked', false)->with('user', 'ratings', 'areas.ratings')->get();
        $areas = Area::all();

        return view('endorsements.visiting', compact('endorsements', 'areas'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create($prefillUserId = null)
    {
        $this->authorize('create', Endorsement::class);
        if ($prefillUserId) {
            $users = collect(User::where('id', $prefillUserId)->get());
        } else {
            $users = User::allWithGroup(4);
        }
        $ratings = PilotRating::whereIn('vatsim_rating', [1, 3, 7, 15])->get();


        return view('endorsements.create', compact('users', 'ratings', 'prefillUserId'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $this->authorize('create', [Endorsement::class]);
        
        $data = [];
        $data = request()->validate([
            'user' => 'required|numeric|exists:App\Models\User,id',
            'rating' => 'required|array',
        ]);

        $user = User::find($data['user']);
        // check if pilot endorsement exists


        foreach ($data['rating'] as $rating) {
            $r = PilotRating::find($rating);

            // Check if the record already exists
            $existingEndorsement = InstructorEndorsement::where('user_id', $user->id)
                ->where('pilot_rating_id', $r->id)
                ->first();

            if ($existingEndorsement) {
                return redirect()->back()->withErrors('An endorsement for this user and rating already exists.');
            }
            self::createInstructorEndorsementModel($user, $r);

            // log endorsement
            ActivityLogController::warning('ENDORSEMENT', 'Created instructor endorsement ' .
            ' ― User: ' . $user->id .
            ' ― Rating: ' . $r->name);
        }

        return redirect()->intended(route('roster'))->withSuccess($user->name . "'s endorsement created");
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Endorsement  $endorsement
     * @return \Illuminate\Http\Response
     */
    public function destroy($endorsementId)
    {
        $endorsement = Endorsement::findOrFail($endorsementId);
        $this->authorize('delete', [Endorsement::class, $endorsement]);
        $user = User::find($endorsement->user_id);

        if ($endorsement->revoked) {
            return redirect()->back()->withErrors($user->name . "'s " . $endorsement->type . ' endorsement is already revoked.');
        }

        if ($endorsement->type == 'EXAMINER') {
            $response = DivisionApi::removeExaminer($user, $endorsement, Auth::id());
            if ($response && $response->failed()) {
                return back()->withErrors('Request failed due to error in ' . DivisionApi::getName() . ' API: ' . $response->json()['message']);
            }
        } elseif ($endorsement->type == 'FACILITY') {
            if (isset($endorsement->ratings->first()->endorsement_type)) {
                $response = DivisionApi::revokeTierEndorsement($endorsement->ratings->first()->endorsement_type, $endorsement->user->id, $endorsement->ratings->first()->name);
                if ($response && $response->failed()) {
                    return back()->withErrors('Request failed due to error in ' . DivisionApi::getName() . ' API: ' . $response->json()['message']);
                }
            }
        } elseif ($endorsement->type == 'SOLO') {
            $response = DivisionApi::revokeSoloEndorsement($endorsement);
            if ($response && $response->failed()) {
                return back()->withErrors('Request failed due to error in ' . DivisionApi::getName() . ' API: ' . $response->json()['message']);
            }
        }

        $endorsement->revoked = true;
        $endorsement->revoked_by = \Auth::user()->id;
        $endorsement->valid_to = now();
        $endorsement->save();

        ActivityLogController::warning('ENDORSEMENT', 'Deleted ' . $user->name . '\'s ' . $endorsement->type . ' endorsement');
        if ($endorsement->type == 'SOLO') {
            $endorsement->user->notify(new EndorsementRevokedNotification($endorsement));

            return redirect()->back()->withSuccess(User::find($endorsement->user_id)->name . "'s " . $endorsement->type . ' endorsement revoked. E-mail confirmation sent to the student.');
        }

        return redirect()->back()->withSuccess(User::find($endorsement->user_id)->name . "'s " . $endorsement->type . ' endorsement revoked.');
    }

    /**
     * Shorten the specified resource from storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function shorten($endorsementId, $date)
    {
        $endorsement = Endorsement::findOrFail($endorsementId);
        $this->authorize('shorten', [Endorsement::class, $endorsement]);

        $date = Carbon::parse($date);

        if ($date->gt($endorsement->valid_to)) {
            return redirect()->back()->withErrors('You can not shorten an endorsement to a future date.');
        }

        $date->setHour(12)->setMinute(00);

        // Push updated date to API
        $response = DivisionApi::assignSoloEndorsement($endorsement->user, $endorsement->positions->first(), Auth::id(), $date);
        if ($response && $response->failed()) {
            return back()->withErrors('Request failed due to error in ' . DivisionApi::getName() . ' API: ' . $response->json()['message']);
        }

        // Save new date
        $endorsement->valid_to = $date;
        $endorsement->save();

        ActivityLogController::warning('ENDORSEMENT', 'Shortened ' . User::find($endorsement->user_id)->name . '\'s ' . $endorsement->type . ' endorsement to date ' . $date);
        $endorsement->user->notify(new EndorsementModifiedNotification($endorsement));

        return redirect()->back()->withSuccess(User::find($endorsement->user_id)->name . "'s " . $endorsement->type . ' endorsement shortened to ' . Carbon::parse($date)->toEuropeanDateTime() . '. E-mail sent to student.');
    }

    /**
     * Private function to create an endorsement object
     *
     * @param  string  $endorsementType
     * @param  string  $valid_to
     * @return \App\Models\Endorsement
     */
    private function createEndorsementModel($endorsementType, User $user, $valid_to = null)
    {
        $endorsement = new Endorsement();
        $endorsement->user_id = $user->id;
        $endorsement->type = $endorsementType;
        $endorsement->issued_by = \Auth::user()->id;
        $endorsement->save();

        return $endorsement;
    }

    private static function createInstructorEndorsementModel(User $user, PilotRating $rating)
    {
        $endorsement = new InstructorEndorsement();
        $endorsement->user_id = $user->id;
        $endorsement->pilot_rating_id = $rating->id;
        $endorsement->issued_by = \Auth::user()->id;
        $endorsement->save();
    }
}
