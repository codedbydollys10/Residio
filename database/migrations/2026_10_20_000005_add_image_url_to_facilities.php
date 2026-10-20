<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('facilities', function (Blueprint $table) {
            $table->string('image_url', 2048)->nullable()->after('image');
        });

        DB::table('facilities')
            ->whereNotNull('image')
            ->where('image', '!=', '')
            ->update(['image_url' => DB::raw('image')]);

        foreach ([
            'clubhouse' => 'Clubhouse',
            'gym' => 'Gym',
            'pool' => 'Swimming Pool',
            'hall' => 'Community Hall',
            'tennis' => 'Tennis Court',
        ] as $image => $name) {
            DB::table('facilities')
                ->whereNull('image_url')
                ->whereRaw('LOWER(name) = ?', [strtolower($name)])
                ->update(['image_url' => "/images/facilities/{$image}.jpg"]);
        }
    }

    public function down(): void
    {
        Schema::table('facilities', function (Blueprint $table) {
            $table->dropColumn('image_url');
        });
    }
};
