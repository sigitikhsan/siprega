<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddGpsSecurityFieldsToAttendancesTable extends Migration
{
    public function up()
    {
        Schema::table('attendances', function (Blueprint $table) {
            $table->dateTime('check_in_captured_at')->nullable()->after('check_in_distance');
            $table->boolean('check_in_location_suspicious')->default(false)->after('check_in_captured_at');
            $table->string('check_in_risk_note', 500)->nullable()->after('check_in_location_suspicious');

            $table->dateTime('check_out_captured_at')->nullable()->after('check_out_distance');
            $table->boolean('check_out_location_suspicious')->default(false)->after('check_out_captured_at');
            $table->string('check_out_risk_note', 500)->nullable()->after('check_out_location_suspicious');
        });
    }

    public function down()
    {
        Schema::table('attendances', function (Blueprint $table) {
            $table->dropColumn([
                'check_in_captured_at',
                'check_in_location_suspicious',
                'check_in_risk_note',
                'check_out_captured_at',
                'check_out_location_suspicious',
                'check_out_risk_note',
            ]);
        });
    }
}
