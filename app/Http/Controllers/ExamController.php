<?php

namespace App\Http\Controllers;

use App\Models\Exam;
use App\Models\User;
use App\Models\PilotRating;
use Illuminate\Http\Request;

class ExamController extends Controller
{
    public function create($prefillUserId = null)
    {
        $this->authorize('create', Exam::class);

        if ($prefillUserId) {
            $users = collect(User::where('id', $prefillUserId)->get());
        } else {
            $users = User::all();
        }

        $ratings = PilotRating::whereIn('vatsim_rating', [1,3,7,15,31])->get();


        return view('exam.create', compact('users', 'ratings', 'prefillUserId'));
    }

    public function store(Request $request)
    {
        $this->authorize('store', [Exam::class]);

        $data = [];
        $data = request()->validate([
            'user' => 'required|numeric|exists:App\Models\User,id',
            'rating' => 'required',
            'url' => 'required|url',
            'score' => 'required|numeric|min:0|max:100',
        ]);

        $user = User::find($data['user']);
        $rating = PilotRating::find($data['rating']);
        
        $exam = Exam::create([
            'pilot_rating_id' => $rating->id,
            'url' => $data['url'],
            'score' => $data['score'],
            'user_id' => $user->id,
            'issued_by' => \Auth::user()->id,
        ]);

        return redirect()->intended(route('exam.create'))->withSuccess($user->name . "'s exam result saved");

    }
}
