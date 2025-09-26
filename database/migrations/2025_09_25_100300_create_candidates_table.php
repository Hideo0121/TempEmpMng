<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('candidates', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name', 120);
            $table->string('name_kana', 120);
            $table->unsignedInteger('agency_id');
            $table->unsignedInteger('wish_job1_id')->nullable();
            $table->unsignedInteger('wish_job2_id')->nullable();
            $table->unsignedInteger('wish_job3_id')->nullable();
            $table->date('introduced_on');
            $table->dateTime('visit_candidate1_at')->nullable();
            $table->dateTime('visit_candidate2_at')->nullable();
            $table->dateTime('visit_candidate3_at')->nullable();
            $table->unsignedInteger('handler1_user_id')->nullable();
            $table->unsignedInteger('handler2_user_id')->nullable();
            $table->unsignedInteger('transport_cost_day')->nullable();
            $table->unsignedInteger('transport_cost_month')->nullable();
            $table->text('other_conditions')->nullable();
            $table->string('status_code', 20);
            $table->date('status_changed_on')->nullable();
            $table->date('start_on')->nullable();
            $table->unsignedInteger('created_by')->nullable();
            $table->unsignedInteger('updated_by')->nullable();
            $table->timestamps();

            $table->foreign('agency_id')->references('id')->on('agencies')->restrictOnDelete();
            $table->foreign('wish_job1_id')->references('id')->on('job_categories')->nullOnDelete();
            $table->foreign('wish_job2_id')->references('id')->on('job_categories')->nullOnDelete();
            $table->foreign('wish_job3_id')->references('id')->on('job_categories')->nullOnDelete();
            $table->foreign('handler1_user_id')->references('id')->on('users')->nullOnDelete();
            $table->foreign('handler2_user_id')->references('id')->on('users')->nullOnDelete();
            $table->foreign('status_code')->references('code')->on('candidate_statuses')->restrictOnDelete();
            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('updated_by')->references('id')->on('users')->nullOnDelete();

            $table->index(['status_code', 'introduced_on']);
            $table->index('status_changed_on');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('candidates');
    }
};
