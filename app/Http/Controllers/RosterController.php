<?php

namespace App\Http\Controllers;

use App\Models\InstructorEndorsement;
use App\Models\PilotRating;
use App\Models\User;

class RosterController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $users = User::allWithGroup(4);
        $ratings = PilotRating::whereIn('id', [2, 3, 4, 5])->get();
        $endorsements = InstructorEndorsement::all();

        return view('roster', compact('users', 'ratings', 'endorsements'));
    }
}
