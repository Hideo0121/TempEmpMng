<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('candidate_views', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('candidate_id');
            $table->unsignedInteger('user_id');
            $table->dateTime('first_viewed_at');
            $table->dateTime('last_viewed_at');
            $table->unsignedInteger('view_count')->default(1);
            $table->timestamps();

            $table->unique(['candidate_id', 'user_id']);
            $table->foreign('candidate_id')->references('id')->on('candidates')->cascadeOnDelete();
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('candidate_views');
    }
};
