<?php

use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::table(Config::get('settings.table'))->insert([
            ['key' => 'ptdCallsign', 'value' => 'SPT'],
            ['key' => 'ptmEmail', 'value' => 'jere.heiskanen@vatsim-scandinavia.org'],
            ['key' => 'ptmCID', 'value' => ''],
            ['key' => 'linkWiki', 'value' => 'https://wiki.vatsim-scandinavia.org/'],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::raw('DELETE FROM ' . Config::get('settings.table') . ' WHERE key = `ptdCallsign`');
        DB::raw('DELETE FROM ' . Config::get('settings.table') . ' WHERE key = `ptmEmail`');
        DB::raw('DELETE FROM ' . Config::get('settings.table') . ' WHERE key = `ptmCID`');
        DB::raw('DELETE FROM ' . Config::get('settings.table') . ' WHERE key = `linkWiki`');
    }
};
