<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class ReactivateWorkSchedulesStillInUse extends Migration
{
    public function up()
    {
        $scheduleIds = DB::table('employees')
            ->whereNotNull('work_schedule_id')
            ->pluck('work_schedule_id')
            ->merge(
                DB::table('employee_shift_assignments')
                    ->whereDate('shift_date', '>=', now()->toDateString())
                    ->pluck('work_schedule_id')
            )
            ->unique()
            ->values();

        if ($scheduleIds->isNotEmpty()) {
            DB::table('work_schedules')->whereIn('id', $scheduleIds)->update([
                'status' => 'active',
                'updated_at' => now(),
            ]);
        }
    }

    public function down()
    {
        // Status sebelumnya tidak dapat dipastikan dengan aman, jadi tidak dibalik.
    }
}
