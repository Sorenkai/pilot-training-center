<?php

use App\Http\Controllers\PilotTrainingActivityController;
use App\Models\PilotTraining;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('pilot_training_activity', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('pilot_training_id');
            $table->unsignedBigInteger('triggered_by_id')->nullable();
            $table->enum('type', ['STATUS', 'TYPE', 'INSTRUCTOR', 'PAUSE', 'ENDORSEMENT', 'COMMENT']);
            $table->bigInteger('old_data')->nullable();
            $table->bigInteger('new_data')->nullable();
            $table->text('comment')->nullable();
            $table->timestamps();

            $table->foreign('pilot_training_id')->references('id')->on('pilot_trainings')->onUpdate('CASCADE')->onDelete('CASCADE');
            $table->foreign('triggered_by_id')->references('id')->on('users')->onUpdate('CASCADE')->onDelete('SET NULL');
        });

        foreach (PilotTraining::all() as $training) {
            if (! empty($training->notes)) {
                $ta = PilotTrainingActivityController::create($training->id, 'COMMENT', null, null, null, $training->notes);
                $ta->save(['created_at' => $training->updated_at]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pilot_training_activity');
    }
};
