<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('societies', function (Blueprint $table) {
            $table->string('resident_invite_code', 20)->nullable()->unique();
        });

        DB::table('societies')->whereNull('resident_invite_code')->orderBy('id')->each(function (object $society): void {
            do {
                $code = Str::upper(Str::random(12));
            } while (DB::table('societies')->where('resident_invite_code', $code)->exists());

            DB::table('societies')->where('id', $society->id)->update(['resident_invite_code' => $code]);
        });
    }

    public function down(): void
    {
        Schema::table('societies', function (Blueprint $table) {
            $table->dropUnique(['resident_invite_code']);
            $table->dropColumn('resident_invite_code');
        });
    }
};
