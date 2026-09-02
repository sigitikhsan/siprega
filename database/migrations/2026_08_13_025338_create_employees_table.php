<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateEmployeesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('employees', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')
                ->unique()
                ->constrained('users')
                ->onDelete('cascade');

            $table->foreignId('work_schedule_id')
                ->constrained('work_schedules')
                ->onDelete('restrict');

            $table->string('employee_number', 30)->unique();

            $table->string('phone', 20)->nullable();

            $table->string('position', 100)->nullable();

            $table->string('company', 150)->nullable();

            $table->date('join_date')->nullable();

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
        Schema::dropIfExists('employees');
    }
}
