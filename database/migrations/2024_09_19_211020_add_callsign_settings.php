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
        DB::table(Config::get('settings.table'))->insert([
            ['key' => 'ptdCallsign', 'value' => 'SPT'],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::raw('DELETE FROM ' . Config::get('settings.table') . ' WHERE key = `ptdCallsign`');
    }
};
