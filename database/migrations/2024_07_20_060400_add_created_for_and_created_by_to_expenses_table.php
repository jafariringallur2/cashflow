<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddCreatedForAndCreatedByToExpensesTable extends Migration
{
    public function up()
    {
        Schema::table('expenses', function (Blueprint $table) {
            $table->unsignedBigInteger('created_for_id')->nullable()->after('note');
            $table->unsignedBigInteger('created_by_id')->nullable()->after('created_for_id');
        });
    }

    public function down()
    {
        Schema::table('expenses', function (Blueprint $table) {
            $table->dropColumn(['created_for_id', 'created_by_id']);
        });
    }
}
