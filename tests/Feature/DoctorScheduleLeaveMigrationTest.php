<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class DoctorScheduleLeaveMigrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_upgrade_preserves_undated_records_and_rollback_restores_legacy_uniqueness(): void
    {
        $migration = require database_path('migrations/2026_10_12_000001_add_leave_date_to_doctor_schedule_leaves_table.php');
        $schedule = ['doctor_code' => 'D001', 'clinic_code' => 'ANA', 'day' => 'SENIN', 'start_time' => '08:00'];
        DB::table('doctor_schedule_leaves')->insert([
            [...$schedule, 'leave_date' => '2026-10-12'],
            [...$schedule, 'leave_date' => '2026-10-19'],
        ]);

        try {
            $migration->down();
            $this->assertFalse(Schema::hasColumn('doctor_schedule_leaves', 'leave_date'));
            $this->assertDatabaseCount('doctor_schedule_leaves', 1);
            $this->assertTrue(collect(Schema::getIndexes('doctor_schedule_leaves'))
                ->contains('name', 'doctor_schedule_leaves_schedule_unique'));

            $migration->up();
            $this->assertDatabaseHas('doctor_schedule_leaves', [...$schedule, 'leave_date' => null]);
            DB::table('doctor_schedule_leaves')->insert([
                [...$schedule, 'leave_date' => '2026-10-12'],
                [...$schedule, 'leave_date' => '2026-10-19'],
            ]);
            $this->assertDatabaseCount('doctor_schedule_leaves', 3);
        } finally {
            if (! Schema::hasColumn('doctor_schedule_leaves', 'leave_date')) {
                $migration->up();
            }
        }
    }
}
