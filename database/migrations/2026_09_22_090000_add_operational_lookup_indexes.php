<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AddOperationalLookupIndexes extends Migration
{
    public function up()
    {
        if (!$this->indexExists('attendances', 'attendances_employee_open_lookup_index')) {
            Schema::table('attendances', function (Blueprint $table) {
                $table->index(
                    ['employee_id', 'check_out', 'check_in'],
                    'attendances_employee_open_lookup_index'
                );
            });
        }

        if (!$this->indexExists('leave_requests', 'leave_requests_employee_status_start_index')) {
            Schema::table('leave_requests', function (Blueprint $table) {
                $table->index(
                    ['employee_id', 'status', 'start_date'],
                    'leave_requests_employee_status_start_index'
                );
            });
        }

        if ($this->indexExists('leave_requests', 'leave_requests_employee_fk_support_index')) {
            Schema::table('leave_requests', function (Blueprint $table) {
                $table->dropIndex('leave_requests_employee_fk_support_index');
            });
        }
    }

    public function down()
    {
        if ($this->indexExists('attendances', 'attendances_employee_open_lookup_index')) {
            Schema::table('attendances', function (Blueprint $table) {
                $table->dropIndex('attendances_employee_open_lookup_index');
            });
        }

        if ($this->indexExists('leave_requests', 'leave_requests_employee_status_start_index')) {
            // MySQL dapat memakai indeks gabungan untuk menopang foreign key.
            // Sediakan pengganti sebelum indeks gabungan dilepas saat rollback.
            if (!$this->firstColumnIndexExists('leave_requests', 'employee_id', 'leave_requests_employee_status_start_index')) {
                Schema::table('leave_requests', function (Blueprint $table) {
                    $table->index('employee_id', 'leave_requests_employee_fk_support_index');
                });
            }

            Schema::table('leave_requests', function (Blueprint $table) {
                $table->dropIndex('leave_requests_employee_status_start_index');
            });
        }
    }

    private function indexExists($table, $index)
    {
        return DB::table('information_schema.statistics')
            ->where('table_schema', DB::raw('DATABASE()'))
            ->where('table_name', $table)
            ->where('index_name', $index)
            ->exists();
    }

    private function firstColumnIndexExists($table, $column, $exceptIndex)
    {
        return DB::table('information_schema.statistics')
            ->where('table_schema', DB::raw('DATABASE()'))
            ->where('table_name', $table)
            ->where('column_name', $column)
            ->where('seq_in_index', 1)
            ->where('index_name', '<>', $exceptIndex)
            ->exists();
    }
}
