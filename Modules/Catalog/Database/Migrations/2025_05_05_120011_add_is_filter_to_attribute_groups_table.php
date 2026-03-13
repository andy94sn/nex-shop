<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::table('attribute_groups', function (Blueprint $table) {
            $table->boolean('is_filter')->default(false)->after('is_default');
        });
    }

    public function down()
    {
        Schema::table('attribute_groups', function (Blueprint $table) {
            $table->dropColumn('is_filter');
        });
    }
};
