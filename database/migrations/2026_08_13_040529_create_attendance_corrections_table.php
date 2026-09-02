<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAttendanceCorrectionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('attendance_corrections', function (Blueprint $table) {
            $table->id();

            $table->foreignId('attendance_id')
                ->constrained('attendances')
                ->onDelete('restrict');

            $table->foreignId('corrected_by')
                ->constrained('users')
                ->onDelete('restrict');

            $table->dateTime('old_check_in')
                ->nullable();

            $table->dateTime('new_check_in')
                ->nullable();

            $table->dateTime('old_check_out')
                ->nullable();

            $table->dateTime('new_check_out')
                ->nullable();

            $table->text('reason');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('attendance_corrections');
    }
}
