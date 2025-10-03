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
        Schema::create('recruitment_info', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('job_category_id');
            $table->unsignedInteger('planned_hires')->default(0);
            $table->text('comment')->nullable();
            $table->timestamps();

            $table->unique('job_category_id');
            $table->foreign('job_category_id')
                ->references('id')
                ->on('job_categories')
                ->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('recruitment_info');
    }
};
