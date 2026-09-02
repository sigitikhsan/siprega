<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddDayOffToEmployeeShiftAssignments extends Migration
{
    public function up()
    {
        Schema::table('employee_shift_assignments', function (Blueprint $table) {
            $table->boolean('is_day_off')->default(false)->after('shift_date')->index();
        });
    }

    public function down()
    {
        Schema::table('employee_shift_assignments', function (Blueprint $table) {
            $table->dropColumn('is_day_off');
        });
    }
}
