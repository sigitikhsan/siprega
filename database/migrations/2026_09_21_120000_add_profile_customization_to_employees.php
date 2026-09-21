<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddProfileCustomizationToEmployees extends Migration
{
    public function up()
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->string('avatar_path')->nullable()->after('phone');
            $table->string('profile_accent', 7)->default('#2563eb')->after('avatar_path');
            $table->string('bio', 240)->nullable()->after('profile_accent');
        });
    }

    public function down()
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropColumn(['avatar_path', 'profile_accent', 'bio']);
        });
    }
}
