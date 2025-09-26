<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('candidate_status_histories', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('candidate_id');
            $table->string('old_code', 20)->nullable();
            $table->string('new_code', 20);
            $table->unsignedInteger('changed_by')->nullable();
            $table->text('changed_reason')->nullable();
            $table->dateTime('changed_at');
            $table->timestamps();

            $table->foreign('candidate_id')->references('id')->on('candidates')->cascadeOnDelete();
            $table->foreign('old_code')->references('code')->on('candidate_statuses')->nullOnDelete();
            $table->foreign('new_code')->references('code')->on('candidate_statuses')->restrictOnDelete();
            $table->foreign('changed_by')->references('id')->on('users')->nullOnDelete();

            $table->index(['candidate_id', 'changed_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('candidate_status_histories');
    }
};
