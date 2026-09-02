<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateLeaveRequestsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('leave_requests', function (Blueprint $table) {
            $table->id();

            $table->foreignId('employee_id')
                ->constrained('employees')
                ->onDelete('restrict');

            $table->enum('type', ['permission', 'sick'])
                ->index();

            $table->date('start_date')
                ->index();

            $table->unsignedTinyInteger('duration');

            $table->text('reason');

            $table->string('attachment', 255)
                ->nullable();

            $table->enum('status', ['pending', 'approved', 'rejected'])
                ->default('pending')
                ->index();

            $table->foreignId('reviewed_by')
                ->nullable()
                ->constrained('users')
                ->onDelete('set null');

            $table->dateTime('reviewed_at')
                ->nullable();

            $table->text('review_note')
                ->nullable();

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
        Schema::dropIfExists('leave_requests');
    }
}
