<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class AddSimpleShiftScheduling extends Migration
{
    public function up()
    {
        Schema::table('work_schedules', function (Blueprint $table) {
            $table->enum('shift_type', ['fixed', 'day', 'night'])->default('fixed')->after('name')->index();
        });

        Schema::create('employee_shift_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->onDelete('cascade');
            $table->foreignId('work_schedule_id')->constrained('work_schedules')->onDelete('restrict');
            $table->date('shift_date')->index();
            $table->string('notes', 255)->nullable();
            $table->timestamps();
            $table->unique(['employee_id', 'shift_date']);
        });

        Schema::table('attendances', function (Blueprint $table) {
            $table->foreignId('work_schedule_id')->nullable()->after('location_id')->constrained('work_schedules')->onDelete('restrict');
            $table->foreignId('shift_assignment_id')->nullable()->after('work_schedule_id')->constrained('employee_shift_assignments')->onDelete('set null');
        });

        DB::table('attendances')->orderBy('id')->chunkById(500, function ($attendances) {
            foreach ($attendances as $attendance) {
                $scheduleId = DB::table('employees')->where('id', $attendance->employee_id)->value('work_schedule_id');
                DB::table('attendances')->where('id', $attendance->id)->update(['work_schedule_id' => $scheduleId]);
            }
        });
    }

    public function down()
    {
        Schema::table('attendances', function (Blueprint $table) {
            $table->dropConstrainedForeignId('shift_assignment_id');
            $table->dropConstrainedForeignId('work_schedule_id');
        });
        Schema::dropIfExists('employee_shift_assignments');
        Schema::table('work_schedules', function (Blueprint $table) {
            $table->dropColumn('shift_type');
        });
    }
}
