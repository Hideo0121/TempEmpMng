<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('interviews', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('candidate_id');
            $table->dateTime('scheduled_at');
            $table->string('place', 120)->nullable();
            $table->string('result', 30)->nullable();
            $table->text('memo')->nullable();
            $table->boolean('remind_prev_day_sent')->default(false);
            $table->boolean('remind_1h_sent')->default(false);
            $table->boolean('remind_30m_sent')->default(false);
            $table->boolean('remind_30m_enabled')->default(true);
            $table->timestamps();

            $table->foreign('candidate_id')->references('id')->on('candidates')->cascadeOnDelete();
            $table->index('scheduled_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('interviews');
    }
};
