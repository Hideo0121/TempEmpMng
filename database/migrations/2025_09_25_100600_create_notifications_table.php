<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notifications', function (Blueprint $table) {
            $table->increments('id');
            $table->string('type', 30)->default('reminder');
            $table->unsignedInteger('target_id');
            $table->text('to_addresses');
            $table->text('cc_addresses')->nullable();
            $table->string('subject', 150);
            $table->text('body');
            $table->dateTime('scheduled_for');
            $table->dateTime('sent_at')->nullable();
            $table->string('status', 30)->default('pending');
            $table->text('error_message')->nullable();
            $table->timestamps();

            $table->foreign('target_id')->references('id')->on('interviews')->cascadeOnDelete();
            $table->index(['status', 'scheduled_for']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};
