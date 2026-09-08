<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddDashboardQueryIndexes extends Migration
{
    public function up()
    {
        Schema::table('attendances', function (Blueprint $table) {
            $table->index('attendance_date', 'attendances_date_index');
            $table->index('check_in', 'attendances_check_in_index');
            $table->index(['attendance_date', 'check_in_status'], 'attendances_date_status_index');
        });

        Schema::table('leave_requests', function (Blueprint $table) {
            $table->index(['status', 'created_at'], 'leave_requests_status_created_index');
        });
    }

    public function down()
    {
        Schema::table('attendances', function (Blueprint $table) {
            $table->dropIndex('attendances_date_index');
            $table->dropIndex('attendances_check_in_index');
            $table->dropIndex('attendances_date_status_index');
        });

        Schema::table('leave_requests', function (Blueprint $table) {
            $table->dropIndex('leave_requests_status_created_index');
        });
    }
}
