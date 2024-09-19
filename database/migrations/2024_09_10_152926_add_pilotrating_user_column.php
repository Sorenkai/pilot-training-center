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
        Schema::table('users', function (Blueprint $table) {
            $table->string('pilotrating', 3)->nullable()->after('rating_long');
            $table->string('pilotrating_short', 3)->nullable()->after('pilotrating');
            $table->string('pilotrating_long', 50)->nullable()->after('pilotrating_short');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
