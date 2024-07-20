<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddCreatedForAndCreatedByToItemsTable extends Migration
{
    public function up()
    {
        Schema::table('items', function (Blueprint $table) {
            $table->unsignedBigInteger('created_for_id')->nullable()->after('name');
            $table->unsignedBigInteger('created_by_id')->nullable()->after('created_for_id');
        });
    }

    public function down()
    {
        Schema::table('items', function (Blueprint $table) {
            $table->dropColumn(['created_for_id', 'created_by_id']);
        });
    }
}
