<?php

namespace App\Http\Controllers;

use App\Models\PilotRating;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

class RosterController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        /*
        $area = Area::find($areaId);
        $users = User::allActiveInArea($area);

        $visitingUsers = User::whereHas('endorsements', function ($query) use ($areaId) {
            $query->where('type', 'VISITING')->where('revoked', false)->whereHas('areas', function ($query) use ($areaId) {
                $query->where('area_id', $areaId);
            });
        })->get();

        $users = $users->merge($visitingUsers);

        // Get ratings that are not VATSIM ratings which belong to the area
        $ratings = Rating::whereHas('areas', function (Builder $query) use ($areaId) {
            $query->where('area_id', $areaId);
        })->whereNull('vatsim_rating')->get()->sortBy('name');
        */

        $users = User::allWithGroup(4);
        $ratings = PilotRating::whereIn('vatsim_rating', [1, 3, 7, 15])->get();


        return view('roster', compact('users', 'ratings'));
    }
}
