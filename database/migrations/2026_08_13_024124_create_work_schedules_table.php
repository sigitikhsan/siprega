<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateWorkSchedulesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('work_schedules', function (Blueprint $table) {
            $table->id();

            $table->string('name', 100);

            $table->time('check_in_start');
            $table->time('check_in_end');

            $table->unsignedSmallInteger('late_tolerance');

            $table->time('check_out_start');

            $table->enum('status', ['active', 'inactive'])
                ->default('active')
                ->index();

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
        Schema::dropIfExists('work_schedules');
    }
}
