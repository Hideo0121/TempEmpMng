<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('candidates', function (Blueprint $table) {
            $table->dateTime('employment_start_at')->nullable()->after('start_on');
            $table->string('assignment_worker_code_a', 50)->nullable()->after('employment_start_at');
            $table->string('assignment_worker_code_b', 50)->nullable()->after('assignment_worker_code_a');
            $table->string('assignment_locker', 50)->nullable()->after('assignment_worker_code_b');

            $table->index('employment_start_at');
            $table->index('assignment_worker_code_a');
            $table->index('assignment_worker_code_b');
        });
    }

    public function down(): void
    {
        Schema::table('candidates', function (Blueprint $table) {
            $table->dropIndex(['employment_start_at']);
            $table->dropIndex(['assignment_worker_code_a']);
            $table->dropIndex(['assignment_worker_code_b']);

            $table->dropColumn([
                'employment_start_at',
                'assignment_worker_code_a',
                'assignment_worker_code_b',
                'assignment_locker',
            ]);
        });
    }
};
