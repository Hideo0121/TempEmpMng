<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('candidate_statuses', function (Blueprint $table) {
            $table->boolean('is_employed_state')->default(false)->after('is_active');
        });

        DB::table('candidate_statuses')
            ->whereIn('code', ['ST03', 'st03', 's_t03'])
            ->update(['is_employed_state' => true]);
    }

    public function down(): void
    {
        Schema::table('candidate_statuses', function (Blueprint $table) {
            $table->dropColumn('is_employed_state');
        });
    }
};
