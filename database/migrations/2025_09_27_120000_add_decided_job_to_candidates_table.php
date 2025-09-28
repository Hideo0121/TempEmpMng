<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('candidates', function (Blueprint $table) {
            $table->unsignedInteger('decided_job_category_id')->nullable()->after('wish_job3_id');

            $table->foreign('decided_job_category_id')
                ->references('id')
                ->on('job_categories')
                ->nullOnDelete();
        });

        $employedCodes = DB::table('candidate_statuses')
            ->select('code')
            ->get()
            ->pluck('code')
            ->map(fn ($code) => mb_strtolower((string) $code))
            ->filter()
            ->unique()
            ->values()
            ->all();

        DB::table('candidates')
            ->when(!empty($employedCodes), function ($query) use ($employedCodes) {
                return $query->whereIn(DB::raw('lower(status_code)'), $employedCodes);
            })
            ->whereNull('decided_job_category_id')
            ->update(['decided_job_category_id' => DB::raw('wish_job1_id')]);
    }

    public function down(): void
    {
        Schema::table('candidates', function (Blueprint $table) {
            $table->dropForeign(['decided_job_category_id']);
            $table->dropColumn('decided_job_category_id');
        });
    }
};
