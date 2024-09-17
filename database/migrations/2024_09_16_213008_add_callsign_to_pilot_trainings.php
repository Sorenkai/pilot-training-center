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
        Schema::table('pilot_trainings', function (Blueprint $table) {
            $table->unsignedInteger('callsign_id')->nullable();
            $table->foreign('callsign_id')->references('id')->on('callsigns')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pilot_trainings', function (Blueprint $table) {
            $table->dropForeign(['callsign_id']);

            // Drop the callsign_id column
            $table->dropColumn('callsign_id');
        });
    }
};
