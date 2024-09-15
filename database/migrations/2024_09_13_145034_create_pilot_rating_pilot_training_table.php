<?php

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
        Schema::create('pilot_rating_pilot_training', function (Blueprint $table) {
            $table->primary(['pilot_rating_id', 'pilot_training_id']);

            $table->unsignedInteger('pilot_rating_id');
            $table->unsignedBigInteger('pilot_training_id');

            $table->foreign('pilot_rating_id')->references('id')->on('pilot_ratings');
            $table->foreign('pilot_training_id')->references('id')->on('pilot_trainings')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pilot_rating_pilot_training');
    }
};
