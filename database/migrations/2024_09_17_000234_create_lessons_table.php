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
        Schema::create('lessons', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
            $table->unsignedInteger('pilot_rating_id');
       
            $table->foreign('pilot_rating_id')->references('id')->on('pilot_ratings')->onDelete('cascade');
        });

        DB::table('lessons')->insert([
            ['name' => '1 - Familiarization with the Aircraft', 'pilot_rating_id' => 2],
            ['name' => '2 - Preparations For and Actions After Flight', 'pilot_rating_id' => 2],
            ['name' => '3 - Effects of Controls', 'pilot_rating_id' => 2],
            ['name' => '4 - Taxiing', 'pilot_rating_id' => 2],
            ['name' => '5 - Straight and Level Flight', 'pilot_rating_id' => 2],
            ['name' => '6 - Climbing', 'pilot_rating_id' => 2],
            ['name' => '7 - Slow Flight and Stalling', 'pilot_rating_id' => 2],
            ['name' => '8 - Take-off and Climb/ Emergency/Abnormal Procedures', 'pilot_rating_id' => 2],
            ['name' => '9 - Circuit, Approach, and Landing', 'pilot_rating_id' => 2],
            ['name' => '10 - Advanced Turning', 'pilot_rating_id' => 2],
            ['name' => '11 - Navigation: VFR Flight planning', 'pilot_rating_id' => 2],
            ['name' => '12 - NAV at lower levels and DVE(deg visual environment)', 'pilot_rating_id' => 2],
            ['name' => '13 - VFR Radio Navigation', 'pilot_rating_id' => 2],
            ['name' => '14 - Emergency and Abnormal Procedures', 'pilot_rating_id' => 2],
            ['name' => '15 - Basic instrument flying', 'pilot_rating_id' => 2],
            ['name' => '16 - First Solo', 'pilot_rating_id' => 2],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lessons');
    }
};
