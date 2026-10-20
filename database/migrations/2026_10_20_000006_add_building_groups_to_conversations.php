<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('conversations', function (Blueprint $table) {
            $table->foreignId('tower_id')->nullable()->after('type')->constrained('towers')->nullOnDelete();
            $table->unsignedBigInteger('group_admin_id')->nullable()->after('created_by')->index();
            $table->unique(['society_id', 'tower_id']);
        });
    }

    public function down(): void
    {
        Schema::table('conversations', function (Blueprint $table) {
            $table->dropUnique(['society_id', 'tower_id']);
            $table->dropForeign(['tower_id']);
            $table->dropIndex(['group_admin_id']);
            $table->dropColumn(['tower_id', 'group_admin_id']);
        });
    }
};
