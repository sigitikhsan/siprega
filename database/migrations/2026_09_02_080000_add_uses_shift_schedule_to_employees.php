<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddUsesShiftScheduleToEmployees extends Migration
{
    public function up()
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->boolean('uses_shift_schedule')->default(false)->after('work_schedule_id')->index();
        });
    }

    public function down()
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropColumn('uses_shift_schedule');
        });
    }
}
