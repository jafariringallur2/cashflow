<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddCreatedForAndCreatedByToIncomesTable extends Migration
{
    public function up()
    {
        Schema::table('incomes', function (Blueprint $table) {
            $table->unsignedBigInteger('created_for_id')->nullable()->after('note');
            $table->unsignedBigInteger('created_by_id')->nullable()->after('created_for_id');
        });
    }

    public function down()
    {
        Schema::table('incomes', function (Blueprint $table) {
            $table->dropColumn(['created_for_id', 'created_by_id']);
        });
    }
}
