<?php

namespace Database\Seeders;

use App\Helpers\FactoryHelper;
use App\Helpers\TrainingStatus;
use App\Models\Callsign;
use App\Models\Endorsement;
use App\Models\Group;
use App\Models\PilotRating;
use App\Models\PilotTraining;
use App\Models\PilotTrainingReport;
use App\Models\Position;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Create the default dev accounts corresponding to VATSIM Connect
        for ($i = 1; $i <= 11; $i++) {
            $name_first = 'Web';
            $name_last = 'X';
            $email = 'auth.dev' . $i . '@vatsim.net';

            $rating_id = 1;
            $group = null;

            switch ($i) {
                case 1:
                    $name_last = 'One';
                    $prating_id = 0;
                    $prating_short = 'P0';
                    $prating_long = 'No Pilot Rating';
                    break;
                case 2:
                    $name_last = 'Two';
                    $rating_id = 2;
                    $prating_id = 1;
                    $prating_short = 'PPL';
                    $prating_long = 'Private Pilot License';
                    break;
                case 3:
                    $name_last = 'Three';
                    $rating_id = 3;
                    $prating_id = 1;
                    $prating_short = 'PPL';
                    $prating_long = 'Private Pilot License';
                    break;
                case 4:
                    $name_last = 'Four';
                    $rating_id = 4;
                    $prating_id = 3;
                    $prating_short = 'IR';
                    $prating_long = 'Instrument Rating';
                    break;
                case 5:
                    $name_last = 'Five';
                    $rating_id = 5;
                    $prating_id = 3;
                    $prating_short = 'IR';
                    $prating_long = 'Instrument Rating';
                    break;
                case 6:
                    $name_last = 'Six';
                    $rating_id = 7;
                    $prating_id = 3;
                    $prating_short = 'IR';
                    $prating_long = 'Instrument Rating';
                    break;
                case 7:
                    $name_last = 'Seven';
                    $rating_id = 8;
                    $prating_id = 7;
                    $prating_short = 'CMEL';
                    $prating_long = 'Commercial Multi-Engine License';
                    $group = 3;
                    break;
                case 8:
                    $name_last = 'Eight';
                    $rating_id = 10;
                    $prating_id = 7;
                    $prating_short = 'CMEL';
                    $prating_long = 'Commercial Multi-Engine License';
                    $group = 3;
                    break;
                case 9:
                    $name_last = 'Nine';
                    $rating_id = 11;
                    $prating_id = 15;
                    $prating_short = 'ATPL';
                    $prating_long = 'Air Transport Pilot License';
                    $group = 4;
                    break;
                case 10:
                    $name_first = 'Team';
                    $name_last = 'Web';
                    $rating_id = 12;
                    $prating_id = 15;
                    $prating_short = 'ATPL';
                    $prating_long = 'Air Transport Pilot License';
                    $email = 'noreply@vatsim.net';
                    $group = 4;
                    break;
                case 11:
                    $name_first = 'Suspended';
                    $name_last = 'User';
                    $rating_id = 0;
                    $email = 'suspended@vatsim.net';
                    $group = 4;
                    break;
            }

            User::factory()->create([
                'id' => 10000000 + $i,
                'email' => $email,
                'first_name' => $name_first,
                'last_name' => $name_last,
                'rating' => $rating_id,
                'rating_short' => FactoryHelper::shortRating($rating_id),
                'rating_long' => FactoryHelper::longRating($rating_id),
                'pilotrating' => $prating_id,
                'pilotrating_short' => $prating_short,
                'pilotrating_long' => $prating_long,
                'region' => 'EMEA',
                'division' => 'EUD',
                'subdivision' => 'SCA',
            ])->groups()->attach(Group::find($group), ['area_id' => 2]);
        }

        // Create random Scandinavian users
        for ($i = 12; $i <= 125; $i++) {
            User::factory()->create([
                'id' => 10000000 + $i,
                'region' => 'EMEA',
                'division' => 'EUD',
                'subdivision' => 'SCA',
            ]);
        }

        // Create random users
        for ($i = 126; $i <= 250; $i++) {
            User::factory()->create([
                'id' => 10000000 + $i,
            ]);
        }

        // Populate trainings and other of the Scandinavian users
        for ($i = 1; $i <= rand(100, 125); $i++) {
            $training = PilotTraining::factory()->create();
            $training->pilotRatings()->attach(PilotRating::where('vatsim_rating', '>', 0)->inRandomOrder()->first());

            self::assignCallsign($training);
            // Give all non-queued trainings a mentor
            if ($training->status > TrainingStatus::IN_QUEUE->value) {
                $training->instructors()->attach(
                    User::whereHas('groups', function ($query) {
                        $query->where('id', 4);
                    })->inRandomOrder()->first(),
                    ['expire_at' => now()->addYears(5)]
                );
                PilotTrainingReport::factory()->create([
                    'pilot_training_id' => $training->id,
                    'written_by_id' => $training->instructors()->inRandomOrder()->first(),
                ]);
            }

            /*
            // Give all exam awaiting trainings a solo endorsement
            if ($training->status == TrainingStatus::AWAITING_EXAM->value) {
                if (! Endorsement::where('user_id', $training->user_id)->exists()) {
                    $soloEndorsement = Endorsement::factory()->create([
                        'user_id' => $training->user_id,
                        'type' => 'SOLO',
                        'valid_to' => Carbon::now()->addWeeks(4),
                    ]);

                    // Add position for solo
                    $soloEndorsement->positions()->save(Position::where('rating', '>', 1)->inRandomOrder()->first());
                }

                // And some a exam result
                if ($i % 7 == 0) {
                    TrainingExamination::factory()->create([
                        'training_id' => $training->id,
                        'examiner_id' => User::where('id', '!=', $training->user_id)->inRandomOrder()->first(),
                    ]);
                }
            }*/
        }
    }

    private function assignCallsign(PilotTraining $pilotTraining)
    {
        $baseNumber = 000;

        // level = rating id - 1 cause ratings start at P0
        $level = $pilotTraining->pilotRatings()->first()->id - 1;
        $callsignPrefix = 'SPT';

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
            'user_id' => $pilotTraining->user_id,
        ]);

        $pilotTraining->callsign_id = $callsign->id;
        $pilotTraining->save();

        return $callsign;
    }
}
