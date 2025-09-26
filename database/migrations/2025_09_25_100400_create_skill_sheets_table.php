<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('skill_sheets', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('candidate_id');
            $table->string('file_path', 255);
            $table->string('original_name', 191);
            $table->unsignedInteger('size_bytes');
            $table->date('received_on')->nullable();
            $table->text('note')->nullable();
            $table->unsignedInteger('uploaded_by')->nullable();
            $table->timestamps();

            $table->foreign('candidate_id')->references('id')->on('candidates')->cascadeOnDelete();
            $table->foreign('uploaded_by')->references('id')->on('users')->nullOnDelete();

            $table->index('candidate_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('skill_sheets');
    }
};
