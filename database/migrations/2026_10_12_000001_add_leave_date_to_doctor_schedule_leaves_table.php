<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('doctor_schedule_leaves', function (Blueprint $table): void {
            // Keep undated legacy records until staff explicitly chooses a date.
            $table->date('leave_date')->nullable();
            $table->dropUnique('doctor_schedule_leaves_schedule_unique');
            $table->unique(['doctor_code', 'clinic_code', 'day', 'start_time', 'leave_date'], 'doctor_schedule_leaves_date_unique');
        });
    }

    public function down(): void
    {
        // The legacy schema supports only one recurring leave per schedule.
        $seen = [];
        foreach (DB::table('doctor_schedule_leaves')->orderByDesc('id')->get() as $leave) {
            $key = implode('|', [$leave->doctor_code, $leave->day, $leave->start_time]);
            if (isset($seen[$key])) {
                DB::table('doctor_schedule_leaves')->where('id', $leave->id)->delete();
            }
            $seen[$key] = true;
        }

        Schema::table('doctor_schedule_leaves', function (Blueprint $table): void {
            $table->dropUnique('doctor_schedule_leaves_date_unique');
            $table->dropColumn('leave_date');
            $table->unique(['doctor_code', 'day', 'start_time'], 'doctor_schedule_leaves_schedule_unique');
        });
    }
};
