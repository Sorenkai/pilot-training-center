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
        Schema::create('pilot_ratings', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name', 50);
            $table->string('description', 100);
            $table->unsignedInteger('vatsim_rating');
        });

        DB::table('pilot_ratings')->insert([
            ['vatsim_rating' => 0, 'name' => 'P0', 'description' => 'No Pilot Rating'],
            ['vatsim_rating' => 1, 'name' => 'PPL', 'description' => 'Private Pilot License'],
            ['vatsim_rating' => 3, 'name' => 'IR', 'description' => 'Instrument Rating'],
            ['vatsim_rating' => 7, 'name' => 'CMEL', 'description' => 'Commercial Multi-Engine License'],
            ['vatsim_rating' => 15, 'name' => 'ATPL', 'description' => 'Air Transport Pilot License'],
            ['vatsim_rating' => 31, 'name' => 'FI', 'description' => 'Flight Instructor'],
            ['vatsim_rating' => 63, 'name' => 'FE', 'description' => 'Flight Examiner'],

        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pilot_ratings');
    }
};
