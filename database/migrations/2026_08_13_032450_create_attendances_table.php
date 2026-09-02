<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAttendancesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('attendances', function (Blueprint $table) {
            $table->id(); 

            $table->foreignId('employee_id')
                ->constrained('employees')
                ->onDelete('restrict');

            $table->foreignId('location_id')
                ->constrained('locations')
                ->onDelete('restrict');

            $table->date('attendance_date');

            // Check-in
            $table->dateTime('check_in')->nullable();

            $table->decimal('check_in_latitude', 10, 8)->nullable();
            $table->decimal('check_in_longitude', 11, 8)->nullable();
            $table->decimal('check_in_accuracy', 8, 2)->nullable();
            $table->decimal('check_in_distance', 8, 2)->nullable();

            $table->enum('check_in_status', ['present', 'late'])
                ->nullable()
                ->index();

            $table->text('late_reason')->nullable();

            // Check-out
            $table->dateTime('check_out')->nullable();

            $table->enum('check_out_status', ['normal', 'early_checkout'])
                ->nullable()
                ->index();

            $table->text('early_checkout_reason')->nullable();

            $table->decimal('check_out_latitude', 10, 8)->nullable();
            $table->decimal('check_out_longitude', 11, 8)->nullable();
            $table->decimal('check_out_accuracy', 8, 2)->nullable();
            $table->decimal('check_out_distance', 8, 2)->nullable();

            $table->timestamps();

            // Satu pegawai hanya boleh punya satu absensi per tanggal
            $table->unique(['employee_id', 'attendance_date']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('attendances');
    }
}
