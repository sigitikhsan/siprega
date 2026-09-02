<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

class RemoveAuditLogsTable extends Migration
{
    public function up()
    {
        Schema::dropIfExists('audit_logs');
    }

    public function down()
    {
        // Audit log telah dikeluarkan dari kebutuhan aplikasi.
    }
}
